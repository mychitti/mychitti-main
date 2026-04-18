<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'order_id',
        'comment',
        'rating',
        'attachment',
        'status',
        'reply',
        'replied_at',
        'service_name',
        'service_date',
        'experience',
    ];

    protected $casts = [
        'attachment'   => 'array',
        'service_date' => 'date',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
 