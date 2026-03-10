<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Item;
use App\Models\Store;
use App\Models\Review;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\CentralLogics\Helpers;
use App\CentralLogics\ProductLogic;
use App\Models\ItemCampaign;
use App\Models\Plan;
use App\Models\Tag;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\DB;
use App\Scopes\StoreScope;
use App\Models\Translation;
use App\Models\VendorSubscription;
use App\Models\SubscriptionPlanRequest;
use App\Models\PlanDuration; 
use App\Models\SubModuleDiscount;
use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class PlanController extends Controller
{
    public function index(Request $request, $type = null, $request_id = null)
    {
        // $categories = Category::where(['position' => 0])->get();
        $request_det = SubscriptionPlanRequest::find($request_id);
        $stores = Store::withoutGlobalScopes()->where('module_id', 6)->get();
        $features = DB::table('subscription_modules')->where('status', 1)->get();

        return view('admin-views.plan.index', compact('type', 'request_det', 'stores', 'features'));
    }
    public function stores()
    {
        $stores = Store::where('module_id', 6)->get();
        $plans = Plan::where('status', 1)->get();
        // Get only stores that have subscriptions 
        $stores = Store::whereHas('subscriptions', function ($q) {
            $q->where('plan_expiry', '>=', now());
        })
            ->with(['subscriptions' => function ($q) {
                // Load only active subscriptions
                $q->where('plan_expiry', '>=', now())
                    ->with('plan');
            }])
            ->paginate(10);



        // $categories = Category::where(['position' => 0])->get();
        return view('admin-views.plan.subscriptions', compact('stores', 'plans'));
    }

    public function store(Request $request)
    {
        // prx($request->all());
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'description' => 'max:500',
            // 'price' => 'required|numeric|between:.01,999999999999.99',
        ], [
            'description.*.max' => 'Description can be max 500 characters',
            'name.required' => "Plan title is required",
            'price.required' => "Price can't be blank",
            'duration_count.required' => 'Duration Count is required',
            'duration_type.required' => 'Duration Type is required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }



        $plan  = new Plan();
        if ($request->has('req_id') && $request->req_id != '') {
            $request_det = SubscriptionPlanRequest::find($request->post('req_id'));
            $request_det->status = 1;
            $request_det->save();

            $plan->customized = 1;
            $plan->req_id = $request->post('req_id');
            $plan->store_id = $request->post('store_id');
        }
        $plan->title = $request->post('name');
        $plan->description = $request->post('description');
        $plan->advanced_leads_manage = $request->post('advanced_leads_manage') ?? '0';
        $plan->projects_manage = $request->post('projects_manage') ?? '0';
        $plan->quotaiton_manage = $request->post('quotaiton_manage') ?? '0';
        $plan->hr_manage = $request->post('hr_manage') ?? '0';
        $plan->billing = $request->post('billing') ?? '0';
        // $plan->salary_manage = $request->post('salary_manage')?? '0';
        // $plan->staff_manage = $request->post('staff_manage')?? '0';
        // $plan->att_manage = $request->post('att_manage')?? '0';
        // $plan->leave_manage = $request->post('leave_manage')?? '0'; 
        // $plan->service_leads = $request->post('service_leads')?? '0';
        $plan->account_manage = $request->post('account_manage') ?? '0';
        $plan->inventory_manage = $request->post('inventory_manage') ?? '0';
        $plan->task_manage = $request->post('task_manage') ?? '0';
        // $plan->price = $request->post('price');
        $plan->hsn = $request->post('hsn');
        $plan->gst_percent = $request->post('gst_percent') ?? 0;
        // $plan->discount = $request->post('discount');
        if ($request->duration_type == 'custom') {
            $plan->duration_count = $request->post('duration_count');
            $plan->duration_type = $request->post('duration_unit');
        } else {

            $durations = $request->standard_duration ?? [];
            $result = [];

            foreach ($durations as $duration) {
                $key = 'price_' . str_replace(' ', '_', $duration);
                $price = isset($request->$key) ? $request->$key : null;

                $key2 = 'discount_' . str_replace(' ', '_', $duration);
                $discount = isset($request->$key2) ? $request->$key2 : null;

                $result[] = [
                    'id' => Str::random(10),
                    'duration' => $duration,
                    'discount' => $discount,
                    'price' => $price
                ];
            }

            $plan->price_variations = json_encode($result);
        }
        // prx($plan);
        $plan->save();
    }

    public function plan_requests()
    {
        $allPlanRequests = SubscriptionPlanRequest::with('store')->get();
        return view('admin-views.plan.plan_requests', compact('allPlanRequests'));
    }
    public function module_pricing()
    {
        $features = DB::table('subscription_modules')->where('status', 1)->get();
        $allPlanRequests = SubscriptionPlanRequest::with('store')->get();
        $sub_modules = DB::table('sub_modules')->get();
        $plan_durations = PlanDuration::orderBy('sort_order')->get();
        $sub_module_discounts = SubModuleDiscount::all()->groupBy('sub_module_id');
        return view('admin-views.plan.module_pricing', compact('allPlanRequests', 'features', 'sub_modules', 'plan_durations', 'sub_module_discounts'));
    }
    public function list() 
    {
        $allPlans = Plan::all();
        return view('admin-views.plan.list', compact('allPlans'));
    }

    public function status(Request $request)
    {
        $plan = Plan::findOrFail($request->id);
        $plan->status = $request->status;
        $plan->save();
        Toastr::success("Status change successfully");
        return back();
    }

    public function delete(Request $request)
    {
        $product = Plan::find($request->id);

        $product->delete();
        Toastr::success('Plan deleted successfully');
        return back();
    }

    public function edit($id)
    {
        $plan = Plan::find($id);

        return view('admin-views.plan.edit', compact('plan'));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'description' => 'max:500',
            // 'price' => 'required|numeric|between:.01,999999999999.99',
            // 'duration_count' => 'required',
            // 'duration_type' => 'required',

        ], [
            'description.*.max' => 'Description can be max 500 characters',
            'name.required' => "Plan title is required",
            'price.required' => "Price can't be blank",
            'duration_count.required' => 'Duration Count is required',
            'duration_type.required' => 'Duration Type is required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $plan  = Plan::find($request->id);
        $plan->title = $request->post('name');
        $plan->description = $request->post('description');
        $plan->advanced_leads_manage = $request->post('advanced_leads_manage') ?? '0';
        $plan->hr_manage = $request->post('hr_manage') ?? '0';
        $plan->billing = $request->post('billing') ?? '0';
        $plan->projects_manage = $request->post('projects_manage') ?? '0';
        $plan->quotaiton_manage = $request->post('quotaiton_manage') ?? '0';
        // $plan->salary_manage = $request->post('salary_manage')?? '0';
        // $plan->staff_manage = $request->post('staff_manage')?? '0';
        // $plan->att_manage = $request->post('att_manage')?? '0';
        // $plan->leave_manage = $request->post('leave_manage')?? '0';
        $plan->account_manage = $request->post('account_manage') ?? '0';
        $plan->inventory_manage = $request->post('inventory_manage') ?? '0';
        $plan->task_manage = $request->post('task_manage') ?? '0';
        $plan->price = $request->post('price');
        $plan->hsn = $request->post('hsn');
        $plan->gst_percent = $request->post('gst_percent');
        // $plan->discount = $request->post('discount');
        if ($request->duration_type == 'custom') {
            $plan->duration_count = $request->post('duration_count');
            $plan->duration_type = $request->post('duration_unit');
        } else {
            $durations = $request->standard_duration ?? [];
            $result = [];

            foreach ($durations as $duration) {
                $key = 'price_' . str_replace(' ', '_', $duration);
                $price = isset($request->$key) ? $request->$key : null;

                $key2 = 'discount_' . str_replace(' ', '_', $duration);
                $discount = isset($request->$key2) ? $request->$key2 : null;

                $result[] = [
                    'id' => Str::random(10),
                    'duration' => $duration,
                    'discount' => $discount,
                    'price' => $price
                ];
            }

            $plan->price_variations = json_encode($result);
        }
        echo $plan->save();
    }
}
