<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class SchoolTransportStop extends Model
{
    use BranchScoped;

    protected $table = 'school_transport_stops';
 
    protected $fillable = [
        'store_id',
        'branch_id',
        'school_transport_route_id',
        'name', 
        'fare',
    ];

    protected $casts = [
        'fare' => 'float',
    ];

    public function route()
    {
        return $this->belongsTo(SchoolTransportRoute::class, 'school_transport_route_id');
    }
}
