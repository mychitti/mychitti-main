<?php

namespace App\Jobs\Scheduled;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp reminders for HMIS appointments. Each vendor picks how many hours before the
 * appointment the reminder goes out (stores.wa_appt_reminder = hours; NULL = off).
 *
 * Runs hourly: the first run where an appointment is within the vendor's chosen window
 * sends the reminder, so it lands between H and H-1 hours before the slot. Sent from the
 * VENDOR's own connected number using their approved `appointment_reminder` template
 * ({{1}} patient, {{2}} hospital, {{3}} date, {{4}} time). One send per appointment,
 * deduped on the whatsapp_messages context ("appt reminder:{id}").
 */
class SendAppointmentRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: WhatsApp sends aren't idempotent.
    public int $tries = 1;
    public int $timeout = 600;

    public function handle(): void
    {
        if (!Schema::hasTable('appointments') || !Schema::hasTable('patients')) {
            return;
        }
        WhatsAppService::ensureStoreColumns();
        WhatsAppService::ensureMessagesTable();

        $stores = DB::table('stores')
            ->where('wa_enabled', 1)
            ->whereNotNull('wa_phone_number_id')
            ->get(['id', 'name', 'wa_appt_reminder']);

        foreach ($stores as $store) {
            try {
                // The number stays linked when a subscription lapses, so the paid-for state has
                // to be checked here — not just at connect time.
                if (!\App\Services\WhatsAppBilling::isActive((int) $store->id)) {
                    continue;
                }

                // NULL = never chose → the 2-hour default; an explicit '0' = turned off.
                $hours = ($store->wa_appt_reminder === null || $store->wa_appt_reminder === '')
                    ? WhatsAppService::DEFAULT_APPT_REMINDER_HOURS
                    : (int) $store->wa_appt_reminder;
                if ($hours < 1) {
                    continue;
                }
                // "Appointment reminders" is one of the things the vendor chooses to send
                // customers (WhatsApp → Chatbot). Off = no reminder, whatever the hours say.
                if (!\App\Services\WhatsAppAgent::shareEnabled((int) $store->id, 'reminder')) {
                    continue;
                }

                // Date range first (cheap, indexed), exact time filter in PHP — a large window
                // (e.g. 48h) legitimately spans multiple days.
                $upcoming = DB::table('appointments')
                    ->where('store_id', $store->id)
                    ->where('status', 'scheduled')
                    ->whereBetween('appointment_date', [
                        now()->toDateString(),
                        now()->addMinutes($hours * 60)->toDateString(),
                    ])
                    ->get()
                    ->filter(function ($a) use ($hours) {
                        try {
                            $at = Carbon::parse(
                                Carbon::parse($a->appointment_date)->toDateString() . ' ' . ($a->appointment_time ?: '00:00')
                            );
                        } catch (\Throwable $e) {
                            return false;
                        }
                        return $at->isFuture() && now()->diffInMinutes($at, false) <= $hours * 60;
                    });

                $this->remind($store, $upcoming);
            } catch (\Throwable $e) {
                Log::warning('Appointment reminders failed for store ' . $store->id . ': ' . $e->getMessage());
            }
        }
    }

    protected function remind(object $store, $appointments): void
    {
        if ($appointments->isEmpty()) {
            return;
        }

        // Reminders only go out on the vendor's own number — never the platform number — and on
        // whichever of their numbers they put in charge of reminders, else their default.
        $wa = WhatsAppService::make((int) $store->id, 'appt_reminder');
        if ($wa->source() !== 'vendor') {
            return;
        }

        // Whichever template this store uses for reminders — the suggested one, or their own if
        // they replaced it. Null means neither exists, so there is nothing to send today.
        $tpl = WhatsAppService::templateFor((int) $store->id, 'appt_reminder');
        if (!$tpl) {
            WhatsAppService::noteMissingTemplate((int) $store->id, 'appt_reminder');
            return;
        }

        // Appointments already reminded, in one query.
        $contexts = $appointments->map(fn($a) => "appt reminder:{$a->id}")->all();
        $sent = DB::table('whatsapp_messages')
            ->where('store_id', $store->id)
            ->whereIn('context', $contexts)
            ->where('status', '!=', 'failed')
            ->pluck('context')
            ->flip();

        $patients = DB::table('patients')
            ->whereIn('id', $appointments->pluck('patient_id')->filter()->unique())
            ->get(['id', 'name', 'phone'])
            ->keyBy('id');

        foreach ($appointments as $appt) {
            $context = "appt reminder:{$appt->id}";
            if (isset($sent[$context])) {
                continue;
            }

            $patient = $patients->get($appt->patient_id);
            if (!$patient || strlen(preg_replace('/[^0-9]/', '', (string) $patient->phone) ?? '') < 10) {
                continue;
            }

            try {
                $date = Carbon::parse($appt->appointment_date)->format('d M Y');
                $time = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('h:i A') : 'your booked slot';
            } catch (\Throwable $e) {
                continue;
            }

            // Body vars: {{1}} patient, {{2}} hospital, {{3}} date, {{4}} time.
            $components = [[
                'type' => 'body',
                'parameters' => array_map(
                    fn($v) => ['type' => 'text', 'text' => $v],
                    [$patient->name ?: 'there', $store->name ?: 'our clinic', $date, $time]
                ),
            ]];

            $wa->sendTemplate($patient->phone, $tpl['name'], $tpl['language'], $components, $context);
        }
    }
}
