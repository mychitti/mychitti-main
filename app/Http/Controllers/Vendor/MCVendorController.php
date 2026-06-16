<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\Contact;
use App\Models\DataSetting;
use App\Models\Plan;
use App\Models\HospitalBedTier;
use App\Models\SubModule;
use App\Models\SubscriptionPlanRequest;
use App\Models\VendorModuleInstruction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Validation\Validator;

class MCVendorController extends Controller
{
    public function module_info(Request $request, $module)
    {
        if($module== 'mc-hmis'){
            return view('mc-vendor.MC_HMIS');
        }
        $module = VendorModuleInstruction::where('slug', $module)->first();
        return view('mc-vendor.vendor_module', compact('module'));
    }

    public function mc_vendor_hub_tnc(Request $request)
    {
        $terms_and_conditions =  DataSetting::where('key', 'vendorhub_terms_and_conditions')->first();
        return view('mc-vendor.vendorhub_terms_and_conditions', compact('terms_and_conditions'));
    }
    public function mc_vendor_hub_pp(Request $request)
    {
        $privacy_policy =  DataSetting::where('key', 'privacy_policy_for_mc_vendor')->first();
        return view('mc-vendor.vendorhub_privacy_policy', compact('privacy_policy'));
    }

    public function mc_vendor_hub_return_policy(Request $request)
    {
        $return_policy = DataSetting::where('key', 'return_policy_for_mc_vendor')->first();
        return view('mc-vendor.vendorhub_return_policy', compact('return_policy'));
    }
    public function request_subscription_plan(Request $request)
    {
        // print_r($request->all());
        // die;
        $request->validate([
            'contact_name' => 'required',
            'phone' => 'required',
            'business_type' => 'required',
            'features' => 'required|array|min:1',
        ]);

        $req = new SubscriptionPlanRequest();
        $req->company_name = $request->company_name;
        $req->contact_name = $request->contact_name;
        $req->email = $request->email;
        $req->phone = $request->phone;
        $req->business_type = $request->business_type;
        $req->features = json_encode($request->features);
        $req->additional_requirements = $request->additional_requirements;
        $req->save();

        if ($req->save()) {
            return response()->json(['status' => true, 'message' => "Requested successfully. We’ve received your request and will get back to you shortly."]);
        } else {
            return response()->json(['status' => false, 'message' => "Some Error Occurred"]);
        }
    }
    public function index(Request $request)
    {

        $host = request()->getHost();

        switch ($host) {
            case 'vendor.mcvendorhub.com':
                return redirect('/login');

            case 'vendor-staff.mcvendorhub.com':
                return redirect('/login');

            default:
        }

        $plans = Plan::where('status', 1)->get();
        $features = DB::table('subscription_modules')->where('status', 1)->get();

        $vendor_modules = VendorModuleInstruction::where('status', 1)->get();
        $lines = DataSetting::whereIn('key', ['mc_first_line', 'mc_second_line', 'mc_third_line'])->get();

        $sub_modules = SubModule::all();

        return view('mc-vendor.home', compact('lines', 'vendor_modules', 'plans', 'features', 'sub_modules'));
    }
    public function testing()
    {
        $plans = Plan::where('status', 1)->get();
        $features = DB::table('subscription_modules')->where('status', 1)->get();

        $vendor_modules = VendorModuleInstruction::where('status', 1)->get();
        $lines = DataSetting::whereIn('key', ['mc_first_line', 'mc_second_line', 'mc_third_line'])->get();

        $sub_modules = SubModule::all();

        return view('mc-vendor.home2', compact('lines', 'vendor_modules', 'plans', 'features', 'sub_modules'));
    }
    public function price_calculator()
    {
        $sub_modules = SubModule::all();
        $bedTiers = HospitalBedTier::where('is_active', true)->orderBy('sort_order')->get();
        $studentTiers = \App\Models\SchoolStudentTier::where('is_active', true)->orderBy('sort_order')->get();
        return view('mc-vendor.price_calculator', compact('sub_modules', 'bedTiers', 'studentTiers'));
    }
    public function contact()
    {
        return view('mc-vendor.contact');
    }

    public function blog_mc_vendor(Request $request)
    {
        $blogs = DB::table('blog_posts')
            ->join('blog_categories', 'blog_categories.id', 'blog_posts.category_id')
            ->select('blog_posts.*', 'blog_categories.name as cat_name', 'blog_categories.slug as cat_slug')
            ->where('blog_posts.type', 'mc_vendor')
            ->where('blog_posts.status', 1)
            ->orderByDesc('blog_posts.created_at')
            ->paginate(9);

        $all_categories = BlogCategory::where('type', 'mc_vendor')->where('status', 1)->get();
        return view('mc-vendor.blog.index', compact('blogs', 'all_categories'));
    }

    public function blog_mc_vendor_category(Request $request, $slug)
    {
        $category       = BlogCategory::where('slug', $slug)->where('type', 'mc_vendor')->firstOrFail();
        $all_categories = BlogCategory::where('type', 'mc_vendor')->where('status', 1)->get();

        $blogs = DB::table('blog_posts')
            ->join('blog_categories', 'blog_categories.id', 'blog_posts.category_id')
            ->select('blog_posts.*', 'blog_categories.name as cat_name', 'blog_categories.slug as cat_slug')
            ->where('blog_categories.slug', $slug)
            ->where('blog_posts.type', 'mc_vendor')
            ->where('blog_posts.status', 1)
            ->orderByDesc('blog_posts.created_at')
            ->paginate(9);

        return view('mc-vendor.blog.index', compact('blogs', 'all_categories', 'category'));
    }

    public function blog_mc_vendor_post(Request $request, $slug)
    {
        $blog = DB::table('blog_posts')
            ->join('blog_categories', 'blog_categories.id', 'blog_posts.category_id')
            ->select('blog_posts.*', 'blog_categories.name as cat_name', 'blog_categories.slug as cat_slug')
            ->where('blog_posts.slug', $slug)
            ->where('blog_posts.type', 'mc_vendor')
            ->first();

        abort_if(!$blog, 404);

        $all_categories = BlogCategory::where('type', 'mc_vendor')->where('status', 1)->get();

        $related_blogs = DB::table('blog_posts')
            ->join('blog_categories', 'blog_categories.id', 'blog_posts.category_id')
            ->select('blog_posts.*', 'blog_categories.name as cat_name', 'blog_categories.slug as cat_slug')
            ->where('blog_posts.type', 'mc_vendor')
            ->where('blog_posts.status', 1)
            ->where('blog_posts.slug', '!=', $slug)
            ->orderByDesc('blog_posts.created_at')
            ->limit(6)
            ->get();

        return view('mc-vendor.blog.post', compact('blog', 'all_categories', 'related_blogs'));
    }
    public function send_message(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
            'message' => 'required',
        ]);

        $contact = new Contact();
        $contact->name = $request->name;
        $contact->business_name = $request->business_name;
        $contact->type = 'mc_vendor';
        $contact->phone = $request->phone;
        $contact->email = '';
        $contact->subject = $request->subject ?? '';
        $contact->message = $request->message;
        $contact->file = $request->hasFile('file') ? Helpers::upload('contact/', 'png', $request->file('file')) : null;
        if ($contact->save()) {
            return response()->json(['message' => "Thank you for contacting MC Vendor Hub Support. We’ve received your request and will get back to you shortly."]);
        } else {
            return response()->json(['message' => "Some Error Occurred"]);
        }
    }
}
