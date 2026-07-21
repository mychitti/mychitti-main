<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\AiServiceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Generate SEO meta (meta_title + meta_description) for one store via the AI service.
 *
 * Dispatched automatically when a store is created (see Store::boot) and on demand from
 * admin / the store:generate-meta command for backfilling stores that are missing it.
 */
class GenerateStoreMeta implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    /**
     * @param bool $force Overwrite meta that is already present. Off by default so a re-run
     *                    never silently re-bills AI for stores that are already done.
     */
    public function __construct(public int $storeId, public bool $force = false)
    {
        // Same low-priority queue as the SEO page generator — bulk backfill must never block
        // leads/accounts work.
        $this->onQueue('seo');
    }

    /**
     * @return array{success: bool, message: string} — returned so the admin's synchronous
     * "Generate" click can show the real outcome instead of a blind success toast.
     */
    public function handle(): array
    {
        // withoutGlobalScopes: ZoneScope would filter stores by the request's zone, which does
        // not exist in a queue/CLI context.
        $store = Store::withoutGlobalScopes()->find($this->storeId);
        if (!$store) {
            return ['success' => false, 'message' => 'Store no longer exists.'];
        }

        if (!$this->force && $store->meta_title && $store->meta_description) {
            return ['success' => true, 'message' => 'Already has meta — skipped.'];
        }

        $result = app(AiServiceClient::class)->chat(
            0,
            'admin',
            $this->context($store),
            systemPrompt: $this->systemPrompt(),
            // Force OpenAI explicitly: the 'admin' guard has no ACTIVE row in system_prompts, so
            // it would fall through to the AI service default (Anthropic) which is unfunded.
            modelConfig: ['ai_provider' => 'openai', 'ai_model' => 'gpt-4o']
        );

        if (empty($result['success']) || empty($result['message'])) {
            $reason = $result['message'] ?? 'no response from AI service';
            Log::warning('Store meta gen failed for store ' . $this->storeId . ': ' . $reason);
            return ['success' => false, 'message' => $reason];
        }

        $data = $this->parseJson($result['message']);
        if (!$data) {
            Log::warning('Store meta gen: unparseable JSON for store ' . $this->storeId . ': ' . substr($result['message'], 0, 500));
            return ['success' => false, 'message' => 'AI response was not valid JSON — see logs for the raw output.'];
        }

        $metaTitle = $this->clip($data['meta_title'] ?? null, 70);
        $metaDescription = $this->clip($data['meta_description'] ?? null, 300);

        if (!$metaTitle && !$metaDescription) {
            return ['success' => false, 'message' => 'AI returned empty meta fields.'];
        }

        $store->meta_title = $metaTitle ?: $store->meta_title;
        $store->meta_description = $metaDescription ?: $store->meta_description;
        $store->save();

        return ['success' => true, 'message' => 'Generated successfully.'];
    }

    /**
     * Grounded in what the store actually is and offers — never invent services it does not have.
     */
    private function context(Store $store): string
    {
        $zoneName = $store->zone_id
            ? DB::table('zones')->where('id', $store->zone_id)->value('name')
            : null;
        $city = $zoneName ? trim(explode(',', $zoneName)[0]) : null;

        // Services actually carried by this store, via the item_store pivot.
        $services = DB::table('item_store as ist')
            ->join('items as i', 'i.id', '=', 'ist.item_id')
            ->where('ist.store_id', $store->id)
            ->where('i.status', 1)
            ->orderByDesc('i.order_count')
            ->distinct()->limit(15)->pluck('i.name')->all();

        $categories = DB::table('item_store as ist')
            ->join('items as i', 'i.id', '=', 'ist.item_id')
            ->join('categories as c', 'c.id', '=', 'i.category_id')
            ->where('ist.store_id', $store->id)
            ->where('i.status', 1)
            ->distinct()->limit(8)->pluck('c.name')->all();

        $lines = ['Business name: ' . $store->name];

        if ($store->business_type) {
            $lines[] = 'Business type: ' . str_replace('_', ' ', $store->business_type);
        }
        if ($city) {
            $lines[] = 'City: ' . $city;
        }
        if ($store->address) {
            $lines[] = 'Address: ' . trim($store->address);
        }
        if ($categories) {
            $lines[] = 'Categories: ' . implode(', ', $categories);
        }
        if ($services) {
            $lines[] = 'Services offered: ' . implode(', ', $services);
        } else {
            // Newly registered stores have no catalogue yet — say so explicitly so the model
            // writes about the business generally instead of inventing a service list.
            $lines[] = 'Services offered: not yet listed — write generally about the business, do NOT invent specific services.';
        }

        return implode("\n", $lines);
    }

    private function clip($value, int $len): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : mb_substr($value, 0, $len);
    }

    private function parseJson(string $text): ?array
    {
        // The model may wrap JSON in prose/code fences — extract the first {...} block.
        if (preg_match('/\{.*\}/s', trim($text), $m)) {
            $json = json_decode($m[0], true);
            if (is_array($json)) {
                return $json;
            }
        }
        return null;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a local-SEO specialist writing search meta tags for a business listed on an Indian
multi-vendor services marketplace.

Return ONLY valid minified JSON — no markdown, no commentary — with this exact shape:
{"meta_title":"...","meta_description":"..."}

Rules:
- meta_title: <= 60 characters. Include the business name and, when a city is given, the city.
- meta_description: <= 155 characters, natural and compelling, mentioning what the business does
  and its city. Write for a real customer searching locally.
- Use ONLY the facts provided. Never invent services, awards, ratings, years in business, or
  claims like "best" / "No.1" / "certified" that are not in the input.
- No keyword stuffing, no ALL CAPS, no quotes around the whole value, no emoji.
Output JSON only.
PROMPT;
    }
}
