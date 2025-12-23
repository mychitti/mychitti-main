<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class SupplyOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'pdf',
    ];

    public function order_items()
    {
        return $this->hasMany(SupplyOrderItem::class , 'order_table_id');
    }
    public function invoice()
    {
        return $this->hasOne(ManualInvoice::class, 'invoice_id', 'invoice_id');
    }
}
