<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentRescheduleRequest;
use App\Models\AppointmentToken;
use App\Models\DoctorProfile;
use App\Models\DoctorSlot;
use App\Models\Patient;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\HmisWhatsAppShare;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AppointmentController extends Controller
{
    /**
     * Appointments had no feature row of their own, so every screen and every write in this
     * controller was open to any staff member who could reach the URL. One feature with the
     * actions the screens actually perform — the reschedule flow (move, withdraw, resend) and
     * the doctor reassignment are separate from plain editing because a receptionist is usually
     * trusted with one and not the other.
     */
    const FEATURES = [
        'hmis_appointment' => ['Appointment', ['list', 'add', 'view', 'status_change', 'reschedule', 'reassign']],
    ];

    public static function ensurePermission(): void
    {
        // Called from the hospital sidebar, so it runs on every panel page. One indexed lookup on
        // the last action seeded is the whole cost once a store is provisioned.
        try {
            $seeded = DB::table('feature_permissions as fp')
                ->join('features as f', 'fp.feature_id', '=', 'f.id')
                ->where('f.name', 'hmis_appointment')
                ->where('fp.action', 'reassign')
                ->exists();
            if ($seeded) {
                return;
            }
        } catch (\Throwable $e) {
            return; // permission tables not provisioned on this database yet
        }

        if (!Schema::hasTable('features') || !Schema::hasTable('feature_permissions')) {
            return;
        }

        foreach (self::FEATURES as $name => [$display, $actions]) {
            $fid = DB::table('features')->where('name', $name)->value('id');
            $isNew = false;
            if (!$fid) {
                $fid = DB::table('features')->insertGetId([
                    'name' => $name, 'display_name' => $display, 'master_module' => 'hospital_manage',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $isNew = true;
            }
            foreach ($actions as $a) {
                if (!DB::table('feature_permissions')->where('feature_id', $fid)->where('action', $a)->exists()) {
                    DB::table('feature_permissions')->insert(['feature_id' => $fid, 'action' => $a, 'free' => 0]);
                }
            }
            if ($isNew) {
                self::backfillFromLeads($fid);
            }
        }
    }

    /**
     * Until this feature existed the only link into these screens was the hospital appointment
     * list, which is gated on leads_manage.list. Roles that hold it keep the access they already
     * had; everyone else starts denied. Runs once, on the request that creates the feature.
     */
    private static function backfillFromLeads(int $featureId): void
    {
        if (!Schema::hasTable('role_feature_permissions')) {
            return;
        }

        // Hospital stores only — a plumbing firm's roles have no business gaining hospital rows.
        $roleIds = DB::table('role_feature_permissions as rfp')
            ->join('feature_permissions as fp', 'rfp.feature_permission_id', '=', 'fp.id')
            ->join('features as f', 'fp.feature_id', '=', 'f.id')
            ->join('employee_roles as er', 'rfp.role_id', '=', 'er.id')
            ->join('stores as st', 'er.store_id', '=', 'st.id')
            ->whereRaw('LOWER(st.business_type) = ?', ['hospital'])
            ->where('f.name', 'leads_manage')
            ->where('fp.action', 'list')
            ->pluck('rfp.role_id')
            ->unique();

        if ($roleIds->isEmpty()) {
            return;
        }

        $permIds = DB::table('feature_permissions')->where('feature_id', $featureId)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permIds as $permId) {
                $exists = DB::table('role_feature_permissions')
                    ->where('role_id', $roleId)
                    ->where('feature_permission_id', $permId)
                    ->exists();
                if (!$exists) {
                    DB::table('role_feature_permissions')->insert([
                        'role_id' => $roleId,
                        'feature_permission_id' => $permId,
                    ]);
                }
            }
        }
    }

    public function index(Request $request)
    {
        self::ensurePermission();
        $store_id = Helpers::get_store_id();
        $doctors  = DoctorProfile::where('store_id', $store_id)->with('employee')->get();

        $query = Appointment::where('store_id', $store_id)
            ->with(['patient', 'doctorProfile.employee', 'token'])
            ->orderByDesc('appointment_date')
            ->orderBy('appointment_time');

        if ($request->date) {
            $query->where('appointment_date', $request->date);
        }
        if ($request->doctor_id) {
            $query->where('doctor_profile_id', $request->doctor_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $appointments = $query->paginate(20)->withQueryString();

        return view('hmis::vendor.appointment.list', [
            'appointments' => $appointments,
            'doctors'      => $doctors,
            'statuses'     => Appointment::STATUSES,
            'filters'      => $request->only('date', 'doctor_id', 'status'),
        ]);
    }

    public function create()
    {
        if (!auth('vendor')->check() && !hasPermission('hmis_appointment', 'add')) abort(403);

        return view('hmis::vendor.appointment.create');
    }

    public function searchPatients(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $q        = $request->get('q');

        $patients = Patient::where('store_id', $store_id)
            ->where('status', 1)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%")
                      ->orWhere('patient_uid', 'like', "%{$q}%");
            })
            ->select('id', 'name', 'phone', 'patient_uid')
            ->limit(15)
            ->get()
            ->map(fn($p) => [
                'id'   => $p->id,
                'text' => "{$p->name} — {$p->phone} ({$p->patient_uid})",
            ]);

        return response()->json($patients);
    }

    public function searchDoctors(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $q        = $request->get('q');

        $doctors = DoctorProfile::where('store_id', $store_id)
            ->with('employee')
            ->whereHas('employee', function ($query) use ($q) {
                $query->where('f_name', 'like', "%{$q}%")
                      ->orWhere('l_name', 'like', "%{$q}%");
            })
            ->orWhere(function ($query) use ($store_id, $q) {
                $query->where('store_id', $store_id)
                      ->where('specialization', 'like', "%{$q}%");
            })
            ->limit(15)
            ->get()
            ->map(fn($d) => [
                'id'   => $d->id,
                'text' => "Dr. {$d->employee?->f_name} {$d->employee?->l_name} — {$d->specialization}",
            ]);

        return response()->json($doctors);
    }

    public function availableSlots(Request $request)
    {
        $request->validate([
            'doctor_profile_id' => 'required|integer',
            'date'              => 'required|date',
        ]);

        $store_id  = Helpers::get_store_id();
        $dayOfWeek = Carbon::parse($request->date)->dayOfWeek;

        $slots = DoctorSlot::where('doctor_profile_id', $request->doctor_profile_id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', 1)
            ->get();

        $slots->each(function ($slot) use ($request) {
            $booked = Appointment::where('slot_id', $slot->id)
                ->where('appointment_date', $request->date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();
            $slot->booked    = $booked;
            $slot->available = max(0, $slot->max_patients - $booked);
        });

        return response()->json($slots);
    }

    public function store(Request $request)
    {
        if (!auth('vendor')->check() && !hasPermission('hmis_appointment', 'add')) abort(403);

        $request->validate([
            'patient_id'        => 'required|integer|exists:patients,id',
            'doctor_profile_id' => 'required|integer|exists:doctor_profiles,id',
            'appointment_date'  => 'required|date',
            'appointment_time'  => 'required',
            'slot_id'           => 'nullable|integer|exists:doctor_slots,id',
            'reason'            => 'nullable|string|max:500',
        ]);

        $store_id = Helpers::get_store_id();

        if ($request->slot_id) {
            $booked = Appointment::where('slot_id', $request->slot_id)
                ->where('appointment_date', $request->appointment_date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();

            $slot = DoctorSlot::findOrFail($request->slot_id);
            if ($booked >= $slot->max_patients) {
                return back()->withErrors(['slot_id' => 'This slot is fully booked.'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $appointment = Appointment::create([
                'store_id'          => $store_id,
                'patient_id'        => $request->patient_id,
                'doctor_profile_id' => $request->doctor_profile_id,
                'slot_id'           => $request->slot_id,
                'appointment_date'  => $request->appointment_date,
                'appointment_time'  => $request->appointment_time,
                'booking_type'      => 'walk_in',
                'status'            => 'scheduled',
                'reason'            => $request->reason,
                'booked_by'         => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            ]);

            $this->generateToken($request->doctor_profile_id, $request->appointment_date, $appointment->id);

            DB::commit();

            $appointment->load(['patient', 'doctorProfile.employee']);
            $patName  = $appointment->patient?->name . ' (' . $appointment->patient?->patient_uid . ')';
            $drName   = 'Dr. ' . trim(($appointment->doctorProfile?->employee?->f_name ?? '') . ' ' . ($appointment->doctorProfile?->employee?->l_name ?? ''));
            \App\Models\HospitalActivityLog::record(
                $store_id, 'appointment', $appointment->id, 'created',
                "Appointment created for {$patName} with {$drName} on {$request->appointment_date}",
                ['doctor_profile_id' => $appointment->doctor_profile_id, 'date' => $request->appointment_date]
            );

            Toastr::success('Appointment booked successfully');
            return redirect()->route('vendor.appointment.show', $appointment->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to book appointment: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function show($id)
    {
        if (!auth('vendor')->check() && !hasPermission('hmis_appointment', 'view')) abort(403);

        $store_id    = Helpers::get_store_id();
        $appointment = Appointment::where('store_id', $store_id)
            ->with(['patient', 'doctorProfile.employee', 'slot', 'token', 'rescheduledFrom'])
            ->findOrFail($id);

        $doctors = DoctorProfile::where('store_id', $store_id)->with('employee')->get();

        // Every time this appointment has been put to the patient, newest first — the open one to
        // act on, and the answered ones because "we asked and they said no" is the context behind
        // an appointment still sitting on its original date.
        AppointmentRescheduleRequest::ensureSchema();
        $rescheduleRequests = AppointmentRescheduleRequest::where('appointment_id', $appointment->id)
            ->orderByDesc('id')
            ->get();

        return view('hmis::vendor.appointment.show', [
            'appointment'        => $appointment,
            'nextStatuses'       => Appointment::STATUS_TRANSITIONS[$appointment->status] ?? [],
            'doctors'            => $doctors,
            'rescheduleRequests' => $rescheduleRequests,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        if (!auth('vendor')->check() && !hasPermission('hmis_appointment', 'status_change')) abort(403);

        $request->validate([
            'status'        => 'required|in:' . implode(',', Appointment::STATUSES),
            'cancel_reason' => 'required_if:status,cancelled|nullable|string|max:500',
        ]);

        $store_id    = Helpers::get_store_id();
        $appointment = Appointment::where('store_id', $store_id)->findOrFail($id);

        $allowed = Appointment::STATUS_TRANSITIONS[$appointment->status] ?? [];
        if (!in_array($request->status, $allowed)) {
            Toastr::error('Invalid status transition.');
            return back();
        }

        $oldStatus = $appointment->status;
        $appointment->status        = $request->status;
        $appointment->cancel_reason = $request->cancel_reason;
        $appointment->save();

        \App\Models\HospitalActivityLog::record(
            $store_id, 'appointment', $appointment->id, 'status_changed',
            "Appointment #{$appointment->id} status changed from {$oldStatus} to {$request->status}",
            ['from' => $oldStatus, 'to' => $request->status]
        );

        // Completing the appointment is the one unambiguous "the consultation is over" signal in
        // the module — an OPD visit has no such transition, its screen just saves fields as the
        // doctor types. So both the summary and the feedback request hang off this moment.
        if ($request->status === 'completed' && $oldStatus !== 'completed') {
            \App\Services\AppointmentCompletion::autoSend($appointment);
        }

        Toastr::success('Status updated to ' . $request->status);
        return back();
    }

    /**
     * Move the appointment, or ask the patient whether it may be moved.
     *
     * Two modes on one form because they answer the same question at different times of day. At
     * the counter, with the patient in front of you, moving it now and telling them is right. A
     * doctor called away next Tuesday is the other case entirely — twenty appointments quietly
     * changing under twenty people, half of whom arrive at the old time regardless. There the
     * appointment must not move until each patient has said yes, which is what mode=request does.
     */
    public function reschedule(Request $request, $id)
    {
        if (!auth('vendor')->check() && !hasPermission('hmis_appointment', 'reschedule')) abort(403);

        $request->validate([
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
            'slot_id'          => 'nullable|integer|exists:doctor_slots,id',
            'mode'             => 'nullable|in:now,request',
            'note'             => 'nullable|string|max:500',
        ]);

        $store_id    = Helpers::get_store_id();
        $old         = Appointment::where('store_id', $store_id)->findOrFail($id);

        if (in_array($old->status, ['completed', 'cancelled'])) {
            Toastr::error('Cannot reschedule a ' . $old->status . ' appointment.');
            return back();
        }

        if (\App\Services\AppointmentReschedule::slotFull($request->slot_id ? (int) $request->slot_id : null, $request->appointment_date)) {
            Toastr::error('Selected slot is fully booked.');
            return back();
        }

        if ($request->input('mode') === 'request') {
            return $this->sendRescheduleRequest($request, $old);
        }

        try {
            $new = \App\Services\AppointmentReschedule::apply(
                $old,
                $request->appointment_date,
                $request->appointment_time,
                $request->slot_id ? (int) $request->slot_id : null,
                'the front desk',
                auth('vendor_employee')->id() ?? auth('vendor')->id()
            );

            // A pending request is a question about an appointment that has now moved anyway, so
            // the link the patient is holding is answered by events. Closed here rather than left
            // to expire, or tapping Confirm tomorrow would move the appointment a second time.
            $this->closePendingRequests($old, 'withdrawn');

            Toastr::success('Appointment rescheduled successfully');
            return redirect()->route('vendor.appointment.show', $new->id);
        } catch (\Exception $e) {
            Toastr::error('Reschedule failed: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Put the new time to the patient and leave the appointment alone.
     *
     * The row is written before the message goes out and kept even if the send fails: staff can
     * see it sitting there unsent and press Resend, which is a great deal better than a silent
     * failure that leaves them believing a patient was asked.
     */
    protected function sendRescheduleRequest(Request $request, Appointment $old)
    {
        AppointmentRescheduleRequest::ensureSchema();

        $old->loadMissing('patient');

        if (!$old->patient) {
            Toastr::error('This appointment has no patient record, so there is nobody to ask.');
            return back();
        }
        if (blank($old->patient->phone)) {
            Toastr::error('This patient has no phone number on file — reschedule it here and tell them yourself.');
            return back();
        }

        // One open question at a time. A patient holding two links, each proposing a different
        // Tuesday, can accept both — and the second one would move an appointment that the first
        // had already replaced.
        $this->closePendingRequests($old, 'withdrawn');

        $req = AppointmentRescheduleRequest::create([
            'store_id'       => (int) $old->store_id,
            'appointment_id' => (int) $old->id,
            'patient_id'     => (int) $old->patient_id,
            'from_date'      => $old->appointment_date,
            'from_time'      => $old->appointment_time,
            'to_date'        => $request->appointment_date,
            'to_time'        => $request->appointment_time,
            'slot_id'        => $request->slot_id ? (int) $request->slot_id : null,
            'note'           => $request->input('note'),
            'token'          => AppointmentRescheduleRequest::mintToken(),
            'status'         => 'pending',
            'sent_to'        => mb_substr((string) $old->patient->phone, 0, 32),
            'requested_by'   => auth('vendor_employee')->id() ?? auth('vendor')->id(),
            // The proposed time is its own deadline: an answer that arrives after it has passed
            // cannot be acted on, whatever it says.
            'expires_at'     => AppointmentRescheduleRequest::when($request->appointment_date, $request->appointment_time)
                ? \Carbon\Carbon::parse($request->appointment_date . ' ' . $request->appointment_time)
                : null,
        ]);

        $result = HmisWhatsAppShare::appointmentReschedule($req, route('appointment-reschedule', ['token' => $req->token]));

        if ($result['success']) {
            $req->forceFill(['sent_at' => now()])->save();

            \App\Models\HospitalActivityLog::record(
                (int) $old->store_id, 'appointment', (int) $old->id, 'reschedule_requested',
                "Asked patient #{$old->patient_id} to move appointment #{$old->id} from {$req->currentLabel()} to {$req->proposedLabel()}",
                ['patient_id' => $old->patient_id, 'to_date' => $req->to_date, 'request_id' => $req->id]
            );

            Toastr::success('Reschedule request sent. The appointment stays as it is until the patient confirms.');
        } else {
            Toastr::error($result['message']);
        }

        return back();
    }

    /** Close whatever is still open on an appointment, so only one question is ever live. */
    protected function closePendingRequests(Appointment $appointment, string $status): void
    {
        AppointmentRescheduleRequest::ensureSchema();

        AppointmentRescheduleRequest::where('appointment_id', $appointment->id)
            ->where('status', 'pending')
            ->update(['status' => $status, 'updated_at' => now()]);
    }

    /** Take back a request the hospital no longer needs — the doctor is free after all. */
    public function rescheduleWithdraw($id)
    {
        if (!auth('vendor')->check() && !hasPermission('hmis_appointment', 'reschedule')) abort(403);

        AppointmentRescheduleRequest::ensureSchema();

        $req = AppointmentRescheduleRequest::where('store_id', Helpers::get_store_id())->findOrFail($id);

        if ($req->status !== 'pending') {
            Toastr::error('That request has already been answered.');
            return back();
        }

        $req->forceFill(['status' => 'withdrawn'])->save();

        \App\Models\HospitalActivityLog::record(
            (int) $req->store_id, 'appointment', (int) $req->appointment_id, 'reschedule_withdrawn',
            "Reschedule request for appointment #{$req->appointment_id} withdrawn",
            ['patient_id' => $req->patient_id, 'request_id' => $req->id]
        );

        Toastr::success('Request withdrawn. The patient\'s link no longer works.');
        return back();
    }

    /** Send the same question again — the first message was missed, or the send failed. */
    public function rescheduleResend($id)
    {
        if (!auth('vendor')->check() && !hasPermission('hmis_appointment', 'reschedule')) abort(403);

        AppointmentRescheduleRequest::ensureSchema();

        $req = AppointmentRescheduleRequest::with('patient', 'appointment.doctorProfile.employee')
            ->where('store_id', Helpers::get_store_id())
            ->findOrFail($id);

        if (!$req->is_open) {
            Toastr::error('That request is closed — send a new one instead.');
            return back();
        }

        $result = HmisWhatsAppShare::appointmentReschedule($req, route('appointment-reschedule', ['token' => $req->token]));

        if ($result['success']) {
            $req->forceFill(['sent_at' => now()])->save();
            Toastr::success('Reschedule request sent again.');
        } else {
            Toastr::error($result['message']);
        }

        return back();
    }

    /**
     * Schedule the patient's NEXT VISIT (follow-up) from an existing appointment.
     * Creates a fresh 'scheduled' appointment for the same patient and doctor, so it
     * automatically rides the WhatsApp appointment-reminder automation. The patient also
     * gets an immediate courtesy confirmation on WhatsApp (best-effort).
     */
    public function nextVisit(Request $request, $id)
    {
        if (!auth('vendor')->check() && !hasPermission('hmis_appointment', 'add')) abort(403);

        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'slot_id'          => 'nullable|integer|exists:doctor_slots,id',
            'reason'           => 'nullable|string|max:500',
        ]);

        $store_id = Helpers::get_store_id();
        $current  = Appointment::where('store_id', $store_id)->findOrFail($id);

        try {
            $next = \App\Services\NextVisitService::schedule(
                (int) $store_id,
                (int) $current->patient_id,
                (int) $current->doctor_profile_id,
                $request->appointment_date,
                $request->appointment_time,
                $request->slot_id ? (int) $request->slot_id : null,
                trim((string) $request->reason) ?: ('Follow-up of appointment #' . $current->id),
                ['from_appointment_id' => (int) $current->id]
            );
        } catch (\RuntimeException $e) {
            Toastr::error($e->getMessage());
            return back();
        } catch (\Throwable $e) {
            Toastr::error('Could not schedule next visit: ' . $e->getMessage());
            return back();
        }

        Toastr::success('Next visit scheduled for ' . Carbon::parse($request->appointment_date)->format('d M Y') . '. The patient will get a WhatsApp reminder before it.');
        return redirect()->route('vendor.appointment.show', $next->id);
    }

    public function reassign(Request $request, $id)
    {
        if (!auth('vendor')->check() && !hasPermission('hmis_appointment', 'reassign')) abort(403);

        $request->validate([
            'doctor_profile_id' => 'required|integer|exists:doctor_profiles,id',
        ]);

        $store_id    = Helpers::get_store_id();
        $appointment = Appointment::where('store_id', $store_id)->findOrFail($id);

        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            Toastr::error('Cannot reassign a ' . $appointment->status . ' appointment.');
            return back();
        }

        if ($appointment->doctor_profile_id == $request->doctor_profile_id) {
            Toastr::warning('The selected doctor is already assigned to this appointment.');
            return back();
        }

        $doctor = DoctorProfile::with('employee')
            ->where('id', $request->doctor_profile_id)
            ->where('store_id', $store_id)
            ->first();

        if (!$doctor) {
            Toastr::error('Doctor not found in this store.');
            return back();
        }

        $oldDoctor = DoctorProfile::with('employee')->find($appointment->doctor_profile_id);

        $appointment->doctor_profile_id = $request->doctor_profile_id;
        $appointment->save();

        $oldDrName = 'Dr. ' . trim(($oldDoctor?->employee?->f_name ?? '') . ' ' . ($oldDoctor?->employee?->l_name ?? ''));
        $newDrName = 'Dr. ' . trim(($doctor->employee?->f_name ?? '') . ' ' . ($doctor->employee?->l_name ?? ''));
        \App\Models\HospitalActivityLog::record(
            $store_id, 'appointment', $appointment->id, 'reassigned',
            "Appointment #{$appointment->id} doctor reassigned from {$oldDrName} to {$newDrName}",
            ['from_doctor_id' => $oldDoctor?->id, 'to_doctor_id' => $doctor->id]
        );

        Toastr::success('Doctor reassigned successfully.');
        return back();
    }

    public function lookupLead(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $srId     = (int) $request->get('id');

        $sr = ServiceRequest::find($srId);

        if (!$sr) {
            return response()->json(['error' => 'Appointment ID not found.'], 404);
        }

        $sentTo = array_map('intval', array_filter(explode(',', $sr->sent_to ?? '')));
        if (!in_array((int)$store_id, $sentTo)) {
            return response()->json(['error' => 'This appointment does not belong to your store.'], 403);
        }

        $user   = User::find($sr->user_id);
        $doctor = $sr->preferred_doctor_id
            ? DoctorProfile::with('employee')->find($sr->preferred_doctor_id)
            : null;
        $slot   = $sr->preferred_slot_id
            ? DoctorSlot::find($sr->preferred_slot_id)
            : null;

        $isOther     = $sr->patient_for === 'other' && $sr->patient_name;
        $patientName  = $isOther ? $sr->patient_name : ($user ? (trim(($user->f_name ?? '') . ' ' . ($user->l_name ?? '')) ?: $user->name) : 'Unknown');
        $patientPhone = $isOther ? $sr->patient_phone : $user?->phone;

        $hmisPatient = null;
        if ($sr->user_id && !$isOther) {
            $hmisPatient = \App\Models\Patient::firstOrCreate(
                ['store_id' => $store_id, 'user_id' => $sr->user_id],
                ['name' => $patientName, 'phone' => $patientPhone, 'store_id' => $store_id, 'user_id' => $sr->user_id]
            );
        } elseif ($patientPhone) {
            // Only reachable when the booking is for someone else — the family case, where one
            // number covers several patients. Matching the number alone picked whichever
            // relative was registered first, so the visit landed on the wrong person's record.
            // locatePatient() matches name before number, the same rule lead conversion uses,
            // so the card and the booking can never point at different people.
            $hmisPatient = \App\Services\LeadAppointmentService::locatePatient($store_id, $sr);
        }

        return response()->json([
            'patient_name'       => $patientName,
            'patient_phone'      => $patientPhone,
            'patient_id'         => $hmisPatient?->id,
            'doctor_name'        => $doctor ? 'Dr. ' . $doctor->employee?->f_name . ' ' . $doctor->employee?->l_name : null,
            'doctor_profile_id'  => $sr->preferred_doctor_id,
            'appointment_date'   => $sr->preferred_date,
            'slot_id'            => $sr->preferred_slot_id,
            'slot_label'         => $slot ? $this->fmtTime($slot->slot_start) . ' – ' . $this->fmtTime($slot->slot_end) : null,
            'appointment_time'   => $sr->preferred_time,
            'reason'             => $sr->requirements,
            'service_name'       => $sr->item?->name,
            'status'             => $sr->status,
        ]);
    }

    public function storeFromLead(Request $request)
    {
        if (!auth('vendor')->check() && !hasPermission('hmis_appointment', 'add')) abort(403);

        $request->validate(['service_request_id' => 'required|integer']);

        $store_id = Helpers::get_store_id();
        $sr       = ServiceRequest::findOrFail($request->service_request_id);

        $sentTo = array_map('intval', array_filter(explode(',', $sr->sent_to ?? '')));
        if (!in_array((int)$store_id, $sentTo)) {
            Toastr::error('This appointment does not belong to your store.');
            return back();
        }

        if (!$sr->preferred_doctor_id || !$sr->preferred_date) {
            Toastr::error('This booking has no preferred doctor or date. Please register as walk-in.');
            return back();
        }

        // Existing appointment wins — the lead may already have been provisioned at confirmation.
        $already = Appointment::where('store_id', $store_id)
            ->where('service_request_id', $sr->id)
            ->first();
        if ($already) {
            Toastr::info('This booking is already registered as an appointment.');
            return redirect()->route('vendor.appointment.show', $already->id);
        }

        if ($sr->preferred_slot_id) {
            $booked = Appointment::where('slot_id', $sr->preferred_slot_id)
                ->where('appointment_date', $sr->preferred_date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();
            $slot = DoctorSlot::find($sr->preferred_slot_id);
            if ($slot && $booked >= $slot->max_patients) {
                Toastr::error('The originally booked slot is now full. Please register as walk-in and pick another slot.');
                return back();
            }
        }

        $appointment = \App\Services\LeadAppointmentService::provision((int) $sr->id, (int) $store_id);

        if (!$appointment) {
            Toastr::error('Could not register this booking as an appointment. Please register as walk-in.');
            return back();
        }

        Toastr::success('Appointment registered successfully.');
        return redirect()->route('vendor.appointment.show', $appointment->id);
    }

    private function resolvePatientFromLead(ServiceRequest $sr, int $storeId): Patient
    {
        // Shared find-then-create, so a repeat booking reuses the existing patient instead of
        // fragmenting the history across duplicate rows.
        $patient = \App\Services\LeadAppointmentService::resolvePatient($sr, $storeId);
        if ($patient) {
            return $patient;
        }

        return Patient::create([
            'store_id'    => $storeId,
            'user_id'     => null,
            'patient_uid' => Patient::generateUid($storeId),
            'name'        => 'Patient',
            'phone'       => null,
            'email'       => null,
            'status'      => 1,
        ]);
    }

    private function fmtTime(string $t): string
    {
        [$h, $m] = explode(':', $t);
        $hr = (int)$h;
        return ($hr > 12 ? $hr - 12 : ($hr ?: 12)) . ':' . $m . ' ' . ($hr >= 12 ? 'PM' : 'AM');
    }

    private function generateToken(int $doctorProfileId, string $date, int $appointmentId): int
    {
        return AppointmentToken::issue($doctorProfileId, $date, $appointmentId);
    }
}
