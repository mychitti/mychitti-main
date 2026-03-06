<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class SystemPrompt extends Model
{
    use HasFactory;

    protected $fillable = [
        // Original fields
        'name', 
        'description',
        'user_type',
        'skill_type',
        'status',
        'prompt',
        'settings',

        // AI Model config
        'ai_provider',
        'ai_model',
        'max_tokens',
        'temperature',
        'top_p',
        'api_key_override',

        // Auth & access
        'requires_auth',
        'session_validation',
        'allowed_roles',

        // Trigger
        'trigger_type',
        'cron_schedule',
        'webhook_url',
        'event_name',
        'run_as_user',
        'timeout_seconds',

        // Memory
        'conv_history_enabled',
        'cross_session_memory',
        'inject_vendor_profile',
        'max_history_messages',
        'context_window',

        // Error handling
        'retry_count',
        'backoff_strategy',
        'fallback_message',
        'escalation_rules',

        // Output & permissions
        'notification_settings',
        'module_permissions',

        // Versioning & env
        'current_version',
        'environment',

        // TTS (Text-to-Speech)
        'tts_voice',
    ];

    protected $casts = [
        'settings'             => 'array', 
        'allowed_roles'        => 'array',
        'escalation_rules'     => 'array',
        'notification_settings'=> 'array',
        'module_permissions'   => 'array',
        'temperature'          => 'float',
        'top_p'                => 'float',
        'requires_auth'        => 'boolean',
        'conv_history_enabled' => 'boolean',
        'cross_session_memory' => 'boolean',
        'inject_vendor_profile'=> 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────

    public function versions()
    {
        return $this->hasMany(AgentVersion::class, 'agent_id')->orderByDesc('created_at');
    }

    public function liveVersion()
    {
        return $this->hasOne(AgentVersion::class, 'agent_id')->where('is_live', 1);
    }

    public function apiTools()
    {
        return $this->hasMany(AgentApiTool::class, 'agent_id')->orderBy('sort_order');
    }

    public function functions()
    {
        return $this->hasMany(AgentFunction::class, 'agent_id')->orderBy('sort_order');
    }

    public function tasks()
    {
        return $this->hasMany(AgentTask::class, 'agent_id')->orderBy('sort_order');
    }

    public function runLogs()
    {
        return $this->hasMany(AgentRunLog::class, 'agent_id')->orderByDesc('created_at');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUserType($query, string $type)
    {
        return $query->where('user_type', $type);
    }
}
