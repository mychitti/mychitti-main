<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Item;
use App\Models\Store;
use App\Models\Quotation;
use App\Mail\QuotationMail;
use App\Models\Review;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\CentralLogics\Helpers;
use App\CentralLogics\ProductLogic;
use App\Models\ItemCampaign;
use App\Models\Tag;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\DB;
use App\Scopes\StoreScope;
use App\Models\Translation;
use DateTime;
use Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class QuoteController extends Controller
{
    public function send_quote(Request $request, $id){
        $quote = Quotation::find($id);
        // echo '<pre>';
        // print_r($quote);die;
        
        //  $template =  view('email-templates.store-templates.send-quote', ['quote'=>$quote]);
        //  print_r($template);
        Mail::to($quote->client_email)->send(new QuotationMail($quote));
    }
    public function index(Request $request)
    {
       $v_id = 0;
        $quotes = Quotation::where('vendor_id',$v_id)->paginate(config('default_pagination'));
        return view('admin-views.quote.index', compact('quotes'));
    }
    public function new(Request $request)
    {
        $v_id = '0';
        $quotes = Quotation::where('vendor_id',$v_id)->where('status', 'New')->paginate(config('default_pagination'));
        return view('admin-views.quote.index', compact('quotes'));
    }
      public function accepted(Request $request)
    {
       $v_id = 0;
        $quotes = Quotation::where('vendor_id',$v_id)->where('status', 'Accepted')->paginate(config('default_pagination'));
        return view('admin-views.quote.index', compact('quotes'));
    }
      public function declined(Request $request)
    {
       $v_id = 0;
        $quotes = Quotation::where('vendor_id',$v_id)->where('status', 'Declined')->paginate(config('default_pagination'));
        return view('admin-views.quote.index', compact('quotes'));
    }
    public function manage(Request $request, $id)
    {
        $quote = Quotation::find($id);
        $services = Item::where('status', '1')->get();
        return view('admin-views.quote.manage', compact('quote', 'services'));
    }

    public function status_change(Request $request)
    {

        $id = $request->post('lead_id');
        $status = $request->post('status');

        $query =  Quotation::where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        // echo $query;
        return back();
    }
    public function delete(Request $request, $id)
    {

        $query =  Quotation::find($id)
            ->delete();
        return back();
    }

    public function add()
    {
        $services = Item::where('status', '1')->get();
        // echo Helpers::get_loggedin_user()->id;die;
        // print_r($services); die;
        return view('admin-views.quote.add', compact('services'));
    }
    public function save_info(Request $request)
    {
        
        $id = $request->post('quote_id');
        $validator = Validator::make($request->all(), [
                 'subject' => 'required|max:255',
                 'q_date' => 'required|max:255',
                 'exp_date' => 'required|max:255',
                 'service_name.*' => 'required|max:255',
                 'service_unit.*' => 'required',
                 'remarks' => 'nullable|max:1000',
                 'service_qty.*' => 'required',
                 'service_amount.*' => 'required',
                 
                'client_name' => 'required|max:100',
                'client_email' => 'required|email',
                'client_mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20',
            ], [
                'client_name.required' => 'Client Name is required',
                'client_email.required' => 'Client Email is required',
                'client_mobile.required' => 'Client Mobile is required',
                'service_name.*.required' => 'Please Select Service / Product',
                'remarks.max' => "Max character limit for remarks is 1000"
            ]);
              if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
            
             $data['service'] = $request->post('service_name');
            $data['unit'] = $request->post('service_unit');
            $data['qty'] = $request->post('service_qty');
            $data['amount'] = $request->post('service_amount');
            
            $service_data =  json_encode($data);
            

        if ($id == '') { // for new quote
            $quote = new Quotation;
            $quote->created_at = date('Y-m-d H:i:s');

        } else { // for existing quote
            $quote = Quotation::find($id);
        }
        
           $quote->vendor_id = 0;
            $quote->subject = $request->post('subject');
            $quote->status = $request->post('status');
            $quote->q_date = $request->post('q_date');
            $quote->exp_date = $request->post('exp_date');
            $quote->remarks = $request->post('remarks');
            $quote->client_name = $request->post('client_name');
            $quote->client_email = $request->post('client_email');
            $quote->client_mobile = $request->post('client_mobile');
            $quote->services = $service_data;
            
            

    if ($id == '') { // for new lead
            $quote->save();
            Toastr::success('Quotation Information saved successfully');
        }else{
            $quote->update();
            Toastr::success('Quotation Information updated successfully');
        }
        return redirect('admin/quotation/list');
    }

 
}
