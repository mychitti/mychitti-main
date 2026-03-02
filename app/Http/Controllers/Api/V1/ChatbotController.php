<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ClaudeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\CentralLogics\Helpers;

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
     *   message     string  (required) — the user's latest message
     *   session_id  string  (optional) — omit to start a new conversation
     *
     * Response:
     *   { reply, session_id }
     *
     * History is stored server-side in cache; the client never needs to manage it.
     */
    public function message(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'message'    => 'required|string|max:5000',
            'session_id' => 'nullable|string|max:64',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // Resolve or create a session ID
        $sessionId = $request->input('session_id') ?: (string) Str::uuid();
        $cacheKey  = 'chatbot_history_' . $sessionId;

        // Load existing history from cache (24-hour TTL)
        $history = Cache::get($cacheKey, []);

        // Append the new user message
        $history[] = [
            'role'    => 'user',
            'content' => $request->input('message'),
        ];

        // System prompt
        $system = 'You are a helpful assistant for My Chitti, a local service marketplace. '
            . 'Help users with questions about services, bookings, and general support. '
            . 'Be friendly, concise, and helpful. '
            . 'If the user wants to raise a complaint or leave feedback, use the store_query tool to save it.';

        try {
            $reply = $this->claude->chat($history, $system);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to get a response. Please try again.',
                'err_details' => $e->getMessage(),
            ], 403);
        }

        // Append the assistant reply and save back to cache
        $history[] = [
            'role'    => 'assistant',
            'content' => $reply,
        ];

        // Keep history in cache for 24 hours; trim to last 40 turns to avoid huge payloads
        Cache::put($cacheKey, array_slice($history, -40), now()->addHours(24));

        return response()->json([
            'reply'      => $reply,
            'session_id' => $sessionId,
        ]);
    }
}
