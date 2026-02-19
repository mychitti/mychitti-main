<?php

namespace App\Http\Controllers;

use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PromptController extends Controller
{
    /**
     * Main listing page — shows all prompts for a category tab.
     */
    public function index(Request $request): View
    {
        $category  = $request->get('category', 'admin');
        $search    = $request->get('search');
        $status    = $request->get('status');
        $selectedId = $request->get('selected');

        $query = AiPrompt::where('category', $category)
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest();

        $skills = $query->get();

        // Selected prompt for editor panel
        $selected = $selectedId
            ? AiPrompt::with('versions')->find($selectedId)
            : null;

        // Tab counts
        $counts = AiPrompt::selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $skillTypes = [
            'billing'    => 'Billing Management',
            'chatbot'    => 'Common Chatbot',
            'user_mgmt'  => 'User Management',
            'onboarding' => 'Onboarding',
            'analytics'  => 'Analytics',
            'support'    => 'Support',
            'custom'     => 'Custom',
        ];

        return view('prompts.index', compact(
            'skills', 'selected', 'category', 'counts',
            'search', 'status', 'skillTypes'
        ));
    }

    /**
     * Store new prompt.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'category'      => 'required|in:admin,user,vendor',
            'skill_type'    => 'required|string',
            'status'        => 'required|in:active,draft,archived',
            'description'   => 'nullable|string|max:500',
            'system_prompt' => 'nullable|string',
        ]);

        $prompt = AiPrompt::create(array_merge($data, [
            'variables' => [],
            'settings'  => [
                'inject_context' => true,
                'memory'         => true,
                'tool_calling'   => false,
                'rag'            => false,
            ],
        ]));

        // Save initial version
        AiPromptVersion::create([
            'ai_prompt_id'  => $prompt->id,
            'version_label' => 'v1.0',
            'system_prompt' => $prompt->system_prompt,
            'variables'     => [],
            'saved_by'      => auth()->user()->name ?? 'Admin',
        ]);

        return redirect()->route('prompts.index', [
            'category' => $prompt->category,
            'selected' => $prompt->id,
        ])->with('success', "\"{$prompt->name}\" created.");
    }

    /**
     * Update existing prompt.
     */
    public function update(Request $request, AiPrompt $prompt): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'skill_type'    => 'required|string',
            'status'        => 'required|in:active,draft,archived',
            'description'   => 'nullable|string|max:500',
            'system_prompt' => 'nullable|string',
            'variables'     => 'nullable|array',
            'variables.*.key' => 'nullable|string',
            'variables.*.val' => 'nullable|string',
            'settings'      => 'nullable|array',
        ]);

        // Auto-increment version label
        $lastVersion = $prompt->versions()->first();
        $lastNum     = $lastVersion ? (float) ltrim($lastVersion->version_label, 'v') : 1.0;
        $newLabel    = 'v' . number_format($lastNum + 0.1, 1);

        // Snapshot current state before overwriting
        AiPromptVersion::create([
            'ai_prompt_id'  => $prompt->id,
            'version_label' => $newLabel,
            'system_prompt' => $request->system_prompt,
            'variables'     => $request->variables ?? [],
            'saved_by'      => auth()->user()->name ?? 'Admin',
        ]);

        $prompt->update($data);

        return redirect()->route('prompts.index', [
            'category' => $prompt->category,
            'selected' => $prompt->id,
        ])->with('success', "\"{$prompt->name}\" saved.");
    }

    /**
     * Duplicate a prompt.
     */
    public function duplicate(AiPrompt $prompt): RedirectResponse
    {
        $copy = $prompt->replicate();
        $copy->name   = $prompt->name . ' (Copy)';
        $copy->status = 'draft';
        $copy->save();

        AiPromptVersion::create([
            'ai_prompt_id'  => $copy->id,
            'version_label' => 'v1.0',
            'system_prompt' => $copy->system_prompt,
            'variables'     => $copy->variables ?? [],
            'saved_by'      => auth()->user()->name ?? 'Admin',
        ]);

        return redirect()->route('prompts.index', [
            'category' => $copy->category,
            'selected' => $copy->id,
        ])->with('success', "\"{$copy->name}\" created.");
    }

    /**
     * Restore a previous version.
     */
    public function restore(AiPrompt $prompt, AiPromptVersion $version): RedirectResponse
    {
        $prompt->update([
            'system_prompt' => $version->system_prompt,
            'variables'     => $version->variables,
        ]);

        return redirect()->route('prompts.index', [
            'category' => $prompt->category,
            'selected' => $prompt->id,
        ])->with('success', "Restored to {$version->version_label}.");
    }

    /**
     * Delete a prompt.
     */
    public function destroy(AiPrompt $prompt): RedirectResponse
    { 
        $category = $prompt->category;
        $name     = $prompt->name;
        $prompt->delete();

        return redirect()->route('prompts.index', ['category' => $category])
            ->with('success', "\"{$name}\" deleted.");
    }
}
