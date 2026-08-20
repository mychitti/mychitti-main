<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use App\Models\DocValidationLog;
use App\Models\DocValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reads an uploaded vendor document and checks it against what the vendor typed into the
 * form, plus whatever rules the admin has added under Doc AI Validation.
 *
 * Goes through the same AI service every other AI feature uses, on the stateless
 * `agent_test` guard the WhatsApp auto-reply takes: provider, model and key follow whatever
 * the admin configured for the active agent, so this never depends on a hardcoded key, and
 * nothing is written to conversation memory.
 *
 * Fails open on purpose: an outage, an unconfigured service or an unreadable scan returns a
 * "review" verdict that lets the upload through and leaves a log row for a human, rather
 * than locking vendors out of onboarding. The admin can flip that with the on_error setting.
 */
class DocumentValidationService
{
    /** Synthetic caller id. Stateless guard, so this never accumulates memory. */
    private const CALLER_ID = 950000001;

    private const MAX_BYTES = 10485760; // 10 MB

    private const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** Providers the AI service can actually forward a PDF to. Gemini flattens to text. */
    private const PDF_PROVIDERS = ['anthropic', 'openai'];

    private const DOC_LABELS = [
        'id_doc'    => 'government photo ID proof (Aadhaar, PAN, Passport, Voter ID or Driving Licence)',
        'gst_doc'   => 'GST registration certificate',
        'fssai_doc' => 'FSSAI food licence',
        'other'     => 'business document',
    ];

    public function __construct(private AiServiceClient $ai) {}

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

        if (!$file->isValid()) {
            return $this->skipped('Upload could not be read.');
        }

        if ($file->getSize() > self::MAX_BYTES) {
            return $this->failOpen($settings, $file, $docType, $expected, $context, 'Document is too large to verify automatically. It has been saved for manual review.');
        }

        $modelConfig = $this->modelConfig($settings);
        $provider    = strtolower((string) ($modelConfig['ai_provider'] ?: 'anthropic'));

        $block = $this->contentBlock($file);
        if ($block === null) {
            return $this->skipped('This file type is not verified automatically.');
        }

        // Only some providers carry a PDF through. The AI service converts `document` blocks
        // for Anthropic and OpenAI; on anything else it drops them with no error, which would
        // leave the model answering about a file it never received. Send those to a human.
        // Keep this list in step with ClaudeService::chatOpenAI/chatClaude in the ai-agent repo.
        if ($block['type'] === 'document' && !in_array($provider, self::PDF_PROVIDERS, true)) {
            return $this->failOpen($settings, $file, $docType, $expected, $context, 'PDF documents cannot be checked automatically on the current AI provider. Your file was saved for manual review — upload a photo or screenshot instead for an instant check.');
        }

        try {
            $response = $this->ai->chat(
                userId: self::CALLER_ID,
                guard: 'agent_test',
                message: $this->instruction($docType, $expected),
                fileContent: $block,
                systemPrompt: $this->systemPrompt($docType, $settings),
                modelConfig: $modelConfig,
                type: 'text'
            );
        } catch (\Throwable $e) {
            Log::error('Doc AI validation call failed', ['doc_type' => $docType, 'error' => $e->getMessage()]);

            return $this->failOpen($settings, $file, $docType, $expected, $context, 'Document check could not run just now. Your file was saved for manual review.');
        }

        if (empty($response['success'])) {
            Log::error('Doc AI validation service error', ['doc_type' => $docType, 'response' => $response['message'] ?? null]);

            return $this->failOpen($settings, $file, $docType, $expected, $context, 'Document check could not run just now. Your file was saved for manual review.');
        }

        $parsed = $this->parse((string) ($response['message'] ?? ''));
        if ($parsed === null) {
            Log::warning('Doc AI validation returned unparseable output', ['doc_type' => $docType, 'reply' => $response['message'] ?? null]);

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
            // Blank means "whatever the admin's active AI agent is set to".
            'model'    => trim((string) ($stored['model'] ?? '')),
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

    /**
     * The provider, model and key document checks borrow — deliberately the *same row the
     * WhatsApp auto-reply resolves* (`user_type = 'user'`, active, newest), so this feature
     * rides the agent that is already known to be working and funded.
     *
     * Do not "improve" this by preferring the admin agent: the persona is irrelevant here
     * (the guard is stateless and the system prompt is overridden), so the only thing being
     * borrowed is provider/model/key — and an admin agent pointed at a provider nobody uses
     * silently breaks every upload while chat and auto-reply keep working.
     */
    public static function resolvedAgent(): ?object
    {
        $columns = ['user_type', 'ai_provider', 'ai_model', 'api_key_override'];

        $agent = DB::table('system_prompts')
            ->where('user_type', 'user')
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->first($columns);

        // Any active agent stands in when that row is missing.
        return $agent ?: DB::table('system_prompts')
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->first($columns);
    }

    private function modelConfig(array $settings): array
    {
        $agent = self::resolvedAgent();

        return [
            'ai_provider'      => $agent->ai_provider ?? 'anthropic',
            // A blank override in settings falls through to the agent's own model.
            'ai_model'         => $settings['model'] !== '' ? $settings['model'] : ($agent->ai_model ?? null),
            'max_tokens'       => 2000,
            'api_key_override' => $agent->api_key_override ?? null,
        ];
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

    private function systemPrompt(string $docType, array $settings): string
    {
        $label = self::docLabel($docType);
        $rules = DocValidationRule::promptBlock($docType);

        $prompt = <<<TXT
You verify documents uploaded by vendors registering on MyChitti, an Indian multi-vendor marketplace.

The vendor was asked to upload a {$label}. Read the document image or PDF and report what it actually contains.

How to compare numbers: ignore case, spaces, hyphens, slashes and dots on both sides before deciding whether the number on the document matches the number the vendor typed. "ABCDE 1234 F" and "abcde1234f" are the same number. If the document shows the number partially masked (for example an Aadhaar shown as XXXX XXXX 1234), compare only the visible digits and set number_match to "match" if they agree, adding a warn issue noting the masking.

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

        $prompt .= "\n\n" . $this->outputContract();

        return $prompt;
    }

    /**
     * The reply is parsed as JSON, so the shape has to be stated in the prompt — this path
     * goes through the shared AI service, which returns plain text and may be pointed at a
     * provider with no schema enforcement.
     */
    private function outputContract(): string
    {
        return <<<'TXT'
Reply with a single JSON object and nothing else. No explanation before or after it, no markdown code fences. Use exactly these keys:

{
  "is_readable": true or false,
  "detected_document_type": "what the document actually is, e.g. PAN card, GST registration certificate, electricity bill",
  "matches_expected_type": true or false,
  "extracted": {
    "document_number": "the identifying number printed on the document, or empty string",
    "holder_name": "who the document is issued to, or empty string",
    "issuing_authority": "who issued it, or empty string",
    "issue_date": "as printed, or empty string",
    "expiry_date": "as printed, or empty string"
  },
  "number_match": "match" or "mismatch" or "not_found" or "not_provided",
  "verdict": "pass" or "fail" or "review",
  "confidence": a number between 0 and 1,
  "issues": [{"code": "short_slug", "severity": "block" or "warn", "detail": "one plain sentence"}],
  "summary": "one sentence summarising the check for the admin"
}

Use an empty array for "issues" when there is nothing to report.
TXT;
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

        $lines[] = 'Verify the document and reply with the JSON object only.';

        return implode("\n", $lines);
    }

    // ── Response handling ─────────────────────────────────────────────────

    /** Pulls the JSON object out of the reply, tolerating code fences and stray prose. */
    private function parse(string $reply): ?array
    {
        $reply = trim($reply);
        if ($reply === '') {
            return null;
        }

        $decoded = json_decode($reply, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $stripped = trim(preg_replace('/^```(?:json)?|```$/mi', '', $reply));
        $decoded  = json_decode($stripped, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($stripped, '{');
        $end   = strrpos($stripped, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($stripped, $start, $end - $start + 1), true);
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
     * The check could not produce a verdict — oversized file, service down or unparseable
     * output. Returns "review" so the upload proceeds, and still writes a log row so the
     * admin can see the documents that went through unverified.
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
