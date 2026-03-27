<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreCustomer extends Model
{
    use HasFactory;
     protected $fillable = [
        'store_id',
        'f_name',
        'phone',
        'gst',
        'email',
        'id_number',
        'address',
        'ledger_account_id',
        'pin_code',
        'user_type',
    ];

    public function billing_address()
    {
        return $this->hasOne(UserAddress::class, 'user_id', 'id')->where('addr_type', 'billing');
    }
    public function shipping_address()
    {
        return $this->hasOne(UserAddress::class, 'user_id', 'id')->where('addr_type', 'shipping');
    }
    public function comments()
    {
        return $this->hasMany(StoreUserComment::class, 'user_id', 'id');
    }
}
