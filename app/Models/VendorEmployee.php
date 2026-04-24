<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class VendorEmployee extends Authenticatable
{
    use Notifiable; 

    protected $fillable = ['remember_token', 'branch_id', 'ledger_account_id'];

    protected $casts = [
        'employee_role_id' => 'integer',
        'vendor_id' => 'integer',
        'store_id' => 'integer',
        // 'status' => 'integer',
        'is_logged_in' => 'boolean',
    ];

    protected $hidden = [
        'password',
        'auth_token',
        'remember_token',
    ];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return trim(($this->f_name ?? '') . ' ' . ($this->l_name ?? ''));
    }


    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function storeShift()
    {
        return $this->belongsTo(StoreShift::class, 'store_shift_id');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    public function comments()
    {
        return $this->hasMany(StoreEmployeeComment::class, 'employee_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function doctorProfile()
    {
        return $this->hasOne(DoctorProfile::class, 'emp_id');
    }

    public function role()
    {
        return $this->belongsTo(EmployeeRole::class, 'employee_role_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    public function teams()
    {
        return $this->belongsToMany(StoreTeam::class, 'store_team_members', 'employee_id', 'team_id')
            ->withPivot('role', 'is_team_lead', 'joined_at')
            ->withTimestamps();
    }
    public function permissions()
    {
        return $this->role->flatMap->permissions->unique('id');
    }

    public function hasPermission($feature, $action)
    {
        return $this->permissions()->contains(function ($perm) use ($feature, $action) {
            return $perm->feature->slug === $feature && $perm->action === $action;
        });
    }
}
