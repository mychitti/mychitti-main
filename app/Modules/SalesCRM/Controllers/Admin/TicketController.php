<?php

namespace App\Modules\SalesCRM\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Zone;
use App\Modules\SalesCRM\Models\QueryActivity;
use App\Modules\SalesCRM\Models\SalesQuery;
use App\Modules\SalesCRM\Models\SupportTicket;
use App\Modules\SalesCRM\Models\TicketReply;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    private function scopeTicket()
    {
        $admin   = auth('admin')->user();
        $zoneIds = $admin->crmZoneIds();
        $q = SupportTicket::with(['zone', 'assignedAdmin']);
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
        $query  = $this->scopeTicket();

        if ($request->status)   $query->where('status', $request->status);
        if ($request->priority) $query->where('priority', $request->priority);
        if ($request->zone_id && !$admin->zone_id) $query->where('zone_id', $request->zone_id);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ticket_no', 'like', "%{$s}%")
                  ->orWhere('subject', 'like', "%{$s}%")
                  ->orWhere('contact_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $tickets    = $query->latest()->paginate(20)->withQueryString();
        $zones      = $this->crmZones();
        $statuses   = SupportTicket::STATUSES;
        $priorities = SupportTicket::PRIORITIES;

        return view('sales_crm::admin-views.ticket.index', compact('tickets', 'zones', 'statuses', 'priorities'));
    }

    public function create(Request $request)
    {
        $admin   = auth('admin')->user();
        $zoneIds = $admin->crmZoneIds();
        $zones   = $this->crmZones();
        $admins  = Admin::orderBy('f_name')->get();

        $fromQuery = $request->from_query ? SalesQuery::find($request->from_query) : null;

        $queryBuilder = SalesQuery::when(!empty($zoneIds), fn($q) => $q->whereIn('zone_id', $zoneIds))
                                  ->orderBy('contact_name');

        if (!$fromQuery) {
            $queryBuilder->whereIn('status', ['new', 'in_progress']);
        }

        $queries = $queryBuilder->get(['id', 'ref_no', 'contact_name']);

        // Ensure the linked query appears in the list even if not in the default filter
        if ($fromQuery && !$queries->contains('id', $fromQuery->id)) {
            $queries->prepend($fromQuery->only(['id', 'ref_no', 'contact_name']));
        }

        return view('sales_crm::admin-views.ticket.create', compact('zones', 'admins', 'queries', 'fromQuery'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'      => 'required|string|max:200',
            'contact_name' => 'required|string|max:100',
            'phone'        => 'required|string|max:20',
            'email'        => 'nullable|email|max:100',
            'zone_id'      => 'nullable|integer|exists:zones,id',
            'channel'      => 'required|in:' . implode(',', SupportTicket::CHANNELS),
            'priority'     => 'required|in:' . implode(',', SupportTicket::PRIORITIES),
            'description'  => 'nullable|string|max:3000',
        ]);

        $admin = auth('admin')->user();

        $ticket = SupportTicket::create([
            'ticket_no'         => SupportTicket::generateRef(),
            'subject'           => $request->subject,
            'contact_name'      => $request->contact_name,
            'phone'             => $request->phone,
            'email'             => $request->email,
            'zone_id'           => $request->zone_id,
            'assigned_admin_id' => $request->assigned_admin_id,
            'query_id'          => $request->query_id,
            'channel'           => $request->channel,
            'priority'          => $request->priority,
            'status'            => 'open',
            'description'       => $request->description,
        ]);

        if ($request->query_id) {
            QueryActivity::log(
                $request->query_id,
                'ticket',
                'Ticket #' . $ticket->ticket_no . ' created: ' . $request->subject
            );
        }

        Toastr::success('Ticket #' . $ticket->ticket_no . ' created.');

        if ($request->from_query) {
            return redirect()->route('admin.sales-crm.query.show', $request->from_query);
        }

        return redirect()->route('admin.sales-crm.ticket.index');
    }

    public function show($id)
    {
        $ticket = $this->scopeTicket()->with(['replies.admin', 'salesQuery'])->findOrFail($id);
        return view('sales_crm::admin-views.ticket.show', compact('ticket'));
    }

    public function edit($id)
    {
        $admin   = auth('admin')->user();
        $zoneIds = $admin->crmZoneIds();
        $ticket  = $this->scopeTicket()->findOrFail($id);
        $zones   = $this->crmZones();
        $admins  = Admin::orderBy('f_name')->get();
        $queries = SalesQuery::when(!empty($zoneIds), fn($q) => $q->whereIn('zone_id', $zoneIds))
                             ->orderBy('contact_name')
                             ->get(['id', 'ref_no', 'contact_name']);

        return view('sales_crm::admin-views.ticket.edit', compact('ticket', 'zones', 'admins', 'queries'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'subject'           => 'required|string|max:200',
            'contact_name'      => 'required|string|max:100',
            'phone'             => 'required|string|max:20',
            'email'             => 'nullable|email|max:100',
            'zone_id'           => 'nullable|integer|exists:zones,id',
            'assigned_admin_id' => 'nullable|integer|exists:admins,id',
            'channel'           => 'required|in:' . implode(',', SupportTicket::CHANNELS),
            'priority'          => 'required|in:' . implode(',', SupportTicket::PRIORITIES),
            'status'            => 'required|in:' . implode(',', SupportTicket::STATUSES),
            'description'       => 'nullable|string|max:3000',
            'resolution'        => 'nullable|string|max:3000',
        ]);

        $ticket = $this->scopeTicket()->findOrFail($id);

        $ticket->update([
            'subject'           => $request->subject,
            'contact_name'      => $request->contact_name,
            'phone'             => $request->phone,
            'email'             => $request->email,
            'zone_id'           => $request->zone_id,
            'assigned_admin_id' => $request->assigned_admin_id,
            'query_id'          => $request->query_id,
            'channel'           => $request->channel,
            'priority'          => $request->priority,
            'status'            => $request->status,
            'description'       => $request->description,
            'resolution'        => $request->resolution,
        ]);

        Toastr::success('Ticket updated.');
        return redirect()->route('admin.sales-crm.ticket.show', $id);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:' . implode(',', SupportTicket::STATUSES)]);
        $ticket = $this->scopeTicket()->findOrFail($id);
        $ticket->update(['status' => $request->status]);

        Toastr::success('Status updated.');
        return back();
    }

    public function reply(Request $request, $id)
    {
        $request->validate(['message' => 'required|string|max:3000']);

        $ticket = $this->scopeTicket()->findOrFail($id);

        TicketReply::create([
            'support_ticket_id' => $ticket->id,
            'replied_by'        => auth('admin')->id(),
            'message'           => $request->message,
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        Toastr::success('Reply added.');
        return back();
    }

    public function destroy($id)
    {
        $this->scopeTicket()->findOrFail($id)->delete();
        Toastr::success('Ticket deleted.');
        return redirect()->route('admin.sales-crm.ticket.index');
    }
}
