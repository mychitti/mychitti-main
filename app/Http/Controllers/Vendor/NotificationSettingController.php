<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Services\HmisWhatsAppShare;
use App\Services\ServiceRecallReminder;
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

        // How long after a completed job this store invites the customer back. NULL = never,
        // which is why the input shows blank rather than a number nobody chose.
        ServiceRecallReminder::ensureColumn();
        $serviceRecallDays = DB::table('stores')->where('id', $storeId)->value('wa_service_recall_days');

        // Timing for the two hospital messages whose value depends on when they arrive.
        $feedbackDelay = HmisWhatsAppShare::feedbackDelayHours($storeId);
        $followupLead  = HmisWhatsAppShare::followUpLeadDays($storeId);

        return view('vendor-views.notification-settings.index', compact(
            'direction', 'channels', 'waConnected', 'apptReminder', 'leadFeature',
            'feedbackDelay', 'followupLead', 'serviceRecallDays'
        ));
    }

    /**
     * Save when a delayed hospital message goes out — hours after the visit for feedback, days
     * before the visit for a follow-up reminder.
     *
     * Only affects messages queued from here on. Anything already waiting keeps the time it was
     * given, so changing the setting never reshuffles messages a patient is already expecting.
     */
    public function timing(Request $request)
    {
        $request->validate([
            'setting' => 'required|in:feedback,followup',
            'value'   => 'required|integer|min:0|max:720',
        ]);

        $storeId = Helpers::get_store_id();
        WhatsAppService::ensureStoreColumns();

        if ($request->setting === 'feedback') {
            $value = min(720, max(0, (int) $request->value));
            DB::table('stores')->where('id', $storeId)->update(['wa_feedback_delay' => (string) $value]);

            Toastr::success($value === 0
                ? 'Feedback will be asked for as soon as the visit is completed.'
                : 'Feedback will be asked for ' . $value . ' hour' . ($value === 1 ? '' : 's') . ' after the visit.');

            return back();
        }

        $value = min(30, max(0, (int) $request->value));
        DB::table('stores')->where('id', $storeId)->update(['wa_followup_lead' => (string) $value]);

        Toastr::success($value === 0
            ? 'Follow-ups will be confirmed as soon as you book them.'
            : 'Follow-up reminders will go out ' . $value . ' day' . ($value === 1 ? '' : 's') . ' before the visit.');

        return back();
    }

    /**
     * How many days after a completed service request this store invites the customer back.
     * Blank or 0 clears it, which takes the store out of the sweep altogether.
     */
    public function serviceRecall(Request $request)
    {
        $request->validate(['days' => 'nullable|integer|min:0|max:1095']);

        ServiceRecallReminder::ensureColumn();
        $days = $request->input('days');
        $days = ($days === null || $days === '' || (int) $days === 0) ? null : (int) $days;

        DB::table('stores')->where('id', Helpers::get_store_id())
            ->update(['wa_service_recall_days' => $days]);

        Toastr::success($days
            ? 'Customers will be invited back ' . $days . ' days after a completed service.'
            : 'Service recall turned off — no invitations will be sent.');

        return back();
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

        if ($request->enabled !== '1') {
            Toastr::success('Notification turned off.');
            return back();
        }

        // Turning it on is only half the job — the message still needs an approved template on the
        // vendor's own WhatsApp account. Saying so now beats them discovering weeks later that
        // nothing was ever sent.
        $warning = $this->templateWarning($request->group, $request->key);
        if ($warning) {
            Toastr::warning($warning);
        } else {
            Toastr::success('Notification turned on.');
        }

        return back();
    }

    /**
     * Why this action can't send yet, or null when it can.
     *
     * An unreadable template list is deliberately treated as "fine": the vendor is told what is
     * definitely wrong, never warned on a guess because Meta's API happened to be slow.
     */
    private function templateWarning(string $group, string $key): ?string
    {
        $item = NotificationPrefs::GROUPS[$group]['items'][$key] ?? null;
        $template = $item['template'] ?? null;
        if (!$template) {
            return null;
        }

        $storeId = Helpers::get_store_id();
        $wa = WhatsAppService::make($storeId);
        if ($wa->source() !== 'vendor') {
            return 'Turned on — but nothing will send until you connect your own WhatsApp number under WhatsApp → Connection.';
        }

        $statuses = WhatsAppService::templateStatuses($storeId);
        if (empty($statuses)) {
            return null;
        }

        $status = $statuses[strtolower($template)] ?? null;

        if ($status === 'APPROVED') {
            return null;
        }
        if ($status === null) {
            return 'Turned on — but the "' . $template . '" template does not exist on your WhatsApp account yet, so nothing will send. '
                . 'Add it in one click from WhatsApp → Message Templates → Ready-made templates.';
        }
        if ($status === 'PENDING') {
            return 'Turned on — "' . $template . '" is still being reviewed by Meta. Messages start going out as soon as it is approved (usually minutes, up to 24 hours).';
        }

        return 'Turned on — but "' . $template . '" is ' . $status . ' at Meta, so nothing will send. '
            . 'Fix or re-submit it under WhatsApp → Message Templates.';
    }
}
