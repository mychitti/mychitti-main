<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Keyword;
use App\Models\Lead;
use App\Models\Zone;
use Illuminate\Support\Facades\Config;

class KeywordsController extends Controller
{
    public function index(Request $request)
    {
      $keywords = Keyword::where('status', 1)->get();
      return view('admin-views.keywords.index', compact('keywords'));
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
        $services = Item::where('status', '1')->module(Config::get('module.current_module_id'))->get();
        return view('admin-views.lead.add', compact('services', 'zones'));
    }
    public function save_info(Request $request)
    {
        $id = $request->post('lead_id');

        if ($id == '') { // for new lead

            $validator = Validator::make($request->all(), [
                'client_name' => 'required|max:100',
                'client_email' => 'required|email',
                'zone' => 'required',
                'client_mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20',
            ], [
                'client_name.required' => 'Client Name is required',
                'client_email.required' => 'Client Email is required',
                'client_mobile.required' => 'Client Mobile is required',
                'zone.required' => 'City is required',
            ]);
    
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
            $lead = new Lead;
            $lead->vendor_id = 0;
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
        // $lead->location = $request->post('location');
        $lead->zone = $request->post('zone');
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
