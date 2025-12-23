<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryGatepass extends Model
{
    use HasFactory;
    public function invoice()
    {
        return $this->belongsTo(ManualInvoice::class, 'invoice_id', 'invoice_id');
    }
    public function gpItems()
    {
        return $this->hasMany(InventoryGatepassItem::class, 'gatepass_id');
    }
}
