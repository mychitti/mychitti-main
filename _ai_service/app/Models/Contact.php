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
        'seen'
    ];

    protected $casts = [
        'seen'       => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        
    ];
}
