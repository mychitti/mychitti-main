<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
    protected $fillable = [
        'store_id', 'order_no', 'patient_id', 'doctor_profile_id', 'prescription_id',
        'opd_id', 'source', 'department', 'priority', 'status', 'sample_type',
        'clinical_notes', 'total_amount', 'referred_by', 'technician_notes',
        'analysed_by', 'verified_by_name', 'collected_at', 'reported_at',
        'created_by', 'created_by_type',
        'external_lab_id', 'external_lab_name', 'external_lab_phone', 'is_outsourced',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'collected_at' => 'datetime',
        'reported_at'  => 'datetime',
    ];

    /**
     * The outside lab this order was sent to, when it was not run on the bench here.
     *
     * A referral lab is a supplier the clinic already invoices, so it is a store customer of type
     * 'vendor' — the same address book OpdLabWork reads for a ceramics lab, and for the same
     * reason: two lists of the same firms drift apart, and the phone number on the stale one is
     * the number a handover code would be sent to.
     */
    public function externalLab()
    {
        return $this->belongsTo(StoreCustomer::class, 'external_lab_id');
    }

    /** Whether this order leaves the building at all. */
    public function getIsOutsourcedAttribute($value): bool
    {
        return (bool) $value || filled($this->external_lab_name) || filled($this->external_lab_id);
    }

    /** Who is running it, in one phrase — this lab's own bench, or the firm it went to. */
    public function labDisplayName(): string
    {
        return trim((string) $this->external_lab_name) ?: 'In-house lab';
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function items()
    {
        return $this->hasMany(LabOrderItem::class);
    }

    public function results()
    {
        return $this->hasMany(LabOrderResult::class)->orderBy('sort_order');
    }

    public function invoice()
    {
        return $this->hasOne(LabInvoice::class);
    }
}
