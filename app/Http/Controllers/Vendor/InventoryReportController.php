<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Enums\ExportFileNames\Admin\Category;
use App\Exports\Vendor\GstReportExport;
use App\Exports\Vendor\SaleReportExport;
use App\Exports\Vendor\PurchaseReportExport;
use App\Exports\Vendor\StockReportExport;
use App\Exports\Vendor\ProfitLossReportExport;
use App\Http\Controllers\Controller;
use App\Models\Category as ModelsCategory;
use App\Models\InventoryItem;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\ManualInvoice;
use App\Models\PurchaseOrder;
use App\Models\InventoryReport;
use App\Models\ServiceInvoice;
use App\Models\Store;
use App\Models\StoreCustomer;
use App\Models\SupplyOrder;
use App\Models\SupplyOrderItem;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use App\Models\ItemEntry;
use Maatwebsite\Excel\Facades\Excel;

class InventoryReportController extends Controller
{
    // Branch filter for reports. Returns: null = all branches, 0 = main store (no branch), >0 = branch id.
    private function reportBranchId(Request $request)
    {
        $b = $request->input('branch_id');
        if ($b === null || $b === '' || $b === 'all') {
            return null;
        }
        if ($b === 'main' || (string) $b === '0') {
            return 0;
        }
        return (int) $b;
    }

    private function reportBranches()
    {
        return \App\Models\Branch::where('store_id', Helpers::get_store_id())->orderBy('name')->get();
    }

    // The date window a report page is showing. Shared so a bulk delete resolves the same rows
    // the page rendered rather than re-deriving the range and drifting from it.
    private function reportRange(string $default): array
    {
        $preset = request('date_range') ?? $default;
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);

        return [$preset, $range['start'], $range['end']];
    }

    // The three invoice sets behind the GST report, already narrowed to the page's date range and
    // branch. Shared with the bulk delete so both agree on what "the current report" contains.
    private function gstReportInvoices(Request $request): array
    {
        $storeId = Helpers::get_store_id();
        $branchId = $this->reportBranchId($request);
        [, $formatted_from, $formatted_to] = $this->reportRange('this_month');

        $purchaseInvoices = ManualInvoice::with(['seller', 'invoiceItems'])
            ->where('bill_to_type', 'vendor')
            ->where('tax_type', 'gst')
            ->where('payment_status', 'Paid')
            ->where('bill_to', $storeId)
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->map(function ($invoice) {
                $invoice->bill_type = 'Purchase';
                $invoice->invoice_type = 'Manual';
                return $invoice;
            });

        $saleInvoices = ManualInvoice::with(['storeCustomer', 'invoiceItems'])
            ->where('payment_status', 'Paid')
            ->where('tax_type', 'gst')
            ->where('vendor_id', $storeId)
            ->when($branchId !== null, function ($q) use ($branchId) {
                $branchId === 0 ? $q->whereNull('pos_branch_id') : $q->where('pos_branch_id', $branchId);
            })
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->map(function ($invoice) {
                $invoice->bill_type = 'Sale';
                $invoice->invoice_type = 'Manual';
                return $invoice;
            });

        $serviceInvoices = ServiceInvoice::with(['websiteUser', 'invoiceItems'])
            ->where('payment_status', 'Paid')
            ->where('tax_type', 'gst')
            ->where('vendor_id', $storeId)
            ->when(is_int($branchId) && $branchId > 0, fn($q) => $q->whereRaw('0=1')) // services aren't branch-scoped
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->map(function ($invoice) {
                $invoice->bill_type = 'Sale';
                $invoice->invoice_type = 'Service';
                return $invoice;
            });

        $invoices = $saleInvoices
            ->merge($purchaseInvoices)
            ->merge($serviceInvoices);

        $saleInvoices = $saleInvoices
            ->merge($serviceInvoices);

        $inRange = function ($collection) use ($formatted_from, $formatted_to) {
            return $collection->filter(function ($invoice) use ($formatted_from, $formatted_to) {
                $date = $invoice->invoice_date ?? $invoice->created_at;
                return $date >= $formatted_from && $date <= $formatted_to;
            });
        };

        return [
            'invoices' => $inRange($invoices),
            'sale' => $inRange($saleInvoices),
            'purchase' => $inRange($purchaseInvoices),
            'service' => $inRange($serviceInvoices),
            'branchId' => $branchId,
        ];
    }

    public function gst(Request $request, $export = false)
    {
        [$preset, $formatted_from, $formatted_to] = $this->reportRange('this_month');

        $sets = $this->gstReportInvoices($request);
        $invoices = $sets['invoices'];
        $saleInvoices = $sets['sale'];
        $purchaseInvoices = $sets['purchase'];
        $serviceInvoices = $sets['service'];
        $branchId = $sets['branchId'];

        $data['formatted_from'] = \Carbon\Carbon::parse($formatted_from)->format('Y-m-d');
        $data['formatted_to'] = \Carbon\Carbon::parse($formatted_to)->format('Y-m-d');
        $data['preset'] = $preset;

        if ($export == 'export-sale') {
            if (hasPermission('gst_report', 'export')) {
                $data = $this->gst_sale_export_excel($saleInvoices, $data);
                return $data;
            } else {
                Toastr::error('Access Denied');
                return back();
            }
        }
        if ($export == 'export-purchase') {
            if (hasPermission('gst_report', 'export')) {
                $data = $this->gst_purchase_export_excel($purchaseInvoices, $data);
                return $data;
            } else {
                Toastr::error('Access Denied');
                return back();
            }
        }
    

        $invoices = $invoices->sortByDesc(function ($invoice) {
            return $invoice->invoice_date ?? $invoice->created_at;
        })
            ->values();


        $branches = $this->reportBranches();
        return view('vendor-views.inventory.report.gst', compact('preset', 'serviceInvoices', 'invoices', 'saleInvoices', 'purchaseInvoices', 'branches', 'branchId'));
    }
    // Every row the Sale Report is currently showing, filters and all.
    private function saleReportQuery(Request $request)
    {
        [, $formatted_from, $formatted_to] = $this->reportRange('last_30_days');
        $search = $request->search ?? '';
        $ids = $request->invoice_ids ?? null;
        $branchId = $this->reportBranchId($request);

        return InventoryOrder::with(['invoice',  'invoice.storeCustomer'])
            ->where('store_id', Helpers::get_store_id())
            ->when($search, function ($q) use ($search) {
                $q->where('invoice_id', 'like', '%' . $search . '%');
            })
            ->when($ids, function ($q) use ($ids) {
                $q->whereIn('id', $ids);
            })
            ->when($branchId !== null, function ($q) use ($branchId) {
                $q->whereHas('invoice', function ($i) use ($branchId) {
                    $branchId === 0 ? $i->whereNull('pos_branch_id') : $i->where('pos_branch_id', $branchId);
                });
            })
            ->whereBetween('created_at', [$formatted_from, $formatted_to]);
    }

    public function sale(Request $request, $export = null, $export_type = null)
    {
        if (hasPermission('sale_report', 'list') || hasPermission('sale_report', 'export')) {

            [$preset, $formatted_from, $formatted_to] = $this->reportRange('last_30_days');

            $storeId = Helpers::get_store_id();
            $branchId = $this->reportBranchId($request);
            $query = $this->saleReportQuery($request);

            $invoices = $query->orderBy('created_at', 'desc')->get();
            $data['unique_items_sold'] = InventoryOrderDetail::whereHas('order', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
                ->distinct('item_id')
                ->count('item_id');

            $data['totalOrderAmount'] = (clone $query)->sum('total_amount');
            $data['totalTaxAmount'] = (clone $query)->sum('tax_amount');

            $data['formatted_from'] = \Carbon\Carbon::parse($formatted_from)->format('Y-m-d');
            $data['formatted_to'] = \Carbon\Carbon::parse($formatted_to)->format('Y-m-d');
            $data['preset'] = $preset;

            if (hasPermission('sale_report', 'export')) {
                if ($export && $export_type == 'pdf') {
                    $data = $this->sale_export_pdf($invoices, $data);
                    return redirect()->to($data['url']);
                } elseif ($export && $export_type == 'excel') {
                    $data = $this->sale_export_excel($invoices, $data);
                    return $data;
                }
            } else {
                Toastr::error('Access Denied');
                return back();
            }

            $branches = $this->reportBranches();
            return view('vendor-views.inventory.report.sale', compact('preset', 'formatted_from', 'formatted_to', 'invoices', 'data', 'branches', 'branchId'));
        } else {
            Toastr::error('Access Denied');
            return back();
        }
    }
    public function gst_sale_export_excel($invoices, $data)
    {
        $fileName = 'gst_report_' . $data['formatted_from'] . '-' . $data['formatted_to'] . '.xlsx';

        $this->save_report($fileName, $data,  'gst_sale', 'excel');

        $headings = [
            'Sl',
            'Date',
            'Invoice Id',
            'Bill Type',
            'Customer/Vendor Name',
            'GST Number',
            'Taxable Value',
            'Tax Rate',
            'Tax Amt.',
            'CGST',
            'SGST',
            'IGST/UGST',
            'Invoice Total'
        ];
        $rows = [];
        foreach ($invoices as $key => $e) {
            // NAME
            $name = null;
            if ($e['invoice_type'] === 'Service' && $e['websiteUser']) {
                $name = trim(
                    $e['websiteUser']?->f_name . ' ' . $e['websiteUser']?->l_name,
                );
            } elseif ($e['bill_type'] === 'Sale' && $e['storeCustomer']) {
                $name = trim(
                    $e['storeCustomer']?->f_name . ' ' . $e['storeCustomer']?->l_name,
                );
            } elseif ($e['seller']) {
                $name = trim(
                    $e['seller']?->f_name . ' ' . $e['seller']?->l_name,
                );
            }
            // GST NUMBER
            $gst = '';
            if ($e['invoice_type'] === 'Service') {
                $gst = $e['websiteUser']?->gst ?? '';
            } elseif ($e['bill_type'] === 'Sale') {
                $gst = $e['storeCustomer']?->gst ?? '';
            } else {
                $gst = $e['seller']?->gst ?? '';
            }

            // TAX RATES 
            $tax_rates  = '';
            if (!empty($e['invoiceItems'])) {
                $taxes = $e['invoiceItems']
                    ->whereNotNull('tax')
                    ->where('tax', '!=', '')
                    ->pluck('tax')
                    ->toArray();
            }

            if (!empty($taxes)) {
                $tax_rates = implode('%, ', $taxes)  . '%';
            }
            $rows[] = [
                $key + 1,
                $e->invoice_date ?? $e->created_at,
                $e->invoice?->invoice_id ?? 'N/A',
                $e['bill_type'] . ' - ' . $e['invoice_type'],
                $name ?? 'N/A',
                $gst,
                _price($e['taxable_amount'] ?? $e['total_amount']),
                $tax_rates,
                _price($e['final_tax']),
                _price($e['cgst']),
                _price($e['sgst']),
                _price($e['igst']),
                _price($e['total_amount'])
            ];
        }
        return Excel::download(new \App\Exports\Vendor\GstReportExport($rows, $headings), $fileName);
    }
    public function gst_purchase_export_excel($invoices, $data)
    {
        $fileName = 'gst_report_' . $data['formatted_from'] . '-' . $data['formatted_to'] . '.xlsx';

        $this->save_report($fileName, $data,  'gst_purchase', 'excel');

        $headings = [
            'Sl',
            'Date',
            'Invoice Id',
            'Bill Type',
            'Customer/Vendor Name',
            'GST Number',
            'Taxable Value',
            'Tax Rate',
            'Tax Amt.',
            'CGST',
            'SGST',
            'IGST/UGST',
            'Invoice Total'
        ];
        $rows = [];
        foreach ($invoices as $key => $e) {
            // NAME
            $name = null;
            if ($e['invoice_type'] === 'Service' && $e['websiteUser']) {
                $name = trim(
                    $e['websiteUser']?->f_name . ' ' . $e['websiteUser']?->l_name,
                );
            } elseif ($e['bill_type'] === 'Sale' && $e['storeCustomer']) {
                $name = trim(
                    $e['storeCustomer']?->f_name . ' ' . $e['storeCustomer']?->l_name,
                );
            } elseif ($e['seller']) {
                $name = trim(
                    $e['seller']?->f_name . ' ' . $e['seller']?->l_name,
                );
            }
            // GST NUMBER
            $gst = '';
            if ($e['invoice_type'] === 'Service') {
                $gst = $e['websiteUser']?->gst ?? '';
            } elseif ($e['bill_type'] === 'Sale') {
                $gst = $e['storeCustomer']?->gst ?? '';
            } else {
                $gst = $e['seller']?->gst ?? '';
            }

            // TAX RATES 
            $tax_rates  = '';
            if (!empty($e['invoiceItems'])) {
                $taxes = $e['invoiceItems']
                    ->whereNotNull('tax')
                    ->where('tax', '!=', '')
                    ->pluck('tax')
                    ->toArray();
            }

            if (!empty($taxes)) {
                $tax_rates = implode('%, ', $taxes)  . '%';
            }
            $rows[] = [
                $key + 1,
                $e->invoice_date ?? $e->created_at,
                $e->invoice?->invoice_id ?? 'N/A',
                $e['bill_type'] . ' - ' . $e['invoice_type'],
                $name ?? 'N/A',
                $gst,
                _price($e['taxable_amount'] ?? $e['total_amount']),
                $tax_rates,
                _price($e['final_tax']),
                _price($e['cgst']),
                _price($e['sgst']),
                _price($e['igst']),
                _price($e['total_amount'])
            ];
        }
        return Excel::download(new \App\Exports\Vendor\GstReportExport($rows, $headings), $fileName);
    }
    public function sale_export_excel($invoices, $data)
    {
        $fileName = 'sales_report_' . $data['formatted_from'] . '-' . $data['formatted_to'] . '.xlsx';

        $this->save_report($fileName, $data,  'sale', 'excel');

        $headings = [
            'Sl',
            'Invoice Id',
            'Date',
            'Customer Name',
            'Total Amount',
            'CGST Amt.',
            'SGST Amt.',
            'IGST Amt.',
            'Payment Status'
        ];
        $rows = [];
        foreach ($invoices as $key => $invoice) {
            $rows[] = [
                $key + 1,
                $invoice->invoice?->invoice_id ?? 'N/A',
                $invoice->invoice_date ?? $invoice->created_at,
                ($customer = $invoice->invoice?->storeCustomer)
                    ? $customer->f_name . ' ' . $customer->l_name
                    : 'Customer Deleted',
                _price($invoice->total_amount),
                $invoice->invoice?->bill_gst_type == 'cgst_sgst' ? _price($invoice->invoice?->final_tax / 2) : '',
                $invoice->invoice?->bill_gst_type == 'cgst_sgst' ? _price($invoice->invoice?->final_tax / 2) : '',
                $invoice->invoice?->bill_gst_type == 'igst' ? _price($invoice->invoice?->final_tax) : '',
                ucfirst($invoice->invoice?->payment_status),
            ];
        }
        return Excel::download(new \App\Exports\Vendor\SaleReportExport($rows, $headings), $fileName);
    }

    public function sale_export_pdf($invoices, $data)
    {
        $fileName = 'sales_report_' . $data['formatted_from'] . '-' . $data['formatted_to'] . '.pdf';

        $this->save_report($fileName, $data,  'sale', 'pdf');


        $store = Helpers::get_store_data();
        $jobcard = View::make('document_templates/sales_report_pdf', compact('invoices', 'data', 'store'))->render();
        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 8,
            'margin_bottom' => 8,
        ]);
        $mpdf->WriteHTML($jobcard);
        if (!Storage::disk('public')->exists('store_reports/sale/')) {
            Storage::disk('public')->makeDirectory('store_reports/sale/');
        }
        $fileUrl = Helpers::savePdfToPublic($mpdf, 'store_reports/sale/', $fileName);
        return ['success' => true, 'file_name' => $fileName,  'url'  => asset('storage/app/public/store_reports/sale/' . $fileName)];
    }
    // Every purchase bill the Purchase Report is currently showing, filters and all.
    private function purchaseReportQuery(Request $request)
    {
        [, $formatted_from, $formatted_to] = $this->reportRange('last_30_days');
        $ids = $request->invoice_ids ?? null;
        $search = $request->search ?? '';
        $vendor = ($request->vendor && $request->vendor !== 'all') ? $request->vendor : '';
        $status = ($request->status && $request->status !== 'all') ? $request->status : '';

        return $this->purchaseReportBaseQuery()
            ->when($search, function ($q) use ($search) {
                $q->where('invoice_id', 'like', '%' . $search . '%');
            })
            ->when($ids, function ($q) use ($ids) {
                $q->whereIn('id', $ids);
            })
            ->when($status, function ($q) use ($status) {
                $q->whereRaw('LOWER(payment_status) = ?', [strtolower($status)]);
            })
            ->when($vendor, function ($query) use ($vendor) {
                $query->whereHas('store', function ($q) use ($vendor) {
                    $q->where('id', $vendor);
                });
            })
            ->whereBetween('created_at', [$formatted_from, $formatted_to]);
    }

    private function purchaseReportBaseQuery()
    {
        return ManualInvoice::withCount('invoiceItems')->with('seller')
            ->where('bill_to', Helpers::get_store_id())
            ->where('user_type', 'store_vendor')
            ->where('bill_to_type', 'vendor');
    }

    public function purchase(Request $request, $export = null, $export_type = null)
    {
        if (hasPermission('purchase_report', 'list') || hasPermission('purchase_report', 'export')) {

            [$preset, $formatted_from, $formatted_to] = $this->reportRange('last_30_days');

            $query = $this->purchaseReportBaseQuery();
            $invoices = $this->purchaseReportQuery($request)->orderBy('invoice_date', 'desc')->get();

            $data['formatted_from'] = \Carbon\Carbon::parse($formatted_from)->format('Y-m-d');
            $data['formatted_to'] = \Carbon\Carbon::parse($formatted_to)->format('Y-m-d');
            $data['preset'] = $preset;
            if (hasPermission('purchase_report', 'export')) {
                if ($export && $export_type == 'excel') {
                    $data = $this->purchase_export_excel($invoices, $data);
                    return redirect()->to($data['url']);
                }
            } else {
                Toastr::error('Access Denied');
                return back();
            }
            $storeIds = (clone $query)->pluck('vendor_id');

            $stores = Store::withoutGlobalScopes()->whereIn('id', $storeIds)->select('name', 'phone', 'id')->get();
            // Purchases are store-wide (not branch-scoped); the branch dropdown is shown for consistency.
            $branches = $this->reportBranches();
            $branchId = $this->reportBranchId($request);
            return view('vendor-views.inventory.report.purchase', compact('preset', 'invoices', 'stores', 'branches', 'branchId'));
        } else {
            Toastr::error('Access Denied');
            return back();
        }
    }
    public function purchase_export_excel($invoices, $data)
    {
        $fileName = 'purchase_report_' . $data['formatted_from'] . '-' . $data['formatted_to'] . '.xlsx';

        $this->save_report($fileName, $data,  'purchase', 'excel');

        $headings = [
            'Sl',
            'Invoice Id',
            'Date',
            'Vendor Name',
            'Total Amount',
            'CGST Amt.',
            'SGST Amt.',
            'IGST Amt.',
            'Payment Status',
            'Total Items',
        ];
        $rows = [];
        foreach ($invoices as $key => $invoice) {
            $rows[] = [
                $key + 1,
                $invoice->invoice_id ?? 'N/A',
                $invoice->invoice_date ?? $invoice->created_at,
                $invoice->store?->name ?? 'Store Deleted',
                _price($invoice->total_amount),
                $invoice->bill_gst_type == 'cgst_sgst' ? _price($invoice->final_tax / 2) : '',
                $invoice->bill_gst_type == 'cgst_sgst' ? _price($invoice->final_tax / 2) : '',
                $invoice->bill_gst_type == 'igst' ? _price($invoice->final_tax) : '',
                ucfirst($invoice->payment_status),
                count($invoice->invoiceItems)
            ];
        }
        return Excel::download(new \App\Exports\Vendor\PurchaseReportExport($rows, $headings), $fileName);
    }
    // Every item the Stock Report is currently showing, including the branch-stock substitution,
    // so a bulk delete acts on exactly the rows on screen.
    private function stockReportItems(Request $request)
    {
        $ids = $request->item_ids ?? null;
        $search = $request->search ?? '';
        $stock_status = ($request->stock_staus && $request->stock_staus !== 'all') ? $request->stock_staus : '';
        $category = ($request->category && $request->category !== 'all') ? $request->category : '';
        $storeId = Helpers::get_store_id();
        $branchId = $this->reportBranchId($request);
        $branchStockMode = is_int($branchId) && $branchId > 0; // showing a specific branch's stock pool
        $items = InventoryItem::with('category')
            ->where('store_id', $storeId)
            ->when($category, function ($query) use ($category) {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('category_id', $category);
                });
            })
            ->when($ids, function ($q) use ($ids) {
                $q->whereIn('id', $ids);
            })
            ->when($search, function ($q) use ($search) {
                $q->where('item_name', $search);
            })
            // Stock-status filter on the main-store stock (skipped for a branch — applied below on branch stock).
            ->when($stock_status && !$branchStockMode, function ($q) use ($stock_status) {
                if ($stock_status == 'low_stock') {
                    $q->whereBetween('stock', [1, 5]);
                } elseif ($stock_status == 'out_of_stock') {
                    $q->where('stock', '<', 1);
                } elseif ($stock_status == 'in_stock') {
                    $q->where('stock', '>', 5);
                }
            })
            ->get();

        // For a specific branch, replace each item's displayed stock with that branch's pool, then
        // apply the stock-status filter on the branch quantity.
        if ($branchStockMode) {
            $branchStock = \Illuminate\Support\Facades\DB::table('pos_branch_stock')
                ->where('branch_id', $branchId)->pluck('stock', 'inventory_item_id');
            $items->each(fn($i) => $i->stock = (float) ($branchStock[$i->id] ?? 0));
            if ($stock_status) {
                $items = $items->filter(function ($i) use ($stock_status) {
                    $s = (float) $i->stock;
                    if ($stock_status == 'low_stock') return $s >= 1 && $s <= 5;
                    if ($stock_status == 'out_of_stock') return $s < 1;
                    if ($stock_status == 'in_stock') return $s > 5;
                    return true;
                })->values();
            }
        }

        return $items;
    }

    public function stock(Request $request, $export = null, $export_type = null)
    {
        if (hasPermission('stock_report', 'list') || hasPermission('stock_report', 'export')) {

            [$preset, $formatted_from, $formatted_to] = $this->reportRange('last_30_days');

            $stock_status = ($request->stock_staus && $request->stock_staus !== 'all') ? $request->stock_staus : '';
            $category = ($request->category && $request->category !== 'all') ? $request->category : '';
            $branchId = $this->reportBranchId($request);
            $items = $this->stockReportItems($request);

            $data['category'] = optional(
                ModelsCategory::where('id', $category)->first()
            )->name ?? 'All';
            $data['stock_status'] = $stock_status ?? 'All';
            $data['formatted_from'] = \Carbon\Carbon::parse($formatted_from)->format('Y-m-d');
            $data['formatted_to'] = \Carbon\Carbon::parse($formatted_to)->format('Y-m-d');
            $data['preset'] = $preset;
            if (hasPermission('stock_report', 'export')) {
                if ($export && $export_type == 'excel') {
                    $data = $this->stock_export_excel($items, $data);
                    return redirect()->to($data['url']);
                } else if ($export && $export_type == 'pdf') {
                    $data = $this->stock_export_pdf($items, $data);
                    return redirect()->to($data['url']);
                }
            } else {
                Toastr::error('Access Denied');
                return back();
            }


            $categories = ModelsCategory::where('module_id', 6)->where('status', 1)->get();
            $branches = $this->reportBranches();
            return view('vendor-views.inventory.report.stock', compact('preset', 'items', 'categories', 'branches', 'branchId'));
        } else {
            Toastr::error('Access Denied');
            return back();
        }
    }

    public function stock_export_excel($items, $data)
    {
        $fileName = 'stock_report_' . $data['formatted_from'] . '-' . $data['formatted_to'] . '.xlsx';

        $this->save_report($fileName, $data,  'stock', 'excel');

        $headings = [
            'Sl',
            'Item Name',
            'Category',
            'Unit Price',
            'Stock Value',
            'Stock'
        ];
        $rows = [];
        foreach ($items as $key => $item) {
            $rows[] = [
                $key + 1,
                $item->item_name ?? 'N/A',
                $item->category?->name ?? 'Deleted',
                _price($item->selling_price),
                _price($item->selling_price * $item->stock),
                $item->stock
            ];
        }
        return Excel::download(new \App\Exports\Vendor\StockReportExport($rows, $headings), $fileName);
    }
    public function stock_export_pdf($items, $data)
    {
        $fileName = 'stock_report_' . $data['formatted_from'] . '-' . $data['formatted_to'] . '.pdf';

        $this->save_report($fileName, $data,  'stock', 'pdf');


        $store_data = Helpers::get_store_data();
        $jobcard = View::make('document_templates/stock_report_pdf', compact('items', 'data', 'store_data'))->render();
        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 8,
            'margin_bottom' => 8,
        ]);
        $mpdf->WriteHTML($jobcard);
        if (!Storage::disk('public')->exists('store_reports/stock2/')) {
            Storage::disk('public')->makeDirectory('store_reports/stock2/');
        }
        $fileUrl = Helpers::savePdfToPublic($mpdf, 'store_reports/stock2/', $fileName);

        return ['success' => true, 'file_name' => $fileName,  'url'  => asset('storage/app/public/store_reports/stock2/' . $fileName)];
    }
    /**
     * Cost of goods sold for one order line, in SQL.
     *
     * Prefers `line_cost`, captured when the sale was billed. The fallback is the old
     * qty × landing_price, which is only right when the line was billed in the item's own unit —
     * a measured variation (three 250g packs off a per-kg item) bills qty 3, so the fallback
     * values it as three kilos and reports a loss on a profitable sale. `base_qty` carries the
     * converted quantity for rows written before line_cost existed but after the backfill ran.
     *
     * Kept as one expression so the totals, the per-row figures and the profit/loss filter can
     * never disagree about what a cost is.
     */
    protected function cogsExpression(): string
    {
        if (!Schema::hasColumn('inventory_order_details', 'line_cost')) {
            return '(inventory_order_details.qty * inventory_items.landing_price)';
        }

        return '(COALESCE(inventory_order_details.line_cost, '
            . 'COALESCE(inventory_order_details.base_qty, inventory_order_details.qty) * inventory_items.landing_price))';
    }

    /**
     * Revenue for one order line, in SQL.
     *
     * Read from `total_price`, never recomputed as qty × unit_price. `qty` is an integer column, so
     * a line sold by weight — 0.565 kg of avocado — stores as 1 and reads back as a whole kilo at
     * the kilo price. total_price is worked out in PHP before it is written, so it is the only
     * faithful record of what the line was billed at. Quantities under one inflated revenue and
     * larger fractional ones deflated it, which is why the report could disagree with the POS
     * dashboard in both directions at once.
     *
     * Less the line's share of any discount, which reduces revenue rather than adding to cost.
     */
    protected function revenueExpression(): string
    {
        $gross = '(CASE WHEN inventory_order_details.total_price > 0 '
            . 'THEN inventory_order_details.total_price '
            . 'ELSE inventory_order_details.qty * inventory_order_details.unit_price END)';

        if (!Schema::hasColumn('inventory_order_details', 'line_discount')) {
            return $gross;
        }

        return "({$gross} - COALESCE(inventory_order_details.line_discount, 0))";
    }

    // The order lines behind the Profit & Loss rows. The report groups them per item; a bulk delete
    // removes the underlying lines, so both start from this one query.
    private function pnlReportQuery(Request $request)
    {
        [, $formatted_from, $formatted_to] = $this->reportRange('last_30_days');

        $storeId = Helpers::get_store_id();
        $branchId = $this->reportBranchId($request);
        $search = $request->search ?? '';
        $status = ($request->status && $request->status !== 'all') ? $request->status : '';
        $category = ($request->category && $request->category !== 'all') ? $request->category : '';

        // Left join on categories: an item with no category (category_id 0 — the sentinel
        // _saveCategoryIfNotExists returns for a blank category) or one whose category row is
        // gone was dropped by the inner join, taking its whole revenue off the report with no
        // sign that anything was missing. The row now shows with "Deleted" as its category.
        return InventoryOrderDetail::with(['order', 'item', 'item.category'])
            ->join('inventory_items', 'inventory_items.id', '=', 'inventory_order_details.item_id')
            ->leftJoin('categories', 'categories.id', '=', 'inventory_items.category_id')
            ->whereBetween('inventory_order_details.created_at', [$formatted_from, $formatted_to])
            ->whereHas('order', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            // A voided bill is not a sale. Voiding restores the stock but leaves the sale-order
            // mirror in place, so its revenue and cost kept counting here.
            ->when(Schema::hasColumn('manual_invoices', 'pos_status'), function ($q) {
                $q->whereDoesntHave('order.invoice', function ($i) {
                    $i->where('pos_status', 'void');
                });
            })
            ->when($branchId !== null, function ($q) use ($branchId) {
                $q->whereHas('order.invoice', function ($i) use ($branchId) {
                    $branchId === 0 ? $i->whereNull('pos_branch_id') : $i->where('pos_branch_id', $branchId);
                });
            })
            ->when($category, function ($q) use ($category) {
                $q->whereHas('item', function ($query) use ($category) {
                    $query->where('category_id', $category);
                });
            })
            ->when($search, function ($q) use ($search) {
                $q->whereHas('item', function ($query) use ($search) {
                    $query->where('item_name', 'like', '%' . $search . '%');
                });
            })
            ->when($status, function ($q) use ($status) {
                $cost = $this->cogsExpression();
                $revenue = $this->revenueExpression();
                if ($status == 'profit') {
                    $q->whereRaw("{$revenue} - {$cost} > 0");
                } elseif ($status == 'loss') {
                    $q->whereRaw("{$revenue} - {$cost} < 0");
                }
            });
    }

    public function profit_and_loss(Request $request, $export = null, $export_type = null)
    {
        Helpers::_ensureInvOrderCostColumns();

        if (hasPermission('profit_loss_summary', 'list') || hasPermission('profit_loss_summary', 'export')) {

            [$preset, $formatted_from, $formatted_to] = $this->reportRange('last_30_days');

            $storeId = Helpers::get_store_id();
            $branchId = $this->reportBranchId($request);
            $invQuery = $this->pnlReportQuery($request);

            $cost = $this->cogsExpression();
            $revenue = $this->revenueExpression();

            $orderItems = (clone $invQuery)
                ->select(
                    'inventory_items.id as item_id',
                    'inventory_items.item_name',
                    'categories.name as cat_name',
                    DB::raw("SUM({$revenue}) as total_revenue"),
                    DB::raw("SUM({$cost}) as total_cost"),
                    DB::raw("SUM({$revenue} - {$cost}) as total_profit_loss")
                )
                ->groupBy('inventory_items.id', 'inventory_items.item_name', 'categories.name')
                ->get();

            // Every bill is rounded to the rupee, so what the till took is never quite what the
            // lines were worth. It belongs in an account of its own rather than spread across item
            // revenue, which would put a few paise of rounding into every margin on the page — but
            // left unstated it reads as the report disagreeing with the POS dashboard. Shown, it is
            // the whole of the difference between the two screens.
            $round_off = 0;
            if (Schema::hasColumn('manual_invoices', 'round_off')) {
                $round_off = (float) ManualInvoice::where('vendor_id', $storeId)
                    ->when($branchId !== null, function ($q) use ($branchId) {
                        $branchId === 0 ? $q->whereNull('pos_branch_id') : $q->where('pos_branch_id', $branchId);
                    })
                    ->whereExists(function ($q) use ($storeId, $formatted_from, $formatted_to) {
                        $q->select(DB::raw(1))->from('inventory_orders as o')
                            ->whereColumn('o.invoice_id', 'manual_invoices.invoice_id')
                            ->whereColumn('o.store_id', 'manual_invoices.vendor_id')
                            ->where('o.store_id', $storeId)
                            ->whereBetween('o.created_at', [$formatted_from, $formatted_to]);
                    })
                    ->sum('round_off');
            }

            // EXPORT EXCEL AND PDF
            $data['formatted_from'] = \Carbon\Carbon::parse($formatted_from)->format('Y-m-d');
            $data['formatted_to'] = \Carbon\Carbon::parse($formatted_to)->format('Y-m-d');
            $data['preset'] = $preset;

            if (hasPermission('profit_loss_summary', 'export')) {

                if ($export && $export_type == 'pdf') {
                    $data = $this->pnl_export_pdf($orderItems, $data);
                    return redirect()->to($data['url']);
                } elseif ($export && $export_type == 'excel') {
                    $data = $this->pnl_export_excel($orderItems, $data);
                    return redirect()->to($data['url']);
                }
            } else {
                Toastr::error('Access Denied');
                return back();
            }
            // VIEW
            $categories = ModelsCategory::where('module_id', 6)->where('status', 1)->get();
            $branches = $this->reportBranches();
            return view('vendor-views.inventory.report.profit_and_loss', compact('preset', 'orderItems', 'categories', 'branches', 'branchId', 'round_off'));
        } else {
            Toastr::error('Access Denied');
            return back();
        }
    }
    public function pnl_export_excel($orderItems, $data)
    {
        $fileName = 'PnL_summary_' . $data['formatted_from'] . '-' . $data['formatted_to'] . '.xlsx';

        $this->save_report($fileName, $data,  'profit_and_loss', 'excel');

        $headings = [
            'Sl',
            'Item Name',
            'Category',
            'Revenue',
            'COGS',
            'Profit / Loss',
        ];
        $rows = [];
        foreach ($orderItems as $key => $orderItem) {
            $pnl_status = $orderItem->total_revenue - $orderItem->total_cost > 0 ? 'Profit' : 'Loss';
            $rows[] = [
                $key + 1,
                ucwords($orderItem->item_name) ?? 'N/A',
                $orderItem->cat_name ?? 'Deleted',
                _price($orderItem->total_revenue, 'ceil', 2),
                _price($orderItem->total_cost, 'ceil', 2),
                _price(abs($orderItem->total_profit_loss), 'ceil', 2) . ' (' . $pnl_status . ')',
            ];
        }
        if (!Storage::disk('public')->exists('store_reports/profit_and_loss/')) {
            Storage::disk('public')->makeDirectory('store_reports/profit_and_loss/');
        }
        $filePath = 'store_reports/profit_and_loss/' . $fileName;
        Excel::store(new ProfitAndLossExport($rows, $headings), $filePath, 'public');

        $url = Storage::disk('public')->url('store_reports/profit_and_loss/' . $fileName);

        return   ['success' => true, 'file_name' => $fileName,  'url'  =>  $url];
    }

    public function pnl_export_pdf($orderItems, $data)
    {
        $fileName = 'PnL_summary_' . $data['formatted_from'] . '-' . $data['formatted_to'] . '.pdf';

        $this->save_report($fileName, $data,  'profit_and_loss', 'pdf');

        $store = Helpers::get_store_data();
        $html = View::make('document_templates/pnl_summary_pdf', compact('orderItems', 'data', 'store'))->render();
        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 8,
            'margin_bottom' => 8,
        ]);
        $mpdf->WriteHTML($html);
        if (!Storage::disk('public')->exists('store_reports/profit_and_loss/')) {
            Storage::disk('public')->makeDirectory('store_reports/profit_and_loss/');
        }
        $fileUrl = Helpers::savePdfToPublic($mpdf, 'store_reports/profit_and_loss', $fileName);
        return ['success' => true, 'file_name' => $fileName,  'url'  => asset('storage/app/public/store_reports/profit_and_loss/' . $fileName)];
    }
    // Batches matching the page's filter. Paginated on screen, so a "delete all" has to come back
    // through here rather than off the checkboxes of the visible page.
    private function batchExpiryQuery(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $filter   = $request->get('filter', 'all'); // all | expired | expiring_soon | active

        $query = ItemEntry::with('item')
            ->where('item_entries.store_id', $store_id)
            ->whereNotNull('item_entries.batch_number')
            ->join('inventory_items', 'inventory_items.id', '=', 'item_entries.item_id')
            ->select('item_entries.*', 'inventory_items.item_name', 'inventory_items.sku_id');

        if ($filter === 'expired') {
            $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now());
        } elseif ($filter === 'expiring_soon') {
            $query->whereNotNull('expiry_date')
                  ->whereDate('expiry_date', '>=', now())
                  ->whereDate('expiry_date', '<=', now()->addDays(90));
        } elseif ($filter === 'active') {
            $query->where(function ($q) {
                $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>', now()->addDays(90));
            });
        }

        return $query;
    }

    public function batchExpiry(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $filter   = $request->get('filter', 'all'); // all | expired | expiring_soon | active

        $batches = $this->batchExpiryQuery($request)->orderBy('expiry_date')->paginate(30);

        $expiredCount      = ItemEntry::where('store_id', $store_id)->whereNotNull('batch_number')->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now())->count();
        $expiringSoonCount = ItemEntry::where('store_id', $store_id)->whereNotNull('batch_number')->whereNotNull('expiry_date')->whereDate('expiry_date', '>=', now())->whereDate('expiry_date', '<=', now()->addDays(90))->count();
        $totalBatches      = ItemEntry::where('store_id', $store_id)->whereNotNull('batch_number')->count();

        return view('vendor-views.inventory.report.batch_expiry', compact(
            'batches', 'filter', 'expiredCount', 'expiringSoonCount', 'totalBatches'
        ));
    }

    public function  save_report($fileName, $data, $type, $fileType)
    {
        $lastReport = InventoryReport::where('store_id', Helpers::get_store_id())
            ->latest('report_id') // order by report_id descending
            ->first();
        $lastId = $lastReport ? $lastReport->report_id : 0;

        $report = new InventoryReport();
        $report->store_id = Helpers::get_store_id();
        $report->report_id = $lastId + 1;
        $report->from_date = $data['formatted_from'];
        $report->to_date = $data['formatted_to'];
        $report->pdf = $fileName;
        $report->type = $type;
        $report->file_type = $fileType;
        $report->save();
    }

    // Delete a sale invoice from the Sale Report (an InventoryOrder + its line items).
    // Optionally add the sold stock back to inventory.
    public function sale_delete(Request $request, $id)
    {
        $storeId = Helpers::get_store_id();
        $order = InventoryOrder::where('store_id', $storeId)->find($id);
        if (!$order) {
            Toastr::error('Sale invoice not found');
            return back();
        }

        $this->deleteSaleOrders([$order], $request->restock == '1');

        Toastr::success('Sale invoice deleted');
        return back();
    }

    // Delete a purchase invoice from the Purchase Report (a ManualInvoice + its items).
    // Optionally remove the purchased stock that the bill had added to inventory.
    public function purchase_delete(Request $request, $id)
    {
        $storeId = Helpers::get_store_id();
        $invoice = ManualInvoice::where('id', $id)->where('bill_to', $storeId)->where('bill_to_type', 'vendor')->first();
        if (!$invoice) {
            Toastr::error('Purchase invoice not found');
            return back();
        }

        $this->deleteManualInvoices([$invoice], $request->restock == '1');

        Toastr::success('Purchase invoice deleted');
        return back();
    }

    /*
     |--------------------------------------------------------------------------
     | Bulk delete from the report pages
     |--------------------------------------------------------------------------
     | Each endpoint takes either an explicit `ids` list (Delete Selected) or `delete_all`,
     | in which case the rows are resolved server-side from the same query that rendered the
     | page — the filters ride along on the query string. Resolving server-side keeps
     | "delete all" honest on a paginated report and avoids posting thousands of ids.
     */

    private function bulkDeleteResponse(int $deleted, string $done = 'deleted')
    {
        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => $deleted . ' record(s) ' . $done,
        ]);
    }

    private function deniedResponse()
    {
        return response()->json(['success' => false, 'message' => 'Access Denied'], 403);
    }

    private function deleteSaleOrders($orders, bool $restock): int
    {
        if (function_exists('_ensureDecimalStockColumns')) {
            _ensureDecimalStockColumns();
        }

        $deleted = 0;
        foreach ($orders as $order) {
            if ($restock) {
                $details = InventoryOrderDetail::where('order_id', $order->order_id)->get();
                foreach ($details as $d) {
                    if (in_array($d->status, ['returned', 'cancelled'])) {
                        continue;
                    }
                    $item = InventoryItem::find($d->item_id);
                    if ($item) {
                        $item->stock = (float) $item->stock + (float) $d->qty;
                        $item->save();
                    }
                }
            }

            InventoryOrderDetail::where('order_id', $order->order_id)->delete();
            $order->delete();
            $deleted++;
        }

        return $deleted;
    }

    private function deleteManualInvoices($invoices, bool $restock): int
    {
        if (function_exists('_ensureDecimalStockColumns')) {
            _ensureDecimalStockColumns();
        }

        $deleted = 0;
        foreach ($invoices as $invoice) {
            if ($restock) {
                $items = \App\Models\InvoiceItem::where('manual_invoice_id', $invoice->id)
                    ->orWhere('rand_invoice_id', $invoice->invoice_id)->get();
                foreach ($items as $it) {
                    if (!$it->inv_id) {
                        continue;
                    }
                    $item = InventoryItem::find($it->inv_id);
                    if ($item) {
                        $item->stock = max(0, (float) $item->stock - (float) $it->qty);
                        $item->save();
                    }
                }
            }

            \App\Models\InvoiceItem::where('manual_invoice_id', $invoice->id)
                ->orWhere('rand_invoice_id', $invoice->invoice_id)->delete();

            if ($invoice->pdf && Storage::disk('public')->exists('invoice/' . $invoice->pdf)) {
                Storage::disk('public')->delete('invoice/' . $invoice->pdf);
            }
            $invoice->delete();
            $deleted++;
        }

        return $deleted;
    }

    // An order whose every line has gone is a shell that would still show up on the Sale Report,
    // so it goes with the last of its lines.
    private function pruneEmptyOrders($orderIds): void
    {
        foreach (collect($orderIds)->unique() as $orderId) {
            if (!InventoryOrderDetail::where('order_id', $orderId)->exists()) {
                InventoryOrder::where('order_id', $orderId)->delete();
            }
        }
    }

    public function sale_bulk_delete(Request $request)
    {
        if (!hasPermission('sale_report', 'delete')) {
            return $this->deniedResponse();
        }

        $query = $this->saleReportQuery($request);
        if (!$request->boolean('delete_all')) {
            $query->whereIn('id', (array) $request->input('ids', []));
        }

        return $this->bulkDeleteResponse(
            $this->deleteSaleOrders($query->get(), $request->restock == '1')
        );
    }

    public function purchase_bulk_delete(Request $request)
    {
        if (!hasPermission('purchase_report', 'delete')) {
            return $this->deniedResponse();
        }

        $query = $this->purchaseReportQuery($request);
        if (!$request->boolean('delete_all')) {
            $query->whereIn('id', (array) $request->input('ids', []));
        }

        return $this->bulkDeleteResponse(
            $this->deleteManualInvoices($query->get(), $request->restock == '1')
        );
    }

    // The GST report lists ManualInvoice and ServiceInvoice rows side by side, so a checkbox
    // carries "manual:12" / "service:5". Ids are matched against the report's own sets, which
    // is also what keeps one store from reaching another's invoice by guessing an id.
    public function gst_bulk_delete(Request $request)
    {
        if (!hasPermission('gst_report', 'delete')) {
            return $this->deniedResponse();
        }

        $side = $request->input('side') === 'purchase' ? 'purchase' : 'sale';
        $sets = $this->gstReportInvoices($request);
        $rows = $sets[$side];

        if (!$request->boolean('delete_all')) {
            $wanted = array_map('strval', (array) $request->input('ids', []));
            $rows = $rows->filter(function ($invoice) use ($wanted) {
                $type = ($invoice->invoice_type ?? 'Manual') === 'Service' ? 'service' : 'manual';
                return in_array($type . ':' . $invoice->id, $wanted, true);
            });
        }

        $restock = $request->restock == '1';
        $storeId = Helpers::get_store_id();
        $deleted = 0;

        foreach ($rows as $invoice) {
            if ($invoice instanceof ServiceInvoice) {
                \App\Models\InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->delete();
                if ($invoice->pdf && Storage::disk('public')->exists('invoice/' . $invoice->pdf)) {
                    Storage::disk('public')->delete('invoice/' . $invoice->pdf);
                }
                $invoice->delete();
                $deleted++;
                continue;
            }

            if ($side === 'sale') {
                // A sale bill is mirrored into inventory_orders; dropping the bill without the
                // mirror would leave the Sale Report and P&L quoting an invoice that is gone.
                // Restocking a sale means putting the sold stock back, which the mirror handles —
                // so the bill itself is removed without touching stock a second time.
                $orders = InventoryOrder::where('store_id', $storeId)
                    ->where('invoice_id', $invoice->invoice_id)->get();
                $this->deleteSaleOrders($orders, $restock);
                $deleted += $this->deleteManualInvoices([$invoice], false);
                continue;
            }

            $deleted += $this->deleteManualInvoices([$invoice], $restock);
        }

        return $this->bulkDeleteResponse($deleted);
    }

    /**
     * Clear the stock behind the selected Stock Report rows.
     *
     * The item itself is never touched — not its price, category, sale history or batches. A stock
     * figure is what this report is about, so that is all this clears; removing an item from the
     * catalogue is a separate, far more destructive act and belongs on the Items page.
     *
     * What gets zeroed follows the page's own branch filter: viewing a branch clears that branch's
     * pool alone, leaving the main store and every other branch as they were.
     */
    public function stock_bulk_delete(Request $request)
    {
        if (!hasPermission('stock_report', 'delete')) {
            return $this->deniedResponse();
        }

        $items = $this->stockReportItems($request);
        if (!$request->boolean('delete_all')) {
            $ids = array_map('strval', (array) $request->input('ids', []));
            $items = $items->filter(fn($i) => in_array((string) $i->id, $ids, true));
        }

        if (function_exists('_ensureDecimalStockColumns')) {
            _ensureDecimalStockColumns();
        }

        $branchId = $this->reportBranchId($request);
        $branchMode = is_int($branchId) && $branchId > 0;
        $itemIds = $items->pluck('id')->all();
        if (empty($itemIds)) {
            return $this->bulkDeleteResponse(0);
        }

        if ($branchMode) {
            $cleared = DB::table('pos_branch_stock')
                ->where('branch_id', $branchId)
                ->whereIn('inventory_item_id', $itemIds)
                ->update(['stock' => 0, 'updated_at' => now()]);

            return $this->bulkDeleteResponse($cleared, "cleared");
        }

        $cleared = 0;
        foreach (InventoryItem::where('store_id', Helpers::get_store_id())->whereIn('id', $itemIds)->get() as $item) {
            $item->stock = 0;

            // A countable item's real counts live per variation and the main figure is their sum,
            // so zeroing only the main figure would leave the item page still showing stock and the
            // next save adding it straight back.
            $variations = _itemVariations($item);
            if (!empty($variations) && _variationMode($item) === 'countable') {
                foreach ($variations as $i => $var) {
                    $variations[$i]['stock'] = 0;
                }
                $item->variations = json_encode(array_values($variations));
            }

            $item->save();
            $cleared++;
        }

        return $this->bulkDeleteResponse($cleared, "cleared");
    }

    // A P&L row is a group of order lines for one item, so deleting it deletes those lines within
    // the report's date range — not the item, and not anything sold outside the range.
    public function pnl_bulk_delete(Request $request)
    {
        if (!hasPermission('profit_loss_summary', 'delete')) {
            return $this->deniedResponse();
        }

        Helpers::_ensureInvOrderCostColumns();

        $query = $this->pnlReportQuery($request);
        if (!$request->boolean('delete_all')) {
            $query->whereIn('inventory_order_details.item_id', (array) $request->input('ids', []));
        }

        $detailIds = (clone $query)->pluck('inventory_order_details.id');
        $orderIds = (clone $query)->pluck('inventory_order_details.order_id');

        if ($request->restock == '1') {
            if (function_exists('_ensureDecimalStockColumns')) {
                _ensureDecimalStockColumns();
            }
            $lines = InventoryOrderDetail::whereIn('id', $detailIds)->get();
            foreach ($lines as $d) {
                if (in_array($d->status, ['returned', 'cancelled'])) {
                    continue;
                }
                $item = InventoryItem::find($d->item_id);
                if ($item) {
                    $item->stock = (float) $item->stock + (float) $d->qty;
                    $item->save();
                }
            }
        }

        $deleted = InventoryOrderDetail::whereIn('id', $detailIds)->delete();
        $this->pruneEmptyOrders($orderIds);

        return $this->bulkDeleteResponse($deleted);
    }

    public function batch_expiry_bulk_delete(Request $request)
    {
        if (!hasPermission('inventory_item_entry', 'delete')) {
            return $this->deniedResponse();
        }

        $query = $this->batchExpiryQuery($request);
        if (!$request->boolean('delete_all')) {
            $query->whereIn('item_entries.id', (array) $request->input('ids', []));
        }

        $batches = $query->get();
        $restock = $request->restock == '1';

        if ($restock && function_exists('_ensureDecimalStockColumns')) {
            _ensureDecimalStockColumns();
        }

        $deleted = 0;
        foreach ($batches as $batch) {
            if ($restock) {
                $item = InventoryItem::find($batch->item_id);
                if ($item) {
                    $item->stock = max(0, (float) $item->stock - (float) $batch->quantity);
                    $item->save();
                }
            }
            ItemEntry::where('id', $batch->id)->delete();
            $deleted++;
        }

        return $this->bulkDeleteResponse($deleted);
    }
}
