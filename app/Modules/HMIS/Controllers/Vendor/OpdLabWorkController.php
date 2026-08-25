<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\HospitalActivityLog;
use App\Models\OpdLabWork;
use App\Models\OpdVisit;
use App\Models\StoreCustomer;
use App\Models\VendorEmployee;
use App\Services\HmisWhatsAppShare;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

/**
 * Lab work — in-house or sent out — tracked from the consultation screen.
 *
 * The two modes share every stage and every screen, and differ in exactly three places: which
 * half of the form is asked for, whether there is a firm outside the building to tell about the
 * job, and who a handover confirmation is addressed to. Everything else here is written once.
 *
 * Every action answers to hmis_lab_work_enabled(): a hospital that does not do lab work never has
 * these endpoints do anything, so a stale tab in an open browser cannot write rows into a module
 * the clinic switched off.
 */
class OpdLabWorkController extends Controller
{
    protected function storeId(): int
    {
        return (int) Helpers::get_store_id();
    }

    /** The tab is a hospital-level choice; the endpoints behind it have to enforce it too. */
    protected function guard(): void
    {
        if (!auth('vendor')->check() && !hasPermission('opd_register', 'view')) {
            abort(403);
        }
        if (!hmis_lab_work_enabled($this->storeId())) {
            abort(404);
        }
    }

    /** One job, scoped to this store so an id from another hospital never loads. */
    protected function find($id): OpdLabWork
    {
        OpdLabWork::ensureSchema();

        return OpdLabWork::where('store_id', $this->storeId())->findOrFail($id);
    }

    /**
     * Only the measurements this speciality actually defines are kept.
     *
     * The form is built from the profile, so anything else in the post is either a stale tab from
     * before a profile changed or someone hand-crafting a request. Neither should be able to put
     * arbitrary keys into a clinical record.
     */
    protected function measurements(Request $request, array $profile): array
    {
        $posted = (array) $request->input('measurements', []);
        $clean  = [];

        foreach ($profile['fields'] as $key => $field) {
            $value = trim((string) ($posted[$key] ?? ''));
            if ($value !== '') {
                $clean[$key] = mb_substr($value, 0, 190);
            }
        }

        return $clean;
    }

    /**
     * $withStatus is false when editing: an existing job's stage moves through status(), which
     * owns the dated milestones and the patient message. Two places setting it would mean two
     * ideas of what "received" does to received_on, and they would drift.
     */
    protected function rules(bool $withStatus = true): array
    {
        return array_filter([
            'work_type'        => 'required|string|max:150',
            'site'             => 'nullable|string|max:190',
            'lab_mode'         => 'required|in:' . implode(',', array_keys(OpdLabWork::MODES)),
            'lab_type'         => 'nullable|string|max:120',
            // The lab's identity comes from the supplier record and nowhere else, so a job sent
            // out has to name one. Without this a job could be saved as external with no lab on
            // it at all — nobody to send it to, and nothing to chase in three weeks.
            'lab_vendor_id'    => 'required_if:lab_mode,external|nullable|integer',
            // Required for an in-house job and nothing else. A bench job with nobody on it is the
            // exact record nobody can answer for three weeks later, and it is the one thing the
            // internal half of the form exists to capture.
            'technician_id'    => 'required_if:lab_mode,internal|nullable|integer',
            // The number the WhatsApp actually goes to, for this one job. Typed over the staff or
            // supplier record's number where the work is going somewhere else today; see labFields().
            'lab_phone'        => 'nullable|string|max:40',
            'technician_phone' => 'nullable|string|max:40',
            'status'           => $withStatus ? 'required|in:' . implode(',', OpdLabWork::STATUSES) : null,
            'sent_on'          => 'nullable|date',
            'expected_on'      => 'nullable|date',
            'amount'           => 'nullable|numeric|min:0|max:99999999',
            'notes'            => 'nullable|string|max:2000',
            'measurements'     => 'nullable|array',
        ]);
    }

    /**
     * Who is doing the work, resolved from the form into the columns that describe it.
     *
     * Two things happen here that the form cannot be trusted to do. The half that does not apply
     * is CLEARED rather than left as it was: a job switched from an outside lab to the bench that
     * kept the lab's phone number would go on sending handover confirmations to a firm no longer
     * touching it. And a chosen vendor's name, number and address are COPIED onto the job rather
     * than read through the relation at display time — a lab that is renamed, or removed from the
     * address book next year, must not silently rewrite what a record from today says about where
     * a patient's crown went.
     */
    protected function labFields(Request $request, int $storeId): array
    {
        $mode = $request->input('lab_mode') === 'internal' ? 'internal' : 'external';

        if ($mode === 'internal') {
            $staffId = (int) $request->input('technician_id');
            $staff   = $staffId
                ? VendorEmployee::where('store_id', $storeId)->find($staffId)
                : null;

            return [
                'lab_mode'         => 'internal',
                'lab_type'         => null,
                'lab_vendor_id'    => null,
                'lab_name'         => null,
                'lab_phone'        => null,
                'lab_address'      => null,
                'technician_id'    => $staff?->id,
                'technician_name'  => $staff ? trim($staff->f_name . ' ' . $staff->l_name) : null,
                'technician_phone' => $this->contactPhone($request->technician_phone, $staff?->phone),
            ];
        }

        $vendorId = (int) $request->input('lab_vendor_id');
        $vendor   = $vendorId
            ? StoreCustomer::where('store_id', $storeId)->where('user_type', 'vendor')->find($vendorId)
            : null;

        // Name and address are read from the supplier row and never from the request. The form does
        // not offer them, and accepting them if they turned up anyway would let a hand-made post
        // file a job against a real lab's id under a name of its own.
        return [
            'lab_mode'         => 'external',
            // The lab's own kind, when the job was not told a different one — it was answered
            // once on the supplier record and does not need retyping per job.
            'lab_type'         => filled($request->lab_type) ? $request->lab_type : $vendor?->lab_type,
            'lab_vendor_id'    => $vendor?->id,
            'lab_name'         => $vendor?->f_name,
            'lab_phone'        => $this->contactPhone($request->lab_phone, $vendor?->phone),
            'lab_address'      => $vendor?->address,
            'technician_id'    => null,
            'technician_name'  => null,
            'technician_phone' => null,
        ];
    }

    /**
     * The number this job's messages go to: what was typed, or the record's own number.
     *
     * A per-job override, deliberately one-way. The lab's main line is not always the number the
     * ceramist actually answers, and a job going to their workshop this week should reach them
     * there — but that is a fact about this crown, not a correction to the supplier, and writing it
     * back would silently redirect every other job and invoice for that firm. Changing a lab's
     * number for good is an edit to the supplier record, where somebody means to do it.
     */
    protected function contactPhone($typed, $onRecord): ?string
    {
        $typed = trim((string) $typed);

        return $typed !== '' ? mb_substr($typed, 0, 40) : (trim((string) $onRecord) ?: null);
    }

    public function store(Request $request, $visitId)
    {
        $this->guard();

        if (!auth('vendor')->check() && !hasPermission('opd_register', 'add')) {
            abort(403);
        }

        $storeId = $this->storeId();
        $visit   = OpdVisit::where('store_id', $storeId)->findOrFail($visitId);
        $profile = OpdLabWork::profileFor($storeId);

        $request->validate($this->rules());

        OpdLabWork::ensureSchema();

        $work = OpdLabWork::create(array_merge([
            'store_id'          => $storeId,
            'patient_id'        => $visit->patient_id,
            'opd_visit_id'      => $visit->id,
            'doctor_profile_id' => $visit->doctor_profile_id,
            'work_type'         => $request->work_type,
            'site'              => $request->site,
            'measurements'      => $this->measurements($request, $profile) ?: null,
            'status'            => $request->status,
            'sent_on'           => $request->sent_on ?: null,
            'expected_on'       => $request->expected_on ?: null,
            'amount'            => $request->amount === null || $request->amount === '' ? null : (float) $request->amount,
            'notes'             => $request->notes,
        ], $this->labFields($request, $storeId)));

        HospitalActivityLog::record(
            $storeId, 'opd_lab_work', (int) $work->id, 'created',
            $profile['label'] . ' job opened for patient #' . $visit->patient_id . ': ' . $work->title()
                . ' (' . $work->statusLabel($profile) . ') — ' . $work->labDisplayName(),
            [
                'patient_id'   => $visit->patient_id,
                'opd_visit_id' => $visit->id,
                'status'       => $work->status,
                'lab_mode'     => $work->lab_mode,
            ]
        );

        Toastr::success($profile['label'] . ' job added.');

        // Telling the lab is the whole point of opening the job for them, so the form offers it on
        // the same click. Never automatic: a job opened at the impression stage often has no
        // specification worth sending yet, and a lab messaged twice about one crown starts
        // ignoring the messages.
        if ($request->boolean('notify_lab')) {
            return $this->sendJobToLab($work, $profile);
        }

        return back();
    }

    public function update(Request $request, $id)
    {
        $this->guard();

        if (!auth('vendor')->check() && !hasPermission('opd_register', 'edit')) {
            abort(403);
        }

        $work    = $this->find($id);
        $profile = OpdLabWork::profileFor($this->storeId());

        $request->validate($this->rules(false));

        $work->fill(array_merge([
            'work_type'    => $request->work_type,
            'site'         => $request->site,
            'measurements' => $this->measurements($request, $profile) ?: null,
            'sent_on'      => $request->sent_on ?: null,
            'expected_on'  => $request->expected_on ?: null,
            'amount'       => $request->amount === null || $request->amount === '' ? null : (float) $request->amount,
            'notes'        => $request->notes,
        ], $this->labFields($request, $this->storeId())));

        $work->save();

        HospitalActivityLog::record(
            $this->storeId(), 'opd_lab_work', (int) $work->id, 'updated',
            $profile['label'] . ' job updated for patient #' . $work->patient_id . ': ' . $work->title(),
            ['patient_id' => $work->patient_id]
        );

        Toastr::success('Lab work updated.');
        return back();
    }

    /**
     * Move a job to its next stage, and optionally tell the patient it moved.
     *
     * The milestone dates are set from the stage rather than typed: a clinic that has just marked
     * something "Received at clinic" has already told us the date, and asking again is a box that
     * gets left wrong. Only ever filled in, never cleared — a job that goes back for a remake
     * keeps the date it was first received.
     */
    public function status(Request $request, $id)
    {
        $this->guard();

        if (!auth('vendor')->check() && !hasPermission('opd_register', 'edit')) {
            abort(403);
        }

        $request->validate([
            'status'         => 'required|in:' . implode(',', OpdLabWork::STATUSES),
            'notes'          => 'nullable|string|max:2000',
            'notify'         => 'nullable|boolean',
            'notify_lab'     => 'nullable|boolean',
            'handed_over_by' => 'nullable|string|max:120',
            'collected_by'   => 'nullable|string|max:120',
            'delivered_by'   => 'nullable|string|max:120',
            'received_by'    => 'nullable|string|max:120',
        ]);

        $storeId = $this->storeId();
        $work    = $this->find($id);
        $profile = OpdLabWork::profileFor($storeId);
        $from    = $work->statusLabel($profile);

        $work->status = $request->status;

        $milestones = ['sent' => 'sent_on', 'received' => 'received_on', 'fitted' => 'fitted_on'];
        if (isset($milestones[$request->status]) && !$work->{$milestones[$request->status]}) {
            $work->{$milestones[$request->status]} = now()->toDateString();
        }

        // Only the pair belonging to this move is read. The form shows one pair at a time, but a
        // browser posts every input it holds, and a stale name from the other direction written on
        // an unrelated stage change would put someone's name against a handover they had no part in.
        $custody = $this->custodyFor($request, $request->status);
        foreach ($custody as $field => $value) {
            $work->{$field} = $value;
        }

        if ($request->filled('notes')) {
            $work->notes = $request->notes;
        }

        $work->save();

        HospitalActivityLog::record(
            $storeId, 'opd_lab_work', (int) $work->id, 'status_changed',
            $profile['label'] . ' job for patient #' . $work->patient_id . ' — ' . $work->title()
                . ': ' . $from . ' to ' . $work->statusLabel($profile)
                . ($custody ? ' (' . $this->movementLine($work, $request->status) . ')' : ''),
            [
                'patient_id' => $work->patient_id,
                'from'       => $from,
                'to'         => $work->status,
                'custody'    => $custody ?: null,
            ]
        );

        Toastr::success('Marked as ' . $work->statusLabel($profile) . '.');

        // Both messages can be wanted on one move — the patient hears their crown is back, the lab
        // hears who signed for it — and they go to different numbers. The lab goes first because
        // its message is the one carrying a confirmation somebody may need to quote.
        //
        // Checked against the stage rather than the tick alone: the box is only shown on the two
        // moves that are a handover, and a confirmation of a handover that did not happen — from a
        // stale tab, or a hand-made request — tells the lab something untrue.
        if ($request->boolean('notify_lab') && in_array($request->status, ['sent', 'received'], true)) {
            $this->sendHandover($work, $request->status);
        }

        if ($request->boolean('notify')) {
            return $this->send($work, null);
        }

        return back();
    }

    /**
     * The custody columns this stage change is allowed to write, and what to write in them.
     *
     * Work only ever changes hands at two moments: when it leaves the clinic and when it comes
     * back. Every other stage is the lab getting on with it, and has no handover to record.
     */
    protected function custodyFor(Request $request, string $status): array
    {
        $pairs = [
            'sent'     => ['handed_over_by', 'collected_by'],
            'received' => ['delivered_by', 'received_by'],
        ];

        $fields = $pairs[$status] ?? [];
        $values = [];

        foreach ($fields as $field) {
            $value = trim((string) $request->input($field));
            if ($value !== '') {
                $values[$field] = mb_substr($value, 0, 120);
            }
        }

        return $values;
    }

    /**
     * The handover in one sentence — "Handed over by Dr Meera and collected by Suresh on 24 Aug".
     *
     * Built from what is stored rather than from the request, so a confirmation resent next week
     * says the same thing as the one sent on the day. Names left blank are simply left out; a
     * confirmation reading "collected by —" tells the lab nothing it can act on.
     */
    protected function movementLine(OpdLabWork $work, string $status): string
    {
        if ($status === 'received') {
            $parts = array_filter([
                trim((string) $work->delivered_by) !== '' ? 'Delivered by ' . $work->delivered_by : null,
                trim((string) $work->received_by) !== '' ? 'received by ' . $work->received_by : null,
            ]);
            $on = $work->received_on ?: now();
        } else {
            $parts = array_filter([
                trim((string) $work->handed_over_by) !== '' ? 'Handed over by ' . $work->handed_over_by : null,
                trim((string) $work->collected_by) !== '' ? 'collected by ' . $work->collected_by : null,
            ]);
            $on = $work->sent_on ?: now();
        }

        // ucfirst because the second half of each pair is written lower-case to read as a clause;
        // where only that half was filled in it becomes the start of the sentence.
        $line = $parts ? ucfirst(implode(' and ', $parts)) : ($status === 'received' ? 'Received at the clinic' : 'Handed over to the lab');

        return $line . ' on ' . \Carbon\Carbon::parse($on)->format('d M Y');
    }

    /**
     * Send the lab the job itself — what to make, for whom, to what specification, by when.
     *
     * Separate from notify() below, which tells the PATIENT where their work has got to. The two
     * are opposite directions on the same record and must never be confused: this one carries the
     * patient's name and their specification to a third party, and it goes only where the clinic
     * has deliberately said to send it.
     */
    public function notifyVendor(Request $request, $id)
    {
        $this->guard();

        if (!auth('vendor')->check() && !hasPermission('opd_register', 'edit')) {
            abort(403);
        }

        $work  = $this->find($id);
        $phone = trim((string) $request->input('phone'));

        return $this->sendJobToLab($work, null, $phone !== '' ? $phone : null);
    }

    /**
     * Confirm a handover to the lab on demand — the same message the stage change offers, for
     * when it was declined at the time or the lab says it never arrived.
     */
    public function notifyHandover(Request $request, $id)
    {
        $this->guard();

        if (!auth('vendor')->check() && !hasPermission('opd_register', 'edit')) {
            abort(403);
        }

        $request->validate(['movement' => 'nullable|in:sent,received']);

        $work = $this->find($id);

        // Which handover is being confirmed: what was asked for, else whichever one this job has
        // actually reached — a job back at the clinic is confirming the delivery, not the send.
        $movement = $request->input('movement')
            ?: ($work->received_on || in_array($work->status, ['received', 'fitted'], true) ? 'received' : 'sent');

        $this->sendHandover($work, $movement);

        return back();
    }

    /**
     * The one place a job goes out to a lab.
     *
     * Stamped on success so the tab can say the lab has already been told, and so a second person
     * looking at the same job does not send it again. A failed template is not something the lab
     * heard about, so it leaves no stamp.
     */
    protected function sendJobToLab(OpdLabWork $work, ?array $profile = null, ?string $phone = null)
    {
        $profile = $profile ?: OpdLabWork::profileFor((int) $work->store_id);
        $result  = HmisWhatsAppShare::labWorkVendorJob($work, $phone);

        if ($result['success']) {
            $work->forceFill(['vendor_notified_at' => now()])->save();

            HospitalActivityLog::record(
                (int) $work->store_id, 'opd_lab_work', (int) $work->id, 'lab_notified',
                $profile['label'] . ' job sent on WhatsApp to ' . $work->labDisplayName()
                    . ' for patient #' . $work->patient_id . ': ' . $work->title(),
                ['patient_id' => $work->patient_id, 'lab_mode' => $work->lab_mode, 'lab' => $work->labDisplayName()]
            );

            Toastr::success($result['message']);
        } else {
            Toastr::error($result['message']);
        }

        return back();
    }

    /** The one place a handover confirmation goes out. */
    protected function sendHandover(OpdLabWork $work, string $movement): void
    {
        $line   = $this->movementLine($work, $movement);
        $result = HmisWhatsAppShare::labWorkHandover($work, $line);

        if ($result['success']) {
            HospitalActivityLog::record(
                (int) $work->store_id, 'opd_lab_work', (int) $work->id, 'handover_confirmed',
                'Handover confirmed on WhatsApp with ' . $work->labDisplayName()
                    . ' for patient #' . $work->patient_id . ': ' . $work->title() . ' — ' . $line,
                ['patient_id' => $work->patient_id, 'movement' => $movement, 'lab' => $work->labDisplayName()]
            );

            Toastr::success($result['message']);
        } else {
            Toastr::error($result['message']);
        }
    }

    /** Send the current stage to the patient on WhatsApp, without changing anything. */
    public function notify(Request $request, $id)
    {
        $this->guard();

        if (!auth('vendor')->check() && !hasPermission('opd_register', 'edit')) {
            abort(403);
        }

        $phone = trim((string) $request->input('phone'));

        return $this->send($this->find($id), $phone !== '' ? $phone : null);
    }

    /**
     * The one place a lab work update actually goes out.
     *
     * Records what was sent on the job itself, so the tab can show "already told them, on Tuesday"
     * rather than leaving staff to guess and send a second one. Only a successful send is stamped:
     * a failed template is not something the patient heard about.
     */
    protected function send(OpdLabWork $work, ?string $phone)
    {
        $result = HmisWhatsAppShare::labWorkStatus($work, $phone);

        if ($result['success']) {
            $work->forceFill([
                'last_notified_status' => $work->status,
                'last_notified_at'     => now(),
            ])->save();

            HospitalActivityLog::record(
                (int) $work->store_id, 'opd_lab_work', (int) $work->id, 'notified',
                'Lab work update sent on WhatsApp for patient #' . $work->patient_id . ': ' . $work->title(),
                ['patient_id' => $work->patient_id, 'status' => $work->status]
            );

            Toastr::success($result['message']);
        } else {
            Toastr::error($result['message']);
        }

        return back();
    }

    public function destroy($id)
    {
        $this->guard();

        if (!auth('vendor')->check() && !hasPermission('opd_register', 'delete')) {
            abort(403);
        }

        $work    = $this->find($id);
        $title   = $work->title();
        $patient = $work->patient_id;
        $work->delete();

        HospitalActivityLog::record(
            $this->storeId(), 'opd_lab_work', (int) $id, 'deleted',
            'Lab work job deleted for patient #' . $patient . ': ' . $title,
            ['patient_id' => $patient]
        );

        Toastr::success('Lab work removed.');
        return back();
    }
}
