<?php

namespace App\Services;
 
use Illuminate\Support\Facades\DB;
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

    public function chat(int $userId , string $guard, string $message, ?array $fileContent = null, ?int $agentId = null, ?string $systemPrompt = null, ?array $modelConfig = null): array
    {
        // Auto-resolve agent_id from system_prompts if not explicitly provided
        if ($agentId === null && $guard !== 'agent_test') {
            $userType = match ($guard) {
                'admin'  => 'admin', 
                'vendor' => 'vendor', 
                default  => 'user',
            }; 
            
            $resolved = DB::table('system_prompts')
                ->where('user_type', $userType)
                ->where('status', 'active')
                ->orderByDesc('updated_at')
                ->first(['id', 'ai_provider', 'ai_model', 'max_tokens', 'temperature', 'top_p', 'api_key_override']);
                // prx($resolved, 'Resolved agent for guard: ' . $guard);
            if ($resolved) {
                $agentId = (int) $resolved->id;

                // Force provider/model from main app resolved agent so runtime
                // does not depend on ai_service DB state.
                if ($modelConfig === null) {
                    $modelConfig = [
                        'ai_provider'      => $resolved->ai_provider ?: 'anthropic',
                        'ai_model'         => $resolved->ai_model ?: null,
                        'max_tokens'       => $resolved->max_tokens ?: null,
                        'temperature'      => strlen((string) $resolved->temperature) ? (float) $resolved->temperature : null,
                        'top_p'            => strlen((string) $resolved->top_p) ? (float) $resolved->top_p : null,
                        'api_key_override' => $resolved->api_key_override ?: null,
                    ];
                }
            }
        }
        $payload = [
            'user_id' => $userId,
            'guard'   => $guard,
            'message' => $message,
        ];

        if ($agentId !== null) {
            $payload['agent_id'] = $agentId;
        }

        if ($systemPrompt !== null) {
            $payload['system_prompt'] = $systemPrompt;
        }

        if ($modelConfig !== null) {
            $payload['model_config'] = $modelConfig;
        }

        if ($fileContent) {
            $payload['attachment'] = $fileContent; 
        }
 
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->key])
                ->timeout(120)
                ->post("{$this->url}/api/ai/chat", $payload);
            if (!$response->successful()) { 
                $body = $response->json();
                // Surface Laravel validation messages cleanly (422)
                if ($response->status() === 422 && !empty($body['errors'])) {
                    $first = array_values(array_merge(...array_values($body['errors'])))[0] ?? ($body['message'] ?? 'Validation error');
                    return ['success' => false, 'message' => $first];
                }
  
                $message = $body['message'] ?? null; 
                $debugError = is_string($body['debug_error'] ?? null) ? $body['debug_error'] : '';

                // Prefer clear upstream message instead of generic "500".
                if (!$message) {
                    $message = 'AI service error: ' . $response->status();
                }

                // Anthropic quota/billing issue: provide a user-facing actionable message.
                if ($debugError !== '' && stripos($debugError, 'credit balance is too low') !== false) {
                    $message = 'AI service is temporarily unavailable due to low API credits. Please contact support.';
                }

                return ['success' => false, 'message' => $message, 'detail' => $response->body()];
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
