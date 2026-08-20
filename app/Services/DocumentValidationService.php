<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use App\Models\DocValidationLog;
use App\Models\DocValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads an uploaded vendor document with Claude and checks it against what the vendor typed
 * into the form, plus whatever rules the admin has added under Doc AI Validation.
 *
 * Fails open on purpose: an API outage, a missing key or an unreadable scan returns a
 * "review" verdict that lets the upload through and leaves a log row for a human, rather
 * than locking vendors out of onboarding. The admin can flip that with the on_error setting.
 */
class DocumentValidationService
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    private const DEFAULT_MODEL = 'claude-opus-5';
    private const MAX_BYTES = 10485760; // 10 MB — comfortably inside the 32 MB request cap

    private const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const DOC_LABELS = [
        'id_doc'    => 'government photo ID proof (Aadhaar, PAN, Passport, Voter ID or Driving Licence)',
        'gst_doc'   => 'GST registration certificate',
        'fssai_doc' => 'FSSAI food licence',
        'other'     => 'business document',
    ];

    /**
     * @param  array  $expected  ['number' => '...', 'name' => '...', 'doc_name' => '...']
     * @param  array  $context   ['source' => 'registration|vendor_panel|admin_panel', 'store_id' => int|null, 'vendor_id' => int|null, 'force' => bool]
     * @return array  ['ok','checked','verdict','message','issues','extracted','confidence']
     */
    public function validate(UploadedFile $file, string $docType, array $expected = [], array $context = []): array
    {
        $settings = self::settings();
        $source   = $context['source'] ?? 'vendor_panel';
        // The admin test console runs regardless of which forms the feature is switched on for.
        $force    = !empty($context['force']);

        if (!$force && (!$settings['status'] || empty($settings['sources'][$source]))) {
            return $this->skipped('AI validation is switched off for this form.');
        }

        $apiKey = (string) config('services.anthropic.key');
        if ($apiKey === '') {
            return $this->failOpen($settings, $file, $docType, $expected, $context, 'Document check unavailable — no AI key configured.');
        }

        if (!$file->isValid()) {
            return $this->skipped('Upload could not be read.');
        }

        if ($file->getSize() > self::MAX_BYTES) {
            return $this->failOpen($settings, $file, $docType, $expected, $context, 'Document is too large to verify automatically. It has been saved for manual review.');
        }

        $block = $this->contentBlock($file);
        if ($block === null) {
            return $this->skipped('This file type is not verified automatically.');
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => self::API_VERSION,
                'content-type'      => 'application/json',
            ])->timeout(150)->post(self::ENDPOINT, [
                'model'         => $settings['model'],
                'max_tokens'    => 8000,
                'system'        => $this->systemPrompt($docType, $expected, $settings),
                'output_config' => [
                    'effort' => $settings['effort'],
                    'format' => ['type' => 'json_schema', 'schema' => $this->schema()],
                ],
                'messages' => [[
                    'role'    => 'user',
                    'content' => [$block, ['type' => 'text', 'text' => $this->instruction($docType, $expected)]],
                ]],
            ]);
        } catch (\Throwable $e) {
            Log::error('Doc AI validation request failed', ['doc_type' => $docType, 'error' => $e->getMessage()]);

            return $this->failOpen($settings, $file, $docType, $expected, $context, 'Document check could not run just now. Your file was saved for manual review.');
        }

        if ($response->failed()) {
            Log::error('Doc AI validation API error', ['status' => $response->status(), 'body' => $response->body()]);

            return $this->failOpen($settings, $file, $docType, $expected, $context, 'Document check could not run just now. Your file was saved for manual review.');
        }

        $data = $response->json();

        if (($data['stop_reason'] ?? null) === 'refusal') {
            return $this->failOpen($settings, $file, $docType, $expected, $context, 'This document could not be verified automatically. It has been sent for manual review.');
        }

        $parsed = $this->parse($data);
        if ($parsed === null) {
            Log::warning('Doc AI validation returned unparseable output', ['doc_type' => $docType, 'body' => $response->body()]);

            return $this->failOpen($settings, $file, $docType, $expected, $context, 'Document check returned an unclear result. Your file was saved for manual review.');
        }

        $verdict = in_array($parsed['verdict'] ?? '', ['pass', 'fail', 'review'], true) ? $parsed['verdict'] : 'review';
        $issues  = $this->normalizeIssues($parsed['issues'] ?? []);

        $result = $this->outcome(
            $settings,
            $verdict,
            $this->message($verdict, $parsed, $issues),
            $issues,
            is_array($parsed['extracted'] ?? null) ? $parsed['extracted'] : [],
            isset($parsed['confidence']) ? (float) $parsed['confidence'] : null,
            true
        );

        $this->log($file, $docType, $expected, $context, $result, $parsed);

        return $result;
    }

    /** Platform settings for the feature, with defaults for a fresh install. */
    public static function settings(): array
    {
        $stored = Helpers::get_business_settings('doc_ai_validation');
        $stored = is_array($stored) ? $stored : [];

        $sources = is_array($stored['sources'] ?? null) ? $stored['sources'] : [];

        return [
            'status'   => (int) ($stored['status'] ?? 0) === 1,
            'mode'     => in_array($stored['mode'] ?? '', ['block', 'warn'], true) ? $stored['mode'] : 'block',
            'model'    => !empty($stored['model']) ? $stored['model'] : self::DEFAULT_MODEL,
            'effort'   => in_array($stored['effort'] ?? '', ['low', 'medium', 'high'], true) ? $stored['effort'] : 'medium',
            'on_error' => ($stored['on_error'] ?? 'allow') === 'block' ? 'block' : 'allow',
            'sources'  => [
                'registration' => (int) ($sources['registration'] ?? 1),
                'vendor_panel' => (int) ($sources['vendor_panel'] ?? 1),
                'admin_panel'  => (int) ($sources['admin_panel'] ?? 0),
            ],
        ];
    }

    public static function docLabel(string $docType): string
    {
        return self::DOC_LABELS[$docType] ?? self::DOC_LABELS['other'];
    }

    // ── Request building ──────────────────────────────────────────────────

    private function contentBlock(UploadedFile $file): ?array
    {
        $mime = strtolower((string) $file->getMimeType());
        if ($mime === 'image/jpg') {
            $mime = 'image/jpeg';
        }

        $base64 = base64_encode((string) file_get_contents($file->getRealPath()));

        if ($mime === 'application/pdf') {
            return [
                'type'   => 'document',
                'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $base64],
            ];
        }

        if (in_array($mime, self::IMAGE_TYPES, true)) {
            return [
                'type'   => 'image',
                'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $base64],
            ];
        }

        return null;
    }

    private function systemPrompt(string $docType, array $expected, array $settings): string
    {
        $label = self::docLabel($docType);
        $rules = DocValidationRule::promptBlock($docType);

        $prompt = <<<TXT
You verify documents uploaded by vendors registering on MyChitti, an Indian multi-vendor marketplace.

The vendor was asked to upload a {$label}. Read the document image or PDF and report what it actually contains.

How to compare numbers: ignore case, spaces, hyphens, slashes and dots on both sides before deciding whether the number on the document matches the number the vendor typed. "ABCDE 1234 F" and "abcde1234f" are the same number. If the document shows the number only partially masked (for example an Aadhaar shown as XXXX XXXX 1234), compare only the visible digits and set number_match to "match" if they agree, and add a warn issue noting the masking.

Verdict rules, applied in this order:
1. "fail" — the document is clearly the wrong kind of document, the number on it does not match the number the vendor entered, the document is expired, or it breaks any blocking rule below.
2. "review" — you cannot read the document well enough to be sure, the number is not visible on the document at all, the name does not match, or it breaks an advisory rule.
3. "pass" — the document is the expected kind, and every value the vendor entered that you can see on the document agrees with it.

Never guess a number you cannot actually read. If a field is not visible, return an empty string for it and set number_match to "not_found" rather than inventing a value. Write every issue detail as one plain sentence a vendor can act on, and never repeat the full document number back in an issue detail — refer to it as "the number on the document".
TXT;

        if ($rules !== '') {
            $prompt .= "\n\nRules the platform admin has added for this document type. Apply them exactly as written:\n\n" . $rules;
        }

        if ($settings['mode'] === 'warn') {
            $prompt .= "\n\nThe platform is in advisory mode, but still return the honest verdict — the application decides what to do with it.";
        }

        return $prompt;
    }

    private function instruction(string $docType, array $expected): string
    {
        $lines = ['The vendor says this document is a ' . self::docLabel($docType) . '.'];

        if (!empty($expected['doc_name'])) {
            $lines[] = 'The vendor named this document: "' . $expected['doc_name'] . '".';
        }

        if (!empty($expected['number'])) {
            $lines[] = 'The vendor entered this document number in the form: "' . $expected['number'] . '". Check whether the same number appears on the document.';
        } else {
            $lines[] = 'The vendor did not enter a document number, so set number_match to "not_provided".';
        }

        if (!empty($expected['name'])) {
            $lines[] = 'The business or owner name on file is "' . $expected['name'] . '". Note it as an issue if the document is clearly issued to someone unrelated.';
        }

        $lines[] = 'Verify the document and return your findings.';

        return implode("\n", $lines);
    }

    private function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'is_readable'             => ['type' => 'boolean', 'description' => 'Whether the document is legible enough to verify.'],
                'detected_document_type'  => ['type' => 'string', 'description' => 'What the document actually is, e.g. "PAN card", "GST registration certificate", "electricity bill".'],
                'matches_expected_type'   => ['type' => 'boolean', 'description' => 'Whether the document is the kind the vendor was asked to upload.'],
                'extracted'               => [
                    'type'       => 'object',
                    'properties' => [
                        'document_number'   => ['type' => 'string', 'description' => 'The identifying number printed on the document, or "" if not visible.'],
                        'holder_name'       => ['type' => 'string', 'description' => 'The person or business the document is issued to, or "".'],
                        'issuing_authority' => ['type' => 'string', 'description' => 'Who issued the document, or "".'],
                        'issue_date'        => ['type' => 'string', 'description' => 'Issue date as printed, or "".'],
                        'expiry_date'       => ['type' => 'string', 'description' => 'Expiry date as printed, or "".'],
                    ],
                    'required'             => ['document_number', 'holder_name', 'issuing_authority', 'issue_date', 'expiry_date'],
                    'additionalProperties' => false,
                ],
                'number_match' => [
                    'type'        => 'string',
                    'enum'        => ['match', 'mismatch', 'not_found', 'not_provided'],
                    'description' => 'How the number on the document compares to the one the vendor typed.',
                ],
                'verdict'    => ['type' => 'string', 'enum' => ['pass', 'fail', 'review']],
                'confidence' => ['type' => 'number', 'description' => 'Confidence in the verdict, 0 to 1.'],
                'issues'     => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'code'     => ['type' => 'string', 'description' => 'Short slug, e.g. "number_mismatch", "wrong_document_type", "expired", "unreadable".'],
                            'severity' => ['type' => 'string', 'enum' => ['block', 'warn']],
                            'detail'   => ['type' => 'string', 'description' => 'One plain sentence the vendor can act on.'],
                        ],
                        'required'             => ['code', 'severity', 'detail'],
                        'additionalProperties' => false,
                    ],
                ],
                'summary' => ['type' => 'string', 'description' => 'One sentence summarising the check for the admin.'],
            ],
            'required'             => ['is_readable', 'detected_document_type', 'matches_expected_type', 'extracted', 'number_match', 'verdict', 'confidence', 'issues', 'summary'],
            'additionalProperties' => false,
        ];
    }

    // ── Response handling ─────────────────────────────────────────────────

    private function parse(array $data): ?array
    {
        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? '') !== 'text') {
                continue;
            }
            $decoded = json_decode((string) ($block['text'] ?? ''), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function normalizeIssues($issues): array
    {
        if (!is_array($issues)) {
            return [];
        }

        $clean = [];
        foreach ($issues as $issue) {
            if (!is_array($issue) || empty($issue['detail'])) {
                continue;
            }
            $clean[] = [
                'code'     => (string) ($issue['code'] ?? 'issue'),
                'severity' => ($issue['severity'] ?? 'warn') === 'block' ? 'block' : 'warn',
                'detail'   => (string) $issue['detail'],
            ];
        }

        return $clean;
    }

    private function message(string $verdict, array $parsed, array $issues): string
    {
        if ($verdict === 'pass') {
            return 'Document verified.';
        }

        $details = array_column(array_filter($issues, fn($i) => $verdict === 'review' || $i['severity'] === 'block'), 'detail');
        if (empty($details)) {
            $details = array_column($issues, 'detail');
        }

        if (!empty($details)) {
            return implode(' ', array_slice($details, 0, 3));
        }

        if (!empty($parsed['summary'])) {
            return (string) $parsed['summary'];
        }

        return $verdict === 'fail'
            ? 'This document does not match the details you entered.'
            : 'This document needs a manual review.';
    }

    /**
     * Turns a verdict into a decision. A "fail" only blocks when the platform is in block
     * mode; a "review" never blocks — it is the fail-open path — unless the admin has set
     * on_error to block and the check itself could not run.
     */
    private function outcome(array $settings, string $verdict, string $message, array $issues, array $extracted, ?float $confidence, bool $checked): array
    {
        $ok = true;
        if ($verdict === 'fail' && $settings['mode'] === 'block') {
            $ok = false;
        }
        if (!$checked && $settings['on_error'] === 'block') {
            $ok = false;
        }

        return [
            'ok'         => $ok,
            'checked'    => $checked,
            'verdict'    => $verdict,
            'message'    => $message,
            'issues'     => $issues,
            'extracted'  => $extracted,
            'confidence' => $confidence,
        ];
    }

    /**
     * The check could not produce a verdict — no key, oversized file, API down, refusal or
     * unparseable output. Returns "review" so the upload proceeds, and still writes a log row
     * so the admin can see the documents that went through unverified.
     */
    private function failOpen(array $settings, UploadedFile $file, string $docType, array $expected, array $context, string $message): array
    {
        $result = $this->outcome($settings, 'review', $message, [], [], null, false);
        $this->log($file, $docType, $expected, $context, $result, ['summary' => $message]);

        return $result;
    }

    private function skipped(string $message): array
    {
        return [
            'ok'         => true,
            'checked'    => false,
            'verdict'    => 'skipped',
            'message'    => $message,
            'issues'     => [],
            'extracted'  => [],
            'confidence' => null,
        ];
    }

    private function log(UploadedFile $file, string $docType, array $expected, array $context, array $result, array $parsed): void
    {
        try {
            DocValidationLog::ensureTable();
            DocValidationLog::create([
                'store_id'         => $context['store_id'] ?? null,
                'vendor_id'        => $context['vendor_id'] ?? null,
                'source'           => $context['source'] ?? 'vendor_panel',
                'doc_type'         => $docType,
                'file_name'        => substr((string) $file->getClientOriginalName(), 0, 255),
                'expected_number'  => substr((string) ($expected['number'] ?? ''), 0, 100) ?: null,
                'extracted_number' => substr((string) ($parsed['extracted']['document_number'] ?? ''), 0, 100) ?: null,
                'verdict'          => $result['verdict'],
                'confidence'       => $result['confidence'],
                'summary'          => $parsed['summary'] ?? null,
                'issues'           => json_encode($result['issues']),
                'raw'              => json_encode($parsed),
            ]);
        } catch (\Throwable $e) {
            Log::error('Doc AI validation log write failed', ['error' => $e->getMessage()]);
        }
    }
}
