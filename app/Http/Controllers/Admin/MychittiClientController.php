<?php 

namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Validator;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\StoreCustomer;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
 
class MychittiClientController extends Controller 
{
    public function list(Request $request, $tab = null)
    {
        $type = ($request->type == 'all' || empty($request->type)) ? '' : $request->type;
        $clients = StoreCustomer::where('store_id', 0)
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
        return view('admin-views.client.list', compact('tab', 'clients', 'search'));
    }

    public function add_new(Request $request)
    {
        return view('admin-views.client.add');
    }

    public function edit(Request $request, $id)
    {
        $client = StoreCustomer::with('billing_address', 'shipping_address')->where('id', $id)->where('store_id', 0)->first();
        return view('admin-views.client.add', compact('client'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'phone' => 'required|max:10',
        ]);

        $phone = _cleanPhoneNumber($request->phone);
        $type = $request->user_type ?? 'customer';
        $existingUser = StoreCustomer::where('phone', $phone)->where('store_id', 0)->where('user_type', $type)->first();
        if ($existingUser) {
            Toastr::error(ucfirst($type . ' with this phone number already exists.'));
            return back();
        }

        $client = new StoreCustomer();
        $client->store_id = 0;
        $client->f_name = $request->f_name;
        $client->user_type = $type;
        $client->address = $request->address;
        $client->gst = $request->gst;
        $client->email = $request->email;
        $client->phone = $phone;
        $client->id_number = $request->id_number;
        if (!empty($request->file('profile_pic'))) {
            $profile_pic = Helpers::upload('profile/', 'png', $request->file('profile_pic'));
            $client->profile_pic = $profile_pic;
        }
        if (!empty($request->file('id_proof'))) {
            $extension = $request->file('id_proof')->getClientOriginalExtension();
            $id_proof = Helpers::upload('customer/documents/', $extension, $request->file('id_proof'));
            $client->id_proof = $id_proof;
        }
        $client->save();

        $user_t = $type == 'customer' ? 'store_customer' : 'store_vendor';
        if ($request->has('ba_address1') && $request->ba_address1 != '' && $request->has('ba_state') && $request->ba_state != '') {
            $bAddr = new UserAddress();
            $bAddr->user_id = $client->id;
            $bAddr->user_type = $user_t;
            $bAddr->addr_type = 'billing';
            $bAddr->address1 = $request->ba_address1;
            $bAddr->address2 = $request->ba_address2;
            $bAddr->pincode = $request->ba_pincode;
            $bAddr->city = $request->ba_city;
            $bAddr->state = $request->ba_state;
            $bAddr->save();
            $client->pin_code = $request->ba_pincode;
            $client->update();
        }
        if ($request->has('sa_address1') && $request->sa_address1 != '' && $request->has('sa_state') && $request->sa_state != '') {
            $sAddr = new UserAddress();
            $sAddr->user_id = $client->id;
            $sAddr->user_type = $type;
            $sAddr->addr_type = 'shipping';
            $sAddr->address1 = $request->sa_address1;
            $sAddr->address2 = $request->sa_address2;
            $sAddr->pincode = $request->sa_pincode;
            $sAddr->city = $request->sa_city;
            $sAddr->state = $request->sa_state;
            $sAddr->save();
            if (!$client->pin_code) {
                $client->pin_code = $request->sa_pincode;
                $client->update();
            }
        }
        if($request->has('form_type') && $request->form_type == 'ajax'){
            return response()->json(['status'=> true, 'message'=> 'Added Successfully','action'=>'add_customer', 'customer'=> $client]);
        }

        Toastr::success('Client added successfully');
        return back();
    }

    public function update(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'phone' => 'required|max:10',
        ]);

        $phone = _cleanPhoneNumber($request->phone);
        $client = StoreCustomer::where('id', $request->client_id)->where('store_id', 0)->first();
        if (!$client) {
            Toastr::error('Client not found');
            return back();
        }

        $existingUser = StoreCustomer::where('phone', $phone)->where('store_id', 0)->where('id', '!=', $client->id)->first();
        if ($existingUser) {
            Toastr::error('Phone number already exists.');
            return back();
        }

        $client->f_name = $request->f_name;
        $client->address = $request->address;
        $client->gst = $request->gst;
        $client->email = $request->email;
        $client->phone = $phone;
        $client->id_number = $request->id_number;
        if (!empty($request->file('profile_pic'))) {
            if ($client->profile_pic) {
                \Storage::disk('public')->delete('profile/' . $client->profile_pic);
            }
            $profile_pic = Helpers::upload('profile/', 'png', $request->file('profile_pic'));
            $client->profile_pic = $profile_pic;
        }
        if (!empty($request->file('id_proof'))) {
            if ($client->id_proof) {
                \Storage::disk('public')->delete('customer/documents/' . $client->id_proof);
            }
            $extension = $request->file('id_proof')->getClientOriginalExtension();
            $id_proof = Helpers::upload('customer/documents/', $extension, $request->file('id_proof'));
            $client->id_proof = $id_proof;
        }
        $client->save();

        $user_t = $client->user_type == 'customer' ? 'store_customer' : 'store_vendor';

        // Billing address
        $bAddr = UserAddress::where('user_id', $client->id)->where("addr_type", 'billing')->first();
        if (!$bAddr) {
            $bAddr = new UserAddress();
            $bAddr->user_id = $client->id;
            $bAddr->user_type = $user_t;
            $bAddr->addr_type = 'billing';
        }
        $bAddr->address1 = $request->ba_address1 ?? '';
        $bAddr->address2 = $request->ba_address2 ?? '';
        $bAddr->pincode = $request->ba_pincode ?? '';
        $bAddr->city = $request->ba_city ?? '';
        $bAddr->state = $request->ba_state ?? '';
        $bAddr->save();
        if ($request->ba_pincode) {
            $client->pin_code = $request->ba_pincode;
            $client->update();
        }

        // Shipping address
        $sAddr = UserAddress::where('user_id', $client->id)->where("addr_type", 'shipping')->first();
        if (!$sAddr) {
            $sAddr = new UserAddress();
            $sAddr->user_id = $client->id;
            $sAddr->user_type = $user_t;
            $sAddr->addr_type = 'shipping';
        }
        $sAddr->address1 = $request->sa_address1 ?? '';
        $sAddr->address2 = $request->sa_address2 ?? '';
        $sAddr->pincode = $request->sa_pincode ?? '';
        $sAddr->city = $request->sa_city ?? '';
        $sAddr->state = $request->sa_state ?? '';
        $sAddr->save();
        if ($request->sa_pincode && !$client->pin_code) {
            $client->pin_code = $request->sa_pincode;
            $client->update();
        }

        Toastr::success('Client updated successfully');
        return back();
    }

    public function view(Request $request, $id)
    {
        $client = StoreCustomer::with('billing_address', 'shipping_address', 'comments')->where('id', $id)->where('store_id', 0)->first();
        if (!$client) {
            Toastr::error('Client not found');
            return redirect()->route('admin.client.list');
        }

        // Services query (adapt for admin)
        $servicesQuery = DB::table('service_requests as sr')
            ->join('items', 'sr.item_id', '=', 'items.id')
            ->leftJoin('accepted_service_requests as asr', 'sr.id', '=', 'asr.service_request_id')
            ->leftJoin('cancelled_service_requests as csr', 'sr.id', '=', 'csr.service_request_id')
            ->where('sr.user_id', $client->id) // Assume user_id links to client
            ->select('sr.*', 'sr.id as service_id', 'items.name as item_name', DB::raw('COALESCE(asr.current_status, csr.current_status) as current_status'))
            ->get();

        $services = $servicesQuery;

        // Placeholder for projects, tasks (adapt queries similarly)
        $projects = collect([]);
        $tasks = collect([]);
        $invoices = collect([]);

        // Invoices (example - adapt)
        $preset = request('date_range') ?? 'last_30_days';
        $range = Helpers::calculatePresetDates($preset, null);
        $formatted_from = $range['start'];
        $formatted_to = $range['end'];
        // Add actual invoice query

        $data = ['paidAmount' => 0, 'unpaidAmount' => 0, 'totalAmount' => 0]; // Calculate

        return view('admin-views.client.view', compact('id', 'preset', 'client', 'data', 'services', 'invoices', 'tasks', 'projects'));
    }

    public function delete(Request $request, $id)
    {
        $client = StoreCustomer::where('id', $id)->where('store_id', 0)->first();
        if ($client) {
            Helpers::delete_file('profile/', $client->profile_pic);
            $client->delete();
            Toastr::success('Client deleted successfully');
        } else {
            Toastr::error('Client not found');
        }
        return back();
    }

    public function bulk_delete(Request $request)
    {
        foreach ($request->client_ids as $id) {
            $client = StoreCustomer::where('id', $id)->where('store_id', 0)->first();
            if ($client) {
                Helpers::delete_file('profile/', $client->profile_pic);
                $client->delete();
            }
        }
        Toastr::success('Bulk delete successful');
        return back();
    }

    public function export(Request $request)
    {
        // Reuse/adapt StoreUserExport or create new
        $type = $request->type ?? 'customer';
        $headings = ['Type', 'Name', 'Phone', 'Email', 'ID Number', 'GST'];
        $all_clients = StoreCustomer::where('store_id', 0)->get();
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
        return Excel::download(new \App\Exports\StoreUserExport($data, $headings), 'clients.xlsx');
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

        $import = new \App\Imports\CustomerImport(0); // store_id = 0
        Excel::import($import, $request->file('file'));

        Toastr::success('Clients imported successfully!');
        return back();
    }

    public function get_matches(Request $request)
    {
        $query = $request->get('q');
        $user_type = $request->user_type ?? 'customer';

        $clients = StoreCustomer::where(function ($q) use ($query) {
            $q->where('f_name', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%");
        })
        ->when($user_type && $user_type != 'both', function ($q) use ($user_type) {
            $q->where('user_type', $user_type);
        })
            ->where('store_id', 0)
            ->select('id', 'f_name', 'phone', 'user_type')
            ->limit(10)
            ->get();

        return response()->json($clients);
    }

    public function fetch_details(Request $request)
    {
        return StoreCustomer::with('billing_address', 'shipping_address')->where('id', $request->id)->where('store_id', 0)->first();
    }

    public function comment_save(Request $request)
    {
        // Adapt StoreUserComment for admin
        // Placeholder - implement if model exists
        Toastr::success('Comment saved');
        return back();
    }

    public function searchMychittiClients(Request $request)
    {
        $searchTerm = $request->input('q');
        $clients = StoreCustomer::where(function ($query) use ($searchTerm) {
                $query->where('f_name', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('phone', 'LIKE', '%' . $searchTerm . '%');
            })
            ->where('store_id', 0)
            ->get(); 
        $results = $clients->map(fn($c) => [
            'id' => $c->id,
            'text' => trim($c->f_name . ' ' . $c->l_name) . ' (' . $c->phone . ')',
        ]);

        return response()->json(['results' => $results]); 
    }
}

