<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('branches')) {
            DB::statement("CREATE TABLE IF NOT EXISTS branches (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NULL, name VARCHAR(190) NOT NULL, address VARCHAR(255) NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /** Manage branches (owner only). */
    public function index()
    {
        $this->ensureSchema();
        abort_unless(auth('vendor')->check(), 403);
        $branches = Branch::where('store_id', Helpers::get_store_id())->orderBy('name')->get();
        return view('school::vendor.branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $this->ensureSchema();
        abort_unless(auth('vendor')->check(), 403);
        $request->validate(['name' => 'required|string|max:190', 'address' => 'nullable|string|max:255']);

        Branch::updateOrCreate(
            ['id' => $request->id, 'store_id' => Helpers::get_store_id()],
            ['store_id' => Helpers::get_store_id(), 'name' => $request->name, 'address' => $request->address]
        );
        Toastr::success('Branch saved.');
        return back();
    }

    public function delete($id)
    {
        abort_unless(auth('vendor')->check(), 403);
        Branch::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        if ((int) session('school_active_branch') === (int) $id) {
            session()->forget('school_active_branch');
        }
        Toastr::success('Branch removed.');
        return back();
    }

    /** Switch the active branch ({id} = 0 → all branches). Owner only. */
    public function switch($id)
    {
        if (!school_can_switch_branch()) {
            return back();
        }
        if ((int) $id === 0) {
            session()->forget('school_active_branch');
        } else {
            $branch = Branch::where('store_id', Helpers::get_store_id())->find($id);
            if ($branch) {
                session(['school_active_branch' => (int) $id]);
            }
        }
        return back();
    }
}
