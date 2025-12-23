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
        return $this->belongsTo(VendorEmployee::class, 'team_lead');
    }

    public function members()
    {
        return $this->belongsToMany(VendorEmployee::class, 'store_team_members', 'team_id', 'employee_id')
                    ->withPivot('role', 'is_team_lead', 'joined_at')
                    ->withTimestamps();
    }
}