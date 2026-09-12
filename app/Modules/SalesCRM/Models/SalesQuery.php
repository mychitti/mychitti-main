<?php

namespace App\Modules\SalesCRM\Models;

use App\Models\Admin;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;

class SalesQuery extends Model
{
    protected $table = 'sales_queries';

    protected $fillable = [
        'ref_no', 'contact_name', 'phone', 'email', 'company',
        'zone_id', 'assigned_admin_id', 'source', 'status', 'platform',
        'lost_reason', 'lost_reason_other',
        'priority', 'description', 'notes', 'sub_module',
    ];

    const SOURCES       = ['website', 'phone', 'whatsapp', 'email', 'referral', 'other'];
    const STATUSES      = ['new', 'in_progress', 'proposal_sent', 'converted', 'lost', 'on_hold'];
    const PRIORITIES    = ['low', 'medium', 'high'];
    const LOST_REASONS  = ['price', 'competitor', 'no_response', 'not_interested', 'budget', 'other'];

    // Which product this prospect is for. MyChitti and MC Vendorhub are sold and onboarded
    // separately, so a MyChitti sales rep's pipeline should not fill up with Vendorhub prospects
    // (and vice versa). Defaults to 'mychitti' since that's the only source that creates these
    // rows automatically today (WhatsAppSalesLead, the platform WABA).
    const PLATFORMS = ['mychitti', 'mcvendorhub'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function followUps()
    {
        return $this->hasMany(SalesFollowUp::class, 'query_id');
    }

    public function activities()
    {
        return $this->hasMany(QueryActivity::class, 'query_id')->orderBy('created_at', 'desc');
    }

    public function quotations()
    {
        return $this->hasMany(\App\Models\Quotation::class, 'sales_query_id')->latest();
    }

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class, 'query_id')->latest();
    }

    public static function generateRef(): string
    {
        $last = static::max('id') ?? 0;
        return 'SQ-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
    }
}
