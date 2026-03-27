<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Item;
use Brian2694\Toastr\Facades\Toastr;
use App\Models\VendorEmployee;
use App\Models\Project;
use App\Models\Admin;
use App\CentralLogics\Helpers;

class ProjectController extends Controller
{

    public function index(Request $request)
    {
        $employees = Admin::whereNot('role_id', 1)->get();
        $projects = Project::where('vendor_id', 0)->get();
        
        
        return view('admin-views.project.index', compact('projects', 'employees'));
    }
 

   public function status_change(Request $request)
    {
        $coupon = Project::find($request->id);
        $coupon->status = $request->status;
        $coupon->save();
        Toastr::success('Project Status Changed Successfully');
        return back();
    }
    public function delete(Request $request, $id)
    {

        $query =  Project::find($id)
            ->delete();
        return back();
    }

 
    public function save_info(Request $request)
    {
        $id = $request->post('project_id');

        if ($id == '') { // for new project

            $validator = Validator::make($request->all(), [
                'title' => 'required|max:255',
                'team_members.*' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
            ], [
                'title.required' => 'Please Enter Project Title',
                'team_members.required' => 'Please Assign Memebers to Project',
            ]);
    
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
            $project = new Project;
            $project->vendor_id = 0;

        } else {
            $project = Project::find($id);
        }

        $project->project_title = $request->post('title');
        $project->team_members = implode(',',$request->post('team_members'));
        $project->team_leader = $request->post('team_leader');
        $project->progress_status = $request->post('prog_status');
        $project->start_date = $request->post('start_date');
        $project->end_date = $request->post('end_date');
        $project->cost = $request->post('cost_est');
        $project->advance_pay = $request->post('advance_pay');
        $project->status = $request->post('status');
        $project->created_at = date('Y-m-d H:i:s');
        $project->save();

        if ($id == '') {
                $request->session()->flash('msg', 'Project Information saved successfully');
        }else{
               $request->session()->flash('msg', 'Project Information updated successfully');
        }
        return back();
    }


}
