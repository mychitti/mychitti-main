<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVariationDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'item_id',
        'type',
        'specifications',
        'description',
        'images',
    ];
     protected $casts = [
        'images'
     ];
}
