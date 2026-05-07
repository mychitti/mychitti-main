<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PromptBoardController extends Controller
{
    public function index()
    {
        $boards = DB::table('prompt_boards as pb')
            ->leftJoin('admins as a', 'pb.created_by', '=', 'a.id')
            ->selectRaw("pb.*, CONCAT(a.f_name, ' ', a.l_name) as creator_name")
            ->orderByDesc('pb.created_at')
            ->get();

        return view('admin-views.prompt-board.index', compact('boards'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'initial_prompt' => 'required|string',
            'attachment'     => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,txt,doc,docx|max:10240',
        ]);

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment')->store('prompt-board', 'public');
        }

        DB::table('prompt_boards')->insert([
            'title'          => $request->title,
            'initial_prompt' => $request->initial_prompt,
            'attachment'     => $attachment,
            'status'         => 'draft',
            'created_by'     => auth('admin')->id(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('admin.prompt-board.index')->with('success', 'Prompt created.');
    }

    public function show($id)
    {
        $board = DB::table('prompt_boards as pb')
            ->leftJoin('admins as a', 'pb.created_by', '=', 'a.id')
            ->selectRaw("pb.*, CONCAT(a.f_name, ' ', a.l_name) as creator_name")
            ->where('pb.id', $id)
            ->first();

        abort_if(!$board, 404);

        $suggestions = DB::table('prompt_board_suggestions as ps')
            ->leftJoin('admins as a', 'ps.admin_id', '=', 'a.id')
            ->selectRaw("ps.*, CONCAT(a.f_name, ' ', a.l_name) as admin_name")
            ->where('ps.prompt_board_id', $id)
            ->orderBy('ps.created_at')
            ->get();

        return view('admin-views.prompt-board.show', compact('board', 'suggestions'));
    }

    public function addSuggestion(Request $request, $id)
    {
        $request->validate([
            'suggestion'  => 'required|string',
            'attachment'  => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,txt,doc,docx|max:10240',
        ]);

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment')->store('prompt-board', 'public');
        }

        DB::table('prompt_board_suggestions')->insert([
            'prompt_board_id' => $id,
            'suggestion'      => $request->suggestion,
            'attachment'      => $attachment,
            'admin_id'        => auth('admin')->id(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return back()->with('success', 'Suggestion added.');
    }

    public function setFinal(Request $request, $id)
    {
        $request->validate(['final_prompt' => 'required|string']);

        DB::table('prompt_boards')->where('id', $id)->update([
            'final_prompt' => $request->final_prompt,
            'status'       => 'finalized',
            'updated_at'   => now(),
        ]);

        return back()->with('success', 'Final prompt saved.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:draft,reviewing,finalized']);

        DB::table('prompt_boards')->where('id', $id)->update([
            'status'     => $request->status,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteSuggestion($id, $suggestionId)
    {
        $suggestion = DB::table('prompt_board_suggestions')
            ->where('id', $suggestionId)
            ->where('prompt_board_id', $id)
            ->first();

        if ($suggestion && $suggestion->attachment) {
            Storage::disk('public')->delete($suggestion->attachment);
        }

        DB::table('prompt_board_suggestions')
            ->where('id', $suggestionId)
            ->where('prompt_board_id', $id)
            ->delete();

        return back()->with('success', 'Suggestion deleted.');
    }

    public function destroy($id)
    {
        $suggestions = DB::table('prompt_board_suggestions')->where('prompt_board_id', $id)->get();
        foreach ($suggestions as $s) {
            if ($s->attachment) Storage::disk('public')->delete($s->attachment);
        }

        $board = DB::table('prompt_boards')->where('id', $id)->first();
        if ($board && $board->attachment) Storage::disk('public')->delete($board->attachment);

        DB::table('prompt_board_suggestions')->where('prompt_board_id', $id)->delete();
        DB::table('prompt_boards')->where('id', $id)->delete();

        return redirect()->route('admin.prompt-board.index')->with('success', 'Prompt deleted.');
    }
}
