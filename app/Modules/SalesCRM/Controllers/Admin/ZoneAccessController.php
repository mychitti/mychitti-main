<?php

namespace App\Modules\SalesCRM\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Zone;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZoneAccessController extends Controller
{
    public function index()
    {
        $zones = Zone::orderBy('name')->get();

        // Only show admins who have sales_crm in their role
        $crmRoleIds = AdminRole::all()->filter(function ($role) {
            $modules = json_decode($role->modules ?? '[]', true);
            return is_array($modules) && in_array('sales_crm', $modules);
        })->pluck('id');

        $admins = Admin::whereIn('role_id', $crmRoleIds)
            ->with('salesCrmZones', 'role')
            ->orderBy('f_name')
            ->get();

        return view('sales_crm::admin-views.zone-access.index', compact('admins', 'zones'));
    }

    public function update(Request $request, $adminId)
    {
        $admin = Admin::findOrFail($adminId);
        $zoneIds = $request->input('zone_ids', []);

        $admin->salesCrmZones()->sync($zoneIds);

        Toastr::success('Zone access updated.');
        return back();
    }
}
