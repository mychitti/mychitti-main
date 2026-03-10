<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanDuration extends Model
{
    protected $guarded = ['id']; 

    public function discounts()
    {
        return $this->hasMany(SubModuleDiscount::class);
    }
}
