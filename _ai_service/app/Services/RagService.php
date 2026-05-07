<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RagService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('AI_RAG_URL', ''), '/');
    }

    public function search(string $query, string $guard = 'vendor', int $topK = 3): string
    {
        if (empty($this->baseUrl) || strlen(trim($query)) < 5) {
            return '';
        }

        try {
            $payload = ['query' => $query, 'top_k' => $topK];

            $response = Http::timeout(30)->post("{$this->baseUrl}/rag/search", $payload);

            if (!$response->ok()) {
                return '';
            }

            $data = $response->json();
            $results = $data['results'] ?? [];

            if (empty($results)) {
                return '';
            }

            $parts = [];
            foreach ($results as $doc) {
                if (($doc['score'] ?? 0) < 0.4) {
                    continue;
                }
                $parts[] = "### {$doc['title']}\n{$doc['content']}";
            }

            if (empty($parts)) {
                return '';
            }

            return "Relevant knowledge base articles:\n\n" . implode("\n\n---\n\n", $parts);
        } catch (\Exception $e) {
            Log::warning('RAG search failed', ['error' => $e->getMessage()]);
            return '';
        }
    }
}