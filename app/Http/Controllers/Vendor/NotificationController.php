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

        $images = [];
        $firstImage = null;

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $uploaded = $this->upload('notification/', $file->getClientOriginalExtension() ?? 'png', $file);
                $images[] = $uploaded;
            }
            $firstImage = $images[0] ?? null;
        } elseif ($request->hasFile('image')) {
            $uploaded = $this->upload('notification/', $request->file('image')->getClientOriginalExtension() ?? 'png', $request->file('image'));
            $firstImage = $uploaded;
            $images[] = $uploaded;
        }
        $storeName = Helpers::get_store_data()->name ?? 'A vendor';

        DB::table('notifications')->insert([
            'title'       => $request->notification_title,
            'description' => $request->description,
            'image'       => $firstImage,
            'images'      => !empty($images) ? json_encode($images) : null,
            'zone_id'     => $request->zone,
            'vendor_id'   => $storeId,
            'added_by'    => 'vendor',
            'tergat'      => $request->tergat ?? 'customer',
            'status'      => 0,
            'approval'    => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        _inAppNotification(
            'New Vendor Ad Posted',
            ucfirst($storeName) . ' has posted a new ad "' . $request->notification_title . '". Please review and approve.',
            null,
            0,
            route('admin.notification.add-new') . '#ads',
            'admin'
        );

        return response()->json([
            'success' => true,
            'message' => translate('messages.notification_submitted_successfully'),
        ]);
    }

    /* ================= STORE SCHEDULED ================= */
    public function storeScheduled(Request $request)
    {
        $request->validate([
            'notification_title' => 'required|string|max:191',
            'description'        => 'required|string',
            'scheduled_at'       => 'required|date|after:now',
            'zone'               => 'nullable',
            'tergat'             => 'nullable|string',
        ]);

        $storeId = Helpers::get_store_id();

        $images = [];
        $firstImage = null;

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $uploaded = $this->upload('notification/', $file->getClientOriginalExtension() ?? 'png', $file);
                $images[] = $uploaded;
            }
            $firstImage = $images[0] ?? null;
        } elseif ($request->hasFile('image')) {
            $uploaded = $this->upload('notification/', $request->file('image')->getClientOriginalExtension() ?? 'png', $request->file('image'));
            $firstImage = $uploaded;
            $images[] = $uploaded;
        }

        $storeName = Helpers::get_store_data()->name ?? 'A vendor';

        DB::table('notifications')->insert([
            'title'         => $request->notification_title,
            'description'   => $request->description,
            'image'         => $firstImage,
            'images'        => !empty($images) ? json_encode($images) : null,
            'zone_id'       => $request->zone,
            'vendor_id'     => $storeId,
            'added_by'      => 'vendor',
            'tergat'        => $request->tergat ?? 'customer',
            'status'        => 0,
            'approval'      => 0,
            'is_scheduled'  => 1,
            'scheduled_at'  => $request->scheduled_at,
            'schedule_time' => $request->scheduled_at,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        _inAppNotification(
            'New Vendor Ad Posted',
            ucfirst($storeName) . ' has posted a new ad "' . $request->notification_title . '". Please review and approve.',
            null,
            0,
            route('admin.notification.add-new') . '#ads',
            'admin'
        );

        return response()->json([
            'success' => true,
            'message' => 'Ad scheduled successfully. It will be sent after approval at the scheduled time.',
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
            $images = [];

            // Get existing images
            if ($notification->images) {
                $images = is_array($notification->images) ? $notification->images : json_decode($notification->images, true);
            } elseif ($notification->image) {
                $images = [$notification->image];
            }

            // Remove selected images
            if ($request->has('removed_images')) {
                $removedImages = $request->input('removed_images');
                if (is_array($removedImages)) {
                    foreach ($removedImages as $removed) {
                        if (($key = array_search($removed, $images)) !== false) {
                            unset($images[$key]);
                            // Delete from disk
                            if (\Storage::disk('public')->exists('notification/' . $removed)) {
                                \Storage::disk('public')->delete('notification/' . $removed);
                            }
                        }
                    }
                    $images = array_values($images); // Re-index array
                }
            }

            // Add new images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $uploaded = $this->upload('notification/', $file->getClientOriginalExtension() ?? 'png', $file);
                    $images[] = $uploaded;
                }
            }

            // Handle legacy single image upload if present
            if ($request->hasFile('image')) {
                $uploaded = $this->upload('notification/', $request->file('image')->getClientOriginalExtension() ?? 'png', $request->file('image'));
                $images[] = $uploaded;
            }

            $firstImage = $images[0] ?? null;

            // Handle scheduling inputs
            $isScheduled = $request->boolean('is_scheduled', false);
            $scheduledAt = $isScheduled ? $request->scheduled_at : null;
            $scheduleTime = $isScheduled ? $request->scheduled_at : null;

            DB::table('notifications')
                ->where('id', $id)
                ->where('vendor_id', $storeId)
                ->update([
                    'title'         => $request->notification_title,
                    'description'   => $request->description,
                    'zone_id'       => $request->zone,
                    'tergat'        => $request->tergat,
                    'image'         => $firstImage,
                    'images'        => !empty($images) ? json_encode($images) : null,
                    'status'        => 0,
                    'approval'      => 0,
                    'is_scheduled'  => $isScheduled ? 1 : 0,
                    'scheduled_at'  => $scheduledAt,
                    'schedule_time' => $scheduleTime,
                    'updated_at'    => now(),
                ]);
        });

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

    /* ================= RESCHEDULE ================= */
    public function reschedule(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $storeId = Helpers::get_store_id();

        $notification = Notification::where('id', $id)
            ->where('vendor_id', $storeId)
            ->firstOrFail();

        DB::table('notifications')
            ->where('id', $id)
            ->where('vendor_id', $storeId)
            ->update([
                'is_scheduled'  => 1,
                'scheduled_at'  => $request->scheduled_at,
                'schedule_time' => $request->scheduled_at,
                'status'        => 0,
                'approval'      => 0,
                'updated_at'    => now(),
            ]);

        Toastr::success('Ad rescheduled successfully and sent for approval.');
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
