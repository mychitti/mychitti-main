<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HospitalDepartmentProfile extends Model
{
    protected $table = 'hospital_department_profiles';

    const DEPARTMENTS = [
        'lab'       => 'Laboratory',
        'pharmacy'  => 'Pharmacy',
        'radiology' => 'Radiology',
    ];

    // Typical Indian registrations each department is asked for, offered as datalist hints so the
    // number goes in under a name the auditor will recognise instead of a free-text guess.
    const LICENSE_HINTS = [
        'lab' => [
            'note' => 'A laboratory usually holds several at once — NABL accreditation, the Clinical Establishment registration and the local trade licence. Add one row for each.',
            'types' => ['NABL Accreditation', 'Clinical Establishment Registration', 'Shops & Establishment', 'Bio-Medical Waste Authorisation', 'Trade Licence'],
        ],
        'pharmacy' => [
            'note' => 'Retail and wholesale drug licences are issued as separate numbers (20B / 21B), so add a row for each one the pharmacy holds.',
            'types' => ['Drug Licence 20B (Retail)', 'Drug Licence 21B (Retail)', 'Drug Licence 20 (Wholesale)', 'Drug Licence 21 (Wholesale)', 'Pharmacist Registration', 'Narcotics Licence'],
        ],
        'radiology' => [
            'note' => 'Imaging carries registrations per statute and often per machine — AERB for each X-ray/CT unit, PCPNDT for ultrasound. Add one row per registration.',
            'types' => ['AERB Registration', 'PCPNDT Registration', 'Clinical Establishment Registration', 'Bio-Medical Waste Authorisation', 'Radiation Safety Officer Approval'],
        ],
    ];

    protected $fillable = [
        'store_id', 'department', 'display_name', 'address', 'city', 'state',
        'pincode', 'phone', 'email', 'gst_no',
    ];

    // Guarded table create — no migration files.
    public static function ensureTable(): void
    {
        if (!Schema::hasTable('hospital_department_profiles')) {
            DB::statement("CREATE TABLE IF NOT EXISTS `hospital_department_profiles` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NOT NULL,
                `department` VARCHAR(20) NOT NULL,
                `display_name` VARCHAR(190) NULL,
                `address` VARCHAR(500) NULL,
                `city` VARCHAR(100) NULL,
                `state` VARCHAR(100) NULL,
                `pincode` VARCHAR(20) NULL,
                `phone` VARCHAR(40) NULL,
                `email` VARCHAR(190) NULL,
                `gst_no` VARCHAR(30) NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `hdp_store_dept_uq` (`store_id`, `department`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public static function forDepartment($storeId, string $department): ?self
    {
        self::ensureTable();

        return self::where('store_id', $storeId)->where('department', $department)->first();
    }

    public function licenses()
    {
        return HospitalLicense::listFor($this->store_id, $this->department);
    }

    public function fullAddress(): string
    {
        $tail = collect([$this->city, $this->state, $this->pincode])->filter()->implode(', ');

        return collect([$this->address, $tail])->filter()->implode(', ');
    }

    /**
     * Letterhead block for a department's printed report.
     *
     * Anything the department has not filled in falls back to the store's own identity, so a
     * hospital that never opens these settings still prints a correct header — only the labs
     * and scan centres that run from a separate premises with their own GSTIN need to differ.
     */
    public static function letterhead($storeId, string $department, $store = null): array
    {
        $profile = self::forDepartment($storeId, $department);
        $address = $profile ? $profile->fullAddress() : '';

        return [
            'name'      => $profile?->display_name ?: ($store->name ?? (self::DEPARTMENTS[$department] ?? '')),
            'address'   => $address ?: ($store->address ?? ''),
            'phone'     => $profile?->phone ?: ($store->phone ?? ''),
            'email'     => $profile?->email ?: ($store->email ?? ''),
            'gst_no'    => $profile?->gst_no ?: '',
            'licenses'  => HospitalLicense::listFor($storeId, $department),
        ];
    }
}
