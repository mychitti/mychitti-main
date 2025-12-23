<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'notified'
    ];
   public function item()
{
    return $this->belongsTo(Item::class, 'item_id')->withoutGlobalScopes();
}
}
