<?php

namespace App\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class ProjectDepartment extends Model
{
    protected $fillable = ['project_id','department_id'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
