<?php

namespace App\Models;

use App\Models\Project; 
use Illuminate\Database\Eloquent\Model;

class ProjectInternalNote extends Model
{
    protected $fillable = ['project_id', 'note'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
