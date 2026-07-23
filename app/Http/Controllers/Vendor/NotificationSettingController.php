<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Services\NotificationPrefs;
use App\Services\WhatsAppService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationSettingController extends Controller
{
    // Per-action toggles, one page per direction (send | receive), tabbed by channel.
    public function index(Request $request, string $direction = 'send')
    {
        if (!in_array($direction, ['send', 'receive'], true)) {
            abort(404);
        }

        $storeId = Helpers::get_store_id();
        WhatsAppService::ensureStoreColumns();

        $channels = NotificationPrefs::forDirection($storeId, $direction);

        $store = DB::table('stores')->where('id', $storeId)
            ->select('wa_enabled', 'wa_phone_number_id', 'wa_appt_reminder')
            ->first();
        $waConnected = (bool) ($store && $store->wa_enabled && $store->wa_phone_number_id);
        // NULL = never chose → the 2-hour default; an explicit '0' = turned off.
        $apptRaw = $store->wa_appt_reminder ?? null;
        $apptReminder = ($apptRaw === null || $apptRaw === '') ? WhatsAppService::DEFAULT_APPT_REMINDER_HOURS : (int) $apptRaw;

        // Paid add-on status — lead alerts need it on top of the toggle.
        $leadFeature = WhatsAppService::receivingFeatureStatus($storeId)['leads'] ?? null;

        return view('vendor-views.notification-settings.index', compact(
            'direction', 'channels', 'waConnected', 'apptReminder', 'leadFeature'
        ));
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'group'   => 'required|string',
            'key'     => 'required|string',
            'enabled' => 'required|in:0,1',
        ]);

        $ok = NotificationPrefs::set(
            Helpers::get_store_id(),
            $request->group,
            $request->key,
            $request->enabled === '1'
        );

        if (!$ok) {
            Toastr::error('Unknown notification setting.');
            return back();
        }

        Toastr::success($request->enabled === '1' ? 'Notification turned on.' : 'Notification turned off.');
        return back();
    }
}
