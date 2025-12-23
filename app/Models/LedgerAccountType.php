<?php

namespace App\Models;

use App\CentralLogics\Helpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerAccountType extends Model
{
    use HasFactory;

    public function account()
    {
        return $this->hasMany(StoreAccount::class, 'ledger_account_type_id')
            ->where('store_id', Helpers::get_store_id())
            ->with('children');
    }
}
