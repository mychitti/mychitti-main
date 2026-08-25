<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\IpdAdmission;
use App\Models\InventoryItem;
use App\Models\InvoiceItem;
use App\Models\LabOrder;
use App\Models\ManualInvoice;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\RadiologyInvoice;
use App\Models\RadiologyStudy;
use App\Services\InvoiceShare;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HospitalBillController extends Controller
{
    public function searchInventory(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $q        = $request->input('q', '');

        $items = InventoryItem::where('store_id', $store_id)
            ->where('item_name', 'like', '%' . $q . '%')
            ->select('id', 'item_name', 'mrp', 'hsn', 'unit', 'gst_rate', 'gst_status')
            ->limit(20)
            ->get();

        return response()->json($items);
    }

    public function createForIPD($admissionId)
    {
        $store_id  = Helpers::get_store_id();
        $admission = IpdAdmission::where('store_id', $store_id)
            ->with(['patient', 'ward', 'bed', 'doctorProfile.employee'])
            ->findOrFail($admissionId);

        $patient = $admission->patient;

        $dischargeDate = $admission->discharge_date ?? now();
        $days          = (int) $admission->admission_date?->diffInDays($dischargeDate) + 1;

        $serviceItems = [];

        if ($admission->daily_charge > 0) {
            $label = ($admission->bed?->bed_number ? 'Bed ' . $admission->bed->bed_number . ' — ' : '')
                . ($admission->ward?->ward_name ?? 'Ward')
                . ' (' . $days . ' day' . ($days > 1 ? 's' : '') . ')';

            $serviceItems[] = [
                'name'  => $label,
                'qty'   => $days,
                'price' => $admission->daily_charge,
            ];
        }

        $doctorName = trim(
            'Dr. '
            . ($admission->doctorProfile?->employee?->f_name ?? '')
            . ' ' . ($admission->doctorProfile?->employee?->l_name ?? '')
        );
        $serviceItems[] = [
            'name'  => 'Consultation Fee — ' . $doctorName,
            'qty'   => 1,
            'price' => $admission->doctorProfile?->consultation_fee ?? 0,
        ];

        $prescriptions = Prescription::where('store_id', $store_id)
            ->where('patient_id', $patient->id)
            ->whereBetween('created_at', [
                $admission->admission_date->startOfDay(),
                ($admission->discharge_date ?? now())->endOfDay(),
            ])
            ->with('items.inventoryItem')
            ->get();

        $medicineItems = [];
        foreach ($prescriptions as $rx) {
            foreach ($rx->items as $item) {
                $medicineItems[] = [
                    'name'   => $item->medicine_name,
                    'qty'    => $item->quantity ?? 1,
                    'price'  => $item->inventoryItem?->mrp ?? 0,
                    'inv_id' => $item->inventory_item_id,
                    'hsn'    => $item->inventoryItem?->hsn ?? '',
                ];
            }
        }

        $context   = 'ipd';
        $contextId = $admissionId;

        // An admission has no intake visit of its own, so only the patient's standing rows apply.
        $customInfo   = DentalIntakeController::decode($patient->custom_info ?? null);
        $presetLabels = DentalIntakeController::PRESET_LABELS;
        $existingReceipts = $this->fetchExistingReceipts($store_id, $patient->id);

        return view('hmis::vendor.hospital.create_bill', compact(
            'patient', 'serviceItems', 'medicineItems',
            'context', 'contextId', 'admission', 'customInfo', 'presetLabels', 'existingReceipts'
        ));
    } 

    public function createForOPD($visitId)
    {
        $store_id = Helpers::get_store_id();
        $visit    = OpdVisit::where('store_id', $store_id)
            ->with(['patient', 'doctorProfile.employee'])
            ->findOrFail($visitId);

        $patient = $visit->patient;

        $doctorName = trim(
            'Dr. '
            . ($visit->doctorProfile?->employee?->f_name ?? '')
            . ' ' . ($visit->doctorProfile?->employee?->l_name ?? '')
        );

        // Check if an OPD Consultation Receipt already exists for this visit or patient+doctor
        $hasConsultationReceipt = false;
        if ($visit->consultation_receipt_id) {
            $hasConsultationReceipt = true;
        } else {
            $hasConsultationReceipt = \App\Models\OpdConsultationReceipt::where('store_id', $store_id)
                ->where('opd_visit_id', $visitId)
                ->exists();
        }

        $serviceItems = [];
        // Only include consultation charge if not already receipted/paid
        if (!$hasConsultationReceipt) {
            $serviceItems[] = [
                'name'  => 'OPD Consultation — ' . $doctorName,
                'qty'   => 1,
                'price' => $visit->doctorProfile?->consultation_fee ?? 0,
            ];
        }

        // Tests and scans raised during this visit belong on the same bill as the consultation.
        // Anything the Lab/Radiology modules already invoiced themselves is skipped — those are
        // separate invoice trails and pulling them in again would charge the patient twice.
        foreach ($this->testChargesForVisit($store_id, $patient->id, $visitId, $visit->visit_date) as $line) {
            $serviceItems[] = $line;
        }

        $prescriptions = Prescription::where('store_id', $store_id)
            ->where('patient_id', $patient->id)
            ->whereDate('created_at', $visit->visit_date)
            ->with('items.inventoryItem')
            ->get();

        $medicineItems = [];
        foreach ($prescriptions as $rx) {
            foreach ($rx->items as $item) {
                $medicineItems[] = [
                    'name'   => $item->medicine_name,
                    'qty'    => $item->quantity ?? 1,
                    'price'  => $item->inventoryItem?->mrp ?? 0,
                    'inv_id' => $item->inventory_item_id,
                    'hsn'    => $item->inventoryItem?->hsn ?? '',
                ];
            }
        }

        $context   = 'opd';
        $contextId = $visitId;

        // Whatever the intake recorded for this patient and this visit, ready to print above the
        // lines. Visit values win over the patient's standing ones — see mergedFor().
        $customInfo   = DentalIntakeController::mergedFor($visit);
        $presetLabels = DentalIntakeController::PRESET_LABELS;
        $existingReceipts = $this->fetchExistingReceipts($store_id, $patient->id, $visitId);

        return view('hmis::vendor.hospital.create_bill', compact(
            'patient', 'serviceItems', 'medicineItems',
            'context', 'contextId', 'visit', 'customInfo', 'presetLabels', 'existingReceipts'
        ));
    }

    /**
     * Gather all existing consultation receipts, bill payments, and invoices for this patient/visit
     * to display in the bottom Receipts & Payment History section.
     */
    private function fetchExistingReceipts(int $storeId, int $patientId, $visitId = null): array
    {
        $receipts = [];

        // 1. OPD Consultation Receipts
        if (\Illuminate\Support\Facades\Schema::hasTable('opd_consultation_receipts')) {
            $cReceipts = \App\Models\OpdConsultationReceipt::where('store_id', $storeId)
                ->where(function ($q) use ($visitId, $patientId) {
                    if ($visitId) {
                        $q->where('opd_visit_id', $visitId)
                          ->orWhere('patient_id', $patientId);
                    } else {
                        $q->where('patient_id', $patientId);
                    }
                })
                ->orderByDesc('id')
                ->get();

            foreach ($cReceipts as $cr) {
                $receipts[] = [
                    'type'       => 'Consultation Receipt',
                    'receipt_no' => 'REC-' . str_pad($cr->bill_no, 5, '0', STR_PAD_LEFT),
                    'date'       => $cr->receipt_date ? \Carbon\Carbon::parse($cr->receipt_date)->format('d M Y') : '—',
                    'item_name'  => 'OPD Consultation',
                    'amount'     => (float) $cr->amount,
                    'paid'       => (float) $cr->paid,
                    'due'        => (float) $cr->due,
                    'mode'       => $cr->payment_mode ?: 'Cash',
                    'status'     => $cr->due > 0 ? 'Partial' : 'Paid',
                    'billed_by'  => $cr->billed_by ?: 'Desk',
                    'pdf_url'    => $cr->opd_visit_id ? route('vendor.opd.consultation-receipt.pdf', $cr->opd_visit_id) : null,
                ];
            }
        }

        // 2. Manual Hospital Invoices & Invoice Payments
        $invoices = ManualInvoice::where('vendor_id', $storeId)
            ->where('bill_to', $patientId)
            ->where('bill_to_type', 'patient')
            ->orderByDesc('id')
            ->get();

        foreach ($invoices as $inv) {
            $pmts = DB::table('invoice_payments')
                ->where('invoice_type', 'manual')
                ->where('invoice_id', $inv->id)
                ->orderBy('id')
                ->get();

            if ($pmts->isNotEmpty()) {
                foreach ($pmts as $pmt) {
                    $receipts[] = [
                        'type'       => 'Bill Payment Receipt',
                        'receipt_no' => $pmt->receipt_no,
                        'date'       => $pmt->payment_date ? \Carbon\Carbon::parse($pmt->payment_date)->format('d M Y') : '—',
                        'item_name'  => 'Hospital Bill #' . $inv->invoice_id,
                        'amount'     => (float) $inv->total_amount,
                        'paid'       => (float) $pmt->amount,
                        'due'        => (float) $pmt->balance_after,
                        'mode'       => $pmt->payment_mode ?: 'Cash',
                        'status'     => $pmt->balance_after <= 0 ? 'Paid' : 'Partial',
                        'billed_by'  => 'Desk',
                        'pdf_url'    => $pmt->pdf ? asset('storage/app/public/receipt/' . $pmt->pdf) : route('vendor.invoice.view-invoice', $inv->id),
                    ];
                }
            } else {
                $receipts[] = [
                    'type'       => 'Hospital Bill',
                    'receipt_no' => $inv->invoice_id,
                    'date'       => $inv->invoice_date ? \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') : '—',
                    'item_name'  => 'Hospital Bill #' . $inv->invoice_id,
                    'amount'     => (float) $inv->total_amount,
                    'paid'       => $inv->payment_status === 'Paid' ? (float) $inv->total_amount : 0.0,
                    'due'        => $inv->payment_status === 'Paid' ? 0.0 : (float) $inv->total_amount,
                    'mode'       => $inv->payment_method ?: 'Cash',
                    'status'     => $inv->payment_status ?: 'Unpaid',
                    'billed_by'  => 'Desk',
                    'pdf_url'    => route('vendor.invoice.view-invoice', $inv->id),
                ];
            }
        }

        return $receipts;
    }

    /**
     * Lab tests and radiology scans raised for this visit, as bill lines.
     *
     * Excludes anything already invoiced by the Lab/Radiology modules (they mint their own
     * LabInvoice/RadiologyInvoice), so a test is billed once whichever counter takes the money.
     * Lab orders raised from an OPD consultation carry opd_id; older ones and all radiology
     * studies have no visit link, so those fall back to same-patient-same-day.
     */
    private function testChargesForVisit(int $storeId, int $patientId, $visitId, $visitDate): array
    {
        $lines = [];

        if (Schema::hasTable('lab_orders')) {
            $orders = LabOrder::where('store_id', $storeId)
                ->where(function ($q) use ($visitId, $patientId, $visitDate) {
                    $q->where('opd_id', $visitId)
                        ->orWhere(fn ($w) => $w->where('patient_id', $patientId)->whereDate('created_at', $visitDate));
                })
                ->whereDoesntHave('invoice')
                ->with('items')
                ->get();

            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    $lines[] = [
                        'name'  => 'Lab — ' . $item->test_name . ' (' . $order->order_no . ')',
                        'qty'   => 1,
                        'price' => (float) ($item->price ?? 0),
                    ];
                }
            }
        }

        if (Schema::hasTable('radiology_studies')) {
            $billed = Schema::hasTable('radiology_invoices')
                ? RadiologyInvoice::where('store_id', $storeId)->pluck('radiology_study_id')->filter()->all()
                : [];

            $studies = RadiologyStudy::where('store_id', $storeId)
                ->where('patient_id', $patientId)
                ->whereDate('created_at', $visitDate)
                ->whereNotIn('id', $billed)
                ->get();

            foreach ($studies as $study) {
                $lines[] = [
                    'name'  => 'Radiology — ' . $study->study_name . ' (' . $study->study_no . ')',
                    'qty'   => 1,
                    'price' => (float) ($study->price ?? 0),
                ];
            }
        }

        return $lines;
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'     => 'required|integer',
            'item_name'      => 'required|array|min:1',
            'item_name.*'    => 'required|string|max:255',
            'item_qty.*'     => 'required|numeric|min:0',
            'item_price.*'   => 'required|numeric|min:0',
            'payment_status' => 'required|in:Paid,Partially Paid,Unpaid',
            'paid_amount'     => 'nullable|numeric|min:0',
            'tax_type'       => 'nullable|in:gst,non-gst',
            'gst_percent'    => 'nullable|numeric|min:0|max:100',
            'transaction_id' => 'required_if:payment_method,UPI,Card,Net Banking|nullable|string|max:100',
        ]);

        $taxType    = $request->input('tax_type', 'non-gst');
        $gstPercent = $taxType === 'gst' ? (float) $request->input('gst_percent', 0) : 0;
        $isOnline   = in_array(strtolower($request->payment_method ?? 'cash'), ['upi', 'card', 'net banking', 'online']);

        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)->findOrFail($request->patient_id);

        DB::beginTransaction();
        try {
            $invoice_id = Helpers::generateInvoiceId('H', true, null, $taxType);
            $baseAmount = 0;

            foreach ($request->item_name as $k => $name) {
                $qty   = (float) ($request->item_qty[$k]   ?? 1);
                $price = (float) ($request->item_price[$k] ?? 0);
                $baseAmount += $qty * $price;
            }

            $totalAmount = $taxType === 'gst'
                ? round($baseAmount * (1 + $gstPercent / 100), 2)
                : $baseAmount;

            $payStatus = $request->payment_status;
            $paidAmt   = 0;
            if ($payStatus === 'Paid') {
                $paidAmt = $totalAmount;
            } elseif ($payStatus === 'Partially Paid') {
                $paidAmt = min($totalAmount, max(0, (float) $request->input('paid_amount', 0)));
            }

            $invoice = ManualInvoice::create([
                'invoice_id'     => $invoice_id,
                'invoice_serial' => (int) substr($invoice_id, strrpos($invoice_id, '_') + 1),
                'financial_year' => _currentFinancialYear(),
                'bill_to'        => $patient->id,
                'bill_to_type'   => 'patient',
                'user_type'      => 'hospital_patient',
                'vendor_id'      => $store_id,
                'total_amount'   => $totalAmount,
                'payment_status' => 'Unpaid',
                'payment_method' => $request->payment_method ?? 'Cash',
                'payment_date'   => $paidAmt > 0 ? now()->toDateString() : null,
                'invoice_date'   => now()->toDateString(),
                'tax_type'       => $taxType,
                'reference_number' => $isOnline && $request->transaction_id ? ['transaction_id' => $request->transaction_id] : [],
                'meta'           => $isOnline && $request->transaction_id ? ['transaction_id' => $request->transaction_id] : null,
                // Intake's "more info", as edited on this screen. Same label → value shape the
                // other billing screens write, so the printed bill needs no special handling.
                'custom_headers' => json_encode(DentalIntakeController::rowsFrom($request)),
            ]);

            $invIds = $request->input('inv_id', []);

            foreach ($request->item_name as $k => $name) {
                $qty   = (float) ($request->item_qty[$k]   ?? 1);
                $price = (float) ($request->item_price[$k] ?? 0);
                $invId = $invIds[$k] ?? null;

                InvoiceItem::create([
                    'rand_invoice_id'   => $invoice_id,
                    'manual_invoice_id' => $invoice->id,
                    'name'              => $name,
                    'qty'               => $qty,
                    'price'             => $price,
                    'tax'               => $gstPercent,
                    'gst_status'        => 'excluding',
                    'inv_id'            => $invId ?: null,
                    'hsn'               => $request->input('item_hsn')[$k] ?? null,
                ]);

                if ($invId) {
                    _updateInventoryStock($invId, $qty, null);
                }
            } 

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack(); 
            return back()->withErrors(['error' => 'Could not create bill: ' . $e->getMessage()]);
        }
 
        // Record payment & receipt AFTER DB commit so PDF build & WhatsApp API calls never lock the DB
        if ($paidAmt > 0) { 
            try {
                \App\Services\InvoicePayments::record($invoice, 'manual', [
                    'amount'       => $paidAmt,
                    'payment_mode' => $request->payment_method ?? 'Cash',
                    'payment_date' => now()->toDateString(),
                    'reference'    => $request->transaction_id ?? '',
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('InvoicePayments::record error: ' . $e->getMessage());
            }
        }

        // Generate main bill PDF if not already built by InvoicePayments::record
        $invoice->refresh();
        if (!$invoice->pdf) {
            try {
                $data = _createBillPdf($invoice, 'vendor');
                $invoice->update(['pdf' => $data['pdf']]);

                $wa = InvoiceShare::auto($invoice, 'manual', $data['url'] ?? null);
                if (!empty($wa['message'])) {
                    $wa['status'] === 'sent' ? Toastr::success($wa['message']) : Toastr::warning($wa['message']);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect(route('vendor.invoice.view-invoice', $invoice->id));
    }
}
