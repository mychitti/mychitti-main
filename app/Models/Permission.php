<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'feature_permissions';
    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
      public function roles()
    {
        return $this->belongsToMany(
            EmployeeRole::class,
            'role_feature_permissions',
            'feature_permission_id',
            'role_id'
        )->withTimestamps();
    }
}
