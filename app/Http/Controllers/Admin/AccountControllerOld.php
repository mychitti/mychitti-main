<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use Brian2694\Toastr\Facades\Toastr;

use Illuminate\Support\Facades\Storage;
use App\CentralLogics\Helpers;
use App\Exports\AccountExport;
use App\Models\Account; 
use App\Models\Department;
use App\Models\Staff; 
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AccountController extends Controller
{
  
    public function index(Request $request)
    { 
        $account = Account::where('account_type', 'admin')->get();
        return view('admin-views.account2.index', compact('account'));
    } 

    
    public function report(Request $request)
    {
        if ($request->has('month')) {
            $month = $request->month;
        } else {
            $month = date("Y-m");
        }

        $v_id = Helpers::get_store_id();
        $account = [];
        // account transaction
        $accountQ = DB::table('account_transactions')->where('from_id', $v_id)->whereBetween('created_at', [$month . '-01 00:00:00', $month . '-31 23:59:59'])->where('from_type', 'store')->whereNot('amount', 0)->get();
        foreach ($accountQ as $key => $value) {
            $account[] = [
                'category' => 'Lead Charges',
                'amount' => $value->amount,
                'type' => $value->action == 'debit' ? 'Expense' : 'Income',
                'date' => $value->created_at
            ];
        }
        // accouns
        $accountQ22 = DB::table('accounts')->where('store_id', $v_id)->whereBetween('created_at', [$month . '-01 00:00:00', $month . '-31 23:59:59'])->where('status', 'completed')->whereNot('amount', 0)->get();
    //  prx( $accountQ22 );
        foreach ($accountQ22 as $key => $value) {
            $account[] = [
                'category' => $value->category,
                'amount' => $value->amount,
                'type' => $value->type == 'expense' ? 'Expense' : 'Income',
                'date' => $value->created_at
            ];
        }
        // service income 
        $completed_services =  DB::table('accepted_service_requests')->join('service_invoices', 'service_invoices.service_id', 'accepted_service_requests.service_request_id')->where('accepted_service_requests.vendor_id',  Helpers::get_store_id())->where('current_status', 'Completed')->whereBetween('completed_at', [$month . '-01 00:00:00', $month . '-31 23:59:59'])->where('service_invoices.payment_status', 'Paid')->select('service_invoices.id', 'service_invoices.total_amount', 'service_invoices.payment_status', 'service_invoices.created_at')->get();
        $recievables = 0;
        $expenses = 0;

        foreach ($completed_services as $key => $value) {
            $sales = 0;
            $invoice_items = DB::table('invoice_items')->where('invoice_id', $value->id)->get();
            foreach ($invoice_items as $key2 => $value2) {
                $sales += _taxIncludedPrice($value2->price, $value2->tax) * $value2->qty;
            }
            $account[] = [
                'category' => 'Sales',
                'amount' => $sales,
                'type' => 'Income',
                'date' => $value->created_at
            ];
        }

        // other invoices - income
        $invoices = DB::table('manual_invoices')->join('users', 'users.id', 'manual_invoices.bill_to')->where('vendor_id', Helpers::get_store_id())->select('users.f_name', 'users.l_name', 'manual_invoices.*')->whereBetween('manual_invoices.created_at', [$month . '-01 00:00:00', $month . '-31 23:59:59'])->get();

        foreach ($invoices as $key => $value) {
            $totalAmount = 0;

            $items = DB::table('invoice_items')->where('rand_invoice_id', $value->invoice_id)->get();
            foreach ($items as $key2 => $value2) {
                $totalAmount +=  _taxIncludedPrice($value2->price * $value2->qty, $value2->tax);
            }
            $account[] = [
                'category' => 'Offline Billing',
                'amount' => $totalAmount,
                'type' => 'Income',
                'date' => $value->created_at
            ];
        }
        // other invoices - expenxe
        $invoices = DB::table('manual_invoices')->where('bill_to', Helpers::get_store_id())->select('manual_invoices.*')->whereBetween('manual_invoices.created_at', [$month . '-01 00:00:00', $month . '-31 23:59:59'])->get();

        foreach ($invoices as $key => $value) {
            $totalAmount = 0;

            if ($value->type == 'manual') {
                $items = DB::table('invoice_items')->where('rand_invoice_id', $value->invoice_id)->get();
                foreach ($items as $key2 => $value2) {
                    $totalAmount +=  _taxIncludedPrice($value2->price * $value2->qty, $value2->tax);
                }
            }
            $account[] = [
                'category' => 'Offline Billing',
                'amount' => $totalAmount,
                'type' => 'Expense',
                'date' => $value->created_at
            ];
        }
        if ($request->has('action')) {
            $headings =  ['sl', 'Category', 'Amount', 'Type', 'Date Time', 'Month'];
            $data = [];
            foreach ($account as $key => $acc) {
                $data[$key] = [
                    $key + 1,
                    $acc['category'],
                    $acc['amount'],
                    $acc['type'],
                    $acc['date'],
                    _monthNYear($month . '-01'),
                ];
            } 
            return Excel::download(new AccountExport($data, $headings), 'Account_Report_'.$month.'.xlsx');
        }

        return view('admin-views.account2.report', compact('account', 'month'));
    }
    public function edit(Request $request, $id)
    { 
        $account = Account::find($id);
        return view('admin-views.account2.manage', compact('account'));
    }

    public function status_change(Request $request)
    {

        $id = $request->post('d_id');
        $status = $request->post('status');

        $query =  Department::where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        // echo $query;
        return back();
    }

    public function status(Request $request)
    {
        $coupon = Staff::find($request->id);
        $coupon->status = $request->status;
        $coupon->save();
        Toastr::success('Staff Status Changed Successfully');
        return back();
    }
    public function delete_department(Request $request, $id)
    {
        $query =  Department::find($id)
            ->delete();
            Toastr::success('Department Deleted Successfully');
        return back();
    }
    public function delete(Request $request, $id)
    {
        $query =  Account::find($id)
            ->delete();
            Toastr::success('Deleted Successfully');
        return back();
    }

    public function add()
    {
        $departments = Department::where('status', '1')->get();
        return view('admin-views.account2.add', compact('departments'));
    }
    public function save_info(Request $request)
    {
        $file = $request->file('file');

        $id = $request->post('account_id');
        
            $validator = Validator::make($request->all(), [
                'date' => 'required',
                'type' => 'required',
                'status' => 'required',
                'note' => 'nullable|max:1000',
                'payment_mode' => 'required',   
                'name' => 'required|max:100',
                'amount' => 'required',
                'file' => 'nullable|mimetypes:application/pdf|max:30720',
            ],[
                'file.mimes' => 'Only .doc, .docx and .pdf file accepted',
            ]);
             if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

        if ($id == '') { // for new lead  
            $staff = new Account;
        } else {
            $staff = Account::find($id);
        }
        
        $v_id = \App\CentralLogics\Helpers::get_store_id(); 
        $fileName = '';
        $staff->store_id = 0;
        $staff->account_type = 'admin';
        $staff->date = $request->post('date');
        $staff->type = $request->post('type');
        $staff->classification = $request->post('classification');
        $staff->status = $request->post('status');
        $staff->payment_mode = $request->post('payment_mode');
        $staff->name = $request->post('name');
        $staff->amount = $request->post('amount');
        $staff->bill_numer = $request->post('bill_numer');
        $staff->additional_note = $request->post('note');
        
         $file = $request->file('file');
         if($file != ''){
                $imageName = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . "." . $file->getClientOriginalName();
         if (!Storage::disk('public')->exists('documents')) {
                Storage::disk('public')->makeDirectory('documents');
            }
            Storage::disk('public')->putFileAs('documents', $file, $imageName);
            $staff->document =  $imageName;
             
         }
    
        
        if ($id == '') { // for new lead
            $staff->save();
            Toastr::success('Information saved successfully');
        }else{
            $staff->update();
            Toastr::success('Information updated successfully');
        }
        return redirect('admin/account/list');
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
