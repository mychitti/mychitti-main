<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientMedicalHistory extends Model
{
    use HasFactory;

    protected $table = 'patient_medical_history';

    protected $fillable = [
        'patient_id',
        'chronic_conditions',
        'past_surgeries',
        'current_medications',
        'family_history',
        'smoking',
        'alcohol',
        'notes',
        'updated_by',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
