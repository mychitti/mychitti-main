<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuspiciousActivityController extends Controller
{
    public function index(Request $request)
    {
        $type   = $request->type ?? null;
        $status = $request->status ?? 'open';

        $logs = DB::table('suspicious_activity_logs')
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderByDesc('updated_at')
            ->paginate(20);

        // Attach user info
        $userIds = $logs->pluck('user_id')->filter()->unique();
        $users   = User::whereIn('id', $userIds)->get()->keyBy('id');

        $openCount      = DB::table('suspicious_activity_logs')->where('status', 'open')->count();
        $otpCount       = DB::table('suspicious_activity_logs')->where('type', 'otp_abuse')->where('status', 'open')->count();
        $enquiryCount   = DB::table('suspicious_activity_logs')->where('type', 'enquiry_abuse')->where('status', 'open')->count();

        return view('admin-views.suspicious-activity.index', compact('logs', 'users', 'openCount', 'otpCount', 'enquiryCount', 'type', 'status'));
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|integer',
            'status' => 'required|in:reviewed,dismissed,open',
        ]);

        DB::table('suspicious_activity_logs')
            ->where('id', $request->id)
            ->update(['status' => $request->status, 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }
}
