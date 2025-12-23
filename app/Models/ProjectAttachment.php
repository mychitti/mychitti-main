<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class ProjectAttachment extends Model
{
    protected $fillable = [
        'project_id','file_name','file_path','file_type','file_size'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
