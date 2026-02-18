<?php

namespace App\Http\Controllers\Api\V1;


use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\InServiceQuotation;
use App\Models\GatePass;
use App\Models\AcceptedServiceRequest;
use App\Models\ServiceRequest;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\AccountTransaction;
use App\Models\BusinessSetting;
use App\Models\CustomerAddress;
use App\Models\DataSetting;
use App\Models\EmployeeRole;
use App\Models\GatePassItem;
use App\Models\LeadCharge;
use App\Models\LeadStatus;
use App\Models\ServiceInvoice;
use App\Models\ServiceQuoteItem;
use App\Models\StoreWallet;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ServiceRequestController extends Controller
{
    public function get_termsnconditions(Request $request)
    {

        $tnc = DB::table('vendor_terms_conditions')->where('type', 'for_customer')->where('vendor_id', $request->vendor_id)->first();
        if ($tnc) {
            return response()->json(['status' => true, 'message' => 'Retrieved Successfully',  'data' => $tnc]);
        } else {
            return response()->json(['status' => false, 'message' => 'Terms and Conditions for this vendor does not exist',  'data' => []]);
        }
    }
    public function get_privacy_policy(Request $request)
    {

        $privacy_policy = DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'privacy_policy')->first();

        if ($privacy_policy) {
            return response()->json(['status' => true, 'message' => 'Retrieved Successfully', 'content' => $privacy_policy->value]);
        } else {
            return response()->json(['status' => false, 'message' => 'Privacy Policy does not exists', 'content' => '']);
        }
    }

    public function add(Request $request)
    {

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        if (!$request->hasHeader('latitude') || !$request->hasHeader('longitude')) {
            $errors = [];
            array_push($errors, ['code' => 'coordinates', 'message' => 'Coordinates required']);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'item_id' => 'required|integer',
            // 'qty' => 'required|integer',
            // 'city' => 'required',
            'address_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = User::find($request->user_id);

        $address_exists = CustomerAddress::where('id', $request->address_id)->first();
        if (!$address_exists) {
            $errors = [];
            array_push($errors, ['code' => 'address', 'message' => "Address not found"]);
            return response()->json([
                'errors' => $errors
            ], 403);
        } else {
            $address = $address_exists->house . ', ' . $address_exists->floor . ', ' . $address_exists->road . ', ' . $address_exists->address;
            $city = '';
        }
        if (!$address_exists->pin_code) {
            $pin_code = getPincodeFromCoordinates((float)$request->header('latitude'), (float)$request->header('longitude'));
            DB::table('customer_addresses')->where('id', $request->address_id)->update(['pin_code' =>  $pin_code]);
        } else {
            $pin_code = $address_exists->pin_code;
        }
        if (!$user->pin_code) {
            $user->pin_code = $pin_code;
            $user->save();
        }

        $storeId = $request->storeId ?? false;
        // prx($storeId);
        $storesChunk = Helpers::get_store_range($request->item_id, $request->header('zoneId'), $request->user_id, $storeId);

        $city = $request->city;
        $serviceReq = new ServiceRequest;
        $serviceReq->user_id = $request->user_id;
        $serviceReq->item_id = $request->item_id;
        $serviceReq->sent_to = implode(',', $storesChunk);
        $serviceReq->qty = 1;
        $serviceReq->requirements = $request->requirements;
        $serviceReq->module_id = 6;
        $serviceReq->zone_id = $request->header('zoneId');
        $serviceReq->latitude = (float)$request->header('latitude');
        $serviceReq->longitude = (float)$request->header('longitude');
        $serviceReq->pin_code = $pin_code;
        $serviceReq->status = 'new';
        $serviceReq->address = $address;
        $serviceReq->address_id = $request->address_id;
        $serviceReq->city =  $city;
        $serviceReq->gst =  $user->gst ?? null;
        $serviceReq->created_at = date('Y-m-d H:i:s');

        try {
            if ($serviceReq->save()) {
                // if (1) {
                //   event( new ServiceNotification('dsaf'));
                DB::table('lead_statuses')->insert([
                    'service_request_id' => $serviceReq->id,
                    'status' => 'User Requested Service',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                $url = route('admin.service.lead-list');
                $msg = "A new service enquiry has been submitted. Please review the details";
                $title =  'New Service Enquiry';
                _sendSMSToAdmin($msg, $title, $url);

                $itemDet = DB::table('items')->where('id', $request->item_id)->first();
                $userDet = User::find($request->user_id);

                if (count($storesChunk)) {
                    $msg = "Hello! , You have received a new ENQUIRY from " . (!empty($userDet->f_name) ? $userDet->f_name : "a customer") . " for " . (!empty($itemDet->name) ? $itemDet->name : "a service") . ". Please visit the My Chitti Vendor App. Thank you, My Chitti Team.";
                    foreach ($storesChunk as $store) {
                        $store2 = DB::table('stores')->where('id', $store)->first();
                        $url =  route('vendor.service.leads_list');
                        if ($store2) {
                            _sendSMS($store2->phone, $msg);
                            _inAppNotification($title, $msg, null, $store2->id, $url, 'vendor');
                        }
                    }
                }

                $request_id = $serviceReq->id;
                $user = DB::table('service_requests')->join('users',  'users.id', 'service_requests.user_id')->where('service_requests.id', $request_id)->select('users.cm_firebase_token', 'service_requests.user_id')->first();
                if ($user) {
                    $fcm_token = $user->cm_firebase_token;
                    $data = [
                        'title' => "Service Request",
                        'description' => "Service Requested Successfully.",
                        'order_id' => '',
                        'image' => '',
                        'type' => 'block'
                    ];
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'user_id' => $user->user_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                return response()->json(['status' => true, 'message' => 'Requested Successfully']);
            } else {
                return response()->json(['status' => false, 'message' => 'Some Error Occured']);
            }
        } catch (\Exception $e) {

            return response()->json(['status' => false, 'message' => $e->getMessage()]);
            // echo 'Error Message: ' . $e->getMessage();
        }
    }

    public function quotaion_approval(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'quote_id' => 'required',
            'action' => 'required', // approve or reject
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $gatepass = InServiceQuotation::find($request->quote_id);
        $acceptedReq = AcceptedServiceRequest::where('id', $gatepass->acc_id)->first();
        if (in_array($acceptedReq->current_status, ['Completed', 'Cancelled', 'Rejected'])) {
            return response()->json(['status' => false, 'message' => 'Service is ' . ucfirst($acceptedReq->current_status)]);
        }
        if ($request->action == 'approve') {
            $gatepass->approved = 1;
            $request->action = 'approv';
            $storestts = 'Approved';
        } else {
            $gatepass->approved = 2; // 2 for reject
            $storestts = 'Rejected';
        }

        if ($gatepass->update()) {


            DB::table('lead_statuses')->insert([
                'service_request_id' => $gatepass->service_id,
                'status' => 'Quotation ' . $storestts,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $title = 'Quotation ' . ucfirst($request->action) . 'ed';
            $msg = 'Customer has ' . ucfirst($request->action) . 'ed the quotaion';
            $assignment_id = $gatepass->acc_id;
            $to = $gatepass->emp_id;
            $url = route('vendor.service.quotations', [$gatepass->service_id]);

            _inAppNotification($title, $msg, $assignment_id, $to, $url, 'vendor_employee');
            _sendMailToStaff($title, $msg, $to, $url);

            return response()->json(['status' => true, 'message' => ucfirst($request->action) . 'ed Successfully']);
        } else {
            return response()->json(['status' => false, 'message' => 'Some error occured']);
        }
    }
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => ['required', function ($attr, $value, $fail) {
                if ($value !== 'all' && !ctype_digit((string)$value)) {
                    $fail('The service id must be a number or "all".');
                }
            }],
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $id = $request->service_id;

        if ($id === 'all') {

            $services = ServiceRequest::where('user_id', $request->user_id)->get();

            foreach ($services as $ser) {
                AcceptedServiceRequest::where('service_request_id', $ser->id)->delete();
                $ser->delete();
            }
        } else {

            $ser = ServiceRequest::where('user_id', $request->user_id)
                ->where('id', $id)
                ->first();

            if (!$ser) {
                return response()->json(['message' => 'Service request not found'], 404);
            }

            AcceptedServiceRequest::where('service_request_id', $ser->id)->delete();
            $ser->delete();
        }

        return response()->json(['status' => true, 'message' => 'Deleted Successfully']);
    }

    public function details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required',
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        //   $serviceRequest = _serviceRunning($request->user_id, true, 10, $request->service_id);
        $serviceRequest = ServiceRequest::with([
            'leadStatus:service_request_id,status,created_at',
            'accepted:id,service_request_id,quoted_price,current_status,service_request_id,coupon_id,vendor_id,id as acceptance_id,assigned_type,assigned_to,assigned_status,cancel_reason,cancelled_by',
            'accepted.store:id,name,phone,email,logo,address',
            'accepted.coupon:id,title,code,start_date,expire_date,min_purchase,discount,limit',

            'accepted.staff' => function (MorphTo $morphTo) {

                $morphTo->constrain([

                    \App\Models\VendorEmployee::class => function ($q) {
                        $q->select(
                            'id',
                            'f_name',
                            'l_name',
                            'phone',
                            'email',
                            'image',
                            'employee_role_id'
                        );
                    },

                    \App\Models\Store::class => function ($q) {
                        $q->select(
                            'id',
                            'phone',
                            'email',
                            'logo as image',
                            'name',
                            'name as f_name',
                        )
                            ->selectRaw('NULL as l_name')
                            ->selectRaw("'Vendor' as role");
                    },


                ]);
            },
            'item:id,name,image,images',

            'gatePass:id,service_id,approved,returned,return_approved,created_at,updated_at',
            'gatePass.items:id,gatepass_id,title,image,description',

            'quotation:id,service_id,approved,created_at,updated_at',
            'quotation.items:id,quote_id,name,price,qty,tax,total',

        ])
            ->select('id', 'item_id', 'user_id', 'status', 'created_at')
            ->where('id', $request->service_id)
            ->first();
        $host = request()->getHost();
        $filesPath =  asset('storage/');
        if (!$serviceRequest) {
            return response()->json(['status' => false, 'message' => 'Service not found'], 404);
        }
        //last status 
        // $lastStatus  = LeadStatus::where('service_request_id', $serviceRequest->id)->latest()->first();
        // $serviceRequest->recent_status = $lastStatus->status;
        // $serviceRequest->recent_status_timestamp = $lastStatus->created_at;

        if ($serviceRequest->item?->image) {
            $serviceRequest->item->image = $filesPath . '/product/' .   $serviceRequest->item->image;
        }
        // add role
        if ($serviceRequest->accepted) {
            if ($serviceRequest->accepted?->assigned_type == 'vendor') {
                $serviceRequest->accepted->staff->role = 'Vendor';
            } elseif ($serviceRequest->accepted?->staff) {
                if ($serviceRequest->accepted?->staff?->employee_role_id) {
                    $role = EmployeeRole::where('id', $serviceRequest->accepted->staff->employee_role_id)->first();
                    $serviceRequest->accepted->staff->role = $role ? $role->name : null;
                }
            }
            if ($serviceRequest->accepted?->staff?->image) {
                $serviceRequest->accepted->staff->image = $filesPath . '/vendor/' .  $serviceRequest->accepted->staff->image;
            }


            if (_reviewStatus($serviceRequest->accepted->id)['status'] === 'exists') {
                $serviceRequest->accepted->review_status = 'Completed';
            } else {
                $serviceRequest->accepted->review_status = null;
            }
        }

        if ($serviceRequest->gatePass) {
            $serviceRequest->gatePass->images_path = $filesPath . '/gatepass/';
        }

        // accepted 
        if (!$serviceRequest->accepted) {
            $fakeAccepted = new AcceptedServiceRequest();
            $fakeAccepted->id = 0;
            $fakeAccepted->current_status  = 'Enquiry Sent';
            $fakeAccepted->assigned_status = 'Not Assigned';
            $fakeAccepted->assigned_type   = 'N/A';
            $fakeAccepted->staff_name      = '';
            $fakeAccepted->staff_role      = '';
            $fakeAccepted->staff_image     = '';
            $fakeAccepted->staff_contact   = '';
            $fakeAccepted->review_status   = null;

            $serviceRequest->setRelation('accepted', $fakeAccepted);
        }


        // invoice 
        $invoice = ServiceInvoice::where('service_id', $request->service_id)->first();
        if ($invoice) {
            if ($host == 'staging.mychitti.net') {
                $serviceRequest->invoice_url = $invoice ? asset('storage/app/public/invoice/' . $invoice->pdf) : null;
            } else {
                $serviceRequest->invoice_url = $invoice ? asset('storage/invoice/' . $invoice->pdf) : null;
            }
            $serviceRequest->invoice_created_at = $invoice->created_at;
        }
        if ($serviceRequest->accepted->coupon) {
            $serviceRequest->accepted->coupon->is_scratched = _isCouponScratched((string)$serviceRequest->user_id, $serviceRequest->accepted->coupon->id);
        }
        if ($serviceRequest->accepted->store?->logo) {
            $serviceRequest->accepted->store->logo = $filesPath . '/store/' . $serviceRequest->accepted->store->logo;
        }

        return response()->json(['status' => true, 'data' => $serviceRequest]);
    }

    public function quotation_terms_and_conditions(Request $request)
    {
        $tnc = BusinessSetting::where('key', 'quotation_terms_and_conditions')->value('value');
        return response()->json([$tnc], 200);
    }
    public function gatepass_terms_and_conditions(Request $request)
    {
        $tnc = BusinessSetting::where('key', 'gatepass_terms_and_conditions')->value('value');
        return response()->json([$tnc], 200);
    }
    public function gatepass_approval(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gatepass_id' => 'required',
            'action' => 'required', // approve or reject
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $gatepass = GatePass::find($request->gatepass_id);
        if ($request->action == 'approve') {
            $gatepass->approved = 1;
            $request->action = 'approv';
            $storestts = 'Approved';
        } else {
            $gatepass->approved = 2; // 2 for reject
            $storestts = 'Rejected';
        }

        if ($gatepass->update()) {

            DB::table('lead_statuses')->insert([
                'service_request_id' => $gatepass->service_id,
                'status' => 'Gatepass ' . $storestts,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $title = 'Gatepass ' . ucfirst($request->action) . 'ed';
            $msg = 'Customer has ' . ucfirst($request->action) . 'ed the gatepass';
            $assignment_id = $gatepass->accepted_service_id;
            $to = $gatepass->emp_id;
            $url = route('vendor.service.gatepass-details', [$gatepass->service_id]);

            _inAppNotification($title, $msg, $assignment_id, $to, $url, 'vendor_employee');
            _sendMailToStaff($title, $msg, $to, $url);
            return response()->json(['status' => true, 'message' => ucfirst($request->action) . 'ed Successfully']);
        } else {
            return response()->json(['status' => false, 'message' => 'Some error occured']);
        }
    }
    public function confirm_return(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gatepass_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $gatepass = GatePass::find($request->gatepass_id);

        $gatepass->return_approved = 1;

        if ($gatepass->update()) {

            DB::table('lead_statuses')->insert([
                'service_request_id' => $request->service_id,
                'status' => 'Return Approved',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return response()->json(['status' => true, 'message' => 'Confirmed Successfully']);
        } else {
            return response()->json(['status' => false, 'message' => 'Some error occured']);
        }
    }


    public function quotations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'service_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $allQuotations = DB::table('in_service_quotations')->where('service_id', $request->service_id)->get()->toArray();

        // print_r($allQuotations);   

        foreach ($allQuotations as $key => $gp) {
            $allQuotationItems = ServiceQuoteItem::where('quote_id', $gp->id)->get();
            $allQuotations[$key]->items =   $allQuotationItems;
        }
        return response()->json(['status' => true, 'data' => $allQuotations]);
    }

    public function gatepasses(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'service_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $allGatepasses = DB::table('gate_passes')->where('service_id', $request->service_id)->get()->toArray();

        foreach ($allGatepasses as $key => $gp) {

            $gatepassItems = GatePassItem::where('gatepass_id', $gp->id)->get();
            $allGatepasses[$key]->gatepassItems  = $gatepassItems;

            foreach ($gatepassItems as $key2 => $item) {
                $imgArr = [];
                if ($item['image']) {

                    if (is_array(json_decode($item['image']))) {
                        foreach (json_decode($item['image']) as  $value) {
                            $imgArr[] = asset('storage/app/public/gatepass') . '/' . $value;
                        }
                    } else {
                        $imgArr[] = asset('storage/app/public/gatepass') . '/' .  $item['image'];
                    }
                }
                $allGatepasses[$key]->gatepassItems[$key2]['image'] = json_encode($imgArr);
            }
        }

        return response()->json(['status' => true, 'data' => $allGatepasses]);
    }


    public function get_confirmations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $uid = $request->post('user_id');
        $confirmationReq = _serviceRunning($uid, true);

        return response()->json(['status' => true, 'data' => $confirmationReq]);
    }
    public function timeline(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $statuses = LeadStatus::where('service_request_id', $request->id)->get();
        return response()->json(['status' => true, 'data' => $statuses], 200);
    }
    public function service_history(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $uid = $request->post('user_id');
        $confirmationReq = _serviceHistory($uid);
        return response()->json(['status' => true, 'data' => $confirmationReq]);
    }

    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'acceptance_id' => 'required',
            'service_id' => 'required',
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $aexist =  AcceptedServiceRequest::where('service_request_id', $request->service_id)->where('tieup', 1)->exists();

        if ($aexist) {
            return response()->json(['status' => false, 'message' => 'Already Confirmed']);
        } else {
            $acceptedReq = AcceptedServiceRequest::where('id', $request->acceptance_id)->first();
            $applyCharges = false;
            $confirmationCharges = 0;
            try {
                $zoneId = Store::withoutGlobalScopes()->where('id', $acceptedReq->vendor_id)->value('zone_id');
                $catId = ServiceRequest::where('service_requests.id', $request->service_id)
                    ->join('items', 'items.id', '=', 'service_requests.item_id')
                    ->value('items.category_id');

                $leadChargeInfo =  LeadCharge::where('category_id', $catId)->where('zone_id',  $zoneId)->first();
                $confirmationCharges = $leadChargeInfo->confirmation_charge;

                // apply charges 
                $applyCharges = true;
            } catch (\Throwable $th) {
                //throw $th;
            }

            if ($acceptedReq->current_status == null) {
                return response()->json(['status' => false, 'message' => 'No quotation available from store yet']);
            }
            $acceptedReq->current_status = 'Confirmed';
            $acceptedReq->confirmed_at = date('Y-m-d H:i:s');

            $upd = AcceptedServiceRequest::where('service_request_id', $request->service_id)->update(['tieup' => 1]);

            if ($acceptedReq->update()) {

                // apply charges ============================
                if ($applyCharges) {
                    try {
                        //code...
                        $vendor_id = Store::where('id', $acceptedReq->vendor_id)->value('vendor_id');

                        $wallet = StoreWallet::where('vendor_id', $vendor_id)->first();
                        $wallet->decrement('total_earning', $confirmationCharges);
                        $wallet->increment('total_withdrawn', $confirmationCharges);
                        $wallet->save();

                        //insert into transactions 
                        $account_transaction = new AccountTransaction();
                        $account_transaction->current_balance = $wallet->sum('total_earning') - $wallet->sum('total_withdrawn');
                        $account_transaction->from_type = 'store';
                        $account_transaction->amount = $confirmationCharges;
                        $account_transaction->from_id = $vendor_id;
                        $account_transaction->method = 'wallet';
                        $account_transaction->action = 'debit';
                        $account_transaction->reason = 'Lead Confirmation Charges';
                        $account_transaction->created_by = 'store';
                        $account_transaction->save();
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }


                DB::table('lead_statuses')->insert([
                    'service_request_id' => $request->service_id,
                    'status' => 'User Confirmed',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                return response()->json(['status' => true, 'message' => 'Confirmed Successfully']);
            } else {
                return response()->json(['status' => false, 'message' => 'Some error occured']);
            }
        }
    }
    public function reject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'acceptance_id' => 'required',
            'service_id' => 'required',
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $aexist =  AcceptedServiceRequest::where('service_request_id', $request->service_id)->where('tieup', 1)->exists();

        if ($aexist) {
            return response()->json(['status' => false, 'message' => 'Already Confirmed']);
        } else {
            $acceptedReq = AcceptedServiceRequest::where('id', $request->acceptance_id)->first();
            $acceptedReq->current_status = 'Rejected';
            $acceptedReq->confirmed_at = date('Y-m-d H:i:s');

            $upd = AcceptedServiceRequest::where('service_request_id', $request->service_id)->update(['tieup' => 0]);

            if ($acceptedReq->update()) {

                DB::table('lead_statuses')->insert([
                    'service_request_id' => $request->service_id,
                    'status' => 'User Rejected Lead',
                    'created_at' => date('Y-m-d H:i:s')
                ]);



                return response()->json(['status' => true, 'message' => 'Rejected Successfully']);
            } else {
                return response()->json(['status' => false, 'message' => 'Some error occured']);
            }
        }
    }


    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'service_id' => 'required',
            'reason' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $acceptedReq = AcceptedServiceRequest::where('service_request_id', $request->service_id)->first();
        $acceptedReq->current_status = 'Cancelled';
        $acceptedReq->cancel_reason = $request->reason;
        $acceptedReq->cancelled_by = 'User';
        if ($acceptedReq->update()) {

            $cancelledRequest = $acceptedReq->replicate();
            $cancelledRequest->setTable('cancelled_service_requests');
            $cancelledRequest->save();
            $acceptedReq->delete();

            DB::table('lead_statuses')->insert([
                'service_request_id' => $request->service_id,
                'status' => 'Cancelled By User',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return response()->json(['status' => true, 'message' => 'Cancelled Successfully']);
        } else {
            return response()->json(['status' => false, 'message' => 'Some error occured']);
        }
    }
}
