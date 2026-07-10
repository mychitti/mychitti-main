<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\SchoolTransportRoute;
use App\Models\SchoolTransportVehicle;
use App\Models\SchoolTransportStop;
use App\Models\SchoolStudentTransport;
use App\Models\StudentEnrollment;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransportController extends Controller
{ 
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('school_transport_routes')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_transport_routes (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL,
                branch_id BIGINT UNSIGNED NULL,
                name VARCHAR(191) NOT NULL,
                start_point VARCHAR(191) NULL,
                end_point VARCHAR(191) NULL,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_store_branch (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!Schema::hasTable('school_transport_vehicles')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_transport_vehicles (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL,
                branch_id BIGINT UNSIGNED NULL,
                vehicle_no VARCHAR(100) NOT NULL,
                vehicle_model VARCHAR(191) NULL,
                driver_name VARCHAR(191) NULL,
                driver_phone VARCHAR(50) NULL,
                driver_license VARCHAR(100) NULL,
                capacity INT DEFAULT NULL,
                status TINYINT NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_store_branch (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!Schema::hasTable('school_transport_stops')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_transport_stops (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL,
                branch_id BIGINT UNSIGNED NULL,
                school_transport_route_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(191) NOT NULL,
                fare DECIMAL(8,2) NOT NULL DEFAULT 0.00,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_store_route (store_id, branch_id, school_transport_route_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!Schema::hasTable('school_student_transports')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_student_transports (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL,
                branch_id BIGINT UNSIGNED NULL,
                student_id BIGINT UNSIGNED NOT NULL,
                school_transport_route_id BIGINT UNSIGNED NOT NULL,
                school_transport_stop_id BIGINT UNSIGNED NOT NULL,
                school_transport_vehicle_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_student_transport (store_id, student_id),
                KEY idx_store_assignment (store_id, branch_id, school_transport_route_id, school_transport_stop_id, school_transport_vehicle_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $routes = SchoolTransportRoute::where('store_id', $store_id)->get();
        $vehicles = SchoolTransportVehicle::where('store_id', $store_id)->get();
        $stops = SchoolTransportStop::where('store_id', $store_id)->with('route')->get();
        
        $allocations = SchoolStudentTransport::where('store_id', $store_id)
            ->with(['student.currentEnrollment.schoolClass', 'student.currentEnrollment.section', 'route', 'stop', 'vehicle'])
            ->get(); 

        // Get active enrolled students in this active branch
        $students = StudentEnrollment::where('store_id', $store_id)
            ->where('status', 1)
            ->with('student')
            ->get()
            ->filter(fn($e) => $e->student)
            ->map(fn($e) => $e->student)
            ->unique('id')
            ->sortBy('name')
            ->values();

        return view('school::vendor.transport.index', compact('routes', 'vehicles', 'stops', 'allocations', 'students'));
    }

    public function routeStore(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $request->validate([
            'name' => 'required|string|max:191',
            'start_point' => 'nullable|string|max:191',
            'end_point' => 'nullable|string|max:191',
        ]);

        SchoolTransportRoute::create([
            'store_id' => $store_id,
            'name' => $request->name,
            'start_point' => $request->start_point ?: null,
            'end_point' => $request->end_point ?: null,
        ]);

        Toastr::success('Route created successfully.');
        return back();
    }

    public function routeDelete($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $route = SchoolTransportRoute::where('store_id', $store_id)->findOrFail($id);
        
        // Remove child elements
        SchoolTransportStop::where('school_transport_route_id', $route->id)->delete();
        SchoolStudentTransport::where('school_transport_route_id', $route->id)->delete();
        $route->delete();

        Toastr::success('Route deleted successfully.');
        return back();
    }

    public function vehicleStore(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $request->validate([
            'vehicle_no' => 'required|string|max:100',
            'vehicle_model' => 'nullable|string|max:191',
            'driver_name' => 'nullable|string|max:191',
            'driver_phone' => 'nullable|string|max:50',
            'driver_license' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'required|integer|in:0,1',
        ]);

        SchoolTransportVehicle::create([
            'store_id' => $store_id,
            'vehicle_no' => $request->vehicle_no,
            'vehicle_model' => $request->vehicle_model ?: null,
            'driver_name' => $request->driver_name ?: null,
            'driver_phone' => $request->driver_phone ?: null,
            'driver_license' => $request->driver_license ?: null,
            'capacity' => $request->capacity ?: null,
            'status' => $request->status,
        ]);

        Toastr::success('Vehicle registered successfully.');
        return back();
    }

    public function vehicleDelete($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $vehicle = SchoolTransportVehicle::where('store_id', $store_id)->findOrFail($id);
        
        // Remove allocations pointing to this vehicle
        SchoolStudentTransport::where('school_transport_vehicle_id', $vehicle->id)->delete();
        $vehicle->delete();

        Toastr::success('Vehicle removed successfully.');
        return back();
    }

    public function stopStore(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $request->validate([
            'school_transport_route_id' => 'required|integer',
            'name' => 'required|string|max:191',
            'fare' => 'required|numeric|min:0',
        ]);

        SchoolTransportStop::create([
            'store_id' => $store_id,
            'school_transport_route_id' => $request->school_transport_route_id,
            'name' => $request->name,
            'fare' => $request->fare,
        ]);

        Toastr::success('Stop added successfully.');
        return back();
    }

    public function stopDelete($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $stop = SchoolTransportStop::where('store_id', $store_id)->findOrFail($id);
        
        // Remove allocations pointing to this stop
        SchoolStudentTransport::where('school_transport_stop_id', $stop->id)->delete();
        $stop->delete();

        Toastr::success('Stop removed successfully.');
        return back();
    }

    public function allocationStore(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $request->validate([
            'student_id' => 'required|integer',
            'school_transport_route_id' => 'required|integer',
            'school_transport_stop_id' => 'required|integer',
            'school_transport_vehicle_id' => 'required|integer',
        ]);

        SchoolStudentTransport::updateOrCreate(
            ['store_id' => $store_id, 'student_id' => (int) $request->student_id],
            [
                'school_transport_route_id' => (int) $request->school_transport_route_id,
                'school_transport_stop_id' => (int) $request->school_transport_stop_id,
                'school_transport_vehicle_id' => (int) $request->school_transport_vehicle_id,
            ]
        );
 
        // Notify parent about transport allocation
        $student = Student::where('store_id', $store_id)->find($request->student_id);
        $route = SchoolTransportRoute::where('store_id', $store_id)->find($request->school_transport_route_id);
        $stop = SchoolTransportStop::where('store_id', $store_id)->find($request->school_transport_stop_id);
        $vehicle = SchoolTransportVehicle::where('store_id', $store_id)->find($request->school_transport_vehicle_id);
        
        if ($student && $route && $stop && $vehicle) {
            $msg = "Dear Parent, transport allocation for {$student->name} has been updated. Route: {$route->name}, Stop: {$stop->name}, Vehicle No: {$vehicle->vehicle_no}, Driver: {$vehicle->driver_name} ({$vehicle->driver_phone}).";
            $push = [
                'title' => 'Transport Allocation Updated',
                'description' => "Vehicle No: {$vehicle->vehicle_no}, Driver: {$vehicle->driver_name} ({$vehicle->driver_phone})."
            ];
            _sendSchoolNotification($student, 'transport_update', $msg, $push);
        }

        Toastr::success('Student transport allocation saved.');
        return back();
    }

    public function allocationDelete($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $allocation = SchoolStudentTransport::where('store_id', $store_id)->findOrFail($id);
        $allocation->delete();

        Toastr::success('Student transport allocation removed.');
        return back();
    }
}
