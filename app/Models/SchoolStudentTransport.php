<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class SchoolStudentTransport extends Model
{
    use BranchScoped;

    protected $table = 'school_student_transports';

    protected $fillable = [
        'store_id',
        'branch_id',
        'student_id', 
        'school_transport_route_id',
        'school_transport_stop_id',
        'school_transport_vehicle_id',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function route()
    {
        return $this->belongsTo(SchoolTransportRoute::class, 'school_transport_route_id');
    }

    public function stop()
    {
        return $this->belongsTo(SchoolTransportStop::class, 'school_transport_stop_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(SchoolTransportVehicle::class, 'school_transport_vehicle_id');
    }
}
