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
}
