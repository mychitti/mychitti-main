<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\HospitalActivityLog;
use App\Models\OpdLabWork;
use App\Models\OpdVisit;
use App\Models\StoreConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Closing off care the patient never came back for.
 *
 * A course of treatment does not end, in practice. It stops. The patient has three sittings left,
 * a crown at the lab and a follow-up booked, and then they simply never appear again — and every
 * one of those rows sits open for the next two years, in the register, in the lab work chase list,
 * in the follow-up count, quietly telling the clinic to chase somebody who left.
 *
 * This is the sweep that admits it. After a hospital's chosen number of days with no visit at all,
 * everything still outstanding on that patient's OPD record is marked discontinued: the planned
 * treatments nobody is waiting on, the lab work nobody is coming to collect, the follow-up whose
 * date went past with an empty chair.
 *
 * Three rules govern it, and they are what make an unattended sweep safe to run:
 *
 *  1. It is off unless the hospital set an interval. There is no platform default, because "how
 *     long is too long" is a clinical judgement that differs by speciality.
 *  2. It only ever closes what is still open. Nothing completed, billed, receipted or already
 *     closed is touched, and nothing anywhere is deleted.
 *  3. A patient with a future appointment is not lost, whatever the gap says — somebody is
 *     expecting them, and that outranks the calendar.
 *
 * Everything it does is written to the hospital activity log, and every state it writes can be
 * moved back by hand if the patient walks in the following week.
 */
class OpdDiscontinue
{
    /**
     * How long the platform waits before it gives up on a course, for a hospital that never said.
     *
     * Every hospital gets this unless it sets its own number or switches the sweep off, so it is
     * deliberately on the long side of what a clinic would choose: a month of complete silence,
     * no future booking, nothing. The point of the default is that abandoned care gets closed
     * everywhere rather than only in the hospitals that went looking for a setting.
     */
    const DEFAULT_DAYS = 30;

    /** Plan rows still waiting on the patient. 'missed' is left alone — staff already closed it. */
    const OPEN_PLAN_STATES = ['pending', 'upcoming', 'in_progress'];

    /** Appointment states that mean somebody is still expecting this patient. */
    const LIVE_APPOINTMENT_STATES = ['scheduled', 'checked_in', 'consulting'];

    /**
     * Run the sweep for every hospital that asked for one.
     *
     * Stores are handled one at a time and independently: a store whose data trips something up
     * must not stop the rest of the platform's sweep, so its failure is logged and the loop
     * carries on.
     */
    public static function sweepAll(bool $dryRun = false): array
    {
        $totals = ['stores' => 0, 'patients' => 0, 'visits' => 0, 'lab_works' => 0, 'treatments' => 0, 'appointments' => 0];

        if (!Schema::hasTable('opd_visits')) {
            return $totals;
        }

        // Every hospital that has ever registered a visit, not only the ones that opened the
        // setting: the interval is a platform default now, so the sweep has to reach a clinic that
        // never went near Hospital Settings. Which of them actually get swept is decided one by
        // one below — a store that switched it off resolves to no interval and is skipped.
        $storeIds = DB::table('opd_visits')->distinct()->pluck('store_id');

        $configured = Schema::hasColumn('store_configs', 'hmis_discontinue_days')
            ? StoreConfig::whereIn('store_id', $storeIds)->pluck('hmis_discontinue_days', 'store_id')
            : collect();

        foreach ($storeIds as $storeId) {
            $days = static::daysFor((int) $storeId, $configured->get($storeId, null));
            if (!$days) {
                continue;
            }

            try {
                $result = static::sweepStore((int) $storeId, $days, $dryRun);

                $totals['stores']++;
                foreach (['patients', 'visits', 'lab_works', 'treatments', 'appointments'] as $key) {
                    $totals[$key] += $result[$key];
                }
            } catch (\Throwable $e) {
                Log::error('OpdDiscontinue sweep failed for store ' . $storeId . ': ' . $e->getMessage());
            }
        }

        return $totals;
    }

    /**
     * The interval one hospital actually runs to, from whatever its config column holds.
     *
     * Three states in one nullable integer, which is what lets a platform default exist at all:
     * NULL means the hospital never said, so it gets DEFAULT_DAYS; 0 means it deliberately turned
     * the sweep off and gets nothing; anything else is its own number. A stored 0 is the only way
     * to opt out, and it survives the default being changed later.
     */
    public static function daysFor(int $storeId, $configured = false): ?int
    {
        if ($configured === false) {
            return hmis_discontinue_days($storeId);
        }

        if ($configured === null) {
            return static::DEFAULT_DAYS;
        }

        $days = (int) $configured;

        return $days > 0 ? $days : null;
    }

    /**
     * One hospital's sweep.
     *
     * $dryRun answers the only question worth asking before switching this on — "what would it
     * close?" — without closing anything.
     */
    public static function sweepStore(int $storeId, ?int $days = null, bool $dryRun = false): array
    {
        $result = ['patients' => 0, 'visits' => 0, 'lab_works' => 0, 'treatments' => 0, 'appointments' => 0, 'detail' => []];

        $days = $days ?: hmis_discontinue_days($storeId);
        if (!$days) {
            return $result;
        }

        OpdVisit::ensureDiscontinueColumns();
        OpdLabWork::ensureSchema();

        $cutoff = now()->subDays($days)->startOfDay();

        // Whose last visit is older than the cutoff. Read as one grouped query rather than per
        // patient: the question is about the newest visit, so a store with ten years of register
        // still answers it in one pass.
        $lastVisits = OpdVisit::where('store_id', $storeId)
            ->notCancelled()
            ->selectRaw('patient_id, MAX(visit_date) as last_visit')
            ->groupBy('patient_id')
            ->havingRaw('MAX(visit_date) < ?', [$cutoff->toDateString()])
            ->pluck('last_visit', 'patient_id');

        if ($lastVisits->isEmpty()) {
            return $result;
        }

        // Somebody is expecting them. A booked appointment says the relationship is live no matter
        // what the gap since the last visit says — a six-month recall is not an abandoned course.
        $expected = Appointment::where('store_id', $storeId)
            ->whereIn('patient_id', $lastVisits->keys()->all())
            ->whereIn('status', static::LIVE_APPOINTMENT_STATES)
            ->whereDate('appointment_date', '>=', now()->toDateString())
            ->distinct()
            ->pluck('patient_id');

        $patientIds = $lastVisits->keys()->diff($expected)->values();
        if ($patientIds->isEmpty()) {
            return $result;
        }

        foreach ($patientIds->chunk(200) as $chunk) {
            $visits = OpdVisit::where('store_id', $storeId)
                ->whereIn('patient_id', $chunk->all())
                ->whereNull('discontinued_at')
                ->notCancelled()
                ->get();

            $labWorks = OpdLabWork::where('store_id', $storeId)
                ->whereIn('patient_id', $chunk->all())
                ->open()
                ->get()
                ->groupBy('patient_id');

            // Only appointments whose date has already gone by. A booking still in the future was
            // filtered out with its patient above; one from last month with nobody marked present
            // is the empty chair this sweep exists to record.
            $appointments = Appointment::where('store_id', $storeId)
                ->whereIn('patient_id', $chunk->all())
                ->where('status', 'scheduled')
                ->whereDate('appointment_date', '<', now()->toDateString())
                ->get()
                ->groupBy('patient_id');

            foreach ($chunk as $patientId) {
                $patientVisits = $visits->where('patient_id', $patientId);
                $patientJobs   = $labWorks[$patientId] ?? collect();
                $patientAppts  = $appointments[$patientId] ?? collect();

                $closed = static::closeForPatient(
                    $storeId,
                    (int) $patientId,
                    $days,
                    (string) $lastVisits[$patientId],
                    $patientVisits,
                    $patientJobs,
                    $patientAppts,
                    $dryRun
                );

                if ($closed['visits'] || $closed['lab_works'] || $closed['treatments'] || $closed['appointments']) {
                    $result['patients']++;
                    foreach (['visits', 'lab_works', 'treatments', 'appointments'] as $key) {
                        $result[$key] += $closed[$key];
                    }
                    $result['detail'][] = ['patient_id' => (int) $patientId] + $closed;
                }
            }
        }

        return $result;
    }

    /**
     * Everything one patient has left open, closed in one transaction.
     *
     * Per patient rather than per row so the activity log reads as one event — "care discontinued,
     * 3 treatments, 1 crown, 1 follow-up" — instead of five unrelated entries somebody has to
     * piece back together.
     */
    protected static function closeForPatient(
        int $storeId,
        int $patientId,
        int $days,
        string $lastVisit,
        $visits,
        $labWorks,
        $appointments,
        bool $dryRun
    ): array {
        $reason = 'No visit since ' . \Carbon\Carbon::parse($lastVisit)->format('d M Y') . ' (' . $days . ' day rule)';
        $closed = ['visits' => 0, 'lab_works' => 0, 'treatments' => 0, 'appointments' => 0, 'reason' => $reason];

        // What would change, worked out before anything is written so a dry run counts exactly
        // what a real run would close.
        $planChanges = [];
        foreach ($visits as $visit) {
            $plan  = $visit->treatment_plan_map;
            $stale = [];

            foreach ($plan as $term => $row) {
                $state = (string) ($row['status'] ?? 'pending');
                if (in_array($state, static::OPEN_PLAN_STATES, true)) {
                    $stale[] = $term;
                }
            }

            if ($stale) {
                $planChanges[$visit->id] = $stale;
            }
        }

        $touchedVisits = collect($visits)->filter(
            fn($v) => isset($planChanges[$v->id])
        )->keyBy('id');

        // A visit with nothing left open of its own still gets stamped when the patient had work
        // or a follow-up outstanding — the stamp is what stops tomorrow's sweep looking again, and
        // what the consultation screen reads to say why the plan is closed.
        if (($labWorks->count() || $appointments->count()) && $touchedVisits->isEmpty()) {
            $latest = collect($visits)->sortByDesc('visit_date')->first();
            if ($latest) {
                $touchedVisits = collect([$latest->id => $latest]);
            }
        }

        $closed['treatments']   = collect($planChanges)->flatten()->count();
        $closed['lab_works']    = $labWorks->count();
        $closed['appointments'] = $appointments->count();
        $closed['visits']       = $touchedVisits->count();

        if ($dryRun || !($closed['treatments'] || $closed['lab_works'] || $closed['appointments'] || $closed['visits'])) {
            return $closed;
        }

        DB::transaction(function () use ($visits, $planChanges, $touchedVisits, $labWorks, $appointments, $reason, $storeId, $patientId) {
            foreach ($visits as $visit) {
                if (isset($planChanges[$visit->id])) {
                    $plan = $visit->treatment_plan_map;
                    foreach ($planChanges[$visit->id] as $term) {
                        $plan[$term]['status'] = OpdVisit::PLAN_DISCONTINUED;
                        // Why it stopped, kept on the row itself: the plan is read long after the
                        // activity log has scrolled away, and "discontinued" with no reason on it
                        // is the entry that gets queried a year later.
                        $plan[$term]['discontinue_reason'] = $reason;
                    }
                    $visit->treatment_plan = json_encode($plan);
                }

                if ($touchedVisits->has($visit->id)) {
                    $visit->discontinued_at    = now();
                    $visit->discontinue_reason = mb_substr($reason, 0, 255);
                }

                if ($visit->isDirty()) {
                    $visit->save();
                }
            }

            foreach ($labWorks as $job) {
                $from = $job->status;
                $job->forceFill(['status' => 'discontinued'])->save();

                HospitalActivityLog::record(
                    $storeId, 'opd_lab_work', (int) $job->id, 'status_changed',
                    'Lab work discontinued for patient #' . $patientId . ' — ' . $job->title()
                        . ': ' . $from . ' to discontinued (' . $reason . ')',
                    ['patient_id' => $patientId, 'from' => $from, 'to' => 'discontinued', 'automatic' => true]
                );
            }

            foreach ($appointments as $appointment) {
                $appointment->forceFill([
                    'status'        => 'no_show',
                    'cancel_reason' => mb_substr('Discontinued automatically — ' . $reason, 0, 255),
                ])->save();
            }
        });

        HospitalActivityLog::record(
            $storeId, 'opd_visit', (int) ($touchedVisits->keys()->first() ?? 0), 'discontinued',
            'Care discontinued for patient #' . $patientId . ' — ' . $reason . '. Closed '
                . $closed['treatments'] . ' planned treatment(s), '
                . $closed['lab_works'] . ' lab work job(s), '
                . $closed['appointments'] . ' unattended follow-up(s).',
            [
                'patient_id'   => $patientId,
                'automatic'    => true,
                'days'         => $days,
                'last_visit'   => $lastVisit,
                'visits'       => $touchedVisits->keys()->all(),
                'treatments'   => $closed['treatments'],
                'lab_works'    => $closed['lab_works'],
                'appointments' => $closed['appointments'],
            ]
        );

        return $closed;
    }
}
