<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request; 
use App\Models\Item;
use App\Models\Store;
use App\Models\Lead;
use Brian2694\Toastr\Facades\Toastr;
use App\Models\AcceptedServiceRequest;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Zone;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Config;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        
       $v_id = 0;
      
        $leads = Lead::select('leads.*', 'items.name as service_name')
        ->join('items', 'leads.service', '=', 'items.id')
        ->join('stores', 'items.store_id', '=', 'stores.id')
        ->where('leads.vendor_id' , $v_id);
        
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
        ->where('leads.vendor_id' , $v_id)
        ->where('leads.status','Hold')->get();
        
         $new =  Lead::select('leads.*', 'items.name as service_name')
        ->join('items', 'leads.service', '=', 'items.id')
        ->join('stores', 'items.store_id', '=', 'stores.id')
       ->where('leads.vendor_id' , $v_id)
        ->where('leads.status','New')->get();
        
         $followups =  Lead::select('leads.*', 'items.name as service_name')
        ->join('items', 'leads.service', '=', 'items.id')
        ->join('stores', 'items.store_id', '=', 'stores.id')
        ->where('leads.vendor_id' , $v_id)
        ->where('leads.status','Follow Ups')->get();
        
         $notinterested =  Lead::select('leads.*', 'items.name as service_name')
        ->join('items', 'leads.service', '=', 'items.id')
        ->join('stores', 'items.store_id', '=', 'stores.id')
        ->where('leads.vendor_id' , $v_id)
        ->where('leads.status','Not Interested')->get();
        
           $completed = Lead::select('leads.*', 'items.name as service_name')
        ->join('items', 'leads.service', '=', 'items.id')
        ->join('stores', 'items.store_id', '=', 'stores.id')
        ->where('leads.vendor_id' , $v_id)
        ->where('leads.status','Completed')->get();
        
           $closed =  Lead::select('leads.*', 'items.name as service_name')
        ->join('items', 'leads.service', '=', 'items.id')
        ->join('stores', 'items.store_id', '=', 'stores.id')
       ->where('leads.vendor_id' , $v_id)
        ->where('leads.status','Closed')->get();


        return view('admin-views.lead.index', compact('leads', 'filter_status', 'from_date', 'to_date', 'closed', 'completed', 'notinterested', 'followups' , 'new', 'onhold'));
    }
    public function save_comment(Request $request){

        DB::table('lead_statuses')->insert([
            'service_request_id' => $request->lead_id,
            'status' => $request->comment, 
            'created_at' => date('Y-m-d H:i:s')
        ]);
        Toastr::success('Comment added successfully!');
  
         return back();
    }
    public function manage(Request $request, $id)
    {
        $v_id = 0;
        $zones = Zone::active()->get();
        $lead = Lead::find($id);
        $serviceName = Item::find($lead->service)->name;
        $previous_leads = Lead::where('lead_date', '<=', $lead->lead_date)->where('vendor_id' , $v_id)->where('client_email', $lead->client_email)->get();
     
        return view('admin-views.lead.manage', compact('lead', 'previous_leads', 'serviceName', 'zones'));
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
        $zones = Zone::active()->get(); 
        $stores = Store::active()->get(); 
        $customers = User::where('status', 1)->get();
        $services = DB::table('items')->where('status', 1)->where('module_id',Config::get('module.current_module_id') )->get();
        return view('admin-views.lead.add', compact('services', 'zones','customers', 'stores'));
    }
    public function save_info(Request $request)
    {
        $id = $request->post('lead_id');

        if ($id == '') { // for new lead

            $validator = Validator::make($request->all(), [
                'client_name' => 'required|max:100',
                'zone' => 'required',
                'service' => 'required',
                // 'client_email' => 'required|email',
                // 'zone' => 'required',
                // 'client_mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20',
            ], [
                // 'client_name.required' => 'Client Name is required',
                // 'client_email.required' => 'Client Email is required',
                // 'client_mobile.required' => 'Client Mobile is required',
                // 'zone.required' => 'City is required',
            ]);
    
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }


            // $store_data = Store::find($request->store);
            $serviceReq = new ServiceRequest;
            $serviceReq->user_id = $request->client_name;
            $serviceReq->item_id = $request->service;
            $serviceReq->module_id = 6;
            $serviceReq->zone_id = '['. $request->zone . ']';
            // $serviceReq->latitude = $store_data->latitude;
            // $serviceReq->longitude = $store_data->longitude;
            $serviceReq->status = 'new';
            $serviceReq->created_at = date('Y-m-d H:i:s');
            $serviceReq->accepted = 1;
            if ($request->vendor_id) {
                $serviceReq->accepted_by = $request->vendor_id;
                $serviceReq->sent_to = $request->vendor_id;
            }
            $uDet = User::find($request->client_name);
            if ($serviceReq->save()) {
                DB::table('lead_statuses')->insert([
                    'service_request_id' => $serviceReq->id,
                    'status' => 'My Chitti Created Lead For ' . $uDet->f_name . " " . $uDet->l_name,
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                if ($request->vendor_id) {
                    $acceptance = new AcceptedServiceRequest();
                    $acceptance->vendor_id = $request->vendor_id;
                    $acceptance->current_status = 'Confirmation Request Sent';
                    $acceptance->service_request_id = $serviceReq->id;
                    $acceptance->created_at = date('Y-m-d H:i:s');
                    $acceptance->save();

                    $this->notifyVendorLead((int) $request->vendor_id, $request->service, $uDet);
                }
            }
            $request_id = $serviceReq->id;
            $item = DB::table('items')->where('id',  $request->service )->first();
            $user = DB::table('service_requests')->join('users' , 'users.id', 'service_requests.user_id')->where('service_requests.id' , $request_id)->select('users.*')->first();
            if($user){
                $fcm_token = $user->cm_firebase_token;
                $data = [
                    'title' => "New Lead Created",
                    'description' => "New Lead Created from MyChitti for ".$item->name.".",
                    'order_id' => $request_id,
                    'image' => '',
                    'type'=> 'block'
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

        } else {
            // $lead = Lead::find($id);
        }
        $request->session()->flash('msg', 'Lead Information saved successfully');
        return back();
    }

    /**
     * WhatsApp the vendor about a new lead — only when the store has the paid
     * "leads" receiving add-on active. Sent from the platform number.
     */
    private function notifyVendorLead(int $storeId, $serviceId, $client): void
    {
        $serviceName = DB::table('items')->where('id', $serviceId)->value('name');
        $clientName = trim(($client->f_name ?? '') . ' ' . ($client->l_name ?? ''));
        WhatsAppService::sendLeadNotification($storeId, $serviceName, $clientName);
    }

    public function search_clients(Request $request)
    {
        $query = $request->get('q', '');
        $clients = User::where('status', 1)
            ->where(function ($q) use ($query) {
                $q->where('f_name', 'like', "%{$query}%")
                  ->orWhere('l_name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->select('id', 'f_name', 'l_name', 'phone')
            ->limit(20)
            ->get();

        return response()->json($clients);
    }

    public function search_services(Request $request)
    {
        $query = $request->get('q', '');
        $services = DB::table('items')
            ->where('status', 1)
            ->where('module_id', Config::get('module.current_module_id'))
            ->where('name', 'like', "%{$query}%")
            ->select('id', 'name')
            ->limit(20)
            ->get();

        return response()->json($services);
    }

    public function search_zones(Request $request)
    {
        $query = $request->get('q', '');
        $zones = Zone::active() 
            ->where('name', 'like', "%{$query}%")
            ->select('id', 'name')
            ->limit(20)
            ->get();

        return response()->json($zones);
    }

    public function search_vendors(Request $request)
    {
        $query = $request->get('q', '');
        $vendors = Store::where('status', 1)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'phone')
            ->limit(20)
            ->get();

        return response()->json($vendors);
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
