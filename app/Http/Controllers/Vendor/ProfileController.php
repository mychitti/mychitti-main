<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Library\Payer;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\PaymentRequest;
use App\Models\Vendor;
use App\Models\Plan;
use App\Models\VendorSubscription;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use DateTime;
use App\Traits\Payment;
use App\Library\Payment as PaymentInfo;
use App\Library\Receiver;
use App\Models\HospitalBedTier;
use App\Models\Store;
use App\Models\StoreConfig;
use App\Models\SubModule;
use App\Models\SubModuleDiscount;
use App\Models\TempModulePurchase;
use App\Models\VendorEmployee;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileController extends Controller
{

    public function view()
    {
        if (auth('vendor_employee')->check()) {
            $employee  = VendorEmployee::with('role', 'department', 'branch')->where('id', Helpers::get_loggedin_user()->id)->first();
            return view('vendor-views.profile.staff_profile', compact('employee'));
        } else {

            $store_data = Helpers::get_store_data();

            if (auth('vendor_employee')->check()) {
                $data['resign'] = DB::table('employee_resignations')->where('id', Helpers::get_loggedin_user()->id)->exists();
            } else {
                $data['resign'] = 0;
            }

            return view('vendor-views.profile.index', compact('data', 'store_data'));
        }
    }

    public function saveDefaultDashboard(Request $request)
    {
        $allowed = ['main', 'leads_page', 'leads_dashboard', 'hr', 'hospital', 'laundry', 'account', 'inventory', 'pos'];
        $value   = in_array($request->default_dashboard, $allowed) ? $request->default_dashboard : null;

        StoreConfig::updateOrCreate(
            ['store_id' => Helpers::get_store_id()],
            ['default_dashboard' => $value]
        );

        Toastr::success('Default dashboard updated.');
        return back();
    }

    public function bank_view()
    {
        $data = Helpers::get_vendor_data();
        return view('vendor-views.profile.bankView', compact('data'));
    }
    public function enable_free_trial(Request $request)
    {
        $storeId  = Helpers::get_store_id();
        // CHECK IF ALREADY CONSUMED  
        $StoreConfig = StoreConfig::firstOrCreate(
            ['store_id' => $storeId],
            ['store_id' => $storeId]
        );


        if ((int) $StoreConfig->free_trial_consumed === 1) {
            Toastr::error('Free Trial Already Consumed');
            return back();
        }

        $business_type = strtolower(Helpers::get_store_data()->business_type ?? '');

        // If specific modules exist for this business type, only give those.
        // Otherwise fall back to generic 'all' modules.
        $hasSpecific = DB::table('sub_modules')
            ->whereRaw('LOWER(business_type) = ?', [$business_type])
            ->where('business_type', '!=', 'all')
            ->where('business_type', '!=', '')
            ->exists();

        $modules = DB::table('sub_modules')
            ->when($hasSpecific,
                fn($q) => $q->whereRaw('LOWER(business_type) = ?', [$business_type]),
                fn($q) => $q->where('business_type', 'all')
            )
            ->get();
        foreach ($modules as $module) {

            if (!$module) continue;

            $finalPrice = 0;

            $free_trial_duration_count = 15;
            $free_trial_duration_unit = 'Days';

            $planDetails = Plan::where('key', $module->Key)->first();
            if (!$planDetails) {
                $planDetails = new Plan();
                $planDetails->key   = $module->Key;
                $planDetails->title = $module->name;
                $dynamicColumn = $module->Key;
                $planDetails->{$dynamicColumn} = 1;
                $planDetails->price  = $module->price_per_month;
                $planDetails->status = 1;
                $planDetails->save();
            }

            $permitted_modules = json_encode([$module->Key]);

            $expDate = new DateTime();
            $expDate->modify('+' . $free_trial_duration_count . ' ' . $free_trial_duration_unit);
            $expDate = $expDate->format('Y-m-d H:i:s');

            $subscription = new VendorSubscription;
            $subscription->vendor_id = Helpers::get_store_id();
            $subscription->plan_id = $planDetails->id;
            $subscription->duration_count = $free_trial_duration_count;
            $subscription->duration_type = $free_trial_duration_unit;
            $subscription->permitted_modules = $permitted_modules;
            $subscription->plan_expiry = $expDate;
            $subscription->purchased_at = $finalPrice;
            $subscription->created_at = date('Y-m-d H:i:s');
            $subscription->free_trial = 1;
            $subscription->save();
        }
        $StoreConfig->free_trial_consumed = 1;
        $StoreConfig->save();

        Toastr::success('Free Trial Enabled Successfully');
        return back();
    }
    public function buy_module(Request $request)
    {
        // prx($request->all());
        $request->validate([
            'selected_modules' => 'required'
        ]);

        $selectedModules = json_decode($request->selected_modules, true);

        if (!$selectedModules || !is_array($selectedModules)) {
            return back()->with('error', 'Invalid module selection');
        }

        $grandTotal = 0;
        $finalData = [];
        $today = now()->toDateString();
        $ids = [];

        foreach ($selectedModules as $item) {

            $module = DB::table('sub_modules')
                ->where('id', $item['module_id'])
                ->first();

            if (!$module) continue;
            $ids[] = $module->id;

            $months = (int) $item['months'];
            if ($module->Key == 'hospital_manage') {
                 $bedTierPrice = HospitalBedTier::where('id', $request->bed_tier_id)->first()?->price_monthly ?? $module->price_per_month; 
                $basePrice = $bedTierPrice * $months;
            } else {
                $basePrice = $module->price_per_month * $months;
            }

            $discountPercent = SubModuleDiscount::where('sub_module_id', $module->id)
                ->whereHas('duration', fn($q) => $q->where('months', $months))
                ->value('discount') ?? 0;

            $discountAmount = ($basePrice * $discountPercent) / 100;
            $finalPrice = $basePrice - $discountAmount;

            $expiryDate = now()->addMonths($months);

            $grandTotal += $finalPrice;

            $finalData[] = [
                'vendor_id' => Helpers::get_store_id(),
                'module_id' => $module->id,
                'duration_count' => $months,
                'duration_type' => 'Months',
                'plan_expiry' => $expiryDate,
                'purchased_at' => $finalPrice,
                'bed_tier_id' => $request->bed_tier_id,
                'created_at' => now()
            ];
        }

        // ✅ Save all subscriptions safely
        DB::table('temp_module_purchases')->insert($finalData);

        // Apply GST to payment amount
        $gstSettings = _planGstSettings();
        $gstPercent = floatval($gstSettings['gst_percent'] ?? 0);
        $gstMode = $gstSettings['gst_mode'] ?? 'exclude';
        $paymentAmount = $grandTotal;
        if ($gstPercent > 0 && $gstMode === 'exclude') {
            $paymentAmount = $grandTotal + ($grandTotal * $gstPercent / 100);
        }

        $vendor =  Vendor::find(Helpers::get_vendor_id());
        $payer = new Payer($vendor['f_name'] . ' ' . $vendor['l_name'], $vendor['email'], $vendor['phone'], '');
        $currency = BusinessSetting::where(['key' => 'currency'])->first()->value;
        $additional_data = [
            'business_name' => BusinessSetting::where(['key' => 'business_name'])->first()?->value,
            'business_logo' => asset('storage/app/public/business') . '/' . BusinessSetting::where(['key' => 'logo'])->first()?->value
        ];
        $domain = $request->getHost(); // e.g. staging.mychitti.net

        if (Str::contains($domain, 'staging')) {
            $external_redirect_link = 'store-panel/subscriptions';
        } else {
            $external_redirect_link = 'subscriptions';
        }
        $payment_info = new PaymentInfo(
            success_hook: 'module_success',
            failure_hook: 'plan_failed',
            currency_code: $currency,
            payment_method: 'razor_pay',
            payment_platform: 'web',
            payer_id: Helpers::get_store_id(),
            receiver_id: 100,
            additional_data: $additional_data,
            payment_amount: $paymentAmount,
            external_redirect_link: $external_redirect_link,
            attribute: 'store_subscription',
            attribute_id: implode(',', $ids),
        );

        $receiver_info = new Receiver('Admin', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
        return redirect()->to($redirect_link);
    }
    // public function buy_plan(Request $request)
    // {
    //     $v_id = \App\CentralLogics\Helpers::get_store_id();
    //     $variation = 0;
    //     if ($request->has('plan_select') && $request->post('plan_select') != '') {

    //         $plan = Plan::find($request->post('plan_select'));
    //         $vrKey  = 'variation' . $request->post('plan_select');

    //         if ($request->has($vrKey) && $request->post($vrKey) != '') {
    //             $planvr = Plan::where('id', $request->post('plan_select'))->whereJsonContains('price_variations', ['duration' => $request->post($vrKey)])->first();
    //             $vrDetails = json_decode($planvr->price_variations, true);

    //             foreach ($vrDetails as $key => $value) {
    //                 if ($value['duration'] == $request->post($vrKey)) {
    //                     // prx($value);

    //                     $plan->discount = $value['discount'];
    //                     $plan->price = $value['price'];
    //                     $variation = $value['id'];
    //                     break;
    //                 }
    //             }
    //         }
    //         $discountedAmount = _discountedPrice($plan->price, $plan->discount, 'percent');

    //         if (!$discountedAmount) {

    //             $buyplan =   $this->buyPlan($v_id, $plan->id . '-' . $variation);
    //             if ($buyplan) {
    //                 Toastr::success('Plan purchased successfully');
    //             } else {
    //                 Toastr::error('Error occured in plan purchase');
    //             }
    //         } else {
    //             $amount = ceil(($discountedAmount * $plan->gst_percent / 100) + $discountedAmount);

    //             $vendor =  Vendor::find(Helpers::get_vendor_id());
    //             $payer = new Payer($vendor['f_name'] . ' ' . $vendor['l_name'], $vendor['email'], $vendor['phone'], '');
    //             $currency = BusinessSetting::where(['key' => 'currency'])->first()->value;
    //             $additional_data = [
    //                 'business_name' => BusinessSetting::where(['key' => 'business_name'])->first()?->value,
    //                 'business_logo' => asset('storage/app/public/business') . '/' . BusinessSetting::where(['key' => 'logo'])->first()?->value
    //             ];
    //             $payment_info = new PaymentInfo(
    //                 success_hook: 'plan_success',
    //                 failure_hook: 'plan_failed',
    //                 currency_code: $currency,
    //                 payment_method: 'razor_pay',
    //                 payment_platform: 'web',
    //                 payer_id: Helpers::get_store_id(),
    //                 receiver_id: 100,
    //                 additional_data: $additional_data,
    //                 payment_amount: $amount,
    //                 external_redirect_link: 'subscriptions',
    //                 attribute: 'store_subscription',
    //                 attribute_id: $plan->id . '-' . $variation,
    //             );

    //             $receiver_info = new Receiver('Admin', 'example.png');

    //             $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
    //             return redirect()->to($redirect_link);
    //         }
    //         // return back();
    //     } else {
    //         // echo 'no plan2'; die;
    //         Toastr::error('Please select a plan');
    //         return back();
    //     }
    // }
    // public function settings_plans(Request $request)
    // {
    //     $v_id = \App\CentralLogics\Helpers::get_store_id();

    //     $buyplan =   $this->buyPlan($v_id, $request->post('plan_select'));
    //     if ($buyplan) {
    //         Toastr::success('Plan purchased successfully');
    //     } else {
    //         Toastr::error('Error occured in plan purchase');
    //     }
    //     return back();
    // }
    public function buyModule($vendorId = NULL, $module_ids = NULL)
    {
        $invoiceTotalAmount = 0;
        $invoice_items = [];
        // prx($module_ids);
        foreach (explode(',', $module_ids) as $value) {
            $module = SubModule::where('id', $value)->first();
            $tempPurchase = TempModulePurchase::where('vendor_id', Helpers::get_store_id())
                ->where('module_id', $value)
                ->orderBy('id', 'desc')
                ->first();

            $planDetails = Plan::where('key', $module->Key)->first();
            if (!$planDetails) {
                $planDetails = new Plan();
                $planDetails->key   = $module->Key;
                $planDetails->title = $module->name;
                $dynamicColumn = $module->Key;
                $planDetails->{$dynamicColumn} = 1;
                $planDetails->price  = $module->price_per_month;
                $planDetails->status = 1;
                $planDetails->save();
            }

            $permitted_modules = json_encode([$module->Key]);

            $expDate = new DateTime();
            $expDate->modify('+' . $tempPurchase->duration_count . ' ' . $tempPurchase->duration_type);
            $expDate = $expDate->format('Y-m-d H:i:s');


            // $subscription_exists = VendorSubscription::where('vendor_id',  $vendorId)->exists();
            // if ($subscription_exists) {
            //     $subscription = VendorSubscription::where('vendor_id',  $vendorId)->first();
            // } else {
            //     $subscription = new VendorSubscription;
            //     $subscription->vendor_id = $vendorId;
            //     $subscription->created_at = date('Y-m-d H:i:s');
            // }

            $subscription = new VendorSubscription;
            $subscription->vendor_id = Helpers::get_store_id();
            $subscription->plan_id = $planDetails->id;
            $subscription->duration_count = $tempPurchase->duration_count;
            $subscription->duration_type = $tempPurchase->duration_type;
            $subscription->permitted_modules = $permitted_modules;
            $subscription->plan_expiry = $expDate;
            $subscription->purchased_at = $tempPurchase->purchased_at;
            $subscription->bed_tier_id = $tempPurchase->bed_tier_id;
            $subscription->created_at = date('Y-m-d H:i:s');

            $subscription->save();

            $invoice_items[] = [
                'name' =>  $planDetails->title . '<br>( Valid for ' . $subscription->duration_count . ' ' . $subscription->duration_type . ' )',
                'qty' => 1,
                'price' => $subscription->purchased_at,
                'hsn' => '',
                'tax' => 0
            ];
            $invoiceTotalAmount += $tempPurchase->purchased_at;
            if ($tempPurchase) {
                $tempPurchase->delete();
            }
        }

        // Apply GST settings from data_settings
        $gstSettings = _planGstSettings();
        $gstPercent = floatval($gstSettings['gst_percent'] ?? 0);
        $gstMode = $gstSettings['gst_mode'] ?? 'exclude';
        $hsnCode = $gstSettings['hsn'] ?? '';

        $taxableAmount = $invoiceTotalAmount;
        $gstAmount = 0;

        if ($gstPercent > 0) {
            if ($gstMode === 'exclude') {
                $gstAmount = ($taxableAmount * $gstPercent) / 100;
            } else {
                $gstAmount = $taxableAmount - ($taxableAmount * 100) / (100 + $gstPercent);
                $taxableAmount = $invoiceTotalAmount - $gstAmount;
            }
        }

        // Update invoice items with HSN and tax
        foreach ($invoice_items as &$invItem) {
            $invItem['hsn'] = $hsnCode;
            $invItem['tax'] = $gstPercent;
        }
        unset($invItem);

        $grandTotalWithGst = ($gstMode === 'exclude') ? $invoiceTotalAmount + $gstAmount : $invoiceTotalAmount;

        $zone_name = 'Tirupati';
        $zone_id  = auth('vendor')->check() ?  Helpers::get_store_data()->zone_id : 21;
        $zone = Zone::find($zone_id);
        if ($zone) {
            $zone_name = $zone->name;
        }

        // create bill
        $invoice = new ManualInvoice();
        $invoice->invoice_id = Helpers::generateInvoiceIdAdmin();
        $invoice->invoice_serial = BusinessSetting::where('key', 'admin_bill_serial_number')->first()->value - 1;
        $invoice->vendor_id = NULL;
        $invoice->bill_to = auth('vendor')->check() ?  Helpers::get_store_id() : ($vendorId ?? null);
        $invoice->bill_to_type = 'vendor';
        $invoice->module_id =  Helpers::get_store_data()->module_id;
        $invoice->total_amount =  $grandTotalWithGst;
        $invoice->payment_method = 'Online';
        $invoice->payment_mode = 'Online';
        $invoice->tax_type =  'gst';
        $invoice->payment_status =  'Paid';
        $invoice->reminder_date = null;
        $invoice->payment_date =  date('Y-m-d');
        $invoice->generated_by =  'admin';
        $invoice->financial_year = _currentFinancialYear();
        $invoice->save();

        // ledger entry 
        $debit_account = Helpers::ensurePurchaseAccount('Subscription Plan');
        $credit_account = Helpers::ensureSubscriptionRevenueAccount();

        $data = [
            'date' => now(),
            'amount' => $invoice->total_amount,
            'status' => 'approved',
            'description' => 'Subscription Plan',
            'voucher_type' => 'Purchase',
            'status' => 'approved',
        ];
        $voucher = _masterLedgerEntry($data, $credit_account, $debit_account, 'store', 'admin', null);
        if ($invoice->payment_status == 'Paid') {
            _saveDayBookEntry($invoice->total_amount, 'debit', Helpers::get_store_id(), "Module Purchase", $invoice->id, $voucher?->id);
        }


        foreach ($invoice_items as $key => $value) {
            $InvoiceItem = new InvoiceItem();
            $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
            $InvoiceItem->manual_invoice_id = $invoice->id;
            $InvoiceItem->name = $value['name'];
            $InvoiceItem->qty =  $value['qty'];
            $InvoiceItem->price =  $value['price'];
            $InvoiceItem->tax =  $value['tax'];
            $InvoiceItem->hsn =  $value['hsn'];
            $InvoiceItem->save();
        }

        // prx($invoice);

        try {
            $data = _createBillPdf($invoice, 'admin');
            $invoice->update(['pdf' => $data['pdf']]);
            return redirect($data['url']);
        } catch (\Exception) {
            // PDF failure is non-fatal
        }
        return true;
    }
    public function buyPlan($vendorId = NULL, $planId = NULL)
    {
        // Request $request, 
        if ($vendorId == NULL && $planId == NULL) {
            $vendorId = $request->post('v_id');
            $planId = $request->post('plan_select');
        }
        $planDetails = Plan::find($planId);
        $permitted_modules = [];
        $variation = null;
        if ($planId) {
            $variation = explode('-', $planId)[1] ?? null;
            $planId = explode('-', $planId)[0] ?? $planId;
        }
        if ($planDetails->advanced_leads_manage) {
            array_push($permitted_modules, 'advanced_leads_manage');
        }
        if ($planDetails->projects_manage) {
            array_push($permitted_modules, 'projects_manage');
        }
        if ($planDetails->quotaiton_manage) {
            array_push($permitted_modules, 'quotaiton_manage');
        }
        // if ($planDetails->salary_manage) {
        //     array_push($permitted_modules, 'salary_manage');
        // }
        // if ($planDetails->staff_manage) {
        //     array_push($permitted_modules, 'staff_manage');
        // }
        // if ($planDetails->att_manage) {
        //     array_push($permitted_modules, 'att_manage');
        // }
        if ($planDetails->hr_manage) {
            array_push($permitted_modules, 'hr_manage');
        }
        if ($planDetails->account_manage) {
            array_push($permitted_modules, 'account_manage');
        }
        if ($planDetails->billing) {
            array_push($permitted_modules, 'billing');
        }
        if ($planDetails->inventory_manage) {
            array_push($permitted_modules, 'inventory_manage');
        }
        if ($planDetails->inventory_manage) {
            array_push($permitted_modules, 'task_manage');
        }

        $variationData = null;
        $permitted_modules = json_encode($permitted_modules);
        if ($variation) {
            $planDet = Plan::find($planId);

            if ($planDet && $planDet->price_variations) {
                $variations = json_decode($planDet->price_variations, true);

                foreach ($variations as $v) {
                    if ((string)$v['id'] === (string)$variation) {
                        $variationData = $v;
                        break;
                    }
                }
            }

            if ($variationData) {
                $duration = $variationData['duration']; // like "1 Month", "6 Months", etc.

                // Normalize or map duration to a label
                $durationMap = [
                    '1 Month' => 'Monthly',
                    '3 Months' => 'Quarterly',
                    '6 Months' => 'Half-Yearly',
                    '12 Months' => 'Yearly',
                ];

                $label = $durationMap[$duration] ?? null;

                if ($label) {
                    $duration = $label;

                    $expDate = new DateTime();

                    switch ($label) {
                        case 'Monthly':
                            $expDate->modify('+1 month');
                            break;
                        case 'Quarterly':
                            $expDate->modify('+3 months');
                            break;
                        case 'Half-Yearly':
                            $expDate->modify('+6 months');
                            break;
                        case 'Yearly':
                            $expDate->modify('+1 year');
                            break;
                    }

                    $expDate = $expDate->format('Y-m-d H:i:s');
                }
            }
        } else {
            $expDate = new DateTime();
            $expDate->modify('+' . $planDetails->duration_count . ' ' . $planDetails->duration_type);
            $expDate = $expDate->format('Y-m-d H:i:s');
        }


        $subscription_exists = VendorSubscription::where('vendor_id',  $vendorId)->exists();
        if ($subscription_exists) {
            $subscription = VendorSubscription::where('vendor_id',  $vendorId)->first();
        } else {
            $subscription = new VendorSubscription;
            $subscription->vendor_id = $vendorId;
            $subscription->created_at = date('Y-m-d H:i:s');
        }

        $subscription->plan_id = $planId;
        $subscription->duration_count = $planDetails->duration_count;
        $subscription->duration_type = $planDetails->duration_type;
        $subscription->permitted_modules = $permitted_modules;
        $subscription->plan_expiry = $expDate;
        $subscription->variation =   $variationData ? json_encode($variationData) : null;
        $subscription->purchased_at = $variationData ? _discountedPrice($variationData['price'], $variationData['discount'], 'percent') : null;
        $subscription->save();

        $zone_name = 'Tirupati';
        $zone_id  = auth('vendor')->check() ?  Helpers::get_store_data()->zone_id : 21;
        $zone = Zone::find($zone_id);
        if ($zone) {
            $zone_name = $zone->name;
        }

        $plan_amount = $variationData ? $variationData['price'] : $planDetails->price;

        // create bill 
        $invoice = new ManualInvoice();
        $invoice->invoice_id = Helpers::generateInvoiceIdAdmin();
        $invoice->invoice_serial = BusinessSetting::where('key', 'admin_bill_serial_number')->first()->value - 1;
        $invoice->vendor_id = NULL;
        $invoice->bill_to = auth('vendor')->check() ?  Helpers::get_store_id() : ($vendorId ?? null);
        $invoice->bill_to_type = 'vendor';
        $invoice->module_id =  Helpers::get_store_data()->module_id;
        $invoice->total_amount = $variationData ?  round(_taxIncludedPrice(_discountedPrice($variationData['price'], $variationData['discount'], 'percent'), $planDetails->gst_percent, 'actual')) : 0;
        $invoice->payment_method = 'Online';
        $invoice->tax_type =  'gst';
        $invoice->payment_status =  'Paid';
        $invoice->reminder_date = null;
        $invoice->payment_date =  date('Y-m-d');
        $invoice->generated_by =  'admin';
        $invoice->financial_year = _currentFinancialYear();
        $invoice->save();

        // ledger entry 
        $debit_account = Helpers::ensurePurchaseAccount('Subscription Plan');
        $credit_account = Helpers::ensureSubscriptionRevenueAccount();

        $data = [
            'date' => now(),
            'amount' => $invoice->total_amount,
            'status' => 'approved',
            'description' => 'Subscription Plan',
            'voucher_type' => 'Purchase',
            'status' => 'approved',
        ];
        $voucher = _masterLedgerEntry($data, $credit_account, $debit_account, 'store', 'admin', null);
        if ($invoice->payment_status == 'Paid') {
            _saveDayBookEntry($invoice->total_amount, 'debit', Helpers::get_store_id(), "Subscription Plan Purchase", $invoice->id, $voucher?->id);
        }

        $InvoiceItem = new InvoiceItem();
        $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
        $InvoiceItem->name = 'Subscription Plan - ' . $planDetails->title . '<br> Plan Validity Till: ' . _formatted_datetime($expDate) . '<br> Service Area: ' . $zone_name;
        $InvoiceItem->qty = 1;
        $InvoiceItem->price = $variationData ? _discountedPrice($variationData['price'], $variationData['discount'], 'percent') : 0;
        $InvoiceItem->tax = $planDetails->gst_percent;
        $InvoiceItem->hsn = $planDetails->hsn;
        $InvoiceItem->save();

        try {

            $data = _createBillPdf($invoice, 'admin');
            $invoice->update(['pdf' => $data['pdf']]);
            return redirect($data['url']);
        } catch (\Throwable $th) {
            //
        }

        return true;
    }

    public function edit()
    {
        $data = Helpers::get_vendor_data();
        return view('vendor-views.profile.edit', compact('data'));
    }

    public function about_update(Request $request)
    {
        $seller = auth('vendor')->user();

        if (auth('vendor')->check()) {
            $seller->why_we_started = $request->why_we_started;
            $seller->our_idea = $request->our_idea;
            $seller->save();
        }
        Toastr::success(translate('messages.about_updated_successfully'));
        return back();
    }
    public function update(Request $request)
    {
        $table = auth('vendor')->check() ? 'vendors' : 'vendor_employees';
        $seller = auth('vendor')->check() ? auth('vendor')->user() : auth('vendor_employee')->user();
        $request->validate([
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'email' => 'required|email|unique:' . $table . ',email,' . $seller->id,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20|unique:' . $table . ',phone,' . $seller->id,
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
        ]);
        $seller = auth('vendor')->check() ? auth('vendor')->user() : auth('vendor_employee')->user();
        // prx($seller);
        if (auth('vendor')->check()) {
            $store = Store::where('vendor_id', $seller->id)->first();
            $store->email = $request->email;
            $store->save();
        }
        $seller->f_name = $request->f_name;
        $seller->l_name = $request->l_name;
        $seller->phone = $request->phone;
        $seller->email = $request->email;

        if ($request->image) {
            $seller->image = Helpers::update('vendor/', $seller->image, 'png', $request->file('image'));
        }
        $seller->save();

        Toastr::success(translate('messages.profile_updated_successfully'));
        return back();
    }

    public function subscriptions(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $allPlans = Plan::where('status', 1)
            ->where(function ($q) use ($storeId) {
                $q->whereNull('store_id')
                    ->orWhere('store_id', $storeId);
            })
            ->get();
        $features = DB::table('subscription_modules')->where('status', 1)->get();
        $StoreConfig = StoreConfig::where('store_id', $storeId)->first();
        $sub_modules = SubModule::all();
        $subscriptions = VendorSubscription::with('plan')->where('vendor_id', $storeId)
            ->where('plan_expiry', '>', now())->get();


        // For hospital stores, pre-select tier from active subscription or bed_count
        $store = \App\Models\Store::find($storeId);
        $bedTier = null;
        if ($store && $store->module_id == 6) {
            // Try active subscription tier first
            $activeSub = $subscriptions->whereNotNull('bed_tier_id')->first();
            if ($activeSub) {
                $bedTier = \App\Models\HospitalBedTier::find($activeSub->bed_tier_id);
            }
            // Fallback to bed_count match
            if (!$bedTier && $store->bed_count > 0) {
                $bedTier = \App\Models\HospitalBedTier::forBedCount($store->bed_count);
            }
        }

        return view('vendor-views.subscriptions.index', compact('allPlans', 'StoreConfig', 'features', 'sub_modules', 'subscriptions', 'bedTier', 'store'));
    }
    public function staff_change_password(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', 'min:6'],
        ]);

        $employee = auth('vendor_employee')->user();

        if (!$employee || !\Illuminate\Support\Facades\Hash::check($request->current_password, $employee->password)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect.'], 422);
        }

        $employee->password = bcrypt($request->password);
        $employee->save();

        return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

    public function settings_password_update(Request $request)
    {
        $request->validate([
            'password' => ['required', 'same:confirm_password', Password::min(8)->mixedCase()->letters()->numbers()->symbols()],
            'confirm_password' => 'required',
        ]);

        $seller = auth('vendor')->check() ? Helpers::get_vendor_data() : auth('vendor_employee')->user();
        $seller->password = bcrypt($request['password']);
        $seller->save();
        Toastr::success(translate('messages.vendor_pasword_updated_successfully'));
        return back();
    }

    public function bank_update(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|max:191',
            'branch' => 'required|max:191',
            'holder_name' => 'required|max:191',
            'account_no' => 'required|max:191',
        ]);
        $bank = Helpers::get_vendor_data();
        $bank->bank_name = $request->bank_name;
        $bank->branch = $request->branch;
        $bank->holder_name = $request->holder_name;
        $bank->account_no = $request->account_no;
        $bank->save();
        Toastr::success(translate('messages.bank_info_updated_successfully'));
        return redirect()->route('vendor.profile.bankView');
    }

    public function bank_edit()
    {
        $data = Helpers::get_vendor_data();
        return view('vendor-views.profile.bankEdit', compact('data'));
    }

    public function bank_delete()
    {
        $data = Helpers::get_vendor_data();
        $data->bank_name = null;
        $data->branch = null;
        $data->holder_name = null;
        $data->account_no = null;
        $data->save();
        Toastr::success(translate('messages.bank_info_updated_successfully'));
        return redirect()->route('vendor.profile.bankView');
    }
}
