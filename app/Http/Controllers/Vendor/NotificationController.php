<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\NotificationAddRequest;
use App\Http\Requests\Admin\NotificationUpdateRequest;
use App\Models\Notification;
use App\Models\Zone;
use App\CentralLogics\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PushNotificationExport;
use App\Http\Controllers\Controller;
use App\Traits\FileManagerTrait;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    use FileManagerTrait;
    /* ================= LIST ================= */
    public function index(Request $request)
    {
        $storeId = Helpers::get_store_id();

        $notifications = Notification::where('added_by', 'vendor')
            ->where('vendor_id', $storeId)
            ->latest()
            ->paginate();

        $zones = Zone::all();

        return view('vendor-views.notification.index', compact('notifications', 'zones'));
    }

    /* ================= STORE ================= */
    public function store(Request $request)
    {
        $storeId = Helpers::get_store_id();

        $image = null;
        // if ($request->hasFile('image')) {
        //     $image = Helpers::upload('notification/', 'png', $request->file('image'));
        // }

        if ($request->has('image')) {
            $image = $this->upload('notification/', 'png', $request->file('image'));
        } else {
            $image = null;
        }

        DB::table('notifications')->insert([
            'title'       => $request->notification_title,
            'description' => $request->description,
            'image'       => $image,
            'zone_id'     => $request->zone,
            'vendor_id'   => $storeId,
            'added_by'    => 'vendor',
            'tergat'    => $request->tergat,
            'status'      => 0,
            'approval'      => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // try {
        //     $this->sendPushNotificationToTopic($notification, 'general', 'general');
        // } catch (Exception $e) {
        //     Toastr::warning(translate('messages.push_notification_failed'));
        // }

        return response()->json([
            'success' => true,
            'message' => translate('messages.notification_submitted_successfully'),
        ]);
    }

    /* ================= EDIT ================= */
    public function edit(int $id): View
    {
        $storeId = Helpers::get_store_id();

        $notification = Notification::where('id', $id)
            ->where('vendor_id', $storeId)
            ->firstOrFail();

        $zones = Zone::all();

        return view('vendor-views.notification.edit', compact('notification', 'zones'));
    }

    /* ================= UPDATE ================= */
    public function update(NotificationUpdateRequest $request, int $id): RedirectResponse
    {
        $storeId = Helpers::get_store_id();

        // Ensure notification belongs to vendor
        $notification = Notification::where('id', $id)
            ->where('vendor_id', $storeId)
            ->firstOrFail();

        DB::transaction(function () use ($request, $notification, $storeId, $id) {

            $image = $notification->image;

            if ($request->hasFile('image')) {
                $image = Helpers::upload('notification/', 'png', $request->file('image'));
            }

            DB::table('notifications')
                ->where('id', $id)
                ->where('vendor_id', $storeId)
                ->update([
                    'title'       => $request->notification_title,
                    'description' => $request->description,
                    'zone_id'     => $request->zone,
                    'tergat'      => $request->tergat,
                    'image'       => $image,
                    'status'      => 0,
                    'approval'    => 0,
                    'updated_at'  => now(),
                ]);
        });

        // $notification->image = $notification->image
        //     ? url('/') . '/storage/app/public/notification/' . $notification->image
        //     : null;

        // try {
        //     $this->sendPushNotificationToTopic($notification, 'general', 'general');
        // } catch (Exception) {
        //     Toastr::warning(translate('messages.push_notification_failed'));
        // }

        Toastr::success(translate('messages.notification_updated_successfully'));
        return back();
    }

    /* ================= STATUS ================= */
    public function updateStatus(Request $request): RedirectResponse
    {
        Notification::where('id', $request->id)
            ->update(['status' => $request->status]);

        Toastr::success(translate('messages.notification_status_updated'));
        return back();
    }

    /* ================= DELETE ================= */
    public function delete(Request $request): RedirectResponse
    {
        Notification::where('id', $request->id)->where('vendor_id', Helpers::get_store_id())->delete();

        Toastr::success(translate('messages.notification_deleted_successfully'));
        return back();
    }

    /* ================= EXPORT ================= */
    public function exportList(Request $request): BinaryFileResponse
    {
        $storeId = Helpers::get_store_id();

        $notifications = Notification::where('vendor_id', $storeId)->get();

        $data = [
            'data'   => $notifications,
            'search' => $request->search ?? null,
        ];

        if ($request->type === 'csv') {
            return Excel::download(new PushNotificationExport($data), 'notifications.csv');
        }

        return Excel::download(new PushNotificationExport($data), 'notifications.xlsx');
    }
}
