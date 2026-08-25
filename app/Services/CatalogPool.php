<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\CatalogSuggestion;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The shared item pool — one curated record per real-world product, adopted by any number of
 * stores.
 *
 * The pool owns identity only (what the thing IS: name, brand, strength, picture). The store's
 * own inventory_items row keeps everything the store owns — price, stock, expiry, unit. A store
 * row points back with inventory_items.catalog_item_id; nothing about the pool is required, so
 * items added before the pool existed, and items typed freehand today, keep working untouched.
 *
 * Domains keep it usable beyond medicines. 'pharmacy' is the first; lab tests, radiology scans,
 * books and packaged goods are the same shape. What differs per domain is how tightly a store's
 * copy stays bound to the pool row — see CatalogItem::LINK_MODES.
 */
class CatalogPool
{
    public const DOMAIN_PHARMACY = 'pharmacy';

    /** Units a strength can be written in, longest first so 'mcg' is not eaten by 'g'. */
    private const STRENGTH_UNITS = ['mcg', 'mg', 'gm', 'ml', 'iu', 'mu', 'g', 'l', '%'];

    /**
     * Dosage forms, offered in the order a pharmacy actually stocks them.
     *
     * Form is part of the identity, not a label: Augmentin 625 tablet and Augmentin 625 syrup are
     * two products, and merging them would be worse than duplicating them — once stores are
     * linked to a merged row it cannot be split cleanly.
     */
    public const FORMS = [
        'Tablet', 'Capsule', 'Syrup', 'Suspension', 'Injection', 'Infusion', 'Drops',
        'Eye Drops', 'Ear Drops', 'Nasal Spray', 'Inhaler', 'Respule', 'Cream', 'Ointment',
        'Gel', 'Lotion', 'Powder', 'Granules', 'Sachet', 'Suppository', 'Patch', 'Solution',
        'Spray', 'Mouthwash', 'Soap', 'Shampoo', 'Pessary', 'Enema', 'Kit',
    ];

    /**
     * What pharmacy staff and supplier sheets actually type, mapped to the canonical form.
     * Packaging words (vial, ampoule) map to Injection: they describe the same dosage form.
     */
    private const FORM_ALIASES = [
        'tab' => 'Tablet', 'tabs' => 'Tablet', 'tablets' => 'Tablet', 'tb' => 'Tablet',
        'cap' => 'Capsule', 'caps' => 'Capsule', 'capsules' => 'Capsule',
        'syp' => 'Syrup', 'syr' => 'Syrup', 'syrups' => 'Syrup', 'liquid' => 'Syrup',
        'susp' => 'Suspension', 'suspensions' => 'Suspension', 'dry syrup' => 'Suspension',
        'inj' => 'Injection', 'injections' => 'Injection', 'vial' => 'Injection',
        'ampoule' => 'Injection', 'ampule' => 'Injection', 'amp' => 'Injection',
        'inf' => 'Infusion', 'iv' => 'Infusion', 'infusions' => 'Infusion',
        'drop' => 'Drops', 'eye drop' => 'Eye Drops', 'eyedrops' => 'Eye Drops',
        'ear drop' => 'Ear Drops', 'eardrops' => 'Ear Drops',
        'nasal drops' => 'Nasal Spray', 'nasal' => 'Nasal Spray',
        'inh' => 'Inhaler', 'inhalers' => 'Inhaler', 'rotacap' => 'Inhaler', 'mdi' => 'Inhaler',
        'respules' => 'Respule', 'nebuliser' => 'Respule', 'nebulizer' => 'Respule',
        'crm' => 'Cream', 'creams' => 'Cream',
        'oint' => 'Ointment', 'ointments' => 'Ointment',
        'gels' => 'Gel', 'lotions' => 'Lotion',
        'pdr' => 'Powder', 'pwd' => 'Powder', 'powders' => 'Powder',
        'sach' => 'Sachet', 'sachets' => 'Sachet', 'gran' => 'Granules',
        'sup' => 'Suppository', 'supp' => 'Suppository', 'suppositories' => 'Suppository',
        'patches' => 'Patch', 'sol' => 'Solution', 'soln' => 'Solution', 'solutions' => 'Solution',
        'spr' => 'Spray', 'sprays' => 'Spray', 'kits' => 'Kit',
        'lot' => 'Lotion', 'mw' => 'Mouthwash', 'sus' => 'Suspension', 'pess' => 'Pessary',
    ];

    /**
     * The unit a pharmacy normally counts each dosage form in.
     *
     * Only a starting point for the Add-from-Catalog rows — a hospital that buys tablets by the
     * box rather than the strip changes it on the row. Getting the default right matters because
     * stock, reorder level and MRP are all read in this unit: "40" means forty of these.
     */
    public const UNIT_BY_FORM = [
        'Tablet' => 'Strip', 'Capsule' => 'Strip',
        'Syrup' => 'Bottle', 'Suspension' => 'Bottle', 'Solution' => 'Bottle',
        'Lotion' => 'Bottle', 'Shampoo' => 'Bottle', 'Mouthwash' => 'Bottle',
        'Spray' => 'Bottle', 'Nasal Spray' => 'Bottle', 'Infusion' => 'Bottle',
        'Drops' => 'Bottle', 'Eye Drops' => 'Bottle', 'Ear Drops' => 'Bottle',
        'Injection' => 'Vial', 'Respule' => 'Respule', 'Inhaler' => 'Piece',
        'Cream' => 'Tube', 'Ointment' => 'Tube', 'Gel' => 'Tube',
        'Powder' => 'Sachet', 'Granules' => 'Sachet', 'Sachet' => 'Sachet',
        'Suppository' => 'Piece', 'Pessary' => 'Piece', 'Patch' => 'Piece',
        'Enema' => 'Piece', 'Soap' => 'Piece', 'Kit' => 'Kit',
    ];

    public static function defaultUnitFor(?string $form): string
    {
        return self::UNIT_BY_FORM[self::normaliseForm($form) ?? ''] ?? 'Unit';
    }

    // ── Schema ──────────────────────────────────────────────────────────────

    /**
     * Tables are created on first use, the way the rest of HMIS does it — no migration files.
     */
    public static function ensureSchema(): void
    {
        if (!Schema::hasTable('catalog_items')) {
            DB::statement("
                CREATE TABLE `catalog_items` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `domain` VARCHAR(30) NOT NULL DEFAULT 'pharmacy',
                    `name` VARCHAR(200) NOT NULL,
                    `brand` VARCHAR(150) NULL,
                    `strength_text` VARCHAR(100) NULL,
                    `strength_value` DECIMAL(12,3) NULL,
                    `strength_unit` VARCHAR(20) NULL,
                    `form` VARCHAR(50) NULL,
                    `image` VARCHAR(255) NULL,
                    `normalized_key` VARCHAR(255) NOT NULL,
                    `link_mode` VARCHAR(20) NOT NULL DEFAULT 'strict',
                    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                    `merged_into_id` BIGINT UNSIGNED NULL,
                    `source` VARCHAR(30) NULL,
                    `usage_count` INT NOT NULL DEFAULT 0,
                    `created_at` TIMESTAMP NULL,
                    `updated_at` TIMESTAMP NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `catalog_items_key_unique` (`normalized_key`),
                    KEY `catalog_items_domain_name_idx` (`domain`, `name`),
                    KEY `catalog_items_domain_status_idx` (`domain`, `status`),
                    KEY `catalog_items_brand_idx` (`brand`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('catalog_suggestions')) {
            DB::statement("
                CREATE TABLE `catalog_suggestions` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `domain` VARCHAR(30) NOT NULL DEFAULT 'pharmacy',
                    `store_id` BIGINT UNSIGNED NULL,
                    `inventory_item_id` BIGINT UNSIGNED NULL,
                    `raw_name` VARCHAR(200) NOT NULL,
                    `raw_brand` VARCHAR(150) NULL,
                    `raw_strength` VARCHAR(100) NULL,
                    `raw_form` VARCHAR(50) NULL,
                    `normalized_key` VARCHAR(255) NOT NULL,
                    `request_count` INT NOT NULL DEFAULT 1,
                    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                    `match_catalog_item_id` BIGINT UNSIGNED NULL,
                    `catalog_item_id` BIGINT UNSIGNED NULL,
                    `ai_verdict` VARCHAR(20) NULL,
                    `ai_confidence` DECIMAL(4,3) NULL,
                    `ai_reason` VARCHAR(500) NULL,
                    `ai_checked_at` TIMESTAMP NULL,
                    `reviewed_by` BIGINT UNSIGNED NULL,
                    `reviewed_at` TIMESTAMP NULL,
                    `created_at` TIMESTAMP NULL,
                    `updated_at` TIMESTAMP NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `catalog_suggestions_key_unique` (`normalized_key`),
                    KEY `catalog_suggestions_status_idx` (`status`, `domain`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasColumn('catalog_suggestions', 'raw_form')) {
            DB::statement('ALTER TABLE `catalog_suggestions` ADD COLUMN `raw_form` VARCHAR(50) NULL AFTER `raw_strength`');
        }

        if (!Schema::hasColumn('inventory_items', 'catalog_item_id')) {
            DB::statement('ALTER TABLE `inventory_items` ADD COLUMN `catalog_item_id` BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE `inventory_items` ADD INDEX `inventory_items_catalog_idx` (`catalog_item_id`)');
        }
    }

    // ── Normalising ─────────────────────────────────────────────────────────

    /**
     * The dedupe key. Two rows that normalise to the same string ARE the same product, and the
     * unique index makes that impossible to violate even when two admins approve the same
     * suggestion at the same moment.
     */
    public static function key(string $domain, ?string $name, ?string $brand = null, ?string $strength = null, ?string $form = null): string
    {
        return implode('|', [
            $domain,
            self::normaliseText($name),
            self::normaliseText($brand),
            self::normaliseStrength($strength),
            self::normaliseText(self::normaliseForm($form)),
        ]);
    }

    public static function normaliseText(?string $v): string
    {
        $v = mb_strtolower(trim((string) $v));
        $v = str_replace(['-', '_', '/', '\\', '.', ',', "'", '"', '(', ')', '[', ']'], ' ', $v);
        return trim(preg_replace('/\s+/', ' ', $v));
    }

    /**
     * "40 MG" / "40mg" / "40 m.g." all collapse to "40mg", and a combination is sorted so
     * "500mg+125mg" and "125mg + 500mg" are one product rather than two.
     */
    public static function normaliseStrength(?string $v): string
    {
        $v = mb_strtolower(trim((string) $v));
        if ($v === '') {
            return '';
        }

        // A decimal point is significant — 2.5 mg and 25 mg are different products — so only
        // stray dots go ("m.g." → "mg"), never one sitting between digits.
        $v = preg_replace('/(?<!\d)\.(?!\d)/', '', $v);
        $v = str_replace([' ', '-'], ['', '+'], $v);
        $parts = array_filter(array_map('trim', preg_split('/[+\/]/', $v)));
        sort($parts);

        return implode('+', $parts);
    }

    /**
     * Choose between a form given in its own column and one found inside the name.
     *
     * The column normally wins, but not when it is a packaging word the canonical list does not
     * know ("1.7 ml cartridge") and the name states a real dosage form ("Articaine 4% Injection").
     */
    public static function pickForm(?string $explicit, ?string $fromName): ?string
    {
        $explicit = self::normaliseForm($explicit);
        $fromName = self::normaliseForm($fromName);

        if ($explicit && in_array($explicit, self::FORMS, true)) {
            return $explicit;
        }

        return $fromName ?: $explicit;
    }

    /**
     * Map whatever was typed onto one of the canonical forms; unrecognised text is title-cased
     * and kept rather than dropped, so a form we have not seen before still separates products.
     */
    public static function normaliseForm(?string $v): ?string
    {
        $v = mb_strtolower(trim((string) $v));
        if ($v === '') {
            return null;
        }

        $v = trim(preg_replace('/\s+/', ' ', str_replace(['.', '-', '/'], ' ', $v)));

        // Supplier sheets routinely put the pack in the dosage-form column — "10 tablets",
        // "1 x 10 tab", "15 tablets". The pack size is the store's business, not the product's
        // identity, so the count is dropped and only the form is kept.
        $v = trim(preg_replace('/^\d+\s*(x\s*\d+\s*)?/i', '', $v));
        if ($v === '') {
            return null;
        }

        if (isset(self::FORM_ALIASES[$v])) {
            return self::FORM_ALIASES[$v];
        }

        foreach (self::FORMS as $form) {
            if ($v === mb_strtolower($form)) {
                return $form;
            }
        }

        // Pack descriptions carry the form as their last word — "20 ml vial", "1.7 ml cartridge",
        // "10 capsules". Fall back to that word, unless it is only a measurement ("30 g", "60 ml"),
        // which describes the pack and says nothing about the form.
        if (str_contains($v, ' ')) {
            $last = self::normaliseForm(substr($v, strrpos($v, ' ') + 1));
            if ($last) {
                return $last;
            }
            return null;
        }

        if (in_array(rtrim($v, 's'), self::STRENGTH_UNITS, true) || is_numeric($v)) {
            return null;
        }

        // "tablets" → "tablet" → Tablet, without a plural entry for every single form.
        $singular = rtrim($v, 's');
        if (isset(self::FORM_ALIASES[$singular])) {
            return self::FORM_ALIASES[$singular];
        }
        foreach (self::FORMS as $form) {
            if ($singular === rtrim(mb_strtolower($form), 's')) {
                return $form;
            }
        }

        return ucwords($v);
    }

    /**
     * Pull a trailing dosage form off a name: "Paracetamol 500 mg Tablet" → ["Paracetamol 500 mg",
     * "Tablet"].
     *
     * Catalogue sheets almost always write the form into the name, and leaving it there would
     * make the name itself the thing that differs — the strength could never be parsed off the
     * end, and "Paracetamol 500 mg Tablet" and "Paracetamol 500 mg" would become two products.
     */
    public static function splitForm(?string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return [null, null];
        }

        $words = array_merge(
            array_map('mb_strtolower', self::FORMS),
            array_keys(self::FORM_ALIASES)
        );
        // Longest first so "eye drops" wins over "drops".
        usort($words, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($words as $word) {
            $pattern = '/\s+' . preg_quote($word, '/') . 's?\s*$/i';
            if (preg_match($pattern, $text)) {
                $stripped = trim(preg_replace($pattern, '', $text));
                if ($stripped !== '') {
                    return [$stripped, self::normaliseForm($word)];
                }
            }
        }

        return [$text, null];
    }

    /**
     * Pull a trailing strength off free text: "Pantoprazole 40 mg" → ["Pantoprazole", "40 mg"].
     *
     * Pharmacists type the whole thing into one box, so matching that against a pool split into
     * name and strength means separating them first. Anything that does not end in a recognised
     * strength comes back with a null strength and the text untouched.
     */
    public static function splitStrength(?string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return [null, null];
        }

        $units = implode('|', array_map('preg_quote', self::STRENGTH_UNITS));
        $token = '\d+(?:\.\d+)?\s*(?:' . $units . ')';

        if (preg_match('/^(.*?)\s*((?:' . $token . ')(?:\s*[\+\/]\s*(?:' . $token . '))*)\s*$/i', $text, $m)) {
            $name = trim($m[1]);
            return $name === '' ? [$text, null] : [$name, trim($m[2])];
        }

        return [$text, null];
    }

    /** Parsed strength for display/sorting. Combinations keep the text and get no number. */
    public static function parseStrength(?string $strength): array
    {
        $strength = trim((string) $strength);
        if ($strength === '' || preg_match('/[+\/]/', $strength)) {
            return [null, null];
        }

        $units = implode('|', array_map('preg_quote', self::STRENGTH_UNITS));
        if (preg_match('/^(\d+(?:\.\d+)?)\s*(' . $units . ')$/i', str_replace(' ', '', $strength), $m)) {
            return [(float) $m[1], mb_strtolower($m[2])];
        }

        return [null, null];
    }

    // ── Reading ─────────────────────────────────────────────────────────────

    /**
     * Type-ahead over the pool. Ranked so an exact prefix beats a match buried mid-string,
     * because a pharmacist typing "pan" wants Pantoprazole before Sompraz-Pan.
     */
    public static function search(string $term, string $domain = self::DOMAIN_PHARMACY, int $limit = 20)
    {
        self::ensureSchema();

        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return collect();
        }

        [$namePart] = self::splitStrength($term);
        $like  = '%' . $term . '%';
        $start = $term . '%';

        return CatalogItem::query()
            ->where('domain', $domain)
            ->where('status', CatalogItem::STATUS_ACTIVE)
            ->where(fn($q) => $q
                ->where('name', 'like', $like)
                ->orWhere('brand', 'like', $like)
                ->orWhere('name', 'like', '%' . $namePart . '%'))
            ->orderByRaw('CASE WHEN `name` LIKE ? THEN 0 WHEN `brand` LIKE ? THEN 1 ELSE 2 END', [$start, $start])
            ->orderByDesc('usage_count')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /** An exact pool hit for this product, following a merge pointer if one was set. */
    public static function match(string $domain, ?string $name, ?string $brand = null, ?string $strength = null, ?string $form = null): ?CatalogItem
    {
        self::ensureSchema();

        $item = CatalogItem::where('normalized_key', self::key($domain, $name, $brand, $strength, $form))->first();

        return $item?->resolved();
    }

    /**
     * What a store typed, matched against the pool.
     *
     * Tries the full key first, then the same product without the brand — a hospital that types
     * "Pantoprazole 40 mg" and leaves brand empty should still land on the pooled record rather
     * than filing a suggestion for something that is already there.
     */
    public static function resolve(string $domain, string $rawName, ?string $brand = null, ?string $strength = null, ?string $form = null): ?CatalogItem
    {
        [$bareName, $nameForm]   = self::splitForm($rawName);
        [$name, $parsedStrength] = self::splitStrength($bareName);
        $strength = $strength ?: $parsedStrength;
        $form     = self::pickForm($form, $nameForm);

        return self::match($domain, $name, $brand, $strength, $form)
            ?? self::match($domain, $name, null, $strength, $form)
            ?? self::match($domain, $rawName, $brand, null, $form);
    }

    /** Near-misses for a candidate, used to ask the AI (and the admin) "is this one of these?". */
    public static function candidates(string $domain, string $name, ?string $strength = null, int $limit = 5, ?string $form = null)
    {
        self::ensureSchema();

        $name = self::normaliseText($name);
        if ($name === '') {
            return collect();
        }

        $head = mb_substr($name, 0, max(4, (int) floor(mb_strlen($name) * 0.6)));

        return CatalogItem::where('domain', $domain)
            ->where('status', CatalogItem::STATUS_ACTIVE)
            ->where('name', 'like', $head . '%')
            ->when($strength, fn($q) => $q->where('strength_text', 'like', '%' . trim($strength) . '%'))
            ->when(self::normaliseForm($form), fn($q, $f) => $q->where('form', $f))
            ->limit($limit)
            ->get();
    }

    // ── Writing ─────────────────────────────────────────────────────────────

    /**
     * Add to the pool, or return what is already there.
     *
     * upsert on the unique key rather than select-then-insert: two admins approving the same
     * suggestion in the same second must not produce two rows.
     */
    public static function upsert(array $data, string $domain = self::DOMAIN_PHARMACY, string $source = 'admin'): CatalogItem
    {
        self::ensureSchema();

        // "Paracetamol 500 mg Tablet" carries all three fields in one string; peel the form off
        // first so the strength is left at the end where splitStrength can find it.
        [$bareName, $nameForm] = self::splitForm($data['name'] ?? '');
        [$name, $splitStrength] = self::splitStrength($bareName);
        $strength = trim((string) ($data['strength_text'] ?? '')) ?: $splitStrength;
        [$value, $unit] = self::parseStrength($strength);

        // An explicit column wins over what was buried in the name, but the name is the fallback.
        $form = self::pickForm($data['form'] ?? null, $nameForm);
        $key  = self::key($domain, $name, $data['brand'] ?? null, $strength, $form);

        $existing = CatalogItem::where('normalized_key', $key)->first();
        if ($existing) {
            // An import that finally carries a picture should fill a gap, never blank one.
            if (!empty($data['image']) && !$existing->image) {
                $existing->image = $data['image'];
                $existing->save();
            }
            return $existing;
        }

        return CatalogItem::create([
            'domain'         => $domain,
            'name'           => $name,
            'brand'          => $data['brand'] ?? null,
            'strength_text'  => $strength ?: null,
            'strength_value' => $value,
            'strength_unit'  => $unit,
            'form'           => $form,
            'image'          => $data['image'] ?? null,
            'normalized_key' => $key,
            'link_mode'      => $data['link_mode'] ?? CatalogItem::LINK_STRICT,
            'status'         => CatalogItem::STATUS_ACTIVE,
            'source'         => $source,
        ]);
    }

    /**
     * File what a store typed but the pool does not have.
     *
     * One row per distinct product no matter how many stores ask for it — the counter is what
     * tells an admin which gaps are worth filling first. Never throws: a pharmacist saving a
     * medicine must not see an error because the suggestion queue had a hiccup.
     */
    public static function suggest(array $data, ?int $storeId, ?int $inventoryItemId = null, string $domain = self::DOMAIN_PHARMACY): ?CatalogSuggestion
    {
        try {
            self::ensureSchema();

            [$bareName, $nameForm]  = self::splitForm($data['name'] ?? '');
            [$name, $splitStrength] = self::splitStrength($bareName);
            $strength = trim((string) ($data['strength'] ?? '')) ?: $splitStrength;
            $brand    = $data['brand'] ?? null;
            $form     = self::pickForm($data['form'] ?? null, $nameForm);

            if (self::normaliseText($name) === '') {
                return null;
            }

            if (self::resolve($domain, $name, $brand, $strength, $form)) {
                return null;
            }

            $key = self::key($domain, $name, $brand, $strength, $form);

            $suggestion = CatalogSuggestion::where('normalized_key', $key)->first();
            if ($suggestion) {
                $suggestion->increment('request_count');
                return $suggestion;
            }

            return CatalogSuggestion::create([
                'domain'            => $domain,
                'store_id'          => $storeId,
                'inventory_item_id' => $inventoryItemId,
                'raw_name'          => $name,
                'raw_brand'         => $brand,
                'raw_strength'      => $strength ?: null,
                'raw_form'          => $form,
                'normalized_key'    => $key,
                'status'            => CatalogSuggestion::STATUS_PENDING,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * Does this store already stock this pooled product?
     *
     * The whole point of the pool is one record per product, so a store adopting the same row
     * twice would put the duplication back — in the one place it actually hurts, their own shelf.
     */
    public static function stockedItem(int $storeId, int $catalogItemId): ?InventoryItem
    {
        self::ensureSchema();

        return InventoryItem::where('store_id', $storeId)
            ->where('catalog_item_id', $catalogItemId)
            ->first();
    }

    /**
     * Push a corrected pool record out to every store that adopted it.
     *
     * Only for link_mode 'strict' — where the pool owns identity — and only onto rows the store
     * has not renamed: a hospital that calls it something of their own keeps their wording. The
     * picture needs no propagation at all, because a store row with no image of its own already
     * reads through to the pool (InventoryItem::display_image_url).
     *
     * @return int rows updated
     */
    public static function propagate(CatalogItem $item, array $original): int
    {
        self::ensureSchema();

        if ($item->link_mode !== CatalogItem::LINK_STRICT) {
            return 0;
        }

        $oldLabel = trim(implode(' ', array_filter([
            $original['name'] ?? null, $original['strength_text'] ?? null, $original['form'] ?? null,
        ])));
        $oldBrand = $original['brand'] ?? null;

        if ($oldLabel === '' || $oldLabel === $item->label) {
            return 0;
        }

        return InventoryItem::where('catalog_item_id', $item->id)
            ->where('item_name', $oldLabel)
            ->update([
                'item_name' => $item->label,
                'brand'     => $item->brand ?: $oldBrand,
            ]);
    }

    /**
     * Point a store's item at a pool row and copy across what the pool owns.
     *
     * The store's own picture always wins — image is only filled when the store has none, which
     * is also what makes "clear my image" fall back to the pooled one instead of going blank.
     */
    public static function link(InventoryItem $item, CatalogItem $catalogItem): void
    {
        self::ensureSchema();

        $catalogItem = $catalogItem->resolved();

        $item->catalog_item_id = $catalogItem->id;
        if (!$item->brand && $catalogItem->brand) {
            $item->brand = $catalogItem->brand;
        }
        $item->save();

        $catalogItem->increment('usage_count');
    }

    /**
     * Close a suggestion off: link the store row that raised it, and stamp who decided.
     *
     * Shared by the admin's approve/merge/reject buttons and by the AI auto-approval, so both
     * paths leave a suggestion in exactly the same shape. $reviewedBy is null when the decision
     * was the model's — that null is what tells the two apart afterwards.
     */
    public static function settle(CatalogSuggestion $suggestion, string $status, ?CatalogItem $item, ?int $reviewedBy = null): void
    {
        self::ensureSchema();

        if ($item && $suggestion->inventory_item_id) {
            $storeItem = InventoryItem::find($suggestion->inventory_item_id);
            if ($storeItem && !$storeItem->catalog_item_id) {
                self::link($storeItem, $item);
            }
        }

        $suggestion->status          = $status;
        $suggestion->catalog_item_id = $item?->id;
        $suggestion->reviewed_by     = $reviewedBy;
        $suggestion->reviewed_at     = now();
        $suggestion->save();
    }
}
