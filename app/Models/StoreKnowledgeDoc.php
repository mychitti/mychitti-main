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
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `skd_store_idx` (`store_id`),
                KEY `skd_store_active_idx` (`store_id`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
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
