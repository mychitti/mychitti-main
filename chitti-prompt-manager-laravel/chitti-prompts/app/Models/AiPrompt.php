<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiPrompt extends Model
{
    protected $fillable = [
        'name', 'category', 'skill_type', 'status',
        'description', 'system_prompt', 'variables', 'settings', 'version',
    ];

    protected $casts = [
        'variables' => 'array',
        'settings'  => 'array',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(AiPromptVersion::class)->latest();
    }

    // Helpers
    public function isActive(): bool   { return $this->status === 'active'; }
    public function isDraft(): bool    { return $this->status === 'draft'; }

    public function categoryLabel(): string
    {
        return match($this->category) {
            'admin'  => 'Admin',
            'user'   => 'User',
            'vendor' => 'Vendor',
            default  => ucfirst($this->category),
        };
    }

    public function skillTypeLabel(): string
    { 
        return match($this->skill_type) {
            'billing'   => 'Billing Management',
            'chatbot'   => 'Common Chatbot',
            'user_mgmt' => 'User Management',
            'onboarding'=> 'Onboarding',
            'analytics' => 'Analytics',
            'support'   => 'Support',
            'custom'    => 'Custom',
            default     => ucfirst($this->skill_type),
        };
    }

    public function estimatedTokens(): int
    {
        return (int) ceil(strlen($this->system_prompt ?? '') / 4);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'active'   => 'badge-active',
            'draft'    => 'badge-draft',
            'archived' => 'badge-archived',
            default    => '',
        };
    }
}
