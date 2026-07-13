<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**  
 * Review Intelligence (Phase 3 §3.7) — classify sentiment of store reviews via the ai-server
 * /analyze/sentiment endpoint and store label + score on store_reviews. Idempotent: only
 * processes rows not yet analyzed (or use --refresh to re-score everything).
 */ 
class AnalyzeReviewSentiment extends Command
{ 
    protected $signature = 'reviews:analyze-sentiment
        {--limit=500 : Max reviews to process this run}
        {--batch=25 : Reviews per ai-server request}
        {--refresh : Re-analyze reviews that already have sentiment}';

    protected $description = 'Analyze store review sentiment (Review Intelligence)';

    public function handle(): int
    {
        $endpoint = rtrim(config('services.ai_server.url', ''), '/') . '/analyze/sentiment';
        if (config('services.ai_server.url') === null || config('services.ai_server.url') === '') {
            $this->error('AI_SERVER_URL is not configured.');
            return self::FAILURE;
        }

        $query = DB::table('store_reviews')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderByDesc('id')
            ->limit((int) $this->option('limit'));

        if (!$this->option('refresh')) {
            $query->whereNull('sentiment_analyzed_at');
        }

        $reviews = $query->get(['id', 'comment']);
        if ($reviews->isEmpty()) {
            $this->info('No reviews to analyze.');
            return self::SUCCESS;
        }

        $done = 0;
        $failed = 0;
        foreach ($reviews->chunk((int) $this->option('batch')) as $chunk) {
            $texts = $chunk->pluck('comment')->map(fn($c) => (string) $c)->all();

            try {
                $response = Http::timeout(90)->post($endpoint, ['texts' => array_values($texts)]);
            } catch (\Exception $e) {
                $failed += $chunk->count();
                $this->warn('Batch error: ' . $e->getMessage());
                continue;
            }

            if (!$response->ok() || !$response->json('success')) {
                $failed += $chunk->count();
                $this->warn('Batch failed: ' . $response->body());
                continue;
            }

            $results = $response->json('results', []);
            $ids = $chunk->pluck('id')->values();
            foreach ($ids as $idx => $reviewId) {
                $r = $results[$idx] ?? null;
                if (!$r) {
                    continue;
                }
                DB::table('store_reviews')->where('id', $reviewId)->update([
                    'sentiment'             => $r['label'] ?? 'neutral',
                    'sentiment_score'       => isset($r['score']) ? (float) $r['score'] : null,
                    'sentiment_analyzed_at' => now(),
                ]);
                $done++;
            }
        }

        $this->info("Analyzed {$done} review(s). Failed: {$failed}.");
        return self::SUCCESS;
    }
}
