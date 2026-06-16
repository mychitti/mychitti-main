<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabInvoice extends Model
{
    protected $fillable = [
        'store_id', 'lab_order_id', 'invoice_no', 'patient_id',
        'subtotal', 'insurance_provider', 'insurance_covered', 'discount',
        'payable', 'payment_mode', 'status',
    ];

    protected $casts = [
        'subtotal'          => 'float',
        'insurance_covered' => 'float',
        'discount'          => 'float',
        'payable'           => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
