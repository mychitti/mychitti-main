<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\HmisHandover;
use App\Models\HospitalActivityLog;
use App\Models\LabOrder;
use App\Models\OpdLabWork;
use App\Services\HmisWhatsAppShare;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Recording what changed hands at the counter, and proving who it changed hands with.
 *
 * The thing being defended against is narrow and specific: a stranger walking in with a printed
 * report that no lab ever produced, and that report reaching a patient's chart. Signatures and
 * photographs do not stop that — a forger signs a false name without hesitating — so the two
 * controls that actually bite live here instead.
 *
 * The first is free: nothing can arrive from a lab that was never sent anything. checkDispatch()
 * asks that before anyone is asked to sign, and most of the attack dies on that question alone.
 *
 * The second is the code, and its direction is what matters. It goes to the lab's own saved
 * number, not to the phone of whoever is standing there, so passing it requires being able to
 * reach somebody inside that lab. The visitor has to ring their own office. A stranger cannot.
 */
class HmisHandoverController extends Controller
{
    /**
     * Which permission governs each kind of subject, and where a slip links back to.
     *
     * Lab work is part of the consultation record and answers to the OPD register; a pathology
     * order belongs to the laboratory and answers to its worklist. Same counter, same form, two
     * different sets of staff who should be allowed to use it.
     */
    const GUARDS = [
        'opd_lab_work' => ['feature' => 'opd_register', 'label' => 'lab work'],
        'lab_order'    => ['feature' => 'lab_worklist', 'label' => 'lab order'],
    ];

    protected function storeId(): int
    {
        return (int) Helpers::get_store_id();
    }

    /**
     * The subject, scoped to this store, with the acting user's permission checked for it.
     *
     * Store scoping first and always: a handover id is guessable, and the whole record is a claim
     * about who was physically present at one particular clinic.
     */
    protected function subject(string $type, $id, string $action = 'edit')
    {
        $guard = static::GUARDS[$type] ?? null;
        if (!$guard) {
            abort(404);
        }

        if (!auth('vendor')->check() && !hasPermission($guard['feature'], $action)) {
            abort(403);
        }

        HmisHandover::ensureSchema();

        $storeId = $this->storeId();

        if ($type === 'opd_lab_work') {
            if (!hmis_lab_work_enabled($storeId)) {
                abort(404);
            }
            OpdLabWork::ensureSchema();

            return OpdLabWork::where('store_id', $storeId)->findOrFail($id);
        }

        return LabOrder::where('store_id', $storeId)->findOrFail($id);
    }

    /** The lab this subject is with, as name / phone / supplier id, whichever half is filled in. */
    protected function labOf(string $type, $subject): array
    {
        if ($type === 'opd_lab_work') {
            return [
                'vendor_id' => $subject->lab_vendor_id,
                'name'      => $subject->labDisplayName(),
                'phone'     => $subject->contactPhone(),
                'internal'  => (bool) $subject->is_internal,
            ];
        }

        return [
            'vendor_id' => $subject->external_lab_id,
            'name'      => trim((string) $subject->external_lab_name) ?: null,
            'phone'     => trim((string) $subject->external_lab_phone) ?: null,
            'internal'  => blank($subject->external_lab_name) && blank($subject->external_lab_id),
        ];
    }

    /** What this subject is, in one line, for the message and the slip. */
    protected function titleOf(string $type, $subject): string
    {
        return $type === 'opd_lab_work'
            ? $subject->title()
            : trim((string) $subject->order_no) . ' — ' . ($subject->sample_type ?: 'Lab order');
    }

    /**
     * Whether anything could legitimately be moving this way right now.
     *
     * This is the cheapest and by far the most effective control in the whole feature, because it
     * does not depend on anyone at the counter being alert. A report cannot come back from a lab
     * that was never sent a sample. Work already marked received cannot be delivered a second
     * time. Neither of those needs a signature to catch, and both are exactly what a forged
     * delivery looks like from the system's side.
     *
     * Returns [expected, reason]. A false never blocks the save — it raises a banner and forces a
     * written reason — because the counter sometimes knows something the record does not, and a
     * hard refusal only teaches staff to stop writing handovers down at all.
     */
    protected function checkDispatch(string $type, $subject, string $direction): array
    {
        if ($type === 'opd_lab_work') {
            if (in_array($subject->status, OpdLabWork::CLOSED_STATUSES, true)) {
                return [false, 'This job is already closed (' . $subject->statusLabel() . '). Nothing should be moving on it.'];
            }

            if ($direction === 'in') {
                if (!$subject->sent_on) {
                    return [false, 'Nothing has been sent out on this job yet, so nothing can be coming back from the lab.'];
                }
                if ($subject->status === 'received') {
                    return [false, 'This job is already marked received. Check you are not recording the same delivery twice.'];
                }
            }

            return [true, null];
        }

        if ($direction === 'in') {
            if (!$subject->collected_at) {
                return [false, 'No sample has been collected on this order yet, so no report can be coming back for it.'];
            }
            if (in_array($subject->status, ['verified', 'sent'], true)) {
                return [false, 'This order is already reported and closed. A report arriving now is not expected.'];
            }

            return [true, null];
        }

        if (in_array($subject->status, ['verified', 'sent'], true)) {
            return [false, 'This order is already finished. Samples should not be leaving for it.'];
        }

        return [true, null];
    }

    /** The clinic-side name to record — whoever is actually logged in and standing there. */
    protected function staffName(): string
    {
        if (auth('vendor_employee')->check()) {
            $emp = auth('vendor_employee')->user();
            return trim(($emp->f_name ?? '') . ' ' . ($emp->l_name ?? '')) ?: 'Staff';
        }
        if (auth('vendor')->check()) {
            $v = auth('vendor')->user();
            return trim(($v->f_name ?? '') . ' ' . ($v->l_name ?? '')) ?: 'Staff';
        }

        return 'Staff';
    }

    protected function causer(): array
    {
        if (auth('vendor_employee')->check()) {
            return ['vendor_employee', (int) auth('vendor_employee')->id()];
        }
        if (auth('vendor')->check()) {
            return ['vendor', (int) auth('vendor')->id()];
        }

        return [null, null];
    }

    /**
     * Everything the counter needs before anyone signs anything: is this expected, who is this
     * lab, and who have they sent before.
     */
    public function start(Request $request, string $type, $id)
    {
        $subject = $this->subject($type, $id, 'view');

        $direction = $request->input('direction') === 'in' ? 'in' : 'out';
        $lab       = $this->labOf($type, $subject);

        [$expected, $reason] = $this->checkDispatch($type, $subject, $direction);

        return response()->json([
            'success'   => true,
            'title'     => $this->titleOf($type, $subject),
            'direction' => $direction,
            'strict'    => (bool) (HmisHandover::DIRECTIONS[$direction]['strict'] ?? false),
            'lab'       => [
                'name'     => $lab['name'],
                'phone'    => $lab['phone'],
                'masked'   => HmisWhatsAppShare::maskedPhone($lab['phone']),
                'internal' => $lab['internal'],
            ],
            'expected'  => $expected,
            'reason'    => $reason,
            'runners'   => HmisHandover::knownRunners($this->storeId(), $lab['vendor_id'], $lab['name']),
        ]);
    }

    /**
     * Send the verification code to the lab's own number.
     *
     * The row is created here rather than at save time because the code has to hang off something
     * that survives the two minutes it takes the runner to ring their office. It is a draft until
     * store() dates it: `happened_at` is null, and every screen that lists the custody trail skips
     * rows without one, so an abandoned verification never shows up as an exchange that happened.
     *
     * Note what is NOT accepted from the browser: the number. It is read from the lab record on
     * the server every time. A form field for it would hand the entire control to the person the
     * control exists to catch.
     */
    public function otp(Request $request, string $type, $id)
    {
        $subject = $this->subject($type, $id);

        $request->validate([
            'direction'   => 'required|in:out,in',
            'person_name' => 'required|string|max:150',
            'purpose'     => 'nullable|string|max:120',
            'handover_id' => 'nullable|integer',
        ]);

        $lab = $this->labOf($type, $subject);

        if (blank($lab['phone'])) {
            return response()->json([
                'success' => false,
                'message' => 'No phone number is saved for this lab, so there is nobody to verify with. Add one on the job first, or record the handover as unconfirmed.',
            ]);
        }

        $storeId = $this->storeId();

        $handover = $request->handover_id
            ? HmisHandover::where('store_id', $storeId)->whereNull('happened_at')->find($request->handover_id)
            : null;

        if (!$handover) {
            [$causerType, $causerId] = $this->causer();

            $handover = HmisHandover::create([
                'store_id'      => $storeId,
                'subject_type'  => $type,
                'subject_id'    => (int) $subject->id,
                'patient_id'    => $subject->patient_id,
                'direction'     => $request->direction,
                'purpose'       => $request->purpose,
                'lab_vendor_id' => $lab['vendor_id'],
                'lab_name'      => $lab['name'],
                'lab_phone'     => $lab['phone'],
                'person_name'   => $request->person_name,
                'staff_name'    => $this->staffName(),
                'causer_type'   => $causerType,
                'causer_id'     => $causerId,
                'verify_method' => 'otp',
                'verify_state'  => 'provisional',
                'ip'            => $request->ip(),
            ]);
        } else {
            $handover->forceFill([
                'direction'   => $request->direction,
                'person_name' => $request->person_name,
                'purpose'     => $request->purpose,
                'lab_phone'   => $lab['phone'],
            ])->save();
        }

        $code   = $handover->issueOtp($lab['phone']);
        $result = HmisWhatsAppShare::handoverOtp($handover, $code, $this->titleOf($type, $subject));

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']]);
        }

        HospitalActivityLog::record(
            $storeId, 'hmis_handover', (int) $handover->id, 'otp_sent',
            'Handover code sent to ' . $handover->lab_name . ' to verify ' . $handover->person_name
                . ' at the counter for ' . $this->titleOf($type, $subject),
            ['subject_type' => $type, 'subject_id' => (int) $subject->id, 'direction' => $handover->direction]
        );

        return response()->json([
            'success'     => true,
            'handover_id' => $handover->id,
            'sent_to'     => HmisWhatsAppShare::maskedPhone($lab['phone']),
            'expires_in'  => HmisHandover::OTP_TTL_MINUTES,
            'message'     => 'Code sent to ' . $lab['name'] . ' on ' . HmisWhatsAppShare::maskedPhone($lab['phone'])
                . '. Ask the person to get it from their office.',
        ]);
    }

    /** Check the code the runner read out. Attempts are counted on the row, not here. */
    public function verify(Request $request, $handoverId)
    {
        HmisHandover::ensureSchema();

        $request->validate(['code' => 'required|string|max:10']);

        $handover = HmisHandover::where('store_id', $this->storeId())->findOrFail($handoverId);

        $guard = static::GUARDS[$handover->subject_type] ?? null;
        if (!$guard || (!auth('vendor')->check() && !hasPermission($guard['feature'], 'edit'))) {
            abort(403);
        }

        if (!$handover->otpIsLive()) {
            return response()->json([
                'success' => false,
                'message' => $handover->otp_attempts >= HmisHandover::OTP_MAX_ATTEMPTS
                    ? 'Too many wrong codes. Send a fresh one.'
                    : 'That code has expired. Send a fresh one.',
            ]);
        }

        if (!$handover->verifyOtp($request->code)) {
            $left = max(0, HmisHandover::OTP_MAX_ATTEMPTS - $handover->otp_attempts);

            return response()->json([
                'success' => false,
                'message' => 'That code is wrong. ' . $left . ' ' . ($left === 1 ? 'try' : 'tries') . ' left.',
            ]);
        }

        HospitalActivityLog::record(
            (int) $handover->store_id, 'hmis_handover', (int) $handover->id, 'otp_verified',
            $handover->person_name . ' verified with ' . $handover->lab_name . ' by code',
            ['subject_type' => $handover->subject_type, 'subject_id' => (int) $handover->subject_id]
        );

        return response()->json(['success' => true, 'message' => 'Verified with ' . $handover->lab_name . '.']);
    }

    /**
     * Write the exchange down and let the paperwork follow it.
     *
     * Order matters here. The record is saved before the stage moves and before anything is sent,
     * so a WhatsApp failure or a stage that will not advance can never cost us the evidence of who
     * was standing at the counter — which is the one thing that cannot be reconstructed later.
     */
    public function store(Request $request, string $type, $id)
    {
        $subject = $this->subject($type, $id, 'add');

        $request->validate([
            'direction'       => 'required|in:out,in',
            'person_name'     => 'required|string|max:150',
            'person_phone'    => 'nullable|string|max:40',
            'person_id_ref'   => 'nullable|string|max:80',
            'purpose'         => 'nullable|string|max:120',
            'item_count'      => 'nullable|integer|min:1|max:999',
            'item_note'       => 'nullable|string|max:255',
            'notes'           => 'nullable|string|max:2000',
            'handover_id'     => 'nullable|integer',
            'signature'       => 'nullable|string',
            'photo'           => 'nullable|image|max:8192',
            'override_reason' => 'nullable|string|max:255',
        ]);

        $storeId  = $this->storeId();
        $lab      = $this->labOf($type, $subject);
        $title    = $this->titleOf($type, $subject);
        $strict   = (bool) (HmisHandover::DIRECTIONS[$request->direction]['strict'] ?? false);

        [$expected, $expectedReason] = $this->checkDispatch($type, $subject, $request->direction);

        // Going against the system's own reading of what should be happening is allowed, but never
        // silently: somebody has to type why, and it is kept on the row for good.
        if (!$expected && blank($request->override_reason)) {
            Toastr::error($expectedReason . ' Give a reason to record it anyway.');
            return back()->withInput();
        }

        $handover = $request->handover_id
            ? HmisHandover::where('store_id', $storeId)->find($request->handover_id)
            : null;

        if (!$handover) {
            [$causerType, $causerId] = $this->causer();
            $handover = new HmisHandover([
                'store_id'    => $storeId,
                'causer_type' => $causerType,
                'causer_id'   => $causerId,
            ]);
        }

        $verified = $handover->exists && $handover->otp_verified_at;

        $handover->fill([
            'subject_type'      => $type,
            'subject_id'        => (int) $subject->id,
            'patient_id'        => $subject->patient_id,
            'direction'         => $request->direction,
            'purpose'           => $request->purpose,
            'item_count'        => $request->item_count ?: null,
            'item_note'         => $request->item_note,
            'lab_vendor_id'     => $lab['vendor_id'],
            'lab_name'          => $lab['name'],
            'lab_phone'         => $lab['phone'],
            'person_name'       => $request->person_name,
            'person_phone'      => $request->person_phone,
            'person_id_ref'     => $request->person_id_ref,
            'staff_name'        => $this->staffName(),
            'happened_at'       => now(),
            'dispatch_expected' => $expected,
            'override_reason'   => $expected ? null : $request->override_reason,
            'notes'             => $request->notes,
            'ip'                => $request->ip(),
        ]);

        // An unverified arrival is 'provisional' for good. An unverified collection is merely
        // 'recorded' — nobody forges taking work away, and the lab is told the moment it leaves.
        $handover->verify_state = $verified
            ? 'verified'
            : ($strict ? 'provisional' : 'recorded');

        if (!$verified) {
            $handover->verify_method = filled($request->signature) ? 'signature' : 'none';
        }

        $handover->signature_path = $this->saveDataUrl($request->input('signature'), 'sig') ?: $handover->signature_path;

        // upload() returns the bare filename, so the directory is put back on: what is stored is a
        // disk-relative path, which is what mediaUrl() and the slip both expect.
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $name = Helpers::upload(
                HmisHandover::MEDIA_DIR . '/',
                $file->getClientOriginalExtension() ?: 'jpg',
                $file
            );
            $handover->photo_path = HmisHandover::MEDIA_DIR . '/' . $name;
        }

        $handover->save();

        $moved = $this->advance($type, $subject, $request->direction);
        $this->syncLegacyCustody($type, $subject, $handover);

        HospitalActivityLog::record(
            $storeId, 'hmis_handover', (int) $handover->id,
            $handover->is_inbound ? 'received' : 'released',
            $handover->movementSentence() . ' — ' . $title
                . ' (' . $handover->stateLabel() . ')'
                . ($expected ? '' : ' [recorded against the record: ' . $request->override_reason . ']'),
            [
                'subject_type' => $type,
                'subject_id'   => (int) $subject->id,
                'patient_id'   => $subject->patient_id,
                'direction'    => $handover->direction,
                'verify_state' => $handover->verify_state,
                'expected'     => $expected,
                'stage'        => $moved,
            ]
        );

        // Both directions, as the confirmation is worth different things each way: outbound it is
        // the clinic's receipt, inbound it is the message that reaches a real lab about a delivery
        // they may know nothing about.
        if (filled($lab['phone'])) {
            $sent = HmisWhatsAppShare::handoverConfirm($handover, $title);
            if ($sent['success'] && $type === 'opd_lab_work') {
                $subject->forceFill(['vendor_notified_at' => now()])->save();
            }
        }

        Toastr::success(
            $handover->is_inbound
                ? ('Delivery recorded' . ($verified ? ' and verified with ' . $lab['name'] . '.' : ' — not yet confirmed with the lab.'))
                : 'Handover recorded and confirmed with ' . ($lab['name'] ?: 'the lab') . '.'
        );

        return back();
    }

    /**
     * Move the subject on, now that the thing has physically moved.
     *
     * Only ever forward, and only where the stage is behind the event: a job already at "ready"
     * does not fall back to "sent" because somebody recorded a trial going out, and the dated
     * milestones are filled in but never rewritten, so the first time work left is the date that
     * survives a remake.
     */
    protected function advance(string $type, $subject, string $direction): ?string
    {
        $target = HmisHandover::SUBJECTS[$type]['advances'][$direction] ?? null;
        if (!$target) {
            return null;
        }

        if ($type === 'opd_lab_work') {
            if (in_array($subject->status, OpdLabWork::CLOSED_STATUSES, true)) {
                return null;
            }

            $milestones = ['sent' => 'sent_on', 'received' => 'received_on'];
            $column     = $milestones[$target] ?? null;

            $subject->status = $target;
            if ($column && !$subject->{$column}) {
                $subject->{$column} = now()->toDateString();
            }
            $subject->save();

            return $target;
        }

        if ($subject->status === 'ordered') {
            $subject->status = $target;
            if (!$subject->collected_at) {
                $subject->collected_at = now();
            }
            $subject->save();

            return $target;
        }

        return null;
    }

    /**
     * Keep the four custody columns on the job showing the most recent exchange each way.
     *
     * Those columns predate this log and are what the consultation card, the WhatsApp handover
     * confirmation and custodyPairs() all read. Leaving them behind would mean a job whose card
     * says "not recorded" while a full verified trail sits underneath it, and staff believing the
     * card. So the log stays the record and these stay the summary of it — written from the event,
     * never typed alongside it, which is what stops the two disagreeing.
     */
    protected function syncLegacyCustody(string $type, $subject, HmisHandover $handover): void
    {
        if ($type !== 'opd_lab_work') {
            return;
        }

        $staff  = mb_substr(trim((string) $handover->staff_name), 0, 120);
        $person = mb_substr(trim((string) $handover->person_name), 0, 120);

        $subject->forceFill($handover->is_inbound
            ? ['delivered_by' => $person, 'received_by' => $staff]
            : ['handed_over_by' => $staff, 'collected_by' => $person]
        )->save();
    }

    /**
     * A signature drawn on the counter tablet, arriving as a data URL.
     *
     * Written straight to the public disk rather than through the upload helper, which expects an
     * UploadedFile. Size-capped because a canvas PNG has no business being large and a data URL is
     * the easiest thing in the world to point at a hundred megabytes.
     */
    protected function saveDataUrl(?string $dataUrl, string $prefix): ?string
    {
        $dataUrl = trim((string) $dataUrl);
        if ($dataUrl === '' || !preg_match('#^data:image/(png|jpeg);base64,#i', $dataUrl, $m)) {
            return null;
        }

        $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
        if ($binary === false || strlen($binary) < 128 || strlen($binary) > 2 * 1024 * 1024) {
            return null;
        }

        $ext  = strtolower($m[1]) === 'jpeg' ? 'jpg' : 'png';
        $path = HmisHandover::MEDIA_DIR . '/' . $prefix . '-' . now()->format('Ymd') . '-' . uniqid() . '.' . $ext;

        try {
            Storage::disk('public')->put($path, $binary);
        } catch (\Throwable $e) {
            return null;
        }

        return $path;
    }

    /**
     * Confirm an arrival that was taken on trust, after the fact.
     *
     * The realistic path back from 'provisional': the lab was shut at six in the evening, somebody
     * rings them the next morning, and the record needs to stop saying "not yet confirmed". Who
     * vouched and when is written into the row rather than the flag simply being cleared.
     */
    public function confirm(Request $request, $handoverId)
    {
        HmisHandover::ensureSchema();

        $request->validate(['how' => 'required|string|max:255']);

        $handover = HmisHandover::where('store_id', $this->storeId())->findOrFail($handoverId);

        $guard = static::GUARDS[$handover->subject_type] ?? null;
        if (!$guard || (!auth('vendor')->check() && !hasPermission($guard['feature'], 'edit'))) {
            abort(403);
        }

        if ($handover->verify_state !== 'provisional') {
            Toastr::error('That handover is not waiting on a confirmation.');
            return back();
        }

        $handover->forceFill([
            'verify_state'    => 'verified',
            'verify_method'   => 'manual',
            'otp_verified_at' => now(),
            'notes'           => trim((string) $handover->notes . "\nConfirmed later by " . $this->staffName() . ': ' . $request->how),
        ])->save();

        HospitalActivityLog::record(
            (int) $handover->store_id, 'hmis_handover', (int) $handover->id, 'confirmed_later',
            'Handover by ' . $handover->person_name . ' of ' . $handover->lab_name
                . ' confirmed after the fact: ' . $request->how,
            ['subject_type' => $handover->subject_type, 'subject_id' => (int) $handover->subject_id]
        );

        Toastr::success('Handover confirmed.');
        return back();
    }

    /** The paper half — what the runner carries away, and what a clinic files. */
    public function slip($handoverId)
    {
        HmisHandover::ensureSchema();

        $handover = HmisHandover::where('store_id', $this->storeId())->findOrFail($handoverId);

        $guard = static::GUARDS[$handover->subject_type] ?? null;
        if (!$guard || (!auth('vendor')->check() && !hasPermission($guard['feature'], 'view'))) {
            abort(403);
        }

        $subject = $handover->subject();

        return view('hmis::vendor.handover.slip', [
            'handover' => $handover,
            'subject'  => $subject,
            'title'    => $subject ? $this->titleOf($handover->subject_type, $subject) : 'Lab work',
            'store'    => \App\Models\Store::find($handover->store_id),
        ]);
    }
}
