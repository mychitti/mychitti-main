<?php

namespace App\Jobs;

use App\Services\WhatsAppCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Score a TYPED reply against whatever campaign the sender is in.
 *
 * Off the webhook's critical path on purpose. Reading free text can involve a call to the AI
 * service, and a webhook that waits on that risks timing out — Meta then retries and eventually
 * disables the endpoint, which would break delivery receipts for every vendor. Dispatched with
 * afterResponse() so Meta has its 200 first; runs on the sync queue with no worker.
 *
 * Tapped buttons never come here: the label is exact, so the webhook records those inline.
 */
class ClassifyCampaignReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No retry: a re-run would count the same reply twice against the step.
    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(
        public ?int $storeId,
        public string $from,
        public string $body,
        public ?string $contextWamid = null
    ) {
    }

    public function handle(): void
    {
        try {
            WhatsAppCampaign::recordReply($this->storeId, $this->from, $this->body, $this->contextWamid, false);
        } catch (\Throwable $e) {
            Log::warning('WA campaign reply classify job failed: ' . $e->getMessage());
        }
    }
}
