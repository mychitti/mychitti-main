<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class McCampaignFaq extends Model {
    protected $table = 'mc_campaign_faqs';
    protected $fillable = ['campaign_id', 'question', 'answer', 'sort_order'];
    public function campaign() { return $this->belongsTo(McCampaign::class, 'campaign_id'); }
}
