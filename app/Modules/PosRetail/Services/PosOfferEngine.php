<?php

namespace App\Modules\PosRetail\Services;

use App\Models\InventoryItem;
use App\Models\InventoryOffer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves and applies POS-retail item offers to a cart at checkout.
 *
 * Given the assembled invoice lines it returns any free reward lines to add,
 * an aggregate bill-level discount, and the list of offers that were applied
 * (for redemption logging + receipt display).
 */
class PosOfferEngine
{
    /**
     * @param array $lines each: ['item'=>InventoryItem,'qty'=>float,'price'=>float,'rate'=>float,'gst_status'=>string,'hsn'=>?string,'var_type'=>?string]
     * @param array $ctx   ['store_id'=>int,'branch_id'=>?int,'customer'=>?StoreCustomer,'subtotal'=>float]
     * @return array ['free_lines'=>array, 'discount'=>float, 'applied'=>array]
     */
    /**
     * All eligible offers whose "buy" products are present in the cart, each with its
     * computed effect. Used to show the cashier what's available — nothing is applied here.
     *
     * @return array list of ['offer'=>InventoryOffer,'free_lines'=>array,'discount'=>float,'free_qty'=>int,'label'=>string,'summary'=>string]
     */
    public function matches(array $lines, array $ctx): array
    {
        $storeId  = (int) $ctx['store_id'];
        $branchId = $ctx['branch_id'] ?? null;
        $customer = $ctx['customer'] ?? null;
        $subtotal = (float) ($ctx['subtotal'] ?? 0);
        $now      = Carbon::now();

        if (!Schema::hasTable('inventory_offers')) {
            return [];
        }

        // Quantity / value of each item currently in the cart (variations roll up to the parent).
        $cartQty = [];
        $cartGross = [];
        foreach ($lines as $line) {
            $id = (int) $line['item']->id;
            $cartQty[$id]   = ($cartQty[$id] ?? 0) + (float) $line['qty'];
            $cartGross[$id] = ($cartGross[$id] ?? 0) + ((float) $line['price'] * (float) $line['qty']);
        }

        $offers = $this->eligibleOffers($storeId, $branchId, $customer, $subtotal, $now);

        $out = [];
        foreach ($offers as $offer) {
            $buyIds = array_map('intval', (array) $offer->buy_product_ids);
            if (empty($buyIds)) {
                continue;
            }

            $qualQty = 0.0;
            $qualGross = 0.0;
            $presentIds = [];
            foreach ($buyIds as $bid) {
                if (!empty($cartQty[$bid])) {
                    $qualQty += $cartQty[$bid];
                    $qualGross += $cartGross[$bid] ?? 0;
                    $presentIds[] = $bid;
                }
            }
            if ($qualQty <= 0) {
                continue;
            }

            $result = $this->applyOffer($offer, [
                'qual_qty'    => $qualQty,
                'qual_gross'  => $qualGross,
                'present_ids' => $presentIds,
                'buy_ids'     => $buyIds,
                'branch_id'   => $branchId,
                'lines'       => $lines,
            ]);

            if ($result === null) {
                continue;
            }

            $out[] = [
                'offer'      => $offer,
                'free_lines' => $result['free_lines'],
                'discount'   => round($result['discount'], 2),
                'free_qty'   => $result['free_qty'],
                'label'      => $offer->offer_name . ' (' . $offer->offer_code . ')',
                'summary'    => $this->summary($offer, $result),
            ];
        }

        return $out;
    }

    /**
     * Apply only the offers the cashier chose to keep (by id). Nothing is auto-applied.
     *
     * @return array ['free_lines'=>array,'discount'=>float,'applied'=>array]
     */
    public function apply(array $lines, array $ctx, array $selectedIds): array
    {
        $selected = array_map('intval', $selectedIds);
        $freeLines = [];
        $discount  = 0.0;
        $applied   = [];

        if (empty($selected)) {
            return ['free_lines' => $freeLines, 'discount' => $discount, 'applied' => $applied];
        }

        $nonCombinableApplied = false;
        foreach ($this->matches($lines, $ctx) as $m) {
            if (!in_array((int) $m['offer']->id, $selected, true)) {
                continue;
            }
            if ($nonCombinableApplied) {
                break; // a previously kept exclusive offer blocks any further stacking
            }

            if (!empty($m['free_lines'])) {
                $freeLines = array_merge($freeLines, $m['free_lines']);
            }
            $discount += $m['discount'];
            $applied[] = [
                'offer'    => $m['offer'],
                'free_qty' => $m['free_qty'],
                'discount' => $m['discount'],
                'label'    => $m['label'],
            ];

            if (!$m['offer']->combine_with_other_offers) {
                $nonCombinableApplied = true;
            }
        }

        return [
            'free_lines' => $freeLines,
            'discount'   => round($discount, 2),
            'applied'    => $applied,
        ];
    }

    private function summary(InventoryOffer $o, array $res): string
    {
        if (($res['free_qty'] ?? 0) > 0) {
            $name = $res['free_lines'][0]['item']->item_name ?? 'item';
            return $res['free_qty'] . ' × ' . $name . ' free';
        }
        if (($res['discount'] ?? 0) > 0) {
            return '₹' . number_format($res['discount'], 2) . ' off';
        }
        return ucwords(str_replace('_', ' ', $o->offer_type));
    }

    /**
     * Record a redemption per applied offer so per-day / per-customer / campaign
     * limits can be enforced on the next sale.
     */
    public function logRedemptions(array $applied, int $storeId, int $invoiceId, ?int $customerId): void
    {
        if (empty($applied) || !Schema::hasTable('inventory_offer_redemptions')) {
            return;
        }
        $rows = [];
        foreach ($applied as $a) {
            $rows[] = [
                'offer_id'          => $a['offer']->id,
                'store_id'          => $storeId,
                'manual_invoice_id' => $invoiceId,
                'customer_id'       => $customerId ?: null,
                'free_qty'          => (int) $a['free_qty'],
                'discount_amount'   => (float) $a['discount'],
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }
        DB::table('inventory_offer_redemptions')->insert($rows);
    }

    // ── internals ─────────────────────────────────────────────────────────────

    private function eligibleOffers(int $storeId, ?int $branchId, $customer, float $subtotal, Carbon $now)
    {
        $today = $now->toDateString();
        $weekday = strtolower($now->format('D')); // mon, tue …
        $time = $now->format('H:i:s');

        $offers = InventoryOffer::where('store_id', $storeId)
            ->where('status', 'published')
            ->where('show_in_pos', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        return $offers->filter(function (InventoryOffer $o) use ($branchId, $customer, $subtotal, $weekday, $time, $storeId, $today) {
            // Time-of-day window
            if ($o->start_time && $o->end_time) {
                if ($time < $o->start_time || $time > $o->end_time) {
                    return false;
                }
            }
            // Day of week
            $days = (array) $o->applicable_days;
            if (!empty($days) && !in_array($weekday, $days, true)) {
                return false;
            }
            // Branch scope
            if (!$o->all_branches) {
                $branches = array_map('intval', (array) $o->branch_ids);
                if (!empty($branches)) {
                    if (!$branchId || !in_array((int) $branchId, $branches, true)) {
                        return false;
                    }
                }
            }
            // Minimum bill value (pre-offer taxable subtotal)
            if ($o->min_bill_value && $subtotal < (float) $o->min_bill_value) {
                return false;
            }
            // Customer eligibility
            if (!$this->customerEligible($o, $customer, $storeId)) {
                return false;
            }
            // Usage limits
            if (!$this->withinLimits($o, $customer, $today)) {
                return false;
            }
            return true;
        })->values();
    }

    private function customerEligible(InventoryOffer $o, $customer, int $storeId): bool
    {
        $type = $o->customer_type ?: 'all_customers';
        if ($type === 'all_customers') {
            return true;
        }
        if (!$customer) {
            return false; // targeted offers need a linked customer
        }
        if (!Schema::hasTable('manual_invoices')) {
            return true;
        }
        $priorCount = DB::table('manual_invoices')
            ->where('vendor_id', $storeId)
            ->where('bill_to', $customer->id)
            ->where('pos_status', 'final')
            ->count();

        return match ($type) {
            'new_customers'       => $priorCount === 0,
            'returning_customers' => $priorCount > 0,
            'vip_customers'       => (int) ($customer->loyalty_points ?? 0) > 0,
            default               => true,
        };
    }

    private function withinLimits(InventoryOffer $o, $customer, string $today): bool
    {
        if (!Schema::hasTable('inventory_offer_redemptions')) {
            return true;
        }
        $base = DB::table('inventory_offer_redemptions')->where('offer_id', $o->id);

        if ($o->total_campaign_limit && (clone $base)->count() >= (int) $o->total_campaign_limit) {
            return false;
        }
        if ($o->max_uses_per_day && (clone $base)->whereDate('created_at', $today)->count() >= (int) $o->max_uses_per_day) {
            return false;
        }
        if ($o->max_uses_per_customer && $customer) {
            $used = (clone $base)->where('customer_id', $customer->id)->count();
            if ($used >= (int) $o->max_uses_per_customer) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array|null ['free_lines'=>array,'discount'=>float,'free_qty'=>int]
     */
    private function applyOffer(InventoryOffer $o, array $c): ?array
    {
        $qualQty   = (float) $c['qual_qty'];
        $qualGross = (float) $c['qual_gross'];
        $branchId  = $c['branch_id'] ?? null;

        $buyQty = max(1, (int) ($o->buy_quantity ?: 1));
        $times  = (int) floor($qualQty / $buyQty);
        if ($times < 1 && in_array($o->offer_type, ['buy_x_get_y_free', 'bundle_deal'], true)) {
            return null;
        }

        $cap = $o->max_offer_value ? (float) $o->max_offer_value : null;

        switch ($o->offer_type) {

            case 'percent_discount': {
                $pct = (float) ($o->reward_value ?? 0);
                if ($pct <= 0) {
                    return null;
                }
                $disc = $qualGross * $pct / 100;
                if ($cap !== null) {
                    $disc = min($disc, $cap);
                }
                return ['free_lines' => [], 'discount' => max(0, $disc), 'free_qty' => 0];
            }

            case 'flat_discount': {
                $disc = (float) ($o->reward_value ?? 0);
                if ($disc <= 0) {
                    return null;
                }
                if ($cap !== null) {
                    $disc = min($disc, $cap);
                }
                $disc = min($disc, $qualGross); // never discount more than the qualifying value
                return ['free_lines' => [], 'discount' => max(0, $disc), 'free_qty' => 0];
            }

            case 'buy_x_get_y_free':
            case 'bundle_deal':
            default: {
                // Reward can be a free product or a discount, driven by reward_type.
                if (($o->reward_type ?? 'free_product') !== 'free_product') {
                    $val = (float) ($o->reward_value ?? 0);
                    if ($val <= 0) {
                        return null;
                    }
                    $disc = $o->reward_type === 'discount_percent'
                        ? ($qualGross * $val / 100)
                        : ($val * $times);
                    if ($cap !== null) {
                        $disc = min($disc, $cap);
                    }
                    return ['free_lines' => [], 'discount' => max(0, min($disc, $qualGross)), 'free_qty' => 0];
                }

                // Free product reward.
                $rewardId = $o->reward_product_id ?: ($c['present_ids'][0] ?? null);
                if (!$rewardId) {
                    return null;
                }
                $reward = InventoryItem::find($rewardId);
                if (!$reward) {
                    return null;
                }

                $freeQty = $times * max(1, (int) ($o->free_quantity ?: 1));
                if ($o->max_free_qty_per_bill) {
                    $freeQty = min($freeQty, (int) $o->max_free_qty_per_bill);
                }

                if ($o->apply_only_if_reward_stock_available) {
                    $available = (float) ($reward->stock ?? 0);
                    $freeQty = (int) min($freeQty, $available);
                }
                if ($freeQty < 1) {
                    return null;
                }

                $freeLine = [
                    'item'       => $reward,
                    'qty'        => $freeQty,
                    'pieces'     => null,
                    'price'      => 0.0,
                    'rate'       => (float) ($reward->gst_rate ?? 0),
                    'gst_status' => $reward->gst_status ?? 'excluding',
                    'hsn'        => $reward->hsn,
                    'var_type'   => null,
                    'is_offer'   => true,
                ];
                return ['free_lines' => [$freeLine], 'discount' => 0.0, 'free_qty' => $freeQty];
            }
        }
    }
}
