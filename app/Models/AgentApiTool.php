<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class AgentApiTool extends Model 
{
    protected $fillable = [
        'agent_id',
        'api_name',
        'endpoint',
        'method',
        'status',
        'sort_order',
    ];

    public function agent()
    {
        return $this->belongsTo(SystemPrompt::class, 'agent_id');
    }
}
