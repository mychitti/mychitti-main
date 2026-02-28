<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class AgentFunction extends Model
{
    protected $fillable = [
        'agent_id',
        'function_name',
        'description',
        'json_schema',
        'sort_order',
    ];

    protected $casts = [
        'json_schema' => 'array',
    ]; 

    public function agent()
    {
        return $this->belongsTo(SystemPrompt::class, 'agent_id');
    }
}
