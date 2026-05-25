<?php

namespace App\Modules\SalesCRM\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;

class FollowUpAssignment extends Model
{
    protected $table = 'followup_assignments';

    protected $fillable = ['followup_id', 'assigned_to', 'assigned_by', 'note'];

    public function assignedTo()
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }
}
