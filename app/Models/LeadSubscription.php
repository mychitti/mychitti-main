<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadSubscription extends Model
{
    protected $fillable = ['store_id', 'plan_id', 'type', 'zone_id', 'category_id', 'starts_at', 'expires_at'];

    protected $casts = ['starts_at' => 'date', 'expires_at' => 'date'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function plan()
    {
        return $this->belongsTo(LeadSubscriptionPlan::class);
    }

    public function isActive(): bool
    {
        return $this->expires_at->isFuture() || $this->expires_at->isToday();
    }
}
