<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdVisit extends Model
{
    protected $fillable = [
        'store_id', 'patient_id', 'doctor_profile_id', 'appointment_id', 'service_request_id',
        'visit_date', 'visit_time', 'token_number', 'visit_type', 'chief_complaint', 'diagnosis', 'treatment',
        'bp_systolic', 'bp_diastolic', 'temperature', 'weight',
        'height', 'spo2', 'pulse_rate', 'respiratory_rate', 'notes', 'recorded_by', 'status',
        'consultation_receipt_id', 'consultation_visit_no',
        'cancelled_at', 'cancel_reason', 'cancelled_by',
        // Label → value rows captured for this visit (dental intake). Overrides the patient's
        // standing values when the bill is built — see DentalIntakeController::mergedFor().
        'custom_info',
    ];

    protected $casts = [
        'visit_date'       => 'date',
        'cancelled_at'     => 'datetime',
        'bp_systolic'      => 'integer',
        'bp_diastolic'     => 'integer',
        'temperature'      => 'float',
        'weight'           => 'float',
        'height'           => 'float',
        'spo2'             => 'integer',
        'pulse_rate'       => 'integer',
        'respiratory_rate' => 'integer',
    ];

    const VISIT_TYPES = [
        'new'      => 'New Visit',
        'followup' => 'Follow-Up',
        'emergency'=> 'Emergency',
        'review'   => 'Review',
    ];

    const STATUS_CANCELLED = 'cancelled';

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * A finished consultation — the OP receipt has been generated and handed over.
     *
     * There is no "completed" value in the status column; the register has always derived the
     * badge from the receipt, so the lock derives it the same way rather than inventing a second
     * source of truth that could disagree with the badge the desk is looking at.
     *
     * Once this is true the visit is a document, not a draft: the patient is holding a receipt
     * that was printed from this record, and a later edit would make the reprint disagree with
     * their copy silently. Everything that writes to the visit checks this.
     */
    public function getIsCompletedAttribute(): bool
    {
        return !empty($this->consultation_receipt_id);
    }

    /** Whether the clinical record may still be written to. */
    public function getIsEditableAttribute(): bool
    {
        return !$this->is_cancelled && !$this->is_completed;
    }

    /**
     * Visits that actually happened — everything counted, charted or exported.
     *
     * Rows created before the status column was written carry NULL rather than 'visited', so
     * a plain `!=` comparison would silently drop the whole of a hospital's older register.
     */
    public function scopeNotCancelled($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('status')->orWhere('status', '!=', self::STATUS_CANCELLED);
        });
    }

    // Diagnosis and treatment are stored as a comma-joined string so they stay readable in
    // exports and receipts; the UI works with them as tag lists.
    /**
     * Complaints are stored in chief_complaint as a comma-separated list, exactly like diagnosis
     * and treatment. Free text written before the field became a chip list still reads back — it
     * simply comes through as one entry.
     */
    public function getComplaintListAttribute(): array
    {
        return self::splitTerms($this->chief_complaint);
    }

    public function getDiagnosisListAttribute(): array
    {
        return self::splitTerms($this->diagnosis);
    }

    public function getTreatmentListAttribute(): array
    {
        return self::splitTerms($this->treatment);
    }

    public static function splitTerms($value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn($term) => trim($term))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function recorder()
    {
        return $this->belongsTo(VendorEmployee::class, 'recorded_by');
    }
}
