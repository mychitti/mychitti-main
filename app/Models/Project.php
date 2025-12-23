<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;



    protected $fillable = [
        'vendor_id',
        'client_id',
        'project_title',
        'team_members',
        'team_leader',
        'progress_status',
        'file',
        'start_date',
        'end_date',
        'cost',
        'specifications',
        'advance_pay',
        'status',
        'prog_percent',
        'project_size',
        'project_type',
        'teams'
    ];
    public function client()
    {
        return $this->belongsTo(StoreCustomer::class, 'client_id');
    }
    public function teamLeader()
    {
        return $this->belongsTo(VendorEmployee::class, 'team_leader');
    }
    public function projectManager()
    {
        return $this->belongsTo(VendorEmployee::class, 'project_manager');
    }
    public function departments()
    {
        return $this->hasMany(ProjectDepartment::class);
    }
    public function teamMembers()
    {
        return $this->hasMany(ProjectTeamMember::class);
    }
    public function internalNotes()
    {
        return $this->hasMany(ProjectInternalNote::class);
    }

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class);
    }
    public function tasks()
    {
        return $this->hasMany(StoreTask::class);
    }

    public function attachments()
    {
        return $this->hasMany(ProjectAttachment::class);
    }

 
}
