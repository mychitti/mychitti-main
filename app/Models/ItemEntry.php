<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemEntry extends Model
{
  use HasFactory;

  protected $fillable = [
    'store_id',
    'date',
    'bill_number',
    'item_id',
    'variation_type',
    'quantity',
    'product_condition',
    'landing_price',
    'price_gst_status',
    'selling_price',
  ];

  public function item()
  {
    return $this->belongsTo(InventoryItem::class, 'item_id');
  }
    public function storageUnit()
    {
        return $this->belongsTo(StorageUnit::class, 'storage_unit');
    }
    public function invoice()
    {
        return $this->belongsTo(ManualInvoice::class, 'bill_number', 'invoice_id');
    }
}
