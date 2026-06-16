<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreopCase extends Model
{
    protected $fillable = [
        'store_id', 'patient_id', 'ipd_admission_id', 'procedure', 'icd_code',
        'surgeon', 'assistant', 'anaesthetist', 'ot_room', 'scheduled_at', 'est_duration',
        'anaesthesia_type', 'asa_class', 'airway', 'intubation_plan', 'nbm_since',
        'anaesthesia_notes', 'diagnosis', 'referred_by', 'special_instructions',
        'handover_from', 'handover_to', 'handover_notes', 'shifted_at',
        'status', 'created_by', 'created_by_type',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'shifted_at'   => 'datetime',
        'asa_class'    => 'integer',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function admission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_admission_id');
    }

    public function meds()
    {
        return $this->hasMany(PreopMed::class);
    }

    public function consents()
    {
        return $this->hasMany(PreopConsent::class);
    }

    public function clearances()
    {
        return $this->hasMany(PreopClearance::class);
    }

    public function checks()
    {
        return $this->hasMany(PreopCheck::class)->orderBy('sort_order');
    }

    public function results()
    {
        return $this->hasMany(PreopResult::class);
    }

    public function bloodUnits()
    {
        return $this->hasMany(PreopBloodUnit::class);
    }
}
