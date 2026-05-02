<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\IpdAdmission;
use App\Models\InventoryItem;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HospitalBillController extends Controller
{
    // ── AJAX: search inventory items ───────────────────────────────────
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

    // ── GET: build form for IPD admission ─────────────────────────────
    public function createForIPD($admissionId)
    {
        $store_id  = Helpers::get_store_id();
        $admission = IpdAdmission::where('store_id', $store_id)
            ->with(['patient', 'ward', 'bed', 'doctorProfile.employee'])
            ->findOrFail($admissionId);

        $patient = $admission->patient;

        $dischargeDate  = $admission->discharge_date ?? now();
        $days           = (int) $admission->admission_date?->diffInDays($dischargeDate) + 1;

        // Pre-filled service charges
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

        // Pre-fill medicines from prescriptions during this admission
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

        return view('vendor-views.hospital.create_bill', compact(
            'patient', 'serviceItems', 'medicineItems',
            'context', 'contextId', 'admission'
        ));
    }

    // ── GET: build form for OPD visit ─────────────────────────────────
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

        $serviceItems = [
            [
                'name'  => 'OPD Consultation — ' . $doctorName,
                'qty'   => 1,
                'price' => $visit->doctorProfile?->consultation_fee ?? 0,
            ],
        ];

        // Pre-fill medicines from prescriptions on this visit date
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

        return view('vendor-views.hospital.create_bill', compact(
            'patient', 'serviceItems', 'medicineItems',
            'context', 'contextId', 'visit'
        ));
    }

    // ── POST: save bill ────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'patient_id'     => 'required|integer',
            'item_name'      => 'required|array|min:1',
            'item_name.*'    => 'required|string|max:255',
            'item_qty.*'     => 'required|numeric|min:0',
            'item_price.*'   => 'required|numeric|min:0',
            'payment_status' => 'required|in:Paid,Unpaid',
            'tax_type'       => 'nullable|in:gst,non-gst',
            'gst_percent'    => 'nullable|numeric|min:0|max:100',
        ]);

        $taxType    = $request->input('tax_type', 'non-gst');
        $gstPercent = $taxType === 'gst' ? (float) $request->input('gst_percent', 0) : 0;

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

            $invoice = ManualInvoice::create([
                'invoice_id'     => $invoice_id,
                'invoice_serial' => (int) substr($invoice_id, strrpos($invoice_id, '_') + 1),
                'financial_year' => _currentFinancialYear(),
                'bill_to'        => $patient->id,
                'bill_to_type'   => 'patient',
                'user_type'      => 'hospital_patient',
                'vendor_id'      => $store_id,
                'total_amount'   => $totalAmount,
                'payment_status' => $request->payment_status,
                'payment_method' => $request->payment_method ?? 'Cash',
                'payment_date'   => $request->payment_status === 'Paid' ? now()->toDateString() : null,
                'invoice_date'   => now()->toDateString(),
                'tax_type'       => $taxType,
            ]);

            $invIds = $request->input('inv_id', []);

            foreach ($request->item_name as $k => $name) {
                $qty   = (float) ($request->item_qty[$k]   ?? 1);
                $price = (float) ($request->item_price[$k] ?? 0);
                $invId = $invIds[$k] ?? null;

                InvoiceItem::create([
                    'rand_invoice_id' => $invoice_id,
                    'manual_invoice_id' => $invoice->id,
                    'name'            => $name,
                    'qty'             => $qty,
                    'price'           => $price,
                    'tax'             => $gstPercent,
                    'gst_status'      => 'excluding',
                    'inv_id'          => $invId ?: null,
                    'hsn'             => $request->input('item_hsn')[$k] ?? null,
                ]);

                // Deduct stock for inventory items
                if ($invId) {
                    _updateInventoryStock($invId, $qty, null);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Could not create bill: ' . $e->getMessage()]);
        }

        // Generate PDF
        try {
            $data = _createBillPdf($invoice, 'vendor');
            $invoice->update(['pdf' => $data['pdf']]);
            return redirect(route('vendor.invoice.view-invoice', $invoice->id));
        } catch (\Throwable $e) {
            return redirect(route('vendor.ipd.index'))
                ->with('success', 'Bill #' . $invoice_id . ' created successfully.');
        }
    }
}
