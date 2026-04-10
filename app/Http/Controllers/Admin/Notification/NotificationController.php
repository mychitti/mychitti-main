<?php

namespace App\Http\Controllers\Admin\Notification;

use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Contracts\Repositories\ZoneRepositoryInterface;
use App\Enums\ExportFileNames\Admin\Notification;
use App\Enums\ViewPaths\Admin\Notification as NotificationViewPath;
use App\Exports\PushNotificationExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\NotificationAddRequest;
use App\Http\Requests\Admin\NotificationUpdateRequest;
use App\Models\Notification as ModelsNotification;
use App\Services\NotificationService;
use App\Traits\NotificationTrait;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NotificationController extends BaseController
{
    use NotificationTrait;
    public function __construct(
        protected NotificationRepositoryInterface $notificationRepo,
        protected NotificationService $notificationService,
        protected ZoneRepositoryInterface $zoneRepo
    ) {}

    public function index(?Request $request): View|Collection|LengthAwarePaginator|null
    {
        return $this->getAddView($request);
    }

    private function getAddView($request): View
    { 
        $notifications = $this->notificationRepo->getListWhere(
            searchValue: $request['search'],
            dataLimit: config('default_pagination'),
        );
        $vendor_ads = $this->notificationRepo->getVendorListWhere(
            searchValue: $request['search'],
            dataLimit: config('default_pagination'),
        );
        $zones = $this->zoneRepo->getList();
        return view(NotificationViewPath::INDEX[VIEW], compact('notifications', 'zones', 'vendor_ads'));
    }

    public function add(NotificationAddRequest $request): JsonResponse
    {
        $isScheduled = $request->boolean('is_scheduled', false);
        
        $data = $this->notificationService->getAddData(request: $request);
        $data['is_scheduled'] = $isScheduled ? 1 : 0;
        $data['schedule_time'] = $isScheduled ? $request->scheduled_at : null;
        
        $notification = $this->notificationRepo->add(data: $data);
        
        if (!$isScheduled) {
            $topic = $this->notificationService->getTopic(request: $request);
            $notification->image = $notification->image ? url('/') . '/storage/app/public/notification/' . $notification->image : null;
            
            try {
                $this->sendPushNotificationToTopic($notification, $topic, 'general');
                Toastr::success('Notification sent successfully!');
            } catch (Exception $e) {
                Toastr::warning(translate('messages.push_notification_failed'));
            }
        } else {
            Toastr::success('Notification scheduled for ' . $request->scheduled_at);
        }
        
        return response()->json(['message' => $isScheduled ? 'Scheduled!' : 'Sent!']);
    }

    public function getUpdateView(string|int $id): View
    {
        $notification = $this->notificationRepo->getFirstWhere(params: ['id' => $id]);
        $zones = $this->zoneRepo->getList();
        return view(NotificationViewPath::UPDATE[VIEW], compact('notification', 'zones'));
    }

    public function detail(Request $request, $id)
    {

        $notification = ModelsNotification::find($id);
        return view('admin-views.notification.detail', compact('notification'));
    }
    public function approval(Request $request, $id)
    {
        $notification = (array) DB::table('notifications')->where('id', $id)->first();

        if (!$notification) {
            Toastr::error('Notification not found');
            return back();
        }

        DB::table('notifications')->where('id', $id)->update([
            'approval' => 1,
            'status'   => 1,
        ]);

        try {
            $topic = $this->notificationService->getTopicByZoneAndTarget($notification['zone_id'], $notification['tergat']);
            $notification['image'] = $notification['image'] ? url('/') . '/storage/app/public/notification/' . $notification['image'] : null;

            $this->sendPushNotificationToTopic($notification,  $topic, 'general');
            Toastr::success('Approved and Sent Successfully');
            return back();
        } catch (\Exception $e) {
            Toastr::warning(translate('messages.push_notification_failed'));
        }
    }

    public function update(NotificationUpdateRequest $request, $id): RedirectResponse
    {
        $notification = $this->notificationRepo->getFirstWhere(params: ['id' => $id]);
        $notification = $this->notificationRepo->update(id: $id, data: $this->notificationService->getUpdateData(request: $request, notification: $notification));

        $topic = $this->notificationService->getTopic(request: $request);

        $notification->image = $notification->image ? url('/') . '/storage/app/public/notification/' . $notification->image : null;

        try {
            $this->sendPushNotificationToTopic($notification, $topic, 'general');
        } catch (Exception) {
            Toastr::warning(translate('messages.push_notification_failed'));
        }

        Toastr::success(translate('messages.notification_updated_successfully'));
        return back();
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $this->notificationRepo->update(id: $request['id'], data: ['status' => $request['status']]);
        Toastr::success(translate('messages.notification_status_updated'));
        return back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->notificationRepo->delete(id: $request['id']);
        Toastr::success(translate('messages.notification_deleted_successfully'));
        return back();
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $notifications = $this->notificationRepo->getExportList($request);
        $data = [
            'data' => $notifications,
            'search' => $request['search'] ?? null
        ];
        if ($request['type'] == 'csv') {
            return Excel::download(new PushNotificationExport($data), Notification::EXPORT_CSV);
        }
        return Excel::download(new PushNotificationExport($data), Notification::EXPORT_XLSX);
    }
}
