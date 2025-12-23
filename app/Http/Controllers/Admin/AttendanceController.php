<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Department;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\Admin;
use App\Exports\AttendanceExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Leave;
use App\Models\VendorEmployee;
use DateInterval;
use DatePeriod;
use DateTime;

class AttendanceController extends Controller
{
   
    
    public function index(Request $request)
    {
        $v_id = Helpers::get_store_id();
        
        $staff =  Admin::whereNot('role_id', 1)->latest()->paginate(config('default_pagination'));
    //   print_r($staff); die;
        return view('admin-views.attendance.index', compact('staff'));
    } 
    public function manage(Request $request, $id)
    {
        $v_id = 0; 
        if(isset($request->year)){
         $filter_year =   $request->year; 
        }else{
           $filter_year = date('Y');
        }
        if(isset($request->month)){
            $filter_month =  $request->month; 
        }else{
            $filter_month = date('m');
        }
        $staff = Admin::find($id);
        $attendance = Attendance::where(['vendor_id'=> 0,'employee_type' => 'admin_employee', 'employee_id' => $id, 'month' => $filter_month, 'year' =>$filter_year])->get()->toArray();
        
        $day_data['absent'] = 0;
        $day_data['present'] = 0;
        $day_data['holiday'] = 0;
        $day_data['cl'] = 0;
        $day_data['halfday'] = 0;
        $day_data['sunday'] = 0;
        $labelArr = [];
        $daArr = [];
        
        
       
        
        foreach($attendance as $att){
          
            array_push($daArr, $att['day']);
            array_push($labelArr, $att['label']);
            if($att['label'] == 'Sun'){$day_data['sunday'] ++;}
           if($att['label'] == 'A'){$day_data['absent'] ++;}
           if($att['label'] == 'P'){$day_data['present'] ++;}
           if($att['label'] == 'CL'){$day_data['cl'] ++;}
           if($att['label'] == 'HD'){$day_data['halfday'] ++;}
           if($att['label'] == 'HL'){$day_data['holiday'] ++;}
        }
        
        // print_r($day_data);
        // die;
        
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $filter_month, $filter_year);

        $firstDayOfMonth = date('N', strtotime(date($filter_year. '-'. $filter_month.'-01')));
        
        $sundays_in_month = 0;
        
        for ($t=1; $t <=  $days_in_month  ; $t++){
            if(date('l', strtotime(date('Y-'.$filter_month.'-'. $t))) == 'Sunday'){
             $sundays_in_month++;
            }
        }
        
        if(!empty($attendance)){
            $sundays_in_month = $day_data['sunday'];
        }
        
        
        // die;
    
        return view('admin-views.attendance.manage', compact(
            'staff',
            'filter_year', 
            'filter_month', 
            'attendance',
            'day_data',
            'days_in_month',
            'firstDayOfMonth',
            'sundays_in_month',
            'daArr',
            'labelArr'
            
            ));
    }

    public function report(Request $request)
    {

        $fromdate = $request->from ?? date('Y-m-d');
        $todate = $request->to ?? date('Y-m-d');

        $staff = Admin::whereNot('role_id', 1)->get()->toArray();

        $startDate = new DateTime($fromdate);
        $endDate = new DateTime($todate);
        $endDate->modify('+1 day');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($startDate, $interval, $endDate);

        foreach ($staff as $key => $value) {
            $lev = 0;
            $present = 0;
            $absent = 0;
            foreach ($period as $date) {
                $dt = $date->format('Y-m-d');
                $staff[$key][$dt] = Attendance::where('date', $dt)->where('employee_id', $value['id'])->where('employee_type' , 'admin_employee')->get()->toArray();
                if (!empty($staff[$key][$dt]) && ($staff[$key][$dt][0]['label'] == 'CL' || $staff[$key][$dt][0]['label'] == 'SL')) {
                    $lev++;
                }
                if (!empty($staff[$key][$dt]) &&  $staff[$key][$dt][0]['label'] == 'A') {
                    $absent++;
                }
                if (!empty($staff[$key][$dt]) &&  $staff[$key][$dt][0]['label'] == 'P') {
                    $present++;
                }
                $staff[$key]['casual_leaves'] = $lev;
                $staff[$key]['absent_days'] = $absent;
                $staff[$key]['present_days'] = $present;
            }
        }
        //format dates 
        $formattedDate = [];
        $dates = [];
        foreach ($period as $date) {
            array_push($formattedDate, $date->format('d M'));
            array_push($dates, $date->format('Y-m-d'));
        }

        return view('admin-views.attendance.report', compact('staff', 'fromdate', 'todate', 'dates', 'formattedDate'));
    }

    public function export(Request $request)
    {


        $fromdate = $request->from ?? date('Y-m-d');
        $todate = $request->to ?? date('Y-m-d');

        $staff =  VendorEmployee::where('store_id', Helpers::get_store_id())->get()->toArray();

        $startDate = new DateTime($fromdate);
        $endDate = new DateTime($todate);
        $endDate->modify('+1 day');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($startDate, $interval, $endDate);

        foreach ($staff as $key => $value) {
            $lev = 0;
            $present = 0;
            $absent = 0;
            foreach ($period as $date) {
                $dt = $date->format('Y-m-d');
                $staff[$key][$dt] = Attendance::where('date', $dt)->where('employee_id', $value['id'])->get()->toArray();
                if (!empty($staff[$key][$dt]) && ($staff[$key][$dt][0]['label'] == 'CL' || $staff[$key][$dt][0]['label'] == 'SL')) {
                    $lev++;
                }
                if (!empty($staff[$key][$dt]) &&  $staff[$key][$dt][0]['label'] == 'A') {
                    $absent++;
                }
                if (!empty($staff[$key][$dt]) &&  $staff[$key][$dt][0]['label'] == 'P') {
                    $present++;
                }
                $staff[$key]['casual_leaves'] = $lev;
                $staff[$key]['absent_days'] = $absent;
                $staff[$key]['present_days'] = $present;
            }
        }
        //format dates 
        $dates = [];
        $headings =  ['Employee', 'P', 'A', 'L'];
        foreach ($period as $date) {
            array_push($headings, $date->format('d M'));
            array_push($dates, $date->format('Y-m-d'));
        }   
        $data = [];
        foreach ($staff as $key => $lead) {
            $data[$key] = [
                $lead['f_name'] . ' ' . $lead['l_name'],
                $lead['present_days'],
                $lead['absent_days'],
                $lead['casual_leaves'] 
            ];

            foreach ($dates as $date) {
                array_push($data[$key] , !empty($lead[$date]) ? $lead[$date][0]['label'] : '-');
            }
            
        }

        return Excel::download(new AttendanceExport($data, $headings), 'attendance.xlsx');
    }
    
    public function save_att(Request $request){
      
        $id = $request->post('emp_id');
        
        Attendance::where(['month'=> $request->post('month') ,'employee_type' => 'admin_employee',  'employee_id' => $id, 'year' =>  $request->post('year')])->delete();
    
        $v_id = 0; 

        foreach ( $request->post('daysArr') as $key => $value) {
           $att = new Attendance;
            $att->vendor_id = $v_id;
            $att->employee_type = 'admin_employee';
            $att->employee_id = $request->post('emp_id');
            $att->date = $request->post('year'). '-' . $request->post('month') . '-' . $request->post('daysArr')[$key];
            $att->label = $request->post('statusArr')[$key];
            $att->day = $request->post('daysArr')[$key];
            $att->month = $request->post('month');
            $att->year = $request->post('year');
            $att->created_at = date('Y-m-d H:i:s');
           $att->save();

           $leave = Leave::where(['vendor_id' => $v_id, 'emp_id' => $request->post('emp_id'), 'day' => $request->post('daysArr')[$key] , 'month' =>$request->post('month'), 'year' => $request->post('year'), 'employee_type' => 'vendor_employee'])->exists();
           if(!$leave && in_array($request->post('statusArr')[$key], ['SL', 'CL', 'HD'])){
               if($request->post('statusArr')[$key] == 'HD'){
                   $request->post('statusArr')[$key] = 'HDS';
               }
               $leave = new Leave;
               $leave->vendor_id = $v_id;
               $leave->employee_type = 'admin_employee';
               $leave->emp_id = $request->post('emp_id');
               $leave->day = $request->post('daysArr')[$key];
               $leave->status = 'approved';
               $leave->added_by = 'admin';
               $leave->month = $request->post('month');
               $leave->year = $request->post('year');
               $leave->leave_type = $request->post('statusArr')[$key];
               $leave->reason = '-';
               $leave->created_at = date('Y-m-d H:i:s');
               $leave->leave_date = $request->post('year'). '-' . $request->post('month') . '-' .$request->post('daysArr')[$key];
               $leave->save();
           }

            
        }

      
        Toastr::success('Attendance Information saved successfully');
      
         $data['status']= true;
         $data['msg'] = 'Updated Successfully';
         
         echo json_encode($data);
    }

    public function status_change(Request $request)
    {

        $id = $request->post('d_id');
        $status = $request->post('status');

        $query =  Department::where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        // echo $query;
        return back();
    }
    public function status(Request $request)
    {
        $coupon = Staff::find($request->id);
        $coupon->status = $request->status;
        $coupon->save();
        Toastr::success('Staff Status Changed Successfully');
        return back();
    }
    public function delete_department(Request $request, $id)
    {
        $query =  Department::find($id)
            ->delete();
            Toastr::success('Department Deleted Successfully');
        return back();
    }
    public function delete(Request $request, $id)
    {
        $query =  Staff::find($id)
            ->delete();
            Toastr::success('Staff Deleted Successfully');
        return back();
    }

    public function add()
    {
        $departments = Department::where('status', '1')->get();
        return view('vendor-views.staff.add', compact('departments'));
    }
    public function save_info(Request $request)
    {
        $id = $request->post('staff_id');
        
            $validator = Validator::make($request->all(), [
                'username' => 'required|max:100',
                'name' => 'required|max:100',
                'city' => 'required|max:100',
                'pincode' => 'required',
                'department_id' => 'required',
                'salary_per_month' => 'required',
                'email' => 'required|email|unique:staff,email,'.$id,
                'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20',
            ], [
                'name.required' => 'Please Enter Name',
                'username.required' => 'Please Enter Username',
                'city.required' => 'Please Enter city',
                'email.required' => 'Please Enter Email',
                'pincode.required' => 'Please Enter Pincode',
                'department_id.required' => 'Please Select Department',
                'salary_per_month.required' => 'Please Enter Salary',
                'mobile.required' => 'Please Enter Mobile',
            ]);
             if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

        if ($id == '') { // for new lead  
            $staff = new Staff;
        } else {
            $staff = Staff::find($id);
        }
        $v_id = \App\CentralLogics\Helpers::get_store_id(); 
        // echo $v_id;die;

        $staff->vendor_id = $v_id;
        $staff->department_id = $request->post('department_id');
        $staff->username = $request->post('username');
        $staff->name = $request->post('name');
        $staff->email = $request->post('email');
        $staff->mobile = $request->post('mobile');
        $staff->address = $request->post('address');
        $staff->city = $request->post('city');
        $staff->pincode = $request->post('pincode');
        $staff->salary_per_month = $request->post('salary_per_month');
        $staff->dob = $request->post('dob');
        $staff->created_at = date('Y-m-d H:i:s');
        
        if ($id == '') { // for new lead
            $staff->save();
            Toastr::success('Staff Information saved successfully');
        }else{
            $staff->update();
            Toastr::success('Staff Information updated successfully');
        }
        return redirect('staff/list');
    }

    public function lead_approval(Request $request)
    {
        $id = $request->post('lead_id');
        $lead = Lead::find($id);
        $lead->approval = $request->post('approval');
        $lead->save();
        return response()->json(['msg' => ucfirst($request->post('approval')) . 'ed', 'status' => true]);
    }
}
