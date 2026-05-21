<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\InvoiceItem;
use App\Models\LeadSubscription;
use App\Models\LeadSubscriptionPlan;
use App\Models\ManualInvoice;
use App\Models\Store;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $plans = LeadSubscriptionPlan::orderBy('type')->orderBy('name')->get();
        $zones = DB::table('zones')->select('id', 'name')->get();
        $categories = DB::table('categories')->where('status', 1)->select('id', 'name')->get();

        $subscriptions = LeadSubscription::with('store', 'plan')
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        $stores = Store::select('id', 'name')->where('status', 1)->orderBy('name')->get();

        $nextInvoiceId = Helpers::generateInvoiceIdAdmin(6, false);
        $billNum = [
            'prefix' => substr($nextInvoiceId, 0, strrpos($nextInvoiceId, '_') + 1),
            'serial' => (int) substr($nextInvoiceId, strrpos($nextInvoiceId, '_') + 1),
        ];

        return view('admin-views.service.lead-subscriptions', compact('plans', 'subscriptions', 'zones', 'categories', 'stores', 'billNum'));
    }

    public function storePlan(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'type'          => 'required|in:shared,dedicated',
            'price'         => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'zone_id'       => 'nullable|integer',
            'category_id'   => 'nullable|integer',
        ]);

        LeadSubscriptionPlan::create($request->only('name', 'type', 'price', 'duration_days', 'zone_id', 'category_id'));
        Toastr::success('Plan created');
        return back();
    }

    public function updatePlan(Request $request, $id)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'price'         => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'status'        => 'required|in:0,1',
        ]);

        LeadSubscriptionPlan::findOrFail($id)->update($request->only('name', 'price', 'duration_days', 'status'));
        Toastr::success('Plan updated');
        return back();
    }

    public function destroyPlan($id)
    {
        LeadSubscriptionPlan::findOrFail($id)->delete();
        Toastr::success('Plan deleted');
        return back();
    }

    public function grant(Request $request)
    {
        $request->validate([
            'store_id'       => 'required|integer',
            'type'           => 'required|in:shared,dedicated',
            'starts_at'      => 'required|date',
            'expires_at'     => 'required|date|after_or_equal:starts_at',
            'zone_id'        => 'nullable|integer',
            'category_id'    => 'nullable|integer',
            'plan_id'        => 'nullable|integer',
            'invoice_date'   => 'nullable|date',
            'invoice_serial' => 'nullable|integer|min:1',
        ]);

        $subscription = LeadSubscription::create($request->only(
            'store_id', 'plan_id', 'type', 'zone_id', 'category_id', 'starts_at', 'expires_at'
        ));

        if ($request->filled('invoice_serial')) {
            $this->createSubscriptionInvoice($subscription, $request);
        }

        Toastr::success('Subscription granted');
        return back();
    }

    private function createSubscriptionInvoice(LeadSubscription $subscription, Request $request): void
    {
        $invoiceDate = $request->invoice_date ?? now()->toDateString();
        $serial      = (int) $request->invoice_serial;

        $prefix      = Helpers::generateInvoiceIdAdmin(6, false);
        $prefixPart  = substr($prefix, 0, strrpos($prefix, '_') + 1);
        $invoiceId   = $prefixPart . $serial;

        $date        = Carbon::parse($invoiceDate);
        $fyStartYear = $date->month >= 4 ? $date->year : $date->year - 1;
        $year        = substr($fyStartYear, -2) . '-' . substr($fyStartYear + 1, -2);

        $plan        = $subscription->plan;
        $amount      = $plan ? $plan->price : 0;
        $planName    = $plan ? $plan->name : 'Lead Subscription';

        $invoice = ManualInvoice::create([
            'bill_to'          => $subscription->store_id,
            'bill_to_type'     => 'vendor',
            'user_type'        => 'store_vendor',
            'invoice_id'       => $invoiceId,
            'invoice_serial'   => $serial,
            'type'             => 'manual',
            'module_id'        => 6,
            'total_amount'     => $amount,
            'subtotal_amount'  => $amount,
            'payment_method'   => 'wallet',
            'vendor_id'        => 0,
            'invoice_date'     => $invoiceDate,
            'payment_status'   => 'Paid',
            'tax_type'         => 'non-gst',
            'financial_year'   => $year,
            'generated_by'     => 'admin',
        ]);

        InvoiceItem::create([
            'manual_invoice_id' => $invoice->id,
            'name'              => ucfirst($subscription->type) . ' Lead Subscription — ' . $planName,
            'qty'               => 1,
            'price'             => $amount,
            'tax'               => 0,
            'gst_status'        => 'excluding',
        ]);
    }

    public function revoke($id)
    {
        LeadSubscription::findOrFail($id)->delete();
        Toastr::success('Subscription revoked');
        return back();
    }
}
