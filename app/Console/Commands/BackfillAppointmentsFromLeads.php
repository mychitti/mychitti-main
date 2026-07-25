<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\LeadAppointmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `appointments` rows that confirmed hospital leads never got, so past bookings
 * show up in the patient's Appointments tab.
 *
 * PAST-DATED ONLY by default. SendAppointmentRemindersJob requires the appointment datetime to
 * be in the future, so backfilling history sends zero WhatsApp messages. Passing --include-future
 * lifts that guard and WILL start reminding real customers on the next hourly run.
 */
class BackfillAppointmentsFromLeads extends Command
{
    protected $signature = 'appointments:backfill-from-leads
        {--dry-run : Report what would be created without writing anything}
        {--store= : Limit to a single store id}
        {--include-future : Also provision future-dated leads (TRIGGERS WhatsApp reminders)}';

    protected $description = 'Backfill appointments for already-confirmed hospital leads (past-dated by default)';

    public function handle(): int
    {
        if (!Schema::hasColumn('appointments', 'service_request_id')) {
            $this->error('appointments.service_request_id is missing. Run the ALTER TABLE first:');
            $this->line('  ALTER TABLE appointments');
            $this->line('    ADD COLUMN service_request_id BIGINT UNSIGNED NULL AFTER store_id,');
            $this->line('    ADD INDEX appointments_service_request_id_idx (service_request_id);');
            return self::FAILURE;
        }

        $dryRun        = (bool) $this->option('dry-run');
        $includeFuture = (bool) $this->option('include-future');
        $today         = now()->toDateString();

        $query = DB::table('accepted_service_requests as asr')
            ->join('service_requests as sr', 'sr.id', '=', 'asr.service_request_id')
            ->where('asr.current_status', 'Confirmed')
            ->whereNotNull('sr.preferred_doctor_id')
            ->whereNotNull('sr.preferred_date')
            ->select('sr.id as service_request_id', 'asr.vendor_id as store_id', 'sr.preferred_date');

        if (!$includeFuture) {
            $query->where('sr.preferred_date', '<', $today);
        }

        if ($this->option('store')) {
            $query->where('asr.vendor_id', (int) $this->option('store'));
        }

        $leads = $query->orderBy('sr.id')->get();

        if ($leads->isEmpty()) {
            $this->info('No confirmed leads need backfilling.');
            return self::SUCCESS;
        }

        if ($includeFuture && !$dryRun) {
            $this->warn('--include-future is set: future-dated appointments will start sending WhatsApp reminders.');
            if (!$this->confirm('Continue?', false)) {
                return self::SUCCESS;
            }
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Processing {$leads->count()} confirmed lead(s)…");

        $created = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($leads as $lead) {
            $exists = Appointment::where('store_id', $lead->store_id)
                ->where('service_request_id', $lead->service_request_id)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $status = $this->inferStatus((int) $lead->service_request_id, (string) $lead->preferred_date, $today);

            if ($dryRun) {
                $this->line("  would create: lead #{$lead->service_request_id} store {$lead->store_id} on {$lead->preferred_date} as '{$status}'");
                $created++;
                continue;
            }

            $appointment = LeadAppointmentService::provision(
                (int) $lead->service_request_id,
                (int) $lead->store_id,
                $status
            );

            if (!$appointment) {
                $this->warn("  failed: lead #{$lead->service_request_id} store {$lead->store_id} (see logs)");
                $failed++;
                continue;
            }

            // Adopt the OPD visit that was recorded without an appointment to point at.
            DB::table('opd_visits')
                ->where('service_request_id', $lead->service_request_id)
                ->whereNull('appointment_id')
                ->update(['appointment_id' => $appointment->id]);

            $created++;
        }

        $this->newLine();
        $this->info(($dryRun ? 'Would create' : 'Created') . ": {$created}");
        $this->info("Already present: {$skipped}");
        if ($failed) {
            $this->warn("Failed: {$failed}");
        }

        return self::SUCCESS;
    }

    /**
     * A past appointment recorded as 'scheduled' would misread as still pending, so infer the
     * real outcome: an OPD visit means the patient was seen; a Completed lead likewise.
     * Anything else that is already in the past never happened.
     */
    private function inferStatus(int $serviceRequestId, string $preferredDate, string $today): string
    {
        if ($preferredDate >= $today) {
            return 'scheduled';
        }

        $seen = DB::table('opd_visits')->where('service_request_id', $serviceRequestId)->exists();
        if ($seen) {
            return 'completed';
        }

        $leadStatus = DB::table('accepted_service_requests')
            ->where('service_request_id', $serviceRequestId)
            ->value('current_status');

        if ($leadStatus === 'Completed') {
            return 'completed';
        }

        $cancelled = DB::table('cancelled_service_requests')
            ->where('service_request_id', $serviceRequestId)
            ->exists();

        return $cancelled ? 'cancelled' : 'no_show';
    }
}
