<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One hospital's own additions to the clinical dropdowns, and the terms it has chosen to hide.
 *
 * The platform list lives in OpdTermCatalogue and is read, never copied — see listFor(). Rows
 * here are only ever the difference between that list and what this hospital actually wants:
 * a term a doctor typed that the catalogue does not carry (hidden = 0), or a catalogue term this
 * hospital does not want offered (hidden = 1).
 *
 * DEFAULTS is retained only so PruneCopiedOpdTerms can recognise the rows the old copy-on-first-use
 * design left behind. Nothing reads it to build a dropdown any more.
 */
class OpdClinicalTerm extends Model
{
    protected $fillable = ['store_id', 'type', 'name', 'hidden'];

    protected $casts = ['hidden' => 'boolean'];

    const TYPE_DIAGNOSIS = 'diagnosis';
    const TYPE_TREATMENT = 'treatment';
    // What the patient reports, as opposed to what the doctor concludes. Kept apart from
    // diagnosis on purpose: "frequent urination" is a complaint, "Type 2 Diabetes" is the finding
    // it leads to, and a hospital wants to count and group the two separately.
    const TYPE_COMPLAINT = 'complaint';

    /** Every type the catalogue and the store-level list understand. */
    const TYPES = [self::TYPE_COMPLAINT, self::TYPE_DIAGNOSIS, self::TYPE_TREATMENT];

    /**
     * The general-medicine list every store used to be seeded with. Kept as a fingerprint for the
     * prune command, not as a source of dropdown entries — that is OpdTermCatalogue's job now.
     */
    const DEFAULTS = [
        self::TYPE_DIAGNOSIS => [
            'Fever', 'Viral Fever', 'Typhoid', 'Malaria', 'Dengue',
            'Common Cold', 'Cough', 'Throat Infection', 'Tonsillitis', 'Sinusitis',
            'Bronchitis', 'Asthma', 'Pneumonia',
            'Gastritis', 'Acidity', 'Diarrhoea', 'Food Poisoning', 'Constipation',
            'Urinary Tract Infection', 'Kidney Stone',
            'Hypertension', 'Type 2 Diabetes', 'Anaemia', 'Thyroid Disorder',
            'Migraine', 'Headache', 'Vertigo',
            'Skin Allergy', 'Fungal Infection', 'Conjunctivitis',
            'Back Pain', 'Arthritis', 'Sprain / Strain', 'Injury / Wound',
            'General Weakness', 'Routine Check-Up',
        ],
        self::TYPE_TREATMENT => [
            'Oral Medication', 'Antibiotic Course', 'Antipyretic', 'Analgesic',
            'Antacid Therapy', 'Antihistamine', 'Bronchodilator / Inhaler',
            'IV Fluids', 'IV Antibiotics', 'Injection (IM)', 'Nebulisation',
            'ORS / Rehydration', 'Vitamin Supplements', 'Iron Supplements',
            'Dressing', 'Suturing', 'Plaster / Immobilisation', 'Physiotherapy',
            'Diet Advice', 'Lifestyle Modification', 'Rest Advised',
            'Lab Investigation Advised', 'Radiology Advised',
            'Referred to Specialist', 'Admission Advised', 'Follow-Up Review',
        ],
    ];

    /** The hidden column arrived after the table; added on first use like the rest of HMIS. */
    public static function ensureSchema(): void
    {
        if (Schema::hasTable('opd_clinical_terms') && !Schema::hasColumn('opd_clinical_terms', 'hidden')) {
            DB::statement("ALTER TABLE `opd_clinical_terms` ADD COLUMN `hidden` TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /** Which catalogue this store reads, from its Hospital Settings category. Null = shared only. */
    public static function categoryFor(int $storeId): ?string
    {
        if (!Schema::hasTable('store_configs') || !Schema::hasColumn('store_configs', 'hospital_category')) {
            return null;
        }

        return DB::table('store_configs')->where('store_id', $storeId)->value('hospital_category');
    }

    /**
     * The dropdown this store should see: the catalogue for its category, plus its own additions,
     * minus anything it has hidden.
     *
     * Resolved on every read rather than seeded, so an admin editing the catalogue reaches every
     * hospital immediately — including ones that have been live for months. Matching is
     * case-insensitive: a doctor who types "fever" must not be given a second entry alongside the
     * catalogue's "Fever".
     */
    public static function listFor(int $storeId, string $type)
    {
        static::ensureSchema();

        $rows = static::forStore($storeId)->ofType($type)->get(['name', 'hidden']);

        $hidden = $rows->where('hidden', true)
            ->pluck('name')
            ->mapWithKeys(fn($name) => [mb_strtolower(trim($name)) => true])
            ->all();

        $names = [];
        foreach (OpdTermCatalogue::namesFor(static::categoryFor($storeId), $type) as $name) {
            $key = mb_strtolower(trim($name));
            if (!isset($hidden[$key], $names[$key])) {
                $names[$key] = $name;
            }
        }

        foreach ($rows->where('hidden', false)->pluck('name') as $name) {
            $key = mb_strtolower(trim($name));
            if (!isset($hidden[$key])) {
                $names[$key] = $name;
            }
        }

        return collect(array_values($names))->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    /**
     * A term-picker field off a request, ready to store.
     *
     * Trimmed, de-duplicated, remembered for the store (so anything typed by hand joins the list),
     * and joined the way every term column in HMIS holds its list. Returns null for an empty pick
     * so the column reads as "not recorded" rather than as an empty string.
     */
    public static function absorb(int $storeId, string $type, $input): ?string
    {
        $terms = collect((array) $input)
            ->map(fn($term) => trim((string) $term))
            ->filter()
            ->unique()
            ->values();

        if ($terms->isEmpty()) {
            return null;
        }

        static::remember($storeId, $type, $terms->all());

        return $terms->implode(', ');
    }

    /**
     * Any term a doctor types that the catalogue does not already carry joins this store's list.
     *
     * A term the catalogue already has is not stored again — that would put a per-store copy
     * beside the platform one and undo the whole point of reading the catalogue live. Typing a
     * term the hospital had hidden un-hides it: using it is a clearer answer than the old setting.
     */
    public static function remember(int $storeId, string $type, array $names): void
    {
        static::ensureSchema();

        $catalogue = collect(OpdTermCatalogue::namesFor(static::categoryFor($storeId), $type))
            ->mapWithKeys(fn($name) => [mb_strtolower(trim($name)) => true])
            ->all();

        foreach ($names as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $existing = static::forStore($storeId)->ofType($type)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if ($existing) {
                if ($existing->hidden) {
                    $existing->hidden = false;
                    $existing->save();
                }
                continue;
            }

            if (isset($catalogue[mb_strtolower($name)])) {
                continue;
            }

            static::create(['store_id' => $storeId, 'type' => $type, 'name' => $name, 'hidden' => false]);
        }
    }

    /**
     * Stop offering a term to this store. A catalogue term gets a hidden row of its own; one of
     * the store's own additions is simply removed, since there is no platform row underneath it
     * that would come back.
     */
    public static function hide(int $storeId, string $type, string $name): void
    {
        static::ensureSchema();

        $name = trim($name);
        if ($name === '') {
            return;
        }

        $existing = static::forStore($storeId)->ofType($type)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        $inCatalogue = collect(OpdTermCatalogue::namesFor(static::categoryFor($storeId), $type))
            ->contains(fn($c) => mb_strtolower(trim($c)) === mb_strtolower($name));

        if ($existing && !$inCatalogue) {
            $existing->delete();
            return;
        }

        if ($existing) {
            $existing->hidden = true;
            $existing->save();
            return;
        }

        if ($inCatalogue) {
            static::create(['store_id' => $storeId, 'type' => $type, 'name' => $name, 'hidden' => true]);
        }
    }

    /** Offer a hidden term again. */
    public static function unhide(int $storeId, string $type, string $name): void
    {
        static::ensureSchema();

        static::forStore($storeId)->ofType($type)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->where('hidden', true)
            ->delete();
    }

    /** Names this store has hidden, lowercased, for a settings screen to mark. */
    public static function hiddenNames(int $storeId, string $type): array
    {
        static::ensureSchema();

        return static::forStore($storeId)->ofType($type)->where('hidden', true)
            ->pluck('name')->all();
    }
}
