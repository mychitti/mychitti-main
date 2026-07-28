<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use App\Models\AccountTransaction;
use App\Models\StoreWallet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Billing for the WhatsApp Business Platform (MC Vendor Hub Master Pricing, Part B).
 *
 * Everything a vendor pays for WhatsApp goes through here:
 *   - one-time setup fee, charged when the vendor activates the platform;
 *   - recurring monthly platform fee, auto-renewed by `whatsapp:bill` (daily);
 *   - optional dedicated account manager, billed with the monthly renewal;
 *   - extra message-template slots beyond the 4 included, one-time per slot;
 *   - chatbot auto-reply token packs (1M tokens per pack, priced in USD);
 *   - AI Agent monthly plans (lead & appointment management) and their token top-ups.
 *
 * Two chatbots, two token pools, billed differently:
 *   POOL_CHATBOT — the knowledge-base bot every WhatsApp store gets. Pay-as-you-go packs.
 *   POOL_AGENT   — the AI Agent. A monthly plan grants an allowance that resets each cycle;
 *                  top-ups are bought per million and carry over.
 *
 * Money always moves the same way as the rest of the vendor panel: debit
 * StoreWallet.total_earning, write an AccountTransaction, and keep a line item in
 * wa_billing_invoices. Every charge carries a `ref` that is unique per store, so a
 * retried renewal can never double-charge.
 *
 * Prices are the published list prices; an admin can override any of them from the
 * `whatsapp_config` business setting without a deploy (see config()).
 */
class WhatsAppBilling
{
    /** Recurring platform fee, ₹/month, exclusive of GST. */
    const PLATFORM_FEE_MONTHLY = 1299;

    /** One-time onboarding fee, exclusive of GST. */
    const SETUP_FEE = 2999;

    /** Optional dedicated account manager, ₹/month, exclusive of GST. */
    const ACCOUNT_MANAGER_MONTHLY = 999;

    /**
     * Per-message charges, exclusive of GST. The two rates are mutually exclusive — a message
     * is billed at one or the other, never both:
     *   OWN      — the vendor messaged their own contact list (Platform Usage Fee).
     *   PLATFORM — the vendor used MC Vendor Hub's customer database (Data Usage Charge).
     *
     * Billed at dispatch, so failed sends count: the message left the platform either way.
     */
    const MESSAGE_FEE_OWN      = 0.06;
    const MESSAGE_FEE_PLATFORM = 0.12;

    /** Message templates included in the platform fee. */
    const INCLUDED_TEMPLATES = 4;

    /** One-time fee for each template slot beyond the included quota. */
    const EXTRA_TEMPLATE_FEE = 99;

    /** Knowledge-base chatbot token pack: 1M tokens for USD 7. */
    const TOKEN_PACK_TOKENS = 1000000;
    const TOKEN_PACK_USD    = 7;

    /** Fallback USD→INR rate when the admin has not set one. */
    const DEFAULT_USD_INR = 90;

    const GST_PERCENT = 18;

    /** Token pools. Each is metered and topped up separately. */
    const POOL_CHATBOT = 'chatbot';
    const POOL_AGENT   = 'agent';

    const ONE_MILLION = 1000000;

    /**
     * AI Agent monthly plans — lead & appointment management (book, reschedule, status, and
     * sharing records the vendor has allowed). Prices exclusive of GST; tokens reset each cycle.
     */
    const AGENT_PLANS = [
        'starter' => ['label' => 'AI Agent — Starter', 'price' => 3999, 'tokens' => 1000000],
        'pro'     => ['label' => 'AI Agent — Pro',     'price' => 6999, 'tokens' => 3000000],
    ];

    /** AI Agent token top-up, ₹ per million, exclusive of GST. Carries over between cycles. */
    const AGENT_TOPUP_PER_MILLION = 700;

    /**
     * Days a subscription keeps working after a failed renewal. The daily command retries
     * inside this window; past it the subscription reads as inactive.
     */
    const GRACE_DAYS = 5;

    /** Chars per token — the estimate used to meter auto-reply usage (no provider usage API). */
    const CHARS_PER_TOKEN = 4;

    /* ------------------------------------------------------------------ schema */

    /** Idempotent schema bootstrap — same approach as the rest of the WhatsApp module. */
    public static function ensureTables(): void
    {
        if (!Schema::hasTable('wa_subscriptions')) {
            DB::statement("CREATE TABLE `wa_subscriptions` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `monthly_fee` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `account_manager` TINYINT(1) NOT NULL DEFAULT 0,
                `setup_fee_paid` TINYINT(1) NOT NULL DEFAULT 0,
                `extra_template_slots` INT NOT NULL DEFAULT 0,
                `started_at` DATE NULL,
                `current_period_end` DATE NULL,
                `last_charged_on` DATE NULL,
                `last_error` VARCHAR(255) NULL,
                `retry_count` INT NOT NULL DEFAULT 0,
                `cancelled_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wa_sub_store` (`store_id`),
                KEY `wa_sub_period` (`status`, `current_period_end`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!Schema::hasTable('wa_billing_invoices')) {
            DB::statement("CREATE TABLE `wa_billing_invoices` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `vendor_id` BIGINT NULL,
                `type` VARCHAR(30) NOT NULL,
                `description` VARCHAR(190) NOT NULL,
                `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `tax` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `total` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `period_start` DATE NULL,
                `period_end` DATE NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'paid',
                `note` VARCHAR(255) NULL,
                `ref` VARCHAR(80) NOT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wa_inv_ref` (`store_id`, `ref`),
                KEY `wa_inv_store` (`store_id`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!Schema::hasTable('wa_agent_subscriptions')) {
            DB::statement("CREATE TABLE `wa_agent_subscriptions` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `plan` VARCHAR(20) NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `monthly_fee` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `included_tokens` BIGINT NOT NULL DEFAULT 0,
                `started_at` DATE NULL,
                `current_period_end` DATE NULL,
                `last_charged_on` DATE NULL,
                `last_error` VARCHAR(255) NULL,
                `retry_count` INT NOT NULL DEFAULT 0,
                `cancelled_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wa_agent_store` (`store_id`),
                KEY `wa_agent_period` (`status`, `current_period_end`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Two rows per store at most — one per token pool. plan_* is the AI Agent's monthly
        // allowance and resets on renewal; topup_* is bought and carries over.
        if (!Schema::hasTable('wa_token_wallets')) {
            DB::statement("CREATE TABLE `wa_token_wallets` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `pool` VARCHAR(20) NOT NULL DEFAULT 'chatbot',
                `plan_tokens` BIGINT NOT NULL DEFAULT 0,
                `plan_tokens_used` BIGINT NOT NULL DEFAULT 0,
                `topup_tokens` BIGINT NOT NULL DEFAULT 0,
                `topup_tokens_used` BIGINT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wa_tw_store_pool` (`store_id`, `pool`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } elseif (!Schema::hasColumn('wa_token_wallets', 'pool')) {
            // Upgrade the single-pool shape: keep whatever was already bought as chatbot top-up.
            DB::statement("ALTER TABLE `wa_token_wallets`
                ADD COLUMN `pool` VARCHAR(20) NOT NULL DEFAULT 'chatbot' AFTER `store_id`,
                ADD COLUMN `plan_tokens` BIGINT NOT NULL DEFAULT 0,
                ADD COLUMN `plan_tokens_used` BIGINT NOT NULL DEFAULT 0,
                ADD COLUMN `topup_tokens` BIGINT NOT NULL DEFAULT 0,
                ADD COLUMN `topup_tokens_used` BIGINT NOT NULL DEFAULT 0");
            DB::statement("UPDATE `wa_token_wallets` SET `topup_tokens` = `tokens_purchased`, `topup_tokens_used` = `tokens_used`");
            DB::statement("ALTER TABLE `wa_token_wallets` DROP INDEX `wa_tw_store`");
            DB::statement("ALTER TABLE `wa_token_wallets` ADD UNIQUE KEY `wa_tw_store_pool` (`store_id`, `pool`)");
        }

        if (!Schema::hasTable('wa_token_usage')) {
            DB::statement("CREATE TABLE `wa_token_usage` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `pool` VARCHAR(20) NOT NULL DEFAULT 'chatbot',
                `tokens` INT NOT NULL DEFAULT 0,
                `context` VARCHAR(40) NOT NULL DEFAULT 'auto reply',
                `created_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `wa_tu_store` (`store_id`, `pool`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } elseif (!Schema::hasColumn('wa_token_usage', 'pool')) {
            DB::statement("ALTER TABLE `wa_token_usage` ADD COLUMN `pool` VARCHAR(20) NOT NULL DEFAULT 'chatbot' AFTER `store_id`");
        }
    }

    /* ------------------------------------------------------------------ pricing */

    /** Admin override from the `whatsapp_config` business setting, else the list price. */
    public static function config(string $key, $default)
    {
        $cfg = Helpers::get_business_settings('whatsapp_config');
        if (is_array($cfg) && isset($cfg[$key]) && $cfg[$key] !== '' && is_numeric($cfg[$key])) {
            return $cfg[$key] + 0;
        }
        return $default;
    }

    public static function gstPercent(): float
    {
        return (float) static::config('gst_percent', self::GST_PERCENT);
    }

    public static function monthlyFee(): float
    {
        return (float) static::config('platform_fee', self::PLATFORM_FEE_MONTHLY);
    }

    public static function setupFee(): float
    {
        return (float) static::config('setup_fee', self::SETUP_FEE);
    }

    public static function accountManagerFee(): float
    {
        return (float) static::config('account_manager_fee', self::ACCOUNT_MANAGER_MONTHLY);
    }

    public static function includedTemplates(): int
    {
        return (int) static::config('included_templates', self::INCLUDED_TEMPLATES);
    }

    public static function extraTemplateFee(): float
    {
        return (float) static::config('extra_template_fee', self::EXTRA_TEMPLATE_FEE);
    }

    public static function usdInrRate(): float
    {
        return (float) static::config('usd_inr_rate', self::DEFAULT_USD_INR);
    }

    public static function messageFeeOwn(): float
    {
        return (float) static::config('message_fee_own', self::MESSAGE_FEE_OWN);
    }

    public static function messageFeePlatform(): float
    {
        return (float) static::config('message_fee_platform', self::MESSAGE_FEE_PLATFORM);
    }

    /** ₹ price of one 1M-token pack, before GST. Priced in USD, converted at the configured rate. */
    public static function tokenPackPrice(): float
    {
        return round(((float) static::config('token_pack_usd', self::TOKEN_PACK_USD)) * static::usdInrRate(), 2);
    }

    public static function tax(float $base): float
    {
        return round($base * static::gstPercent() / 100, 2);
    }

    public static function withTax(float $base): float
    {
        return round($base + static::tax($base), 2);
    }

    /* ------------------------------------------------------------- subscription */

    public static function subscription(int $storeId)
    {
        static::ensureTables();
        return DB::table('wa_subscriptions')->where('store_id', $storeId)->first();
    }

    /**
     * Is the store's WhatsApp platform subscription live? A failed renewal keeps working
     * through the grace window so a temporarily empty wallet does not cut service instantly.
     */
    public static function isActive(int $storeId): bool
    {
        $sub = static::subscription($storeId);
        if (!$sub || $sub->status === 'cancelled' || !$sub->current_period_end) {
            return false;
        }
        return Carbon::parse($sub->current_period_end)->addDays(self::GRACE_DAYS)->endOfDay()->isFuture();
    }

    /**
     * Activate the platform for a store: one-time setup fee (first time only) plus the first
     * month, both debited now. Nothing is activated unless the full amount could be charged.
     */
    public static function subscribe(int $storeId, bool $accountManager = false): array
    {
        static::ensureTables();
        $sub = static::subscription($storeId);

        if ($sub && $sub->status !== 'cancelled' && $sub->current_period_end
            && Carbon::parse($sub->current_period_end)->endOfDay()->isFuture()) {
            return ['success' => false, 'message' => 'WhatsApp platform is already active until ' . $sub->current_period_end . '.'];
        }

        $setupPaid = $sub && $sub->setup_fee_paid;
        $monthly   = static::monthlyFee() + ($accountManager ? static::accountManagerFee() : 0);
        $due       = ($setupPaid ? 0 : static::setupFee()) + $monthly;

        if (!static::walletCovers($storeId, static::withTax($due))) {
            return [
                'success' => false,
                'message' => 'Insufficient wallet balance. Activating WhatsApp needs ' . _price(static::withTax($due))
                    . ' (' . _price($due) . ' + ' . static::gstPercent() . '% GST). Recharge your wallet and try again.',
            ];
        }

        if (!$setupPaid) {
            $setup = static::charge(
                $storeId,
                'setup',
                'WhatsApp Business Platform — one-time setup',
                static::setupFee(),
                'wa_setup_' . $storeId
            );
            if (!$setup['success']) {
                return $setup;
            }
        }

        $periodStart = now()->toDateString();
        $periodEnd   = now()->addMonth()->toDateString();
        $charge = static::charge(
            $storeId,
            'monthly',
            'WhatsApp Business Platform — monthly fee' . ($accountManager ? ' + dedicated account manager' : ''),
            $monthly,
            // Same ref shape as renew(): one paid month per store per calendar month, so a
            // double-clicked Activate (or a cancel-and-reactivate) can never bill twice.
            'wa_monthly_' . $storeId . '_' . now()->format('Y_m'),
            $periodStart,
            $periodEnd
        );
        if (!$charge['success']) {
            return $charge;
        }

        DB::table('wa_subscriptions')->updateOrInsert(
            ['store_id' => $storeId],
            [
                'status'             => 'active',
                'monthly_fee'        => static::monthlyFee(),
                'account_manager'    => $accountManager ? 1 : 0,
                'setup_fee_paid'     => 1,
                'started_at'         => $sub->started_at ?? $periodStart,
                'current_period_end' => $periodEnd,
                'last_charged_on'    => $periodStart,
                'last_error'         => null,
                'retry_count'        => 0,
                'cancelled_at'       => null,
                'updated_at'         => now(),
                'created_at'         => $sub->created_at ?? now(),
            ]
        );

        return ['success' => true, 'message' => 'WhatsApp Business Platform is active until ' . $periodEnd . '.'];
    }

    /**
     * Charge one more month. Called by `whatsapp:bill` for every subscription whose period has
     * ended. On failure the subscription is marked past_due and retried on the next run; the
     * grace window in isActive() decides when service actually stops.
     */
    public static function renew(int $storeId): array
    {
        static::ensureTables();
        $sub = static::subscription($storeId);
        if (!$sub || $sub->status === 'cancelled') {
            return ['success' => false, 'message' => 'No active WhatsApp subscription.'];
        }

        // Renewals never backfill missed months: the new period always starts from the old
        // period end, or today when the subscription has been past due for longer than a cycle.
        $base = $sub->current_period_end ? Carbon::parse($sub->current_period_end) : now();
        if ($base->lt(now()->subMonth())) {
            $base = now();
        }
        $periodStart = $base->toDateString();
        $periodEnd   = $base->copy()->addMonth()->toDateString();

        $amount = static::monthlyFee() + ($sub->account_manager ? static::accountManagerFee() : 0);
        $charge = static::charge(
            $storeId,
            'monthly',
            'WhatsApp Business Platform — monthly fee' . ($sub->account_manager ? ' + dedicated account manager' : ''),
            $amount,
            'wa_monthly_' . $storeId . '_' . Carbon::parse($periodStart)->format('Y_m'),
            $periodStart,
            $periodEnd
        );

        if (!$charge['success']) {
            DB::table('wa_subscriptions')->where('store_id', $storeId)->update([
                'status'      => 'past_due',
                'last_error'  => mb_substr($charge['message'], 0, 250),
                'retry_count' => $sub->retry_count + 1,
                'updated_at'  => now(),
            ]);
            return $charge;
        }

        DB::table('wa_subscriptions')->where('store_id', $storeId)->update([
            'status'             => 'active',
            'current_period_end' => $periodEnd,
            'last_charged_on'    => now()->toDateString(),
            'last_error'         => null,
            'retry_count'        => 0,
            'updated_at'         => now(),
        ]);

        return ['success' => true, 'message' => 'Renewed until ' . $periodEnd . '.', 'period_end' => $periodEnd];
    }

    /** Stop auto-renewal. The paid period is honoured — service ends at current_period_end. */
    public static function cancel(int $storeId): array
    {
        static::ensureTables();
        $sub = static::subscription($storeId);
        if (!$sub || $sub->status === 'cancelled') {
            return ['success' => false, 'message' => 'No active WhatsApp subscription.'];
        }

        DB::table('wa_subscriptions')->where('store_id', $storeId)->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
            'updated_at'   => now(),
        ]);

        return ['success' => true, 'message' => 'Auto-renewal stopped. WhatsApp stays active until ' . $sub->current_period_end . '.'];
    }

    /**
     * Add or drop the dedicated account manager. Adding it takes effect from the next renewal —
     * the current month has already been billed, so nothing is charged now.
     */
    public static function setAccountManager(int $storeId, bool $on): array
    {
        static::ensureTables();
        $sub = static::subscription($storeId);
        if (!$sub || $sub->status === 'cancelled') {
            return ['success' => false, 'message' => 'Activate the WhatsApp platform first.'];
        }

        DB::table('wa_subscriptions')->where('store_id', $storeId)
            ->update(['account_manager' => $on ? 1 : 0, 'updated_at' => now()]);

        return [
            'success' => true,
            'message' => $on
                ? 'Dedicated account manager added — ' . _price(static::withTax(static::accountManagerFee()))
                    . '/month is added from your next renewal on ' . $sub->current_period_end . '.'
                : 'Dedicated account manager removed from your next renewal.',
        ];
    }

    /* ------------------------------------------------------------ message usage */

    /**
     * Outbound message counts and what they cost for a date range, split by which contact list
     * the vendor used. Every outbound row counts — Part E bills at dispatch, not delivery, so a
     * failed send is still billable.
     *
     * `audience` is written at send time; rows from before that column existed fall back to the
     * context, which is how platform-database blasts have always been tagged.
     */
    public static function usageFor(int $storeId, string $from, string $to): array
    {
        WhatsAppService::ensureMessagesTable();

        $platformCase = "(`audience` = 'platform' OR `context` = 'nearby')";
        $row = DB::table('whatsapp_messages')
            ->where('store_id', $storeId)
            ->where('direction', 'out')
            ->whereBetween('sent_at', [$from, $to])
            ->selectRaw("SUM(CASE WHEN {$platformCase} THEN 1 ELSE 0 END) AS platform_count")
            ->selectRaw("SUM(CASE WHEN {$platformCase} THEN 0 ELSE 1 END) AS own_count")
            ->first();

        $own      = (int) ($row->own_count ?? 0);
        $platform = (int) ($row->platform_count ?? 0);

        return [
            'own'            => $own,
            'platform'       => $platform,
            'total'          => $own + $platform,
            'own_amount'     => round($own * static::messageFeeOwn(), 2),
            'platform_amount' => round($platform * static::messageFeePlatform(), 2),
            'amount'         => round($own * static::messageFeeOwn() + $platform * static::messageFeePlatform(), 2),
        ];
    }

    /** Usage so far in the current calendar month — what the vendor sees live on the billing page. */
    public static function usageThisMonth(int $storeId): array
    {
        return static::usageFor($storeId, now()->startOfMonth()->toDateTimeString(), now()->toDateTimeString());
    }

    /**
     * Bill one finished calendar month of message usage, in arrears. Kept on the calendar rather
     * than the subscription anniversary so the line reads "Message usage — March 2026" and
     * reconciles against a month of whatsapp_messages exactly.
     *
     * The ref makes it idempotent, so the daily command can call this every day of the month
     * and it lands once.
     */
    public static function chargeUsage(int $storeId, ?string $month = null): array
    {
        static::ensureTables();

        $start = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->subMonthNoOverflow()->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        if ($end->isFuture()) {
            return ['success' => false, 'message' => 'That month has not finished yet.', 'skipped' => true];
        }

        $usage = static::usageFor($storeId, $start->toDateTimeString(), $end->toDateTimeString());
        if ($usage['amount'] <= 0) {
            return ['success' => true, 'message' => 'No billable messages in ' . $start->format('M Y') . '.', 'skipped' => true];
        }

        $description = 'WhatsApp message usage — ' . $start->format('M Y') . ' ('
            . number_format($usage['own']) . ' own list @ ' . static::messageFeeOwn()
            . ', ' . number_format($usage['platform']) . ' MyChitti list @ ' . static::messageFeePlatform() . ')';

        return static::charge(
            $storeId,
            'usage',
            $description,
            $usage['amount'],
            'wa_usage_' . $storeId . '_' . $start->format('Y_m'),
            $start->toDateString(),
            $end->toDateString()
        );
    }

    /* ---------------------------------------------------------------- templates */

    /** How many message templates this store may have: 4 included + purchased slots. */
    public static function templateAllowance(int $storeId): int
    {
        $sub = static::subscription($storeId);
        return static::includedTemplates() + (int) ($sub->extra_template_slots ?? 0);
    }

    /** Buy one extra template slot (one-time). */
    public static function buyTemplateSlot(int $storeId): array
    {
        static::ensureTables();
        $sub = static::subscription($storeId);
        $slot = (int) ($sub->extra_template_slots ?? 0) + 1;

        $charge = static::charge(
            $storeId,
            'template_slot',
            'WhatsApp extra message template (slot ' . $slot . ')',
            static::extraTemplateFee(),
            'wa_tpl_slot_' . $storeId . '_' . $slot
        );
        if (!$charge['success']) {
            return $charge;
        }

        if ($sub) {
            DB::table('wa_subscriptions')->where('store_id', $storeId)
                ->update(['extra_template_slots' => $slot, 'updated_at' => now()]);
        } else {
            // A store can buy template slots without the full platform subscription — keep the
            // counter on the same row so templateAllowance() has one source of truth.
            DB::table('wa_subscriptions')->insert([
                'store_id'             => $storeId,
                'status'               => 'templates_only',
                'monthly_fee'          => 0,
                'extra_template_slots' => $slot,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

        return [
            'success' => true,
            'message' => 'Extra template slot added. You can now create up to ' . static::templateAllowance($storeId) . ' templates.',
        ];
    }

    /* ------------------------------------------------------------- AI Agent plan */

    public static function agentPlans(): array
    {
        $plans = [];
        foreach (self::AGENT_PLANS as $key => $plan) {
            $plans[$key] = [
                'label'  => $plan['label'],
                'price'  => (float) static::config('agent_' . $key . '_price', $plan['price']),
                'tokens' => (int) static::config('agent_' . $key . '_tokens', $plan['tokens']),
            ];
        }
        return $plans;
    }

    /** ₹ per million AI Agent top-up tokens, before GST. */
    public static function agentTopupPerMillion(): float
    {
        return (float) static::config('agent_topup_per_million', self::AGENT_TOPUP_PER_MILLION);
    }

    public static function agentSubscription(int $storeId)
    {
        static::ensureTables();
        return DB::table('wa_agent_subscriptions')->where('store_id', $storeId)->first();
    }

    /** AI Agent plan live (inside the same grace window as the platform subscription). */
    public static function agentActive(int $storeId): bool
    {
        $sub = static::agentSubscription($storeId);
        if (!$sub || $sub->status === 'cancelled' || !$sub->current_period_end) {
            return false;
        }
        return Carbon::parse($sub->current_period_end)->addDays(self::GRACE_DAYS)->endOfDay()->isFuture();
    }

    /**
     * Start an AI Agent plan (or switch plans). The first month is charged now and the plan's
     * token allowance is granted immediately; a plan switch re-grants at the new plan's size.
     */
    public static function subscribeAgent(int $storeId, string $plan): array
    {
        static::ensureTables();
        $plans = static::agentPlans();
        if (!isset($plans[$plan])) {
            return ['success' => false, 'message' => 'Unknown AI Agent plan.'];
        }

        // The agent rides on the WhatsApp platform — no point selling it standalone.
        if (!static::isActive($storeId)) {
            return ['success' => false, 'message' => 'Activate the WhatsApp Business Platform first, then add the AI Agent.'];
        }

        $sub = static::agentSubscription($storeId);
        if ($sub && $sub->plan === $plan && $sub->status !== 'cancelled'
            && $sub->current_period_end && Carbon::parse($sub->current_period_end)->endOfDay()->isFuture()) {
            return ['success' => false, 'message' => 'This AI Agent plan is already active until ' . $sub->current_period_end . '.'];
        }

        $price       = $plans[$plan]['price'];
        $periodStart = now()->toDateString();
        $periodEnd   = now()->addMonth()->toDateString();

        $charge = static::charge(
            $storeId,
            'agent_plan',
            $plans[$plan]['label'] . ' — monthly',
            $price,
            'wa_agent_' . $storeId . '_' . $plan . '_' . now()->format('Y_m'),
            $periodStart,
            $periodEnd
        );
        if (!$charge['success']) {
            return $charge;
        }

        DB::table('wa_agent_subscriptions')->updateOrInsert(
            ['store_id' => $storeId],
            [
                'plan'               => $plan,
                'status'             => 'active',
                'monthly_fee'        => $price,
                'included_tokens'    => $plans[$plan]['tokens'],
                'started_at'         => $sub->started_at ?? $periodStart,
                'current_period_end' => $periodEnd,
                'last_charged_on'    => $periodStart,
                'last_error'         => null,
                'retry_count'        => 0,
                'cancelled_at'       => null,
                'updated_at'         => now(),
                'created_at'         => $sub->created_at ?? now(),
            ]
        );

        static::grantAgentTokens($storeId, $plans[$plan]['tokens']);

        return [
            'success' => true,
            'message' => $plans[$plan]['label'] . ' is active until ' . $periodEnd . ' with '
                . number_format($plans[$plan]['tokens']) . ' tokens for this cycle.',
        ];
    }

    /** Charge the next AI Agent month and reset the cycle's token allowance. */
    public static function renewAgent(int $storeId): array
    {
        static::ensureTables();
        $sub = static::agentSubscription($storeId);
        if (!$sub || $sub->status === 'cancelled') {
            return ['success' => false, 'message' => 'No active AI Agent plan.'];
        }

        $plans = static::agentPlans();
        $plan  = $plans[$sub->plan] ?? null;
        if (!$plan) {
            return ['success' => false, 'message' => 'AI Agent plan "' . $sub->plan . '" no longer exists.'];
        }

        $base = $sub->current_period_end ? Carbon::parse($sub->current_period_end) : now();
        if ($base->lt(now()->subMonth())) {
            $base = now();
        }
        $periodStart = $base->toDateString();
        $periodEnd   = $base->copy()->addMonth()->toDateString();

        $charge = static::charge(
            $storeId,
            'agent_plan',
            $plan['label'] . ' — monthly',
            $plan['price'],
            'wa_agent_' . $storeId . '_' . $sub->plan . '_' . Carbon::parse($periodStart)->format('Y_m'),
            $periodStart,
            $periodEnd
        );

        if (!$charge['success']) {
            DB::table('wa_agent_subscriptions')->where('store_id', $storeId)->update([
                'status'      => 'past_due',
                'last_error'  => mb_substr($charge['message'], 0, 250),
                'retry_count' => $sub->retry_count + 1,
                'updated_at'  => now(),
            ]);
            return $charge;
        }

        DB::table('wa_agent_subscriptions')->where('store_id', $storeId)->update([
            'status'             => 'active',
            'included_tokens'    => $plan['tokens'],
            'monthly_fee'        => $plan['price'],
            'current_period_end' => $periodEnd,
            'last_charged_on'    => now()->toDateString(),
            'last_error'         => null,
            'retry_count'        => 0,
            'updated_at'         => now(),
        ]);

        static::grantAgentTokens($storeId, $plan['tokens']);

        return ['success' => true, 'message' => 'AI Agent renewed until ' . $periodEnd . '.', 'period_end' => $periodEnd];
    }

    public static function cancelAgent(int $storeId): array
    {
        static::ensureTables();
        $sub = static::agentSubscription($storeId);
        if (!$sub || $sub->status === 'cancelled') {
            return ['success' => false, 'message' => 'No active AI Agent plan.'];
        }

        DB::table('wa_agent_subscriptions')->where('store_id', $storeId)->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
            'updated_at'   => now(),
        ]);

        return ['success' => true, 'message' => 'AI Agent auto-renewal stopped. It stays active until ' . $sub->current_period_end . '.'];
    }

    /**
     * Grant the cycle's allowance. Plan tokens are Fair Usage and do NOT carry over — the
     * counter resets rather than accumulating. Purchased top-ups are untouched.
     */
    protected static function grantAgentTokens(int $storeId, int $tokens): void
    {
        static::ensureTokenWallet($storeId, self::POOL_AGENT);
        DB::table('wa_token_wallets')
            ->where('store_id', $storeId)->where('pool', self::POOL_AGENT)
            ->update([
                'plan_tokens'      => $tokens,
                'plan_tokens_used' => 0,
                'updated_at'       => now(),
            ]);
    }

    /** Buy AI Agent tokens — ₹700 + GST per million, carried over between cycles. */
    public static function buyAgentTokens(int $storeId, int $millions = 1): array
    {
        static::ensureTables();
        $millions = max(1, min(50, $millions));
        $base     = round(static::agentTopupPerMillion() * $millions, 2);
        $tokens   = self::ONE_MILLION * $millions;

        $charge = static::charge(
            $storeId,
            'agent_tokens',
            'AI Agent token top-up — ' . $millions . 'M tokens',
            $base,
            'wa_agent_tokens_' . $storeId . '_' . now()->format('YmdHis')
        );
        if (!$charge['success']) {
            return $charge;
        }

        static::ensureTokenWallet($storeId, self::POOL_AGENT);
        DB::table('wa_token_wallets')
            ->where('store_id', $storeId)->where('pool', self::POOL_AGENT)
            ->increment('topup_tokens', $tokens, ['updated_at' => now()]);

        return [
            'success' => true,
            'message' => number_format($tokens) . ' AI Agent tokens added. Balance: '
                . number_format(static::tokenBalance($storeId, self::POOL_AGENT)) . '.',
        ];
    }

    /* ------------------------------------------------------------- AI tokens */

    /** Token metering applies once a store is on the paid WhatsApp platform. */
    public static function aiMeteringApplies(int $storeId): bool
    {
        $sub = static::subscription($storeId);
        return $sub && in_array($sub->status, ['active', 'past_due'], true);
    }

    /** One wallet row per store per pool. The unique key makes a concurrent insert harmless. */
    protected static function ensureTokenWallet(int $storeId, string $pool): void
    {
        if (DB::table('wa_token_wallets')->where('store_id', $storeId)->where('pool', $pool)->exists()) {
            return;
        }
        try {
            DB::table('wa_token_wallets')->insert([
                'store_id'   => $storeId,
                'pool'       => $pool,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Another request created it first — nothing to do.
        }
    }

    public static function tokenWallet(int $storeId, string $pool)
    {
        static::ensureTables();
        return DB::table('wa_token_wallets')->where('store_id', $storeId)->where('pool', $pool)->first();
    }

    public static function tokenBalance(int $storeId, string $pool = self::POOL_CHATBOT): int
    {
        $row = static::tokenWallet($storeId, $pool);
        if (!$row) {
            return 0;
        }
        return max(0, (int) $row->plan_tokens - (int) $row->plan_tokens_used)
            + max(0, (int) $row->topup_tokens - (int) $row->topup_tokens_used);
    }

    /** Rough token count from character length — no provider usage API is available here. */
    public static function estimateTokens(string ...$parts): int
    {
        $chars = 0;
        foreach ($parts as $part) {
            $chars += mb_strlen($part);
        }
        return (int) ceil($chars / self::CHARS_PER_TOKEN);
    }

    /**
     * Spend tokens: the plan allowance first, the remainder from purchased top-ups. Usage is
     * recorded even when it overshoots the balance — the last reply of a cycle is never
     * half-billed, the vendor simply lands on a zero balance.
     */
    public static function recordTokenUsage(int $storeId, int $tokens, string $context = 'auto reply', string $pool = self::POOL_CHATBOT): void
    {
        if ($tokens <= 0) {
            return;
        }
        static::ensureTables();
        static::ensureTokenWallet($storeId, $pool);

        $row = static::tokenWallet($storeId, $pool);
        $planLeft  = max(0, (int) $row->plan_tokens - (int) $row->plan_tokens_used);
        $fromPlan  = min($tokens, $planLeft);
        $fromTopup = $tokens - $fromPlan;

        DB::table('wa_token_wallets')->where('id', $row->id)->update([
            'plan_tokens_used'  => (int) $row->plan_tokens_used + $fromPlan,
            'topup_tokens_used' => (int) $row->topup_tokens_used + $fromTopup,
            'updated_at'        => now(),
        ]);

        DB::table('wa_token_usage')->insert([
            'store_id'   => $storeId,
            'pool'       => $pool,
            'tokens'     => $tokens,
            'context'    => $context,
            'created_at' => now(),
        ]);
    }

    /** Buy knowledge-base chatbot tokens — 1M tokens per pack, priced in USD. */
    public static function buyTokenPack(int $storeId, int $packs = 1): array
    {
        static::ensureTables();
        $packs = max(1, min(50, $packs));
        $base  = round(static::tokenPackPrice() * $packs, 2);
        $tokens = self::TOKEN_PACK_TOKENS * $packs;

        $charge = static::charge(
            $storeId,
            'token_pack',
            'WhatsApp chatbot tokens — ' . $packs . 'M tokens',
            $base,
            'wa_tokens_' . $storeId . '_' . now()->format('YmdHis')
        );
        if (!$charge['success']) {
            return $charge;
        }

        static::ensureTokenWallet($storeId, self::POOL_CHATBOT);
        DB::table('wa_token_wallets')
            ->where('store_id', $storeId)->where('pool', self::POOL_CHATBOT)
            ->increment('topup_tokens', $tokens, ['updated_at' => now()]);

        return [
            'success' => true,
            'message' => number_format($tokens) . ' chatbot tokens added. Balance: '
                . number_format(static::tokenBalance($storeId, self::POOL_CHATBOT)) . '.',
        ];
    }

    /* ------------------------------------------------------------------ money */

    public static function vendorId(int $storeId): ?int
    {
        $id = DB::table('stores')->where('id', $storeId)->value('vendor_id');
        return $id ? (int) $id : null;
    }

    public static function walletBalance(int $storeId): float
    {
        $vendorId = static::vendorId($storeId);
        if (!$vendorId) {
            return 0;
        }
        return (float) (StoreWallet::where('vendor_id', $vendorId)->value('total_earning') ?? 0);
    }

    protected static function walletCovers(int $storeId, float $total): bool
    {
        return static::walletBalance($storeId) >= $total;
    }

    /**
     * Debit the vendor wallet for one WhatsApp charge and record it.
     *
     * `$ref` makes the charge idempotent per store: a ref already marked paid is a no-op, so a
     * re-run of the renewal command (or a double-clicked button) never bills twice.
     */
    public static function charge(
        int $storeId,
        string $type,
        string $description,
        float $base,
        string $ref,
        ?string $periodStart = null,
        ?string $periodEnd = null
    ): array {
        static::ensureTables();

        $paid = DB::table('wa_billing_invoices')
            ->where('store_id', $storeId)->where('ref', $ref)->where('status', 'paid')->exists();
        if ($paid) {
            return ['success' => true, 'message' => 'Already charged.', 'skipped' => true];
        }

        $vendorId = static::vendorId($storeId);
        if (!$vendorId) {
            return ['success' => false, 'message' => 'This store has no vendor account to bill.'];
        }

        $tax   = static::tax($base);
        $total = round($base + $tax, 2);

        $wallet  = StoreWallet::where('vendor_id', $vendorId)->first();
        $balance = $wallet ? (float) $wallet->total_earning : 0;

        if (!$wallet || $balance < $total) {
            static::writeInvoice($storeId, $vendorId, $type, $description, $base, $tax, $total, $ref, 'failed', 'Insufficient wallet balance', $periodStart, $periodEnd);
            return [
                'success' => false,
                'message' => 'Insufficient wallet balance. ' . _price($total) . ' is due ('
                    . _price($base) . ' + ' . static::gstPercent() . '% GST) and your balance is ' . _price($balance) . '.',
                'total'   => $total,
            ];
        }

        try {
            DB::transaction(function () use ($wallet, $vendorId, $storeId, $type, $description, $base, $tax, $total, $ref, $periodStart, $periodEnd) {
                // Balance lives in total_earning — the same field the other add-ons debit.
                $wallet->decrement('total_earning', $total);

                $txn = new AccountTransaction();
                $txn->current_balance = $wallet->total_earning;
                $txn->from_type  = 'store';
                $txn->from_id    = $vendorId;
                $txn->amount     = $total;
                $txn->method     = 'wallet';
                $txn->type       = 'debit';
                $txn->action     = 'debit';
                $txn->reason     = $description;
                $txn->created_by = 'system';
                $txn->save();

                static::writeInvoice($storeId, $vendorId, $type, $description, $base, $tax, $total, $ref, 'paid', null, $periodStart, $periodEnd);
            });
        } catch (\Throwable $e) {
            Log::error('WA billing charge failed', ['store' => $storeId, 'ref' => $ref, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not complete the charge. Please try again.'];
        }

        return ['success' => true, 'message' => _price($total) . ' charged to your wallet.', 'total' => $total];
    }

    protected static function writeInvoice(
        int $storeId,
        ?int $vendorId,
        string $type,
        string $description,
        float $base,
        float $tax,
        float $total,
        string $ref,
        string $status,
        ?string $note,
        ?string $periodStart,
        ?string $periodEnd
    ): void {
        DB::table('wa_billing_invoices')->updateOrInsert(
            ['store_id' => $storeId, 'ref' => $ref],
            [
                'vendor_id'    => $vendorId,
                'type'         => $type,
                'description'  => $description,
                'amount'       => $base,
                'tax'          => $tax,
                'total'        => $total,
                'period_start' => $periodStart,
                'period_end'   => $periodEnd,
                'status'       => $status,
                'note'         => $note,
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );
    }

    public static function invoices(int $storeId, int $limit = 20)
    {
        static::ensureTables();
        return DB::table('wa_billing_invoices')
            ->where('store_id', $storeId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
