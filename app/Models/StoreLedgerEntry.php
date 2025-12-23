<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class StoreLedgerEntry extends Model
{
    use HasFactory;
     protected $fillable = [
        'store_id',
        'voucher_type',
        'voucher_id',
        'entry_date',
        'account_id',
        'debit',
        'credit',
        'narration',
        'request_no',
        'created_by',
        'created_by_type',
        'created_at',
        'gst_amount',
        'payment_mode',
        'note',
        'document',
        'store_user',
        'status',
        'updated_at',
        'completed_at',

    ];
    public $timestamps = true;
      public function voucher()
    {
        return $this->belongsTo(StoreVoucher::class, 'voucher_id');
    }

    public function account()
    {
        return $this->belongsTo(StoreAccount::class, 'account_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
    public function storeUser()
    {
        return $this->belongsTo(StoreCustomer::class, 'store_user');
    }
}
