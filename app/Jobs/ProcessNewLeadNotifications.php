<?php

namespace App\Jobs;

use App\CentralLogics\Helpers;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fan-out notifications for a new service enquiry (lead): per-vendor SMS + in-app +
 * auto-accept/WhatsApp + wallet reminder, and the customer confirmation push.
 * Runs on the queue (Horizon) so the customer's enquiry-submit returns immediately
 * instead of blocking on N vendor HTTP calls.
 */
class ProcessNewLeadNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: SMS/WhatsApp/push aren't idempotent, so a retry could double-send.
    public int $tries = 1;
    public int $timeout = 180;

    public function __construct(
        public int $serviceRequestId,
        public array $storeIds = [],
        public bool $isAppointment = false
    ) {
        // Dedicated, high-priority queue with its own workers — leads are time-critical and
        // must never wait behind a backlog on the default queue.
        $this->onQueue('leads');
    }

    public function handle(): void
    {
        $serviceReq = DB::table('service_requests')->where('id', $this->serviceRequestId)->first();
        if (!$serviceReq) {
            return;
        }

        $itemDet = DB::table('items')->where('id', $serviceReq->item_id)->first();
        $userDet = User::find($serviceReq->user_id);
        $customerName = !empty($userDet->f_name) ? $userDet->f_name : 'a customer';
        $serviceName  = !empty($itemDet->name) ? $itemDet->name : 'a service';
        $title = $this->isAppointment ? 'New Appointment Request' : 'New Service Enquiry';

        // Fan-out to the matched vendors.
        if (!empty($this->storeIds)) {
            $msg = 'Hello! , You have received a new ' . ($this->isAppointment ? 'APPOINTMENT request' : 'ENQUIRY')
                . ' from ' . $customerName . ' for ' . $serviceName
                . '. Please visit the My Chitti Vendor App. Thank you, My Chitti Team.';
            $url = parse_url(route('vendor.service.leads_list'), PHP_URL_PATH);

            foreach ($this->storeIds as $storeId) {
                try {
                    $store2 = DB::table('stores')->where('id', $storeId)->first();
                    if (!$store2) {
                        continue;
                    }
                    _sendSMS($store2->phone, $msg);
                    _inAppNotification($title, $msg, null, $store2->id, $url, 'vendor');
                    if (!_autoAcceptLeadForStore($store2->id, $serviceReq->id)) {
                        WhatsAppService::sendLeadNotification($store2->id, $itemDet->name ?? null, $userDet->f_name ?? null);
                    }
                    _remindLeadWalletRecharge($store2, $itemDet->category_id ?? null);
                } catch (\Throwable $e) {
                    Log::warning('Lead notify failed for store ' . $storeId . ': ' . $e->getMessage());
                }
            }
        }

        // Confirmation push to the customer.
        if ($userDet && !empty($userDet->cm_firebase_token)) {
            try {
                $data = [
                    'title' => 'Service Request',
                    'description' => 'Service Requested Successfully.',
                    'order_id' => $this->serviceRequestId,
                    'image' => '',
                    'type' => 'block',
                ];
                Helpers::send_push_notif_to_device($userDet->cm_firebase_token, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'user_id' => $userDet->id,
                    'type' => 'service',
                    'type_id' => $this->serviceRequestId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Lead customer push failed (req ' . $this->serviceRequestId . '): ' . $e->getMessage());
            }
        }
    }
}
