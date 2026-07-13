<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AI Citation Monitoring (Phase 3 §3.6 / GEO KPI) — monthly record of how often My Chitti is
 * cited by AI platforms (ChatGPT, Perplexity, Gemini, Claude), plus GA4 referral sessions and
 * branded-search-volume proxy. One row per (platform, period YYYY-MM).
 */ 
class AiCitation extends Model
{
    protected $fillable = [
        'platform', 'period', 'citations',
        'referral_sessions', 'branded_search_volume', 'notes',
    ];

    public const PLATFORMS = ['chatgpt', 'perplexity', 'gemini', 'claude'];
}
 