<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\OpdClinicalTerm;
use App\Models\OpdVisit;
use App\Models\Patient;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dental intake — one screen that registers the patient and opens the visit together.
 *
 * A dental chair needs five facts to start work: who, how old, gender, where they live and what
 * hurts. The general Patients form asks for twenty-three, and the OPD form can only pick a patient
 * who already exists, so a walk-in took two screens and a lot of empty boxes. Everything the long
 * form used to ask is still reachable — as named chips on the custom-info repeater — but nothing
 * beyond the five is required, and only what the desk actually clicks gets stored.
 *
 * Custom info is kept in two places on purpose. The patient row holds the defaults (their phone,
 * their allergies — true next visit too) and the visit row holds what was entered for that visit.
 * The bill reads the visit over the patient, so a one-off value corrects the default without
 * overwriting it.
 */
class DentalIntakeController extends Controller
{
    /**
     * The old patient fields, offered as named chips rather than a wall of inputs.
     *
     * Same interaction as the advanced bill's Custom Headers: clicking a chip drops a row with
     * that label fixed, "+ Other" drops a row where the label is typed too. Both end up in the
     * same label → value map, so nothing here is special-cased downstream.
     */
    public const PRESET_LABELS = [
        // No "Phone" chip — it is a required field on the form now, and offering it here as well
        // would let the same fact be recorded twice with two different answers.
        'Email',
        'Blood Group',
        'City',
        'Pincode',
        'Emergency Contact',
        'Emergency Phone',
        'Allergies',
        'Chronic Conditions',
        'Medications',
        'Past Surgeries',
        'Family History',
        'Medical Notes',
    ];

    /**
     * Columns this screen needs, added in place — no migration files (see CLAUDE.md).
     *
     * `age` rather than reusing `dob`: a dental desk is told "34", and turning that into a birth
     * date invents a birthday that is wrong for all but one day of the year. Patients keeps dob
     * for the hospitals that do collect it; the two are independent.
     */
    public static function ensureSchema(): void
    {
        if (Schema::hasTable('patients')) {
            if (!Schema::hasColumn('patients', 'age')) {
                DB::statement("ALTER TABLE `patients` ADD COLUMN `age` SMALLINT UNSIGNED NULL AFTER `dob`");
            }
            if (!Schema::hasColumn('patients', 'custom_info')) {
                DB::statement("ALTER TABLE `patients` ADD COLUMN `custom_info` TEXT NULL");
            }
        }

        if (Schema::hasTable('opd_visits') && !Schema::hasColumn('opd_visits', 'custom_info')) {
            DB::statement("ALTER TABLE `opd_visits` ADD COLUMN `custom_info` TEXT NULL");
        }
    }

    /** Dental-only screen. Any other category has the full Patients + OPD pair and does not need it. */
    public static function isDental(int $storeId): bool
    {
        return OpdClinicalTerm::categoryFor($storeId) === 'dental';
    }

    /**
     * Label → value rows off a request, in submission order.
     *
     * Blank labels and blank values are dropped rather than stored: an empty "+ Other" row the
     * user opened and thought better of must not reach the bill as a headerless column. A repeated
     * label keeps its last value, which is what someone who added the same chip twice meant.
     */
    public static function rowsFrom(Request $request): array
    {
        $labels = (array) $request->input('header_label', []);
        $values = (array) $request->input('header_field', []);
        $rows   = [];

        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            $value = trim((string) ($values[$i] ?? ''));

            if ($label === '' || $value === '') {
                continue;
            }

            $rows[$label] = $value;
        }

        return $rows;
    }

    /** Stored JSON back as a label → value array, tolerant of a null or malformed column. */
    public static function decode($json): array
    {
        if (is_array($json)) {
            return $json;
        }

        $rows = json_decode((string) $json, true);

        return is_array($rows) ? $rows : [];
    }

    /**
     * What a bill for this visit should print: the patient's standing details, with anything
     * entered on the visit itself taking precedence.
     */
    public static function mergedFor(?OpdVisit $visit): array
    {
        if (!$visit) {
            return [];
        }

        return array_merge(
            self::decode($visit->patient->custom_info ?? null),
            self::decode($visit->custom_info ?? null)
        );
    }

    public function create(Request $request)
    {
        self::ensureSchema();
        $storeId = Helpers::get_store_id();

        if (!self::isDental($storeId)) {
            Toastr::error('Dental intake is available once Hospital Settings category is set to Dental.');
            return redirect()->route('vendor.opd.index');
        }

        $presetLabels     = self::PRESET_LABELS;
        $complaintOptions = OpdClinicalTerm::listFor($storeId, OpdClinicalTerm::TYPE_COMPLAINT);
        $complaintGroups  = \App\Models\OpdComplaintGroup::listFor($storeId);

        return view('hmis::vendor.dental.intake', compact('presetLabels', 'complaintOptions', 'complaintGroups'));
    }

    public function store(Request $request)
    {
        self::ensureSchema();
        $storeId = Helpers::get_store_id();

        if (!self::isDental($storeId)) {
            Toastr::error('Dental intake is available once Hospital Settings category is set to Dental.');
            return redirect()->route('vendor.opd.index');
        }

        // Separators are stripped before validating, so "+91 98765 43210" and "9876543210" are the
        // same number to the rule below — and the stored value is the tidy one either way. The
        // screen applies the identical regex as the box is typed in.
        $request->merge([
            'phone' => preg_replace('/[\s\-()]/', '', (string) $request->input('phone')),
        ]);

        // The six the chair actually needs. Everything else on this screen is opt-in.
        $request->validate([
            'name'    => 'required|string|max:150',
            // Optional +91 / 0 trunk prefix, then a 10-digit mobile — Indian numbers start 6-9.
            'phone'   => ['required', 'string', 'max:20', 'regex:/^(?:\+?91|0)?[6-9]\d{9}$/'],
            'age'     => 'required|integer|min:0|max:150',
            'gender'  => 'required|in:male,female,other',
            'address'   => 'required|string|max:500',
            'problem'   => 'required|array|min:1',
            'problem.*' => 'string|max:150',
        ], [
            'problem.required' => 'Pick or type at least one problem.',
            'phone.regex' => 'Enter a valid 10-digit mobile number.',
        ]);

        $rows   = self::rowsFrom($request);
        $userId = auth('vendor_employee')->id() ?? auth('vendor')->id();

        // Link-table DDL before the transaction — DDL inside one implicitly commits it and would
        // split the registration in half (same reason PatientController::save does this).
        \App\Services\PatientCustomerLink::ensureSchema();

        DB::beginTransaction();
        try {
            // An existing patient is re-used rather than duplicated when the desk picked one from
            // the search box; otherwise this is a new record.
            $patient = $request->filled('patient_id')
                ? Patient::where('store_id', $storeId)->find($request->patient_id)
                : null;

            if (!$patient) {
                $patient              = new Patient();
                $patient->store_id    = $storeId;
                $patient->patient_uid = Patient::generateUid($storeId);
                $patient->created_by  = $userId;
            }

            $patient->name    = $request->name;
            $patient->phone   = $request->phone;
            $patient->age     = $request->age;
            $patient->gender  = $request->gender;
            $patient->address = $request->address;

            // Patient-level defaults grow with each visit: a phone captured today stays the
            // default tomorrow, and a corrected one replaces it. Nothing is dropped by omission,
            // so leaving a chip off this visit does not erase what the record already held.
            $patient->custom_info = json_encode(array_merge(self::decode($patient->custom_info), $rows));
            $patient->save();

            $visitDate = now()->toDateString();
            $nextToken = (OpdVisit::where('store_id', $storeId)
                ->whereDate('visit_date', $visitDate)
                ->max('token_number') ?? 0) + 1;

            $visit = OpdVisit::create([
                'store_id'          => $storeId,
                'patient_id'        => $patient->id,
                'doctor_profile_id' => $request->doctor_profile_id ?: null,
                'visit_date'        => $visitDate,
                'visit_time'        => now()->format('H:i'),
                'token_number'      => $nextToken,
                'visit_type'        => 'new',
                // Stored as a comma-separated term list, the same shape the OPD screens use, and
                // anything typed here joins the store's complaint list for next time.
                'chief_complaint'   => OpdClinicalTerm::absorb($storeId, OpdClinicalTerm::TYPE_COMPLAINT, $request->problem),
                // What was entered for THIS visit, kept apart from the patient defaults above so
                // a one-off value can override without overwriting.
                'custom_info'       => json_encode($rows),
                'recorded_by'       => $userId,
                'status'            => 'visited',
            ]);

            DB::commit();

            Toastr::success('Patient registered — token ' . $visit->token_number . '.');

            // Straight to the bill when asked, since that is what the intake feeds.
            return $request->input('action') === 'bill'
                ? redirect()->route('vendor.hospital-bill.create-opd', $visit->id)
                : redirect()->route('vendor.opd.show', $visit->id);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Dental intake failed: ' . $e->getMessage());
            Toastr::error('Could not register the patient. Please try again.');

            return back()->withInput();
        }
    }
}
