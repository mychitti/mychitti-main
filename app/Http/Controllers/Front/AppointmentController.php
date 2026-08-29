<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentToken; 
use App\Models\DoctorProfile;
use App\Models\DoctorSlot;
use App\Models\Patient;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function show(Request $request, $city, $slug)
    {
        $store = Store::where('slug', $slug)->first();
        if (!$store) return redirect()->route('home');

        $doctors = DoctorProfile::where('store_id', $store->id)
            ->with('employee')
            ->get();

        if ($doctors->isEmpty()) {
            return redirect()->back()->with('error', 'No doctors available at this store.');
        }

        $selectedDoctorId = $request->doctor_id;

        return view('front-views.appointment.book', compact('store', 'doctors', 'selectedDoctorId'));
    }
    public function doctors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|integer|exists:stores,id',
            'item_id'  => 'nullable|integer|exists:items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        $query = DoctorProfile::where('store_id', $request->store_id)
            ->with(['employee:id,f_name,l_name,phone,image', 'services'])
            ->whereHas('slots', fn($s) => $s->where('is_active', 1));

        if ($request->filled('item_id')) {
            $query->whereHas('services', fn($s) => $s->where('item_id', $request->item_id));
        }

        $doctors = $query->get()->map(fn($d) => [
            'id'                => $d->id,
            'name'              => 'Dr. ' . trim(($d->employee?->f_name ?? '') . ' ' . ($d->employee?->l_name ?? '')),
            'image'             => $d->employee?->image,
            'specialization'    => $d->specialization,
            'qualification'     => $d->qualification,
            'department'        => $d->department,
            'consultation_fee'  => (float) $d->consultation_fee,
            'available_days'    => $d->available_days,
            'service_ids'       => $d->services->pluck('item_id'),
        ]);

        return response()->json(['doctors' => $doctors]);
    }
    public function slots(Request $request)
    {
        $request->validate([
            'doctor_profile_id' => 'required|integer',
            'date'              => 'required|date',
        ]);

        $doctorId  = $request->doctor_profile_id;
        $date      = $request->date;
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        $slots = DoctorSlot::where('doctor_profile_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', 1)
            ->get();

        $slots->each(function ($slot) use ($date) {
            $booked = Appointment::where('slot_id', $slot->id)
                ->where('appointment_date', $date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();
            $slot->booked    = $booked;
            $slot->available = max(0, $slot->max_patients - $booked);
        });

        // Find next available date when no slots or all slots are full
        $allFull = $slots->isNotEmpty() && $slots->every(fn($s) => $s->available <= 0);
        $nextAvailable = null;

        if ($slots->isEmpty() || $allFull) {
            $activeDays = DoctorSlot::where('doctor_profile_id', $doctorId)
                ->where('is_active', 1)
                ->pluck('day_of_week')
                ->unique()
                ->toArray();

            $check = Carbon::parse($date)->addDay();
            for ($i = 0; $i < 60; $i++, $check->addDay()) {
                if (!in_array($check->dayOfWeek, $activeDays)) continue;

                $daySlots = DoctorSlot::where('doctor_profile_id', $doctorId)
                    ->where('day_of_week', $check->dayOfWeek)
                    ->where('is_active', 1)
                    ->get();

                foreach ($daySlots as $ds) {
                    $booked = Appointment::where('slot_id', $ds->id)
                        ->where('appointment_date', $check->toDateString())
                        ->whereNotIn('status', ['cancelled', 'no_show'])
                        ->count();
                    if ($booked < $ds->max_patients) {
                        $nextAvailable = $check->toDateString();
                        break 2;
                    }
                }
            }
        }

        return response()->json([
            'slots'          => $slots->values(),
            'next_available' => $nextAvailable,
            'all_full'       => $allFull,
        ]);
    }

    public function book(Request $request, $city, $slug)
    {
        if (!auth('web')->check()) {
            return redirect()->route('user-login')
                ->with('redirectIntent', url()->current());
        }

        $request->validate([
            'doctor_profile_id' => 'required|integer|exists:doctor_profiles,id',
            'appointment_date'  => 'required|date|after_or_equal:today',
            'appointment_time'  => 'required',
            'slot_id'           => 'nullable|integer|exists:doctor_slots,id',
            'reason'            => 'nullable|string|max:500',
        ]);

        $store = Store::where('slug', $slug)->firstOrFail();
        $user  = auth('web')->user();

        // Verify doctor belongs to store
        $doctor = DoctorProfile::where('id', $request->doctor_profile_id)
            ->where('store_id', $store->id)
            ->firstOrFail();

        // Validate slot capacity
        if ($request->slot_id) {
            $slot = DoctorSlot::where('id', $request->slot_id)
                ->where('doctor_profile_id', $doctor->id)
                ->where('is_active', 1)
                ->firstOrFail();

            $dayOfWeek = Carbon::parse($request->appointment_date)->dayOfWeek;
            if ($slot->day_of_week != $dayOfWeek) {
                return back()->withErrors(['slot_id' => 'Slot not available on this day.'])->withInput();
            }

            $booked = Appointment::where('slot_id', $slot->id)
                ->where('appointment_date', $request->appointment_date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->count();

            if ($booked >= $slot->max_patients) {
                return back()->withErrors(['slot_id' => 'This slot is fully booked.'])->withInput();
            }
        }

        // Resolve patient — find or auto-create for this store
        $patient = Patient::where('user_id', $user->id)
            ->where('store_id', $store->id)
            ->first();

        if (!$patient) {
            $patient = $this->createPatientFromUser($user, $store->id);
        }

        DB::beginTransaction();
        try {
            $appointment = Appointment::create([
                'store_id'          => $store->id,
                'patient_id'        => $patient->id,
                'doctor_profile_id' => $doctor->id,
                'slot_id'           => $request->slot_id,
                'appointment_date'  => $request->appointment_date,
                'appointment_time'  => $request->appointment_time,
                'booking_type'      => 'online',
                'status'            => 'scheduled',
                'reason'            => $request->reason,
                'booked_by'         => null,
            ]);

            $tokenNumber = $this->generateToken($doctor->id, $request->appointment_date, $appointment->id);

            DB::commit();

            // (No lead_signal here — a completed appointment already has its own record in the
            // Appointments/OPD flow. The Lead Inbox tracks only soft, pre-conversion signals.)
  
            return redirect()->route('front.appointment.confirm', [
                'city'  => $city,
                'slug'  => $slug,
                'id'    => $appointment->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Booking failed. Please try again.'])->withInput();
        }
    }

    public function confirm($city, $slug, $id)
    {
        if (!auth('web')->check()) {
            return redirect()->route('user-login');
        }

        $user  = auth('web')->user();
        $store = Store::where('slug', $slug)->firstOrFail();

        $appointment = Appointment::where('id', $id)
            ->where('store_id', $store->id)
            ->whereHas('patient', fn($q) => $q->where('user_id', $user->id))
            ->with(['doctorProfile.employee', 'slot', 'token', 'patient'])
            ->firstOrFail();

        return view('front-views.appointment.confirm', compact('store', 'appointment'));
    }

    private function createPatientFromUser($user, int $storeId): Patient
    {
        $last = Patient::where('store_id', $storeId)->latest('id')->first();
        $next = $last ? ($last->id + 1) : 1;
        $uid  = 'P-' . str_pad($next, 5, '0', STR_PAD_LEFT);

        return Patient::create([
            'store_id'    => $storeId,
            'user_id'     => $user->id,
            'patient_uid' => $uid,
            'name'        => trim(($user->f_name ?? '') . ' ' . ($user->l_name ?? '')) ?: ($user->name ?? 'Patient'),
            'phone'       => $user->phone,
            'email'       => $user->email,
            'status'      => 1,
        ]);
    }

    private function generateToken(int $doctorProfileId, string $date, int $appointmentId): int
    {
        $last        = AppointmentToken::where('doctor_profile_id', $doctorProfileId)
            ->where('token_date', $date)
            ->max('token_number');
        $tokenNumber = ($last ?? 0) + 1;

        AppointmentToken::create([
            'appointment_id'    => $appointmentId,
            'token_number'      => $tokenNumber,
            'token_date'        => $date,
            'doctor_profile_id' => $doctorProfileId,
        ]);

        return $tokenNumber;
    }

    public function userReschedule(Request $request)
    {
        if (!auth('web')->check()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Please login to reschedule.'], 401);
            }
            return redirect()->route('user-login');
        }

        $request->validate([
            'service_request_id' => 'required',
            'opd_visit_id'       => 'nullable|integer',
            'appointment_date'   => 'required|date|after_or_equal:today',
            'appointment_time'   => 'nullable|string',
            'reason'             => 'nullable|string|max:500',
        ]);

        $user = auth('web')->user();

        // 1. Check if an explicit opd_visit_id was passed or service_request_id starts with "opd_"
        $opdVisitId = $request->opd_visit_id;
        if (!$opdVisitId && is_string($request->service_request_id) && str_starts_with($request->service_request_id, 'opd_')) {
            $opdVisitId = (int) str_replace('opd_', '', $request->service_request_id);
        }

        if ($opdVisitId) {
            $patientIds = Patient::where(function($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->phone) {
                    $cleanPhone = preg_replace('/[^0-9]/', '', $user->phone);
                    $shortPhone = strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone;
                    $q->orWhere('phone', $user->phone)
                      ->orWhere('phone', 'like', '%' . $shortPhone);
                }
            })->pluck('id')->toArray();

            $visit = \App\Models\OpdVisit::whereIn('patient_id', $patientIds)
                ->where('id', $opdVisitId)
                ->first();

            if ($visit) {
                if ($visit->is_cancelled) {
                    if ($request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Cannot reschedule a cancelled visit.'], 422);
                    }
                    \Brian2694\Toastr\Facades\Toastr::error('Cannot reschedule a cancelled visit.');
                    return back();
                }

                $newDate = \Carbon\Carbon::parse($request->appointment_date)->format('Y-m-d');
                $newTime = $request->appointment_time ?: $visit->visit_time;

                // Recalculate token if date changes
                $tokenNumber = $visit->token_number;
                if ($newDate !== $visit->visit_date?->format('Y-m-d')) {
                    $lastToken = \App\Models\OpdVisit::where('store_id', $visit->store_id)
                        ->whereDate('visit_date', $newDate)
                        ->max('token_number');
                    $tokenNumber = ($lastToken ?? 0) + 1;
                }

                $visit->visit_date   = $newDate;
                if ($newTime) $visit->visit_time = $newTime;
                $visit->token_number = $tokenNumber;
                if ($request->filled('reason')) {
                    $visit->notes = trim(($visit->notes ? $visit->notes . "\n" : '') . 'Patient Rescheduled: ' . $request->reason);
                }
                $visit->save();

                // If linked to an appointment, reschedule it as well
                if ($visit->appointment_id) {
                    $appt = Appointment::find($visit->appointment_id);
                    if ($appt && !in_array($appt->status, ['completed', 'cancelled'])) {
                        try {
                            \App\Services\AppointmentReschedule::apply(
                                $appt,
                                $newDate,
                                $newTime ?: '10:00',
                                $appt->slot_id,
                                'the patient',
                                null
                            );
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('Patient reschedule appointment sync failed: ' . $e->getMessage());
                        }
                    }
                }

                if ($request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => 'OPD Visit rescheduled successfully!']);
                }

                \Brian2694\Toastr\Facades\Toastr::success('OPD Visit rescheduled successfully!');
                return back();
            }
        }

        // 2. Fallback to ServiceRequest appointment lookup
        $srId = (int) $request->service_request_id;
        $sr = \App\Models\ServiceRequest::where('id', $srId)
            ->where('user_id', $user->id)
            ->first();

        if (!$sr) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
            }
            \Brian2694\Toastr\Facades\Toastr::error('Booking not found.');
            return back();
        }

        $appt = Appointment::where('service_request_id', $sr->id)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->first();

        if (!$appt) {
            $patientIds = Patient::where('user_id', $user->id)->pluck('id');
            $appt = Appointment::whereIn('patient_id', $patientIds)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->latest()
                ->first();
        }

        if ($appt) {
            try {
                $newTime = $request->appointment_time ?: ($appt->appointment_time ?: '10:00');
                $new = \App\Services\AppointmentReschedule::apply(
                    $appt,
                    $request->appointment_date,
                    $newTime,
                    $appt->slot_id,
                    'the patient',
                    null
                );

                // Update linked OPD visit if exists
                $opdVisit = \App\Models\OpdVisit::where('appointment_id', $appt->id)->first();
                if ($opdVisit && $opdVisit->is_editable) {
                    $opdVisit->visit_date = $request->appointment_date;
                    if ($request->appointment_time) $opdVisit->visit_time = $request->appointment_time;
                    $opdVisit->appointment_id = $new->id;
                    $opdVisit->save();
                }

                if ($request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => 'Appointment rescheduled successfully!']);
                }

                \Brian2694\Toastr\Facades\Toastr::success('Appointment rescheduled successfully!');
                return back();
            } catch (\Exception $e) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Reschedule failed: ' . $e->getMessage()], 422);
                }
                \Brian2694\Toastr\Facades\Toastr::error('Reschedule failed: ' . $e->getMessage());
                return back();
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'No active appointment found for this booking.'], 404);
        }

        \Brian2694\Toastr\Facades\Toastr::error('No active appointment found for this booking.');
        return back();
    }
}
