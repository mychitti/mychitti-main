<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Store;
use App\Models\VendorLoginLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LogController extends Controller
{

    public function index(Request $request)
    {
        $query = VendorLoginLog::latest();

        if ($request->filled('store_id')) {
            $store = Store::find($request->store_id);
            if ($store) {
                $query->where('vendor_id', $store->vendor_id);
            }
        }

        $logs = $query->paginate(20)->withQueryString();

        $inactiveLimit = 15; // minutes

        foreach ($logs as $log) {

            if (!$log->login_at) {
                $log->duration = '-';
                continue;
            }

            if ($log->logout_at) {
                $endTime = Carbon::parse($log->logout_at);
            } elseif ($log->last_activity_at) {

                $last = Carbon::parse($log->last_activity_at);

                // if inactive → stop at last activity
                if (now()->diffInMinutes($last) > $inactiveLimit) {
                    $endTime = $last;
                } else {
                    $endTime = now(); // still active
                }
            } else {
                $log->duration = '-';
                continue;
            }

            $duration = Carbon::parse($log->login_at)
                ->diffInSeconds($endTime);

            $log->duration = gmdate("H:i:s", $duration);
        }
        return view('admin-views.logs.vendor-access-logs', compact('logs'));
    }
}
