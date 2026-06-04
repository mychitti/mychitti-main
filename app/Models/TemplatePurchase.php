<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class TemplatePurchase extends Model
{
    protected $fillable = [
        'vendor_id',
        'template_id',  
        'amount_paid',
        'invoice_id',
        'purchased_at',
        'expires_at',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'vendor_id');
    }
}
