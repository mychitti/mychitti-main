<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreTeam extends Model
{
    protected $table = 'store_teams';

    protected $fillable = [
        'store_id',  
        'name',
        'description',
    ];
    public function team_leader() 
    {
        if ($this->store_id == 0) {
            return $this->belongsTo(Admin::class, 'team_lead');
        }
        return $this->belongsTo(VendorEmployee::class, 'team_lead'); 
    }

    public function members()
    {
        $model = $this->store_id == 0 ? Admin::class : VendorEmployee::class;
        return $this->belongsToMany($model, 'store_team_members', 'team_id', 'employee_id')
                    ->withPivot('role', 'is_team_lead', 'joined_at')
                    ->withTimestamps();
    }
}