<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdClinicalTerm extends Model
{
    protected $fillable = ['store_id', 'type', 'name'];

    const TYPE_DIAGNOSIS = 'diagnosis';
    const TYPE_TREATMENT = 'treatment';

    // Seeded once per store on first use; the list then grows with whatever doctors type.
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

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Returns the store's dropdown list, seeding the defaults the first time it is asked for.
    public static function listFor(int $storeId, string $type)
    {
        $names = self::forStore($storeId)->ofType($type)->orderBy('name')->pluck('name');

        if ($names->isEmpty() && !empty(self::DEFAULTS[$type])) {
            $now  = now();
            $rows = array_map(fn($name) => [
                'store_id'   => $storeId,
                'type'       => $type,
                'name'       => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ], self::DEFAULTS[$type]);

            self::insertOrIgnore($rows);
            $names = self::forStore($storeId)->ofType($type)->orderBy('name')->pluck('name');
        }

        return $names;
    }

    // Any term a doctor types that isn't already on the list joins it for next time.
    public static function remember(int $storeId, string $type, array $names): void
    {
        foreach ($names as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            self::firstOrCreate(['store_id' => $storeId, 'type' => $type, 'name' => $name]);
        }
    }
}
