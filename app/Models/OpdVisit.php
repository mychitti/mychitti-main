<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdVisit extends Model
{
    protected $fillable = [
        'store_id', 'patient_id', 'doctor_profile_id', 'appointment_id', 'service_request_id',
        'visit_date', 'token_number', 'visit_type', 'chief_complaint', 'diagnosis', 'treatment',
        'bp_systolic', 'bp_diastolic', 'temperature', 'weight',
        'height', 'spo2', 'pulse_rate', 'respiratory_rate', 'notes', 'recorded_by', 'status',
        'consultation_receipt_id', 'consultation_visit_no',
        'cancelled_at', 'cancel_reason', 'cancelled_by',
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
