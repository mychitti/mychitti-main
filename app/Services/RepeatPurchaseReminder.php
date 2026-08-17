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
 * customer ends up chased about a mop bucket.
 *
 * A line billed without an item id — every service invoice line, and hand-typed billing lines —
 * resolves by name: an explicit store rule first, otherwise the store's own item of that name.
 * So the cycle is still set once, on the item, and applies wherever that work gets billed from.
 *
 * Both places a store bills are swept: manual_invoices (counter, POS Retail, billing) and
 * service_invoices (work that arrived as a service request). See lastPurchases().
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
        //
        // customer_id is only unique WITHIN customer_type. A billing invoice bills a
        // store_customers row; a service invoice bills a users row; both count from 1, so the two
        // are different people at the same id and the type has to be part of the key.
        //
        // phone_key (last 10 digits) is the other half: it is the one identifier that means the
        // same human across both tables, so the "don't message this person again for a fortnight"
        // cooldown reads it rather than the id.
        if (!Schema::hasTable('wa_repeat_reminders')) {
            DB::statement("CREATE TABLE `wa_repeat_reminders` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `customer_type` VARCHAR(10) NOT NULL DEFAULT 'store',
                `customer_id` BIGINT NOT NULL,
                `phone_key` VARCHAR(15) NULL,
                `ref_key` VARCHAR(190) NOT NULL,
                `label` VARCHAR(190) NULL,
                `last_bought_at` TIMESTAMP NULL,
                `reminded_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wrr_once` (`store_id`, `customer_type`, `customer_id`, `ref_key`),
                KEY `wrr_customer` (`store_id`, `customer_id`, `reminded_at`),
                KEY `wrr_phone` (`store_id`, `phone_key`, `reminded_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } elseif (!Schema::hasColumn('wa_repeat_reminders', 'customer_type')) {
            // Built before service invoices were swept. Everything already in it came from a
            // billing invoice, so the default backfills every existing row correctly.
            DB::statement("ALTER TABLE `wa_repeat_reminders`
                ADD COLUMN `customer_type` VARCHAR(10) NOT NULL DEFAULT 'store' AFTER `store_id`,
                ADD COLUMN `phone_key` VARCHAR(15) NULL AFTER `customer_id`,
                DROP INDEX `wrr_once`,
                ADD UNIQUE KEY `wrr_once` (`store_id`, `customer_type`, `customer_id`, `ref_key`),
                ADD KEY `wrr_phone` (`store_id`, `phone_key`, `reminded_at`)");
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
     * Returns rows of [customer_type, customer_id, ref_key, label, last_bought_at, due_at] — one
     * per customer per thing, already filtered to those actually due and not already chased for
     * that purchase. The per-person cooldown is applied later, in runStore(), because it works on
     * the phone number and that is not known until the customer row is resolved.
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
                'customer_type'  => $row->customer_type,
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
     * The most recent purchase of each thing by each identified customer, from both places a
     * store's work gets billed.
     *
     * Walk-in sales (bill_to 0/null) are skipped throughout — there is nobody to remind.
     *
     * Two sources, because a store's sales do not all land in one table. A counter sale, a POS
     * Retail sale and a billing invoice are all `manual_invoices`. Work that came in as a service
     * request — a room cleaning booked through the site, say — is billed on `service_invoices`
     * instead, whose lines sit in the same invoice_items table but hang off `invoice_id` and are
     * never given a `manual_invoice_id`. Sweeping only the first table is why a vendor who works
     * entirely through service requests saw nothing from this feature.
     *
     * The two differ in who they bill: a billing invoice names a store_customers row, a service
     * invoice names the marketplace `users` row that raised the request. Hence customer_type —
     * see ensureTables().
     */
    protected static function lastPurchases(int $storeId)
    {
        $fromBilling = DB::table('invoice_items as ii')
            ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)          // vendor_id holds the STORE id here
            ->whereNotNull('mi.bill_to')->where('mi.bill_to', '>', 0)
            ->where('mi.created_at', '>=', now()->subDays(400))
            // Customer + thing only. `label` is MAX(ii.name), and grouping on an aggregate's own
            // alias is an error in MySQL ("Can't group on 'label'") — it threw for every store,
            // which the job's per-store catch then swallowed as a log line.
            ->groupBy('mi.bill_to', 'ref_key')
            ->selectRaw("'store' as customer_type,
                mi.bill_to as customer_id,
                CASE WHEN ii.inv_id IS NULL OR ii.inv_id = 0
                     THEN CONCAT('svc:', LOWER(TRIM(ii.name)))
                     ELSE CONCAT('item:', ii.inv_id) END as ref_key,
                MAX(ii.name) as label,
                MAX(mi.created_at) as last_bought_at")
            ->get();

        if (!Schema::hasTable('service_invoices')) {
            return $fromBilling;
        }

        // bill_to_type is what says which table bill_to points into. Only 'user' is a person the
        // store sells to; a 'vendor' row is one business billing another and must never be
        // chased to rebuy.
        $fromServices = DB::table('invoice_items as ii')
            ->join('service_invoices as si', 'si.id', '=', 'ii.invoice_id')
            ->where('si.vendor_id', $storeId)
            ->where('si.bill_to_type', 'user')
            ->whereNotNull('si.bill_to')->where('si.bill_to', '>', 0)
            ->where('si.created_at', '>=', now()->subDays(400))
            ->groupBy('si.bill_to', 'ref_key')
            ->selectRaw("'user' as customer_type,
                si.bill_to as customer_id,
                CASE WHEN ii.inv_id IS NULL OR ii.inv_id = 0
                     THEN CONCAT('svc:', LOWER(TRIM(ii.name)))
                     ELSE CONCAT('item:', ii.inv_id) END as ref_key,
                MAX(ii.name) as label,
                MAX(si.created_at) as last_bought_at")
            ->get();

        return self::mergeByRefKey($fromBilling->concat($fromServices));
    }

    /**
     * Collapse rows that are the same purchase spelled differently.
     *
     * SQL groups a service line by LOWER(TRIM(name)), which unifies case and the spaces at either
     * end but nothing inside: "Room  Cleaning" and "Room Cleaning" arrive as two ref_keys, and
     * since ref_key is what records a thing as chased, the customer would be reminded once for
     * each spelling. matchKey() is the one definition of "the same name" in this feature, so the
     * key is rebuilt through it here and the rows folded together, keeping the latest purchase.
     */
    protected static function mergeByRefKey($rows)
    {
        $merged = [];
        foreach ($rows as $row) {
            if (str_starts_with($row->ref_key, 'svc:')) {
                $row->ref_key = 'svc:' . self::matchKey(substr($row->ref_key, 4));
            }

            $key  = $row->customer_type . '|' . $row->customer_id . '|' . $row->ref_key;
            $seen = $merged[$key] ?? null;

            if (!$seen || $row->last_bought_at > $seen->last_bought_at) {
                $merged[$key] = $row;
            }
        }

        return collect(array_values($merged));
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
        if ($days > 0) {
            return $days;
        }

        // No explicit rule — try to find the item the vendor already configured, by name.
        //
        // Service invoice lines save a typed name and no inv_id at all, so without this they
        // could never resolve to anything and the whole service-invoice sweep would be dead
        // weight. Matching "Room cleaning" on the bill to the "Room cleaning" service item means
        // the vendor sets the cycle once, on the item form, and it applies wherever that work
        // gets billed from. Nothing matching still means no reminder.
        return self::itemDaysByName($storeId, $key);
    }

    /**
     * repeat_days of this store's item whose name normalises to $key, or null.
     *
     * Loaded once per store per run. Ambiguous names (two items normalising the same) take the
     * larger cycle: chasing someone later than ideal is recoverable, chasing them too early is
     * the message that gets a number reported.
     */
    protected static function itemDaysByName(int $storeId, string $key): ?int
    {
        static $byName = [];

        if (!array_key_exists($storeId, $byName)) {
            $map = [];
            DB::table('inventory_items')
                ->where('store_id', $storeId)
                ->where('repeat_days', '>', 0)
                ->select('item_name', 'repeat_days')
                ->orderBy('id')
                ->each(function ($item) use (&$map) {
                    $name = self::matchKey($item->item_name);
                    if ($name === '') {
                        return;
                    }
                    $map[$name] = max($map[$name] ?? 0, (int) $item->repeat_days);
                });
            $byName[$storeId] = $map;
        }

        return $byName[$storeId][$key] ?? null;
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

        // Keyed by type as well as id — the same number in store_customers and in users is two
        // different people, and treating them as one would silence a real reminder.
        $chased = DB::table('wa_repeat_reminders')
            ->where('store_id', $storeId)
            ->whereIn('customer_id', $customerIds)
            ->get(['customer_type', 'customer_id', 'ref_key', 'reminded_at'])
            ->keyBy(fn($r) => ($r->customer_type ?: 'store') . '|' . $r->customer_id . '|' . $r->ref_key);

        return array_values(array_filter($due, function ($d) use ($chased) {
            $seen = $chased[$d->customer_type . '|' . $d->customer_id . '|' . $d->ref_key] ?? null;
            if (!$seen || !$seen->reminded_at) {
                return true;
            }
            // Chased before — only again once they have actually bought it since.
            return $d->last_bought_at->gt(Carbon::parse($seen->reminded_at));
        }));
    }

    /**
     * Anyone this store messaged inside the cooldown, as a set of phone keys.
     *
     * By phone rather than by id, deliberately: a customer who bought over the counter and also
     * booked a service request exists twice, once in each table, and the whole point of the
     * cooldown is that the human on the other end hears from this feature once a fortnight.
     *
     * Public because the appointment rebook sweep shares this table and this cooldown — in a
     * hospital a patient and a client are the same person, so "buy your rice again" and "time for
     * a check-up" landing in the same fortnight is one business pestering one human twice.
     */
    public static function recentlyMessagedPhones(int $storeId): array
    {
        return array_flip(array_filter(
            DB::table('wa_repeat_reminders')
                ->where('store_id', $storeId)
                ->whereNotNull('phone_key')
                ->where('reminded_at', '>=', now()->subDays(self::CUSTOMER_COOLDOWN_DAYS))
                ->distinct()->pluck('phone_key')->all()
        ));
    }

    /** Last 10 digits — how a number is compared everywhere in this feature. */
    public static function phoneKey(?string $phone): string
    {
        return substr(preg_replace('/[^0-9]/', '', (string) $phone) ?? '', -10);
    }

    /** Name and phone for whichever table this customer lives in. */
    protected static function resolveCustomer(string $type, int $id): ?object
    {
        if ($type === 'user') {
            $user = DB::table('users')->where('id', $id)->first(['f_name', 'name', 'phone']);
            if (!$user) {
                return null;
            }
            return (object) [
                'name'  => trim((string) ($user->f_name ?: $user->name)),
                'phone' => trim((string) $user->phone),
            ];
        }

        $customer = StoreCustomer::find($id);
        if (!$customer) {
            return null;
        }

        return (object) [
            'name'  => trim((string) $customer->f_name),
            'phone' => trim((string) $customer->phone),
        ];
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

        $tpl = WhatsAppService::roleTemplate($storeId, 'repeat_purchase', self::TEMPLATE);

        $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?: 'our store';
        $optedOut  = array_flip(array_map(
            fn($p) => self::phoneKey((string) $p),
            WhatsAppService::optedOutPhones($storeId)
        ));
        $onCooldown = self::recentlyMessagedPhones($storeId);

        // Grouped by the phone we will actually message, not by customer id. Someone who buys at
        // the counter AND books service requests is two rows in two tables and one person holding
        // one handset: they get a single message listing both halves, and both halves are marked
        // chased when it goes.
        $byPhone = [];
        foreach ($due as $d) {
            $who = self::resolveCustomer($d->customer_type, $d->customer_id);
            if (!$who || $who->phone === '') {
                continue;
            }

            $key = self::phoneKey($who->phone);
            if ($key === '' || isset($optedOut[$key]) || isset($onCooldown[$key])) {
                continue;
            }

            if (!isset($byPhone[$key])) {
                $byPhone[$key] = ['phone' => $who->phone, 'name' => $who->name, 'lines' => []];
            }
            // First non-empty name wins, so a bare users row does not blank out a real name.
            if ($byPhone[$key]['name'] === '' && $who->name !== '') {
                $byPhone[$key]['name'] = $who->name;
            }
            $byPhone[$key]['lines'][] = $d;
        }

        $sent = 0;
        foreach (array_slice($byPhone, 0, self::BATCH, true) as $phoneKey => $target) {
            try {
                if (!WhatsAppBilling::canAffordMessage($storeId, 'own')) {
                    Log::info('Repeat reminders stopped — wallet empty', ['store' => $storeId]);
                    break;
                }

                $res = $wa->sendTemplate(
                    $target['phone'],
                    $tpl['name'],
                    $tpl['language'],
                    [['type' => 'body', 'parameters' => array_map(
                        fn($v) => ['type' => 'text', 'text' => $v],
                        [
                            $target['name'] ?: 'there',
                            self::itemsPhrase($target['lines']),
                            $storeName,
                        ]
                    )]],
                    'repeat reminder'
                );

                // Marked as chased whatever Meta said. A failed send that gets retried tomorrow,
                // and the day after, is how one broken template turns into a daily charge.
                self::markReminded($storeId, (string) $phoneKey, $target['lines']);

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

    /**
     * One row per thing that was in the message.
     *
     * Every line is stamped with the phone actually messaged, whichever table its customer came
     * from — that stamp is what the fortnight cooldown reads next time.
     */
    protected static function markReminded(int $storeId, string $phoneKey, array $lines): void
    {
        foreach ($lines as $line) {
            DB::table('wa_repeat_reminders')->updateOrInsert(
                [
                    'store_id'      => $storeId,
                    'customer_type' => $line->customer_type,
                    'customer_id'   => $line->customer_id,
                    'ref_key'       => $line->ref_key,
                ],
                [
                    'phone_key'      => $phoneKey,
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
