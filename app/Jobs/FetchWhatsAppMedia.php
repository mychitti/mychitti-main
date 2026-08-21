<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fetch the photo, voice note or document a customer just sent, and hang it on its message row.
 *
 * The webhook carries an id, not a file. Resolving that id and downloading the bytes is two round
 * trips to Meta and can be megabytes, so it runs with afterResponse() — Meta has its 200 first,
 * because a webhook that waits gets retried and eventually disabled. Works on the sync queue with
 * no worker, like the other WhatsApp jobs.
 *
 * The inbox polls, so the bubble fills in on its own a moment after the message lands. If the
 * download fails the row simply keeps its "[image]" placeholder, which is what it showed before.
 */
class FetchWhatsAppMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** One attempt: Meta's media links are short-lived, so a retry ten minutes later fetches nothing. */
    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(
        public ?int $storeId,
        public int $messageId,
        public string $mediaId,
        public ?string $filename = null,
        public ?int $numberId = null
    ) {
    }

    public function handle(): void
    {
        try {
            // Scoped to the number the message arrived on: a store with two connected numbers
            // holds a separate token per WABA, and only that one can read this file.
            $wa   = WhatsAppService::make($this->storeId ?: null, null, $this->numberId);
            $file = $wa->downloadMedia($this->mediaId, $this->filename);
            if (!$file) {
                return;
            }

            DB::table('whatsapp_messages')->where('id', $this->messageId)->update([
                'media_url'  => mb_substr($file['path'], 0, 500),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('WA inbound media fetch failed for message #' . $this->messageId . ': ' . $e->getMessage());
        }
    }
}
