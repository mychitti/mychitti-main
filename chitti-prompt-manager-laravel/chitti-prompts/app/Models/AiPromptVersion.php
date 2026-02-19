<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPromptVersion extends Model
{
    protected $fillable = [
        'ai_prompt_id', 'version_label', 'system_prompt', 'variables', 'saved_by',
    ]; 

    protected $casts = [
        'variables' => 'array',
    ];

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(AiPrompt::class, 'ai_prompt_id');
    }
}
