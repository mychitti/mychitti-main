<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadSubscriptionPlan extends Model
{
    protected $fillable = ['name', 'type', 'price', 'duration_days', 'zone_id', 'category_id', 'status'];
}
