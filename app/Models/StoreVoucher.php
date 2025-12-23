<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class StoreVoucher extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'store_id',
        'voucher_number',
        'voucher_type',
        'voucher_date',
        'total_amount',
        'narration',
        'reference_no',
        'created_by',
        'created_by_type',
        'created_at',
        'completed_at',
        'status',
        'request_no',
        'maintanace_id',
        'updated_at',
        'invoice_id',
        'debit_entity_type',
        'credit_entity_type',
    ];
    public $timestamps = true;
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(StoreLedgerEntry::class, 'voucher_id');
    }
}
