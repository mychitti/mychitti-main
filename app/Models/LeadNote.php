<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadNote extends Model
{
    protected $fillable = ['service_id', 'store_id', 'note', 'remind_at'];

    protected $casts = [
        'remind_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
