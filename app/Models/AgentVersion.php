<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class AgentVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'version_tag',
        'changelog',
        'updated_by',
        'is_live',
        'created_at',
    ]; 

    protected $casts = [
        'is_live'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function agent()
    {
        return $this->belongsTo(SystemPrompt::class, 'agent_id');
    }
}
