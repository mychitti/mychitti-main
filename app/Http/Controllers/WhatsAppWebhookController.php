<?php

namespace App\Http\Controllers;

use App\CentralLogics\Helpers;
use App\Services\WhatsAppCampaign;
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

                    // 0) How many numbers Meta will let this business connect. It starts at 2 and
                    // becomes 20 once the business is verified or clears a 2,000 messaging limit;
                    // Meta announces the new ceiling here rather than answering a query for it, so
                    // this callback is the only way to know. The entry id is the WABA.
                    if (data_get($change, 'field') === 'business_capability_update') {
                        WhatsAppService::recordNumberCap(
                            data_get($entry, 'id'),
                            (int) data_get($value, 'max_phone_numbers_per_business', 0)
                        );
                        continue;
                    }

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

                        // Same callback drives the per-step delivered/read counters on a campaign.
                        WhatsAppCampaign::recordStatus($wamid, $status, $error);
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
                        $from = $msg['from'] ?? null;

                        // A tapped button is not a text message: a template's quick reply arrives
                        // as type 'button' carrying the label, and an interactive message's reply
                        // as type 'interactive'. Read the label out of whichever shape it came in
                        // — that label is the answer a campaign series branches on, and storing
                        // "[button]" instead would throw it away.
                        $buttonLabel = data_get($msg, 'button.text')
                            ?: data_get($msg, 'button.payload')
                            ?: data_get($msg, 'interactive.button_reply.title')
                            ?: data_get($msg, 'interactive.list_reply.title');

                        $body = data_get($msg, 'text.body') ?: ($buttonLabel ?: '[' . $type . ']');

                        // "STOP" and friends must actually stop the marketing — otherwise the
                        // recipient blocks the number instead, and that hits the sender's
                        // WhatsApp quality rating. A "Stop"/"Unsubscribe" quick reply counts the
                        // same as typing it.
                        $optOut = $from && WhatsAppService::isOptOutMessage(data_get($msg, 'text.body') ?: $buttonLabel);
                        if ($optOut) {
                            WhatsAppService::recordOptOut($storeId, $from, $buttonLabel ? 'button' : 'reply');
                        }

                        DB::table('whatsapp_messages')->insert([
                            'store_id'   => $storeId,
                            'wamid'      => $msg['id'] ?? null,
                            'direction'  => 'in',
                            'recipient'  => $from,
                            'type'       => $type,
                            'body'       => mb_substr((string) $body, 0, 1000),
                            'context'    => $optOut ? 'opt-out' : ($buttonLabel ? 'button-reply' : 'inbound'),
                            'status'     => 'received',
                            'sent_at'    => !empty($msg['timestamp']) ? date('Y-m-d H:i:s', (int) $msg['timestamp']) : now(),
                            'status_at'  => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Score the answer against any drip campaign this number is in.
                        // context.id is the message being replied to — WhatsApp sends it with
                        // every button tap, so the answer lands on the exact step that asked
                        // rather than on a guess. A typed reply has no context and falls back to
                        // the most recent send.
                        //
                        // A tap is recorded inline: the label is exact, so it is pure DB work and
                        // the series must not wait on a queue to branch. Typed text can involve an
                        // AI call, so it goes to a job AFTER Meta has its 200 — a webhook that
                        // waits on the AI service would get retried and eventually disabled.
                        // An answer to "How was your visit?" is handled before anything else and,
                        // when it is one, stops here: the AI auto-reply must never respond to
                        // "the doctor was late" with a knowledge-base article.
                        $feedbackHandled = \App\Services\FeedbackFlow::handleReply(
                            $storeId ?: null,
                            (string) $from,
                            $buttonLabel,
                            trim((string) data_get($msg, 'text.body')),
                            data_get($msg, 'context.id')
                        );

                        if ($from && !empty($buttonLabel)) {
                            WhatsAppCampaign::recordReply(
                                $storeId,
                                (string) $from,
                                $buttonLabel,
                                data_get($msg, 'context.id'),
                                true
                            );
                        } elseif ($from && !$optOut && !$feedbackHandled && trim((string) data_get($msg, 'text.body')) !== '') {
                            \App\Jobs\ClassifyCampaignReply::dispatch(
                                $storeId ?: null,
                                (string) $from,
                                trim((string) data_get($msg, 'text.body')),
                                data_get($msg, 'context.id')
                            )->afterResponse();
                        }

                        // AI auto-reply. afterResponse() runs it AFTER Meta gets its 200, so a
                        // slow reply can never make the webhook time out (which would make Meta
                        // retry and eventually disable the webhook). Works on the sync queue with
                        // no worker. Text only; opt-outs get silence, not a sales pitch. A message
                        // on a vendor's own number (storeId set) uses that store's knowledge; a
                        // message on the MyChitti platform number (storeId null) uses the platform
                        // knowledge.
                        $text = trim((string) data_get($msg, 'text.body'));
                        if ($from && !$optOut && !$feedbackHandled && $type === 'text' && $text !== '') {
                            \App\Jobs\SendAutoReply::dispatch(
                                $storeId ?: null,
                                (string) $from,
                                $text,
                                WhatsAppService::numberIdByPhoneNumberId($phoneNumberId)
                            )->afterResponse();
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
