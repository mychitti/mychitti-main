<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestRule extends Model
{
    use HasFactory;

    protected $casts = [
        'permissions' => 'array',
        'send_to_dep_id'  => 'array',
        'send_to_role_id' => 'array',
        'send_to_employee_id'     => 'array',
    ];
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    public function role()
    {
        return $this->belongsTo(EmployeeRole::class, 'role_id');
    }

    public function getNextLevelDepsAttribute()
    {
        return Department::whereIn('id', $this->send_to_dep_id ?? [])->get();
    }

    public function getNextLevelRolesAttribute()
    {
        $roleIds = $this->send_to_role_id;

        // Convert to array safely
        if (is_string($roleIds)) {
            $roleIds = array_filter(explode(',', $roleIds));
        } elseif (is_numeric($roleIds)) {
            $roleIds = [$roleIds]; // single integer → array
        } elseif (!is_array($roleIds)) {
            $roleIds = [];
        }

        return EmployeeRole::whereIn('id', $roleIds)->get();
    }


    public function getNextLevelEmpsAttribute()
{
    $empIds = $this->send_to_employee_id;

    // Convert to array safely
    if (is_string($empIds)) {
        $empIds = array_filter(explode(',', $empIds));
    } elseif (is_numeric($empIds)) {
        $empIds = [$empIds]; // single integer → array
    } elseif (!is_array($empIds)) {
        $empIds = [];
    }

    return VendorEmployee::whereIn('id', $empIds)->get();
}

}
