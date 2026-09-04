<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WaChatArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Brian2694\Toastr\Facades\Toastr;

/**
 * Read side of the WhatsApp account archive: which conversations the bridge is capturing,
 * what was said in them, and what the model pulled out (sales, leads, tasks, payments).
 */
class WaChatFeedController extends Controller
{
    public function index(Request $request)
    {
        WaChatArchive::ensureTables();

        $kind   = $request->get('kind');
        $chat   = $request->get('chat');
        $type   = $request->get('type');
        $search = trim((string) $request->get('search'));
        $from   = $request->get('from');
        $to     = $request->get('to');

        $insights = DB::table('wa_chat_insights as i')
            ->leftJoin('wa_chat_messages as m', 'm.id', '=', 'i.message_id')
            ->when($kind, fn($q) => $q->where('i.kind', $kind))
            ->when($chat, fn($q) => $q->where('i.chat_jid', $chat))
            ->when($type, fn($q) => $q->where('m.chat_type', $type))
            ->when($from, fn($q) => $q->whereDate('i.occurred_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('i.occurred_at', '<=', $to))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('i.title', 'like', "%{$search}%")
                        ->orWhere('i.summary', 'like', "%{$search}%")
                        ->orWhere('i.counterparty', 'like', "%{$search}%")
                        ->orWhere('i.assignee', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('i.occurred_at')
            ->orderByDesc('i.id')
            ->paginate(30, [
                'i.*',
                'm.body as message_body',
                'm.sender_name as message_sender',
                'm.chat_name as message_chat',
                'm.chat_type as message_chat_type',
            ])
            ->appends($request->query());

        $counts = DB::table('wa_chat_insights')
            ->when($chat, fn($q) => $q->where('chat_jid', $chat))
            ->select('kind', DB::raw('COUNT(*) as total'))
            ->groupBy('kind')
            ->pluck('total', 'kind');

        // Money booked this month, from whatever the model read as a sale.
        $salesThisMonth = DB::table('wa_chat_insights')
            ->where('kind', 'sale')
            ->whereNotNull('amount')
            ->where('occurred_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $openItems = DB::table('wa_chat_insights')
            ->whereIn('kind', ['task', 'followup'])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'done');
            })
            ->count();

        $chats = DB::table('wa_chats')
            ->orderByDesc('last_message_at')
            ->get(['chat_jid', 'name', 'chat_type', 'phone', 'message_count', 'ai_enabled']);

        $stats = [
            'messages'     => DB::table('wa_chat_messages')->count(),
            'chats'        => $chats->count(),
            'pending'      => self::pendingCount(),
            'last_message' => DB::table('wa_chat_messages')->max('sent_at'),
            'sales_month'  => (float) $salesThisMonth,
            'open_items'   => $openItems,
            'new_leads'    => DB::table('wa_chat_insights')
                ->where('kind', 'lead')
                ->where('occurred_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        // The bridge is a long-lived process on another box; a stale archive is the only
        // symptom an admin ever sees when it dies, so surface it here rather than in a log.
        $stats['bridge_stale'] = $stats['last_message']
            ? Carbon::parse($stats['last_message'])->lt(now()->subHours(12))
            : true;

        return view('admin-views.whatsapp.chat-feed', compact(
            'insights', 'counts', 'stats', 'chats', 'kind', 'chat', 'type', 'search', 'from', 'to'
        ));
    }

    /** The conversation list: what is being archived, and what the AI is allowed to read. */
    public function chats(Request $request)
    {
        WaChatArchive::ensureTables();

        $search = trim((string) $request->get('search'));
        $type   = $request->get('type');

        $chats = DB::table('wa_chats')
            ->when($type, fn($q) => $q->where('chat_type', $type))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('chat_jid', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('last_message_at')
            ->paginate(50)
            ->appends($request->query());

        $totals = [
            'chats'    => DB::table('wa_chats')->count(),
            'groups'   => DB::table('wa_chats')->where('chat_type', 'group')->count(),
            'dms'      => DB::table('wa_chats')->where('chat_type', 'dm')->count(),
            'excluded' => DB::table('wa_chats')->where('ai_enabled', 0)->count(),
        ];

        return view('admin-views.whatsapp.chats', compact('chats', 'totals', 'search', 'type'));
    }

    /** Raw archive, for when someone needs the literal message. */
    public function messages(Request $request)
    {
        WaChatArchive::ensureTables();

        $search = trim((string) $request->get('search'));
        $chat   = $request->get('chat');

        $messages = DB::table('wa_chat_messages')
            ->when($chat, fn($q) => $q->where('chat_jid', $chat))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('body', 'like', "%{$search}%")
                        ->orWhere('sender_name', 'like', "%{$search}%")
                        ->orWhere('sender_phone', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->appends($request->query());

        $chats = DB::table('wa_chats')
            ->orderByDesc('last_message_at')
            ->get(['chat_jid', 'name', 'chat_type', 'message_count']);

        return view('admin-views.whatsapp.chat-messages', compact('messages', 'chats', 'search', 'chat'));
    }

    /** Run the extraction now instead of waiting for the fifteen-minute schedule. */
    public function analyze(Request $request)
    {
        $result = WaChatArchive::analyzePending(60, $request->get('chat') ?: null);

        if ($result['messages'] === 0) {
            Toastr::info(translate('Nothing new to analyse.'));
        } else {
            Toastr::success(translate('Analysed') . " {$result['messages']} " . translate('messages') . " — {$result['insights']} " . translate('insights extracted.'));
        }

        return back();
    }

    /** Stop or resume paying a model to read one conversation. */
    public function toggleChat(Request $request, $id)
    {
        $chat = DB::table('wa_chats')->where('id', $id)->first();
        if (!$chat) {
            Toastr::error(translate('Chat not found.'));
            return back();
        }

        DB::table('wa_chats')->where('id', $id)->update([
            'ai_enabled' => $chat->ai_enabled ? 0 : 1,
            'updated_at' => now(),
        ]);

        Toastr::success($chat->ai_enabled
            ? translate('AI analysis turned off for this chat.')
            : translate('AI analysis turned on for this chat.'));

        return back();
    }

    /**
     * Delete one conversation from the archive entirely — messages and everything read out
     * of them. The way to un-archive a chat that should never have been captured.
     */
    public function destroyChat($id)
    {
        $chat = DB::table('wa_chats')->where('id', $id)->first();
        if (!$chat) {
            Toastr::error(translate('Chat not found.'));
            return back();
        }

        DB::table('wa_chat_insights')->where('chat_jid', $chat->chat_jid)->delete();
        DB::table('wa_chat_messages')->where('chat_jid', $chat->chat_jid)->delete();
        DB::table('wa_chats')->where('id', $id)->delete();

        Toastr::success(translate('Chat and its history removed from the archive.'));

        return back();
    }

    /** Correct or close out a row the model got wrong. */
    public function updateInsight(Request $request, $id)
    {
        $request->validate([
            'status' => 'nullable|in:open,in_progress,blocked,done',
            'kind'   => 'nullable|in:' . implode(',', WaChatArchive::KINDS),
        ]);

        $update = array_filter([
            'status' => $request->get('status'),
            'kind'   => $request->get('kind'),
        ], fn($v) => !is_null($v) && $v !== '');

        if ($update) {
            $update['updated_at'] = now();
            DB::table('wa_chat_insights')->where('id', $id)->update($update);
            Toastr::success(translate('Updated.'));
        }

        return back();
    }

    public function destroyInsight($id)
    {
        DB::table('wa_chat_insights')->where('id', $id)->delete();
        Toastr::success(translate('Removed.'));

        return back();
    }

    /** Messages waiting on the model, counting only chats analysis is switched on for. */
    private static function pendingCount(): int
    {
        return DB::table('wa_chat_messages as m')
            ->whereNull('m.analyzed_at')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('wa_chats as c')
                    ->whereColumn('c.chat_jid', 'm.chat_jid')
                    ->where('c.ai_enabled', 1);
            })
            ->count();
    }
}
