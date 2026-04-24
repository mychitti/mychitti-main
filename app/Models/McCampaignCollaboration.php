<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class McCampaignCollaboration extends Model {
    protected $table = 'mc_campaign_collaborations';
    protected $fillable = ['campaign_id', 'partner_name', 'type', 'terms', 'status', 'notes'];
    public function campaign() { return $this->belongsTo(McCampaign::class, 'campaign_id'); }
}
