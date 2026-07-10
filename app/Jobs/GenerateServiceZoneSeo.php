<?php

namespace App\Jobs;

use App\Models\ServiceZoneSeo;
use App\Services\AiServiceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Generate SEO assets (keywords + meta + copy) for one (category x zone) combo via the AI service.
 */
class GenerateServiceZoneSeo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;

    public function __construct(public int $comboId)
    {
        // Dedicated low-priority queue — bulk generation must never block leads/accounts.
        $this->onQueue('seo');
    }

    /**
     * @return array{success: bool, message: string} — dispatchSync() returns whatever handle()
     * returns, so the admin's synchronous "Generate" click can show the real outcome instead of
     * a blind success toast. Queued (async) runs just ignore the return value.
     */
    public function handle(): array
    {
        $combo = ServiceZoneSeo::find($this->comboId);
        if (!$combo) {
            return ['success' => false, 'message' => 'Combo no longer exists.'];
        }

        $zone     = DB::table('zones')->where('id', $combo->zone_id)->first();
        $category = DB::table('categories')->where('id', $combo->category_id)->first();
        if (!$zone || !$category) {
            return ['success' => false, 'message' => 'Zone or category no longer exists.'];
        }

        $isItemLevel = (int) $combo->item_id > 0;
        $item = $isItemLevel ? DB::table('items')->where('id', $combo->item_id)->first() : null;
        if ($isItemLevel && !$item) {
            return ['success' => false, 'message' => 'Item no longer exists.'];
        }

        if ($isItemLevel) {
            $context = "City / Zone: {$zone->name}\n"
                . "Specific service: {$item->name}\n"
                . "Parent category: {$category->name}\n"
                . "Stores offering this exact service in the city: {$combo->store_count}";
        } else {
            // Actual services offered in this combo — grounds the keywords in real supply.
            $itemNames = DB::table('item_store as ist')
                ->join('items as i', 'i.id', '=', 'ist.item_id')
                ->join('stores as s', 's.id', '=', 'ist.store_id')
                ->where('i.category_id', $combo->category_id)
                ->where('s.zone_id', $combo->zone_id)
                ->where('i.status', 1)
                ->distinct()->limit(15)->pluck('i.name')->all();

            $context = "City / Zone: {$zone->name}\n"
                . "Service category: {$category->name}\n"
                . "Stores offering this in the city: {$combo->store_count}\n"
                . 'Specific services offered here: ' . (implode(', ', $itemNames) ?: $category->name);
        }

        $result = app(AiServiceClient::class)->chat(0, 'admin', $context, systemPrompt: $this->systemPrompt($isItemLevel));

        if (empty($result['success']) || empty($result['message'])) {
            $reason = $result['message'] ?? 'no response from AI service';
            Log::warning('SEO gen failed for combo ' . $this->comboId . ': ' . $reason);
            return ['success' => false, 'message' => $reason];
        }

        $data = $this->parseJson($result['message']);
        if (!$data) {
            Log::warning('SEO gen: unparseable JSON for combo ' . $this->comboId . ': ' . substr($result['message'], 0, 500));
            return ['success' => false, 'message' => 'AI response was not valid JSON — see logs for the raw output.'];
        }

        $keywords = array_slice(array_values(array_unique(array_filter(array_map(
            fn($k) => strtolower(trim((string) $k)),
            (array) ($data['keywords'] ?? [])
        )))), 0, 30);

        $combo->update([
            'keywords'         => $keywords,
            'meta_title'       => $this->clip($data['meta_title'] ?? null, 70),
            'meta_description' => $this->clip($data['meta_description'] ?? null, 300),
            'h1'               => $this->clip($data['h1'] ?? null, 160),
            'intro_paragraph'  => $data['intro_paragraph'] ?? null,
            'faqs'             => is_array($data['faqs'] ?? null) ? array_slice($data['faqs'], 0, 6) : null,
            'model'            => $result['model'] ?? 'ai',
            'generated_at'     => now(),
            'status'           => $combo->store_count > 0 ? 'published' : 'unpublished',
        ]);

        return ['success' => true, 'message' => 'Generated successfully.'];
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

    private function systemPrompt(bool $isItemLevel = false): string
    {
        $focus = $isItemLevel
            ? "You are given ONE SPECIFIC SERVICE (not a whole category) and a city. Every asset must "
                . "focus tightly on that one specific service — mention the parent category only in passing, "
                . "never as the main subject."
            : "You are given a service CATEGORY (covering multiple specific services) and a city. Every "
                . "asset should speak to the category as a whole, informed by the specific services listed.";

        return <<<PROMPT
You are a local-SEO specialist for an Indian multi-vendor services marketplace. {$focus}

Return ONLY valid minified JSON — no markdown, no commentary — with this exact shape:
{"keywords":["..."],"meta_title":"...","meta_description":"...","h1":"...","intro_paragraph":"...","faqs":[{"q":"...","a":"..."}]}

Rules:
- keywords: 15-25 short (2-5 word) lowercase phrases with local intent — mix {service}+{city}, {service}+{area/district}, "near me", and long-tail variants. No duplicates. No city that isn't the given one.
- meta_title: <= 60 characters, include the service and the city.
- meta_description: <= 155 characters, compelling, include service + city.
- h1: a natural heading containing the service and the city.
- intro_paragraph: 2-3 natural sentences mentioning the service and city; do NOT keyword-stuff.
- faqs: exactly 3 concise, locally relevant question/answer pairs.
Output JSON only.
PROMPT;
    }
}
