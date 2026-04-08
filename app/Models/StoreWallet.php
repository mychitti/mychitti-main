<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreWallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['vendor_id'];

    public function getBalanceAttribute()
    {
        if ($this->total_earning <= 0) {
            return (float)0;
        }
        return (float) round(($this->total_earning - ($this->total_withdrawn + $this->pending_withdraw + $this->collected_cash)), 8);
    }
    public function vendorStore()
    {
        return $this->belongsTo(Store::class, 'vendor_id', 'vendor_id');
    }
    public function lastRecharge()
    {
        return $this->hasOne(AccountTransaction::class, 'from_id', 'vendor_id')
            ->where('reason', 'Wallet Recharge')
            ->where('from_type', 'store')
            ->latestOfMany();
    }
 public function transactions()
{
    return $this->hasMany(AccountTransaction::class, 'from_id', 'vendor_id')
        ->where('method', 'wallet')
        ->where('from_type', 'store');
}
}
