<?php

namespace App\Http\Controllers\Api;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\Item;
use App\Models\ServiceRequest;  
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Internal API for the AI service droplet.
 * Protected by X-Api-Key (ai.internal middleware).
 */
class AiInternalController extends Controller
{
    // ── Service Booking History ───────────────────────────────────────────

    /**
     * GET /api/v1/ai-internal/user/{userId}/service-bookings
     */
    public function userServiceBookings(Request $request, int $userId)
    {
        $bookings = ServiceRequest::where('user_id', $userId)
            ->with([
                'item:id,name',
                'accepted:service_request_id,vendor_id,assigned_to,assigned_type',
                'accepted.store:id,name,phone',
                'accepted.staff' => function (MorphTo $morphTo) {
                    $morphTo->constrain([
                        \App\Models\VendorEmployee::class => function ($q) {
                            $q->select('id', 'f_name', 'l_name', 'phone', 'email', 'image');
                        },
                        \App\Models\Store::class => function ($q) {
                            $q->select('id', 'name', 'phone', 'email', 'logo', 'address');
                        },
                    ]);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'item_id', 'status', 'requirements', 'city', 'created_at', 'updated_at']);

        return response()->json([
            'success'  => true,
            'bookings' => $bookings->map(fn($b) => [
                'id'           => $b->id,
                'service_name' => $b->item?->name ?? 'Unknown Service',
                'status'       => $b->status ?? 'new',
                'requirements' => $b->requirements,
                'city'         => $b->city,
                'date'         => $b->created_at?->format('d M Y'),
                'assigned'     => $b->accepted !== null,
                'staff'        => $b->accepted?->assigned_details['name'], // normalized: {id,type,name,phone,...}
                'store'        => $b->accepted?->store?->name,
            ]),
        ]);
    }

    // ── Available Services ────────────────────────────────────────────────

    /**
     * GET /api/v1/ai-internal/services
     */
    public function listServices(Request $request)
    {
        $services = Item::withoutGlobalScopes()
            ->where('module_id', 6)
            ->where('status', 1)
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'services' => $services]);
    }

    // ── User Addresses ────────────────────────────────────────────────────

    /**
     * GET /api/v1/ai-internal/user/{userId}/addresses
     */
    public function userAddresses(Request $request, int $userId)
    {
        $addresses = CustomerAddress::where('user_id', $userId)
            ->select('id', 'address_type', 'house', 'floor', 'road', 'address', 'city', 'zone_id', 'latitude', 'longitude')
            ->get()
            ->map(fn($a) => [
                'id'      => $a->id,
                'label'   => $a->address_type ?? 'Address',
                'address' => trim(collect([$a->house, $a->floor, $a->road, $a->address])->filter()->implode(', ')),
            ]);

        return response()->json(['success' => true, 'addresses' => $addresses]);
    }

    // ── Create Service Booking ────────────────────────────────────────────

    /**
     * POST /api/v1/ai-internal/user/{userId}/service-booking
     */ 
    public function createServiceBooking(Request $request, int $userId)
    {
        $validator = Validator::make($request->all(), [
            'item_id'      => 'required|integer',
            'address_id'   => 'nullable|integer',
            'requirements' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }
        try {

            // Resolve address: use provided address_id or fall back to user's most recent address
            $addressQuery = CustomerAddress::where('user_id', $userId);
            $address = $request->address_id
                ? $addressQuery->where('id', $request->address_id)->first()
                : $addressQuery->latest()->first();

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'No address found. Please add an address in the app first.',
                ], 404);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found.'], 404);
            }

            // Resolve pin_code from address or coordinates
            $pinCode = $address->pin_code;
            if (!$pinCode && $address->latitude && $address->longitude) {
                $pinCode = getPincodeFromCoordinates((float) $address->latitude, (float) $address->longitude);
            }
            if (!$user->pin_code && $pinCode) {
                $user->pin_code = $pinCode;
                $user->save();
            }

            // get_store_range expects zone_ids as a JSON array string (e.g. "[5]"), not a plain integer
            $storesChunk = Helpers::get_store_range($request->item_id, json_encode([$address->zone_id]), $userId);

            $fullAddress = trim(
                collect([$address->house, $address->floor, $address->road, $address->address])
                    ->filter()->implode(', ')
            );

            $serviceReq               = new ServiceRequest;
            $serviceReq->user_id      = $userId;
            $serviceReq->item_id      = $request->item_id;
            $serviceReq->sent_to      = implode(',', $storesChunk);
            $serviceReq->qty          = 1;
            $serviceReq->requirements = $request->requirements;
            $serviceReq->module_id    = 6;
            $serviceReq->zone_id      = $address->zone_id;
            $serviceReq->latitude     = (float) $address->latitude;
            $serviceReq->longitude    = (float) $address->longitude;
            $serviceReq->pin_code     = $pinCode;
            $serviceReq->status       = 'new';
            $serviceReq->address      = $fullAddress;
            $serviceReq->address_id   = $request->address_id;
            $serviceReq->gst          = $user->gst ?? null;
            $serviceReq->created_at   = now();

            if (!$serviceReq->save()) {
                return response()->json(['success' => false, 'message' => 'Failed to create booking.'], 500);
            }

            DB::table('lead_statuses')->insert([
                'service_request_id' => $serviceReq->id,
                'status'             => 'User Requested Service',
                'created_at'         => now(),
            ]);

            // ── Notifications (mirrors ServiceRequestController@add) ──────

            $adminUrl  = route('admin.service.lead-list');
            $adminMsg  = 'A new service enquiry has been submitted. Please review the details';
            $adminTitle = 'New Service Enquiry';
            _sendSMSToAdmin($adminMsg, $adminTitle, $adminUrl);

            if (count($storesChunk)) {
                $itemDet  = DB::table('items')->where('id', $request->item_id)->first();
                $vendorMsg = 'Hello! , You have received a new ENQUIRY from '
                    . (!empty($user->f_name) ? $user->f_name : 'a customer')
                    . ' for ' . (!empty($itemDet->name) ? $itemDet->name : 'a service')
                    . '. Please visit the My Chitti Vendor App. Thank you, My Chitti Team.';
                $vendorUrl = route('vendor.service.leads_list');
  
                foreach ($storesChunk as $storeId) {
                    $store = DB::table('stores')->where('id', $storeId)->first();
                    if ($store) {
                        if (\App\Services\NotificationPrefs::enabled($store->id, 'sms_receive', 'new_lead')) {
                            _sendSMS($store->phone, $vendorMsg);
                        }
                        if (\App\Services\NotificationPrefs::enabled($store->id, 'push_receive', 'new_lead')) {
                            _inAppNotification($adminTitle, $vendorMsg, null, $store->id, $vendorUrl, 'vendor');
                        }
                        if (!_autoAcceptLeadForStore($store->id, $serviceReq->id)) {
                            \App\Services\WhatsAppService::sendLeadNotification($store->id, $itemDet->name ?? null, $user->f_name ?? null);
                        }
                        _remindLeadWalletRecharge($store, $itemDet->category_id ?? null);
                    }
                }
            }

            $userRow = DB::table('service_requests')
                ->join('users', 'users.id', '=', 'service_requests.user_id')
                ->where('service_requests.id', $serviceReq->id)
                ->select('users.cm_firebase_token', 'service_requests.user_id')
                ->first();

            if ($userRow) {
                $fcmData = [
                    'title'       => 'Service Request',
                    'description' => 'Service Requested Successfully.',
                    'order_id'    => $serviceReq->id,
                    'image'       => '',
                    'type'        => 'block',
                ];
                Helpers::send_push_notif_to_device($userRow->cm_firebase_token, $fcmData);
                DB::table('user_notifications')->insert([
                    'data'       => json_encode($fcmData),
                    'user_id'    => $userRow->user_id,
                    'type'       => 'service',
                    'type_id'    => $serviceReq->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'success'    => true,
                'message'    => 'Service booking created successfully.',
                'booking_id' => $serviceReq->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success'     => false,
                'message'     => $e->getMessage(),
                'debug_error' => get_class($e) . ' in ' . basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }
}
