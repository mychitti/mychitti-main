<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ClaudeService;
use App\Services\MemoryService;
use App\Services\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIChatController extends Controller
{
    public function __construct(
        private ClaudeService $claude,
        private MemoryService $memory,
        private RagService $rag
    ) {}

    public function chat(Request $request) 
    {
        try {
            $request->validate([
                'user_id'       => 'required|integer',
                'guard'         => 'required|string|in:user,admin,vendor,agent_test,guest',
                'message'       => 'nullable|string|max:50000',
                'attachment'    => 'nullable|array',
                'agent_id'      => 'nullable|integer',
                'system_prompt' => 'nullable|string',
            ]);
            $guard = $request->string('guard')->toString();

            // ── agent_test: stateless, no memory, uses passed system prompt ──
            if ($guard === 'agent_test') {
                return $this->handleAgentTest($request);
            }

            $ownerId      = $request->integer('user_id');
            $agentId      = $request->input('agent_id') ? (int) $request->input('agent_id') : null;
            $finalMessage = trim($request->input('message', ''));
            $fileContent  = $request->input('attachment');
            $msgType      = $request->input('type', 'text'); 
            $incomingModelConfig = $request->input('model_config');

            if ($finalMessage === '' && !$fileContent) {
                return response()->json(['success' => false, 'message' => 'Empty message.'], 422);
            }

            // ── Load error handling settings from agent ───────
            $retryCount      = 1;
            $backoffStrategy = 'linear';
            $fallbackMessage = "Sorry, I couldn't complete this action right now. Please try again in a moment or contact support.";
            $escalationRules = [];

            // Prefer explicitly provided runtime model config from caller (main app).
            $modelConfig = is_array($incomingModelConfig) ? $incomingModelConfig : null;

            if ($agentId) {
                $agentRow = DB::table('system_prompts')->where('id', $agentId)->first();
                if ($agentRow) {
                    $retryCount      = (int) ($agentRow->retry_count ?? 1);
                    $backoffStrategy = $agentRow->backoff_strategy ?? 'linear';
                    if (!empty($agentRow->fallback_message)) {
                        $fallbackMessage = $agentRow->fallback_message;
                    }
                    $escalationRules = json_decode($agentRow->escalation_rules ?? '[]', true) ?: [];

                    // Only fall back to ai_service DB model settings if caller did not pass one.
                    if ($modelConfig === null) {
                        $modelConfig = [
                            'ai_provider'      => $agentRow->ai_provider      ?: 'anthropic',
                            'ai_model'         => $agentRow->ai_model          ?: null,
                            'max_tokens'       => $agentRow->max_tokens        ?: null,
                            'temperature'      => strlen((string) $agentRow->temperature) ? (float) $agentRow->temperature : null,
                            'top_p'            => strlen((string) $agentRow->top_p)        ? (float) $agentRow->top_p        : null,
                            'api_key_override' => $agentRow->api_key_override  ?: null,
                        ];
                    }
                }
            }
 
            $savedText = $finalMessage ?: '[File uploaded]';
            $this->memory->saveMessage($ownerId, 'user', $savedText, $msgType, $guard);

            [$system, $history] = $this->memory->buildContext($ownerId, $guard, $agentId);

            $ragContext = $this->rag->search($finalMessage, $guard);
            if ($ragContext) {
                $system = "IMPORTANT: The following knowledge base information is accurate and must be used to answer the user's question. Always prioritize this over any general assumptions:\n\n"
                    . $ragContext
                    . "\n\n---\n\n"
                    . $system;
            }

            $userContent = [];
            if ($fileContent) {
                $userContent[] = $fileContent;
            }
            if ($finalMessage !== '') {
                $userContent[] = ['type' => 'text', 'text' => $finalMessage];
            } elseif ($fileContent) {
                $userContent[] = ['type' => 'text', 'text' => 'Please analyze this file.'];
            }

            $history[] = ['role' => 'user', 'content' => $userContent];

            // ── Retry loop with backoff ───────────────────────
            $reply     = null;
            $lastError = null;
            $errorCode = null;

            for ($attempt = 0; $attempt <= $retryCount; $attempt++) {
                try {
                    $reply = $this->claude->chat($history, $system, 4096, $modelConfig, $ownerId, $guard, $agentId);
                    break;
                } catch (\Exception $e) {
                    $lastError = $e;
                    $errorCode = $this->detectErrorCode($e->getMessage());

                    // Don't retry auth errors — they won't resolve on retry
                    if ($errorCode === 'auth_error') break;

                    if ($attempt < $retryCount) {
                        $wait = match ($backoffStrategy) {
                            'exponential' => (int) pow(2, $attempt + 1), // 2s, 4s, 8s
                            'linear'      => 2,
                            default       => 0,
                        };
                        if ($wait > 0) sleep($wait);
                    }
                }
            }

            // ── All retries exhausted ─────────────────────────
            if ($reply === null) {
                $responseMsg = $this->resolveEscalation($errorCode, $escalationRules, $fallbackMessage);
                Log::warning('Agent chat failed after retries', [
                    'agent_id' => $agentId,
                    'attempts' => $attempt,
                    'error'    => $lastError?->getMessage(),
                    'code'     => $errorCode,
                ]);
                return response()->json(['success' => false, 'message' => $responseMsg, 'debug_error' => $lastError?->getMessage(), 'server' => gethostname()], 500);
            }

            $this->memory->saveMessage($ownerId, 'assistant', $reply, 'text', $guard);
            $this->memory->maybeSummarize($ownerId, $guard);

            return response()->json([ 
                'success'   => true,
                'message'   => $reply,
                'debug'     => $this->claude->getDebugLog(),
                'server'    => gethostname(),
            ]);

        } catch (\Exception $e) {
            return response()->json(
                ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage(), 'server' => gethostname()],
                500
            );
        }
    }

    // ── Detect error category from exception message ─────────────────────
    private function detectErrorCode(string $message): ?string
    {
        if (preg_match('/status[:\s]+40[13]/i', $message)) return 'auth_error';
        if (preg_match('/status[:\s]+429/i',    $message)) return 'rate_limit';
        if (preg_match('/status[:\s]+5\d\d/i',  $message)) return 'write_error';
        if (preg_match('/timeout|timed.?out/i',  $message)) return 'timeout';
        return null;
    }

    // ── Pick response message based on escalation rules ──────────────────
    private function resolveEscalation(?string $errorCode, array $rules, string $fallback): string
    {
        if ($errorCode && !empty($rules[$errorCode])) {
            return match ($errorCode) {
                'auth_error'  => 'Authentication failed. Please log out and log in again.',
                'rate_limit'  => 'The AI service is currently busy. Please try again in a few moments.',
                'write_error' => 'An error occurred while processing your request. Our team has been notified.',
                'timeout'     => $fallback, // "log and skip silently" — just return fallback
                default       => $fallback,
            };
        }
        return $fallback;
    }

    // ── agent_test: stateless, uses passed system prompt + optional history ─
    private function handleAgentTest(Request $request): \Illuminate\Http\JsonResponse
    {
        $message      = trim($request->input('message', ''));
        $systemPrompt = trim($request->input('system_prompt', ''));
        $fileContent  = $request->input('attachment');
        $modelConfig  = $request->input('model_config');
        $historyInput = $request->input('history', []);

        if ($message === '' && !$fileContent) {
            return response()->json(['success' => false, 'message' => 'Empty message.'], 422);
        }

        // Build multi-turn history from previous turns
        $history = [];
        foreach ((array) $historyInput as $turn) {
            if (!empty($turn['role']) && !empty($turn['content'])) {
                $history[] = [
                    'role'    => $turn['role'],
                    'content' => [['type' => 'text', 'text' => (string) $turn['content']]],
                ];
            }
        }

        $userContent = [];
        if ($fileContent) {
            $userContent[] = $fileContent;
        }
        if ($message !== '') {
            $userContent[] = ['type' => 'text', 'text' => $message];
        } elseif ($fileContent) {
            $userContent[] = ['type' => 'text', 'text' => 'Please analyze this file.'];
        }
        $history[] = ['role' => 'user', 'content' => $userContent];

        try {
            $reply = $this->claude->chat($history, $systemPrompt ?: '', 4096, $modelConfig ?: null);
            return response()->json(['success' => true, 'message' => $reply]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Claude error: ' . $e->getMessage()], 500);
        }
    }

    public function history(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer',
                'guard'   => 'required|string|in:user,admin,vendor,agent_test,guest',
            ]);

            $ownerId = $request->integer('user_id');
            $guard   = $request->string('guard')->toString();
            // Use MemoryService::col() mapping: 'guest' → 'user_id', others → '{guard}_id'
            $col     = $this->memory->col($guard);

            $messages = \App\Models\AIChatMessage::where($col, $ownerId)
                ->where('summarized', false)
                ->orderBy('created_at', 'asc')
                ->get(['role', 'content', 'type', 'created_at']);

            return response()->json(['success' => true, 'messages' => $messages]);
        } catch (\Exception $e) {
            return response()->json(
                ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()],
                500
            );
        }
    }

    public function clearMemory(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer',
                'guard'   => 'required|string|in:user,admin,vendor,agent_test,guest',
            ]);

            $this->memory->clearAll($request->integer('user_id'), $request->string('guard')->toString());

            return response()->json(['success' => true, 'message' => 'All memory cleared.']);
        } catch (\Exception $e) {
            return response()->json(
                ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()],
                500
            );
        }
    }
}
