<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
/** @mixin \Eloquent */
class SystemPrompt extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'name',
        'description',
        'user_type',
        'skill_type',
        'status',
        'prompt',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];
}
