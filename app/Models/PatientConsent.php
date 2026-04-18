<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientConsent extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'patient_id',
        'ipd_admission_id',
        'consent_template_id',
        'title',
        'content',
        'signatory_name',
        'witness_name',
        'signed_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function admission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_admission_id');
    }

    public function template()
    {
        return $this->belongsTo(ConsentTemplate::class, 'consent_template_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(VendorEmployee::class, 'created_by');
    }
}
