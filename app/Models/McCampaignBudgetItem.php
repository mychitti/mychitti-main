<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class McCampaignBudgetItem extends Model {
    protected $table = 'mc_campaign_budget_items';
    protected $fillable = ['campaign_id', 'category', 'item_description', 'budgeted_amount', 'actual_amount', 'notes'];
    protected $casts = ['budgeted_amount' => 'float', 'actual_amount' => 'float'];
    public function campaign() { return $this->belongsTo(McCampaign::class, 'campaign_id'); }
}
