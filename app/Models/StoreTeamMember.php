<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreTeamMember extends Model
{
    protected $table = 'store_team_members';

    protected $fillable = [
        'team_id',
        'employee_id',
        'role',
        'is_team_lead',
        'joined_at',
    ];

    public function team()
    {
        return $this->belongsTo(StoreTeam::class, 'team_id');
    }

    
    public function employee()
    {
        return $this->belongsTo(VendorEmployee::class, 'employee_id');
    }
}
