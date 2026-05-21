<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\LeadSubscription;
use App\Models\LeadSubscriptionPlan;
use App\Models\StoreWallet;
use App\CentralLogics\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class LeadSubscriptionController extends Controller
{
    public function index()
    {
        $storeId = Helpers::get_store_id();
        $vendorId = auth('vendor')->id();

        $plans = LeadSubscriptionPlan::where('status', 1)->orderBy('type')->orderBy('price')->get();
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

        $existing = LeadSubscription::where('store_id', $storeId)
            ->where('type', $plan->type)
            ->where('expires_at', '>=', now()->toDateString())
            ->orderByDesc('expires_at')
            ->first();

        $startsAt  = $existing ? $existing->expires_at->addDay() : now();
        $expiresAt = $startsAt->copy()->addDays($plan->duration_days - 1);

        LeadSubscription::create([
            'store_id'    => $storeId,
            'plan_id'     => $plan->id,
            'type'        => $plan->type,
            'zone_id'     => $plan->zone_id,
            'category_id' => $plan->category_id,
            'starts_at'   => $startsAt->toDateString(),
            'expires_at'  => $expiresAt->toDateString(),
        ]);

        Toastr::success(ucfirst($plan->type) . ' lead subscription activated until ' . $expiresAt->format('d M Y'));
        return back();
    }
}
