<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Models\AcceptedServiceRequest;
use App\Models\AccountTransaction;
use App\Models\GatePass;
use App\Models\ServiceQuoteItem;
use App\Models\GatePassItem;
use App\Models\InServiceQuotation;
use App\Models\Salary;
use App\Models\VendorEmpJob;
use App\Models\Store;
use App\Models\ServiceInvoice;
use App\Models\InvoiceItem;
use App\Models\LeadCharge;
use App\Models\StoreWallet;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Exports\ServiceLeadsExport;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\Department;
use App\Models\JobCard;
use App\Models\ManualInvoice;
use App\Models\ManualTrial;
use App\Models\ReceivableReceipt;
use App\Models\Staff;
use App\Models\StorageUnit;
use App\Models\StoreCustomer;
use App\Models\StoreLedgerEntry;
use App\Models\StoreTask;
use App\Models\StoreVoucher;
use App\Models\TempStoreStatus;
use App\Models\User;
use App\Models\Zone;
use Carbon\Carbon;
use Doctrine\DBAL\Schema\View;
use Exception;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as FacadesView;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;

class ServiceController extends Controller
{
    public function applyCoupon(Request $request)
    {
        $request->validate(['coupon_code' => 'required|string']);

        $code = $request->coupon_code;

        $coupon = Coupon::withoutGlobalScopes()
            ->where('code', $code)
            ->where('status', 1)
            ->whereDate('expire_date', '>=', now()->toDateString())
            ->whereNull('claimed_at')
            ->first();

        if (!$coupon) {
            return response()->json(['status' => false, 'message' => 'Invalid, expired, or already claimed coupon.']);
        }

        if ($coupon->total_uses >= $coupon->limit) {
            return response()->json(['status' => false, 'message' => 'This coupon has already been used.']);
        }

        // Identify the customer this coupon belongs to
        $customerIds = json_decode($coupon->customer_id, true) ?? [];
        if (empty($customerIds)) {
            return response()->json(['status' => false, 'message' => 'No customer linked to this coupon.']);
        }
        $customerId = $customerIds[0];
        $customer   = User::find($customerId);

        if (!$customer) {
            return response()->json(['status' => false, 'message' => 'Customer not found.']);
        }

        // Check minimum completed services requirement
        if ($coupon->min_services > 0) {
            $completedCount = ServiceRequest::where('user_id', $customerId)
                ->whereNotNull('completed_at')
                ->count();

            if ($completedCount < $coupon->min_services) {
                return response()->json([
                    'status'  => false,
                    'message' => "Customer has only completed {$completedCount} service(s). "
                        . "{$coupon->min_services} required to redeem this coupon.",
                ]);
            }
        }

        // Generate 4-digit OTP and store on coupon (valid 10 minutes)
        $otp = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        $coupon->claim_otp            = $otp;
        $coupon->claim_otp_expires_at = now()->addMinutes(10);
        $coupon->save();

        // Send OTP to customer's phone
        try {
            _send_confirmation_sms('otp', $customer->phone, $otp);
        } catch (\Exception $e) {
            // SMS failure is non-blocking
        }

        return response()->json([
            'status'        => true,
            'otp_sent'      => true,
            'message'       => 'OTP sent to customer\'s phone. Ask the customer for the OTP to confirm.',
            'customer_name' => trim($customer->f_name . ' ' . $customer->l_name),
            'coupon_code'   => $coupon->code,
            'discount'      => $coupon->discount,
        ]);
    }

    public function verifyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'otp'         => 'required|string|size:4',
        ]);

        $storeId = Helpers::get_store_id();
        $store   = Store::find($storeId);

        $coupon = Coupon::withoutGlobalScopes()
            ->where('code', $request->coupon_code)
            ->where('status', 1)
            ->whereNull('claimed_at')
            ->first();

        if (!$coupon) {
            return response()->json(['status' => false, 'message' => 'Invalid or already claimed coupon.']);
        }

        if ($coupon->claim_otp !== $request->otp) {
            return response()->json(['status' => false, 'message' => 'Incorrect OTP.']);
        }

        if (!$coupon->claim_otp_expires_at || now()->isAfter($coupon->claim_otp_expires_at)) {
            return response()->json(['status' => false, 'message' => 'OTP has expired. Please restart the process.']);
        }

        // Mark coupon as claimed
        $coupon->claimed_at           = now();
        $coupon->claimed_by_store_id  = $storeId;
        $coupon->claim_otp            = null;
        $coupon->claim_otp_expires_at = null;
        $coupon->increment('total_uses');
        $coupon->save();

        // Credit vendor wallet
        $vendorId = $store->vendor_id;
        $amount   = $coupon->discount;

        $wallet = StoreWallet::firstOrCreate(
            ['vendor_id' => $vendorId],
            ['total_earning' => 0, 'total_withdrawn' => 0, 'pending_withdraw' => 0]
        );
        $wallet->increment('total_earning', $amount);

        // Record transaction
        AccountTransaction::create([]);

        return response()->json([
            'status'  => true,
            'message' => "Coupon claimed! ₹{$amount} has been credited to your wallet.",
            'amount'  => $amount,
        ]);
    }

    public function accept(Request $request, $serviceRequestId)
    {

        // check if already tiedup with any other vendor
        $acExists = DB::table('accepted_service_requests')->where('service_request_id', $serviceRequestId)->where('tieup', 1)->exists();
        if ($acExists) {
            if ($request->ajax()) return response()->json(['status' => false, 'message' => 'Already Tiedup with other vendor']);
            Toastr::error('Already Tiedup with other vendor');
            return back();
        }
        $leadinfo = DB::table('service_requests')
            ->join('items', 'items.id', '=', 'service_requests.item_id')
            ->join('categories', 'categories.id', '=', 'items.category_id')
            ->where('service_requests.id', '=', $serviceRequestId)
            ->where('service_requests.expired',  0)
            ->select('categories.id as cat_id', 'service_requests.item_id', 'service_requests.created_at', 'service_requests.preferred_doctor_id')
            ->first();
        if (!$leadinfo) {
            DB::table('service_requests')->where('id', $serviceRequestId)->delete();
            if ($request->ajax()) return response()->json(['status' => false, 'message' => 'Lead expired']);
            Toastr::error('Lead expired');
            return back();
        }

        $cat_id = $leadinfo->cat_id;

        // check if expired ===============

        if ($leadinfo->created_at < now()->subMinutes(Helpers::get_lead_exp_minutes())) {
            DB::table('service_requests')->where('id', $serviceRequestId)->update(['expired' => 1]);
            if ($request->ajax()) return response()->json(['status' => false, 'message' => 'Lead expired']);
            Toastr::error('Lead expired');
            return back();
        }
        $zoneId = \App\CentralLogics\Helpers::get_store_data()->zone_id;
        $store_id = \App\CentralLogics\Helpers::get_store_id();
        $vendor_id = \App\CentralLogics\Helpers::get_vendor_id();

        // Try service-specific charge first, then fall back to category-level
        $leadChargeInfo = LeadCharge::where('category_id', $cat_id)->where('zone_id', $zoneId)
            ->where('item_id', $leadinfo->item_id)->first()
            ?? LeadCharge::where('category_id', $cat_id)->where('zone_id', $zoneId)
            ->whereNull('item_id')->first();
        $balanceInfo  = StoreWallet::where('vendor_id', $vendor_id)->first();
        if (!$balanceInfo) {
            if ($request->ajax()) return response()->json(['status' => false, 'message' => 'Insufficient wallet balance to accept leads']);
            Toastr::error('Insufficient wallet balance to accept leads');
            return back();
        }
        $avlblBalance = $balanceInfo->total_earning - $balanceInfo->pending_withdraw;

        $totalVendors = DB::table('stores')
            ->join('items', function ($join) {
                $join->whereRaw('FIND_IN_SET(stores.id, items.store_ids) > 0');
            })
            ->where('items.category_id', $cat_id)
            ->where('stores.zone_id', $zoneId)
            ->select('stores.name', 'items.category_id')
            ->groupBy('stores.id', 'items.category_id')
            ->count();

        // Check if this is a dedicated lead
        $serviceReqCheck = ServiceRequest::find($serviceRequestId);
        $isDedicatedLead = $serviceReqCheck && $serviceReqCheck->is_dedicated;

        if (!$leadChargeInfo) {
            $chargesToBeApplied = 0;
        } elseif ($isDedicatedLead && $leadChargeInfo->dedicated_lead_charge > 0) {
            $chargesToBeApplied = $leadChargeInfo->dedicated_lead_charge;
        } else {
            if ($totalVendors <= $leadChargeInfo->vendor_count) {
                $chargesToBeApplied = $leadChargeInfo->ven_same_charges;
            } else {
                $acceptedCount = AcceptedServiceRequest::where('service_request_id', $serviceRequestId)->count();
                if ($acceptedCount == 0) {
                    $chargesToBeApplied =  $leadChargeInfo->ven_1_charges;
                } elseif ($acceptedCount == 1) {
                    $chargesToBeApplied =  $leadChargeInfo->ven_2_charges;
                } elseif ($acceptedCount == 2) {
                    $chargesToBeApplied =  $leadChargeInfo->ven_3_charges;
                } else {
                    $chargesToBeApplied =  $leadChargeInfo->ven_other_charges;
                }
            }
        }
        $walletMinBalance = Helpers::get_wallet_min_balance($zoneId, $cat_id);
        $minimumBalanceRequired = $chargesToBeApplied > $walletMinBalance ? $chargesToBeApplied : $walletMinBalance;
        // check balance
        if ($chargesToBeApplied) {
            if ($avlblBalance < $minimumBalanceRequired) {
                $msg = 'Insufficient wallet balance. Minimum ' . _price($minimumBalanceRequired) . ' required';
                if ($request->ajax()) return response()->json(['status' => false, 'message' => $msg]);
                Toastr::error('Insufficient wallet balance to accept leads. Minimum ' . _price($minimumBalanceRequired) . ' required');
                return back();
            }

            //deduct amount from wallet
            $wallet = StoreWallet::where('vendor_id', $vendor_id)->first();
            $wallet->decrement('total_earning', $chargesToBeApplied);
            $wallet->increment('total_withdrawn', $chargesToBeApplied);
            $wallet->save();

            //insert into transactions 
            $account_transaction = new AccountTransaction();
            $account_transaction->current_balance = $wallet->sum('total_earning') - $wallet->sum('total_withdrawn');
            $account_transaction->from_type = 'store';
            $account_transaction->amount = $chargesToBeApplied;
            $account_transaction->from_id = $vendor_id;
            $account_transaction->method = 'wallet';
            $account_transaction->action = 'debit';
            $account_transaction->reason = $isDedicatedLead ? 'Dedicated Lead Charges' : 'Lead Charges';
            $account_transaction->created_by = 'store';
            $account_transaction->save();
        }
        $appointmentConfirmed = false;
        $servieQty = ServiceRequest::find($serviceRequestId);
        // save acceptance 
        $acceptance = new AcceptedServiceRequest;
        $acceptance->vendor_id = $store_id;
        $acceptance->qty = $servieQty->qty;

        $acceptance->service_request_id = $serviceRequestId;
        $acceptance->created_at = date('Y-m-d H:i:s');

        // for hospital flow only 
        if (isset($leadinfo->preferred_doctor_id) && $leadinfo->preferred_doctor_id) {
            // auto confirm and assign 
            $doctorProfile = \App\Models\DoctorProfile::find($leadinfo->preferred_doctor_id);
            if ($doctorProfile && $doctorProfile->emp_id) {
                $acceptance->assigned_status = 'Assigned';
                $acceptance->assigned_type   = 'staff';
                $acceptance->assigned_to     = $doctorProfile->emp_id;
                $acceptance->assigned_at     = date('Y-m-d H:i:s');
                $acceptance->current_status  = 'Confirmed';
                $acceptance->confirmed_at    = date('Y-m-d H:i:s');
            } elseif ($doctorProfile) {
                $acceptance->current_status = 'Confirmed';
                $acceptance->confirmed_at   = date('Y-m-d H:i:s');
            }
            $appointmentConfirmed = true;
        }


        $serReq = ServiceRequest::find($serviceRequestId);
        $serReq->accepted = 1;
        if ($serReq->accepted_by) {
            $currently_accepted_by = explode(',', $serReq->accepted_by);
            if (!in_array($store_id, $currently_accepted_by)) {
                array_push($currently_accepted_by, $store_id);
            }
            $serReq->accepted_by = implode(',', $currently_accepted_by);
        } else {
            $serReq->accepted_by = $store_id;
        }

        if ($acceptance->save() && $serReq->update()) {
            if ($request->ajax()) return response()->json(['status' => true, 'message' => 'Lead accepted! You can now contact the customer.']);
            Toastr::success('You can now contact customer');
            DB::table('lead_statuses')->insert([
                'service_request_id' => $serReq->id,
                'status' => 'Requested Accepted',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Notify customer — appointment confirmed (hospital) or enquiry accepted (general)
            $user = User::find($serReq->user_id);
            if ($user) {
                $storeName = Helpers::get_store_data()?->name ?? 'The store';
                if ($appointmentConfirmed) {
                    $title       = 'Appointment Confirmed';
                    $description = "Your appointment for \"{$serReq->item_name}\" has been confirmed by {$storeName}.";
                } else {
                    $title       = 'Enquiry Accepted';
                    $description = "{$storeName} has accepted your enquiry for \"{$serReq->item_name}\". They will contact you shortly.";
                }
                $notifData = [
                    'title'       => $title,
                    'description' => $description,
                    'order_id'    => $serReq->id,
                    'image'       => '',
                    'type'        => 'service',
                ];
                Helpers::send_push_notif_to_device($user->cm_firebase_token, $notifData);
                DB::table('user_notifications')->insert([
                    'data'       => json_encode($notifData),
                    'user_id'    => $user->id,
                    'type'       => 'service',
                    'type_id'    => $serReq->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        return back();
    }

    public function lead_details($leadId)
    {
        $avlblSttsIds = Helpers::get_store_data()->lead_statuses;
        $statuses = [];
        if ($avlblSttsIds) {
            $arr = explode(',', $avlblSttsIds);
            if (!empty($arr) && is_array($arr)) {
                $statuses = DB::table('service_statuses')->whereIn('id', $arr)->get();
            }
        }
        $default_statuses = DB::table('service_statuses')->where('removable', 0)->get();

        $reqDetails = DB::table('service_requests')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->join('users', 'users.id', 'service_requests.user_id')
            ->where('service_requests.id', $leadId)
            ->select('items.name', 'service_requests.*', 'service_requests.id as s_id', 'users.phone')
            ->first();
        $acceptanceDetails =  DB::table('accepted_service_requests')->where('service_request_id', $leadId)->where('vendor_id', Helpers::get_store_id())->first();
        if (!$acceptanceDetails) {
            $acceptanceDetails = DB::table('cancelled_service_requests')->where('service_request_id', $leadId)->where('vendor_id', Helpers::get_store_id())->first();
        }
        $venJobDetails = DB::table('vendor_emp_jobs')->where(['acc_id' => $acceptanceDetails->id, 'emp_id' => Helpers::get_loggedin_user()->id])->first();
        // prx($venJobDetails);
        $timeline = DB::table('lead_statuses')->where('service_request_id', $leadId)->get();

        $quotation = DB::table('in_service_quotations')->where('service_id', $leadId)->first();
        if ($quotation) {
            $quotationItems =  DB::table('service_quote_items')->where('quote_id', $quotation->id)->get();
        } else {
            $quotationItems = [];
        }
        $gatepass  = GatePass::where('service_id', $leadId)->first();
        if ($gatepass) {
            $gpItems = DB::table('gate_pass_items')->where('gatepass_id', $gatepass->id)->get();
        } else {
            $gpItems =  [];
        }
        $data['job_card'] = JobCard::where('lead_id', $leadId)->first();
        $data['receivable_rec'] = ReceivableReceipt::where('lead_id', $leadId)->first();

        return view('vendor-views.service.lead-details', compact('data', 'reqDetails', 'statuses', 'acceptanceDetails', 'gatepass', 'quotation', 'quotationItems', 'gpItems', 'timeline', 'venJobDetails', 'default_statuses'));
    }

    public function cancel(Request $request)
    {
        if (auth('vendor')->check()) {
            $cancelled_by = 'Vendor';
        } else {
            $cancelled_by = 'Staff';
        }

        $service_id = $request->service_id;
        $serviceReq = AcceptedServiceRequest::where('service_request_id', $service_id)->first();
        $serviceReq->current_status = 'Cancelled By ' . $cancelled_by;
        $serviceReq->cancelled_by = $cancelled_by;


        $request_id = $service_id;
        $service_requests = ServiceRequest::where('id', $serviceReq->service_request_id)->first();
        $item = DB::table('items')->where('id', $service_requests->item_id)->first();
        $user = DB::table('service_requests')->join('users', 'users.id', 'service_requests.user_id')->where('service_requests.id', $request_id)->select('users.*')->first();
        if ($user) {
            $storeName = Helpers::get_store_data()?->name ?? 'The store';
            $isAppointment = !empty($service_requests->preferred_doctor_id);
            $data = [
                'title'       => $isAppointment ? 'Appointment Cancelled' : 'Enquiry Cancelled',
                'description' => $isAppointment
                    ? "Your appointment for \"{$item->name}\" has been cancelled by {$storeName}."
                    : "{$storeName} has cancelled your enquiry for \"{$item->name}\".",
                'order_id'    => $request_id,
                'image'       => '',
                'type'        => 'service',
            ];
            Helpers::send_push_notif_to_device($user->cm_firebase_token, $data);
            DB::table('user_notifications')->insert([
                'data'       => json_encode($data),
                'user_id'    => $user->id,
                'type'       => 'service',
                'type_id'    => $request_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }


        if ($serviceReq->update()) {

            DB::table('service_requests')->where('id', $service_id)->update([
                'cancelled_by' => $cancelled_by,
                'reason'       => $request->reason,
            ]);

            $cancelledRequest = $serviceReq->replicate();
            $cancelledRequest->setTable('cancelled_service_requests');
            $cancelledRequest->save();
            $serviceReq->delete();

            return response()->json(['status' => true, 'message' => 'Cancelled Successfully']);
        } else {
            return response()->json(['status' => false, 'message' => 'Some error occured']);
        }

        return back();
    }

    public function lead_list(Request $request, $status)
    {
        $title = $status . ' Leads';
        $confirmedAll = DB::table('accepted_service_requests')
            ->join('service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('stores.id', Helpers::get_store_id())
            ->where('accepted_service_requests.current_status', $status)
            ->select('service_requests.id as service_id', 'items.name as item_name', 'items.image as item_image',  'accepted_service_requests.*')
            ->get()
            ->toArray();
        // && $status != 'Cancelled'
        if ($request->has('status') && $request->status != 'All') {

            $filter_status = $request->status;
            $filtere_confirmed = DB::table('accepted_service_requests')
                ->join('service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
                ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
                ->join('items', 'items.id', 'service_requests.item_id')
                ->where('stores.id', Helpers::get_store_id())
                ->where('accepted_service_requests.current_status', $status)
                // ->where('accepted_service_requests.assigned_status', $status)
                ->select('service_requests.id as service_id', 'items.name as item_name', 'items.image as item_image',  'accepted_service_requests.*')
                ->get()
                ->toArray();
            $confirmed = $filtere_confirmed;
        } else if ($status == 'Cancelled') {

            $filter_status = $request->status;
            $filtere_confirmed = DB::table('cancelled_service_requests')
                ->join('service_requests', 'cancelled_service_requests.service_request_id', 'service_requests.id')
                ->join('stores', 'stores.id', 'cancelled_service_requests.vendor_id')
                ->join('items', 'items.id', 'service_requests.item_id')
                ->where('stores.id', Helpers::get_store_id())
                ->select('service_requests.id as service_id', 'items.name as item_name', 'items.image as item_image',  'cancelled_service_requests.*')
                ->get()
                ->toArray();
            $confirmed = $filtere_confirmed;
        } else {
            $filter_status = 'All';
            $confirmed = $confirmedAll;
        }

        $count['total_confirmed'] = count($confirmed);
        $count['total_assigned'] = 0;
        $count['total_unassigned']  = 0;

        foreach ($confirmed as $conf) {
            if ($conf->assigned_status == 'Unassigned') {
                $count['total_unassigned']++;
            } else if ($conf->assigned_status == 'Assigned') {
                $count['total_assigned']++;
            }
        }


        $allStaff  = DB::table('vendor_employees')->where('store_id', Helpers::get_store_id())->where('status', 1)->get();

        if ($status == 'Cancelled' || $status == 'Completed' || $status == 'Rejected') {
            return view('vendor-views.service.list-services', compact('count', 'confirmed', 'title', 'filter_status', 'allStaff', 'status'));
        } else {
            return view('vendor-views.service.confirmed-services', compact('count', 'confirmed', 'title', 'status', 'filter_status', 'allStaff'));
        }
    }
    public function delete_row(Request $request)
    {

        if ($request->type == 'invoice') {
            $InvoiceItemDlt = InvoiceItem::where('id', $request->quoteId)->first();
            if ($InvoiceItemDlt->service_id) {
                $service_invoice = ServiceInvoice::where('service_id', $InvoiceItemDlt->service_id)->first();
                if ($service_invoice->correction) {
                    return response()->json(['status' => false, 'message' => "Can't edit invoice more than one time"]);
                }
            }
            // $InvoiceItemDlt = InvoiceItem::where('id', $request->quoteId)->delete();
            if ($InvoiceItemDlt) {
                return response()->json(['status' => true, 'message' => "Deleted successfully"]);
            } else {
                return response()->json(['status' => false, 'message' => "Some error occured"]);
            }
        }
    }

    public function save_manual_invoice(Request $request, $task_id = null)
    {
        $request->validate([
            'item_name.*' => 'required',
        ]);

        if ($request->payment_stts == 'Unpaid') {
            $request->validate([
                'payment_date' => 'required',
                'reminder_date' => 'required',
            ]);
        }
        if (
            $request->payment_stts == 'Paid' && $request->payment_mode == 'Cash and Online'
            && $request->final_total_amount != $request->cash_amount + $request->online_amount
        ) {
            Toastr::error("Amount Mismatched");
            return  back();
        }

        $rules = [];
        $messages = [];
        $bill_to = null;
        $customer_rule = 'required';

        if ($request->filled('bill_to')) {
            $bill_to = $request->bill_to;
        } elseif ($request->filled('customer_id')) {
            $bill_to = $request->customer_id;
            $customer_rule = 'nullable';
        } elseif (!empty($task_id)) {
            $task = StoreTask::find($task_id);
            if ($task && $task->user_id) {
                $customer_rule = 'nullable';
                $bill_to = $task->user_id;
            }
        }

        if (!$request->has('bill_from')) {
            $rules['bill_to'] = $customer_rule;
            $messages['bill_to.required'] = 'Customer field is required';
            $prefixe = _getInvoicePrefix($request->tax_type);
        } else {
            $rules['bill_from'] = 'required';
            $messages['bill_from.required'] = 'Seller field is required';

            $store = DB::table('stores')->find($request->bill_from);
            $prefixe = _getInvoicePrefix($request->tax_type, $store);
        }

        $request->validate($rules, $messages);

        if (!$request->filled('bill_to') && $bill_to) {
            $request->merge(['bill_to' => $bill_to]);
        }


        // check invoice number 
        if (is_serial_number_used($request->number, $prefixe,  $request->tax_type)) {
            Toastr::error("Serial Number Already Used");
            return  back();
        }

        $totalPrice = 0;
        if ($request->has('item_name')) {
            foreach ($request->item_price as $key => $price) {
                $totalPrice += _taxIncludedPrice($price, $request->item_tax[$key], 'actual') * $request->item_qty[$key];
            }
        }
        if (isset($request->item_price_new)) {
            foreach ($request->item_price_new as $key => $price) {
                $gstStatus = $request->item_gst_status_new[$key] ?? 'excluding';
                $qty = $request->item_qty_new[$key];
                $tax = $request->item_tax_new[$key] ?? 0;
                $totalPrice += $gstStatus === 'including'
                    ? $price * $qty
                    : _taxIncludedPrice($price, $tax, 'actual') * $qty;
            }
        }


        if ($request->has('number')) {
            $invoice_id = Helpers::generateInvoiceId('M', $update = true, $serial_num = $request->number, $tax_type = $request->tax_type, $store ?? null); // M = manual
        } else {
            $invoice_id = Helpers::generateInvoiceId('M', $update = true, $serial_num = null,  $tax_type = $request->tax_type, $store ?? null); // M = manual
        }
        if ($request->has('bill_from') && $request->bill_from) {
            $store_id = $request->bill_from;
            $bill_to = Helpers::get_store_id();
            $bill_to_type =   'vendor';
            $user_type =  'vendor';
        } else {
            $store_id = Helpers::get_store_id();
            $bill_to = $bill_to ?? $request->bill_to;
            $bill_to_type =  'user';
            $userTypeInfo = StoreCustomer::find($bill_to)->user_type;
            $user_type =  $userTypeInfo == 'customer' ? 'store_user' : 'store_vendor';
        }


        $invoice = new ManualInvoice;
        $invoice->task_id =  $task_id;
        $invoice->invoice_id = $invoice_id;
        $invoice->invoice_serial = (int) substr($invoice_id, strrpos($invoice_id, '_') + 1);
        $invoice->financial_year = _currentFinancialYear();
        $invoice->reference_number = $request->reference_number;
        $invoice->vendor_id = $store_id;
        $invoice->bill_to = $bill_to;
        $invoice->bill_to_type = $bill_to_type;
        $invoice->user_type = $user_type;
        $invoice->module_id =  Helpers::get_store_data()->module_id;
        $invoice->total_amount =  $totalPrice;
        $invoice->tax_type =  $request->tax_type;
        $invoice->payment_method =  $request->payment_mode;
        $invoice->payment_mode = $request->payment_mode;

        if ($request->payment_stts == 'Paid' && $request->payment_mode == 'Cash and Online') {
            $invoice->cash_amount = $request->cash_amount;
            $invoice->online_amount = $request->online_amount;
        }
        $invoice->invoice_date = $request->invoice_date;
        $invoice->payment_status =  $request->payment_stts;
        $invoice->payment_date =  $request->payment_date;
        $invoice->reminder_date =  $request->reminder_date;
        $invoice->reminder_freq =  $request->reminder_freq;
        $invoice->reminder_freq_unit =  $request->reminder_freq_unit;
        $invoice->created_by = auth('vendor')->check() ? 0 : Helpers::get_loggedin_user()->id;
        if ($request->filled('tnc_id')) {
            $tnc = \App\Models\StoreTnc::find($request->tnc_id);
            if ($tnc) {
                $invoice->terms_and_conditions = $tnc->content;
            }
        }
        $invoice->save();

        if ($request->has('item_name')) {
            foreach ($request->item_name as $key => $name) {
                $InvoiceItem = new InvoiceItem();
                $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
                $InvoiceItem->manual_invoice_id = $invoice->id;
                $InvoiceItem->name = $request->item_name[$key];
                $InvoiceItem->qty = $request->item_qty[$key];
                $InvoiceItem->price = $request->item_price[$key];
                $InvoiceItem->unit = $request->item_unit[$key];
                $InvoiceItem->tax = $request->tax_type == 'gst' ?  ($request->item_tax[$key] ?? 0) : 0;
                $InvoiceItem->hsn = $request->item_hsn[$key];
                $InvoiceItem->save();
            }
        }
        $inv_items = 0;
        if ($request->has('invoice_item_new')) { // insert new items to invoice
            foreach ($request->invoice_item_new as $key => $id) {
                $InvoiceItem = new InvoiceItem();
                $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
                $InvoiceItem->manual_invoice_id = $invoice->id;
                $InvoiceItem->name = $request->item_name_new[$key];
                $InvoiceItem->price = $request->item_price_new[$key];
                $InvoiceItem->qty = $request->item_qty_new[$key];
                $InvoiceItem->unit = $request->item_unit_new[$key] ?? null;
                $InvoiceItem->tax = $request->tax_type == 'gst' ?  ($request->item_tax_new[$key] ?? 0) : 0;
                $InvoiceItem->hsn = $request->item_hsn_new[$key];
                $InvoiceItem->gst_status = $request->item_gst_status_new[$key] ?? 'excluding';
                $InvoiceItem->inv_id = $request->inventory_item_id_new[$key] ?? null;
                $InvoiceItem->save();
                if ($request->inventory_item_id_new[$key]) {
                    $inv_items++;
                }

                if ($request->inventory_item_id_new[$key]) {
                    _updateInventoryStock($request->inventory_item_id_new[$key], $request->item_qty_new[$key], $request->item_unit_new[$key]);
                }
            }
        }
        // RECHECK AMOUNT 
        // if ($request->payment_mode == 'Cash and Online' && ($invoice->final_total_amount != $request->cash_amount + $request->online_amount)) {
        //     $invoice->payment_status = 'Unpaid';
        //     $invoice->save();
        // }

        // PETTY CASHBOOK ENTRY 
        if ($invoice->payment_status == 'Paid') {
            if ($request->payment_mode == 'Cash' || $request->payment_mode == 'Cash and Online') {
                if ($request->payment_mode == 'Cash and Online') {
                    $amount = $invoice->cash_amount;
                } else {
                    $amount = $totalPrice;
                }
            } else {
                $amount = $request->cash_amount;
            }
            _savePettyCashBookEntry($amount, 'Received', 'Invoice Payment', $request->invoice_date, $invoice->invoice_id);
        }

        // master ledger entry 
        $data = [
            'date' => $request->invoice_date ?? now(),
            'amount' => $totalPrice,
            'status' => $invoice->payment_status == 'Paid' ? 'approved' : 'pending',
            'payment_mode' => 'cash',
            'invoice_id' => 'manual-' . $invoice->id,
            'voucher_type' => 'Sales',
            'invoice_id' => 'manual-' . $invoice->id,
            'file' => $request->hasFile('file') ?? null,
        ];

        $customer = StoreCustomer::where('store_id', Helpers::get_store_id())->where('id', $request->bill_to)->first();
        $debit_account = Helpers::ensureCustomerLedger($customer);
        $credit_account = Helpers::ensureSalesAccount();
        $voucher =  _masterLedgerEntry(
            $data,
            $credit_account,
            $debit_account,
            'customer',
            'store',
            null
        );

        if ($invoice->payment_status == 'Paid') {
            _saveDayBookEntry($totalPrice, 'credit', Helpers::get_store_id(), "Sales Invoice", $invoice->id, $voucher?->id, $invoice->invoice_date, $invoice->reference_number, $request->payment_mode);
        }

        _auditLogs('Created Invoice : ' . $invoice->invoice_id);

        if ($invoice->tax_type === 'non-gst') {
            $currentStore = Helpers::get_store_data();
            $currentStore->non_gst_sno = ((int) $currentStore->non_gst_sno) + 1;
            $currentStore->save();
        }

        $docData = Helpers::generateInventoryGatepass($invoice, (object)[], 'sale');

        // Inventory order 
        if ($inv_items) {
            Helpers::_placeInventoryOrder($invoice);
        }
        $data = _createBillPdf($invoice, 'vendor');
        $invoice->update(['pdf' => $data['pdf']]);
        try {
            return redirect($data['url']);
        } catch (\Throwable $th) {
            //
        }
        // return redirect()->route('vendor.invoice.manual-invoice-view', ['manual', $invoice->invoice_id]);
    }

    public function invoice_list(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $today = date('Y-m-d');

        $preset = request('date_range') ?? 'last_30_days';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];
        $from = $range['start']->toDateString();
        $to  = $range['end']->toDateString();

        $status = request()->get('status') ?? 'all';
        $search = request()->get('search');

        $invoices = collect();

        if (in_array($status, ['overdue', 'pending', 'credit', 'Unpaid'])) {
            if ($status === 'overdue') {
                $invoices = fetchInvoices(ServiceInvoice::class, 'service', 'Unpaid', '<', $today, 'overdue', $formatted_from, $formatted_to, $search)
                    ->merge(fetchInvoices(ManualInvoice::class, 'manual', 'Unpaid', '<', $today, 'overdue', $formatted_from, $formatted_to, $search));
            } elseif ($status === 'pending') {
                $invoices = fetchInvoices(ServiceInvoice::class, 'service', 'Unpaid', '>=', $today, 'pending', $formatted_from, $formatted_to, $search)
                    ->merge(fetchInvoices(ManualInvoice::class, 'manual', 'Unpaid', '>=', $today, 'pending', $formatted_from, $formatted_to, $search));
            } elseif ($status === 'credit') {
                $invoices = fetchInvoices(ServiceInvoice::class, 'service', 'Paid', null, null, 'credit', $formatted_from, $formatted_to, $search)
                    ->merge(fetchInvoices(ManualInvoice::class, 'manual', 'Paid', null, null, 'credit', $formatted_from, $formatted_to, $search));
            } elseif ($status === 'Unpaid') {
                $invoices = fetchInvoices(ServiceInvoice::class, 'service', 'Unpaid', null, null, 'pending', $formatted_from, $formatted_to, $search)
                    ->merge(fetchInvoices(ManualInvoice::class, 'manual', 'Unpaid', null, null, 'pending', $formatted_from, $formatted_to, $search));
            }
        } else {
            $overdue = fetchInvoices(ServiceInvoice::class, 'service', 'Unpaid', '<', $today, 'overdue', $formatted_from, $formatted_to, $search)
                ->concat(fetchInvoices(ManualInvoice::class, 'manual', 'Unpaid', '<', $today, 'overdue', $formatted_from, $formatted_to, $search));

            $pending = fetchInvoices(ServiceInvoice::class, 'service', 'Unpaid', '>=', $today, 'pending', $formatted_from, $formatted_to, $search)
                ->concat(fetchInvoices(ManualInvoice::class, 'manual', 'Unpaid', '>=', $today, 'pending', $formatted_from, $formatted_to, $search));

            $credit = fetchInvoices(ServiceInvoice::class, 'service', 'Paid', null, null, 'credit', $formatted_from, $formatted_to, $search)
                ->concat(fetchInvoices(ManualInvoice::class, 'manual', 'Paid', null, null, 'credit', $formatted_from, $formatted_to, $search));

            // echo '<pre>';
            // echo 'pending';
            // print_r($pending); 
            // echo 'credit';
            // print_r($credit);
            // echo 'overdue';
            // print_r($overdue);

            $invoices = $overdue->concat($pending)->concat($credit)->unique('invoice_id')->values();;
        }
        // prx($invoices);
        // die;

        return view('vendor-views.service.invoices', compact('preset', 'invoices', 'from', 'to', 'status', 'search'));
    }

    public function reminder_status(Request $request, $type,  $status, $id)
    {
        if ($type == 'service') {
            $invoice = ServiceInvoice::find($id);
        } else {
            $invoice = ManualInvoice::find($id);
        }
        $invoice->reminder_status = $status ? 0 : 1;
        $invoice->save();


        Toastr::success('Reminder Status Changed Successfully');
        return back();
    }
    public function leadsDashboard(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $preset  = $request->input('date_range', 'last_30_days');
        $custom  = $request->input('custom_date_range');
        $range   = Helpers::calculatePresetDates($preset, $custom);
        $from    = $range['start']->startOfDay();
        $to      = $range['end']->endOfDay();
        $days    = max(1, $from->diffInDays($to) + 1);

        $between = [$from, $to];

        $total = DB::table('service_requests')
            ->whereRaw("FIND_IN_SET(?, sent_to)", [$storeId])
            ->whereBetween('created_at', $between)->count();

        $accepted = DB::table('accepted_service_requests')
            ->join('service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
            ->where('accepted_service_requests.vendor_id', $storeId)
            ->whereBetween('service_requests.created_at', $between)->count();

        $completed = DB::table('accepted_service_requests')
            ->join('service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
            ->where('accepted_service_requests.vendor_id', $storeId)
            ->where('accepted_service_requests.current_status', 'Completed')
            ->whereBetween('service_requests.created_at', $between)->count();

        $cancelled = DB::table('cancelled_service_requests')
            ->join('service_requests', 'service_requests.id', '=', 'cancelled_service_requests.service_request_id')
            ->where('cancelled_service_requests.vendor_id', $storeId)
            ->whereBetween('service_requests.created_at', $between)->count();

        $expMins = Helpers::get_lead_exp_minutes();
        $missed  = DB::table('service_requests')
            ->whereRaw("FIND_IN_SET(?, sent_to)", [$storeId])
            ->whereBetween('created_at', $between)
            ->where('created_at', '<', now()->subMinutes($expMins))
            ->whereNotExists(fn($q) => $q->from('accepted_service_requests')
                ->whereColumn('service_request_id', 'service_requests.id')
                ->where('vendor_id', $storeId))
            ->whereNotExists(fn($q) => $q->from('cancelled_service_requests')
                ->whereColumn('service_request_id', 'service_requests.id')
                ->where('vendor_id', $storeId))
            ->count();

        $completionRate   = $accepted > 0 ? round($completed / $accepted * 100) : 0;
        $acceptanceRate   = $total    > 0 ? round($accepted  / $total    * 100) : 0;
        $cancellationRate = $accepted > 0 ? round($cancelled / $accepted * 100) : 0;

        // Daily chart — iterate between from→to
        $dailyRaw = DB::table('service_requests')
            ->whereRaw("FIND_IN_SET(?, sent_to)", [$storeId])
            ->whereBetween('created_at', $between)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as cnt")
            ->groupBy('date')->get()->keyBy('date');

        $dailyAccRaw = DB::table('accepted_service_requests')
            ->join('service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
            ->where('accepted_service_requests.vendor_id', $storeId)
            ->whereBetween('service_requests.created_at', $between)
            ->selectRaw("DATE(service_requests.created_at) as date, COUNT(*) as cnt")
            ->groupBy('date')->get()->keyBy('date');

        $dailyLabels = $dailyNew = $dailyAccepted = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $d = $cursor->format('Y-m-d');
            $dailyLabels[]   = $cursor->format('d M');
            $dailyNew[]      = $dailyRaw->get($d)->cnt ?? 0;
            $dailyAccepted[] = $dailyAccRaw->get($d)->cnt ?? 0;
            $cursor->addDay();
        }

        // Top services
        $topServices = DB::table('service_requests')
            ->join('items', 'items.id', '=', 'service_requests.item_id')
            ->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [$storeId])
            ->whereBetween('service_requests.created_at', $between)
            ->selectRaw("items.name, COUNT(*) as cnt")
            ->groupBy('items.name')->orderByDesc('cnt')->limit(6)->get();

        $recentLeads = DB::table('service_requests')
            ->leftJoin('accepted_service_requests as acc', function ($j) use ($storeId) {
                $j->on('acc.service_request_id', '=', 'service_requests.id')
                  ->where('acc.vendor_id', $storeId);
            })
            ->leftJoin('cancelled_service_requests as can', function ($j) use ($storeId) {
                $j->on('can.service_request_id', '=', 'service_requests.id')
                  ->where('can.vendor_id', $storeId);
            })
            ->join('items', 'items.id', '=', 'service_requests.item_id')
            ->join('users', 'users.id', '=', 'service_requests.user_id')
            ->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [$storeId])
            ->whereBetween('service_requests.created_at', $between)
            ->select(
                'service_requests.id',
                'service_requests.created_at',
                'items.name as service_name',
                'users.f_name',
                'users.phone',
                DB::raw('COALESCE(acc.current_status, can.current_status) as current_status'),
                DB::raw("CASE
                    WHEN acc.id IS NOT NULL AND can.id IS NULL THEN 'Accepted'
                    WHEN can.id IS NOT NULL THEN 'Cancelled'
                    WHEN service_requests.created_at < NOW() - INTERVAL {$expMins} MINUTE THEN 'Missed'
                    ELSE 'New'
                END as display_status")
            )
            ->orderByDesc('service_requests.created_at')
            ->limit(5)->get();

        return view('vendor-views.service.leads_dashboard', compact(
            'preset', 'total', 'accepted', 'completed', 'cancelled', 'missed',
            'completionRate', 'acceptanceRate', 'cancellationRate',
            'dailyLabels', 'dailyNew', 'dailyAccepted', 'topServices', 'recentLeads'
        ));
    }

    public function report(Request $request)
    {
        // self service report 
        $self_done_services = DB::table('accepted_service_requests')
            ->join('service_requests', 'service_requests.id', 'accepted_service_requests.service_request_id')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('accepted_service_requests.vendor_id', Helpers::get_store_id())
            ->where('accepted_service_requests.assigned_to', Helpers::get_store_id())
            ->where('accepted_service_requests.assigned_type', 'vendor')->count();
        // prx($selfservice_done);

        //staff service report
        $staff = VendorEmployee::where('store_id', Helpers::get_store_id())->get();
        foreach ($staff as $key => $value) {
            $staff[$key]->service_done =  $staff->assigned_services = DB::table('accepted_service_requests')
                ->join('service_requests', 'service_requests.id', 'accepted_service_requests.service_request_id')
                ->join('items', 'items.id', 'service_requests.item_id')
                ->where('accepted_service_requests.assigned_to', $value->id)
                ->where('accepted_service_requests.assigned_type', 'staff')->count();
        }
        return view('vendor-views.service.report', compact('staff', 'self_done_services'));
    }
    public function staff_report(Request $request, $id)
    {
        $staff = null;
        if ($id == 0) {
            $assigned_services = DB::table('accepted_service_requests')
                ->join('service_requests', 'service_requests.id', 'accepted_service_requests.service_request_id')
                ->join('items', 'items.id', 'service_requests.item_id')
                ->leftJoin('vendor_emp_jobs', 'vendor_emp_jobs.service_id', 'accepted_service_requests.service_request_id')
                ->leftJoin('service_statuses', 'service_statuses.id', 'vendor_emp_jobs.status')
                ->where('accepted_service_requests.assigned_type', 'vendor')
                ->where('accepted_service_requests.vendor_id', Helpers::get_store_id())
                ->where('accepted_service_requests.assigned_to', Helpers::get_store_id())
                ->select('accepted_service_requests.*', 'service_statuses.status as job_status', 'vendor_emp_jobs.ended_at', 'items.name', 'items.image')->get();
        } else {
            $assigned_services = null;
            $staff = VendorEmployee::find($id);
            $staff->assigned_services = DB::table('accepted_service_requests')
                ->join('service_requests', 'service_requests.id', 'accepted_service_requests.service_request_id')
                ->join('items', 'items.id', 'service_requests.item_id')
                ->leftJoin('vendor_emp_jobs', 'vendor_emp_jobs.service_id', 'accepted_service_requests.service_request_id')
                ->leftJoin('service_statuses', 'service_statuses.id', 'vendor_emp_jobs.status')
                ->where('accepted_service_requests.assigned_to', $id)
                ->select('accepted_service_requests.*', 'service_statuses.status as job_status', 'vendor_emp_jobs.ended_at', 'items.name', 'items.image')->get();
        }

        return view('vendor-views.service.staff-report', compact('staff', 'assigned_services'));
    }
    public function save_invoice(Request $request)
    {
        // prx($request->all());
        if ($request->payment_stts == 'Unpaid') {
            $request->validate([
                'payment_date' => 'required',
                'reminder_date' => 'required',
            ]);
        }
        if (
            $request->payment_stts == 'Paid' && $request->payment_method == 'Cash and Online'
            && $request->total_amt != $request->cash_amount + $request->online_amount
        ) {
            Toastr::error("Amount Mismatched");
            return  back();
        }

        $service_id = $request->service_id;
        $tax_type = $request->tax_type ?? 'non-gst';

        $existInvoice = ServiceInvoice::where('service_id', $service_id)->exists();
        $totalPrice = 0;
        if ($request->has('item_price')) {
            foreach ($request->item_price as $key => $price) {
                if ($tax_type == 'gst') {
                    $rtax = $request->item_tax[$key] ?? 0;
                    $totalPrice += _taxIncludedPrice($price, $rtax, 'actual') *  $request->item_qty[$key];
                } else {
                    $totalPrice += _taxIncludedPrice($price, 0, 'actual') *  $request->item_qty[$key];
                }
            }
        }
        if ($request->has('item_price_new')) {
            foreach ($request->item_price_new as $key => $price) {
                if ($tax_type == 'gst') {
                    $totalPrice += _taxIncludedPrice($price, $request->item_tax_new[$key] ?? 0, 'actual') *  $request->item_qty_new[$key];
                } else {
                    $totalPrice += _taxIncludedPrice($price, 0, 'actual') *  $request->item_qty_new[$key];
                }
            }
        }

        // prx( $totalPrice);
        $service_req = ServiceRequest::find($service_id);
        $user_id = $service_req->user_id;
        $pdfName = 'invoice_' . date('YmdHis') . '.pdf';

        if ($existInvoice) {
            $serviceInvoice = $request->filled('manual_invoice_id')
                ? ServiceInvoice::find($request->manual_invoice_id)
                : ServiceInvoice::where('service_id', $service_id)->first();


            if ($serviceInvoice->correction) {
                Toastr::error('Only one time correction was permitted');
                // return redirect()->route('vendor.business-settings.generate-bill.list');
            }

            $serviceInvoice->total_amount =  $totalPrice;
            $serviceInvoice->bill_to_type = 'user';
            $serviceInvoice->payment_method = $request->payment_mode;
            $serviceInvoice->payment_mode = $request->payment_mode;
            $serviceInvoice->tax_type = $tax_type;

            if ($request->payment_stts == 'Paid' && $request->payment_mode == 'Cash and Online') {
                $serviceInvoice->cash_amount = $request->cash_amount;
                $serviceInvoice->online_amount = $request->online_amount;
            }
            $serviceInvoice->payment_status =  $request->payment_stts;
            $serviceInvoice->payment_date =  $request->payment_date;
            $serviceInvoice->reminder_date =  $request->reminder_date;
            $serviceInvoice->reminder_freq =  $request->reminder_freq;
            $serviceInvoice->reminder_freq_unit =  $request->reminder_freq_unit;
            $serviceInvoice->correction =  1;
            if (!$serviceInvoice->pdf) {
                $serviceInvoice->pdf = $pdfName;
            }

            $serviceInvoice->update();
            _auditLogs('Edited Service Invoice : ' . $serviceInvoice->invoice_id);
        } else {

            $serviceInvoice = new ServiceInvoice();
            $serviceInvoice->bill_to = $user_id;
            $serviceInvoice->invoice_id = Helpers::generateInvoiceId('S'); // S = service
            $serviceInvoice->vendor_id = Helpers::get_store_id();
            $serviceInvoice->service_id =  $service_id;
            $serviceInvoice->bill_to_type = 'user';
            $serviceInvoice->total_amount =  floor($totalPrice);
            $serviceInvoice->payment_method = $request->payment_mode;
            $serviceInvoice->payment_mode = $request->payment_mode;
            $serviceInvoice->tax_type = $tax_type;
            $serviceInvoice->payment_status =  $request->payment_stts;
            $serviceInvoice->payment_date =  $request->payment_date;

            if ($request->payment_stts == 'Paid' && $request->payment_mode == 'Cash and Online') {
                $serviceInvoice->cash_amount = $request->cash_amount;
                $serviceInvoice->online_amount = $request->online_amount;
            }
            $serviceInvoice->reminder_date =  $request->reminder_date;
            $serviceInvoice->reminder_freq =  $request->reminder_freq;
            $serviceInvoice->reminder_freq_unit =  $request->reminder_freq_unit;
            $serviceInvoice->pdf =  $pdfName;
            $serviceInvoice->save();
            _auditLogs('Created Service Invoice : ' . $serviceInvoice->invoice_id);
        }

        if (isset($request->invoice_item_id)) { // existing invoice
            foreach ($request->invoice_item_id as $key => $id) {
                $InvoiceItem = InvoiceItem::where('id', $id)->first();
                $InvoiceItem->service_id = $service_id;
                $InvoiceItem->rand_invoice_id = $serviceInvoice->invoice_id;
                $InvoiceItem->invoice_id = $serviceInvoice->id;
                $InvoiceItem->name = $request->item_name[$key];
                $InvoiceItem->price = $request->item_price[$key];
                $InvoiceItem->qty = $request->item_qty[$key];
                $InvoiceItem->unit = $request->item_unit[$key];
                $InvoiceItem->hsn = $request->item_hsn[$key];
                $InvoiceItem->tax = $request->item_tax[$key] ?? 0;
                $InvoiceItem->update();
            }
        } else { // insert quote items to invoice
            if ($existInvoice) {
                InvoiceItem::where('invoice_id', $serviceInvoice->id)->delete();
            }
            if ($request->has('item_name')) {
                foreach ($request->item_name as $key => $name) {
                    $InvoiceItem = new InvoiceItem();
                    $InvoiceItem->rand_invoice_id = $serviceInvoice->invoice_id;
                    $InvoiceItem->invoice_id = $serviceInvoice->id;
                    $InvoiceItem->service_id = $service_id;
                    $InvoiceItem->name = $request->item_name[$key];
                    $InvoiceItem->price = $request->item_price[$key];
                    $InvoiceItem->hsn = $request->item_hsn[$key];
                    $InvoiceItem->qty = $request->item_qty[$key];
                    $InvoiceItem->unit = $request->item_unit[$key];
                    $InvoiceItem->tax = $request->item_tax[$key] ?? 0;
                    $InvoiceItem->save();
                }
            }
        }
        // echo '<pre>';
        if ($request->has('invoice_item_new')) { // insert new items to invoice
            foreach ($request->invoice_item_new as $key => $id) {
                $InvoiceItem = new InvoiceItem();
                $InvoiceItem->invoice_id = $serviceInvoice->id;
                $InvoiceItem->rand_invoice_id = $serviceInvoice->invoice_id;
                $InvoiceItem->service_id = $service_id;
                $InvoiceItem->name = $request->item_name_new[$key];
                $InvoiceItem->price = $request->item_price_new[$key];
                $InvoiceItem->qty = $request->item_qty_new[$key];
                $InvoiceItem->unit = $request->item_unit_new[$key];
                $InvoiceItem->tax = $request->item_tax_new[$key] ?? 0;
                $InvoiceItem->save();

                // print_r($InvoiceItem);
            }
        }

        // RECHECK AMOUNT 
        if ($serviceInvoice->total_amount != $request->cash_amount + $request->online_amount && ($request->payment_mode == 'Cash and Online')) {
            $serviceInvoice->payment_status = 'Unpaid';
            $serviceInvoice->save();
        }

        // PDF Generate ==========================
        // try {
        // ledger entry 


        $debit_account = Helpers::ensureOtherCustomerLedger();
        // print_r( $debit_account);
        $credit_account = Helpers::ensureServiceRevenueAccount();
        $data = [
            'date' => now(),
            'amount' => $serviceInvoice->total_amount,
            'voucher_type' => 'Sales',
            'status' => $serviceInvoice->payment_status == 'Paid' ? 'approved' : 'pending',
        ];
        $store_id = Helpers::get_store_id();

        $voucher = _masterLedgerEntry($data, $credit_account, $debit_account, 'customer', 'store', null);

        if ($serviceInvoice->payment_status == 'Paid') {
            _saveDayBookEntry($serviceInvoice->total_amount, 'credit', $store_id, 'Lead Invoice', null, $voucher?->id);
        }

        $data = _createBillPdf($serviceInvoice, 'vendor',  $service_req->address_id, false);
        $serviceInvoice->pdf = $data['pdf'];
        $serviceInvoice->save();

        // prx($serviceInvoice);


        return redirect($data['url']);
        // } catch (\Throwable $th) {
        //     return redirect()->back();
        // }
    }

    public function invoice_view(Request $request, $type, $service_id)
    {
        $vendor_contact_det = Store::find(Helpers::get_store_id());
        if ($type == 'manual') {
            $existingInvoice = ManualInvoice::where('invoice_id', $service_id)->where('vendor_id', Helpers::get_store_id())->latest('id')->first();
            $service_det = User::find($existingInvoice->bill_to);
            $quotations = InvoiceItem::where('rand_invoice_id',  $service_id)->get();
            $invoic_num = $service_id;
            $invoice_id = $service_id;
        } else {
            $existingInvoice = ServiceInvoice::where('service_id', $service_id)->get();
            $service_det = DB::table('service_requests')
                ->join('users', 'users.id', 'service_requests.user_id')
                ->where('service_requests.id', $service_id)
                ->select('service_requests.*', 'users.*', 'service_requests.address as s_address')
                ->get()[0];
            $quotations = InvoiceItem::where('service_id',  $service_id)->get();
            $invoic_num = $existingInvoice[0]->invoice_id;
            $invoice_id = $existingInvoice[0]->invoice_id;
        }
        return view('vendor-views.invoice.invoice', compact('vendor_contact_det', 'invoice_id', 'invoic_num', 'service_det', 'quotations', 'service_id', 'existingInvoice', 'service_id'));
    }
    public function manual_invoice_view(Request $request, $type, $invoice_id)
    {
        $existingInvoice[0] = ManualInvoice::where('invoice_id', $invoice_id)->where('vendor_id', Helpers::get_store_id())->latest('id')->first();
        $quotations = InvoiceItem::where('rand_invoice_id',  $existingInvoice[0]->invoice_id)->get();
        if ($request->has('store')) {
            $service_det = Store::find($existingInvoice[0]->bill_to);
            $type = 'store';

            $addr['address'] = $service_det->address;
            $addr['city'] = Zone::find($service_det->zone_id)->name;
            $vendor_contact_det = null;
        } else {
            $vendor_contact_det = Store::find(Helpers::get_store_id());
            $service_det = User::find($existingInvoice[0]->bill_to);
            $type = 'user';
            $address = CustomerAddress::where('user_id', $existingInvoice[0]->bill_to)->first();
            if ($address) {
                $addr['address'] = $address->house . ', ' . $address->floor . ', ' . $address->road . ', ' . $address->address;
                $addr['city'] = $address->city;
            } else {
                $addr['address'] = '';
                $addr['city'] = '';
            }
        }
        return view('vendor-views.invoice.invoice-manual', compact('vendor_contact_det', 'invoice_id', 'service_det', 'quotations',  'existingInvoice', 'addr', 'type'));
    }

    public function service_invoices()
    {
        $invoices = ServiceInvoice::where('vendor_id', Helpers::get_store_id())->where('payment_status', 'Unpaid')->get();
        return view('vendor-views.service.invoices', compact('invoices'));
    }
    public function manual_bill()
    {
        $store = Helpers::get_store_data();
        $categories = Category::where(['position' => 0])->module(Helpers::get_store_data()->module_id)->get();

        $upcoming_bill_number = Helpers::generateInvoiceId('M', $update = false); // only get .. not update
        $bill_number = $upcoming_bill_number; // Example: 'PJS_M_25-26_82'
        $lastUnderscorePos = strrpos($bill_number, '_');
        $bill_num['prefix'] = substr($bill_number, 0, strrpos($bill_number, '_') + 1); // 'PJS_M_25-26_'
        $bill_num['nongst_prefix'] = Helpers::_storePrefix($store->name);
        $bill_num['number'] = substr($bill_number, strrpos($bill_number, '_') + 1);    // '82'
        $bill_num['non_gst_sno'] = Helpers::getNextNonGstSerial();
        $storage_units = StorageUnit::with('parent')->where('store_id', $store->id)->get();

        // Keep incrementing until unique
        do {
            $invoice_num = $bill_num['prefix'] . $bill_num['number'];
            $exists = ManualInvoice::where('invoice_id', $invoice_num)->where('vendor_id', Helpers::get_store_id())->where('financial_year', _currentFinancialYear())->exists();
            if ($exists) {
                $bill_num['number']++;
            }
        } while ($exists);

        $customers = StoreCustomer::where('store_id', Helpers::get_store_id())->get();
        $tncs = \App\Models\StoreTnc::where('store_id', Helpers::get_store_id())->where('tnc_type', 'invoice')->get();
        return view('vendor-views.billing.invoice_generate', compact('storage_units', 'categories', 'customers', 'bill_num', 'tncs'));
    }
    public function generate_bill($service_id)
    {

        $vendor_contact_det = Store::find(Helpers::get_store_id());

        $service_det = DB::table('service_requests')
            ->join('users', 'users.id', 'service_requests.user_id')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->where('service_requests.id', $service_id)
            ->get()[0];

        $existingInvoice = ServiceInvoice::where('service_id', $service_id)->exists();
        $invoice = ServiceInvoice::where('service_id', $service_id)->first();
        // prx($invoice);
        $is_invoice = false;

        if ($existingInvoice) {
            $quotations = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->get();
            $is_invoice = true;
        } else {
            $quotations = DB::table('in_service_quotations')
                ->join('service_quote_items', 'service_quote_items.quote_id', 'in_service_quotations.id')
                ->where('in_service_quotations.service_id', $service_id)
                ->where('in_service_quotations.approved', 1)
                ->get();
        }
        // prx( $quotations);
        return view('vendor-views.staff.invoice_generate', compact('invoice', 'vendor_contact_det', 'is_invoice', 'service_det', 'quotations', 'service_id', 'existingInvoice'));
    }

    public function generate_bill_list()
    {
        $status = 'Completed';
        $title = 'Generate Bill';
        $confirmed = DB::table('accepted_service_requests')
            ->join('service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('stores.id', Helpers::get_store_id())
            ->where('accepted_service_requests.current_status', 'Completed')
            ->select('service_requests.id as service_id', 'items.name as item_name', 'items.id as item_id', 'items.image as item_image',  'accepted_service_requests.*')
            ->get()
            ->toArray();

        // prx($confirmed);

        return view('vendor-views.service.list-services', compact('confirmed', 'title', 'status'));
    }

    public function track_location(Request $request, $staff_id)

    {
        $staff = VendorEmployee::find($staff_id);
        return view('vendor-views.staff.location-tracking', compact('staff_id', 'staff'));
    }
    public function mark_paid(Request $request)
    {
        $type = $request->type;
        if ($type == 'service') {
            $invoice = ServiceInvoice::find($request->id);
        } else {
            $invoice = ManualInvoice::find($request->id);
        }
        // voucher update
        $voucher = StoreVoucher::where('invoice_id', $type . '-' . $invoice->id)->first();
        if ($voucher) {
            $voucher->status = 'approved';
            $voucher->completed_at = now();
            $voucher->save();

            // ledger entries update 
            $ledger_entries = StoreLedgerEntry::where('voucher_id', $voucher->id)->get();
            foreach ($ledger_entries as $key => $value) {
                $value->status = 'approved';
                $value->completed_at = now();
                $value->save();
            }
        }


        $invoice->payment_status = 'Paid';
        $invoice->payment_date = date('Y-m-d');
        $invoice->save();

        $data = _createBillPdf($invoice, 'vendor');
        $invoice->update(['pdf' => $data['pdf']]);

        Toastr::success("Payment status changed successfully");
        return back();
    }
    public function mark_paid2(Request $request)
    {
        $type = $request->type;
        if ($type == 'service') {
            $invoice = ServiceInvoice::find($request->id);
        } else {
            $invoice = ManualInvoice::find($request->id);
        }
        if ($request->payment_mode == 'Cash and Online' && ($invoice->total_amount != ($request->cash_amount + $request->online_amount))) {
            Toastr::error('Amount Mismatched');
            return back();
        }
        // voucher update
        $voucher = StoreVoucher::where('invoice_id', $type . '-' . $invoice->id)->first();
        if ($voucher) {
            $voucher->status = 'approved';
            $voucher->completed_at = now();
            $voucher->save();

            // ledger entries update 
            $ledger_entries = StoreLedgerEntry::where('voucher_id', $voucher->id)->get();
            foreach ($ledger_entries as $key => $value) {
                $value->status = 'approved';
                $value->completed_at = now();
                $value->save();
            }
        }
        $invoice->cash_amount = $request->cash_amount;
        $invoice->online_amount = $request->online_amount;
        $invoice->payment_status = 'Paid';
        $invoice->payment_date = date('Y-m-d');
        $invoice->save();

        $data = _createBillPdf($invoice, 'vendor');
        $invoice->update(['pdf' => $data['pdf']]);

        Toastr::success("Payment status changed successfully");
        return back();
    }
    public function reviews(Request $request)
    {
        $reviews = DB::table('store_reviews')->join('stores', 'stores.id', 'store_reviews.store_id')->join('users', 'users.id', 'store_reviews.user_id')->where('stores.id', Helpers::get_store_id())->select('users.f_name', 'users.l_name', 'users.image as profile_image', 'stores.*', 'store_reviews.comment', 'store_reviews.attachment', 'store_reviews.created_at', 'store_reviews.rating', 'store_reviews.id as rev_id', 'store_reviews.reply', 'store_reviews.status as review_status')->get();
        return view('vendor-views.service.reviews', compact('reviews'));
    }

    public function review_status(Request $request)
    {
        DB::table('store_reviews')->where('id', $request->id)->update(['status' => $request->status]);
        return response()->json(['success' => true]);
    }

    public function lead_settings()
    {
        $storeId = Helpers::get_store_id();
        $storeConfig = \App\Models\StoreConfig::firstOrCreate(['store_id' => $storeId]);
        $store_data = Helpers::get_store_data();
        return view('vendor-views.service.lead_settings', compact('storeConfig', 'store_data'));
    }

    public function lead_settings_update(Request $request)
    {
        $storeId = Helpers::get_store_id();
        \App\Models\StoreConfig::where('store_id', $storeId)->update([
            'lead_available' => $request->has('lead_available') ? 1 : 0,
        ]);
        $store = \App\Models\Store::withoutGlobalScopes()->find($storeId);
        if ($store && $store->module_id == 6) {
            $store->dedicated_leads = $request->has('dedicated_leads') ? 1 : 0;
            $store->save();
        }
        Toastr::success('Lead settings updated successfully');
        return back();
    }
    public function send_confirmation_notification(Request $request)
    {

        $request_id = $request->get('id');
        $serviceReq = AcceptedServiceRequest::where('service_request_id', $request_id)->where('vendor_id', Helpers::get_store_id())->first();
        $serviceReq->current_status = 'Confirmation Request Sent';
        $serviceReq->quoted_price = $request->get('price');

        DB::table('lead_statuses')->insert([
            'service_request_id' => $request_id,
            'status' => 'Confirmation Request Sent',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $user = DB::table('service_requests')->join('users', 'users.id', 'service_requests.user_id')->where('service_requests.id', $request_id)->select('users.*')->first();
        //   prx( $user);
        if ($user) {
            $fcm_token = $user->cm_firebase_token;
            $data = [
                'title' => "Service Confirmation",
                'description' => "You have recieved a confirmation request from " . Helpers::get_store_data()->name . " for rs." . $request->get('price') . " .",
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


        if ($serviceReq->update()) {
            if ($request->ajax()) return response()->json(['status' => true, 'message' => 'Confirmation request sent successfully!']);
            Toastr::success('Confirmation request sent successfully!');
        } else {
            if ($request->ajax()) return response()->json(['status' => false, 'message' => 'Some error occured']);
            Toastr::error('Some error occured');
        }
        return back();
    }

    public function save_assignment(Request $request)
    {

        $service_id = $request->service_id;
        $assignment_id = $request->id;
        $vid = Helpers::get_store_id();
        $serviceToUpdate = AcceptedServiceRequest::where('id', $assignment_id)->first();
        $serviceToUpdate->assigned_status = 'Assigned';
        $serviceToUpdate->assigned_to =  $request->staff_id == 'vendor' ? Helpers::get_store_id() : $request->staff_id;
        $serviceToUpdate->assigned_type = $request->staff_id == 'vendor' ? 'vendor' : 'staff';
        $serviceToUpdate->assigned_at = date('Y-m-d H:i:s');
        if ($request->staff_id == 'vendor') {
            $staffName = DB::table('stores')->join('vendors', 'vendors.id', 'stores.vendor_id')->where('stores.id', Helpers::get_store_id())->first();
            $to = Helpers::get_store_id();
            $rec_type = 'vendor';
            $nStatus = 'Assigned to  ' . $staffName->f_name . ' ' . $staffName->l_name;

            $serviceReq = AcceptedServiceRequest::where('id', $assignment_id)->first();
            $serviceReq->accepted_by_staff = 1;
            $serviceReq->update();
        } else {
            $staffName = DB::table('vendor_employees')->where('id',  $request->staff_id)->first();
            $to = $request->staff_id;
            $rec_type = 'vendor_employee';
            $nStatus = 'Assigned to  ' . $staffName->f_name . ' ' . $staffName->l_name . ' #' . $request->staff_id;

            $serviceReq = AcceptedServiceRequest::where('id', $assignment_id)->first();
            $serviceReq->accepted_by_staff = 0;
            $serviceReq->update();
        }

        // create job
        $empJob = new VendorEmpJob;
        $empJob->store_id = Helpers::get_store_id();
        $empJob->emp_id = $request->staff_id == 'vendor' ? 0 : $request->staff_id;
        $empJob->service_id = $service_id;
        $empJob->acc_id = $assignment_id;
        $empJob->created_at = date('Y-m-d H:i:s');
        $empJob->save();

        if ($serviceToUpdate->update()) {

            DB::table('lead_statuses')->insert([
                'service_request_id' => $service_id,
                'status' => $nStatus,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $msg = 'New Service Assigned to you';
            $title = 'New Task Assigned';
            $url = route('vendor.service.assigned_services');

            _inAppNotification($title, $msg, $assignment_id, $to, $url, $rec_type);
            if ($request->staff_id == 'vendor') {
                _sendMailToVendor($title, $msg, $to, $url);
            } else {
                _sendMailToStaff($title, $msg, $to, $url);
            }
            if ($request->ajax()) return response()->json(['status' => true, 'message' => 'Assigned successfully!']);
            Toastr::success('Assigned successfully!');
        } else {
            if ($request->ajax()) return response()->json(['status' => false, 'message' => 'Some Error Occured']);
            Toastr::error('Some Error Occured');
        }
        return back();
    }

    public function delete_quote_item($qId)
    {

        $dl = ServiceQuoteItem::where('id', $qId)->delete();
        if ($dl) {
            Toastr::success('Deleted successfully!');
        } else {
            Toastr::error('Some Error Occured');
        }
        return back();
    }

    public function delete_gatepass_item($gpId)
    {

        $dl = GatePassItem::where('id', $gpId)->delete();
        if ($dl) {
            Toastr::success('Deleted successfully!');
        } else {
            Toastr::error('Some Error Occured');
        }
        return back();
    }
    public function task_action(Request $request, $service_id,  $action, $acc_id)
    {

        $empId = Helpers::get_loggedin_user()->id;

        $serviceReq = AcceptedServiceRequest::where('service_request_id', $service_id)->first();
        if ($action == 'accept') {

            $empJob = new VendorEmpJob;
            $empJob->store_id = Helpers::get_store_id();
            $empJob->emp_id = $empId;
            $empJob->service_id = $service_id;
            $empJob->acc_id = $acc_id;
            $empJob->created_at = date('Y-m-d H:i:s');
            $empJob->save();
            $serviceReq->accepted_by_staff = 1;
        } else if ($action == 'reject') {

            $serviceToUpdate = AcceptedServiceRequest::where('service_request_id', $service_id)->first();
            $serviceToUpdate->assigned_status = 'Unassigned';
            $serviceToUpdate->assigned_to = NULL;
            $serviceToUpdate->assigned_at = NULL;
            $serviceToUpdate->update();

            $serviceReq->accepted_by_staff = 0;
        }
        if ($serviceReq->update()) {
            $staffName = DB::table('vendor_employees')->where('id',  $empId)->first();

            DB::table('lead_statuses')->insert([
                'service_request_id' => $service_id,
                'acc_id' => $acc_id,
                'status' =>  $staffName->f_name . ' ' . $staffName->l_name . ' #' . $empId . ' ' . ucfirst($action) . 'ed task',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $storeId = Helpers::get_store_id();
            $sName   = $staffName ? trim($staffName->f_name . ' ' . $staffName->l_name) : 'Staff';
            if ($action == 'reject') {
                _inAppNotification(
                    'Lead Rejected',
                    "{$sName} has rejected the assigned lead #" . $service_id,
                    null,
                    $storeId,
                    null,
                    'vendor'
                );
            } elseif ($action == 'accept') {
                _inAppNotification(
                    'Lead Accepted',
                    "{$sName} has accepted the assigned lead #" . $service_id,
                    null,
                    $storeId,
                    null,
                    'vendor'
                );
            }

            Toastr::success(ucfirst($action) . 'ed successfully!');
        } else {
            Toastr::error('Some Error Occured');
        }
        return back();
    }


    public function leads_list(Request $request, $empId = null, $action = null, $mode = null)
    {
        $preset = request('date_range') ?? 'this_year';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];
        $from = $range['start']->toDateString();
        $to  = $range['end']->toDateString();
        $search = request()->get('search') ?? '';


        $storeId = Helpers::get_store_id();
        $store_data = Helpers::get_store_data();
        $allStaff = DB::table('vendor_employees')->where('store_id', $storeId)->where('status', 1)->get();
        $type = $request->type ?? '';
        $query = DB::table('service_requests')
            ->join('items', 'service_requests.item_id', '=', 'items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->join('users', 'service_requests.user_id', '=', 'users.id')
            ->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [$storeId])
            ->when($search, function ($q) use ($search) {
                $q->where('items.name', 'like', '%' . $search . '%');
            });
        $query->where('service_requests.created_at', '>=', now()->subYear());

        if (!$empId) {
            $query->whereBetween('service_requests.created_at', [$formatted_from, $formatted_to]);
        }

        $query->select(
            'service_requests.*',
            'items.name as item_name',
            'items.image as image',
            'categories.name as category_name',
            'users.f_name as f_name',
            'users.id as uid'
        );
        if ($type == 'New' || $type ==  'Missed') {
            $query->leftJoin('accepted_service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
                ->where(function ($query) use ($storeId) {
                    $query->whereRaw('NOT FIND_IN_SET(?, service_requests.accepted_by)', [$storeId])
                        ->orWhereNull('service_requests.accepted_by');
                });
            if ($type == 'New') {
                $query->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [$storeId])->where('service_requests.created_at', '>', now()->subMinutes(Helpers::get_lead_exp_minutes()));
            } else {
                $query->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [$storeId])->where('service_requests.created_at', '<', now()->subMinutes(Helpers::get_lead_exp_minutes()));
                // ->whereNull('accepted_service_requests.id');
                $query->addSelect(
                    DB::raw("'missed' as additional_status")
                );
            }
            $query->addSelect(
                'accepted_service_requests.assigned_status',
                'accepted_service_requests.current_status',
                'accepted_service_requests.assigned_type',
                'accepted_service_requests.id as acc_id',
                'accepted_service_requests.assigned_to',
                'accepted_service_requests.accepted_by_staff'
            );
        } elseif ($type == 'Accepted') {
            $query->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
                ->where(function ($query) {
                    $query->whereNull('accepted_service_requests.tieup')
                        ->orWhere('accepted_service_requests.current_status', 'Confirmation Request Sent');
                })
                ->where('accepted_service_requests.vendor_id', $storeId)
                ->addSelect(
                    'accepted_service_requests.assigned_status',
                    'accepted_service_requests.assigned_type',
                    'accepted_service_requests.current_status',
                    'accepted_service_requests.id as acc_id',
                    'accepted_service_requests.assigned_to',
                    'accepted_service_requests.accepted_by_staff'
                );
        } elseif ($type == 'Cancelled') {
            $query->join('cancelled_service_requests', 'service_requests.id', '=', 'cancelled_service_requests.service_request_id')
                ->join('stores', 'stores.id', '=', 'cancelled_service_requests.vendor_id')
                ->where('cancelled_service_requests.vendor_id', $storeId)
                ->addSelect(
                    'cancelled_service_requests.assigned_type',
                    'cancelled_service_requests.assigned_status',
                    'cancelled_service_requests.current_status',
                    'cancelled_service_requests.assigned_to',
                    'cancelled_service_requests.accepted_by_staff',
                    'cancelled_service_requests.id as acc_id'
                );
        } elseif (!$type || $type == 'All' || $empId) {

            $query->leftJoin('accepted_service_requests', function ($join) use ($storeId) {
                $join->on('service_requests.id', '=', 'accepted_service_requests.service_request_id')
                    ->where('accepted_service_requests.vendor_id', '=', $storeId);
            })
                ->leftJoin('cancelled_service_requests', function ($join) use ($storeId) {

                    $join->on('service_requests.id', '=', 'cancelled_service_requests.service_request_id')
                        ->where('cancelled_service_requests.vendor_id', '=', $storeId);
                });


            if ($mode === 'assigned') {
                $assignedEmpId = Helpers::get_loggedin_user()->id;
                $query->where(function ($q) use ($assignedEmpId) {
                    $q->where('accepted_service_requests.assigned_to', $assignedEmpId)
                      ->orWhere('cancelled_service_requests.assigned_to', $assignedEmpId);
                });
            } elseif (!is_null($empId) && $empId != 0) {
                $query->where(function ($q) use ($empId) {
                    $q->where('cancelled_service_requests.assigned_to', $empId)
                        ->orWhere('accepted_service_requests.assigned_to', $empId);
                });
            }
            $query->where(function ($q) {
                $q->whereNotNull('accepted_service_requests.service_request_id')
                    ->orWhereNotNull('cancelled_service_requests.service_request_id')
                    ->orWhere('service_requests.created_at', '>=', now()->subMinutes(Helpers::get_lead_exp_minutes()))
                    ->orWhere(function ($query) {
                        $query->whereNull('accepted_service_requests.service_request_id')
                            ->whereNull('cancelled_service_requests.service_request_id')
                            ->where('service_requests.created_at', '<', now()->subMinutes(Helpers::get_lead_exp_minutes()));
                    });
            })
                ->addSelect(
                    DB::raw('COALESCE(accepted_service_requests.assigned_status, cancelled_service_requests.assigned_status) as assigned_status'),
                    DB::raw('COALESCE(accepted_service_requests.current_status, cancelled_service_requests.current_status) as current_status'),
                    DB::raw('COALESCE(accepted_service_requests.assigned_type, cancelled_service_requests.assigned_to) as assigned_type'),
                    DB::raw('COALESCE(accepted_service_requests.assigned_to, cancelled_service_requests.assigned_to) as assigned_to'),
                    DB::raw('COALESCE(accepted_service_requests.accepted_by_staff, cancelled_service_requests.accepted_by_staff) as accepted_by_staff'),
                    DB::raw('COALESCE(accepted_service_requests.id, cancelled_service_requests.id) as acc_id'),

                    // Add the missed status when conditions match
                    DB::raw("CASE 
                    WHEN service_requests.created_at < '" . now()->subMinutes(Helpers::get_lead_exp_minutes()) . "' 
                        AND accepted_service_requests.id IS NULL 
                        AND cancelled_service_requests.id IS NULL 
                        AND FIND_IN_SET($storeId, service_requests.sent_to) > 0 
                    THEN 'missed' 
                    ELSE NULL 
                 END as additional_status")

                );

            // Add direct columns from accepted_service_requests
            $query->addSelect(
                'accepted_service_requests.assigned_status',
                'accepted_service_requests.current_status',
                'accepted_service_requests.assigned_type',
                'accepted_service_requests.id as acc_id',
                'accepted_service_requests.assigned_to',
                'accepted_service_requests.accepted_by_staff'
            );
        } else {

            $query->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
                ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id');
            if ($type == 'In Progress') {
                $query->where('accepted_service_requests.status', 9);
            } else {
                $query->where('accepted_service_requests.current_status', $type);
            }
            $query->where('accepted_service_requests.vendor_id', $storeId);

            $query->addSelect(
                'accepted_service_requests.assigned_type',
                'accepted_service_requests.assigned_status',
                'accepted_service_requests.current_status',
                'accepted_service_requests.id as acc_id',
                'accepted_service_requests.assigned_to',
                'accepted_service_requests.accepted_by_staff'
            );
        }
        // prx($query->get());

        $query->groupBy('service_requests.id');
        $query->orderBy('service_requests.created_at', 'desc');
        $product = $query->get();


        if ($action == 'export') {
            return $this->export_leads($product);
        }
        // $sql = Str::replaceArray('?', $query->getBindings(), $query->toSql());

        $avlblSttsIds = Helpers::get_store_data()->lead_statuses;

        $statuses = DB::table('service_statuses')->where('removable', 1)->where(function ($q) use ($storeId) { $q->where('store_id', $storeId); })->get();

        $default_statuses = DB::table('service_statuses')->where('removable', 0)->get();
        $approval_pending = TempStoreStatus::with('serviceStatus')
            ->where('store_id', Helpers::get_store_id())
            ->get();
        if ($request->ajax()) {
            return view('vendor-views.product._leads_grid', compact('product', 'allStaff', 'statuses', 'default_statuses', 'store_data'));
        }

        $storeConfig = \App\Models\StoreConfig::firstOrCreate(['store_id' => $storeId]);

        // ── Inline dashboard stats ────────────────────────────────
        $between = [$formatted_from, $formatted_to];
        $ld_total = DB::table('service_requests')
            ->whereRaw("FIND_IN_SET(?, sent_to)", [$storeId])
            ->whereBetween('created_at', $between)->count();
        $ld_accepted = DB::table('accepted_service_requests')
            ->join('service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
            ->where('accepted_service_requests.vendor_id', $storeId)
            ->whereBetween('service_requests.created_at', $between)->count();
        $ld_completed = DB::table('accepted_service_requests')
            ->join('service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
            ->where('accepted_service_requests.vendor_id', $storeId)
            ->where('accepted_service_requests.current_status', 'Completed')
            ->whereBetween('service_requests.created_at', $between)->count();
        $ld_cancelled = DB::table('cancelled_service_requests')
            ->join('service_requests', 'service_requests.id', '=', 'cancelled_service_requests.service_request_id')
            ->where('cancelled_service_requests.vendor_id', $storeId)
            ->whereBetween('service_requests.created_at', $between)->count();
        $ldExpMins = Helpers::get_lead_exp_minutes();
        $ld_missed = DB::table('service_requests')
            ->whereRaw("FIND_IN_SET(?, sent_to)", [$storeId])
            ->whereBetween('created_at', $between)
            ->where('created_at', '<', now()->subMinutes($ldExpMins))
            ->whereNotExists(fn($q) => $q->from('accepted_service_requests')
                ->whereColumn('service_request_id', 'service_requests.id')
                ->where('vendor_id', $storeId))
            ->whereNotExists(fn($q) => $q->from('cancelled_service_requests')
                ->whereColumn('service_request_id', 'service_requests.id')
                ->where('vendor_id', $storeId))
            ->count();
        $ld_completionRate   = $ld_accepted > 0 ? round($ld_completed / $ld_accepted * 100) : 0;
        $ld_acceptanceRate   = $ld_total    > 0 ? round($ld_accepted  / $ld_total    * 100) : 0;
        $ld_cancellationRate = $ld_accepted > 0 ? round($ld_cancelled / $ld_accepted * 100) : 0;
        $ld_topServices = DB::table('service_requests')
            ->join('items', 'items.id', '=', 'service_requests.item_id')
            ->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [$storeId])
            ->whereBetween('service_requests.created_at', $between)
            ->selectRaw("items.name, COUNT(*) as cnt")
            ->groupBy('items.name')->orderByDesc('cnt')->limit(6)->get();

        $view = _isHospital()
            ? 'vendor-views.hospital.appointment_list'
            : 'vendor-views.product.service_request_list_all';

        return view($view, compact('preset', 'empId', 'approval_pending', 'store_data', 'product', 'type', 'allStaff', 'from', 'to', 'statuses', 'default_statuses', 'storeConfig',
            'ld_total', 'ld_accepted', 'ld_completed', 'ld_cancelled', 'ld_missed',
            'ld_completionRate', 'ld_acceptanceRate', 'ld_cancellationRate', 'ld_topServices'));
    }
    public function assigned_leads_list(Request $request, $empId = null, $action = null)
    {
        return $this->leads_list($request, $empId, $action, 'assigned');
    }

    public function dismissLeadsGuide(Request $request)
    {
        \App\Models\StoreConfig::where('store_id', Helpers::get_store_id())
            ->update(['leads_guide_dismissed' => 1]);
        return response()->json(['status' => true]);
    }

    public function addLeadNote(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $note = trim($request->note ?? '');

        if ($note === '') {
            \App\Models\LeadNote::where('service_id', $request->service_id)->where('store_id', $storeId)->delete();
        } else {
            \App\Models\LeadNote::updateOrCreate(
                ['service_id' => $request->service_id, 'store_id' => $storeId],
                ['note' => $note, 'remind_at' => $request->filled('remind_at') ? $request->remind_at : null, 'notified_at' => null]
            );
        }
        return response()->json(['status' => true]);
    }

    public function addCustomStatus(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $name = trim($request->name);

        if (!$name) {
            return response()->json(['status' => false, 'message' => 'Status name is required.']);
        }

        $existing = DB::table('service_statuses')
            ->where('status', $name)
            ->where(function ($q) use ($storeId) {
                $q->whereNull('store_id')->orWhere('store_id', $storeId);
            })->first();

        if ($existing) {
            return response()->json(['status' => true, 'id' => $existing->id, 'name' => $existing->status]);
        }

        $id = DB::table('service_statuses')->insertGetId([
            'status'     => $name,
            'removable'  => 1,
            'store_id'   => $storeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true, 'id' => $id, 'name' => $name]);
    }

    public function sendCompletionOtp(Request $request)
    {
        $serviceId = $request->service_id;
        $serviceDet = DB::table('service_requests')->where('id', $serviceId)->first();
        $user = User::find($serviceDet->user_id ?? null);

        if (!$user || !$user->phone) {
            return response()->json(['status' => false, 'message' => 'Customer phone not found.']);
        }

        $check = _check_otp_send_allowed($user->phone);
        if (!$check['allowed']) {
            return response()->json(['status' => false, 'message' => $check['message']]);
        }

        $otp = rand(1000, 9999);
        _store_otp($user->phone, $otp);

        $data = [
            'title' => 'Confirm Job Completion',
            'description' => "Your service has been completed.\nPlease confirm the job by sharing this OTP: {$otp}\nThank you for choosing us!",
            'order_id' => $serviceId,
            'image' => '',
            'type' => 'block',
        ];
        Helpers::send_push_notif_to_device($user->cm_firebase_token, $data, '');
        DB::table('user_notifications')->insert([
            'data' => json_encode($data),
            'user_id' => $user->id,
            'type' => 'service',
            'type_id' => $serviceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        _send_confirmation_sms('job_msg', $user->phone, $otp);

        return response()->json(['status' => true, 'message' => 'OTP sent to customer.']);
    }

    public function getLeadCard($id)
    {
        $storeId = Helpers::get_store_id();
        $store_data = Helpers::get_store_data();
        $allStaff = DB::table('vendor_employees')->where('store_id', $storeId)->where('status', 1)->get();
        $statuses = DB::table('service_statuses')->where('removable', 1)->where(function ($q) use ($storeId) { $q->whereNull('store_id')->orWhere('store_id', $storeId); })->get();
        $default_statuses = DB::table('service_statuses')->where('removable', 0)->get();

        $lead = DB::table('service_requests')
            ->join('items', 'service_requests.item_id', '=', 'items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->join('users', 'service_requests.user_id', '=', 'users.id')
            ->leftJoin('accepted_service_requests', function ($join) use ($storeId) {
                $join->on('service_requests.id', '=', 'accepted_service_requests.service_request_id')
                    ->where('accepted_service_requests.vendor_id', '=', $storeId);
            })
            ->leftJoin('cancelled_service_requests', function ($join) use ($storeId) {
                $join->on('service_requests.id', '=', 'cancelled_service_requests.service_request_id')
                    ->where('cancelled_service_requests.vendor_id', '=', $storeId);
            })
            ->where('service_requests.id', $id)
            ->select(
                'service_requests.*',
                'items.name as item_name',
                'items.image as image',
                'categories.name as category_name',
                'users.f_name as f_name',
                'users.id as uid',
                DB::raw('COALESCE(accepted_service_requests.assigned_status, cancelled_service_requests.assigned_status) as assigned_status'),
                DB::raw('COALESCE(accepted_service_requests.current_status, cancelled_service_requests.current_status) as current_status'),
                DB::raw('COALESCE(accepted_service_requests.assigned_type, cancelled_service_requests.assigned_to) as assigned_type'),
                DB::raw('COALESCE(accepted_service_requests.assigned_to, cancelled_service_requests.assigned_to) as assigned_to'),
                DB::raw('COALESCE(accepted_service_requests.accepted_by_staff, cancelled_service_requests.accepted_by_staff) as accepted_by_staff'),
                DB::raw('COALESCE(accepted_service_requests.id, cancelled_service_requests.id) as acc_id'),
                DB::raw("CASE
                    WHEN service_requests.created_at < '" . now()->subMinutes(Helpers::get_lead_exp_minutes()) . "'
                        AND accepted_service_requests.id IS NULL
                        AND cancelled_service_requests.id IS NULL
                        AND FIND_IN_SET($storeId, service_requests.sent_to) > 0
                    THEN 'missed'
                    ELSE NULL
                END as additional_status")
            )
            ->first();

        if (!$lead) {
            return response('', 404);
        }

        return view('vendor-views.product._lead_card', compact('lead', 'allStaff', 'statuses', 'default_statuses', 'store_data'));
    }

    public function export_leads($products)
    {

        $headings = [
            'Sl',
            'ID',
            'Service Name',
            'Customer Name',
            'Customer Phone',
            'Requested At',
            'Status',
        ];
        $rows = [];
        foreach ($products as $key => $lead) {
            $finalStatus = _leadFinalStatus($lead);
            $user_details = _getUserDetails($lead->uid);
            $rows[] = [
                $key + 1,
                $lead->id,
                $lead->item_name,
                $user_details->f_name . ' ' . $user_details->l_name,
                $user_details->phone,
                $lead->created_at,
                $finalStatus ?? '',

            ];
        }
        return Excel::download(new ServiceLeadsExport($rows, $headings), 'service_leads.xlsx');
    }
    public function assigned_projects()
    {
        if (auth('vendor_employee')->check()) {
            $empId = Helpers::get_loggedin_user()->id;
            // prx($empId);

            $projects = DB::table('projects')->whereRaw("FIND_IN_SET(?, team_members)", [$empId])->where('vendor_id', Helpers::get_store_id())->get();

            return view('vendor-views.project.assigned-projects', compact('projects'));
        } else {
            echo 'Access Denied';
        }
    }
    public function assigned_services()
    {
        if (auth('vendor_employee')->check()) {
            $empId = Helpers::get_loggedin_user()->id;

            $assignedServices = DB::table('accepted_service_requests')
                ->join('service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
                ->join('items', 'items.id', 'service_requests.item_id')
                ->join('users', 'users.id', 'service_requests.user_id')
                ->select('service_requests.id as service_id', 'items.name as item_name', 'items.image as item_image',  'accepted_service_requests.*', 'users.f_name', 'users.l_name', 'users.phone')
                ->where('accepted_service_requests.assigned_to', $empId)
                ->whereNot('accepted_service_requests.current_status', 'Cancelled')
                ->get();
            $avlblSttsIds = Helpers::get_store_data()->lead_statuses;
            $statuses = [];
            if ($avlblSttsIds) {
                $arr = explode(',', $avlblSttsIds);

                if (!empty($arr) && is_array($arr)) {
                    $statuses = DB::table('service_statuses')->whereIn('id', $arr)->get();
                }
            }
            $default_statuses = DB::table('service_statuses')->where('removable', 0)->get();
            return view('vendor-views.service.assigned-services', compact('assignedServices', 'statuses', 'default_statuses'));
        } else {
            echo 'Access Denied';
        }
    }
    public function change_status(Request $request)
    {

        $service_id = $request->service_id;
        $stts_id = $request->status;
        $serviceReq = AcceptedServiceRequest::where('service_request_id', $service_id)->first();

        if ($stts_id == 14) { // for cancel 
            if (auth('vendor')->check()) {
                $cancelled_by = 'Vendor';
            } else {
                $cancelled_by = 'Staff';
            }

            $serviceReq->current_status = 'Cancelled';
            $serviceReq->cancelled_by = $cancelled_by;
            $serviceReq->update();

            $empJob = VendorEmpJob::where('service_id', $service_id)->first();
            $empJob->ended_at = NOW();
            $empJob->update();

            $serviceReq->accepted_by_staff = 1;
        } else if ($stts_id == 12) { // completed 

            $service_id = $request->service_id;
            $serviceReq->completed_at = NOW();
            $serviceReq->update();

            // send sms here
            // get user mobile
            $userPhone = User::find($serviceReq->user_id);
            if ($userPhone) {
                $otp  = rand(1000, 9999);
                $insert  = DB::table('phone_otp')->updateOrInsert([
                    'phone' =>  $userPhone,
                ], [
                    'otp' => $otp,
                    'created_at' => now()
                ]);
            }
            $empJob = VendorEmpJob::where('service_id', $service_id)->first();
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
                'type_id' => $service_id,
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
    }

    public function details($id)
    {
        // prx(Helpers::get_store_id());
        $serviceDetails =  DB::table('accepted_service_requests')
            ->join('service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('accepted_service_requests.service_request_id', $id)
            ->where('accepted_service_requests.vendor_id', Helpers::get_store_id())
            ->select('service_requests.id as service_id', 'items.name as item_name', 'items.image as item_image',  'accepted_service_requests.*')
            ->first();

        $allGatepasses  = GatePass::where('service_id', $id)->first();
        if (!empty($allGatepasses)) {
            $gpItems = DB::table('gate_pass_items')->where('gatepass_id', $allGatepasses->id)->get();
        } else {
            $gpItems = [];
        }

        return view('vendor-views.service.details', compact('serviceDetails', 'allGatepasses', 'gpItems'));
    }

    public function  gatepass_update(Request $request)
    {
        $request->validate([
            'desc' => 'max:500',
            'title.*' => 'required|max:500',
            'image.*.*' => 'required|image|mimes:jpg,jpeg,png|max:30720'
        ], [
            'title.*.required' => 'Title is required',
            'image.*.*.required' => 'Image is required'
        ]);

        $saved = false;
        $gatepass = GatePass::where('service_id', $request->service_id)->first();
        $isRecreate = $request->has('gp_status') && $request->gp_status == 'rejected';

        if ($isRecreate) {
            $gatepass->approved = 0;
            $gatepass->created_at = date('Y-m-d H:i:s');
            $gatepass->updated_at = date('Y-m-d H:i:s');
            $gatepass->update();
        }

        $statusText = $isRecreate ? 'Gatepass Recreated' : 'Gatepass Updated';
        $descriptionText = $isRecreate ? 'Gatepass recreated' : 'Gatepass updated';

        DB::table('lead_statuses')->insert([
            'service_request_id' => $request->service_id,
            'status' => $statusText,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->sendServiceNotification($request->service_id, 'Service Gatepass', $descriptionText);

        //existing items 
        foreach ($request->ids as $key => $item) {
            $gatepassItem  =  GatePassItem::find($item);

            if (isset($request->existing_image[$key]) && is_array($request->existing_image[$key])) {
                if (is_array(json_decode($gatepassItem->image))) {
                    $image_names = json_decode($gatepassItem->image);
                } else {
                    $image_names[] = $gatepassItem->image;
                }

                foreach ($request->existing_image[$key] as $img) {
                    $image_name = Helpers::upload('gatepass/', 'png', $img);
                    $image_names[] = $image_name;
                }

                // You can either save as JSON or handle as needed
                $gatepassItem->image = json_encode($image_names);
            }


            $gatepassItem->gatepass_id = $gatepass->id;
            $gatepassItem->title = $request->title[$key];
            $gatepassItem->description = $request->desc[$key];
            $gatepassItem->created_at = date('Y-m-d H:i:s');
            if ($gatepassItem->update()) {
                $saved = true;
            } else {
                $saved = false;
            }
        }

        //. new items 
        if ($request->new_item) {

            foreach ($request->new_item as $key => $item) {
                $images = [];

                if ($request->hasFile("image.$key")) {
                    foreach ($request->image[$key] as $img) {
                        if ($img) {
                            $images[] = Helpers::upload('gatepass/', 'png', $img);
                        }
                    }
                }

                $gatepassItem  = new GatePassItem;
                $gatepassItem->gatepass_id = $gatepass->id;
                $gatepassItem->title = $request->title[$key];
                $gatepassItem->image = json_encode($images);
                $gatepassItem->description = $request->desc[$key];
                $gatepassItem->created_at = date('Y-m-d H:i:s');
                if ($gatepassItem->save()) {
                    $saved = true;
                } else {
                    $saved = false;
                }
            }
        }

        if ($saved) {
            Toastr::success('Gatepass updated successfully!');
        } else {
            Toastr::error('Some Error Occured');
        }
        return back();
    }

    public function quotations($id)
    {
        $serviceDetails =  DB::table('accepted_service_requests')
            ->join('service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('accepted_service_requests.service_request_id', $id)
            ->where('accepted_service_requests.vendor_id', Helpers::get_store_id())
            ->select('service_requests.id as service_id', 'items.name as item_name', 'items.image as item_image',  'accepted_service_requests.*')
            ->first();

        $allQuotations = DB::table('in_service_quotations')->where('service_id', $id)->first();
        if (!empty($allQuotations)) {
            $quoteItems = DB::table('service_quote_items')->where('quote_id', $allQuotations->id)->get();
            // print_r($quoteItems);die;

        } else {
            $quoteItems = [];
        }

        return view('vendor-views.service.quotations', compact('serviceDetails', 'allQuotations', 'quoteItems'));
    }


    public function gatepass_add(Request $request)
    {
        $request->validate([
            'desc' => 'max:500',
            'title.*' => 'required|max:500',
            'image.*.*' => 'required|image|mimes:jpg,jpeg,png|max:30720'
        ], [
            'title.*.required' => 'Title is required',
            'image.*.*.required' => 'Image is required'
        ]);

        $accDet = AcceptedServiceRequest::find($request->acc_id);
        if ($accDet->assign_type == 'vendor') {
            $empId = Helpers::get_store_id();
            $assign_type = 'vendor';
        } else {
            $empId = Helpers::get_loggedin_user()->id;
            $assign_type = 'staff';
        }
        $saved = false;
        $gatepass = new GatePass;
        $gatepass->emp_id = $empId;
        $gatepass->assign_type = $assign_type;
        $gatepass->service_id = $request->service_id;
        $gatepass->accepted_service_id = $request->acc_id;
        // $gatepass->description = $request->desc; 
        // $gatepass->image = $image_name; 
        $gatepass->created_at = date('Y-m-d H:i:s');

        if ($gatepass->save()) {
            foreach ($request->title as $key => $item) {
                $images = [];

                if ($request->hasFile("image.$key")) {
                    foreach ($request->image[$key] as $img) {
                        if ($img) {
                            $images[] = Helpers::upload('gatepass/', 'png', $img);
                        }
                    }
                }

                $gatepassItem = new GatePassItem;
                $gatepassItem->image = json_encode($images);

                $gatepassItem->gatepass_id = $gatepass->id;
                $gatepassItem->title = $request->title[$key];
                $gatepassItem->description = $request->desc[$key];
                $gatepassItem->created_at = date('Y-m-d H:i:s');
                if ($gatepassItem->save()) {
                    $saved = true;
                } else {
                    $saved = false;
                }
            }
            DB::table('lead_statuses')->insert([
                'service_request_id' => $request->service_id,
                'status' => 'Gatepass Created',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $request_id = $request->service_id;
            $service_requests = ServiceRequest::where('id', $request->service_id)->first();
            $item = DB::table('items')->where('id', $service_requests->item_id)->first();
            $user = DB::table('service_requests')->join('users', 'users.id', 'service_requests.user_id')->where('service_requests.id', $request_id)->select('users.*')->first();
            if ($user) {
                $fcm_token = $user->cm_firebase_token;
                $data = [
                    'title' => "Service Gatepass",
                    'description' => "Gatepass created for " . $item->name . ".",
                    'order_id' => $request_id,
                    'image' => '',
                    'type' => 'block'
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'user_id' => $user->id,
                    'type' => 'service',
                    'type_id' => $request->service_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }


        if ($saved) {
            Toastr::success('Gatepass saved successfully!');
        } else {
            Toastr::error('Some Error Occured');
        }
        return back();
    }
    public function quotation_update(Request $request)
    {
        $request->validate([
            'item_name.*'  => 'required|max:500',
            'item_price.*' => 'required|integer',
            'item_tax.*'   => 'required|integer',
        ]);

        $saved = false;

        $quotation = InServiceQuotation::where('service_id', $request->service_id)->first();

        // delete old items
        ServiceQuoteItem::where('quote_id', $quotation->id)->delete();

        // reset approval
        $quotation->approved = 0;
        $quotation->save();

        // ✅ totals
        $subTotal = 0;
        $taxTotal = 0;

        foreach ($request->item_name as $key => $item) {

            $price = $request->item_price[$key];
            $qty   = $request->item_qty[$key] ?? 1;
            $tax   = $request->item_tax[$key];

            // calculations
            $lineSubTotal = $price * $qty;
            $lineTax      = ($lineSubTotal * $tax) / 100;

            $subTotal += $lineSubTotal;
            $taxTotal += $lineTax;

            $quotationItem  = new ServiceQuoteItem;
            $quotationItem->quote_id = $quotation->id;
            $quotationItem->price = $price;
            $quotationItem->name = $item;
            $quotationItem->qty = $qty;
            $quotationItem->tax = $tax;
            $quotationItem->total = $lineTax + $lineSubTotal;
            $quotationItem->created_at = date('Y-m-d H:i:s');

            if ($quotationItem->save()) {
                $saved = true;
            }
        }

        // ✅ update totals in quotation
        $grandTotal = $subTotal + $taxTotal;

        $quotation->sub_total    = $subTotal;
        $quotation->tax_total   = $taxTotal;
        $quotation->total = $grandTotal;
        $quotation->save();

        if ($saved) {
            $this->sendServiceNotification($request->service_id, 'Service Quotation', 'Quotation updated');
            Toastr::success('Quotations updated successfully!');
        } else {
            Toastr::error('Some Error Occured');
        }

        return back();
    }

    public function quotation_add(Request $request)
    {
        $request->validate([
            'item_name.*' => 'required|max:500',
            'item_price.*' => 'required|integer',
            'item_tax.*' =>  'required|integer',
        ]);

        $saved = false;

        $accDet = AcceptedServiceRequest::find($request->acc_id);
        if ($accDet->assign_type == 'vendor') {
            $empId = Helpers::get_store_id();
            $assign_type = 'vendor';
        } else {
            $empId = Helpers::get_loggedin_user()->id;
            $assign_type = 'staff';
        }

        $quotation = new InServiceQuotation;
        $quotation->emp_id = $empId;
        $quotation->assign_type = $assign_type;
        $quotation->service_id = $request->service_id;
        $quotation->acc_id = $request->acc_id;
        $quotation->created_at = date('Y-m-d H:i:s');

        if ($quotation->save()) {
            $saved = true;
            $subTotal  = 0;
            $taxTotal  = 0;

            foreach ($request->item_name as $key => $item) {

                $price = $request->item_price[$key];
                $qty   = $request->item_qty[$key] ?? 1;
                $tax   = $request->item_tax[$key];

                $lineSubTotal = $price * $qty;
                $lineTax      = ($lineSubTotal * $tax) / 100;

                $subTotal += $lineSubTotal;
                $taxTotal += $lineTax;

                $quotationItem  = new ServiceQuoteItem;
                $quotationItem->quote_id = $quotation->id;
                $quotationItem->price = $price;
                $quotationItem->name = $item;
                $quotationItem->qty = $qty;
                $quotationItem->tax = $tax;
                $quotationItem->total = $lineTax + $lineSubTotal;

                $quotationItem->created_at = date('Y-m-d H:i:s');
                $quotationItem->save();
            }

            $grandTotal = $subTotal + $taxTotal;
            $quotation->total = $grandTotal;
            $quotation->sub_total = $subTotal;
            $quotation->tax_total = $taxTotal;
            $quotation->update();

            DB::table('lead_statuses')->insert([
                'service_request_id' => $request->service_id,
                'status' => 'Quotation Created',
                'created_at' => date('Y-m-d H:i:s')
            ]);


            $request_id = $request->service_id;
            $service_requests = ServiceRequest::where('id', $request->service_id)->first();
            $item = DB::table('items')->where('id', $service_requests->item_id)->first();
            $user = DB::table('service_requests')->join('users', 'users.id', 'service_requests.user_id')->where('service_requests.id', $request_id)->select('users.*')->first();
            if ($user) {
                $fcm_token = $user->cm_firebase_token;
                $data = [
                    'title' => "Service Quotation",
                    'description' => "Quotation created for " . $item->name . ".",
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
        }


        if ($saved) {
            Toastr::success('Quotations saved successfully!');
        } else {
            Toastr::error('Some Error Occured');
        }
        return back();
    }

    public function gatepass_return($gpId)
    {
        $gp =  GatePass::find($gpId);
        $gp->returned = 1;
        if ($gp->update()) {
            Toastr::success('Return claimed successfully!');
        } else {
            Toastr::error('Some Error Occured');
        }
        return back();
    }

    private function sendServiceNotification($serviceId, $title, $description)
    {
        $service = ServiceRequest::where('id', $serviceId)->first();
        $item = DB::table('items')->where('id', $service->item_id)->first();
        $user = DB::table('service_requests')
            ->join('users', 'users.id', 'service_requests.user_id')
            ->where('service_requests.id', $serviceId)
            ->select('users.*')
            ->first();

        $data = [
            'title' => $title,
            'description' => $description . ' for ' . $item->name . '.',
            'order_id' => $serviceId,
            'image' => '',
            'type' => 'block'
        ];

        if ($user) {
            Helpers::send_push_notif_to_device($user->cm_firebase_token, $data);
            DB::table('user_notifications')->insert([
                'data' => json_encode($data),
                'user_id' => $user->id,
                'type' => 'service',
                'type_id' => $serviceId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
