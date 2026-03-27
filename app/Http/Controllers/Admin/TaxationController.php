<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Exports\TaxReportExport;
use App\Models\ManualInvoice;

use App\Models\ServiceInvoice;
use Maatwebsite\Excel\Facades\Excel;

class TaxationController extends Controller
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
            ->where('bill_to_type', 'admin')
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

        if ($export == 'export') {
            return Excel::download($this->exportGst($invoices), 'gst_taxation.xlsx');
            _auditLogs('Exported gst taxation');
        }

        return view('admin-views.account.taxation.gst', compact('preset', 'serviceInvoices', 'invoices', 'saleInvoices', 'purchaseInvoices'));
    }
    public function exportGst($invoices)
    {
        $headings = [
            'Sl',
            'Date',
            'Invoice Id',
            'Bill Type',
            'Customer / Vendor Name',
            'GST Number',
            'Taxable Value',
            'Tax Rate',
            'Tax Amount',
            'CGST',
            'SGST',
            'IGST / UGST',
            'Invoice Total',
        ];
        $rows = [];
        foreach ($invoices as $key => $e) {

            $name = null;

            if ($e['invoice_type'] === 'Service' && $e['websiteUser']) {
                $name = trim($e['websiteUser']?->f_name . ' ' . $e['websiteUser']?->l_name);
            } elseif ($e['bill_type'] === 'Sale' && $e['storeCustomer']) {
                $name = trim($e['storeCustomer']?->f_name . ' ' . $e['storeCustomer']?->l_name);
            } elseif ($e['seller']) {
                $name = trim($e['seller']?->f_name . ' ' . $e['seller']?->l_name);
            }

            if ($e['invoice_type'] === 'Service') {
                $gst = $e['websiteUser']?->gst ?? '';
            } elseif ($e['bill_type'] === 'Sale') {
                $gst = $e['storeCustomer']?->gst ?? '';
            } else {
                $gst = $e['seller']?->gst ?? '';
            }

            if (!empty($e['invoiceItems'])) {
                $taxes = $e['invoiceItems']
                    ->whereNotNull('tax')
                    ->where('tax', '!=', '')
                    ->pluck('tax')
                    ->toArray();
                if (!empty($taxes)) {
                    implode('%, ', $taxes) . '%';
                }
            }



            $rows[] = [
                $key + 1,
                $e['invoice_date'] ?? date('Y-m-d', strtotime($e['created_at'])),
                $e->invoice_id,
                $e['bill_type'],
                $name ?? '',
                $gst ?? '',
                _price($e['taxable_amount'] ?? $e['total_amount']),
                $taxes,
                _price($e['final_tax']),
                _price($e['cgst']),
                _price($e['sgst']),
                _price($e['igst']),
                _price($e['total_amount'])
            ];
        }

        return new TaxReportExport($rows, $headings);
    }
}
