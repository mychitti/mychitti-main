<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Enums\ExportFileNames\Admin\Category;
use App\Exports\ProfitAndLossExport;
use App\Exports\PurchaseExport;
use App\Exports\SaleExport;
use App\Exports\StockExport;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Category as ModelsCategory;
use App\Models\InventoryItem;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\ManualInvoice;
use App\Models\InventoryReport;
use App\Models\ServiceInvoice;
use App\Models\Store;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;

class InventoryReportController extends Controller
{
    public function gst(Request $request, $export  = false)
    {
        $storeId = 0;
        $preset = request('date_range') ?? 'this_month';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

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

        if ($export != 'export') {
            $invoices = $invoices->filter(function ($invoice) use ($formatted_from, $formatted_to) {
                $date = $invoice->invoice_date ?? $invoice->created_at;
                return $date >= $formatted_from && $date <= $formatted_to;
            });
            $saleInvoices = $saleInvoices->filter(function ($invoice) use ($formatted_from, $formatted_to) {
                $date = $invoice->invoice_date ?? $invoice->created_at;
                return $date >= $formatted_from && $date <= $formatted_to;
            });
            $purchaseInvoices = $purchaseInvoices->filter(function ($invoice) use ($formatted_from, $formatted_to) {
                $date = $invoice->invoice_date ?? $invoice->created_at;
                return $date >= $formatted_from && $date <= $formatted_to;
            });
            $serviceInvoices = $serviceInvoices->filter(function ($invoice) use ($formatted_from, $formatted_to) {
                $date = $invoice->invoice_date ?? $invoice->created_at;
                return $date >= $formatted_from && $date <= $formatted_to;
            });
        }

        $invoices = $invoices->sortByDesc(function ($invoice) {
            return $invoice->invoice_date ?? $invoice->created_at;
        })
            ->values();


        return view('admin-views.inventory.report.gst', compact('preset', 'serviceInvoices', 'invoices', 'saleInvoices', 'purchaseInvoices'));
    }
    public function sale(Request $request, $export = null, $export_type = null)
    {
        if (hasPermission('sale_report', 'list') || hasPermission('sale_report', 'export')) {

            $preset = request('date_range') ?? 'last_30_days';
            $custom = request('custom_date_range') ?? null;
            $range = Helpers::calculatePresetDates($preset, $custom);
            $formatted_from  = $range['start'];
            $formatted_to = $range['end'];
            $search = $request->search ?? '';
            $ids = $request->invoice_ids ?? null;

            $storeId = 0;
            $query = InventoryOrder::with(['invoice',  'invoice.storeCustomer', 'invoice.websiteUser', 'invoice.websiteVendor'])
                ->where('store_id', $storeId)
                ->when($search, function ($q) use ($search) {
                    $q->where('invoice_id', 'like', '%' . $search . '%');
                })
                ->when($ids, function ($q) use ($ids) {
                    $q->whereIn('id', $ids);
                })
                ->whereBetween('created_at', [$formatted_from, $formatted_to]);

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
                    return redirect()->to($data['url']);
                }
            } else {
                Toastr::error('Access Denied');
                return back();
            }

            return view('admin-views.inventory.report.sale', compact('preset', 'formatted_from', 'formatted_to', 'invoices', 'data'));
        } else {
            Toastr::error('Access Denied');
            return back();
        }
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
                $this->getInvoiceCustomerName($invoice),
                _price($invoice->total_amount),
                $invoice->invoice?->bill_gst_type == 'cgst_sgst' ? _price($invoice->invoice?->final_tax / 2) : '',
                $invoice->invoice?->bill_gst_type == 'cgst_sgst' ? _price($invoice->invoice?->final_tax / 2) : '',
                $invoice->invoice?->bill_gst_type == 'igst' ? _price($invoice->invoice?->final_tax) : '',
                ucfirst($invoice->invoice?->payment_status),
            ];
        }
        if (!Storage::disk('public')->exists('store_reports/sale/')) {
            Storage::disk('public')->makeDirectory('store_reports/sale/');
        }
        $filePath = 'store_reports/sale/' . $fileName;
        Excel::store(new SaleExport($rows, $headings), $filePath, 'public');
        $url = Storage::disk('public')->url('store_reports/sale/' . $fileName);
        return   ['success' => true, 'file_name' => $fileName,  'url'  => $url];
    }

    public function sale_export_pdf($invoices, $data)
    {
        $fileName = 'sales_report_' . $data['formatted_from'] . '-' . $data['formatted_to'] . '.pdf';

        $this->save_report($fileName, $data,  'sale', 'pdf');

          if (auth('admin')->check()) {
            $logo = BusinessSetting::where("key", 'logo')->first()?->value;
            $store = (object)[
                'name' =>  BusinessSetting::where("key", 'business_name')->first()?->value,
                'logo' => $logo,
                'logo_path' => asset('storage/business/') . '/' . $logo,
                'gst' =>  json_encode(['code' => BusinessSetting::where("key", 'gst_number')->first()?->value]),
                'email' =>  BusinessSetting::where("key", 'email_address')->first()?->value,
                'phone' =>  BusinessSetting::where("key", 'phone')->first()?->value,
                'address' =>  BusinessSetting::where("key", 'address')->first()?->value,
                'logo_dir' =>  'business/'
            ];
        } else {
            $store = Helpers::get_store_data();
            $store->logo_path =  asset('storage/store/') . '/' . $store->logo;
            $store->logo_dir =  'store/';
        }


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
    public function purchase(Request $request, $export = null, $export_type = null)
    {
        if (hasPermission('purchase_report', 'list') || hasPermission('purchase_report', 'export')) {

            $preset = request('date_range') ?? 'last_30_days';
            $custom = request('custom_date_range') ?? null;
            $range = Helpers::calculatePresetDates($preset, $custom);
            $formatted_from  = $range['start'];
            $formatted_to = $range['end'];

            $ids = $request->invoice_ids ?? null;
            $search = $request->search ?? '';
            $vendor = ($request->vendor && $request->vendor !== 'all') ? $request->vendor : '';
            $status = ($request->status && $request->status !== 'all') ? $request->status : '';
            // prx($vendor);

            $storeId = 0;
            // prx($storeId);
            $query = ManualInvoice::withCount('invoiceItems')->with('seller')
                ->where('bill_to', $storeId)
                ->where('user_type', 'store_vendor')
                ->where('bill_to_type', 'vendor');
            $invoices =  (clone $query)->where('bill_to', $storeId)->where('bill_to_type', 'vendor')
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

                ->whereBetween('created_at', [$formatted_from, $formatted_to])->orderBy('invoice_date', 'desc')->get();
            // prx($invoices);
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
            // prx($invoices);

            return view('admin-views.inventory.report.purchase', compact('preset', 'invoices', 'stores'));
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
        if (!Storage::disk('public')->exists('store_reports/purchase/')) {
            Storage::disk('public')->makeDirectory('store_reports/purchase/');
        }
        $filePath = 'store_reports/purchase/' . $fileName;

        $url = Storage::disk('public')->url('store_reports/purchase/' . $fileName);

        Excel::store(new PurchaseExport($rows, $headings), $filePath, 'public');

        return   ['success' => true, 'file_name' => $fileName,  'url'  => $url];
    }
    public function stock(Request $request, $export = null, $export_type = null)
    {
        if (hasPermission('stock_report', 'list') || hasPermission('stock_report', 'export')) {

            $preset = request('date_range') ?? 'last_30_days';
            $custom = request('custom_date_range') ?? null;
            $range = Helpers::calculatePresetDates($preset, $custom);
            $formatted_from = $range['start'];
            $formatted_to = $range['end'];

            $ids = $request->item_ids ?? null;
            $search = $request->search ?? '';
            $stock_status = ($request->stock_staus && $request->stock_staus !== 'all') ? $request->stock_staus : '';
            $category = ($request->category && $request->category !== 'all') ? $request->category : '';
            // prx($category);
            $storeId = 0;
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
                ->when($stock_status, function ($q) use ($stock_status) {
                    if ($stock_status == 'low_stock') {
                        $q->whereBetween('stock', [1, 5]);
                    } elseif ($stock_status == 'out_of_stock') {
                        $q->where('stock', '<', 1);
                    } elseif ($stock_status == 'in_stock') {
                        $q->where('stock', '>', 5);
                    }
                })
                ->get();
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
            return view('admin-views.inventory.report.stock', compact('preset', 'items', 'categories'));
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
        if (!Storage::disk('public')->exists('store_reports/stock2/')) {
            Storage::disk('public')->makeDirectory('store_reports/stock2/');
        } else {
        }
        $filePath = 'store_reports/stock2/' . $fileName;
        Excel::store(new StockExport($rows, $headings), $filePath, 'public');
        $url = Storage::disk('public')->url('store_reports/stock2/' . $fileName);
        return   ['success' => true, 'file_name' => $fileName,  'url'  =>  $url];
    }
    public function stock_export_pdf($items, $data)
    {
        $fileName = 'stock_report_' . $data['formatted_from'] . '-' . $data['formatted_to'] . '.pdf';

        $this->save_report($fileName, $data,  'stock', 'pdf');

        if (auth('admin')->check()) {
            $logo = BusinessSetting::where("key", 'logo')->first()?->value;
            $store_data = (object)[
                'name' =>  BusinessSetting::where("key", 'business_name')->first()?->value,
                'logo' => $logo,
                'logo_path' => asset('storage/business/') . '/' . $logo,
                'gst' =>  json_encode(['code' => BusinessSetting::where("key", 'gst_number')->first()?->value]),
                'email' =>  BusinessSetting::where("key", 'email_address')->first()?->value,
                'phone' =>  BusinessSetting::where("key", 'phone')->first()?->value,
                'address' =>  BusinessSetting::where("key", 'address')->first()?->value,
                'logo_dir' =>  'business/'
            ];
        } else {
            $store_data = Helpers::get_store_data();
            $store_data->logo_path =  asset('storage/store/') . '/' . $store->logo;
            $store_data->logo_dir =  'store/';
        }

        // prx($store_data->logo_path);
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
     * Cost of goods sold for one order line, in SQL. Mirrors the vendor report exactly — the two
     * screens showing different profit for the same sale is its own kind of wrong.
     *
     * Prefers `line_cost`, captured when the sale was billed, because `qty` is whatever unit the
     * line was billed in: a measured variation bills three 250g packs as qty 3, and multiplying
     * that by a per-kilo landing price reports a loss on a profitable sale.
     */
    protected function cogsExpression(): string
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('inventory_order_details', 'line_cost')) {
            return '(inventory_order_details.qty * inventory_items.landing_price)';
        }

        return '(COALESCE(inventory_order_details.line_cost, '
            . 'COALESCE(inventory_order_details.base_qty, inventory_order_details.qty) * inventory_items.landing_price))';
    }

    public function profit_and_loss(Request $request, $export = null, $export_type = null)
    {
        Helpers::_ensureInvOrderCostColumns();

        if (hasPermission('profit_loss_summary', 'list') || hasPermission('profit_loss_summary', 'export')) {

            $preset = request('date_range') ?? 'last_30_days';
            $custom = request('custom_date_range') ?? null;
            $range = Helpers::calculatePresetDates($preset, $custom);
            $formatted_from  = $range['start'];
            $formatted_to = $range['end'];

            $storeId = 0;
            $search = $request->search ?? '';
            $status = ($request->status && $request->status !== 'all') ? $request->status : '';
            $category = ($request->category && $request->category !== 'all') ? $request->category : '';

            $invQuery = InventoryOrderDetail::with(['order', 'item', 'item.category'])
                ->join('inventory_items', 'inventory_items.id', '=', 'inventory_order_details.item_id')
                ->join('categories', 'categories.id', '=', 'inventory_items.category_id')
                ->whereBetween('inventory_order_details.created_at', [$formatted_from, $formatted_to])
                ->whereHas('order', function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
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
                    if ($status == 'profit') {
                        $q->whereRaw("(inventory_order_details.qty * inventory_order_details.unit_price) - {$cost} > 0");
                    } elseif ($status == 'loss') {
                        $q->whereRaw("(inventory_order_details.qty * inventory_order_details.unit_price) - {$cost} < 0");
                    }
                });

            $cost = $this->cogsExpression();

            $orderItems = (clone $invQuery)
                ->select(
                    'inventory_items.id as item_id',
                    'inventory_items.item_name',
                    'categories.name as cat_name',
                    DB::raw('SUM(inventory_order_details.qty * inventory_order_details.unit_price) as total_revenue'),
                    DB::raw("SUM({$cost}) as total_cost"),
                    DB::raw("SUM((inventory_order_details.qty * inventory_order_details.unit_price) - {$cost}) as total_profit_loss")
                )
                ->groupBy('inventory_items.id', 'inventory_items.item_name', 'categories.name')
                ->get();

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
            return view('admin-views.inventory.report.profit_and_loss', compact('preset', 'orderItems', 'categories'));
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

         if (auth('admin')->check()) {
            $logo = BusinessSetting::where("key", 'logo')->first()?->value;
            $store = (object)[
                'name' =>  BusinessSetting::where("key", 'business_name')->first()?->value,
                'logo' => $logo,
                'logo_path' => asset('storage/business/') . '/' . $logo,
                'gst' =>  json_encode(['code' => BusinessSetting::where("key", 'gst_number')->first()?->value]),
                'email' =>  BusinessSetting::where("key", 'email_address')->first()?->value,
                'phone' =>  BusinessSetting::where("key", 'phone')->first()?->value,
                'address' =>  BusinessSetting::where("key", 'address')->first()?->value,
                'logo_dir' =>  'business/'
            ];
        } else {
            $store = Helpers::get_store_data();
            $store->logo_path =  asset('storage/store/') . '/' . $store->logo;
            $store->logo_dir =  'store/';
        }

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
    public function  save_report($fileName, $data, $type, $fileType)
    {
        $lastReport = InventoryReport::where('store_id', 0)
            ->latest('report_id') // order by report_id descending
            ->first();
        $lastId = $lastReport ? $lastReport->report_id : 0;

        $report = new InventoryReport();
        $report->store_id = 0;
        $report->report_id = $lastId + 1;
        $report->from_date = $data['formatted_from'];
        $report->to_date = $data['formatted_to'];
        $report->pdf = $fileName;
        $report->type = $type;
        $report->file_type = $fileType;
        $report->save();
    }

    private function getInvoiceCustomerName($invoice)
    {
        $inv = $invoice->invoice;
        if (!$inv) return 'Customer Deleted';

        if ($inv->bill_to_type == 'vendor' && $inv->websiteVendor) {
            return $inv->websiteVendor->name;
        } elseif ($inv->bill_to_type == 'user' && $inv->websiteUser) {
            return $inv->websiteUser->f_name . ' ' . $inv->websiteUser->l_name;
        } elseif ($inv->storeCustomer) {
            return $inv->storeCustomer->f_name . ' ' . $inv->storeCustomer->l_name;
        } 

        return 'Customer Deleted';
    }
}
