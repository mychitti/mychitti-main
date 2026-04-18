<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LaundryChallanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'laundry_challan_id',
        'laundry_item_id',
        'item_name',
        'rate',
        'previous_balance',
        'sent_qty',
        'total',
        'received_qty',
        'damaged_qty',
        'balance',
        'remarks',
    ];

    protected $casts = [
        'rate'             => 'decimal:2',
        'previous_balance' => 'integer',
        'sent_qty'         => 'integer',
        'total'            => 'integer',
        'received_qty'     => 'integer',
        'damaged_qty'      => 'integer',
        'balance'          => 'integer',
    ];

    public function challan()
    {
        return $this->belongsTo(LaundryChallan::class, 'laundry_challan_id');
    }

    public function laundryItem()
    {
        return $this->belongsTo(LaundryItem::class, 'laundry_item_id');
    }

    public function computeTotals()
    {
        $this->total   = $this->previous_balance + $this->sent_qty;
        $this->balance = $this->total - $this->received_qty - $this->damaged_qty;
        return $this;
    }
}
