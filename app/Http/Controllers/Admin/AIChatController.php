<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AIChatMessage;
use App\Services\OpenAIService;
use App\Services\AiServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AIChatController extends Controller  
{ 
    public function __construct(
        private OpenAIService $openai,
        private AiServiceClient $aiService
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

        $adminId     = auth('admin')->id();
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
        $result = $this->aiService->chat($adminId, 'admin', $finalMessage, $fileContent, type: $msgType);

        if ($request->hasFile('voice') && !empty($result['success']) && !empty($result['message'])) {
            try {
                $plainText = preg_replace('/[#*_`~\[\]()>|\\\\-]/', '', $result['message']);
                $ttsVoice = \Illuminate\Support\Facades\DB::table('system_prompts')
                    ->where('user_type', 'admin')
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
        $result = $this->aiService->history(auth('admin')->id(), 'admin');
        return response()->json($result);
    }

    public function tts(Request $request)
    {
        $request->validate(['text' => 'required|string|max:4096']);

        try {
            $ttsVoice = \Illuminate\Support\Facades\DB::table('system_prompts')
                ->where('user_type', 'admin')
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
        $result = $this->aiService->clearMemory(auth('admin')->id(), 'admin');
        return response()->json($result);
    }

    // ── Chat Logs ─────────────────────────────────────────────

    public function chatLogs(Request $request)
    {
        $type = $request->get('type', 'all'); // all, user, vendor, admin

        $query = AIChatMessage::select(
                DB::raw("COALESCE(user_id, 0) as user_id"),
                DB::raw("COALESCE(vendor_id, 0) as vendor_id"),
                DB::raw("COALESCE(admin_id, 0) as admin_id"),
                DB::raw("COUNT(*) as message_count"),
                DB::raw("MAX(created_at) as last_message_at"),
                DB::raw("CASE WHEN user_id > 0 THEN 'user' WHEN vendor_id > 0 THEN 'vendor' WHEN admin_id > 0 THEN 'admin' ELSE 'guest' END as chat_type")
            )
            ->groupBy('user_id', 'vendor_id', 'admin_id');

        if ($type === 'user') {
            $query->where('user_id', '>', 0);
        } elseif ($type === 'vendor') {
            $query->where('vendor_id', '>', 0);
        } elseif ($type === 'admin') {
            $query->where('admin_id', '>', 0);
        }

        $conversations = $query->orderByDesc('last_message_at')->paginate(20);

        // Eager load names
        $userIds = $conversations->where('user_id', '>', 0)->pluck('user_id')->toArray(); 
        $vendorIds = $conversations->where('vendor_id', '>', 0)->pluck('vendor_id')->toArray();
        $adminIds = $conversations->where('admin_id', '>', 0)->pluck('admin_id')->toArray();

        $users = !empty($userIds) ? DB::table('users')->whereIn('id', $userIds)->pluck(DB::raw("CONCAT(f_name, ' ', l_name)"), 'id')->toArray() : [];
        $vendors = !empty($vendorIds) ? DB::table('vendors')->whereIn('id', $vendorIds)->pluck(DB::raw("CONCAT(f_name, ' ', l_name)"), 'id')->toArray() : [];
        $admins = !empty($adminIds) ? DB::table('admins')->whereIn('id', $adminIds)->pluck(DB::raw("CONCAT(f_name, ' ', l_name)"), 'id')->toArray() : [];

        return view('admin-views.ai-chat.logs', compact('conversations', 'users', 'vendors', 'admins', 'type'));
    }

    public function chatLogDetail($type, $id)
    {
        $col = $type . '_id';
        $messages = AIChatMessage::where($col, $id)->orderBy('created_at', 'asc')->paginate(50);

        $name = match ($type) {
            'user' => DB::table('users')->where('id', $id)->value(DB::raw("CONCAT(f_name, ' ', l_name)")) ?? "User #$id",
            'vendor' => DB::table('vendors')->where('id', $id)->value(DB::raw("CONCAT(f_name, ' ', l_name)")) ?? "Vendor #$id",
            'admin' => DB::table('admins')->where('id', $id)->value(DB::raw("CONCAT(f_name, ' ', l_name)")) ?? "Admin #$id",
            default => "Unknown #$id",
        };

        return view('admin-views.ai-chat.log-detail', compact('messages', 'type', 'id', 'name'));
    }

    // ── Analytics ─────────────────────────────────────────────

    public function analytics(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $from = now()->subDays($days)->startOfDay();

        // 1. Overview stats
        $totalMessages    = AIChatMessage::where('created_at', '>=', $from)->count();
        $totalUsers       = AIChatMessage::where('created_at', '>=', $from)->where('user_id', '>', 0)->distinct('user_id')->count('user_id');
        $totalVendors     = AIChatMessage::where('created_at', '>=', $from)->where('vendor_id', '>', 0)->distinct('vendor_id')->count('vendor_id');
        $totalAdmins      = AIChatMessage::where('created_at', '>=', $from)->where('admin_id', '>', 0)->distinct('admin_id')->count('admin_id');
        $userMessages     = AIChatMessage::where('created_at', '>=', $from)->where('role', 'user')->count();
        $assistantMessages = AIChatMessage::where('created_at', '>=', $from)->where('role', 'assistant')->count();
        $voiceMessages    = AIChatMessage::where('created_at', '>=', $from)->where('type', 'voice')->count();

        // 2. Messages per day (chart data)
        $dailyMessages = AIChatMessage::select(
                DB::raw("DATE(created_at) as date"),
                DB::raw("SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_msgs"),
                DB::raw("SUM(CASE WHEN role = 'assistant' THEN 1 ELSE 0 END) as ai_msgs"),
                DB::raw("COUNT(*) as total")
            )
            ->where('created_at', '>=', $from)
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy('date')
            ->get();

        // 3. Most active users (top 10)
        $topUsers = AIChatMessage::select(
                'user_id',
                DB::raw("COUNT(*) as msg_count"),
                DB::raw("MAX(created_at) as last_active")
            )
            ->where('created_at', '>=', $from)
            ->where('user_id', '>', 0)
            ->where('role', 'user')
            ->groupBy('user_id')
            ->orderByDesc('msg_count')
            ->limit(10)
            ->get();

        $topUserNames = [];
        if ($topUsers->isNotEmpty()) {
            $topUserNames = DB::table('users')
                ->whereIn('id', $topUsers->pluck('user_id'))
                ->pluck(DB::raw("CONCAT(f_name, ' ', l_name)"), 'id')
                ->toArray();
        }

        // 4. Most active vendors (top 10)
        $topVendors = AIChatMessage::select(
                'vendor_id',
                DB::raw("COUNT(*) as msg_count"),
                DB::raw("MAX(created_at) as last_active")
            )
            ->where('created_at', '>=', $from)
            ->where('vendor_id', '>', 0)
            ->where('role', 'user')
            ->groupBy('vendor_id')
            ->orderByDesc('msg_count')
            ->limit(10)
            ->get();

        $topVendorNames = [];
        if ($topVendors->isNotEmpty()) {
            $topVendorNames = DB::table('vendors')
                ->whereIn('id', $topVendors->pluck('vendor_id'))
                ->pluck(DB::raw("CONCAT(f_name, ' ', l_name)"), 'id')
                ->toArray();
        }

        // 5. Message type breakdown
        $typeBreakdown = AIChatMessage::select('type', DB::raw("COUNT(*) as count"))
            ->where('created_at', '>=', $from)
            ->where('role', 'user')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // 6. Avg messages per conversation
        $convCounts = AIChatMessage::select(
                DB::raw("COALESCE(user_id, 0) as uid"),
                DB::raw("COALESCE(vendor_id, 0) as vid"),
                DB::raw("COALESCE(admin_id, 0) as aid"),
                DB::raw("COUNT(*) as cnt")
            )
            ->where('created_at', '>=', $from)
            ->groupBy('uid', 'vid', 'aid')
            ->pluck('cnt');

        $avgMsgsPerConv = $convCounts->count() > 0 ? round($convCounts->avg(), 1) : 0;
        $totalConversations = $convCounts->count();

        // 7. Hourly distribution
        $hourlyDistribution = AIChatMessage::select(
                DB::raw("HOUR(created_at) as hour"),
                DB::raw("COUNT(*) as count")
            )
            ->where('created_at', '>=', $from)
            ->where('role', 'user')
            ->groupBy(DB::raw("HOUR(created_at)"))
            ->pluck('count', 'hour')
            ->toArray();

        return view('admin-views.ai-chat.analytics', compact(
            'days', 'totalMessages', 'totalUsers', 'totalVendors', 'totalAdmins',
            'userMessages', 'assistantMessages', 'voiceMessages',
            'dailyMessages', 'topUsers', 'topUserNames', 'topVendors', 'topVendorNames',
            'typeBreakdown', 'avgMsgsPerConv', 'totalConversations', 'hourlyDistribution'
        ));
    }

    // ── Export ─────────────────────────────────────────────────

    public function exportChatLogs(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $type = $request->get('type', 'all');
        $from = now()->subDays($days)->startOfDay();

        $query = AIChatMessage::where('created_at', '>=', $from)->orderBy('created_at', 'asc');

        if ($type === 'user') {
            $query->where('user_id', '>', 0);
        } elseif ($type === 'vendor') {
            $query->where('vendor_id', '>', 0);
        } elseif ($type === 'admin') {
            $query->where('admin_id', '>', 0);
        }

        $messages = $query->get();

        $csv = "ID,User ID,Vendor ID,Admin ID,Role,Type,Content,Created At\n";
        foreach ($messages as $msg) {
            $content = str_replace('"', '""', $msg->content);
            $csv .= "{$msg->id},{$msg->user_id},{$msg->vendor_id},{$msg->admin_id},{$msg->role},{$msg->type},\"{$content}\",{$msg->created_at}\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=chat-logs-{$type}-{$days}d.csv",
        ]);
    }
}
