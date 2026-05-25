<?php

namespace App\Modules\SalesCRM\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Modules\SalesCRM\Models\FollowUpAssignment;
use App\Modules\SalesCRM\Models\QueryActivity;
use App\Modules\SalesCRM\Models\SalesFollowUp;
use App\Modules\SalesCRM\Models\SalesQuery;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    private function scopeFollowUp()
    {
        $admin   = auth('admin')->user();
        $zoneIds = $admin->crmZoneIds();
        $q = SalesFollowUp::with(['salesQuery', 'admin', 'zone']);
        if (!empty($zoneIds)) {
            $q->whereIn('zone_id', $zoneIds);
        }
        return $q;
    }

    public function index(Request $request)
    {
        $query = $this->scopeFollowUp();

        $filter = $request->get('filter', 'upcoming');
        if ($filter === 'today') {
            $query->whereDate('due_date', today());
        } elseif ($filter === 'overdue') {
            $query->where('due_date', '<', today())->where('status', 'pending');
        } elseif ($filter === 'upcoming') {
            $query->where('due_date', '>=', today())->where('status', 'pending');
        }

        if ($request->status) $query->where('status', $request->status);

        $followups = $query->orderBy('due_date')->orderBy('due_time')->paginate(25)->withQueryString();
        $statuses  = SalesFollowUp::STATUSES;

        $counts = [
            'today'    => $this->scopeFollowUp()->whereDate('due_date', today())->count(),
            'overdue'  => $this->scopeFollowUp()->where('due_date', '<', today())->where('status', 'pending')->count(),
            'upcoming' => $this->scopeFollowUp()->where('due_date', '>=', today())->where('status', 'pending')->count(),
        ];

        return view('sales_crm::admin-views.followup.index', compact('followups', 'statuses', 'filter', 'counts'));
    }

    public function create(Request $request)
    {
        $admin   = auth('admin')->user();
        $zoneIds = $admin->crmZoneIds();
        $queryId = $request->get('query_id');

        $queries = SalesQuery::when(!empty($zoneIds), fn($q) => $q->whereIn('zone_id', $zoneIds))
                             ->whereIn('status', ['new', 'in_progress', 'proposal_sent'])
                             ->orderBy('contact_name')
                             ->get(['id', 'ref_no', 'contact_name']);

        $selectedQuery = $queryId ? SalesQuery::find($queryId) : null;

        return view('sales_crm::admin-views.followup.create', compact('queries', 'selectedQuery'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'query_id' => 'nullable|integer|exists:sales_queries,id',
            'title'    => 'required|string|max:200',
            'notes'    => 'nullable|string|max:2000',
            'due_date' => 'required|date',
            'due_time' => 'nullable|date_format:H:i',
        ]);

        $admin = auth('admin')->user();

        $zoneId = $request->query_id ? SalesQuery::find($request->query_id)?->zone_id : null;

        SalesFollowUp::create([
            'query_id' => $request->query_id,
            'title'    => $request->title,
            'notes'    => $request->notes,
            'admin_id' => $admin->id,
            'zone_id'  => $zoneId,
            'due_date' => $request->due_date,
            'due_time' => $request->due_time,
            'status'   => 'pending',
        ]);

        if ($request->query_id) {
            QueryActivity::log(
                $request->query_id,
                'follow_up',
                'Follow-up scheduled: "' . $request->title . '" due ' . $request->due_date
            );
        }

        Toastr::success('Follow-up scheduled.');

        if ($request->query_id) {
            return redirect()->route('admin.sales-crm.query.show', $request->query_id);
        }
        return redirect()->route('admin.sales-crm.followup.index');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:' . implode(',', SalesFollowUp::STATUSES)]);
        $followUp = $this->scopeFollowUp()->findOrFail($id);
        $followUp->update(['status' => $request->status]);

        if ($followUp->query_id) {
            QueryActivity::log(
                $followUp->query_id,
                'follow_up',
                'Follow-up "' . $followUp->title . '" marked as ' . $request->status
            );
        }

        Toastr::success('Follow-up marked as ' . $request->status . '.');
        return back();
    }

    public function assign(Request $request, $id)
    {
        $request->validate([
            'assigned_to' => 'required|integer|exists:admins,id',
            'note'        => 'nullable|string|max:500',
        ]);

        $followUp  = $this->scopeFollowUp()->findOrFail($id);
        $actor     = auth('admin')->user();
        $assignee  = Admin::findOrFail($request->assigned_to);

        $followUp->update(['assigned_to' => $assignee->id]);

        FollowUpAssignment::create([
            'followup_id' => $followUp->id,
            'assigned_to' => $assignee->id,
            'assigned_by' => $actor->id,
            'note'        => $request->note,
        ]);

        if ($followUp->query_id) {
            QueryActivity::log(
                $followUp->query_id,
                'note',
                'Follow-up "' . $followUp->title . '" assigned to ' . $assignee->f_name . ' ' . $assignee->l_name
                . ($request->note ? ' — ' . $request->note : '')
            );
        }

        _inAppNotification(
            'Follow-up Assigned to You',
            '"' . $followUp->title . '" has been assigned to you by ' . $actor->f_name . '.'
            . ($request->note ? ' Note: ' . $request->note : ''),
            '',
            $assignee->id,
            route('admin.sales-crm.followup.index'),
            'admin_employee'
        );

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'assignee' => $assignee->f_name . ' ' . $assignee->l_name]);
        }

        Toastr::success('Follow-up assigned to ' . $assignee->f_name . '.');
        return back();
    }

    public function assignmentHistory($id)
    {
        $followUp = $this->scopeFollowUp()
            ->with(['assignments.assignedTo', 'assignments.assignedBy'])
            ->findOrFail($id);

        $history = $followUp->assignments->map(fn($a) => [
            'assigned_to' => $a->assignedTo?->f_name . ' ' . $a->assignedTo?->l_name,
            'assigned_by' => $a->assignedBy?->f_name . ' ' . $a->assignedBy?->l_name,
            'note'        => $a->note,
            'at'          => $a->created_at->format('d M Y, h:i A'),
        ]);

        return response()->json($history);
    }

    public function myHistory(Request $request)
    {
        $admin = auth('admin')->user();

        $query = SalesFollowUp::with(['salesQuery', 'zone'])
            ->where(function ($q) use ($admin) {
                $q->where('admin_id', $admin->id)
                  ->orWhere('assigned_to', $admin->id);
            });

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $followups = $query->orderByDesc('due_date')->orderByDesc('due_time')->paginate(25)->withQueryString();
        $statuses  = SalesFollowUp::STATUSES;

        return view('sales_crm::admin-views.followup.my', compact('followups', 'statuses'));
    }

    public function destroy($id)
    {
        $this->scopeFollowUp()->findOrFail($id)->delete();
        Toastr::success('Follow-up deleted.');
        return back();
    }
}
