<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\PlatformFee;
use App\Services\WhatsAppBilling;
use App\Services\WhatsAppRecurring;
use App\Services\WhatsAppService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin-side selling of the WhatsApp Business Platform: put a vendor on a plan, settle their
 * onboarding fee, add message-template slots and top up their AI tokens — all on the admin's
 * authority rather than a payment the vendor made themselves.
 *
 * Every action offers the same two ways to collect (see WhatsAppBilling::ADMIN_MODE_*):
 *   Billing — bill to store, which raises the GST tax invoice and posts it to the books.
 *   Retail  — no bill at all, for complimentary grants and money settled off-platform.
 *
 * The vendor's own screens (WhatsApp → Plan & Billing) are unchanged and keep working; anything
 * granted here shows up there, because both write the same wa_subscriptions / wa_token_wallets
 * rows and the same wa_billing_invoices history.
 */
class WhatsAppVendorBillingController extends Controller
{
    /**
     * Which of the one-time purchases the admin panel is offering. Set either to false to pull
     * the card without deleting it; the POST action is gated on the same flag, so a stale tab
     * cannot submit to a form that is no longer on screen.
     */
    const OFFER_SETUP_FEE = true;
    const OFFER_TOKEN_TOPUP = true;

    /** Every store with what it currently holds on the WhatsApp platform. */
    public function index(Request $request)
    {
        WhatsAppService::ensureStoreColumns();
        WhatsAppBilling::ensureTables();

        $search = $request->get('search');
        $status = $request->get('status');

        $stores = DB::table('stores')
            ->leftJoin('wa_subscriptions as ws', 'ws.store_id', '=', 'stores.id')
            ->leftJoin('store_wallets as sw', 'sw.vendor_id', '=', 'stores.vendor_id')
            ->when($search, fn($q) => $q->where(function ($w) use ($search) {
                $w->where('stores.name', 'like', "%{$search}%")
                    ->orWhere('stores.phone', 'like', "%{$search}%");
            }))
            ->when($status === 'active', fn($q) => $q->whereNotNull('ws.current_period_end')
                ->where('ws.current_period_end', '>=', now()->toDateString()))
            ->when($status === 'inactive', fn($q) => $q->where(function ($w) {
                $w->whereNull('ws.current_period_end')->orWhere('ws.current_period_end', '<', now()->toDateString());
            }))
            ->when($status === 'onboarded', fn($q) => $q->where('ws.setup_fee_paid', 1))
            ->select(
                'stores.id',
                'stores.name',
                'stores.business_type',
                'stores.wa_enabled',
                'ws.plan',
                'ws.status as sub_status',
                'ws.current_period_end',
                'ws.setup_fee_paid',
                'ws.extra_template_slots',
                'ws.account_manager',
                DB::raw('COALESCE(sw.total_earning, 0) as wallet_balance')
            )
            ->orderBy('stores.name')
            ->paginate(20)
            ->appends($request->query());

        return view('admin-views.business-settings.whatsapp-vendor-billing', [
            'stores' => $stores,
            'search' => $search,
            'status' => $status,
            'plans'  => WhatsAppBilling::plans(),
        ]);
    }

    /** One store: what it holds, what each action costs, and the charge history. */
    public function show(Request $request, $storeId)
    {
        WhatsAppService::ensureStoreColumns();
        WhatsAppBilling::ensureTables();

        $store = Store::find($storeId);
        if (!$store) {
            Toastr::error(translate('Store not found.'));
            return redirect()->route('admin.business-settings.third-party.whatsapp-vendor-billing');
        }

        $storeId     = (int) $store->id;
        $currentPlan = WhatsAppBilling::storePlan($storeId);

        PlatformFee::ensureColumns();
        $waiver = PlatformFee::forStore($storeId);

        return view('admin-views.business-settings.whatsapp-vendor-billing-store', [
            'waiver'       => $waiver,
            'waiverLive'   => PlatformFee::isLive($waiver),
            'freeGrant'    => WhatsAppBilling::freeGrant($storeId),
            'store'        => $store,
            'subscription' => WhatsAppBilling::subscription($storeId),
            'active'       => WhatsAppBilling::isActive($storeId),
            'hasPlan'      => WhatsAppBilling::hasPlan($storeId),
            'setupPaid'    => WhatsAppBilling::setupFeePaid($storeId),
            'plans'        => WhatsAppBilling::plans(),
            'currentPlan'  => $currentPlan,
            'planMeta'     => WhatsAppBilling::plan($currentPlan),
            'gatewayBilled' => WhatsAppRecurring::isGatewayBilled($storeId),
            'tokens'       => [
                'in'  => WhatsAppBilling::tokenBalance($storeId, WhatsAppBilling::DIR_IN),
                'out' => WhatsAppBilling::tokenBalance($storeId, WhatsAppBilling::DIR_OUT),
            ],
            'allowance'    => WhatsAppBilling::templateAllowance($storeId),
            'included'     => WhatsAppBilling::includedTemplates(),
            'walletBalance' => WhatsAppBilling::walletBalance($storeId),
            'invoices'     => WhatsAppBilling::invoices($storeId, 30),
            'methods'      => WhatsAppBilling::ADMIN_METHODS,
            'offerSetupFee' => self::OFFER_SETUP_FEE,
            'offerTokens'   => self::OFFER_TOKEN_TOPUP,
            'pricing'      => [
                'setup'         => WhatsAppBilling::setupFee(),
                'manager'       => WhatsAppBilling::accountManagerFee(),
                'template_slot' => WhatsAppBilling::extraTemplateFee(),
                'topup_in'      => WhatsAppBilling::topupPerMillion(WhatsAppBilling::DIR_IN),
                'topup_out'     => WhatsAppBilling::topupPerMillion(WhatsAppBilling::DIR_OUT),
                'gst'           => WhatsAppBilling::gstPercent(),
            ],
        ]);
    }

    /** Put the store on a plan, or extend the one it is on, for a number of whole months. */
    public function plan(Request $request, $storeId)
    {
        // Plans are only ever GIVEN from the admin panel — a vendor who is paying subscribes
        // themselves through the gateway, so there is nothing to collect here and no `paid`
        // grant to post. adminActivate() still supports it; the panel just does not offer it.
        $request->validate([
            'plan'            => 'required|in:' . implode(',', array_keys(WhatsAppBilling::PLANS)),
            'months'          => 'required|integer|min:1|max:36',
            'account_manager' => 'nullable|boolean',
            'grant'           => 'required|in:' . implode(',', WhatsAppBilling::FREE_GRANTS),
            'note'            => 'nullable|string|max:190',
        ]);

        $storeId = $this->storeIdOrFail($storeId);
        if (!$storeId) {
            return back();
        }

        // A Razorpay mandate bills this store on its own schedule. Extending the period by hand
        // would leave the card being debited for months the admin has already given away.
        if (WhatsAppRecurring::isGatewayBilled($storeId) && !$request->boolean('override_mandate')) {
            Toastr::error(translate('This store is on a Razorpay auto-debit mandate. Cancel the mandate first, or tick "Override" to grant anyway.'));
            return back();
        }

        $result = WhatsAppBilling::adminActivate(
            $storeId,
            $request->plan,
            $request->boolean('account_manager'),
            (int) $request->months,
            ['note' => $request->input('note')],
            $request->grant
        );

        return $this->flash($result);
    }

    /** Record the one-time onboarding fee as settled. */
    public function setupFee(Request $request, $storeId)
    {
        if (!self::OFFER_SETUP_FEE) {
            abort(404);
        }

        $request->validate($this->termRules());

        $storeId = $this->storeIdOrFail($storeId);
        if (!$storeId) {
            return back();
        }

        return $this->flash(WhatsAppBilling::adminSetupFee($storeId, $this->terms($request)));
    }

    /** Add message-template slots beyond the quota included in the plan. */
    public function templateSlots(Request $request, $storeId)
    {
        $request->validate(['slots' => 'required|integer|min:1|max:50'] + $this->termRules());

        $storeId = $this->storeIdOrFail($storeId);
        if (!$storeId) {
            return back();
        }

        return $this->flash(WhatsAppBilling::adminTemplateSlots($storeId, (int) $request->slots, $this->terms($request)));
    }

    /** Top up the store's AI token pool, one direction at a time. */
    public function tokens(Request $request, $storeId)
    {
        if (!self::OFFER_TOKEN_TOPUP) {
            abort(404);
        }

        $request->validate([
            'direction' => 'required|in:in,out',
            'millions'  => 'required|integer|min:1|max:50',
        ] + $this->termRules());

        $storeId = $this->storeIdOrFail($storeId);
        if (!$storeId) {
            return back();
        }

        return $this->flash(WhatsAppBilling::adminTokens(
            $storeId,
            $request->direction,
            (int) $request->millions,
            $this->terms($request)
        ));
    }

    /**
     * Waive the monthly platform fee for this store, or put it back on the normal fee.
     *
     * Nothing to do with WhatsApp — it lives on this screen because this is the one place an
     * admin sets what a vendor pays. The fee itself is taken by `platform-fee:deduct` on the 1st.
     */
    public function platformFee(Request $request, $storeId)
    {
        $request->validate([
            'action' => 'required|in:grant,revoke',
            'type'   => 'required_if:action,grant|in:trial,lifetime',
            'until'  => 'nullable|date',
            'note'   => 'nullable|string|max:190',
        ]);

        $storeId = $this->storeIdOrFail($storeId);
        if (!$storeId) {
            return back();
        }

        return $this->flash($request->action === 'revoke'
            ? PlatformFee::revoke($storeId)
            : PlatformFee::grant($storeId, $request->type, $request->until, $request->note));
    }

    /**
     * Stop the subscription renewing. The paid period is honoured — this is the same cancel the
     * vendor can do themselves, and it cancels the Razorpay mandate first when there is one, or
     * the card goes on being debited for a subscription everyone believes is closed.
     */
    public function cancel(Request $request, $storeId)
    {
        $storeId = $this->storeIdOrFail($storeId);
        if (!$storeId) {
            return back();
        }

        if (WhatsAppRecurring::isGatewayBilled($storeId)) {
            $mandate = WhatsAppRecurring::cancel($storeId);
            if (!$mandate['success']) {
                Toastr::error($mandate['message']);
                return back();
            }
        }

        return $this->flash(WhatsAppBilling::cancel($storeId));
    }

    /* ------------------------------------------------------------------ helpers */

    /** The collection fields every action form carries. */
    private function termRules(): array
    {
        return [
            'mode'   => 'required|in:billing,retail',
            'amount' => 'nullable|numeric|min:0',
            'method' => 'nullable|string|max:30',
            'status' => 'nullable|in:Paid,Unpaid',
            'note'   => 'nullable|string|max:190',
        ];
    }

    private function terms(Request $request): array
    {
        return [
            'mode'   => $request->input('mode'),
            'amount' => $request->input('amount'),
            'method' => $request->input('method'),
            'status' => $request->input('status'),
            'note'   => $request->input('note'),
        ];
    }

    private function storeIdOrFail($storeId): ?int
    {
        $id = (int) DB::table('stores')->where('id', $storeId)->value('id');
        if (!$id) {
            Toastr::error(translate('Store not found.'));
            return null;
        }
        return $id;
    }

    private function flash(array $result)
    {
        $result['success'] ? Toastr::success($result['message']) : Toastr::error($result['message']);
        return back();
    }
}
