<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class McCampaignInfluencer extends Model {
    protected $table = 'mc_campaign_influencers';
    protected $fillable = ['campaign_id', 'name', 'handle', 'platform', 'followers', 'agreed_rate', 'notes'];
    protected $casts = ['agreed_rate' => 'float'];
    public function campaign() { return $this->belongsTo(McCampaign::class, 'campaign_id'); }
}
