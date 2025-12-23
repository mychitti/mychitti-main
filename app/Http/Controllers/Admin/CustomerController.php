<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Order;
use App\Models\Newsletter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\CentralLogics\SMS_module;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\DB;
use App\Exports\CustomerListExport;
use App\Exports\CustomerOrderExport;
use App\Exports\SubscriberListExport;
use App\Imports\CustomerImport;
use App\Http\Controllers\Controller;
use App\Mail\EmailVerification;
use App\Models\Cart;
use App\Models\Store;
use App\Models\WalletTransaction;
use App\Models\Wishlist;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as RulesPassword;
use Maatwebsite\Excel\Facades\Excel;
use PSpell\Config;
use Rap2hpoutre\FastExcel\FastExcel;

class CustomerController extends Controller
{
    public function __construct()
    {
        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
    }
    public function customer_list(Request $request)
    {
        // mark all as checked
        User::query()->update(['checked' => 1]);

        $zone_id =  $request->zone_id ?? null;
        $filter =  $request->filter ?? null;
        $order_wise =  $request->order_wise ?? null;
        $key = [];
        if ($request->search) {
            $key = explode(' ', $request['search']);
        }
        $customers = User::when(count($key) > 0, function ($query) use ($key) {
            foreach ($key as $value) {
                $query->orWhere('f_name', 'like', "%{$value}%")
                    ->orWhere('l_name', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%")
                    ->orWhere('phone', 'like', "%{$value}%");
            };
        })->withcount('orders')

            ->when(isset($zone_id) && is_numeric($zone_id), function ($query) use ($zone_id) {
                $query->where('zone_id', $zone_id);
            })
            ->when(isset($filter) && $filter == 'active', function ($query) {
                $query->where('status', 1);
            })
            ->when(isset($filter) && $filter == 'blocked', function ($query) {
                $query->where('status', 0);
            })
            ->when(isset($filter) && $filter == 'new', function ($query) {
                $query->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
            })
            ->when(isset($order_wise) && $order_wise == 'top', function ($query) {
                $query->orderBy('orders_count', 'desc');
            })
            ->when(isset($order_wise) && $order_wise == 'least', function ($query) {
                $query->orderBy('orders_count', 'asc');
            })
            ->when(isset($order_wise) && $order_wise == 'latest', function ($query) {
                $query->latest();
            })
            ->when(!$order_wise, function ($query) {
                $query->orderBy('orders_count', 'desc');
            })

            ->paginate(config('default_pagination'));

        return view('admin-views.customer.list', compact('customers'));
    }

    public function upload_excel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required',
        ]);

        if ($validator->fails()) {
            Toastr::warning("Please uplaod excel file");
            return redirect()->back();
        }
        
        $import = new CustomerImport();
        Excel::import($import, $request->file('file'));

        if ($import->failedRows) {
            Toastr::warning($import->failedRows . '. Uploaded Successfully');
        }else{
            Toastr::success('Customers list imported successfully!');
        }
        return redirect()->back();

    }
    public function add_new(User $customer, Request $request)
    {
        return view('admin-views.customer.add');
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|unique:users',
            'phone' => 'required|unique:users|max:10',
            'password' => ['required', RulesPassword::min(8)],
        ], [
            'f_name.required' => 'The first name field is required.',
            'l_name.required' => 'The last name field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }
        $ref_by = null;
        $customer_verification = BusinessSetting::where('key', 'customer_verification')->first()->value;

        if ($request->ref_code) {
            $ref_status = BusinessSetting::where('key', 'ref_earning_status')->first()->value;
            if ($ref_status != '1') {
                return response()->json(['errors' => Helpers::error_formater('ref_code', translate('messages.referer_disable'))], 403);
            }

            $referar_user = User::where('ref_code', '=', $request->ref_code)->first();
            if (!$referar_user || !$referar_user->status) {
                return response()->json(['errors' => Helpers::error_formater('ref_code', translate('messages.referer_code_not_found'))], 405);
            }

            if (WalletTransaction::where('reference', $request->phone)->first()) {
                return response()->json(['errors' => Helpers::error_formater('phone', translate('Referrer code already used'))], 203);
            }

            $notification_data = [
                'title' => translate('messages.Your_referral_code_is_used_by') . ' ' . $request->f_name . ' ' . $request->l_name,
                'description' => translate('Be prepare to receive when they complete there first purchase'),
                'order_id' => 1,
                'image' => '',
                'type' => 'referral_code',
            ];

            if ($referar_user?->cm_firebase_token) {
                Helpers::send_push_notif_to_device($referar_user?->cm_firebase_token, $notification_data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($notification_data),
                    'user_id' => $referar_user?->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            $ref_by = $referar_user->id;
        }

        $user = User::create([
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'ref_by' =>   $ref_by,
            'password' => bcrypt($request->password),
        ]);
        $user->ref_code = Helpers::generate_referer_code($user);
        $user->save();

        $token = $user->createToken('RestaurantCustomerAuth')->accessToken;

        if ($customer_verification && env('APP_MODE') != 'demo') {
            $otp_interval_time = 60; //seconds
            $verification_data = DB::table('phone_verifications')->where('phone', $request['phone'])->first();

            if (isset($verification_data) &&  Carbon::parse($verification_data->updated_at)->DiffInSeconds() < $otp_interval_time) {
                $time = $otp_interval_time - Carbon::parse($verification_data->updated_at)->DiffInSeconds();
                $errors = [];
                array_push($errors, ['code' => 'otp', 'message' =>  translate('messages.please_try_again_after_') . $time . ' ' . translate('messages.seconds')]);
                return response()->json([
                    'errors' => $errors
                ], 405);
            }

            $otp = rand(1000, 9999);
            DB::table('phone_verifications')->updateOrInsert(
                ['phone' => $request['phone']],
                [
                    'token' => $otp,
                    'otp_hit_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $mail_status = Helpers::get_mail_status('registration_otp_mail_status_user');
            if (config('mail.status') && $mail_status == '1') {
                Mail::to($request['email'])->send(new EmailVerification($otp, $request->f_name));
            }
            //for payment and sms gateway addon
            $published_status = 0;
            $payment_published_status = config('get_payment_publish_status');
            if (isset($payment_published_status[0]['is_published'])) {
                $published_status = $payment_published_status[0]['is_published'];
            }

            if ($published_status == 1) {
                $response = SmsGateway::send($request['phone'], $otp);
            } else {
                $response = SMS_module::send($request['phone'], $otp);
            }
            if ($response != 'success') {
                $errors = [];
                array_push($errors, ['code' => 'otp', 'message' => translate('messages.faield_to_send_sms')]);
                return response()->json([
                    'errors' => $errors
                ], 405);
            }
        }
        try {
            $mail_status = Helpers::get_mail_status('registration_mail_status_user');
            if (config('mail.status') && $request->email && $mail_status == '1') {
                Mail::to($request->email)->send(new \App\Mail\CustomerRegistration($request->f_name . ' ' . $request->l_name));
            }
        } catch (\Exception $ex) {
            info($ex->getMessage());
        }
        if ($request->guest_id  && isset($user->id)) {

            $userStoreIds = Cart::where('user_id', $request->guest_id)
                ->join('items', 'carts.item_id', '=', 'items.id')
                ->pluck('items.store_id')
                ->toArray();

            Cart::where('user_id', $user->id)
                ->whereHas('item', function ($query) use ($userStoreIds) {
                    $query->whereNotIn('store_id', $userStoreIds);
                })
                ->delete();

            Cart::where('user_id', $request->guest_id)->update(['user_id' => $user->id, 'is_guest' => 0]);
        }
        Toastr::success('Customer added successfully');
        return redirect()->route('admin.users.customer.list');
        // return redirect()->json(['token' => $token, 'is_phone_verified' => 0, 'message' => 'Account Created Successfully', 'phone_verify_end_url' => "api/v1/auth/verify-phone"], 200);
    }
    public function customer_cart(User $customer, Request $request)
    {
        $carts = DB::table('carts')->join('users', 'users.id', 'carts.user_id')->join('items', 'items.id', 'carts.item_id')->paginate(10);
        return view('admin-views.customer.cart', compact('carts'));
    }
    public function status(User $customer, Request $request)
    {
        $customer->status = $request->status;
        $customer->save();

        try {
            if ($request->status == 0) {
                $customer->tokens->each(function ($token, $key) {
                    $token->delete();
                });
                if (isset($customer->cm_firebase_token)) {
                    $data = [
                        'title' => translate('messages.suspended'),
                        'description' => translate('messages.your_account_has_been_blocked'),
                        'order_id' => '',
                        'image' => '',
                        'type' => 'block'
                    ];
                    Helpers::send_push_notif_to_device($customer->cm_firebase_token, $data);

                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'user_id' => $customer->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Toastr::warning(translate('messages.push_notification_faild'));
        }

        Toastr::success(translate('messages.customer') . translate('messages.status_updated'));
        return back();
    }

    public function search(Request $request)
    {
        $key = explode(' ', $request['search']);
        $customers = User::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('f_name', 'like', "%{$value}%")
                    ->orWhere('l_name', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%")
                    ->orWhere('phone', 'like', "%{$value}%");
            }
        })->orderBy('order_count', 'desc')->limit(50)->get();
        return response()->json([
            'count' => count($customers),
            'view' => view('admin-views.customer.partials._table', compact('customers'))->render()
        ]);
    }

    public function view(Request $request, $id)
    {
        $key = $request['search'];
        $customer = User::find($id);
        if (isset($customer)) {
            $total_order_amount = Order::selectRaw('sum(order_amount) as total_order_amount')->latest()->where(['user_id' => $id])->whereNot('order_status', 'canceled')
                ->when(isset($key), function ($query) use ($key) {
                    $query->Where('id', 'like', "%{$key}%");
                })
                ->Notpos()->get();
            $orders = Order::withcount('details')->latest()->where(['user_id' => $id])
                ->when(isset($key), function ($query) use ($key) {
                    $query->Where('id', 'like', "%{$key}%");
                })
                ->Notpos()->paginate(config('default_pagination'));

            // ======================= service fetch  =========================

            $servicesQuery = DB::table('service_requests')
                ->join('items', 'service_requests.item_id', '=', 'items.id')
                ->join('categories', 'items.category_id', '=', 'categories.id')
                ->join('users', 'service_requests.user_id', '=', 'users.id')
                ->leftJoin('accepted_service_requests', function ($join) {
                    $join->on('service_requests.id', '=', 'accepted_service_requests.service_request_id');
                })
                ->leftJoin('cancelled_service_requests', function ($join) {
                    $join->on('service_requests.id', '=', 'cancelled_service_requests.service_request_id');
                })
                ->where('service_requests.user_id', $id)
                ->select(
                    'service_requests.*',
                    'service_requests.id as service_id',
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

            $services = $servicesQuery->get()->toArray();


            // ======================= service fetch end =========================

            // Merge the two arrays
            // prx($services);
            // prx($services); die;

            $productWishlist = DB::table('wishlists')->join('items', 'items.id', 'wishlists.item_id')->where('wishlists.user_id', $id)->whereNotNull('item_id')->get();
            $storeWishlist = DB::table('wishlists')->join('stores', 'stores.id', 'wishlists.store_id')->where('wishlists.user_id', $id)->join('zones', 'zones.id', 'stores.zone_id')->select('zones.name as zone_name', 'stores.*')->whereNotNull('store_id')->get();
            // prx($productWishlist);
            return view('admin-views.customer.customer-view', compact('services', 'customer', 'orders', 'total_order_amount', 'productWishlist', 'storeWishlist'));
        }
        Toastr::error(translate('messages.customer_not_found'));
        return back();
    }

    public function customer_order_export(Request $request)
    {
        $customer = User::find($request->id);

        $orders = Order::latest()->where(['user_id' => $request->id])->Notpos()->get();

        $data = [
            'orders' => $orders,
            'customer_id' => $customer->id,
            'customer_name' => $customer->f_name . ' ' . $customer->l_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
        ];

        if ($request->type == 'excel') {
            return Excel::download(new CustomerOrderExport($data), 'CustomerOrders.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new CustomerOrderExport($data), 'CustomerOrders.csv');
        }
    }

    public function subscribedCustomers(Request $request)
    {
        $key = explode(' ', $request['search']);
        $data['subscribedCustomers'] = Newsletter::orderBy('id', 'desc')

            ->when(isset($key), function ($query) use ($key) {
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('email', 'like', "%" . $value . "%");
                    }
                });
            })
            ->paginate(config('default_pagination'));
        return view('admin-views.customer.subscribed-emails', $data);
    }

    public function subscribed_customer_export(Request $request)
    {
        $key = explode(' ', $request['search']);
        $customers = Newsletter::orderBy('id', 'desc')

            ->when(isset($key), function ($query) use ($key) {
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('email', 'like', "%" . $value . "%");
                    }
                });
            })
            ->get();
        $data = [
            'customers' => $customers
        ];

        if ($request->type == 'excel') {
            return Excel::download(new SubscriberListExport($data), 'Subscribers.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new SubscriberListExport($data), 'Subscribers.csv');
        }
    }

    public function subscriberMailSearch(Request $request)
    {
        $key = explode(' ', $request['search']);
        $customers = Newsletter::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('email', 'like', "%" . $value . "%");
            }
        })

            ->orderBy('id', 'desc')->get();
        return response()->json([
            'count' => count($customers),
            'view' => view('admin-views.customer.partials._subscriber-email-table', compact('customers'))->render()
        ]);
    }

    public function get_customers(Request $request)
    {
        $key = explode(' ', $request['q']);
        $data = User::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('f_name', 'like', "%{$value}%")
                    ->orWhere('l_name', 'like', "%{$value}%")
                    ->orWhere('phone', 'like', "%{$value}%");
            }
        })
            ->limit(8)
            ->get([DB::raw('id, CONCAT(f_name, " ", l_name, " (", phone ,")") as text')]);
        if ($request->all) $data[] = (object)['id' => false, 'text' => translate('messages.all')];


        return response()->json($data);
    }

    public function save_pincode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'pin_code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }
        if ($request->type == 'customer') {
            $user = User::find($request->user_id);
        } else {
            $user = Store::find($request->user_id);
        }
        if ($user) {
            $user->pin_code = $request->pin_code;
            if ($user->save()) {
                return response()->json(['status' => true, 'msg' => 'updated successfully']);
            } else {
                return response()->json(['status' => false, 'msg' => 'some error occured']);
            }
        } else {
            return response()->json(['status' => false, 'msg' => 'user not found']);
        }
    }
    public function check_addr(Request $request)
    {
        if ($request->type == 'store') {
            $user = Store::find($request->user_id);
        } else {
            $user = User::find($request->user_id);
        }
        echo ($user && $user->pin_code) ?  1 :  0;
    }

    public function settings()
    {
        $data = BusinessSetting::where('key', 'like', 'wallet_%')
            ->orWhere('key', 'like', 'loyalty_%')
            ->orWhere('key', 'like', 'ref_earning_%')
            ->orWhere('key', 'like', 'ref_earning_%')->get();
        $data = array_column($data->toArray(), 'value', 'key');
        // dd($data);
        return view('admin-views.customer.settings', compact('data'));
    }

    public function update_settings(Request $request)
    {
        // dd($request->all());
        if (env('APP_MODE') == 'demo') {
            Toastr::info(translate('messages.update_option_is_disable_for_demo'));
            return back();
        }

        $request->validate([
            'add_fund_bonus' => 'nullable|numeric|max:100|min:0',
            'loyalty_point_exchange_rate' => 'nullable|numeric',
            'ref_earning_exchange_rate' => 'nullable|numeric',
        ]);
        BusinessSetting::updateOrInsert(['key' => 'customer_verification'], [
            'value' => $request['customer_verification_status'] ?? 0
        ]);
        BusinessSetting::updateOrInsert(['key' => 'wallet_status'], [
            'value' => $request['customer_wallet'] ?? 0
        ]);
        BusinessSetting::updateOrInsert(['key' => 'loyalty_point_status'], [
            'value' => $request['customer_loyalty_point'] ?? 0
        ]);
        BusinessSetting::updateOrInsert(['key' => 'ref_earning_status'], [
            'value' => $request['ref_earning_status'] ?? 0
        ]);
        BusinessSetting::updateOrInsert(['key' => 'wallet_add_refund'], [
            'value' => $request['refund_to_wallet'] ?? 0
        ]);
        BusinessSetting::updateOrInsert(['key' => 'loyalty_point_exchange_rate'], [
            'value' => $request['loyalty_point_exchange_rate'] ?? 0
        ]);
        BusinessSetting::updateOrInsert(['key' => 'ref_earning_exchange_rate'], [
            'value' => $request['ref_earning_exchange_rate'] ?? 0
        ]);
        BusinessSetting::updateOrInsert(['key' => 'loyalty_point_item_purchase_point'], [
            'value' => $request['item_purchase_point'] ?? 0
        ]);
        BusinessSetting::updateOrInsert(['key' => 'loyalty_point_minimum_point'], [
            'value' => $request['minimun_transfer_point'] ?? 0
        ]);
        BusinessSetting::updateOrInsert(['key' => 'add_fund_status'], [
            'value' => $request['add_fund_status'] ?? 0
        ]);

        Toastr::success(translate('messages.customer_settings_updated_successfully'));
        return back();
    }

    public function export(Request $request)
    {
        $key = [];
        if ($request->search) {
            $key = explode(' ', $request['search']);
        }
        $customers = User::when(count($key) > 0, function ($query) use ($key) {
            foreach ($key as $value) {
                $query->orWhere('f_name', 'like', "%{$value}%")
                    ->orWhere('l_name', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%")
                    ->orWhere('phone', 'like', "%{$value}%");
            };
        })
            ->orderBy('order_count', 'desc')->get();


        $data = [
            'customers' => $customers,
            'search' => $request->search ?? null,

        ];

        if ($request->type == 'excel') {
            return Excel::download(new CustomerListExport($data), 'Customers.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new CustomerListExport($data), 'Customers.csv');
        }
    }
}
