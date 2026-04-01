<?php

namespace App\Http\Controllers\Front;

use App\CentralLogics\BannerLogic;
use App\CentralLogics\CouponLogic;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use App\CentralLogics\CustomerLogic;
use App\CentralLogics\Helpers;
use App\CentralLogics\OrderLogic;  
use App\Http\Controllers\Controller;
use App\Models\User; 
use App\Models\DMVehicle;
use App\Mail\PlaceOrder;
use Illuminate\Support\Facades\Validator;
use App\Mail\OrderVerificationMail;
use Illuminate\Support\Facades\Mail;
use App\CentralLogics\ProductLogic; 
use App\Http\Controllers\Api\V1\CategoryController as V1CategoryController;
use App\Models\BlogCategory;
use App\Models\BusinessSetting;
use App\Models\Cart;
use Illuminate\Http\Request;
use MatanYadaev\EloquentSpatial\Objects\Point;
use App\Models\DataSetting;
use App\Models\Module;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\InventoryItem;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\ItemCampaign;
use App\Models\ItemFaq;
use App\Models\ItemVariationDetail;
use App\Models\ManualInvoice;
use App\Models\OfferBanner;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ParcelCategory;
use App\Models\SeoContent;
use App\Models\ServiceKeyword;
use App\Models\ServiceRequest;
use App\Models\ZoneRequest;
use App\Models\Store;
use App\Models\StoreConfig;
use App\Models\StoreGallery;
use App\Models\Vendor;
use App\Models\VendorEmployee;
use Illuminate\Support\Facades\DB;
use App\Models\Zone;
use Carbon\Carbon;
use CURLFile;
use Illuminate\Support\Facades\View as FacadesView;
use Illuminate\Support\Facades\Storage;

class FrontController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $latitude;
    private $longitude;
    private $module;
    private $zone_id;
    private $module_id;


    public function __construct()
    {

        $this->middleware(function ($request, $next) {
            $location = Helpers::_setLocation();
            $this->latitude = $location['latitude'];
            $this->longitude = $location['longitude'];
            $this->module = $location['module'];
            $this->zone_id = $location['zone_id'];
            if (isset($location['module']) && is_object($location['module'])) {
                $this->module_id = $location['module']->id;
            } else {
                $this->module_id = null;
            }
            return $next($request);
        });
    }
    public function ocrTest(Request $request)
    {
        return view('front-views.ocr-test');

        // source paddle_env/bin/activate
        // python app.py
    }
    public function ocrTestPost(Request $request)
    {
        // 1️⃣ Validate upload
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,pdf|max:30720',
        ]);

        // 2️⃣ Get uploaded file (TEMP path)
        $file = $request->file('image');

        // VERY IMPORTANT: local real path
        $filePath = $file->getRealPath();

        // 3️⃣ Send to OCR API
        $ch = curl_init("http://159.65.159.250:5000/ocr");

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'image' => new \CURLFile(
                    $filePath,
                    $file->getMimeType(),
                    $file->getClientOriginalName()
                )
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120, // OCR can take time
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            return response()->json([
                'error' => curl_error($ch)
            ], 500);
        }

        curl_close($ch);

        print_r($response);

        // 4️⃣ Return OCR result
        return response()->json(json_decode($response, true));
    }
    /**  
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable 
     */

    public function icons_view()
    {
        return view('front-views.icons');
    }
    public function approve_success(Request $request)
    {
        return view('vendor-views.documents.approve_success');
    }
    public function testing(Request $request) 
    {
hasAnyPermission(['billing.list', 'billing.export', 'billing.import']);

    die;
        $user_fcm = $request->fcm_token;
  $data = [
                'title' => translate('messages.test_notification') ,
                'description' => "Test Notification",
                'order_id' => 3,
                'image' => '', 
                'type' => 'order_status',
            ];
          Helpers::send_push_notif_to_device($user_fcm, $data, null, true);

     $bookings = ServiceRequest::where('user_id', 925)
            ->with([
                'item:id,name',
                'accepted:service_request_id,vendor_id,assigned_to,assigned_type',
                'accepted.store:id,name,phone',
                'accepted.staff' => function (MorphTo $morphTo) {
                    $morphTo->constrain([
                        \App\Models\VendorEmployee::class => function ($q) {
                            $q->select('id', 'f_name', 'l_name', 'phone', 'email', 'image');
                        },
                        \App\Models\Store::class => function ($q) {
                            $q->select('id', 'name', 'phone', 'email', 'logo', 'address');
                        },
                    ]);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'item_id', 'status', 'requirements', 'city', 'created_at', 'updated_at']);
        $data = [
            'success'  => true, 
            'bookings' => $bookings->map(fn($b) => [
                'id'           => $b->id,
                'service_name' => $b->item?->name ?? 'Unknown Service',
                'status'       => $b->status ?? 'new',
                'requirements' => $b->requirements,
                'city'         => $b->city,
                'date'         => $b->created_at?->format('d M Y'),
                'assigned'     => $b->accepted !== null,
                'staff'        => $b->accepted?->assigned_details['name'], // normalized: {id,type,name,phone,...}
                'store'        => $b->accepted?->store?->name,
            ]),
        ];
        prx($data);
        // $filePath = 'apis.json';
        // if (Storage::disk('secure')->exists($filePath)) {
        //     return Storage::disk('secure')->download($filePath);
        // }
        // $fcm_token = 'd53HZ75ERu-oO121i68zsX:APA91bEh0nmc-aiPbN5wrJQ2Vz-5gvNe3XN90JcDZOgsZ4pf6NKCfuCbRVi0epRcTdBSMvhfA_LZmkL0HFFvKsi0lU30V7xrmPBQVNpRbFxr9gMjpu91acw';

        // return view('front-views.test_view');
    }


    public function send_test_notification(Request $request, $receiver = null)
    {
        $email = $request->email;
        $type = $receiver ?? 'vendor';
        if ($type == 'vendor') {
            $reciever = Vendor::where('email', $email)->first();
        } else if ($type == 'staff') {
            $reciever = VendorEmployee::where('email', $email)->first();
        } else {
            $reciever = User::where('email', $email)->first();
        }

        if ($reciever) {
            $fcm_token = $reciever->cm_firebase_token;
            $data = [
                'title' => 'Test Notification ' . $type,
                'description' => 'this is a test notification',
                'order_id' => 0,
                'image' => '',
                'type' => 'order_status',
            ];
            echo   Helpers::send_push_notif_to_device($fcm_token, $data);
        } else {
            echo $type . ' not found';
        }
    }
    public function add_feature_actions(Request $request)
    {
        // $data = [
        // ['feature_id' => '99', 'action' => 'dashboard', 'display_name' => 'Dashboard'],
        // ['feature_id' => '108', 'action' => 'add_for_others', 'display_name' => 'Add for others'],
        // ['feature_id' => '108', 'action' => 'add', 'display_name' => 'Add for self'],
        // ];

        // foreach ($data as $key => $value) {
        //     DB::table('feature_permissions')->insert([
        //         'feature_id' => $value['feature_id'],
        //         'action' => $value['action'],
        //         'display_name' => $value['display_name'] ?? ucfirst($value['action']),
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]);
        // }
    }
    public function registration_success(Request $request)
    {
        return view('front-views.store_reg_successfull');
    }
    public function store_reviews(Request $request, $slug)
    {
        $reviews = DB::table('store_reviews')->join('stores', 'stores.id', 'store_reviews.store_id')->join('users', 'users.id', 'store_reviews.user_id')->where('stores.slug', $slug)->select('users.f_name', 'users.l_name', 'users.image as profile_image', 'stores.*', 'store_reviews.comment', 'store_reviews.attachment', 'store_reviews.created_at', 'store_reviews.rating', 'store_reviews.reply', 'store_reviews.replied_at')->where('store_reviews.status', 1)->get();

        $store = Store::with(['discount' => function ($q) {
            return $q->validate();
        }, 'campaigns', 'schedules', 'activeCoupons'])
            ->withCount(['items', 'campaigns'])
            ->where('slug', $slug)
            ->first();



        return view('front-views.store-reviews', compact('reviews', 'store'));
        return view('front-views.store-terms-and-conditions', compact('reviews'));
    }
    public function index(Request $request)
    {

        $module = $this->module;
        $module_id = config('module.current_module_data')['id'];

        $type =  'all';
        $store_type =  'all';
        $zone_id = $this->zone_id;
        $longitude = $this->longitude;
        $latitude = $this->latitude;

        // featured stores
        $stores['featued_stores'] = Store::whereIn('zone_id',  json_decode($this->zone_id, true))->where(['featured' => 1, 'active' => 1, 'module_id' =>  $this->module_id, 'status' => 1])->paginate(8);

        // categories
        $catController = new V1CategoryController();
        $resp = $catController->get_categories($request);
        $data['h_categories'] = json_decode($resp->getContent(), true);

        $data['service_categories'] = Category::where(['status' => 1, 'position' => 0])->whereNull('added_by')->module(6)->orderBy('priority', 'desc')->get();

        $data['shop_categories'] = Category::with(['childes' => function ($query) {
            $query->withCount(['childes']);
        }])->where(['position' => 0, 'status' => 1])->module(5)->orderBy('priority', 'desc')->get();

        $modules = Module::with('zones')->withCount('items')->whereHas('zones', function ($query) use ($zone_id) {
            $query->whereIn('zone_id', json_decode($zone_id, true));
        })->select('id', 'status', 'module_name', 'thumbnail', 'icon')->active()->get();


        // TOP SELLING PRODUCT 
        $data['top_sell_products'] = DB::table('items')->join('stores', 'stores.id', 'items.store_id')->join('categories', 'categories.id', 'items.category_id')->whereIn('stores.zone_id',  json_decode($zone_id, true))->where('items.status', 1)->where('stores.status', 1)->where('items.is_approved', 1)->where('items.module_id', 5)
            ->select('items.*', 'stores.delivery_time', 'categories.slug as cat_slug')
            ->orderBy("items.order_count", 'desc')
            ->take(6)->get();


        // TOP SELLING  SERVICES 
        $categoryIds = Item::withoutGlobalScopes()
            ->join('stores', function ($join) use ($zone_id) {
                $join->whereRaw('FIND_IN_SET(stores.id, items.store_ids) > 0');
                $join->whereIn('stores.zone_id', json_decode($zone_id, true));
            })
            ->join('categories', 'categories.id', 'items.category_id')
            ->where('items.status', 1)
            ->whereNull('categories.added_by')
            ->where('items.module_id', 6)
            ->distinct('items.category_id')
            ->pluck('items.category_id')
            ->shuffle() // Randomize the categories
            ->take(12);

        $data['top_sell_services'] = collect();
        foreach ($categoryIds as $categoryId) {
            $randomItem = Item::withoutGlobalScopes()
                ->join('stores', function ($join) use ($zone_id) {
                    $join->whereRaw('FIND_IN_SET(stores.id, items.store_ids) > 0');
                    $join->whereIn('stores.zone_id', json_decode($zone_id, true));
                })
                ->where('items.status', 1)
                ->where('items.module_id', 6)
                ->where('items.category_id', $categoryId)
                ->join('categories', 'categories.id', 'items.category_id')
                ->select('items.*', 'categories.slug as cat_slug')
                ->inRandomOrder()
                ->first();

            if ($randomItem) {
                $data['top_sell_services']->push($randomItem);
            }
        }

        // TOP SELLING PRODUCT / SERVICES END 
        //banners 
        $zone_ids = json_decode($zone_id, true);

        $data['offer_banners'] = OfferBanner::where('approved', 1)
            ->where('status', 1)
            ->whereDate('start_date', '<=', date('Y-m-d'))
            ->whereDate('end_date', '>=', date('Y-m-d'))->whereHas('store', function ($query) use ($zone_ids) {
                $query->whereIn('zone_id', $zone_ids);
            })
            ->get();

        if (!count($data['offer_banners'])) {
            $data['offer_banners'] = OfferBanner::where('store_id', 0)
                ->whereDate('start_date', '<=', date('Y-m-d'))
                ->whereDate('end_date', '>=', date('Y-m-d'))->where(function ($query) use ($zone_ids) {
                    $query->whereIn('zone', $zone_ids)
                        ->orWhere('zone', 0);
                })
                ->get();
        }

        $data['nearby_stores'] = _nearbyStoresOptimized($this->zone_id, 9);
        // $data['special_product'] = _getSpecialProduct($zone_id);

        $data['popular_services'] = _getPopularService($zone_id) ?? [];

        // prx($data['popular_services']);
        $data['banners'] = BannerLogic::get_all_module_banners($zone_id, 0, $type = 'default', null, 'web');
        return view('front-views.home', compact('stores', 'zone_id', 'data', 'modules'));
    }


    public function invoice_correction(Request $request)
    {
        $invoices = ManualInvoice::whereDate('created_at', '<', '2025-04-01')->get();

        foreach ($invoices as $key => $invoice) {
            $invoice_items = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->get();
            $from = $invoice->vendor_id ? 'vendor' : 'admin';

            $totalPrice = 0;
            foreach ($invoice_items as $item) {
                $totalPrice += _taxIncludedPrice($item->price, $item->tax, 'actual') * $item->qty;
            }
            $invoice->total_amount = round($totalPrice);
            $invoice->save();
            try {
                $data = _createBillPdf($invoice, $from);
                $invoice->pdf = $data['pdf'];
                $invoice->save();
            } catch (\Throwable $th) {
                // prx($th);
            }
        }
    }

    public function vendor_terms_conditions(Request $request, $v_id)
    {

        $cond = DB::table('vendor_terms_conditions')->where('vendor_id', $v_id)->first();
        if ($cond) {
            $cond = $cond->terms_n_conditons;
        } else {
            $cond = '';
        }

        return view('vendor_t_n_c', compact('cond'));
    }
    public function send_vendor_otp(Request $request)
    {
        $phone =  $request->phone;
        $exists = Store::where('phone', $phone)->exists();

        if (!$exists) {
            return response()->json(['status' => false, 'message' => 'This phone is not registered']);
        }
        $otp  = rand(1000, 9999);

        $sendsms = _send_confirmation_sms('mobile_verification', $phone, $otp);

        $insert  = DB::table('phone_otp')->updateOrInsert([
            'phone' =>  $phone,
        ], [
            'otp' => $otp,
            'created_at' => now()
        ]);

        if ($insert) {
            return response()->json(['status' => true, 'message' => 'OTP sent successfully.', 'action' => 'otp_sent', 'phone' => $phone]);
        } else {
            return response()->json(['status' => false, 'message' => 'Some error occured', 'sms_status' => $sendsms, 'action' => '']);
        }
    }

    public function verify_vendor_otp(Request $request)
    {
        $phone = $request->phone;
        $otp = implode('', $request->otp);
        $verify_otp   = DB::table('phone_otp')->where([
            'phone' =>  $phone,
            'otp' => $otp
        ])->exists();

        if ($verify_otp) {
            return response()->json(['status' => true, 'message' => 'Verified successfully.', 'action' => 'verified', 'otp' => $otp]);
        } else {
            return response()->json(['status' => false, 'message' => 'Incorrect OTP.', 'action' => '', 'otp' => '']);
        }
    }
    public function fetch_subcategory(Request $request)
    {
        $allcategories = [];
        array_push($allcategories, $request->cat_id);

        // Fetch child category IDs
        $ct = Category::where('parent_id', $request->cat_id)
            ->pluck('id')
            ->toArray();

        // Merge the categories
        $allcategories = array_merge($allcategories, $ct);
        $module_id = Category::find($request->cat_id)->module_id;

        if ($module_id == 6) {
            // Prepare the query
            $categories = DB::table('items')->where('items.is_approved', 1)->whereIn('category_id', $allcategories)->whereNull('deleted_at')->get();
        } else {
            $categories = DB::table('categories')->where('parent_id', $request->cat_id)->whereNull('deleted_at')->get();
        }

        return response()->json(['status' => true, 'categories' => $categories]);
    }
    public function fetch_categories(Request $request)
    {
        $categories  = Category::where('module_id', $request->id)->where('parent_id', 0)->select('name', 'id')->get()->toArray();
        return response()->json(['status' => true, 'categories' => $categories]);
    }



    public function blog(Request $request)
    {
        $blogs = DB::table('blog_posts')->join('blog_categories', 'blog_categories.id', 'blog_posts.category_id')->select('blog_posts.*', 'blog_categories.name as cat_name')->where('blog_posts.type', 'common')->paginate(10);

        $all_categories = BlogCategory::where('type', 'common')->where('status', 1)->get();
        return view('front-views.blog.index', compact('blogs', 'all_categories'));
    }
    public function blog_mc_vendor(Request $request)
    {
        $blogs = DB::table('blog_posts')->join('blog_categories', 'blog_categories.id', 'blog_posts.category_id')->select('blog_posts.*', 'blog_categories.name as cat_name')->where('blog_posts.type', 'mc_vendor')->paginate(10);

        $all_categories = BlogCategory::where('type', 'mc_vendor')->where('status', 1)->get();
        return view('front-views.blog.index', compact('blogs', 'all_categories'));
    }


    public function blog_by_category(Request $request, $slug)
    {
        $category = BlogCategory::where('slug', $slug)->first();
        $type = $category->type ?? 'common';

        $blogs = DB::table('blog_posts')->join('blog_categories', 'blog_categories.id', 'blog_posts.category_id')->where('blog_categories.slug', $slug)->select('blog_posts.*', 'blog_categories.name as cat_name')->paginate(10);

        $all_categories = BlogCategory::where('status', 1)->where('type', $type)->get();
        return view('front-views.blog.index', compact('blogs', 'all_categories'));
    }

    public function blog_post(Request $request, $slug)
    {
        $blog = DB::table('blog_posts')->join('blog_categories', 'blog_categories.id', 'blog_posts.category_id')->where('blog_posts.slug', $slug)->select('blog_posts.*', 'blog_categories.name as cat_name')->first();
        $type = $blog->type ?? 'common';
        $all_blogs = DB::table('blog_posts')->join('blog_categories', 'blog_categories.id', 'blog_posts.category_id')->whereNot('blog_posts.slug', $slug)->where('blog_posts.type', $type)->select('blog_posts.*', 'blog_categories.name as cat_name')->limit(6)->get();
        $all_categories = BlogCategory::where('status', 1)->where('type', $type)->get();
        return view('front-views.blog.post', compact('blog', 'all_categories', 'all_blogs', 'type'));
    }
    public function search(Request $request)
    {
        $zone_id = json_decode($this->zone_id, true);
        $keyword = trim($request->keyword);

        if (empty($keyword) || strlen($keyword) < 2) {
            return response()->json([
                'status' => false,
                'html' => '<div class="p-5 fs-4">Please enter at least 2 characters...</div>'
            ]);
        }

        $searchTerm = '%' . $keyword . '%';
        $startsWithTerm = strtolower($keyword) . '%';
        $lowerKeyword = strtolower($keyword);
        $zoneIdPlaceholders = implode(',', array_fill(0, count($zone_id), '?'));

        // Optimized: Pre-filter stores by zone, then join
        $sql = "
        (
            SELECT 
                c.slug as cat_slug,
                i.slug,
                i.name,
                i.id,
                NULL as keyword_text,
                'product' as result_type,
                CASE 
                    WHEN LOWER(i.name) = ? THEN 100
                    WHEN LOWER(i.name) LIKE ? THEN 80
                    ELSE 50
                END as relevance_score
            FROM items i
            STRAIGHT_JOIN categories c ON c.id = i.category_id
            WHERE LOWER(i.name) LIKE ?
                AND i.is_approved = 1
                AND i.status = 1
                AND i.module_id = 6
                AND EXISTS (
                    SELECT 1 
                    FROM stores s 
                    WHERE s.zone_id IN ({$zoneIdPlaceholders})
                    AND FIND_IN_SET(s.id, i.store_ids) > 0
                    LIMIT 1
                )
            GROUP BY i.id
            LIMIT 8
        )
        UNION ALL
        (
            SELECT 
                c.slug as cat_slug,
                i.slug,
                i.name,
                i.id,
                sk.keyword as keyword_text,
                'keyword' as result_type,
                CASE 
                    WHEN LOWER(sk.keyword) = ? THEN 95
                    WHEN LOWER(sk.keyword) LIKE ? THEN 75
                    ELSE 48
                END as relevance_score
            FROM service_keywords sk
            STRAIGHT_JOIN items i ON i.id = sk.service_id
            STRAIGHT_JOIN categories c ON c.id = i.category_id
            WHERE LOWER(sk.keyword) LIKE ?
                AND i.is_approved = 1
                AND i.status = 1
                AND i.module_id = 6
                AND EXISTS (
                    SELECT 1 
                    FROM stores s 
                    WHERE s.zone_id IN ({$zoneIdPlaceholders})
                    AND FIND_IN_SET(s.id, i.store_ids) > 0
                    LIMIT 1
                )
            GROUP BY sk.id
            LIMIT 6
        )
        UNION ALL
        (
            SELECT 
                NULL as cat_slug,
                slug,
                name,
                id,
                NULL as keyword_text,
                'category' as result_type,
                CASE 
                    WHEN LOWER(name) = ? THEN 90
                    WHEN LOWER(name) LIKE ? THEN 70
                    ELSE 45
                END as relevance_score
            FROM categories
            WHERE LOWER(name) LIKE ?
                AND module_id = 6
                AND position = 0
                AND status = 1
            LIMIT 3
        )
        UNION ALL
        (
            SELECT 
                NULL as cat_slug,
                slug,
                name,
                id,
                NULL as keyword_text,
                'store' as result_type,
                CASE 
                    WHEN LOWER(name) = ? THEN 92
                    WHEN LOWER(name) LIKE ? THEN 72
                    ELSE 46
                END as relevance_score
            FROM stores
            WHERE LOWER(name) LIKE ?
                AND module_id = 6
                AND status = 1
                AND zone_id IN ({$zoneIdPlaceholders})
            LIMIT 3
        )
        ORDER BY relevance_score DESC
        LIMIT 20
      ";

        $params = array_merge(
            // Products
            [$lowerKeyword, $startsWithTerm, $searchTerm],
            $zone_id,
            // Keywords
            [$lowerKeyword, $startsWithTerm, $searchTerm],
            $zone_id,
            // Categories
            [$lowerKeyword, $startsWithTerm, $searchTerm],
            // Stores
            [$lowerKeyword, $startsWithTerm, $searchTerm],
            $zone_id
        );

        $allResults = DB::select($sql, $params);

        if (empty($allResults)) {
            $html = '<div class="p-5 fs-4">No Items Found...</div>';
            return response()->json(['status' => false, 'html' => $html]);
        }

        $html = '<ul class="list-unstyled mb-0">';

        foreach ($allResults as $result) {
            switch ($result->result_type) {
                case 'product':
                    $url = route('product.details', [_selectedCity(), $result->slug]);
                    $html .= '<li class="d-flex gap-2"><i class="fa fa-search"></i><a class="d-flex flex-column" href="' . $url . '"><span class="fw-bold">' . e($result->name) . '</span></a></li>';
                    break;

                case 'keyword':
                    $url = route('product.details', [_selectedCity(), $result->slug]);
                    $html .= '<li class="d-flex gap-2"><i class="fa fa-search"></i><a class="d-flex flex-column" href="' . $url . '"><span class="fw-bold">' . e($result->keyword_text) . ' - ' . e($result->name) . '</span></a></li>';
                    break;

                case 'category':
                    $url = route('category.listing', [$result->slug, _selectedCity()]);
                    $html .= '<li class="d-flex gap-2"><i class="fa fa-search"></i><a class="d-flex flex-column" href="' . $url . '"><span class="fw-bold">' . e($result->name) . '</span><small class="text-muted">Category</small></a></li>';
                    break;

                case 'store':
                    $url = route('store.details', [_selectedCity(), $result->slug]);
                    $html .= '<li class="d-flex gap-2"><i class="fa fa-search"></i><a class="d-flex flex-column" href="' . $url . '"><span class="fw-bold">' . e($result->name) . '</span><small class="text-muted">Store</small></a></li>';
                    break;
            }
        }

        $html .= '</ul>';

        return response()->json(['status' => true, 'html' => $html]);
    }

    public function search_old(Request $request)
    {
        $zone_id = json_decode($this->zone_id, true);
        $keyword = trim($request->keyword);

        if (empty($keyword) || strlen($keyword) < 2) {
            return response()->json([
                'status' => false,
                'html' => '<div class="p-5 fs-4">Please enter at least 2 characters...</div>'
            ]);
        }

        $searchTerm = '%' . $keyword . '%';
        $startsWithTerm = $keyword . '%';
        $allResults = collect();

        // 1. Search Products with relevance scoring
        $products = DB::table('items')
            ->join('stores', function ($join) {
                $join->whereRaw("FIND_IN_SET(stores.id, items.store_ids)");
            })
            ->join('categories', 'categories.id', '=', 'items.category_id')
            ->where(function ($query) use ($keyword, $searchTerm) {
                $query->where('items.name', 'like', $searchTerm)
                    ->orWhereRaw('items.name REGEXP ?', ['[[:<:]]' . preg_quote($keyword, '/') . '[[:>:]]']);
            })
            ->where('items.is_approved', 1)
            ->where('items.status', 1)
            ->where('items.module_id', 6)
            ->whereIn('stores.zone_id', $zone_id)
            ->selectRaw(
                "categories.slug as cat_slug,
            items.slug,
            items.name,
            items.id,
            NULL as keyword_text,
            'product' as result_type,
            CASE 
                WHEN LOWER(items.name) = LOWER(?) THEN 100
                WHEN items.name REGEXP ? THEN 80
                WHEN LOWER(items.name) LIKE LOWER(?) THEN 50
                ELSE 10
            END as relevance_score",
                [$keyword, '[[:<:]]' . preg_quote($keyword, '/') . '[[:>:]]', $startsWithTerm]
            )
            ->groupBy('items.id', 'items.slug', 'items.name', 'categories.slug')
            ->get();

        // 2. Search Keywords with service details
        $keywords = DB::table('service_keywords')
            ->join('items', 'items.id', '=', 'service_keywords.service_id')
            ->join('categories', 'categories.id', '=', 'items.category_id')
            ->join('stores', function ($join) {
                $join->whereRaw("FIND_IN_SET(stores.id, items.store_ids)");
            })
            ->where(function ($query) use ($keyword, $searchTerm) {
                $query->where('service_keywords.keyword', 'LIKE', $searchTerm)
                    ->orWhereRaw('service_keywords.keyword REGEXP ?', ['[[:<:]]' . preg_quote($keyword, '/') . '[[:>:]]']);
            })
            ->where('items.is_approved', 1)
            ->where('items.status', 1)
            ->where('items.module_id', 6)
            ->whereIn('stores.zone_id', $zone_id)
            ->selectRaw(
                "categories.slug as cat_slug,
            items.slug,
            items.name,
            items.id,
            service_keywords.keyword as keyword_text,
            'keyword' as result_type,
            CASE 
                WHEN LOWER(service_keywords.keyword) = LOWER(?) THEN 95
                WHEN service_keywords.keyword REGEXP ? THEN 75
                WHEN LOWER(service_keywords.keyword) LIKE LOWER(?) THEN 48
                ELSE 8
            END as relevance_score",
                [$keyword, '[[:<:]]' . preg_quote($keyword, '/') . '[[:>:]]', $startsWithTerm]
            )
            ->groupBy('items.id', 'items.slug', 'items.name', 'categories.slug', 'service_keywords.keyword')
            ->get();

        // 3. Search Categories
        $categories = DB::table('categories')
            ->where(function ($query) use ($keyword, $searchTerm) {
                $query->where('name', 'LIKE', $searchTerm)
                    ->orWhereRaw('name REGEXP ?', ['[[:<:]]' . preg_quote($keyword, '/') . '[[:>:]]']);
            })
            ->where('module_id', 6)
            ->where('position', 0)
            ->where('status', 1)
            ->selectRaw(
                "NULL as cat_slug,
            slug,
            name,
            id,
            NULL as keyword_text,
            'category' as result_type,
            CASE 
                WHEN LOWER(name) = LOWER(?) THEN 90
                WHEN name REGEXP ? THEN 70
                WHEN LOWER(name) LIKE LOWER(?) THEN 45
                ELSE 7
            END as relevance_score",
                [$keyword, '[[:<:]]' . preg_quote($keyword, '/') . '[[:>:]]', $startsWithTerm]
            )
            ->get();

        // 4. Search Stores
        $stores = DB::table('stores')
            ->where(function ($query) use ($keyword, $searchTerm) {
                $query->where('name', 'like', $searchTerm)
                    ->orWhereRaw('name REGEXP ?', ['[[:<:]]' . preg_quote($keyword, '/') . '[[:>:]]']);
            })
            ->where('module_id', 6)
            ->where('status', 1)
            ->whereIn('zone_id', $zone_id)
            ->selectRaw(
                "NULL as cat_slug,
            slug,
            name,
            id,
            NULL as keyword_text,
            'store' as result_type,
            CASE 
                WHEN LOWER(name) = LOWER(?) THEN 92
                WHEN name REGEXP ? THEN 72
                WHEN LOWER(name) LIKE LOWER(?) THEN 46
                ELSE 7
            END as relevance_score",
                [$keyword, '[[:<:]]' . preg_quote($keyword, '/') . '[[:>:]]', $startsWithTerm]
            )
            ->get();

        // Merge all results
        $allResults = $allResults
            ->concat($products)
            ->concat($keywords)
            ->concat($categories)
            ->concat($stores);

        $allResults = $allResults
            ->sortByDesc('relevance_score')
            ->take(20)
            ->values();

        if ($allResults->isEmpty()) {
            $html = '<div class="p-5 fs-4">No Items Found...</div>';
            return response()->json(['status' => false, 'html' => $html]);
        }

        $html = '<ul class="list-unstyled mb-0">';

        foreach ($allResults as $result) {
            $linkHtml = '';

            switch ($result->result_type) {
                case 'product':
                    $url = route('product.details', [_selectedCity(), $result->slug]);

                    $linkHtml = '<li class="d-flex gap-2"><i class="fa fa-search"></i><a class="d-flex flex-column" href="' . $url . '"><span class="fw-bold">' . e($result->name) . '</span></a></li>';
                    break;

                case 'keyword':
                    $url = route('product.details', [_selectedCity(), $result->slug]);
                    $linkHtml = '<li class="d-flex gap-2"><i class="fa fa-search"></i><a class="d-flex flex-column" href="' . $url . '"><span class="fw-bold">' . e($result->keyword_text) . ' - ' . e($result->name) . '</span></a></li>';
                    break;

                case 'category':
                    $url = route('category.listing', [$result->slug, _selectedCity()]);
                    $linkHtml = '<li class="d-flex gap-2"><i class="fa fa-search"></i><a class="d-flex flex-column" href="' . $url . '"><span class="fw-bold">' . e($result->name) . '</span><small class="text-muted">Category</small></a></li>';
                    break;

                case 'store':
                    $url = route('store.details', [_selectedCity(), $result->slug]);
                    $linkHtml = '<li class="d-flex gap-2" ><i class="fa fa-search"></i><a class="d-flex flex-column" href="' . $url . '"><span class="fw-bold">' . e($result->name) . '</span><small class="text-muted">Store</small></a></li>';
                    break;
            }

            $html .= $linkHtml;
        }

        $html .= '</ul>';

        return response()->json(['status' => true, 'html' => $html]);
    }

    public function all_stores(Request $request, $type = null)
    {
        if ($type == 'nearby') {
            $stores = _nearbyStores($this->zone_id, null, 16);
        } else {
            $userLat = session('latitude');
            $userLng = session('longitude');


            $query = Store::select('stores.*')
                ->leftJoin('store_enabled_modules', 'store_enabled_modules.store_id', 'stores.id')
                ->where([
                    'stores.active' => 1,
                    'stores.status' => 1,
                    'stores.module_id' => 6
                ])
                ->whereIn('zone_id', json_decode($this->zone_id, true))
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->selectRaw("( 6371 * acos(
                    cos(radians(?)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(latitude))
                )
            ) AS distance", [$userLat, $userLng, $userLat])->groupBy('stores.id');;
            $query->orderBy('distance', 'asc');
            $stores = $query->paginate(18);
        }
        return view('front-views.store-list', compact('stores'));
    }

    public function change_module(Request $request)
    {
        $module_id = $request->moduleId;
        session(['moduleId' => $module_id]);
        if ($module_id == 6) {
            return response()->json(['status' => true, 'message' => 'Switched to Services']);
        } else {
            return response()->json(['status' => true, 'message' => 'Switched to Shop']);
        }
    }

    public function update_location(Request $request)
    {
        session(['latitude' => $request->latitude, 'longitude' =>  $request->longitude, 'customer_address' => $request->address, 'customer_city' => $request->city]);
    }
    public function contact()
    {
        return view('front-views.contact');
    }
    public function about()
    {
        $content = DB::table('data_settings')->where('type', 'admin_landing_page')->where('key', 'about_us')->first();
        $title = DB::table('data_settings')->where('type', 'admin_landing_page')->where('key', 'about_title')->first();
        return view('front-views.about', compact('content', 'title'));
    }
    public function faq()
    {
        $content = DB::table('data_settings')->where('type', 'admin_landing_page')->where('key', 'faq')->first();
        return view('front-views.faq', compact('content'));
    }
    public function disclaimer()
    {
        $content = DB::table('data_settings')->where('type', 'admin_landing_page')->where('key', 'disclaimer')->first();
        return view('front-views.disclaimer', compact('content'));
    }
    public function privacy_policy()
    {
        $content = DB::table('data_settings')->where('type', 'admin_landing_page')->where('key', 'privacy_policy')->first();
        return view('front-views.privacy-policy', compact('content'));
    }
    public function terms_n_conditions()
    {
        $content = DB::table('data_settings')->where('type', 'admin_landing_page')->where('key', 'terms_and_conditions')->first();
        return view('front-views.terms-n-conditions', compact('content'));
    }
    public function refund_policy()
    {
        $content = DB::table('data_settings')->where('type', 'admin_landing_page')->where('key', 'refund_policy')->first();
        return view('front-views.refund-policy', compact('content'));
    }
    public function cancellation_policy()
    {
        $content = DB::table('data_settings')->where('type', 'admin_landing_page')->where('key', 'cancellation_policy')->first();
        return view('front-views.cancellation-policy', compact('content'));
    }
    public function shipping_policy()
    {
        $content = DB::table('data_settings')->where('type', 'admin_landing_page')->where('key', 'shipping_policy')->first();
        return view('front-views.shipping-policy', compact('content'));
    }


    public function send_otp(Request $request)
    {
        $phone = $request->phone;

        // Check if phone is already registered
        if (Store::where('phone', $phone)->exists()) {
            return response()->json(['status' => false, 'message' => 'This phone is already registered']);
        }

        // Get last OTP record
        $lastOtp = DB::table('phone_otp')->where('phone', $phone)
            ->orderBy('created_at', 'desc')
            ->first();

        // **Check if user has reached 3 attempts (apply 10-minute lock)**
        if ($lastOtp && $lastOtp->attempts >= 3) {
            $timePassed = Carbon::now()->diffInMinutes($lastOtp->created_at);
            $timeLeft = max(10 - $timePassed, 0);

            if ($timeLeft > 0) {
                return response()->json([
                    'status' => false,
                    'message' => "You've reached the maximum attempts. Please try again after $timeLeft minutes."
                ]);
            } else {
                // Reset attempts after 10 minutes
                DB::table('phone_otp')->where('phone', $phone)->update(['attempts' => 0]);
            }
        }

        //  **Check if OTP was sent in the last 1 minute**
        if ($lastOtp && Carbon::parse($lastOtp->created_at)->diffInSeconds(Carbon::now()) < 60) {
            $timeLeft = 60 - Carbon::parse($lastOtp->created_at)->diffInSeconds(Carbon::now());
            return response()->json([
                'status' => false,
                'message' => "Please wait $timeLeft seconds before requesting another OTP."
            ]);
        }

        //  **Generate OTP**
        $otp = rand(1000, 9999);
        $sendsms = _send_confirmation_sms('mobile_verification', $phone, $otp);
        // $sendsms = true; // Simulating SMS sent (remove in production)

        //  **Insert or Update OTP in the Database**
        if (!$lastOtp) {
            // Insert new OTP
            $insert = DB::table('phone_otp')->updateOrInsert([
                'phone' => $phone,
            ], [
                'otp' => $otp,
                'attempts' => 1,
                'created_at' => now()
            ]);
        } else {
            // Update OTP and increase attempt count
            $insert = DB::table('phone_otp')->where('phone', $phone)->update([
                'attempts' => (int) $lastOtp->attempts + 1,
                'otp' => $otp,
                'created_at' => now()
            ]);
        }

        //  **Return Response**
        if ($insert) {
            return response()->json([
                'status' => true,
                'message' => 'OTP sent successfully.',
                'action' => 'otp_sent',
                'phone' => $phone
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Some error occurred',
                'sms_status' => $sendsms,
                'action' => ''
            ]);
        }
    }

    public function verify_otp(Request $request)
    {
        $phone = $request->phone;
        $otp = implode('', $request->otp);
        $verify_otp   = DB::table('phone_otp')->where([
            'phone' =>  $phone,
            'otp' => $otp
        ])->exists();

        if ($verify_otp) {
            return response()->json(['status' => true, 'message' => 'Verified successfully.', 'action' => 'verified', 'otp' => $otp]);
        } else {
            return response()->json(['status' => false, 'message' => 'Incorrect OTP.', 'action' => '', 'otp' => '']);
        }
    }

    public function missing_zone_request(Request $request)
    {
        $value = $this->getBoundingBoxCoordinates($request->place);
        $exists = ZoneRequest::where('coordinates', $value)->exists();

        if (!$exists) {
            $zoneRequest = new ZoneRequest();
            $zoneRequest->user_id = auth('web')->user() ? auth('web')->user()->email : 0;
            $zoneRequest->user_email = $request->user_mail ? $request->user_mail : '';
            $zoneRequest->place_name = $request->place;
            $zoneRequest->coordinates = $value;
            $zoneRequest->save();
        }
        return response()->json(['status' => true, 'message' => 'Requested Successfully. We will approve and notify you via email.']);
    }


    public function getBoundingBoxCoordinates($address)
    {
        $encodedAddress = urlencode($address);
        $apiKey = BusinessSetting::where(['key' => 'map_api_key_server'])->first()['value'];

        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$encodedAddress}&key={$apiKey}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($responseData['status'] === 'OK') {
            $northeast = $responseData['results'][0]['geometry']['bounds']['northeast'];
            $southwest = $responseData['results'][0]['geometry']['bounds']['southwest'];

            $northwest = [
                'lat' => $northeast['lat'],
                'lng' => $southwest['lng']
            ];
            $southeast = [
                'lat' => $southwest['lat'],
                'lng' => $northeast['lng']
            ];

            $coordinates = "({$northeast['lat']}, {$northeast['lng']}),"
                . "({$northwest['lat']}, {$northwest['lng']}),"
                . "({$southwest['lat']}, {$southwest['lng']}),"
                . "({$southeast['lat']}, {$southeast['lng']}),"
                . "({$northeast['lat']}, {$northeast['lng']})";

            return $coordinates;
        } else {
            return "Coordinates not found2.";
        }
    }

    public function coupon_list(Request $request)
    {

        $customer_id =  auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');

        $zone_id = $this->zone_id;

        $data = [];
        $coupons = Coupon::with('store:id,name')->active()
            ->when(config('module.current_module_data'), function ($query) {
                $query->module(config('module.current_module_data')['id']);
            })
            ->whereDate('expire_date', '>=', date('Y-m-d'))->whereDate('start_date', '<=', date('Y-m-d'))->get();
        foreach ($coupons as $key => $coupon) {
            if ($coupon->coupon_type == 'store_wise') {
                $temp = Store::active()
                    ->when(config('module.current_module_data'), function ($query) use ($zone_id) {
                        if (!config('module.current_module_data')['all_zone_service']) {
                            $query->whereIn('zone_id', json_decode($zone_id, true));
                        }
                    })
                    ->whereIn('id', json_decode($coupon->data, true))->first();
                if ($temp && (in_array("all", json_decode($coupon->customer_id, true)) || in_array($customer_id, json_decode($coupon->customer_id, true)))) {
                    $coupon->data = $temp->name;
                    $coupon['store_id'] = (int)$temp->id;
                    $data[] = $coupon;
                }
            } else if ($coupon->coupon_type == 'zone_wise') {
                if (count(array_intersect(json_decode($zone_id, true), json_decode($coupon->data, true)))) {
                    $data[] = $coupon;
                }
            } else if (isset($coupon->store_id)) {
                $temp = Store::active()->when(config('module.current_module_data'), function ($query) use ($zone_id) {
                    if (!config('module.current_module_data')['all_zone_service']) {
                        $query->whereIn('zone_id', json_decode($zone_id, true));
                    }
                })->where('id', $coupon->store_id)->exists();

                if ($temp) {
                    $data[] = $coupon;
                }
            } else {
                if ((in_array("all", json_decode($coupon->customer_id, true)) || in_array($customer_id, json_decode($coupon->customer_id, true)))) {
                    $data[] = $coupon;
                }
            }
        }

        return response()->json($data, 200);
    }
    public function cart()
    {
        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');
        $is_guest = auth('web')->user() ? 0 : 1;

        $cart = DB::table('carts')
            ->join('items', 'items.id', 'carts.item_id')
            ->join('categories', 'categories.id', 'items.category_id')
            ->join('stores', 'items.store_id', 'stores.id')
            ->where('carts.user_id', $user_id)->where('carts.is_guest', $is_guest)->where('carts.module_id', 5)
            ->select('items.name', 'items.image', 'items.price as item_price', 'items.mrp_price', 'items.slug',  'items.discount as item_discount', 'items.discount_type', 'stores.slug as store_slug', 'carts.*', 'categories.slug as cat_slug')
            ->get();

        return view('front-views.cart', compact('cart'));
    }
    public function checkout()
    {
        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');
        $is_guest = auth('web')->user() ? 0 : 1;

        // user address =================
        $user_addresses = CustomerAddress::where('user_id', $user_id)->get();
        $user = User::find($user_id);

        $cart = DB::table('carts')
            ->join('items', 'items.id', 'carts.item_id')
            ->join('stores', 'stores.id', 'items.store_id')
            ->join('categories', 'categories.id', 'items.category_id')
            ->where('carts.user_id', $user_id)->where('carts.is_guest', $is_guest)->where('carts.module_id', 5)
            ->select('items.name', 'items.slug', 'items.image', 'items.price as item_price', 'items.mrp_price', 'items.tax', 'items.discount as item_discount', 'items.discount_type', 'items.store_id', 'categories.slug as cat_slug', 'carts.*')
            ->get();
        if (!count($cart)) {
            return redirect('cart');
        }
        $store = Store::where('id', $cart[0]->store_id)->first();


        // COUPONS  
        $customer_id = $user_id;
        $zone_id = $this->zone_id;

        $coupondata = [];
        $coupons = Coupon::with('store:id,name')->active()
            ->when(config('module.current_module_data'), function ($query) {
                $query->module(config('module.current_module_data')['id']);
            })
            ->whereDate('expire_date', '>=', date('Y-m-d'))->whereDate('start_date', '<=', date('Y-m-d'))->get();
        foreach ($coupons as $key => $coupon) {
            if ($coupon->coupon_type == 'store_wise') {
                $temp = Store::active()
                    ->when(config('module.current_module_data'), function ($query) use ($zone_id) {
                        if (!config('module.current_module_data')['all_zone_service']) {
                            $query->whereIn('zone_id', json_decode($zone_id, true));
                        }
                    })
                    ->whereIn('id', json_decode($coupon->data, true))->first();
                if ($temp && (in_array("all", json_decode($coupon->customer_id, true)) || in_array($customer_id, json_decode($coupon->customer_id, true)))) {
                    $coupon->data = $temp->name;
                    $coupon['store_id'] = (int)$temp->id;
                    $coupondata[] = $coupon;
                }
            } else if ($coupon->coupon_type == 'zone_wise') {
                if (count(array_intersect(json_decode($zone_id, true), json_decode($coupon->data, true)))) {
                    $coupondata[] = $coupon;
                }
            } else if (isset($coupon->store_id)) {
                $temp = Store::active()->when(config('module.current_module_data'), function ($query) use ($zone_id) {
                    if (!config('module.current_module_data')['all_zone_service']) {
                        $query->whereIn('zone_id', json_decode($zone_id, true));
                    }
                })->where('id', $coupon->store_id)->exists();

                if ($temp) {
                    $coupondata[] = $coupon;
                }
            } else {
                if ((in_array("all", json_decode($coupon->customer_id, true)) || in_array($customer_id, json_decode($coupon->customer_id, true)))) {
                    $coupondata[] = $coupon;
                }
            }
        }

        return view('front-views.checkout', compact('cart', 'store', 'user_addresses', 'coupondata', 'user'));
    }

    public function apply_coupon(Request $request)
    {

        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');
        $is_guest = auth('web')->user() ? 0 : 1;


        $coupon = Coupon::active()->where(['code' => $request->code])->first();
        if (isset($coupon)) {
            $staus = CouponLogic::is_valide($coupon, $user_id, $request->store_id);

            switch ($staus) {
                case 200:
                    return response()->json(['coupon' => $coupon, 'message' => 'Applied successfully']);
                case 406:
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.coupon_usage_limit_over')]
                        ]
                    ]);
                case 407:
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.coupon_expire')]
                        ]
                    ]);
                case 408:
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.You_are_not_eligible_for_this_coupon')]
                        ]
                    ]);
                default:
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.not_found')]
                        ]
                    ]);
            }
        } else {
            return response()->json([
                'errors' => [
                    ['code' => 'coupon', 'message' => translate('messages.not_found')]
                ]
            ]);
        }
    }



    public function place_order(Request $request)
    {
        $key = $request->addr_id;
        $user_id =  auth('web')->user()->id;
        $user = User::find($user_id);
        $validator = Validator::make($request->all(), [
            'order_amount' => 'required',
            'payment_method' => 'required|in:cash_on_delivery,digital_payment,wallet,offline_payment',
            'order_type' => 'required|in:take_away,delivery,parcel',
            'store_id' => 'required_unless:order_type,parcel',
            // 'distance' => 'required_unless:order_type,take_away',
            'address' => 'required_unless:order_type,take_away',
            // 'longitude' => 'required_unless:order_type,take_away',
            // 'latitude' => 'required_unless:order_type,take_away',
            'parcel_category_id' => 'required_if:order_type,parcel',
            'receiver_details' => 'required_if:order_type,parcel',
            'charge_payer' => 'required_if:order_type,parcel|in:sender,receiver',
            'dm_tips' => 'nullable|numeric',
            'guest_id' => $user ? 'nullable' : 'required',
            // 'contact_person_name' => $user ? 'nullable' : 'required',
            // 'contact_person_number' => $user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if ($request->is_guest && !Helpers::get_mail_status('guest_checkout_status')) {
            return response()->json([
                'errors' => [
                    ['code' => 'is_guest', 'message' => translate('messages.Guest_order_is_not_active')]
                ]
            ]);
        }
        $coupon = null;
        $coupon_created_by = null;
        $delivery_charge = null;
        $schedule_at = $request->schedule_at ? \Carbon\Carbon::parse($request->schedule_at) : now();
        $store = null;
        $free_delivery_by = null;
        $distance_data = 15.257;
        $increased = 0;
        $vr_tax = false;
        $maximum_shipping_charge = 0;
        $total_order_tax = 0;

        if ($request->order_type == 'delivery' && !Helpers::get_business_settings('home_delivery_status')) {
            return response()->json([
                'errors' => [
                    ['code' => 'order_type', 'message' => translate('messages.home_delivery_is_not_active')]
                ]
            ]);
        }

        if ($request->order_type == 'take_away' && !Helpers::get_business_settings('takeaway_status')) {
            return response()->json([
                'errors' => [
                    ['code' => 'order_type', 'message' => translate('messages.take_away_is_not_active')]
                ]
            ]);
        }

        if ($request->partial_payment && !Helpers::get_business_settings('partial_payment_status')) {
            return response()->json([
                'errors' => [
                    ['code' => 'order_method', 'message' => translate('messages.partial_payment_is_not_active')]
                ]
            ]);
        }

        if ($request->payment_method == 'offline_payment' &&  Helpers::get_mail_status('offline_payment_status') == 0) {
            return response()->json([
                'errors' => [
                    ['code' => 'offline_payment_status', 'message' => translate('messages.offline_payment_for_the_order_not_available_at_this_time')]
                ]
            ]);
        }

        $digital_payment = Helpers::get_business_settings('digital_payment');
        if ($digital_payment['status'] == 0 && $request->payment_method == 'digital_payment') {
            return response()->json([
                'errors' => [
                    ['code' => 'digital_payment', 'message' => translate('messages.digital_payment_for_the_order_not_available_at_this_time')]
                ]
            ]);
        }

        $data =  DMVehicle::active()->where(function ($query) use ($distance_data) {
            $query->where('starting_coverage_area', '<=', $distance_data)->where('maximum_coverage_area', '>=', $distance_data)
                ->orWhere(function ($query) use ($distance_data) {
                    $query->where('starting_coverage_area', '>=', $distance_data);
                });
        })
            ->orderBy('starting_coverage_area')->first();

        $extra_charges = (float) (isset($data) ? $data->extra_charges  : 0);
        $vehicle_id = (isset($data) ? $data->id  : null);

        $zone = null;
        if ($request->latitude[$key] && $request->longitude[$key]) {
            $point = new Point($request->latitude[$key], $request->longitude[$key]);
            if ($request->order_type == 'parcel') {
                // if(isset($request->sender_zone_id) ){
                // $zone_id = $request->sender_zone_id;
                $zone_ids = $request->header('zoneId') ? json_decode($request->header('zoneId'), true) : [];
                $zone = Zone::whereIn('id', $zone_ids)->whereContains('coordinates', new Point($request->latitude[$key], $request->longitude[$key], POINT_SRID))->wherehas('modules', function ($q) {
                    $q->where('module_type', 'parcel');
                })->first();
            } else {
                // ->selectRaw('*, IF(((select count(*) from `store_schedule` where `stores`.`id` = `store_schedule`.`store_id` and `store_schedule`.`day` = ' . $schedule_at->format('w') . ' and `store_schedule`.`opening_time` < "' . $schedule_at->format('H:i:s') . '" and `store_schedule`.`closing_time` >"' . $schedule_at->format('H:i:s') . '") > 0), true, false) as open')
                $store = Store::with('discount')->where('id', $request->store_id)->first();

                if (!$store) {
                    return response()->json([
                        'errors' => [
                            ['code' => 'order_time', 'message' => translate('messages.store_not_found')]
                        ]
                    ]);
                }
                $zone_id =  [$store->zone_id];
                $zone = Zone::where('id', $zone_id)->whereContains('coordinates', new Point($request->latitude[$key], $request->longitude[$key], POINT_SRID))->first();
            }
        }
        if (!$zone) {
            $errors = [];
            array_push($errors, ['code' => 'coordinates', 'message' => translate('messages.out_of_coverage')]);
            return response()->json([
                'errors' => $errors
            ]);
        }
        if ($zone && $zone->increased_delivery_fee_status == 1) {
            $increased = $zone->increased_delivery_fee ?? 0;
        }

        if ($request->order_type !== 'parcel') {


            if (!$store->active) {
                return response()->json([
                    'errors' => [
                        ['code' => 'order_time', 'message' => translate('messages.store_is_closed_at_order_time')]
                    ]
                ]);
            }

            if ($request->coupon_code) {
                $coupon = Coupon::active()->where(['code' => $request->coupon_code])->first();
                if (isset($coupon)) {


                    $staus = CouponLogic::is_valide($coupon, $user->id, $request->store_id);


                    if ($staus == 407) {
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => translate('messages.coupon_expire')]
                            ]
                        ]);
                    } else if ($staus == 408) {
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => translate('messages.You_are_not_eligible_for_this_coupon')]
                            ]
                        ]);
                    } else if ($staus == 406) {
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => translate('messages.coupon_usage_limit_over')]
                            ]
                        ]);
                    } else if ($staus == 404) {
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => translate('messages.not_found')]
                            ]
                        ]);
                    }

                    $coupon_created_by = $coupon->created_by;
                    if ($coupon->coupon_type == 'free_delivery') {
                        $delivery_charge = 0;
                        $free_delivery_by =  $coupon_created_by;
                        $coupon_created_by = null;
                    }
                } else {
                    return response()->json([
                        'errors' => [
                            ['code' => 'coupon', 'message' => translate('messages.not_found')]
                        ]
                    ], 404);
                }
            }

            $module_wise_delivery_charge = $store->zone->modules()->where('modules.id', 5)->first();
            if ($module_wise_delivery_charge) {
                $per_km_shipping_charge = $module_wise_delivery_charge->pivot->per_km_shipping_charge;
                $minimum_shipping_charge = $module_wise_delivery_charge->pivot->minimum_shipping_charge;
                $maximum_shipping_charge = $module_wise_delivery_charge->pivot->maximum_shipping_charge;
            } else {
                $per_km_shipping_charge = (float)BusinessSetting::where(['key' => 'per_km_shipping_charge'])->first()->value;
                $minimum_shipping_charge = (float)BusinessSetting::where(['key' => 'minimum_shipping_charge'])->first()->value;
            }

            if ($request->order_type != 'take_away' && !$store->free_delivery && !isset($delivery_charge) &&  $store->self_delivery_system == 1) {
                $per_km_shipping_charge = $store->per_km_shipping_charge;
                $minimum_shipping_charge = $store->minimum_shipping_charge;
                $maximum_shipping_charge = $store->maximum_shipping_charge;
                $extra_charges = 0;
                $vehicle_id = null;
                $increased = 0;
            }

            if ($store->free_delivery || $free_delivery_by == 'vendor') {
                $per_km_shipping_charge = $store->per_km_shipping_charge;
                $minimum_shipping_charge = $store->minimum_shipping_charge;
                $maximum_shipping_charge = $store->maximum_shipping_charge;
                $extra_charges = 0;
                $increased = 0;
            }

            $original_delivery_charge = (($request->distance * $per_km_shipping_charge) > $minimum_shipping_charge) ? $request->distance * $per_km_shipping_charge  : $minimum_shipping_charge;

            if ($request->order_type == 'take_away') {
                $per_km_shipping_charge = 0;
                $minimum_shipping_charge = 0;
                $maximum_shipping_charge = 0;
                $extra_charges = 0;
                $distance_data = 0;
                $vehicle_id = null;
                $original_delivery_charge = 0;
                $increased = 0;
            }

            if ($maximum_shipping_charge  >= $minimum_shipping_charge  && $original_delivery_charge >  $maximum_shipping_charge) {
                $original_delivery_charge = $maximum_shipping_charge;
            } else {
                $original_delivery_charge = $original_delivery_charge;
            }

            if (!isset($delivery_charge)) {
                $delivery_charge = ($request->distance * $per_km_shipping_charge > $minimum_shipping_charge) ? $request->distance * $per_km_shipping_charge : $minimum_shipping_charge;
                if ($maximum_shipping_charge  >= $minimum_shipping_charge  && $delivery_charge >  $maximum_shipping_charge) {
                    $delivery_charge = $maximum_shipping_charge;
                } else {
                    $delivery_charge = $delivery_charge;
                }
            }
            $original_delivery_charge = $original_delivery_charge + $extra_charges;
            $delivery_charge = $delivery_charge + $extra_charges;
        } else {
            $parcel_category = ParcelCategory::findOrFail($request->parcel_category_id);
            if (isset($parcel_category) && isset($parcel_category->parcel_minimum_shipping_charge)) {
                $per_km_shipping_charge = $parcel_category->parcel_per_km_shipping_charge;
                $minimum_shipping_charge = $parcel_category->parcel_minimum_shipping_charge;
            } else {
                $per_km_shipping_charge = (float)BusinessSetting::where(['key' => 'parcel_per_km_shipping_charge'])->first()->value;
                $minimum_shipping_charge = (float)BusinessSetting::where(['key' => 'parcel_minimum_shipping_charge'])->first()->value;
            }

            $original_delivery_charge = (($request->distance * $per_km_shipping_charge) > $minimum_shipping_charge) ? ($request->distance * $per_km_shipping_charge) + $extra_charges : ($minimum_shipping_charge + $extra_charges);
        }

        if ($increased > 0) {
            if ($delivery_charge > 0) {
                $increased_fee = ($delivery_charge * $increased) / 100;
                $delivery_charge = $delivery_charge + $increased_fee;
            }
            if ($original_delivery_charge > 0) {
                $increased_fee = ($original_delivery_charge * $increased) / 100;
                $original_delivery_charge = $original_delivery_charge + $increased_fee;
            }
        }
        $address = [
            'contact_person_name' => $request->contact_person_name[$key] ? $request->contact_person_name[$key] : ($user ? $user->f_name . ' ' . $user->f_name : ''),
            'contact_person_number' => $request->contact_person_number[$key] ? ($user ? $request->contact_person_number[$key] : str_replace('+', '', $request->contact_person_number[$key])) : ($user ? $user->phone : ''),
            'contact_person_email' => $user ? $user->email : '',
            'address_type' => $request->address_type ? $request->address_type : 'Delivery',
            'address' => $request?->address[$key] ?? '',
            'floor' => $request?->floor[$key] ?? '',
            'road' => $request?->road[$key] ?? '',
            'house' => $request?->house[$key] ?? '',
            'longitude' => (string)$request->longitude[$key],
            'latitude' => (string)$request->latitude[$key],
        ];
        $total_addon_price = 0;
        $product_price = 0;
        $store_discount_amount = 0;
        $flash_sale_vendor_discount_amount = 0;
        $flash_sale_admin_discount_amount = 0;
        $store_discount_amount = 0;
        $product_data = [];

        $order_details = [];
        $order = new Order();
        $lastOrder = Order::orderBy('id', 'desc')->first();

        if ($lastOrder) {
            $order->id = $lastOrder->id + 1;
        } else {
            $order->id = 10001;
        }
        $order->invoice_id = _generateOrderInvoiceId($request->store_id);

        $order_status = 'pending';
        if (($request->partial_payment && $request->payment_method != 'offline_payment') || $request->payment_method == 'wallet') {
            $order_status = 'confirmed';
        }

        $order->user_id = $user ? $user->id : 0;
        $order->order_amount = $request->order_amount;
        $order->payment_status = ($request->partial_payment ? 'partially_paid' : ($request->payment_method == 'wallet' ? 'paid' : 'unpaid'));
        $order->order_status = $order_status;
        $order->coupon_code = $request->coupon_code;
        $order->payment_method = $request->partial_payment ? 'partial_payment' : $request->payment_method;
        $order->transaction_reference = null;
        $order->order_note = $request->order_note;
        $order->unavailable_item_note = $request->unavailable_item_note;
        $order->delivery_instruction = $request->delivery_instruction;
        $order->order_type = $request->order_type;
        $order->store_id = $request->store_id;
        if ($store->delivery_charges_on && $store->delivery_charges_on <= $request->order_amount) {
            $order->delivery_charge = 0;
        } else {
            $order->delivery_charge = round($delivery_charge, config('round_up_to_digit')) ?? 0;
        }
        $order->original_delivery_charge = round($original_delivery_charge, config('round_up_to_digit'));
        $order->delivery_address = json_encode($address);
        $order->schedule_at = $schedule_at;
        $order->scheduled = $request->schedule_at ? 1 : 0;
        $order->cutlery = $request->cutlery ? 1 : 0;
        $order->is_guest = $user ? 0 : 1;
        $order->otp = rand(1000, 9999);
        $order->zone_id = isset($zone) ? $zone->id : end(json_decode($this->zone_id, true));
        $order->module_id = 5;
        $order->parcel_category_id = $request->parcel_category_id;
        $order->receiver_details = json_decode($request->receiver_details);

        if ($order_status == 'confirmed') {
            $order->confirmed = now();
        }
        $order->dm_vehicle_id = $vehicle_id;
        $order->pending = now();
        $order->order_attachment = $request->has('order_attachment') ? Helpers::upload('order/', 'png', $request->file('order_attachment')) : null;
        $order->distance = $distance_data;
        $order->created_at = now();
        $order->updated_at = now();
        $order->charge_payer = null;

        //Added DM TIPS
        $dm_tips_manage_status = BusinessSetting::where('key', 'dm_tips_status')->first()->value;
        if ($dm_tips_manage_status == 1) {
            $order->dm_tips = $request->dm_tips ?? 0;
        } else {
            $order->dm_tips = 0;
        }
        //Added service charge
        $additional_charge_status = BusinessSetting::where('key', 'additional_charge_status')->first()->value;
        $additional_charge = BusinessSetting::where('key', 'additional_charge')->first()->value;
        if ($additional_charge_status == 1) {
            $order->additional_charge = $additional_charge ?? 0;
        } else {
            $order->additional_charge = 0;
        }

        $carts = Cart::where('user_id', $order->user_id)->where('is_guest', 0)->where('module_id', 5)
            ->when(isset($request->is_buy_now) && $request->is_buy_now == 1 && $request->cart_id, function ($query) use ($request) {
                return $query->where('id', $request->cart_id);
            })
            ->get()->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                return $data;
            });

        if (isset($request->is_buy_now) && $request->is_buy_now == 1) {
            $carts = json_decode($request->cart, true);
        }


        if ($request->order_type !== 'parcel') {
            foreach ($carts as $c) {
                if ($c['item_type'] === 'App\Models\ItemCampaign' || $c['item_type'] === 'AppModelsItemCampaign') {
                    $product = ItemCampaign::with('module')->active()->find($c['item_id']);
                    if ($product) {
                        if ($product->store_id != $order->store_id) {
                            return response()->json([
                                'errors' => [
                                    ['code' => 'different_stores', 'message' => translate('messages.Please_select_items_from_the_same_store')]
                                ]
                            ], 403);
                        }

                        if ($product->module->module_type == 'food' && $product->food_variations) {
                            $product_variations = json_decode($product->food_variations, true);
                            $variations = [];
                            if (count($product_variations)) {
                                $variation_data = Helpers::get_varient($product_variations, $c['variation']);
                                $price = $product['price'] + $variation_data['price'];
                                $variations = $variation_data['variations'];
                            } else {
                                $price = $product['price'];
                            }
                            // $product->tax = $store->tax;
                            $product = Helpers::product_data_formatting($product, false, false, app()->getLocale());
                            $addon_data = Helpers::calculate_addon_price(\App\Models\AddOn::whereIn('id', $c['add_on_ids'])->get(), $c['add_on_qtys']);
                            $or_d = [
                                'item_id' => null,
                                'item_campaign_id' => $c['item_id'],
                                'item_details' => json_encode($product),
                                'quantity' => $c['quantity'],
                                'price' => round($price, config('round_up_to_digit')),
                                'tax_amount' => Helpers::tax_calculate($product, $price),
                                'discount_on_item' => Helpers::product_discount_calculate($product, $price, $store)['discount_amount'],
                                'discount_type' => 'discount_on_product',
                                'variant' => json_encode($c['variant']),
                                'variation' => json_encode($variations),
                                'add_ons' => json_encode($addon_data['addons']),
                                'total_add_on_price' => $addon_data['total_add_on_price'],
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                            $order_details[] = $or_d;
                            $total_addon_price += $or_d['total_add_on_price'];
                            $product_price += $price * $or_d['quantity'];
                            $store_discount_amount += $or_d['discount_on_item'] * $or_d['quantity'];
                        } else {
                            if (count(json_decode($product['variations'], true)) > 0) {
                                $variant_data = Helpers::variation_price($product, json_encode($c['variation']));
                                $price = $variant_data['price'];
                                $stock = $variant_data['stock'];
                                $mychitti_fee = $variant_data['price'] - $variant_data['askingprice'];
                            } else {
                                $price = $product['price'];
                                $stock = $product->stock;
                                $mychitti_fee = $product->mychitty_fee;
                            }
                            if (config('module.' . $product->module->module_type)['stock']) {
                                if ($c['quantity'] > $stock) {
                                    return response()->json([
                                        'errors' => [
                                            ['code' => 'campaign', 'message' => translate('messages.product_out_of_stock', ['item' => $product->title])]
                                        ]
                                    ]);
                                }

                                $product_data[] = [
                                    'item' => clone $product,
                                    'quantity' => $c['quantity'],
                                    'variant' => count($c['variation']) > 0 ? $c['variation'][0]['type'] : null
                                ];
                            }

                            // $product->tax = $store->tax;
                            $product = Helpers::product_data_formatting($product, false, false, app()->getLocale());
                            $addon_data = Helpers::calculate_addon_price(\App\Models\AddOn::whereIn('id', $c['add_on_ids'])->get(), $c['add_on_qtys']);
                            $or_d = [
                                'item_id' => null,
                                'item_campaign_id' => $c['item_id'],
                                'item_details' => json_encode($product),
                                'quantity' => $c['quantity'],
                                'price' => $price,
                                'tax_amount' => Helpers::tax_calculate($product, $price),
                                'discount_on_item' => Helpers::product_discount_calculate($product, $price, $store)['discount_amount'],
                                'discount_type' => 'discount_on_product',
                                'variant' => json_encode($c['variant']),
                                'variation' => json_encode($c['variation']),
                                'add_ons' => json_encode($addon_data['addons']),
                                'total_add_on_price' => $addon_data['total_add_on_price'],
                                'platform_fee' => $mychitti_fee ??  0,
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                            $order_details[] = $or_d;
                            $total_addon_price += $or_d['total_add_on_price'];
                            $product_price += $price * $or_d['quantity'];
                            $store_discount_amount += $or_d['discount_on_item'] * $or_d['quantity'];
                        }
                    } else {
                        return response()->json([
                            'errors' => [
                                ['code' => 'campaign', 'message' => translate('messages.product_unavailable_warning')]
                            ]
                        ]);
                    }
                } else {
                    $product = Item::hydrate(
                        DB::table('items')->where('id',  $c['item_id'])->get()->toArray()
                    )->first();

                    if ($product) {

                        if ($product->store_id != $order->store_id) {
                            return response()->json([
                                'errors' => [
                                    ['code' => 'different_stores', 'message' => translate('messages.Please_select_items_from_the_same_store')]
                                ]
                            ]);
                        }


                        if ($product->maximum_cart_quantity && ($c['quantity'] > $product->maximum_cart_quantity)) {
                            return response()->json([
                                'errors' => [
                                    ['code' => 'quantity', 'message' => translate('messages.maximum_cart_quantity_limit_over')]
                                ]
                            ]);
                        }
                        if ($product->module->module_type == 'food' && $product->food_variations) {

                            $product_variations = json_decode($product->food_variations, true);
                            $variations = [];
                            if (count($product_variations)) {
                                $variation_data = Helpers::get_varient($product_variations, $c['variation']);
                                $mrpprice = $variation_data['mrpprice'];
                                $price = $variation_data['mrpprice'];
                                $variations =  $variation_data['variations'];
                                $mychitti_fee = $variation_data['price'] - $variation_data['askingprice'];
                            } else {
                                $mrpprice = $product['mrp_price'] ?? $product['price'];
                                $price = $product['price'];
                                $mychitti_fee = $product->mychitty_fee;
                            }
                            // $product->tax = $store->tax;
                            $product = Helpers::product_data_formatting($product, false, false, app()->getLocale());
                            $addon_data = Helpers::calculate_addon_price(\App\Models\AddOn::whereIn('id', $c['add_on_ids'])->get(), $c['add_on_qtys']);
                            $product_discount = Helpers::product_discount_calculate($product, $mrpprice, $store);

                            $tax =  _taxCalcluation($price, $product->tax)['gst_amount'];
                            $taxable = _taxCalcluation($price, $product->tax)['price_excluding_gst'];


                            $or_d = [
                                'item_id' => $c['item_id'],
                                'item_campaign_id' => null,
                                'item_details' => json_encode($product),
                                'quantity' => $c['quantity'],
                                'price' => $price,
                                'tax_amount' =>  $tax,
                                'taxable_amount' => $taxable,
                                'discount_on_item' => $product_discount['discount_amount'],
                                'discount_type' => $product_discount['discount_type'],
                                'variant' => json_encode($c['variant']),
                                'variation' => json_encode($variations),
                                'platform_fee' => $mychitti_fee ?? 0,
                                'add_ons' => json_encode($addon_data['addons']),
                                'total_add_on_price' => round($addon_data['total_add_on_price'], config('round_up_to_digit')),
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                            $total_addon_price += $or_d['total_add_on_price'];

                            $product_price += $price * $or_d['quantity'];
                            $store_discount_amount += $or_d['discount_type'] != 'flash_sale' ? $or_d['discount_on_item'] * $or_d['quantity'] : 0;
                            $flash_sale_admin_discount_amount += $or_d['discount_type'] == 'flash_sale' ? $product_discount['admin_discount_amount'] * $or_d['quantity'] : 0;
                            $flash_sale_vendor_discount_amount += $or_d['discount_type'] == 'flash_sale' ? $product_discount['vendor_discount_amount'] * $or_d['quantity'] : 0;
                            $order_details[] = $or_d;
                        } else {

                            if (count(json_decode($product['variations'], true)) > 0 && count($c['variation']) > 0) {
                                $variant_data = Helpers::variation_price($product, json_encode($c['variation']));
                                $price = $variant_data['mrpprice'];
                                $stock = $variant_data['stock'];
                                $is_vr = true;
                                $mychitti_fee = $variant_data['price'] - $variant_data['askingprice'];
                            } else {
                                $price = $product['price'];
                                $stock = $product->stock;
                                $is_vr = false;
                                $mychitti_fee = $product->mychitty_fee;
                            }

                            if (($stock - $c['quantity']) < 6) {
                                _stock_alert_sms($store->phone, $product['name']);
                                $title = 'Low Stock Alert for ' . $product['name'];
                                $msg = "Dear Vendor, Stock for " . $product['name'] . " is low. Please update accordingly.";
                                $to = $store->id;
                                $url = route('vendor.item.stock-limit-list');
                                $user_typ = 'vendor';
                                _inAppNotification($title, $msg, $acceptnce_id = '', $to, $url, $user_typ);
                            }


                            if (config('module.' . $product->module->module_type)['stock']) {
                                if ($c['quantity'] > $stock) {
                                    return response()->json([
                                        'errors' => [
                                            ['code' => 'campaign', 'message' => translate('messages.product_out_of_stock', ['item' => $product->name])]
                                        ]
                                    ]);
                                }

                                $product_data[] = [
                                    'item' => clone $product,
                                    'quantity' => $c['quantity'],
                                    'variant' => count($c['variation']) > 0 ? $c['variation'][0]['type'] : null
                                ];
                            }

                            // $product->tax = $store->tax;
                            $product = Helpers::product_data_formatting($product, false, false, app()->getLocale());
                            $addon_data = Helpers::calculate_addon_price(\App\Models\AddOn::whereIn('id', $c['add_on_ids'])->get(), $c['add_on_qtys']);

                            if (isset($c['variation']) && $c['variation']) {
                                $mrpprice = $c['variation'][0]['mrpprice'] ?? $c['variation'][0]['price'];
                                $price = $c['variation'][0]['price'];
                                $product_discount = Helpers::product_discount_calculate_vr($c['variation']);
                                $mychitti_fee = isset($c['variation'][0]['askingprice']) ? $price - $c['variation'][0]['askingprice'] : 0;
                            } else {
                                $mrpprice = $product->mrp_price;
                                $price = $product->price;
                                $mychitti_fee = $product->mychitty_fee;
                                $product_discount = Helpers::product_discount_calculate($product, $mrpprice, $store);
                            }
                            $tax_amount =  _taxCalcluation($price, $product->tax)['gst_amount'];
                            $taxable = _taxCalcluation($price, $product->tax)['price_excluding_gst'];

                            $or_d = [
                                'item_id' => $c['item_id'],
                                'item_campaign_id' => null,
                                'item_details' => json_encode($product),
                                'quantity' => $c['quantity'],
                                'price' => $price,
                                'taxable_amount' => $taxable,
                                'tax_amount' => $tax_amount,
                                'discount_on_item' => $product_discount['discount_amount'],
                                'discount_type' => $product_discount['discount_type'],
                                'variant' => json_encode($c['variant']),
                                'variation' => json_encode($c['variation']),
                                'platform_fee' => $mychitti_fee ?? 0,
                                'add_ons' => json_encode($addon_data['addons']),
                                'total_add_on_price' => $addon_data['total_add_on_price'],
                                'created_at' => now(),
                                'updated_at' => now()
                            ];

                            // prx($product_discount['discount_amount']);
                            $total_addon_price += $or_d['total_add_on_price'];
                            $total_order_tax += ($tax_amount * $or_d['quantity']);
                            if ($is_vr) {
                                $product_price += ($price * $or_d['quantity']);
                            } else {
                                $product_price += $price * $or_d['quantity'];
                            }

                            $store_discount_amount += $or_d['discount_type'] != 'flash_sale' ? $or_d['discount_on_item'] * $or_d['quantity'] : 0;
                            $flash_sale_admin_discount_amount += $or_d['discount_type'] == 'flash_sale' ? $product_discount['admin_discount_amount'] * $or_d['quantity'] : 0;
                            $flash_sale_vendor_discount_amount += $or_d['discount_type'] == 'flash_sale' ? $product_discount['vendor_discount_amount'] * $or_d['quantity'] : 0;
                            $order_details[] = $or_d;
                        }
                    } else {
                        return response()->json([
                            'errors' => [
                                ['code' => 'item', 'message' => translate('messages.product_unavailable_warning')]
                            ]
                        ]);
                    }
                }
            }
            $order->discount_on_product_by = 'vendor';
            $store_discount = Helpers::get_store_discount($store);
            if (isset($store_discount)) {
                $order->discount_on_product_by = 'admin';
                if ($product_price + $total_addon_price < $store_discount['min_purchase']) {
                    $store_discount_amount = 0;
                }
                if ($store_discount['max_discount'] != 0 && $store_discount_amount > $store_discount['max_discount']) {
                    $store_discount_amount = $store_discount['max_discount'];
                }
            }
            $coupon_discount_amount = $coupon ? CouponLogic::get_discount($coupon, $product_price + $total_addon_price - $store_discount_amount - $flash_sale_admin_discount_amount - $flash_sale_vendor_discount_amount) : 0;
            $total_price = $product_price + $total_addon_price - $total_addon_price - $flash_sale_admin_discount_amount - $flash_sale_vendor_discount_amount  - $coupon_discount_amount;
            $tax = ($store->tax > 0) ? $store->tax : 0;
            $order->tax_status = 'included';

            $tax_included = BusinessSetting::where(['key' => 'tax_included'])->first() ?  BusinessSetting::where(['key' => 'tax_included'])->first()->value : 0;
            if ($tax_included ==  1) {
                $order->tax_status = 'included';
            }

            $total_tax_amount = Helpers::product_tax($total_price, $tax, $order->tax_status == 'included');
            $tax_a = $order->tax_status == 'included' ? 0 : $total_tax_amount;


            if ($store->minimum_order > $product_price + $total_addon_price) {
                return response()->json([
                    'errors' => [
                        ['code' => 'order_time', 'message' => translate('messages.you_need_to_order_at_least') . $store->minimum_order . ' ' . Helpers::currency_code()]
                    ]
                ]);
            }

            $free_delivery_over = BusinessSetting::where('key', 'free_delivery_over')->first()->value;
            if (isset($free_delivery_over)) {
                if ($free_delivery_over <= $product_price + $total_addon_price - $coupon_discount_amount - $store_discount_amount - $flash_sale_admin_discount_amount - $flash_sale_vendor_discount_amount) {
                    $order->delivery_charge = 0;
                    $free_delivery_by = 'admin';
                }
            }

            if ($store->free_delivery) {
                $order->delivery_charge = 0;
                $free_delivery_by = 'vendor';
            }

            if ($coupon) {
                if ($coupon->coupon_type == 'free_delivery') {
                    if ($coupon->min_purchase <= $product_price + $total_addon_price - $store_discount_amount - $flash_sale_admin_discount_amount - $flash_sale_vendor_discount_amount) {
                        $order->delivery_charge = 0;
                        $free_delivery_by = $coupon->created_by;
                    }
                }
                $coupon->increment('total_uses');
            }
            $order->coupon_created_by = $coupon_created_by;
            $order->coupon_discount_amount = round($coupon_discount_amount, config('round_up_to_digit'));
            $order->coupon_discount_title = $coupon ? $coupon->title : '';

            $order->store_discount_amount = round($store_discount_amount, config('round_up_to_digit'));
            $order->tax_percentage = $tax;
            $order->total_tax_amount = $total_order_tax ?? round($total_tax_amount, config('round_up_to_digit'));
            $order->rounded_off = ($total_price + $tax_a + $order->delivery_charge) - floor($total_price + $tax_a + $order->delivery_charge);
            $order->order_amount = round($total_price + $tax_a + $order->delivery_charge);

            $order->free_delivery_by = $free_delivery_by;
        } else {

            $point = new Point(json_decode($request->receiver_details, true)['latitude'], json_decode($request->receiver_details, true)['longitude']);
            $zone_id =  json_decode($request->receiver_details, true)['zone_id'];
            $zone = Zone::where('id', $zone_id)->whereContains('coordinates', new Point(json_decode($request->receiver_details, true)['latitude'], json_decode($request->receiver_details, true)['longitude'], POINT_SRID))->first();
            if (!$zone) {
                $errors = [];
                array_push($errors, ['code' => 'receiver_details', 'message' => translate('messages.out_of_coverage')]);
                return response()->json([
                    'errors' => $errors
                ]);
            }
            $order->delivery_charge = round($original_delivery_charge, config('round_up_to_digit')) ?? 0;
            $order->original_delivery_charge = round($original_delivery_charge, config('round_up_to_digit'));


            $order->order_amount = round($order->delivery_charge);
        }
        $order->flash_admin_discount_amount = round($flash_sale_admin_discount_amount, config('round_up_to_digit'));
        $order->flash_store_discount_amount = round($flash_sale_vendor_discount_amount, config('round_up_to_digit'));
        //DM TIPs

        $order->order_amount = $order->order_amount + $order->dm_tips + $order->additional_charge;

        $order->rounded_off = ($order->order_amount) - floor($order->order_amount);

        if ($request->payment_method == 'wallet' && $user->wallet_balance < $order->order_amount) {
            return response()->json([
                'errors' => [
                    ['code' => 'order_amount', 'message' => translate('messages.insufficient_balance')]
                ]
            ]);
        }
        if ($request->partial_payment && $user->wallet_balance > $order->order_amount) {
            return response()->json([
                'errors' => [
                    ['code' => 'partial_payment', 'message' => translate('messages.order_amount_must_be_greater_than_wallet_amount')]
                ]
            ]);
        }
        if (isset($module_wise_delivery_charge) && $request->payment_method == 'cash_on_delivery' && $module_wise_delivery_charge->pivot->maximum_cod_order_amount && $order->order_amount > $module_wise_delivery_charge->pivot->maximum_cod_order_amount) {
            return response()->json([
                'errors' => [
                    ['code' => 'order_amount', 'message' => translate('messages.amount_crossed_maximum_cod_order_amount')]
                ]
            ]);
        }

        try {
            DB::beginTransaction();

            $order->save();
            if ($request->order_type !== 'parcel') {
                foreach ($order_details as $key => $item) {
                    $order_details[$key]['order_id'] = $order->id;

                    if ($store_discount_amount <= 0) {
                        $order_details[$key]['discount_on_item'] = 0;
                    }
                }
                OrderDetail::insert($order_details);
                if (count($product_data) > 0) {
                    foreach ($product_data as $item) {
                        ProductLogic::update_stock($item['item'], $item['quantity'], $item['variant'])->save();
                        ProductLogic::update_flash_stock($item['item'], $item['quantity'])?->save();
                    }
                }
                $store->increment('total_order');
            }
            if (!isset($request->is_buy_now) || (isset($request->is_buy_now) && $request->is_buy_now == 0)) {
                foreach ($carts as $cart) {
                    // $cart->delete();
                }
            }

            if ($user) {
                $customer = $user;
                $customer->zone_id = $order->zone_id;
                $customer->save();

                if ($request->payment_method == 'wallet') CustomerLogic::create_wallet_transaction($order->user_id, $order->order_amount, 'order_place', $order->id);

                if ($request->partial_payment) {
                    if ($user->wallet_balance <= 0) {
                        return response()->json([
                            'errors' => [
                                ['code' => 'order_amount', 'message' => translate('messages.insufficient_balance_for_partial_amount')]
                            ]
                        ], 203);
                    }
                    $p_amount = min($user->wallet_balance, $order->order_amount);
                    $unpaid_amount = $order->order_amount - $p_amount;
                    $order->partially_paid_amount = $p_amount;

                    $order->save();
                    CustomerLogic::create_wallet_transaction($order->user_id, $p_amount, 'partial_payment', $order->id);
                    OrderLogic::create_order_payment(order_id: $order->id, amount: $p_amount, payment_status: 'paid', payment_method: 'wallet');
                    OrderLogic::create_order_payment(order_id: $order->id, amount: $unpaid_amount, payment_status: 'unpaid', payment_method: $request->payment_method);
                }
            }

            DB::commit();


            $payments = $order->payments()->where('payment_method', 'cash_on_delivery')->exists();
            $order_mail_status = Helpers::get_mail_status('place_order_mail_status_user');
            $order_verification_mail_status = Helpers::get_mail_status('order_verification_mail_status_user');

            //PlaceOrderMail
            try {
                if (0 && !in_array($order->payment_method, ['digital_payment', 'partial_payment', 'offline_payment'])  || $payments) {
                    Helpers::send_order_notification($order);

                    if ($order->order_status == 'pending' && config('mail.status') && $order_mail_status == '1' && $user) {

                        Mail::to($user->email)->send(new PlaceOrder($order->id));
                    }
                    if ($order->order_status == 'pending' && config('order_delivery_verification') == 1 && $order_verification_mail_status == '1' && $user) {
                        Mail::to($user->email)->send(new OrderVerificationMail($order->otp, $user->f_name));
                    }
                    if ($order->is_guest == 1 && $order->order_status == 'pending' && config('mail.status') && $order_mail_status == '1' && isset($request->contact_person_email)) {
                        Mail::to($request->contact_person_email)->send(new PlaceOrder($order->id));
                    }
                    if ($order->is_guest == 1 && $order->order_status == 'pending' && config('order_delivery_verification') == 1 && $order_verification_mail_status == '1' && isset($request->contact_person_email)) {
                        Mail::to($request->contact_person_email)->send(new OrderVerificationMail($order->otp, $request->contact_person_name));
                    }
                }
            } catch (\Exception $ex) {
                // info($ex->getMessage());
            }

            // place order sms, email and in app notification to vendor  =================== 
            $title = "Received New Order";
            $msg = "Good news! You've got a new order. Let's get it ready!";
            $acceptnce_id = '';
            $to = $store->id;
            $url = route('vendor.order.list', ['pending']);
            $user_typ = 'vendor';

            _inAppNotification($title, $msg, $acceptnce_id, $to, $url, $user_typ);
            // _sendMailToVendor($title, $msg, $to, $url); 
            $smsTemplate = "Hello! Your order NO " . $order->id . " is received. Please review the details on My Chitti Vendor Dashboard and confirm accuracy.";
            _sendSMS($store->phone, $smsTemplate);
            _sendOrderSMSToAdmins($order, $user, $store);

            // SendOrderNotifications::dispatch( 
            //     $title, 
            //     $msg,
            //     $acceptnce_id, 
            //     $to,
            //     $url,
            //     $user_typ,
            //     $order->id,
            //     $store->phone
            // );


            // place order sms, email and in app notification to vendor ===================
            return response()->json([
                'message' => translate('messages.order_placed_successfully'),
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'total_ammount' => $order->order_amount,
                'status' => $order->order_status,
                'created_at' => $order->created_at,
                'url' => $request->payment_method == 'digital_payment'  ? "payment-mobile?customer_id=" . $user_id . "&order_id=" . $order->id . "&payment_platform=web&payment_method=razor_pay&callback=/success" : ''
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([$e], 403);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order_time', 'message' => translate('messages.failed_to_place_order')]
            ]
        ]);
    }

    public function change_variation(Request $request)
    {
        $vrType = $request->type;
        $prId = $request->id;

        $item = DB::table('items')->where('id', $prId)->first();
        $variations = json_decode($item->variations);
        $selectedVr = [];

        foreach ($variations as $key => $value) {
            if ($value->type == $vrType) {
                $selectedVr = $value;
                $selectedVr->discounted_price =  $selectedVr->price;
                $vrDetails = ItemVariationDetail::find($selectedVr->variations_table_id);
                $selectedVr->variation_details = $vrDetails;
            }
        }
        return response()->json(['status' => true, 'data' => $selectedVr]);
    }
    public function order_success(Request $request, $order_id = null)
    {
        $order_id = $order_id;
        return view('front-views.order-success', compact('order_id'));
    }
    public function store_gallery(Request $request)
    {
        $store = Store::with('galleries')->where('slug', $request->slug)->first();
        // prx($store->galleries);
        return view('front-views.store_gallery', compact('store'));
    }
    public function trackBannerClick(Request $request)
    {
        $bannerId = $request->banner_id;
        $banner = DB::table('banners')->where('id', $bannerId)->first();
        if (!$banner) {
            return response()->json(['message' => 'Banner not found'], 404);
        }

        $ip = $request->ip();
        $isUnique = !DB::table('analytics_logs')
            ->where('screen_type', 'banner')
            ->where('ref_id', $bannerId)
            ->where('ip', $ip)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        DB::table('banners')->where('id', $bannerId)->increment('total_clicks');
        if ($isUnique) {
            DB::table('banners')->where('id', $bannerId)->increment('unique_clicks');
        }

        DB::table('analytics_logs')->insert([
            'screen_type' => 'banner',
            'sub_type' => 'web',
            'ref_id' => $bannerId,
            'user_id' => auth()->check() ? auth()->id() : null,
            'ip' => $ip,
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Banner click counted']);
    }

    public function store_details(Request $request, $city, $slug)
    {
        $check_module = DB::table('stores')->where('slug', $slug)->first();
        if ($check_module) {
            $module = $check_module->module_id;
        } else {
            return redirect()->route('home');
        }
        $invItemdata = [];
        $longitude = $this->longitude;
        $latitude = $this->latitude;
        $store = Store::with(['discount' => function ($q) {
            return $q->validate();
        }, 'campaigns', 'schedules', 'activeCoupons'])
            ->withCount(['items', 'campaigns'])
            ->when($module, function ($query) use ($module) {
                $query->module($module);
            })
            ->where('slug', $slug)
            ->first();


        if ($store) {
            // Increment store visit analytics
            $ip = $request->ip();
            $isUnique = !DB::table('analytics_logs')
                ->where('screen_type', 'store')
                ->where('ref_id', $store->id)
                ->where('ip', $ip)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            DB::table('stores')->where('id', $store->id)->increment('total_visits');
            if ($isUnique) {
                DB::table('stores')->where('id', $store->id)->increment('unique_visits');
            }

            DB::table('analytics_logs')->insert([
                'screen_type' => 'store',
                'sub_type' => 'web',
                'ref_id' => $store->id,
                'user_id' => auth()->check() ? auth()->id() : null,
                'ip' => $ip,
                'created_at' => now(),
            ]);

            if ($module == 5) {

                $keywordsData = DB::table('items')
                    ->where('items.is_approved', 1)
                    ->join('categories', 'items.category_id', '=', 'categories.id')
                    ->where('items.store_id', $store->id)
                    ->select('items.name as item_name', 'categories.name as category_name')
                    ->distinct()
                    ->get();

                $keywords = implode(',', $keywordsData->pluck('item_name')->merge($keywordsData->pluck('category_name'))->toArray());

                $category_ids = DB::table('items')
                    ->join('categories', 'items.category_id', '=', 'categories.id')
                    ->selectRaw('categories.position as positions, IF((categories.position = "0"), categories.id, categories.parent_id) as categories')
                    ->where('items.store_id', $store->id)
                    ->where('categories.status', 1)
                    ->groupBy('categories', 'positions')
                    ->get();

                $store = Helpers::store_data_formatting($store);
                $store['category_ids'] = array_map('intval', $category_ids->pluck('categories')->toArray());
                $store['category_details'] = Category::whereIn('id', $store['category_ids'])->get();
                $store['price_range']  = Item::withoutGlobalScopes()->where('store_id', $store->id)
                    ->select(DB::raw('MIN(price) AS min_price, MAX(price) AS max_price'))
                    ->get(['min_price', 'max_price']);

                //products 

                $productdata = DB::table('items')
                    ->join('categories', 'items.category_id', 'categories.id')
                    ->where('items.store_id', $store->id)->where('items.status', 1)->where('items.is_approved', 1)->select('categories.id', 'categories.name', 'categories.slug as cat_slug')->distinct()->get()->toArray();

                foreach ($productdata as $key => $cat) {
                    $cat->items = DB::table('items')
                        ->where('store_id', $store->id)->where('category_id', $cat->id)->where('status', 1)->where('is_approved', 1)->get();
                }
            } else {
                $keywordsData = DB::table('items')
                    ->where('items.is_approved', 1)
                    ->join('categories', 'items.category_id', '=', 'categories.id')
                    ->whereRaw('FIND_IN_SET(?, items.store_ids)', [$store->id])
                    ->select('items.name as item_name', 'categories.name as category_name')
                    ->distinct()
                    ->get();

                $keywords = implode(',', $keywordsData->pluck('item_name')->merge($keywordsData->pluck('category_name'))->toArray());
                // echo '<pre>';
                // SERVICES
                $productdata1 = DB::table('items')
                    ->join('categories', 'items.category_id', 'categories.id')
                    ->whereRaw('FIND_IN_SET(?, items.store_ids)', [$store->id])
                    ->whereNull('items.inventory_item_id')
                    ->where('categories.status', 1)
                    ->select('categories.id', 'categories.name', 'categories.slug as cat_slug')
                    ->distinct()
                    ->get();


                foreach ($productdata1 as $cat) {
                    $cat->items = DB::table('items')
                        ->whereRaw('FIND_IN_SET(?, store_ids)', [$store->id])
                        ->whereNull('items.inventory_item_id')
                        ->where('category_id', $cat->id)
                        ->where('status', 1)
                        ->get();
                }

                //INVENTORY ITEMS
                $invItemdata = DB::table('items')
                    ->join('categories', 'items.category_id', 'categories.id')
                    ->whereRaw('FIND_IN_SET(?, items.store_ids)', [$store->id])
                    ->whereNotNull('items.inventory_item_id')
                    ->select('categories.id', 'categories.name', 'categories.slug as cat_slug')
                    ->distinct()
                    ->get();

                foreach ($invItemdata as $cat) {
                    $cat->items = DB::table('items')
                        ->whereRaw('FIND_IN_SET(?, store_ids)', [$store->id])
                        ->whereNotNull('items.inventory_item_id')
                        ->where('category_id', $cat->id)
                        ->where('status', 1)
                        ->get();
                }
                $productdata = $productdata1;

                // print_r($productdata);
            }

            // die;
        } else {
            return redirect()->route('home');
        }
        $data['store_config'] = StoreConfig::where('store_id', $store->id)->first();
        $data['galleries'] = StoreGallery::where('store_id', $store->id)->get();
        $data['banners'] = DB::table('banners')->where('type', 'store_wise')->where('data',  $store->id)->where('status', 1)->whereIn('platform', ['web', 'all'])->orderBy('sort_order')->get();
        $data['reviews'] = DB::table('store_reviews')->join('stores', 'stores.id', 'store_reviews.store_id')->join('users', 'users.id', 'store_reviews.user_id')->where('stores.slug', $slug)->select('users.f_name', 'users.l_name', 'users.image as profile_image', 'stores.*', 'store_reviews.comment', 'store_reviews.attachment', 'store_reviews.created_at', 'store_reviews.rating', 'store_reviews.reply', 'store_reviews.replied_at')->where('store_reviews.status', 1)->take(3)->get();
        $data['review_count'] = DB::table('store_reviews')
            ->join('stores', 'stores.id', 'store_reviews.store_id')
            ->where('stores.slug', $slug)
            ->where('store_reviews.status', 1) 
            ->count();
            // && in_array($request->getHost(), ['vendor.mcvendorhub.com', 'vendor-staff.mcvendorhub.com'])
        if ($request->has('template') && $request->template ) {
            return view('front-views.store_webpage.template-' . $request->template . '', compact('store', 'productdata', 'invItemdata', 'keywords', 'data', 'module'));
        }
        // prx($store);
        $templateId = $data['store_config']?->template_id ?? 1;
       
        return view('front-views.store_webpage.template-' . $templateId, compact('store', 'productdata', 'invItemdata', 'keywords', 'data', 'module'));
    }

    public function store_removal_request(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'reason' => 'required|string|max:1000',
        ]);

        DB::table('store_removal_requests')->insert([
            'store_id' => $request->store_id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'reason' => $request->reason,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        return back()->with('success', 'Your removal request has been submitted. Our team will review it shortly.');
    }

    public function product_details(Request $request, $category_slug, $slug)
    {
        $item = DB::table('items')->where('slug', $slug)->first();
        $is_inventory_product = false;
        if ($item && $item->inventory_item_id) {
            $inventory = InventoryItem::where('id', $item->inventory_item_id)->first();
            if ($inventory && $inventory->item_type == 'product') {
                $is_inventory_product = true;
            }
        }
        if ($item) {
            $module = $item->module_id;
        }
        $zone_id = $this->zone_id;
        if (!$item) {
            return back();
        }
        // prx($item->id);
        $data['banners'] =  BannerLogic::get_all_module_banners($zone_id, 0, $type = 'item_wise',  $item->id, 'web');
        // prx($data['banners']);
        $stores = [];
        // if (Config::get('module.current_module_id') == 5) {
        if ($module == 5) {
            $item = DB::table('items')
                ->where('items.is_approved', 1)
                ->join('categories', 'items.category_id', 'categories.id')
                ->join('stores', 'stores.id', 'items.store_id')
                ->where('items.slug', $slug)
                ->where('items.module_id', $module)
                ->select('items.*',  'categories.name as category_name', 'categories.slug as category_slug', 'stores.name as store_name', 'stores.slug as store_slug', 'stores.active as store_open', 'stores.suspended')
                ->first();
        } else {
            $item = DB::table('items')
                ->join('categories', 'items.category_id', 'categories.id')
                ->where('items.slug', $slug)
                ->where('items.is_approved', 1)
                ->where('items.module_id', $module)
                ->select('items.*', 'categories.name as category_name', 'categories.slug as category_slug')
                ->first();
            $zone_id = $this->zone_id;
            $store_ids = $item->store_ids;

            $zoneIds = json_decode($this->zone_id, true);
            $storeIds = explode(',', $store_ids);

            $zoneIds = json_decode($this->zone_id, true);
            $storeIds = explode(',', $store_ids);

            // subscribed store IDs
            $subscribedStoreIds = DB::table('vendor_subscriptions')
                ->where('plan_expiry', '>', now())
                ->pluck('vendor_id')
                ->map(fn($id) => (int) $id)
                ->toArray();
            // prx(implode(',',$subscribedStoreIds));

            $subscribedStores = DB::table('stores')
                ->whereIn('zone_id', $zoneIds)
                ->whereIn('id', $storeIds)
                ->whereIn('id', $subscribedStoreIds)
                ->where('active', 1)
                ->inRandomOrder()
                ->get()
                ->map(function ($store) {
                    $store->subscribed = true;
                    return $store;
                });
            $nonSubscribedStores = DB::table('stores')
                ->whereIn('zone_id', $zoneIds)
                ->whereIn('id', $storeIds)
                ->whereNotIn('id', $subscribedStoreIds)
                ->where('active', 1)
                ->orderByDesc('id')
                ->get()
                ->map(function ($store) {
                    $store->subscribed = false;
                    return $store;
                });

            $stores = $subscribedStores->merge($nonSubscribedStores)->values();
            // prx($stores);
        }

        $keywords = implode(',', ServiceKeyword::where('service_id', $item->id)->whereNotNull('keyword')->pluck('keyword')->toArray());

        $item_area_keywords = Helpers::getAreasByZoneIds(
            json_decode($this->zone_id, true),
            21
        );
        $item_area_keywords_arr = $item_area_keywords;
        $item_area_keywords = $item_area_keywords->implode(', ');

        // prx($item_area_keywords_arr);

        if (!$item) {
            return redirect()->route('home');
        }
        if ($module == 6) {
            $data['featured_products'] = DB::table('items')
                ->where('items.is_approved', 1)
                ->where('items.module_id', $module)
                ->join('categories', 'items.category_id', 'categories.id')
                ->join('stores', function ($join) {
                    $join->on(DB::raw('FIND_IN_SET(stores.id, items.store_ids)'), '>', DB::raw('0'));
                })
                ->whereIn('stores.zone_id',  json_decode($this->zone_id, true))
                ->where(['categories.featured' => 1, 'categories.status' => 1,  'items.status' => 1])
                ->select('items.*', 'categories.slug as cat_slug')
                ->limit(20)

                ->distinct()
                ->get();

            $data['related_products'] = DB::table('items')
                ->where('items.is_approved', 1)
                ->join('categories', 'items.category_id', 'categories.id')
                ->join('stores', function ($join) {
                    $join->on(DB::raw('FIND_IN_SET(stores.id, items.store_ids)'), '>', DB::raw('0'));
                })
                ->whereIn('stores.zone_id',  json_decode($this->zone_id, true))
                ->where(['categories.status' => 1, 'items.status' => 1, 'items.category_id' => $item->category_id])
                ->whereNot('items.slug', $slug)
                ->where('items.module_id', $module)
                ->select('items.*', 'stores.delivery_time', 'categories.slug as cat_slug')
                ->limit(12)
                ->groupBy('items.id')
                ->get();

            $data['reviews'] = DB::table('store_reviews')->join('service_requests', 'service_requests.id', 'store_reviews.order_id')->join('stores', 'stores.id', 'store_reviews.store_id')->join('users', 'users.id', 'store_reviews.user_id')->select('users.f_name', 'users.l_name', 'users.image as profile_image', 'store_reviews.comment', 'store_reviews.attachment', 'store_reviews.created_at', 'stores.logo as store_logo', 'stores.name as store_name', 'store_reviews.rating', 'store_reviews.reply', 'store_reviews.replied_at')->where('store_reviews.status', 1)->take(3)->get();
        } else {
            $data['featured_products'] = DB::table('items')
                ->where('items.is_approved', 1)
                ->where('items.module_id', $module)
                ->join('categories', 'items.category_id', 'categories.id')
                ->join('stores', 'stores.id', 'items.store_id')
                ->whereIn('stores.zone_id',  json_decode($this->zone_id, true))
                ->select('items.*', 'categories.slug as cat_slug')
                ->limit(20)
                ->groupBy('items.id')
                ->orderBy('items.id', 'desc')
                ->get();

            $data['related_products'] = DB::table('items')
                ->where('items.is_approved', 1)
                ->join('categories', 'items.category_id', 'categories.id')
                ->join('stores', 'stores.id', 'items.store_id')
                ->whereIn('stores.zone_id',  json_decode($this->zone_id, true))
                ->where(['categories.status' => 1, 'items.status' => 1, 'items.category_id' => $item->category_id])
                ->whereNot('items.slug', $slug)
                ->where('items.module_id', $module)
                ->select('items.*', 'stores.delivery_time', 'categories.slug as cat_slug')
                ->orderBy('items.id', 'desc')
                ->distinct()
                ->limit(12)
                ->get();
            $data['reviews'] =  DB::table('reviews')->join('items', 'items.id', 'reviews.item_id')->join('users', 'users.id', 'reviews.user_id')->where('items.slug', $slug)->select('users.f_name', 'users.l_name', 'users.image as profile_image', 'items.*', 'reviews.comment', 'reviews.attachment', 'reviews.created_at', 'reviews.rating')->where('reviews.status', 1)->get();
        }
        $data['seoContent'] = SeoContent::where('seo_type', 'item')
            ->where('data', $item->id)
            ->inRandomOrder()
            ->first();

        $data['faqContent'] = SeoContent::where('seo_type', 'faq')
            ->where('data', $item->id)
            ->inRandomOrder()
            ->first();

        // 1️⃣ Get top 12 stores by review count, then pick 8 random
        $topStores = Store::leftJoin('store_reviews', 'stores.id', '=', 'store_reviews.store_id')
            ->whereIn('stores.zone_id', json_decode($this->zone_id, true))
            ->select(
                'stores.id',
                'stores.name',
                'stores.slug',
                'stores.address',
                'stores.average_rating',
                'stores.rating_count',
                // DB::raw('COUNT(store_reviews.id) as review_count')  
            )
            ->groupBy('stores.id', 'stores.name', 'stores.slug', 'stores.address', 'stores.average_rating', 'stores.rating_count')
            ->where('stores.module_id', 6)
            ->where('stores.status', 1)
            ->orderByDesc('stores.rating_count')
            ->take(12)
            ->get()
            ->shuffle()
            ->take(8); // final 8 stores

        // 2️⃣ Fetch up to 4 items per store separately
        $storeIds = $topStores->pluck('id')->toArray();

        // Get items for all top stores at once
        $items = DB::table('items')
            ->select('items.id', 'items.name', 'items.slug', 'stores.id as store_id')
            ->join('stores', function ($join) {
                $join->whereRaw("FIND_IN_SET(items.id, stores.services_1)")
                    ->orWhereRaw("FIND_IN_SET(items.id, stores.services_2)");
            })
            ->whereIn('stores.id', $storeIds)
            ->orderBy('items.id') // pick top items
            ->get()
            ->groupBy('store_id'); // group by store

        // Attach top 4 items to each store
        $topStores->each(function ($store) use ($items) {
            $storeItems = $items->get($store->id, collect())->take(4); // max 4 items
            $store->item_names_array = $storeItems->pluck('name')->toArray();
            $store->item_slugs_array = $storeItems->pluck('slug')->toArray();
        });

        $data['top_stores'] = $topStores;

        $itemFaqs = ItemFaq::where('item_id', $item->id)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get();
        // prx(count($data['top_stores']));

        return view('front-views.product_details', compact('itemFaqs', 'item_area_keywords_arr', 'item_area_keywords', 'is_inventory_product', 'item', 'data', 'stores', 'keywords', 'module'));
    }

    public function category_listing(Request $request, $slug)
    {
        $zone_id = $this->zone_id;

        $cat = DB::table('categories')->where('slug', $slug)->first();
        if ($cat) {
            $module = $cat->module_id;
        }
        $data['banners'] =  BannerLogic::get_all_module_banners($zone_id, 0, $type = 'category_wise',  $cat->id, 'web');
        $catDetails = Category::where('slug', $slug)
            ->where('module_id', $module)
            ->first();
        if (!$catDetails) {
            return redirect()->route('home');
        }
        $catArr = [];

        if ($catDetails) {
            $catArr[] = $catDetails->id;
            $childIds = Category::where('parent_id', $catDetails->id)
                ->pluck('id')
                ->toArray();

            $catArr = array_merge($catArr, $childIds);
        }

        // all categories 
        $data['all_categories'] = DB::table('categories')->where('status', 1)->whereNull('added_by')->where('module_id', $module)->where('position', 0)->orderBy('priority', 'desc')->get()->toArray();

        if ($module == 6) {

            $catProducts  = DB::table('items')
                ->where('items.is_approved', 1)
                ->join('stores', function ($join) {
                    $join->on(DB::raw('FIND_IN_SET(stores.id, items.store_ids)'), '>', DB::raw('0'));
                })
                ->join('categories', 'categories.id', 'items.category_id')
                ->where('stores.status', 1)
                ->whereIn('stores.zone_id',  json_decode($this->zone_id, true))
                ->where('items.category_id', $catDetails->id)->select('items.*',  'categories.slug as cat_slug')->distinct()->where('items.status', 1)->get();
        } else {


            $catProducts  = DB::table('items')
                ->where('items.is_approved', 1)
                ->join('stores', 'stores.id', 'items.store_id')
                ->join('categories', 'categories.id', 'items.category_id')
                ->whereIn('stores.zone_id',  json_decode($this->zone_id, true))
                ->where('stores.status', 1)
                ->whereIn('items.category_id', $catArr)->select('items.*', 'categories.slug as cat_slug', 'stores.active as store_open', 'stores.delivery_time')->distinct()->where('items.status', 1)->get();
        }

        return view('front-views.category_listing', compact('catDetails', 'catProducts', 'data', 'module'));
    }
    public function mc_vendor_hub_tnc(Request $request)
    {
        $terms_and_conditions =  DataSetting::where('key', 'vendorhub_terms_and_conditions')->first();
        return view('front-views.vendorhub_terms_and_conditions', compact('terms_and_conditions'));
    }
    public function mc_vendor_hub_pp(Request $request)
    {
        $privacy_policy =  DataSetting::where('key', 'privacy_policy_for_mc_vendor')->first();
        return view('front-views.vendorhub_privacy_policy', compact('privacy_policy'));
    }
    public function store_terms_and_conditions(Request $request, $type)
    {

        if ($type == 'shop') {
            $terms_and_conditions =  BusinessSetting::where('key', 'shop_vendor_tnc')->first();
        } else {
            $terms_and_conditions =  BusinessSetting::where('key', 'service_vendor_tnc')->first();
        }
        return view('front-views.store-terms-and-conditions', compact('terms_and_conditions'));
    }

    public function app_config()
    {
        $app_config = DB::table('app_configs')->first();
        $columns = DB::getSchemaBuilder()->getColumnListing('app_configs');
        $columns = array_diff($columns, ['id', 'created_at', 'updated_at']);
        return view('front-views.app-config', compact('app_config', 'columns'));
    }

    public function app_config_update(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        if (empty($data)) {
            return back()->with('error', 'No data provided to update');
        }

        $appConfig = DB::table('app_configs')->first();
        $data['updated_at'] = Carbon::now();

        if (!$appConfig) {
            $data['created_at'] = Carbon::now();
            DB::table('app_configs')->insert($data);
        } else {
            DB::table('app_configs')->where('id', $appConfig->id)->update($data);
        }

        return back()->with('success', 'App config updated successfully');
    }

    public function test_push_view()
    {
        return view('test-push');
    }

    public function test_push_send(Request $request)
    {
        $request->validate(['fcm_token' => 'required']);

        $user_fcm = $request->fcm_token;
        $data = [
            'title' => translate('messages.test_push_notification'),
            'description' => "Test Notification.",
            'order_id' => 3,
            'image' => '',
            'type' => 'order_status',
        ];
        Helpers::send_push_notif_to_device($user_fcm, $data, null, true);

        return back()->with('success', 'Push notification sent!');
    }
}
