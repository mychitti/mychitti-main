<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * MyChitti platform WhatsApp inbox — two-way chat on the platform WABA with vendors and
 * customers. Platform messages carry store_id NULL in whatsapp_messages (they are not
 * attributed to any vendor store), which is the scope for every query here.
 */
class WhatsAppInboxController extends Controller
{
    public function inbox(Request $request)
    {
        WhatsAppService::ensureMessagesTable();
        $wa = WhatsAppService::make();
        $connected = $wa->isConfigured();

        // Self-heal: subscribe the MyChitti platform WABA to our app so Meta forwards its
        // INBOUND messages to the webhook. Without this the platform number can send but
        // never receive. Idempotent — Meta returns success when already subscribed.
        $subscribeError = null;
        if ($connected && $wa->hasWaba()) {
            $sub = $wa->ensureWebhookSubscription();
            if (!$sub['success']) {
                $subscribeError = $sub['error'];
            }
        }

        return view('admin-views.whatsapp.inbox', compact('connected', 'subscribeError'));
    }

    /** Conversation list: one row per contact, newest activity first. */
    public function threads(Request $request)
    {
        $rows = DB::table('whatsapp_messages')
            ->whereNull('store_id')
            ->whereNotNull('recipient')
            ->where('recipient', '!=', '')
            // A bulk blast is not a conversation. Left in, one 10,000-number send fills the window
            // below and pushes every real chat out of the list. Anyone who replies to a blast comes
            // back on their inbound row, and the thread itself still shows the message they got.
            ->where(function ($q) {
                $q->where('direction', 'in')
                    ->orWhereNull('context')
                    ->orWhere('context', '!=', WhatsAppBulkController::CONTEXT);
            })
            ->orderByDesc('sent_at')
            ->limit(2000)
            ->get(['recipient', 'direction', 'body', 'type', 'sent_at']);

        $threads = [];
        foreach ($rows as $m) {
            $key = substr(preg_replace('/[^0-9]/', '', (string) $m->recipient) ?? '', -10);
            if (strlen($key) < 10) {
                continue;
            }
            if (!isset($threads[$key])) {
                $threads[$key] = [
                    'key'       => $key,
                    'phone'     => $m->recipient,
                    'name'      => null,
                    'kind'      => null,
                    'last_body' => mb_substr((string) $m->body, 0, 80),
                    'last_dir'  => $m->direction,
                    'last_at'   => $m->sent_at,
                ];
            }
        }

        if ($threads) {
            // Vendors first (store name), then customers (user name) — vendor identity wins.
            foreach (DB::table('stores')->whereNotNull('phone')->where('phone', '!=', '')
                ->get(['name', 'phone']) as $s) {
                $k = substr(preg_replace('/[^0-9]/', '', (string) $s->phone) ?? '', -10);
                if (isset($threads[$k]) && $threads[$k]['name'] === null) {
                    $threads[$k]['name'] = $s->name;
                    $threads[$k]['kind'] = 'Vendor';
                }
            }
            foreach (DB::table('users')->whereNotNull('phone')->where('phone', '!=', '')
                ->get(['f_name', 'l_name', 'phone']) as $u) {
                $k = substr(preg_replace('/[^0-9]/', '', (string) $u->phone) ?? '', -10);
                if (isset($threads[$k]) && $threads[$k]['name'] === null) {
                    $threads[$k]['name'] = trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? ''));
                    $threads[$k]['kind'] = 'Customer';
                }
            }
        }

        return response()->json(['success' => true, 'threads' => array_values($threads)]);
    }

    /** Full message history with one contact + whether the 24h free-text window is open. */
    public function thread(Request $request)
    {
        $request->validate(['phone' => 'required|digits:10']);
        $key = $request->phone;

        $messages = DB::table('whatsapp_messages')
            ->whereNull('store_id')
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(recipient, ' ', ''), '-', ''), '+', ''), 10) = ?", [$key])
            ->orderByDesc('sent_at')
            ->limit(300)
            ->get(['id', 'direction', 'type', 'body', 'context', 'status', 'error', 'sent_at'])
            ->reverse()
            ->values();

        $lastInbound = $messages->where('direction', 'in')->last();
        $windowOpen = $lastInbound && Carbon::parse($lastInbound->sent_at)->gt(now()->subHours(24));

        return response()->json([
            'success'     => true,
            'messages'    => $messages,
            'window_open' => (bool) $windowOpen,
        ]);
    }

    /** Send a manual reply (free text) from the MyChitti platform number. */
    public function send(Request $request)
    {
        $request->validate([
            'phone'   => 'required|digits:10',
            'message' => 'required|string|max:4000',
        ]);

        $wa = WhatsAppService::make();
        if (!$wa->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'MyChitti WhatsApp is not configured.'], 422);
        }

        $res = $wa->sendText($request->phone, trim((string) $request->message), false, 'chat reply');

        return response()->json([
            'success' => (bool) $res['success'],
            'error'   => $res['error'] ?? null,
        ]);
    }
}
