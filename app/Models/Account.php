<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    use HasFactory;
    protected $fillable = [
        'store_id',
        'date',
        'amount',
        'user_type',
        'user_type_id',
        'type',
        'account_type',
        'status',
        'payment_mode',
        'category',
        'description',
        'ledger_account_type',
        'gst_amount',
   
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
    public function storeAsset()
    {
        return $this->hasOne(StoreAsset::class, 'id', 'asset_id');
    }
    public function storeCustomer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class, 'customer_id');
    }
}
