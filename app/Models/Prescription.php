<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'patient_id',
        'doctor_profile_id',
        'appointment_id',
        'service_request_id',
        'diagnosis',
        'notes',
        'follow_up_date',
        'is_finalized',
        // Which language the patient's copy should read in. Recorded per prescription, not per
        // hospital: a clinic in Hyderabad writes Telugu for one patient and Hindi for the next.
        'language',
        'created_by',
        'created_by_type',
    ];

    /**
     * English plus the twenty-two languages of the Eighth Schedule, shown in their own script.
     *
     * Codes are stored rather than names so a label can be reworded, or a translation layer added
     * later, without rewriting every row. Sorted by English name; English itself sits first
     * because it is the default and by far the most-picked.
     */
    const LANGUAGES = [
        'en' => 'English',
        'as' => 'Assamese — অসমীয়া',
        'bn' => 'Bengali — বাংলা',
        'brx' => 'Bodo — बड़ो',
        'doi' => 'Dogri — डोगरी',
        'gu' => 'Gujarati — ગુજરાતી',
        'hi' => 'Hindi — हिन्दी',
        'kn' => 'Kannada — ಕನ್ನಡ',
        'ks' => 'Kashmiri — کٲشُر',
        'kok' => 'Konkani — कोंकणी',
        'mai' => 'Maithili — मैथिली',
        'ml' => 'Malayalam — മലയാളം',
        'mni' => 'Manipuri — ꯃꯤꯇꯩꯂꯣꯟ',
        'mr' => 'Marathi — मराठी',
        'ne' => 'Nepali — नेपाली',
        'or' => 'Odia — ଓଡ଼ିଆ',
        'pa' => 'Punjabi — ਪੰਜਾਬੀ',
        'sa' => 'Sanskrit — संस्कृतम्',
        'sat' => 'Santali — ᱥᱟᱱᱛᱟᱲᱤ',
        'sd' => 'Sindhi — سنڌي',
        'ta' => 'Tamil — தமிழ்',
        'te' => 'Telugu — తెలుగు',
        'ur' => 'Urdu — اردو',
    ];

    /**
     * The languages this hospital has switched on, as code => label.
     *
     * A clinic in Coimbatore offers English and Tamil; making its doctors scroll past Bodo and
     * Santali on every prescription is the reason this is a setting at all. English is always in
     * the list — it is what the printed sheet falls back to for anything untranslated.
     */
    public static function enabledLanguages($storeId): array
    {
        $codes = [];

        if (Schema::hasTable('store_configs') && Schema::hasColumn('store_configs', 'rx_languages')) {
            $raw   = DB::table('store_configs')->where('store_id', $storeId)->value('rx_languages');
            $codes = json_decode((string) $raw, true) ?: [];
        }

        $codes = collect(is_array($codes) ? $codes : [])->prepend('en')->unique();

        return collect(self::LANGUAGES)
            ->filter(fn($label, $code) => $codes->contains($code))
            ->all();
    }

    protected $casts = [
        'follow_up_date' => 'date',
        'is_finalized'   => 'boolean',
        'created_by'     => 'integer',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
