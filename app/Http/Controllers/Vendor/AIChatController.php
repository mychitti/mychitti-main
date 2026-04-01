<?php

namespace App\Http\Controllers\Vendor;

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
 
    public function index()
    { 
        return view('vendor-views.ai-chat.index'); 
    } 

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string|max:10000',
            'file'    => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:30720',
            'voice'   => 'nullable|file|mimes:webm,wav,mp3,m4a|max:30720',
        ]);

        $vendorId    = auth('vendor')->id();
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

        $msgType = $request->hasFile('voice') ? 'voice' : ($request->hasFile('file') ? 'file' : 'text');
        $result = $this->aiService->chat($vendorId, 'vendor', $finalMessage, $fileContent, type: $msgType);

        if ($request->hasFile('voice') && !empty($result['success']) && !empty($result['message'])) {
            try {
                $plainText = preg_replace('/[#*_`~\[\]()>|\\\\-]/', '', $result['message']);
                // Resolve the configured TTS voice for this user type's active agent
                $ttsVoice = \Illuminate\Support\Facades\DB::table('system_prompts')
                    ->where('user_type', 'vendor')
                    ->where('status', 'active')
                    ->orderByDesc('updated_at')
                    ->value('tts_voice') ?? 'nova';
                $filename = $this->openai->textToSpeech(mb_substr($plainText, 0, 4096), $ttsVoice);
                $result['audio_url'] = asset('storage/tts/' . $filename);
            } catch (\Exception $e) {}
        } 

        return response()->json($result);
    }

    public function history()
    {
        $result = $this->aiService->history(auth('vendor')->id(), 'vendor');
        return response()->json($result);
    }

    public function tts(Request $request)
    {
        $request->validate(['text' => 'required|string|max:4096']);

        try {
            $ttsVoice = \Illuminate\Support\Facades\DB::table('system_prompts')
                ->where('user_type', 'vendor')
                ->where('status', 'active')
                ->orderByDesc('updated_at')
                ->value('tts_voice') ?? 'nova';
            $filename = $this->openai->textToSpeech($request->input('text'), $ttsVoice);
            return response()->json([
                'success'   => true,
                'audio_url' => asset('storage/tts/' . $filename),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'TTS failed.'], 500);
        }
    }

    public function clearMemory()
    {
        $result = $this->aiService->clearMemory(auth('vendor')->id(), 'vendor');
        return response()->json($result);
    }
}
