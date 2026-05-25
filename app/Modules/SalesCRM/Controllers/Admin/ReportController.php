<?php

namespace App\Modules\SalesCRM\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Modules\SalesCRM\Models\SalesFollowUp;
use App\Modules\SalesCRM\Models\SalesQuery;
use App\Modules\SalesCRM\Models\SupportTicket;

class ReportController extends Controller
{
    public function zone()
    {
        $admin   = auth('admin')->user();
        $zoneIds = $admin->crmZoneIds();
        $zones   = Zone::withoutGlobalScopes()
                       ->when(!empty($zoneIds), fn($q) => $q->whereIn('id', $zoneIds))
                       ->orderBy('name')
                       ->get();

        $report = $zones->map(function ($zone) {
            $qTotal     = SalesQuery::where('zone_id', $zone->id)->count();
            $qConverted = SalesQuery::where('zone_id', $zone->id)->where('status', 'converted')->count();
            $qLost      = SalesQuery::where('zone_id', $zone->id)->where('status', 'lost')->count();
            $qPipeline  = SalesQuery::where('zone_id', $zone->id)
                              ->whereIn('status', ['new', 'in_progress', 'proposal_sent'])->count();

            $fuDone    = SalesFollowUp::where('zone_id', $zone->id)->where('status', 'done')->count();
            $fuOverdue = SalesFollowUp::where('zone_id', $zone->id)->where('status', 'pending')
                             ->where('due_date', '<', today())->count();
            $fuPending = SalesFollowUp::where('zone_id', $zone->id)->where('status', 'pending')
                             ->where('due_date', '>=', today())->count();

            $tkTotal    = SupportTicket::where('zone_id', $zone->id)->count();
            $tkResolved = SupportTicket::where('zone_id', $zone->id)
                              ->whereIn('status', ['resolved', 'closed'])->count();

            return [
                'zone'           => $zone->name,
                'q_total'        => $qTotal,
                'q_converted'    => $qConverted,
                'q_lost'         => $qLost,
                'q_pipeline'     => $qPipeline,
                'conv_rate'      => $qTotal > 0 ? round($qConverted / $qTotal * 100) : 0,
                'fu_done'        => $fuDone,
                'fu_overdue'     => $fuOverdue,
                'fu_pending'     => $fuPending,
                'tk_total'       => $tkTotal,
                'tk_resolved'    => $tkResolved,
                'tk_resolve_rate'=> $tkTotal > 0 ? round($tkResolved / $tkTotal * 100) : 0,
            ];
        });

        // Lost reason breakdown (all zones or scoped)
        $lostReasons = SalesQuery::where('status', 'lost')
            ->when(!empty($zoneIds), fn($q) => $q->whereIn('zone_id', $zoneIds))
            ->whereNotNull('lost_reason')
            ->selectRaw('lost_reason, COUNT(*) as total')
            ->groupBy('lost_reason')
            ->orderByDesc('total')
            ->get();

        return view('sales_crm::admin-views.report.zone', compact('report', 'lostReasons'));
    }
}
