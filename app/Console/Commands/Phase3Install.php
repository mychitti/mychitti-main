<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** 
 * One-shot, idempotent installer for all Phase 3 (AI Search & Intelligence) tables/columns.
 * Follows the project rule: no migration files — guarded CREATE/ALTER via DB::statement.
 *
 * Creates: store_offers (Local Offers Engine), ai_citations (GEO citation monitoring),
 * lead_signals (Lead Inbox signal tracking); adds sentiment columns to store_reviews
 * (Review Intelligence). (vendor_trust_score is installed by vendor:sync-trust-score; 
 * the pgvector business index is installed by the ai-server on first ingest.)
 */
class Phase3Install extends Command
{
    protected $signature = 'phase3:install';
    protected $description = 'Install Phase 3 (AI Search & Intelligence) tables and columns';

    public function handle(): int
    {
        $this->storeOffers();
        $this->aiCitations();
        $this->leadSignals();
        $this->reviewSentiment();
        $this->info('Phase 3 schema installed.');
        return self::SUCCESS;
    }

    private function storeOffers(): void
    {
        if (!Schema::hasTable('store_offers')) {
            DB::statement("
                CREATE TABLE store_offers (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    store_id BIGINT UNSIGNED NOT NULL,
                    category_id BIGINT UNSIGNED NULL,
                    title VARCHAR(150) NOT NULL,
                    description VARCHAR(500) NULL,
                    discount_type ENUM('percent','flat','info') NOT NULL DEFAULT 'info',
                    discount_value DECIMAL(10,2) NULL,
                    start_date DATE NULL,
                    end_date DATE NULL,
                    status TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    KEY idx_store_status (store_id, status),
                    KEY idx_dates (start_date, end_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->info('Created store_offers.');
        }
    }

    private function aiCitations(): void
    {
        if (!Schema::hasTable('ai_citations')) {
            DB::statement("
                CREATE TABLE ai_citations (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    platform VARCHAR(40) NOT NULL,
                    period CHAR(7) NOT NULL,
                    citations INT NOT NULL DEFAULT 0,
                    referral_sessions INT NOT NULL DEFAULT 0,
                    branded_search_volume INT NOT NULL DEFAULT 0,
                    notes VARCHAR(500) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    UNIQUE KEY uq_platform_period (platform, period)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->info('Created ai_citations.');
        }
    }

    private function leadSignals(): void
    {
        if (!Schema::hasTable('lead_signals')) {
            DB::statement("
                CREATE TABLE lead_signals (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    store_id BIGINT UNSIGNED NOT NULL,
                    user_id BIGINT UNSIGNED NULL,
                    type ENUM('call','whatsapp','booking','quote','direction','website') NOT NULL,
                    source VARCHAR(60) NULL,
                    utm_source VARCHAR(80) NULL,
                    utm_medium VARCHAR(80) NULL,
                    utm_campaign VARCHAR(120) NULL,
                    meta JSON NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    KEY idx_store_type (store_id, type),
                    KEY idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->info('Created lead_signals.');
        }
    }

    private function reviewSentiment(): void
    {
        if (!Schema::hasColumn('store_reviews', 'sentiment')) {
            DB::statement("ALTER TABLE store_reviews ADD COLUMN sentiment VARCHAR(10) NULL AFTER experience");
            $this->info('Added store_reviews.sentiment.');
        }
        if (!Schema::hasColumn('store_reviews', 'sentiment_score')) {
            DB::statement("ALTER TABLE store_reviews ADD COLUMN sentiment_score DECIMAL(4,3) NULL AFTER sentiment");
            $this->info('Added store_reviews.sentiment_score.');
        }
        if (!Schema::hasColumn('store_reviews', 'sentiment_analyzed_at')) {
            DB::statement("ALTER TABLE store_reviews ADD COLUMN sentiment_analyzed_at TIMESTAMP NULL AFTER sentiment_score");
            $this->info('Added store_reviews.sentiment_analyzed_at.');
        }
    }
}
