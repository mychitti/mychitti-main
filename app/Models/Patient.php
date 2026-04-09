<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'patient_uid',
        'name',
        'dob',
        'gender',
        'blood_group',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'allergies',
        'photo',
        'status',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function medicalHistory()
    {
        return $this->hasOne(PatientMedicalHistory::class, 'patient_id');
    }

    public function documents()
    {
        return $this->hasMany(PatientDocument::class, 'patient_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }
}
