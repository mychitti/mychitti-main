<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'patient_id',
        'doctor_profile_id',
        'appointment_id',
        'service_request_id',
        'diagnosis',
        'notes',
        'follow_up_date',
        'is_finalized',
        'created_by',
        'created_by_type',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'is_finalized'   => 'boolean',
        'created_by'     => 'integer',
    ];

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

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
