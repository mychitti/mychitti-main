<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietChart extends Model
{
    protected $fillable = [
        'store_id', 'ipd_admission_id', 'patient_id',
        'date', 'meal_type', 'diet_type', 'instructions', 'recorded_by',
    ];

    protected $casts = ['date' => 'date'];

    const MEAL_TYPES = [
        'all'       => 'All Meals',
        'breakfast' => 'Breakfast',
        'lunch'     => 'Lunch',
        'dinner'    => 'Dinner',
        'snacks'    => 'Snacks',
    ];

    const DIET_TYPES = [
        'normal'       => 'Normal / Regular',
        'soft'         => 'Soft Diet',
        'liquid'       => 'Liquid Diet',
        'diabetic'     => 'Diabetic Diet',
        'low_sodium'   => 'Low Sodium',
        'low_fat'      => 'Low Fat',
        'high_protein' => 'High Protein',
        'npo'          => 'NPO (Nothing by Mouth)',
        'custom'       => 'Custom',
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
