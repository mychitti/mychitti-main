<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class SchoolTransportRoute extends Model
{
    use BranchScoped;

    protected $table = 'school_transport_routes';
 
    protected $fillable = [
        'store_id',
        'branch_id', 
        'name',
        'start_point',
        'end_point',
    ];

    public function stops()
    {
        return $this->hasMany(SchoolTransportStop::class, 'school_transport_route_id');
    }

    public function allocations()
    {
        return $this->hasMany(SchoolStudentTransport::class, 'school_transport_route_id');
    }
}
