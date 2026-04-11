<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendScheduledEmailBroadcast;
use App\Models\ScheduledEmail;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduledNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $emails = ScheduledEmail::latest()->paginate(config('default_pagination'));
        return view('admin-views.notification.scheduled-email', compact('emails'));
    }

    public function storeEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email_subject' => 'required|string|max:255',
            'message'       => 'required|string',
            'send_to'       => 'required|in:all_users,all_vendors,all_delivery_men',
            'scheduled_at'  => 'required|date|after:now',
        ]);

        ScheduledEmail::create([
            'subject'      => $request->email_subject,
            'message'      => $request->message,
            'send_to'      => $request->send_to,
            'scheduled_at' => $request->scheduled_at,
            'status'       => 'pending',
        ]);

        return response()->json(['message' => 'Email broadcast scheduled successfully!']);
    }

    public function sendNow(Request $request, $scheduledNotification): RedirectResponse
    {
        $scheduled = ScheduledEmail::findOrFail($scheduledNotification);

        if ($scheduled->status !== 'pending') {
            Toastr::warning('This email has already been sent or failed.');
            return back();
        }

        SendScheduledEmailBroadcast::dispatch($scheduled->id);
        Toastr::success('Email broadcast queued for immediate sending.');
        return back();
    }

    public function destroy($scheduledNotification): RedirectResponse
    {
        $scheduled = ScheduledEmail::findOrFail($scheduledNotification);
        $scheduled->delete();
        Toastr::success('Scheduled email deleted.');
        return back();
    }
}
