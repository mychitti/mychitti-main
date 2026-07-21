<?php

namespace App\Console\Commands;

use App\Jobs\GenerateStoreMeta;
use App\Models\Store;
use Illuminate\Console\Command;

/**
 * Backfill SEO meta (meta_title / meta_description) for stores that are missing it.
 *
 * New stores get meta automatically on registration (see Store::boot). This command is for
 * the existing catalogue — by default it only touches stores that have no meta, so re-running
 * it never re-bills AI for work already done.
 */
class GenerateStoreMetaCommand extends Command
{
    protected $signature = 'store:generate-meta
        {--store= : Generate for a single store by id}
        {--all : Regenerate for every store, OVERWRITING existing meta}
        {--limit= : Cap the number of stores processed}
        {--queue : Push onto the \'seo\' queue instead of generating inline (needs a worker)}';

    protected $description = 'Generate missing store SEO meta (meta_title / meta_description) via AI';

    public function handle(): int
    {
        $force = (bool) $this->option('all');

        // withoutGlobalScopes: ZoneScope filters by the request zone, which does not exist in CLI.
        $query = Store::withoutGlobalScopes()->orderBy('id');

        if ($storeId = $this->option('store')) {
            $query->where('id', (int) $storeId);
        } elseif (!$force) {
            $query->where(function ($q) {
                $q->whereNull('meta_title')->orWhere('meta_title', '')
                    ->orWhereNull('meta_description')->orWhere('meta_description', '');
            });
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $stores = $query->get(['id', 'name']);
        if ($stores->isEmpty()) {
            $this->info('Nothing to do — no stores are missing meta.');
            return self::SUCCESS;
        }

        if ($force && !$this->option('store') && !$this->confirm("--all will OVERWRITE meta for {$stores->count()} store(s). Continue?", false)) {
            $this->warn('Aborted.');
            return self::SUCCESS;
        }

        // Inline by default: nothing currently consumes the 'seo' queue, so queueing would
        // look like success and generate nothing. --queue is opt-in for once a worker exists.
        if ($this->option('queue')) {
            foreach ($stores as $store) {
                GenerateStoreMeta::dispatch($store->id, $force);
            }
            $this->info("Queued {$stores->count()} store(s) on the 'seo' queue.");
            $this->warn("Nothing consumes that queue today — start one with: php artisan queue:work --queue=seo");
            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar($stores->count());
        $bar->start();

        foreach ($stores as $store) {
            // Call handle() directly rather than dispatchSync(): for a ShouldQueue job,
            // dispatchSync() returns the queue-push result, not handle()'s return value.
            $outcome = (new GenerateStoreMeta($store->id, $force))->handle();
            if (!empty($outcome['success'])) {
                $ok++;
            } else {
                $failed++;
                $this->newLine();
                $this->warn("  #{$store->id} {$store->name}: " . ($outcome['message'] ?? 'unknown error'));
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Generated: {$ok}. Failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
