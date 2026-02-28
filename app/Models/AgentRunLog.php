<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class AgentRunLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'version_tag',
        'trigger_type',
        'status',
        'message',
        'duration_ms',
        'triggered_by', 
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public function agent()
    {
        return $this->belongsTo(SystemPrompt::class, 'agent_id');
    }
}
