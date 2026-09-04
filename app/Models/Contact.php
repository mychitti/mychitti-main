<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'message',
        'name',
        'email',
        'seen',
        'brand'
    ];

    protected $casts = [
        'seen'       => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',

    ];

    /**
     * Enquiries belonging to one brand's Sales & Marketing desk.
     */
    public function scopeBrand($query, string $brand)
    {
        return $query->where('brand', $brand);
    }
}
