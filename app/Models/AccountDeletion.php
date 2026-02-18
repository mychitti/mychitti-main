<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDeletion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_type',
        'user_id',
        'reason_id',
        'other_reason',
        'deleted_at',
    ];
 
    protected $casts = [
        'deleted_at' => 'datetime',
    ];
}
