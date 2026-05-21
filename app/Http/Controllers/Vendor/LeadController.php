<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Item;
use App\Models\Store;
use App\Models\Lead;
use App\Models\Review;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\CentralLogics\Helpers;
use App\CentralLogics\ProductLogic;
use App\Models\AcceptedServiceRequest;
use App\Models\CommonServiceIssue;
use App\Models\ItemCampaign;
use App\Models\LeadOrder;
use App\Models\LeadOrderDetail;
use App\Models\ServiceRequest;
use App\Models\StoreCustomer;
use App\Models\StoreTask;
use App\Models\Tag;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\DB;
use App\Scopes\StoreScope;
use App\Models\Translation;
use App\Models\User;
use App\Models\VendorEmpJob;
use App\Models\LeadCharge;
use App\Models\StoreWallet;
use App\Models\AccountTransaction;
use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $v_id = Helpers::get_store_id();

        $leads = Lead::select('leads.*', 'items.name as service_name')
            ->join('items', 'leads.service', '=', 'items.id')
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->where('stores.id', $v_id);

        $filter_status = '';
        $from_date = NULL;
        $to_date = NULL;
        if (request()->has('from_date') && request()->get('from_date') !== NULL) {
            $from_date = request()->get('from_date');
            $leads = $leads->where('leads.lead_date', '>=', $from_date . ' 00:00:00');
        }
        if (request()->has('to_date') && request()->get('to_date') !== NULL) {
            $to_date = request()->get('to_date');
            $leads = $leads->where('leads.lead_date', '<=', $to_date . ' 23:59:59');
        }
        if (request()->has('status') && request()->get('status') !== 'all') {
            $filter_status = request()->get('status');
            $leads = $leads->where('leads.status', request()->get('status'));
        }
        $leads = $leads->paginate(config('default_pagination'));;
        // echo $leads->toSql();

        $onhold =  Lead::select('leads.*', 'items.name as service_name')
            ->join('items', 'leads.service', '=', 'items.id')
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->where('stores.id', $v_id)
            ->where('leads.status', 'Hold')->get();

        $new =  Lead::select('leads.*', 'items.name as service_name')
            ->join('items', 'leads.service', '=', 'items.id')
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->where('stores.id', $v_id)
            ->where('leads.status', 'New')->get();

        $followups =  Lead::select('leads.*', 'items.name as service_name')
            ->join('items', 'leads.service', '=', 'items.id')
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->where('stores.id', $v_id)
            ->where('leads.status', 'Follow Ups')->get();

        $notinterested =  Lead::select('leads.*', 'items.name as service_name')
            ->join('items', 'leads.service', '=', 'items.id')
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->where('stores.id', $v_id)
            ->where('leads.status', 'Not Interested')->get();

        $completed = Lead::select('leads.*', 'items.name as service_name')
            ->join('items', 'leads.service', '=', 'items.id')
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->where('stores.id', $v_id)
            ->where('leads.status', 'Completed')->get();

        $closed =  Lead::select('leads.*', 'items.name as service_name')
            ->join('items', 'leads.service', '=', 'items.id')
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->where('stores.id', $v_id)
            ->where('leads.status', 'Closed')->get();

        return view('vendor-views.lead.index', compact('leads', 'filter_status', 'from_date', 'to_date', 'closed', 'completed', 'notinterested', 'followups', 'new', 'onhold'));
    }
    public function job_otp_verify(Request $request)
    {
        $acc_id = $request->acc_id;
        $phone  = $request->phone;
        $otp    = implode('', $request->otp ?? []);

        $is_otp = _verify_otp($phone, $otp); // handles expiry, brute-force blocking, cleanup

        if ($is_otp) {
            $accDet = AcceptedServiceRequest::find($acc_id);

            if ($accDet->assigned_type == 'vendor') {
                $empId  = Helpers::get_store_id();
            } else {
                $empId  = Helpers::get_loggedin_user()->id;
            }
            if ($request->action == 'end') {
                DB::table('accepted_service_requests')->where(['vendor_id' => $empId])->update(['completed_at' => NOW()]);
                DB::table('vendor_emp_jobs')->where('acc_id', $acc_id)->whereIn('emp_id', [$empId, 0])->update(['ended_at' => NOW()]);
            } else {
                DB::table('vendor_emp_jobs')->where('acc_id', $acc_id)->whereIn('emp_id', [$empId, 0])->update(['started_at' => NOW(), 'assign_type' => $accDet->assigned_type]);
            }

            DB::table('lead_statuses')->insert([
                'service_request_id' => $accDet->service_request_id,
                'status' => 'Job ' . $request->action . 'ed',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            Toastr::success('Job ' . $request->action . 'ed successfully');
        } else {
            Toastr::error('Invalid OTP');
        }
        return response()->json(['status' => true, 'message' => "Status changed successfully"]);

        return back();
    }
    public function job_otp_verify2(Request $request)
    {
        // change status
        $acc = $request->acc_id;
        $stts_id = $request->status;
        $serviceReq = AcceptedServiceRequest::where('id', $acc)->first();
        $ser3 = ServiceRequest::where('id', $serviceReq->service_request_id)->first();
        // OTP verification required for completion
        if ($stts_id == 12 && $request->has('otp')) {
            $otp = implode('', (array) $request->otp);
            $userPhone = User::find($ser3->user_id)?->phone;
            if (!$userPhone) {
                return response()->json(['status' => false, 'message' => 'User phone number not valid.']);
            } else if (!_verify_otp($userPhone, $otp)) { 
                return response()->json(['status' => false, 'message' => 'Invalid or expired OTP.']);
            }
        }
        $service_id = $serviceReq->service_request_id;
        if ($stts_id == 14) { // for cancel 
            if (auth('vendor')->check()) {
                $cancelled_by = 'Vendor';
            } else {
                $cancelled_by = 'Staff';
            }

            $serviceReq->current_status = 'Cancelled';
            $serviceReq->cancelled_by = $cancelled_by;
            if ($request->hasFile('file')) {
                $extension = $request->file('file')->getClientOriginalExtension();
                $serviceReq->file = Helpers::upload('store/orders/', $extension, $request->file('file'));
            }
            $serviceReq->update();

            $empJob = VendorEmpJob::where('service_id', $serviceReq->service_request_id)->first();
            $empJob->ended_at = NOW();
            $empJob->update();

            $serviceReq->accepted_by_staff = 1;
        } else if ($stts_id == 12) { // completed
            if ($request->hasFile('file')) {
                $extension = $request->file('file')->getClientOriginalExtension();
                $serviceReq->file = Helpers::upload('store/orders/', $extension, $request->file('file'));
            }
            $serviceReq->current_status = 'Completed';
            $serviceReq->completed_at = NOW();
            $serviceReq->update();

            $serviceReq->coupon_id = Helpers::alotServiceCoupon($serviceReq->service_request_id);
            $serviceReq->update();

            // Apply completion charges
            try {
                $store = Store::withoutGlobalScopes()->find($serviceReq->vendor_id);
                $zoneId = $store->zone_id;
                $vendorId = $store->vendor_id;
                $svcReq = ServiceRequest::where('service_requests.id', $serviceReq->service_request_id)
                    ->join('items', 'items.id', '=', 'service_requests.item_id')
                    ->select('items.category_id', 'service_requests.item_id')
                    ->first();

                $leadChargeInfo = LeadCharge::where('category_id', $svcReq->category_id)
                    ->where('zone_id', $zoneId)
                    ->where('item_id', $svcReq->item_id)->first()
                    ?? LeadCharge::where('category_id', $svcReq->category_id)
                    ->where('zone_id', $zoneId)
                    ->whereNull('item_id')->first();

                $completionCharges = $leadChargeInfo ? $leadChargeInfo->completion_charge : 0;

                if ($completionCharges > 0 && !\App\CentralLogics\Helpers::store_has_active_lead_subscription((int)$serviceReq->vendor_id)) {
                    $wallet = StoreWallet::where('vendor_id', $vendorId)->first();
                    $wallet->decrement('total_earning', $completionCharges);
                    $wallet->increment('total_withdrawn', $completionCharges);
                    $wallet->refresh();

                    $account_transaction = new AccountTransaction();
                    $account_transaction->current_balance = $wallet->total_earning - $wallet->total_withdrawn;
                    $account_transaction->from_type = 'store';
                    $account_transaction->amount = $completionCharges;
                    $account_transaction->from_id = $vendorId;
                    $account_transaction->method = 'wallet';
                    $account_transaction->action = 'debit';
                    $account_transaction->reason = 'Lead Completion Charges';
                    $account_transaction->service_request_id = $service_id;
                    $account_transaction->created_by = 'store';
                    $account_transaction->save();
                }
            } catch (\Throwable $th) {
                info('Completion charges error: ' . $th->getMessage());
            }

            // send sms here
            // get user mobile
            $userPhone = User::find($serviceReq->user_id);
            if ($userPhone) {
                _send_confirmation_sms('mobile_verification', $userPhone->phone);
            }

            $empJob = VendorEmpJob::where('service_id', $serviceReq->service_request_id)->first();
            $empJob->ended_at = NOW();
            $empJob->update();
        }

        $stats = DB::table('service_statuses')->where('id', $stts_id)->first();
        DB::table('lead_statuses')->insert([
            'service_request_id' => $service_id,
            'status' => $stats->status,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $request_id = $service_id;
        $service_requests = ServiceRequest::where('id', $serviceReq->service_request_id)->first();
        $item = DB::table('items')->where('id', $service_requests->item_id)->first();
        $user = DB::table('service_requests')->join('users', 'users.id', 'service_requests.user_id')->where('service_requests.id', $request_id)->select('users.*')->first();
        if ($user) {
            $fcm_token = $user->cm_firebase_token;
            $data = [
                'title' => "Service Status Update",
                'description' => "Status of " . $item->name . " is changed to " . $stats->status . ".",
                'order_id' => $request_id,
                'image' => '',
                'type' => 'block'
            ];
            Helpers::send_push_notif_to_device($fcm_token, $data);
            DB::table('user_notifications')->insert([
                'data' => json_encode($data),
                'user_id' => $user->id,
                'type' => 'service',
                'type_id' => $request_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // status change
        $jobUpdate = DB::table('vendor_emp_jobs')->where('service_id', $service_id)->update(['status' => $stts_id]);

        if ($jobUpdate) {
            return response()->json(['status' => true, 'message' => "Status changed successfully"]);
        } else {
            return response()->json(['status' => false, 'message' => "Some error occured"]);
        }
        Toastr::success('status updated successfully');
        return back();
    }
    public function start_job(Request $request)
    {
        $phone = $request->customer_phone;

        $check = _check_otp_send_allowed($phone);
        if (!$check['allowed']) {
            // Still send the old OTP if one exists; just don't send a new SMS
            // Return the throttle message to the frontend
            return response()->json(['status' => false, 'message' => $check['message']], 429);
        }

        $otp = rand(1000, 9999);
        _store_otp($phone, $otp);
        $user = User::where('phone', $request->customer_phone)->first();
        if ($user) {
            $data = [
                'order_id' => $request->service_id,
                'image' => '',
                'type' => 'block'
            ];
            if ($request->has('job_action') && $request->job_action == 'start') {
                $data['title'] =  "Technician Arrived - Share OTP";
                $data['description'] = "Your service request has started \n Start OTP: " . $otp . " Please share this only with the technician.";
            } else {
                $data['title'] =  "Confirm Job Completion";
                $data['description'] = "Your service has been completed.\nPlease confirm the job by sharing this OTP: " . $otp . "\nThank you for choosing us!";
            }


            Helpers::send_push_notif_to_device($user->cm_firebase_token, $data, '');
            DB::table('user_notifications')->insert([
                'data' => json_encode($data),
                'user_id' => $user->id,
                'type' => 'service',
                'type_id' => $request->service_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        _send_confirmation_sms('job_msg', $phone, $otp);
    }
    public function change_job_status(Request $request)
    {
        $service_id = $request->service_id;
        $serviceDet = DB::table('service_requests')->where('id', $service_id)->first();
        $user       = User::where('id', $serviceDet->user_id)->first();
        $phone      = $user->phone;

        $check = _check_otp_send_allowed($phone);
        if (!$check['allowed']) {
            return 0;
        }

        $otp = rand(1000, 9999);

        $data = [
            'title'       => 'Confirm Job Completion',
            'description' => "Your service has been completed.\nPlease confirm the job by sharing this OTP: {$otp}\nThank you for choosing us!",
            'order_id'    => $service_id,
            'image'       => '',
            'type'        => 'block',
        ];
        Helpers::send_push_notif_to_device($user->cm_firebase_token, $data, '');
        DB::table('user_notifications')->insert([
            'data'       => json_encode($data),
            'user_id'    => $user->id,
            'type'       => 'service',
            'type_id'    => $service_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        _store_otp($phone, $otp);
        _send_confirmation_sms('job_msg', $phone, $otp);
        return 1;
    }
    public function manage(Request $request, $id)
    {
        $lead = Lead::find($id);
        $serviceName = Item::find($lead->service)->name;
        $previous_leads = Lead::where('lead_date', '<=', $lead->lead_date)->where('vendor_id', Helpers::get_store_id())->where('client_email', $lead->client_email)->get();
        return view('vendor-views.lead.manage', compact('lead', 'previous_leads', 'serviceName'));
    }

    public function status_change(Request $request)
    {

        $id = $request->post('lead_id');
        $status = $request->post('status');

        $query =  Lead::where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        // echo $query;
        return back();
    }
    public function delete(Request $request, $id)
    {

        $query =  Lead::find($id)
            ->delete();
        return back();
    }

    public function add()
    {
        $zone_id = Helpers::get_store_data()->zone_id;
        $reported_issue_list  = CommonServiceIssue::all();
        $customers  = StoreCustomer::where('store_id', Helpers::get_store_id())->get();
        $services = DB::table('items')->join('categories', 'categories.id', 'items.category_id')->where('categories.status', 1)->where('items.status', '1')->whereRaw("FIND_IN_SET(?, items.store_ids)", [Helpers::get_store_id()])->select('items.*')
            ->distinct()->get();
        // prx($services); 
        return view('vendor-views.lead.add', compact('services', 'customers', 'reported_issue_list'));
    }
    public function save_comment(Request $request)
    {

        DB::table('lead_statuses')->insert([
            'service_request_id' => $request->lead_id,
            'status' => $request->comment,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        Toastr::success('Comment added successfully!');

        return back();
    }
    public function save_info(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $store_data = Helpers::get_store_data();

        $serviceReq = new ServiceRequest;
        $serviceReq->user_id = $request->client_name;
        $serviceReq->item_id = $request->service;
        $serviceReq->module_id = 6;
        $serviceReq->zone_id = $store_data->zone_id;
        $serviceReq->latitude = $store_data->latitude;
        $serviceReq->longitude = $store_data->longitude;
        $serviceReq->remarks = $request->remarks;
        $serviceReq->requirements = $request->requirements;
        $serviceReq->status = 'new';
        $serviceReq->template = $request->template;
        $serviceReq->image = $request->has('image') ? Helpers::upload('lead/', 'png', $request->file('image')) :  '';
        $serviceReq->created_at = date('Y-m-d H:i:s');
        $serviceReq->accepted = 1;
        $serviceReq->accepted_by = $store_id;
        $serviceReq->sent_to = $store_id;
        $serviceReq->save();

        $acceptance = new AcceptedServiceRequest();
        $acceptance->quoted_price = $request->final_price;
        $acceptance->vendor_id = $store_id;
        $acceptance->current_status = 'Confirmation Request Sent';
        $acceptance->service_request_id = $serviceReq->id;
        $acceptance->created_at = date('Y-m-d H:i:s');
        $acceptance->save();

        $library = app(LibraryController::class);
        $quoteApp = app(QuoteController::class);
        if ($request->has('job_card') && $request->job_card) {
            $jobcard =  $library->job_card($request, 'save', null, $serviceReq->id);
            if (!$jobcard['success']) {
                Toastr::error('Failed to generate job card');
            }
        }
        if ($request->has('receivable_receipt') && $request->receivable_receipt) {
            $jobcard =  $library->recievable_reciept($request, 'save', null, $serviceReq->id);
            if (!$jobcard['success']) {
                Toastr::error('Failed to generate job card');
            }
        }

        $data = [
            'store_name' => $store_data->name,
            'est_price' => $request->est_price,
            'final_price' => $request->final_price,
            'item_details' =>  DB::table('items')->where('id', $request->service)->first(),
            'user_details' => User::find($request->client_name),
        ];

        // _sendQuotationMail('Recieved New Quotaion',  $request->template, $data, User::find($request->client_name)->email);

        $item = DB::table('items')->where('id', $request->service)->first();
        $user = User::find($request->client_name);
        if ($user) {
            $fcm_token = $user->cm_firebase_token;
            $data = [
                'title' => "New Quotaion",
                'description' => "Recieved New Quotaion for " . $item->name . ".",
                'order_id' => $serviceReq->id,
                'image' => '',
                'type' => 'block'
            ];
            Helpers::send_push_notif_to_device($fcm_token, $data);
            DB::table('user_notifications')->insert([
                'data' => json_encode($data),
                'user_id' => $user->id,
                'type' => 'service',
                'type_id' => $serviceReq->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        DB::table('lead_statuses')->insert([
            'service_request_id' => $serviceReq->id,
            'status' => 'Lead Created',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        Toastr::success('Lead Created and Sent successfully!');
        return redirect()->route('vendor.service.leads_list');
    }
    public function convert_to_task(Request $request, $lead_id)
    {
        $task = StoreTask::where('lead_id', $lead_id)->exists();
        if ($task) {
            Toastr::error('Task for this lead already exists');
            return back();
        }
        $lead = ServiceRequest::where('service_requests.id', $lead_id)->join('accepted_service_requests as acc', 'acc.service_request_id', 'service_requests.id')->join('items', 'items.id', 'service_requests.item_id')->select('acc.*', 'items.image as item_image', 'items.name as item_name', 'service_requests.id as service_id')->first();
        // prx($lead);
        $employee_id = null;
        if ($lead->assigned_type == 'vendor') {
            $employee_id = 0;
        } else {
            $employee_id = $lead->assigned_to;
        }

        $store_id = Helpers::get_store_id();
        $user_id = null;
        $user = User::find($lead->user_id);
        if ($user) {
            $store_user = StoreCustomer::where('phone', $user->phone)->first();
            if ($store_user) {
                $user_id = $store_user->id;
            }
        }

        // prx($request->progress);
        $task = new StoreTask();
        $task->store_id =  $store_id;
        $task->task_amount =  $lead->quoted_price;
        $task->user_id =  $user_id; // store customer
        $task->user_type = 'customer';
        $task->progress = 0;
        $task->employee_id = $employee_id;
        $task->task_type = 'common';
        $task->title =  $lead->item_name;
        $task->description = $lead->remarks ?? null;
        $task->lead_id = $lead->service_id;
        $task->time_count = 1;
        $task->time_unit = 'week';

        if ($lead->item_image) {
            $sourcePath = 'product/' . $lead->item_image;
            $destinationPath = 'task/' . $lead->item_image;

            Storage::disk('public')->copy($sourcePath, $destinationPath);
            $task->file = $lead->item_image;
        } else {
            $task->file = null;
        }

        $task->status = 'new';
        $task->save();

        $lead = ServiceRequest::find($lead_id);
        $lead->task_id = $task->id;
        $lead->save();

        Toastr::success('Converted Successfully');
        if ($task->parent_id) {
            return redirect()->route('vendor.task.subtask.detail', [$task->id]);
        } else {
            return redirect()->route('vendor.task.detail', [$task->id]);
        }
    }
    public function convert_to_order(Request $request, $id)
    {
        $lead = ServiceRequest::with('item')->where('id', $id)->first();
        // prx($lead);
        return view('vendor-views.lead.convert_to_order', compact('lead'));
    }
    public function convert_to_order_store(Request $request, $id)
    {
        prx($request->all());
        $additional_items = [];
        $total_amount = 0;
        $address  = '';

        $lead = ServiceRequest::find($id);
        $store_id = Helpers::get_store_id();

        $order = new LeadOrder();
        $order->tax_status = 'included';
        $order->delivery_charge = 0;
        $order->order_amount = $total_amount; //
        $order->invoice_id = _generateOrderInvoiceId($store_id);
        $order->user_id = $lead->user_id;
        $order->payment_status = 'unpaid';
        $order->order_status = 'confirmed';
        $order->payment_method = 'cash_on_delivery';
        $order->order_type = 'delivery'; //
        $order->store_id = $store_id;
        $order->delivery_charge = 0;
        $order->additional_items = $additional_items; // 
        $order->delivery_address = json_encode($address); //
        $order->otp = rand(1000, 9999);
        $order->zone_id = $lead->zone_id ? $lead->zone_id[0] : 21;
        $order->module_id = 6;
        $order->confirmed = now();
        $order->pending = now();
        $order->created_at = now();
        $order->updated_at = now();
        $order->save();

        // ORDER DETAILS 

        $product = Item::withoutGlobalScopes()->find($lead->item_id);
        $product = Helpers::product_data_formatting($product, false, false, app()->getLocale());
        $order_details = [
            'item_id' => $lead->item_id,
            'item_details' => json_encode($product),
            'quantity' => 1,
            'price' => 0, // 
            'taxable_amount' => 0, //
            'tax_amount' => 0, //
            'discount_on_item' => 0, //
            'discount_type' => 'amount',
            'platform_fee' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ];
        LeadOrderDetail::insert($order_details);
    }
    public function preview(Request $request)
    {
        $store_data = Helpers::get_store_data();
        $data = [
            'store_name' => $store_data->name,
            'est_price' => $request->est_price,
            'final_price' => $request->final_price,
            'item_details' =>  DB::table('items')->where('id', $request->service)->first(),
            'user_details' => User::find($request->client_name),
        ];
        return view('email-templates.quotation_' . $request->template, compact('data'));
    }
    public function save_info_old(Request $request)
    {
        $id = $request->post('lead_id');

        if ($id == '') { // for new lead

            $validator = Validator::make($request->all(), [
                'client_name' => 'required|max:100',
                'client_email' => 'required|email',
                'client_mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20',
            ], [
                'client_name.required' => 'Client Name is required',
                'client_email.required' => 'Client Email is required',
                'client_mobile.required' => 'Client Mobile is required',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
            $lead = new Lead;
            $lead->client_name = $request->post('client_name');
            $lead->client_email = $request->post('client_email');
            $lead->client_mobile = $request->post('client_mobile');
            $lead->service = $request->post('service');
        } else {
            $lead = Lead::find($id);
        }

        $userAgent = $request->header('User-Agent');

        if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false || strpos($userAgent, 'iPhone') !== false) {
            $lead->channel = 'MOBILE';
        } else {
            $lead->channel = 'WEB';
        }


        $lead->follow_up_date = $request->post('follow-up-date');
        $lead->status = $request->post('status');
        $lead->price = $request->post('price');
        $lead->location = $request->post('location');
        $lead->requirements = $request->post('requirements');
        $lead->remarks = $request->post('remarks');
        $lead->save();

        $request->session()->flash('msg', 'Lead Information updated successfully');
        return back();
    }

    public function lead_approval(Request $request)
    {
        $id = $request->post('lead_id');
        $lead = Lead::find($id);
        $lead->approval = $request->post('approval');
        $lead->save();
        return response()->json(['msg' => ucfirst($request->post('approval')) . 'ed', 'status' => true]);
    }
}
