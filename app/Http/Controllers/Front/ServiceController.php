<?php

namespace App\Http\Controllers\Front;

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
use App\Models\CustomerAddress;
use App\Models\GatePassItem;
use App\Models\LeadCharge;
use App\Models\LeadStatus;
use App\Models\Quotation;
use App\Models\ServiceQuoteItem;
use App\Models\StoreWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;



class ServiceController extends Controller
{

    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'acceptance_id' => 'required',
            'service_id' => 'required',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        // service request user id showuld be current user id 
        $aexist =  AcceptedServiceRequest::where('service_request_id', $request->service_id)->where('tieup', 1)->exists();

        if ($aexist) {
            return response()->json(['status' => false, 'message' => 'Already Confirmed']);
        } else {
            $acceptedReq = AcceptedServiceRequest::where('id', $request->acceptance_id)->first();
            $applyCharges = false;
            $confirmationCharges = 0;
            try {
                $zoneId = Store::withoutGlobalScopes()->where('id', $acceptedReq->vendor_id)->value('zone_id');
                $serviceReq = ServiceRequest::where('service_requests.id', $request->service_id)
                    ->join('items', 'items.id', '=', 'service_requests.item_id')
                    ->select('items.category_id', 'service_requests.item_id')
                    ->first();
                $catId = $serviceReq->category_id;
                $itemId = $serviceReq->item_id; 

                // Try service-specific charge first, then fall back to category-level
                $leadChargeInfo = LeadCharge::where('category_id', $catId)->where('zone_id', $zoneId)
                    ->where('item_id', $itemId)->first()
                    ?? LeadCharge::where('category_id', $catId)->where('zone_id', $zoneId)
                    ->whereNull('item_id')->first();
                $confirmationCharges = $leadChargeInfo->confirmation_charge;
 
                $applyCharges = true;
            } catch (\Throwable $th) {
            }

            if ($acceptedReq->current_status == null) {
                return response()->json(['status' => false, 'message' => 'No quotation available from store yet']);
            }
            $acceptedReq->current_status = 'Confirmed';
            $acceptedReq->confirmed_at = date('Y-m-d H:i:s');

            $upd = AcceptedServiceRequest::where('service_request_id', $request->service_id)->update(['tieup' => 1]);

            if ($acceptedReq->update()) {

                // apply charges ============================
                if ($applyCharges && !\App\CentralLogics\Helpers::store_has_active_lead_subscription((int)$acceptedReq->vendor_id)) {
                    try {
                        $vendor_id = Store::where('id', $acceptedReq->vendor_id)->value('vendor_id');

                        $wallet = StoreWallet::where('vendor_id', $vendor_id)->first();
                        $wallet->decrement('total_earning', $confirmationCharges);
                        $wallet->increment('total_withdrawn', $confirmationCharges);
                        $wallet->save();

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
                $html = view('front-views.partials.dashboard._service-actions-element', compact('acceptedReq'))->render();
                return response()->json(['status' => true, 'message' => 'Confirmed Successfully', 'html' => $html]);
            } else {
                return response()->json(['status' => false, 'message' => 'Some error occured']);
            }
        }
    }
    public function gatepass_details(Request $request)
    {
        $serviceId = $request->service_id;
        $gatepass = GatePass::where('service_id', $serviceId)->first();

        if (!$gatepass) {
            return response()->json(['status' => false, 'message' => 'Gatepass not found'], 404);
        }
        $gatepass_items = GatePassItem::where('gatepass_id', $gatepass->id)->get();
        $html = view('front-views.partials.dashboard.service._gatepass', compact(
            'gatepass',
            'gatepass_items'
        ))->render();

        return response()->json(['status' => true, 'html' => $html]);
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
            $request->action = 'reject';
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
            $html = view('front-views.partials.dashboard.service._gatepass_cta', ['gatepass' => $gatepass])->render();
            return response()->json(['status' => true, 'message' => ucfirst($request->action) . 'ed Successfully', 'html' => $html]);
        } else {
            return response()->json(['status' => false, 'message' => 'Some error occured']);
        }
    }

    public function gatepass_return_approval(Request $request)
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
            $html = view('front-views.partials.dashboard.service._gatepass_cta', ['gatepass' => $gatepass])->render();

            return response()->json(['status' => true, 'message' => 'Confirmed Successfully',  'html' => $html]);
        } else {
            return response()->json(['status' => false, 'message' => 'Some error occured']);
        }
    }
    public function quotation_details(Request $request)
    {
        $serviceId = $request->service_id;
        $quotation = InServiceQuotation::with('items')->where('service_id', $serviceId)->first();

        if (!$quotation) {
            return response()->json(['status' => false, 'message' => 'Quotation not found'], 404);
        }
        $html = view('front-views.partials.dashboard.service._quotation', compact('quotation'))->render();

        return response()->json(['status' => true, 'html' => $html]);
    }
    public function quotation_approval(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quote_id' => 'required',
            'action' => 'required', // approve or reject
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $quotation = InServiceQuotation::find($request->quote_id);
        // prx($quotation);
        $acceptedReq = AcceptedServiceRequest::where('id', $quotation->acc_id)->first();
        if (in_array($acceptedReq->current_status, ['Completed', 'Cancelled', 'Rejected'])) {
            return response()->json(['status' => false, 'message' => 'Service is ' . ucfirst($acceptedReq->current_status)]);
        }
        if ($request->action == 'approve') {
            $quotation->approved = 1;
            $request->action = 'approv';
            $storestts = 'Approved';
        } else {
            $quotation->approved = 2; // 2 for reject
            $storestts = 'Rejected';
        }

        if ($quotation->update()) {


            DB::table('lead_statuses')->insert([
                'service_request_id' => $quotation->service_id,
                'status' => 'Quotation ' . $storestts,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $title = 'Quotation ' . ucfirst($request->action) . 'ed';
            $msg = 'Customer has ' . ucfirst($request->action) . 'ed the quotaion';
            $assignment_id = $quotation->acc_id;
            $to = $quotation->emp_id;
            $url = route('vendor.service.quotations', [$quotation->id]);

            _inAppNotification($title, $msg, $assignment_id, $to, $url, 'vendor_employee');
            _sendMailToStaff($title, $msg, $to, $url);
            $html = view('front-views.partials.dashboard.service._quotation_cta', ['quotation' => $quotation])->render();

            return response()->json(['status' => true, 'message' => ucfirst($request->action) . 'ed Successfully', 'html' => $html, 'quotation_id' => $quotation->id]);
        } else {
            return response()->json(['status' => false, 'message' => 'Some error occured']);
        }
    }
}
