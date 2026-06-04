<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'patient_uid',
        'name',
        'dob',
        'gender',
        'blood_group',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'allergies',
        'photo',
        'status',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function medicalHistory()
    {
        return $this->hasOne(PatientMedicalHistory::class, 'patient_id');
    }

    public function documents()
    {
        return $this->hasMany(PatientDocument::class, 'patient_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    /**
     * Generate a globally-unique patient UID for the given store.
     * Uses per-store sequence but bumps the number if taken by another store.
     */
    public static function generateUid(int $storeId): string
    {
        $config    = \App\Models\StoreConfig::where('store_id', $storeId)->first();
        $prefix    = strtoupper($config?->patient_uid_prefix ?? 'P');
        $padding   = (int)($config?->patient_uid_padding ?? 5);
        $minSerial = (int)($config?->patient_uid_serial ?? 1);

        $lastUid = static::where('store_id', $storeId)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('patient_uid');

        $next = 1;
        if ($lastUid && preg_match('/(\d+)$/', $lastUid, $m)) {
            $next = (int)$m[1] + 1;
        }
        $next = max($next, $minSerial);

        // Loop until the candidate is not taken globally (handles cross-store collisions)
        do {
            $candidate = $prefix . '-' . str_pad($next, $padding, '0', STR_PAD_LEFT);
            if (!static::where('patient_uid', $candidate)->exists()) {
                return $candidate;
            }
            $next++;
        } while (true);
    }
}
