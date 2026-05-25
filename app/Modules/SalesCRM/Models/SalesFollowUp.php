<?php

namespace App\Modules\SalesCRM\Models;

use App\Models\Admin;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;

class SalesFollowUp extends Model
{
    protected $table = 'sales_followups';

    protected $fillable = [
        'query_id', 'title', 'notes', 'admin_id', 'assigned_to',
        'zone_id', 'due_date', 'due_time', 'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    const STATUSES = ['pending', 'done', 'missed', 'cancelled'];

    public function salesQuery()
    {
        return $this->belongsTo(SalesQuery::class, 'query_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function assignments()
    {
        return $this->hasMany(FollowUpAssignment::class, 'followup_id')->latest();
    }
}
