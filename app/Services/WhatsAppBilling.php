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
 *   - a recurring monthly plan, auto-renewed by `whatsapp:bill` (daily);
 *   - optional dedicated account manager, billed with the monthly renewal;
 *   - extra message-template slots beyond the included quota, one-time per slot;
 *   - AI token top-ups, on the plans that include a chatbot.
 *
 * Three plans, one ladder — a store is on exactly one of them at a time, and moving up
 * REPLACES the monthly fee rather than adding a second charge. See PLANS.
 *
 * One token pool per store. The plan's allowance resets at the start of every cycle and does
 * not carry over; purchased top-ups do. Which bot spends it — the knowledge-base bot or the AI
 * Agent — is a per-store setting (WhatsAppAgent), not a separate price. Basic has no chatbot at
 * all, so it is granted no allowance and cannot buy top-ups.
 *
 * ── How each charge is collected ───────────────────────────────────────────────
 *   Per-message usage    → VENDOR WALLET, hand to hand. Debited at dispatch by
 *                          chargeMessage(), so the balance drops as the vendor sends and
 *                          an empty wallet stops sending. Nothing is billed in arrears.
 *   One-time setup fee   → PAYMENT GATEWAY, collected directly at onboarding — it is
 *                          not a wallet debit and must not depend on wallet balance.
 *   Monthly plan fee     → PAYMENT GATEWAY, prepaid recurring auto-debit, taken at the
 *                          start of each cycle rather than billed after it.
 *
 * Two message rates, decided by who is being messaged rather than by which screen sent it:
 * a number in the store's own book (store_customers) is the cheaper own-list rate, and
 * everyone else is the Data Usage Charge — the vendor UI calls that second group "MyChitti
 * customers". See WhatsAppService::audienceFor().
 *
 * Mechanically a charge() debits StoreWallet.total_earning, writes an AccountTransaction,
 * and keeps a line item in wa_billing_invoices. Every charge carries a `ref` that is
 * unique per store, so a retried renewal can never double-charge.
 *
 * Prices are the published list prices; an admin can override any of them from the
 * `whatsapp_config` business setting without a deploy (see config()).
 */
class WhatsAppBilling
{
    /**
     * The monthly plans, cheapest first. A store is on exactly one — these are tiers, not
     * add-ons, so `price` is the whole monthly fee for that tier. Exclusive of GST.
     *
     *   bot        — may run a chatbot at all (knowledge base or AI Agent)
     *   agent      — may run the AI Agent: lead & appointment management
     *   tokens_in  — input allowance (prompt: system, thread history, the customer's message)
     *   tokens_out — output allowance (the reply the model writes back)
     *
     * The two allowances are separate buckets and are NOT interchangeable: running out of output
     * tokens stops replies even with input tokens to spare. Both reset at the start of each cycle
     * and neither carries over.
     */
    const PLANS = [
        'basic' => [
            'label'      => 'Basic',
            'price'      => 1299,
            'tokens_in'  => 0,
            'tokens_out' => 0,
            'bot'        => false,
            'agent'      => false,
            'blurb'      => 'Bulk message sending, message templates and the shared inbox. No chatbot.',
        ],
        'starter' => [
            'label'      => 'AI Agent — Starter',
            'price'      => 3999,
            'tokens_in'  => 300000,
            'tokens_out' => 700000,
            'bot'        => true,
            'agent'      => true,
            'blurb'      => 'Everything in Basic, plus the AI Agent answering, booking and rescheduling.',
        ],
        'pro' => [
            'label'      => 'AI Agent — Pro',
            'price'      => 6999,
            'tokens_in'  => 1000000,
            'tokens_out' => 2000000,
            'bot'        => true,
            'agent'      => true,
            'blurb'      => 'Everything in Starter, with three times the monthly token allowance.',
        ],
    ];

    /** What a store falls back to when nothing else is recorded. */
    const DEFAULT_PLAN = 'basic';

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

    /** Message templates included in the platform fee. Admin can override via whatsapp_config. */
    const INCLUDED_TEMPLATES = 4;

    /** One-time fee for each template slot beyond the included quota. */
    const EXTRA_TEMPLATE_FEE = 99;

    const GST_PERCENT = 18;

    /**
     * The one token pool a store has. Kept as a column rather than dropped so the wallet table
     * can grow a second pool later without another migration.
     */
    const POOL_PLAN = 'plan';

    const ONE_MILLION = 1000000;

    /** The two directions a token can be spent in. Metered and topped up separately. */
    const DIR_IN  = 'in';
    const DIR_OUT = 'out';

    /**
     * Token top-ups, ₹ per million, exclusive of GST. Output costs more than input — the same
     * split the published sheet uses. Unlike the plan allowance, top-ups carry over between
     * cycles, and each direction can only be spent in its own direction.
     */
    const TOPUP_IN_PER_MILLION  = 700;
    const TOPUP_OUT_PER_MILLION = 1200;

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
                `plan` VARCHAR(20) NOT NULL DEFAULT 'basic',
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `monthly_fee` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `included_tokens` BIGINT NOT NULL DEFAULT 0,
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
                `own_count` INT NOT NULL DEFAULT 0,
                `platform_count` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wa_inv_ref` (`store_id`, `ref`),
                KEY `wa_inv_store` (`store_id`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Carries the activation choice across the Razorpay redirect — the setup fee is collected
        // by the gateway, so the store_id is not in session by the time the hook fires.
        if (!Schema::hasTable('wa_tmp_setup')) {
            DB::statement("CREATE TABLE `wa_tmp_setup` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `plan` VARCHAR(20) NOT NULL DEFAULT 'basic',
                `account_manager` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `wa_tmp_setup_store` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Per-message charges roll up into one invoice row per store per day, so the row has to
        // carry the running counts that build its description.
        foreach (['own_count', 'platform_count'] as $column) {
            if (Schema::hasTable('wa_billing_invoices') && !Schema::hasColumn('wa_billing_invoices', $column)) {
                DB::statement("ALTER TABLE `wa_billing_invoices` ADD COLUMN `{$column}` INT NOT NULL DEFAULT 0");
            }
        }

        // Pointer to the platform tax invoice this charge raised, so the vendor can open the GST
        // document straight from the WhatsApp billing history. Null on wallet-funded rows, which
        // are covered by the invoice issued when the wallet was recharged.
        foreach ([
            'tax_invoice_id'  => 'BIGINT NULL',
            'tax_invoice_no'  => 'VARCHAR(60) NULL',
            'tax_invoice_pdf' => 'VARCHAR(190) NULL',
        ] as $column => $definition) {
            if (Schema::hasTable('wa_billing_invoices') && !Schema::hasColumn('wa_billing_invoices', $column)) {
                DB::statement("ALTER TABLE `wa_billing_invoices` ADD COLUMN `{$column}` {$definition}");
            }
        }

        // Older installs carry these on wa_subscriptions only after this upgrade — the AI Agent
        // used to be a second subscription stacked on the platform fee, and is now a tier of the
        // one plan. wa_agent_subscriptions is no longer read or written; drop it once you are
        // satisfied nothing needs the history.
        foreach ([
            'plan'            => "VARCHAR(20) NOT NULL DEFAULT 'basic'",
            'included_tokens' => "BIGINT NOT NULL DEFAULT 0",
        ] as $column => $definition) {
            if (Schema::hasTable('wa_subscriptions') && !Schema::hasColumn('wa_subscriptions', $column)) {
                DB::statement("ALTER TABLE `wa_subscriptions` ADD COLUMN `{$column}` {$definition}");
            }
        }
        if (Schema::hasTable('wa_tmp_setup') && !Schema::hasColumn('wa_tmp_setup', 'plan')) {
            DB::statement("ALTER TABLE `wa_tmp_setup` ADD COLUMN `plan` VARCHAR(20) NOT NULL DEFAULT 'basic' AFTER `store_id`");
        }

        // Razorpay Subscriptions state. A store with rzp_subscription_id set is billed by
        // Razorpay on its own schedule — renew() must leave it alone and let the webhook drive
        // current_period_end, or the vendor pays twice.
        foreach ([
            'rzp_subscription_id' => "VARCHAR(64) NULL",
            'rzp_plan_id'         => "VARCHAR(64) NULL",
            'mandate_status'      => "VARCHAR(20) NULL",
        ] as $column => $definition) {
            if (Schema::hasTable('wa_subscriptions') && !Schema::hasColumn('wa_subscriptions', $column)) {
                DB::statement("ALTER TABLE `wa_subscriptions` ADD COLUMN `{$column}` {$definition}");
            }
        }
        if (Schema::hasTable('wa_subscriptions') && !Schema::hasColumn('wa_subscriptions', 'rzp_idx_added')) {
            DB::statement("ALTER TABLE `wa_subscriptions` ADD COLUMN `rzp_idx_added` TINYINT(1) NOT NULL DEFAULT 1");
            DB::statement("ALTER TABLE `wa_subscriptions` ADD KEY `wa_sub_rzp` (`rzp_subscription_id`)");
        }

        // A one-time purchase the vendor is paying for at the gateway right now. The row is the
        // only thing tying the payment callback back to what was bought, and is deleted once
        // fulfilled — nothing here is a ledger, wa_billing_invoices is.
        if (!Schema::hasTable('wa_purchase_intents')) {
            DB::statement("CREATE TABLE `wa_purchase_intents` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `kind` VARCHAR(30) NOT NULL,
                `quantity` INT NOT NULL DEFAULT 1,
                `base` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `total` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `description` VARCHAR(190) NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `wa_intent_store` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // One row per store per pool, and today there is one pool. Four counters per direction:
        // plan_* is the cycle's allowance and resets on renewal, topup_* is bought and carries
        // over. Input and output never borrow from each other.
        if (!Schema::hasTable('wa_token_wallets')) {
            DB::statement("CREATE TABLE `wa_token_wallets` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `pool` VARCHAR(20) NOT NULL DEFAULT 'plan',
                `plan_tokens_in` BIGINT NOT NULL DEFAULT 0,
                `plan_tokens_in_used` BIGINT NOT NULL DEFAULT 0,
                `plan_tokens_out` BIGINT NOT NULL DEFAULT 0,
                `plan_tokens_out_used` BIGINT NOT NULL DEFAULT 0,
                `topup_tokens_in` BIGINT NOT NULL DEFAULT 0,
                `topup_tokens_in_used` BIGINT NOT NULL DEFAULT 0,
                `topup_tokens_out` BIGINT NOT NULL DEFAULT 0,
                `topup_tokens_out_used` BIGINT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wa_tw_store_pool` (`store_id`, `pool`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            if (!Schema::hasColumn('wa_token_wallets', 'pool')) {
                DB::statement("ALTER TABLE `wa_token_wallets` ADD COLUMN `pool` VARCHAR(20) NOT NULL DEFAULT 'plan' AFTER `store_id`");
                DB::statement("ALTER TABLE `wa_token_wallets` DROP INDEX `wa_tw_store`");
                DB::statement("ALTER TABLE `wa_token_wallets` ADD UNIQUE KEY `wa_tw_store_pool` (`store_id`, `pool`)");
            }

            // Split the single counter into a pair. Whatever a store already had is carried into
            // the input side — it was metered as one blended number, and input is the cheaper of
            // the two, so this never hands the vendor more value than they paid for.
            if (!Schema::hasColumn('wa_token_wallets', 'plan_tokens_in')) {
                foreach ([
                    'plan_tokens_in', 'plan_tokens_in_used', 'plan_tokens_out', 'plan_tokens_out_used',
                    'topup_tokens_in', 'topup_tokens_in_used', 'topup_tokens_out', 'topup_tokens_out_used',
                ] as $column) {
                    DB::statement("ALTER TABLE `wa_token_wallets` ADD COLUMN `{$column}` BIGINT NOT NULL DEFAULT 0");
                }
                if (Schema::hasColumn('wa_token_wallets', 'plan_tokens')) {
                    DB::statement("UPDATE `wa_token_wallets` SET
                        `plan_tokens_in` = `plan_tokens`, `plan_tokens_in_used` = `plan_tokens_used`,
                        `topup_tokens_in` = `topup_tokens`, `topup_tokens_in_used` = `topup_tokens_used`");
                } elseif (Schema::hasColumn('wa_token_wallets', 'tokens_purchased')) {
                    // The original poolless shape, which never had a plan allowance at all —
                    // everything a store held was bought.
                    DB::statement("UPDATE `wa_token_wallets` SET
                        `topup_tokens_in` = `tokens_purchased`, `topup_tokens_in_used` = `tokens_used`");
                }
            }
        }

        // One row per reply, carrying both directions so a month can be totalled either way.
        if (!Schema::hasTable('wa_token_usage')) {
            DB::statement("CREATE TABLE `wa_token_usage` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `pool` VARCHAR(20) NOT NULL DEFAULT 'plan',
                `tokens` INT NOT NULL DEFAULT 0,
                `tokens_in` INT NOT NULL DEFAULT 0,
                `tokens_out` INT NOT NULL DEFAULT 0,
                `context` VARCHAR(40) NOT NULL DEFAULT 'auto reply',
                `created_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `wa_tu_store` (`store_id`, `pool`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            if (!Schema::hasColumn('wa_token_usage', 'pool')) {
                DB::statement("ALTER TABLE `wa_token_usage` ADD COLUMN `pool` VARCHAR(20) NOT NULL DEFAULT 'plan' AFTER `store_id`");
            }
            if (!Schema::hasColumn('wa_token_usage', 'tokens_in')) {
                DB::statement("ALTER TABLE `wa_token_usage`
                    ADD COLUMN `tokens_in` INT NOT NULL DEFAULT 0,
                    ADD COLUMN `tokens_out` INT NOT NULL DEFAULT 0");
                DB::statement("UPDATE `wa_token_usage` SET `tokens_in` = `tokens`");
            }
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

    /**
     * The plan ladder with admin price/allowance overrides applied. Keyed by plan id, in the
     * order they should be shown.
     */
    public static function plans(): array
    {
        $plans = [];
        foreach (self::PLANS as $key => $plan) {
            $in  = (int) static::config('plan_' . $key . '_tokens_in', $plan['tokens_in']);
            $out = (int) static::config('plan_' . $key . '_tokens_out', $plan['tokens_out']);

            $plans[$key] = [
                'key'        => $key,
                'label'      => $plan['label'],
                'blurb'      => $plan['blurb'],
                'bot'        => $plan['bot'],
                'agent'      => $plan['agent'],
                'price'      => (float) static::config('plan_' . $key . '_price', $plan['price']),
                'tokens_in'  => $in,
                'tokens_out' => $out,
                // What the plan is advertised as ("1M tokens") — the two buckets added up.
                'tokens'     => $in + $out,
            ];
        }
        return $plans;
    }

    /** One plan, falling back to Basic for an id that is no longer sold. */
    public static function plan(?string $key): array
    {
        $plans = static::plans();
        return $plans[$key] ?? $plans[self::DEFAULT_PLAN];
    }

    /**
     * Which plan this store is on. Says nothing about whether it is paid up — see isActive() —
     * and falls back to the cheapest tier so plan() always has something to return. That default
     * is an internal convenience, NOT a statement that the store bought Basic: ask hasPlan()
     * before showing it to a vendor, or a store that has purchased nothing reads as "Basic".
     */
    public static function storePlan(int $storeId): string
    {
        $key = static::subscription($storeId)->plan ?? null;
        return isset(self::PLANS[$key]) ? $key : self::DEFAULT_PLAN;
    }

    /**
     * Has the store ever actually started a plan? True once a plan month has been collected or
     * scheduled — which is what separates a real subscriber from a row that exists only because
     * the setup fee was paid, or from storePlan()'s fallback.
     */
    public static function hasPlan(int $storeId): bool
    {
        $sub = static::subscription($storeId);

        return (bool) ($sub
            && $sub->current_period_end
            && in_array($sub->status, ['active', 'past_due', 'cancelled'], true));
    }

    /** Monthly fee for a plan; with no plan named, the cheapest one. */
    public static function monthlyFee(?string $plan = null): float
    {
        return (float) static::plan($plan ?? self::DEFAULT_PLAN)['price'];
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

    public static function messageFeeOwn(): float
    {
        return (float) static::config('message_fee_own', self::MESSAGE_FEE_OWN);
    }

    public static function messageFeePlatform(): float
    {
        return (float) static::config('message_fee_platform', self::MESSAGE_FEE_PLATFORM);
    }

    /** ₹ per million top-up tokens for one direction, before GST. Carried over between cycles. */
    public static function topupPerMillion(string $direction = self::DIR_IN): float
    {
        return $direction === self::DIR_OUT
            ? (float) static::config('topup_out_per_million', self::TOPUP_OUT_PER_MILLION)
            : (float) static::config('topup_in_per_million', self::TOPUP_IN_PER_MILLION);
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
     * TEMPORARY (development): store ids the WhatsApp module is visible to while the onboarding
     * and billing flow is being built. An empty array means everyone, which is where this goes
     * once the flow is signed off — delete the constant and pilotVisible() together.
     */
    const PILOT_STORE_IDS = [];

    /** Should this store see the WhatsApp module at all? See PILOT_STORE_IDS. */
    public static function pilotVisible(?int $storeId): bool
    {
        return empty(self::PILOT_STORE_IDS) || in_array((int) $storeId, self::PILOT_STORE_IDS, true);
    }

    /**
     * Has the store settled the one-time onboarding fee? This is what unlocks Embedded Signup —
     * connecting a number costs us the Meta onboarding whether or not the vendor ever subscribes,
     * so the fee is collected before the WABA is linked, not after.
     */
    public static function setupFeePaid(int $storeId): bool
    {
        $sub = static::subscription($storeId);
        return (bool) ($sub && $sub->setup_fee_paid);
    }

    /**
     * Is the store's WhatsApp platform subscription live? A failed renewal keeps working
     * through the grace window so a temporarily empty wallet does not cut service instantly.
     */
    public static function isActive(int $storeId): bool
    {
        $sub = static::subscription($storeId);
        if (!$sub || !$sub->current_period_end) {
            return false;
        }

        // A cancelled plan runs out the month it was paid for, but gets no grace window on top
        // of it — grace covers a failed renewal, and a cancelled subscription has none coming.
        return $sub->status === 'cancelled'
            ? Carbon::parse($sub->current_period_end)->endOfDay()->isFuture()
            : Carbon::parse($sub->current_period_end)->addDays(self::GRACE_DAYS)->endOfDay()->isFuture();
    }

    /** Does this store's plan include a chatbot at all? Basic does not. */
    public static function botIncluded(int $storeId): bool
    {
        return static::plan(static::storePlan($storeId))['bot'] && static::isActive($storeId);
    }

    /**
     * May this store run the AI Agent? True only on a paid-up Starter/Pro — the agent is a tier
     * of the plan now, not a subscription of its own.
     */
    public static function agentActive(int $storeId): bool
    {
        return static::plan(static::storePlan($storeId))['agent'] && static::isActive($storeId);
    }

    /**
     * Activate the platform for a store on a given plan, or move an existing store to another
     * plan. The month is debited from the wallet here; the one-time setup fee is not — it is
     * collected by Razorpay before this runs, and arrives with setup_fee_paid already set (see
     * markSetupPaidViaGateway). A store that never went through the gateway would still be
     * charged the setup fee from its wallet by the block below, which is the fallback for
     * reactivations and admin-side activation.
     *
     * Switching plans mid-cycle charges the new tier in full and re-grants its allowance. There
     * is no proration: the tiers are close enough together that a part-month credit costs more
     * to explain than it saves the vendor.
     */
    public static function subscribe(int $storeId, ?string $plan = null, bool $accountManager = false): array
    {
        static::ensureTables();
        $sub = static::subscription($storeId);

        $plan = isset(self::PLANS[$plan]) ? $plan : ($sub->plan ?? self::DEFAULT_PLAN);
        $meta = static::plan($plan);

        $live = $sub && $sub->status !== 'cancelled' && $sub->current_period_end
            && Carbon::parse($sub->current_period_end)->endOfDay()->isFuture();

        // Paid up on this very plan — nothing to sell. A different plan is a switch, and
        // switches are allowed mid-cycle.
        if ($live && ($sub->plan ?? self::DEFAULT_PLAN) === $plan) {
            return ['success' => false, 'message' => $meta['label'] . ' is already active until ' . $sub->current_period_end . '.'];
        }

        $setupPaid = $sub && $sub->setup_fee_paid;
        $monthly   = $meta['price'] + ($accountManager ? static::accountManagerFee() : 0);
        $due       = ($setupPaid ? 0 : static::setupFee()) + $monthly;

        if (!static::walletCovers($storeId, static::withTax($due))) {
            return [
                'success' => false,
                'message' => 'Insufficient wallet balance. ' . $meta['label'] . ' needs ' . _price(static::withTax($due))
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
            'WhatsApp ' . $meta['label'] . ' — monthly' . ($accountManager ? ' + dedicated account manager' : ''),
            $monthly,
            // Same ref shape as renew(): one paid month per store per plan per calendar month,
            // so a double-clicked Activate (or a cancel-and-reactivate) can never bill twice,
            // while an upgrade still charges the tier the vendor is moving to.
            'wa_monthly_' . $storeId . '_' . $plan . '_' . now()->format('Y_m'),
            $periodStart,
            $periodEnd
        );
        if (!$charge['success']) {
            return $charge;
        }

        DB::table('wa_subscriptions')->updateOrInsert(
            ['store_id' => $storeId],
            [
                'plan'               => $plan,
                'status'             => 'active',
                'monthly_fee'        => $meta['price'],
                'included_tokens'    => $meta['tokens'],
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

        static::grantPlanTokens($storeId, $meta);

        return [
            'success' => true,
            'message' => 'WhatsApp ' . $meta['label'] . ' is active until ' . $periodEnd
                . ($meta['tokens'] > 0
                    ? ' with ' . number_format($meta['tokens_in']) . ' input and '
                        . number_format($meta['tokens_out']) . ' output tokens for this cycle.'
                    : '.'),
        ];
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

        // Razorpay owns the schedule for mandate-billed stores and reports each debit through
        // the webhook. Charging here as well would take the same month twice — once from the
        // card and once from the wallet.
        if ($sub->rzp_subscription_id && in_array($sub->mandate_status, ['pending', 'active', 'cancelling'], true)) {
            return [
                'success' => true,
                'skipped' => true,
                'message' => 'Billed by Razorpay auto-debit — nothing to charge here.',
            ];
        }

        // Renewals never backfill missed months: the new period always starts from the old
        // period end, or today when the subscription has been past due for longer than a cycle.
        $base = $sub->current_period_end ? Carbon::parse($sub->current_period_end) : now();
        if ($base->lt(now()->subMonth())) {
            $base = now();
        }
        $periodStart = $base->toDateString();
        $periodEnd   = $base->copy()->addMonth()->toDateString();

        $plan   = $sub->plan ?? self::DEFAULT_PLAN;
        $meta   = static::plan($plan);
        $amount = $meta['price'] + ($sub->account_manager ? static::accountManagerFee() : 0);
        $charge = static::charge(
            $storeId,
            'monthly',
            'WhatsApp ' . $meta['label'] . ' — monthly' . ($sub->account_manager ? ' + dedicated account manager' : ''),
            $amount,
            'wa_monthly_' . $storeId . '_' . $plan . '_' . Carbon::parse($periodStart)->format('Y_m'),
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
            'monthly_fee'        => $meta['price'],
            'included_tokens'    => $meta['tokens'],
            'current_period_end' => $periodEnd,
            'last_charged_on'    => now()->toDateString(),
            'last_error'         => null,
            'retry_count'        => 0,
            'updated_at'         => now(),
        ]);

        // A new cycle, a fresh allowance. Basic grants zero, which is also what clears a
        // leftover allowance from a store that has downgraded to it.
        static::grantPlanTokens($storeId, $meta);

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

    /** ₹ for one outbound message at the given audience rate, before GST. */
    public static function messageFee(string $audience): float
    {
        return $audience === 'own' ? static::messageFeeOwn() : static::messageFeePlatform();
    }

    /** GST-inclusive cost of one message — what actually leaves the wallet. */
    public static function messageCost(string $audience): float
    {
        return static::withTax(static::messageFee($audience));
    }

    /**
     * Can the wallet cover one more message at this rate? Checked before dispatch so a store
     * that has run dry stops sending rather than going into a negative balance.
     */
    public static function canAffordMessage(int $storeId, string $audience): bool
    {
        return static::walletBalance($storeId) >= static::messageCost($audience);
    }

    /**
     * Debit one message from the wallet, at dispatch. This is the hand-to-hand charge: the
     * balance drops as the vendor sends, not at the end of the month.
     *
     * The wallet moves per message, but the ledger does not: one AccountTransaction and one
     * wa_billing_invoices line per store per day, both accumulating as the day goes on. A
     * 2,000-recipient campaign would otherwise bury the vendor's account statement under 2,000
     * seven-paise rows. `current_balance` on the rollup is therefore the balance after the most
     * recent message of that day, which is what makes the running row reconcile.
     *
     * Returns false when the wallet could not cover it — the caller has already dispatched, so
     * this is only a signal for logging; the gate that matters is canAffordMessage() before the
     * send. Never throws: a billing failure must not take down message delivery.
     */
    public static function chargeMessage(int $storeId, string $audience): bool
    {
        try {
            static::ensureTables();

            $base = static::messageFee($audience);
            if ($base <= 0) {
                return true;
            }

            $vendorId = static::vendorId($storeId);
            if (!$vendorId) {
                return false;
            }

            $tax   = static::tax($base);
            $total = round($base + $tax, 2);
            $day   = now();
            $ref   = 'wa_msgs_' . $storeId . '_' . $day->format('Ymd');

            DB::transaction(function () use ($storeId, $vendorId, $audience, $base, $tax, $total, $ref, $day) {
                $wallet = StoreWallet::where('vendor_id', $vendorId)->lockForUpdate()->first();
                if (!$wallet || (float) $wallet->total_earning < $total) {
                    throw new \RuntimeException('insufficient');
                }

                $wallet->decrement('total_earning', $total);

                // Roll the day up rather than writing a row per message.
                $invoice = DB::table('wa_billing_invoices')
                    ->where('store_id', $storeId)->where('ref', $ref)->lockForUpdate()->first();

                $own      = (int) ($invoice->own_count ?? 0) + ($audience === 'own' ? 1 : 0);
                $platform = (int) ($invoice->platform_count ?? 0) + ($audience === 'own' ? 0 : 1);

                DB::table('wa_billing_invoices')->updateOrInsert(
                    ['store_id' => $storeId, 'ref' => $ref],
                    [
                        'vendor_id'      => $vendorId,
                        'type'           => 'usage',
                        'description'    => 'WhatsApp messages — ' . $day->format('d M Y') . ' ('
                            . number_format($own) . ' own list @ ' . static::messageFeeOwn()
                            . ', ' . number_format($platform) . ' MyChitti customers @ ' . static::messageFeePlatform() . ')',
                        'amount'         => round((float) ($invoice->amount ?? 0) + $base, 2),
                        'tax'            => round((float) ($invoice->tax ?? 0) + $tax, 2),
                        'total'          => round((float) ($invoice->total ?? 0) + $total, 2),
                        'own_count'      => $own,
                        'platform_count' => $platform,
                        'period_start'   => $day->toDateString(),
                        'period_end'     => $day->toDateString(),
                        'status'         => 'paid',
                        'note'           => 'Charged from wallet at dispatch',
                        'updated_at'     => now(),
                        'created_at'     => $invoice->created_at ?? now(),
                    ]
                );

                // Same rollup on the vendor's account statement.
                $txn = AccountTransaction::where('from_type', 'store')
                    ->where('from_id', $vendorId)
                    ->where('reason', 'like', 'WhatsApp messages — ' . $day->format('d M Y') . '%')
                    ->whereDate('created_at', $day->toDateString())
                    ->lockForUpdate()
                    ->first() ?: new AccountTransaction();

                $txn->current_balance = $wallet->total_earning;
                $txn->from_type  = 'store';
                $txn->from_id    = $vendorId;
                $txn->amount     = round((float) ($txn->amount ?? 0) + $total, 2);
                $txn->method     = 'wallet';
                $txn->type       = 'debit';
                $txn->action     = 'debit';
                $txn->reason     = 'WhatsApp messages — ' . $day->format('d M Y')
                    . ' (' . number_format($own + $platform) . ' sent)';
                $txn->created_by = 'system';
                $txn->save();
            });

            return true;
        } catch (\Throwable $e) {
            if (!str_contains($e->getMessage(), 'insufficient')) {
                Log::error('WhatsApp per-message charge failed', ['store' => $storeId, 'error' => $e->getMessage()]);
            }
            return false;
        }
    }

    /**
     * Outbound message counts and what they cost for a date range, split by which contact list
     * the vendor used. Every outbound row counts — billed at dispatch, not delivery, so a
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
     * Bill one finished calendar month of message usage in arrears.
     *
     * DEPRECATED and no longer scheduled: per-message charges are taken from the wallet at
     * dispatch by chargeMessage(). Kept only so an admin can settle a month that predates the
     * hand-to-hand switch; running it over a month that was already charged at dispatch would
     * bill the vendor a second time, which is why nothing calls it automatically any more.
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
            . ', ' . number_format($usage['platform']) . ' MyChitti customers @ ' . static::messageFeePlatform() . ')';

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

    /* ------------------------------------------------- one-time gateway purchases */

    /**
     * Price a one-time purchase and remember what it was for, so the payment callback can
     * fulfil it. Tokens and template slots are bought by card/UPI like everything else — a
     * four-figure charge should ask for a payment, not silently drain the store wallet.
     *
     * Returns the intent row id and the GST-inclusive total the gateway must collect, or null
     * when the store may not buy this at all.
     */
    public static function createPurchaseIntent(int $storeId, string $kind, int $quantity = 1): ?array
    {
        static::ensureTables();
        $quantity = max(1, min(50, $quantity));

        switch ($kind) {
            case 'topup_tokens_in':
            case 'topup_tokens_out':
                // Basic has no chatbot to spend them, so it cannot buy them either.
                if (!static::plan(static::storePlan($storeId))['bot']) {
                    return null;
                }
                $direction = $kind === 'topup_tokens_out' ? self::DIR_OUT : self::DIR_IN;
                $base = round(static::topupPerMillion($direction) * $quantity, 2);
                $description = 'WhatsApp AI token top-up — ' . $quantity . 'M '
                    . ($direction === self::DIR_OUT ? 'output' : 'input') . ' tokens';
                break;

            case 'template_slot':
                $sub   = static::subscription($storeId);
                $first = (int) ($sub->extra_template_slots ?? 0) + 1;
                $last  = $first + $quantity - 1;
                $base  = round(static::extraTemplateFee() * $quantity, 2);
                $description = $quantity === 1
                    ? 'WhatsApp extra message template (slot ' . $first . ')'
                    : 'WhatsApp extra message templates — ' . $quantity . ' slots (' . $first . '–' . $last . ')';
                break;

            default:
                return null;
        }

        $total = static::withTax($base);

        $id = DB::table('wa_purchase_intents')->insertGetId([
            'store_id'    => $storeId,
            'kind'        => $kind,
            'quantity'    => $quantity,
            'base'        => $base,
            'total'       => $total,
            'description' => $description,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return ['id' => $id, 'base' => $base, 'total' => $total, 'description' => $description];
    }

    /**
     * Hand over what the vendor just paid for at the gateway — top-up tokens or an extra
     * template slot. Writes the invoice from the intent and grants the goods; the intent is
     * consumed by the caller.
     *
     * Keyed on the gateway transaction id, so a replayed callback rewrites one invoice row
     * rather than granting the tokens twice.
     */
    public static function fulfilPurchase(object $intent, ?string $txnId = null): void
    {
        static::ensureTables();

        $storeId = (int) $intent->store_id;
        $ref     = 'wa_buy_' . ($txnId ?: $intent->id);

        $alreadyPaid = DB::table('wa_billing_invoices')
            ->where('store_id', $storeId)->where('ref', $ref)->where('status', 'paid')->exists();

        $base = (float) $intent->base;
        $tax  = round((float) $intent->total - $base, 2);

        static::writeInvoice(
            $storeId,
            static::vendorId($storeId),
            $intent->kind,
            (string) $intent->description,
            $base,
            $tax,
            (float) $intent->total,
            $ref,
            'paid',
            'Paid by Razorpay' . ($txnId ? ' — ' . $txnId : ''),
            null,
            null
        );

        if ($alreadyPaid) {
            return;
        }

        static::billTaxInvoice($storeId, $ref, (string) $intent->description, $base, $tax, (float) $intent->total, $txnId);

        switch ($intent->kind) {
            case 'topup_tokens_in':
            case 'topup_tokens_out':
                $column = $intent->kind === 'topup_tokens_out' ? 'topup_tokens_out' : 'topup_tokens_in';
                static::ensureTokenWallet($storeId, self::POOL_PLAN);
                DB::table('wa_token_wallets')
                    ->where('store_id', $storeId)->where('pool', self::POOL_PLAN)
                    ->increment($column, self::ONE_MILLION * (int) $intent->quantity, ['updated_at' => now()]);
                break;

            case 'template_slot':
                static::grantTemplateSlot($storeId, (int) $intent->quantity);
                break;
        }
    }

    /** Add template slots to the store's allowance, creating the row if it has none. */
    protected static function grantTemplateSlot(int $storeId, int $count = 1): void
    {
        $count = max(1, $count);
        $sub   = static::subscription($storeId);
        $slots = (int) ($sub->extra_template_slots ?? 0) + $count;

        if ($sub) {
            DB::table('wa_subscriptions')->where('store_id', $storeId)
                ->update(['extra_template_slots' => $slots, 'updated_at' => now()]);
            return;
        }

        // A store can buy template slots without the full platform subscription — keep the
        // counter on the same row so templateAllowance() has one source of truth.
        DB::table('wa_subscriptions')->insert([
            'store_id'             => $storeId,
            'status'               => 'templates_only',
            'extra_template_slots' => $slots,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    /* ---------------------------------------------------------------- templates */

    /** How many message templates this store may have: the included quota + purchased slots. */
    public static function templateAllowance(int $storeId): int
    {
        $sub = static::subscription($storeId);
        return static::includedTemplates() + (int) ($sub->extra_template_slots ?? 0);
    }

    /** Buy extra template slots (one-time) from the wallet. */
    public static function buyTemplateSlot(int $storeId, int $slots = 1): array
    {
        static::ensureTables();
        $slots = max(1, min(50, $slots));

        $sub   = static::subscription($storeId);
        $first = (int) ($sub->extra_template_slots ?? 0) + 1;
        $last  = $first + $slots - 1;

        $charge = static::charge(
            $storeId,
            'template_slot',
            $slots === 1
                ? 'WhatsApp extra message template (slot ' . $first . ')'
                : 'WhatsApp extra message templates — ' . $slots . ' slots (' . $first . '–' . $last . ')',
            round(static::extraTemplateFee() * $slots, 2),
            // Keyed on the range, so a retried click for the same slots is a no-op while a
            // genuine second purchase gets its own ref.
            'wa_tpl_slot_' . $storeId . '_' . $first . '_' . $last
        );
        if (!$charge['success']) {
            return $charge;
        }

        static::grantTemplateSlot($storeId, $slots);

        return [
            'success' => true,
            'message' => $slots === 1
                ? 'Extra template slot added. You can now create up to ' . static::templateAllowance($storeId) . ' templates.'
                : $slots . ' extra template slots added. You can now create up to ' . static::templateAllowance($storeId) . ' templates.',
        ];
    }

    /* ------------------------------------------------------------- AI tokens */

    /**
     * Grant the cycle's allowance, both directions. Plan tokens are Fair Usage and do NOT carry
     * over — the counters are reset rather than accumulated. Purchased top-ups are untouched.
     *
     * Called on every activation, plan switch and renewal, Basic included: granting zero there
     * is exactly what strips the allowance from a store that has downgraded.
     */
    protected static function grantPlanTokens(int $storeId, array $plan): void
    {
        static::ensureTokenWallet($storeId, self::POOL_PLAN);
        DB::table('wa_token_wallets')
            ->where('store_id', $storeId)->where('pool', self::POOL_PLAN)
            ->update([
                'plan_tokens_in'       => (int) $plan['tokens_in'],
                'plan_tokens_in_used'  => 0,
                'plan_tokens_out'      => (int) $plan['tokens_out'],
                'plan_tokens_out_used' => 0,
                'updated_at'           => now(),
            ]);
    }

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

    /** Balance for one direction: what is left of the plan allowance plus purchased top-ups. */
    public static function tokenBalance(int $storeId, string $direction = self::DIR_IN, string $pool = self::POOL_PLAN): int
    {
        $row = static::tokenWallet($storeId, $pool);
        if (!$row) {
            return 0;
        }
        $d = $direction === self::DIR_OUT ? 'out' : 'in';

        return max(0, (int) $row->{"plan_tokens_{$d}"} - (int) $row->{"plan_tokens_{$d}_used"})
            + max(0, (int) $row->{"topup_tokens_{$d}"} - (int) $row->{"topup_tokens_{$d}_used"});
    }

    /**
     * Can this store afford another reply? A reply spends both directions, and the buckets do
     * not lend to each other, so either one hitting zero stops the bot.
     *
     * Returns the direction that has run out, or null when both have tokens left.
     */
    public static function exhaustedDirection(int $storeId, string $pool = self::POOL_PLAN): ?string
    {
        if (static::tokenBalance($storeId, self::DIR_IN, $pool) <= 0) {
            return self::DIR_IN;
        }
        if (static::tokenBalance($storeId, self::DIR_OUT, $pool) <= 0) {
            return self::DIR_OUT;
        }
        return null;
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
     * Spend one reply's tokens: input against the input buckets, output against the output ones,
     * each taking the plan allowance first and the remainder from purchased top-ups. The two
     * never borrow from each other — that is the whole point of metering them apart.
     *
     * Usage is recorded even when it overshoots the balance: the last reply of a cycle is never
     * half-billed, the vendor simply lands on a zero balance.
     */
    public static function recordTokenUsage(
        int $storeId,
        int $tokensIn,
        int $tokensOut,
        string $context = 'auto reply',
        string $pool = self::POOL_PLAN
    ): void {
        $tokensIn  = max(0, $tokensIn);
        $tokensOut = max(0, $tokensOut);
        if ($tokensIn + $tokensOut <= 0) {
            return;
        }

        static::ensureTables();
        static::ensureTokenWallet($storeId, $pool);

        $row    = static::tokenWallet($storeId, $pool);
        $update = ['updated_at' => now()];

        foreach ([self::DIR_IN => $tokensIn, self::DIR_OUT => $tokensOut] as $d => $spend) {
            $planLeft  = max(0, (int) $row->{"plan_tokens_{$d}"} - (int) $row->{"plan_tokens_{$d}_used"});
            $fromPlan  = min($spend, $planLeft);
            $fromTopup = $spend - $fromPlan;

            $update["plan_tokens_{$d}_used"]  = (int) $row->{"plan_tokens_{$d}_used"} + $fromPlan;
            $update["topup_tokens_{$d}_used"] = (int) $row->{"topup_tokens_{$d}_used"} + $fromTopup;
        }

        DB::table('wa_token_wallets')->where('id', $row->id)->update($update);

        DB::table('wa_token_usage')->insert([
            'store_id'   => $storeId,
            'pool'       => $pool,
            'tokens'     => $tokensIn + $tokensOut,
            'tokens_in'  => $tokensIn,
            'tokens_out' => $tokensOut,
            'context'    => $context,
            'created_at' => now(),
        ]);
    }

    /* ---------------------------------------------------------------- teardown */

    /**
     * Close out a store's WhatsApp billing because the account is being deleted.
     *
     * The mandate lives at Razorpay, not here — deleting our rows does nothing to it, and the
     * vendor's card would go on being debited every month for a business that no longer exists,
     * with the webhook re-creating the subscription row each time it collected. So the mandate
     * is cancelled outright rather than at cycle end: there is nobody left to serve the paid
     * period out for.
     *
     * Invoices and the ledger are deliberately left alone — a deleted account does not undo the
     * money that already changed hands, and those rows are the tax record of it.
     *
     * Never throws: a Razorpay outage must not make an account undeletable. It logs loudly
     * instead, because a mandate left running is a real charge on a real card.
     */
    public static function closeForDeletedStore(int $storeId): void
    {
        try {
            $sub = static::subscription($storeId);
            if (!$sub) {
                return;
            }

            if (!empty($sub->rzp_subscription_id)) {
                $res = WhatsAppRecurring::cancel($storeId, false);
                if (empty($res['success'])) {
                    Log::error('WhatsApp mandate still live for a deleted store — cancel it by hand at Razorpay', [
                        'store'        => $storeId,
                        'subscription' => $sub->rzp_subscription_id,
                        'reason'       => $res['message'] ?? 'unknown',
                    ]);
                }
            }

            DB::table('wa_subscriptions')->where('store_id', $storeId)->update([
                'status'         => 'cancelled',
                'mandate_status' => 'cancelled',
                'cancelled_at'   => now(),
                'updated_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp teardown failed for a deleted store', [
                'store' => $storeId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /* ------------------------------------------------------------- tax invoices */

    /**
     * Raise a platform tax invoice for a WhatsApp charge the PAYMENT GATEWAY collected, the same
     * way a custom-domain or webpage-template purchase does: a ManualInvoice with its line item,
     * the GST invoice PDF, and the ledger + day book entries that put the revenue in the books.
     *
     * Only gateway money comes through here. Wallet-funded charges — per-message usage, and the
     * wallet fallbacks in subscribe()/renew() — must NOT be invoiced again: wallet_recharge()
     * already issued a GST invoice when the vendor put the money in, so invoicing the spend as
     * well would count the same revenue twice and over-report GST.
     *
     * wa_billing_invoices stays the module's own running ledger and is written either way; this
     * is the vendor's actual downloadable tax document.
     *
     * Never throws — a failure here must not undo a payment that has already been captured.
     *
     * Returns ['id', 'no', 'pdf'] for the caller to stamp onto its wa_billing_invoices row, or
     * null when nothing was raised.
     */
    protected static function issueTaxInvoice(
        int $storeId,
        string $itemName,
        float $base,
        float $tax,
        float $total,
        ?string $txnId = null
    ): ?array {
        if ($total <= 0) {
            return null;
        }

        try {
            $invoice = new \App\Models\ManualInvoice();
            $invoice->invoice_id     = Helpers::generateInvoiceIdAdmin();
            $invoice->invoice_serial = (int) \App\Models\BusinessSetting::where('key', 'admin_bill_serial_number')->value('value') - 1;
            $invoice->vendor_id      = null;
            $invoice->bill_to        = $storeId;
            $invoice->bill_to_type   = 'vendor';
            $invoice->module_id      = DB::table('stores')->where('id', $storeId)->value('module_id');
            $invoice->total_amount   = $total;
            $invoice->payment_method = 'Online';
            $invoice->tax_type       = $tax > 0 ? 'gst' : 'non-gst';
            $invoice->payment_status = 'Paid';
            $invoice->payment_date   = now()->toDateString();
            $invoice->generated_by   = 'admin';
            $invoice->financial_year = _currentFinancialYear();
            $invoice->save();

            $item = new \App\Models\InvoiceItem();
            $item->rand_invoice_id   = $invoice->invoice_id;
            $item->manual_invoice_id = $invoice->id;
            $item->name              = $itemName;
            $item->qty               = 1;
            $item->price             = $base;
            $item->tax               = static::gstPercent();
            $item->hsn               = '';
            $item->save();

            try {
                $pdf = _createBillPdf($invoice, 'admin');
                if ($pdf && isset($pdf['pdf'])) {
                    $invoice->update(['pdf' => $pdf['pdf']]);
                }
            } catch (\Throwable $e) {
                // A missing PDF is recoverable — the invoice row is what matters.
                Log::warning('WhatsApp invoice PDF failed', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
            }

            $voucher = _masterLedgerEntry(
                [
                    'date'         => now(),
                    'amount'       => $total,
                    'status'       => 'approved',
                    'description'  => $itemName . ($txnId ? ' — ' . $txnId : ''),
                    'voucher_type' => 'Purchase',
                    'invoice_id'   => $invoice->getKey(),
                ],
                Helpers::ensureSubscriptionRevenueAccount(),
                Helpers::ensurePurchaseAccount('WhatsApp Business Platform', $storeId),
                'store',
                'admin',
                null,
                $storeId
            );

            _saveDayBookEntry(
                $total,
                'debit',
                $storeId,
                $itemName,
                $invoice->getKey(),
                $voucher?->id,
                null,
                $txnId,
                'Online'
            );

            return [
                'id'  => (int) $invoice->getKey(),
                'no'  => (string) $invoice->invoice_id,
                'pdf' => $invoice->pdf,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp tax invoice failed', ['store' => $storeId, 'item' => $itemName, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Raise the tax invoice for a charge and point its wa_billing_invoices row at it, so the
     * billing history can link straight to the GST document.
     */
    protected static function billTaxInvoice(
        int $storeId,
        string $ref,
        string $itemName,
        float $base,
        float $tax,
        float $total,
        ?string $txnId = null
    ): void {
        $issued = static::issueTaxInvoice($storeId, $itemName, $base, $tax, $total, $txnId);
        if (!$issued) {
            return;
        }

        DB::table('wa_billing_invoices')
            ->where('store_id', $storeId)->where('ref', $ref)
            ->update([
                'tax_invoice_id'  => $issued['id'],
                'tax_invoice_no'  => $issued['no'],
                'tax_invoice_pdf' => $issued['pdf'],
                'updated_at'      => now(),
            ]);
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
     * Record a setup fee that Razorpay already collected. No wallet movement and no
     * AccountTransaction — the money never passed through the vendor wallet — but the invoice
     * line is still written so wa_billing_invoices stays the complete ledger.
     *
     * Marks setup_fee_paid so the subscribe() that follows skips its own setup charge and only
     * debits the monthly. Shares the `wa_setup_{store}` ref with the wallet path, so a store can
     * never be billed the setup fee twice by either route.
     */
    public static function markSetupPaidViaGateway(int $storeId, ?string $txnId = null): void
    {
        static::ensureTables();

        $base  = static::setupFee();
        $tax   = static::tax($base);
        $total = round($base + $tax, 2);
        $ref   = 'wa_setup_' . $storeId;

        // Re-entrant: this is a payment callback, and the same fee must never raise a second
        // tax invoice if it fires twice.
        $alreadyPaid = DB::table('wa_billing_invoices')
            ->where('store_id', $storeId)->where('ref', $ref)->where('status', 'paid')->exists();

        static::writeInvoice(
            $storeId,
            static::vendorId($storeId),
            'setup',
            'WhatsApp Business Platform — one-time setup',
            $base,
            $tax,
            $total,
            $ref,
            'paid',
            'Paid by Razorpay' . ($txnId ? ' — ' . $txnId : ''),
            null,
            null
        );

        if (!$alreadyPaid) {
            static::billTaxInvoice($storeId, $ref, 'WhatsApp Business Platform — one-time onboarding', $base, $tax, $total, $txnId);
        }

        $sub = static::subscription($storeId);
        DB::table('wa_subscriptions')->updateOrInsert(
            ['store_id' => $storeId],
            [
                'setup_fee_paid' => 1,
                // Activation is subscribe()'s job — leaving current_period_end null keeps
                // isActive() false until the first month is actually paid.
                'status'         => $sub->status ?? 'pending',
                'updated_at'     => now(),
                'created_at'     => $sub->created_at ?? now(),
            ]
        );
    }

    /**
     * Record a monthly fee that the Razorpay mandate already collected, and move the store's
     * period forward. No wallet movement — the debit happened on the vendor's card/UPI.
     *
     * $total is what Razorpay actually captured (GST inclusive); the base/tax split is derived
     * from it rather than recomputed from the price list, so a mid-cycle price change can never
     * make the invoice disagree with the amount taken.
     *
     * Keyed on the Razorpay payment id, so a replayed webhook rewrites the same invoice row
     * instead of granting a second month.
     */
    public static function recordGatewayMonthly(
        int $storeId,
        string $paymentId,
        float $total,
        ?string $periodStart,
        ?string $periodEnd
    ): void {
        static::ensureTables();

        $ref = 'wa_rzp_' . $paymentId;
        $alreadyPaid = DB::table('wa_billing_invoices')
            ->where('store_id', $storeId)->where('ref', $ref)->where('status', 'paid')->exists();

        $base = round($total / (1 + static::gstPercent() / 100), 2);
        $tax  = round($total - $base, 2);

        // The mandate was raised against a named tier (WhatsAppRecurring::start writes it), so
        // the row already says which plan this month bought — and therefore how many tokens.
        $sub  = static::subscription($storeId);
        $meta = static::plan($sub->plan ?? null);

        static::writeInvoice(
            $storeId,
            static::vendorId($storeId),
            'monthly',
            'WhatsApp ' . $meta['label'] . ' — monthly (Razorpay auto-debit)',
            $base,
            $tax,
            $total,
            $ref,
            'paid',
            'Razorpay ' . $paymentId,
            $periodStart,
            $periodEnd
        );

        if ($alreadyPaid) {
            return;
        }

        static::billTaxInvoice(
            $storeId,
            $ref,
            'WhatsApp ' . $meta['label'] . ' — monthly'
                . ($periodStart ? ' (' . Carbon::parse($periodStart)->format('d M Y') . ')' : ''),
            $base,
            $tax,
            $total,
            $paymentId
        );

        DB::table('wa_subscriptions')->updateOrInsert(
            ['store_id' => $storeId],
            [
                'status'             => 'active',
                'mandate_status'     => 'active',
                'monthly_fee'        => $meta['price'],
                'included_tokens'    => $meta['tokens'],
                'current_period_end' => $periodEnd,
                'last_charged_on'    => $periodStart ?? now()->toDateString(),
                'started_at'         => $sub->started_at ?? ($periodStart ?? now()->toDateString()),
                'last_error'         => null,
                'retry_count'        => 0,
                'cancelled_at'       => null,
                'updated_at'         => now(),
                'created_at'         => $sub->created_at ?? now(),
            ]
        );

        // A collected month buys the cycle's tokens.
        static::grantPlanTokens($storeId, $meta);
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
