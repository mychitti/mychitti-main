<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;

use Illuminate\Support\Facades\DB;

use App\Models\StoreSchedule;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\AccountDetail;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Holiday;
use App\Models\HolidayOverride;
use App\Models\InventoryItem;
use App\Models\OfferBanner;
use App\Models\OrderType;
use App\Models\Plan;
use App\Models\StoreConfig;
use App\Models\StoreGallery;
use App\Models\StoreSignature;
use App\Models\StoreTnc;
use App\Models\TempStoreStatus;
use App\Models\Translation;
use App\Models\VendorEmployee;
use App\Models\VendorRequirement;
use App\Models\BusinessSetting;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\TemplatePurchase;
use App\Models\Vendor;
use App\Traits\Payment;
use App\Library\Payer;
use App\Library\Payment as PaymentInfo;
use App\Library\Receiver;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function invoice_settings(Request $request)
    {
        $tncs = StoreTnc::where('store_id', Helpers::get_store_id())->where('tnc_type', 'invoice')->get();
        $store_id = Helpers::get_store_id();
        $accounts = AccountDetail::where('user_type', 'vendor')->where('user_id',  $store_id)->where('type', 'invoice')->get();
        $staffs = VendorEmployee::where('store_id',  $store_id)->get();
        $signatures = StoreSignature::with('employee')->where('store_id', $store_id)->where('type', 'invoice')->get();
        $store = Store::where('id',  $store_id)->first();
        return view('vendor-views.settings.invoice_settings', compact('tncs', 'signatures', 'staffs', 'accounts',  'store'));
    }
    public function webpage_template_update(Request $request)
    {
        $storeId = Helpers::get_store_id();

        StoreConfig::updateOrInsert(['store_id' => $storeId], [
            'template_id' => $request->template_id,
        ]);
        Toastr::success('Updated Successfully');
        return back();
    }
    public function webpage_settings_update(Request $request)
    {
        $storeId = Helpers::get_store_id();

        StoreConfig::updateOrInsert(['store_id' => $storeId], [
            'webpage_name' => $request->website_name,
            'webpage_email' => $request->email,
            'webpage_address' => $request->address,
            'webpage_phones' => json_encode($request->phone),
            'webpage_latitude' => $request->latitude,
            'webpage_longitude' => $request->longitude,
            'inventory_items_position' => $request->inventory_items_position,
        ]);
        Toastr::success('Updated Successfully');

        return back();
    }
    public function webpage_settings(Request $request, $tab = 'basic-info')
    {
        $store_id = Helpers::get_store_id();
        $store = Store::where('id',  $store_id)->first();
        if ($tab == 'basic-info') {
            $storeConfig = StoreConfig::firstOrNew(['store_id' => Helpers::get_store_id()]);
            return view('vendor-views.settings.webpage', compact('store', 'tab', 'storeConfig'));
        } else if ($tab == 'gallery') {
            $galleries = StoreGallery::where('store_id', Helpers::get_store_id())->paginate(40);
            return view('vendor-views.settings.webpage', compact('store', 'tab', 'galleries'));
        } else if ($tab == 'banners') {
            $key = explode(' ', $request['search']);
            $banners = Banner::where('data', Helpers::get_store_id())->where('created_by', 'store')
                ->when($key, function ($query) use ($key) {
                    $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('title', 'like', "%" . $value . "%");
                        }
                    });
                })
                ->latest()->paginate(config('default_pagination'));
            return view('vendor-views.settings.webpage', compact('store', 'tab', 'banners'));
        } else if ($tab == 'privacy-policy') {
            $privacyPolicy = DB::table('store_privacy_policy')->where('store_id',  $store_id)->first();
            return view('vendor-views.settings.webpage', compact('store', 'tab', 'privacyPolicy'));
        } else if ($tab == 'tnc') {
            $tAndCContent = DB::table('vendor_terms_conditions')->where('type', 'for_customer')->where('vendor_id',  $store_id)->first();
            return view('vendor-views.settings.webpage', compact('store', 'tab', 'tAndCContent'));
        } else if ($tab == 'offer-banners') {
            $banners  = OfferBanner::where('store_id', Helpers::get_store_id())->paginate(10);
            return view('vendor-views.settings.webpage', compact('store', 'tab', 'banners'));
        } else if ($tab == 'about-us') {
            $about_us = StoreConfig::where('store_id', Helpers::get_store_id())
                ->value('about_us') ?? '';
            return view('vendor-views.settings.webpage', compact('store', 'tab', 'about_us'));
        } else if ($tab == 'my-services') {
            $search  = $request->search ?? '';
            $type  = $request->type ?? '';
            $filter  = $request->filter ?? '';

            $inventory_items = InventoryItem::with('entries')->where('store_id', Helpers::get_store_id())
                ->where("show_on_store_page", 1)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('item_name', 'like', "%{$search}%")
                            ->orWhere('sku_id', 'like', "%{$search}%");
                    });
                })
                ->when($type, function ($query) use ($type) {
                    $query->where(function ($q) use ($type) {
                        $q->where('item_type', $type);
                    });
                })
                ->when($filter, function ($query) {
                    $query->where('stock', '<', 5);
                })
                ->orderBy('id', 'desc')
                ->paginate(50);
            return view('vendor-views.settings.webpage', compact('store', 'tab', 'inventory_items'));
        } else if ($tab == 'mychitti-services') {
            $mychitti_services = '';
            $store_data = Helpers::get_store_data();

            $categoryIds = [
                $store_data->category_1,
                $store_data->category_2
            ];
            $children = Category::whereIn('parent_id', $categoryIds)->select('id', 'parent_id')->get()->groupBy('parent_id');
            $allcategories_1 = array_merge(
                [$store_data->category_1],
                ($children->get($store_data->category_1, collect()))->pluck('id')->toArray()
            );

            $allcategories_2 = array_merge(
                [$store_data->category_2],
                ($children->get($store_data->category_2, collect()))->pluck('id')->toArray()
            );

            $items_1 = DB::table('items')->whereIn('category_id', $allcategories_1)->get();
            $items_2 = DB::table('items')->whereIn('category_id', $allcategories_2)->get();
            $module_categories = Category::where('module_id', $store_data->module_id)->where('position', 0)->where('status', 1)->get();

            return view('vendor-views.settings.webpage', compact('store', 'tab', 'store_data', 'items_1', 'items_2', 'module_categories'));
        } else if ($tab == 'webpage-templates') {
            $store_template_id = StoreConfig::where('store_id', $store_id)->value('template_id') ?: 1;
            $templates = DB::table("store_webpage_templates")->where('status', 1)->get();
            $purchases = TemplatePurchase::where('vendor_id', $store_id)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->get()
                ->keyBy('template_id');
            $purchasedTemplateIds = $purchases->keys()->toArray();
            return view('vendor-views.settings.webpage', compact('store', 'tab', 'store_template_id', 'templates', 'purchasedTemplateIds', 'purchases'));
        } else if ($tab == 'domain-setup') {
             $domain = Helpers::get_store_data()->domain;
            return view('vendor-views.settings.webpage', compact('store', 'tab', 'domain'));
        } else if ($tab == 'third-party') {
            return view('vendor-views.settings.webpage', compact('store', 'tab'));
        }
    }
    public function common_setting_save(Request $request)
    {
        //  prx($request->all());
        $data = $request->except(['_token', '_method']);
        if (!empty($data)) {
            $config = StoreConfig::firstOrNew(['store_id' => Helpers::get_store_id()]);
            $config->fill($data); // updates only fields present in request
            $config->save();

            Toastr::success('Updated Successfully');
        } else {
            Toastr::info('No fields to update');
        }
        return back();
    }
    public function receivable_receipts(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $store = StoreConfig::where('store_id', $store_id)->first();

        return view('vendor-views.settings.receivable_receipts_settings', compact('store'));
    }
    public function pos(Request $request)
    {

        $store_id = Helpers::get_store_id();
        $store = StoreConfig::where('store_id', $store_id)->first();
        $order_type = OrderType::where('store_id', $store_id)->get();

        return view('vendor-views.settings.pos', compact('store', 'order_type'));
    }

    public function quotation_settings(Request $request)
    {
        $tncs = StoreTnc::where('store_id', Helpers::get_store_id())->where('tnc_type', 'quotation')->get();
        $store_id = Helpers::get_store_id();
        $accounts = AccountDetail::where('user_type', 'vendor')->where('user_id',  $store_id)->where('type', 'quotation')->get();
        $staffs = VendorEmployee::where('store_id',  $store_id)->get();
        $signatures = StoreSignature::with('employee')->where('store_id', $store_id)->where('type', 'quotation')->get();
        $store = Store::where('id',  $store_id)->first();
        return view('vendor-views.settings.quotation_settings', compact('tncs', 'signatures', 'staffs', 'accounts',  'store'));
    }
    public function store_settings(Request $request) {}
    public function holiday_settings(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $holidays = _getStoreHolidays($storeId);
        return view('vendor-views.settings.holidays', compact('holidays'));
    }
    public function holiday_delete(Request $request, $holidayId)
    {
        $storeId = Helpers::get_store_id();

        $holiday = Holiday::findOrFail($holidayId);

        if ($holiday->vendor_id == $storeId && $holiday->is_global == 0) {
            $holiday->delete();
            Toastr::success('Your holiday has been deleted.');
        } elseif ($holiday->is_global == 1) {
            HolidayOverride::updateOrCreate(
                [
                    'holiday_id' => $holiday->id,
                    'vendor_id' => $storeId
                ],
                [
                    'is_deleted' => 1,
                    'custom_title' => null,
                    'custom_date' => null,
                ]
            );
            Toastr::success('Global holiday has been hidden for your store.');
        } else {
            Toastr::error('You are not allowed to delete this holiday.');
        }

        return back();
    }
    public function holiday_add(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date|unique:holidays,date,NULL,id,vendor_id,' . Helpers::get_store_id(),
        ]);

        Holiday::create([
            'title' => $request->title,
            'date' => $request->date,
            'is_global' => 0,
            'vendor_id' => Helpers::get_store_id(),
            'created_by' => 'vendor',
        ]);
        Toastr::success('Holiday added successfully.');
        return back();
    }
    public function holiday_update(Request $request)
    {
        $holiday_id = $request->hl_id;

        $request->validate([
            'title' => 'required|string|max:255',
            'date'  => 'required|date',
        ]);
        $storeId = Helpers::get_store_id();
        $holiday = Holiday::findOrFail($holiday_id);
        if ($holiday->vendor_id == $storeId && $holiday->is_global == 0) {
            $holiday->update([
                'title' => $request->title,
                'date'  => $request->date
            ]);

            Toastr::success('Your holiday has been updated.');
        } elseif ($holiday->is_global == 1) {
            HolidayOverride::updateOrCreate(
                [
                    'vendor_id'   => $storeId,
                    'holiday_id'  => $holiday_id
                ],
                [
                    'custom_title' => $request->title,
                    'custom_date'  => $request->date,
                    'is_deleted'   => 0
                ]
            );
            Toastr::success('Global holiday has been customized for your store.');
        } else {
            Toastr::error('You are not allowed to update this holiday.');
        }
        return back();


        return back()->with('success', 'Holiday updated.');
    }
    public function profile_settings(Request $request)
    {
        $allPlans = Plan::where('status', 1)
            ->where(function ($q) {
                $q->whereNull('store_id')
                    ->orWhere('store_id', Helpers::get_store_id());
            })
            ->get();


        $store_data = Helpers::get_store_data();

        $module_categories = Category::where('module_id', $store_data->module_id)->where('position', 0)->where('status', 1)->get();
        $module_subcategories = Category::where('module_id', $store_data->module_id)->where('position', 1)->where('status', 1)->get();

        // all items set 1
        $allcategories_1 = [];
        array_push($allcategories_1, $store_data->category_1);
        $ct1 = Category::where('parent_id', $store_data->category_1)->pluck('id')->toArray();
        $allcategories_1 = array_merge($allcategories_1, $ct1);
        $items_1 = DB::table('items')->whereIn('category_id', $allcategories_1)->get();

        // all items set 2
        $allcategories_2 = [];
        array_push($allcategories_2, $store_data->category_2);
        $ct2 = Category::where('parent_id', $store_data->category_2)->pluck('id')->toArray();
        $allcategories_2 = array_merge($allcategories_2, $ct2);
        $items_2 = DB::table('items')->whereIn('category_id', $allcategories_2)->get();
        if (auth('vendor_employee')->check()) {
            $data['resign'] = DB::table('employee_resignations')->where('employee_id', Helpers::get_loggedin_user()->id)->exists();
        } else {
            $data['resign'] = 0;
        }
        $store = Helpers::get_store_data();

        return view('vendor-views.profile.index', compact('data', 'store', 'allPlans', 'items_1', 'items_2', 'store_data', 'module_categories', 'module_subcategories'));
    }
    public function quick_actions_save(Request $request)
    {
        $storeId = Helpers::get_store_id();
        DB::table('store_menu_visibility')->where('store_id', $storeId)->where('menu_type', 'quick_action')->delete();
        if ($request->menu) {
            foreach ($request->menu as $key) {
                DB::table('store_menu_visibility')->insert([
                    'store_id' => $storeId,
                    'menu_type' => 'quick_action',
                    'menu_key' => $key,
                    'is_visible' => 1
                ]);
            }
        }

        Toastr::success('Quick Actions Updated.');
        return back();
    }
    public function menu_preference(Request $request)
    {
        $storeId = Helpers::get_store_id();

        $full_menu = DB::table('menu')->where('menu_type', 'sidebar')->where('status', 1)->orderBy('name', 'asc')->get();
        $groupedMenus = $full_menu->groupBy(function ($item) {
            return $item->group ?? 'other';
        });

        $full_quick_actions = DB::table('menu')->where('menu_type', 'quick_action')->where('status', 1)->get();

        $selectedMenus = DB::table('store_menu_visibility')
            ->where('store_id', $storeId)
            ->where('menu_type', 'sidebar')
            ->pluck('menu_key')
            ->toArray();

        $selectedQuickActions = DB::table('store_menu_visibility')
            ->where('store_id', $storeId)
            ->where('menu_type', 'quick_action')
            ->pluck('menu_key')
            ->toArray();

        // prx($selectedQuickActions);

        // default menu items
        if (empty($selectedMenus)) {
            $selectedMenus = DB::table('menu')
                ->where('default', 1)
                // slug is your menu_key
                ->pluck('slug') // slug is your menu_key
                ->toArray();
        }
        return view('vendor-views.business-settings.menu_preference', compact('full_menu', 'full_quick_actions', 'selectedMenus', 'selectedQuickActions'));
    }
    public function menu_preference_save(Request $request)
    {
        $storeId = Helpers::get_store_id();
        DB::table('store_menu_visibility')->where('store_id', $storeId)->where('menu_type', 'sidebar')->delete();
        if ($request->menu) {
            foreach ($request->menu as $key) {
                DB::table('store_menu_visibility')->insert([
                    'store_id' => $storeId,
                    'menu_type' => 'sidebar',
                    'menu_key' => $key,
                    'is_visible' => 1
                ]);
            }
        }
        Toastr::success('Menu Preference updated.');
        return back();
    }

    public function purchase_template(Request $request)
    {
        $request->validate(['template_id' => 'required|integer|exists:store_webpage_templates,id']);

        $storeId  = Helpers::get_store_id();
        $template = DB::table('store_webpage_templates')
            ->where('id', $request->template_id)
            ->where('status', 1)
            ->first();

        if (!$template) {
            Toastr::error('Template not found.');
            return back();
        }

        // Already purchased and not expired — just select it
        $existing = TemplatePurchase::where('vendor_id', $storeId)
            ->where('template_id', $template->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existing || $template->price == 0) {
            StoreConfig::updateOrInsert(['store_id' => $storeId], ['template_id' => $template->id]);
            Toastr::success('Template selected successfully.');
            return back();
        }

        // Initiate Razorpay payment
        $vendor     = Vendor::find(Helpers::get_vendor_id());
        $currency   = BusinessSetting::where('key', 'currency')->value('value');
        $gstPercent = (float) (BusinessSetting::where('key', 'template_gst_percent')->value('value') ?? 0);
        $totalAmount = _taxIncludedPrice($template->price, $gstPercent, 'actual');

        $additional_data = [
            'business_name' => BusinessSetting::where('key', 'business_name')->value('value'),
            'business_logo' => asset('storage/app/public/business') . '/' . BusinessSetting::where('key', 'logo')->value('value'),
        ];

        $payer = new Payer($vendor->f_name . ' ' . $vendor->l_name, $vendor->email, $vendor->phone, '');

        $external_redirect_link = $request->getHost() == 'staging.mychitti.net' ?  'store-panel/settings/webpage/webpage-templates' : 'settings/webpage/webpage-templates';
        $payment_info = new PaymentInfo(
            success_hook: 'template_purchase_success',
            failure_hook: 'plan_failed',
            currency_code: $currency,
            payment_method: 'razor_pay',
            payment_platform: 'web',
            payer_id: $storeId,
            receiver_id: 100,
            additional_data: $additional_data,
            payment_amount: $totalAmount,
            external_redirect_link: $external_redirect_link,
            attribute: 'template_purchase',
            attribute_id: $template->id,
        );

        $receiver_info = new Receiver('Admin', 'example.png');
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return redirect()->to($redirect_link);
    } 

    public function domain_update(Request $request)
    {
        $store = Store::findOrFail(Helpers::get_store_id());
        $request->validate([
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?!https?:\/\/)(?!-)(?:[a-zA-Z0-9-]{1,63}\.)+[a-zA-Z]{2,}$/',
                \Illuminate\Validation\Rule::unique('stores', 'domain')->ignore($store->id),
            ],
        ]);   
        $store->domain = $request->domain;
        $store->save();

        \Illuminate\Support\Facades\Http::withToken(env('CLOUDFLARE_API_TOKEN'))
            ->post('https://api.cloudflare.com/client/v4/zones/' . env('CLOUDFLARE_ZONE_ID') . '/custom_hostnames', [
                'hostname' => $request->domain,
                'ssl' => ['method' => 'http', 'type' => 'dv'],
            ]);

        Toastr::success('Domain updated successfully');
        return back();
    }
    public function completeTemplatePurchase($vendorId, $templateId)
    {
        $template = DB::table('store_webpage_templates')->where('id', $templateId)->first();
        if (!$template) {
            return false;
        }


        // Fetch GST setting and compute GST-inclusive total
        $gstPercent  = (float) (BusinessSetting::where('key', 'template_gst_percent')->value('value') ?? 0);
        $totalAmount = _taxIncludedPrice($template->price, $gstPercent, 'actual');

        // Create invoice following the same pattern as ProfileController::buyModule
        $invoice = new ManualInvoice();
        $invoice->invoice_id     = Helpers::generateInvoiceIdAdmin();
        $invoice->invoice_serial = BusinessSetting::where('key', 'admin_bill_serial_number')->first()->value - 1;
        $invoice->vendor_id      = null;
        $invoice->bill_to        = $vendorId;
        $invoice->bill_to_type   = 'vendor';
        $invoice->module_id      = null;
        $invoice->total_amount   = $totalAmount;
        $invoice->payment_method = 'Online';
        $invoice->tax_type       = $gstPercent > 0 ? 'gst' : 'non-gst';
        $invoice->payment_status = 'Paid';
        $invoice->payment_date   = now()->toDateString();
        $invoice->generated_by   = 'admin';
        $invoice->save();

        $item = new InvoiceItem();
        $item->rand_invoice_id = $invoice->invoice_id;
        $item->name            = $template->name . ' - Webpage Template';
        $item->qty             = 1;
        $item->price           = $template->price;
        $item->tax             = $gstPercent;
        $item->hsn             = '';
        $item->save();

        try {
            $pdfResult = _createBillPdf($invoice, 'admin');
            if ($pdfResult && isset($pdfResult['pdf'])) {
                $invoice->update(['pdf' => $pdfResult['pdf']]);
            }
        } catch (\Exception $e) {
            // PDF generation failure should not block purchase recording
        }

        // Calculate expiry
        $expiresAt = null;
        if (!empty($template->duration_count) && !empty($template->duration_unit)) {
            $expiresAt = match (strtolower($template->duration_unit)) {
                'months', 'month' => now()->addMonths((int) $template->duration_count),
                'years', 'year'   => now()->addYears((int) $template->duration_count),
                'weeks', 'week'   => now()->addWeeks((int) $template->duration_count),
                default           => now()->addDays((int) $template->duration_count),
            };
        }

        TemplatePurchase::create([
            'vendor_id'    => $vendorId,
            'template_id'  => $template->id,
            'amount_paid'  => $totalAmount,
            'invoice_id'   => $invoice->invoice_id,
            'purchased_at' => now(),
            'expires_at'   => $expiresAt,
        ]);

        // Ledger & daybook entries (same pattern as buyModule)
        $debit_account  = Helpers::ensurePurchaseAccount('Template Purchase', $vendorId);
        $credit_account = Helpers::ensureSubscriptionRevenueAccount();

        $ledgerData = [
            'date'         => now(),
            'amount'       => $totalAmount,
            'status'       => 'approved',
            'description'  => 'Webpage Template Purchase - ' . $template->name,
            'voucher_type' => 'Purchase',
            'invoice_id'   => $invoice->getKey(),
        ];

        $voucher = _masterLedgerEntry(
            $ledgerData,
            $credit_account,
            $debit_account,
            'store',
            'admin',
            null,
            $vendorId
        );

        _saveDayBookEntry(
            $totalAmount,
            'debit',
            $vendorId,
            'Template Purchase',
            $invoice->getKey(),
            $voucher?->id,
            null,
            null,
            'Online'
        );

        // Auto-select the template
        StoreConfig::updateOrInsert(['store_id' => $vendorId], ['template_id' => $template->id]);

        return true;
    }
}
