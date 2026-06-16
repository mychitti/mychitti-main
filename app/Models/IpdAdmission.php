<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IpdAdmission extends Model
{
    protected $fillable = [
        'store_id', 'patient_id', 'bed_id', 'ward_id',
        'doctor_profile_id', 'admission_number', 'admission_date',
        'discharge_date', 'diagnosis', 'admission_reason',
        'discharge_summary', 'discharge_type', 'status',
        'admitted_by', 'discharged_by', 'daily_charge',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'discharge_date' => 'date',
        'daily_charge'   => 'float',
    ];

    const DISCHARGE_TYPES = [
        'normal'   => 'Normal Discharge',
        'lama'     => 'LAMA (Left Against Medical Advice)',
        'transfer' => 'Transfer to Another Facility',
        'death'    => 'Death',
        'absconded'=> 'Absconded',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function admittedBy()
    {
        return $this->belongsTo(VendorEmployee::class, 'admitted_by');
    }

    public function dischargedBy()
    {
        return $this->belongsTo(VendorEmployee::class, 'discharged_by');
    }

    public function nursingNotes()
    {
        return $this->hasMany(NursingNote::class, 'ipd_admission_id')->latest('recorded_at');
    }

    public function dietCharts()
    {
        return $this->hasMany(DietChart::class, 'ipd_admission_id')->latest('date');
    }

    // Multiple, re-assignable nurses per admission (pivot: ipd_admission_nurses).
    public function assignedNurses()
    {
        return $this->belongsToMany(NurseProfile::class, 'ipd_admission_nurses', 'ipd_admission_id', 'nurse_profile_id')
            ->withTimestamps();
    }

    // Guarded pivot table — no migration files.
    public static function ensureNurseAssignTable(): void
    {
        if (!Schema::hasTable('ipd_admission_nurses')) {
            DB::statement("CREATE TABLE IF NOT EXISTS `ipd_admission_nurses` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `ipd_admission_id` BIGINT UNSIGNED NOT NULL,
                `nurse_profile_id` BIGINT UNSIGNED NOT NULL,
                `store_id` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `ian_unique` (`ipd_admission_id`, `nurse_profile_id`),
                KEY `ian_nurse_idx` (`nurse_profile_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}
