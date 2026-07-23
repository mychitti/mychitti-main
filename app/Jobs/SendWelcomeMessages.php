<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * WhatsApp welcome messages for a batch of imported store customers.
 *
 * Dispatched from the import flows when the vendor opts in, so a large sheet never blocks
 * the upload request on N Graph API calls. The dispatcher chunks recipients across jobs;
 * sendWelcomeMessage() itself guards vendor connection, phone validity and the
 * one-welcome-per-number dedupe, and never throws.
 */
class SendWelcomeMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: a mid-batch failure would re-run the whole chunk, and while the
    // dedupe makes most re-sends harmless, WhatsApp sends aren't idempotent by contract.
    public int $tries = 1;
    public int $timeout = 600;

    /** @param array<int, array{name: ?string, phone: string}> $recipients */
    public function __construct(
        public int $storeId,
        public array $recipients
    ) {
    }

    public function handle(): void
    {
        foreach ($this->recipients as $recipient) {
            WhatsAppService::sendWelcomeMessage(
                $this->storeId,
                $recipient['name'] ?? null,
                $recipient['phone'] ?? null
            );
        }
    }
}
