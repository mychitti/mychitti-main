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
    /**
     * Reading a hospital bill, kept apart from the billing module's own `billing,view`.
     *
     * A ward clerk who raises bills against patients has no business browsing the store's whole
     * invoice book, and a bookkeeper with billing,view should not need an HMIS role to open one.
     * So this permission opens patient bills and nothing else — see view() for the enforcement.
     */
    const FEATURES = [
        'hospital_bill' => ['Hospital Bill', ['view']],
    ];

    public static function ensurePermission(): void
    {
        if (!Schema::hasTable('features') || !Schema::hasTable('feature_permissions')) {
            return;
        }

        foreach (self::FEATURES as $name => [$display, $actions]) {
            $fid = DB::table('features')->where('name', $name)->value('id');
            if (!$fid) {
                $fid = DB::table('features')->insertGetId([
                    'name' => $name, 'display_name' => $display, 'master_module' => 'hospital_manage',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            foreach ($actions as $a) {
                if (!DB::table('feature_permissions')->where('feature_id', $fid)->where('action', $a)->exists()) {
                    DB::table('feature_permissions')->insert(['feature_id' => $fid, 'action' => $a, 'free' => 0]);
                }
            }
        }
    }


    /**
     * One raised hospital bill, on the same template the billing module uses.
     *
     * Scoped twice on purpose: to this store, and to bills raised against a patient. Without the
     * second check this would be `billing,view` under another name, handing every HMIS role the
     * store's supplier and customer invoices as well.
     */
    public function view($id)
    {
        $invoice = ManualInvoice::where('vendor_id', Helpers::get_store_id())->find($id);

        if (!$invoice || $invoice->bill_to_type !== 'patient') {
            abort(404, 'Hospital bill not found.');
        }

        return view('vendor-views.billing.view_invoice', compact('invoice'));
    }

    /**
     * Link to a raised bill on whichever screen the viewer may open, or null when neither.
     *
     * The panels that use this are rendered for HMIS roles, so the hospital screen is tried first
     * — a link that 403s is worse than no link.
     */
    public static function billUrl($invoiceId): ?string
    {
        if (hasPermission('hospital_bill', 'view')) {
            return route('vendor.hospital-bill.view', $invoiceId);
        }
        if (hasPermission('billing', 'view')) {
            return route('vendor.invoice.view-invoice', $invoiceId);
        }
        return null;
    }

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
        // So the permission exists to be granted before anyone opens the role editor.
        self::ensurePermission();

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

        // Tests and scans raised across the stay, minus anything the Lab/Radiology modules already
        // invoiced themselves.
        foreach ($this->testCharges($store_id, $patient->id, null, $admission->admission_date, $dischargeDate) as $line) {
            $serviceItems[] = $line;
        }

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
                // Dispensed means the pharmacy handed it over and billed it on its own counter
                // sale. Putting it on the hospital bill as well charges the patient twice for the
                // same strip — the prescription line is an instruction, not an unpaid charge.
                if ($item->dispensed) {
                    continue;
                }

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

        $existingBills = $this->billsForContext($store_id, 'ipd', $admissionId);

        // What has already been collected against this admission. The bill itself stays whole —
        // every line still shows at full price — and this only changes what is left to pay.
        $alreadyPaid = round((float) $existingBills->sum('paid'), 2);

        return view('hmis::vendor.hospital.create_bill', compact(
            'patient', 'serviceItems', 'medicineItems',
            'context', 'contextId', 'admission', 'customInfo', 'presetLabels', 'existingReceipts',
            'existingBills', 'alreadyPaid'
        ));
    }

    public function createForOPD($visitId)
    {
        // So the permission exists to be granted before anyone opens the role editor.
        self::ensurePermission();

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

        // The consultation is only left off the bill when money for it has actually been collected:
        // a receipt linked to this visit, a receipt raised against this visit, or an earlier
        // receipt for the same patient+doctor still inside its free follow-up window. If no OP
        // receipt was generated, the fee belongs on the bill.
        $hasConsultationReceipt = false;

        if (Schema::hasTable('opd_consultation_receipts')) {
            if ($visit->consultation_receipt_id) {
                $hasConsultationReceipt = \App\Models\OpdConsultationReceipt::where('store_id', $store_id)
                    ->where('id', $visit->consultation_receipt_id)
                    ->exists();
            }

            if (!$hasConsultationReceipt) {
                $hasConsultationReceipt = \App\Models\OpdConsultationReceipt::where('store_id', $store_id)
                    ->where('opd_visit_id', $visitId)
                    ->exists();
            }

            // Follow-up covered by an earlier receipt that is still valid and has visits left.
            if (!$hasConsultationReceipt && $visit->doctor_profile_id) {
                $hasConsultationReceipt = \App\Models\OpdConsultationReceipt::where('store_id', $store_id)
                    ->where('patient_id', $visit->patient_id)
                    ->where('doctor_profile_id', $visit->doctor_profile_id)
                    ->whereColumn('consultations_used', '<', 'allowed_consultations')
                    ->whereDate('valid_until', '>=', $visit->visit_date)
                    ->exists();
            }
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

        // Everything the doctor advised and priced on this visit, minus what has already been paid
        // for sitting by sitting.
        foreach ($this->treatmentChargesForVisit($visit, $store_id) as $line) {
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
                // Dispensed means the pharmacy handed it over and billed it on its own counter
                // sale. Putting it on the hospital bill as well charges the patient twice for the
                // same strip — the prescription line is an instruction, not an unpaid charge.
                if ($item->dispensed) {
                    continue;
                }

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

        $existingBills = $this->billsForContext($store_id, 'opd', $visitId);

        // What has already been collected against this visit. The bill itself stays whole —
        // every line still shows at full price — and this only changes what is left to pay.
        $alreadyPaid = round((float) $existingBills->sum('paid'), 2);

        return view('hmis::vendor.hospital.create_bill', compact(
            'patient', 'serviceItems', 'medicineItems',
            'context', 'contextId', 'visit', 'customInfo', 'presetLabels', 'existingReceipts',
            'existingBills', 'alreadyPaid'
        ));
    }

    /**
     * Gather all existing consultation receipts, bill payments, and invoices for this patient/visit
     * to display in the bottom Receipts & Payment History section.
     */
    /**
     * The two columns that tie a hospital bill back to the visit or admission it came from.
     *
     * Added here rather than in a migration, per the project's schema rules. Cheap to call: the
     * column check is a schema read, and it is skipped for the rest of the request once done.
     */
    public static function ensureContextColumns(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!Schema::hasColumn('manual_invoices', 'hmis_context')) {
            DB::statement("ALTER TABLE `manual_invoices` ADD COLUMN `hmis_context` VARCHAR(20) NULL");
        }
        if (!Schema::hasColumn('manual_invoices', 'hmis_context_id')) {
            DB::statement("ALTER TABLE `manual_invoices` ADD COLUMN `hmis_context_id` BIGINT UNSIGNED NULL");
            DB::statement("ALTER TABLE `manual_invoices` ADD INDEX `mi_hmis_context` (`vendor_id`, `hmis_context`, `hmis_context_id`)");
        }
    }

    /**
     * Bills already raised from this same visit or admission, newest first, with what is still
     * owed on each.
     *
     * This is what stops the same visit being billed twice. The screen builds a fresh bill from
     * the visit's chargeable items every time it is opened, which is right the first time and
     * wrong every time after — and a gateway timeout on the save is enough to make somebody
     * reopen it and do exactly that. Only bills carrying the context are found, so bills raised
     * before these columns existed are invisible here; nothing can be done about those.
     */
    private function billsForContext(int $storeId, string $context, $contextId): \Illuminate\Support\Collection
    {
        self::ensureContextColumns();

        if (!$contextId) {
            return collect();
        }

        return ManualInvoice::where('vendor_id', $storeId)
            ->where('hmis_context', $context)
            ->where('hmis_context_id', (int) $contextId)
            ->orderByDesc('id')
            ->get()
            ->map(function ($inv) {
                $paid = (float) DB::table('invoice_payments')
                    ->where('invoice_type', 'manual')
                    ->where('invoice_id', $inv->id)
                    ->sum('amount');

                return (object) [
                    'id'         => $inv->id,
                    'invoice_id' => $inv->invoice_id,
                    'date'       => $inv->invoice_date,
                    'total'      => (float) $inv->total_amount,
                    'paid'       => round($paid, 2),
                    'due'        => max(0, round((float) $inv->total_amount - $paid, 2)),
                    'status'     => $inv->payment_status,
                ];
            });
    }

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
                        'pdf_url'    => $pmt->pdf ? asset('storage/app/public/receipt/' . $pmt->pdf) : self::billUrl($inv->id),
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
                    // This branch is the no-payments-recorded case, so there is no payment mode to
                    // report. The invoice carries payment_method from the moment it is raised, and
                    // printing it here badged a wholly unpaid bill as "Cash".
                    'mode'       => $inv->payment_status === 'Paid' ? ($inv->payment_method ?: 'Cash') : null,
                    'status'     => $inv->payment_status ?: 'Unpaid',
                    'billed_by'  => 'Desk',
                    'pdf_url'    => self::billUrl($inv->id),
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
        return $this->testCharges($storeId, $patientId, $visitId, $visitDate, $visitDate);
    }

    /**
     * The same, over a window rather than a single day — an admission spans days, so an IPD bill
     * has to sweep everything raised between admission and discharge.
     */
    private function testCharges(int $storeId, int $patientId, $visitId, $from, $to): array
    {
        $from = \Carbon\Carbon::parse($from)->startOfDay();
        $to   = \Carbon\Carbon::parse($to)->endOfDay();

        $lines = [];

        if (Schema::hasTable('lab_orders')) {
            $orders = LabOrder::where('store_id', $storeId)
                ->where(function ($q) use ($visitId, $patientId, $from, $to) {
                    if ($visitId) {
                        $q->where('opd_id', $visitId);
                    }
                    $q->orWhere(fn ($w) => $w->where('patient_id', $patientId)->whereBetween('created_at', [$from, $to]));
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
                ->whereBetween('created_at', [$from, $to])
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

    /**
     * What the doctor advised and priced on this visit, as bill lines.
     *
     * Treatments live as free text on the visit with their money in `treatment_plan` — one row per
     * advised term carrying its own amount, discount and paid flag, because a course is usually
     * paid for sitting by sitting. A term already marked paid is left off: it has been collected
     * against, and pulling it in again would charge for it twice. An unpriced term falls back to
     * whatever this hospital last charged for it (OpdTreatmentPrice), so a term the doctor never
     * put a figure against still reaches the desk with a rate to confirm rather than a zero.
     */
    private function treatmentChargesForVisit(OpdVisit $visit, int $storeId): array
    {
        // Bill what the patient agreed to. Advice they declined is still recorded on the visit,
        // but charging for it would put treatments on the bill that were never going to happen.
        // Falls back to the advised list while no willing list has been recorded, which is how
        // every visit taken before consent was captured separately still bills correctly.
        $terms = $visit->willing_treatment_list ?: $visit->treatment_list;
        if (!$terms) {
            return [];
        }

        $plan   = $visit->treatment_plan_map;
        $learnt = \App\Models\OpdTreatmentPrice::mapFor($storeId, $terms);

        $lines = [];
        foreach ($terms as $term) {
            $row = $plan[$term] ?? [];

            if (!empty($row['paid'])) {
                continue;
            }

            $amount   = array_key_exists('amount', $row) && $row['amount'] !== null && $row['amount'] !== ''
                ? (float) $row['amount']
                : (float) ($learnt[$term]['amount'] ?? 0);
            $discount = array_key_exists('discount', $row) && $row['discount'] !== null && $row['discount'] !== ''
                ? (float) $row['discount']
                : (float) ($learnt[$term]['discount'] ?? 0);

            $lines[] = [
                'name'  => 'Treatment — ' . $term,
                'qty'   => 1,
                'price' => max($amount - $discount, 0),
            ];
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
            // UPI is deliberately not in this list. A counter taking a UPI payment sees the money
            // land on their own phone and has no reference to hand until they open the app for it,
            // so demanding one here stopped bills being raised for a number nobody needed. Card and
            // net banking still carry a slip with the reference printed on it.
            'transaction_id' => 'required_if:payment_method,Card,Net Banking|nullable|string|max:100',
        ]);

        $taxType    = $request->input('tax_type', 'non-gst');
        $gstPercent = $taxType === 'gst' ? (float) $request->input('gst_percent', 0) : 0;
        $isOnline   = in_array(strtolower($request->payment_method ?? 'cash'), ['upi', 'card', 'net banking', 'online']);

        $store_id = Helpers::get_store_id();
        $patient  = Patient::where('store_id', $store_id)->findOrFail($request->patient_id);

        self::ensureContextColumns();

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
                // What this bill was raised from. Without it nothing downstream can tell that a
                // visit has already been billed, which is how one visit ends up with three bills.
                'hmis_context'    => in_array($request->input('context'), ['opd', 'ipd'], true) ? $request->input('context') : null,
                'hmis_context_id' => $request->input('context_id') ? (int) $request->input('context_id') : null,
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
                // This screen speaks the patient's language — UPI, Card, Net Banking, Cheque — while
                // the payment ledger records only Cash, Online, or both, since that is the split the
                // books and the receipt care about. Without this mapping every non-cash payment
                // fell through InvoicePayments' whitelist and was banked as Cash: the money landed
                // in cash_amount, the invoice read "Cash", and a UPI collection was invisible in
                // the online takings. The transaction id below is what preserves which rail it was.
                $mode = in_array($request->payment_method, ['UPI', 'Card', 'Net Banking', 'Online'], true)
                    ? 'Online'
                    : 'Cash';

                \App\Services\InvoicePayments::record($invoice, 'manual', [
                    'amount'       => $paidAmt,
                    'payment_mode' => $mode,
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

        // Whichever bill screen this role can actually open. Sending a biller to one they cannot
        // ended a successful bill on "Access denied" and bounced them back to the blank form, with
        // the bill and its receipt already saved and nothing on screen saying so.
        if (hasPermission('hospital_bill', 'view')) {
            return redirect(route('vendor.hospital-bill.view', $invoice->id));
        }
        if (hasPermission('billing', 'view')) {
            return redirect(route('vendor.invoice.view-invoice', $invoice->id));
        }

        Toastr::success('Bill ' . $invoice->invoice_id . ' raised'
            . ($paidAmt > 0 ? ' — ' . _price($paidAmt) . ' received.' : '.'));

        // Back to whatever the bill was raised from, which they demonstrably can open.
        $contextId = (int) $request->input('context_id');
        if ($contextId && $request->input('context') === 'opd') {
            return redirect(route('vendor.opd.show', $contextId));
        }
        if ($contextId && $request->input('context') === 'ipd') {
            return redirect(route('vendor.ipd.show', $contextId));
        }

        return redirect(route('vendor.patient.show', $patient->id));
    }
}
