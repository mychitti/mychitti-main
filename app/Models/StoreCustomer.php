<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreCustomer extends Model
{
    use HasFactory;

    /**
     * Import flows set this false for the duration of the sheet: a synchronous Graph call
     * per row would stall the upload, so imports instead collect the new rows and queue
     * SendWelcomeMessages when the vendor opted in.
     */
    public static bool $welcomeOnCreate = true;

    /**
     * Every new store customer/vendor — whichever flow saved them (CRM add form, Laundry,
     * billing quick-create, AI agent) — gets the WhatsApp welcome.
     * sendWelcomeMessage() guards everything itself: vendor connection, phone validity,
     * one-welcome-per-number dedupe, and never throws.
     */
    protected static function booted()
    {
        static::created(function (self $customer) {
            if (static::$welcomeOnCreate && $customer->store_id && $customer->phone) {
                \App\Services\WhatsAppService::sendWelcomeMessage((int) $customer->store_id, $customer->f_name, $customer->phone);
            }
        });
    }

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
