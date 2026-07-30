<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

/**
 * Razorpay Subscriptions for the WhatsApp monthly plan fee.
 *
 * The monthly fee is a prepaid recurring auto-debit: the vendor authorises a mandate (UPI
 * Autopay / card / e-NACH) once, and from then on Razorpay charges on its own schedule and tells
 * us by webhook. Nothing here initiates a charge — see RazorPayWebhookController for the side
 * that actually advances the billing period.
 *
 * One mandate per store, whatever the tier. Moving between plans cancels the old mandate and
 * raises a new one for the new amount — two live mandates would debit for both tiers.
 *
 * Credentials come from the same addon_settings row the one-time checkout uses, so switching the
 * gateway between test and live moves both together.
 */
class WhatsAppRecurring
{
    /** Razorpay bills for a fixed number of cycles; 120 months is effectively "until cancelled". */
    const TOTAL_CYCLES = 120;

    /** Cached plan ids, keyed by amount in paise, so we create one Plan per distinct price. */
    const PLAN_MAP_KEY = 'wa_rzp_plan_map';

    /** Webhook secret configured in the Razorpay dashboard. */
    const WEBHOOK_SECRET_KEY = 'wa_rzp_webhook_secret';

    public static function api(): ?Api
    {
        $config = DB::table('addon_settings')
            ->where('key_name', 'razor_pay')
            ->where('settings_type', 'payment_config')
            ->first();

        if (!$config) {
            return null;
        }

        $values = json_decode($config->mode === 'live' ? $config->live_values : $config->test_values);
        if (!$values || empty($values->api_key) || empty($values->api_secret)) {
            return null;
        }

        return new Api($values->api_key, $values->api_secret);
    }

    /**
     * The gateway's public key id, for opening Razorpay Checkout in the browser. Safe to render
     * into a page — it identifies the merchant, it does not authorise anything on its own.
     */
    public static function publicKey(): ?string
    {
        $config = DB::table('addon_settings')
            ->where('key_name', 'razor_pay')
            ->where('settings_type', 'payment_config')
            ->first();

        if (!$config) {
            return null;
        }

        $values = json_decode($config->mode === 'live' ? $config->live_values : $config->test_values);
        return $values->api_key ?? null;
    }

    /**
     * Which deployment created a subscription. Staging and production register their own webhook
     * against the same Razorpay account, so both are delivered every event — this is what lets
     * each one recognise its own and ignore the other's. Without it a staging test writes into
     * whichever production store happens to share the id, via the notes.store_id fallback.
     */
    public static function instanceTag(): string
    {
        return (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'unknown');
    }

    public static function webhookSecret(): ?string
    {
        $secret = BusinessSetting::where('key', self::WEBHOOK_SECRET_KEY)->value('value');
        return $secret ?: (env('RAZORPAY_WEBHOOK_SECRET') ?: null);
    }

    /** Total the mandate debits each month — the plan fee (+ account manager) with GST. */
    public static function monthlyTotal(string $plan, bool $accountManager): float
    {
        return WhatsAppBilling::withTax(
            WhatsAppBilling::monthlyFee($plan) + ($accountManager ? WhatsAppBilling::accountManagerFee() : 0)
        );
    }

    /**
     * Razorpay Plan id for a monthly amount, created on first use and remembered afterwards.
     * Keyed on the amount AND the label so a price change makes a new plan rather than silently
     * charging the old amount, and so two products that happen to cost the same don't end up
     * sharing a plan whose name describes only one of them.
     */
    public static function planFor(Api $api, float $totalInr, string $label): ?string
    {
        $paise = (int) round($totalInr * 100);
        $key   = $paise . ':' . $label;
        $map   = json_decode(BusinessSetting::where('key', self::PLAN_MAP_KEY)->value('value') ?: '{}', true) ?: [];

        if (!empty($map[$key])) {
            return $map[$key];
        }

        try {
            $plan = $api->plan->create([
                'period'   => 'monthly',
                'interval' => 1,
                'item'     => [
                    'name'        => $label,
                    'amount'      => $paise,
                    'currency'    => 'INR',
                    'description' => $label . ' — monthly, GST included',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay plan create failed: ' . $e->getMessage());
            return null;
        }

        $map[$key] = $plan['id'];
        BusinessSetting::updateOrCreate(['key' => self::PLAN_MAP_KEY], ['value' => json_encode($map)]);

        return $plan['id'];
    }

    /**
     * Create the store's subscription and hand back the Razorpay-hosted mandate authorisation
     * URL. The subscription stays 'created' until the vendor authorises; subscription.activated
     * is what turns the platform on.
     */
    public static function start(int $storeId, string $plan, bool $accountManager): array
    {
        $api = static::api();
        if (!$api) {
            return ['success' => false, 'message' => 'Razorpay is not configured. Ask the admin to set the payment gateway keys.'];
        }

        $plans = WhatsAppBilling::plans();
        if (!isset($plans[$plan])) {
            return ['success' => false, 'message' => 'Unknown WhatsApp plan.'];
        }

        WhatsAppBilling::ensureTables();
        $existing = WhatsAppBilling::subscription($storeId);

        // Only a no-op when it is the same tier — an upgrade has a live mandate too, and that
        // is exactly the case that needs a new one raised.
        if ($existing && $existing->rzp_subscription_id && $existing->mandate_status === 'active'
            && ($existing->plan ?? WhatsAppBilling::DEFAULT_PLAN) === $plan) {
            return ['success' => false, 'message' => 'Monthly auto-debit for ' . $plans[$plan]['label'] . ' is already authorised for this store.'];
        }

        $total = static::monthlyTotal($plan, $accountManager);
        $label = 'WhatsApp ' . $plans[$plan]['label'] . ($accountManager ? ' + account manager' : '');

        $planId = static::planFor($api, $total, $label);
        if (!$planId) {
            return ['success' => false, 'message' => 'Could not set up the recurring plan with Razorpay. Please try again.'];
        }

        // Send the vendor back to the mandate they already have instead of creating another one.
        // Coming back to this button — a closed tab, a browser back, an abandoned authorisation —
        // is normal, and every one of those clicks used to leave a live subscription behind at
        // Razorpay that nothing on our side pointed at any more. Only reused when the price is
        // unchanged; changing plan or toggling the account manager has to raise a fresh mandate
        // for the new amount.
        if ($existing && $existing->rzp_subscription_id && $existing->rzp_plan_id === $planId) {
            try {
                $open = $api->subscription->fetch($existing->rzp_subscription_id);
                if (in_array($open['status'] ?? '', ['created', 'authenticated', 'pending'], true)
                    && !empty($open['short_url'])) {
                    return ['success' => true, 'url' => $open['short_url'], 'id' => $open['id']];
                }
            } catch (\Throwable $e) {
                // Deleted at Razorpay, or an id from another mode — fall through and make a new one.
                Log::warning('Razorpay subscription re-fetch failed, creating a new one', [
                    'store'        => $storeId,
                    'subscription' => $existing->rzp_subscription_id,
                    'error'        => $e->getMessage(),
                ]);
            }
        }

        // Moving to a different tier: stop the old mandate before raising the new one, or the
        // vendor is debited for both.
        if ($existing && $existing->rzp_subscription_id && $existing->rzp_plan_id !== $planId) {
            try {
                $api->subscription->fetch($existing->rzp_subscription_id)->cancel(['cancel_at_cycle_end' => 0]);
            } catch (\Throwable $e) {
                Log::warning('Could not cancel the previous WhatsApp mandate', [
                    'store' => $storeId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // No return-to-site parameters here: the Subscriptions API rejects callback_url /
        // callback_method ("is/are not required and should not be sent"). The hosted page is
        // therefore the last stop — the vendor navigates back themselves, and the mandate is
        // confirmed by the subscription.activated webhook either way.
        $payload = [
            'plan_id'         => $planId,
            'total_count'     => self::TOTAL_CYCLES,
            'quantity'        => 1,
            'customer_notify' => 1,
            // The webhook resolves the store from here, so it still works if our row is lost.
            'notes'           => [
                'store_id'        => (string) $storeId,
                'purpose'         => 'whatsapp_monthly',
                'plan'            => $plan,
                'account_manager' => $accountManager ? '1' : '0',
                'env'             => static::instanceTag(),
            ],
        ];

        try {
            $subscription = $api->subscription->create($payload);
        } catch (\Throwable $e) {
            Log::error('Razorpay subscription create failed', ['store' => $storeId, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not start the auto-debit with Razorpay: ' . $e->getMessage()];
        }

        // The plan is written now so the webhook knows which tier it just collected for, and
        // therefore how many tokens the cycle buys. Not active until Razorpay collects —
        // subscription.charged does that.
        DB::table('wa_subscriptions')->updateOrInsert(
            ['store_id' => $storeId],
            [
                'plan'                => $plan,
                'rzp_subscription_id' => $subscription['id'],
                'rzp_plan_id'         => $planId,
                'mandate_status'      => 'pending',
                'account_manager'     => $accountManager ? 1 : 0,
                'monthly_fee'         => $plans[$plan]['price'],
                'included_tokens'     => $plans[$plan]['tokens'],
                'status'              => $existing->status ?? 'pending',
                'updated_at'          => now(),
                'created_at'          => $existing->created_at ?? now(),
            ]
        );

        return [
            'success' => true,
            'url'     => $subscription['short_url'],
            'id'      => $subscription['id'],
        ];
    }

    /**
     * Ask Razorpay what actually happened and write it down.
     *
     * The webhook is the normal path, but it only delivers events that fired while it existed
     * and was configured correctly — a mandate authorised before the endpoint was set up is
     * never re-sent, and the vendor is left staring at "Finish auto-debit setup" for a
     * subscription Razorpay considers active. This closes that gap by reading the truth
     * directly. Safe to call repeatedly: everything it writes is keyed the same way the webhook
     * keys it, so a later delivery of the same event changes nothing.
     */
    public static function reconcile(int $storeId): bool
    {
        $sub = WhatsAppBilling::subscription($storeId);
        if (!$sub || !$sub->rzp_subscription_id) {
            return false;
        }

        $api = static::api();
        if (!$api) {
            return false;
        }

        try {
            $remote = $api->subscription->fetch($sub->rzp_subscription_id);
        } catch (\Throwable $e) {
            Log::warning('Razorpay reconcile fetch failed', [
                'store'        => $storeId,
                'subscription' => $sub->rzp_subscription_id,
                'error'        => $e->getMessage(),
            ]);
            return false;
        }

        $status = (string) ($remote['status'] ?? '');
        $mandate = match ($status) {
            'active', 'authenticated' => 'active',
            'pending'                 => 'pending',
            'halted'                  => 'halted',
            'cancelled', 'completed', 'expired' => 'cancelled',
            default                   => null,
        };

        if ($mandate) {
            DB::table('wa_subscriptions')->where('store_id', $storeId)->update([
                'mandate_status' => $mandate,
                'updated_at'     => now(),
            ]);
        }

        // Money only counts once Razorpay says it collected some. paid_count is the number of
        // cycles actually charged, which is exactly what subscription.charged would have told us.
        if ((int) ($remote['paid_count'] ?? 0) < 1) {
            return (bool) $mandate;
        }

        try {
            $invoices = $api->invoice->all(['subscription_id' => $sub->rzp_subscription_id, 'count' => 10]);
        } catch (\Throwable $e) {
            Log::warning('Razorpay reconcile invoice lookup failed', ['store' => $storeId, 'error' => $e->getMessage()]);
            return (bool) $mandate;
        }

        foreach (($invoices['items'] ?? []) as $invoice) {
            if (($invoice['status'] ?? '') !== 'paid' || empty($invoice['payment_id'])) {
                continue;
            }

            // Same keying as the webhook (the Razorpay payment id), so replaying either path
            // rewrites one invoice row instead of granting a second month.
            WhatsAppBilling::recordGatewayMonthly(
                $storeId,
                (string) $invoice['payment_id'],
                (float) ($invoice['amount_paid'] ?? $invoice['amount'] ?? 0) / 100,
                isset($remote['current_start']) ? date('Y-m-d', (int) $remote['current_start']) : now()->toDateString(),
                isset($remote['current_end']) ? date('Y-m-d', (int) $remote['current_end']) : now()->addMonth()->toDateString()
            );
        }

        return true;
    }

    /**
     * Stop the mandate at Razorpay.
     *
     * $atCycleEnd keeps the month the vendor already paid for, which is what a normal
     * "stop auto-renewal" should do. Pass false to kill it on the spot — used when the account
     * is being deleted, where there is no one left to serve out the period for.
     */
    public static function cancel(int $storeId, bool $atCycleEnd = true): array
    {
        $sub = WhatsAppBilling::subscription($storeId);
        if (!$sub || !$sub->rzp_subscription_id) {
            return ['success' => false, 'message' => 'No Razorpay auto-debit is set up for this store.'];
        }

        $api = static::api();
        if (!$api) {
            return ['success' => false, 'message' => 'Razorpay is not configured.'];
        }

        try {
            $api->subscription->fetch($sub->rzp_subscription_id)
                ->cancel(['cancel_at_cycle_end' => $atCycleEnd ? 1 : 0]);
        } catch (\Throwable $e) {
            Log::error('Razorpay subscription cancel failed', ['store' => $storeId, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not cancel the auto-debit with Razorpay.'];
        }

        DB::table('wa_subscriptions')->where('store_id', $storeId)->update([
            'mandate_status' => 'cancelling',
            'updated_at'     => now(),
        ]);

        return ['success' => true, 'message' => 'Auto-debit will stop at the end of the paid period.'];
    }

    /** Does this store let Razorpay drive its monthly billing? */
    public static function isGatewayBilled(int $storeId): bool
    {
        $sub = WhatsAppBilling::subscription($storeId);
        return (bool) ($sub && $sub->rzp_subscription_id && in_array($sub->mandate_status, ['pending', 'active', 'cancelling'], true));
    }
}
