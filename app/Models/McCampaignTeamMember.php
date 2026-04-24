<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class McCampaignTeamMember extends Model {
    protected $table = 'mc_campaign_team_members';
    protected $fillable = ['campaign_id', 'member_name', 'role', 'responsibilities', 'phone', 'email'];
    public function campaign() { return $this->belongsTo(McCampaign::class, 'campaign_id'); }
}
