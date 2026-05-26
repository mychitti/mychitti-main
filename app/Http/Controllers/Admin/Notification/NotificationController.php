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
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NotificationController extends BaseController
{
    use NotificationTrait;

    /**
     * Resolve valid FCM tokens for the target audience.
     * target: customer | deliveryman | store
     * zoneId: numeric zone ID or null for all zones
     */
    private function resolveTokens(string $target, ?int $zoneId): array
    {
        $column = match($target) {
            'deliveryman' => 'dm_firebase_token',
            'store'       => 'firebase_token',
            default       => 'cm_firebase_token', // customer
        };

        $table = match($target) {
            'deliveryman' => 'delivery_men',
            'store'       => 'vendors',
            default       => 'users',
        };

        return DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->where($column, '!=', '1')
            ->where($column, '!=', '0')
            ->where(DB::raw("length({$column})"), '>', 20)
            ->when($zoneId, fn($q) => $q->where('zone_id', $zoneId))
            ->pluck($column)
            ->toArray();
    }

    public function __construct(
        protected NotificationRepositoryInterface $notificationRepo,
        protected NotificationService $notificationService,
        protected ZoneRepositoryInterface $zoneRepo
    ) {}

    private function resolveCustomerTopic($zone): string
    {
        if (!$zone || $zone === 'all') {
            return 'all_zone_customer';
        }
        return 'zone_' . (int) $zone . '_customer';
    }

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
            $notification->image = $notification->image ? url('/') . '/storage/app/public/notification/' . $notification->image : null;

            if ($request->tergat === 'customer') {
                $topic = $this->resolveCustomerTopic($request->zone);
                $result = $this->sendPushNotificationToTopic(
                    ['title' => $notification->title, 'description' => $notification->description ?? '', 'image' => $notification->image ?? ''],
                    $topic,
                    'general'
                );
                if (!empty($result['error'])) {
                    Toastr::warning('Push notification failed: ' . $result['error']);
                } else {
                    Toastr::success('Notification sent to topic: ' . $topic);
                }
            } else {
                $zoneId = $request->zone === 'all' ? null : (int) $request->zone;
                $tokens = $this->resolveTokens($request->tergat, $zoneId);
                if (empty($tokens)) {
                    Toastr::warning('No device tokens found for the selected audience.');
                } else {
                    $result = $this->sendPushNotificationToTokensBatch($tokens, $notification, 'general');
                    if ($result['sent'] > 0) {
                        Toastr::success("Notification sent to {$result['sent']} device(s)." . ($result['failed'] ? " ({$result['failed']} failed)" : ''));
                    } else {
                        Toastr::warning('Push notification failed: ' . implode(', ', $result['errors'] ?: ['All sends failed']));
                    }
                }
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
    public function approval(Request $request)
    {
        $id = $request->id;
        $days = $request->days;
        $notification = (array) DB::table('notifications')->where('id', $id)->first();

        if (!$notification) {
            Toastr::error('Notification not found');
            return back();
        }

        DB::table('notifications')->where('id', $id)->update([
            'approval'    => 1,
            'status'      => 1,
            'days'        => $days,
            'approved_at' => now(),
        ]);

        $isScheduledFuture = !empty($notification['is_scheduled'])
            && !empty($notification['scheduled_at'])
            && \Carbon\Carbon::parse($notification['scheduled_at'])->isFuture();

        if ($isScheduledFuture) {
            Toastr::success('Approved. Will be sent at ' . \Carbon\Carbon::parse($notification['scheduled_at'])->format('d M Y, h:i A') . '.');
            return back();
        }

        $notification['image'] = $notification['image'] ? url('/') . '/storage/app/public/notification/' . $notification['image'] : null;

        if (($notification['tergat'] ?? 'customer') === 'customer') {
            $topic = $this->resolveCustomerTopic($notification['zone_id'] ?? null);
            $result = $this->sendPushNotificationToTopic(
                ['title' => $notification['title'], 'description' => $notification['description'] ?? '', 'image' => $notification['image'] ?? ''],
                $topic,
                'general'
            );
            if (!empty($result['error'])) {
                Toastr::warning('Approved but push failed: ' . $result['error']);
            } else {
                Toastr::success('Approved and sent to topic: ' . $topic);
            }
        } else {
            $zoneId = !empty($notification['zone_id']) ? (int) $notification['zone_id'] : null;
            $tokens = $this->resolveTokens($notification['tergat'], $zoneId);
            if (empty($tokens)) {
                Toastr::warning('Approved but no device tokens found for the audience.');
            } else {
                $result = $this->sendPushNotificationToTokensBatch($tokens, $notification, 'general');
                if ($result['sent'] > 0) {
                    Toastr::success("Approved and sent to {$result['sent']} device(s).");
                } else {
                    Toastr::warning('Approved but push notification failed: ' . implode(', ', $result['errors'] ?: ['unknown']));
                }
            }
        }
        return back();
    }

    public function update(NotificationUpdateRequest $request, $id): RedirectResponse
    {
        $notification = $this->notificationRepo->getFirstWhere(params: ['id' => $id]);
        $notification = $this->notificationRepo->update(id: $id, data: $this->notificationService->getUpdateData(request: $request, notification: $notification));

        $notification->image = $notification->image ? url('/') . '/storage/app/public/notification/' . $notification->image : null;

        if ($request->tergat === 'customer') {
            $topic = $this->resolveCustomerTopic($request->zone);
            $result = $this->sendPushNotificationToTopic(
                ['title' => $notification->title, 'description' => $notification->description ?? '', 'image' => $notification->image ?? ''],
                $topic,
                'general'
            );
            if (!empty($result['error'])) {
                Toastr::warning('Push notification failed: ' . $result['error']);
            } else {
                Toastr::success('Notification sent to topic: ' . $topic);
            }
        } else {
            $zoneId = ($request->zone && $request->zone !== 'all') ? (int) $request->zone : null;
            $tokens = $this->resolveTokens($request->tergat, $zoneId);
            if (empty($tokens)) {
                Toastr::warning('No device tokens found for the selected audience.');
            } else {
                $result = $this->sendPushNotificationToTokensBatch($tokens, $notification, 'general');
                if ($result['sent'] > 0) {
                    Toastr::success("Notification sent to {$result['sent']} device(s)." . ($result['failed'] ? " ({$result['failed']} failed)" : ''));
                } else {
                    Toastr::warning('Push notification failed: ' . implode(', ', $result['errors'] ?: ['All sends failed']));
                }
            }
        }

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
