<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'structure',
        'store_id',
        'form_type'
    ];

    protected $casts = [
        'structure' => 'array', // Automatically cast JSON to array
    ];

    // A form can have many submissions
    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }
    
}
