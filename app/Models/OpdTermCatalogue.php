<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The platform's diagnosis and treatment lists, per hospital category, owned by admin.
 *
 * Read at the moment a consultation screen asks for its dropdown — never copied into a store.
 * The previous design seeded OpdClinicalTerm::DEFAULTS into per-store rows on first use, which
 * meant an edit here could never reach a hospital already live, and every hospital got the same
 * general-medicine list whatever it actually practised.
 *
 * CATEGORY_COMMON holds what every category needs (Fever, Routine Check-Up, Follow-Up Review), so
 * a term shared by all sixteen categories is stored once rather than sixteen times.
 */
class OpdTermCatalogue extends Model
{
    protected $table = 'opd_term_catalogue';

    protected $fillable = ['category', 'type', 'name', 'sort_order', 'active'];

    protected $casts = [
        'sort_order' => 'integer',
        'active'     => 'boolean',
    ];

    /** Terms offered to every hospital, whatever its category. */
    const CATEGORY_COMMON = 'common';

    /**
     * Built here rather than by a migration, like the rest of HMIS.
     *
     * Retired terms are deactivated, never deleted — a visit recorded against a term the admin
     * later drops must still read back as what the doctor chose.
     */
    public static function ensureTable(): void
    {
        if (Schema::hasTable('opd_term_catalogue')) {
            static::seedNewTypes();
            return;
        }

        DB::statement("CREATE TABLE `opd_term_catalogue` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `category` VARCHAR(40) NOT NULL,
            `type` VARCHAR(20) NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `otc_cat_type_name` (`category`, `type`, `name`),
            KEY `otc_lookup` (`category`, `type`, `active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        static::seed();
    }

    /**
     * First cut: the shared list plus two categories.
     *
     * Only ever runs when the table is created, and every insert is ignore-on-duplicate, so an
     * admin who renames or retires a seeded term does not get it back on the next deploy.
     */
    public static function seed(): void
    {
        $now  = now();
        $rows = [];

        foreach (static::SEED as $category => $types) {
            foreach ($types as $type => $names) {
                foreach (array_values($names) as $i => $name) {
                    $rows[] = [
                        'category'   => $category,
                        'type'       => $type,
                        'name'       => $name,
                        'sort_order' => $i,
                        'active'     => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            static::insertOrIgnore($chunk);
        }
    }

    /**
     * Fill in a term type the catalogue has never carried.
     *
     * seed() only runs when the table is first created, which is right for the types that were
     * there from the start — an admin who retires a term must not get it back on the next deploy.
     * But a type added later (complaints) would otherwise stay empty forever on every hospital
     * that is already live. Seeded only while the type has no rows at all, so it happens once and
     * an admin's later edits to it are never overwritten.
     */
    private static function seedNewTypes(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        foreach ([OpdClinicalTerm::TYPE_COMPLAINT] as $type) {
            if (static::where('type', $type)->exists()) {
                continue;
            }

            $now  = now();
            $rows = [];

            foreach (static::SEED as $category => $types) {
                foreach (array_values($types[$type] ?? []) as $i => $name) {
                    $rows[] = [
                        'category'   => $category,
                        'type'       => $type,
                        'name'       => $name,
                        'sort_order' => $i,
                        'active'     => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                static::insertOrIgnore($chunk);
            }
        }
    }

    /** Active terms for a store's category, plus the shared list. */
    public static function namesFor(?string $category, string $type): array
    {
        static::ensureTable();

        $categories = array_values(array_filter([self::CATEGORY_COMMON, $category]));

        return static::whereIn('category', $categories)
            ->where('type', $type)
            ->where('active', 1)
            ->orderBy('sort_order')->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * The starting lists. `common` is deliberately the general-practice set that was previously
     * seeded into every store, so nothing a hospital already relies on disappears; the category
     * lists add to it rather than replacing it.
     */
    const SEED = [
        self::CATEGORY_COMMON => [
            OpdClinicalTerm::TYPE_DIAGNOSIS => [
                'Fever', 'Viral Fever', 'Common Cold', 'Cough', 'Throat Infection',
                'Headache', 'Body Ache', 'General Weakness', 'Routine Check-Up',
                'Hypertension', 'Type 2 Diabetes', 'Anaemia', 'Thyroid Disorder',
                'Injury / Wound', 'Skin Allergy',
            ],
            OpdClinicalTerm::TYPE_TREATMENT => [
                'Oral Medication', 'Antibiotic Course', 'Antipyretic', 'Analgesic',
                'Vitamin Supplements', 'Rest Advised', 'Diet Advice', 'Lifestyle Modification',
                'Lab Investigation Advised', 'Radiology Advised',
                'Referred to Specialist', 'Follow-Up Review',
            ],
            // What the patient says, in their words — the presentation, not the finding.
            OpdClinicalTerm::TYPE_COMPLAINT => [
                'Fever', 'Cough', 'Cold / Running Nose', 'Sore Throat', 'Headache',
                'Body Ache', 'Weakness / Fatigue', 'Loss of Appetite', 'Vomiting',
                'Nausea', 'Loose Motions', 'Stomach Pain', 'Chest Pain', 'Breathlessness',
                'Giddiness', 'Swelling', 'Itching', 'Rash', 'Burning Urination',
                'Frequent Urination', 'Increased Thirst', 'Increased Hunger',
                'Weight Loss', 'Hair Fall', 'Sleeplessness', 'Back Pain', 'Joint Pain',
            ],
        ],

        'general' => [
            OpdClinicalTerm::TYPE_DIAGNOSIS => [
                'Typhoid', 'Malaria', 'Dengue', 'Tonsillitis', 'Sinusitis', 'Bronchitis',
                'Asthma', 'Pneumonia', 'Gastritis', 'Acidity', 'Diarrhoea', 'Food Poisoning',
                'Constipation', 'Urinary Tract Infection', 'Kidney Stone', 'Migraine',
                'Vertigo', 'Fungal Infection', 'Conjunctivitis', 'Back Pain', 'Arthritis',
                'Sprain / Strain',
            ],
            OpdClinicalTerm::TYPE_TREATMENT => [
                'Antacid Therapy', 'Antihistamine', 'Bronchodilator / Inhaler', 'IV Fluids',
                'IV Antibiotics', 'Injection (IM)', 'Nebulisation', 'ORS / Rehydration',
                'Iron Supplements', 'Dressing', 'Suturing', 'Plaster / Immobilisation',
                'Physiotherapy', 'Admission Advised',
            ],
        ],

        'dental' => [
            OpdClinicalTerm::TYPE_DIAGNOSIS => [
                'Dental Caries', 'Deep Caries', 'Pulpitis', 'Periapical Abscess',
                'Gingivitis', 'Periodontitis', 'Calculus / Stains', 'Malocclusion',
                'Impacted Third Molar', 'Tooth Fracture', 'Attrition / Erosion',
                'Dentine Hypersensitivity', 'Edentulous Arch', 'Missing Tooth',
                'Oral Ulcer', 'Temporomandibular Joint Disorder', 'Discoloured Tooth',
            ],
            OpdClinicalTerm::TYPE_TREATMENT => [
                'Scaling & Polishing', 'Composite Restoration', 'GIC Restoration',
                'Root Canal Treatment', 'Pulpectomy', 'Crown (PFM)', 'Crown (Zirconia)',
                'Bridge', 'Extraction', 'Surgical Extraction', 'Dental Implant',
                'Complete Denture', 'Partial Denture', 'Orthodontic Braces', 'Clear Aligners',
                'Teeth Whitening', 'Fluoride Application', 'Pit & Fissure Sealant',
                'Flap Surgery', 'Oral Hygiene Instructions',
            ],
            OpdClinicalTerm::TYPE_COMPLAINT => [
                'Toothache', 'Sensitivity to Cold', 'Sensitivity to Hot', 'Bleeding Gums',
                'Swollen Gums', 'Bad Breath', 'Loose Tooth', 'Broken Tooth', 'Cavity',
                'Food Lodgement', 'Difficulty Chewing', 'Jaw Pain', 'Clicking Jaw',
                'Mouth Ulcer', 'Stained Teeth', 'Crooked Teeth', 'Missing Tooth',
                'Denture Not Fitting', 'Pain After Extraction', 'Swelling of Face',
            ],
        ],

        'neurology' => [
            OpdClinicalTerm::TYPE_DIAGNOSIS => [
                'Migraine with Aura', 'Tension Headache', 'Cluster Headache', 'Epilepsy',
                'Febrile Seizure', 'Ischaemic Stroke', 'Transient Ischaemic Attack',
                'Parkinson\'s Disease', 'Essential Tremor', 'Peripheral Neuropathy',
                'Diabetic Neuropathy', 'Bell\'s Palsy', 'Sciatica', 'Cervical Radiculopathy',
                'Multiple Sclerosis', 'Dementia', 'Benign Paroxysmal Positional Vertigo',
                'Carpal Tunnel Syndrome',
            ],
            OpdClinicalTerm::TYPE_TREATMENT => [
                'Anticonvulsant Therapy', 'Migraine Prophylaxis', 'Abortive Migraine Therapy',
                'Antiplatelet Therapy', 'Anticoagulation', 'Dopaminergic Therapy',
                'Neuropathic Pain Medication', 'Nerve Conduction Study Advised',
                'EEG Advised', 'MRI Brain Advised', 'CT Brain Advised',
                'Physiotherapy / Rehabilitation', 'Speech Therapy', 'Occupational Therapy',
                'Neurosurgery Referral',
            ],
        ],
    ];
}
