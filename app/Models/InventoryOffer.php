<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'item_id',
        'offer_name',
        'offer_code',
        'offer_type',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'applicable_days',
        'all_branches',
        'branch_ids',
        'customer_type',
        'apply_on',
        'buy_product_ids',
        'buy_quantity',
        'buy_type',
        'count_based_on',
        'reward_type',
        'reward_product_id',
        'reward_value',
        'free_quantity',
        'min_bill_value',
        'max_offer_value',
        'apply_only_if_reward_stock_available',
        'max_free_qty_per_bill',
        'max_uses_per_day',
        'max_uses_per_customer',
        'total_campaign_limit',
        'priority',
        'combine_with_other_offers',
        'show_in_pos',
        'auto_expire_after_end_date',
        'notify_sms',
        'notify_whatsapp',
        'notify_push',
        'notify_in_app',
        'customer_eligibility',
        'allow_multiple_times',
        'banner',
        'status',
    ];

    protected $casts = [
        'applicable_days' => 'array',
        'branch_ids' => 'array',
        'buy_product_ids' => 'array',
        'all_branches' => 'boolean',
        'apply_only_if_reward_stock_available' => 'boolean',
        'combine_with_other_offers' => 'boolean',
        'show_in_pos' => 'boolean',
        'auto_expire_after_end_date' => 'boolean',
        'notify_sms' => 'boolean',
        'notify_whatsapp' => 'boolean',
        'notify_push' => 'boolean',
        'notify_in_app' => 'boolean',
        'allow_multiple_times' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id', 'id');
    }

    public function rewardProduct()
    {
        return $this->belongsTo(InventoryItem::class, 'reward_product_id', 'id');
    }
}
