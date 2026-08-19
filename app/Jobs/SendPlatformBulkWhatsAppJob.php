<?php

namespace App\Jobs;

use App\Services\WhatsAppBulkRun;
use App\Services\WhatsAppService;
use App\Traits\PlatformWhatsAppAudience;
use App\Traits\WhatsAppAudience;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Carries out one bulk send from the MyChitti platform number.
 *
 * The admin twin of SendVendorBulkWhatsAppJob: the composer posts the run once, this claims the
 * audience and works through it on the queue, and closing the browser no longer stops the send.
 * Nothing here touches a vendor's WABA, wallet or templates — platform sends carry store_id 0 in
 * wa_bulk_sends, which is what keeps them out of vendor billing entirely.
 */
class SendPlatformBulkWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WhatsAppAudience, PlatformWhatsAppAudience;

    // No auto-retry: WhatsApp sends are not idempotent. A pass that dies leaves its remaining
    // recipients queued in wa_bulk_sends and ResumeWhatsAppBulkRunsJob picks the run back up —
    // the claim on (run_id, phone10) is what guarantees nobody is messaged twice on the way.
    public int $tries = 1;
    public int $timeout = 900;

    /** Recipients one pass sends before re-queueing the remainder. */
    const PASS_LIMIT = 300;

    /** Recipients handled between two lock renewals / stop checks. */
    const CHUNK = 25;

    public function __construct(public string $runId)
    {
    }

    public function handle(): void
    {
        $run = WhatsAppBulkRun::find($this->runId, WhatsAppBulkRun::PLATFORM_SCOPE);
        if (!$run || in_array($run->status, WhatsAppBulkRun::CLOSED, true)) {
            return;
        }

        // Somebody else is already walking this run — a re-dispatch racing the sweeper, most
        // likely. Two passes on one run would send the same chunk twice.
        $token = WhatsAppBulkRun::acquire($this->runId);
        if (!$token) {
            return;
        }

        // Whatever the previous holder was in the middle of goes back into the queue — it has no
        // claim on the run any more, and its chunk must not be stranded half-sent.
        WhatsAppBulkRun::reclaim($this->runId);

        if ((int) $run->attempts >= WhatsAppBulkRun::MAX_ATTEMPTS) {
            WhatsAppBulkRun::close(
                $this->runId,
                WhatsAppBulkRun::STATUS_FAILED,
                'Stopped after ' . WhatsAppBulkRun::MAX_ATTEMPTS . ' attempts. Anyone already messaged was not messaged twice.'
            );
            return;
        }

        $spec = WhatsAppBulkRun::spec($run);

        try {
            $wa = WhatsAppService::make();
            if (!$wa->isConfigured()) {
                WhatsAppBulkRun::close($this->runId, WhatsAppBulkRun::STATUS_STOPPED,
                    'The MyChitti WhatsApp number is not configured.');
                return;
            }

            if ($run->status === WhatsAppBulkRun::STATUS_QUEUED) {
                $this->seed($spec);
            }

            $this->send($wa, $spec, $token);
        } catch (\Throwable $e) {
            Log::error('WA platform bulk pass failed', ['run' => $this->runId, 'error' => $e->getMessage()]);

            // Left open on purpose: the remaining recipients are still queued, and the sweeper
            // gives the run another go rather than throwing away what is left of it.
            WhatsAppBulkRun::note($this->runId, 'Interrupted: ' . $e->getMessage());
            WhatsAppBulkRun::release($this->runId, $token);
        }
    }

    /** Claim everyone this run is meant to reach, so the rows themselves become the work list. */
    private function seed(array $spec): void
    {
        $audience = (string) ($spec['audience'] ?? 'customers');
        $mode = (string) ($spec['mode'] ?? 'selected');
        $template = (string) ($spec['template'] ?? '');

        // A re-seed after an interrupted first pass tops the audience up rather than asking for
        // another full helping — 'all' is a count, not a fixed list.
        $held = (int) DB::table('wa_bulk_sends')->where('run_id', $this->runId)->count();
        $limit = max(0, (int) ($spec['limit'] ?? 0) - $held);

        // Already topped up by an earlier pass — there is nothing left to ask the audience for.
        if ($mode === 'all' && $limit === 0) {
            $this->markSeeded(0);
            return;
        }

        $people = $this->platformRecipients(
            $audience,
            $mode,
            (array) ($spec['filters'] ?? []),
            (array) ($spec['ids'] ?? []),
            (array) ($spec['numbers'] ?? []),
            $limit,
            $this->runId
        );

        $report = WhatsAppBulkRun::seed(
            WhatsAppBulkRun::PLATFORM_SCOPE,
            $this->runId,
            $people,
            $audience,
            $template,
            // Only a pasted list can carry an opted-out number — the audience queries drop them
            // before they are ever offered, and reading the list is not free.
            $audience === 'manual' ? $this->optOutSuffixes() : []
        );

        $this->markSeeded($report['blocked']);

        Log::info('WA platform bulk seeded', [
            'run'      => $this->runId,
            'audience' => $audience,
            'claimed'  => $report['seeded'],
            'blocked'  => $report['blocked'],
        ]);
    }

    private function markSeeded(int $blocked): void
    {
        WhatsAppBulkRun::markRunning(
            $this->runId,
            (int) DB::table('wa_bulk_sends')->where('run_id', $this->runId)->count(),
            $blocked
        );
    }

    private function send(WhatsAppService $wa, array $spec, string $token): void
    {
        $template = (string) ($spec['template'] ?? '');
        $language = (string) ($spec['language'] ?? 'en');
        $rawParams = array_values((array) ($spec['params'] ?? []));

        // A template is sent with the components it was approved with: one carrying an image,
        // video or document header needs that file on every message, or Graph refuses the lot
        // with "(#132012) Parameter format does not match format in the created template".
        $tpl = $this->platformTemplate($template, $language);
        $headerComponent = null;
        $headerFormat = $this->headerFormatOf($tpl);
        if (in_array($headerFormat, WhatsAppService::MEDIA_HEADERS, true)) {
            $mediaUrl = trim((string) ($spec['header_media'] ?? ''));
            if ($mediaUrl === '') {
                WhatsAppBulkRun::close($this->runId, WhatsAppBulkRun::STATUS_STOPPED,
                    'This template needs a file at the top and none was attached.');
                return;
            }
            $headerComponent = WhatsAppService::mediaHeaderComponent($headerFormat, $mediaUrl);
        }

        // Read once for the whole run and filled per recipient below, so the history can show the
        // words each number actually read — the delivery log only keeps "template: {name}", and a
        // template can be edited or deleted long before anyone asks what was sent.
        $templateBody = $this->bodyTextOf($tpl);

        $processed = 0;

        while ($processed < self::PASS_LIMIT) {
            // Ownership is re-taken before every chunk, never assumed for the length of the pass.
            // A worker frozen long enough for the sweeper to hand the run on must not wake up and
            // carry on sending alongside its replacement.
            if (!WhatsAppBulkRun::renew($this->runId, $token)) {
                Log::warning('WA platform bulk pass lost its run', ['run' => $this->runId]);
                return;
            }

            if (WhatsAppBulkRun::stopping($this->runId)) {
                WhatsAppBulkRun::close($this->runId, WhatsAppBulkRun::STATUS_STOPPED,
                    'Stopped. Nobody past this point was messaged.');
                return;
            }

            $rows = WhatsAppBulkRun::pending($this->runId, self::CHUNK);
            if ($rows->isEmpty()) {
                WhatsAppBulkRun::close($this->runId, WhatsAppBulkRun::STATUS_DONE);
                return;
            }

            WhatsAppBulkRun::markSending($rows->pluck('id')->all());

            foreach ($rows as $row) {
                $name = trim((string) $row->name) ?: 'Customer';
                $phone = trim((string) $row->phone);

                [$parameters, $filled] = WhatsAppBulkRun::bodyParameters($rawParams, $name, $phone);

                // Header first — Meta matches components against the approved template in order.
                $components = [];
                if ($headerComponent) {
                    $components[] = $headerComponent;
                }
                if ($parameters) {
                    $components[] = ['type' => 'body', 'parameters' => $parameters];
                }

                $res = $wa->sendTemplate($phone, $template, $language, $components, WhatsAppBulkRun::PLATFORM_CONTEXT);

                WhatsAppBulkRun::record((int) $row->id, $res, $templateBody, $filled, $language);
                $processed++;
            }
        }

        // More to go. Handing over rather than looping on keeps each job well inside its timeout,
        // and leaves a gap where a stop request can take effect.
        WhatsAppBulkRun::release($this->runId, $token);
        static::dispatch($this->runId);
    }
}
