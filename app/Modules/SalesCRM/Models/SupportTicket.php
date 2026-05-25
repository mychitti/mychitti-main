<?php

namespace App\Modules\SalesCRM\Models;

use App\Models\Admin;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $table = 'support_tickets';

    protected $fillable = [
        'ticket_no', 'subject', 'contact_name', 'phone', 'email',
        'zone_id', 'assigned_admin_id', 'query_id',
        'channel', 'priority', 'status', 'description', 'resolution',
    ];

    const CHANNELS  = ['phone', 'email', 'whatsapp', 'website', 'walk_in'];
    const PRIORITIES = ['low', 'medium', 'high', 'urgent'];
    const STATUSES  = ['open', 'in_progress', 'resolved', 'closed', 'on_hold'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function salesQuery()
    {
        return $this->belongsTo(SalesQuery::class, 'query_id');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'support_ticket_id')->orderBy('created_at');
    }

    public static function generateRef(): string
    {
        $last = static::max('id') ?? 0;
        return 'TK-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
    }
}
