<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Services\AiServiceClient;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    private AiServiceClient $ai;
    private OpenAIService $openai;

    public function __construct(AiServiceClient $ai, OpenAIService $openai)
    {
        $this->ai = $ai; 
        $this->openai = $openai;
    }

    /**
     * POST /api/v1/chatbot/message
     *
     * Body:
     *   message     string  (nullable)   — the user's latest message
     *   file        file    (nullable)   — image or PDF attachment
     *   voice       file    (nullable)   — voice recording (webm, wav, mp3, m4a)
     *   session_id  string  (optional)   — UUID to continue an existing conversation
     *
     * Response:
     *   { reply, session_id, audio_url? }
     */
    public function message(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message'    => 'nullable|string|max:5000',
            'file'       => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:30720',
            'voice'      => 'nullable|file|mimes:webm,wav,mp3,m4a|max:30720',
            'session_id' => 'nullable|string|max:64',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $userId = auth('api')->id()
            ?? (int) crc32($request->input('session_id', 'guest'));
        $guard   = auth('api')->check() ? 'user' : 'guest';
        $message = $request->input('message') ?? '';
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

        // Voice → Text (OpenAI Whisper)
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
            return response()->json(['error' => 'Empty message.'], 422);
        }

        $msgType = $request->hasFile('voice') ? 'voice' : ($request->hasFile('file') ? 'file' : 'text');
        $result = $this->ai->chat($userId, $guard, $finalMessage, $fileContent, type: $msgType);

        if (!($result['success'] ?? false)) {
            return response()->json([
                'error' => $result['message'] ?? 'Failed to get a response. Please try again.',
            ], 403);
        }

        $sessionId = $request->input('session_id') ?: (string) \Illuminate\Support\Str::uuid();

        $response = [
            'reply'      => $result['message'],
            'session_id' => $sessionId,
        ];

        // Auto-generate TTS when input was voice
        if ($request->hasFile('voice') && !empty($result['message'])) {
            try {
                $plainText = preg_replace('/[#*_`~\[\]()>|\\\\-]/', '', $result['message']);
                $ttsVoice = DB::table('system_prompts')
                    ->where('user_type', 'user')
                    ->where('status', 'active')
                    ->orderByDesc('updated_at')
                    ->value('tts_voice') ?? 'nova';
                $filename = $this->openai->textToSpeech(mb_substr($plainText, 0, 4096), $ttsVoice);
                $response['audio_url'] = asset('storage/tts/' . $filename);
            } catch (\Exception $e) {
                // TTS failure shouldn't block the response
            }
        }

        return response()->json($response);
    }
}
