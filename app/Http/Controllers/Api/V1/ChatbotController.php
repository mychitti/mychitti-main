<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Services\ClaudeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    private ClaudeService $claude;
 
    public function __construct(ClaudeService $claude) 
    {
        $this->claude = $claude;
    }

    /**
     * POST /api/v1/chatbot/message
     *
     * Body: 
     *   message     string  (required)   — the user's latest message
     *   session_id  string  (optional)   — omit to start a new conversation
     *   provider    string  (optional)   — 'anthropic' | 'openai' | 'gemini'  (default: anthropic)
     *   model       string  (optional)   — override the default model for the provider
     *
     * Response:
     *   { reply, session_id, provider }
     *
     * History is stored server-side in cache (24 h). The client never manages history.
     */
    public function message(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message'    => 'required|string|max:5000',
            'session_id' => 'nullable|string|max:64',
            'provider'   => 'nullable|string|in:anthropic,openai,google,custom',
            'model'      => 'nullable|string|max:100',
            'custom_url' => 'nullable|url',          // required when provider=custom
            'custom_key' => 'nullable|string|max:200', // API key for custom endpoint
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // Resolve or create a session ID
        $sessionId = $request->input('session_id') ?: (string) Str::uuid();
        $cacheKey  = 'chatbot_history_' . $sessionId;

        // Load existing history from cache
        $history = Cache::get($cacheKey, []);

        // Append the new user message
        $history[] = [
            'role'    => 'user',
            'content' => $request->input('message'),
        ];

        // Resolve provider / model
        $provider   = $request->input('provider', 'anthropic');
        $model      = $request->input('model') ?: null;       // null = use provider default
        $customUrl  = $request->input('custom_url') ?: null;  // for custom/self-hosted
        $customKey  = $request->input('custom_key') ?: null;

        // System prompt
        $system = 'You are a helpful assistant for My Chitti, a local service marketplace. '
            . 'Help users with questions about services, bookings, and general support. '
            . 'Be friendly, concise, and helpful. '
            . 'If the user wants to raise a complaint or leave feedback, use the store_query tool to save it.';

        try {
            $reply = $this->claude->chat($history, $system, 4096, $provider, $model, $customUrl, $customKey);
        } catch (\Throwable $e) {
            return response()->json([
                'error'       => 'Failed to get a response. Please try again.',
                'err_details' => $e->getMessage(),
            ], 403);
        }

        // Append assistant reply and save back to cache (24 h, max 40 turns)
        $history[] = [
            'role'    => 'assistant',
            'content' => $reply,
        ];

        Cache::put($cacheKey, array_slice($history, -40), now()->addHours(24));

        return response()->json([
            'reply'      => $reply,
            'session_id' => $sessionId,
            'provider'   => $provider,
        ]);
    }
}
