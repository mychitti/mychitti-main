<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentApiTool;
use App\Models\AgentFunction;
use App\Models\AgentRunLog;
use App\Models\AgentTask;
use App\Models\AgentVersion;
use App\Models\AiProvider;
use App\Models\SystemPrompt;
use App\Services\AiServiceClient;
use Illuminate\Http\Request; 

 class AIAgentSkillController extends Controller  
{  
    // ── Dashboard ──────────────────────────────────────────

    public function index(Request $request)
    { 
        $userType = $request->get('user_type', 'vendor');
 
        $agents = SystemPrompt::where('user_type', $userType)
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'skill_type', 'status', 'current_version']);

            $aiProviders = AiProvider::where('status', 1)->get();

        $activeAgent = null;
        if ($request->filled('agent_id')) {
            $activeAgent = SystemPrompt::with([
                'versions',
                'apiTools',
                'functions',
                'tasks',
                'runLogs' => fn($q) => $q->limit(5),
            ])->findOrFail($request->agent_id);
        } elseif ($agents->isNotEmpty()) {
            $activeAgent = SystemPrompt::with([
                'versions',
                'apiTools',
                'functions', 
                'tasks', 
                'runLogs' => fn($q) => $q->limit(5),
            ])->find($agents->first()->id);
        }
        
        // prx($activeAgent);
 
        $counts = SystemPrompt::selectRaw('user_type, count(*) as total')
            ->whereIn('user_type', ['admin', 'user', 'vendor'])
            ->groupBy('user_type')
            ->pluck('total', 'user_type')
            ->toArray();
        $counts = array_merge(['admin' => 0, 'user' => 0, 'vendor' => 0], $counts);

        return view('admin-views.dashboard-pharmacy', compact('agents', 'aiProviders','activeAgent', 'userType', 'counts'));
    }

    // ── Agent CRUD ─────────────────────────────────────────

    public function store(Request $request)
    {
        $agent = SystemPrompt::create($this->agentFields($request));

        // Create initial version entry
        AgentVersion::create([
            'agent_id'    => $agent->id,
            'version_tag' => 'v1.0',
            'changelog'   => 'Initial agent setup',
            'updated_by'  => auth('admin')->user()->f_name ?? 'Admin',
            'is_live'     => 1,
            'created_at'  => now(),
        ]);

        return response()->json(['status' => true, 'agent' => $agent]);
    }

    public function show($id)
    {
        $agent = SystemPrompt::with([
            'versions',
            'apiTools',
            'functions',
            'tasks',
            'runLogs' => fn($q) => $q->limit(5),
        ])->findOrFail($id); 
 
        return response()->json($agent);
    } 

    public function update(Request $request, $id)
    {
        $agent = SystemPrompt::findOrFail($id);
        $agent->update($this->agentFields($request));

        // Sync API tools
        if ($request->has('api_tools')) {
            $agent->apiTools()->delete();
            foreach ((array) $request->api_tools as $i => $tool) {
                AgentApiTool::create(array_merge(
                    $this->normalizeApiToolPayload((array) $tool),
                    ['agent_id' => $id, 'sort_order' => $i]
                ));
            }
        }

        // Sync function schemas
        if ($request->has('functions')) {
            $agent->functions()->delete();
            foreach ((array) $request->functions as $i => $fn) {
                AgentFunction::create(array_merge($fn, ['agent_id' => $id, 'sort_order' => $i]));
            }
        }

        // Sync tasks
        if ($request->has('tasks')) {
            $agent->tasks()->delete();
            foreach ((array) $request->tasks as $i => $task) {
                AgentTask::create(array_merge($task, ['agent_id' => $id, 'sort_order' => $i]));
            }
        }

        return response()->json(['status' => true, 'message' => 'Agent saved successfully.']);
    }

    public function destroy($id)
    {
        SystemPrompt::findOrFail($id)->delete();
        return response()->json(['status' => true]);
    }

    // ── Test Console ───────────────────────────────────────

    public function test(Request $request, $id, AiServiceClient $aiService)
    {
        $agent   = SystemPrompt::findOrFail($id);
        $message = trim($request->input('message', ''));

        if ($message === '') { 
            return response()->json(['success' => false, 'message' => 'Message is required.'], 422);
        }
 
        $start  = microtime(true);
        $result = $aiService->chat(
            userId:       0,
            guard:        'agent_test',
            message:      $message,
            agentId:      (int) $id,
            systemPrompt: $agent->prompt ?? '',
            modelConfig:  [
                'ai_provider'      => $agent->ai_provider ?: 'anthropic',
                'ai_model'         => $agent->ai_model         ?: null,
                'max_tokens'       => $agent->max_tokens        ?: null,
                'temperature'      => strlen((string) $agent->temperature) ? (float) $agent->temperature : null,
                'top_p'            => strlen((string) $agent->top_p)        ? (float) $agent->top_p        : null,
                'api_key_override' => $agent->api_key_override ?: null,
            ],
        );
        $ms     = (int) round((microtime(true) - $start) * 1000);

        AgentRunLog::create([
            'agent_id'     => $id,
            'version_tag'  => $agent->current_version,
            'trigger_type' => 'manual_test',
            'status'       => ($result['success'] ?? false) ? 'success' : 'failed',
            'message'      => ($result['success'] ?? false) ? 'Test run OK' : ($result['message'] ?? 'Error'),
            'duration_ms'  => $ms,
            'triggered_by' => auth('admin')->user()->f_name ?? 'Admin',
            'meta'         => ['input' => $message, 'output' => $result['message'] ?? null],
            'created_at'   => now(),
        ]);

        return response()->json($result);
    }

    // ── Versioning ─────────────────────────────────────────

    public function bumpVersion(Request $request, $id)
    {
        $agent = SystemPrompt::findOrFail($id);

        // Unset previous live version
        AgentVersion::where('agent_id', $id)->update(['is_live' => 0]);

        $version = AgentVersion::create([
            'agent_id'    => $id,
            'version_tag' => $request->version_tag,
            'changelog'   => $request->changelog,
            'updated_by'  => auth('admin')->user()->f_name ?? 'Admin',
            'is_live'     => 1,
            'created_at'  => now(),
        ]); 

        $agent->update(['current_version' => $request->version_tag]);

        return response()->json(['status' => true, 'version' => $version]);
    }

    // ── API Tools ────────────────────────────────────────── 
 
    public function storeApiTool(Request $request, $agentId)
    { 
        $tool = AgentApiTool::create(array_merge(
            $this->normalizeApiToolPayload($request->only([
                'api_name',
                'endpoint',
                'method',
                'status', 'sort_order',
            ])),
            ['agent_id' => $agentId]
        ));
        return response()->json(['status' => true, 'tool' => $tool]);
    }

    public function updateApiTool(Request $request, $toolId)
    {
        $tool = AgentApiTool::findOrFail($toolId);
        $tool->update($this->normalizeApiToolPayload($request->only([
            'api_name',
            'endpoint',
            'method',
            'status', 'sort_order',
        ])));
        return response()->json(['status' => true]);
    }

    public function destroyApiTool($toolId)
    {
        AgentApiTool::findOrFail($toolId)->delete();
        return response()->json(['status' => true]);
    }

    // ── Function Schemas ───────────────────────────────────

    public function storeFunction(Request $request, $agentId)
    {
        $fn = AgentFunction::create(array_merge(
            $request->only(['function_name','description','json_schema','sort_order']),
            ['agent_id' => $agentId]
        ));
        return response()->json(['status' => true, 'function' => $fn]);
    }

    public function updateFunction(Request $request, $fnId)
    {
        AgentFunction::findOrFail($fnId)->update(
            $request->only(['function_name','description','json_schema','sort_order'])
        );
        return response()->json(['status' => true]);
    }

    public function destroyFunction($fnId)
    {
        AgentFunction::findOrFail($fnId)->delete(); 
        return response()->json(['status' => true]);
    }

    // ── Tasks ──────────────────────────────────────────────

    public function storeTask(Request $request, $agentId)
    {
        $task = AgentTask::create(array_merge(
            $request->only(['task_name','description','trigger_type','skill_category',
                            'is_destructive','require_approval','status','sort_order']),
            ['agent_id' => $agentId]
        ));
        return response()->json(['status' => true, 'task' => $task]);
    }

    public function updateTask(Request $request, $taskId)
    {
        AgentTask::findOrFail($taskId)->update(
            $request->only(['task_name','description','trigger_type','skill_category',
                            'is_destructive','require_approval','status','sort_order'])
        );
        return response()->json(['status' => true]);
    }

    public function destroyTask($taskId)
    {
        AgentTask::findOrFail($taskId)->delete();
        return response()->json(['status' => true]);
    }

    // ── Helpers ────────────────────────────────────────────

    private function agentFields(Request $request): array
    {
        $fields = $request->only([
            'name', 'description', 'user_type', 'skill_type', 'status', 'prompt', 'settings',
            'ai_provider', 'ai_model', 'max_tokens', 'temperature', 'top_p', 'api_key_override',
            'requires_auth', 'session_validation', 'allowed_roles',
            'trigger_type', 'cron_schedule', 'webhook_url', 'event_name', 'run_as_user', 'timeout_seconds',
            'conv_history_enabled', 'cross_session_memory', 'inject_vendor_profile',
            'max_history_messages', 'context_window',
            'retry_count', 'backoff_strategy', 'fallback_message', 'escalation_rules',
            'notification_settings', 'module_permissions',
            'current_version', 'environment',
        ]);

        // Don't overwrite stored API key with empty string
        if (isset($fields['api_key_override']) && $fields['api_key_override'] === '') {
            unset($fields['api_key_override']);
        }

        return $fields;
    }

    private function normalizeApiToolPayload(array $payload): array
    {
        return [
            'api_name'        => trim((string) ($payload['api_name'] ?? '')),
            'endpoint'        => trim((string) ($payload['endpoint'] ?? '')),
            'method'          => strtoupper((string) ($payload['method'] ?? 'POST')),
            'status'          => (string) ($payload['status'] ?? 'active'),
            'sort_order'      => (int) ($payload['sort_order'] ?? 0),
        ];
    }
}
