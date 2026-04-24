<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class McCampaignWinner extends Model {
    protected $table = 'mc_campaign_winners';
    protected $fillable = ['campaign_id', 'winner_name', 'position', 'prize_detail', 'drawn_at', 'notes'];
    protected $casts = ['drawn_at' => 'date'];
    public function campaign() { return $this->belongsTo(McCampaign::class, 'campaign_id'); }
}
