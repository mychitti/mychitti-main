<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateStoreMeta;
use App\Models\Contact;
use App\Models\Store;
use App\Models\SupportTicket;
use App\Models\Vendor;
use App\Models\VendorRequirement;
use App\Models\Zone;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * MC Vendorhub — the SaaS-only side of the platform.
 *
 * A store belongs here when it has opted out of the MyChitti consumer
 * marketplace (stores.show_in_mychitti = 0). It keeps its module_id and all of
 * its vendor-panel data; only its marketplace listing is switched off.
 */
class McVendorhubController extends Controller
{
    public function dashboard()
    {
        $storeIds = Store::withoutGlobalScopes()->hiddenFromMychitti()->pluck('id');

        $data = [
            'total_vendors' => $storeIds->count(),
            'active_vendors' => Store::withoutGlobalScopes()->hiddenFromMychitti()->where('status', 1)->count(),
            'pending_vendors' => Store::withoutGlobalScopes()->hiddenFromMychitti()->where('status', 0)->count(),
            'new_this_month' => Store::withoutGlobalScopes()->hiddenFromMychitti()
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'active_subscriptions' => DB::table('vendor_subscriptions')
                ->whereIn('vendor_id', $storeIds)
                ->where('plan_expiry', '>', now())->count(),
            'expired_subscriptions' => DB::table('vendor_subscriptions')
                ->whereIn('vendor_id', $storeIds)
                ->where('plan_expiry', '<=', now())->count(),
            'open_enquiries' => Contact::brand('mcvendorhub')->where('seen', 0)->count(),
        ];

        $recent_vendors = Store::withoutGlobalScopes()->hiddenFromMychitti()
            ->with(['vendor', 'module'])
            ->latest()->take(10)->get();

        $recent_enquiries = Contact::brand('mcvendorhub')->latest()->take(10)->get();

        $expiring_soon = DB::table('vendor_subscriptions')
            ->join('stores', 'stores.id', '=', 'vendor_subscriptions.vendor_id')
            ->leftJoin('plans', 'plans.id', '=', 'vendor_subscriptions.plan_id')
            ->whereIn('vendor_subscriptions.vendor_id', $storeIds)
            ->whereBetween('vendor_subscriptions.plan_expiry', [now(), now()->addDays(30)])
            ->select('stores.id as store_id', 'stores.name as store_name', 'plans.title as plan_name', 'vendor_subscriptions.plan_expiry')
            ->orderBy('vendor_subscriptions.plan_expiry')
            ->take(10)->get();

        return view('admin-views.mcvendorhub.dashboard', compact('data', 'recent_vendors', 'recent_enquiries', 'expiring_soon'));
    }

    public function vendors(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $vendors = Store::withoutGlobalScopes()->hiddenFromMychitti()
            ->with(['vendor', 'module', 'zone'])
            ->when($search, function ($query) use ($search) {
                $keys = explode(' ', $search);
                $query->where(function ($q) use ($keys) {
                    foreach ($keys as $value) {
                        $q->orWhere('name', 'like', "%{$value}%")
                            ->orWhere('phone', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%");
                    }
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(config('default_pagination'))
            ->appends($request->query());

        return view('admin-views.mcvendorhub.vendors', compact('vendors', 'search', 'status'));
    }

    /**
     * Admin override of the vendor's own opt-out switch.
     */
    public function vendor_listing_toggle(Request $request, $store_id)
    {
        $store = Store::withoutGlobalScopes()->findOrFail($store_id);
        $store->show_in_mychitti = $store->show_in_mychitti ? 0 : 1;
        $store->save();

        Toastr::success($store->show_in_mychitti
            ? translate('Store is now listed on MyChitti.')
            : translate('Store moved to MC Vendorhub. It no longer appears on MyChitti.'));

        return back();
    }

    public function subscriptions(Request $request)
    {
        $search = $request->input('search');
        $state = $request->input('state');

        $storeIds = Store::withoutGlobalScopes()->hiddenFromMychitti()->pluck('id');

        $subscriptions = DB::table('vendor_subscriptions')
            ->join('stores', 'stores.id', '=', 'vendor_subscriptions.vendor_id')
            ->leftJoin('plans', 'plans.id', '=', 'vendor_subscriptions.plan_id')
            ->whereIn('vendor_subscriptions.vendor_id', $storeIds)
            ->when($search, function ($query) use ($search) {
                $query->where('stores.name', 'like', "%{$search}%");
            })
            ->when($state === 'active', function ($query) {
                $query->where('vendor_subscriptions.plan_expiry', '>', now());
            })
            ->when($state === 'expired', function ($query) {
                $query->where('vendor_subscriptions.plan_expiry', '<=', now());
            })
            ->select(
                'vendor_subscriptions.id',
                'vendor_subscriptions.vendor_id as store_id',
                'vendor_subscriptions.plan_expiry',
                'vendor_subscriptions.purchased_at',
                'vendor_subscriptions.duration_count',
                'vendor_subscriptions.duration_type',
                'vendor_subscriptions.permitted_modules',
                'stores.name as store_name',
                'stores.phone as store_phone',
                'plans.title as plan_name'
            )
            ->orderByDesc('vendor_subscriptions.plan_expiry')
            ->paginate(config('default_pagination'))
            ->appends($request->query());

        return view('admin-views.mcvendorhub.subscriptions', compact('subscriptions', 'search', 'state'));
    }

    /**
     * What each MC Vendorhub store has actually bought from the platform: its active plan and
     * the paid WhatsApp "Lead Notifications" add-on, in one register. Subscriptions (above) is
     * the plan-only list; this is the fuller "everything we've sold this vendor" view.
     */
    public function sales(Request $request)
    {
        $search = $request->input('search');

        \App\Services\WhatsAppService::ensureReceivingTable();

        $stores = Store::withoutGlobalScopes()->hiddenFromMychitti()
            ->when($search, fn($q) => $q->where('stores.name', 'like', "%{$search}%"))
            ->with(['subscriptions' => function ($q) {
                $q->where('plan_expiry', '>', now())->with('plan');
            }])
            ->orderBy('stores.name')
            ->paginate(config('default_pagination'))
            ->appends($request->query());

        $addons = DB::table('wa_receiving_features')
            ->where('feature', 'leads')
            ->whereIn('store_id', $stores->pluck('id'))
            ->get()
            ->keyBy('store_id');

        return view('admin-views.mcvendorhub.sales', compact('stores', 'addons', 'search'));
    }

    /**
     * Support tickets raised by MC Vendorhub vendors — kept apart from the main admin support
     * queue the same way Vendors/Subscriptions/Sales/Enquiries are. A ticket lands here when the
     * vendor who raised it owns a store that opted out of MyChitti; end-customer and guest
     * tickets (no vendor_id) never belong to MC Vendorhub and stay out of this list entirely.
     */
    public function support(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $tickets = SupportTicket::with(['assignedTo', 'vendor'])
            ->whereHas('vendor.stores', function ($q) {
                $q->withoutGlobalScopes()->hiddenFromMychitti();
            })
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('ticket_id', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(config('default_pagination'))
            ->appends($request->query());

        return view('admin-views.mcvendorhub.support', compact('tickets', 'status', 'search'));
    }

    public function enquiries(Request $request)
    {
        $search = $request->input('search');

        $contacts = Contact::brand('mcvendorhub')
            ->when($search, function ($query) use ($search) {
                $keys = explode(' ', $search);
                $query->where(function ($q) use ($keys) {
                    foreach ($keys as $value) {
                        $q->orWhere('name', 'like', "%{$value}%")
                            ->orWhere('subject', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%")
                            ->orWhere('phone', 'like', "%{$value}%");
                    }
                });
            })
            ->orderByDesc('created_at')
            ->paginate(config('default_pagination'))
            ->appends($request->query());

        $requirements = VendorRequirement::with('store')
            ->whereHas('store', function ($q) {
                $q->hiddenFromMychitti();
            })->get();

        return view('admin-views.mcvendorhub.enquiries', compact('contacts', 'requirements', 'search'));
    }

    public function enquiry_view($id)
    {
        $contact = Contact::brand('mcvendorhub')->findOrFail($id);
        $contact->seen = 1;
        $contact->save();

        return view('admin-views.mcvendorhub.enquiry-view', compact('contact'));
    }

    public function enquiry_update(Request $request, $id)
    {
        $contact = Contact::brand('mcvendorhub')->findOrFail($id);
        $contact->feedback = $request->feedback;
        $contact->seen = 1;
        $contact->save();

        Toastr::success(translate('Enquiry updated.'));
        return back();
    }

    public function enquiry_delete($id)
    {
        $contact = Contact::brand('mcvendorhub')->findOrFail($id);
        $contact->delete();

        Toastr::success(translate('Enquiry removed.'));
        return back();
    }

    /**
     * Store SEO meta — for EVERY store, MyChitti-visible or MC Vendorhub-only alike. This is the
     * one place it's managed; the old admin/store-meta screen is unlinked and superseded by this.
     */
    public function seo(Request $request)
    {
        $counts = [
            'total'   => (clone $this->seoBaseQuery())->count(),
            'missing' => (clone $this->seoBaseQuery())->missingMeta()->count(),
        ];
        $counts['filled'] = $counts['total'] - $counts['missing'];

        $stores = $this->seoApplyFilters($request)
            ->paginate(config('default_pagination'))->withQueryString();

        $zones = Zone::orderBy('name')->get(['id', 'name']);

        return view('admin-views.mcvendorhub.seo', compact('stores', 'counts', 'zones'));
    }

    public function seo_generate($id)
    {
        $store = Store::withoutGlobalScopes()->findOrFail($id);

        $outcome = (new GenerateStoreMeta($store->id, true))->handle();

        if (!empty($outcome['success'])) {
            Toastr::success('Generated meta for ' . $store->name . '.');
        } else {
            Toastr::error('Generation failed: ' . ($outcome['message'] ?? 'unknown error'));
        }

        return back();
    }

    /** Stores generated per click — each one is an AI call, and this runs inside the request. */
    const SEO_BATCH_SIZE = 10;

    public function seo_generate_missing(Request $request)
    {
        $pending = $this->seoApplyFilters($request, true)->count();

        if ($pending === 0) {
            Toastr::info('Nothing to generate — every store matching this filter already has meta.');
            return back();
        }

        $ids = $this->seoApplyFilters($request, true)->limit(self::SEO_BATCH_SIZE)->pluck('stores.id');

        $ok = 0;
        $failed = 0;
        foreach ($ids as $id) {
            $outcome = (new GenerateStoreMeta((int) $id))->handle();
            empty($outcome['success']) ? $failed++ : $ok++;
        }

        $remaining = max(0, $pending - $ok);
        $message = "Generated {$ok} store(s)." . ($failed ? " {$failed} failed — see logs." : '');
        $message .= $remaining
            ? " {$remaining} still missing meta — click again for the next " . self::SEO_BATCH_SIZE . '.'
            : ' Every store matching this filter now has meta.';

        $failed ? Toastr::warning($message) : Toastr::success($message);
        return back();
    }

    private function seoBaseQuery()
    {
        return Store::withoutGlobalScopes();
    }

    private function seoApplyFilters(Request $request, bool $missingOnly = false)
    {
        $query = $this->seoBaseQuery()->select('stores.*');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('stores.name', 'like', "%{$search}%")
                    ->orWhere('stores.address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('zone_id')) {
            $query->where('stores.zone_id', (int) $request->input('zone_id'));
        }

        $status = $missingOnly ? 'missing' : $request->input('status');
        if ($status === 'missing') {
            $query->missingMeta();
        } elseif ($status === 'filled') {
            $query->whereNotNull('stores.meta_title')->where('stores.meta_title', '!=', '')
                ->whereNotNull('stores.meta_description')->where('stores.meta_description', '!=', '');
        }

        return $query->orderByDesc('stores.id');
    }
}
