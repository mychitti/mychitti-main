<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class SchoolTransportVehicle extends Model
{
    use BranchScoped;

    protected $table = 'school_transport_vehicles';

    protected $fillable = [
        'store_id',
        'branch_id', 
        'vehicle_no',
        'vehicle_model',
        'driver_name', 
        'driver_phone',
        'driver_license',
        'capacity',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'status' => 'integer',
    ];
}
