<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class StoreBankTransactionFile extends Model
{
    use HasFactory;

    public function bankAccount()
    {
        return $this->belongsTo(StoreBankAccount::class, 'bank_id');
    }
}
