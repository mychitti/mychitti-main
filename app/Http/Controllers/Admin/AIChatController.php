<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ClaudeService;
use App\Services\MemoryService;
use Illuminate\Http\Request;

class AIChatController extends Controller
{
    public function __construct(
        private ClaudeService $claude,
        private MemoryService $memory,
            private \OpenAI\Client $openai   // ← add this
    ) {}

    public function index()
    {
        return view('admin-views.ai-chat.index');
    }

public function chat(Request $request)
{
    $request->validate([
        'message' => 'nullable|string|max:10000',
        'file'    => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        'voice'   => 'nullable|file|mimes:webm,wav,mp3,m4a|max:10240',
    ]);

    $adminId = auth('admin')->id();

    $message      = $request->input('message') ?? '';
    $fileContent  = null; // multimodal content block for Claude

    // ---------------------------
    // File upload (image or PDF)
    // ---------------------------
    if ($request->hasFile('file')) {
        $file     = $request->file('file');
        $mime     = $file->getMimeType();
        $base64   = base64_encode(file_get_contents($file->getRealPath()));

        if ($mime === 'application/pdf') {
            $fileContent = [
                'type'   => 'document',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => 'application/pdf',
                    'data'       => $base64,
                ],
            ];
        } elseif (str_starts_with($mime, 'image/')) {
            $fileContent = [
                'type'   => 'image',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => $mime,
                    'data'       => $base64,
                ],
            ];
        }
    }

    // --------------------------- 
    // Voice → Text (OpenAI Whisper)
    // ---------------------------
   if ($request->hasFile('voice')) {

    $voiceFile = $request->file('voice');

    // Map Laravel mime → Whisper-accepted extension
    $mimeToExt = [
        'audio/webm'       => 'webm', 
        'audio/wav'        => 'wav',
        'audio/wave'       => 'wav',
        'audio/x-wav'      => 'wav',
        'audio/mpeg'       => 'mp3',
        'audio/mp3'        => 'mp3',
        'audio/mp4'        => 'mp4',
        'audio/m4a'        => 'm4a',
        'audio/x-m4a'      => 'm4a',
        'audio/ogg'        => 'ogg',
        'video/webm'       => 'webm', // browsers often send webm as video/webm
        'video/mp4'        => 'mp4',
        'application/octet-stream' => 'wav', // fallback
    ];

    $mimeType  = $voiceFile->getMimeType();             // detected by Laravel
    $extension = $mimeToExt[$mimeType]
              ?? $voiceFile->getClientOriginalExtension() // fallback to uploaded name
              ?? 'wav';

    // Copy to a temp file with the correct extension so Whisper knows the format
    $tmpPath = sys_get_temp_dir() . '/' . uniqid('voice_') . '.' . $extension;
    copy($voiceFile->getRealPath(), $tmpPath);

    try {
        $transcript = $this->openai->audio()->transcribe([
            'model' => 'whisper-1',
            'file'  => fopen($tmpPath, 'r'),
        ]); 

        if (!empty($transcript->text)) {
            $message = $transcript->text;
        }

    } finally {
        // Always clean up temp file
        if (file_exists($tmpPath)) {
            unlink($tmpPath);
        }
    }
}
    // ---------------------------
    // Final message assembly
    // ---------------------------
    $finalMessage = trim($message);

    if ($finalMessage === '' && !$fileContent) {
        return response()->json([
            'success' => false,
            'message' => 'Empty message.',
        ], 422);
    }

    // Save user message (text only for DB storage)
    $savedText = $finalMessage ?: '[File uploaded]';
    $this->memory->saveMessage($adminId, 'user', $savedText);

    [$system, $history] = $this->memory->buildContext($adminId);

    // Build multimodal content array for Claude
    $userContent = [];
    if ($fileContent) {
        $userContent[] = $fileContent;
    }
    if ($finalMessage !== '') {
        $userContent[] = ['type' => 'text', 'text' => $finalMessage];
    } elseif ($fileContent) {
        $userContent[] = ['type' => 'text', 'text' => 'Please analyze this file.'];
    }

    $history[] = [
        'role'    => 'user',
        'content' => $userContent,
    ];

    $reply = $this->claude->chat($history, $system);

    $this->memory->saveMessage($adminId, 'assistant', $reply);

    $this->memory->maybeSummarize($adminId);

    return response()->json([
        'success' => true,
        'message' => $reply,
    ]);
}

    public function history()
    {
        $adminId = auth('admin')->id();

        $messages = \App\Models\AIChatMessage::where('admin_id', $adminId)
            ->where('summarized', false)
            ->orderBy('created_at', 'asc')
            ->get(['role', 'content', 'type', 'created_at']);

        return response()->json([
            'success'  => true,
            'messages' => $messages,
        ]);
    }

    public function clearMemory()
    {
        $this->memory->clearAll(auth('admin')->id());

        return response()->json([
            'success' => true,
            'message' => 'All memory cleared.',
        ]);
    }
}
