<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Eloquent
 * @property string $gst_status
 */
class InvoiceItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'rand_invoice_id', 'manual_invoice_id', 'name', 'price', 'tax', 'hsn', 'qty', 'gst_status'
    ];

    public function unitId(){
        return $this->belongsTo(Unit::class , 'unit');
    }
    public function item(){
        return $this->belongsTo(InventoryItem::class ,  'inv_id');
    }
    public function invoice(){
        return $this->belongsTo(ManualInvoice::class, 'manual_invoice_id');
    }
}
