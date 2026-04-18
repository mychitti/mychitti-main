<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingNote extends Model
{
    protected $fillable = [
        'store_id', 'ipd_admission_id', 'patient_id',
        'recorded_by', 'note_type', 'note', 'recorded_at',
    ];

    protected $casts = ['recorded_at' => 'datetime'];

    const TYPES = [
        'observation'  => 'Observation',
        'medication'   => 'Medication Given',
        'procedure'    => 'Procedure',
        'vital_check'  => 'Vital Check',
        'handover'     => 'Shift Handover',
        'other'        => 'Other',
    ];

    public function admission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_admission_id');
    }

    public function recorder()
    {
        return $this->belongsTo(VendorEmployee::class, 'recorded_by');
    }
}
