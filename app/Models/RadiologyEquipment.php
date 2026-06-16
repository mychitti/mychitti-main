<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadiologyEquipment extends Model
{
    protected $table = 'radiology_equipment';

    // status: online | maintenance | offline
    protected $fillable = [
        'store_id', 'name', 'model', 'modality', 'location', 'status',
        'last_service', 'note', 'studies_total',
    ];

    protected $casts = [
        'last_service' => 'date',
    ];
}
