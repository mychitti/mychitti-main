<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;

use Illuminate\Support\Facades\DB;

use App\Models\StoreSchedule;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\AccountDetail;
use App\Models\Category;
use App\Models\Holiday;
use App\Models\HolidayOverride;
use App\Models\OrderType;
use App\Models\Plan;
use App\Models\StoreConfig;
use App\Models\StoreSignature;
use App\Models\StoreTnc;
use App\Models\TempStoreStatus;
use App\Models\Translation;
use App\Models\VendorEmployee;
use App\Models\VendorRequirement;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function invoice_settings(Request $request)
    {
        $tncs = StoreTnc::where('store_id', Helpers::get_store_id())->where('tnc_type', 'invoice')->get();
        $store_id = Helpers::get_store_id();
        $accounts = AccountDetail::where('user_type', 'vendor')->where('user_id',  $store_id)->where('type', 'invoice')->get();
        $staffs = VendorEmployee::where('store_id',  $store_id)->get();
        $signatures = StoreSignature::with('employee')->where('store_id', $store_id)->where('type', 'invoice')->get();
        $store = Store::where('id',  $store_id)->first();
        return view('vendor-views.settings.invoice_settings', compact('tncs', 'signatures', 'staffs', 'accounts',  'store'));
    }
    public function webpage_settings_update(Request $request)
    {
        // prx($request->all());
        $storeId = Helpers::get_store_id();

        StoreConfig::updateOrInsert(['store_id' => $storeId], [
            'webpage_name' => $request->website_name,
            'webpage_email' => $request->email,
            'webpage_address' => $request->address,
            'webpage_phones' => json_encode($request->phone),
            'webpage_latitude' => $request->latitude,
            'webpage_longitude' => $request->longitude,
            'inventory_items_position' => $request->inventory_items_position,
        ]);
        Toastr::success('Updated Successfully');

        return back();
    }
    public function webpage_settings(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $store = Store::where('id',  $store_id)->first();
        $storeConfig = StoreConfig::firstOrNew(['store_id' => Helpers::get_store_id()]);
        return view('vendor-views.settings.webpage', compact('store', 'storeConfig'));
    }
    public function common_setting_save(Request $request)
    {
        //  prx($request->all());
        $data = $request->except(['_token', '_method']);
        if (!empty($data)) {
            $config = StoreConfig::firstOrNew(['store_id' => Helpers::get_store_id()]);
            $config->fill($data); // updates only fields present in request
            $config->save();

            Toastr::success('Updated Successfully');
        } else {
            Toastr::info('No fields to update');
        }
        return back();
    }
    public function receivable_receipts(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $store = StoreConfig::where('store_id', $store_id)->first();

        return view('vendor-views.settings.receivable_receipts_settings', compact('store'));
    }
    public function pos(Request $request)
    {

        $store_id = Helpers::get_store_id();
        $store = StoreConfig::where('store_id', $store_id)->first();
        $order_type = OrderType::where('store_id', $store_id)->get();

        return view('vendor-views.settings.pos', compact('store', 'order_type'));
    }

    public function quotation_settings(Request $request)
    {
        $tncs = StoreTnc::where('store_id', Helpers::get_store_id())->where('tnc_type', 'quotation')->get();
        $store_id = Helpers::get_store_id();
        $accounts = AccountDetail::where('user_type', 'vendor')->where('user_id',  $store_id)->where('type', 'quotation')->get();
        $staffs = VendorEmployee::where('store_id',  $store_id)->get();
        $signatures = StoreSignature::with('employee')->where('store_id', $store_id)->where('type', 'quotation')->get();
        $store = Store::where('id',  $store_id)->first();
        return view('vendor-views.settings.quotation_settings', compact('tncs', 'signatures', 'staffs', 'accounts',  'store'));
    }
    public function store_settings(Request $request) {}
    public function holiday_settings(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $holidays = _getStoreHolidays($storeId);
        return view('vendor-views.settings.holidays', compact('holidays'));
    }
    public function holiday_delete(Request $request, $holidayId)
    {
        $storeId = Helpers::get_store_id();

        $holiday = Holiday::findOrFail($holidayId);

        if ($holiday->vendor_id == $storeId && $holiday->is_global == 0) {
            $holiday->delete();
            Toastr::success('Your holiday has been deleted.');
        } elseif ($holiday->is_global == 1) {
            HolidayOverride::updateOrCreate(
                [
                    'holiday_id' => $holiday->id,
                    'vendor_id' => $storeId
                ],
                [
                    'is_deleted' => 1,
                    'custom_title' => null,
                    'custom_date' => null,
                ]
            );
            Toastr::success('Global holiday has been hidden for your store.');
        } else {
            Toastr::error('You are not allowed to delete this holiday.');
        }

        return back();
    }
    public function holiday_add(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date|unique:holidays,date,NULL,id,vendor_id,' . Helpers::get_store_id(),
        ]);

        Holiday::create([
            'title' => $request->title,
            'date' => $request->date,
            'is_global' => 0,
            'vendor_id' => Helpers::get_store_id(),
            'created_by' => 'vendor',
        ]);
        Toastr::success('Holiday added successfully.');
        return back();
    }
    public function holiday_update(Request $request)
    {
        $holiday_id = $request->hl_id;

        $request->validate([
            'title' => 'required|string|max:255',
            'date'  => 'required|date',
        ]);
        $storeId = Helpers::get_store_id();
        $holiday = Holiday::findOrFail($holiday_id);
        if ($holiday->vendor_id == $storeId && $holiday->is_global == 0) {
            $holiday->update([
                'title' => $request->title,
                'date'  => $request->date
            ]);

            Toastr::success('Your holiday has been updated.');
        } elseif ($holiday->is_global == 1) {
            HolidayOverride::updateOrCreate(
                [
                    'vendor_id'   => $storeId,
                    'holiday_id'  => $holiday_id
                ],
                [
                    'custom_title' => $request->title,
                    'custom_date'  => $request->date,
                    'is_deleted'   => 0
                ]
            );
            Toastr::success('Global holiday has been customized for your store.');
        } else {
            Toastr::error('You are not allowed to update this holiday.');
        }
        return back();


        return back()->with('success', 'Holiday updated.');
    }
    public function profile_settings(Request $request)
    {
        $allPlans = Plan::where('status', 1)
            ->where(function ($q) {
                $q->whereNull('store_id')
                    ->orWhere('store_id', Helpers::get_store_id());
            })
            ->get();


        $store_data = Helpers::get_store_data();

        $module_categories = Category::where('module_id', $store_data->module_id)->where('position', 0)->where('status', 1)->get();
        $module_subcategories = Category::where('module_id', $store_data->module_id)->where('position', 1)->where('status', 1)->get();

        // all items set 1
        $allcategories_1 = [];
        array_push($allcategories_1, $store_data->category_1);
        $ct1 = Category::where('parent_id', $store_data->category_1)->pluck('id')->toArray();
        $allcategories_1 = array_merge($allcategories_1, $ct1);
        $items_1 = DB::table('items')->whereIn('category_id', $allcategories_1)->get();

        // all items set 2
        $allcategories_2 = [];
        array_push($allcategories_2, $store_data->category_2);
        $ct2 = Category::where('parent_id', $store_data->category_2)->pluck('id')->toArray();
        $allcategories_2 = array_merge($allcategories_2, $ct2);
        $items_2 = DB::table('items')->whereIn('category_id', $allcategories_2)->get();
        if (auth('vendor_employee')->check()) {
            $data['resign'] = DB::table('employee_resignations')->where('employee_id', Helpers::get_loggedin_user()->id)->exists();
        } else {
            $data['resign'] = 0;
        }
        return view('vendor-views.profile.index', compact('data', 'allPlans', 'items_1', 'items_2', 'store_data', 'module_categories', 'module_subcategories'));
    }
    public function quick_actions_save(Request $request)
    {
        $storeId = Helpers::get_store_id();
        DB::table('store_menu_visibility')->where('store_id', $storeId)->where('menu_type', 'quick_action')->delete();
        if ($request->menu) {
            foreach ($request->menu as $key) {
                DB::table('store_menu_visibility')->insert([
                    'store_id' => $storeId,
                    'menu_type' => 'quick_action',
                    'menu_key' => $key,
                    'is_visible' => 1
                ]);
            }
        }

        Toastr::success('Quick Actions Updated.');
        return back();
    }
    public function menu_preference(Request $request)
    {
        $storeId = Helpers::get_store_id();

        $full_menu = DB::table('menu')->where('menu_type', 'sidebar')->where('status', 1)->orderBy('name', 'asc')->get();
        $groupedMenus = $full_menu->groupBy(function ($item) {
            return $item->group ?? 'other';
        });

        $full_quick_actions = DB::table('menu')->where('menu_type', 'quick_action')->where('status', 1)->get();

        $selectedMenus = DB::table('store_menu_visibility')
            ->where('store_id', $storeId)
            ->where('menu_type', 'sidebar')
            ->pluck('menu_key')
            ->toArray();

        $selectedQuickActions = DB::table('store_menu_visibility')
            ->where('store_id', $storeId)
            ->where('menu_type', 'quick_action')
            ->pluck('menu_key')
            ->toArray();

        // prx($selectedQuickActions);

        // default menu items
        if (empty($selectedMenus)) {
            $selectedMenus = DB::table('menu')
                ->where('default', 1)
                // slug is your menu_key
                ->pluck('slug') // slug is your menu_key
                ->toArray();
        }
        return view('vendor-views.business-settings.menu_preference', compact('full_menu', 'full_quick_actions', 'selectedMenus', 'selectedQuickActions'));
    }
    public function menu_preference_save(Request $request)
    {
        $storeId = Helpers::get_store_id();
        DB::table('store_menu_visibility')->where('store_id', $storeId)->where('menu_type', 'sidebar')->delete();
        if ($request->menu) {
            foreach ($request->menu as $key) {
                DB::table('store_menu_visibility')->insert([
                    'store_id' => $storeId,
                    'menu_type' => 'sidebar',
                    'menu_key' => $key,
                    'is_visible' => 1
                ]);
            }
        }
        Toastr::success('Menu Preference updated.');
        return back();
    }
}
