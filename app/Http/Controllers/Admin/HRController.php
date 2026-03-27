<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcceptedServiceRequest;
use App\Models\Admin;
use App\Models\AdvanceRequest;
use App\Models\EmployeeTimeCard;
use App\Models\Leave;
use App\Models\Project;
use App\Models\StoreTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HRController extends Controller
{

    public function dashboard()
    {

        $store_id  = 0;
        // chart js
        $months = [];
        $now = Carbon::now();

        for ($m = 1; $m <= $now->month; $m++) {
            $months[] = Carbon::create()->month($m)->format('F'); // Jan, Feb, ...
        }
        $chart_data['months'] = $months;

        $currentYear = Carbon::now()->year;
        $monthlyLeads = AcceptedServiceRequest::join('service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
            ->where('accepted_service_requests.vendor_id', $store_id)
            ->where('accepted_service_requests.assigned_type', 'staff')
            ->where('accepted_service_requests.current_status', 'Completed')
            ->whereYear('accepted_service_requests.assigned_at', $currentYear)
            ->selectRaw('MONTH(accepted_service_requests.assigned_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');
        $monthlyTasks = StoreTask::where('store_id', $store_id)->where('status', 'Completed')->whereYear('created_at', $currentYear)->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');
        $monthlyProjects = Project::where('vendor_id', $store_id)->where('progress_status', 'Completed')->whereYear('created_at', $currentYear)->selectRaw('MONTH(created_at) as month, COUNT(*) as count')->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');
        // prx( $monthlyProjects);

        $leadsData = [];
        $tasksData = [];
        $projectsData = [];

        for ($m = 1; $m <= Carbon::now()->month; $m++) {
            $months[] = Carbon::create()->month($m)->format('F');
            $leadsData[] = $monthlyLeads->get($m, 0);
            $tasksData[] = $monthlyTasks->get($m, 0);
            $projectsData[] = $monthlyProjects->get($m, 0);
        }

        $chart_data['leads'] = $leadsData;     // count per month
        $chart_data['projects'] =  $tasksData;
        $chart_data['tasks'] = $projectsData;


        $now = Carbon::now();
        //all
        $staff = Admin::with('role')->where('role_id', '!=', 1)->get();
        $data['future_unapproved_leaves'] = Leave::with('employee')->where('vendor_id', $store_id)
            ->where('employee_type', 'admin_employee')
            ->whereNot('status', 'approved')
            ->where('year', $now->year)
            ->get();
        $data['advance_requests'] = AdvanceRequest::where('store_id', $store_id)->where('status', 'pending')->count();
        
        //today 
        $attendances = EmployeeTimeCard::where('vendor_id', $store_id)->whereDate('date', now())->get();

        //this month
        $data['total_base_salary'] = Admin::where('role_id', '!=', 1)->sum('base_salary');
        $data['approved_leaves'] = Leave::where('vendor_id', $store_id)
            ->where('employee_type', 'admin_employee')
            ->where('month', $now->month)
            ->where('status', 'approved')
            ->where('year', $now->year)
            ->get();
        $data['unapproved_leaves'] = Leave::where('vendor_id', $store_id)
            ->where('employee_type', 'admin_employee')
            ->where('month', $now->month)
            ->whereNot('status', 'approved')
            ->where('year', $now->year)
            ->get();
        $data['holidays'] = _getStoreHolidays($store_id, 'upcoming', null);
        $data['resignations'] =  DB::table('employee_resignations')->where('store_id', 0)->get();
        return view('admin-views.hr.dashboard', compact('chart_data', 'staff', 'attendances', 'data'));
    }
}
