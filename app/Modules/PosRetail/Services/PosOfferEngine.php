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
        // Both values are kept: what the line sells for, and what it lists for. A discount offer
        // is measured against the list price so the everyday markdown is not conceded twice.
        $cartQty = [];
        $cartGross = [];
        $cartMrp = [];
        foreach ($lines as $line) {
            $id = (int) $line['item']->id;
            $cartQty[$id]   = ($cartQty[$id] ?? 0) + (float) $line['qty'];
            $cartGross[$id] = ($cartGross[$id] ?? 0) + ((float) $line['price'] * (float) $line['qty']);
            $cartMrp[$id]   = ($cartMrp[$id] ?? 0) + ($this->lineListPrice($line) * (float) $line['qty']);
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
            $qualMrp = 0.0;
            $presentIds = [];
            foreach ($buyIds as $bid) {
                if (!empty($cartQty[$bid])) {
                    $qualQty += $cartQty[$bid];
                    $qualGross += $cartGross[$bid] ?? 0;
                    $qualMrp += $cartMrp[$bid] ?? 0;
                    $presentIds[] = $bid;
                }
            }
            if ($qualQty <= 0) {
                continue;
            }

            $result = $this->applyOffer($offer, [
                'qual_qty'    => $qualQty,
                'qual_gross'  => $qualGross,
                'qual_mrp'    => $qualMrp,
                // A combo is priced per basket, so it needs each member's own count and value
                // rather than the qualifying totals the other offer types work from.
                'cart_qty'    => $cartQty,
                'cart_gross'  => $cartGross,
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
        if (($res['combo_sets'] ?? 0) > 0) {
            $sets = (int) $res['combo_sets'];
            return $sets . ' × combo @ ₹' . number_format((float) $o->combo_price, 2)
                . ' (saves ₹' . number_format((float) ($res['discount'] ?? 0), 2) . ')';
        }
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

        // An offer set to run until stock is exhausted outlives its end date — the goods decide
        // when it finishes, not the calendar. Everything else keeps the plain date window.
        $stockRun = _ensureOfferColumns();

        $offers = InventoryOffer::where('store_id', $storeId)
            ->where('status', 'published')
            ->where('show_in_pos', true)
            ->whereDate('start_date', '<=', $today)
            ->when(
                $stockRun,
                fn($q) => $q->where(fn($w) => $w->whereDate('end_date', '>=', $today)
                    ->orWhere('run_until_stock_out', 1)),
                fn($q) => $q->whereDate('end_date', '>=', $today)
            )
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        return $offers->filter(function (InventoryOffer $o) use ($branchId, $customer, $subtotal, $weekday, $time, $storeId, $today, $stockRun) {
            // Stock-governed offers end the moment the qualifying goods run out — inside the
            // date window as well as past it. "Until the apples are gone" cuts both ways.
            if ($stockRun && $o->run_until_stock_out && !$this->qualifyingStockRemains($o, $branchId)) {
                return false;
            }
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

    /**
     * A combo offer's fixed basket as [item_id => required qty].
     *
     * Falls back to one of each buy product when combo_items was never filled in, so an offer
     * saved before the per-member quantity boxes existed still behaves sensibly.
     */
    private function comboBasket(InventoryOffer $o): array
    {
        $rows = json_decode((string) ($o->combo_items ?? ''), true);
        $basket = [];

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $id  = (int) ($row['item_id'] ?? 0);
                $qty = (float) ($row['qty'] ?? 0);
                if ($id > 0 && $qty > 0) {
                    $basket[$id] = ($basket[$id] ?? 0) + $qty;
                }
            }
        }

        if (empty($basket)) {
            foreach (array_map('intval', (array) $o->buy_product_ids) as $id) {
                if ($id > 0) {
                    $basket[$id] = 1;
                }
            }
        }

        return $basket;
    }

    /**
     * One line's list price — the figure a discount offer is measured against.
     *
     * An item with no MRP recorded, or an MRP below its own selling price, has no everyday
     * markdown to protect, so the selling price stands in and the offer behaves exactly as it
     * did before this rule existed.
     */
    private function lineListPrice(array $line): float
    {
        $item  = $line['item'];
        $price = (float) $line['price'];
        $mrp   = (float) ($item->mrp ?? 0);

        // mrp is held per item unit, so a measured pack is worth that much of it — the same
        // conversion the receipt's "saved on MRP" line uses.
        $varType = trim((string) ($line['var_type'] ?? ''));
        if ($mrp > 0 && $varType !== '' && _variationMode($item) === 'measured'
            && ($var = _variationRow($item, $varType)) && ($pack = _variationPack($item, $var))) {
            $mrp = $mrp * _variationQtyInItemUnit($item, $pack, 1);
        }

        return max($mrp, $price);
    }

    /**
     * Turn a discount taken off the list price into the discount that comes off the bill.
     *
     * The customer should pay MRP minus the offer. The bill charges the selling price, which is
     * already below MRP, so what comes off it is only the difference between the two — a bench
     * listed at 1800 and sold at 1600 with 20% off should reach 1440, which is 160 off the bill,
     * not 320.
     *
     * Floored at zero because an offer smaller than the everyday markdown would otherwise price
     * the item ABOVE its shelf price: 5% off 1800 is 1710, and no customer should pay more for
     * taking an offer. Capped at the qualifying value so a discount can never exceed the goods.
     */
    private function billDiscountFromList(float $qualGross, float $qualMrp, float $listDiscount): float
    {
        $target = $qualMrp - $listDiscount;

        return min($qualGross, max(0.0, $qualGross - $target));
    }

    /**
     * Whether any of an offer's "buy" products still has stock to sell.
     *
     * Read at the counter's own location: a branch checks its own pool, the main store its own
     * figure. Stock is only deducted at checkout, so the last 5 kg sitting in the cart still
     * counts as available here — the offer holds for the sale that empties the shelf and stops
     * only afterwards, which is what "until stock reaches zero" has to mean to be usable.
     *
     * An offer that names no buy products has nothing that can run out, so it is left alone.
     */
    private function qualifyingStockRemains(InventoryOffer $o, $branchId): bool
    {
        $buyIds = array_values(array_filter(array_map('intval', (array) $o->buy_product_ids)));
        if (empty($buyIds)) {
            return true;
        }

        if ($branchId) {
            return DB::table('pos_branch_stock')
                ->where('branch_id', $branchId)
                ->whereIn('inventory_item_id', $buyIds)
                ->where('stock', '>', 0)
                ->exists();
        }

        return InventoryItem::whereIn('id', $buyIds)->where('stock', '>', 0)->exists();
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
        // Falls back to the selling total, which makes every formula below collapse to what it
        // computed before whenever no MRP is on file.
        $qualMrp   = (float) ($c['qual_mrp'] ?? $c['qual_gross']);
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
                $disc = $this->billDiscountFromList($qualGross, $qualMrp, $qualMrp * $pct / 100);
                if ($cap !== null) {
                    $disc = min($disc, $cap);
                }
                return ['free_lines' => [], 'discount' => max(0, $disc), 'free_qty' => 0];
            }

            case 'flat_discount': {
                $flat = (float) ($o->reward_value ?? 0);
                if ($flat <= 0) {
                    return null;
                }
                $disc = $this->billDiscountFromList($qualGross, $qualMrp, $flat);
                if ($cap !== null) {
                    $disc = min($disc, $cap);
                }
                return ['free_lines' => [], 'discount' => max(0, $disc), 'free_qty' => 0];
            }

            case 'combo_offer': {
                $comboPrice = (float) ($o->combo_price ?? 0);
                $basket = $this->comboBasket($o);
                if ($comboPrice <= 0 || empty($basket)) {
                    return null;
                }

                $cartQty   = (array) ($c['cart_qty'] ?? []);
                $cartGross = (array) ($c['cart_gross'] ?? []);

                // A combo only exists when every member is in the basket, so the number of
                // complete combos is the scarcest member — two toothbrushes and one paste is
                // still one combo, and the spare brush is billed normally.
                $sets = null;
                $listValue = 0.0;
                foreach ($basket as $itemId => $need) {
                    $have = (float) ($cartQty[$itemId] ?? 0);
                    if ($need <= 0 || $have < $need) {
                        return null;
                    }
                    $sets = min($sets ?? PHP_INT_MAX, (int) floor($have / $need));

                    // What those units are actually being billed at, averaged over the line in
                    // case the same product came in at two prices.
                    $unit = $have > 0 ? ((float) ($cartGross[$itemId] ?? 0)) / $have : 0.0;
                    $listValue += $unit * $need;
                }

                if (!$sets || $sets < 1) {
                    return null;
                }
                if ($o->max_free_qty_per_bill) {
                    $sets = min($sets, (int) $o->max_free_qty_per_bill);
                }

                // The combo price is what the customer pays, full stop — so the discount is
                // whatever brings the bill down to it. Floored at zero: a combo priced above
                // what the items already cost must never add to the bill.
                $disc = max(0.0, ($listValue - $comboPrice)) * $sets;
                if ($cap !== null) {
                    $disc = min($disc, $cap);
                }

                return ['free_lines' => [], 'discount' => max(0, $disc), 'free_qty' => 0, 'combo_sets' => $sets];
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
                    // Same rule as the standalone discounts: measured off the list price, then
                    // converted to what actually comes off the bill.
                    $listDisc = $o->reward_type === 'discount_percent'
                        ? ($qualMrp * $val / 100)
                        : ($val * $times);
                    $disc = $this->billDiscountFromList($qualGross, $qualMrp, $listDisc);
                    if ($cap !== null) {
                        $disc = min($disc, $cap);
                    }
                    return ['free_lines' => [], 'discount' => max(0, $disc), 'free_qty' => 0];
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
