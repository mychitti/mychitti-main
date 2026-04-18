<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConsentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'title',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function consents()
    {
        return $this->hasMany(PatientConsent::class);
    }
}
