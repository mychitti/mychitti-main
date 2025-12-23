<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferBanner extends Model
{
    use HasFactory;

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function zoneData()
{
    return $this->belongsTo(Zone::class, 'zone', 'id');
}
}
