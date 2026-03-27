<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Exports\CustomerLeadsExport;
use App\Exports\CustomerTaskExport;
use App\Exports\CustomerTransactionExport;
use App\Exports\StoreUserExport;
use App\Imports\CustomerImport;
use App\Models\Project;
use App\Models\StoreCustomer;
use App\Models\StoreTask;
use App\Models\StoreUserComment;
use App\Models\User;
use App\Models\UserAddress;
use Brian2694\Toastr\Facades\Toastr;
use Faker\Extension\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{

    public function add_new(Request $request)
    {
        return view('vendor-views.customer.add');
    }
    public function edit(Request $request, $id)
    {
        $customer = StoreCustomer::with('billing_address', 'shipping_address')->where('id', $id)->first();
        return view('vendor-views.customer.add', compact('customer'));
    }
    public function export(Request $request)
    {
        $type = $request->type;
        $headings =  ['Type', 'Name', 'Phone', 'Email', 'ID Number', 'GST'];
        $all_clients = StoreCustomer::where('store_id', Helpers::get_store_id())->get();
        $data = [];
        foreach ($all_clients as $key => $client) {
            $data[$key] = [
                $client->user_type,
                $client->f_name,
                $client->phone,
                $client->email,
                $client->id_number,
                $client->gst,
            ];
        }

        return Excel::download(new StoreUserExport($data, $headings), 'clients.xlsx');
    }
    public function get_store_customer(Request $request)
    {
        $query = $request->get('q');

        $customers = User::where(function ($q) use ($query) {
            $q->where('f_name', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%");
        })
            ->select('id', 'f_name', 'phone')
            ->limit(10)
            ->get();

            return $customers;
    }
    public function get_matches(Request $request)
    {
        $query = $request->get('q');
        $user_type = $request->user_type ?? 'customer';

        $customers = StoreCustomer::where(function ($q) use ($query) {
            $q->where('f_name', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%");
        })
            ->when($user_type != 'vendor_customer', function ($q) use ($user_type) {
                $q->where('user_type', $user_type);
            })
            ->where('store_id', Helpers::get_store_id())
            ->select('id', 'f_name', 'phone', 'user_type')
            ->limit(10)
            ->get();

            return $customers;
    }
    public function upload_excel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            Toastr::warning("Please upload a valid excel file");
            return redirect()->back();
        }

        $vendorId = Helpers::get_store_id();
        $import = new CustomerImport($vendorId);
        Excel::import($import, $request->file('file'));

        if ($import->failedRows) {
            Toastr::warning($import->failedRows . '. Uploaded Successfully');
        } else {
            Toastr::success('Customers list imported successfully!');
        }
        return redirect()->back();
    }

    public function leads_export(Request $request)
    {
        $ids = $request->lead_id;
        $services = self::serviceLeads(null, null, null, $ids = $ids);

        $headings =  ['Sl', 'Date', 'Service Name', 'Service Lead Id', 'Status'];
        $data = [];
        foreach ($services as $key => $service) {
            $data[$key] = [
                $key + 1,
                $service->created_at,
                $service->item_name,
                $service->service_id,
                $service->current_status ?? $service->status
            ];
        }
        // prx($invoices);
        return Excel::download(new CustomerLeadsExport($data, $headings), 'customer_leads.xlsx');
    }
    public function project_export(Request $request)
    {
        $project_ids = json_decode($request->project_id, true);
        $user_id = $request->user_id;
        $projects = Project::with('teamLeader')->where('client_id', $user_id)
            ->whereIn('id', $project_ids)
            ->orderBy('created_at', 'desc')
            ->get();
        $headings =  ['Sl', 'Date', 'Title', 'Team Leader', 'Progress', 'Status', 'Advance Pay', 'Deadline', 'Other Info'];
        $data = [];
        foreach ($projects as $key => $value) {
            $data[$key] = [
                $key + 1,
                $value->created_at,
                $value->project_title,
                $value->teamLeader?->f_name . ' ' . $value->teamLeader->l_name,
                $value->prog_percent . '%',
                $value->progress_status,
                _price($value->advance_pay),
                $value->end_date,
                ucfirst($value->project_type . ' - ' . $value->project_size)
            ];
        }
        return Excel::download(new CustomerTaskExport($data, $headings), 'customer_project.xlsx');
    }
    public function tasks_export(Request $request)
    {
        $task_ids = json_decode($request->task_id, true);
        $user_id = $request->user_id;

        $tasks = StoreTask::with('employee')->where('user_id', $user_id)
            ->whereIn('id', $task_ids)
            ->orderBy('created_at', 'desc')
            ->get();
        $headings =  ['Sl', 'Date', 'Title', 'Duration', 'Assignee', 'Progress', 'Status'];
        $data = [];
        foreach ($tasks as $key => $value) {
            $data[$key] = [
                $key + 1,
                $value->created_at,
                $value->title,
                $value->time_count . ' ' . $value->time_unit . '(s)',
                $value->employee?->f_name . ' ' . $value->employee?->l_name,
                $value->progress . '%',
                $value->payment_status,
            ];
        }
        return Excel::download(new CustomerTaskExport($data, $headings), 'customer_tasks.xlsx');
    }
    public function transactions_export(Request $request)
    {
        // prx($request->all());
        $serviceIds = json_decode($request->service_inv_id, true);
        $manualIds  = json_decode($request->manual_inv_id, true);

        $storeId = Helpers::get_store_id();
        $serviceInvoices = DB::table('service_invoices')
            ->select('*', DB::raw("'debit' as invoice_type"))
            ->whereIn('id', $serviceIds)
            ->get();
        // prx($serviceInvoices);

        $manualInvoices = DB::table('manual_invoices')
            ->select(
                '*',
                DB::raw("CASE 
                    WHEN vendor_id = " . Helpers::get_store_id() . " 
                    THEN 'debit' 
                    ELSE 'credit' 
                 END as invoice_type")
            )
            ->whereIn('id', $manualIds)
            ->get();


        // Merge all invoices
        $invoices = $serviceInvoices->merge($manualInvoices);
        $headings =  ['Sl', 'Date', 'Invoice Id', 'Total Amount', 'Tax Amount', 'Credit', 'Debit', 'Payment Status', 'Created At'];
        $data = [];
        foreach ($invoices as $key => $invoice) {
            $data[$key] = [
                $key + 1,
                $invoice->invoice_date ?? $invoice->created_at,
                $invoice->invoice_id,
                $invoice->total_amount,
                $invoice->final_tax,
                $invoice->invoice_type == 'credit' ? $invoice->total_amount : 0,
                $invoice->invoice_type == 'debit' ? $invoice->total_amount : 0,
                $invoice->payment_status,
                $invoice->created_at
            ];
        }
        // prx($invoices);
        return Excel::download(new CustomerTransactionExport($data, $headings), 'customer_transactions.xlsx');
    }
    public function serviceLeads($userids = null, $formatted_from = null, $formatted_to = null, $ids = null)
    {
        $servicesQuery = DB::table('service_requests');
        if ($ids) {
            $ids = json_decode($ids, true);
            $servicesQuery->whereIn('service_requests.id', $ids);
        } else {
            $servicesQuery->whereBetween('service_requests.created_at', [$formatted_from, $formatted_to])
                ->whereIn('service_requests.user_id',  $userids);
        }
        $servicesQuery->join('items', 'service_requests.item_id', '=', 'items.id')
            ->join('users', 'service_requests.user_id', '=', 'users.id')
            ->leftJoin('accepted_service_requests', function ($join) {
                $join->on('service_requests.id', '=', 'accepted_service_requests.service_request_id');
            })
            ->leftJoin('cancelled_service_requests', function ($join) {
                $join->on('service_requests.id', '=', 'cancelled_service_requests.service_request_id');
            })
            ->whereRaw("FIND_IN_SET(?, service_requests.sent_to)", [Helpers::get_store_id()])
            ->select(
                'service_requests.*',
                'service_requests.id as service_id',
                'items.name as item_name',
                'items.image as image',
                'users.f_name as f_name',
                'users.id as uid',
                DB::raw('COALESCE(accepted_service_requests.assigned_status, cancelled_service_requests.assigned_status) as assigned_status'),
                DB::raw('COALESCE(accepted_service_requests.current_status, cancelled_service_requests.current_status) as current_status'),
                DB::raw('COALESCE(accepted_service_requests.assigned_type, cancelled_service_requests.assigned_to) as assigned_type'),
                DB::raw('COALESCE(accepted_service_requests.assigned_to, cancelled_service_requests.assigned_to) as assigned_to'),
                DB::raw('COALESCE(accepted_service_requests.accepted_by_staff, cancelled_service_requests.accepted_by_staff) as accepted_by_staff'),
                DB::raw('COALESCE(accepted_service_requests.id, cancelled_service_requests.id) as acc_id'),
                DB::raw('COALESCE(accepted_service_requests.vendor_id, cancelled_service_requests.vendor_id) as vendor_id'),
                DB::raw("CASE 
            WHEN service_requests.created_at < '" . now()->subMinutes(Helpers::get_lead_exp_minutes()) . "' 
                AND accepted_service_requests.id IS NULL 
                AND cancelled_service_requests.id IS NULL 
            THEN 'missed' 
            ELSE NULL 
        END as additional_status")
            )
            ->where(function ($q) {
                $q->whereNotNull('accepted_service_requests.service_request_id')
                    ->orWhereNotNull('cancelled_service_requests.service_request_id')
                    ->orWhere('service_requests.created_at', '>=', now()->subMinutes(Helpers::get_lead_exp_minutes()))
                    ->orWhere(function ($query) {
                        $query->whereNull('accepted_service_requests.service_request_id')
                            ->whereNull('cancelled_service_requests.service_request_id')
                            ->where('service_requests.created_at', '<', now()->subMinutes(Helpers::get_lead_exp_minutes()));
                    });
            });

        $services = $servicesQuery->groupBy('service_id')->get();
        return $services;
    }
    public function view(Request $request, $id)
    {
        $storeId = Helpers::get_store_id();
        $data = [];
        $customer = StoreCustomer::with('billing_address', 'shipping_address', 'comments')->where('id', $id)->first();
        $userids = User::where('phone', $customer->phone)->pluck('id')->toArray();

        $preset = request('date_range') ?? 'last_30_days';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $serviceInvoices = DB::table('service_invoices')
            ->select('*', DB::raw("'debit' as invoice_type"), DB::raw("'service' as inv_type"))
            ->whereIn('bill_to', $userids)
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->where('vendor_id', Helpers::get_store_id())
            ->get();

        $manualInvoices = DB::table('manual_invoices')
            ->select('*', DB::raw("'debit' as invoice_type"), DB::raw("'manual' as inv_type"))
            ->where('bill_to', $id)
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->where('vendor_id', Helpers::get_store_id())
            ->where('user_type', 'store_user')
            ->where('bill_to_type', '!=', 'vendor')
            ->get();

        $fromCustomerInvoices = DB::table('manual_invoices')
            ->select('*', DB::raw("'credit' as invoice_type"), DB::raw("'manual' as inv_type"))
            ->where('bill_to', $storeId)
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->where('store_vendor_id', $id)
            ->where('user_type', 'store_vendor')
            ->where('bill_to_type', 'vendor')
            ->get();

        // Merge all invoices
        $invoices = $serviceInvoices->merge($manualInvoices)->merge($fromCustomerInvoices);

        // Totals by source
        $data['serviceTotal'] = $serviceInvoices->sum('total_amount');
        $data['manualTotal'] = $invoices->sum('total_amount');
        $data['totalAmount'] = $data['manualTotal'];

        // Paid and unpaid
        $data['paidAmount'] = $invoices->where('payment_status', 'Paid')->sum('total_amount');
        $data['unpaidAmount'] = $invoices->where('payment_status', 'Unpaid')->sum('total_amount');

        $services = self::serviceLeads($userids, $formatted_from, $formatted_to);

        $tasks = StoreTask::with('employee')->where('user_id', $customer->id)
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->whereNull('parent_id')
            ->where('user_type',  $customer->user_type)
            ->orderBy('created_at', 'desc')
            ->get();

        $projects = Project::with('teamLeader')->where('client_id', $customer->id)
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->orderBy('created_at', 'desc')
            ->get();
        // prx($projects);
        return view('vendor-views.customer.view', compact('id', 'preset', 'customer', 'data', 'services', 'invoices', 'tasks', 'projects'));
    }

    public function search(Request $request)
    {
        $search = $request->get('q');

        $users = User::where(function ($query) use ($search) {
            // $query->where('f_name', 'like', '%' . $search . '%')
            //     ->orWhere('l_name', 'like', '%' . $search . '%')
            $query->where('phone', 'like', '%' . $search . '%');
        })
            ->select('id', 'f_name', 'l_name', 'phone')
            ->limit(10)
            ->get();

        return response()->json($users);
    }
    public function save(Request $request)
    {
        // prx($request->all());
        $request->validate([
            'f_name' => 'required',
            'phone' => 'required|max:10',
        ]);

        $phone = _cleanPhoneNumber($request->phone);
        $type = $request->user_type ?? 'customer';
        $existingUser = StoreCustomer::where('phone', $phone)->where('store_id', $this->vendorId ?? Helpers::get_store_id())->where('user_type', $type)->first();
        if ($existingUser) {
            Toastr::error(ucfirst($type . ' with this phone number already exists.'));
            return back();
        }

        // get fcm token =========
        $customer = new StoreCustomer();
        $customer->store_id =   Helpers::get_store_id();
        $customer->f_name = $request->f_name;
        $customer->user_type = $request->user_type ?? 'customer';
        $customer->address = $request->address;
        $customer->gst = $request->gst;
        $customer->email = $request->email;
        $customer->phone = $phone;
        $customer->id_number = $request->id_number;
        if (!empty($request->file('profile_pic'))) {
            $profile_pic = Helpers::upload('profile/', 'png', $request->file('profile_pic'));
            $customer->profile_pic = $profile_pic;
        }
        if (!empty($request->file('id_proof'))) {
            $extension = $request->file('id_proof')->getClientOriginalExtension(); // e.g. jpg, pdf, png
            $id_proof = Helpers::upload('customer/documents/', $extension, $request->file('id_proof'));
            $customer->id_proof = $id_proof;
        }
        $customer->save();
        $user_t = $request->user_type == 'customer' ? 'store_customer' : 'store_vendor';
        if ($request->has('ba_address1') && $request->ba_address1 != '' && $request->has('ba_state') && $request->ba_state != '') {
            $bAddr = new UserAddress();
            $bAddr->user_id = $customer->id;
            $bAddr->user_type = $user_t;
            $bAddr->addr_type = 'billing';
            $bAddr->address1 = $request->ba_address1;
            $bAddr->address2 = $request->ba_address2;
            $bAddr->pincode = $request->ba_pincode;
            $bAddr->city = $request->ba_city;
            $bAddr->state = $request->ba_state;
            $bAddr->save();
            $customer->pin_code = $request->ba_pincode;
            $customer->update();
        }
        if ($request->has('sa_address1') && $request->sa_address1 != '' && $request->has('sa_state') && $request->sa_state != '') {
            $sAddr = new UserAddress();
            $sAddr->user_id = $customer->id;
            $sAddr->user_type = $request->user_type ?? 'customer';
            $sAddr->addr_type = 'shipping';
            $sAddr->address1 = $request->sa_address1;
            $sAddr->address2 = $request->sa_address2;
            $sAddr->pincode = $request->sa_pincode;
            $sAddr->city = $request->sa_city;
            $sAddr->state = $request->sa_state;
            $sAddr->save();
            if (!$customer->pin_code) {
                $customer->pin_code = $request->sa_pincode;
                $customer->update();
            }
        }
        $customer  = StoreCustomer::with('billing_address', 'shipping_address')->where('id', $customer->id)->first();
        if ($request->form_type == 'ajax') {
            return response()->json(['status' => true, 'msg' => "Added  Successfully", 'action' => 'add_customer', 'customer' => $customer]);
        } else {
            Toastr::success('Client added successfully');
            return back();
        }
    }
    public function delete(Request $request, $id)
    {
        $client  = StoreCustomer::findOrFail($id);
        Helpers::delete_file('profile/',  $client->profile_pic);
        $client->delete();
        Toastr::success('Deleted Successfully');
        return back();
    }
    public function bulk_delete(Request $request)
    {
        foreach ($request->client_ids as $key => $value) {
            $client = StoreCustomer::where('id', $value)->first();
            Helpers::delete_file('profile/',  $client->profile_pic);
            $client->delete();
        }
        Toastr::success('Bulk Deleted Successfully');
    }
    public function fetch_details(Request $request)
    {
        return  StoreCustomer::with('billing_address', 'shipping_address')->where('id', $request->id)->first();
    }
    public function update(Request $request)
    {
        // prx($request->all());
        $request->validate([
            'f_name' => 'required',
            'phone' => 'required|max:10',
        ]);

        $phone = _cleanPhoneNumber($request->phone);
        // get fcm token =========
        $customer = StoreCustomer::find($request->customer_id);
        $customer->f_name = $request->f_name;
        $customer->address = $request->address;
        $customer->gst = $request->gst;
        $customer->email = $request->email;
        $customer->phone = $phone;
        $customer->id_number = $request->id_number;
        if (!empty($request->file('profile_pic'))) {
            $profile_pic = Helpers::upload('profile/', 'png', $request->file('profile_pic'));

            //delete old 
            if ($customer->profile_pic) {
                if (Storage::disk('public')->exists('profile/' . $customer->profile_pic)) {
                    Storage::disk('public')->delete('profile/' . $customer->profile_pic);
                }
            }
            $customer->profile_pic = $profile_pic;
        }
        if (!empty($request->file('id_proof'))) {
            $extension = $request->file('id_proof')->getClientOriginalExtension(); // e.g. jpg, pdf, png
            $id_proof = Helpers::upload('customer/documents/', $extension, $request->file('id_proof'));

            //delete old  
            if ($customer->id_proof) {
                if (Storage::disk('public')->exists('customer/documents/' . $customer->id_proof)) {
                    Storage::disk('public')->delete('customer/documents/' . $customer->id_proof);
                }
            }
            $customer->id_proof = $id_proof;
        }
        $customer->save();
        $user_t = $customer->user_type == 'customer' ? 'store_customer' : 'store_vendor';
        // shipping address

        $bAddr = UserAddress::where('user_id', $customer->id)->where("addr_type", 'billing')->first();

        if (!$bAddr) {
            $bAddr = new UserAddress();
            $bAddr->user_id = $customer->id;
            $bAddr->user_type = $user_t;
            $bAddr->addr_type = 'billing';
        }
        $bAddr->address1 = $request->ba_address1;
        $bAddr->address2 = $request->ba_address2;
        $bAddr->pincode = $request->ba_pincode;
        $bAddr->city = $request->ba_city;
        $bAddr->state = $request->ba_state;
        $bAddr->save();
        $customer->pin_code = $request->ba_pincode;
        $customer->update();

        // shipping address
        $sAddr = UserAddress::where('user_id', $customer->id)->where("addr_type", 'shipping')->first();
        if (!$sAddr) {
            $sAddr = new UserAddress();
            $sAddr->user_id = $customer->id;
            $sAddr->user_type = $user_t;
            $sAddr->addr_type = 'shipping';
        }
        $sAddr->address1 = $request->sa_address1;
        $sAddr->address2 = $request->sa_address2;
        $sAddr->pincode = $request->sa_pincode;
        $sAddr->city = $request->sa_city;
        $sAddr->state = $request->sa_state;
        $sAddr->save();
        $customer->pin_code = $request->sa_pincode;
        $customer->update();

        Toastr::success('Customer info updated successfully');
        return back();
        // return redirect()->route('vendor.customer.list');
    }

    public function comment_save(Request $request)
    {
        $comment = new StoreUserComment();
        $comment->user_id =  $request->user_id;
        $comment->store_id = Helpers::get_store_id();
        $comment->comment = $request->comment;
        $comment->save();

        Toastr::success('Comment Added Successfully');
        return back();
    }
    public function list(Request $request, $tab = null)
    {
        // prx($request->all());
        $type = ($request->type == 'all' || empty($request->type)) ? '' : $request->type;
        $customers = StoreCustomer::where('store_id', Helpers::get_store_id())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('phone', 'like', '%' . $search . '%')
                        ->orWhere('f_name', 'like', '%' . $search . '%');
                });
            })
            ->when($type, function ($query) use ($type) {
                $query->where('user_type', $type);
            })
            ->paginate(50);
        $search = $request->search ?? '';
        return view('vendor-views.customer.list', compact('tab', 'customers', 'search'));
    }
}
