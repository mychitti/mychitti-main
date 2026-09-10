<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Crawls the site's own SEO page graph — published service_zone_seo landing pages plus
 * category-listing pages with real supply — and counts internal links between them, the same
 * way Google would find them by following <a href>, not by trusting our own link-building code.
 *
 * A plain command, not a queued job: no worker on any server currently consumes the 'seo' queue
 * (checked — the two running queue:work processes are --queue=leads and the unqueued default,
 * and the default worker's --timeout=90 would kill this well before it finishes anyway), so this
 * runs the same way seo:sync-combos does — directly inside the cron-triggered scheduler process,
 * no queue involved at all.
 *
 * Scope is deliberately limited to these ~1,900 hub pages, not every store/item page: these are
 * the pages the internal-linking strategy ($seoLinks / $otherCityLinks in category_listing(),
 * and the equivalent cross-links on SEO landing pages) is actually about. A store or item page
 * with zero inbound links is expected — it's meant to be found via its category hub, not linked
 * to directly from other pages.
 */
class AuditInternalLinksCommand extends Command
{
    protected $signature = 'internal-links:audit {--limit= : Crawl only the first N pages, for a quick test run}';
    protected $description = 'Crawl the SEO page graph and record inbound/outbound internal link counts';

    public function handle(): int
    {
        $this->ensureSchema();

        $baseUrl = rtrim(config('app.url'), '/');
        $urls = $this->scopeUrls($baseUrl);

        if ($limit = $this->option('limit')) {
            $urls = $urls->take((int) $limit);
        }

        if ($urls->isEmpty()) {
            $this->warn('No URLs in scope — nothing to crawl.');
            return self::SUCCESS;
        }

        $this->info('Crawling ' . $urls->count() . ' page(s)...');
        $bar = $this->output->createProgressBar($urls->count());
        $bar->start();

        // Fast membership checks while crawling.
        $scopeSet = $urls->flip();
        $outboundCount = [];
        $inboundCount = array_fill_keys($urls->all(), 0);
        $httpStatus = [];

        foreach ($urls as $url) {
            $outboundCount[$url] = 0;

            try {
                $response = Http::timeout(15)->get($url);
            } catch (\Throwable $e) {
                $httpStatus[$url] = 0;
                $bar->advance();
                continue;
            }

            $httpStatus[$url] = $response->status();

            if ($response->ok()) {
                foreach ($this->extractLinks($response->body(), $baseUrl) as $target) {
                    if ($target === $url) {
                        continue; // don't count a page linking to itself
                    }
                    if (isset($scopeSet[$target])) {
                        $outboundCount[$url]++;
                        $inboundCount[$target]++;
                    }
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        DB::transaction(function () use ($urls, $outboundCount, $inboundCount, $httpStatus) {
            DB::table('internal_link_audit')->delete();

            $now = now();
            foreach ($urls as $url) {
                DB::table('internal_link_audit')->insert([
                    'url'            => mb_substr($url, 0, 500),
                    'http_status'    => $httpStatus[$url] ?? 0,
                    'outbound_links' => $outboundCount[$url] ?? 0,
                    'inbound_links'  => $inboundCount[$url] ?? 0,
                    'checked_at'     => $now,
                ]);
            }
        });

        $orphans = collect($inboundCount)->filter(fn($n, $url) => $n === 0 && ($httpStatus[$url] ?? 0) === 200)->count();
        $this->info("Done. {$urls->count()} page(s) crawled, {$orphans} orphan(s) found.");

        return self::SUCCESS;
    }

    /** Every URL <a href> could plausibly point at — the same set we crawl from. */
    private function scopeUrls(string $baseUrl): \Illuminate\Support\Collection
    {
        $urls = collect();

        // Published SEO landing pages (category-level and item-level).
        DB::table('service_zone_seo')
            ->where('status', 'published')
            ->pluck('slug')
            ->each(fn($slug) => $urls->push($baseUrl . '/' . $slug));

        // Category-listing pages that actually have supply — same computation as
        // SitemapController's "other modules" branch and category_listing()'s own resolution.
        $zoneSlugs = DB::table('zones')->pluck('name', 'id')->map(fn($name) => _zoneCitySlug($name));

        DB::table('item_store as ist')
            ->join('items as i', 'i.id', '=', 'ist.item_id')
            ->join('categories as c', 'c.id', '=', 'i.category_id')
            ->join('stores as s', 's.id', '=', 'ist.store_id')
            ->where('s.status', 1)->where('s.active', 1)->where('s.show_in_mychitti', 1)->where('i.status', 1)
            ->whereNull('c.added_by')
            ->select('c.slug as cat_slug', 's.zone_id')
            ->distinct()
            ->get()
            ->each(function ($row) use ($urls, $zoneSlugs, $baseUrl) {
                $citySlug = $zoneSlugs[$row->zone_id] ?? null;
                if ($citySlug) {
                    $urls->push($baseUrl . '/category/' . $row->cat_slug . '/' . $citySlug);
                }
            });

        return $urls->unique()->values();
    }

    /** @return string[] absolute, scheme+host-stripped-to-baseUrl-form internal link targets */
    private function extractLinks(string $html, string $baseUrl): array
    {
        if (trim($html) === '') {
            return [];
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $links = [];
        foreach ($doc->getElementsByTagName('a') as $a) {
            $href = trim($a->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            // Relative → absolute against baseUrl; anything on a different host is not internal.
            if (str_starts_with($href, '/')) {
                $href = $baseUrl . $href;
            } elseif (!str_starts_with($href, $baseUrl)) {
                continue;
            }

            // Strip query string / fragment — scope URLs never carry either.
            $href = strtok($href, '?#');
            $links[] = rtrim($href, '/');
        }

        return $links;
    }

    private function ensureSchema(): void
    {
        if (Schema::hasTable('internal_link_audit')) {
            return;
        }

        DB::statement("
            CREATE TABLE internal_link_audit (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                url VARCHAR(500) NOT NULL,
                http_status SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                outbound_links INT UNSIGNED NOT NULL DEFAULT 0,
                inbound_links INT UNSIGNED NOT NULL DEFAULT 0,
                checked_at TIMESTAMP NULL,
                KEY idx_inbound (inbound_links)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
