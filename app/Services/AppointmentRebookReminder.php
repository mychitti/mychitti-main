<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * "You last saw Dr Nair about six months ago — due for a check-up?"
 *
 * The appointment-side twin of RepeatPurchaseReminder: a patient who came once and never came
 * back is chased to book again, a set number of days after their last completed visit.
 *
 * How long is set per doctor, on the doctor's profile, because it is a clinical judgement and not
 * one number for a whole hospital — a dermatologist's recall is not a dentist's. A doctor left at
 * nothing is never chased, which is the right default for the specialities where "come back in six
 * months" is not a thing.
 *
 * Three things keep this from becoming nagging, and they matter more here than in retail because
 * the recipient is a patient:
 *
 *   1. Anyone with an appointment already on the books is skipped entirely. The hospital already
 *      sends a pre-visit reminder and a follow-up confirmation (see NextVisitService and
 *      SendAppointmentRemindersJob) — a third message telling them to book what they have booked
 *      is how a hospital's number gets reported.
 *   2. The fortnight cooldown is shared with the retail sweep, through the same table and the same
 *      phone key. A hospital that also sells from a pharmacy counter has one person in both
 *      systems — PatientCustomerLink exists precisely because a patient and a client are the same
 *      person — and they hear from all of this once a fortnight, not twice.
 *   3. A visit is chased once. Coming back in re-arms it; not coming back does not earn a second
 *      message.
 */
class AppointmentRebookReminder
{
    /** Statuses that mean the patient still has something on the books. */
    const OPEN_STATUSES = ['scheduled', 'checked_in', 'consulting'];

    /** Stop chasing a visit this long after it came due — it is not a recall any more. */
    const STALE_DAYS = 90;

    /** Approved template this needs on the vendor's WABA. */
    const TEMPLATE = 'rebook_reminder';

    /** Patients processed per store per sweep. */
    const BATCH = 200;

    public static function ensureColumn(): void
    {
        if (Schema::hasTable('doctor_profiles') && !Schema::hasColumn('doctor_profiles', 'rebook_days')) {
            DB::statement("ALTER TABLE `doctor_profiles` ADD COLUMN `rebook_days` INT NULL");
        }
    }

    /** Stores with at least one doctor set up to recall patients. */
    public static function configuredStoreIds(): array
    {
        self::ensureColumn();

        if (!Schema::hasTable('doctor_profiles')) {
            return [];
        }

        return DB::table('doctor_profiles')->where('rebook_days', '>', 0)
            ->distinct()->pluck('store_id')->filter()->values()->all();
    }

    /**
     * Patients this store is due to invite back, as rows of
     * [patient_id, doctor_profile_id, ref_key, doctor_name, last_visit, due_at].
     */
    public static function dueFor(int $storeId): array
    {
        self::ensureColumn();

        if (!Schema::hasTable('appointments') || !Schema::hasTable('doctor_profiles')) {
            return [];
        }

        $cycles = DB::table('doctor_profiles')->where('store_id', $storeId)
            ->where('rebook_days', '>', 0)->pluck('rebook_days', 'id')->all();

        if (empty($cycles)) {
            return [];
        }

        // Anyone already booked in is out, whichever doctor they are booked with — the point is
        // that this person is coming back, not that they are coming back to the same clinician.
        $alreadyBooked = array_flip(
            DB::table('appointments')->where('store_id', $storeId)
                ->whereIn('status', self::OPEN_STATUSES)
                ->whereDate('appointment_date', '>=', today())
                ->distinct()->pluck('patient_id')->all()
        );

        $lastVisits = DB::table('appointments')
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->whereIn('doctor_profile_id', array_keys($cycles))
            ->whereNotNull('patient_id')
            ->where('appointment_date', '>=', now()->subDays(400))
            ->groupBy('patient_id', 'doctor_profile_id')
            ->selectRaw('patient_id, doctor_profile_id, MAX(appointment_date) as last_visit')
            ->get();

        $due = [];
        foreach ($lastVisits as $row) {
            if (isset($alreadyBooked[$row->patient_id])) {
                continue;
            }

            $days  = (int) ($cycles[$row->doctor_profile_id] ?? 0);
            $last  = Carbon::parse($row->last_visit);
            $dueAt = $last->copy()->addDays($days);

            if ($dueAt->isFuture() || $dueAt->lt(now()->subDays(self::STALE_DAYS))) {
                continue;
            }

            $due[] = (object) [
                'patient_id'        => (int) $row->patient_id,
                'doctor_profile_id' => (int) $row->doctor_profile_id,
                'ref_key'           => 'doctor:' . (int) $row->doctor_profile_id,
                'last_visit'        => $last,
                'due_at'            => $dueAt,
            ];
        }

        return self::withoutAlreadyReminded($storeId, $due);
    }

    /** Drop any visit already chased — coming back since is what re-arms it. */
    protected static function withoutAlreadyReminded(int $storeId, array $due): array
    {
        if (empty($due)) {
            return [];
        }

        RepeatPurchaseReminder::ensureTables();

        $chased = DB::table('wa_repeat_reminders')
            ->where('store_id', $storeId)
            ->where('customer_type', 'patient')
            ->whereIn('customer_id', array_map(fn($d) => $d->patient_id, $due))
            ->get(['customer_id', 'ref_key', 'reminded_at'])
            ->keyBy(fn($r) => $r->customer_id . '|' . $r->ref_key);

        return array_values(array_filter($due, function ($d) use ($chased) {
            $seen = $chased[$d->patient_id . '|' . $d->ref_key] ?? null;
            if (!$seen || !$seen->reminded_at) {
                return true;
            }
            return $d->last_visit->gt(Carbon::parse($seen->reminded_at));
        }));
    }

    /** Send this store's due recalls. Returns how many patients were messaged. */
    public static function runStore(int $storeId): int
    {
        if (!NotificationPrefs::enabled($storeId, 'whatsapp_send', 'hmis_rebook')) {
            return 0;
        }

        $wa = WhatsAppService::make($storeId);
        if ($wa->source() !== 'vendor' || !WhatsAppBilling::isActive($storeId)) {
            return 0;
        }

        $due = self::dueFor($storeId);
        if (empty($due)) {
            return 0;
        }

        $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?: 'our clinic';
        $doctors   = self::doctorNames($storeId);
        $optedOut  = array_flip(array_map(
            fn($p) => RepeatPurchaseReminder::phoneKey((string) $p),
            WhatsAppService::optedOutPhones($storeId)
        ));
        $onCooldown = RepeatPurchaseReminder::recentlyMessagedPhones($storeId);

        // One message per patient even when two doctors are both due — the patient is being asked
        // to come back to this hospital, and two texts about it is the thing to avoid. The doctor
        // named is the one whose recall came due first.
        $byPhone = [];
        foreach ($due as $d) {
            $patient = DB::table('patients')->where('id', $d->patient_id)->first(['name', 'phone']);
            $phone   = trim((string) ($patient->phone ?? ''));
            if (!$patient || $phone === '') {
                continue;
            }

            $key = RepeatPurchaseReminder::phoneKey($phone);
            if ($key === '' || isset($optedOut[$key]) || isset($onCooldown[$key])) {
                continue;
            }

            if (!isset($byPhone[$key])) {
                $byPhone[$key] = [
                    'phone' => $phone,
                    'name'  => trim((string) $patient->name),
                    'lines' => [],
                ];
            }
            $byPhone[$key]['lines'][] = $d;
        }

        $sent = 0;
        foreach (array_slice($byPhone, 0, self::BATCH, true) as $phoneKey => $target) {
            try {
                if (!WhatsAppBilling::canAffordMessage($storeId, 'own')) {
                    Log::info('Rebook reminders stopped — wallet empty', ['store' => $storeId]);
                    break;
                }

                usort($target['lines'], fn($a, $b) => $a->due_at <=> $b->due_at);
                $lead = $target['lines'][0];

                $res = $wa->sendTemplate(
                    $target['phone'],
                    self::TEMPLATE,
                    'en_US',
                    [['type' => 'body', 'parameters' => array_map(
                        fn($v) => ['type' => 'text', 'text' => $v],
                        [
                            $target['name'] ?: 'there',
                            $doctors[$lead->doctor_profile_id] ?? 'your doctor',
                            $storeName,
                        ]
                    )]],
                    'rebook reminder'
                );

                // Recorded whatever Meta said, for the same reason as the retail sweep: a failed
                // send retried every morning is one broken template becoming a daily charge.
                self::markReminded($storeId, (string) $phoneKey, $target['lines']);

                if ($res['success']) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                Log::warning('Rebook reminder failed: ' . $e->getMessage());
            }
        }

        return $sent;
    }

    /** doctor_profile_id => "Dr. Meera Nair". */
    protected static function doctorNames(int $storeId): array
    {
        return DB::table('doctor_profiles as dp')
            ->leftJoin('vendor_employees as ve', 've.id', '=', 'dp.emp_id')
            ->where('dp.store_id', $storeId)
            ->selectRaw("dp.id, TRIM(CONCAT(COALESCE(ve.f_name,''), ' ', COALESCE(ve.l_name,''))) as name")
            ->get()
            ->mapWithKeys(fn($d) => [
                (int) $d->id => trim($d->name) !== '' ? 'Dr. ' . trim($d->name) : 'your doctor',
            ])
            ->all();
    }

    /**
     * Recorded in the same table the retail sweep uses, as customer_type 'patient'.
     *
     * Sharing the table is what makes the fortnight cooldown span both features — see the note on
     * RepeatPurchaseReminder::recentlyMessagedPhones().
     */
    protected static function markReminded(int $storeId, string $phoneKey, array $lines): void
    {
        foreach ($lines as $line) {
            DB::table('wa_repeat_reminders')->updateOrInsert(
                [
                    'store_id'      => $storeId,
                    'customer_type' => 'patient',
                    'customer_id'   => $line->patient_id,
                    'ref_key'       => $line->ref_key,
                ],
                [
                    'phone_key'      => $phoneKey,
                    'label'          => 'Follow-up visit',
                    'last_bought_at' => $line->last_visit,
                    'reminded_at'    => now(),
                    'updated_at'     => now(),
                    'created_at'     => now(),
                ]
            );
        }
    }
}
