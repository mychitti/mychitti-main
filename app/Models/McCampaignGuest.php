<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class McCampaignGuest extends Model {
    protected $table = 'mc_campaign_guests';
    protected $fillable = ['campaign_id', 'name', 'email', 'phone', 'type', 'notes'];
    public function campaign() { return $this->belongsTo(McCampaign::class, 'campaign_id'); }
}
