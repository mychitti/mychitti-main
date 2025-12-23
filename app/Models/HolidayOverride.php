<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayOverride extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'vendor_id',
        'holiday_id',
        'custom_title',
        'custom_date',
        'is_deleted',
    ];
}
