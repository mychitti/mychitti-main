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
    ];

    protected $casts = [
        'total_amount' => 'float',
        'collected_at' => 'datetime',
        'reported_at'  => 'datetime',
    ];

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
