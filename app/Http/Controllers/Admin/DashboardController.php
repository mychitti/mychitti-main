<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\User;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Store;
use App\Models\Module;
use App\Models\Review;
use App\Models\Wishlist;
use App\Models\AdminRole;
use App\Scopes\ZoneScope;
use App\Models\DeliveryMan;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\OrderTransaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AcceptedServiceRequest;
use App\Models\ActionLog;
use App\Models\AdminAction;
use App\Models\GoogleAd;
use App\Models\InAppNotification;
use App\Models\ServiceInvoice;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Config;

class DashboardController extends Controller
{
    public function common_dashboard(Request $request)
    {
        return view("admin-views.dashboard-common");
    }
    public function google_ads(Request $request)
    {
        $ads = GoogleAd::paginate(10);
        return view("admin-views.google-ads", compact('ads'));
    }
    public function google_ads_save(Request $request)
    {
        $ad = new GoogleAd();
        $ad->name = $request->name;
        $ad->ad_id = $request->ad_id;
        $ad->save();
        Toastr::success('Saved successfully');
        return back();
    }
    public function google_ads_delete(Request $request, $id)
    {
        $ad = GoogleAd::findOrFail($id);
        $ad->delete();
        Toastr::success('Deleted successfully');
        return back();
    }
    public function google_ads_update(Request $request)
    {
        $ad = GoogleAd::findOrFail($request->id);
        $ad->name = $request->name;
        $ad->ad_id = $request->ad_id;
        $ad->save();
        Toastr::success('Updated successfully');
        return back();
    }
    public function action_logs(Request $request, $tab = 'common')
    {
        if ($tab == 'common') {
            $actions = ActionLog::with('admin')->latest()->paginate(10);
            return view("admin-views.logs.action-logs", compact('actions'));
        } else {
            $actions = AdminAction::with('admin')->latest()->paginate(10);
            return view("admin-views.logs.admin-action", compact('actions'));
        }
    }

    public function user_dashboard(Request $request)
    {
        $params = [
            'zone_id' => $request['zone_id'] ?? 'all',
            'module_id' => Config::get('module.current_module_id'),
            'statistics_type' => $request['statistics_type'] ?? 'overall',
            'user_overview' => $request['user_overview'] ?? 'overall',
            'commission_overview' => $request['commission_overview'] ?? 'this_year',
            'business_overview' => $request['business_overview'] ?? 'overall',
        ];

        session()->put('dash_params', $params);
        $data = self::dashboard_data($request);
        $total_sell = $data['total_sell'];
        $commission = $data['commission'];
        $delivery_commission = $data['delivery_commission'];
        $customers = User::zone($params['zone_id'])->take(2)->get();

        $delivery_man = DeliveryMan::with('last_location')->when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()
            ->limit(2)->get('image');

        $active_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()->Active()->count();

        $inactive_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()->where('application_status', 'approved')->where('active', 0)->count();

        $blocked_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()->where('application_status', 'approved')->where('status', 0)->count();

        $newly_joined_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'))->count();

        $reviews = Review::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->whereHas('item.store', function ($query) use ($params) {
                return $query->where('zone_id', $params['zone_id']);
            });
        })->count();

        $positive_reviews = Review::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->whereHas('item.store', function ($query) use ($params) {
                return $query->where('zone_id', $params['zone_id']);
            });
        })->whereIn('rating', [4, 5])->get()->count();
        $good_reviews = Review::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->whereHas('item.store', function ($query) use ($params) {
                return $query->where('zone_id', $params['zone_id']);
            });
        })->where('rating', 3)->count();
        $neutral_reviews = Review::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->whereHas('item.store', function ($query) use ($params) {
                return $query->where('zone_id', $params['zone_id']);
            });
        })->where('rating', 2)->count();
        $negative_reviews = Review::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->whereHas('item.store', function ($query) use ($params) {
                return $query->where('zone_id', $params['zone_id']);
            });
        })->where('rating', 1)->count();

        $from = now()->startOfMonth(); // first date of the current month
        $to = now();
        $this_month = User::zone($params['zone_id'])->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'))->count();
        $number = 12;
        $from = Carbon::now()->startOfYear()->format('Y-m-d');
        $to = Carbon::now()->endOfYear()->format('Y-m-d');

        $last_year_users = User::zone($params['zone_id'])
            ->whereMonth('created_at', 12)
            ->whereYear('created_at', now()->format('Y') - 1)
            ->count();

        $users = User::zone($params['zone_id'])
            ->select(
                DB::raw('(count(id)) as total'),
                DB::raw('YEAR(created_at) year, MONTH(created_at) month')
            )
            ->whereBetween('created_at', [Carbon::parse(now())->startOfYear(), Carbon::parse(now())->endOfYear()])
            ->groupBy('year', 'month')->get()->toArray();

        for ($inc = 1; $inc <= $number; $inc++) {
            $user_data[$inc] = 0;
            foreach ($users as $match) {
                if ($match['month'] == $inc) {
                    $user_data[$inc] = $match['total'];
                }
            }
        }

        $active_customers = User::zone($params['zone_id'])->where('status', 1)->count();
        $blocked_customers = User::zone($params['zone_id'])->where('status', 0)->count();
        $newly_joined = User::zone($params['zone_id'])->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'))->count();

        $employees = Admin::zone()->with(['role'])->where('role_id', '!=', '1')
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
            ->get();

        $deliveryMen = DeliveryMan::with('last_location')->when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })->zonewise()->available()->active()->get();

        $deliveryMen = Helpers::deliverymen_list_formatting($deliveryMen);

        $module_type = 'users';

        // prx($module_type);
        // {$module_type}
        return view("admin-views.dashboard-users", compact('data', 'reviews', 'this_month', 'user_data', 'neutral_reviews', 'good_reviews', 'negative_reviews', 'positive_reviews', 'employees', 'active_deliveryman', 'deliveryMen', 'inactive_deliveryman', 'newly_joined_deliveryman', 'delivery_man', 'total_sell', 'commission', 'delivery_commission', 'params', 'module_type', 'customers', 'active_customers', 'blocked_customers', 'newly_joined', 'last_year_users', 'blocked_deliveryman'));
    }

    public function mark_notif_read(Request $request)
    {
        $notif = InAppNotification::find($request->id);
        $notif->is_read = 1;
        $notif->save();
    }
    public function transaction_dashboard(Request $request)
    {
        $module_type = Config::get('module.current_module_type');
        return view("admin-views.dashboard-{$module_type}");
    }

    public function dispatch_dashboard(Request $request)
    {
        $params = [
            'zone_id' => $request['zone_id'] ?? 'all',
            'module_id' => Config::get('module.current_module_id'),
            'statistics_type' => $request['statistics_type'] ?? 'overall',
            'user_overview' => $request['user_overview'] ?? 'overall',
            'commission_overview' => $request['commission_overview'] ?? 'this_year',
            'business_overview' => $request['business_overview'] ?? 'overall',
        ];

        session()->put('dash_params', $params);
        $data = self::dashboard_data($request);
        $total_sell = $data['total_sell'];
        $commission = $data['commission'];
        $delivery_commission = $data['delivery_commission'];
        $label = $data['label'];
        $customers = User::zone($params['zone_id'])->take(2)->get();

        $delivery_man = DeliveryMan::with('last_location')->when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()
            ->limit(2)->get('image');

        $active_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()->where('active', 1)->count();

        $inactive_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()->where('application_status', 'approved')->where('active', 0)->count();

        $suspend_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()->where('application_status', 'approved')->where('status', 0)->count();

        $unavailable_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()->where('active', 1)->Unavailable()->count();

        $available_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()->where('active', 1)->Available()->count();

        $newly_joined_deliveryman = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'))->count();

        $deliveryMen = DeliveryMan::when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })->zonewise()->available()->active()->get();

        $deliveryMen = Helpers::deliverymen_list_formatting($deliveryMen);

        $module_type = 'dispatch';
        return view("admin-views.dashboard-dispatch", compact('data', 'active_deliveryman', 'deliveryMen', 'unavailable_deliveryman', 'available_deliveryman', 'inactive_deliveryman', 'newly_joined_deliveryman', 'delivery_man', 'total_sell', 'commission', 'delivery_commission', 'label', 'params', 'module_type', 'suspend_deliveryman'));
    }

    public function dashboard(Request $request)
    {
        if (_onlyStoreAddEdit()) {
            return redirect()->route('admin.store.list');
        }
        $params = [
            'zone_id' => $request['zone_id'] ?? 'all',
            'module_id' => Config::get('module.current_module_id'),
            'statistics_type' => $request['statistics_type'] ?? 'overall',
            'user_overview' => $request['user_overview'] ?? 'overall',
            'commission_overview' => $request['commission_overview'] ?? 'this_year',
            'business_overview' => $request['business_overview'] ?? 'overall',
        ];
        session()->put('dash_params', $params);
        $data = self::dashboard_data($request);
        $total_sell = $data['total_sell'];
        $commission = $data['commission'];
        $delivery_commission = $data['delivery_commission'];
        $label = $data['label'];
        $module_type = Config::get('module.current_module_type');

        if ($module_type == 'settings') {
            return redirect()->route('admin.business-settings.business-setup');
        }

        //   dd(session()->all());
        return view("admin-views.dashboard-{$module_type}", compact('data', 'total_sell', 'commission', 'delivery_commission', 'label', 'params', 'module_type'));
    }

    public function order(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'statistics_type') {
                $params['statistics_type'] = $request['statistics_type'];
            }
        }
        session()->put('dash_params', $params);

        if ($params['zone_id'] != 'all') {
            $store_ids = Store::where(['module_id' => $params['module_id']])->where(['zone_id' => $params['zone_id']])->pluck('id')->toArray();
        } else {
            $store_ids = Store::where(['module_id' => $params['module_id']])->pluck('id')->toArray();
        }
        $data = self::order_stats_calc($params['zone_id'], $params['module_id']);
        $module_type = Config::get('module.current_module_type');
        if ($params['module_id'] == 6) {
            return response()->json([
                'view' => view('admin-views.partials._dashboard-service-stats', compact('data'))->render()
            ], 200);
        } elseif ($module_type == 'parcel') {
            return response()->json([
                'view' => view('admin-views.partials._dashboard-order-stats-parcel', compact('data'))->render()
            ], 200);
        } elseif ($module_type == 'food') {
            return response()->json([
                'view' => view('admin-views.partials._dashboard-order-stats-food', compact('data'))->render()
            ], 200);
        }
        return response()->json([
            'view' => view('admin-views.partials._dashboard-order-stats', compact('data'))->render()
        ], 200);
    }

    public function zone(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'zone_id') {
                $params['zone_id'] = $request['zone_id'];
            }
        }
        session()->put('dash_params', $params);

        $data = self::dashboard_data($request);
        $total_sell = $data['total_sell'];
        $commission = $data['commission'];
        $popular = $data['popular'];
        $top_deliveryman = $data['top_deliveryman'];
        $top_rated_foods = $data['top_rated_foods'];
        $top_restaurants = $data['top_restaurants'];
        $top_customers = $data['top_customers'];
        $top_sell = $data['top_sell'];
        $delivery_commission = $data['delivery_commission'];
        $module_type = Config::get('module.current_module_type');

        return response()->json([
            'popular_restaurants' => view('admin-views.partials._popular-restaurants', compact('popular'))->render(),
            'top_deliveryman' => view('admin-views.partials._top-deliveryman', compact('top_deliveryman'))->render(),
            'top_rated_foods' => view('admin-views.partials._top-rated-foods', compact('top_rated_foods'))->render(),
            'top_restaurants' => view('admin-views.partials._top-restaurants', compact('top_restaurants'))->render(),
            'top_customers' => view('admin-views.partials._top-customer', compact('top_customers'))->render(),
            'top_selling_foods' => view('admin-views.partials._top-selling-foods', compact('top_sell'))->render(),

            'order_stats' => $module_type == 'parcel' ? view('admin-views.partials._dashboard-order-stats-parcel', compact('data'))->render() : ($module_type == 'food' ? view('admin-views.partials._dashboard-order-stats-food', compact('data'))->render() : (Config::get('module.current_module_id') == 5 ? view('admin-views.partials._dashboard-order-stats', compact('data'))->render() : view('admin-views.partials._dashboard-service-stats', compact('data'))->render())),

            'user_overview' => view('admin-views.partials._user-overview-chart', compact('data'))->render(),
            'monthly_graph' => view('admin-views.partials._monthly-earning-graph', compact('total_sell', 'commission', 'delivery_commission'))->render(),
            'stat_zone' => view('admin-views.partials._zone-change', compact('data'))->render(),
        ], 200);
    }

    public function user_overview(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'user_overview') {
                $params['user_overview'] = $request['user_overview'];
            }
        }
        session()->put('dash_params', $params);

        $data = self::user_overview_calc($params['zone_id'], $params['module_id']);
        $module_type = Config::get('module.current_module_type');
        if ($module_type == 'parcel') {
            return response()->json([
                'view' => view('admin-views.partials._user-overview-chart-parcel', compact('data'))->render()
            ], 200);
        }

        return response()->json([
            'view' => view('admin-views.partials._user-overview-chart', compact('data'))->render()
        ], 200);
    }
    public function commission_overview(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'commission_overview') {
                $params['commission_overview'] = $request['commission_overview'];
            }
        }
        session()->put('dash_params', $params);

        $data = self::dashboard_data($request);
        // prx($data);

        return response()->json([
            'view' => view('admin-views.partials._commission-overview-chart', compact('data'))->render(),
            'gross_sale' => view('admin-views.partials._gross_sale', compact('data'))->render()
        ], 200);
    }

    public function order_stats_calc($zone_id, $module_id)
    {
        $params = session('dash_params');
        $module_type = Config::get('module.current_module_type');

        if ($module_id && $params['statistics_type'] == 'today') {
            $accepted_by_dm = Order::AccepteByDeliveryman()->where('module_id', $module_id)->whereDate('accepted', Carbon::now());
            $preparing_in_rs = Order::Preparing()->where('module_id', $module_id)->whereDate('processing', Carbon::now());
            $picked_up = Order::ItemOnTheWay()->where('module_id', $module_id)->whereDate('picked_up', Carbon::now());
            $refund_requested = Order::where('module_id', $module_id)->where(['order_status' => 'refund_requested'])->whereDate('refund_requested', Carbon::now());
            $refunded = Order::where('module_id', $module_id)->where(['order_status' => 'refunded'])->whereDate('refunded', Carbon::now());
            $new_orders = Order::where('module_id', $module_id)->whereDate('schedule_at', Carbon::now());
            $new_stores = Store::where('module_id', $module_id)->whereDate('created_at', Carbon::now());
            $new_customers = User::whereDate('created_at', Carbon::now());
            if ($module_type == 'parcel') {
                $total_orders = Order::where('module_id', $module_id)->whereDate('created_at', Carbon::now());
            } else {
                $total_orders = Order::where('module_id', $module_id);
            }
            if ($module_id == 5) {
                $canceled = Order::where('module_id', $module_id)->where(['order_status' => 'canceled'])->whereDate('canceled', Carbon::now());

                $delivered = Order::Delivered()->where('module_id', $module_id)->whereDate('delivered', Carbon::now());

                $searching_for_dm = Order::SearchingForDeliveryman()->where('module_id', $module_id)->whereDate('created_at', Carbon::now());

                $new_items = Item::where('module_id', $module_id)->whereDate('created_at', Carbon::now());
                $total_items = Item::where('module_id', $module_id);
            } else {

                $delivered = DB::table('accepted_service_requests')->where('current_status', 'Completed')->whereDate('completed_at', Carbon::now());
                $canceled = DB::table('accepted_service_requests')->where('current_status', 'Cancelled')->whereDate('created_at', Carbon::now());

                $searching_for_dm = DB::table('accepted_service_requests')->whereNot('assigned_status', 'Assigned')->where('module_id', $module_id)->whereDate('created_at', Carbon::now());
                $accepted_services = DB::table('accepted_service_requests')->where('assigned_status', 'Assigned')->where('module_id', $module_id)->whereDate('created_at', Carbon::now());

                $new_items = DB::table('items')->where('module_id', $module_id)->whereDate('created_at', Carbon::now());
                $total_items = DB::table('items')->where('module_id', $module_id)->get();
                $service_leads = DB::table('accepted_service_requests')->get();
            }
            $total_stores = Store::where('module_id', $module_id);
            $total_customers = User::all();
        } elseif ($module_id && $params['statistics_type'] == 'this_year') {
            $accepted_by_dm = Order::AccepteByDeliveryman()->where('module_id', $module_id)->whereYear('accepted', now()->format('Y'));
            $preparing_in_rs = Order::Preparing()->where('module_id', $module_id)->whereYear('processing', now()->format('Y'));
            $picked_up = Order::ItemOnTheWay()->where('module_id', $module_id)->whereYear('picked_up', now()->format('Y'));
            $refund_requested = Order::where('module_id', $module_id)->where(['order_status' => 'refund_requested'])->whereYear('refund_requested', now()->format('Y'));
            $refunded = Order::where('module_id', $module_id)->where(['order_status' => 'refunded'])->whereYear('refunded', now()->format('Y'));
            $new_orders = Order::where('module_id', $module_id)->whereYear('schedule_at', now()->format('Y'));
            $new_stores = Store::where('module_id', $module_id)->whereYear('created_at', now()->format('Y'));
            $new_customers = User::whereYear('created_at', now()->format('Y'));
            $total_orders = Order::where('module_id', $module_id);
            if ($module_id == 5) {
                $delivered = Order::Delivered()->where('module_id', $module_id)->whereYear('delivered', now()->format('Y'));
                $searching_for_dm = Order::SearchingForDeliveryman()->where('module_id', $module_id)->whereYear('created_at', now()->format('Y'));
                $canceled = Order::where('module_id', $module_id)->where(['order_status' => 'canceled'])->whereYear('canceled', now()->format('Y'));

                $total_items = Item::where('module_id', $module_id);
                $new_items = Item::where('module_id', $module_id)->whereYear('created_at', now()->format('Y'));
            } else {
                $canceled = DB::table('accepted_service_requests')->where('current_status', 'Cancelled')->whereYear('created_at', now()->format('Y'));
                $delivered = DB::table('accepted_service_requests')->where('current_status', 'Completed')->whereYear('completed_at', now()->format('Y'));

                $searching_for_dm = DB::table('accepted_service_requests')->whereNot('assigned_status', 'Assigned')->whereYear('created_at', now()->format('Y'));
                $accepted_services  = DB::table('accepted_service_requests')->where('assigned_status', 'Assigned')->whereYear('created_at', now()->format('Y'));

                $total_items = DB::table('items')->where('module_id', $module_id)->get();
                $new_items = DB::table('items')->where('module_id', $module_id)->whereYear('created_at', now()->format('Y'));
                $service_leads = DB::table('accepted_service_requests')->whereYear('created_at', now()->format('Y'));
            }
            $total_stores = Store::where('module_id', $module_id);
            $total_customers = User::all();
        } elseif ($module_id && $params['statistics_type'] == 'this_month') {
            $accepted_by_dm = Order::AccepteByDeliveryman()->where('module_id', $module_id)->whereMonth('accepted', now()->format('m'))->whereYear('accepted', now()->format('Y'));
            $preparing_in_rs = Order::Preparing()->where('module_id', $module_id)->whereMonth('processing', now()->format('m'))->whereYear('processing', now()->format('Y'));
            $picked_up = Order::ItemOnTheWay()->where('module_id', $module_id)->whereMonth('picked_up', now()->format('m'))->whereYear('picked_up', now()->format('Y'));
            $refund_requested = Order::where('module_id', $module_id)->where(['order_status' => 'refund_requested'])->whereMonth('refund_requested', now()->format('m'))->whereYear('refund_requested', now()->format('Y'));
            $refunded = Order::where('module_id', $module_id)->where(['order_status' => 'refunded'])->whereMonth('refunded', now()->format('m'))->whereYear('refunded', now()->format('Y'));
            $new_orders = Order::where('module_id', $module_id)->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            $new_stores = Store::where('module_id', $module_id)->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            $new_customers = User::whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            $total_orders = Order::where('module_id', $module_id);
            if ($module_id == 5) {
                $searching_for_dm = Order::SearchingForDeliveryman()->where('module_id', $module_id)->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                $delivered = Order::Delivered()->where('module_id', $module_id)->whereMonth('delivered', now()->format('m'))->whereYear('delivered', now()->format('Y'));
                $canceled = Order::where('module_id', $module_id)->where(['order_status' => 'canceled'])->whereMonth('canceled', now()->format('m'))->whereYear('canceled', now()->format('Y'));

                $new_items = Item::where('module_id', $module_id)->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                $total_items = Item::where('module_id', $module_id);
            } else {
                $canceled =  DB::table('accepted_service_requests')->where('current_status', 'Cancelled')->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                $delivered =  DB::table('accepted_service_requests')->where('current_status', 'Completed')->whereMonth('completed_at', now()->format('m'))->whereYear('completed_at', now()->format('Y'));

                $searching_for_dm = DB::table('accepted_service_requests')->whereNot('assigned_status', 'Assigned')->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                $accepted_services = DB::table('accepted_service_requests')->where('assigned_status', 'Assigned')->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));

                $new_items = DB::table('items')->where('module_id', $module_id)->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                $total_items = DB::table('items')->where('module_id', $module_id)->get();
                $service_leads = DB::table('accepted_service_requests')->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            }
            $total_stores = Store::where('module_id', $module_id);
            $total_customers = User::all();
        } elseif ($module_id && $params['statistics_type'] == 'this_week') {
            $accepted_by_dm = Order::AccepteByDeliveryman()->where('module_id', $module_id)->whereBetween('accepted', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $preparing_in_rs = Order::Preparing()->where('module_id', $module_id)->whereBetween('processing', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $picked_up = Order::ItemOnTheWay()->where('module_id', $module_id)->whereBetween('picked_up', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $refund_requested = Order::where('module_id', $module_id)->where(['order_status' => 'refund_requested'])->whereBetween('refund_requested', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $refunded = Order::where('module_id', $module_id)->where(['order_status' => 'refunded'])->whereBetween('refunded', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $new_orders = Order::where('module_id', $module_id)->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $new_stores = Store::where('module_id', $module_id)->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $new_customers = User::whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $total_orders = Order::where('module_id', $module_id);
            if ($module_id == 5) {
                $searching_for_dm = Order::SearchingForDeliveryman()->where('module_id', $module_id)->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                $delivered = Order::Delivered()->where('module_id', $module_id)->whereBetween('delivered', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                $canceled = Order::where('module_id', $module_id)->where(['order_status' => 'canceled'])->whereBetween('canceled', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);

                $new_items = Item::where('module_id', $module_id)->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                $total_items = Item::where('module_id', $module_id);
            } else {

                $searching_for_dm = DB::table('accepted_service_requests')->whereNot('assigned_status', 'Assigned')->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                $accepted_services = DB::table('accepted_service_requests')->where('assigned_status', 'Assigned')->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);

                $delivered = DB::table('accepted_service_requests')->where('current_status', 'Completed')->whereBetween('completed_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                $canceled = DB::table('accepted_service_requests')->where('current_status', 'Cancelled')->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);

                $new_items = DB::table('items')->where('module_id', $module_id)->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                $total_items = DB::table('items')->where('module_id', $module_id)->get();
                $service_leads = DB::table('accepted_service_requests')->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            }
            $total_stores = Store::where('module_id', $module_id);
            $total_customers = User::all();
        } elseif ($module_id) {
            $accepted_by_dm = Order::AccepteByDeliveryman()->where('module_id', $module_id);
            $preparing_in_rs = Order::Preparing()->where('module_id', $module_id);
            $picked_up = Order::ItemOnTheWay()->where('module_id', $module_id);
            $refund_requested = Order::failed()->where('module_id', $module_id);
            $refunded = Order::Refunded()->where('module_id', $module_id);
            $new_orders = Order::where('module_id', $module_id)->whereDate('schedule_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $new_stores = Store::where('module_id', $module_id)->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $new_customers = User::whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $total_orders = Order::where('module_id', $module_id);
            if ($module_id == 5) {
                $searching_for_dm = Order::SearchingForDeliveryman()->where('module_id', $module_id);
                $delivered = Order::Delivered()->where('module_id', $module_id);
                $canceled = Order::Canceled()->where('module_id', $module_id);

                $new_items = Item::where('module_id', $module_id)->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
                $total_items = Item::where('module_id', $module_id);
            } else {
                $canceled = DB::table('accepted_service_requests')->where('current_status', 'Cancelled');

                $searching_for_dm = DB::table('accepted_service_requests')->whereNot('assigned_status', 'Assigned');
                $accepted_services = DB::table('accepted_service_requests')->where('assigned_status', 'Assigned');
                $delivered = DB::table('accepted_service_requests')->where('current_status', 'Completed');

                $new_items = DB::table('items')->where('module_id', $module_id)->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
                $total_items = DB::table('items')->where('module_id', $module_id)->get();
                $service_leads = DB::table('accepted_service_requests')->get();
            }
            $total_stores = Store::where('module_id', $module_id);
            $total_customers = User::all();
            $new_items = DB::table('items')->where('module_id', $module_id)->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
        } else {
            $new_items = DB::table('items')->where('module_id', $module_id)->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $accepted_by_dm = Order::AccepteByDeliveryman();
            $preparing_in_rs = Order::Preparing();
            $picked_up = Order::ItemOnTheWay();
            $refund_requested = Order::failed();
            $refunded = Order::Refunded();
            $new_orders = Order::whereDate('schedule_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $new_stores = Store::whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $new_customers = User::whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
            $total_orders = Order::all();
            if (Config::get('module.current_module_id') == 5) {
                $searching_for_dm = Order::SearchingForDeliveryman();
                $delivered = Order::Delivered();
                $canceled = Order::Canceled();

                $new_items = Item::whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
                $total_items = Item::all();
            } else {
                $searching_for_dm = DB::table('accepted_service_requests')->whereNot('assigned_status', 'Assigned');
                $accepted_services = DB::table('accepted_service_requests')->where('assigned_status', 'Assigned');
                $delivered = DB::table('accepted_service_requests')->where('current_status', 'Completed');
                $canceled = DB::table('accepted_service_requests')->where('current_status', 'Cancelled');

                $new_items = DB::table('items')->whereDate('created_at', '>=', now()->subDays(30)->format('Y-m-d'));
                $total_items = DB::table('items')->where('module_id', 6)->get();
                $service_leads = DB::table('accepted_service_requests')->get();
            }
            $total_stores = Store::all();
            $total_customers = User::all();
        }
        if (is_numeric($zone_id) && $module_id &&  !in_array($module_type, ['parcel'])) {
            if (Config::get('module.current_module_id') == 5) {
                $searching_for_dm = $searching_for_dm->StoreOrder()->OrderScheduledIn(30)->where('zone_id', $zone_id)->count();
                $delivered = $delivered->StoreOrder()->where('zone_id', $zone_id)->count();
                $canceled = $canceled->StoreOrder()->where('zone_id', $zone_id)->count();
            } else {
                $delivered = $delivered->count();
                $canceled = $canceled->count();
                $searching_for_dm = $searching_for_dm->count();
                $accepted_services = $accepted_services->count();
            }
            $accepted_by_dm = $accepted_by_dm->StoreOrder()->where('zone_id', $zone_id)->count();
            $preparing_in_rs = $preparing_in_rs->StoreOrder()->where('zone_id', $zone_id)->count();
            $picked_up = $picked_up->StoreOrder()->where('zone_id', $zone_id)->count();
            $refund_requested = $refund_requested->StoreOrder()->where('zone_id', $zone_id)->count();
            $refunded = $refunded->StoreOrder()->where('zone_id', $zone_id)->count();
            $total_orders = $total_orders->StoreOrder()->where('zone_id', $zone_id)->count();
            $total_items = $total_items->count();
            $total_stores = $total_stores->where('zone_id', $zone_id)->count();
            $service_leads = $service_leads->count();

            $total_customers = $total_customers->count();
            $new_orders = $new_orders->StoreOrder()->where('zone_id', $zone_id)->count();
            $new_items = $new_items->count();
            $new_stores = $new_stores->where('zone_id', $zone_id)->count();
            $new_customers = $new_customers->count();
        } elseif ($module_id && $module_type != 'parcel') {
            if (Config::get('module.current_module_id') == 5) {
                $searching_for_dm = $searching_for_dm->StoreOrder()->OrderScheduledIn(30)->count();
                $delivered = $delivered->StoreOrder()->count();
                $canceled = $canceled->StoreOrder()->count();
            } else {
                $delivered = $delivered->count();
                $canceled = $canceled->count();
                $service_leads = $service_leads->count();

                $searching_for_dm = $searching_for_dm->count();
                $accepted_services = $accepted_services->count();
            }

            $accepted_by_dm = $accepted_by_dm->StoreOrder()->count();
            $preparing_in_rs = $preparing_in_rs->StoreOrder()->count();
            $picked_up = $picked_up->StoreOrder()->count();
            $refund_requested = $refund_requested->StoreOrder()->count();
            $refunded = $refunded->StoreOrder()->count();
            $total_orders = $total_orders->StoreOrder()->count();
            $total_items = $total_items->count();
            $total_stores = $total_stores->count();
            $total_customers = $total_customers->count();
            $new_orders = $new_orders->StoreOrder()->count();
            $new_items = $new_items->count();
            $new_stores = $new_stores->count();
            $new_customers = $new_customers->count();
        } elseif (is_numeric($zone_id) && $module_id && $module_type == 'parcel') {
            $searching_for_dm = $searching_for_dm->ParcelOrder()->OrderScheduledIn(30)->where('zone_id', $zone_id)->count();
            $accepted_services = $accepted_services->count();
            $accepted_by_dm = $accepted_by_dm->ParcelOrder()->where('zone_id', $zone_id)->count();
            $preparing_in_rs = $preparing_in_rs->ParcelOrder()->where('zone_id', $zone_id)->count();
            $picked_up = $picked_up->ParcelOrder()->where('zone_id', $zone_id)->count();
            $refund_requested = $refund_requested->ParcelOrder()->where('zone_id', $zone_id)->count();
            $refunded = $refunded->ParcelOrder()->where('zone_id', $zone_id)->count();
            $total_orders = $total_orders->ParcelOrder()->where('zone_id', $zone_id)->count();
            $total_items = $total_items->count();
            if (Config::get('module.current_module_id') == 6) {
                $service_leads = $service_leads->count();
                $delivered = $delivered->count();
                $canceled = $canceled->count();
            } else {
                $canceled = $canceled->ParcelOrder()->where('zone_id', $zone_id)->count();
                $delivered = $delivered->ParcelOrder()->where('zone_id', $zone_id)->count();
            }
            $total_stores = $total_stores->where('zone_id', $zone_id)->count();
            $total_customers = $total_customers->where('zone_id', $zone_id)->count();
            $new_orders = $new_orders->ParcelOrder()->where('zone_id', $zone_id)->count();
            $new_items = $new_items->count();
            $new_stores = $new_stores->where('zone_id', $zone_id)->count();
            $new_customers = $new_customers->where('zone_id', $zone_id)->count();
        } elseif ($module_id && $module_type == 'parcel') {
            $accepted_services = $accepted_services->count();
            $searching_for_dm = $searching_for_dm->ParcelOrder()->OrderScheduledIn(30)->count();
            $accepted_by_dm = $accepted_by_dm->ParcelOrder()->count();
            $preparing_in_rs = $preparing_in_rs->ParcelOrder()->count();
            $picked_up = $picked_up->ParcelOrder()->count();
            $refund_requested = $refund_requested->ParcelOrder()->count();
            $refunded = $refunded->ParcelOrder()->count();
            $total_orders = $total_orders->ParcelOrder()->count();
            $total_items = $total_items->count();
            $total_stores = $total_stores->count();
            if (Config::get('module.current_module_id') == 6) {
                $service_leads = $service_leads->count();
                $delivered = $delivered->count();
                $canceled = $canceled->count();
            } else {
                $delivered = $delivered->ParcelOrder()->count();
                $canceled = $canceled->ParcelOrder()->count();
            }

            $total_customers = $total_customers->count();
            $new_orders = $new_orders->ParcelOrder()->count();
            $new_items = $new_items->count();
            $new_stores = $new_stores->count();
            $new_customers = $new_customers->count();
        } else {
            if (Config::get('module.current_module_id') == 5) {
                $searching_for_dm = $searching_for_dm->StoreOrder()->OrderScheduledIn(30)->count();
            } else {
                $searching_for_dm = $searching_for_dm->count();
                $accepted_services = $accepted_services->count();
            }
            $accepted_by_dm = $accepted_by_dm->StoreOrder()->count();
            $preparing_in_rs = $preparing_in_rs->StoreOrder()->count();
            $picked_up = $picked_up->StoreOrder()->count();
            $refund_requested = $refund_requested->StoreOrder()->count();
            $refunded = $refunded->StoreOrder()->count();
            $total_orders = $total_orders->count();
            $total_items = $total_items->count();
            $total_stores = $total_stores->count();
            if (Config::get('module.current_module_id') == 6 || !Config::get('module.current_module_id')) {
                $service_leads = $service_leads->count();
                $delivered = $delivered->count();
                $canceled = $canceled->count();
            } else {
                $canceled = $canceled->StoreOrder()->count();

                $delivered = $delivered->StoreOrder()->count();
            }
            $total_customers = $total_customers->count();
            $new_orders = $new_orders->count();
            $new_items = $new_items->count();
            $new_stores = $new_stores->count();
            $new_customers = $new_customers->count();
        }
        $data = [
            'searching_for_dm' => $searching_for_dm,
            'accepted_by_dm' => $accepted_by_dm,
            'preparing_in_rs' => $preparing_in_rs,
            'picked_up' => $picked_up,
            'delivered' => $delivered,
            'canceled' => $canceled,
            'refund_requested' => $refund_requested,
            'refunded' => $refunded,
            'total_orders' => $total_orders,
            'total_items' => $total_items,
            'total_stores' => $total_stores,
            'total_customers' => $total_customers,
            'new_orders' => $new_orders,
            'new_items' => $new_items,
            'new_stores' => $new_stores,
            'new_customers' => $new_customers,
        ];
        if (Config::get('module.current_module_id') == 6) {
            $data['service_leads'] = $service_leads;
            $data['accepted_services'] = $accepted_services;
        }
        return $data;
    }

    public function user_overview_calc($zone_id, $module_id)
    {
        $params = session('dash_params');
        //zone
        if (is_numeric($zone_id)) {
            $customer = User::where('zone_id', $zone_id);
            $stores = Store::where('module_id', $module_id)->where(['zone_id' => $zone_id]);
            $delivery_man = DeliveryMan::where('application_status', 'approved')->where('zone_id', $zone_id)->Zonewise();
        } else {
            $customer = User::whereNotNull('id');
            $stores = Store::where('module_id', $module_id)->whereNotNull('id');
            $delivery_man = DeliveryMan::where('application_status', 'approved')->Zonewise();
        }
        //user overview
        if ($params['user_overview'] == 'overall') {
            $customer = $customer->count();
            $stores = $stores->count();
            $delivery_man = $delivery_man->count();
        } elseif ($params['user_overview'] == 'this_month') {
            $customer = $customer->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))->count();
            $stores = $stores->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))->count();
            $delivery_man = $delivery_man->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))->count();
        } elseif ($params['user_overview'] == 'this_year') {
            $customer = $customer
                ->whereYear('created_at', date('Y'))->count();
            $stores = $stores
                ->whereYear('created_at', date('Y'))->count();
            $delivery_man = $delivery_man
                ->whereYear('created_at', date('Y'))->count();
        } else {
            $customer = $customer->whereDate('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')])->count();
            $stores = $stores->whereDate('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')])->count();
            $delivery_man = $delivery_man->whereDate('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')])->count();
        }
        $data = [
            'customer' => $customer,
            'stores' => $stores,
            'delivery_man' => $delivery_man
        ];
        return $data;
    }


    public function dashboard_data($request)
    {
        $params = session('dash_params');
        if (!url()->current() == $request->is('admin/users')) {
            $data_os = self::order_stats_calc($params['zone_id'], $params['module_id']);
            $data_uo = self::user_overview_calc($params['zone_id'], $params['module_id']);
        }
        $popular = Wishlist::with(['store'])
            ->whereHas('store')
            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                return $q->whereHas('store', function ($query) use ($params) {
                    return $query->where('module_id', $params['module_id']);
                });
            })
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->whereHas('store', function ($query) use ($params) {
                    return $query->where('zone_id', $params['zone_id']);
                });
            })
            ->select('store_id', DB::raw('COUNT(store_id) as count'))->groupBy('store_id')->orderBy('count', 'DESC')->limit(6)->get();
        if (Config::get('module.current_module_id') == 6) {

            $top_sell = Item::withoutGlobalScopes()
                ->join('stores', function ($join) {
                    $join->whereRaw('FIND_IN_SET(stores.id, items.store_ids) > 0');
                })
                ->leftJoin(
                    DB::raw('(SELECT service_requests.item_id, COUNT(*) as service_request_count 
                FROM service_requests 
                INNER JOIN accepted_service_requests ON service_requests.id = accepted_service_requests.service_request_id 
                WHERE accepted_service_requests.current_status = "Completed" 
                GROUP BY service_requests.item_id) as request_counts'),
                    'items.id',
                    '=',
                    'request_counts.item_id'
                )
                // ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                //     return $q->whereHas('store', function ($query) use ($params) {
                //         return $query->where('zone_id', $params['zone_id']);
                //     });
                // })
                ->where('stores.active', 1)
                ->whereHas('module', function ($query) {
                    $query->where('modules.id', 6);
                })
                ->select('items.*', DB::raw('COALESCE(request_counts.service_request_count, 0) as service_request_count'))
                ->distinct()
                ->orderBy('service_request_count', 'desc')
                ->take(6)
                ->get();

            $top_rated_foods = Item::whereNull('name')->get();

            $top_customers = User::when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
                ->leftJoin(
                    DB::raw('(SELECT service_requests.user_id, COUNT(*) as service_request_count 
                FROM service_requests 
                INNER JOIN accepted_service_requests ON service_requests.id = accepted_service_requests.service_request_id 
                WHERE accepted_service_requests.current_status = "Completed" 
                GROUP BY service_requests.user_id) as request_counts'),
                    'users.id',
                    '=',
                    'request_counts.user_id'
                )
                ->select('users.*', DB::raw('COALESCE(request_counts.service_request_count, 0) as order_count'))
                ->orderBy('order_count', 'desc')
                ->take(6)
                ->get();



            $top_restaurants = Store::when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
                ->where('stores.active', 1)
                ->leftJoin(
                    DB::raw('(SELECT vendor_id, COUNT(*) as service_request_count 
                FROM accepted_service_requests 
                WHERE current_status = "Completed" 
                GROUP BY vendor_id) as request_counts'),
                    'stores.id',
                    '=',
                    'request_counts.vendor_id'
                )
                ->select('stores.*', DB::raw('COALESCE(request_counts.service_request_count, 0) as order_count'))
                ->orderBy('order_count', 'desc')
                ->take(6)
                ->get();
        } else {
            $top_sell = Item::withoutGlobalScope(ZoneScope::class)
                ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                    return $q->whereHas('store', function ($query) use ($params) {
                        return $query->where('module_id', $params['module_id']);
                    });
                })
                ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                    return $q->whereHas('store', function ($query) use ($params) {
                        return $query->where('module_id', $params['module_id'])->where('zone_id', $params['zone_id']);
                    });
                })
                ->orderBy("order_count", 'desc')
                ->take(6)
                ->get();
            $top_rated_foods = Item::withoutGlobalScope(ZoneScope::class)
                ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                    return $q->whereHas('store', function ($query) use ($params) {
                        return $query->where('module_id', $params['module_id']);
                    });
                })
                ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                    return $q->whereHas('store', function ($query) use ($params) {
                        return $query->where('zone_id', $params['zone_id']);
                    });
                })
                ->orderBy('rating_count', 'desc')
                ->take(6)
                ->get();

            $top_customers = User::when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            })
                ->orderBy("order_count", 'desc')
                ->take(6)
                ->get();

            $top_restaurants = Store::when(is_numeric($params['module_id']), function ($q) use ($params) {
                return $q->where('module_id', $params['module_id']);
            })
                ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                    return $q->where('zone_id', $params['zone_id']);
                })
                ->orderBy("order_count", 'desc')
                ->take(6)
                ->get();
        }
        $top_deliveryman = DeliveryMan::withCount('orders')->when(is_numeric($params['zone_id']), function ($q) use ($params) {
            return $q->where('zone_id', $params['zone_id']);
        })
            ->Zonewise()
            ->orderBy("orders_count", 'desc')
            ->take(6)
            ->get();


        // custom filtering for bar chart
        $months = array(
            '"' . translate('Jan') . '"',
            '"' . translate('Feb') . '"',
            '"' . translate('Mar') . '"',
            '"' . translate('Apr') . '"',
            '"' . translate('May') . '"',
            '"' . translate('Jun') . '"',
            '"' . translate('Jul') . '"',
            '"' . translate('Aug') . '"',
            '"' . translate('Sep') . '"',
            '"' . translate('Oct') . '"',
            '"' . translate('Nov') . '"',
            '"' . translate('Dec') . '"'
        );
        $days = array(
            '"' . translate('Sun') . '"',
            '"' . translate('Mon') . '"',
            '"' . translate('Tue') . '"',
            '"' . translate('Wed') . '"',
            '"' . translate('Thu') . '"',
            '"' . translate('Fri') . '"',
            '"' . translate('Sat') . '"'
        );
        $total_sell = [];
        $commission = [];
        $label = [];
        $query = OrderTransaction::NotRefunded()
            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                return $q->where('module_id', $params['module_id']);
            })
            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                return $q->where('zone_id', $params['zone_id']);
            });
        if (Config::get('module.current_module_id') == 5) {


            switch ($params['commission_overview']) {
                case "this_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $total_sell[$i] = OrderTransaction::NotRefunded()
                            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                                return $q->where('module_id', $params['module_id']);
                            })
                            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                                return $q->where('zone_id', $params['zone_id']);
                            })
                            ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                            ->sum('order_amount');
                        $commission[$i] = OrderTransaction::NotRefunded()
                            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                                return $q->where('module_id', $params['module_id']);
                            })
                            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                                return $q->where('zone_id', $params['zone_id']);
                            })
                            ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                            ->sum(DB::raw('admin_commission + admin_expense - delivery_fee_comission'));
                        $delivery_commission[$i] = OrderTransaction::when(is_numeric($params['module_id']), function ($q) use ($params) {
                            return $q->where('module_id', $params['module_id']);
                        })
                            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                                return $q->where('zone_id', $params['zone_id']);
                            })
                            ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                            ->sum('delivery_fee_comission');
                    }
                    $label = $months;
                    break;
                case "this_week":
                    $weekStartDate = now()->startOfWeek();
                    for ($i = 1; $i <= 7; $i++) {
                        $total_sell[$i] = OrderTransaction::NotRefunded()
                            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                                return $q->where('module_id', $params['module_id']);
                            })
                            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                                return $q->where('zone_id', $params['zone_id']);
                            })
                            ->whereDay('created_at', $weekStartDate->format('d'))->whereMonth('created_at', now()->format('m'))
                            ->sum('order_amount');
                        $commission[$i] = OrderTransaction::NotRefunded()
                            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                                return $q->where('module_id', $params['module_id']);
                            })
                            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                                return $q->where('zone_id', $params['zone_id']);
                            })
                            ->whereDay('created_at', $weekStartDate->format('d'))->whereMonth('created_at', now()->format('m'))
                            ->sum(DB::raw('admin_commission + admin_expense - delivery_fee_comission'));
                        $delivery_commission[$i] = OrderTransaction::when(is_numeric($params['module_id']), function ($q) use ($params) {
                            return $q->where('module_id', $params['module_id']);
                        })
                            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                                return $q->where('zone_id', $params['zone_id']);
                            })
                            ->whereDay('created_at', $weekStartDate->format('d'))->whereMonth('created_at', now()->format('m'))
                            ->sum('delivery_fee_comission');
                    }
                    $label = $days;
                    break;
                case "this_month":
                    $start = now()->startOfMonth();
                    $end = now()->startOfMonth()->addDays(7);
                    $total_day = now()->daysInMonth;
                    $remaining_days = now()->daysInMonth - 28;
                    $weeks = array(
                        '"Day 1-7"',
                        '"Day 8-14"',
                        '"Day 15-21"',
                        '"Day 22-' . $total_day . '"',
                    );
                    for ($i = 1; $i <= 4; $i++) {
                        $total_sell[$i] = OrderTransaction::NotRefunded()
                            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                                return $q->where('module_id', $params['module_id']);
                            })
                            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                                return $q->where('zone_id', $params['zone_id']);
                            })
                            ->whereBetween('created_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                            ->sum('order_amount');
                        $commission[$i] = OrderTransaction::NotRefunded()
                            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                                return $q->where('module_id', $params['module_id']);
                            })
                            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                                return $q->where('zone_id', $params['zone_id']);
                            })
                            ->whereBetween('created_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                            ->sum(DB::raw('admin_commission + admin_expense - delivery_fee_comission'));
                        $delivery_commission[$i] = OrderTransaction::when(is_numeric($params['module_id']), function ($q) use ($params) {
                            return $q->where('module_id', $params['module_id']);
                        })
                            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                                return $q->where('zone_id', $params['zone_id']);
                            })
                            ->whereBetween('created_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                            ->sum('delivery_fee_comission');

                        $start = $start->addDays(7);
                        $end = $i == 3 ? $end->addDays(7 + $remaining_days) : $end->addDays(7);
                    }
                    $label = $weeks;
                    break;
                default:
                    for ($i = 1; $i <= 12; $i++) {
                        $total_sell[$i] = OrderTransaction::NotRefunded()
                            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                                return $q->where('module_id', $params['module_id']);
                            })
                            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                                return $q->where('zone_id', $params['zone_id']);
                            })
                            ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                            ->sum('order_amount');
                        $commission[$i] = OrderTransaction::NotRefunded()
                            ->when(is_numeric($params['module_id']), function ($q) use ($params) {
                                return $q->where('module_id', $params['module_id']);
                            })
                            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                                return $q->where('zone_id', $params['zone_id']);
                            })
                            ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                            ->sum(DB::raw('admin_commission + admin_expense - delivery_fee_comission'));
                        $delivery_commission[$i] = OrderTransaction::when(is_numeric($params['module_id']), function ($q) use ($params) {
                            return $q->where('module_id', $params['module_id']);
                        })
                            ->when(is_numeric($params['zone_id']), function ($q) use ($params) {
                                return $q->where('zone_id', $params['zone_id']);
                            })
                            ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                            ->sum('delivery_fee_comission');
                    }
                    $label = $months;
            }
        } else {

            switch ($params['commission_overview']) {
                case "this_year":
                    for ($i = 1; $i <= 12; $i++) {

                        $sales = 0;
                        $commission_given = 0;

                        $statistics = ServiceInvoice::where(['payment_status' => 'Paid'])->whereMonth('payment_date', $i)->whereYear('payment_date', now()->format('Y'))->get()->toArray();
                        if (!empty($statistics)) {
                            foreach ($statistics as $key => $value) {
                                $sales += $value['total_amount'];
                            }
                        }
                        $total_sell[$i] = $sales;

                        $commissionQ = AcceptedServiceRequest::where(['current_status' => 'Completed'])->whereMonth('completed_at', $i)->whereYear('completed_at', now()->format('Y'))->get()->toArray();
                        if (!empty($commissionQ)) {
                            foreach ($commissionQ as $key => $value) {
                                $commission_given += $value['sales_commission'];
                            }
                        }
                        $commission[$i] = $commission_given;


                        $delivery_commission[$i] = 0;
                    }
                    $label = $months;
                    // prx($total_sell);
                    break;
                case "this_week":
                    $weekStartDate = now()->startOfWeek();
                    for ($i = 1; $i <= 7; $i++) {

                        $sales = 0;
                        $commission_given = 0;

                        $statistics = ServiceInvoice::where(['payment_status' => 'Paid'])->whereDay('payment_date', $weekStartDate->format('d'))->whereMonth('payment_date', now()->format('m'))->get()->toArray();
                        if (!empty($statistics)) {
                            foreach ($statistics as $key => $value) {
                                $sales += $value['total_amount'];
                            }
                        }
                        $total_sell[$i] = $sales;

                        $commissionQ = AcceptedServiceRequest::where(['current_status' => 'Completed'])->whereDay('completed_at', $weekStartDate->format('d'))->whereMonth('completed_at', now()->format('m'))->get()->toArray();
                        if (!empty($commissionQ)) {
                            foreach ($commissionQ as $key => $value) {
                                $commission_given += $value['sales_commission'];
                            }
                        }
                        $commission[$i] = $commission_given;


                        $delivery_commission[$i] = 0;
                    }
                    $label = $days;
                    break;
                case "this_month":
                    $start = now()->startOfMonth();
                    $end = now()->startOfMonth()->addDays(7);
                    $total_day = now()->daysInMonth;
                    $remaining_days = now()->daysInMonth - 28;
                    $weeks = array(
                        '"Day 1-7"',
                        '"Day 8-14"',
                        '"Day 15-21"',
                        '"Day 22-' . $total_day . '"',
                    );
                    for ($i = 1; $i <= 4; $i++) {

                        $sales = 0;
                        $commission_given = 0;

                        $statistics = ServiceInvoice::where(['payment_status' => 'Paid'])->whereBetween('payment_date', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])->get()->toArray();
                        if (!empty($statistics)) {
                            foreach ($statistics as $key => $value) {
                                $sales += $value['total_amount'];
                            }
                        }
                        $total_sell[$i] = $sales;

                        $commissionQ = AcceptedServiceRequest::where(['current_status' => 'Completed'])->whereBetween('completed_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])->get()->toArray();
                        if (!empty($commissionQ)) {
                            foreach ($commissionQ as $key => $value) {
                                $commission_given += $value['sales_commission'];
                            }
                        }
                        $commission[$i] = $commission_given;



                        $delivery_commission[$i] = 0;

                        $start = $start->addDays(7);
                        $end = $i == 3 ? $end->addDays(7 + $remaining_days) : $end->addDays(7);
                    }
                    $label = $weeks;
                    break;
                default:
                    for ($i = 1; $i <= 12; $i++) {
                        $sales = 0;
                        $commission_given = 0;

                        $statistics = ServiceInvoice::where(['payment_status' => 'Paid'])->whereMonth('payment_date', $i)->whereYear('payment_date', now()->format('Y'))->get()->toArray();
                        if (!empty($statistics)) {
                            foreach ($statistics as $key => $value) {
                                $sales += $value['total_amount'];
                            }
                        }
                        $total_sell[$i] = $sales;

                        $commissionQ = AcceptedServiceRequest::where(['current_status' => 'Completed'])->whereMonth('completed_at', $i)->whereYear('completed_at', now()->format('Y'))->get()->toArray();
                        if (!empty($commissionQ)) {
                            foreach ($commissionQ as $key => $value) {
                                $commission_given += $value['sales_commission'];
                            }
                        }
                        $commission[$i] = $commission_given;


                        $delivery_commission[$i] = 0;
                    }
                    $label = $months;
            }
        }


        if (!url()->current() == $request->is('admin/users')) {
            $dash_data = array_merge($data_os, $data_uo);
        }

        $dash_data['popular'] = $popular;
        $dash_data['top_sell'] = $top_sell;
        $dash_data['top_rated_foods'] = $top_rated_foods;
        $dash_data['top_deliveryman'] = $top_deliveryman;
        $dash_data['top_restaurants'] = $top_restaurants;
        $dash_data['top_customers'] = $top_customers;
        $dash_data['total_sell'] = $total_sell;
        $dash_data['commission'] = $commission;
        $dash_data['delivery_commission'] = $delivery_commission;
        $dash_data['label'] = $label;
        return $dash_data;
    }
}
