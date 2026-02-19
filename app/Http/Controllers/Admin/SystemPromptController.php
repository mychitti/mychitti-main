<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemPrompt;
use function redirect;

class SystemPromptController extends Controller
{
    public function index(Request $request)
    {
        $user_type  = $request->get('user_type', 'admin');
        $search    = $request->get('search');
        $status    = $request->get('status');
        $selectedId = $request->get('selected');

        $query = SystemPrompt::where('user_type', $user_type)
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest();

        $skills = $query->get();

        // Selected prompt for editor panel
        $selected = $selectedId
            ? SystemPrompt::find($selectedId)
            : null;

        // Tab counts
        $counts = SystemPrompt::selectRaw('user_type, count(*) as total')
            ->groupBy('user_type')
            ->pluck('total', 'user_type');

        $skillTypes = [
            'billing'    => 'Billing Management',
            'chatbot'    => 'Common Chatbot',
            'user_mgmt'  => 'User Management', 
            'onboarding' => 'Onboarding',
            'analytics'  => 'Analytics',
            'support'    => 'Support',
            'custom'     => 'Custom',
        ];

        return view('admin-views.prompt.index', compact(
            'skills',
            'selected',
            'user_type',
            'counts',
            'search',
            'status',
            'skillTypes'
        ));
    }
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'user_type'  => 'required|in:admin,user,vendor',
            'skill_type' => 'required|string',
        ]);

        $prompt = SystemPrompt::create([
            'name'          => $request->name,
            'user_type'     => $request->user_type,
            'skill_type'    => $request->skill_type,
            'description'   => $request->description,
            'status'        => $request->input('status', 'draft'),
            'prompt'        => $request->prompt,
            'settings'      => $request->input('settings', []),
        ]);

        return redirect()->route('admin.prompt.index', [
            'user_type' => $prompt->user_type,
            'selected'  => $prompt->id,
        ])->with('success', 'Skill created.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'prompt_id'  => 'required|integer|exists:system_prompts,id',
            'name'       => 'required|string|max:255',
            'user_type'  => 'required|in:admin,user,vendor',
            'skill_type' => 'required|string',
            'status'     => 'required|in:active,draft,archived',
        ]);
        $prompt = SystemPrompt::findOrFail($request->prompt_id);
        $prompt->update([ 
            'name'          => $request->name,
            'user_type'     => $request->user_type,
            'skill_type'    => $request->skill_type,
            'description'   => $request->description,
            'status'        => $request->status,
            'prompt'        => $request->prompt,
            'settings'      => $request->input('settings', []),
        ]);

        return redirect()->route('admin.prompt.index', [
            'user_type' => $prompt->user_type,
            'selected'  => $prompt->id,
        ])->with('success', 'Skill updated successfully.');
    }

    public function delete(SystemPrompt $prompt)
    {
        $userType = $prompt->user_type;
        $prompt->delete();

        return redirect()->route('admin.prompt.index', ['user_type' => $userType])
            ->with('success', 'Skill deleted.');
    }
}
