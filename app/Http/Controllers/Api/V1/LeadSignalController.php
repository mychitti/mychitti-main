<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeadSignal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 
/**
 * Lead Inbox signal capture (Phase 3 §3.3). The app/web fires this when a user takes an inbound
 * action on a store (click-to-call, WhatsApp, booking, quote, directions). The MC Vendor Hub Lead
 * Inbox reads the aggregate; over time these also feed the popularity signal in hybrid search.
 */
class LeadSignalController extends Controller
{
    public function store(Request $request)
    { 
        $validated = $request->validate([
            'store_id'     => 'required|integer|exists:stores,id',
            'type'         => 'required|in:' . implode(',', LeadSignal::TYPES),
            'source'       => 'nullable|string|max:60',
            'utm_source'   => 'nullable|string|max:80',
            'utm_medium'   => 'nullable|string|max:80',
            'utm_campaign' => 'nullable|string|max:120',
        ]);

        $signal = LeadSignal::create([
            'store_id'     => $validated['store_id'],
            'user_id'      => auth('api')->id() ?? $request->user()?->id,
            'type'         => $validated['type'],
            'source'       => $validated['source'] ?? null,
            'utm_source'   => $validated['utm_source'] ?? null,
            'utm_medium'   => $validated['utm_medium'] ?? null,
            'utm_campaign' => $validated['utm_campaign'] ?? null,
            'meta'         => ['ip' => $request->ip(), 'ua' => substr((string) $request->userAgent(), 0, 255)],
        ]);

        return response()->json(['success' => true, 'id' => $signal->id]);
    }

    /**
     * Aggregate counts for a store over the last N days — consumed by the MC Vendor Hub Lead Inbox.
     */
    public function summary(Request $request, int $store_id)
    {
        $days = min((int) $request->input('days', 30), 365);
        $since = now()->subDays($days);

        $counts = DB::table('lead_signals')
            ->where('store_id', $store_id)
            ->where('created_at', '>=', $since)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $out = [];
        foreach (LeadSignal::TYPES as $t) {
            $out[$t] = (int) ($counts[$t] ?? 0);
        }

        return response()->json([
            'success'  => true,
            'store_id' => $store_id,
            'days'     => $days,
            'signals'  => $out,
            'total'    => array_sum($out),
        ]);
    }
}
