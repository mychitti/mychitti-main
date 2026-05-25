<?php

namespace App\Modules\SalesCRM\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Zone;
use App\Modules\SalesCRM\Models\QueryActivity;
use App\Modules\SalesCRM\Models\SalesQuery;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    private function scopeQuery()
    {
        $admin   = auth('admin')->user();
        $zoneIds = $admin->crmZoneIds();
        $q = SalesQuery::with(['zone', 'assignedAdmin']);
        if (!empty($zoneIds)) {
            $q->whereIn('zone_id', $zoneIds);
        }
        return $q;
    }

    private function crmZones()
    {
        $admin   = auth('admin')->user();
        $zoneIds = $admin->crmZoneIds();
        $q       = Zone::withoutGlobalScopes()->orderBy('name');
        if (!empty($zoneIds)) {
            $q->whereIn('id', $zoneIds);
        }
        return $q->get();
    }

    public function index(Request $request)
    {
        $admin  = auth('admin')->user();
        $query  = $this->scopeQuery();

        if ($request->status)  $query->where('status', $request->status);
        if ($request->priority) $query->where('priority', $request->priority);
        if ($request->zone_id && !$admin->zone_id) $query->where('zone_id', $request->zone_id);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('contact_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('company', 'like', "%{$s}%")
                  ->orWhere('ref_no', 'like', "%{$s}%");
            });
        }

        $queries  = $query->latest()->paginate(20)->withQueryString();
        $zones    = $this->crmZones();
        $statuses = SalesQuery::STATUSES;

        return view('sales_crm::admin-views.query.index', compact('queries', 'zones', 'statuses'));
    }

    public function create()
    {
        $zones      = $this->crmZones();
        $admins     = Admin::orderBy('f_name')->get();
        $subModules = \DB::table('sub_modules')->orderBy('name')->get();

        return view('sales_crm::admin-views.query.create', compact('zones', 'admins', 'subModules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'contact_name' => 'required|string|max:100',
            'phone'        => 'required|string|max:20',
            'email'        => 'nullable|email|max:100',
            'company'      => 'nullable|string|max:150',
            'zone_id'      => 'nullable|integer|exists:zones,id',
            'source'       => 'required|in:' . implode(',', SalesQuery::SOURCES),
            'priority'     => 'required|in:' . implode(',', SalesQuery::PRIORITIES),
            'sub_module'   => 'nullable|string|max:255',
            'description'  => 'nullable|string|max:2000',
        ]);

        $admin = auth('admin')->user();

        $salesQuery = SalesQuery::create([
            'ref_no'            => SalesQuery::generateRef(),
            'contact_name'      => $request->contact_name,
            'phone'             => $request->phone,
            'email'             => $request->email,
            'company'           => $request->company,
            'zone_id'           => $request->zone_id,
            'assigned_admin_id' => $request->assigned_admin_id,
            'source'            => $request->source,
            'priority'          => $request->priority,
            'status'            => 'new',
            'sub_module'        => $request->sub_module ?: null,
            'description'       => $request->description,
        ]);

        QueryActivity::log($salesQuery->id, 'note', 'Query created.');

        Toastr::success('Query added successfully.');
        return redirect()->route('admin.sales-crm.query.index');
    }

    public function show($id)
    {
        $salesQuery = $this->scopeQuery()
            ->with(['followUps.admin', 'activities.admin', 'quotations.quote_detail', 'tickets.assignedAdmin'])
            ->findOrFail($id);

        $activityTypes = array_diff(QueryActivity::TYPES, ['status_change', 'follow_up', 'ticket']);

        return view('sales_crm::admin-views.query.show', compact('salesQuery', 'activityTypes'));
    }

    public function edit($id)
    {
        $salesQuery = $this->scopeQuery()->findOrFail($id);
        $zones      = $this->crmZones();
        $admins     = Admin::orderBy('f_name')->get();
        $subModules = \DB::table('sub_modules')->orderBy('name')->get();

        return view('sales_crm::admin-views.query.edit', compact('salesQuery', 'zones', 'admins', 'subModules'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'contact_name'      => 'required|string|max:100',
            'phone'             => 'required|string|max:20',
            'email'             => 'nullable|email|max:100',
            'company'           => 'nullable|string|max:150',
            'zone_id'           => 'nullable|integer|exists:zones,id',
            'assigned_admin_id' => 'nullable|integer|exists:admins,id',
            'source'            => 'required|in:' . implode(',', SalesQuery::SOURCES),
            'priority'          => 'required|in:' . implode(',', SalesQuery::PRIORITIES),
            'status'            => 'required|in:' . implode(',', SalesQuery::STATUSES),
            'sub_module'        => 'nullable|string|max:255',
            'description'       => 'nullable|string|max:2000',
            'notes'             => 'nullable|string|max:2000',
        ]);

        $salesQuery = $this->scopeQuery()->findOrFail($id);
        $oldStatus  = $salesQuery->status;

        $salesQuery->update([
            'contact_name'      => $request->contact_name,
            'phone'             => $request->phone,
            'email'             => $request->email,
            'company'           => $request->company,
            'zone_id'           => $request->zone_id,
            'assigned_admin_id' => $request->assigned_admin_id,
            'source'            => $request->source,
            'priority'          => $request->priority,
            'status'            => $request->status,
            'sub_module'        => $request->sub_module ?: null,
            'description'       => $request->description,
            'notes'             => $request->notes,
        ]);

        if ($oldStatus !== $request->status) {
            QueryActivity::log($salesQuery->id, 'status_change',
                ucfirst(str_replace('_', ' ', $oldStatus)) . ' → ' . ucfirst(str_replace('_', ' ', $request->status))
            );
        }

        Toastr::success('Query updated.');
        return redirect()->route('admin.sales-crm.query.show', $id);
    }

    public function updateStatus(Request $request, $id)
    {
        $rules = ['status' => 'required|in:' . implode(',', SalesQuery::STATUSES)];

        if ($request->status === 'lost') {
            $rules['lost_reason']       = 'required|in:' . implode(',', SalesQuery::LOST_REASONS);
            $rules['lost_reason_other'] = 'nullable|string|max:200';
        }

        $request->validate($rules);

        $salesQuery = $this->scopeQuery()->findOrFail($id);
        $oldStatus  = $salesQuery->status;

        $salesQuery->update([
            'status'            => $request->status,
            'lost_reason'       => $request->status === 'lost' ? $request->lost_reason : null,
            'lost_reason_other' => ($request->status === 'lost' && $request->lost_reason === 'other')
                                    ? $request->lost_reason_other : null,
        ]);

        if ($oldStatus !== $request->status) {
            $desc = ucfirst(str_replace('_', ' ', $oldStatus)) . ' → ' . ucfirst(str_replace('_', ' ', $request->status));
            if ($request->status === 'lost' && $request->lost_reason) {
                $reason = $request->lost_reason === 'other'
                    ? ($request->lost_reason_other ?? 'other')
                    : ucfirst(str_replace('_', ' ', $request->lost_reason));
                $desc .= ' (Reason: ' . $reason . ')';
            }
            QueryActivity::log($salesQuery->id, 'status_change', $desc);
        }

        if ($request->ajax()) {
            return response()->json(['ok' => true]);
        }

        Toastr::success('Status updated.');
        return back();
    }

    public function logActivity(Request $request, $id)
    {
        $request->validate([
            'type'        => 'required|in:call,email,meeting,note',
            'description' => 'required|string|max:1000',
        ]);

        $salesQuery = $this->scopeQuery()->findOrFail($id);
        QueryActivity::log($salesQuery->id, $request->type, $request->description);

        Toastr::success('Activity logged.');
        return back();
    }

    public function destroy($id)
    {
        $this->scopeQuery()->findOrFail($id)->delete();
        Toastr::success('Query deleted.');
        return redirect()->route('admin.sales-crm.query.index');
    }
}
