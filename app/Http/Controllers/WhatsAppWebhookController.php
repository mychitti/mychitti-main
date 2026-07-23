<?php

namespace App\Http\Controllers;

use App\CentralLogics\Helpers;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    // Meta calls this once to verify the endpoint (GET, echoes hub.challenge).
    public function verify(Request $request)
    {
        $mode      = $request->get('hub_mode');
        $token     = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        $config = Helpers::get_business_settings('whatsapp_config');
        $expected = $config['verify_token'] ?? null;

        if ($mode === 'subscribe' && $expected && hash_equals((string) $expected, (string) $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }
        return response('Forbidden', 403);
    }

    // Meta posts status updates (sent/delivered/read/failed) and inbound messages here.
    public function receive(Request $request)
    {
        try {
            WhatsAppService::ensureMessagesTable();
            $payload = $request->all();

            foreach (data_get($payload, 'entry', []) as $entry) {
                foreach (data_get($entry, 'changes', []) as $change) {
                    $value = data_get($change, 'value', []);

                    // 1) Delivery/read status callbacks — update the matching outbound row.
                    foreach (data_get($value, 'statuses', []) as $st) {
                        $wamid  = $st['id'] ?? null;
                        $status = $st['status'] ?? null;
                        if (!$wamid || !$status) {
                            continue;
                        }
                        $error = null;
                        if (!empty($st['errors'])) {
                            $error = data_get($st, 'errors.0.title') ?: data_get($st, 'errors.0.message');
                            $detail = data_get($st, 'errors.0.error_data.details');
                            if ($detail) {
                                $error = trim(($error ? $error . ' — ' : '') . $detail);
                            }
                        }
                        $ts = !empty($st['timestamp']) ? date('Y-m-d H:i:s', (int) $st['timestamp']) : now();

                        DB::table('whatsapp_messages')->where('wamid', $wamid)->update(array_filter([
                            'status'     => $status,
                            'error'      => $error,
                            'status_at'  => $ts,
                            'updated_at' => now(),
                        ], fn($v) => !is_null($v)));
                    }

                    // 2) Inbound messages — log so the 24h window / two-way chat is visible.
                    $phoneNumberId = data_get($value, 'metadata.phone_number_id');
                    $storeId = WhatsAppService::storeByPhoneNumberId($phoneNumberId);

                    // A message we can't attribute is stored with store_id NULL and shows in no
                    // vendor's inbox — make that loudly visible instead of silently losing it.
                    if (!$storeId && !empty(data_get($value, 'messages'))) {
                        Log::warning('WA inbound: no store matches phone_number_id — message will not appear in any vendor inbox', [
                            'phone_number_id' => $phoneNumberId,
                            'from'            => data_get($value, 'messages.0.from'),
                        ]);
                    }

                    foreach (data_get($value, 'messages', []) as $msg) {
                        $type = $msg['type'] ?? 'text';
                        $body = data_get($msg, 'text.body') ?: ('[' . $type . ']');
                        $from = $msg['from'] ?? null;

                        // "STOP" and friends must actually stop the marketing — otherwise the
                        // recipient blocks the number instead, and that hits the sender's
                        // WhatsApp quality rating.
                        $optOut = $from && WhatsAppService::isOptOutMessage(data_get($msg, 'text.body'));
                        if ($optOut) {
                            WhatsAppService::recordOptOut($storeId, $from, 'reply');
                        }

                        DB::table('whatsapp_messages')->insert([
                            'store_id'   => $storeId,
                            'wamid'      => $msg['id'] ?? null,
                            'direction'  => 'in',
                            'recipient'  => $from,
                            'type'       => $type,
                            'body'       => mb_substr((string) $body, 0, 1000),
                            'context'    => $optOut ? 'opt-out' : 'inbound',
                            'status'     => 'received',
                            'sent_at'    => !empty($msg['timestamp']) ? date('Y-m-d H:i:s', (int) $msg['timestamp']) : now(),
                            'status_at'  => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // AI auto-reply from the store's knowledge — queued so Meta gets its
                        // 200 immediately. Text only; opt-outs get silence, not a sales pitch.
                        $text = trim((string) data_get($msg, 'text.body'));
                        if ($storeId && $from && !$optOut && $type === 'text' && $text !== '') {
                            \App\Jobs\SendAutoReply::dispatch($storeId, (string) $from, $text);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook error: ' . $e->getMessage());
        }

        // Always 200 so Meta doesn't retry/disable the webhook.
        return response()->json(['received' => true]);
    }
}
