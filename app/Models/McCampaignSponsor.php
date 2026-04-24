<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class McCampaignSponsor extends Model {
    protected $table = 'mc_campaign_sponsors';
    protected $fillable = ['campaign_id', 'name', 'logo', 'website', 'tier', 'sort_order'];
    public function campaign() { return $this->belongsTo(McCampaign::class, 'campaign_id'); }
}
