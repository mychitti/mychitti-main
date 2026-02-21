<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ClaudeService;
use App\Services\MemoryService;
use Illuminate\Http\Request;

class AIChatController extends Controller 
{
    public function __construct(
        private ClaudeService $claude,
        private MemoryService $memory
    ) {}

    public function chat(Request $request)
    {
        $request->validate([
            'user_id'    => 'required|integer',
            'guard'      => 'required|string|in:user,admin,vendor',
            'message'    => 'nullable|string|max:50000',
            'attachment' => 'nullable|array',
        ]);

        $ownerId     = $request->integer('user_id');
        $guard       = $request->string('guard')->toString();
        $finalMessage = trim($request->input('message', ''));
        $fileContent  = $request->input('attachment');

        if ($finalMessage === '' && !$fileContent) {
            return response()->json(['success' => false, 'message' => 'Empty message.'], 422);
        }

        $savedText = $finalMessage ?: '[File uploaded]';
        $this->memory->saveMessage($ownerId, 'user', $savedText, 'text', $guard);

        [$system, $history] = $this->memory->buildContext($ownerId, $guard);

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

        $reply = $this->claude->chat($history, $system);

        $this->memory->saveMessage($ownerId, 'assistant', $reply, 'text', $guard);
        $this->memory->maybeSummarize($ownerId, $guard);

        return response()->json(['success' => true, 'message' => $reply]);
    }

    public function history(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'guard'   => 'required|string|in:user,admin,vendor',
        ]);

        $ownerId = $request->integer('user_id');
        $guard   = $request->string('guard')->toString();
        $col     = $guard . '_id';

        $messages = \App\Models\AIChatMessage::where($col, $ownerId)
            ->where('summarized', false)
            ->orderBy('created_at', 'asc')
            ->get(['role', 'content', 'type', 'created_at']);

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    public function clearMemory(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'guard'   => 'required|string|in:user,admin,vendor',
        ]);

        $this->memory->clearAll($request->integer('user_id'), $request->string('guard')->toString());

        return response()->json(['success' => true, 'message' => 'All memory cleared.']);
    }
}
