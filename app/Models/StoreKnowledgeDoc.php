<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-store knowledge documents for WhatsApp auto-reply: the vendor writes what their
 * business knows (services, prices, FAQs, policies…) and the auto-reply engine answers
 * customer messages from the ACTIVE documents of that store only.
 */
class StoreKnowledgeDoc extends Model
{
    protected $table = 'store_knowledge_docs';

    protected $fillable = [
        'store_id',
        'doc_type',
        'title',
        'content',
        'active',
        'rag_doc_id',
    ];

    /** Dropdown options for the document type. Key is stored in doc_type. */
    const DOC_TYPES = [
        'business_info' => 'Business Information',
        'services'      => 'Services & Pricing',
        'products'      => 'Products / Price List',
        'faq'           => 'FAQs (Questions & Answers)',
        'timings'       => 'Working Hours & Location',
        'policies'      => 'Policies (booking, refund, cancellation)',
        'offers'        => 'Offers & Discounts',
        'other'         => 'Other',
    ];

    public static function typeLabel(?string $type): string
    {
        return self::DOC_TYPES[$type] ?? ucfirst((string) $type);
    }

    /** Idempotent, no migration files (per project rules). */
    public static function ensureTable(): void
    {
        if (!Schema::hasTable('store_knowledge_docs')) {
            DB::statement("CREATE TABLE `store_knowledge_docs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `doc_type` VARCHAR(40) NOT NULL DEFAULT 'other',
                `title` VARCHAR(200) NOT NULL,
                `content` TEXT NOT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `rag_doc_id` BIGINT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `skd_store_idx` (`store_id`),
                KEY `skd_store_active_idx` (`store_id`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasColumn('store_knowledge_docs', 'rag_doc_id')) {
            DB::statement("ALTER TABLE `store_knowledge_docs` ADD COLUMN `rag_doc_id` BIGINT NULL");
        }
    }

    /** RAG category that scopes this store's knowledge — one namespace per store. */
    public static function ragCategory(int $storeId): string
    {
        return 'store_' . $storeId;
    }

    protected static function ragUrl(): string
    {
        return rtrim((string) config('services.ai_server.url'), '/') . '/rag';
    }

    /**
     * Push this document into the RAG index (pgvector) so auto-reply retrieves only the
     * chunks relevant to each question. Best-effort: the local row is the source of truth,
     * and a RAG outage never blocks the vendor's CRUD — auto-reply falls back to full-text.
     */
    public function syncToRag(): void
    {
        try {
            $title = '[' . static::typeLabel($this->doc_type) . '] ' . $this->title;
            if ($this->rag_doc_id) {
                $resp = \Illuminate\Support\Facades\Http::timeout(30)->put(static::ragUrl() . "/documents/{$this->rag_doc_id}", [
                    'title'    => $title,
                    'content'  => $this->content,
                    'category' => static::ragCategory((int) $this->store_id),
                ]);
                if ($resp->successful()) {
                    return;
                }
                // Stale id (index rebuilt / doc pruned) — fall through and re-ingest fresh.
            }
            $resp = \Illuminate\Support\Facades\Http::timeout(30)->post(static::ragUrl() . '/ingest', [
                'title'    => $title,
                'content'  => $this->content,
                'source'   => 'vendor_knowledge',
                'category' => static::ragCategory((int) $this->store_id),
                'metadata' => ['store_id' => (int) $this->store_id, 'doc_type' => $this->doc_type, 'local_id' => (int) $this->id],
            ]);
            if ($resp->successful() && ($id = data_get($resp->json(), 'id'))) {
                DB::table('store_knowledge_docs')->where('id', $this->id)->update(['rag_doc_id' => $id]);
                $this->rag_doc_id = $id;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Knowledge RAG sync failed (doc ' . $this->id . '): ' . $e->getMessage());
        }
    }

    /** Drop this document from the RAG index (delete or pause). Best-effort. */
    public function removeFromRag(): void
    {
        if (!$this->rag_doc_id) {
            return;
        }
        try {
            \Illuminate\Support\Facades\Http::timeout(15)->delete(static::ragUrl() . "/documents/{$this->rag_doc_id}");
            DB::table('store_knowledge_docs')->where('id', $this->id)->update(['rag_doc_id' => null]);
            $this->rag_doc_id = null;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Knowledge RAG remove failed (doc ' . $this->id . '): ' . $e->getMessage());
        }
    }

    /** Index any active docs not yet in RAG — covers docs created before RAG wiring. */
    public static function syncMissing(int $storeId): void
    {
        static::ensureTable();
        static::where('store_id', $storeId)->where('active', 1)
            ->whereNull('rag_doc_id')->get()
            ->each(fn($doc) => $doc->syncToRag());
    }

    /** What the auto-reply engine reads: this store's active knowledge, newest first. */
    public static function activeForStore(int $storeId)
    {
        static::ensureTable();
        return static::where('store_id', $storeId)
            ->where('active', 1)
            ->orderByDesc('updated_at')
            ->get();
    }
}
