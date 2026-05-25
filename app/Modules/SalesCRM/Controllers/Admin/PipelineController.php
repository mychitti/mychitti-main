<?php

namespace App\Modules\SalesCRM\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Modules\SalesCRM\Models\SalesQuery;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    public function index(Request $request)
    {
        $admin   = auth('admin')->user();
        $zoneIds = $admin->crmZoneIds();

        $query = SalesQuery::with(['assignedAdmin', 'zone']);

        if (!empty($zoneIds)) {
            $query->whereIn('zone_id', $zoneIds);
        }
        if ($request->assigned_admin_id) {
            $query->where('assigned_admin_id', $request->assigned_admin_id);
        }
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        $grouped = $query->orderBy('created_at', 'desc')->get()->groupBy('status');

        $columns = [
            'new'           => ['label' => 'New',           'color' => '#17a2b8'],
            'in_progress'   => ['label' => 'In Progress',   'color' => '#fd7e14'],
            'proposal_sent' => ['label' => 'Proposal Sent', 'color' => '#6f42c1'],
            'converted'     => ['label' => 'Won',           'color' => '#28a745'],
            'lost'          => ['label' => 'Lost',          'color' => '#dc3545'],
        ];

        $admins = !empty($zoneIds)
            ? Admin::whereIn('zone_id', $zoneIds)->orderBy('f_name')->get()
            : Admin::orderBy('f_name')->get();
        $priorities = SalesQuery::PRIORITIES;

        return view('sales_crm::admin-views.pipeline.index', compact('grouped', 'columns', 'admins', 'priorities'));
    }
}
