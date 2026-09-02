<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\EmployeeRole;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\CentralLogics\Helpers;
use App\Models\Feature;
use App\Models\Permission;
use App\Models\SubModule;
use App\Models\Translation;
use App\Models\VendorSubscription;
use Illuminate\Validation\Rule;

class CustomRoleController extends Controller
{
    public function create(Request $request)
    {
        // The add-role form renders the permission grid straight from the features table, so the
        // WhatsApp rows have to exist before it is drawn — not one page load later.
        \App\Services\WhatsAppPermissions::ensure();

        $key = explode(' ', $request['search']);
        $rl = EmployeeRole::where('store_id', Helpers::get_store_id())->orderBy('name')
            ->when(
                isset($key),
                function ($query) use ($key) {
                    $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('name', 'like', "%{$value}%");
                        }
                    });
                }
            )
            ->paginate(config('default_pagination'));

        $accessibleModules = _accessibleModules();
        return view('vendor-views.custom-role.create', compact('rl', 'accessibleModules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            // 'modules' => 'required|array|min:1',
            'name' => [
                'required',
                Rule::unique('employee_roles')->where(function ($query) {
                    $query->where('store_id', Helpers::get_store_id());
                })
            ],
            'name.0' => 'required',
        ], [
            'name.required' => translate('messages.Role name is required!'),
            // 'modules.required' => translate('messages.Please select atleast one module'),
            'name.0.required' => translate('default_name_is_required'),
        ]);


        $role = new EmployeeRole();
        $role->name = $request->name[array_search('default', $request->lang)];
        $role->modules = $request->has('modules') ? json_encode($request['modules']) : null;
        $role->status = 1;
        $role->store_id = Helpers::get_store_id();
        $role->save();

        // GRANULAR PERMISSION
        foreach ($request['modules'] as $key => $master_module) {
            $permissions = $request->input('permissions', []);
            $data = [];

            foreach ($permissions as $featurePermissionId) {
                // check if this permission belongs to the current master module
                $exists = DB::table('feature_permissions as fp')
                    ->join('features as f', 'fp.feature_id', '=', 'f.id')
                    ->where('fp.id', $featurePermissionId)
                    ->where('f.master_module', $master_module) // 🔹 filter by module
                    ->exists();

                if ($exists) {
                    $data[] = [
                        'role_id'               => $role->id,
                        'feature_permission_id' => $featurePermissionId,
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ];
                }
            }
            if (!empty($data)) {
                DB::table('role_feature_permissions')->insert($data);
            }
        }
        // GRANULAR PERMISSION end

        $data = [];
        $default_lang = str_replace('_', '-', app()->getLocale());
        foreach ($request->lang as $index => $key) {
            if ($default_lang == $key && !($request->name[$index])) {
                if ($key != 'default') {
                    array_push($data, array(
                        'translationable_type' => 'App\Models\EmployeeRole',
                        'translationable_id' => $role->id,
                        'locale' => $key,
                        'key' => 'name',
                        'value' => $role->name,
                    ));
                }
            } else {
                if ($request->name[$index] && $key != 'default') {
                    array_push($data, array(
                        'translationable_type' => 'App\Models\EmployeeRole',
                        'translationable_id' => $role->id,
                        'locale' => $key,
                        'key' => 'name',
                        'value' => $request->name[$index],
                    ));
                }
            }
        }

        Translation::insert($data);

        if ($request->form_type == 'ajax') {
            return response()->json(['status' => true, 'msg' => "Added  Successfully", 'action' => 'add_role', 'role_id' => $role->id]);
        } else {
            Toastr::success(translate('messages.role_added_successfully'));
            return back();
        }
    }

    public function edit($id)
    {
        // The WhatsApp rows carried a NULL master_module and so never appeared in this grid at all.
        // Seeded here as well as from the sidebar so the section is complete the first time an
        // owner opens a role, not one page load later.
        \App\Services\WhatsAppPermissions::ensure();

        $accessibleModules = [];

        $submodules = SubModule::all();

        foreach ($submodules as $submodule) {
            $status = _isSubmoduleEnabled($submodule->id);

            if ($status['enabled'] || Helpers::permission_check($submodule->Key)) {
                $accessibleModules[$submodule->Key] = $submodule->name;
            }
        }

        // Not a SubModule — WhatsApp is open to every store and bills through its own plan — but it
        // still needs a checkbox here, because the grid only shows and saves permissions whose
        // master_module the role has ticked.
        $accessibleModules[\App\Services\WhatsAppPermissions::MODULE] = 'WhatsApp';

        // Basic billing is free for every store: its route group carries no planwise guard, and
        // list / view / add_basic / edit / delete / pay / settings are all flagged free. The
        // SubModule above only covers the paid "Advanced Billing" tier, so without this a store
        // that never bought it could raise invoices itself but had no way to delegate that to
        // staff. Paid rows in the section stay enforced by hasPermission().
        $accessibleModules['billing'] = $accessibleModules['billing'] ?? 'Billing';
        $role = EmployeeRole::withoutGlobalScope('translate')->with('permissions')->where('store_id', Helpers::get_store_id())->where(['id' => $id])->first(['id', 'name', 'modules']);
        return view('vendor-views.custom-role.edit', compact('role', 'accessibleModules'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'modules' => 'required|array|min:1',
            'name' => [
                'required',
                Rule::unique('employee_roles')->where(function ($query) use ($id) {
                    $query->where('store_id', Helpers::get_store_id())->where('id', '<>', $id);
                })
            ], 
            'name.0' => 'required',
        ], [
            'name.required' => translate('messages.Role name is required!'),
            'name.unique' => translate('messages.Role name already taken!'),
            'modules.required' => translate('messages.Please select atleast one module'),
            'name.0.required' => translate('default_name_is_required'),
        ]);

        $role = EmployeeRole::where('store_id', Helpers::get_store_id())->where(['id' => $id])->first();
        $role->name = $request->name[array_search('default', $request->lang)];
        $role->modules = $request->has('modules') ? json_encode($request['modules']) : null;
        $role->status = 1;
        $role->store_id = Helpers::get_store_id();
        $role->save();

        // granular permissions
        // $ids = $request->input('permissions', []); // array of permission IDs
        // $role->permissions()->sync($ids);
        // granular permissions end

        //  GRANULAR PERMISSION UPDATE
        $allSyncedIds = []; // collect all feature_permission_ids that should remain

        foreach ($request['modules'] as $master_module) {
            $permissions = $request->input('permissions', []);

            $modulePermissionIds = DB::table('feature_permissions as fp')
                ->join('features as f', 'fp.feature_id', '=', 'f.id')
                ->whereIn('fp.id', $permissions)
                ->where('f.master_module', $master_module)
                ->pluck('fp.id')
                ->toArray();

            if (!empty($modulePermissionIds)) {
                $allSyncedIds = array_merge($allSyncedIds, $modulePermissionIds);
            }
        }

        //  Remove all old permissions not in current selection
        $role->permissions()->sync($allSyncedIds);
        //  GRANULAR PERMISSION UPDATE end




        $default_lang = str_replace('_', '-', app()->getLocale());
        foreach ($request->lang as $index => $key) {
            if ($default_lang == $key && !($request->name[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\EmployeeRole',
                            'translationable_id' => $role->id,
                            'locale' => $key,
                            'key' => 'name'
                        ],
                        ['value' => $role->name]
                    );
                }
            } else {

                if ($request->name[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\EmployeeRole',
                            'translationable_id' => $role->id,
                            'locale' => $key,
                            'key' => 'name'
                        ],
                        ['value' => $request->name[$index]]
                    );
                }
            }
        }


        Toastr::success(translate('messages.role_updated_successfully'));
        return back();
        // return redirect()->route('vendor.custom-role.create');
    }

    public function distroy($id)
    {
        $role = EmployeeRole::where('store_id', Helpers::get_store_id())->where(['id' => $id])->delete();
        Toastr::success(translate('messages.role_deleted_successfully'));
        return back();
    }
}
