<?php

namespace App\Modules\SalesCRM\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    protected $table = 'support_ticket_replies';

    protected $fillable = ['support_ticket_id', 'replied_by', 'message'];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'replied_by');
    }
}
