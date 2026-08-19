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

        return view('hmis::vendor.hospital.create_bill', compact(
            'patient', 'serviceItems', 'medicineItems',
            'context', 'contextId', 'admission', 'customInfo', 'presetLabels'
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

        $serviceItems = [
            [
                'name'  => 'OPD Consultation — ' . $doctorName,
                'qty'   => 1,
                'price' => $visit->doctorProfile?->consultation_fee ?? 0,
            ],
        ];

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

        return view('hmis::vendor.hospital.create_bill', compact(
            'patient', 'serviceItems', 'medicineItems',
            'context', 'contextId', 'visit', 'customInfo', 'presetLabels'
        ));
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
            'payment_status' => 'required|in:Paid,Unpaid',
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

        try {
            $data = _createBillPdf($invoice, 'vendor');
            $invoice->update(['pdf' => $data['pdf']]);

            // WhatsApp the patient their bill, if this hospital turned that on. Never blocks the
            // redirect: the bill is raised either way, and only something the staff member can
            // actually act on (a missing phone number, a refused send) is worth a message.
            $wa = InvoiceShare::auto($invoice, 'manual', $data['url'] ?? null);
            if ($wa['message']) {
                $wa['status'] === 'sent' ? Toastr::success($wa['message']) : Toastr::warning($wa['message']);
            }

            return redirect(route('vendor.invoice.view-invoice', $invoice->id));
        } catch (\Throwable $e) {
            // The bill is already committed — only the PDF failed. Reporting plain success and
            // bouncing to the admissions list made a real failure look like a redirect bug, so
            // log the cause and say what actually happened. Most often this is the 'public' disk
            // (DO Spaces) missing its AWS_* config, which _createBillPdf writes the PDF to.
            report($e);
            Toastr::warning('Bill #' . $invoice_id . ' was created, but its PDF could not be generated: ' . $e->getMessage());
            return redirect(route('vendor.ipd.index'));
        }
    }
}
