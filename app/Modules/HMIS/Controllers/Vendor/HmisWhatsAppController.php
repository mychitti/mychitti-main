<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\LabOrder;
use App\Models\OpdVisit;
use App\Models\Prescription;
use App\Models\RadiologyStudy;
use App\Models\StoreWallet;
use App\Services\HmisWhatsAppShare;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * "Send on WhatsApp" for the hospital screens.
 *
 * Thin on purpose — every rule about what may be sent, how it is worded and what the patient
 * opens lives in HmisWhatsAppShare. This layer only resolves the record (scoped to the store, so
 * an id from another hospital never loads), lets staff override the number, and reports back.
 */
class HmisWhatsAppController extends Controller
{
    protected function storeId(): int
    {
        return (int) Helpers::get_store_id();
    }

    /** An alternate number typed into the send box — otherwise the patient's own. */
    protected function phone(Request $request): ?string
    {
        $phone = trim((string) $request->input('phone'));
        return $phone !== '' ? $phone : null;
    }

    protected function done(array $result)
    {
        $result['success'] ? Toastr::success($result['message']) : Toastr::error($result['message']);
        return back();
    }

    public function treatment(Request $request, $id)
    {
        $visit = OpdVisit::where('store_id', $this->storeId())->findOrFail($id);
        return $this->done(HmisWhatsAppShare::treatment($visit, $this->phone($request)));
    }

    public function prescription(Request $request, $id)
    {
        $rx = Prescription::where('store_id', $this->storeId())->findOrFail($id);
        return $this->done(HmisWhatsAppShare::prescription($rx, $this->phone($request)));
    }

    /** Same prescription, attached as a PDF instead of linked. */
    public function prescriptionPdf(Request $request, $id)
    {
        $rx = Prescription::where('store_id', $this->storeId())->findOrFail($id);
        return $this->done(HmisWhatsAppShare::prescriptionPdf(
            $rx,
            $this->phone($request),
            $this->printOptions($request)
        ));
    }

    /**
     * The Print options the sender had on screen, so the attachment says exactly what the sheet
     * beside them said. Read key by key rather than trusted wholesale: this arrives from the
     * browser and it decides what a patient is and is not told about their own diagnosis.
     */
    private function printOptions(Request $request): array
    {
        $raw = json_decode((string) $request->input('print_opts'), true);
        if (!is_array($raw)) {
            return [];
        }

        $secs = [];
        foreach (['patient', 'diagnosis', 'meds', 'advice', 'followup'] as $key) {
            if (isset($raw['secs'][$key])) {
                $secs[$key] = (bool) $raw['secs'][$key];
            }
        }

        return [
            'header' => (($raw['header'] ?? 'with') === 'without') ? 'without' : 'with',
            'blank'  => max(0, min(120, (int) ($raw['blank'] ?? 0))),
            'secs'   => $secs,
        ];
    }

    public function medicines(Request $request, $id)
    {
        $rx = Prescription::where('store_id', $this->storeId())->findOrFail($id);
        return $this->done(HmisWhatsAppShare::medicines($rx, $this->phone($request)));
    }

    /** Follow-up off the date the doctor wrote on the prescription. */
    public function prescriptionFollowUp(Request $request, $id)
    {
        $rx = Prescription::with('patient', 'doctorProfile.employee')
            ->where('store_id', $this->storeId())->findOrFail($id);

        if (!$rx->patient) {
            Toastr::error('This prescription has no patient on it.');
            return back();
        }

        return $this->done(HmisWhatsAppShare::followUp(
            (int) $rx->store_id,
            $rx->patient,
            $rx->follow_up_date,
            HmisWhatsAppShare::doctorName($rx->doctorProfile),
            $this->phone($request),
            (int) $rx->id
        ));
    }

    public function appointmentFollowUp(Request $request, $id)
    {
        $appointment = Appointment::where('store_id', $this->storeId())->findOrFail($id);
        return $this->done(HmisWhatsAppShare::followUpForAppointment($appointment, $this->phone($request)));
    }

    public function opdFeedback(Request $request, $id)
    {
        $visit = OpdVisit::with('patient')->where('store_id', $this->storeId())->findOrFail($id);

        if (!$visit->patient) {
            Toastr::error('This visit has no patient on it.');
            return back();
        }

        return $this->done(HmisWhatsAppShare::feedback(
            (int) $visit->store_id,
            $visit->patient,
            $visit->visit_date,
            $this->phone($request),
            (int) $visit->id
        ));
    }

    public function appointmentFeedback(Request $request, $id)
    {
        $appointment = Appointment::with('patient')->where('store_id', $this->storeId())->findOrFail($id);

        if (!$appointment->patient) {
            Toastr::error('This appointment has no patient on it.');
            return back();
        }

        return $this->done(HmisWhatsAppShare::feedback(
            (int) $appointment->store_id,
            $appointment->patient,
            $appointment->appointment_date,
            $this->phone($request),
            (int) $appointment->id
        ));
    }

    public function labReport(Request $request, $id)
    {
        $order = LabOrder::where('store_id', $this->storeId())->findOrFail($id);

        // Only a finalized report goes to a patient. 'resulted' means values are typed in but
        // nobody has verified them yet — a patient acting on unverified results is the one
        // outcome this screen must not enable.
        if (!in_array($order->status, ['verified', 'sent'], true)) {
            Toastr::error($order->status === 'resulted'
                ? 'Finalize and verify the results before sending this report to the patient.'
                : 'This report is still ' . str_replace('_', ' ', (string) $order->status) . ' — it can be sent once verified.');
            return back();
        }

        return $this->done(HmisWhatsAppShare::labReport($order, $this->phone($request)));
    }

    public function radiologyReport(Request $request, $id)
    {
        $study = RadiologyStudy::where('store_id', $this->storeId())->findOrFail($id);

        // Same rule as a lab report: 'reported' means a radiologist has typed the findings but
        // nobody has verified them, and a patient acting on an unverified scan is the one outcome
        // this screen must not enable.
        if (!in_array($study->status, ['verified', 'sent'], true)) {
            Toastr::error($study->status === 'reported'
                ? 'Finalize and verify this report before sending it to the patient.'
                : 'This study is still ' . str_replace('_', ' ', (string) $study->status) . ' — it can be sent once verified.');
            return back();
        }

        return $this->done(HmisWhatsAppShare::radiologyReport($study, $this->phone($request)));
    }

    public function activateMonthlyPlan(Request $request)
    {
        $storeId  = (int) Helpers::get_store_id();
        $vendorId = (int) Helpers::get_vendor_id();

        if (!$storeId || !$vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized or store not found.'], 401);
        }

        $wallet     = StoreWallet::where('vendor_id', $vendorId)->first();
        $balance    = (float) ($wallet?->total_earning ?? 0);
        $monthlyFee = 200.00;

        if ($balance < $monthlyFee) {
            return response()->json([
                'success'        => false,
                'message'        => 'Insufficient wallet balance. ₹' . number_format($monthlyFee, 2) . ' is required to activate WhatsApp Monthly Plan. Your current wallet balance is ₹' . number_format($balance, 2) . '.',
                'wallet_balance' => $balance,
                'recharge_url'   => route('vendor.wallet.index'),
            ]);
        }

        DB::beginTransaction();
        try {
            // Deduct ₹200 from vendor's store wallet
            $wallet->decrement('total_earning', $monthlyFee);

            // Record transaction log
            if (class_exists(\App\Models\AccountTransaction::class)) {
                \App\Models\AccountTransaction::create([
                    'from_type'  => 'store',
                    'from_id'    => $storeId,
                    'to_type'    => 'admin',
                    'to_id'      => 1,
                    'method'     => 'wallet',
                    'amount'     => $monthlyFee,
                    'ref'        => 'wa_plan_' . $storeId . '_' . date('YmdHis'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Record billing invoice if table exists
            if (\Illuminate\Support\Facades\Schema::hasTable('wa_billing_invoices')) {
                DB::table('wa_billing_invoices')->insert([
                    'store_id'     => $storeId,
                    'vendor_id'    => $vendorId,
                    'type'         => 'monthly',
                    'description'  => 'WhatsApp Monthly Plan (₹200/month deducted from wallet)',
                    'amount'       => $monthlyFee,
                    'tax'          => 0.00,
                    'total'        => $monthlyFee,
                    'status'       => 'paid',
                    'ref'          => 'wa_monthly_plan_' . $storeId . '_' . date('YmdHis'),
                    'period_start' => now()->toDateString(),
                    'period_end'   => now()->addMonth()->toDateString(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            // Enable WhatsApp flag on store
            DB::table('stores')->where('id', $storeId)->update([
                'wa_enabled' => 1,
                'updated_at' => now(),
            ]);

            // Record subscription if table exists
            if (\Illuminate\Support\Facades\Schema::hasTable('wa_subscriptions')) {
                DB::table('wa_subscriptions')->updateOrInsert(
                    ['store_id' => $storeId],
                    [
                        'plan'               => 'basic',
                        'status'             => 'active',
                        'monthly_fee'        => $monthlyFee,
                        'setup_fee_paid'     => 1,
                        'started_at'         => now()->toDateString(),
                        'current_period_end' => now()->addMonth()->toDateString(),
                        'last_charged_on'    => now()->toDateString(),
                        'updated_at'         => now(),
                        'created_at'         => now(),
                    ]
                );
            }

            DB::commit();

            $updatedBalance = (float) ($wallet->fresh()->total_earning ?? 0);

            return response()->json([
                'success'        => true,
                'message'        => 'WhatsApp Monthly Plan activated successfully! ₹200 deducted from your wallet.',
                'wallet_balance' => $updatedBalance,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate plan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
