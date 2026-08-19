<?php

namespace App\Jobs;

use App\Services\WhatsAppBilling;
use App\Services\WhatsAppBulkRun;
use App\Services\WhatsAppService;
use App\Traits\WhatsAppAudience;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Carries out one vendor bulk send from the store's own connected number.
 *
 * This is the send that used to happen in the browser: the composer posted 25 recipients at a
 * time and the run existed only for as long as the tab did. Now the composer posts once, this job
 * claims the whole audience up front, and the messages go out from the queue — closing the window,
 * or losing the connection, no longer stops anything.
 *
 * One pass sends at most PASS_LIMIT recipients and then hands the rest to a fresh instance of
 * itself. That keeps every job short enough to finish well inside its timeout, so a 17,000-person
 * run is a chain of small jobs rather than one that the worker eventually kills halfway through.
 */
class SendVendorBulkWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WhatsAppAudience;

    // No auto-retry: WhatsApp sends are not idempotent. A pass that dies leaves its remaining
    // recipients queued in wa_bulk_sends, and ResumeWhatsAppBulkRunsJob picks the run back up —
    // the claim on (run_id, phone10) is what guarantees nobody is messaged twice on the way.
    public int $tries = 1;
    public int $timeout = 900;

    /** Recipients one pass sends before re-queueing the remainder. */
    const PASS_LIMIT = 300;

    /** Recipients handled between two lock renewals / stop checks. */
    const CHUNK = 25;

    public function __construct(public int $storeId, public string $runId)
    {
    }

    public function handle(): void
    {
        $run = WhatsAppBulkRun::find($this->runId, $this->storeId);
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
            $wa = WhatsAppService::make($this->storeId);

            if ($wa->source() !== 'vendor') {
                WhatsAppBulkRun::close($this->runId, WhatsAppBulkRun::STATUS_STOPPED,
                    'The store is no longer sending from its own WhatsApp number.');
                return;
            }

            // A subscription can lapse mid-run — a long send must not carry on for free past the
            // grace window just because it was started while the plan was live.
            if (!WhatsAppBilling::isActive($this->storeId)) {
                WhatsAppBulkRun::close($this->runId, WhatsAppBulkRun::STATUS_STOPPED,
                    'Stopped — the WhatsApp subscription is no longer active.');
                return;
            }

            if ($run->status === WhatsAppBulkRun::STATUS_QUEUED) {
                $this->seed($spec);
            }

            $this->send($wa, $spec, $token);
        } catch (\Throwable $e) {
            Log::error('WA vendor bulk pass failed', [
                'store' => $this->storeId,
                'run'   => $this->runId,
                'error' => $e->getMessage(),
            ]);

            // Left open on purpose: the remaining recipients are still queued, and the sweeper
            // gives the run another go rather than throwing away what is left of it.
            WhatsAppBulkRun::note($this->runId, 'Interrupted: ' . $e->getMessage());
            WhatsAppBulkRun::release($this->runId, $token);
        }
    }

    /**
     * Claim everyone this run is meant to reach.
     *
     * Own customers first: they are the cheaper audience and the relationship the store actually
     * has, so if a wallet runs dry mid-run it runs dry on strangers rather than on the people who
     * already buy from them. A number in both audiences is claimed once, by the unique key.
     */
    private function seed(array $spec): void
    {
        $template = (string) ($spec['template'] ?? '');
        $seeded = 0;

        $clientIds = array_values(array_filter((array) ($spec['client_ids'] ?? [])));
        if ($clientIds) {
            $clients = $this->clientQuery($this->storeId)
                ->whereIn('id', $clientIds)
                ->get(['id', 'f_name as name', 'phone']);

            $seeded += WhatsAppBulkRun::seed($this->storeId, $this->runId, $clients, 'own', $template)['seeded'];
        }

        $wanted = (int) ($spec['platform_limit'] ?? 0);
        if ($wanted > 0) {
            // Anyone this run already claimed as a platform recipient — a re-seed after an
            // interrupted first pass must top the audience up, not ask for another full helping.
            $held = (int) DB::table('wa_bulk_sends')
                ->where('run_id', $this->runId)->where('audience', 'platform')->count();

            $remaining = $wanted - $held;
            if ($remaining > 0) {
                // No offset — the pool is walked by exclusion. outreachQuery drops everyone this
                // store reached inside the rotation window; the claim exclusion below drops
                // everyone this run already holds. Between them, what comes back is the next
                // unmessaged people rather than the same lowest-numbered ones every time.
                $phone10 = $this->phone10Sql('t.phone');
                $claimed = $this->collatedPhone('b.`phone10`');
                $candidate = $this->collatedPhone($phone10);

                $people = $this->outreachQuery($this->storeId)
                    ->whereNotExists(function ($q) use ($claimed, $candidate) {
                        $q->select(DB::raw(1))->from('wa_bulk_sends as b')
                            ->where('b.run_id', $this->runId)
                            ->whereRaw("{$claimed} = {$candidate}");
                    })
                    ->orderByRaw($phone10)
                    ->limit($remaining)
                    ->get();

                $seeded += WhatsAppBulkRun::seed($this->storeId, $this->runId, $people, 'platform', $template)['seeded'];
            }
        }

        WhatsAppBulkRun::markRunning(
            $this->runId,
            (int) DB::table('wa_bulk_sends')->where('run_id', $this->runId)->count()
        );

        Log::info('WA vendor bulk seeded', ['store' => $this->storeId, 'run' => $this->runId, 'claimed' => $seeded]);
    }

    private function send(WhatsAppService $wa, array $spec, string $token): void
    {
        $template = (string) ($spec['template'] ?? '');
        $language = (string) ($spec['language'] ?? 'en');
        $rawParams = array_values((array) ($spec['params'] ?? []));

        // A template is sent with the components it was approved with. One with an image, video or
        // document header needs that file on every message, or Graph rejects the lot with
        // "(#132012) Parameter format does not match format in the created template".
        $headerComponent = null;
        $headerFormat = WhatsAppService::templateHeaderFormat($this->storeId, $template, $language);
        if (in_array($headerFormat, WhatsAppService::MEDIA_HEADERS, true)) {
            $mediaUrl = trim((string) ($spec['header_media'] ?? ''));
            if ($mediaUrl === '') {
                WhatsAppBulkRun::close($this->runId, WhatsAppBulkRun::STATUS_STOPPED,
                    'This template needs a file at the top and none was attached.');
                return;
            }
            $headerComponent = WhatsAppService::mediaHeaderComponent($headerFormat, $mediaUrl);
        }

        // Read once for the whole run, filled per recipient below. Kept so the history can show
        // the words each customer read — the delivery log only has "template: {name}", and a
        // template can be edited or deleted long before anyone asks.
        $templateBody = WhatsAppService::templateBodyText($this->storeId, $template, $language);

        $processed = 0;
        $reachedPlatform = false;

        while ($processed < self::PASS_LIMIT) {
            // Ownership is re-taken before every chunk, never assumed for the length of the pass.
            // A worker frozen long enough for the sweeper to hand the run on must not wake up and
            // carry on sending alongside its replacement.
            if (!WhatsAppBulkRun::renew($this->runId, $token)) {
                Log::warning('WA vendor bulk pass lost its run', ['run' => $this->runId]);
                $this->afterPlatform($reachedPlatform);
                return;
            }

            if (WhatsAppBulkRun::stopping($this->runId)) {
                WhatsAppBulkRun::close($this->runId, WhatsAppBulkRun::STATUS_STOPPED,
                    'Stopped. Nobody past this point was messaged.');
                $this->afterPlatform($reachedPlatform);
                return;
            }

            $rows = WhatsAppBulkRun::pending($this->runId, self::CHUNK);
            if ($rows->isEmpty()) {
                WhatsAppBulkRun::close($this->runId, WhatsAppBulkRun::STATUS_DONE);
                $this->afterPlatform($reachedPlatform);
                return;
            }

            // Per-message charges leave the wallet at dispatch, so price the chunk before any of
            // it goes out. A store that has run dry stops here with everyone still unsent rather
            // than going into a negative balance.
            $cost = 0.0;
            foreach ($rows as $row) {
                $cost += WhatsAppBilling::messageCost($row->audience === 'platform' ? 'platform' : 'own');
            }

            if (WhatsAppBilling::walletBalance($this->storeId) < round($cost, 2)) {
                WhatsAppBulkRun::close($this->runId, WhatsAppBulkRun::STATUS_STOPPED,
                    'Stopped — wallet balance too low to carry on. Recharge your wallet, then start a new send for whoever is left.');
                $this->afterPlatform($reachedPlatform);
                return;
            }

            WhatsAppBulkRun::markSending($rows->pluck('id')->all());

            foreach ($rows as $row) {
                $platform = $row->audience === 'platform';
                $reachedPlatform = $reachedPlatform || $platform;

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

                // Context 'nearby' is what nearbyCappedPhones() counts — it must stay distinct
                // from sends to the store's own book or the frequency cap silently stops working.
                $res = $wa->sendTemplate(
                    $phone,
                    $template,
                    $language,
                    $components,
                    $platform ? 'nearby' : 'bulk'
                );

                WhatsAppBulkRun::record((int) $row->id, $res, $templateBody, $filled, $language);
                $processed++;
            }
        }

        $this->afterPlatform($reachedPlatform);

        // More to go. Handing over rather than looping on keeps each job well inside its timeout,
        // and leaves a gap where a stop request can take effect.
        WhatsAppBulkRun::release($this->runId, $token);
        static::dispatch($this->storeId, $this->runId);
    }

    /**
     * These recipients have just entered the store's rotation window, so the audience size the
     * composer shows is out of date — drop it rather than let it keep offering people it can no
     * longer reach.
     */
    private function afterPlatform(bool $reached): void
    {
        if ($reached) {
            $this->forgetOutreachCount($this->storeId);
        }
    }
}
