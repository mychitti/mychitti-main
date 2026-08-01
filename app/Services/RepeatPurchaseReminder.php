<?php

namespace App\Services;

use App\Models\StoreCustomer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * "You bought rice about a month ago — running low?"
 *
 * Finds customers whose repeat purchases are due and sends each of them ONE WhatsApp message
 * listing everything that's due. One message, not one per item: a customer due for rice, oil and
 * shampoo gets a single note, because three separate messages is how a number's WhatsApp quality
 * rating gets destroyed in a week.
 *
 * How long a thing lasts is set per item, and only per item: the vendor ticks it on the item form
 * and types the days. There is no category-wide cycle — a category holds things that run out at
 * completely different rates, and a number inherited by items nobody chose it for is how a
 * customer ends up chased about a mop bucket. A service line (billing has no item id, just a typed
 * name) resolves through a matching store rule instead.
 *
 * Nothing set means no reminder. Guessing a cycle is worse than staying quiet.
 */
class RepeatPurchaseReminder
{
    /** A customer hears from this feature at most once in this many days, whatever is due. */
    const CUSTOMER_COOLDOWN_DAYS = 14;

    /** Items listed by name in the message; the rest become "and N more". */
    const NAMES_IN_MESSAGE = 3;

    /** Stop chasing a purchase this long after it came due — it isn't a reminder any more. */
    const STALE_DAYS = 60;

    /** Approved template this needs on the vendor's WABA. */
    const TEMPLATE = 'repeat_purchase_reminder';

    /** Customers processed per store per sweep. */
    const BATCH = 200;

    public static function ensureTables(): void
    {
        // The one cycle there is: per item, set by the vendor on the item form.
        if (Schema::hasTable('inventory_items') && !Schema::hasColumn('inventory_items', 'repeat_days')) {
            DB::statement("ALTER TABLE `inventory_items` ADD COLUMN `repeat_days` INT NULL");
        }

        // One-time cleanup: the cycle used to fall back to the item's category, and this column is
        // what held that. Nothing reads it now — a category groups things that run out at wildly
        // different rates, so a number inherited by items nobody chose it for was only ever going
        // to chase the wrong customers. Safe to delete this block once every server has run it.
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'repeat_days')) {
            DB::statement("ALTER TABLE `categories` DROP COLUMN `repeat_days`");
        }

        // Billing service lines carry a typed name and nothing else — no id, no category — so a
        // service's cycle has to hang off the name the vendor bills under.
        if (!Schema::hasTable('store_repeat_rules')) {
            DB::statement("CREATE TABLE `store_repeat_rules` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `label` VARCHAR(190) NOT NULL,
                `match_key` VARCHAR(190) NOT NULL,
                `repeat_days` INT NOT NULL DEFAULT 30,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `srr_store_key` (`store_id`, `match_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // What has already been chased, per customer per thing. A reminder goes out once per
        // purchase: buying again moves last_bought past reminded_at and re-arms it.
        if (!Schema::hasTable('wa_repeat_reminders')) {
            DB::statement("CREATE TABLE `wa_repeat_reminders` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `customer_id` BIGINT NOT NULL,
                `ref_key` VARCHAR(190) NOT NULL,
                `label` VARCHAR(190) NULL,
                `last_bought_at` TIMESTAMP NULL,
                `reminded_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wrr_once` (`store_id`, `customer_id`, `ref_key`),
                KEY `wrr_customer` (`store_id`, `customer_id`, `reminded_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /** Normalised form of a service name, so "AC Servicing" and "ac  servicing" are one thing. */
    public static function matchKey(?string $label): string
    {
        $key = mb_strtolower(trim((string) $label));
        $key = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $key) ?? $key;
        return trim(preg_replace('/\s+/', ' ', $key) ?? $key);
    }

    /** Stores with at least one cycle configured — the only ones worth sweeping. */
    public static function configuredStoreIds(): array
    {
        self::ensureTables();

        $fromItems = DB::table('inventory_items')->whereNotNull('repeat_days')
            ->where('repeat_days', '>', 0)->distinct()->pluck('store_id')->all();

        $fromRules = DB::table('store_repeat_rules')->where('active', 1)
            ->where('repeat_days', '>', 0)->distinct()->pluck('store_id')->all();

        return array_values(array_unique(array_filter(array_merge($fromItems, $fromRules))));
    }

    /**
     * Everything this store's customers are due to rebuy.
     *
     * Returns rows of [customer_id, ref_key, label, last_bought_at, due_at] — one per customer per
     * thing, already filtered to those actually due and not already chased for that purchase.
     */
    public static function dueFor(int $storeId): array
    {
        self::ensureTables();

        $due = [];
        foreach (self::lastPurchases($storeId) as $row) {
            $days = self::cycleFor($storeId, $row);
            if (!$days || $days <= 0) {
                continue;
            }

            $last = Carbon::parse($row->last_bought_at);
            $dueAt = $last->copy()->addDays($days);

            if ($dueAt->isFuture() || $dueAt->lt(now()->subDays(self::STALE_DAYS))) {
                continue;
            }

            $due[] = (object) [
                'customer_id'    => (int) $row->customer_id,
                'ref_key'        => $row->ref_key,
                'label'          => $row->label,
                'last_bought_at' => $last,
                'due_at'         => $dueAt,
            ];
        }

        return self::withoutAlreadyReminded($storeId, $due);
    }

    /**
     * The most recent purchase of each thing by each identified customer.
     *
     * Walk-in sales (bill_to 0/null) are skipped throughout — there is nobody to remind. Both
     * halves come from the same invoice tables, so a POS Retail sale and a billing invoice are
     * treated identically; the only difference is that a stocked line knows its item id and a
     * service line only knows the name it was billed under.
     */
    protected static function lastPurchases(int $storeId)
    {
        return DB::table('invoice_items as ii')
            ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)          // vendor_id holds the STORE id here
            ->whereNotNull('mi.bill_to')->where('mi.bill_to', '>', 0)
            ->where('mi.created_at', '>=', now()->subDays(400))
            ->groupBy('mi.bill_to', 'ref_key', 'label')
            ->selectRaw("mi.bill_to as customer_id,
                CASE WHEN ii.inv_id IS NULL OR ii.inv_id = 0
                     THEN CONCAT('svc:', LOWER(TRIM(ii.name)))
                     ELSE CONCAT('item:', ii.inv_id) END as ref_key,
                MAX(ii.name) as label,
                MAX(mi.created_at) as last_bought_at")
            ->get();
    }

    /** Days between repeats for one purchased thing, or null when nothing is configured. */
    protected static function cycleFor(int $storeId, $row): ?int
    {
        static $items = [];
        static $rules = [];

        if (str_starts_with($row->ref_key, 'item:')) {
            $itemId = (int) substr($row->ref_key, 5);
            if (!array_key_exists($itemId, $items)) {
                $items[$itemId] = (int) DB::table('inventory_items')
                    ->where('id', $itemId)->value('repeat_days');
            }

            // Only what the vendor set on this item. Unticked (0) and never-touched (NULL) both
            // land here as 0 and both mean the same thing: this item is not chased.
            return $items[$itemId] > 0 ? $items[$itemId] : null;
        }

        if (!array_key_exists($storeId, $rules)) {
            $rules[$storeId] = DB::table('store_repeat_rules')
                ->where('store_id', $storeId)->where('active', 1)
                ->pluck('repeat_days', 'match_key')->all();
        }

        $key  = self::matchKey(substr($row->ref_key, 4));
        $days = (int) ($rules[$storeId][$key] ?? 0);

        return $days > 0 ? $days : null;
    }

    /**
     * Drop anything already chased for this same purchase, and anyone who has heard from this
     * feature inside the cooldown.
     */
    protected static function withoutAlreadyReminded(int $storeId, array $due): array
    {
        if (empty($due)) {
            return [];
        }

        $customerIds = array_values(array_unique(array_map(fn($d) => $d->customer_id, $due)));

        $recentlyMessaged = DB::table('wa_repeat_reminders')
            ->where('store_id', $storeId)
            ->whereIn('customer_id', $customerIds)
            ->where('reminded_at', '>=', now()->subDays(self::CUSTOMER_COOLDOWN_DAYS))
            ->distinct()->pluck('customer_id')->all();
        $recentlyMessaged = array_flip($recentlyMessaged);

        $chased = DB::table('wa_repeat_reminders')
            ->where('store_id', $storeId)
            ->whereIn('customer_id', $customerIds)
            ->get(['customer_id', 'ref_key', 'reminded_at'])
            ->keyBy(fn($r) => $r->customer_id . '|' . $r->ref_key);

        return array_values(array_filter($due, function ($d) use ($recentlyMessaged, $chased) {
            if (isset($recentlyMessaged[$d->customer_id])) {
                return false;
            }
            $seen = $chased[$d->customer_id . '|' . $d->ref_key] ?? null;
            if (!$seen || !$seen->reminded_at) {
                return true;
            }
            // Chased before — only again once they have actually bought it since.
            return $d->last_bought_at->gt(Carbon::parse($seen->reminded_at));
        }));
    }

    /**
     * Send this store's due reminders. Returns how many customers were messaged.
     *
     * Grouped by customer first, so each person receives one message however many things are due.
     */
    public static function runStore(int $storeId): int
    {
        self::ensureTables();

        if (!NotificationPrefs::enabled($storeId, 'whatsapp_send', 'repeat_purchase')) {
            return 0;
        }

        $wa = WhatsAppService::make($storeId);
        if ($wa->source() !== 'vendor' || !WhatsAppBilling::isActive($storeId)) {
            return 0;
        }

        $due = self::dueFor($storeId);
        if (empty($due)) {
            return 0;
        }

        $byCustomer = [];
        foreach ($due as $d) {
            $byCustomer[$d->customer_id][] = $d;
        }

        $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?: 'our store';
        $optedOut  = array_flip(array_map(
            fn($p) => substr(preg_replace('/[^0-9]/', '', (string) $p) ?? '', -10),
            WhatsAppService::optedOutPhones($storeId)
        ));

        $sent = 0;
        foreach (array_slice($byCustomer, 0, self::BATCH, true) as $customerId => $lines) {
            try {
                $customer = StoreCustomer::find($customerId);
                $phone = trim((string) ($customer->phone ?? ''));
                if (!$customer || $phone === '') {
                    continue;
                }
                if (isset($optedOut[substr(preg_replace('/[^0-9]/', '', $phone) ?? '', -10)])) {
                    continue;
                }
                if (!WhatsAppBilling::canAffordMessage($storeId, 'own')) {
                    Log::info('Repeat reminders stopped — wallet empty', ['store' => $storeId]);
                    break;
                }

                $res = $wa->sendTemplate(
                    $phone,
                    self::TEMPLATE,
                    'en_US',
                    [['type' => 'body', 'parameters' => array_map(
                        fn($v) => ['type' => 'text', 'text' => $v],
                        [
                            trim((string) $customer->f_name) ?: 'there',
                            self::itemsPhrase($lines),
                            $storeName,
                        ]
                    )]],
                    'repeat reminder'
                );

                // Marked as chased whatever Meta said. A failed send that gets retried tomorrow,
                // and the day after, is how one broken template turns into a daily charge.
                self::markReminded($storeId, (int) $customerId, $lines);

                if ($res['success']) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                Log::warning('Repeat reminder failed: ' . $e->getMessage());
            }
        }

        return $sent;
    }

    /** "rice, sunflower oil and 2 more" — one parameter, no line breaks (Meta rejects those). */
    protected static function itemsPhrase(array $lines): string
    {
        $names = array_values(array_unique(array_map(
            fn($l) => trim((string) $l->label) ?: 'your usual items',
            $lines
        )));

        $shown = array_slice($names, 0, self::NAMES_IN_MESSAGE);
        $extra = count($names) - count($shown);

        $phrase = count($shown) > 1
            ? implode(', ', array_slice($shown, 0, -1)) . ' and ' . end($shown)
            : ($shown[0] ?? 'your usual items');

        if ($extra > 0) {
            $phrase .= ' and ' . $extra . ' more';
        }

        return trim(preg_replace('/\s+/u', ' ', mb_substr($phrase, 0, 500)) ?? $phrase);
    }

    protected static function markReminded(int $storeId, int $customerId, array $lines): void
    {
        foreach ($lines as $line) {
            DB::table('wa_repeat_reminders')->updateOrInsert(
                ['store_id' => $storeId, 'customer_id' => $customerId, 'ref_key' => $line->ref_key],
                [
                    'label'          => mb_substr((string) $line->label, 0, 190),
                    'last_bought_at' => $line->last_bought_at,
                    'reminded_at'    => now(),
                    'updated_at'     => now(),
                    'created_at'     => now(),
                ]
            );
        }
    }
}
