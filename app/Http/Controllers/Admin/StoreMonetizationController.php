<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\PosToken;
use App\Models\AcceptedServiceRequest;
use App\Models\VendorEmployee;
use App\Models\StoreWallet;
use App\Models\VendorSubscription;
use App\Models\StoreCustomer;
use App\Models\Project;
use App\Models\StoreTask;
use App\Models\Quotation;
use App\Models\InventoryItem;
use App\Models\OfferBanner;
use App\Models\TemplatePurchase;
use App\Models\Item;
use App\Models\StoreAccount;
use App\Models\StoreVoucher;
use App\CentralLogics\Helpers;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StoreMonetizationController extends Controller
{
    public function dashboard(Request $request)
    {
        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range = Helpers::calculatePresetDates($preset, $custom);
        $dateRange = [$range['start'], $range['end']];

        // Platform Stats
        $totalStores = Store::count();
        $activeStores = Store::where('status', 1)->count();

        $totalPosTokens = PosToken::whereBetween('created_at', $dateRange)
            ->where('payment_status', '!=', 'cancelled')
            ->count();
        $totalPosRevenue = PosToken::whereBetween('created_at', $dateRange)
            ->where('payment_status', '!=', 'cancelled')
            ->sum('total');

        $totalLeads = ServiceRequest::whereBetween('created_at', $dateRange)->count();
        $completedLeads = AcceptedServiceRequest::whereBetween('created_at', $dateRange)
            ->where('current_status', 'Completed')
            ->count();

        $totalStaff = VendorEmployee::count();
        $totalWalletBalance = StoreWallet::sum('total_earning');

        // Additional module stats (filtered by date range)
        $totalClients = StoreCustomer::whereBetween('created_at', $dateRange)->count();
        $totalProjects = Project::whereBetween('created_at', $dateRange)->count();
        $totalTasks = StoreTask::whereBetween('created_at', $dateRange)->count();
        $totalInventoryItems = InventoryItem::whereBetween('created_at', $dateRange)->count();
        // $totalBanners = OfferBanner::whereBetween('created_at', $dateRange)->count();
        $totalTemplatePurchases = TemplatePurchase::whereBetween('created_at', $dateRange)->count();
        // $totalServices = Item::where('store_id', '>', 0)->whereBetween('created_at', $dateRange)->count();
        $totalQuotations = DB::table('quotations')->whereBetween('created_at', $dateRange)->count();
        // $totalAccounts = StoreAccount::whereBetween('created_at', $dateRange)->count();
        $totalVouchers = StoreVoucher::whereBetween('created_at', $dateRange)->count();
        $totalAdsPosted = DB::table('google_ads')->whereBetween('created_at', $dateRange)->count();

        $platformStats = [
            'total_stores' => $totalStores,
            'active_stores' => $activeStores,
            'total_pos_tokens' => $totalPosTokens,
            'total_pos_revenue' => $totalPosRevenue,
            'total_leads' => $totalLeads,
            'completed_leads' => $completedLeads,
            'total_staff' => $totalStaff,
            'total_wallet_balance' => $totalWalletBalance,
            'total_clients' => $totalClients,
            'total_projects' => $totalProjects,
            'total_tasks' => $totalTasks,
            'total_inventory_items' => $totalInventoryItems,
            // 'total_banners' => $totalBanners,
            'total_template_purchases' => $totalTemplatePurchases,
            // 'total_services' => $totalServices,
            'total_quotations' => $totalQuotations,
            // 'total_accounts' => $totalAccounts,
            'total_vouchers' => $totalVouchers,
            'total_ads_posted' => $totalAdsPosted,
        ];

        // Top Stores - Highest POS Usage
        $topPosByUsage = DB::table('pos_tokens')
            ->select('store_id', DB::raw('COUNT(*) as token_count'), DB::raw('SUM(total) as total_revenue'))
            ->where('payment_status', '!=', 'cancelled')
            ->whereBetween('created_at', $dateRange)
            ->groupBy('store_id')
            ->orderByDesc('token_count')
            ->limit(5)
            ->get();
        $topPosByUsage = $this->attachStoreNames($topPosByUsage, 'store_id');

        // Top Stores - Highest POS Revenue
        $topByRevenue = DB::table('pos_tokens')
            ->select('store_id', DB::raw('COUNT(*) as token_count'), DB::raw('SUM(total) as total_revenue'))
            ->where('payment_status', '!=', 'cancelled')
            ->whereBetween('created_at', $dateRange)
            ->groupBy('store_id')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();
        $topByRevenue = $this->attachStoreNames($topByRevenue, 'store_id');

        // Top Stores - Highest Bills Revenue
        // Manual invoices
        $manualBills = DB::table('manual_invoices')
            ->select('vendor_id', DB::raw('SUM(total_amount) as revenue'))
            ->where('payment_status', 'Paid')
            ->whereBetween(DB::raw('COALESCE(invoice_date, created_at)'), $dateRange)
            ->groupBy('vendor_id')
            ->get();


        // Service invoices
        $serviceBills = DB::table('service_invoices')
            ->select('vendor_id', DB::raw('SUM(total_amount) as revenue'))
            ->where('payment_status', 'Paid')
            ->whereBetween(DB::raw('COALESCE(invoice_date, created_at)'), $dateRange)
            ->groupBy('vendor_id')
            ->get();


        // Merge + combine revenue per vendor
        $topByBillRevenue = $manualBills
            ->merge($serviceBills)
            ->groupBy('vendor_id')
            ->map(function ($items) {
                return (object)[
                    'vendor_id' => $items->first()->vendor_id,
                    'total_revenue' => $items->sum('revenue')
                ];
            })
            ->sortByDesc('total_revenue')
            ->take(5)
            ->values();

        $topByBillRevenue = $this->attachStoreNames($topByBillRevenue, 'vendor_id');
// prx($topByBillRevenue);
        // Top Stores - Most Staff
        $topByStaff = DB::table('vendor_employees')
            ->select('store_id', DB::raw('COUNT(*) as staff_count'))
            ->groupBy('store_id')
            ->orderByDesc('staff_count')
            ->limit(5)
            ->get();
        $topByStaff = $this->attachStoreNames($topByStaff, 'store_id');

        // Top Stores - Most Leads
        $topByLeads = DB::table('accepted_service_requests')
            ->select('vendor_id as store_id', DB::raw('COUNT(*) as lead_count'))
            ->whereBetween('created_at', $dateRange)
            ->groupBy('vendor_id')
            ->orderByDesc('lead_count')
            ->limit(5)
            ->get();
        $topByLeads = $this->attachStoreNames($topByLeads, 'store_id');

        // Top Stores - Highest Wallet Balance
        $topByWallet = DB::table('store_wallets')
            ->join('stores', 'stores.vendor_id', '=', 'store_wallets.vendor_id')
            ->select('stores.id as store_id', 'stores.name as store_name', 'store_wallets.total_earning')
            ->orderByDesc('store_wallets.total_earning')
            ->limit(5)
            ->get();

        // Top Stores - Most Clients
        $topByClients = DB::table('store_customers')
            ->select('store_id', DB::raw('COUNT(*) as client_count'))
            ->groupBy('store_id')
            ->orderByDesc('client_count')
            ->limit(5)
            ->get();
        $topByClients = $this->attachStoreNames($topByClients, 'store_id');

        // Top Stores - Most Projects
        $topByProjects = DB::table('projects')
            ->select('vendor_id as store_id', DB::raw('COUNT(*) as project_count'))
            ->groupBy('vendor_id')
            ->orderByDesc('project_count')
            ->limit(5)
            ->get();
        $topByProjects = $this->attachStoreNames($topByProjects, 'store_id');

        // Top Stores - Most Inventory Items
        $topByInventory = DB::table('inventory_items')
            ->select('store_id', DB::raw('COUNT(*) as item_count'))
            ->groupBy('store_id')
            ->orderByDesc('item_count')
            ->limit(5)
            ->get();
        $topByInventory = $this->attachStoreNames($topByInventory, 'store_id');

        // Build store list with aggregated metrics
        $start = $dateRange[0];
        $end = $dateRange[1];
        $stores = Store::select('stores.*')
            ->leftJoin(DB::raw("(SELECT store_id, COUNT(*) as pos_count, SUM(total) as pos_revenue FROM pos_tokens WHERE payment_status != 'cancelled' AND created_at BETWEEN '{$start}' AND '{$end}' GROUP BY store_id) as pos"), 'pos.store_id', '=', 'stores.id')
            ->leftJoin(DB::raw("(SELECT vendor_id as store_id, COUNT(*) as lead_count FROM accepted_service_requests WHERE created_at BETWEEN '{$start}' AND '{$end}' GROUP BY vendor_id) as leads"), 'leads.store_id', '=', 'stores.id')
            ->leftJoin(DB::raw("(SELECT store_id, COUNT(*) as staff_count FROM vendor_employees WHERE created_at BETWEEN '{$start}' AND '{$end}' GROUP BY store_id) as staff"), 'staff.store_id', '=', 'stores.id')
            ->leftJoin(DB::raw('(SELECT sw.vendor_id, sw.total_earning FROM store_wallets sw) as wallet'), 'wallet.vendor_id', '=', 'stores.vendor_id')
            ->leftJoin(DB::raw("(SELECT store_id, COUNT(*) as client_count FROM store_customers WHERE created_at BETWEEN '{$start}' AND '{$end}' GROUP BY store_id) as clients"), 'clients.store_id', '=', 'stores.id')
            ->leftJoin(DB::raw("(SELECT vendor_id as store_id, COUNT(*) as project_count FROM projects WHERE created_at BETWEEN '{$start}' AND '{$end}' GROUP BY vendor_id) as projects"), 'projects.store_id', '=', 'stores.id')
            ->leftJoin(DB::raw("(SELECT store_id, COUNT(*) as inventory_count FROM inventory_items WHERE created_at BETWEEN '{$start}' AND '{$end}' GROUP BY store_id) as inv"), 'inv.store_id', '=', 'stores.id')
            ->leftJoin(DB::raw("(SELECT store_id, COUNT(*) as service_count FROM items WHERE store_id > 0 AND created_at BETWEEN '{$start}' AND '{$end}' GROUP BY store_id) as svc"), 'svc.store_id', '=', 'stores.id')
            ->select([
                'stores.id',
                'stores.name',
                'stores.phone',
                'stores.status',
                'stores.created_at',
                DB::raw('COALESCE(pos.pos_count, 0) as pos_count'),
                DB::raw('COALESCE(pos.pos_revenue, 0) as pos_revenue'),
                DB::raw('COALESCE(leads.lead_count, 0) as lead_count'),
                DB::raw('COALESCE(staff.staff_count, 0) as staff_count'),
                DB::raw('COALESCE(wallet.total_earning, 0) as wallet_balance'),
                DB::raw('COALESCE(clients.client_count, 0) as client_count'),
                DB::raw('COALESCE(projects.project_count, 0) as project_count'),
                DB::raw('COALESCE(inv.inventory_count, 0) as inventory_count'),
                DB::raw('COALESCE(svc.service_count, 0) as service_count'),
            ])
            ->when($request->search, function ($q) use ($request) {
                $q->where('stores.name', 'like', '%' . $request->search . '%');
            })
            ->orderByDesc('stores.id')
            ->paginate(25);

        return view('admin-views.store-monetization.dashboard', compact(
            'platformStats',
            'topPosByUsage',
            'topByRevenue',
            'topByStaff',
            'topByLeads',
            'topByWallet',
            'topByClients',
            'topByProjects',
            'topByInventory',
            'stores',
            'preset',
            'topByBillRevenue'
        ));
    }

    public function storeDetail(Request $request, $id)
    {
        $store = Store::with('vendor')->findOrFail($id);
        $tab = $request->get('tab', 'pos');
        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range = Helpers::calculatePresetDates($preset, $custom);
        $dateRange = [$range['start'], $range['end']];

        $data = ['store' => $store, 'tab' => $tab, 'preset' => $preset];

        // Overview stats - always loaded
        $data['wallet'] = StoreWallet::where('vendor_id', $store->vendor_id)->first();
        // $data['subscription'] = VendorSubscription::where('vendor_id', $store->vendor_id)
        //     ->latest()->first();

        // Core counts
        $data['staff_count'] = VendorEmployee::where('store_id', $id)->count();
        $data['total_pos'] = PosToken::where('store_id', $id)
            ->where('payment_status', '!=', 'cancelled')
            ->whereBetween('created_at', $dateRange)->count();
        $data['total_pos_revenue'] = PosToken::where('store_id', $id)
            ->where('payment_status', '!=', 'cancelled')
            ->whereBetween('created_at', $dateRange)->sum('total');
        $data['total_leads'] = AcceptedServiceRequest::where('vendor_id', $id)
            ->whereBetween('created_at', $dateRange)->count();
        $data['completed_leads'] = AcceptedServiceRequest::where('vendor_id', $id)
            ->whereBetween('created_at', $dateRange)
            ->whereNotNull('completed_at')->count();

        // Additional module counts (filtered by date range)
        $data['customer_count'] = StoreCustomer::where('store_id', $id)->where('user_type', 'customer')->whereBetween('created_at', $dateRange)->count();
        $data['vendor_count'] = StoreCustomer::where('store_id', $id)->where('user_type', 'vendor')->whereBetween('created_at', $dateRange)->count();
        $data['project_count'] = Project::where('vendor_id', $id)->whereBetween('created_at', $dateRange)->count();
        $data['task_count'] = StoreTask::where('store_id', $id)->whereBetween('created_at', $dateRange)->count();
        $data['inventory_count'] = InventoryItem::where('store_id', $id)->whereBetween('created_at', $dateRange)->count();
        // $data['banner_count'] = OfferBanner::where('store_id', $id)->count();
        $data['template_count'] = TemplatePurchase::where('vendor_id', $store->vendor_id)->whereBetween('created_at', $dateRange)->count();
        $data['service_count'] = count(\App\CentralLogics\Helpers::store_item_ids($store->id));
        $data['quotation_count'] = DB::table('quotations')->where('vendor_id', $id)->whereBetween('created_at', $dateRange)->count();
        // $data['account_count'] = StoreAccount::where('store_id', $id)->count();
        $data['store_voucher_count'] = StoreVoucher::where('store_id', $id)->whereBetween('created_at', $dateRange)->count();
        $data['store_voucher_amount'] = StoreVoucher::where('store_id', $id)->whereBetween('created_at', $dateRange)->sum('total_amount');
        $data['bills_count'] = DB::table('manual_invoices')->where('vendor_id', $id)->where('payment_status', 'Paid')->whereBetween(DB::raw('COALESCE(invoice_date, created_at)'), $dateRange)->count()
            + DB::table('service_invoices')->where('vendor_id', $id)->where('payment_status', 'Paid')->whereBetween(DB::raw('COALESCE(invoice_date, created_at)'), $dateRange)->count();
        $data['bills_amount'] = DB::table('manual_invoices')->where('vendor_id', $id)->where('payment_status', 'Paid')->whereBetween(DB::raw('COALESCE(invoice_date, created_at)'), $dateRange)->sum('total_amount')
            + DB::table('service_invoices')->where('vendor_id', $id)->where('payment_status', 'Paid')->whereBetween(DB::raw('COALESCE(invoice_date, created_at)'), $dateRange)->sum('total_amount');
        $data['ads_count'] = DB::table('notifications')->where('vendor_id', $id)->whereBetween('created_at', $dateRange)->count();

        // Enabled modules
        $data['subscriptions'] = VendorSubscription::with('plan')->where('vendor_id', $id)
            ->where('plan_expiry', '>', now())->get();

        // Tab-specific data
        if ($tab === 'leads') {
            $data['leads'] = AcceptedServiceRequest::where('vendor_id', $id)
                ->whereBetween('created_at', $dateRange)
                ->with('serviceRequest')
                ->latest()->paginate(20);
        }

        if ($tab === 'pos') {
            $data['pos_tokens'] = PosToken::where('store_id', $id)
                ->where('payment_status', '!=', 'cancelled')
                ->whereBetween('created_at', $dateRange)
                ->with('client')
                ->latest()->paginate(20);

            $data['pos_monthly'] = PosToken::where('store_id', $id)
                ->where('payment_status', '!=', 'cancelled')
                ->whereBetween('created_at', $dateRange)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total) as revenue')
                )
                ->groupBy('date')->orderBy('date')->get();
        }

        if ($tab === 'hr') {
            $data['employees'] = VendorEmployee::where('store_id', $id)
                ->latest()->paginate(20);
        }

        if ($tab === 'clients') {
            $data['clients'] = StoreCustomer::where('store_id', $id)
                ->latest()->paginate(20);
        }

        if ($tab === 'projects') {
            $data['projects'] = Project::where('vendor_id', $id)
                ->whereBetween('created_at', $dateRange)
                ->with('client')
                ->latest()->paginate(20);
        }
        if ($tab === 'tasks') {
            $data['tasks'] = StoreTask::where('store_id', $id)
            ->where('task_type', 'common')
                ->whereBetween('created_at', $dateRange)
                ->with('user')
                ->latest()->paginate(20);
        }

        if ($tab === 'inventory') {
            $data['inventory_items'] = InventoryItem::where('store_id', $id)
                ->whereBetween('created_at', $dateRange)
                ->latest()->paginate(20);
        }

        if ($tab === 'services') {
            $data['services'] = Item::withoutGlobalScopes()->whereIn('id', \App\CentralLogics\Helpers::store_item_ids($store->id))
                ->latest()->paginate(20);
        }

        return view('admin-views.store-monetization.store-detail', $data);
    }

    private function attachStoreNames($collection, $storeIdField)
    {
        $storeIds = $collection->pluck($storeIdField)->filter()->toArray();
        $stores = Store::whereIn('id', $storeIds)->pluck('name', 'id');
        return $collection->map(function ($item) use ($stores, $storeIdField) {
            $item->store_name = $stores[$item->{$storeIdField}] ?? 'Unknown';
            return $item;
        });
    }
}
