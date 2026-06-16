<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadiologyInvoice extends Model
{
    protected $fillable = [
        'store_id', 'radiology_study_id', 'invoice_no', 'patient_id',
        'subtotal', 'insurance_provider', 'insurance_covered', 'discount',
        'payable', 'payment_mode', 'status',
    ];

    protected $casts = [
        'subtotal'          => 'float',
        'insurance_covered' => 'float',
        'discount'          => 'float',
        'payable'           => 'float',
    ];

    public function study()
    {
        return $this->belongsTo(RadiologyStudy::class, 'radiology_study_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
