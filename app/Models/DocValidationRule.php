<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-authored knowledge the AI applies when checking a vendor document. Each row is one
 * rule in plain English ("A GST certificate must show a 15-character GSTIN"), scoped to a
 * document type and marked either blocking or advisory.
 */
class DocValidationRule extends Model
{
    use HasFactory;

    protected $fillable = ['doc_type', 'title', 'rule', 'severity', 'active'];

    const DOC_TYPES = [
        'all'        => 'All Documents',
        'id_doc'     => 'ID Proof',
        'gst_doc'    => 'GST Certificate',
        'fssai_doc'  => 'FSSAI Licence',
        'other'      => 'Other Documents',
    ];

    const SEVERITIES = [
        'block' => 'Block upload',
        'warn'  => 'Flag for review',
    ];

    public static function ensureTable(): void
    {
        if (!Schema::hasTable('doc_validation_rules')) {
            DB::statement("CREATE TABLE `doc_validation_rules` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `doc_type` VARCHAR(40) NOT NULL DEFAULT 'all',
                `title` VARCHAR(200) NOT NULL,
                `rule` TEXT NOT NULL,
                `severity` VARCHAR(10) NOT NULL DEFAULT 'block',
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `dvr_type_active_idx` (`doc_type`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public static function typeLabel(?string $type): string
    {
        return self::DOC_TYPES[$type] ?? 'Other Documents';
    }

    /** Active rules that apply to one document type — its own, plus the ones marked "all". */
    public static function activeFor(string $docType)
    {
        self::ensureTable();

        return self::where('active', 1)
            ->where(function ($q) use ($docType) {
                $q->where('doc_type', $docType)->orWhere('doc_type', 'all');
            })
            ->orderBy('severity')
            ->orderBy('id')
            ->get();
    }

    /**
     * The rules rendered for the system prompt. Blocking and advisory rules are listed
     * separately so the model knows which failures may reject an upload.
     */
    public static function promptBlock(string $docType): string
    {
        $rules = self::activeFor($docType);
        if ($rules->isEmpty()) {
            return '';
        }

        $lines = [];
        foreach (['block', 'warn'] as $severity) {
            $group = $rules->where('severity', $severity);
            if ($group->isEmpty()) {
                continue;
            }
            $lines[] = $severity === 'block'
                ? "BLOCKING RULES — a document that breaks any of these must get verdict \"fail\":"
                : "ADVISORY RULES — a document that breaks any of these must get verdict \"review\" (never \"fail\" on these alone):";
            foreach ($group as $rule) {
                $lines[] = '- ' . trim($rule->title) . ': ' . trim($rule->rule);
            }
            $lines[] = '';
        }

        return trim(implode("\n", $lines));
    }
}
