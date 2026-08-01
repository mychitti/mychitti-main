<?php

namespace App\Jobs\Scheduled;

use App\Services\WhatsAppCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Drives every running WhatsApp drip campaign: sends whatever step is due, in batches, from each
 * vendor's own connected number.
 *
 * Runs every five minutes. A step becomes due `delay_hours` after the previous one finished, so
 * the schedule follows the actual send, not the moment the campaign was created. Each pass sends
 * at most WhatsAppCampaign::RUN_BATCH per campaign — a 5,000-person list drains over several
 * passes instead of holding the queue for an hour.
 */
class RunWhatsAppCampaignsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: WhatsApp sends aren't idempotent, and a half-finished batch is picked up by
    // the next pass anyway — the unique key on (step_id, recipient_id) is what guarantees that.
    public int $tries = 1;
    public int $timeout = 900;

    public function handle(): void
    {
        if (!Schema::hasTable('wa_campaigns')) {
            return;
        }

        foreach (WhatsAppCampaign::dueCampaigns() as $campaign) {
            try {
                $report = WhatsAppCampaign::runCampaign($campaign);

                if ($report['sent'] || $report['failed']) {
                    Log::info('WA campaign pass', [
                        'campaign' => $campaign->id,
                        'store'    => $campaign->store_id,
                        'step'     => $report['step'],
                        'sent'     => $report['sent'],
                        'failed'   => $report['failed'],
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('WA campaign run failed', [
                    'campaign' => $campaign->id,
                    'error'    => $e->getMessage(),
                ]);
                WhatsAppCampaign::setStatus(
                    (int) $campaign->id,
                    WhatsAppCampaign::STATUS_PAUSED,
                    'Paused after an error: ' . mb_substr($e->getMessage(), 0, 180)
                );
            }
        }
    }
}
