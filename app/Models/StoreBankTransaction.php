<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class StoreBankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'store_id',
        'bank_id',
        'txn_date',
        'particulars',
        'txn_id',
        'type',
        'amount',
        'closing_balance',
        'value_date',
        'bill_number',
        'reference_number'
    ];
    public function bankAccount()
    {
        return $this->belongsTo(StoreBankAccount::class, 'bank_id');
    }
}
