<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\LabOrder;
use App\Models\OpdVisit;
use App\Models\Prescription;
use App\Services\HmisWhatsAppShare;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

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
        return $this->done(HmisWhatsAppShare::prescriptionPdf($rx, $this->phone($request)));
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
}
