<?php 

namespace App\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class ProjectMilestone extends Model
{
    protected $fillable = ['project_id','title','due_date','status'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

