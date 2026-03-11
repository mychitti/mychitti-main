<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Category;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use App\Exports\StoreCategoryExport;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel; 
use Rap2hpoutre\FastExcel\FastExcel;
use App\Exports\StoreSubCategoryExport;
use App\Models\Store;
use Illuminate\Support\Facades\Config;

class CategoryController extends Controller
{
    function index(Request $request)
    {
        $key = explode(' ', $request['search']);
        $categories = Category::where(['position' => 0])->module(Helpers::get_store_data()->module_id)
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()->paginate(config('default_pagination'));
        return view('vendor-views.category.index', compact('categories'));
    }
  

    public function selected(Request $request)
    {
        $store_data = Helpers::get_store_data();

        $module_categories = Category::where('module_id', $store_data->module_id)->where('position', 0)->get();
        $module_subcategories = Category::where('module_id', $store_data->module_id)->where('position', 1)->get();

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

        return view('vendor-views.category.selected', compact('items_1', 'items_2', 'store_data', 'module_categories', 'module_subcategories'));
    }
    public function update_selected(Request $request)
    {
        $store = Store::find(Helpers::get_store_id());

        if ($store->module_id == 6) {
            // services  offered 1
            $services_1 = explode(',', $store->services_1);
            //   prx($services_1);
            if (count($services_1) && $services_1[0]) {
                foreach ($services_1 as $key => $value) {
                    // echo $value . ' - ' ;
                    $fetchServicesRow  = DB::table('items')->where('id', $value)->first();
                    if ($fetchServicesRow) {
                        $storeArr = explode(',', $fetchServicesRow->store_ids);

                        //remove store
                        $index = array_search($store->id, $storeArr);
                        unset($storeArr[$index]);

                        $newStoreString = implode(',', $storeArr);

                        $oldIds = _getIdsFrist($value);
                        DB::table('items')->where('id', $value)->update(['store_ids' => $newStoreString]);
                        _trackStoreIds('test 15', $newStoreString, $value, '-', Helpers::get_store_id() . '_vendor', $oldIds);
                    }
                }
                // die;
            }

            // services offered 2
            $services_2 = explode(',', $store->services_2);
            if (count($services_2) && $services_2[0]) {
                foreach ($services_2 as $key => $value) {

                    $fetchServicesRow  = DB::table('items')->where('id', $value)->first();
                    if ($fetchServicesRow) {
                        $storeArr = explode(',', $fetchServicesRow->store_ids);

                        //remove store
                        $index = array_search($store->id, $storeArr);
                        unset($storeArr[$index]);

                        $newStoreString = implode(',', $storeArr);

                        $oldIds = _getIdsFrist($value);
                        DB::table('items')->where('id', $value)->update(['store_ids' => $newStoreString]);
                        _trackStoreIds('test 14', $newStoreString, $value, '-', Helpers::get_store_id() . '_vendor', $oldIds);
                    }
                }
            }

            $store->category_1 = $request->category_1;
            $store->category_2 = $request->category_2;
            $store->services_1 = $request->services_1 ? implode(',', $request->services_1) : '';
            $store->services_2 = $request->services_2 ? implode(',', $request->services_2) : '';
            $store->update();

            // services  offered 1
            if ($request->services_1 && count($request->services_1)) {
                foreach ($request->services_1 as $key => $value) {
                    $fetchServicesRow  = DB::table('items')->where('id', $value)->first();
                    $storeArr = explode(',', $fetchServicesRow->store_ids);
                    array_push($storeArr, $store->id);
                    $newStoreString = implode(',', $storeArr);

                    $oldIds = _getIdsFrist($value);
                    DB::table('items')->where('id', $value)->update(['store_ids' => $newStoreString]);
                    _trackStoreIds('test 13', $newStoreString, $value, '-', Helpers::get_store_id() . '_vendor', $oldIds);
                }
            }

            // services offered 2
            if ($request->services_2  && count($request->services_2)) {
                foreach ($request->services_2 as $key => $value) {
                    $fetchServicesRow  = DB::table('items')->where('id', $value)->first();
                    $storeArr = explode(',', $fetchServicesRow->store_ids);
                    array_push($storeArr, $store->id);
                    $newStoreString = implode(',', $storeArr);
                    $oldIds = _getIdsFrist($value);
                    DB::table('items')->where('id', $value)->update(['store_ids' => $newStoreString]);
                    _trackStoreIds('test 12', $newStoreString, $value, '-', Helpers::get_store_id() . '_vendor',  $oldIds);
                }
            }
        }elseif(isset($request->shop_categories)){ // for shop module
            $store->shop_categories =  implode(',',$request->shop_categories);
            $store->update();
        }

        // Save dedicated leads setting
        if ($store->module_id == 6) {
            $store->dedicated_leads = $request->has('dedicated_leads') ? 1 : 0;
            $store->save();
        }

        Toastr::success('Updated successfully');
        return back();
    }
    public function get_all(Request $request)
    {
        $data = Category::where('name', 'like', '%' . $request->q . '%')->module(Helpers::get_store_data()->module_id)->limit(8)->get([DB::raw('id, CONCAT(name, " (", if(position = 0, "' . translate('messages.main') . '", "' . translate('messages.sub') . '"),")") as text')]);
        if (isset($request->all)) {
            $data[] = (object)['id' => 'all', 'text' => translate('messages.all')];
        }
        return response()->json($data);
    }

    function sub_index(Request $request)
    {
        $key = explode(' ', $request['search']);
        $categories = Category::with(['parent'])
            ->whereHas('parent', function ($query) {
                $query->module(Helpers::get_store_data()->module_id);
            })
            ->where(['position' => 1])
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()->paginate(config('default_pagination'));
        return view('vendor-views.category.sub-index', compact('categories'));
    }

    // public function search(Request $request){
    //     $key = explode(' ', $request['search']);
    //     $categories=Category::where(['position'=>0])
    //     ->module(Helpers::get_store_data()->module_id)
    //     ->where(function ($q) use ($key) {
    //         foreach ($key as $value) {
    //             $q->orWhere('name', 'like', "%{$value}%");
    //         }
    //     })
    //     ->latest()
    //     ->limit(50)->get();
    //     return response()->json([
    //         'view'=>view('vendor-views.category.partials._table',compact('categories'))->render(),
    //         'count'=>$categories->count()
    //     ]);
    // }

    //    public function sub_search(Request $request){
    //        $key = explode(' ', $request['search']);
    //        $categories=Category::with(['parent'])
    //        ->where(function ($q) use ($key) {
    //            foreach ($key as $value) {
    //                $q->orWhere('name', 'like', "%{$value}%");
    //            }
    //        })
    //        ->where(['position'=>1])->limit(50)->get();
    //
    //        return response()->json([
    //            'view'=>view('vendor-views.category.partials._sub_table',compact('categories'))->render(),
    //            'count'=>$categories->count()
    //        ]);
    //    }

    public function export_categories(Request $request)
    {
        $key = explode(' ', $request['search']);
        $categories = Category::where(['position' => 0])->module(Helpers::get_store_data()->module_id)
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()->get();


        $data = [
            'data' => $categories,
            'search' => $request['search'] ?? null,
        ];
        if ($request->type == 'csv') {
            return Excel::download(new StoreCategoryExport($data), 'Categories.csv');
        }
        return Excel::download(new StoreCategoryExport($data), 'Categories.xlsx');
    }

    public function export_sub_categories(Request $request)
    {
        $key = explode(' ', $request['search']);
        $categories = Category::with(['parent'])
            ->whereHas('parent', function ($query) {
                $query->module(Helpers::get_store_data()->module_id);
            })
            ->where(['position' => 1])
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()->get();

        $data = [
            'data' => $categories,
            'search' => $request['search'] ?? null,
        ];
        if ($request->type == 'csv') {
            return Excel::download(new StoreSubCategoryExport($data), 'SubCategories.csv');
        }
        return Excel::download(new StoreSubCategoryExport($data), 'SubCategories.xlsx');
    }
}
