<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Store;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');
 
        $admin = auth('admin')->user(); 

        $tickets = SupportTicket::with(['assignedTo', 'vendor'])
            ->when($admin->role_id != 1, fn($q) => $q->where(function($q2) use ($admin) {
                $q2->where('assigned_to', $admin->id)->orWhere('created_by', $admin->id);
            }))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('ticket_id', 'like', "%$search%") 
                    ->orWhere('subject', 'like', "%$search%")
                    ->orWhere('guest_name', 'like', "%$search%")
                    ->orWhere('guest_phone', 'like', "%$search%");
            }))
            ->latest()
            ->paginate(25); 

        return view('admin-views.support-ticket.index', compact('tickets', 'status', 'search'));
    }

    public function create()
    {
        $staff = Admin::all();
        return view('admin-views.support-ticket.create', compact('staff'));
    }

    public function store(Request $request)
    { 
        $request->validate([
            'subject' => 'required|string|max:255',
            'department' => 'required|string|max:100',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high',
        ]);

        $ticketId = 'TKT-' . str_pad(SupportTicket::max('id') + 1, 6, '0', STR_PAD_LEFT);

        SupportTicket::create([
            'ticket_id' => $ticketId,
            'subject' => $request->subject,
            'department' => $request->department,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => 'open',
            'assigned_to' => $request->assigned_to ?: null,
            'vendor_id' => $request->vendor_id,
            'user_id' => $request->user_id,
            'guest_name' => $request->guest_name,
            'guest_phone' => $request->guest_phone,
            'guest_email' => $request->guest_email,
            'created_by' => auth('admin')->id(),
        ]);

        Toastr::success('Ticket created successfully!');
        return redirect()->route('admin.ticket.index');
    }

    public function show($id)
    {
        $ticket = SupportTicket::with(['replies.repliedBy', 'assignedTo', 'vendor'])->findOrFail($id);
        $staff = Admin::all();
        return view('admin-views.support-ticket.show', compact('ticket', 'staff'));
    }

    public function reply(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);

        $ticket = SupportTicket::findOrFail($id);

        SupportTicketReply::create([
            'support_ticket_id' => $ticket->id,
            'message' => $request->message,
            'replied_by' => auth('admin')->id(),
        ]);

        if ($ticket->status === 'closed') {
            $ticket->update(['status' => 'reopened']);
        }

        Toastr::success('Reply added!');
        return redirect()->back();
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:open,closed,reopened']);
        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['status' => $request->status]);

        Toastr::success('Ticket status updated to ' . $request->status . '!');
        return redirect()->back();
    }

    public function assign(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['assigned_to' => $request->assigned_to ?: null]);

        Toastr::success('Ticket assigned!');
        return redirect()->back(); 
    }

    public function delete($id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->replies()->delete();
        $ticket->delete();

        Toastr::success('Ticket deleted!');
        return redirect()->route('admin.ticket.index');
    }

    public function searchCustomers(Request $request)
    {
        $search = $request->query('q', '');
        $customers = User::where(function ($q) use ($search) {
                $q->where('f_name', 'like', "%{$search}%")
                  ->orWhere('l_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'f_name', 'l_name', 'phone']);

        $results = $customers->map(fn($c) => [
            'id' => $c->id,
            'text' => $c->f_name . ' ' . $c->l_name . ' (' . $c->phone . ')',
        ]);

        return response()->json(['results' => $results]);
    }

    public function searchVendors(Request $request)
    {
        $search = $request->query('q', '');
        $vendors = Store::withoutGlobalScopes()->where('status', 1)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'phone']);

        $results = $vendors->map(fn($v) => [
            'id' => $v->id,
            'text' => $v->name .  ' (' . $v->phone . ')',
        ]);

        return response()->json(['results' => $results]);
    }
}
