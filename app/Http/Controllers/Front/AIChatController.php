<?php

namespace App\Http\Controllers\Front;
 
use App\Http\Controllers\Controller;
use App\Services\OpenAIService;
use App\Services\AiServiceClient;
use Illuminate\Http\Request;

class AIChatController extends Controller
{
    public function __construct( 
        private OpenAIService $openai,
        private AiServiceClient $aiService
    ) {}

    // ── Resolve user ID: real auth ID or session-based guest pseudo-ID ────

    private function resolveUserId(): int
    {
        if ($id = auth('web')->id()) {
            return $id;
        }

        if ($id = auth('customer')->id()) {
            return $id;
        }

        // Guest: stable pseudo-ID for this browser session (100M–999M range)
        if (!session()->has('ai_guest_id')) {
            session(['ai_guest_id' => random_int(100_000_000, 999_999_999)]);
        }

        return session('ai_guest_id');
    }

    private function resolveGuard(): string
    {
        return (auth('web')->check() || auth('customer')->check()) ? 'user' : 'guest';
    }

    // ── Chat ──────────────────────────────────────────────────────────────

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string|max:10000',
            'file'    => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'voice'   => 'nullable|file|mimes:webm,wav,mp3,m4a|max:10240',
        ]);

        $userId      = $this->resolveUserId();
        $guard       = $this->resolveGuard();
        $message     = $request->input('message') ?? '';
        $fileContent = null;

        // File upload (image or PDF) → base64 content block
        if ($request->hasFile('file')) {
            $file   = $request->file('file');
            $mime   = $file->getMimeType();
            $base64 = base64_encode(file_get_contents($file->getRealPath()));

            if ($mime === 'application/pdf') {
                $fileContent = [
                    'type'   => 'document',
                    'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $base64],
                ];
            } elseif (str_starts_with($mime, 'image/')) {
                $fileContent = [
                    'type'   => 'image',
                    'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $base64],
                ];
            }
        }

        // Voice → Text (OpenAI Whisper — stays local)
        if ($request->hasFile('voice')) {
            $voiceFile = $request->file('voice');
            $mimeToExt = [
                'audio/webm' => 'webm', 'audio/wav' => 'wav', 'audio/wave' => 'wav',
                'audio/x-wav' => 'wav', 'audio/mpeg' => 'mp3', 'audio/mp3' => 'mp3',
                'audio/mp4' => 'mp4', 'audio/m4a' => 'm4a', 'audio/x-m4a' => 'm4a',
                'audio/ogg' => 'ogg', 'video/webm' => 'webm', 'video/mp4' => 'mp4',
                'application/octet-stream' => 'wav',
            ];
            $extension = $mimeToExt[$voiceFile->getMimeType()] ?? $voiceFile->getClientOriginalExtension() ?? 'wav';
            $tmpPath   = storage_path('app/tmp') . '/' . uniqid('voice_') . '.' . $extension;
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
            return response()->json(['success' => false, 'message' => 'Empty message.'], 422);
        }

        $result = $this->aiService->chat($userId, $guard, $finalMessage, $fileContent);

        return response()->json($result);
    }

    // ── History ───────────────────────────────────────────────────────────

    public function history()
    {
        $result = $this->aiService->history($this->resolveUserId(), $this->resolveGuard());
        return response()->json($result);
    }

    // ── Clear memory ──────────────────────────────────────────────────────

    public function clearMemory()
    {
        $result = $this->aiService->clearMemory($this->resolveUserId(), $this->resolveGuard());
        return response()->json($result);
    }
}
