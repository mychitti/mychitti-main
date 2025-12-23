<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoneRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'approved', 
    ];
}
