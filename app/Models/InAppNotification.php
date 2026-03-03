<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InAppNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'type',
        'title',
        'description',
        'is_read',
    ];
}
 