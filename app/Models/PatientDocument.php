<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientDocument extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'patient_id',
        'document_type',
        'document_name',
        'file_path',
        'uploaded_by',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
