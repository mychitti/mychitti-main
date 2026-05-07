<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainPurchase extends Model
{
    protected $fillable = [
        'store_id', 'domain', 'charge', 'gst_percent', 'gst_amount',
        'total_amount', 'transaction_id', 'invoice_id', 'status', 'activated_at', 'removed_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'removed_at'   => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
