<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class InventoryOrder extends Model
{
    use HasFactory;
    public function details()
    {
        return $this->hasMany(InventoryOrderDetail::class, 'order_id', 'order_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
    public function invoice()
    {
        return $this->belongsTo(ManualInvoice::class, 'invoice_id', 'invoice_id');
    }
   
}
