<?php

namespace App\Http\Controllers\Vendor;
 
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\LeadSignal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lead Inbox — contact-signal counts per store (Phase 3 §3.3). Shows call / WhatsApp / booking /
 * quote / website / directions actions customers took, over a selectable window. Complements the
 * existing enquiry (service_requests) leads; here we track the lighter contact signals.
 */
class LeadSignalController extends Controller
{
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
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $byType = [];
        foreach (LeadSignal::TYPES as $t) {
            $byType[$t] = (int) ($counts[$t] ?? 0);
        }
        $total = array_sum($byType);

        $recent = DB::table('lead_signals')
            ->leftJoin('users', 'users.id', '=', 'lead_signals.user_id')
            ->where('lead_signals.store_id', $storeId)
            ->where('lead_signals.created_at', '>=', $since)
            ->orderByDesc('lead_signals.created_at')
            ->select(
                'lead_signals.type', 'lead_signals.source', 'lead_signals.utm_source',
                'lead_signals.created_at', 'users.f_name', 'users.l_name', 'users.phone'
            )
            ->paginate(20);

        return view('vendor-views.lead-signals.index', compact('byType', 'total', 'recent', 'days'));
    }
}
