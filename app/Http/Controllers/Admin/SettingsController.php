<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
use App\Models\VendorEmployee;
use App\Models\BusinessSetting;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\TemplatePurchase;
use App\Models\Vendor;
use App\Traits\Payment;
use App\Library\Payer;
use App\Library\Payment as PaymentInfo;
use App\Library\Receiver;
use App\Models\Admin;

class SettingsController extends Controller
{
   
   
    public function common_setting_save(Request $request)
    {
        //  prx($request->all());
        $data = $request->except(['_token', '_method']);
        if (!empty($data)) {
            $config = StoreConfig::firstOrNew(['store_id' => 0]);
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
        $store_id = 0;
        $store = StoreConfig::where('store_id', $store_id)->first();

        return view('admin-views.settings.receivable_receipts_settings', compact('store'));
    }
    public function pos(Request $request)
    {

        $store_id = 0;
        $store = StoreConfig::where('store_id', $store_id)->first();
        $order_type = OrderType::where('store_id', $store_id)->get();
        $accounts = AccountDetail::where('user_type', 'vendor')->where('user_id',  $store_id)->where('type', 'pos')->get();
        // $data['upcoming_number'] = Helpers::_nextTokenNumber();

        return view('admin-views.settings.pos', compact('store', 'order_type', 'accounts'));
    }

    public function quotation_settings(Request $request)
    {
        $tncs = StoreTnc::where('store_id', 0)->where('tnc_type', 'quotation')->get();
        $store_id = 0;
        $accounts = AccountDetail::where('user_type', 'vendor')->where('user_id',  $store_id)->where('type', 'quotation')->get();
        $staffs = Admin::all();
        $signatures = StoreSignature::with('adminEmployee')->where('store_id', $store_id)->where('type', 'quotation')->get();
        $storeConfig = StoreConfig::where('store_id', $store_id)->first();
        return view('admin-views.settings.quotation_settings', compact('tncs', 'signatures', 'staffs', 'accounts',  'storeConfig'));
    }
    public function store_settings(Request $request) {}
    public function holiday_settings(Request $request)
    {
        $storeId = 0;
        $holidays = _getStoreHolidays($storeId);
        return view('admin-views.settings.holidays', compact('holidays'));
    }
    public function holiday_delete(Request $request, $holidayId)
    {
        $storeId = 0;

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
            'date' => 'required|date|unique:holidays,date,NULL,id,vendor_id,' . 0,
        ]);

        Holiday::create([
            'title' => $request->title, 
            'date' => $request->date,
            'is_global' => 0,
            'vendor_id' => 0,
            'created_by' => 'admin',
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
        $storeId = 0;
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
                    ->orWhere('store_id', 0);
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
        if (auth('admin')->user()->role_id != 1) {
            $data['resign'] = DB::table('employee_resignations')->where('employee_id', auth('admin')->id())->where('store_id', 0)->exists();
        } else {
            $data['resign'] = 0; 
        }
        $store = Helpers::get_store_data();

        return view('admin-views.profile.index', compact('data', 'store', 'allPlans', 'items_1', 'items_2', 'store_data', 'module_categories', 'module_subcategories'));
    }
    public function quick_actions_save(Request $request)
    {
        $storeId = 0;
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
        $storeId = 0;

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
        return view('admin-views.business-settings.menu_preference', compact('full_menu', 'full_quick_actions', 'selectedMenus', 'selectedQuickActions'));
    }
    public function menu_preference_save(Request $request)
    {
        $storeId = 0;
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

        $storeId  = 0;
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

    public function domain_remove()
    {
        $store = Store::findOrFail(0);
        $domain = $store->domain;

        // prx($domain);
 
        if ($domain) {
            $token   = config('services.cloudflare.api_token');
            $zoneId  = config('services.cloudflare.zone_id');
            $baseUrl = 'https://api.cloudflare.com/client/v4/zones/' . $zoneId . '/custom_hostnames';

            $listRes = Http::withToken(config('services.cloudflare.api_token'))->get($baseUrl, ['hostname' => $domain]);

            if ($listRes->successful()) {
                $hostnames = $listRes->json('result');
                if (!empty($hostnames)) {
                   Http::withToken($token)->delete($baseUrl . '/' . $hostnames[0]['id']);
                   
                }
            } else {
                // dd([ 
                //     'status'  => $listRes->status(),
                //     'body'    => $listRes->json(),
                //     'token'   => $token ? 'set (' . strlen($token) . ' chars)' : 'MISSING',
                //     'zone_id' => $zoneId ?: 'MISSING',
                //     'url'     => $baseUrl,
                //     'domain'  => $domain,
                // ]);
            }
            

            $store->domain = null;
            $store->save();
        }

        Toastr::success('Domain removed successfully');
        return back();
    }

    public function domain_update(Request $request)
    {
        $store = Store::findOrFail(0);
        $request->validate([
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?!https?:\/\/)(?!-)(?:[a-zA-Z0-9-]{1,63}\.)+[a-zA-Z]{2,}$/',
                \Illuminate\Validation\Rule::unique('stores', 'domain')->ignore($store->id),
            ],
        ]);

        $charge  = (float) (BusinessSetting::where('key', 'custom_domain_charge')->value('value') ?? 0);
        $gstPct  = (float) (BusinessSetting::where('key', 'cd_gst_percent')->value('value') ?? 0);
        $gstIncl = (bool)  (BusinessSetting::where('key', 'cd_gst_include')->value('value') ?? 0);

        if ($charge > 0) {
            // GST already baked into price → use as-is; otherwise add GST on top
            $totalAmount = $gstIncl ? $charge : _taxIncludedPrice($charge, $gstPct, 'actual');

            $vendor   = Vendor::find(Helpers::get_vendor_id());
            $currency = BusinessSetting::where('key', 'currency')->value('value');

            $additional_data = [
                'business_name' => BusinessSetting::where('key', 'business_name')->value('value'),
                'business_logo' => asset('storage/app/public/business') . '/' . BusinessSetting::where('key', 'logo')->value('value'),
            ];

            $payer = new Payer($vendor->f_name . ' ' . $vendor->l_name, $vendor->email, $vendor->phone, '');

            $external_redirect_link = $request->getHost() == 'staging.mychitti.net'
                ? 'store-panel/settings/webpage/domain-setup'
                : 'settings/webpage/domain-setup';
 
            $payment_info = new PaymentInfo(
                success_hook: 'domain_purchase_success',
                failure_hook: 'plan_failed',
                currency_code: $currency,
                payment_method: 'razor_pay',
                payment_platform: 'web',
                payer_id: 0,
                receiver_id: 100,
                additional_data: $additional_data,
                payment_amount: $totalAmount,
                external_redirect_link: $external_redirect_link,
                attribute: 'domain_purchase',
                attribute_id: $request->domain,
            );

            $receiver_info = new Receiver('Admin', 'example.png');
            $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
            return redirect()->to($redirect_link);
        }

        // Free — save and register directly
        $this->completeDomainRegistration(0, $request->domain);
        Toastr::success('Domain saved successfully');
        return back();
    }
    public function completeDomainRegistration($storeId, $domain)
    {
        $store = Store::findOrFail($storeId);
        $store->domain = $domain;
        $store->save();

        Http::withToken(config('services.cloudflare.api_token'))
            ->post('https://api.cloudflare.com/client/v4/zones/' . config('services.cloudflare.zone_id') . '/custom_hostnames', [
                'hostname' => $domain,
                'ssl'      => ['method' => 'http', 'type' => 'dv'],
            ]);

        return true;
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
            'admin',
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

    // ─── Webpage Template Management ───────────────────────────────────────────

    public function webpageTemplates()
    {
        $templates = DB::table('store_webpage_templates')->orderBy('id')->get();
        return view('admin-views.settings.webpage-templates', compact('templates'));
    }

    public function webpageTemplateUpdate(Request $request)
    {
        $request->validate([
            'id'     => 'required|integer|exists:store_webpage_templates,id',
            'price'  => 'required|numeric|min:0',
            'status' => 'required|in:0,1',
            'name'   => 'required|string|max:191',
        ]);

        DB::table('store_webpage_templates')->where('id', $request->id)->update([
            'name'       => $request->name,
            'price'      => $request->price,
            'status'     => $request->status,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Template updated.']);
    }

    public function webpageTemplateToggle(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:store_webpage_templates,id']);

        $current = DB::table('store_webpage_templates')->where('id', $request->id)->value('status');
        DB::table('store_webpage_templates')->where('id', $request->id)->update([
            'status'     => $current ? 0 : 1,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'status' => !$current]);
    }
}
