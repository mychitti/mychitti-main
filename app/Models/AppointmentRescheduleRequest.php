<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A hospital asking a patient to move an appointment, and the patient's answer.
 *
 * The clinic already had a reschedule: staff picked a new time and the appointment moved. That is
 * the right tool when the patient is standing at the counter, and the wrong one for every other
 * case — a doctor called away on Tuesday means twenty patients whose appointment silently changed
 * under them, half of whom turn up at the old time anyway.
 *
 * So this is the other half: a proposal with an answer attached. Nothing about the appointment
 * changes when the request goes out. It changes when the patient taps Confirm, and if they never
 * do, the original time stands — which is the honest outcome, because an unanswered request is
 * exactly a patient who has not agreed to anything.
 *
 * The token is the patient's whole credential, the same bargain the record-share links make: a
 * patient has no login, the link arrives on a phone number the hospital already verified by
 * sending to it, and it can do precisely two things to one appointment.
 */
class AppointmentRescheduleRequest extends Model
{
    protected $fillable = [
        'store_id', 'appointment_id', 'patient_id',
        'from_date', 'from_time', 'to_date', 'to_time', 'slot_id',
        'note', 'token', 'status', 'sent_to', 'sent_at', 'requested_by',
        'responded_at', 'response_note', 'new_appointment_id', 'expires_at', 'views',
    ];

    protected $casts = [
        'from_date'    => 'date',
        'to_date'      => 'date',
        'sent_at'      => 'datetime',
        'responded_at' => 'datetime',
        'expires_at'   => 'datetime',
        'views'        => 'integer',
    ];

    protected $hidden = ['token'];

    /**
     * Where a request can get to.
     *
     * 'withdrawn' is the hospital taking it back — the doctor is available after all — and is kept
     * apart from 'declined', which is the patient's answer. Reading a month later, "we changed our
     * minds" and "the patient said no" are not the same fact about that patient.
     */
    const STATUSES = ['pending', 'accepted', 'declined', 'withdrawn', 'expired'];

    const STATUS_LABELS = [
        'pending'   => 'Waiting for the patient',
        'accepted'  => 'Patient confirmed',
        'declined'  => 'Patient can’t make it',
        'withdrawn' => 'Withdrawn by the hospital',
        'expired'   => 'Expired unanswered',
    ];

    const STATUS_COLOURS = [
        'pending'   => ['#92400e', '#fef3c7'],
        'accepted'  => ['#166534', '#dcfce7'],
        'declined'  => ['#991b1b', '#fee2e2'],
        'withdrawn' => ['#475569', '#f1f5f9'],
        'expired'   => ['#6b7280', '#f3f4f6'],
    ];

    public static function ensureSchema(): void
    {
        if (Schema::hasTable('appointment_reschedule_requests')) {
            return;
        }

        DB::statement("CREATE TABLE `appointment_reschedule_requests` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT UNSIGNED NOT NULL,
            `appointment_id` BIGINT UNSIGNED NOT NULL,
            `patient_id` BIGINT UNSIGNED NULL,
            `from_date` DATE NULL,
            `from_time` VARCHAR(20) NULL,
            `to_date` DATE NOT NULL,
            `to_time` VARCHAR(20) NOT NULL,
            `slot_id` BIGINT UNSIGNED NULL,
            `note` VARCHAR(500) NULL,
            `token` VARCHAR(64) NOT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
            `sent_to` VARCHAR(32) NULL,
            `sent_at` TIMESTAMP NULL,
            `requested_by` BIGINT UNSIGNED NULL,
            `responded_at` TIMESTAMP NULL,
            `response_note` VARCHAR(500) NULL,
            `new_appointment_id` BIGINT UNSIGNED NULL,
            `expires_at` TIMESTAMP NULL,
            `views` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `arr_token` (`token`),
            KEY `arr_appointment` (`appointment_id`),
            KEY `arr_store_status` (`store_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function newAppointment()
    {
        return $this->belongsTo(Appointment::class, 'new_appointment_id');
    }

    /** 48 hex characters, the same shape and strength the record-share links use. */
    public static function mintToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    /** The moment being proposed, as one value the whole flow can compare against. */
    public function proposedAt(): Carbon
    {
        $time = trim((string) $this->to_time) ?: '00:00';

        return Carbon::parse($this->to_date->toDateString() . ' ' . $time);
    }

    /**
     * Still waiting on the patient.
     *
     * A pending request whose proposed time has been and gone is not waiting for anybody — it
     * reads as expired here rather than being swept by a job, because the only thing that makes
     * it stale is the clock, and the clock is readable at any time.
     */
    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'pending' && !$this->getIsLapsedAttribute();
    }

    public function getIsLapsedAttribute(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $deadline = $this->expires_at ?: $this->proposedAt();

        return now()->greaterThan($deadline);
    }

    /** What the status is called, after the clock has had its say. */
    public function stateLabel(): string
    {
        $state = $this->is_lapsed ? 'expired' : $this->status;

        return self::STATUS_LABELS[$state] ?? ucfirst($state);
    }

    public function stateColour(): array
    {
        $state = $this->is_lapsed ? 'expired' : $this->status;

        return self::STATUS_COLOURS[$state] ?? ['#475569', '#f1f5f9'];
    }

    /** "Tue 2 Sep 2026 at 04:30 PM" — the one phrasing used on the page and in the message. */
    public static function when($date, $time): string
    {
        if (!$date) {
            return '—';
        }

        $when = Carbon::parse($date)->format('D j M Y');

        return trim((string) $time) !== ''
            ? $when . ' at ' . Carbon::parse($time)->format('h:i A')
            : $when;
    }

    public function proposedLabel(): string
    {
        return static::when($this->to_date, $this->to_time);
    }

    public function currentLabel(): string
    {
        return static::when($this->from_date, $this->from_time);
    }
}
