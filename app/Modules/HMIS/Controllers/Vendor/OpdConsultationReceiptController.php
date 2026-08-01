<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\OpdConsultationReceipt;
use App\Models\OpdVisit;
use App\Models\Store;
use App\Models\StoreConfig;
use App\Services\HmisWhatsAppShare;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class OpdConsultationReceiptController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('opd_consultation_receipts')) {
            DB::statement("
                CREATE TABLE IF NOT EXISTS opd_consultation_receipts (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    store_id BIGINT UNSIGNED NOT NULL,
                    patient_id BIGINT UNSIGNED NOT NULL,
                    doctor_profile_id BIGINT UNSIGNED NULL,
                    opd_visit_id BIGINT UNSIGNED NULL,
                    bill_no INT NULL,
                    receipt_date DATE NULL,
                    amount DECIMAL(12,2) DEFAULT 0,
                    concession DECIMAL(12,2) DEFAULT 0,
                    paid DECIMAL(12,2) DEFAULT 0,
                    due DECIMAL(12,2) DEFAULT 0,
                    payment_mode VARCHAR(30) NULL,
                    allowed_consultations INT DEFAULT 1,
                    validity_days INT DEFAULT 7,
                    valid_until DATE NULL,
                    consultations_used INT DEFAULT 1,
                    billed_by VARCHAR(150) NULL,
                    created_at TIMESTAMP NULL DEFAULT NULL,
                    updated_at TIMESTAMP NULL DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY idx_lookup (store_id, patient_id, doctor_profile_id, valid_until)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
        if (!Schema::hasColumn('opd_visits', 'consultation_receipt_id')) {
            DB::statement("ALTER TABLE `opd_visits`
                ADD COLUMN `consultation_receipt_id` BIGINT UNSIGNED NULL,
                ADD COLUMN `consultation_visit_no` INT NULL");
        }
        if (Schema::hasTable('opd_consultation_receipts') && !Schema::hasColumn('opd_consultation_receipts', 'invoice_id')) {
            DB::statement("ALTER TABLE `opd_consultation_receipts` ADD COLUMN `invoice_id` VARCHAR(60) NULL");
        }
        if (Schema::hasTable('opd_consultation_receipts') && !Schema::hasColumn('opd_consultation_receipts', 'transaction_id')) {
            DB::statement("ALTER TABLE `opd_consultation_receipts` ADD COLUMN `transaction_id` VARCHAR(100) NULL");
        }
    }

    /**
     * Show the consultation receipt for a visit:
     *  - already linked  → print it
     *  - active receipt for same patient+doctor → attach as a follow-up (no charge) → print it
     *  - otherwise → show the fee-collection form
     */
    public function receipt($id)
    {
        // Hiding the button is not a gate — the route is still reachable by URL.
        if (!_canViewOpdReceipt()) abort(403);
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $visit = OpdVisit::where('store_id', $store_id)
            ->with(['patient', 'doctorProfile.employee'])
            ->findOrFail($id);

        if ($visit->consultation_receipt_id) {
            $receipt = OpdConsultationReceipt::find($visit->consultation_receipt_id);
            if ($receipt) {
                return $this->render($visit, $receipt);
            }
        }

        $active = OpdConsultationReceipt::where('store_id', $store_id)
            ->where('patient_id', $visit->patient_id)
            ->where('doctor_profile_id', $visit->doctor_profile_id)
            ->whereColumn('consultations_used', '<', 'allowed_consultations')
            ->whereDate('valid_until', '>=', now()->toDateString())
            ->orderByDesc('id')
            ->first();

        if ($active) {
            $active->increment('consultations_used');
            $visit->consultation_receipt_id = $active->id;
            $visit->consultation_visit_no   = $active->consultations_used;
            $visit->save();
            return $this->render($visit, $active->fresh());
        }

        // New paid consultation → collect fee
        $config = StoreConfig::where('store_id', $store_id)->first();
        $defaultFee = (float) ($visit->doctorProfile?->consultation_fee ?? 0);
        $allowed = (int) ($config?->opd_consultation_count ?? 1);
        $validityDays = (int) ($config?->opd_consultation_validity_days ?? 7);

        return view('hmis::vendor.opd.consultation_receipt_form', compact('visit', 'defaultFee', 'allowed', 'validityDays'));
    }

    public function store(Request $request, $id)
    {
        // A receptionist who cannot view the receipt must not be able to raise one either.
        if (!_canViewOpdReceipt()) abort(403);
        $this->ensureSchema();
        $request->validate([
            'amount'         => 'required|numeric|min:0',
            'concession'     => 'nullable|numeric|min:0',
            'payment_mode'   => 'required|string|max:30',
            'transaction_id' => 'required_if:payment_mode,Card,UPI,Online,Wallet|nullable|string|max:100',
        ]);

        $store_id = Helpers::get_store_id();
        $visit = OpdVisit::where('store_id', $store_id)->findOrFail($id);

        if ($visit->consultation_receipt_id) {
            return redirect()->route('vendor.opd.consultation-receipt', $visit->id);
        }

        $config       = StoreConfig::where('store_id', $store_id)->first();
        $allowed      = (int) ($config?->opd_consultation_count ?? 1);
        $validityDays = (int) ($config?->opd_consultation_validity_days ?? 7);

        $amount     = (float) $request->amount;
        $concession = (float) ($request->concession ?? 0);
        $paid       = max(0, $amount - $concession);
        $due        = 0;

        $billNo = (int) (OpdConsultationReceipt::where('store_id', $store_id)->max('bill_no') ?? 0) + 1;

        $receipt = OpdConsultationReceipt::create([
            'store_id'              => $store_id,
            'patient_id'            => $visit->patient_id,
            'doctor_profile_id'     => $visit->doctor_profile_id,
            'opd_visit_id'          => $visit->id,
            'bill_no'               => $billNo,
            'receipt_date'          => now()->toDateString(),
            'amount'                => $amount,
            'concession'            => $concession,
            'paid'                  => $paid,
            'due'                   => $due,
            'payment_mode'          => $request->payment_mode,
            'transaction_id'        => $request->transaction_id,
            'allowed_consultations' => $allowed,
            'validity_days'         => $validityDays,
            'valid_until'           => now()->addDays($validityDays)->toDateString(),
            'consultations_used'    => 1,
            'billed_by'             => $this->currentUserName(),
        ]);

        $visit->consultation_receipt_id = $receipt->id;
        $visit->consultation_visit_no   = 1;
        $visit->save();

        // Record the bill in the canonical manual_invoices ledger with the store's running invoice id.
        $invoiceId = 'opd-receipt-' . $receipt->id; // fallback
        try {
            $taxType   = 'non-gst';
            $invoiceId = Helpers::generateInvoiceId('H', true, null, $taxType);
            $manual    = ManualInvoice::create([
                'invoice_id'     => $invoiceId,
                'invoice_serial' => (int) substr($invoiceId, strrpos($invoiceId, '_') + 1),
                'financial_year' => _currentFinancialYear(),
                'bill_to'        => $visit->patient_id,
                'bill_to_type'   => 'patient',
                'user_type'      => 'hospital_patient',
                'vendor_id'      => $store_id,
                'total_amount'   => $paid,
                'payment_status' => $paid > 0 ? 'Paid' : 'Unpaid',
                'payment_method' => $request->payment_mode ?: 'Cash',
                'payment_date'   => $paid > 0 ? now()->toDateString() : null,
                'invoice_date'   => now()->toDateString(),
                'tax_type'       => $taxType,
                'reference_number' => $request->transaction_id ? ['transaction_id' => $request->transaction_id] : [],
                'meta'           => $request->transaction_id ? ['transaction_id' => $request->transaction_id] : null,
            ]);
            InvoiceItem::create([
                'rand_invoice_id'   => $invoiceId,
                'manual_invoice_id' => $manual->id,
                'name'              => 'OP Consultation' . ($visit->doctorProfile?->employee ? ' — Dr. ' . trim(($visit->doctorProfile->employee->f_name ?? '') . ' ' . ($visit->doctorProfile->employee->l_name ?? '')) : ''),
                'qty'               => 1,
                'price'             => $amount,
                'tax'               => 0,
                'gst_status'        => 'excluding',
            ]);
            $receipt->update(['invoice_id' => $invoiceId]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('OPD consultation manual invoice failed: ' . $e->getMessage());
        }

        // Post to the hospital's accounts (ledger + daybook) for the collected amount
        if ($paid > 0) {
            try {
                $isCash        = in_array(strtolower($request->payment_mode), ['cash', 'wallet']);
                $creditAccount = Helpers::ensureConsultationRevenueAccount();           // income
                $debitAccount  = $isCash ? Helpers::ensureCashAccount() : Helpers::ensureBankAccount();
                $patientName   = $visit->patient?->name ?? 'Patient';

                $ledgerData = [
                    'date'         => now(),
                    'amount'       => $paid,
                    'voucher_type' => 'Receipt',
                    'invoice_id'   => $invoiceId,
                    'status'       => 'approved',
                    'description'  => 'OP Consultation — ' . $patientName . ' (Bill #' . $receipt->bill_no . ')',
                    'payment_mode' => $isCash ? 'cash' : 'bank',
                ];
                $voucher = _masterLedgerEntry($ledgerData, $creditAccount, $debitAccount, 'store', 'store', null);
                _saveDayBookEntry($paid, 'credit', $store_id, 'OP Consultation — ' . $patientName, null, $voucher?->id, now(), null, $isCash ? 'cash' : 'bank');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('OPD consultation ledger post failed: ' . $e->getMessage());
            }
        }

        $this->autoSendToPatient($visit);

        Toastr::success('OP consultation receipt generated.');
        return redirect()->route('vendor.opd.consultation-receipt', $visit->id);
    }

    /**
     * WhatsApp the patient their consultation summary once the visit is billed.
     *
     * Generating the receipt is the only signal in OPD that a walk-in consultation is actually
     * over — nothing sets opd_visits.status, and the queue itself reads "completed" as
     * consultation_receipt_id being present. A visit that came from an appointment is already
     * handled when the appointment is marked completed; the summary is keyed to the visit id in
     * both places, so whichever happens first sends and the other is a no-op.
     */
    private function autoSendToPatient(OpdVisit $visit): void
    {
        $storeId = (int) $visit->store_id;

        HmisWhatsAppShare::auto('treatment', $storeId, (int) $visit->id,
            fn() => HmisWhatsAppShare::treatment($visit));

        // Feedback for walk-ins only. An appointment-driven visit gets its request from
        // AppointmentController::autoSendOnCompletion, held back by the hospital's chosen delay —
        // asking again from here would be the same question twice about one visit.
        if ($visit->appointment_id) {
            return;
        }

        $visit->loadMissing('patient');
        if (!$visit->patient) {
            return;
        }

        HmisWhatsAppShare::auto(
            'feedback_opd',
            $storeId,
            (int) $visit->id,
            fn() => HmisWhatsAppShare::feedback(
                $storeId,
                $visit->patient,
                $visit->visit_date,
                null,
                (int) $visit->id
            ),
            'opd_visit',
            HmisWhatsAppShare::feedbackDueAt($storeId)
        );
    }

    public function pdf($id)
    {
        if (!_canViewOpdReceipt()) abort(403);
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $visit = OpdVisit::where('store_id', $store_id)
            ->with(['patient', 'doctorProfile.employee'])
            ->findOrFail($id);

        if (!$visit->consultation_receipt_id) {
            return redirect()->route('vendor.opd.consultation-receipt', $visit->id);
        }
        $receipt = OpdConsultationReceipt::findOrFail($visit->consultation_receipt_id);

        $html = View::make('hmis::vendor.opd.consultation_receipt', $this->receiptData($visit, $receipt, true))->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => [150, 150],   // compact receipt page (mm), not a full A4
            'margin_left'   => 6,
            'margin_right'  => 6,
            'margin_top'    => 6,
            'margin_bottom' => 6,
            'tempDir'       => storage_path('tmp'),
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output('op_consultation_receipt_' . $receipt->bill_no . '.pdf', 'I');
    }

    private function render(OpdVisit $visit, OpdConsultationReceipt $receipt)
    {
        return view('hmis::vendor.opd.consultation_receipt', $this->receiptData($visit, $receipt, false));
    }

    private function receiptData(OpdVisit $visit, OpdConsultationReceipt $receipt, bool $pdf): array
    {
        $store   = Store::withoutGlobalScopes()->find($visit->store_id);
        $patient = $visit->patient;
        $dp      = $visit->doctorProfile;

        $doctorName = trim('Dr. ' . ($dp?->employee?->f_name ?? '') . ' ' . ($dp?->employee?->l_name ?? ''));
        if ($dp?->qualification) {
            $doctorName .= ', ' . $dp->qualification;
        }

        $age = '';
        if ($patient?->dob) {
            try { $age = \Carbon\Carbon::parse($patient->dob)->age . ' Years'; } catch (\Exception $e) {}
        }

        return [
            'pdf'         => $pdf,
            'store'       => $store,
            'patient'     => $patient,
            'visit'       => $visit,
            'receipt'     => $receipt,
            'doctorName'  => $doctorName,
            'department'  => $dp?->department ?: ($dp?->specialization ?? ''),
            'age'         => $age,
            'visitNo'     => $visit->consultation_visit_no ?? 1,
            'amountWords' => $this->amountToWords((int) round($receipt->paid)),
        ];
    }

    private function amountToWords(int $n): string
    {
        if ($n === 0) return 'Zero Rupees Only';
        try {
            if (class_exists(\NumberFormatter::class)) {
                $f = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
                return ucwords($f->format($n)) . ' Rupees Only';
            }
        } catch (\Throwable $e) {
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $two = function ($x) use ($ones, $tens) {
            if ($x < 20) return $ones[$x];
            return trim($tens[intdiv($x, 10)] . ' ' . $ones[$x % 10]);
        };
        $three = function ($x) use ($ones, $two) {
            $h = intdiv($x, 100);
            $r = $x % 100;
            return trim(($h ? $ones[$h] . ' Hundred ' : '') . $two($r));
        };
        $out = '';
        $crore = intdiv($n, 10000000); $n %= 10000000;
        $lakh  = intdiv($n, 100000);   $n %= 100000;
        $thou  = intdiv($n, 1000);     $n %= 1000;
        $hund  = $n;
        if ($crore) $out .= $three($crore) . ' Crore ';
        if ($lakh)  $out .= $three($lakh) . ' Lakh ';
        if ($thou)  $out .= $three($thou) . ' Thousand ';
        if ($hund)  $out .= $three($hund) . ' ';
        return trim($out) . ' Rupees Only';
    }

    private function currentUserName(): string
    {
        if (auth('vendor_employee')->check()) {
            $u = auth('vendor_employee')->user();
            return trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? '')) ?: 'Staff';
        }
        $v = auth('vendor')->user();
        return trim(($v->f_name ?? '') . ' ' . ($v->l_name ?? '')) ?: 'Admin';
    }
}
