<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreDailyBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'closing_balance',
        'date',
        'bank_id',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function bank()
    {
        return $this->belongsTo(StoreBankAccount::class);
    }
}
