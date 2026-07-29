<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppBilling;
use App\Services\WhatsAppRecurring;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Utility;

/**
 * Razorpay webhook for recurring WhatsApp subscriptions.
 *
 * Razorpay owns the schedule for mandate-billed stores: it debits the vendor and then tells us
 * here. Everything this endpoint does is idempotent, because Razorpay retries a delivery until
 * it gets a 2xx and will happily send the same event more than once.
 *
 * Configure in the Razorpay dashboard against POST /payment/razor-pay/webhook with the events
 * subscription.activated, subscription.charged, subscription.pending, subscription.halted and
 * subscription.cancelled, and put the signing secret in the wa_rzp_webhook_secret setting.
 */
class RazorPayWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $body      = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $secret    = WhatsAppRecurring::webhookSecret();

        if (!$secret) {
            Log::error('Razorpay webhook received but no signing secret is configured.');
            return response()->json(['status' => 'not configured'], 500);
        }

        if (!$signature) {
            return response()->json(['status' => 'missing signature'], 400);
        }

        try {
            Utility::verifyWebhookSignature($body, $signature, $secret);
        } catch (\Throwable $e) {
            Log::warning('Razorpay webhook signature rejected: ' . $e->getMessage());
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $payload = json_decode($body, true) ?: [];
        $event   = $payload['event'] ?? '';
        $entity  = data_get($payload, 'payload.subscription.entity', []);

        if (!str_starts_with($event, 'subscription.')) {
            return response()->json(['status' => 'ignored']);
        }

        // Only our WhatsApp subscriptions carry this note — anything else on the same webhook
        // (order, refund, another product's mandate) is not ours to act on.
        if (data_get($entity, 'notes.purpose') !== 'whatsapp_monthly') {
            return response()->json(['status' => 'ignored']);
        }

        $storeId = $this->resolveStoreId($entity);
        if (!$storeId) {
            Log::warning('Razorpay subscription webhook could not resolve a store', ['event' => $event]);
            return response()->json(['status' => 'unknown store']);
        }

        try {
            $this->apply($event, $storeId, $entity, $payload);
        } catch (\Throwable $e) {
            Log::error('Razorpay webhook handling failed', ['event' => $event, 'store' => $storeId, 'error' => $e->getMessage()]);
            // 500 makes Razorpay retry, which is what we want for a transient DB failure.
            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => 'ok']);
    }

    /** Prefer our own mapping; fall back to the note Razorpay carries for us. */
    private function resolveStoreId(array $entity): ?int
    {
        $subscriptionId = data_get($entity, 'id');
        if ($subscriptionId) {
            $storeId = DB::table('wa_subscriptions')->where('rzp_subscription_id', $subscriptionId)->value('store_id');
            if ($storeId) {
                return (int) $storeId;
            }
        }

        $noted = data_get($entity, 'notes.store_id');
        return $noted ? (int) $noted : null;
    }

    private function apply(string $event, int $storeId, array $entity, array $payload): void
    {
        switch ($event) {
            // The mandate is authorised. The platform is not on yet — the first charge does that.
            case 'subscription.activated':
                $this->setMandate($storeId, 'active');
                break;

            // Money actually taken. This is the only event that moves the billing period.
            case 'subscription.charged':
                $paymentId = data_get($payload, 'payload.payment.entity.id');
                $amount    = (float) data_get($payload, 'payload.payment.entity.amount', 0) / 100;
                if (!$paymentId || $amount <= 0) {
                    Log::warning('subscription.charged without a usable payment', ['store' => $storeId]);
                    break;
                }
                WhatsAppBilling::recordGatewayMonthly(
                    $storeId,
                    $paymentId,
                    $amount,
                    $this->tsToDate(data_get($entity, 'current_start')) ?? now()->toDateString(),
                    $this->tsToDate(data_get($entity, 'current_end')) ?? now()->addMonth()->toDateString()
                );
                break;

            // A debit failed and Razorpay is retrying. Service stays up on the existing grace
            // window — current_period_end is untouched, so isActive() decides as it always has.
            case 'subscription.pending':
                $this->setMandate($storeId, 'pending', 'Auto-debit failed — Razorpay is retrying.');
                break;

            // Razorpay gave up retrying. Still no forced cut-off: the grace window runs out on
            // its own and isActive() turns the store off when the paid period truly expires.
            case 'subscription.halted':
                $this->setMandate($storeId, 'halted', 'Auto-debit failed. Update your payment method to keep WhatsApp active.');
                break;

            case 'subscription.cancelled':
            case 'subscription.completed':
                DB::table('wa_subscriptions')->where('store_id', $storeId)->update([
                    'mandate_status' => 'cancelled',
                    'status'         => 'cancelled',
                    'cancelled_at'   => now(),
                    'updated_at'     => now(),
                ]);
                break;
        }
    }

    private function setMandate(int $storeId, string $status, ?string $error = null): void
    {
        DB::table('wa_subscriptions')->where('store_id', $storeId)->update([
            'mandate_status' => $status,
            'last_error'     => $error,
            'updated_at'     => now(),
        ]);
    }

    private function tsToDate($timestamp): ?string
    {
        return $timestamp ? Carbon::createFromTimestamp((int) $timestamp)->toDateString() : null;
    }
}
