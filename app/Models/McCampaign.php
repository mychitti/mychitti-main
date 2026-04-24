<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class McCampaign extends Model
{
    protected $table = 'mc_campaigns';

    protected $fillable = [
        'name', 'slug', 'status', 'start_date', 'end_date', 'draw_date',
        'banner_image',
        'prize_title', 'prize_value', 'number_of_winners', 'prize_description',
        'max_entries_per_person', 'total_entry_limit',
        'page_content', 'how_it_works', 'terms',
        'meta_title', 'meta_description', 'og_image',
        'require_login', 'show_entry_count', 'plan_notes', 'created_by',
    ];

    protected $casts = [
        'start_date'       => 'datetime',
        'end_date'         => 'datetime',
        'draw_date'        => 'date',
        'prize_value'      => 'float',
        'require_login'    => 'boolean',
        'show_entry_count' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function faqs()          { return $this->hasMany(McCampaignFaq::class, 'campaign_id')->orderBy('sort_order'); }
    public function winners()       { return $this->hasMany(McCampaignWinner::class, 'campaign_id')->orderBy('position'); }
    public function sponsors()      { return $this->hasMany(McCampaignSponsor::class, 'campaign_id')->orderBy('sort_order'); }
    public function influencers()   { return $this->hasMany(McCampaignInfluencer::class, 'campaign_id'); }
    public function guests()        { return $this->hasMany(McCampaignGuest::class, 'campaign_id'); }
    public function collaborations(){ return $this->hasMany(McCampaignCollaboration::class, 'campaign_id'); }
    public function budgetItems()   { return $this->hasMany(McCampaignBudgetItem::class, 'campaign_id'); }
    public function expenses()      { return $this->hasMany(McCampaignExpense::class, 'campaign_id'); }
    public function targets()       { return $this->hasMany(McCampaignTarget::class, 'campaign_id'); }
    public function teamMembers()   { return $this->hasMany(McCampaignTeamMember::class, 'campaign_id'); }

    public function getBudgetTotalAttribute()
    {
        return $this->budgetItems->sum('budgeted_amount');
    }

    public function getExpenseTotalAttribute()
    {
        return $this->expenses->sum('amount');
    }
}
