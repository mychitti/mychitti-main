<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ManualInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Vendor AI Agent action proxy.
 * Called by the AI service droplet with X-Api-Key auth (ai.internal middleware).
 */
class VendorAgentController extends Controller
{
    public function action(Request $request, int $vendorId)
    {
        $module = strtolower(trim($request->input('module', '')));
        $action = strtolower(trim($request->input('action', '')));
        $data   = (array) ($request->input('data', []));

        if (!$module || !$action) {
            return response()->json(['success' => false, 'message' => 'module and action are required.'], 422);
        }

        $store = DB::table('stores')->where('vendor_id', $vendorId)->first();
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Vendor store not found.'], 404);
        }
        $storeId = $store->id;

        try {
            return match ($module) {
                'staff'      => $this->handleStaff($action, $data, $vendorId, $storeId),
                'inventory'  => $this->handleInventory($action, $data, $storeId),
                'invoice'    => $this->handleInvoice($action, $data, $vendorId, $storeId),
                'leads'      => $this->handleLeads($action, $data, $vendorId, $storeId),
                'crm'        => $this->handleCrm($action, $data, $vendorId),
                'attendance' => $this->handleAttendance($action, $data, $vendorId),
                'leave'      => $this->handleLeave($action, $data, $vendorId),
                'salary'     => $this->handleSalary($action, $data, $vendorId),
                'project'    => $this->handleProject($action, $data, $vendorId),
                'task'       => $this->handleTask($action, $data, $vendorId, $storeId),
                'calendar'   => $this->handleCalendar($action, $data, $storeId),
                'job_card'   => $this->handleJobCard($action, $data, $storeId),
                'store'      => $this->handleStore($action, $data, $vendorId, $storeId),
                'account'      => $this->handleAccount($action, $data, $storeId),
                'banking'      => $this->handleBanking($action, $data, $storeId),
                'assets'       => $this->handleAssets($action, $data, $storeId),
                'shifts'       => $this->handleShifts($action, $data, $storeId),
                'quotation'    => $this->handleQuotation($action, $data, $storeId),
                'clients'      => $this->handleClients($action, $data, $storeId),
                'orders'       => $this->handleOrders($action, $data, $storeId),
                'service'      => $this->handleService($action, $data, $storeId),
                'pos'          => $this->handlePos($action, $data, $storeId),
                'documents'    => $this->handleDocuments($action, $data, $storeId),
                'notification' => $this->handleNotification($action, $data, $storeId),
                'items'        => $this->handleItems($action, $data, $storeId),
                'campaign'     => $this->handleCampaign($action, $data, $storeId),
                'coupon'       => $this->handleCoupon($action, $data, $storeId),
                default        => response()->json(['success' => false, 'message' => "Unknown module: {$module}. Available: staff, inventory, invoice, leads, crm, attendance, leave, salary, project, task, calendar, job_card, store, account, banking, assets, shifts, quotation, clients, orders, service, pos, documents, notification, items, campaign, coupon."], 422),
            };
        } catch (\Throwable $e) {
            Log::error('VendorAgentController error', [
                'vendor_id' => $vendorId, 'module' => $module, 'action' => $action, 'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Action failed: ' . $e->getMessage()], 500);
        }
    }

    // ── Staff ─────────────────────────────────────────────────────────────

    private function handleStaff(string $action, array $data, int $vendorId, int $storeId)
    {
        switch ($action) {
            case 'list':
                $staff = DB::table('vendor_employees')
                    ->where('store_id', $storeId)->where('status', 1)
                    ->select('id', 'f_name', 'l_name', 'email', 'phone', 'current_salary', 'designation', 'department_id')
                    ->get();
                if ($staff->isEmpty()) return response()->json(['success' => true, 'message' => 'No active staff found.']);
                $lines = $staff->map(fn($s) => "- [{$s->id}] {$s->f_name} {$s->l_name} | {$s->email} | ₹{$s->current_salary}/mo" . ($s->designation ? " | {$s->designation}" : ''))->implode("\n");
                return response()->json(['success' => true, 'message' => "Staff list ({$staff->count()}):\n{$lines}"]);

            case 'get':
                $q = DB::table('vendor_employees')->where('store_id', $storeId)->where('status', 1);
                if (!empty($data['id']))   $q->where('id', $data['id']);
                if (!empty($data['name'])) $q->where(fn($x) => $x->where('f_name', 'like', "%{$data['name']}%")->orWhere('l_name', 'like', "%{$data['name']}%"));
                $s = $q->first();
                if (!$s) return response()->json(['success' => false, 'message' => 'Staff member not found.']);
                return response()->json(['success' => true, 'message' => "Staff: {$s->f_name} {$s->l_name} | Email: {$s->email} | Phone: {$s->phone} | Salary: ₹{$s->current_salary}/mo" . ($s->designation ? " | {$s->designation}" : '')]);

            case 'add':
                foreach (['f_name', 'email'] as $f) {
                    if (empty($data[$f])) return response()->json(['success' => false, 'message' => "Field '{$f}' is required."]);
                }
                if (DB::table('vendor_employees')->where('email', $data['email'])->exists()) {
                    return response()->json(['success' => false, 'message' => "Email {$data['email']} already exists."]);
                }
                $id = DB::table('vendor_employees')->insertGetId([
                    'vendor_id' => $vendorId, 'store_id' => $storeId,
                    'f_name' => $data['f_name'], 'l_name' => $data['l_name'] ?? '',
                    'email' => $data['email'], 'phone' => $data['phone'] ?? null,
                    'current_salary' => $data['salary'] ?? $data['current_salary'] ?? 0,
                    'base_salary'    => $data['salary'] ?? $data['base_salary'] ?? 0,
                    'designation'    => $data['designation'] ?? null,
                    'department_id'  => $data['department_id'] ?? null,
                    'employee_role_id' => $data['role_id'] ?? null,
                    'status' => 1, 'employee_type' => 'permanent',
                    'password' => bcrypt('changeme123'),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                return response()->json(['success' => true, 'message' => "Staff member {$data['f_name']} added. ID: #{$id}"]);

            case 'edit':
            case 'update':
                $id = $data['id'] ?? null;
                if (!$id) return response()->json(['success' => false, 'message' => 'Staff ID is required.']);
                unset($data['id']);
                if (isset($data['salary'])) { $data['current_salary'] = $data['salary']; $data['base_salary'] = $data['salary']; unset($data['salary']); }
                if (isset($data['role_id'])) { $data['employee_role_id'] = $data['role_id']; unset($data['role_id']); }
                $data['updated_at'] = now();
                $affected = DB::table('vendor_employees')->where('id', $id)->where('store_id', $storeId)->update($data);
                return response()->json(['success' => true, 'message' => $affected ? "Staff #{$id} updated." : "Staff #{$id} not found or no changes."]);

            case 'delete':
                $id = $data['id'] ?? null;
                if (!$id) return response()->json(['success' => false, 'message' => 'Staff ID is required.']);
                DB::table('vendor_employees')->where('id', $id)->where('store_id', $storeId)->update(['status' => 0, 'updated_at' => now()]);
                return response()->json(['success' => true, 'message' => "Staff #{$id} deactivated."]);
        }
        return response()->json(['success' => false, 'message' => "Unknown staff action: {$action}. Available: list, get, add, edit, delete."]);
    }

    // ── Inventory ─────────────────────────────────────────────────────────

    private function handleInventory(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $items = DB::table('inventory_items')->where('store_id', $storeId)
                    ->select('id', 'item_name', 'sku_id', 'stock', 'selling_price', 'landing_price', 'unit')
                    ->orderBy('item_name')->limit(50)->get();
                if ($items->isEmpty()) return response()->json(['success' => true, 'message' => 'No inventory items found.']);
                $lines = $items->map(fn($i) => "- [{$i->id}] {$i->item_name} | SKU: {$i->sku_id} | Stock: {$i->stock} {$i->unit} | ₹{$i->selling_price}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Inventory ({$items->count()}):\n{$lines}"]);

            case 'get':
            case 'stock':
                $q = DB::table('inventory_items')->where('store_id', $storeId);
                if (!empty($data['id']))   $q->where('id', $data['id']);
                if (!empty($data['name'])) $q->where('item_name', 'like', '%' . $data['name'] . '%');
                if (!empty($data['sku']))  $q->where('sku_id', $data['sku']);
                $item = $q->first();
                if (!$item) return response()->json(['success' => false, 'message' => 'Item not found.']);
                return response()->json(['success' => true, 'message' => "{$item->item_name} | Stock: {$item->stock} {$item->unit} | Sale: ₹{$item->selling_price} | Purchase: ₹{$item->landing_price}"]);

            case 'add':
                $name = $data['item_name'] ?? $data['name'] ?? null;
                if (!$name) return response()->json(['success' => false, 'message' => 'Item name is required.']);
                $id = DB::table('inventory_items')->insertGetId([
                    'store_id' => $storeId, 'item_name' => $name,
                    'sku_id' => $data['sku'] ?? $data['sku_id'] ?? null,
                    'stock' => $data['stock'] ?? 0,
                    'selling_price' => $data['selling_price'] ?? $data['sale_price'] ?? $data['price'] ?? 0,
                    'landing_price' => $data['landing_price'] ?? $data['purchase_price'] ?? 0,
                    'unit' => $data['unit'] ?? 'pcs', 'category_id' => $data['category_id'] ?? null,
                    'module_id' => 6, 'created_at' => now(), 'updated_at' => now(),
                ]);
                // A query-builder insert skips model events, so record the opening prices
                // explicitly or they are missing from the item's price history.
                _logItemPriceValues($id, $storeId, [
                    'purchase' => ['old' => null, 'new' => $data['landing_price'] ?? $data['purchase_price'] ?? 0],
                    'sell' => ['old' => null, 'new' => $data['selling_price'] ?? $data['sale_price'] ?? $data['price'] ?? 0],
                ], 'create');
                return response()->json(['success' => true, 'message' => "Item '{$name}' added. ID: #{$id}"]);

            case 'edit':
            case 'update':
                $id = $data['id'] ?? null;
                if (!$id) return response()->json(['success' => false, 'message' => 'Item ID is required.']);
                unset($data['id']);
                if (isset($data['price']))          { $data['selling_price'] = $data['price']; unset($data['price']); }
                if (isset($data['sale_price']))     { $data['selling_price'] = $data['sale_price']; unset($data['sale_price']); }
                if (isset($data['purchase_price'])) { $data['landing_price'] = $data['purchase_price']; unset($data['purchase_price']); }
                if (isset($data['name']))           { $data['item_name'] = $data['name']; unset($data['name']); }
                if (isset($data['sku']))            { $data['sku_id'] = $data['sku']; unset($data['sku']); }
                $data['updated_at'] = now();
                // Read the prices about to be overwritten — once the update lands the old
                // figures are gone, and a price history with no "was" is half a history.
                $priceBefore = DB::table('inventory_items')->where('id', $id)->where('store_id', $storeId)
                    ->first(['landing_price', 'selling_price']);
                DB::table('inventory_items')->where('id', $id)->where('store_id', $storeId)->update($data);
                if ($priceBefore && (array_key_exists('landing_price', $data) || array_key_exists('selling_price', $data))) {
                    $pricePairs = [];
                    if (array_key_exists('landing_price', $data)) {
                        $pricePairs['purchase'] = ['old' => $priceBefore->landing_price, 'new' => $data['landing_price']];
                    }
                    if (array_key_exists('selling_price', $data)) {
                        $pricePairs['sell'] = ['old' => $priceBefore->selling_price, 'new' => $data['selling_price']];
                    }
                    _logItemPriceValues($id, $storeId, $pricePairs, 'edit');
                }
                // A query-builder update skips model events, so re-derive stock_base explicitly
                // when this call touched stock or the unit (see InventoryItem::booted).
                if (array_key_exists('stock', $data) || array_key_exists('unit', $data)) {
                    $item = \App\Models\InventoryItem::where('id', $id)->where('store_id', $storeId)->first();
                    if ($item && \Illuminate\Support\Facades\Schema::hasColumn('inventory_items', 'stock_base')) {
                        [$base, $baseUnit] = _stockBaseFor($item->unit, $item->stock);
                        $item->stock_base = $base;
                        $item->base_unit  = $baseUnit;
                        $item->save();
                    }
                }
                return response()->json(['success' => true, 'message' => "Item #{$id} updated."]);

            case 'low_stock':
                $threshold = $data['threshold'] ?? 10;
                $items = DB::table('inventory_items')->where('store_id', $storeId)
                    ->where('stock', '<=', $threshold)->orderBy('stock')->limit(20)
                    ->get(['id', 'item_name', 'stock', 'unit']);
                if ($items->isEmpty()) return response()->json(['success' => true, 'message' => "No items with stock ≤ {$threshold}."]);
                $lines = $items->map(fn($i) => "- {$i->item_name}: {$i->stock} {$i->unit}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Low stock (≤{$threshold}):\n{$lines}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown inventory action: {$action}. Available: list, get, add, edit, low_stock."]);
    }

    // ── Invoice / Billing ─────────────────────────────────────────────────

    private function handleInvoice(string $action, array $data, int $vendorId, int $storeId)
    {
        switch ($action) {
            case 'list':
                $invoices = DB::table('manual_invoices')->where('vendor_id', $storeId)
                    ->orderByDesc('created_at')->limit(20)
                    ->get(['id', 'invoice_id', 'customer_name', 'total_amount', 'payment_status', 'created_at']);
                if ($invoices->isEmpty()) return response()->json(['success' => true, 'message' => 'No invoices found.']);
                $lines = $invoices->map(fn($i) => "- #{$i->invoice_id} | {$i->customer_name} | ₹{$i->total_amount} | {$i->payment_status} | " . \Carbon\Carbon::parse($i->created_at)->format('d M Y'))->implode("\n");
                return response()->json(['success' => true, 'message' => "Recent invoices ({$invoices->count()}):\n{$lines}"]);

            case 'get':
                $inv = null;
                if (!empty($data['id']))         $inv = DB::table('manual_invoices')->where('id', $data['id'])->where('vendor_id', $storeId)->first();
                if (!$inv && !empty($data['number'])) $inv = DB::table('manual_invoices')->where('invoice_id', $data['number'])->where('vendor_id', $storeId)->first();
                if (!$inv) return response()->json(['success' => false, 'message' => 'Invoice not found.']);
                $pdfLink = $inv->pdf ? ' | PDF: ' . asset('storage/invoice/' . $inv->pdf) : '';
                return response()->json(['success' => true, 'message' => "Invoice #{$inv->invoice_id} | Customer: {$inv->customer_name} | Total: ₹{$inv->total_amount} | Status: {$inv->payment_status}{$pdfLink}"]);

            case 'unpaid':
            case 'pending':
                $invoices = DB::table('manual_invoices')->where('vendor_id', $storeId)
                    ->whereIn('payment_status', ['Unpaid', 'unpaid', 'Pending', 'pending', 'Due', 'due'])
                    ->orderByDesc('created_at')->limit(20)
                    ->get(['id', 'invoice_id', 'customer_name', 'total_amount', 'created_at']);
                if ($invoices->isEmpty()) return response()->json(['success' => true, 'message' => 'No unpaid invoices.']);
                $total = $invoices->sum('total_amount');
                $lines = $invoices->map(fn($i) => "- #{$i->invoice_id} | {$i->customer_name} | ₹{$i->total_amount}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Unpaid (₹{$total} total):\n{$lines}"]);

            case 'add':
            case 'create':
                if (empty($data['customer_name'])) {
                    return response()->json(['success' => false, 'message' => 'customer_name is required.']);
                }
                $items = $data['items'] ?? [];
                if (empty($items)) {
                    if (!empty($data['item_name']) || !empty($data['item'])) {
                        $items = [[
                            'name'  => $data['item_name'] ?? $data['item'],
                            'price' => $data['price'] ?? $data['amount'] ?? 0,
                            'qty'   => $data['qty'] ?? $data['quantity'] ?? 1,
                            'unit'  => $data['unit'] ?? 'pcs',
                            'tax'   => $data['tax'] ?? 0,
                        ]];
                    } else {
                        return response()->json(['success' => false, 'message' => 'items array is required. Each item needs: name, price, qty.']);
                    }
                }

                // Generate invoice ID matching the real system format
                // NOTE: manual_invoices.vendor_id stores STORE ID (not vendor user ID)
                $storeName   = DB::table('stores')->where('id', $storeId)->value('name') ?? '';
                $storePrefix = substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $storeName)), 0, 3) ?: 'INV';
                $fyStartYear = now()->month >= 4 ? now()->year : now()->year - 1;
                $fy          = substr($fyStartYear, -2) . '-' . substr($fyStartYear + 1, -2);
                $serial      = ((int) DB::table('manual_invoices')->where('vendor_id', $storeId)->where('financial_year', $fy)->max('invoice_serial') ?? 0) + 1;
                $invoiceId   = $storePrefix . '_' . $serial;
                while (DB::table('manual_invoices')->where('financial_year', $fy)->where('invoice_id', $invoiceId)->exists()) {
                    $serial++;
                    $invoiceId = $storePrefix . '_' . $serial;
                }

                // Calculate totals
                $subtotal = 0;
                $taxTotal = 0;
                foreach ($items as &$item) {
                    $item['qty']        = (int)   ($item['qty']   ?? 1);
                    $item['price']      = (float) ($item['price'] ?? 0);
                    $item['tax']        = (float) ($item['tax']   ?? 0);
                    $item['total']      = round($item['qty'] * $item['price'], 2);
                    $item['tax_amount'] = round($item['total'] * $item['tax'] / 100, 2);
                    $subtotal += $item['total'];
                    $taxTotal += $item['tax_amount'];
                }
                unset($item);
                $totalAmount = round($subtotal + $taxTotal, 2);

                // Resolve or create store_customers record so bill_to is properly linked
                $customerName  = $data['customer_name'];
                $customerPhone = $data['phone'] ?? $data['customer_phone'] ?? null;
                $customerEmail = $data['email'] ?? $data['customer_email'] ?? null;
                $storeCustomer = DB::table('store_customers')
                    ->where('store_id', $storeId)
                    ->where('user_type', 'customer')
                    ->when($customerPhone, fn($q) => $q->where('phone', $customerPhone))
                    ->when(!$customerPhone, fn($q) => $q->where('f_name', $customerName))
                    ->first();
                if (!$storeCustomer) {
                    $storeCustomerId = DB::table('store_customers')->insertGetId([
                        'store_id'   => $storeId,
                        'user_type'  => 'customer',
                        'f_name'     => $customerName,
                        'phone'      => $customerPhone,
                        'email'      => $customerEmail,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    // Raw insert bypasses the StoreCustomer::created hook — welcome explicitly.
                    \App\Services\WhatsAppService::sendWelcomeMessage($storeId, $customerName, $customerPhone);
                } else {
                    $storeCustomerId = $storeCustomer->id;
                }

                $invoiceRow = DB::table('manual_invoices')->insertGetId([
                    'vendor_id'       => $storeId,        // stores STORE ID per system convention
                    'invoice_id'      => $invoiceId,
                    'financial_year'  => $fy,
                    'invoice_serial'  => $serial,
                    'customer_name'   => $customerName,
                    'bill_to'         => $storeCustomerId,
                    'bill_to_type'    => 'user',
                    'user_type'       => 'store_user',
                    'subtotal_amount' => $subtotal,
                    'total_amount'    => $totalAmount,
                    'tax_type'        => 'non-gst',
                    'payment_method'  => $data['payment_method'] ?? 'Cash',
                    'payment_status'  => $data['payment_status'] ?? 'Unpaid',
                    'invoice_date'    => now()->toDateString(),
                    'type'            => 'manual',
                    'module_id'       => 6,
                    'generated_by'    => 'vendor',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                foreach ($items as $item) {
                    DB::table('invoice_items')->insert([
                        'manual_invoice_id' => $invoiceRow,
                        'rand_invoice_id'   => $invoiceId,
                        'name'              => $item['name'],
                        'price'             => $item['price'],
                        'qty'               => $item['qty'],
                        'total'             => $item['total'],
                        'unit'              => $item['unit'] ?? 'pcs',
                        'tax'               => $item['tax'],
                        'tax_amount'        => $item['tax_amount'],
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }

                // Generate PDF using the same flow as manual billing
                $pdfUrl = '';
                try {
                    $invoiceModel = ManualInvoice::find($invoiceRow);
                    if ($invoiceModel) {
                        $pdfData = _createBillPdf($invoiceModel, 'vendor');
                        if (!empty($pdfData['pdf'])) {
                            $invoiceModel->update(['pdf' => $pdfData['pdf']]);
                            $pdfUrl = "\nPDF: " . asset('storage/invoice/' . $pdfData['pdf']);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error('AI invoice PDF generation failed', ['invoice_id' => $invoiceId, 'error' => $e->getMessage()]);
                }

                return response()->json(['success' => true, 'message' => "Invoice #{$invoiceId} created for {$customerName}. Total: ₹{$totalAmount}. Status: " . ($data['payment_status'] ?? 'Unpaid') . $pdfUrl]);

            case 'mark_paid':
            case 'paid':
                $inv = null;
                if (!empty($data['id']))     $inv = DB::table('manual_invoices')->where('id', $data['id'])->where('vendor_id', $storeId)->first();
                if (!$inv && !empty($data['number'])) $inv = DB::table('manual_invoices')->where('invoice_id', $data['number'])->where('vendor_id', $storeId)->first();
                if (!$inv) return response()->json(['success' => false, 'message' => 'Invoice not found.']);
                DB::table('manual_invoices')->where('id', $inv->id)->update([
                    'payment_status' => 'Paid',
                    'payment_method' => $data['payment_method'] ?? $inv->payment_method ?? 'Cash',
                    'payment_date'   => now()->toDateString(),
                    'updated_at'     => now(),
                ]);
                return response()->json(['success' => true, 'message' => "Invoice #{$inv->invoice_id} marked as Paid."]);
        }
        return response()->json(['success' => false, 'message' => "Unknown invoice action: {$action}. Available: list, get, unpaid, add, mark_paid."]);
    }

    // ── Service Leads (enquiries from customers) ──────────────────────────

    private function handleLeads(string $action, array $data, int $vendorId, int $storeId)
    {
        switch ($action) {
            case 'list':
                $status = $data['status'] ?? null;
                $q = DB::table('service_requests')
                    ->whereRaw("FIND_IN_SET(?, sent_to)", [$storeId])
                    ->orderByDesc('created_at')->limit(20);
                if ($status) $q->where('status', $status);
                $leads = $q->get(['id', 'status', 'requirements', 'created_at', 'city']);
                if ($leads->isEmpty()) return response()->json(['success' => true, 'message' => 'No leads found.']);
                $lines = $leads->map(fn($l) => "- [#{$l->id}] {$l->status} | {$l->city} | " . \Carbon\Carbon::parse($l->created_at)->format('d M Y'))->implode("\n");
                return response()->json(['success' => true, 'message' => "Leads ({$leads->count()}):\n{$lines}"]);

            case 'count':
                $counts = DB::table('service_requests')
                    ->selectRaw('status, count(*) as total')
                    ->whereRaw("FIND_IN_SET(?, sent_to)", [$storeId])
                    ->groupBy('status')->get();
                if ($counts->isEmpty()) return response()->json(['success' => true, 'message' => 'No leads found.']);
                return response()->json(['success' => true, 'message' => "Lead counts — " . $counts->map(fn($c) => "{$c->status}: {$c->total}")->implode(', ')]);

            case 'get':
                $lead = DB::table('service_requests')->where('id', $data['id'] ?? 0)->first();
                if (!$lead) return response()->json(['success' => false, 'message' => 'Lead not found.']);
                return response()->json(['success' => true, 'message' => "Lead #{$lead->id} | Status: {$lead->status} | City: {$lead->city} | Requirements: {$lead->requirements} | Date: " . \Carbon\Carbon::parse($lead->created_at)->format('d M Y')]);
        }
        return response()->json(['success' => false, 'message' => "Unknown leads action: {$action}. Available: list, count, get."]);
    }

    // ── CRM (vendor's own leads / clients) ───────────────────────────────
    // leads table: vendor_id, client_name, client_email, client_mobile, status, service, requirements

    private function handleCrm(string $action, array $data, int $vendorId)
    {
        switch ($action) {
            case 'list':
                $status = $data['status'] ?? null;
                $q = DB::table('leads')->where('vendor_id', $vendorId)->orderByDesc('created_at')->limit(20);
                if ($status) $q->where('status', $status);
                $leads = $q->get(['id', 'client_name', 'client_mobile', 'service', 'status', 'follow_up_date', 'created_at']);
                if ($leads->isEmpty()) return response()->json(['success' => true, 'message' => 'No CRM leads found.']);
                $lines = $leads->map(fn($l) => "- [#{$l->id}] {$l->client_name} | {$l->client_mobile} | {$l->service} | {$l->status}")->implode("\n");
                return response()->json(['success' => true, 'message' => "CRM Leads ({$leads->count()}):\n{$lines}"]);

            case 'get':
                $q = DB::table('leads')->where('vendor_id', $vendorId);
                if (!empty($data['id']))   $q->where('id', $data['id']);
                if (!empty($data['name'])) $q->where('client_name', 'like', '%' . $data['name'] . '%');
                $l = $q->first();
                if (!$l) return response()->json(['success' => false, 'message' => 'Lead not found.']);
                return response()->json(['success' => true, 'message' => "Lead #{$l->id} | {$l->client_name} | {$l->client_mobile} | {$l->client_email} | Service: {$l->service} | Status: {$l->status} | Notes: {$l->requirements}"]);

            case 'add':
                if (empty($data['client_name'])) return response()->json(['success' => false, 'message' => 'client_name is required.']);
                $id = DB::table('leads')->insertGetId([
                    'vendor_id'      => $vendorId,
                    'client_name'    => $data['client_name'],
                    'client_email'   => $data['email'] ?? $data['client_email'] ?? null,
                    'client_mobile'  => $data['phone'] ?? $data['mobile'] ?? $data['client_mobile'] ?? null,
                    'client_address' => $data['address'] ?? null,
                    'service'        => $data['service'] ?? null,
                    'requirements'   => $data['requirements'] ?? $data['notes'] ?? null,
                    'status'         => $data['status'] ?? 'New',
                    'follow_up_date' => $data['follow_up_date'] ?? null,
                    'channel'        => 'WEB',
                    'created_at'     => now(), 'updated_at' => now(),
                ]);
                return response()->json(['success' => true, 'message' => "CRM lead added for {$data['client_name']}. ID: #{$id}"]);

            case 'update_status':
            case 'status':
                $id = $data['id'] ?? null;
                if (!$id) return response()->json(['success' => false, 'message' => 'Lead ID is required.']);
                DB::table('leads')->where('id', $id)->where('vendor_id', $vendorId)->update([
                    'status'     => $data['status'] ?? 'New',
                    'remarks'    => $data['remarks'] ?? null,
                    'updated_at' => now(),
                ]);
                return response()->json(['success' => true, 'message' => "Lead #{$id} status updated to '{$data['status']}'."]);
        }
        return response()->json(['success' => false, 'message' => "Unknown CRM action: {$action}. Available: list, get, add, update_status."]);
    }

    // ── Attendance ────────────────────────────────────────────────────────

    private function handleAttendance(string $action, array $data, int $vendorId)
    {
        switch ($action) {
            case 'list':
                $date = $data['date'] ?? now()->toDateString();
                $rows = DB::table('attendances')
                    ->join('vendor_employees', 'vendor_employees.id', '=', 'attendances.employee_id')
                    ->where('attendances.vendor_id', $vendorId)
                    ->where('attendances.employee_type', 'vendor_employee')
                    ->whereDate('attendances.date', $date)
                    ->get(['vendor_employees.f_name', 'vendor_employees.l_name', 'attendances.label']);
                if ($rows->isEmpty()) return response()->json(['success' => true, 'message' => "No attendance records for {$date}."]);
                $lines = $rows->map(fn($r) => "- {$r->f_name} {$r->l_name}: {$r->label}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Attendance for {$date}:\n{$lines}"]);

            case 'absent':
                $date = $data['date'] ?? now()->toDateString();
                $present = DB::table('attendances')
                    ->where('vendor_id', $vendorId)->where('employee_type', 'vendor_employee')
                    ->whereDate('date', $date)->pluck('employee_id');
                $absent = DB::table('vendor_employees')
                    ->where('store_id', DB::table('stores')->where('vendor_id', $vendorId)->value('id'))
                    ->where('status', 1)->whereNotIn('id', $present)
                    ->get(['f_name', 'l_name']);
                if ($absent->isEmpty()) return response()->json(['success' => true, 'message' => "All staff present on {$date}."]);
                $lines = $absent->map(fn($s) => "- {$s->f_name} {$s->l_name}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Absent on {$date}:\n{$lines}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown attendance action: {$action}. Available: list, absent."]);
    }

    // ── Leave ─────────────────────────────────────────────────────────────

    private function handleLeave(string $action, array $data, int $vendorId)
    {
        switch ($action) {
            case 'list':
                $leaves = DB::table('leaves')
                    ->join('vendor_employees', 'vendor_employees.id', '=', 'leaves.emp_id')
                    ->where('leaves.vendor_id', $vendorId)->where('leaves.employee_type', 'vendor_employee')
                    ->orderByDesc('leaves.created_at')->limit(20)
                    ->get(['leaves.id', 'vendor_employees.f_name', 'vendor_employees.l_name', 'leaves.leave_type', 'leaves.leave_date', 'leaves.status']);
                if ($leaves->isEmpty()) return response()->json(['success' => true, 'message' => 'No leave records found.']);
                $lines = $leaves->map(fn($l) => "- [#{$l->id}] {$l->f_name} {$l->l_name}: {$l->leave_type} | {$l->leave_date} | {$l->status}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Leave records:\n{$lines}"]);

            case 'pending':
                $leaves = DB::table('leaves')
                    ->join('vendor_employees', 'vendor_employees.id', '=', 'leaves.emp_id')
                    ->where('leaves.vendor_id', $vendorId)->where('leaves.employee_type', 'vendor_employee')
                    ->where('leaves.status', 'pending')
                    ->get(['leaves.id', 'vendor_employees.f_name', 'vendor_employees.l_name', 'leaves.leave_type', 'leaves.leave_date', 'leaves.reason']);
                if ($leaves->isEmpty()) return response()->json(['success' => true, 'message' => 'No pending leave requests.']);
                $lines = $leaves->map(fn($l) => "- [#{$l->id}] {$l->f_name} {$l->l_name}: {$l->leave_type} | {$l->leave_date} | Reason: {$l->reason}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Pending leaves:\n{$lines}"]);

            case 'approve':
                $id = $data['id'] ?? null;
                if (!$id) return response()->json(['success' => false, 'message' => 'Leave ID is required.']);
                DB::table('leaves')->where('id', $id)->where('vendor_id', $vendorId)->update(['status' => 'approved', 'updated_at' => now()]);
                return response()->json(['success' => true, 'message' => "Leave #{$id} approved."]);

            case 'reject':
                $id = $data['id'] ?? null;
                if (!$id) return response()->json(['success' => false, 'message' => 'Leave ID is required.']);
                DB::table('leaves')->where('id', $id)->where('vendor_id', $vendorId)->update(['status' => 'rejected', 'updated_at' => now()]);
                return response()->json(['success' => true, 'message' => "Leave #{$id} rejected."]);
        }
        return response()->json(['success' => false, 'message' => "Unknown leave action: {$action}. Available: list, pending, approve, reject."]);
    }

    // ── Salary ────────────────────────────────────────────────────────────

    private function handleSalary(string $action, array $data, int $vendorId)
    {
        switch ($action) {
            case 'list':
                $month = $data['month'] ?? now()->format('Y-m');
                $rows = DB::table('salary_transactions')
                    ->join('vendor_employees', 'vendor_employees.id', '=', 'salary_transactions.employee_id')
                    ->where('salary_transactions.vendor_id', $vendorId)
                    ->where('salary_transactions.employee_type', 'vendor_employee')
                    ->where('salary_transactions.salary_month', 'like', $month . '%')
                    ->get(['vendor_employees.f_name', 'vendor_employees.l_name', 'salary_transactions.id', 'salary_transactions.base_salary', 'salary_transactions.pay_status', 'salary_transactions.bonus_incentives', 'salary_transactions.deductions']);
                if ($rows->isEmpty()) return response()->json(['success' => true, 'message' => "No salary records for {$month}."]);
                $lines = $rows->map(fn($r) => "- [#{$r->id}] {$r->f_name} {$r->l_name}: ₹{$r->base_salary} | Bonus: ₹{$r->bonus_incentives} | Deductions: ₹{$r->deductions} | {$r->pay_status}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Salary for {$month}:\n{$lines}"]);

            case 'mark_paid':
            case 'paid':
                $id = $data['id'] ?? null;
                if (!$id) return response()->json(['success' => false, 'message' => 'Salary transaction ID is required.']);
                DB::table('salary_transactions')->where('id', $id)->where('vendor_id', $vendorId)->update([
                    'pay_status'     => 'paid',
                    'payment_method' => $data['payment_method'] ?? null,
                    'updated_at'     => now(),
                ]);
                return response()->json(['success' => true, 'message' => "Salary transaction #{$id} marked as paid."]);
        }
        return response()->json(['success' => false, 'message' => "Unknown salary action: {$action}. Available: list, mark_paid."]);
    }

    // ── Projects ──────────────────────────────────────────────────────────
    // projects: vendor_id, project_title, progress_status, start_date, end_date, cost, priority, status

    private function handleProject(string $action, array $data, int $vendorId)
    {
        switch ($action) {
            case 'list':
                $status = $data['status'] ?? null;
                $q = DB::table('projects')->where('vendor_id', $vendorId)->where('status', 1)->orderByDesc('created_at')->limit(20);
                if ($status) $q->where('progress_status', $status);
                $projects = $q->get(['id', 'project_title', 'progress_status', 'start_date', 'end_date', 'cost', 'priority', 'prog_percent']);
                if ($projects->isEmpty()) return response()->json(['success' => true, 'message' => 'No projects found.']);
                $lines = $projects->map(fn($p) => "- [#{$p->id}] {$p->project_title} | {$p->progress_status} | {$p->prog_percent}% | ₹{$p->cost} | {$p->priority} | {$p->start_date} → {$p->end_date}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Projects ({$projects->count()}):\n{$lines}"]);

            case 'get':
                $q = DB::table('projects')->where('vendor_id', $vendorId);
                if (!empty($data['id']))    $q->where('id', $data['id']);
                if (!empty($data['title'])) $q->where('project_title', 'like', '%' . $data['title'] . '%');
                $p = $q->first();
                if (!$p) return response()->json(['success' => false, 'message' => 'Project not found.']);
                return response()->json(['success' => true, 'message' => "Project: {$p->project_title} | Status: {$p->progress_status} | Progress: {$p->prog_percent}% | Cost: ₹{$p->cost} | Priority: {$p->priority} | {$p->start_date} → {$p->end_date}\n{$p->short_description}"]);

            case 'add':
            case 'create':
                if (empty($data['title']) && empty($data['project_title'])) return response()->json(['success' => false, 'message' => 'Project title is required.']);
                $id = DB::table('projects')->insertGetId([
                    'vendor_id'       => $vendorId,
                    'project_title'   => $data['title'] ?? $data['project_title'],
                    'short_description' => $data['description'] ?? null,
                    'start_date'      => $data['start_date'] ?? now()->toDateString(),
                    'end_date'        => $data['end_date'] ?? null,
                    'cost'            => $data['cost'] ?? $data['budget'] ?? 0,
                    'priority'        => $data['priority'] ?? 'medium',
                    'progress_status' => $data['status'] ?? 'In Progress',
                    'prog_percent'    => 0,
                    'status'          => 1,
                    'created_at'      => now(), 'updated_at' => now(),
                ]);
                return response()->json(['success' => true, 'message' => "Project '{$data['title']}' created. ID: #{$id}"]);

            case 'update':
            case 'edit':
                $id = $data['id'] ?? null;
                if (!$id) return response()->json(['success' => false, 'message' => 'Project ID is required.']);
                unset($data['id']);
                if (isset($data['title'])) { $data['project_title'] = $data['title']; unset($data['title']); }
                if (isset($data['description'])) { $data['short_description'] = $data['description']; unset($data['description']); }
                if (isset($data['status'])) { $data['progress_status'] = $data['status']; unset($data['status']); }
                if (isset($data['percent'])) { $data['prog_percent'] = $data['percent']; unset($data['percent']); }
                $data['updated_at'] = now();
                DB::table('projects')->where('id', $id)->where('vendor_id', $vendorId)->update($data);
                return response()->json(['success' => true, 'message' => "Project #{$id} updated."]);
        }
        return response()->json(['success' => false, 'message' => "Unknown project action: {$action}. Available: list, get, add, update."]);
    }

    // ── Tasks ─────────────────────────────────────────────────────────────
    // store_tasks: store_id, title, description, employee_id, status, progress, task_type

    private function handleTask(string $action, array $data, int $vendorId, int $storeId)
    {
        switch ($action) {
            case 'list':
                $status = $data['status'] ?? null;
                $q = DB::table('store_tasks')->where('store_id', $storeId)->orderByDesc('created_at')->limit(20);
                if ($status) $q->where('status', $status);
                $tasks = $q->get(['id', 'title', 'status', 'progress', 'employee_id', 'created_at']);
                if ($tasks->isEmpty()) return response()->json(['success' => true, 'message' => 'No tasks found.']);
                $lines = $tasks->map(fn($t) => "- [#{$t->id}] {$t->title} | {$t->status} | {$t->progress}%")->implode("\n");
                return response()->json(['success' => true, 'message' => "Tasks ({$tasks->count()}):\n{$lines}"]);

            case 'pending':
                $tasks = DB::table('store_tasks')->where('store_id', $storeId)
                    ->whereNotIn('status', ['completed', 'Completed', 'cancelled', 'Cancelled'])
                    ->orderByDesc('created_at')->limit(20)->get(['id', 'title', 'status', 'progress', 'employee_id']);
                if ($tasks->isEmpty()) return response()->json(['success' => true, 'message' => 'No pending tasks.']);
                $lines = $tasks->map(fn($t) => "- [#{$t->id}] {$t->title} | {$t->status} | {$t->progress}%")->implode("\n");
                return response()->json(['success' => true, 'message' => "Pending tasks ({$tasks->count()}):\n{$lines}"]);

            case 'add':
            case 'create':
                if (empty($data['title'])) return response()->json(['success' => false, 'message' => 'Task title is required.']);
                $id = DB::table('store_tasks')->insertGetId([
                    'store_id'    => $storeId,
                    'title'       => $data['title'],
                    'description' => $data['description'] ?? null,
                    'employee_id' => $data['employee_id'] ?? $data['staff_id'] ?? null,
                    'status'      => $data['status'] ?? 'Pending',
                    'progress'    => 0,
                    'task_type'   => $data['task_type'] ?? 'common',
                    'project_id'  => $data['project_id'] ?? null,
                    'created_at'  => now(), 'updated_at' => now(),
                ]);
                return response()->json(['success' => true, 'message' => "Task '{$data['title']}' created. ID: #{$id}"]);

            case 'update':
            case 'edit':
                $id = $data['id'] ?? null;
                if (!$id) return response()->json(['success' => false, 'message' => 'Task ID is required.']);
                unset($data['id']);
                $data['updated_at'] = now();
                if (!empty($data['status']) && strtolower($data['status']) === 'completed') {
                    $data['completed_at'] = now();
                }
                DB::table('store_tasks')->where('id', $id)->where('store_id', $storeId)->update($data);
                return response()->json(['success' => true, 'message' => "Task #{$id} updated."]);
        }
        return response()->json(['success' => false, 'message' => "Unknown task action: {$action}. Available: list, pending, add, update."]);
    }

    // ── Calendar / Appointments ───────────────────────────────────────────
    // appointments: store_id, appointment_date, appointment_time, status

    private function handleCalendar(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
            case 'today':
                $date = $data['date'] ?? now()->toDateString();
                $rows = DB::table('appointments')->where('store_id', $storeId)
                    ->whereDate('appointment_date', $date)
                    ->orderBy('appointment_time')
                    ->get(['id', 'appointment_date', 'appointment_time', 'status', 'reason', 'booking_type']);
                if ($rows->isEmpty()) return response()->json(['success' => true, 'message' => "No appointments for {$date}."]);
                $lines = $rows->map(fn($a) => "- [#{$a->id}] {$a->appointment_time} | {$a->status} | {$a->booking_type}" . ($a->reason ? " | {$a->reason}" : ''))->implode("\n");
                return response()->json(['success' => true, 'message' => "Appointments for {$date} ({$rows->count()}):\n{$lines}"]);

            case 'upcoming':
                $rows = DB::table('appointments')->where('store_id', $storeId)
                    ->where('appointment_date', '>=', now()->toDateString())
                    ->whereIn('status', ['scheduled', 'checked_in'])
                    ->orderBy('appointment_date')->orderBy('appointment_time')
                    ->limit(10)->get(['id', 'appointment_date', 'appointment_time', 'status', 'reason']);
                if ($rows->isEmpty()) return response()->json(['success' => true, 'message' => 'No upcoming appointments.']);
                $lines = $rows->map(fn($a) => "- [#{$a->id}] {$a->appointment_date} {$a->appointment_time} | {$a->status}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Upcoming appointments ({$rows->count()}):\n{$lines}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown calendar action: {$action}. Available: list, upcoming."]);
    }

    // ── Job Cards ─────────────────────────────────────────────────────────

    private function handleJobCard(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $cards = DB::table('job_cards')->where('store_id', $storeId)
                    ->orderByDesc('created_at')->limit(20)
                    ->get(['id', 'job_card_number', 'task_id', 'lead_id', 'status', 'payment_method', 'created_at']);
                if ($cards->isEmpty()) return response()->json(['success' => true, 'message' => 'No job cards found.']);
                $lines = $cards->map(fn($c) => "- [#{$c->id}] JC-{$c->job_card_number} | Task: #{$c->task_id} | Status: {$c->status} | " . \Carbon\Carbon::parse($c->created_at)->format('d M Y'))->implode("\n");
                return response()->json(['success' => true, 'message' => "Job cards ({$cards->count()}):\n{$lines}"]);

            case 'get':
                $q = DB::table('job_cards')->where('store_id', $storeId);
                if (!empty($data['id']))     $q->where('id', $data['id']);
                if (!empty($data['number'])) $q->where('job_card_number', $data['number']);
                $c = $q->first();
                if (!$c) return response()->json(['success' => false, 'message' => 'Job card not found.']);
                return response()->json(['success' => true, 'message' => "Job Card #{$c->job_card_number} | Task: #{$c->task_id} | Lead: #{$c->lead_id} | Status: {$c->status} | Payment: {$c->payment_method}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown job_card action: {$action}. Available: list, get."]);
    }

    // ── Store Info ────────────────────────────────────────────────────────

    private function handleStore(string $action, array $data, int $vendorId, int $storeId)
    {
        switch ($action) {
            case 'get':
            case 'info':
                $store = DB::table('stores')->where('id', $storeId)->first();
                if (!$store) return response()->json(['success' => false, 'message' => 'Store not found.']);
                return response()->json(['success' => true, 'message' => "Store: {$store->name} | Phone: {$store->phone} | Email: {$store->email} | Address: {$store->address} | GST: " . ($store->gst ?: 'Not registered')]);

            case 'staff_count':
                $count = DB::table('vendor_employees')->where('store_id', $storeId)->where('status', 1)->count();
                return response()->json(['success' => true, 'message' => "Total active staff: {$count}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown store action: {$action}. Available: get, staff_count."]);
    }

    // ── Account / Finance ─────────────────────────────────────────────────

    private function handleAccount(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'summary':
                $total  = DB::table('manual_invoices')->where('vendor_id', $storeId)->sum('total_amount');
                $paid   = DB::table('manual_invoices')->where('vendor_id', $storeId)->whereIn('payment_status', ['Paid', 'paid'])->sum('total_amount');
                $unpaid = DB::table('manual_invoices')->where('vendor_id', $storeId)->whereIn('payment_status', ['Unpaid', 'unpaid', 'Pending', 'pending', 'Due', 'due'])->sum('total_amount');
                $count  = DB::table('manual_invoices')->where('vendor_id', $storeId)->count();
                return response()->json(['success' => true, 'message' => "Invoice summary — Billed: ₹{$total} | Paid: ₹{$paid} | Unpaid: ₹{$unpaid} | Total invoices: {$count}"]);

            case 'recent':
                $rows = DB::table('manual_invoices')->where('vendor_id', $storeId)
                    ->orderByDesc('created_at')->limit(10)
                    ->get(['invoice_id', 'customer_name', 'total_amount', 'payment_status', 'created_at']);
                if ($rows->isEmpty()) return response()->json(['success' => true, 'message' => 'No invoices found.']);
                $lines = $rows->map(fn($r) => "- #{$r->invoice_id} | {$r->customer_name} | ₹{$r->total_amount} | {$r->payment_status} | " . \Carbon\Carbon::parse($r->created_at)->format('d M Y'))->implode("\n");
                return response()->json(['success' => true, 'message' => "Recent:\n{$lines}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown account action: {$action}. Available: summary, recent."]);
    }

    // ── Banking ───────────────────────────────────────────────────────────

    private function handleBanking(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
            case 'accounts':
                $accounts = DB::table('store_bank_accounts')->where('store_id', $storeId)
                    ->get(['id', 'bank_name', 'account_holder_name', 'account_number', 'current_balance', 'minimum_balance']);
                if ($accounts->isEmpty()) return response()->json(['success' => true, 'message' => 'No bank accounts found.']);
                $lines = $accounts->map(fn($a) => "- [{$a->id}] {$a->bank_name} | A/C: {$a->account_number} | Holder: {$a->account_holder_name} | Balance: ₹{$a->current_balance}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Bank accounts ({$accounts->count()}):\n{$lines}"]);

            case 'transactions':
                $bankId = $data['bank_id'] ?? null;
                $q = DB::table('store_bank_transactions')->where('store_id', $storeId)->orderByDesc('txn_date')->limit(20);
                if ($bankId) $q->where('bank_id', $bankId);
                $txns = $q->get(['id', 'bank_id', 'txn_date', 'particulars', 'type', 'amount', 'closing_balance']);
                if ($txns->isEmpty()) return response()->json(['success' => true, 'message' => 'No transactions found.']);
                $lines = $txns->map(fn($t) => "- {$t->txn_date} | {$t->type} | ₹{$t->amount} | {$t->particulars} | Closing: ₹{$t->closing_balance}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Transactions ({$txns->count()}):\n{$lines}"]);

            case 'balance':
                $total = DB::table('store_bank_accounts')->where('store_id', $storeId)->sum('current_balance');
                $count = DB::table('store_bank_accounts')->where('store_id', $storeId)->count();
                return response()->json(['success' => true, 'message' => "Total bank balance: ₹{$total} across {$count} account(s)."]);
        }
        return response()->json(['success' => false, 'message' => "Unknown banking action: {$action}. Available: list, transactions, balance."]);
    }

    // ── Assets ────────────────────────────────────────────────────────────

    private function handleAssets(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $assets = DB::table('store_assets')
                    ->leftJoin('inventory_items', 'inventory_items.id', '=', 'store_assets.inventory_item_id')
                    ->where('store_assets.store_id', $storeId)
                    ->select('store_assets.id', 'store_assets.cost', 'store_assets.current_value', 'store_assets.quantity', 'store_assets.category', 'inventory_items.item_name')
                    ->orderByDesc('store_assets.id')->limit(30)->get();
                if ($assets->isEmpty()) return response()->json(['success' => true, 'message' => 'No assets found.']);
                $lines = $assets->map(fn($a) => "- [{$a->id}] " . ($a->item_name ?? 'Asset') . " | Qty: {$a->quantity} | Cost: ₹{$a->cost} | Value: ₹{$a->current_value}" . ($a->category ? " | {$a->category}" : ''))->implode("\n");
                return response()->json(['success' => true, 'message' => "Assets ({$assets->count()}):\n{$lines}"]);

            case 'get':
                $id = $data['id'] ?? null;
                if (!$id) return response()->json(['success' => false, 'message' => 'Asset ID is required.']);
                $asset = DB::table('store_assets')
                    ->leftJoin('inventory_items', 'inventory_items.id', '=', 'store_assets.inventory_item_id')
                    ->where('store_assets.id', $id)->where('store_assets.store_id', $storeId)
                    ->select('store_assets.*', 'inventory_items.item_name')->first();
                if (!$asset) return response()->json(['success' => false, 'message' => 'Asset not found.']);
                return response()->json(['success' => true, 'message' => "Asset #{$asset->id} | " . ($asset->item_name ?? 'Unknown') . " | Qty: {$asset->quantity} | Cost: ₹{$asset->cost} | Current Value: ₹{$asset->current_value} | Category: {$asset->category}"]);

            case 'summary':
                $total_cost  = DB::table('store_assets')->where('store_id', $storeId)->sum('cost');
                $total_value = DB::table('store_assets')->where('store_id', $storeId)->sum('current_value');
                $count       = DB::table('store_assets')->where('store_id', $storeId)->count();
                return response()->json(['success' => true, 'message' => "Assets: {$count} items | Total cost: ₹{$total_cost} | Current value: ₹{$total_value}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown assets action: {$action}. Available: list, get, summary."]);
    }

    // ── Shifts ────────────────────────────────────────────────────────────

    private function handleShifts(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $shifts = DB::table('store_shifts')->where('store_id', $storeId)
                    ->get(['id', 'name', 'start_time', 'end_time', 'grace_minutes', 'working_days']);
                if ($shifts->isEmpty()) return response()->json(['success' => true, 'message' => 'No shifts defined.']);
                $lines = $shifts->map(fn($s) => "- [{$s->id}] {$s->name} | {$s->start_time} – {$s->end_time} | Grace: {$s->grace_minutes}min | Days: {$s->working_days}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Shifts ({$shifts->count()}):\n{$lines}"]);

            case 'get':
                $q = DB::table('store_shifts')->where('store_id', $storeId);
                if (!empty($data['id']))   $q->where('id', $data['id']);
                if (!empty($data['name'])) $q->where('name', 'like', '%' . $data['name'] . '%');
                $shift = $q->first();
                if (!$shift) return response()->json(['success' => false, 'message' => 'Shift not found.']);
                return response()->json(['success' => true, 'message' => "Shift: {$shift->name} | {$shift->start_time} – {$shift->end_time} | Grace: {$shift->grace_minutes}min | Working days: {$shift->working_days}"]);

            case 'add':
            case 'create':
                if (empty($data['name'])) return response()->json(['success' => false, 'message' => 'Shift name is required.']);
                $id = DB::table('store_shifts')->insertGetId([
                    'store_id'      => $storeId,
                    'name'          => $data['name'],
                    'start_time'    => $data['start_time'] ?? '09:00',
                    'end_time'      => $data['end_time'] ?? '18:00',
                    'grace_minutes' => $data['grace_minutes'] ?? 0,
                    'working_days'  => $data['working_days'] ?? 'Mon,Tue,Wed,Thu,Fri',
                    'created_at'    => now(), 'updated_at' => now(),
                ]);
                return response()->json(['success' => true, 'message' => "Shift '{$data['name']}' created. ID: #{$id}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown shifts action: {$action}. Available: list, get, add."]);
    }

    // ── Quotations ────────────────────────────────────────────────────────

    private function handleQuotation(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $status = $data['status'] ?? null;
                $q = DB::table('quotations')->where('vendor_id', $storeId)->orderByDesc('created_at')->limit(20);
                if ($status) $q->where('status', $status);
                $quotes = $q->get(['id', 'client_name', 'status', 'total', 'created_at']);
                if ($quotes->isEmpty()) return response()->json(['success' => true, 'message' => 'No quotations found.']);
                $lines = $quotes->map(function ($q) {
                    $customer = DB::table('store_customers')->where('id', $q->client_name)->value('f_name') ?? "Client #{$q->client_name}";
                    return "- [#{$q->id}] {$customer} | {$q->status}" . ($q->total ? " | ₹{$q->total}" : '') . " | " . \Carbon\Carbon::parse($q->created_at)->format('d M Y');
                })->implode("\n");
                return response()->json(['success' => true, 'message' => "Quotations ({$quotes->count()}):\n{$lines}"]);

            case 'get':
                $id = $data['id'] ?? null;
                if (!$id) return response()->json(['success' => false, 'message' => 'Quotation ID is required.']);
                $quote = DB::table('quotations')->where('id', $id)->where('vendor_id', $storeId)->first();
                if (!$quote) return response()->json(['success' => false, 'message' => 'Quotation not found.']);
                $customer = DB::table('store_customers')->where('id', $quote->client_name)->value('f_name') ?? "Client #{$quote->client_name}";
                return response()->json(['success' => true, 'message' => "Quotation #{$quote->id} | Client: {$customer} | Status: {$quote->status}" . ($quote->total ? " | Total: ₹{$quote->total}" : '') . " | Date: " . \Carbon\Carbon::parse($quote->created_at)->format('d M Y')]);

            case 'summary':
                $counts = DB::table('quotations')->where('vendor_id', $storeId)
                    ->selectRaw('status, count(*) as total')->groupBy('status')->get();
                if ($counts->isEmpty()) return response()->json(['success' => true, 'message' => 'No quotations found.']);
                return response()->json(['success' => true, 'message' => "Quotations — " . $counts->map(fn($c) => "{$c->status}: {$c->total}")->implode(', ')]);
        }
        return response()->json(['success' => false, 'message' => "Unknown quotation action: {$action}. Available: list, get, summary."]);
    }

    // ── Clients / Customers ───────────────────────────────────────────────

    private function handleClients(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $clients = DB::table('store_customers')->where('store_id', $storeId)
                    ->where('user_type', 'customer')
                    ->orderByDesc('created_at')->limit(30)
                    ->get(['id', 'f_name', 'l_name', 'phone', 'email', 'gst', 'address']);
                if ($clients->isEmpty()) return response()->json(['success' => true, 'message' => 'No clients found.']);
                $lines = $clients->map(fn($c) => "- [{$c->id}] {$c->f_name} {$c->l_name}" . ($c->phone ? " | {$c->phone}" : '') . ($c->email ? " | {$c->email}" : '') . ($c->gst ? " | GST: {$c->gst}" : ''))->implode("\n");
                return response()->json(['success' => true, 'message' => "Clients ({$clients->count()}):\n{$lines}"]);

            case 'get':
                $q = DB::table('store_customers')->where('store_id', $storeId)->where('user_type', 'customer');
                if (!empty($data['id']))    $q->where('id', $data['id']);
                if (!empty($data['name']))  $q->where('f_name', 'like', '%' . $data['name'] . '%');
                if (!empty($data['phone'])) $q->where('phone', $data['phone']);
                $c = $q->first();
                if (!$c) return response()->json(['success' => false, 'message' => 'Client not found.']);
                return response()->json(['success' => true, 'message' => "Client: {$c->f_name} {$c->l_name} | Phone: {$c->phone} | Email: {$c->email} | GST: " . ($c->gst ?: 'N/A') . " | Address: {$c->address}"]);

            case 'add':
            case 'create':
                if (empty($data['name']) && empty($data['f_name'])) return response()->json(['success' => false, 'message' => 'Client name is required.']);
                $name = $data['f_name'] ?? $data['name'];
                $id = DB::table('store_customers')->insertGetId([
                    'store_id'   => $storeId,
                    'user_type'  => 'customer',
                    'f_name'     => $name,
                    'l_name'     => $data['l_name'] ?? '',
                    'phone'      => $data['phone'] ?? null,
                    'email'      => $data['email'] ?? null,
                    'address'    => $data['address'] ?? null,
                    'gst'        => $data['gst'] ?? null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                // Raw insert bypasses the StoreCustomer::created hook — welcome explicitly.
                \App\Services\WhatsAppService::sendWelcomeMessage($storeId, $name, $data['phone'] ?? null);
                return response()->json(['success' => true, 'message' => "Client '{$name}' added. ID: #{$id}"]);

            case 'count':
                $count = DB::table('store_customers')->where('store_id', $storeId)->where('user_type', 'customer')->count();
                return response()->json(['success' => true, 'message' => "Total clients: {$count}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown clients action: {$action}. Available: list, get, add, count."]);
    }

    // ── Orders ────────────────────────────────────────────────────────────

    private function handleOrders(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $status = $data['status'] ?? null;
                $q = DB::table('orders')->where('store_id', $storeId)->orderByDesc('created_at')->limit(20);
                if ($status) $q->where('order_status', $status);
                $orders = $q->get(['id', 'order_status', 'order_type', 'order_amount', 'payment_method', 'payment_status', 'created_at']);
                if ($orders->isEmpty()) return response()->json(['success' => true, 'message' => 'No orders found.']);
                $lines = $orders->map(fn($o) => "- [#{$o->id}] {$o->order_status} | {$o->order_type} | ₹{$o->order_amount} | {$o->payment_method} | " . \Carbon\Carbon::parse($o->created_at)->format('d M Y'))->implode("\n");
                return response()->json(['success' => true, 'message' => "Orders ({$orders->count()}):\n{$lines}"]);

            case 'summary':
                $total   = DB::table('orders')->where('store_id', $storeId)->sum('order_amount');
                $count   = DB::table('orders')->where('store_id', $storeId)->count();
                $pending = DB::table('orders')->where('store_id', $storeId)->whereIn('order_status', ['pending', 'confirmed', 'processing'])->count();
                $today   = DB::table('orders')->where('store_id', $storeId)->whereDate('created_at', now())->count();
                return response()->json(['success' => true, 'message' => "Orders — Total: {$count} | Revenue: ₹{$total} | Active: {$pending} | Today: {$today}"]);

            case 'get':
                $id = $data['id'] ?? null;
                if (!$id) return response()->json(['success' => false, 'message' => 'Order ID is required.']);
                $order = DB::table('orders')->where('id', $id)->where('store_id', $storeId)->first();
                if (!$order) return response()->json(['success' => false, 'message' => 'Order not found.']);
                return response()->json(['success' => true, 'message' => "Order #{$order->id} | Status: {$order->order_status} | Type: {$order->order_type} | Amount: ₹{$order->order_amount} | Payment: {$order->payment_method} ({$order->payment_status})"]);

            case 'pending':
                $orders = DB::table('orders')->where('store_id', $storeId)
                    ->whereIn('order_status', ['pending', 'confirmed', 'processing'])
                    ->orderBy('created_at')->limit(15)
                    ->get(['id', 'order_status', 'order_type', 'order_amount', 'created_at']);
                if ($orders->isEmpty()) return response()->json(['success' => true, 'message' => 'No pending orders.']);
                $lines = $orders->map(fn($o) => "- [#{$o->id}] {$o->order_status} | {$o->order_type} | ₹{$o->order_amount} | " . \Carbon\Carbon::parse($o->created_at)->format('d M H:i'))->implode("\n");
                return response()->json(['success' => true, 'message' => "Pending orders ({$orders->count()}):\n{$lines}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown orders action: {$action}. Available: list, summary, get, pending."]);
    }

    // ── Service Items ─────────────────────────────────────────────────────

    private function handleService(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $items = DB::table('items')->where('store_id', $storeId)->where('status', 1)
                    ->orderBy('name')->limit(30)
                    ->get(['id', 'name', 'price', 'discount', 'discount_type', 'avg_rating', 'order_count']);
                if ($items->isEmpty()) return response()->json(['success' => true, 'message' => 'No service items found.']);
                $lines = $items->map(fn($i) => "- [{$i->id}] {$i->name} | ₹{$i->price}" . ($i->discount ? " | Disc: {$i->discount}" . ($i->discount_type === 'percentage' ? '%' : '') : '') . " | Orders: {$i->order_count} | Rating: {$i->avg_rating}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Services ({$items->count()}):\n{$lines}"]);

            case 'get':
                $q = DB::table('items')->where('store_id', $storeId)->where('status', 1);
                if (!empty($data['id']))   $q->where('id', $data['id']);
                if (!empty($data['name'])) $q->where('name', 'like', '%' . $data['name'] . '%');
                $item = $q->first();
                if (!$item) return response()->json(['success' => false, 'message' => 'Service item not found.']);
                return response()->json(['success' => true, 'message' => "{$item->name} | Price: ₹{$item->price} | Orders: {$item->order_count} | Rating: {$item->avg_rating}/5 | Stock: {$item->stock}"]);

            case 'top':
                $items = DB::table('items')->where('store_id', $storeId)->where('status', 1)
                    ->orderByDesc('order_count')->limit(10)
                    ->get(['id', 'name', 'price', 'order_count', 'avg_rating']);
                if ($items->isEmpty()) return response()->json(['success' => true, 'message' => 'No service items found.']);
                $lines = $items->map(fn($i) => "- {$i->name} | ₹{$i->price} | {$i->order_count} orders | ⭐{$i->avg_rating}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Top services:\n{$lines}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown service action: {$action}. Available: list, get, top."]);
    }

    // ── POS ───────────────────────────────────────────────────────────────

    private function handlePos(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $date = $data['date'] ?? null;
                $q = DB::table('orders')->where('store_id', $storeId)->where('order_type', 'pos')->orderByDesc('created_at')->limit(20);
                if ($date) $q->whereDate('created_at', $date);
                $orders = $q->get(['id', 'order_amount', 'payment_method', 'payment_status', 'order_status', 'created_at']);
                if ($orders->isEmpty()) return response()->json(['success' => true, 'message' => 'No POS orders found.']);
                $lines = $orders->map(fn($o) => "- [#{$o->id}] ₹{$o->order_amount} | {$o->payment_method} | {$o->order_status} | " . \Carbon\Carbon::parse($o->created_at)->format('d M H:i'))->implode("\n");
                return response()->json(['success' => true, 'message' => "POS orders ({$orders->count()}):\n{$lines}"]);

            case 'summary':
                $today    = now()->toDateString();
                $todaySales = DB::table('orders')->where('store_id', $storeId)->where('order_type', 'pos')->whereDate('created_at', $today)->sum('order_amount');
                $todayCount = DB::table('orders')->where('store_id', $storeId)->where('order_type', 'pos')->whereDate('created_at', $today)->count();
                $monthSales = DB::table('orders')->where('store_id', $storeId)->where('order_type', 'pos')->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('order_amount');
                return response()->json(['success' => true, 'message' => "POS — Today: {$todayCount} orders | ₹{$todaySales} | This month: ₹{$monthSales}"]);

            case 'today':
                $orders = DB::table('orders')->where('store_id', $storeId)->where('order_type', 'pos')
                    ->whereDate('created_at', now()->toDateString())
                    ->orderByDesc('created_at')
                    ->get(['id', 'order_amount', 'payment_method', 'order_status', 'created_at']);
                if ($orders->isEmpty()) return response()->json(['success' => true, 'message' => "No POS orders today."]);
                $total = $orders->sum('order_amount');
                $lines = $orders->map(fn($o) => "- [#{$o->id}] ₹{$o->order_amount} | {$o->payment_method} | " . \Carbon\Carbon::parse($o->created_at)->format('H:i'))->implode("\n");
                return response()->json(['success' => true, 'message' => "Today's POS ({$orders->count()} orders, ₹{$total} total):\n{$lines}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown pos action: {$action}. Available: list, summary, today."]);
    }

    // ── Documents ─────────────────────────────────────────────────────────

    private function handleDocuments(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'gatepasses':
            case 'gatepass':
                $rows = DB::table('inventory_gatepasses')->where('store_id', $storeId)
                    ->orderByDesc('created_at')->limit(20)
                    ->get(['id', 'type', 'status', 'created_at']);
                if ($rows->isEmpty()) return response()->json(['success' => true, 'message' => 'No gate passes found.']);
                $lines = $rows->map(fn($r) => "- [#{$r->id}] {$r->type} | {$r->status} | " . \Carbon\Carbon::parse($r->created_at)->format('d M Y'))->implode("\n");
                return response()->json(['success' => true, 'message' => "Gate passes ({$rows->count()}):\n{$lines}"]);

            case 'receipts':
            case 'receivable':
                $rows = DB::table('receivable_receipts')
                    ->join('store_customers', 'store_customers.id', '=', 'receivable_receipts.client_id')
                    ->where('store_customers.store_id', $storeId)
                    ->orderByDesc('receivable_receipts.created_at')->limit(20)
                    ->get(['receivable_receipts.id', 'store_customers.f_name', 'receivable_receipts.amount', 'receivable_receipts.receipt_date']);
                if ($rows->isEmpty()) return response()->json(['success' => true, 'message' => 'No receivable receipts found.']);
                $lines = $rows->map(fn($r) => "- [#{$r->id}] {$r->f_name} | ₹{$r->amount} | {$r->receipt_date}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Receivable receipts ({$rows->count()}):\n{$lines}"]);

            case 'list':
                $gatepasses = DB::table('inventory_gatepasses')->where('store_id', $storeId)->count();
                $receipts   = DB::table('receivable_receipts')
                    ->join('store_customers', 'store_customers.id', '=', 'receivable_receipts.client_id')
                    ->where('store_customers.store_id', $storeId)->count();
                return response()->json(['success' => true, 'message' => "Documents — Gate passes: {$gatepasses} | Receivable receipts: {$receipts}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown documents action: {$action}. Available: list, gatepass, receipts."]);
    }

    // ── Notifications ─────────────────────────────────────────────────────

    private function handleNotification(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $rows = DB::table('notifications')->where('vendor_id', $storeId)
                    ->orderByDesc('created_at')->limit(20)
                    ->get(['id', 'title', 'description', 'status', 'created_at']);
                if ($rows->isEmpty()) return response()->json(['success' => true, 'message' => 'No notifications found.']);
                $lines = $rows->map(fn($n) => "- [#{$n->id}] {$n->title} | " . ($n->status ? 'Sent' : 'Draft') . " | " . \Carbon\Carbon::parse($n->created_at)->format('d M Y'))->implode("\n");
                return response()->json(['success' => true, 'message' => "Notifications ({$rows->count()}):\n{$lines}"]);

            case 'send':
            case 'create':
                if (empty($data['title'])) return response()->json(['success' => false, 'message' => 'Notification title is required.']);
                $id = DB::table('notifications')->insertGetId([
                    'vendor_id'   => $storeId,
                    'added_by'    => 'vendor',
                    'title'       => $data['title'],
                    'description' => $data['description'] ?? $data['body'] ?? null,
                    'status'      => 1,
                    'created_at'  => now(), 'updated_at' => now(),
                ]);
                return response()->json(['success' => true, 'message' => "Notification '{$data['title']}' created. ID: #{$id}"]);

            case 'count':
                $sent  = DB::table('notifications')->where('vendor_id', $storeId)->where('status', 1)->count();
                $total = DB::table('notifications')->where('vendor_id', $storeId)->count();
                return response()->json(['success' => true, 'message' => "Notifications — Total: {$total} | Sent: {$sent}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown notification action: {$action}. Available: list, send, count."]);
    }

    // ── Shop Items ────────────────────────────────────────────────────────

    private function handleItems(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $items = DB::table('items')->where('store_id', $storeId)
                    ->orderByDesc('order_count')->limit(30)
                    ->get(['id', 'name', 'price', 'discount', 'status', 'stock', 'avg_rating', 'order_count']);
                if ($items->isEmpty()) return response()->json(['success' => true, 'message' => 'No items found.']);
                $lines = $items->map(fn($i) => "- [{$i->id}] {$i->name} | ₹{$i->price}" . ($i->discount ? " (-{$i->discount}%)" : '') . " | Stock: {$i->stock} | " . ($i->status ? 'Active' : 'Inactive'))->implode("\n");
                return response()->json(['success' => true, 'message' => "Items ({$items->count()}):\n{$lines}"]);

            case 'get':
                $q = DB::table('items')->where('store_id', $storeId);
                if (!empty($data['id']))   $q->where('id', $data['id']);
                if (!empty($data['name'])) $q->where('name', 'like', '%' . $data['name'] . '%');
                $item = $q->first();
                if (!$item) return response()->json(['success' => false, 'message' => 'Item not found.']);
                return response()->json(['success' => true, 'message' => "{$item->name} | Price: ₹{$item->price} | Discount: {$item->discount}% | Stock: {$item->stock} | Rating: {$item->avg_rating}/5 | Orders: {$item->order_count} | " . ($item->status ? 'Active' : 'Inactive')]);

            case 'inactive':
            case 'disabled':
                $items = DB::table('items')->where('store_id', $storeId)->where('status', 0)
                    ->get(['id', 'name', 'price']);
                if ($items->isEmpty()) return response()->json(['success' => true, 'message' => 'All items are active.']);
                $lines = $items->map(fn($i) => "- [{$i->id}] {$i->name} | ₹{$i->price}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Inactive items ({$items->count()}):\n{$lines}"]);

            case 'summary':
                $total    = DB::table('items')->where('store_id', $storeId)->count();
                $active   = DB::table('items')->where('store_id', $storeId)->where('status', 1)->count();
                $topItem  = DB::table('items')->where('store_id', $storeId)->orderByDesc('order_count')->value('name');
                return response()->json(['success' => true, 'message' => "Items — Total: {$total} | Active: {$active} | Most ordered: {$topItem}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown items action: {$action}. Available: list, get, inactive, summary."]);
    }

    // ── Campaigns ─────────────────────────────────────────────────────────

    private function handleCampaign(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $campaigns = DB::table('item_campaigns')->where('store_id', $storeId)
                    ->orderByDesc('created_at')->limit(20)
                    ->get(['id', 'title', 'price', 'status', 'start_date', 'end_date']);
                if ($campaigns->isEmpty()) return response()->json(['success' => true, 'message' => 'No campaigns found.']);
                $lines = $campaigns->map(fn($c) => "- [{$c->id}] {$c->title} | ₹{$c->price} | " . ($c->status ? 'Active' : 'Inactive') . " | {$c->start_date} – {$c->end_date}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Campaigns ({$campaigns->count()}):\n{$lines}"]);

            case 'active':
                $campaigns = DB::table('item_campaigns')->where('store_id', $storeId)
                    ->where('status', 1)->whereDate('end_date', '>=', now())
                    ->get(['id', 'title', 'price', 'start_date', 'end_date']);
                if ($campaigns->isEmpty()) return response()->json(['success' => true, 'message' => 'No active campaigns.']);
                $lines = $campaigns->map(fn($c) => "- [{$c->id}] {$c->title} | ₹{$c->price} | Ends: {$c->end_date}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Active campaigns ({$campaigns->count()}):\n{$lines}"]);

            case 'get':
                $q = DB::table('item_campaigns')->where('store_id', $storeId);
                if (!empty($data['id']))    $q->where('id', $data['id']);
                if (!empty($data['title'])) $q->where('title', 'like', '%' . $data['title'] . '%');
                $c = $q->first();
                if (!$c) return response()->json(['success' => false, 'message' => 'Campaign not found.']);
                return response()->json(['success' => true, 'message' => "Campaign: {$c->title} | ₹{$c->price} | " . ($c->status ? 'Active' : 'Inactive') . " | {$c->start_date} – {$c->end_date}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown campaign action: {$action}. Available: list, active, get."]);
    }

    // ── Coupons ───────────────────────────────────────────────────────────

    private function handleCoupon(string $action, array $data, int $storeId)
    {
        switch ($action) {
            case 'list':
                $coupons = DB::table('coupons')->where('store_id', $storeId)
                    ->orderByDesc('created_at')->limit(20)
                    ->get(['id', 'code', 'title', 'discount', 'discount_type', 'status', 'expire_date', 'total_uses']);
                if ($coupons->isEmpty()) return response()->json(['success' => true, 'message' => 'No coupons found.']);
                $lines = $coupons->map(fn($c) => "- [{$c->id}] {$c->code} | {$c->title} | {$c->discount}" . ($c->discount_type === 'percentage' ? '%' : ' flat') . " | " . ($c->status ? 'Active' : 'Inactive') . " | Expires: {$c->expire_date} | Uses: {$c->total_uses}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Coupons ({$coupons->count()}):\n{$lines}"]);

            case 'get':
                $q = DB::table('coupons')->where('store_id', $storeId);
                if (!empty($data['code'])) $q->where('code', $data['code']);
                if (!empty($data['id']))   $q->where('id', $data['id']);
                $c = $q->first();
                if (!$c) return response()->json(['success' => false, 'message' => 'Coupon not found.']);
                return response()->json(['success' => true, 'message' => "Coupon: {$c->code} | {$c->title} | Discount: {$c->discount}" . ($c->discount_type === 'percentage' ? '%' : '') . " | Min purchase: ₹{$c->min_purchase} | Expires: {$c->expire_date} | Uses: {$c->total_uses} / " . ($c->limit ?: '∞')]);

            case 'add':
            case 'create':
                foreach (['code', 'title'] as $f) {
                    if (empty($data[$f])) return response()->json(['success' => false, 'message' => "Field '{$f}' is required."]);
                }
                if (DB::table('coupons')->where('code', $data['code'])->exists()) {
                    return response()->json(['success' => false, 'message' => "Coupon code '{$data['code']}' already exists."]);
                }
                $id = DB::table('coupons')->insertGetId([
                    'store_id'      => $storeId,
                    'code'          => strtoupper($data['code']),
                    'title'         => $data['title'],
                    'discount'      => $data['discount'] ?? 0,
                    'discount_type' => $data['discount_type'] ?? 'percentage',
                    'coupon_type'   => $data['coupon_type'] ?? 'store_wise',
                    'min_purchase'  => $data['min_purchase'] ?? 0,
                    'max_discount'  => $data['max_discount'] ?? 0,
                    'limit'         => $data['limit'] ?? null,
                    'start_date'    => $data['start_date'] ?? now()->toDateString(),
                    'expire_date'   => $data['expire_date'] ?? now()->addMonth()->toDateString(),
                    'status'        => 1,
                    'created_by'    => 'vendor',
                    'total_uses'    => 0,
                    'created_at'    => now(), 'updated_at' => now(),
                ]);
                return response()->json(['success' => true, 'message' => "Coupon '{$data['code']}' created. ID: #{$id}"]);

            case 'active':
                $coupons = DB::table('coupons')->where('store_id', $storeId)->where('status', 1)
                    ->where('expire_date', '>=', now()->toDateString())
                    ->get(['id', 'code', 'title', 'discount', 'discount_type', 'expire_date']);
                if ($coupons->isEmpty()) return response()->json(['success' => true, 'message' => 'No active coupons.']);
                $lines = $coupons->map(fn($c) => "- {$c->code} | {$c->title} | {$c->discount}" . ($c->discount_type === 'percentage' ? '%' : ' flat') . " | Expires: {$c->expire_date}")->implode("\n");
                return response()->json(['success' => true, 'message' => "Active coupons ({$coupons->count()}):\n{$lines}"]);
        }
        return response()->json(['success' => false, 'message' => "Unknown coupon action: {$action}. Available: list, get, add, active."]);
    }
}
