<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

/**
 * Razorpay Subscriptions for the WhatsApp monthly platform fee.
 *
 * The monthly fee is a prepaid recurring auto-debit: the vendor authorises a mandate (UPI
 * Autopay / card / e-NACH) once, and from then on Razorpay charges on its own schedule and tells
 * us by webhook. Nothing here initiates a charge — see RazorPayWebhookController for the side
 * that actually advances the billing period.
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

    public static function webhookSecret(): ?string
    {
        $secret = BusinessSetting::where('key', self::WEBHOOK_SECRET_KEY)->value('value');
        return $secret ?: (env('RAZORPAY_WEBHOOK_SECRET') ?: null);
    }

    /** Total the mandate debits each month — the platform fee (+ account manager) with GST. */
    public static function monthlyTotal(bool $accountManager): float
    {
        return WhatsAppBilling::withTax(
            WhatsAppBilling::monthlyFee() + ($accountManager ? WhatsAppBilling::accountManagerFee() : 0)
        );
    }

    /**
     * Razorpay Plan id for a monthly amount, created on first use and remembered afterwards.
     * Keyed on paise so a price change in whatsapp_config transparently makes a new plan rather
     * than silently charging the old amount.
     */
    public static function planFor(Api $api, float $totalInr, string $label): ?string
    {
        $paise = (int) round($totalInr * 100);
        $map   = json_decode(BusinessSetting::where('key', self::PLAN_MAP_KEY)->value('value') ?: '{}', true) ?: [];

        if (!empty($map[$paise])) {
            return $map[$paise];
        }

        try {
            $plan = $api->plan->create([
                'period'   => 'monthly',
                'interval' => 1,
                'item'     => [
                    'name'        => $label,
                    'amount'      => $paise,
                    'currency'    => 'INR',
                    'description' => 'WhatsApp Business Platform — monthly, GST included',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay plan create failed: ' . $e->getMessage());
            return null;
        }

        $map[$paise] = $plan['id'];
        BusinessSetting::updateOrCreate(['key' => self::PLAN_MAP_KEY], ['value' => json_encode($map)]);

        return $plan['id'];
    }

    /**
     * Create the store's subscription and hand back the Razorpay-hosted mandate authorisation
     * URL. The subscription stays 'created' until the vendor authorises; subscription.activated
     * is what turns the platform on.
     */
    public static function start(int $storeId, bool $accountManager): array
    {
        $api = static::api();
        if (!$api) {
            return ['success' => false, 'message' => 'Razorpay is not configured. Ask the admin to set the payment gateway keys.'];
        }

        WhatsAppBilling::ensureTables();
        $existing = WhatsAppBilling::subscription($storeId);

        if ($existing && $existing->rzp_subscription_id && $existing->mandate_status === 'active') {
            return ['success' => false, 'message' => 'Monthly auto-debit is already authorised for this store.'];
        }

        $total = static::monthlyTotal($accountManager);
        $label = 'WhatsApp Business Platform' . ($accountManager ? ' + account manager' : '');

        $planId = static::planFor($api, $total, $label);
        if (!$planId) {
            return ['success' => false, 'message' => 'Could not set up the recurring plan with Razorpay. Please try again.'];
        }

        try {
            $subscription = $api->subscription->create([
                'plan_id'         => $planId,
                'total_count'     => self::TOTAL_CYCLES,
                'quantity'        => 1,
                'customer_notify' => 1,
                // The webhook resolves the store from here, so it still works if our row is lost.
                'notes'           => [
                    'store_id'        => (string) $storeId,
                    'purpose'         => 'whatsapp_monthly',
                    'account_manager' => $accountManager ? '1' : '0',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay subscription create failed', ['store' => $storeId, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not start the auto-debit with Razorpay. Please try again.'];
        }

        DB::table('wa_subscriptions')->updateOrInsert(
            ['store_id' => $storeId],
            [
                'rzp_subscription_id' => $subscription['id'],
                'rzp_plan_id'         => $planId,
                'mandate_status'      => 'pending',
                'account_manager'     => $accountManager ? 1 : 0,
                'monthly_fee'         => WhatsAppBilling::monthlyFee(),
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

    /** Stop the mandate at Razorpay. The paid-for period is honoured by isActive()'s grace. */
    public static function cancel(int $storeId): array
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
            // cancel_at_cycle_end keeps the month they already paid for.
            $api->subscription->fetch($sub->rzp_subscription_id)->cancel(['cancel_at_cycle_end' => 1]);
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
