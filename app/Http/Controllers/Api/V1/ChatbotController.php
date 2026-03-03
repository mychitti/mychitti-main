<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Services\AiServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    private AiServiceClient $ai;

    public function __construct(AiServiceClient $ai)
    {
        $this->ai = $ai;
    }

    /**
     * POST /api/v1/chatbot/message
     *
     * Body:
     *   message     string  (required)   — the user's latest message
     *   session_id  string  (optional)   — UUID to continue an existing conversation
     *                                      Omit to start a new session (guest user_id auto-assigned)
     *
     * Response:
     *   { reply, session_id }
     *
     * Routes through AiServiceClient → _ai_service (ClaudeService with full multi-provider support).
     * History and memory are managed server-side by _ai_service.
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

        // Use the authenticated user ID, or fall back to a stable guest ID from session_id
        $userId = auth('api')->id()
            ?? (int) crc32($request->input('session_id', 'guest'));

        $result = $this->ai->chat(
            userId:     $userId,
            guard:      auth('api')->check() ? 'user' : 'guest',
            message:    $request->input('message'),
        );

        if (!($result['success'] ?? false)) {
            return response()->json([
                'error' => $result['message'] ?? 'Failed to get a response. Please try again.',
            ], 403);
        }

        // Generate a stable session_id the client can pass back (not used for server-side
        // memory — _ai_service tracks by user_id+guard — but useful for the client to
        // distinguish conversations).
        $sessionId = $request->input('session_id') ?: (string) \Illuminate\Support\Str::uuid();

        return response()->json([
            'reply'      => $result['message'],
            'session_id' => $sessionId,
        ]);
    }
}
