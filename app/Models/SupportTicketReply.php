<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketReply extends Model
{
    protected $guarded = ['id'];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function repliedBy()
    {
        return $this->belongsTo(Admin::class, 'replied_by');
    }

    public function repliedByVendor()
    {
        return $this->belongsTo(Vendor::class, 'replied_by_vendor');
    }

    public function repliedByUser()
    {
        return $this->belongsTo(User::class, 'replied_by_user');
    }
}
 