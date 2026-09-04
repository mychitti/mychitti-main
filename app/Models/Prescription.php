<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class Prescription extends Model
{
    use HasFactory;

    /** Null until looked up — see hasVisitLink(). */
    protected static ?bool $hasVisitLink = null;

    protected $fillable = [
        'store_id',
        'patient_id',
        'doctor_profile_id',
        'appointment_id',
        'service_request_id',
        // Which consultation produced this sheet — see ensureVisitLink().
        'opd_visit_id',
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

    public function opdVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'opd_visit_id');
    }

    /**
     * The consultation this prescription was written at, recorded on the row itself.
     *
     * Until this column existed the link was inferred — the appointment, else the service
     * request, else "same doctor, same day" — and anything the inference missed vanished from
     * the visit that produced it and turned up only under Past Rx. Added in place, no migration
     * file (see CLAUDE.md).
     */
    public static function ensureVisitLink(): void
    {
        if (static::$hasVisitLink === true || !Schema::hasTable('prescriptions')) {
            return;
        }

        if (Schema::hasColumn('prescriptions', 'opd_visit_id')) {
            static::$hasVisitLink = true;
            return;
        }

        $after = Schema::hasColumn('prescriptions', 'service_request_id')
            ? ' AFTER `service_request_id`'
            : '';

        DB::statement("ALTER TABLE `prescriptions`
            ADD COLUMN `opd_visit_id` BIGINT UNSIGNED NULL{$after},
            ADD INDEX `rx_opd_visit` (`opd_visit_id`)");

        static::$hasVisitLink = true;
    }

    public static function hasVisitLink(): bool
    {
        if (static::$hasVisitLink === null) {
            static::$hasVisitLink = Schema::hasTable('prescriptions')
                && Schema::hasColumn('prescriptions', 'opd_visit_id');
        }

        return static::$hasVisitLink;
    }

    /**
     * The prescriptions belonging to one OPD visit.
     *
     * opd_visit_id answers it outright wherever it is set. The three older signals stay on as an
     * OR rather than the if/elseif ladder they were: a visit carrying an appointment id matched
     * on that alone, so a prescription written anywhere that does not pass one — the standalone
     * form, a sheet written the next morning — dropped off the visit even though it was for the
     * same patient and the same doctor. The loose date arm only ever claims a prescription tied
     * to nothing else, so it cannot pull another appointment's sheet onto this visit.
     */
    public function scopeForOpdVisit($query, $visit)
    {
        return $query->where('patient_id', $visit->patient_id)
            ->where(function ($q) use ($visit) {
                $arms = 0;

                if (static::hasVisitLink()) {
                    $q->orWhere('opd_visit_id', $visit->id);
                    $arms++;
                }
                if ($visit->appointment_id) {
                    $q->orWhere('appointment_id', $visit->appointment_id);
                    $arms++;
                }
                if ($visit->service_request_id) {
                    $q->orWhere('service_request_id', $visit->service_request_id);
                    $arms++;
                }
                if ($visit->doctor_profile_id) {
                    // Both the day the visit is registered for and the day its row was opened: a
                    // walk-in booked onto tomorrow's list, or a sheet written after midnight, is
                    // still that consultation's prescription.
                    $days = collect([$visit->visit_date, $visit->created_at])
                        ->filter()
                        ->map(fn($d) => Carbon::parse($d)->toDateString())
                        ->unique()
                        ->values();
                    if ($days->isEmpty()) {
                        $days = collect([today()->toDateString()]);
                    }

                    $q->orWhere(function ($u) use ($visit, $days) {
                        $u->whereNull('appointment_id')
                            ->whereNull('service_request_id')
                            ->when(static::hasVisitLink(), fn($w) => $w->whereNull('opd_visit_id'))
                            ->where('doctor_profile_id', $visit->doctor_profile_id)
                            ->where(function ($d) use ($days) {
                                foreach ($days as $day) {
                                    $d->orWhereDate('created_at', $day);
                                }
                            });
                    });
                    $arms++;
                }

                // A visit with nothing to match on at all — an empty group would hand back the
                // patient's entire history as this one visit's prescription.
                if (!$arms) {
                    $q->whereRaw('1 = 0');
                }
            });
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
