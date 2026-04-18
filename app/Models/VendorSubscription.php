<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorSubscription extends Model
{
    use HasFactory;
    protected $fillable = [
        'vendor_id',
        'plan_id',
        'bed_tier_id',
        'duration_count',
        'duration_type',
        'permitted_modules',
        'plan_expiry',
        'purchased_at',
        'created_at', // optional
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'vendor_id', 'id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function bedTier()
    {
        return $this->belongsTo(HospitalBedTier::class, 'bed_tier_id');
    }

}
