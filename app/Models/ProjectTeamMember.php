<?php

namespace App\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class ProjectTeamMember extends Model
{
    protected $fillable = ['project_id','employee_id','member_type'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
 
    public function employee()
    { 
        return $this->belongsTo(VendorEmployee::class, 'employee_id'); 
    }

    public function admin() 
    {
        return $this->belongsTo(Admin::class, 'employee_id');
    }

    public function member()
    {
        if ($this->member_type === 'admin') {
            return $this->belongsTo(Admin::class, 'employee_id');
        }
        return $this->belongsTo(VendorEmployee::class, 'employee_id');
    }
}
