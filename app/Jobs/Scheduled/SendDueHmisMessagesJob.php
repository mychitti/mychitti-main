<?php

namespace App\Jobs\Scheduled;

use App\Services\HmisWhatsAppShare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Sends the hospital messages a vendor asked to be delayed — feedback requests a few hours after
 * the visit, follow-up reminders a few days before the next one.
 *
 * The queue is the claim table itself: a row with a future due_at is a scheduled message, and this
 * sweep sends whatever has come due. Each message is rebuilt from the record at send time, so a
 * cancelled appointment or a deleted prescription simply doesn't go out.
 *
 * Runs every fifteen minutes — fine for something measured in hours and days.
 */
class SendDueHmisMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: a re-run would message the patient a second time.
    public int $tries = 1;
    public int $timeout = 600;

    public function handle(): void
    {
        if (!Schema::hasTable('wa_hmis_auto_sends')) {
            return;
        }

        $sent = 0;

        foreach (HmisWhatsAppShare::dueQueue() as $row) {
            try {
                $result = HmisWhatsAppShare::sendQueued(
                    (string) $row->kind,
                    (string) ($row->source ?: ''),
                    (int) $row->store_id,
                    (int) $row->record_id
                );

                HmisWhatsAppShare::settleQueued((int) $row->id, $result);

                if (!empty($result['success'])) {
                    $sent++;
                } else {
                    Log::info('HMIS queued message not sent', [
                        'kind'   => $row->kind,
                        'source' => $row->source,
                        'store'  => $row->store_id,
                        'record' => $row->record_id,
                        'reason' => $result['message'] ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                // Settle it as failed rather than leaving it to be retried forever on the next
                // sweep — the row keeps the reason for anyone looking into it.
                HmisWhatsAppShare::settleQueued((int) $row->id, [
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);
                Log::error('HMIS queued message error: ' . $e->getMessage());
            }
        }

        if ($sent) {
            Log::info('Sent ' . $sent . ' scheduled hospital WhatsApp message(s).');
        }
    }
}
