<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per AI document check. Kept so the admin can see why an upload was rejected, and
 * so a "review" verdict has somewhere to surface for a human to look at later.
 */
class DocValidationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'vendor_id',
        'source',
        'doc_type',
        'file_name',
        'expected_number',
        'extracted_number',
        'verdict',
        'confidence',
        'summary',
        'issues',
        'raw',
    ];

    public static function ensureTable(): void
    {
        if (!Schema::hasTable('doc_validation_logs')) {
            DB::statement("CREATE TABLE `doc_validation_logs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NULL,
                `vendor_id` BIGINT NULL,
                `source` VARCHAR(30) NOT NULL DEFAULT 'vendor_panel',
                `doc_type` VARCHAR(40) NOT NULL,
                `file_name` VARCHAR(255) NULL,
                `expected_number` VARCHAR(100) NULL,
                `extracted_number` VARCHAR(100) NULL,
                `verdict` VARCHAR(20) NOT NULL,
                `confidence` DECIMAL(4,3) NULL,
                `summary` TEXT NULL,
                `issues` TEXT NULL,
                `raw` LONGTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `dvl_store_idx` (`store_id`),
                KEY `dvl_verdict_idx` (`verdict`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function issueList(): array
    {
        $decoded = json_decode((string) $this->issues, true);

        return is_array($decoded) ? $decoded : [];
    }
}
