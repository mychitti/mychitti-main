<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\EmployeeRole;
use App\Models\GatePass;
use App\Models\ServiceQuoteItem;
use App\Models\GatePassItem;
use App\Models\InServiceQuotation;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Category;
use App\Models\CommonServiceIssue;
use App\Models\ServiceRequest;
use App\Models\Store;
use App\Models\TempStoreStatus;
use App\Models\User;

use function PHPSTORM_META\type;

class ServiceController extends Controller
{

    public function lead_list(Request $request)
    {
        // mark all as checked
        ServiceRequest::query()->update(['checked' => 1]);

        $type = $request->type ?? '';
        // prx( $type);
        if (!$type || $type == 'all') {
            $leads = DB::table('service_requests')
                ->leftJoin('accepted_service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
                ->leftJoin('stores', 'stores.id', '=', 'accepted_service_requests.vendor_id')
                ->leftJoin('items', 'items.id', '=', 'service_requests.item_id')
                ->leftJoin('vendor_emp_jobs', 'vendor_emp_jobs.service_id', '=', 'accepted_service_requests.service_request_id')
                ->leftJoin('service_statuses', 'service_statuses.id', '=', 'vendor_emp_jobs.status')
                ->select(
                    'service_requests.id',
                    // 'service_requests.*',
                    'service_requests.id as service_id',
                    'service_requests.created_at as enquiry_date',
                    'accepted_service_requests.current_status',
                    'accepted_service_requests.id as accepted_id',
                    'service_statuses.status',
                    'items.name',
                    'items.image',
                    'stores.name as store_name',
                    'stores.logo'
                )
                ->groupBy('service_requests.id')
                ->orderBy('service_id', 'desc')
                ->paginate(15);

            // prx($leads);
        } else {
            $type = $request->type;
            $leads = DB::table('service_requests')
                ->leftJoin('accepted_service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
                ->leftJoin('stores', 'stores.id', '=', 'accepted_service_requests.vendor_id')
                ->leftJoin('items', 'items.id', '=', 'service_requests.item_id')
                ->leftJoin('vendor_emp_jobs', 'vendor_emp_jobs.service_id', '=', 'accepted_service_requests.service_request_id')
                ->leftJoin('service_statuses', 'service_statuses.id', '=', 'vendor_emp_jobs.status')
                ->where('accepted_service_requests.current_status', $request->type)
                ->select(
                    'accepted_service_requests.current_status',
                    'service_requests.id as service_id',
                    'service_requests.created_at as enquiry_date',
                    'service_statuses.status',
                    'items.name',
                    'items.image',
                    'stores.name as store_name',
                    'stores.logo'
                )
                // ->groupBy('service_requests.id')
                ->orderBy('service_requests.id', 'desc')
                ->paginate(15)
                ->appends(['type' => $request->type]);
        }
        return view('admin-views.service.leads-list', compact('leads', 'type'));
    }

    public function common_issue_delete(Request $request)
    {
        CommonServiceIssue::destroy($request->id);
        Toastr::success('Deleted successfully');
        return back();
    }
    public function common_issue_save(Request $request)
    {
        $request->validate([
            'issue' => 'required',
        ]);
        $issue = new CommonServiceIssue;
        $issue->issue = $request->issue;
        $issue->save();

        Toastr::success('Added successfully');
        return back();
    }
    public function config()
    {
        $reported_issue_list  = CommonServiceIssue::all();
        return view('admin-views.service.config', compact('reported_issue_list'));
    }

    public function approve_status_request(Request $request)
    {
        $r = TempStoreStatus::find($request->id);

        if ($r) {
            $store = Store::find($r->store_id);

            if ($store) {
                $leadStatuses = array_filter(explode(',', $store->lead_statuses));

                if (!in_array($r->status_id, $leadStatuses)) {
                    $leadStatuses[] = $r->status_id;
                }

                $store->lead_statuses = implode(',', $leadStatuses);
                $store->save();

                $r->delete();
                Toastr::success('Status approved and added to store!');
            } else {
                Toastr::error('Store not found!');
            }
            return back();
        } else {
            Toastr::error('Request not found!');
            return back();
        }
    }

    public function new_status_request()
    {
        $stts_requests =  TempStoreStatus::with('serviceStatus', 'store')
            ->paginate(10);
        return view('admin-views.service.new_status_request', compact('stts_requests', 'stts_requests'));
    }
    public function config_update(Request $request)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'exp_count'], [
            'value' => $request['exp_count']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'exp_unit'], [
            'value' => $request['exp_unit']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'leads_distribut_vendor'], [
            'value' => $request['leads_distribut_vendor']
        ]);

        Toastr::success('Configurations updated successfully!');
        return back();
    }
    public function manual_bill()
    {
        $customers = User::where('status', 1)->get();

        return view('vendor-views.billing.invoice_generate', compact('customers'));
    }

    public function lead_detail_old(Request $request, $leadId)
    {

        $reqDetails = DB::table('service_requests')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('service_requests.id', $leadId)
            ->select('items.name', 'service_requests.*', 'service_requests.id as s_id')
            ->first();

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
            $gpItems = [];
        }
        return view('admin-views.service.lead-details', compact('reqDetails',  'quotationItems', 'gpItems', 'timeline'));
    }
    public function lead_detail(Request $request, $leadId)
    {
        $confirmedLeadVendors = DB::table('service_requests')
            ->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
            ->where('service_requests.id', $leadId)
            ->whereRaw("FIND_IN_SET(stores.id, service_requests.sent_to) > 0")
            ->whereNotNull('accepted_service_requests.confirmed_at')
            ->select('stores.name', 'stores.id as store_id', 'stores.logo', 'accepted_service_requests.id', 'service_requests.id as service_id', 'accepted_service_requests.confirmed_at')
            ->get();

        $acceptedLeadVendors = DB::table('service_requests')
            ->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
            ->where('service_requests.id', $leadId)
            ->whereRaw("FIND_IN_SET(stores.id, service_requests.sent_to) > 0")
            ->select('stores.name', 'stores.id as store_id', 'stores.logo', 'accepted_service_requests.id', 'service_requests.id as service_id', 'accepted_service_requests.created_at')
            ->get();

        $completedLeadVendors = DB::table('service_requests')
            ->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
            ->where('service_requests.id', $leadId)
            ->whereRaw("FIND_IN_SET(stores.id, service_requests.sent_to) > 0")
            ->whereNotNull('accepted_service_requests.completed_at')
            ->select('stores.name', 'stores.id as store_id', 'stores.logo', 'accepted_service_requests.id', 'service_requests.id as service_id', 'accepted_service_requests.completed_at')
            ->get();

        $recievedLeadVendors = DB::table('service_requests')
            ->join('stores', function ($join) {
                $join->on(DB::raw("FIND_IN_SET(stores.id, service_requests.sent_to)"), '>', DB::raw('0'));
            })
            ->where('service_requests.id', $leadId)
            ->select('stores.name', 'stores.id as store_id', 'stores.logo', 'service_requests.id as service_id', 'service_requests.created_at')
            ->get();
        // prx( $confirmedLeadVendors)

        $reqDetails = DB::table('service_requests')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('service_requests.id', $leadId)
            ->select('items.name', 'service_requests.*', 'service_requests.id as s_id')
            ->first();

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
            $gpItems = [];
        }
        return view('admin-views.service.lead-details', compact('reqDetails', 'acceptedLeadVendors', 'recievedLeadVendors', 'completedLeadVendors', 'confirmedLeadVendors', 'quotationItems', 'gpItems', 'timeline'));
    }
    function lead_timeline(Request $request, $leadId)
    {
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

        return view('admin-views.service.timeline', compact('reqDetails', 'acceptanceDetails', 'gatepass', 'quotation', 'quotationItems', 'gpItems', 'timeline'));
    }
}
