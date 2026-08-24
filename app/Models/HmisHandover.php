<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * One physical exchange between the clinic and somebody from outside it — a runner who walks in,
 * takes a tray of impressions away, or puts a printed report on the counter.
 *
 * This is an event log, not a set of columns on the thing being carried. A single crown goes out
 * as an impression, comes back for a trial, goes out again, comes back finished, and sometimes
 * goes out a third time for a remake. Five exchanges, five different people, five different days.
 * Recording that on the job itself means the second pickup overwrites the first and the trail is
 * gone by the time anyone asks for it — which is always weeks later, when something is missing.
 *
 * Deliberately shared between two tables that have nothing else in common. A dental lab collecting
 * an impression and a pathology courier collecting blood tubes are the same event at the counter:
 * a stranger is standing there, something is changing hands, and somebody has to be able to prove
 * afterwards who it was. `subject_type` is a slug rather than a class name for the same reason
 * HospitalActivityLog uses one — the row has to stay readable after a model is renamed or moved.
 */
class HmisHandover extends Model
{
    protected $fillable = [
        'store_id', 'subject_type', 'subject_id', 'patient_id',
        'direction', 'purpose', 'item_count', 'item_note',
        'lab_vendor_id', 'lab_name', 'lab_phone',
        'person_name', 'person_phone', 'person_id_ref',
        'staff_name', 'causer_type', 'causer_id',
        'happened_at', 'verify_method', 'verify_state',
        'otp_hash', 'otp_sent_to', 'otp_sent_at', 'otp_verified_at', 'otp_attempts',
        'signature_path', 'photo_path',
        'dispatch_expected', 'override_reason', 'notes', 'ip',
    ];

    protected $casts = [
        'happened_at'       => 'datetime',
        'otp_sent_at'       => 'datetime',
        'otp_verified_at'   => 'datetime',
        'item_count'        => 'integer',
        'otp_attempts'      => 'integer',
        'dispatch_expected' => 'boolean',
    ];

    protected $hidden = ['otp_hash'];

    /**
     * What can change hands, and what recording an exchange does to it.
     *
     * `advances` maps a direction onto the stage the subject should move to once the exchange is
     * recorded, so staff record the physical event and the paperwork follows. Recording that a
     * runner took the impressions IS the job being sent; asking someone to also remember to change
     * a dropdown afterwards is how a job sits at the wrong stage for a fortnight.
     *
     * A pathology order has no stage for work coming back — the report arriving is not the order
     * being finished, it is a document that still has to be read and vouched for — so 'in' maps to
     * null and the arrival lives in the custody trail alone.
     */
    const SUBJECTS = [
        'opd_lab_work' => [
            'label'    => 'Lab work',
            'model'    => OpdLabWork::class,
            'advances' => ['out' => 'sent', 'in' => 'received'],
        ],
        'lab_order' => [
            'label'    => 'Lab order',
            'model'    => LabOrder::class,
            'advances' => ['out' => 'in_progress', 'in' => null],
        ],
    ];

    /**
     * Which way the thing moved, and how hard we check.
     *
     * The asymmetry is the whole point. Work leaving the building is low risk — nobody is
     * impersonating a lab runner to steal an alginate impression, and the confirmation that goes
     * to the lab the moment it leaves means they would know within seconds if someone did. Work
     * ARRIVING is the dangerous direction: a stranger handing over a forged report gets a wrong
     * number into a patient's chart, and no signature on a screen would have caught it.
     */
    const DIRECTIONS = [
        'out' => [
            'label'  => 'Collected from us',
            'verb'   => 'collected',
            'strict' => false,
            'colour' => ['#92400e', '#fef3c7'],
        ],
        'in' => [
            'label'  => 'Delivered to us',
            'verb'   => 'delivered',
            'strict' => true,
            'colour' => ['#065f46', '#d1fae5'],
        ],
    ];

    /**
     * How much the record is actually worth as evidence.
     *
     * 'provisional' exists because the alternative is worse. A hard block when the lab's phone is
     * out of coverage does not stop the handover happening — the runner still puts the report on
     * the counter and leaves — it only stops it being written down, and then there is no record at
     * all. So the exchange is always recordable, and an unconfirmed one stays visibly unconfirmed
     * for good, with the report kept out of the patient's results until a person vouches for it.
     */
    const STATES = [
        'verified'    => ['label' => 'Verified with lab', 'colour' => ['#166534', '#dcfce7']],
        'provisional' => ['label' => 'Not yet confirmed', 'colour' => ['#9a3412', '#ffedd5']],
        'recorded'    => ['label' => 'Recorded',          'colour' => ['#475569', '#f1f5f9']],
    ];

    /** Minutes a code stays good for. Long enough to ring the lab, short enough to be worth little. */
    const OTP_TTL_MINUTES = 15;

    /** Wrong codes allowed before the attempt is dead and a fresh one has to be sent. */
    const OTP_MAX_ATTEMPTS = 5;

    /** Where signatures and counter photos land on the public disk (a DO Spaces mount). */
    const MEDIA_DIR = 'hmis-handover';

    public static function ensureSchema(): void
    {
        if (Schema::hasTable('hmis_handovers')) {
            static::ensureColumns();
            return;
        }

        DB::statement("CREATE TABLE `hmis_handovers` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT UNSIGNED NOT NULL,
            `subject_type` VARCHAR(40) NOT NULL,
            `subject_id` BIGINT UNSIGNED NOT NULL,
            `patient_id` BIGINT UNSIGNED NULL,
            `direction` VARCHAR(10) NOT NULL,
            `purpose` VARCHAR(120) NULL,
            `item_count` INT NULL,
            `item_note` VARCHAR(255) NULL,
            `lab_vendor_id` BIGINT UNSIGNED NULL,
            `lab_name` VARCHAR(190) NULL,
            `lab_phone` VARCHAR(40) NULL,
            `person_name` VARCHAR(150) NOT NULL,
            `person_phone` VARCHAR(40) NULL,
            `person_id_ref` VARCHAR(80) NULL,
            `staff_name` VARCHAR(150) NULL,
            `causer_type` VARCHAR(30) NULL,
            `causer_id` BIGINT UNSIGNED NULL,
            `happened_at` TIMESTAMP NULL,
            `verify_method` VARCHAR(20) NOT NULL DEFAULT 'none',
            `verify_state` VARCHAR(20) NOT NULL DEFAULT 'recorded',
            `otp_hash` VARCHAR(255) NULL,
            `otp_sent_to` VARCHAR(40) NULL,
            `otp_sent_at` TIMESTAMP NULL,
            `otp_verified_at` TIMESTAMP NULL,
            `otp_attempts` INT NOT NULL DEFAULT 0,
            `signature_path` VARCHAR(255) NULL,
            `photo_path` VARCHAR(255) NULL,
            `dispatch_expected` TINYINT(1) NOT NULL DEFAULT 1,
            `override_reason` VARCHAR(255) NULL,
            `notes` TEXT NULL,
            `ip` VARCHAR(60) NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            KEY `hho_subject` (`subject_type`, `subject_id`),
            KEY `hho_store_when` (`store_id`, `happened_at`),
            KEY `hho_store_state` (`store_id`, `verify_state`),
            KEY `hho_store_vendor` (`store_id`, `lab_vendor_id`),
            KEY `hho_patient` (`patient_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    protected static function ensureColumns(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $columns = [
            'person_id_ref'     => "VARCHAR(80) NULL AFTER `person_phone`",
            'dispatch_expected' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `photo_path`",
            'override_reason'   => "VARCHAR(255) NULL AFTER `dispatch_expected`",
        ];

        foreach ($columns as $column => $definition) {
            if (!Schema::hasColumn('hmis_handovers', $column)) {
                DB::statement("ALTER TABLE `hmis_handovers` ADD COLUMN `{$column}` {$definition}");
            }
        }
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function labVendor()
    {
        return $this->belongsTo(StoreCustomer::class, 'lab_vendor_id');
    }

    /** The record this exchange was about — a lab job, a pathology order — or null if it is gone. */
    public function subject()
    {
        $model = static::SUBJECTS[$this->subject_type]['model'] ?? null;

        return $model ? $model::find($this->subject_id) : null;
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeFor($query, string $subjectType, $subjectId)
    {
        return $query->where('subject_type', $subjectType)->where('subject_id', $subjectId);
    }

    public function scopeUnconfirmed($query)
    {
        return $query->where('verify_state', 'provisional');
    }

    public function directionLabel(): string
    {
        return static::DIRECTIONS[$this->direction]['label'] ?? ucfirst((string) $this->direction);
    }

    public function stateLabel(): string
    {
        return static::STATES[$this->verify_state]['label'] ?? ucfirst((string) $this->verify_state);
    }

    public function stateColour(): array
    {
        return static::STATES[$this->verify_state]['colour'] ?? ['#475569', '#f1f5f9'];
    }

    public function getIsInboundAttribute(): bool
    {
        return $this->direction === 'in';
    }

    /** Whether this direction gets the strict path — the dispatch check and the code to the lab. */
    public function getIsStrictAttribute(): bool
    {
        return (bool) (static::DIRECTIONS[$this->direction]['strict'] ?? false);
    }

    /**
     * "Suresh of Sri Ceramics collected 2 items from Dr Meera on 24 Aug 2026 at 3:42 PM".
     *
     * One sentence, because this is what goes into the lab's WhatsApp confirmation and into the
     * activity log, and both are read by someone reconstructing a day they were not present for.
     * A confirmation naming only one end of an exchange settles nothing.
     */
    public function movementSentence(): string
    {
        $at    = $this->happened_at ?: ($this->created_at ?: now());
        $who   = trim((string) $this->person_name) ?: 'Their representative';
        $lab   = trim((string) $this->lab_name);
        $staff = trim((string) $this->staff_name) ?: 'our staff';
        $when  = $at->format('d M Y') . ' at ' . $at->format('h:i A');

        $what = trim((string) $this->purpose) ?: 'the work';
        if ($this->item_count) {
            $what .= ' (' . $this->item_count . ' item' . ($this->item_count > 1 ? 's' : '') . ')';
        }

        $subject = $lab !== '' ? $who . ' of ' . $lab : $who;

        return $this->is_inbound
            ? $subject . ' delivered ' . $what . ' to ' . $staff . ' on ' . $when
            : $subject . ' collected ' . $what . ' from ' . $staff . ' on ' . $when;
    }

    /**
     * A fresh code, returned in clear once and never stored that way.
     *
     * Six digits rather than four: this one is worth guessing, because passing it is what makes a
     * delivered report count as genuine. The clear value goes out on WhatsApp and is dropped —
     * nothing in the system can read it back, so nothing at the counter can be shown it either.
     */
    public function issueOtp(string $sendTo): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'otp_hash'        => Hash::make($code),
            'otp_sent_to'     => $sendTo,
            'otp_sent_at'     => now(),
            'otp_verified_at' => null,
            'otp_attempts'    => 0,
        ])->save();

        return $code;
    }

    public function otpIsLive(): bool
    {
        return filled($this->otp_hash)
            && $this->otp_sent_at
            && $this->otp_sent_at->gt(now()->subMinutes(static::OTP_TTL_MINUTES))
            && $this->otp_attempts < static::OTP_MAX_ATTEMPTS;
    }

    /**
     * Check a code the runner read out. Every attempt is counted whether or not it was close, so
     * a wrong number cannot be walked to by trying six hundred thousand of them at the counter.
     */
    public function verifyOtp(string $code): bool
    {
        if (!$this->otpIsLive()) {
            return false;
        }

        $this->increment('otp_attempts');

        if (!Hash::check(trim($code), (string) $this->otp_hash)) {
            return false;
        }

        $this->forceFill([
            'otp_verified_at' => now(),
            'verify_state'    => 'verified',
            'verify_method'   => 'otp',
            'otp_hash'        => null,
        ])->save();

        return true;
    }

    /** Public URL of the signature or counter photo, or null when none was captured. */
    public function mediaUrl(string $field): ?string
    {
        $path = trim((string) $this->{$field});

        return $path !== '' ? asset('storage/' . ltrim($path, '/')) : null;
    }

    /**
     * Names this lab's runners have used before, newest first.
     *
     * A roster nobody has to maintain: it builds itself out of who has actually turned up. What it
     * is for is the negative case — a name that is NOT on it is someone this lab has never sent
     * before, which is worth a second look before a report is taken off them.
     */
    public static function knownRunners(int $storeId, ?int $labVendorId, ?string $labName = null, int $limit = 20): array
    {
        $query = static::where('store_id', $storeId)->whereNotNull('person_name');

        if ($labVendorId) {
            $query->where('lab_vendor_id', $labVendorId);
        } elseif (filled($labName)) {
            $query->where('lab_name', $labName);
        } else {
            return [];
        }

        return $query->orderByDesc('id')
            ->limit(200)
            ->pluck('person_name')
            ->map(fn($n) => trim((string) $n))
            ->filter()
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }
}
