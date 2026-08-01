<?php

namespace App\Jobs\Scheduled;

use App\Services\HmisWhatsAppShare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes the PDFs generated for WhatsApp document messages once Meta has had time to fetch them.
 *
 * A media template's attachment has to sit at a URL Meta can download with no session, so for a
 * short while a patient's prescription is a public file protected only by a random filename. Meta
 * fetches it within seconds of the send and the patient keeps their own copy inside WhatsApp, so
 * nothing needs the file a day later — leaving it there would quietly build a public archive of
 * medical records, which is the one outcome this whole design is trying to avoid.
 */
class PruneSharedPdfsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    /** Generous next to Meta's fetch, short next to how long a medical record stays sensitive. */
    const KEEP_HOURS = 24;

    public function handle(): void
    {
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists(HmisWhatsAppShare::PDF_DIR)) {
                return;
            }

            $cutoff  = now()->subHours(self::KEEP_HOURS)->getTimestamp();
            $removed = 0;

            foreach ($disk->files(HmisWhatsAppShare::PDF_DIR) as $file) {
                try {
                    if ($disk->lastModified($file) < $cutoff) {
                        $disk->delete($file);
                        $removed++;
                    }
                } catch (\Throwable $e) {
                    // One unreadable file on the Spaces mount must not stop the sweep.
                    Log::warning('Shared PDF prune skipped ' . $file . ': ' . $e->getMessage());
                }
            }

            if ($removed) {
                Log::info('Pruned ' . $removed . ' shared WhatsApp PDF(s).');
            }
        } catch (\Throwable $e) {
            Log::error('Shared PDF prune failed: ' . $e->getMessage());
        }
    }
}
