<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrescriptionTemplate extends Model
{
    protected $table = 'prescription_templates';

    protected $fillable = [
        'store_id', 'doctor_profile_id', 'name', 'diagnosis', 'notes',
        'follow_up_days', 'items', 'is_shared', 'created_by', 'created_by_type',
    ];

    protected $casts = [
        'items'     => 'array',
        'is_shared' => 'boolean',
    ];

    // The medicine columns a template carries. Anything else posted from the row is ignored, so a
    // new field on the prescription form cannot silently start being stored here unreviewed.
    const ITEM_FIELDS = [
        'type', 'medicine_name', 'inventory_item_id', 'dosage',
        'instructions', 'frequency', 'duration', 'quantity', 'notes',
    ];

    // Guarded table create — no migration files.
    public static function ensureTable(): void
    {
        if (!Schema::hasTable('prescription_templates')) {
            DB::statement("CREATE TABLE IF NOT EXISTS `prescription_templates` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NOT NULL,
                `doctor_profile_id` BIGINT UNSIGNED NULL,
                `name` VARCHAR(190) NOT NULL,
                `diagnosis` VARCHAR(1000) NULL,
                `notes` TEXT NULL,
                `follow_up_days` INT NULL,
                `items` LONGTEXT NULL,
                `is_shared` TINYINT(1) NOT NULL DEFAULT 0,
                `created_by` BIGINT UNSIGNED NULL,
                `created_by_type` VARCHAR(30) NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `rxt_store_doctor_idx` (`store_id`, `doctor_profile_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }

    /**
     * What this doctor may pick from: their own templates plus anything shared hospital-wide.
     *
     * A doctor's regimens are their own by default — one consultant's "Back Pain" is not another's
     * — but a hospital that wants a house standard can tick Share and have it show for everyone.
     */
    public static function visibleTo($storeId, $doctorProfileId = null)
    {
        self::ensureTable();

        return self::where('store_id', $storeId)
            ->where(function ($q) use ($doctorProfileId) {
                $q->where('is_shared', 1);
                if ($doctorProfileId) {
                    $q->orWhere('doctor_profile_id', $doctorProfileId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Normalise posted medicine rows into what the template stores.
     *
     * Rows with no medicine name are blank repeater lines and are dropped. `quantity` is kept as
     * typed rather than cast, because a template is a starting point the doctor edits, not a
     * dispensing record.
     */
    public static function normaliseItems(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            if (!is_array($row) || trim((string) ($row['medicine_name'] ?? '')) === '') {
                continue;
            }

            $item = [];
            foreach (self::ITEM_FIELDS as $field) {
                $value = trim((string) ($row[$field] ?? ''));
                $item[$field] = $value === '' ? null : $value;
            }
            $items[] = $item;
        }

        return $items;
    }

    /**
     * The template's rows, with stock links that no longer resolve dropped.
     *
     * A template outlives the inventory row it was built from. Carrying a dead `inventory_item_id`
     * into a new prescription would hand the pharmacy a line it cannot dispense against, so the
     * name survives and the broken link does not.
     */
    public function itemsForForm(): array
    {
        $items = $this->items ?: [];

        $ids = collect($items)->pluck('inventory_item_id')->filter()->unique()->all();
        $live = $ids
            ? InventoryItem::where('store_id', $this->store_id)->whereIn('id', $ids)->pluck('id')->all()
            : [];

        return collect($items)->map(function ($item) use ($live) {
            if (!empty($item['inventory_item_id']) && !in_array($item['inventory_item_id'], $live)) {
                $item['inventory_item_id'] = null;
            }

            return $item;
        })->values()->all();
    }
}
