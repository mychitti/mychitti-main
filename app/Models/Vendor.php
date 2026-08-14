<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Store;
use Carbon\Carbon;

class Vendor extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['remember_token', 'image', 'cm_firebase_token', 'last_login_at', 'welcome_guide_seen_at'];

    protected $casts = [
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'last_login_at' => 'datetime',
        'welcome_guide_seen_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'auth_token',
        'remember_token',
    ];

    /**
     * Make sure the column behind the Quick Start Guide exists.
     *
     * Without it the guide can never be dismissed: the UPDATE that records "seen" fails on an
     * unknown column, and the modal's auto-open test reads the missing attribute as null — which
     * is exactly "never seen" — so it reopens on every page, forever.
     *
     * Self-healing at the point of use, the way the rest of this codebase adds columns, since the
     * project keeps no migration files. The static flag keeps it to one catalogue lookup per
     * request rather than one per call.
     */
    public static function ensureWelcomeGuideColumn(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('vendors', 'welcome_guide_seen_at')) {
                \Illuminate\Support\Facades\DB::statement(
                    'ALTER TABLE `vendors` ADD COLUMN `welcome_guide_seen_at` TIMESTAMP NULL DEFAULT NULL'
                );
            }
        } catch (\Throwable $e) {
            // A racing request may have added it a moment earlier; either way this must never be
            // the reason a dashboard fails to load.
            \Illuminate\Support\Facades\Log::warning('welcome_guide_seen_at ensure failed: ' . $e->getMessage());
        }
    }
    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
    public function order_transaction()
    {
        return $this->hasMany(OrderTransaction::class);
    }

    public function todays_earning()
    {
        return $this->hasMany(OrderTransaction::class)->whereDate('created_at',now());
    }

    public function this_week_earning()
    {
        return $this->hasMany(OrderTransaction::class)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    }

    public function this_month_earning()
    {
        return $this->hasMany(OrderTransaction::class)->whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'));
    }

    public function todaysorders()
    {
        return $this->hasManyThrough(Order::class, Store::class)->whereDate('orders.created_at',now());
    }

    public function this_week_orders()
    {
        return $this->hasManyThrough(Order::class, Store::class)->whereBetween('orders.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    }

    public function this_month_orders()
    {
        return $this->hasManyThrough(Order::class, Store::class)->whereMonth('orders.created_at', date('m'))->whereYear('orders.created_at', date('Y'));
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, Store::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }
    public function withdrawrequests()
    {
        return $this->hasMany(WithdrawRequest::class);
    }
    public function wallet()
    {
        return $this->hasOne(StoreWallet::class);
    }

    public function userinfo()
    {
        return $this->hasOne(UserInfo::class,'vendor_id', 'id');
    }


}
