<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceStockRestoreApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'invoice_ref',
        'invoice_type',
        'items',
        'requested_by',
        'requested_by_name',
        'status',
        'approved_by',
        'decided_at',
    ];

    protected $casts = [
        'items' => 'array',
        'decided_at' => 'datetime',
    ];
}
