<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppError extends Model
{
    protected $fillable = [
        'message',
        'stack',
        'api_url',
        'user_id',
        'type',
        'device',
        'app_version',
        'platform',
        'status',
    ];
}
 