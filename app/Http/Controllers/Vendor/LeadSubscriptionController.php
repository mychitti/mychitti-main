<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Library\Payer;
use App\Library\Receiver;
use App\Library\Payment as PaymentInfo;
use App\Models\AccountTransaction;
use App\Models\BusinessSetting;
use App\Models\LeadSubscription;
use App\Models\LeadSubscriptionPlan;
use App\Models\StoreWallet;
use App\Models\TmpLeadSubscription;
use App\CentralLogics\Helpers;
use App\Traits\Payment;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class LeadSubscriptionController extends Controller
{
    use Payment;

    public function index()
    {
        $storeId = Helpers::get_store_id();
        $vendorId = auth('vendor')->id();
        $storeZoneId = \App\Models\Store::where('id', $storeId)->value('zone_id');

        $plans = LeadSubscriptionPlan::where('status', 1)
            ->where(function ($q) use ($storeZoneId) {
                $q->whereNull('zone_id')
                    ->orWhere('zone_id', $storeZoneId);
            })
            ->orderBy('type')
            ->orderBy('price')
            ->get();
        $subscriptions = LeadSubscription::where('store_id', $storeId)->orderByDesc('expires_at')->get();
        $wallet = StoreWallet::where('vendor_id', $vendorId)->first();
        $walletBalance = $wallet ? ($wallet->total_earning - $wallet->total_withdrawn) : 0;

        return view('vendor-views.service.lead-subscription', compact('plans', 'subscriptions', 'walletBalance'));
    }

    public function buy(Request $request)
    {
        $request->validate(['plan_id' => 'required|integer|exists:lead_subscription_plans,id']);

        $storeId  = Helpers::get_store_id();
        $vendorId = auth('vendor')->id();
        $plan     = LeadSubscriptionPlan::findOrFail($request->plan_id);

        $wallet = StoreWallet::where('vendor_id', $vendorId)->first();
        $balance = $wallet ? ($wallet->total_earning - $wallet->total_withdrawn) : 0;

        if ($balance < $plan->price) {
            Toastr::error('Insufficient wallet balance. Required: ' . _price($plan->price));
            return back();
        }

        $wallet->decrement('total_earning', $plan->price);
        $wallet->increment('total_withdrawn', $plan->price);
        $wallet->save();

        $transaction = new AccountTransaction();
        $transaction->current_balance = $wallet->total_earning - $wallet->total_withdrawn;
        $transaction->from_type  = 'store';
        $transaction->amount     = $plan->price;
        $transaction->from_id    = $vendorId;
        $transaction->method     = 'wallet';
        $transaction->action     = 'debit';
        $transaction->reason     = ucfirst($plan->type) . ' Lead Subscription — ' . $plan->name;
        $transaction->created_by = 'store';
        $transaction->save();

        $this->activateSubscription($storeId, $plan);

        Toastr::success(ucfirst($plan->type) . ' lead subscription activated.');
        return back();
    }

    public function initiate_gateway(Request $request)
    {
        $request->validate(['plan_id' => 'required|integer|exists:lead_subscription_plans,id']);

        $storeId = Helpers::get_store_id();
        $plan    = LeadSubscriptionPlan::findOrFail($request->plan_id);

        $tmp = TmpLeadSubscription::create([
            'store_id' => $storeId,
            'plan_id'  => $plan->id,
        ]);

        $currency = BusinessSetting::where('key', 'currency')->value('value');

        $additional_data = [
            'business_name' => BusinessSetting::where('key', 'business_name')->value('value'),
            'business_logo' => asset('storage/app/public/business') . '/' . BusinessSetting::where('key', 'logo')->value('value'),
        ];

        $vendor = \App\Models\Vendor::find(auth('vendor')->id());
        $payer  = new Payer($vendor['f_name'] . ' ' . $vendor['l_name'], $vendor['email'], $vendor['phone'], '');

        $domain = $request->getHost();
        $external_redirect_link = \Illuminate\Support\Str::contains($domain, 'staging')
            ? 'store-panel/service/lead-subscription'
            : 'service/lead-subscription';

        $payment_info = new PaymentInfo(
            success_hook: 'lead_subscription_success',
            failure_hook: 'lead_subscription_fail',
            currency_code: $currency,
            payment_method: 'razor_pay',
            payment_platform: 'web',
            payer_id: $storeId,
            receiver_id: 100,
            additional_data: $additional_data,
            payment_amount: $plan->price,
            external_redirect_link: $external_redirect_link,
            attribute: 'lead_subscription',
            attribute_id: $tmp->id
        );

        $receiver_info = new Receiver('Admin', 'example.png');
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return redirect()->to($redirect_link);
    }

    private function activateSubscription(int $storeId, LeadSubscriptionPlan $plan): LeadSubscription
    {
        $existing = LeadSubscription::where('store_id', $storeId)
            ->where('type', $plan->type)
            ->where('expires_at', '>=', now()->toDateString())
            ->orderByDesc('expires_at')
            ->first();

        $startsAt  = $existing ? $existing->expires_at->addDay() : now();
        $expiresAt = $startsAt->copy()->addDays($plan->duration_days - 1);

        return LeadSubscription::create([
            'store_id'    => $storeId,
            'plan_id'     => $plan->id,
            'type'        => $plan->type,
            'zone_id'     => $plan->zone_id,
            'category_id' => $plan->category_id,
            'starts_at'   => $startsAt->toDateString(),
            'expires_at'  => $expiresAt->toDateString(),
        ]);
    }
}
