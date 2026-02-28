<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class AgentTask extends Model
{
    protected $fillable = [
        'agent_id',
        'task_name',
        'description',
        'trigger_type',
        'skill_category',
        'is_destructive',
        'require_approval',
        'status', 
        'sort_order',
    ];

    protected $casts = [
        'is_destructive'   => 'boolean',
        'require_approval' => 'boolean',
    ];

    public function agent()
    {
        return $this->belongsTo(SystemPrompt::class, 'agent_id');
    }
}
