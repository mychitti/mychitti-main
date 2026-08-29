<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Work a clinic sends out to an external lab and gets back — a crown, a denture, a spectacle
 * lens, an ear mould, a limb brace.
 *
 * Distinct from LabOrder, which is pathology: a test that produces a result. This is fabrication.
 * It produces an object, it takes days or weeks, it moves through a fixed set of stages, and the
 * patient has to be told when to come back in. The two share the word "lab" and nothing else,
 * which is why they are separate tables rather than a status on one.
 *
 * What a piece of work is measured by depends entirely on the speciality — shade and tooth
 * numbers for a crown, sphere and axis for a lens, circumference for a brace — so the measurement
 * set is a per-category profile and the values land in one JSON column. Adding a speciality is a
 * PROFILES entry, not a migration.
 */
class OpdLabWork extends Model
{
    protected $fillable = [
        'store_id', 'patient_id', 'opd_visit_id', 'doctor_profile_id',
        'work_type', 'site', 'measurements',
        'lab_mode', 'lab_type', 'lab_vendor_id', 'lab_name', 'lab_phone', 'lab_address',
        'technician_id', 'technician_name', 'technician_phone',
        'handed_over_by', 'collected_by', 'delivered_by', 'received_by',
        'status', 'sent_on', 'expected_on', 'received_on', 'fitted_on',
        'amount', 'notes', 'remake_reason', 'last_notified_status', 'last_notified_at', 'vendor_notified_at',
    ];

    protected $casts = [
        'measurements'       => 'array',
        'sent_on'            => 'date',
        'expected_on'        => 'date',
        'received_on'        => 'date',
        'fitted_on'          => 'date',
        'amount'             => 'float',
        'last_notified_at'   => 'datetime',
        'vendor_notified_at' => 'datetime',
    ];

    /**
     * Where the work is actually done.
     *
     * A hospital with its own bench and a hospital that couriers everything to a lab across town
     * are running the same job through the same stages, but almost nothing else about them is the
     * same — one has a technician on the payroll, the other has an outside firm that has to be
     * told what to make, invoiced, and chased. So the mode decides which half of the form is
     * asked for, whether there is anyone outside the building to notify, and who the handover
     * confirmations go to.
     */
    const MODES = [
        'internal' => 'In-house lab',
        'external' => 'External lab',
    ];

    /**
     * The stages every piece of lab work moves through, in order.
     *
     * One ladder for all specialities: an impression is taken, it goes out, it is worked on, it
     * comes back, it is fitted. An optical lab and a dental lab genuinely do run the same shape,
     * and a shared ladder is what lets the list screen, the WhatsApp update and the activity log
     * be written once. Only the WORDS change per speciality — see PROFILES.
     */
    const STATUSES = [
        'impression', 'sent', 'in_progress', 'trial', 'ready', 'received', 'fitted', 'remake', 'cancelled',
        'discontinued',
    ];

    /**
     * Stages where the patient has something to do about it, so a paid-for message is worth
     * sending. Remake is here for the opposite reason to the rest: nothing is ready, and a
     * patient expecting their work back this week is the person most owed the news that it
     * has gone back to be made again.
     */
    const NOTIFY_STATUSES = ['trial', 'ready', 'received', 'fitted', 'remake'];

    /**
     * Stages that close a job out of the open-work count.
     *
     * Discontinued is not the same as cancelled and is kept apart from it deliberately: cancelled
     * is a decision somebody made about the work, discontinued is what happens when the patient
     * simply stops coming. A clinic reading its own register months later needs to tell those two
     * apart — one is a job it called off, the other is a patient it lost.
     */
    const CLOSED_STATUSES = ['fitted', 'cancelled', 'discontinued'];

    /** Colour for each stage: [text, background]. Shared across every speciality. */
    const STATUS_COLOURS = [
        'impression'  => ['#475569', '#f1f5f9'],
        'sent'        => ['#1e40af', '#dbeafe'],
        'in_progress' => ['#92400e', '#fef3c7'],
        'trial'       => ['#7c2d12', '#ffedd5'],
        'ready'       => ['#166534', '#dcfce7'],
        'received'    => ['#065f46', '#d1fae5'],
        'fitted'      => ['#3730a3', '#e0e7ff'],
        'remake'      => ['#991b1b', '#fee2e2'],
        'cancelled'   => ['#6b7280', '#f3f4f6'],
        'discontinued'=> ['#7c3aed', '#f3e8ff'],
    ];

    /**
     * What "lab work" means in each speciality — its name, its stage wording, the jobs it sends
     * out, and the measurements that describe one.
     *
     * `statuses` is keyed by the shared ladder above, so a profile only renames stages; it can
     * never invent one the rest of the system does not understand. `fields` is what gets written
     * into the measurements JSON — key, label and input shape.
     */
    const PROFILES = [
        'dental' => [
            'label' => 'Dental Lab',
            'unit'  => 'lab work',
            'site'  => ['label' => 'Tooth / FDI numbers', 'placeholder' => 'e.g. 16, 17, 46'],
            'types' => [
                'Crown', 'Bridge', 'Complete Denture', 'Partial Denture', 'Cast Partial',
                'Inlay / Onlay', 'Veneer', 'Post & Core', 'Implant Prosthesis',
                'Night Guard', 'Aligner', 'Retainer', 'Bite Block', 'Repair / Reline',
            ],
            'lab_types' => [
                'Ceramic Lab', 'Milling / CAD-CAM Centre', 'Denture Lab', 'Orthodontic Lab',
                'Implant Lab', 'Full-service Dental Lab',
            ],
            'statuses' => [
                'impression'  => 'Impression taken',
                'sent'        => 'Sent to lab',
                'in_progress' => 'Work in progress',
                'trial'       => 'Jaw / trial ready',
                'ready'       => 'Work ready at lab',
                'received'    => 'Received at clinic',
                'fitted'      => 'Fitted & delivered',
                'remake'      => 'Sent for remake',
                'cancelled'   => 'Cancelled',
                'discontinued' => 'Discontinued — patient stopped coming',
            ],
            'fields' => [
                'arch'      => ['label' => 'Arch',             'type' => 'select', 'options' => ['Upper', 'Lower', 'Both']],
                'shade'     => ['label' => 'Shade',            'type' => 'text',   'placeholder' => 'A2, B1'],
                'material'  => ['label' => 'Material',         'type' => 'text',   'placeholder' => 'Zirconia, PFM, Acrylic'],
                'occlusion' => ['label' => 'Occlusion / bite', 'type' => 'text',   'placeholder' => 'Class I, edge-to-edge'],
                'pontic'    => ['label' => 'Pontic design',    'type' => 'text',   'placeholder' => 'Ovate, modified ridge lap'],
                'units'     => ['label' => 'No. of units',     'type' => 'number', 'placeholder' => '3'],
            ],
        ],

        'orthopaedic' => [
            'label' => 'Orthotics & Prosthetics',
            'unit'  => 'appliance',
            'site'  => ['label' => 'Limb / side', 'placeholder' => 'e.g. Left below-knee'],
            'types' => [
                'Prosthetic Limb', 'Below-Knee Prosthesis', 'Above-Knee Prosthesis',
                'Knee Brace', 'Ankle-Foot Orthosis', 'Spinal Brace', 'Cervical Collar',
                'Wrist Splint', 'Custom Insole', 'Orthopaedic Footwear', 'Repair / Adjustment',
            ],
            'lab_types' => [
                'Prosthetics Workshop', 'Orthotics Workshop', 'Footwear / Insole Unit',
                'Full-service P&O Centre',
            ],
            'statuses' => [
                'impression'  => 'Cast / measurements taken',
                'sent'        => 'Sent to workshop',
                'in_progress' => 'Fabrication in progress',
                'trial'       => 'Trial fitting ready',
                'ready'       => 'Appliance ready',
                'received'    => 'Received at clinic',
                'fitted'      => 'Fitted & delivered',
                'remake'      => 'Sent for rework',
                'cancelled'   => 'Cancelled',
                'discontinued' => 'Discontinued — patient stopped coming',
            ],
            'fields' => [
                'side'           => ['label' => 'Side',               'type' => 'select', 'options' => ['Left', 'Right', 'Bilateral']],
                'length'         => ['label' => 'Length (cm)',        'type' => 'text'],
                'circumference'  => ['label' => 'Circumference (cm)', 'type' => 'text'],
                'material'       => ['label' => 'Material',           'type' => 'text', 'placeholder' => 'Polypropylene, carbon'],
                'weight_bearing' => ['label' => 'Weight bearing',     'type' => 'text'],
            ],
        ],

        'eye' => [
            'label' => 'Optical Lab',
            'unit'  => 'eyewear',
            'site'  => ['label' => 'Eye', 'placeholder' => 'Both / Right / Left'],
            'types' => [
                'Single Vision Lens', 'Bifocal Lens', 'Progressive Lens', 'Spectacles (Complete)',
                'Contact Lens', 'Prosthetic Eye', 'Frame Fitting', 'Lens Replacement', 'Repair',
            ],
            'lab_types' => [
                'Glazing / Edging Lab', 'Surfacing Lab', 'Contact Lens Lab',
                'Frame Service Centre', 'Ocular Prosthetics Lab',
            ],
            'statuses' => [
                'impression'  => 'Prescription / measurements taken',
                'sent'        => 'Sent to optical lab',
                'in_progress' => 'Glazing in progress',
                'trial'       => 'Trial lens ready',
                'ready'       => 'Ready at lab',
                'received'    => 'Received at clinic',
                'fitted'      => 'Collected by patient',
                'remake'      => 'Sent for remake',
                'cancelled'   => 'Cancelled',
                'discontinued' => 'Discontinued — patient stopped coming',
            ],
            'fields' => [
                'r_sph'   => ['label' => 'R Sph',  'type' => 'text'],
                'r_cyl'   => ['label' => 'R Cyl',  'type' => 'text'],
                'r_axis'  => ['label' => 'R Axis', 'type' => 'text'],
                'l_sph'   => ['label' => 'L Sph',  'type' => 'text'],
                'l_cyl'   => ['label' => 'L Cyl',  'type' => 'text'],
                'l_axis'  => ['label' => 'L Axis', 'type' => 'text'],
                'add'     => ['label' => 'Add',    'type' => 'text'],
                'pd'      => ['label' => 'PD (mm)', 'type' => 'text'],
                'frame'   => ['label' => 'Frame',  'type' => 'text'],
                'coating' => ['label' => 'Lens / coating', 'type' => 'text', 'placeholder' => 'AR, blue-cut, photochromic'],
            ],
        ],

        'ent' => [
            'label' => 'Hearing & ENT Lab',
            'unit'  => 'device',
            'site'  => ['label' => 'Ear', 'placeholder' => 'Both / Right / Left'],
            'types' => [
                'Custom Ear Mould', 'Hearing Aid (Custom)', 'Hearing Aid Repair',
                'Swim Plug', 'Noise Plug', 'Speech Prosthesis', 'Re-shell / Re-tube',
            ],
            'lab_types' => [
                'Ear Mould Lab', 'Hearing Aid Manufacturer', 'Hearing Aid Service Centre',
                'Shell / Re-shell Lab',
            ],
            'statuses' => [
                'impression'  => 'Ear impression taken',
                'sent'        => 'Sent to lab',
                'in_progress' => 'Fabrication in progress',
                'trial'       => 'Trial fitting ready',
                'ready'       => 'Device ready at lab',
                'received'    => 'Received at clinic',
                'fitted'      => 'Fitted & delivered',
                'remake'      => 'Sent for remake',
                'cancelled'   => 'Cancelled',
                'discontinued' => 'Discontinued — patient stopped coming',
            ],
            'fields' => [
                'side'       => ['label' => 'Side',        'type' => 'select', 'options' => ['Left', 'Right', 'Bilateral']],
                'mould_type' => ['label' => 'Mould type',  'type' => 'text', 'placeholder' => 'Full shell, canal, skeleton'],
                'vent'       => ['label' => 'Vent size',   'type' => 'text'],
                'material'   => ['label' => 'Material',    'type' => 'text', 'placeholder' => 'Soft silicone, hard acrylic'],
                'gain'       => ['label' => 'Target gain', 'type' => 'text'],
            ],
        ],

        // Anything else that switches the tab on by hand — a multi-speciality hospital with a
        // dental chair, a general hospital that orders braces. Deliberately plain: the specific
        // profiles above exist because someone asked for them, and guessing a field set for a
        // speciality nobody has described yet is how you end up with boxes nobody fills in.
        'default' => [
            'label' => 'Lab Work',
            'unit'  => 'lab work',
            'site'  => ['label' => 'Site / side', 'placeholder' => 'Where on the patient'],
            'types' => ['Appliance', 'Prosthesis', 'Custom Device', 'Repair / Adjustment'],
            'lab_types' => ['Fabrication Lab', 'Repair Workshop', 'Service Centre'],
            'statuses' => [
                'impression'  => 'Measurements taken',
                'sent'        => 'Sent to lab',
                'in_progress' => 'Work in progress',
                'trial'       => 'Trial ready',
                'ready'       => 'Ready at lab',
                'received'    => 'Received at clinic',
                'fitted'      => 'Fitted & delivered',
                'remake'      => 'Sent for remake',
                'cancelled'   => 'Cancelled',
                'discontinued' => 'Discontinued — patient stopped coming',
            ],
            'fields' => [
                'material'     => ['label' => 'Material',     'type' => 'text'],
                'measurements' => ['label' => 'Measurements', 'type' => 'text'],
            ],
        ],
    ];

    /**
     * Specialities that get the tab without anyone switching it on.
     *
     * These are the ones where sending work out to a lab IS the practice — a dental clinic with no
     * lab jobs open is unusual. Every other category can still switch it on in Hospital Settings;
     * it just isn't assumed for them, because a cardiology unit does not order crowns.
     */
    const AUTO_CATEGORIES = ['dental', 'orthopaedic', 'eye', 'ent'];

    public static function ensureSchema(): void
    {
        if (Schema::hasTable('opd_lab_works')) {
            static::ensureColumns();
            return;
        }

        DB::statement("CREATE TABLE `opd_lab_works` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT UNSIGNED NOT NULL,
            `patient_id` BIGINT UNSIGNED NOT NULL,
            `opd_visit_id` BIGINT UNSIGNED NULL,
            `doctor_profile_id` BIGINT UNSIGNED NULL,
            `work_type` VARCHAR(150) NOT NULL,
            `site` VARCHAR(190) NULL,
            `measurements` TEXT NULL,
            `lab_mode` VARCHAR(20) NOT NULL DEFAULT 'external',
            `lab_type` VARCHAR(120) NULL,
            `lab_vendor_id` BIGINT UNSIGNED NULL,
            `lab_name` VARCHAR(190) NULL,
            `lab_phone` VARCHAR(40) NULL,
            `lab_address` VARCHAR(255) NULL,
            `technician_id` BIGINT UNSIGNED NULL,
            `technician_name` VARCHAR(150) NULL,
            `technician_phone` VARCHAR(40) NULL,
            `handed_over_by` VARCHAR(120) NULL,
            `collected_by` VARCHAR(120) NULL,
            `delivered_by` VARCHAR(120) NULL,
            `received_by` VARCHAR(120) NULL,
            `status` VARCHAR(40) NOT NULL DEFAULT 'impression',
            `sent_on` DATE NULL,
            `expected_on` DATE NULL,
            `received_on` DATE NULL,
            `fitted_on` DATE NULL,
            `amount` DECIMAL(12,2) NULL,
            `notes` TEXT NULL,
            `last_notified_status` VARCHAR(40) NULL,
            `last_notified_at` TIMESTAMP NULL,
            `vendor_notified_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            KEY `olw_store_patient` (`store_id`, `patient_id`),
            KEY `olw_store_status` (`store_id`, `status`),
            KEY `olw_store_vendor` (`store_id`, `lab_vendor_id`),
            KEY `olw_visit` (`opd_visit_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Columns added after the table shipped, back-filled onto installs that already have it.
     *
     * Existing rows predate the internal/external split entirely, and every one of them was work
     * sent out — the tab had no other meaning — so 'external' is the right default rather than a
     * null that would make old jobs render as neither.
     */
    protected static function ensureColumns(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $columns = [
            'lab_mode'           => "VARCHAR(20) NOT NULL DEFAULT 'external' AFTER `measurements`",
            'lab_type'           => "VARCHAR(120) NULL AFTER `lab_mode`",
            'lab_vendor_id'      => "BIGINT UNSIGNED NULL AFTER `lab_type`",
            'lab_address'        => "VARCHAR(255) NULL AFTER `lab_phone`",
            'technician_id'      => "BIGINT UNSIGNED NULL AFTER `lab_address`",
            'technician_name'    => "VARCHAR(150) NULL AFTER `lab_address`",
            'technician_phone'   => "VARCHAR(40) NULL AFTER `technician_name`",
            'handed_over_by'     => "VARCHAR(120) NULL AFTER `technician_phone`",
            'collected_by'       => "VARCHAR(120) NULL AFTER `handed_over_by`",
            'delivered_by'       => "VARCHAR(120) NULL AFTER `collected_by`",
            'received_by'        => "VARCHAR(120) NULL AFTER `delivered_by`",
            'vendor_notified_at' => "TIMESTAMP NULL AFTER `last_notified_at`",
            // Why a job went back. Holds the most recent reason for the card to show; every one
            // of them is also written into the activity log, so a piece that has been remade
            // twice keeps both accounts rather than only the last.
            'remake_reason'      => "TEXT NULL AFTER `notes`",
        ];

        foreach ($columns as $column => $definition) {
            if (!Schema::hasColumn('opd_lab_works', $column)) {
                DB::statement("ALTER TABLE `opd_lab_works` ADD COLUMN `{$column}` {$definition}");
            }
        }
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(OpdVisit::class, 'opd_visit_id');
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }

    /**
     * The outside firm this job went to, as the store already knows them.
     *
     * A lab is a supplier the clinic already keeps — it invoices them, it has their number. So it
     * is a store customer of type 'vendor' rather than a second address book that would drift out
     * of step with the first. Nullable on purpose: a clinic that uses a lab once, or has not got
     * round to adding it, still gets to type a name and a number straight onto the job.
     */
    public function labVendor()
    {
        return $this->belongsTo(StoreCustomer::class, 'lab_vendor_id');
    }

    public function getIsInternalAttribute(): bool
    {
        return $this->lab_mode === 'internal';
    }

    public function getIsExternalAttribute(): bool
    {
        return $this->lab_mode !== 'internal';
    }

    /** Who is doing the work, in one phrase — the bench technician, or the lab it went out to. */
    public function labDisplayName(): string
    {
        if ($this->is_internal) {
            return trim((string) $this->technician_name) ?: 'In-house lab';
        }

        return trim((string) $this->lab_name) ?: 'External lab';
    }

    /**
     * The number a handover confirmation goes to.
     *
     * An in-house job confirms to the technician, an outside job to the lab — the person who
     * actually has the work in their hands either way. Blank is a normal answer: a clinic may
     * never have taken a number for its own bench, and nothing here is worth a failed send.
     */
    public function contactPhone(): ?string
    {
        $phone = $this->is_internal ? $this->technician_phone : $this->lab_phone;
        $phone = trim((string) $phone);

        return $phone !== '' ? $phone : null;
    }

    /**
     * The chain of custody, as label => value pairs, in the order things happen.
     *
     * Who handed a crown over and who carried it away is exactly the question asked when a job
     * goes missing between the clinic and the lab, and it is asked weeks later, by which time
     * nobody remembers. Only the halves that were actually filled in appear — a blank slot says
     * "not recorded", which is the truth, and is more use than an empty box labelled Collected by.
     */
    public function custodyPairs(): array
    {
        $pairs = [];

        foreach ([
            'Handed over by' => $this->handed_over_by,
            'Collected by'   => $this->collected_by,
            'Delivered by'   => $this->delivered_by,
            'Received by'    => $this->received_by,
        ] as $label => $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $pairs[$label] = $value;
            }
        }

        return $pairs;
    }

    /** The lab types this speciality offers, for the picker on the form. */
    public static function labTypesFor(?int $storeId = null): array
    {
        $profile = static::profileFor($storeId);

        return $profile['lab_types'] ?? static::PROFILES['default']['lab_types'];
    }

    /** The store's hospital category, or null when it has never chosen one. */
    public static function categoryFor(int $storeId): ?string
    {
        if (!Schema::hasTable('store_configs') || !Schema::hasColumn('store_configs', 'hospital_category')) {
            return null;
        }

        return DB::table('store_configs')->where('store_id', $storeId)->value('hospital_category');
    }

    /** The speciality profile this store works to, falling back to the plain one. */
    public static function profileFor(?int $storeId = null): array
    {
        $category = $storeId ? static::categoryFor($storeId) : null;

        return static::PROFILES[$category] ?? static::PROFILES['default'];
    }

    /** Whether this category is one the tab is assumed for. */
    public static function isAutoCategory(?string $category): bool
    {
        return $category !== null && in_array($category, static::AUTO_CATEGORIES, true);
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', static::CLOSED_STATUSES);
    }

    public function getIsOpenAttribute(): bool
    {
        return !in_array($this->status, static::CLOSED_STATUSES, true);
    }

    /** This stage in the store's own words. */
    public function statusLabel(?array $profile = null): string
    {
        $profile = $profile ?: static::profileFor((int) $this->store_id);

        return $profile['statuses'][$this->status]
            ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    /** "Crown — 16, 17": the one-line name for this job, used in lists and in the WhatsApp update. */
    public function title(): string
    {
        $title = trim((string) $this->work_type) ?: 'Lab work';
        $site  = trim((string) $this->site);

        return $site !== '' ? $title . ' — ' . $site : $title;
    }

    /**
     * The measurements as label => value, in the profile's field order, skipping anything blank.
     *
     * Reads the profile rather than the raw JSON keys so a renamed label reaches records saved
     * before the rename; anything in the JSON the profile no longer defines is still shown, under
     * its raw key, rather than silently vanishing out of a clinical record.
     */
    public function measurementPairs(?array $profile = null): array
    {
        $profile = $profile ?: static::profileFor((int) $this->store_id);
        $values  = (array) ($this->measurements ?? []);
        $pairs   = [];

        foreach ($profile['fields'] as $key => $field) {
            $value = trim((string) ($values[$key] ?? ''));
            if ($value !== '') {
                $pairs[$field['label']] = $value;
            }
        }

        foreach ($values as $key => $value) {
            $value = trim((string) $value);
            if ($value !== '' && !isset($profile['fields'][$key])) {
                $pairs[ucfirst(str_replace('_', ' ', $key))] = $value;
            }
        }

        return $pairs;
    }

    /**
     * Every measurement on one line — "Shade: A2, Material: Zirconia, Units: 3".
     *
     * This is what a lab is actually told to make, and it has to survive a WhatsApp parameter,
     * which Meta will not accept with newlines in it. Falls back to the notes, and then to a
     * dash, because a template parameter cannot be empty either.
     */
    public function specLine(?array $profile = null): string
    {
        $parts = [];
        foreach ($this->measurementPairs($profile) as $label => $value) {
            $parts[] = $label . ': ' . $value;
        }

        $line = $parts ? implode(', ', $parts) : (trim((string) $this->notes) ?: 'As discussed');

        // A job going back carries what was wrong with it. Folded into the spec rather than sent
        // as its own line because the message to the lab is an approved template with a fixed set
        // of variables — and a remake instruction without the complaint on it is the one thing
        // the lab cannot act on.
        if ($this->status === 'remake' && filled($this->remake_reason)) {
            $line .= ' — REMAKE: ' . trim((string) $this->remake_reason);
        }

        return $line;
    }
}
