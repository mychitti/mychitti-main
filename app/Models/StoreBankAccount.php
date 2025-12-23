<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreBankAccount extends Model
{
    use HasFactory;

    public function lastTxn()
    {
        return $this->hasOne(StoreBankTransaction::class, 'bank_id')->latestOfMany();
    }
    public function transactions()
    {
        return $this->hasMany(StoreBankTransaction::class, 'bank_id');
    }
}
