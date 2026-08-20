<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HospitalLicense extends Model
{
    protected $table = 'hospital_licenses';

    // Department licences hang off the store (owner_id 0); a doctor's hang off the profile id.
    const OWNER_TYPES = [
        'doctor'    => 'Doctor',
        'lab'       => 'Laboratory',
        'pharmacy'  => 'Pharmacy',
        'radiology' => 'Radiology',
    ];

    protected $fillable = [
        'store_id', 'owner_type', 'owner_id', 'license_type', 'license_no',
        'issuing_authority', 'issued_on', 'valid_till', 'notes', 'is_active',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'valid_till' => 'date',
        'is_active' => 'boolean',
    ];

    // Guarded table create — no migration files.
    public static function ensureTable(): void
    {
        if (!Schema::hasTable('hospital_licenses')) {
            DB::statement("CREATE TABLE IF NOT EXISTS `hospital_licenses` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NOT NULL,
                `owner_type` VARCHAR(20) NOT NULL,
                `owner_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `license_type` VARCHAR(150) NULL,
                `license_no` VARCHAR(150) NOT NULL,
                `issuing_authority` VARCHAR(190) NULL,
                `issued_on` DATE NULL,
                `valid_till` DATE NULL,
                `notes` VARCHAR(500) NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `hl_owner_idx` (`store_id`, `owner_type`, `owner_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public static function listFor($storeId, string $ownerType, $ownerId = 0)
    {
        self::ensureTable();

        return self::where('store_id', $storeId)
            ->where('owner_type', $ownerType)
            ->where('owner_id', (int) $ownerId)
            ->orderBy('id')
            ->get();
    }

    /**
     * Replace an owner's licence list with what the form submitted.
     *
     * Rows arrive from a repeater, so the ones the user deleted simply stop being posted — a
     * rewrite of the whole set is what keeps the stored list matching the screen. Rows with no
     * number at all are blank repeater rows and are dropped rather than saved as empty licences.
     */
    public static function syncFor($storeId, string $ownerType, $ownerId, array $rows): void
    {
        self::ensureTable();

        $ownerId = (int) $ownerId;
        $keep    = [];

        foreach ($rows as $row) {
            $number = trim((string) ($row['license_no'] ?? ''));
            if ($number === '') {
                continue;
            }

            $data = [
                'store_id'          => $storeId,
                'owner_type'        => $ownerType,
                'owner_id'          => $ownerId,
                'license_type'      => trim((string) ($row['license_type'] ?? '')) ?: null,
                'license_no'        => $number,
                'issuing_authority' => trim((string) ($row['issuing_authority'] ?? '')) ?: null,
                'issued_on'         => ($row['issued_on'] ?? '') ?: null,
                'valid_till'        => ($row['valid_till'] ?? '') ?: null,
                'notes'             => trim((string) ($row['notes'] ?? '')) ?: null,
                'is_active'         => 1,
            ];

            $existing = !empty($row['id'])
                ? self::where('store_id', $storeId)->where('owner_type', $ownerType)->find($row['id'])
                : null;

            if ($existing) {
                $existing->update($data);
                $keep[] = $existing->id;
            } else {
                $keep[] = self::create($data)->id;
            }
        }

        self::where('store_id', $storeId)
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->whereNotIn('id', $keep ?: [0])
            ->delete();
    }

    /**
     * Rebuild repeater rows from posted input so a form that failed validation redraws the
     * licences the user had already typed instead of dropping back to the stored set.
     */
    public static function fromInput($rows)
    {
        return collect($rows)->map(function ($row) {
            $license = new self();
            $license->forceFill([
                'id'                => $row['id'] ?? null,
                'license_type'      => $row['license_type'] ?? null,
                'license_no'        => $row['license_no'] ?? null,
                'issuing_authority' => $row['issuing_authority'] ?? null,
                'issued_on'         => ($row['issued_on'] ?? null) ?: null,
                'valid_till'        => ($row['valid_till'] ?? null) ?: null,
            ]);

            return $license;
        })->values();
    }

    public function isExpired(): bool
    {
        return $this->valid_till && $this->valid_till->isPast();
    }

    public function expiresSoon(int $days = 60): bool
    {
        return $this->valid_till
            && !$this->valid_till->isPast()
            && $this->valid_till->lte(now()->addDays($days));
    }

    // "NABL: MC-1234" for a print header — the type is optional, the number never is.
    public function label(): string
    {
        return $this->license_type ? $this->license_type . ': ' . $this->license_no : $this->license_no;
    }
}
