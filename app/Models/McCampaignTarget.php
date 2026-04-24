<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class McCampaignTarget extends Model {
    protected $table = 'mc_campaign_targets';
    protected $fillable = ['campaign_id', 'metric', 'target_value', 'current_value', 'unit', 'notes'];
    protected $casts = ['target_value' => 'float', 'current_value' => 'float'];
    public function campaign() { return $this->belongsTo(McCampaign::class, 'campaign_id'); }
    public function getProgressAttribute() {
        return $this->target_value > 0 ? min(100, round(($this->current_value / $this->target_value) * 100)) : 0;
    }
}
