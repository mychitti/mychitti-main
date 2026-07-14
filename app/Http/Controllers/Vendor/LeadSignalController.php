<?php

namespace App\Http\Controllers\Vendor;
 
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}
