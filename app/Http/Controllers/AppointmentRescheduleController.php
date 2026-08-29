<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentRescheduleRequest;
use App\Models\HospitalActivityLog;
use App\Services\AppointmentReschedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The page a patient opens from "we would like to move your appointment".
 *
 * Public because a patient has no login, so the token is the credential — 48 hex characters
 * against one request row, and every fact on the page is re-read from that row's own store,
 * appointment and patient ids rather than from anything in the URL or the form. A valid token can
 * do exactly two things to exactly one appointment: agree to the time the hospital proposed, or
 * say it does not suit.
 *
 * What it deliberately cannot do is pick a different time. A patient choosing their own slot needs
 * to see a live diary, and a link sent three days ago is not one — so declining sends the question
 * back to the hospital with a note, and a human answers it.
 */
class AppointmentRescheduleController extends Controller
{
    public function show($token)
    {
        AppointmentRescheduleRequest::ensureSchema();

        $req = $this->find($token);
        if (!$req instanceof AppointmentRescheduleRequest) {
            return $req;
        }

        DB::table('appointment_reschedule_requests')->where('id', $req->id)->update([
            'views'      => DB::raw('views + 1'),
            'updated_at' => now(),
        ]);

        return $this->page($req);
    }

    /**
     * The patient's answer.
     *
     * Confirming re-checks the slot before it moves anything: a request sent on Monday and tapped
     * on Thursday is three days in which somebody else can have taken the last place. If it has
     * gone, nothing is moved and nothing is refused either — the request stays open and the
     * hospital is told, because the patient did their part and a human needs to sort it out.
     */
    public function respond(Request $request, $token)
    {
        AppointmentRescheduleRequest::ensureSchema();

        $request->validate([
            'answer' => 'required|in:accept,decline',
            'note'   => 'nullable|string|max:500',
        ]);

        $req = $this->find($token);
        if (!$req instanceof AppointmentRescheduleRequest) {
            return $req;
        }

        if ($request->input('answer') === 'decline') {
            $req->forceFill([
                'status'        => 'declined',
                'responded_at'  => now(),
                'response_note' => $request->input('note'),
            ])->save();

            HospitalActivityLog::record(
                (int) $req->store_id, 'appointment', (int) $req->appointment_id, 'reschedule_declined',
                "Patient #{$req->patient_id} cannot make the proposed time for appointment #{$req->appointment_id} ({$req->proposedLabel()})"
                    . ($request->filled('note') ? ' — ' . $request->input('note') : ''),
                ['patient_id' => $req->patient_id, 'request_id' => $req->id, 'by' => 'patient']
            );

            return $this->page($req->fresh(), 'Thank you — we have told the hospital. Your original appointment still stands, and they will be in touch.');
        }

        $appointment = Appointment::where('store_id', $req->store_id)->find($req->appointment_id);

        if (!$appointment || in_array($appointment->status, ['completed', 'cancelled'], true)) {
            return $this->page($req, 'This appointment has already changed. Please call the hospital.');
        }

        if (AppointmentReschedule::slotFull($req->slot_id, $req->to_date->toDateString())) {
            HospitalActivityLog::record(
                (int) $req->store_id, 'appointment', (int) $req->appointment_id, 'reschedule_slot_full',
                "Patient #{$req->patient_id} accepted {$req->proposedLabel()} for appointment #{$req->appointment_id}, but that slot is now full",
                ['patient_id' => $req->patient_id, 'request_id' => $req->id]
            );

            return $this->page($req, 'That time has just been taken. The hospital has been told and will call you with another.');
        }

        $new = AppointmentReschedule::apply(
            $appointment,
            $req->to_date->toDateString(),
            (string) $req->to_time,
            $req->slot_id,
            'the patient'
        );

        $req->forceFill([
            'status'             => 'accepted',
            'responded_at'       => now(),
            'response_note'      => $request->input('note'),
            'new_appointment_id' => $new->id,
        ])->save();

        return $this->page($req->fresh(), 'Confirmed. Your appointment has been moved — see you then.');
    }

    /**
     * The request behind a token, or the page explaining why there isn't one.
     *
     * Every refusal is a rendered page rather than an abort: the person reading it is a patient
     * who did what they were asked, and "410 Gone" tells them nothing about what to do next.
     */
    protected function find($token)
    {
        $req = AppointmentRescheduleRequest::with('patient', 'appointment.doctorProfile.employee')
            ->where('token', (string) $token)
            ->first();

        if (!$req) {
            return response()->view('patient-record.unavailable', [
                'reason' => 'This link is not valid. Please contact the hospital.',
            ], 404);
        }

        if ($req->status === 'withdrawn') {
            return response()->view('patient-record.unavailable', [
                'reason' => 'The hospital has withdrawn this request. Your original appointment stands.',
            ], 410);
        }

        if ($req->is_lapsed) {
            return response()->view('patient-record.unavailable', [
                'reason' => 'This request has expired. Please contact the hospital to arrange another time.',
            ], 410);
        }

        return $req;
    }

    /** The page itself — the proposal, or the outcome once it has been answered. */
    protected function page(AppointmentRescheduleRequest $req, ?string $flash = null)
    {
        $store = DB::table('stores')->where('id', $req->store_id)
            ->first(['name', 'address', 'phone', 'logo']);

        return response()
            ->view('patient-record.reschedule', [
                'req'    => $req,
                'store'  => $store,
                'doctor' => $req->appointment?->doctorProfile?->employee,
                'flash'  => $flash,
            ])
            // A link that moves an appointment has no business in a proxy cache or a search index.
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
