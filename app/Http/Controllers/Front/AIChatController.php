<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\ClaudeService;
use App\Services\OpenAIService;
use App\Services\MemoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIChatController extends Controller
{ 
    public function __construct(
        private OpenAIService $openai,
        private ClaudeService $claude,
        private MemoryService $memory
    ) {}

    public function chat(Request $request)
    {
        // --- TEMP DEBUG ---
        $phpUploadErrors = [
            0 => 'UPLOAD_ERR_OK',
            1 => 'UPLOAD_ERR_INI_SIZE',
            2 => 'UPLOAD_ERR_FORM_SIZE',
            3 => 'UPLOAD_ERR_PARTIAL',
            4 => 'UPLOAD_ERR_NO_FILE',
            6 => 'UPLOAD_ERR_NO_TMP_DIR',
            7 => 'UPLOAD_ERR_CANT_WRITE',
            8 => 'UPLOAD_ERR_EXTENSION',
        ];
        if (isset($_FILES['file'])) {
            $errCode = $_FILES['file']['error'];
            \Log::error('AI Chat file upload error', [
                'php_error_code' => $errCode,
                'php_error_name' => $phpUploadErrors[$errCode] ?? 'UNKNOWN',
                'file_name'      => $_FILES['file']['name'] ?? null,
                'file_size'      => $_FILES['file']['size'] ?? null,
                'tmp_name'       => $_FILES['file']['tmp_name'] ?? null,
                'tmp_writable'   => is_writable(sys_get_temp_dir()),
                'tmp_dir'        => sys_get_temp_dir(),
            ]);
        }
        // --- END DEBUG ---

        $request->validate([
            'message' => 'nullable|string|max:10000',
            'file'    => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'voice'   => 'nullable|file|mimes:webm,wav,mp3,m4a|max:10240',
        ]);

        $userId = auth('web')->id();

        $message     = $request->input('message') ?? '';
        $fileContent = null;

        // File upload (image or PDF)
        if ($request->hasFile('file')) {
            $file   = $request->file('file');
            $mime   = $file->getMimeType();
            $base64 = base64_encode(file_get_contents($file->getRealPath()));

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

        // Voice → Text (OpenAI Whisper)
        if ($request->hasFile('voice')) {
            $voiceFile = $request->file('voice');

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
                'video/webm'       => 'webm',
                'video/mp4'        => 'mp4',
                'application/octet-stream' => 'wav',
            ];

            $mimeType  = $voiceFile->getMimeType();
            $extension = $mimeToExt[$mimeType]
                      ?? $voiceFile->getClientOriginalExtension()
                      ?? 'wav';

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
                if (file_exists($tmpPath)) {
                    unlink($tmpPath);
                }
            }
        }

        $finalMessage = trim($message);

        if ($finalMessage === '' && !$fileContent) {
            return response()->json([
                'success' => false,
                'message' => 'Empty message.',
            ], 422);
        }

        $savedText = $finalMessage ?: '[File uploaded]';
        $this->memory->saveMessage($userId, 'user', $savedText, 'text', 'user');

        [$system, $history] = $this->memory->buildContext($userId, 'user');

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

        $reply = $this->openai->chat($history, $system);

        $this->memory->saveMessage($userId, 'assistant', $reply, 'text', 'user');

        $this->memory->maybeSummarize($userId, 'user');

        return response()->json([
            'success' => true,
            'message' => $reply,
        ]);
    }

    public function history() 
    {
        $userId = auth('web')->id();

        $messages = \App\Models\AIChatMessage::where('user_id', $userId)
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
        $this->memory->clearAll(auth('web')->id(), 'user');

        return response()->json([
            'success' => true,
            'message' => 'All memory cleared.',
        ]);
    }
}
