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
    private function theme_submodule($sub_modules, array $needles)
    {
        foreach ($needles as $needle) {
            $match = $sub_modules->first(function ($m) use ($needle) {
                return strcasecmp(trim($m->Key ?? ''), $needle) === 0
                    || (!empty($m->name) && stripos($m->name, $needle) !== false);
            });

            if ($match) {
                return $match;
            }
        }

        return null;
    }

    private function theme_module_price($module, $yearly_duration)
    {
        if (!$module) {
            return null;
        }

        $monthly  = (float) ($module->price_per_month ?? 0);
        $months   = (int) ($yearly_duration->months ?? 12);
        $gross    = $monthly * $months;
        $discount = $yearly_duration ? (float) _moduleDiscount($module->id, $yearly_duration->id) : 0;

        return [
            'name'       => $module->name,
            'monthly'    => $monthly,
            'yearly'     => $gross,
            'yearly_net' => $gross - ($gross * $discount / 100),
            'discount'   => $discount,
            'trial_days' => (int) ($module->free_trial_days ?? 0),
        ];
    }

    private function theme_shared()
    {
        $sub_modules = SubModule::all();
        $yearly = _planDurations()->firstWhere('months', 12);

        $needles = [
            'hmis'     => ['hospital_manage', 'hospital', 'hmis'],
            'retail'   => ['pos_retail', 'POS Retail', 'Retail POS'],
            'school'   => ['school_manage', 'school'],
            'laundry'  => ['laundry_manage', 'laundry'],
            'whatsapp' => ['whatsapp'],
        ];

        $mc_pricing = [];
        foreach ($needles as $slug => $candidates) {
            $mc_pricing[$slug] = $this->theme_module_price($this->theme_submodule($sub_modules, $candidates), $yearly);
        }

        return [
            'mc_login_url'  => 'https://vendor.mcvendorhub.com/login',
            'mc_signup_url' => _vendorSignupUrl(),
            'mc_wa_url'     => 'https://wa.me/919951968473',
            'mc_pricing'    => $mc_pricing,
            'mc_modules'    => $this->theme_module_board($sub_modules, $yearly),
            'base_plan'     => Plan::where('status', 1)->orderBy('price')->first(),
            'bedTiers'      => HospitalBedTier::where('is_active', true)->orderBy('sort_order')->get(),
            'studentTiers'  => \App\Models\SchoolStudentTier::where('is_active', true)->orderBy('sort_order')->get(),
        ];
    }

    /**
     * Every priced module the platform actually sells, cheapest first. This is what the website
     * shows in place of mocked-up dashboard screenshots — an admin price change lands on the
     * public site with no one editing a blade. Discounts come from _moduleDiscount() so the board
     * cannot disagree with the pricing tables further down the same page. Modules with no price
     * set are left out rather than shown as ₹0.
     */
    private function theme_module_board($sub_modules, $yearly_duration)
    {
        return $sub_modules
            ->filter(function ($m) {
                return trim($m->name ?? '') !== '' && (float) ($m->price_per_month ?? 0) > 0;
            })
            ->map(function ($m) use ($yearly_duration) {
                return [
                    'name'       => $m->name,
                    'monthly'    => (float) $m->price_per_month,
                    'discount'   => $yearly_duration ? (float) _moduleDiscount($m->id, $yearly_duration->id) : 0,
                    'trial_days' => (int) ($m->free_trial_days ?? 0),
                ];
            })
            ->sortBy('monthly')
            ->values();
    }

    public function theme_home(Request $request)
    {
        $vendor_modules = VendorModuleInstruction::where('status', 1)->get();
        $features = DB::table('subscription_modules')->where('status', 1)->get();

        $mc_plan_action = null;
        foreach (['vendor.mc-vendor.request-subscription-plan', 'vendor.request-subscription-plan'] as $route_name) {
            if (\Illuminate\Support\Facades\Route::has($route_name)) {
                $mc_plan_action = route($route_name);
                break;
            }
        }

        return view('mc-vendor.theme.home', array_merge($this->theme_shared(), compact('vendor_modules', 'features', 'mc_plan_action')));
    }

    public function theme_page(Request $request, $page)
    {
        abort_if(!view()->exists('mc-vendor.theme.' . $page), 404);

        return view('mc-vendor.theme.' . $page, $this->theme_shared());
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
        return view('mc-vendor.price_calculator', array_merge($this->theme_shared(), compact('sub_modules', 'bedTiers', 'studentTiers')));
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
        $contact->brand = 'mcvendorhub';
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
