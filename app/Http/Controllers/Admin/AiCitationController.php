<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiCitation;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
 
/**
 * AI Citation Monitoring — GEO KPI dashboard (Phase 3 §3.6 / SEO Monitoring Dashboard).
 * Tracks monthly AI-platform citations + GA4 referral sessions + branded-search proxy.
 * Data entry is manual (there is no standard AI-citation API — see the master doc risk register);
 * this gives leadership one place to record and trend the GEO KPI (target 50+/month).
 */ 
class AiCitationController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', now()->format('Y-m'));

        $rows = AiCitation::where('period', $period)->get()->keyBy('platform');
        $rollup = AiCitation::selectRaw('period,
                SUM(citations) as citations,
                SUM(referral_sessions) as referral_sessions,
                SUM(branded_search_volume) as branded_search_volume')
            ->groupBy('period')
            ->orderByDesc('period')
            ->limit(12)
            ->get();

        return view('admin-views.ai-citations.index', [
            'period'    => $period,
            'platforms' => AiCitation::PLATFORMS,
            'rows'      => $rows,
            'rollup'    => $rollup,
        ]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'period'                  => 'required|date_format:Y-m',
            'platform'                => 'required|in:' . implode(',', AiCitation::PLATFORMS),
            'citations'               => 'required|integer|min:0',
            'referral_sessions'       => 'nullable|integer|min:0',
            'branded_search_volume'   => 'nullable|integer|min:0',
            'notes'                   => 'nullable|string|max:500',
        ]);

        AiCitation::updateOrCreate(
            ['platform' => $request->platform, 'period' => $request->period],
            [
                'citations'             => $request->citations,
                'referral_sessions'     => $request->referral_sessions ?? 0,
                'branded_search_volume' => $request->branded_search_volume ?? 0,
                'notes'                 => $request->notes,
            ]
        );

        Toastr::success('Citation data saved.');
        return back();
    }

    public function destroy(int $id)
    {
        AiCitation::findOrFail($id)->delete();
        Toastr::success('Entry removed.');
        return back();
    }
}
