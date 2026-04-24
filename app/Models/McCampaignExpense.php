<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class McCampaignExpense extends Model {
    protected $table = 'mc_campaign_expenses';
    protected $fillable = ['campaign_id', 'title', 'category', 'amount', 'expense_date', 'receipt', 'notes'];
    protected $casts = ['amount' => 'float', 'expense_date' => 'date'];
    public function campaign() { return $this->belongsTo(McCampaign::class, 'campaign_id'); }
}
