<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FreeTrialHistory;
use App\Models\Store;
use App\Models\StoreEnabledModule;
use App\Models\SubModule;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmoduleController extends Controller
{

    public function index(Request $request)
    {
        $submodules = DB::table('sub_modules')->get();
        return view('admin-views.submodule.index', compact('submodules'));
    }

    public function update_info(Request $request)
    {
        $submodules = DB::table('sub_modules')->get();

        foreach ($submodules as $m) {
            $newPrice = $request->input('price_' . $m->id);
            $trialDays = $request->input('trial_days_' . $m->id);

            DB::table('sub_modules')
                ->where('id', $m->id)
                ->update([
                    'price_per_month' => $newPrice,
                    'free_trial_days' => $trialDays,
                ]);
        }

        Toastr::success('Updated successfully');
        return back();
    }
    public function store_billing(Request $request)
    {
        $storeIds = StoreEnabledModule::select('store_id')->with('store')
            ->groupBy('store_id')
            ->paginate(10); // Paginate by unique stores

        $storemodules = StoreEnabledModule::with('store', 'submodule')
            ->whereIn('store_id', $storeIds->pluck('store_id'))
            ->get()
            ->groupBy('store_id');
        $stores = Store::active()->get();
        $submodules = SubModule::all();

        return view('admin-views.submodule.store_modules', compact('storeIds', 'storemodules', 'stores', 'submodules'));
    }
    public function renew_free_trial(Request $request)
    {
        // prx($request->all());
        $request->validate([
            'stores' => 'required|array',
            'stores.*' => 'numeric',
            'submodules' => 'required|array',
            'submodules.*' => 'numeric',
            'free_trial_days' => 'required|integer|min:1',
        ]);

        foreach ($request->submodules as $key1 =>  $submodule) {
            foreach ($request->stores as $key2 => $store) {

                $storeModule = StoreEnabledModule::where('store_id', $store)->where('submodule_id', $submodule)->first();
                if (!$storeModule) {
                    $storeModule =  new StoreEnabledModule();
                }

                $startDate = $storeModule->start_date ?? now();
                $storeModule->store_id = $store;
                $storeModule->submodule_id = $submodule;
                $storeModule->start_date = $startDate;
                $storeModule->type = 'free';

                // Add trial days to start_date and subtract 1 to get last trial day
                $trialEndsOn = Carbon::parse($startDate)->addDays($request->free_trial_days - 1);
                $storeModule->free_trial_extended_until = $trialEndsOn;
                $storeModule->save();
                $submoduleId = (int) $submodule;

                //  Save history
                $h =  new FreeTrialHistory();
                $h->store_id = $store;
                $h->submodule_id = $submoduleId;
                $h->assigned_by = auth('admin')->id();
                $h->start_date = $startDate;
                $h->trial_days = $request->free_trial_days;
                $h->trial_ends_on =  $storeModule->free_trial_extended_until;
                $h->save();
            }
        }
        Toastr::success('Assigned successfully'); 
        return back();
    }
    public function history(Request $request, $store_id = null){
        if($store_id ){
            $history = FreeTrialHistory::with('store' , 'submodule', 'admin')->where('store_id', $store_id)->paginate(10);
        }else{
            $history = FreeTrialHistory::with('store' , 'submodule', 'admin')->paginate(10);
        }
        return view('admin-views.submodule.history', compact('history'));
    }
}
