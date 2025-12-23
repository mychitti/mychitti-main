<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\AssetDepreciation;
use App\Models\MonthlyMaintanance;
use Carbon\Carbon;

class MonthlyFinanceController extends Controller
{

    public function monthly_maintanance(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $search = request('search') ?? '';
        $preset = request('date_range') ?? 'this_year';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $masters = MonthlyMaintanance::where('store_id', $store_id)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('expense_type', 'like', '%' . $search . '%')
                        ->orWhere('payment_day', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%');
                });
            })
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->where('master', 1)
            ->get();

        $masterIds = $masters->pluck('id')->toArray();

        $records = MonthlyMaintanance::where('store_id', $store_id)
            ->where('master', 0)
            ->whereIn('parent', $masterIds)
            ->where('due', '1')
            ->paginate(10);
            // prx($records)
        ;
        foreach ($masters as $master) {
            $dues = MonthlyMaintanance::where('store_id', $store_id)
                ->where('master', 0)
                ->where('parent', $master->id)
                ->where('due', 1)
                ->orderBy('for_month')
                ->get(['for_month']);

            if ($dues->count() > 0) {
                $dueMonths = $dues->map(function ($due) {
                    return Carbon::parse($due->for_month)->format('F Y');
                })->toArray();

                $master->stts = 'Due for ' . implode(', ', $dueMonths);
            } else {
                $master->stts = 'No dues';
            }
        }


        return view('vendor-views.account.monthly-finance.maintanance', compact( 'preset', 'records', 'masters'));
    }
    public function property_valuation(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $asset_depreciations = AssetDepreciation::with('asset.inventoryItem')->where('store_id', $storeId)->get();
        return view('vendor-views.account.monthly-finance.property-valuation', compact("asset_depreciations"));
    }
}
