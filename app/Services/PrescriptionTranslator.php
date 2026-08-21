<?php

namespace App\Services;

use App\Models\Prescription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Prints a prescription in the language the doctor picked.
 *
 * Two halves, translated and cached separately because they change at completely different rates:
 *
 *   Labels  - "Patient", "Diagnosis", "Dosage", the table headers. Identical on every sheet, so
 *             they are translated once per language for the whole platform and reused forever.
 *   Content - this patient's diagnosis, advice and per-medicine instructions. Translated once per
 *             prescription and stored, so reopening or reprinting the sheet costs nothing.
 *
 * Medicine names are deliberately never translated. A brand or molecule name rendered into
 * another script is what a pharmacist has to read back, and a transliterated "Paracetamol" is a
 * dispensing error waiting to happen. Doses like "1-0-1" are numerals in every script and pass
 * through untouched for the same reason.
 *
 * Nothing here can fail a page: every path falls back to the English original.
 */
class PrescriptionTranslator
{
    /** The fixed sheet furniture, in English. Keys are what the Blade asks for. */
    const LABELS = [
        'patient'        => 'Patient',
        'date'           => 'Date',
        'appointment'    => 'Appt #',
        'age'            => 'Age',
        'years'          => 'yrs',
        'diagnosis'      => 'Diagnosis',
        'medicine'       => 'Medicine',
        'dosage'         => 'Dosage',
        'frequency'      => 'Frequency',
        'duration'       => 'Duration',
        'quantity'       => 'Qty',
        'instructions'   => 'Instructions',
        'no_medicines'   => 'No medicines prescribed.',
        'advice'         => 'Advice / Notes',
        'follow_up'      => 'Follow-up',
        'finalized'      => 'Finalized',
        'draft'          => 'Draft',
        'reg_no'         => 'Reg. No',
        'computer_note'  => 'This prescription is computer generated.',
        'machine_note'   => 'Translated automatically. The English original is on file.',
    ];

    /**
     * The patient-facing copy - the WhatsApp link and the PDF attachment, which is the version a
     * patient actually reads. Worded for a patient rather than a pharmacy counter, so it gets its
     * own set instead of reusing the clinical sheet's headings.
     */
    const PATIENT_LABELS = [
        'diagnosis'      => 'Diagnosis',
        'your_medicines' => 'Your medicines',
        'medicine'       => 'Medicine',
        'dose'           => 'Dose',
        'when'           => 'When',
        'how_long'       => 'How long',
        'none_listed'    => 'No medicines listed.',
        'remember'       => 'Please remember',
        'remember_body'  => 'Take every medicine exactly as written above, and finish the full course even if you feel better sooner. Do not change the dose or stop early without asking your doctor. If anything feels wrong after a dose, contact {hospital} straight away.',
        'doctor_notes'   => "Doctor's notes",
        'next_visit'     => 'Next visit',
        'machine_note'   => 'Translated automatically. The English original is on file.',
    ];

    /** The label sets this class can translate, by name. */
    private static function labelSet(string $set): array
    {
        return $set === 'patient' ? self::PATIENT_LABELS : self::LABELS;
    }

    /** Per-item fields worth translating. Everything else on a row is a name or a number. */
    const ITEM_FIELDS = ['frequency', 'duration', 'instructions'];

    /**
     * Why the last translation attempt in this request produced nothing.
     *
     * A failed translation falls back to English, which on screen is indistinguishable from a
     * sheet that was never meant to be translated. Holding the reason lets the page say which
     * of the two happened instead of leaving the doctor to guess.
     */
    private static ?string $lastError = null;

    public static function lastError(): ?string
    {
        return self::$lastError;
    }

    public static function ensureTables(): void
    {
        if (!Schema::hasTable('rx_label_translations')) {
            DB::statement("CREATE TABLE `rx_label_translations` (
                `language` VARCHAR(32) NOT NULL PRIMARY KEY,
                `payload` TEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        if (!Schema::hasTable('prescription_translations')) {
            DB::statement("CREATE TABLE `prescription_translations` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `prescription_id` BIGINT UNSIGNED NOT NULL,
                `language` VARCHAR(10) NOT NULL,
                `payload` TEXT NULL,
                `translated_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                UNIQUE KEY `rx_lang` (`prescription_id`, `language`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }

    /** English name of a language code, without the native-script half of the picker label. */
    public static function languageName(string $code): string
    {
        $label = Prescription::LANGUAGES[$code] ?? $code;

        return trim(explode("\u{2014}", $label)[0]);
    }

    /**
     * The sheet's fixed labels in one language, English if anything goes wrong.
     *
     * Cached in its own table rather than per prescription: the twenty words below are the same
     * on the ten-thousandth Telugu prescription as on the first.
     */
    public static function labels(?string $language, string $set = 'sheet'): array
    {
        $english  = self::labelSet($set);
        $language = $language ?: 'en';
        if ($language === 'en' || !isset(Prescription::LANGUAGES[$language])) {
            return $english;
        }

        self::ensureTables();

        $key = $language . ':' . $set;

        $stored = DB::table('rx_label_translations')->where('language', $key)->value('payload');
        if ($stored) {
            $decoded = json_decode($stored, true);
            if (is_array($decoded)) {
                return array_merge($english, array_intersect_key($decoded, $english));
            }
        }

        $name = self::languageName($language);
        $translated = self::ask(
            "You are translating the fixed headings of an Indian medical prescription into {$name}.",
            "Translate each value into {$name}. These are printed headings a patient reads, so keep them as "
                . "short as the English and use everyday words. Keep any {placeholder} token exactly as it is. "
                . "Reply with a JSON object using the same keys and nothing else.\n\n"
                . json_encode($english, JSON_UNESCAPED_UNICODE)
        );

        if (!$translated) {
            return $english;
        }

        $payload = array_merge($english, array_intersect_key($translated, $english));

        DB::table('rx_label_translations')->updateOrInsert(
            ['language' => $key],
            ['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE), 'updated_at' => now(), 'created_at' => now()]
        );

        return $payload;
    }

    /**
     * This prescription's free text in its chosen language, or null when it prints in English.
     *
     * Shape: ['diagnosis' => ?string, 'notes' => ?string, 'items' => [itemId => [field => string]]].
     * Generated on first view and stored, so the second print is a table read.
     */
    public static function content(Prescription $rx, bool $force = false): ?array
    {
        $language = $rx->language ?: 'en';
        if ($language === 'en' || !isset(Prescription::LANGUAGES[$language])) {
            return null;
        }

        self::ensureTables();

        if (!$force) {
            $stored = DB::table('prescription_translations')
                ->where('prescription_id', $rx->id)->where('language', $language)->value('payload');
            if ($stored) {
                $decoded = json_decode($stored, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        $source = self::sourceText($rx);
        if (!$source) {
            return null;
        }

        $name = self::languageName($language);
        $translated = self::ask(
            "You are translating an Indian doctor's prescription for the patient to read in {$name}.",
            "Translate every value below into {$name}. Rules:\n"
                . "- Never translate or transliterate a medicine, brand or molecule name.\n"
                . "- Keep numbers, dose patterns (1-0-1), units (mg, ml) and dates exactly as they are.\n"
                . "- Use plain words a patient understands, not clinical register.\n"
                . "- Keep each value about as short as the English.\n"
                . "Reply with a JSON object of the same shape and keys, and nothing else.\n\n"
                . json_encode($source, JSON_UNESCAPED_UNICODE)
        );

        if (!$translated) {
            return null;
        }

        $payload = self::shape($translated, $source);

        DB::table('prescription_translations')->updateOrInsert(
            ['prescription_id' => $rx->id, 'language' => $language],
            [
                'payload'       => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'translated_at' => now(),
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );

        return $payload;
    }

    /** Label set only if one is already stored - never generates. Pairs with cached(). */
    public static function cachedLabels(?string $language, string $set = 'sheet'): array
    {
        $english  = self::labelSet($set);
        $language = $language ?: 'en';
        if ($language === 'en' || !Schema::hasTable('rx_label_translations')) {
            return $english;
        }

        $stored = DB::table('rx_label_translations')
            ->where('language', $language . ':' . $set)->value('payload');

        $decoded = $stored ? json_decode($stored, true) : null;

        return is_array($decoded) ? array_merge($english, array_intersect_key($decoded, $english)) : $english;
    }

    /**
     * A translation only if one is already stored - never generates.
     *
     * What the public patient link and the PDF renderer use. Both run on a request the hospital
     * is not watching: the patient tapping a WhatsApp link should not be the one waiting out a
     * translation round-trip, and a page that fell back to English is far better than one that
     * hangs. Finalizing the prescription is what fills this cache (see prime()).
     */
    public static function cached(Prescription $rx): array
    {
        $language = $rx->language ?: 'en';
        if ($language === 'en' || !Schema::hasTable('prescription_translations')) {
            return [];
        }

        $stored = DB::table('prescription_translations')
            ->where('prescription_id', $rx->id)->where('language', $language)->value('payload');

        $decoded = $stored ? json_decode($stored, true) : null;

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Translate ahead of the patient, on the hospital's own request.
     *
     * Called when a prescription is finalized, which is the moment its wording stops changing and
     * the moment the link and the PDF go out. Swallows everything: a translation that could not be
     * made must never stop a prescription being finalized or sent.
     */
    public static function prime(Prescription $rx): void
    {
        if (($rx->language ?: 'en') === 'en') {
            return;
        }

        try {
            self::labels($rx->language);
            self::labels($rx->language, 'patient');
            self::content($rx);
        } catch (\Throwable $e) {
            Log::warning('Rx translation prime failed', ['rx' => $rx->id, 'error' => $e->getMessage()]);
        }
    }

    /** Drop a stored translation so the next view regenerates it - used after an edit. */
    public static function forget(int $prescriptionId, ?string $language = null): void
    {
        if (!Schema::hasTable('prescription_translations')) {
            return;
        }

        DB::table('prescription_translations')
            ->where('prescription_id', $prescriptionId)
            ->when($language, fn($q) => $q->where('language', $language))
            ->delete();
    }

    /** What is worth sending: free text only, keyed so the reply can be matched back. */
    private static function sourceText(Prescription $rx): array
    {
        $source = [];

        if (filled($rx->diagnosis)) {
            $source['diagnosis'] = (string) $rx->diagnosis;
        }
        if (filled($rx->notes)) {
            $source['notes'] = (string) $rx->notes;
        }

        $items = [];
        foreach ($rx->items as $item) {
            $row = [];
            foreach (self::ITEM_FIELDS as $field) {
                if (filled($item->{$field})) {
                    $row[$field] = (string) $item->{$field};
                }
            }
            if ($row) {
                $items[(string) $item->id] = $row;
            }
        }
        if ($items) {
            $source['items'] = $items;
        }

        return $source;
    }

    /** Keep only the keys we asked about, so a chatty reply cannot inject fields into the sheet. */
    private static function shape(array $reply, array $source): array
    {
        $out = [];

        foreach (['diagnosis', 'notes'] as $key) {
            if (isset($source[$key]) && is_string($reply[$key] ?? null)) {
                $out[$key] = $reply[$key];
            }
        }

        foreach (($source['items'] ?? []) as $itemId => $fields) {
            foreach (array_keys($fields) as $field) {
                $value = $reply['items'][$itemId][$field] ?? null;
                if (is_string($value) && $value !== '') {
                    $out['items'][$itemId][$field] = $value;
                }
            }
        }

        return $out;
    }

    /**
     * One translation round-trip through the AI service, decoded from JSON.
     *
     * Runs on the 'agent_test' guard on purpose: every other guard makes AiServiceClient attach
     * the vendor's dashboard persona, its tool catalogue and its page context, none of which
     * belongs in a translation call. Returns null on any failure - the caller falls back to
     * English rather than printing half a sheet.
     */
    private static function ask(string $systemPrompt, string $message): ?array
    {
        $agent = DB::table('system_prompts')
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->first(['id', 'ai_provider', 'ai_model', 'api_key_override']);

        try {
            $result = app(AiServiceClient::class)->chat(
                userId:       0,
                guard:        'agent_test',
                message:      $message,
                agentId:      $agent ? (int) $agent->id : null,
                systemPrompt: $systemPrompt . ' Reply with JSON only - no explanation, no code fences.',
                modelConfig:  [
                    'ai_provider'      => $agent->ai_provider ?? 'openai',
                    'ai_model'         => $agent->ai_model ?? null,
                    'max_tokens'       => 4000,
                    'temperature'      => 0.2,
                    'api_key_override' => $agent->api_key_override ?? null,
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('Rx translation failed', ['error' => $e->getMessage()]);
            self::$lastError = $e->getMessage();
            return null;
        }

        if (empty($result['success'])) {
            Log::warning('Rx translation refused', ['error' => $result['message'] ?? 'unknown']);
            self::$lastError = $result['message'] ?? 'The AI service did not answer.';
            return null;
        }

        $decoded = self::decode((string) ($result['message'] ?? ''));
        if ($decoded === null) {
            self::$lastError = 'The AI service replied with something that was not usable.';
        }

        return $decoded;
    }

    /** Pull the JSON object out of a reply that may still arrive wrapped in prose or fences. */
    private static function decode(string $reply): ?array
    {
        $reply = trim($reply);
        $reply = preg_replace('/^```(?:json)?|```$/mi', '', $reply) ?? $reply;

        $start = strpos($reply, '{');
        $end   = strrpos($reply, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $decoded = json_decode(substr($reply, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }
}
