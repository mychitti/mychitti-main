<?php

namespace App\Services;
 
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; 

class AiServiceClient
{
    private string $url;
    private string $key; 

    public function __construct()
    {
        $this->url = rtrim(config('services.ai_service.url', ''), '/');
        $this->key = config('services.ai_service.key', '');
    }

    public function chat(int $userId, string $guard, string $message, ?array $fileContent = null): array
    {
        $payload = [ 
            'user_id' => $userId,
            'guard'   => $guard,
            'message' => $message,
        ];

        if ($fileContent) {
            $payload['attachment'] = $fileContent; 
        }
 
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->key])
                ->timeout(120)
                ->post("{$this->url}/api/ai/chat", $payload);
            if (!$response->successful()) {
                return ['success' => false, 'message' => 'AI service error: ' . $response->status(), 'detail' => $response->body()];
            }

            return $response->json() ?? ['success' => false, 'message' => 'No response from AI service.'];
        } catch (\Exception $e) {
            Log::error('AI service chat error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'AI service unavailable.'];
        } 
    }

    public function history(int $userId, string $guard): array
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->key])
                ->timeout(30)
                ->get("{$this->url}/api/ai/history", ['user_id' => $userId, 'guard' => $guard]);

            return $response->json() ?? ['success' => false, 'messages' => []];
        } catch (\Exception $e) {
            Log::error('AI service history error', ['error' => $e->getMessage()]);
            return ['success' => false, 'messages' => []];
        }
    }

    public function clearMemory(int $userId, string $guard): array
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->key])
                ->timeout(30)
                ->post("{$this->url}/api/ai/clear", ['user_id' => $userId, 'guard' => $guard]);

            return $response->json() ?? ['success' => false, 'message' => 'Memory cleared.'];
        } catch (\Exception $e) {
            Log::error('AI service clear error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'AI service unavailable.'];
        }
    }
}
