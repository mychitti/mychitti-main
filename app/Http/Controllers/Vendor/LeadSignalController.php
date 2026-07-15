<?php

namespace App\Http\Controllers\Vendor;
 
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** 
 * Lead Inbox — SOFT, pre-conversion contact signals per store (Phase 3 §3.3): call / WhatsApp /
 * directions / website. These are interest signals that don't create a formal record anywhere else.
 * Completed conversions (appointments, enquiries, quotations) are intentionally EXCLUDED — they
 * already have their own dedicated management screens. Purpose: "who was interested but hasn't
 * converted yet — follow up."
 */ 
class LeadSignalController extends Controller
{
    // Only the soft signals belong here; booking/quote live in their own systems.
    private const SOFT_TYPES = ['call', 'whatsapp', 'direction', 'website'];

    public function index(Request $request)
    {
        $storeId = Helpers::get_store_id();

        $days = (int) $request->input('days', 30);
        if (!in_array($days, [7, 30, 90, 365], true)) {
            $days = 30;
        }
        $since = now()->subDays($days);

        $counts = DB::table('lead_signals')
            ->where('store_id', $storeId)
            ->where('created_at', '>=', $since)
            ->whereIn('type', self::SOFT_TYPES)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $byType = [];
        foreach (self::SOFT_TYPES as $t) {
            $byType[$t] = (int) ($counts[$t] ?? 0);
        }
        $total = array_sum($byType);

        $recent = DB::table('lead_signals')
            ->leftJoin('users', 'users.id', '=', 'lead_signals.user_id')
            ->where('lead_signals.store_id', $storeId)
            ->where('lead_signals.created_at', '>=', $since)
            ->whereIn('lead_signals.type', self::SOFT_TYPES)
            ->orderByDesc('lead_signals.created_at')
            ->select(
                'lead_signals.type', 'lead_signals.source', 'lead_signals.utm_source',
                'lead_signals.created_at', 'users.f_name', 'users.l_name', 'users.phone'
            )
            ->paginate(20);

        return view('vendor-views.lead-signals.index', compact('byType', 'total', 'recent', 'days'));
    }

    /**
     * Draft a warm WhatsApp follow-up for a soft lead (Phase 4 §4.1 "AI Reply Drafts", made
     * contextual to the Lead Inbox). Uses the signal type (what the customer did) + first name +
     * the vendor's own store name; returns one ready-to-send message the vendor edits before
     * sending via the WhatsApp deep link. OpenAI gpt-4o-mini.
     */
    public function ai_follow_up(Request $request)
    {
        $request->validate([
            'type' => 'required|in:' . implode(',', self::SOFT_TYPES),
            'name' => 'nullable|string|max:60',
        ]);

        $store     = DB::table('stores')->where('id', Helpers::get_store_id())->first(['name']);
        $storeName = $store->name ?? 'our business';
        $firstName = trim((string) $request->input('name'));

        $actions = [
            'call'      => 'called your business recently but may not have connected',
            'whatsapp'  => 'messaged your business on WhatsApp',
            'direction' => 'looked up directions to your business',
            'website'   => 'visited your business profile',
        ];
        $action = $actions[$request->type] ?? 'showed interest in your business';

        $key = config('services.openai.key');
        if (!$key) {
            return response()->json(['success' => false, 'message' => 'AI is not configured. Please contact support.'], 500);
        }

        $system = "You help \"{$storeName}\", a local service business in India, write ONE short, warm, friendly "
            . "WhatsApp follow-up to a potential customer who showed interest but hasn't booked yet. 2–3 sentences. "
            . "Greet by first name if given. Be polite and helpful, never pushy or salesy. Invite them to reply or ask "
            . "questions. Do NOT invent prices, offers, discounts or any detail the business didn't state, and do not use "
            . "placeholders. Sound like a real person from the business. Plain text, ready to send as-is.";

        $user = "Customer first name: " . ($firstName !== '' ? $firstName : '(not given)') . "\n"
            . "What they did: they {$action}.\n"
            . "Write the follow-up message.";

        try {
            $response = Http::timeout(45)->withToken($key)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => 'gpt-4o-mini',
                'max_tokens'  => 220,
                'temperature' => 0.7,
                'messages'    => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'AI service is temporarily unavailable. Please try again.'], 503);
        }

        if (!$response->ok()) {
            return response()->json(['success' => false, 'message' => 'Could not generate right now. Please try again.'], 502);
        }

        $text = trim((string) $response->json('choices.0.message.content'));
        if ($text === '') {
            return response()->json(['success' => false, 'message' => 'Empty response — please try again.'], 502);
        }

        return response()->json(['success' => true, 'text' => $text]);
    }
}
