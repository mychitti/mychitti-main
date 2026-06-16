<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdConsultationReceipt extends Model
{
    protected $fillable = [
        'store_id',
        'patient_id',
        'doctor_profile_id',
        'opd_visit_id',
        'bill_no',
        'receipt_date',
        'amount',
        'concession',
        'paid',
        'due',
        'payment_mode',
        'transaction_id',
        'allowed_consultations',
        'validity_days',
        'valid_until',
        'consultations_used',
        'billed_by',
        'invoice_id',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'valid_until'  => 'date',
        'amount'       => 'float',
        'concession'   => 'float',
        'paid'         => 'float',
        'due'          => 'float',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    /** Is this receipt still usable for a follow-up visit today? */
    public function isActive(): bool
    {
        return $this->consultations_used < $this->allowed_consultations
            && $this->valid_until >= now()->startOfDay();
    }
}
