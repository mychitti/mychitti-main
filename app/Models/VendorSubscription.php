<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VendorSubscription extends Model
{
    use HasFactory;
    protected $fillable = [
        'vendor_id',
        'plan_id',
        'bed_tier_id',
        'duration_count',
        'duration_type',
        'permitted_modules',
        'plan_expiry',
        'purchased_at',
        'created_at', // optional
    ];

    // planwise module key → sidebar menu slug(s)
    private const MODULE_MENU_MAP = [
        'leads_manage'          => ['leads_manage'],
        'advanced_leads_manage' => ['leads_manage'],
        'task_manage'           => ['task_management'],
        'projects_manage'       => ['project_manage'],
        'quotaiton_manage'      => ['quotation_manage'],
        'hr_manage'             => ['attendance_manage', 'leave_manage', 'salary_manage'],
        'inventory_manage'      => ['inventory_manage'],
        'account_manage'        => ['account_manage'],
        'billing'               => ['billing'],
        'hospital_manage'       => ['hospital'],
        'pos'                   => ['pos'],
        'laundry'               => ['laundry'],
        'library'               => ['library'],
        'client_manage'         => ['client_manage'],
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($subscription) {
            $storeId = $subscription->vendor_id;
            $modules = json_decode($subscription->permitted_modules, true) ?? [];

            if (!$storeId || empty($modules)) return;

            $slugsToAdd = array_unique(array_merge(
                ...array_map(fn($m) => self::MODULE_MENU_MAP[$m] ?? [], $modules)
            ));

            if (empty($slugsToAdd)) return;

            $hasPreferences = DB::table('store_menu_visibility')
                ->where('store_id', $storeId)
                ->where('menu_type', 'sidebar')
                ->exists();

            // Seed with defaults first so the store doesn't lose basic menu items
            if (!$hasPreferences) {
                $defaults = DB::table('menu')
                    ->where('menu_type', 'sidebar')
                    ->where('default', 1)
                    ->where('status', 1)
                    ->pluck('slug');

                foreach ($defaults as $slug) {
                    DB::table('store_menu_visibility')->insert([
                        'store_id'   => $storeId,
                        'menu_type'  => 'sidebar',
                        'menu_key'   => $slug,
                        'is_visible' => 1,
                    ]);
                }
            }

            $existing = DB::table('store_menu_visibility')
                ->where('store_id', $storeId)
                ->where('menu_type', 'sidebar')
                ->pluck('menu_key')
                ->toArray();

            foreach ($slugsToAdd as $slug) {
                if (!in_array($slug, $existing)) {
                    DB::table('store_menu_visibility')->insert([
                        'store_id'   => $storeId,
                        'menu_type'  => 'sidebar',
                        'menu_key'   => $slug,
                        'is_visible' => 1,
                    ]);
                }
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'vendor_id', 'id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function bedTier()
    {
        return $this->belongsTo(HospitalBedTier::class, 'bed_tier_id');
    }

}
