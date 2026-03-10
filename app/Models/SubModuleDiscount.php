<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubModuleDiscount extends Model
{
    protected $guarded = ['id'];

    public function duration()
    {
        return $this->belongsTo(PlanDuration::class, 'plan_duration_id');
    }

    public function subModule()
    {
        return $this->belongsTo(SubModule::class);
    }
}
