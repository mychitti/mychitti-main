<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Item;
use App\Models\Order;
use App\Models\Review;
use App\Models\Category;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\CentralLogics\StoreLogic;
use App\CentralLogics\CategoryLogic;
use Illuminate\Support\Facades\DB;
use App\CentralLogics\ProductLogic;
use App\Http\Controllers\Controller;
use App\Models\ServiceKeyword;
use App\Models\Store;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    public function get_stores(Request $request, $id)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        $longitude = $request->header('longitude');
        $latitude = $request->header('latitude');
        $type = $request->query('type', 'all');

        $data = ProductLogic::stores($id, $zone_id, $request['limit'], $request['offset'], $type, $longitude, $latitude);
        $data['stores'] = Helpers::store_data_formatting($data['stores'], true);
        return response()->json($data, 200);
    }
    public function fetch_services_bkp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cat_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $allcategories = [];
        array_push($allcategories, $request->cat_id);

        // Fetch child category IDs
        $ct = Category::where('parent_id', $request->cat_id)
            ->pluck('id')
            ->toArray();

        // Merge the categories
        $allcategories = array_merge($allcategories, $ct);

        // Prepare the query
        $categories = DB::table('items')->whereIn('category_id', $allcategories)->get();

        return response()->json(['status' => true, 'services' => $categories]);
    }
    public function popular_services(Request $request,  $category = null)
    {

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        $limit  = $request['limit'] ?? 10;
        $offset  = $request['offset'] ?? 1;

        $items =  DB::table('service_requests')
            ->join('items', 'service_requests.item_id', 'items.id')
            ->join('stores', function ($join) use ($zone_id) {
                $join->whereRaw('FIND_IN_SET(stores.id, items.store_ids) > 0');
                $join->whereIn('stores.zone_id',  json_decode($zone_id, true));
                $join->where(['stores.module_id' => 6, 'stores.active' => 1, 'items.status' => 1]);
            })
            ->join('categories', 'categories.id', 'items.category_id')
            ->whereNull('categories.added_by')
            ->select('items.id','items.name', 'items.image',DB::raw('COUNT(service_requests.item_id) as total_requests'))
            ->groupBy('items.id')
           ->orderBy('total_requests', 'desc')
            ->paginate($limit, ['*'], 'page', $offset);

        return response()->json($items, 200);
    }
    public function fetch_services(Request $request,  $category = null)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        $limit  = $request['limit'] ?? 10;
        $offset  = $request['offset'] ?? 1;
        $featured = $request->query('featured');
        $items = Item::withoutGlobalScopes()
            ->where('items.status', 1)

            ->join('stores', function ($join) {
                $join->whereRaw('FIND_IN_SET(stores.id, items.store_ids)');
            })

            ->whereIn('stores.zone_id', json_decode($zone_id, true))

            ->when(config('module.current_module_data'), function ($query) {
                $query->where('items.module_id', config('module.current_module_data')['id']);
            })

            ->when($category, function ($query) use ($category) {
                $query->where('items.category_id', $category);
            })

            ->when($featured, function ($query) {
                $query->featured();
            })

            ->select('items.id', 'items.name', 'items.image')
            ->groupBy('items.id')
            ->orderBy('items.id', 'desc')
            ->paginate($limit, ['*'], 'page', $offset);

        return response()->json($items, 200);
    }
    public function searchbar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $zone_id = $request->header('zoneId');
        $resultItems = '';
        $matchingProducts = Item::where('items.name', 'like', '%' . $request->keyword . '%')
            ->where('items.is_approved', 1)
            ->where('items.status', 1)
            ->when(config('module.current_module_data'), function ($query) {
                $query->where('items.module_id', config('module.current_module_data')['id']);
            })
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->select('items.name', 'items.slug', 'categories.slug as cat_slug')
            ->whereIn('stores.zone_id', json_decode($zone_id, true))
            ->get();

        // keywords =======================
        $keyword = $request->keyword;
        $query = Category::query();
        $query->where('keywords', 'LIKE', '%' . $keyword . '%');
        $keywordResults = $query->get();
        $matchingColumnsKeywords = [];
        $categoryIds = [];
        $categoryNames = [];

        foreach ($keywordResults as $cat) {
            foreach (explode(',', $cat->keywords) as $value) {
                $matchingColumnsKeywords[] = $value;
                $categoryIds[$value] = $cat->slug; // Map keyword to category ID
                $categoryNames[$value] = $cat->name; // Map keyword to category ID
            }
        }

        $pattern = '/' . preg_quote($keyword, '/') . '/i'; // i for case-insensitive
        $keywordsMatch = preg_grep($pattern, $matchingColumnsKeywords);

        foreach ($keywordsMatch as $result) {
            $catId = $categoryIds[$result] ?? 'Unknown'; // Get category ID or default to 'Unknown'
            $catName = $categoryNames[$result] ?? 'Unknown'; // Get category ID or default to 'Unknown'
            $resultItems .= '<li><a href="' . route('category.listing', [$catId, _selectedCity()]) . '">' . $result . ' - in ' . $catName . ' </a></li>';
        }
        // end keywords ====================

        $matchingCategories = Category::where('name', 'like', '%' . $request->keyword . '%')->where(['position' => 0, 'status' => 1, 'featured' => 1])
            ->when(config('module.current_module_data'), function ($query) {
                $query->module(config('module.current_module_data')['id']);
            })->get();

        $matchingStores = Store::where('name', 'like', '%' . $request->keyword . '%')->where('status', 1)->whereIn('zone_id', json_decode($zone_id, true))->when(config('module.current_module_data'), function ($query) {
            $query->module(config('module.current_module_data')['id']);
        })->get();


        foreach ($matchingProducts as $pro) {
            $resultItems .= '<li><a href="' . route('product.details', [$pro->cat_slug, $pro->slug]) . '">' . $pro->name . ' </a></li> ';
        }
        foreach ($matchingCategories as $pro) {
            $resultItems .= '<li><a href="' . route('category.listing', [$pro->slug, _selectedCity()]) . '">' . $pro->name . ' - Category </a></li>';
        }
        foreach ($matchingStores as $pro) {
            $resultItems .= '<li><a href="' . route('store.details', [_selectedCity() ,$pro->slug]) . '">' . $pro->name . ' - Store </a></li>';
        }

        if (count($matchingProducts) || count($matchingCategories) || count($matchingStores) || count($keywordsMatch)) {
            $html = '<ul class="list-unstyled mb-0">';
            $html .= $resultItems;
            $html .= '</ul>';

            return response()->json(['status' => true, 'html' => $html]);
        } else {
            $html = '<div class="p-5 fs-4">No Items Found...</div>';
            return response()->json(['status' => false, 'html' => $html]);
        }
    }
    public function keywords_searchbar(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'keyword' => 'required',
        ]);
        $keyword = $request['keyword'];
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }


        $zone_id = json_decode($request->header('zoneId'), true);
        $resultItems = [];
        $matchingProducts = DB::table('items')->where('items.name', 'like', '%' . $request->keyword . '%')
            ->where('items.is_approved', 1)
            ->where('items.status', 1)
            ->join('stores', 'items.store_id', 'stores.id')
            ->join('categories', 'categories.id', 'items.category_id')
            ->whereIn('stores.zone_id', $zone_id)
            ->where('items.module_id', 6)
            ->select('items.*')
            ->get();

        // keywords =======================
        $keyword = $request->keyword;
        $keywordsMatch = ServiceKeyword::where('keyword', 'LIKE', "%{$keyword}%")->get();

        foreach ($keywordsMatch as $result) {
            $serviceId = $result->service_id;
            $service = DB::table('items')->where('items.is_approved', 1)->where('items.module_id', 6)->where('items.id', $serviceId)->where('items.status', 1)->first();
            if ($service) {
                $r = [];
                $r['type'] = 'item';
                $r['image'] = asset('storage/app/public/product/')  . '/' . $service->image;
                $r['id'] = $serviceId;
                $r['name'] =  $result->keyword . ' - ' . $service->name;;
                $r['data'] = Item::find($serviceId);
                $resultItems[] = $r;
            }
        }
        // end keywords ====================

        $matchingCategories = Category::where('name', 'LIKE', "%{$request->keyword}%")->where('module_id', 6)
            ->where('position', 0)
            ->where('status', 1)
            ->get();

        $matchingStores = Store::where('name', 'like', '%' . $request->keyword . '%')->where('module_id', 6)->where('status', 1)->whereIn('zone_id', $zone_id)->get();


        foreach ($matchingProducts as $pro) {
            $r = [];
            $r['type'] = 'item';
            $r['image'] = asset('storage/app/public/product/')  . '/' . $service->image;
            $r['id'] = $serviceId;
            $r['name'] =  $result->keyword . ' - ' . $service->name;;
            $r['data'] = Item::find($serviceId);
            $resultItems[] = $r;
        }
        foreach ($matchingCategories as $pro) {
            $r = [];
            $r['type'] = 'category';
            $r['id'] = $pro->id;
            $r['image'] = asset('storage/app/public/category/')  . '/' . $pro->image;
            $r['name'] =  $pro->name;
            $r['data'] = Category::find($pro->id);
            $resultItems[] = $r;
        }
        foreach ($matchingStores as $pro) {
            $r = [];
            $r['type'] = 'store';
            $r['id'] = $pro->id;
            $r['image'] = asset('storage/app/public/store/')  . '/' . $pro->logo;
            $r['name'] =  $pro->name . ' - Store';
            $r['data'] = Store::find($pro->id);
            $resultItems[] = $r;
        }

        $total_size = count($matchingStores) + count($matchingCategories) + count($matchingProducts) + count($keywordsMatch);
        return response()->json(['total_size' => $total_size, 'limit' => 500, 'offset' => 1, 'products' => $resultItems]);
    }
    public function keywords_searchbar_fhjd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'required',
        ]);
        $keyword = $request['keyword'];
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $zone_id = json_decode($request->header('zoneId'), true);
        $resultItems = [];

        $matchingProducts = DB::table('items')->where('items.name', 'like', '%' . $request->keyword . '%')
            ->where('items.is_approved', 1)
            ->where('items.status', 1)
            ->join('stores', 'items.store_id', 'stores.id')
            ->whereIn('stores.zone_id', $zone_id)
            ->where('items.module_id', 6)
            ->select('items.*')
            ->get();

        // keywords =======================
        $keyword = $request->keyword;
        $keywordsMatch = DB::table('service_keywords')
            ->join('items', 'items.id', 'service_keywords.service_id')
            ->where('service_keywords.keyword', 'LIKE', "%{$keyword}%")
            ->where('items.is_approved', 1)
            ->where('items.status', 1)
            ->where('items.module_id', 6)
            ->join('stores', 'items.store_id', 'stores.id')
            ->whereIn('stores.zone_id', $zone_id)
            ->get();

        // categories =======================
        $matchingCategories = DB::table('categories')->where('name', 'like', '%' . $request->keyword . '%')
            ->where(['position' => 0, 'status' => 1])->where('module_id', 6)
            ->get();

        // stores =======================
        $matchingStores = DB::table('stores')->where('name', 'like', '%' . $request->keyword . '%')
            ->where('status', 1)
            ->whereIn('zone_id', $zone_id)
            ->where('module_id', 6)
            ->get();


        foreach ($keywordsMatch as $result) {
            $serviceId = $result->service_id;
            $service = DB::table('items')->where('items.is_approved', 1)->where('id', $serviceId)->where('status', 1)->first();
            if ($service) {
                $r = [];
                $r['type'] = 'item';
                $r['image'] = asset('storage/app/public/product/')  . '/' . $service->image;
                $r['id'] = $serviceId;
                $r['name'] =  $result->keyword . ' - ' . $service->name;;
                $r['data'] = Item::find($serviceId);
                $resultItems[] = $r;
            }
        }

        foreach ($matchingProducts as $pro) {
            $r = [];
            $r['type'] = 'item';
            $r['id'] = $pro->id;
            $r['name'] =  $pro->name;
            $r['image'] = asset('storage/app/public/product/')  . '/' . $pro->image;
            $r['data'] = Item::find($pro->id);
            $resultItems[] = $r;
        }
        foreach ($matchingCategories as $pro) {
            $r = [];
            $r['type'] = 'category';
            $r['id'] = $pro->id;
            $r['image'] = asset('storage/app/public/category/')  . '/' . $pro->image;
            $r['name'] =  $pro->name;
            $r['data'] = Category::find($pro->id);
            $resultItems[] = $r;
        }
        foreach ($matchingStores as $pro) {
            $r = [];
            $r['type'] = 'store';
            $r['id'] = $pro->id;
            $r['image'] = asset('storage/app/public/store/')  . '/' . $pro->logo;
            $r['name'] =  $pro->name . ' - Store';
            $r['data'] = Store::find($pro->id);
            $resultItems[] = $r;
        }
        $total_size = count($matchingStores) + count($matchingCategories) + count($matchingProducts);
        return response()->json(['total_size' => $total_size, 'limit' => 500, 'offset' => 1, 'products' => $resultItems]);
    }

    public function keywords_searchbar_old(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required',
        ]);
        $keyword = $request['q'];
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $zone_id = $request->header('zoneId');
        $resultItems = [];
        $matchingProducts = Item::where('items.name', 'like', '%' . $request->keyword . '%')
            ->where('items.is_approved', 1)
            ->where('items.status', 1)
            ->when(config('module.current_module_data'), function ($query) {
                $query->where('items.module_id', config('module.current_module_data')['id']);
            })
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->whereIn('stores.zone_id', json_decode($zone_id, true))
            ->get();

        // echo 7; die;  
        // keywords ======================= 
        $keyword = $request->keyword;
        $query = ServiceKeyword::where('keyword', 'LIKE', '%' . $keyword . '%');
        $keywordsMatch = $query->get();

        foreach ($keywordsMatch as $result) {
            $serviceId = $result->service_id;
            $serviceName = DB::table('items')->where('items.is_approved', 1)->where('id', $serviceId)->where('module_id', config('module.current_module_data')['id'])->where('status', 1)->first();

            if ($serviceName) {
                $r = [];
                $r['type'] = 'keyword';
                $r['id'] = $serviceId;
                $r['name'] =  $result->keyword . ' - ' . $serviceName->name;;
                $resultItems[] = $r;
            }
        }
        // end keywords ====================

        $matchingCategories = Category::where('name', 'like', '%' . $request->keyword . '%')->where(['position' => 0, 'status' => 1, 'featured' => 1])
            ->when(config('module.current_module_data'), function ($query) {
                $query->module(config('module.current_module_data')['id']);
            })->get();

        $matchingStores = Store::where('name', 'like', '%' . $request->keyword . '%')->where('status', 1)->whereIn('zone_id', json_decode($zone_id, true))->when(config('module.current_module_data'), function ($query) {
            $query->module(config('module.current_module_data')['id']);
        })->get();


        foreach ($matchingProducts as $pro) {
            $r = [];
            $r['type'] = 'item';
            $r['id'] = $pro->id;
            $r['name'] =  $pro->name;
            $resultItems[] = $r;
        }
        foreach ($matchingCategories as $pro) {
            $r = [];
            $r['type'] = 'category';
            $r['id'] = $pro->id;
            $r['name'] =  $pro->name;
        }
        foreach ($matchingStores as $pro) {
            $r = [];
            $r['type'] = 'store';
            $r['id'] = $pro->id;
            $r['name'] =  $pro->name . ' - Store';
        }


        return response()->json(['status' => true, 'data' => $resultItems]);
    }
    public function get_latest_products(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required',
            'category_id' => 'required',
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        $moduleId = $request->header('moduleId');
        $type = $request->query('type', 'all');
        $product_id = $request->query('product_id') ?? null;
        $min = $request->query('min_price');
        $max = $request->query('max_price');
        // if ($moduleId == 5) {
        $items = ProductLogic::get_latest_products($zone_id, $request['limit'], $request['offset'], $request['store_id'], $request['category_id'], $type, $min, $max, $product_id, $request->header('moduleId'));

        $items['products'] = Helpers::product_data_formatting($items['products'], true, false, app()->getLocale());
        $items['categories'] = $items['categories'];
        // } else {

        //     if (isset($request['store_id']) && $request['store_id']) {
        //         $services_offered = Store::find($request['store_id']);
        //         $srevice_1 = explode(',', $services_offered->services_1);
        //         $srevice_2 = explode(',', $services_offered->services_2);
        //         $new_arr = array_merge($srevice_1, $srevice_2);

        //         $items['products'] = Item::whereExists(function ($query) use ($zone_id, $moduleId) {
        //             $query->select(DB::raw(1))
        //                 ->from('stores')
        //                 ->whereIn('zone_id', json_decode($zone_id, true))
        //                 ->where('module_id', $moduleId)
        //                 ->where('active', 1);
        //         })
        //             ->whereIn('id', $new_arr)
        //             ->get();
        //     } else {
        //         $items['products'] = Item::whereExists(function ($query) use ($zone_id, $moduleId) {
        //             $query->select(DB::raw(1))
        //                 ->from('stores')
        //                 ->whereIn('zone_id', json_decode($zone_id, true))
        //                 ->where('module_id', $moduleId)
        //                 ->where('active', 1)
        //                 ->whereRaw('FIND_IN_SET(stores.id, items.store_ids) > 0');
        //         })
        //             ->get();
        //     }


        //     $cat_ids = [];
        //     foreach ($items['products'] as $key => $value) {
        //         array_push($cat_ids, $value['category_id']);
        //     }
        //     $items['total_size'] = count($items['products']);
        //     $items['limit'] =  $request->limit;
        //     $items['offset'] = $request->offset;
        //     $items['categories'] = Category::whereIn('id', $cat_ids)->get();
        // }


        return response()->json($items, 200);
    }

    public function get_new_products(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        $type = $request->query('type', 'all');
        $product_id = $request->query('product_id') ?? null;
        $min = $request->query('min_price');
        $max = $request->query('max_price');
        $limit = isset($request['limit']) ? $request['limit'] : 50;
        $offset = isset($request['offset']) ? $request['offset'] : 1;

        $items = ProductLogic::get_new_products($zone_id, $type, $min, $max, $product_id, $limit, $offset);
        $items['categories'] = $items['categories'];
        $items['products'] = Helpers::product_data_formatting($items['products'], true, false, app()->getLocale());
        return response()->json($items, 200);
    }

    public function get_searched_products(Request $request)
    {

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $zone_id = $request->header('zoneId');

        $key = explode(' ', $request['name']);

        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;
        $category_ids = $request['category_ids'] ? (is_array($request['category_ids']) ? $request['category_ids'] : json_decode($request['category_ids'])) : '';
        $filter = $request['filter'] ? (is_array($request['filter']) ? $request['filter'] : str_getcsv(trim($request['filter'], "[]"), ',')) : '';
        $type = $request->query('type', 'all');
        $min = $request->query('min_price');
        $max = $request->query('max_price');
        $rating_count = $request->query('rating_count');

        if (config('module.current_module_data')['id'] == 5) {


            $items = Item::active()->type($type)
                ->with('store', function ($query) {
                    $query->withCount(['campaigns' => function ($query) {
                        $query->Running();
                    }]);
                })
                ->when($request->category_id, function ($query) use ($request) {
                    $query->whereHas('category', function ($q) use ($request) {
                        return $q->whereId($request->category_id)->orWhere('parent_id', $request->category_id);
                    });
                })
                ->when($category_ids, function ($query) use ($category_ids) {
                    $query->whereHas('category', function ($q) use ($category_ids) {
                        return $q->whereIn('id', $category_ids)->orWhereIn('parent_id', $category_ids);
                    });
                })
                ->when($request->store_id, function ($query) use ($request) {
                    return $query->where('store_id', $request->store_id);
                })
                ->whereHas('module.zones', function ($query) use ($zone_id) {
                    $query->whereIn('zones.id', json_decode($zone_id, true));
                })
                ->whereHas('store', function ($query) use ($zone_id) {
                    $query->when(config('module.current_module_data'), function ($query) {
                        $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules', function ($query) {
                            $query->where('modules.id', config('module.current_module_data')['id']);
                        });
                    })->whereIn('zone_id', json_decode($zone_id, true));
                })
                ->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                    $q->orWhereHas('translations', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('value', 'like', "%{$value}%");
                            };
                        });
                    });
                    $q->orWhereHas('tags', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('tag', 'like', "%{$value}%");
                            };
                        });
                    });
                    $q->orWhereHas('category.parent', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('name', 'like', "%{$value}%");
                            };
                        });
                    });
                    $q->orWhereHas('category', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('name', 'like', "%{$value}%");
                            };
                        });
                    });
                })
                ->when($rating_count, function ($query) use ($rating_count) {
                    $query->where('avg_rating', '>=', $rating_count);
                })
                ->when($min && $max, function ($query) use ($min, $max) {
                    $query->whereBetween('price', [$min, $max]);
                })
                ->orderByRaw("FIELD(name, ?) DESC", [$request['name']])
                ->when($filter && in_array('top_rated', $filter), function ($qurey) {
                    $qurey->withCount('reviews')->orderBy('reviews_count', 'desc');
                })
                ->when($filter && in_array('popular', $filter), function ($qurey) {
                    $qurey->popular();
                })
                ->when($filter && in_array('discounted', $filter), function ($qurey) {
                    $qurey->Discounted()->orderBy('discount', 'desc');
                })
                ->when($filter && in_array('high', $filter), function ($qurey) {
                    $qurey->orderBy('price', 'desc');
                })
                ->when($filter && in_array('low', $filter), function ($qurey) {
                    $qurey->orderBy('price', 'asc');
                })
                ->paginate($limit, ['*'], 'page', $offset);


            $item_categories = Item::active()->type($type)
                ->with('store', function ($query) {
                    $query->withCount(['campaigns' => function ($query) {
                        $query->Running();
                    }]);
                })
                ->when($request->category_id, function ($query) use ($request) {
                    $query->whereHas('category', function ($q) use ($request) {
                        return $q->whereId($request->category_id)->orWhere('parent_id', $request->category_id);
                    });
                })
                ->when($category_ids, function ($query) use ($category_ids) {
                    $query->whereHas('category', function ($q) use ($category_ids) {
                        return $q->whereIn('id', $category_ids)->orWhereIn('parent_id', $category_ids);
                    });
                })
                ->when($request->store_id, function ($query) use ($request) {
                    return $query->where('store_id', $request->store_id);
                })
                ->whereHas('module.zones', function ($query) use ($zone_id) {
                    $query->whereIn('zones.id', json_decode($zone_id, true));
                })
                ->whereHas('store', function ($query) use ($zone_id) {
                    $query->when(config('module.current_module_data'), function ($query) {
                        $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules', function ($query) {
                            $query->where('modules.id', config('module.current_module_data')['id']);
                        });
                    })->whereIn('zone_id', json_decode($zone_id, true));
                })
                ->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                    $q->orWhereHas('translations', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('value', 'like', "%{$value}%");
                            };
                        });
                    });
                    $q->orWhereHas('tags', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('tag', 'like', "%{$value}%");
                            };
                        });
                    });
                    $q->orWhereHas('category.parent', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('name', 'like', "%{$value}%");
                            };
                        });
                    });
                    $q->orWhereHas('category', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('name', 'like', "%{$value}%");
                            };
                        });
                    });
                })
                ->when($rating_count, function ($query) use ($rating_count) {
                    $query->where('avg_rating', '>=', $rating_count);
                })
                ->when($min && $max, function ($query) use ($min, $max) {
                    $query->whereBetween('price', [$min, $max]);
                })
                ->pluck('category_id')->toArray();

            $item_categories = array_unique($item_categories);

            $categories = Category::withCount(['products', 'childes'])->with(['childes' => function ($query) {
                $query->withCount(['products', 'childes']);
            }])
                ->where(['position' => 0, 'status' => 1])
                ->when(config('module.current_module_data'), function ($query) {
                    $query->module(config('module.current_module_data')['id']);
                })
                ->whereIn('id', $item_categories)
                ->orderBy('priority', 'desc')->get();

            $data =  [
                'total_size' => $items->total(),
                'limit' => $limit,
                'offset' => $offset,
                'products' => $items->items(),
                'categories' => $categories
            ];
        } elseif (config('module.current_module_data')['id'] == 6) {
            // First part - Basic query with essential joins

            $items = Item::withoutGlobalScope('store')
                // ->with(['stores' => function ($query) {
                //     $query->withCount(['campaigns' => function ($query) {  
                //         $query->Running();
                //     }]);
                // }])
                ->when($request->store_id, function ($query) use ($request) {
                    return $query->whereRaw("FIND_IN_SET(?, store_ids)", [$request->store_id]);
                })
                ->when($request->category_id, function ($query) use ($request) {
                    $query->whereHas('category', function ($q) use ($request) {
                        return $q->whereId($request->category_id)
                            ->orWhere('parent_id', $request->category_id);
                    });
                })
                ->when($category_ids, function ($query) use ($category_ids) {
                    $query->whereHas('category', function ($q) use ($category_ids) {
                        return $q->whereIn('id', $category_ids)
                            ->orWhereIn('parent_id', $category_ids);
                    });
                })
                ->whereHas('module.zones', function ($query) use ($zone_id) {
                    $query->whereIn('zones.id', json_decode($zone_id, true));
                })
                // Search conditions
                ->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                    // Add other search conditions as needed
                })
                ->when($rating_count, function ($query) use ($rating_count) {
                    $query->where('avg_rating', '>=', $rating_count);
                })
                ->when($min && $max, function ($query) use ($min, $max) {
                    $query->whereBetween('price', [$min, $max]);
                })
                // Sorting
                ->when($filter && in_array('top_rated', $filter), function ($query) {
                    $query->withCount('reviews')->orderBy('reviews_count', 'desc');
                })
                ->when($filter && in_array('popular', $filter), function ($query) {
                    $query->popular();
                })
                ->when($filter && in_array('discounted', $filter), function ($query) {
                    $query->Discounted()->orderBy('discount', 'desc');
                })
                ->paginate($limit, ['*'], 'page', $offset);

            $item_categories = Item::active()->type($type)
                ->with('store', function ($query) {
                    $query->withCount(['campaigns' => function ($query) {
                        $query->Running();
                    }]);
                })
                ->when($request->category_id, function ($query) use ($request) {
                    $query->whereHas('category', function ($q) use ($request) {
                        return $q->whereId($request->category_id)->orWhere('parent_id', $request->category_id);
                    });
                })
                ->when($category_ids, function ($query) use ($category_ids) {
                    $query->whereHas('category', function ($q) use ($category_ids) {
                        return $q->whereIn('id', $category_ids)->orWhereIn('parent_id', $category_ids);
                    });
                })
                ->when($request->store_id, function ($query) use ($request) {
                    return $query->where('store_id', $request->store_id);
                })
                ->whereHas('module.zones', function ($query) use ($zone_id) {
                    $query->whereIn('zones.id', json_decode($zone_id, true));
                })
                ->whereHas('store', function ($query) use ($zone_id) {
                    $query->when(config('module.current_module_data'), function ($query) {
                        $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules', function ($query) {
                            $query->where('modules.id', config('module.current_module_data')['id']);
                        });
                    })->whereIn('zone_id', json_decode($zone_id, true));
                })
                ->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                    $q->orWhereHas('translations', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('value', 'like', "%{$value}%");
                            };
                        });
                    });
                    $q->orWhereHas('tags', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('tag', 'like', "%{$value}%");
                            };
                        });
                    });
                    $q->orWhereHas('category.parent', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('name', 'like', "%{$value}%");
                            };
                        });
                    });
                    $q->orWhereHas('category', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('name', 'like', "%{$value}%");
                            };
                        });
                    });
                })
                ->when($rating_count, function ($query) use ($rating_count) {
                    $query->where('avg_rating', '>=', $rating_count);
                })
                ->when($min && $max, function ($query) use ($min, $max) {
                    $query->whereBetween('price', [$min, $max]);
                })
                ->pluck('category_id')->toArray();

            $item_categories = array_unique($item_categories);

            $categories = Category::withCount(['products', 'childes'])->with(['childes' => function ($query) {
                $query->withCount(['products', 'childes']);
            }])
                ->where(['position' => 0, 'status' => 1])
                ->when(config('module.current_module_data'), function ($query) {
                    $query->module(config('module.current_module_data')['id']);
                })
                ->whereIn('id', $item_categories)
                ->orderBy('priority', 'desc')->get();

            $data =  [
                'total_size' => $items->total(),
                'limit' => $limit,
                'offset' => $offset,
                'products' => $items->items(),
                'categories' => $categories
            ];
        }
        // die;
        // echo 433;
        $data['products'] = Helpers::product_data_formatting($data['products'], true, false, app()->getLocale());
        return response()->json($data, 200);
    }

    public function get_searched_products_suggestion(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $zone_id = $request->header('zoneId');

        $key = explode(' ', $request['name']);

        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        $type = $request->query('type', 'all');

        $items = Item::active()->type($type)

            ->when($request->category_id, function ($query) use ($request) {
                $query->whereHas('category', function ($q) use ($request) {
                    return $q->whereId($request->category_id)->orWhere('parent_id', $request->category_id);
                });
            })
            ->when($request->store_id, function ($query) use ($request) {
                return $query->where('store_id', $request->store_id);
            })
            ->whereHas('module.zones', function ($query) use ($zone_id) {
                $query->whereIn('zones.id', json_decode($zone_id, true));
            })
            ->whereHas('store', function ($query) use ($zone_id) {
                $query->when(config('module.current_module_data'), function ($query) {
                    $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules', function ($query) {
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                })->whereIn('zone_id', json_decode($zone_id, true));
            })
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
                $q->orWhereHas('translations', function ($query) use ($key) {
                    $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->where('value', 'like', "%{$value}%");
                        };
                    });
                });
                $q->orWhereHas('tags', function ($query) use ($key) {
                    $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->where('tag', 'like', "%{$value}%");
                        };
                    });
                });
            })->select(['name', 'image'])

            ->paginate($limit, ['*'], 'page', $offset);

        $data =  [
            'total_size' => $items->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $items->items()
        ];

        return response()->json($data, 200);
    }

    public function get_popular_products(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $type = $request->query('type', 'all');

        $zone_id = $request->header('zoneId');
        $items = ProductLogic::popular_products($zone_id, $request['limit'], $request['offset'], $type);
        // prx($items);
        $items['products'] = Helpers::product_data_formatting($items['products'], true, false, app()->getLocale());
        return response()->json($items, 200);
    }

    public function get_most_reviewed_products(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $type = $request->query('type', 'all');

        $zone_id = $request->header('zoneId');
        $items = ProductLogic::most_reviewed_products($zone_id, $request['limit'], $request['offset'], $type);
        $items['categories'] = $items['categories'];
        $items['products'] = Helpers::product_data_formatting($items['products'], true, false, app()->getLocale());
        return response()->json($items, 200);
    }

    public function get_discounted_products(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $type = $request->query('type', 'all');
        $category_ids = $request->query('category_ids', '');

        $zone_id = $request->header('zoneId');
        $items = ProductLogic::discounted_products($zone_id, $request['limit'], $request['offset'], $type, $category_ids);
        $items['products'] = Helpers::product_data_formatting($items['products'], true, false, app()->getLocale());
        return response()->json($items, 200);
    }

    public function get_cart_suggest_products(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');

        $type = $request->query('type', 'all');
        $recommended = $request->query('recommended');

        $items = ProductLogic::cart_suggest_products($zone_id, $request['store_id'], $request['limit'], $request['offset'], $type, $recommended);
        $items['items'] = Helpers::product_data_formatting($items['items'], true, false, app()->getLocale());
        return response()->json($items, 200);
    }

    public function get_product($id)
    {
        try {
            $item = Item::withCount('whislists')->with(['tags', 'reviews', 'reviews.customer'])->active()
                ->when(config('module.current_module_data'), function ($query) {
                    $query->module(config('module.current_module_data')['id']);
                })
                ->when(is_numeric($id), function ($qurey) use ($id) {
                    $qurey->where('id', $id);
                })
                ->when(!is_numeric($id), function ($qurey) use ($id) {
                    $qurey->where('slug', $id);
                })
                ->first();
            $store = StoreLogic::get_store_details($item->store_id);
            if ($store) {
                $category_ids = DB::table('items')
                    ->join('categories', 'items.category_id', '=', 'categories.id')
                    ->selectRaw('categories.position as positions, IF((categories.position = "0"), categories.id, categories.parent_id) as categories')
                    ->where('items.store_id', $item->store_id)
                    ->where('categories.status', 1)
                    ->groupBy('categories', 'positions')
                    ->get();

                $store = Helpers::store_data_formatting($store);
                $store['category_ids'] = array_map('intval', $category_ids->pluck('categories')->toArray());
                $store['category_details'] = Category::whereIn('id', $store['category_ids'])->get();
                $store['price_range']  = Item::withoutGlobalScopes()->where('store_id', $item->store_id)
                    ->select(DB::raw('MIN(price) AS min_price, MAX(price) AS max_price'))
                    ->get(['min_price', 'max_price']);
            }
            $item = Helpers::product_data_formatting($item, false, false, app()->getLocale());
            $item['store_details'] = $store;
            return response()->json($item, 200);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => ['code' => 'product-001', 'message' => translate('messages.not_found')]
            ], 404);
        }
    }

    public function get_related_products(Request $request, $id)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        if (Item::find($id)) {
            $items = ProductLogic::get_related_products($zone_id, $id);
            $items = Helpers::product_data_formatting($items, true, false, app()->getLocale());
            return response()->json($items, 200);
        }
        return response()->json([
            'errors' => ['code' => 'product-001', 'message' => translate('messages.not_found')]
        ], 404);
    }
    public function get_related_store_products(Request $request, $id)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        if (Item::find($id)) {
            $items = ProductLogic::get_related_store_products($zone_id, $id);
            $items = Helpers::product_data_formatting($items, true, false, app()->getLocale());
            return response()->json($items, 200);
        }
        return response()->json([
            'errors' => ['code' => 'product-001', 'message' => translate('messages.not_found')]
        ], 404);
    }

    public function get_recommended(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $type = $request->query('type', 'all');
        $filter = $request->query('filter', 'all');

        $zone_id = $request->header('zoneId');
        $items = ProductLogic::recommended_items($zone_id, $request->store_id, $request['limit'], $request['offset'], $type, $filter);
        $items['items'] = Helpers::product_data_formatting($items['items'], true, false, app()->getLocale());
        return response()->json($items, 200);
    }

    public function get_set_menus()
    {
        try {
            $items = Helpers::product_data_formatting(Item::active()->with(['rating'])->where(['set_menu' => 1, 'status' => 1])->get(), true, false, app()->getLocale());
            return response()->json($items, 200);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => ['code' => 'product-001', 'message' => 'Set menu not found!']
            ], 404);
        }
    }

    public function get_product_reviews(Request $request, $item_id)
    {
        if (isset($request['limit']) && ($request['limit'] != null) && isset($request['offset']) && ($request['offset'] != null)) {

            $reviews = Review::with(['customer', 'item'])->where(['item_id' => $item_id])->active()->paginate($request['limit'], ['*'], 'page', $request['offset']);
            $total = $reviews->total();
        } else {

            $reviews = Review::with(['customer', 'item'])->where(['item_id' => $item_id])->active()->get();
            $total = $reviews->count();
        }

        $storage = [];
        foreach ($reviews as $temp) {
            $temp['attachment'] = json_decode($temp['attachment']);
            $temp['item_name'] = null;
            if ($temp->item) {
                $temp['item_name'] = $temp->item->name;
                if (count($temp->item->translations) > 0) {
                    $translate = array_column($temp->item->translations->toArray(), 'value', 'key');
                    $temp['item_name'] = $translate['name'];
                }
            }

            unset($temp['item']);
            array_push($storage, $temp);
        }

        $data =  [
            'total_size' => $total,
            'limit' => $request['limit'],
            'offset' => $request['offset'],
            'reviews' => $storage
        ];

        return response()->json($data, 200);
    }

    public function get_product_rating($id)
    {
        try {
            $item = Item::find($id);
            $overallRating = ProductLogic::get_overall_rating($item->reviews);
            return response()->json(floatval($overallRating[0]), 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e], 403);
        }
    }

    public function submit_product_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required',
            'order_id' => 'required',
            'comment' => 'required',
            'rating' => 'required|numeric|max:5',
        ]);

        $order = Order::find($request->order_id);
        if (isset($order) == false) {
            $validator->errors()->add('order_id', translate('messages.order_data_not_found'));
        }

        $item = Item::find($request->item_id);
        if (isset($order) == false) {
            $validator->errors()->add('item_id', translate('messages.item_not_found'));
        }

        $multi_review = Review::where(['item_id' => $request->item_id, 'user_id' => $request->user()->id, 'order_id' => $request->order_id])->first();
        if (isset($multi_review)) {
            return response()->json([
                'errors' => [
                    ['code' => 'review', 'message' => translate('messages.already_submitted')]
                ]
            ], 403);
        } else {
            $review = new Review;
        }

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $image_array = [];
        if (!empty($request->file('attachment'))) {
            foreach ($request->file('attachment') as $image) {
                if ($image != null) {
                    if (!Storage::disk('public')->exists('review')) {
                        Storage::disk('public')->makeDirectory('review');
                    }
                    array_push($image_array, Storage::disk('public')->put('review', $image));
                }
            }
        }

        $order?->OrderReference?->update([
            'is_reviewed' => 1
        ]);

        $review->user_id = $request->user()->id;
        $review->item_id = $request->item_id;
        $review->order_id = $request->order_id;
        $review->module_id = $order->module_id;
        $review->comment = $request->comment;
        $review->rating = $request->rating;
        $review->attachment = json_encode($image_array);
        $review->save();

        if ($item->store) {
            $store_rating = StoreLogic::update_store_rating($item->store->rating, (int)$request->rating);
            $item->store->rating = $store_rating;
            $item->store->save();
        }

        $item->rating = ProductLogic::update_rating($item->rating, (int)$request->rating);
        $item->avg_rating = ProductLogic::get_avg_rating(json_decode($item->rating, true));
        $item->save();
        $item->increment('rating_count');

        return response()->json(['message' => translate('messages.review_submited_successfully')], 200);
    }

    public function item_or_store_search(Request $request)
    {

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        if (!$request->hasHeader('longitude') || !$request->hasHeader('latitude')) {
            $errors = [];
            array_push($errors, ['code' => 'longitude-latitude', 'message' => translate('messages.longitude-latitude_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        $zone_id = $request->header('zoneId');
        $longitude = $request->header('longitude');
        $latitude = $request->header('latitude');

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $key = explode(' ', $request->name);

        $items = Item::active()->whereHas('store', function ($query) use ($zone_id) {
            $query->when(config('module.current_module_data'), function ($query) {
                $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules', function ($query) {
                    $query->where('modules.id', config('module.current_module_data')['id']);
                });
            })->whereIn('zone_id', json_decode($zone_id, true));
        })
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orwhere('name', 'like', "%{$value}%")->orWhere('description', 'like', "%{$value}%");
                }
                $q->orWhereHas('translations', function ($query) use ($key) {
                    $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->where('value', 'like', "%{$value}%");
                        };
                    });
                });
                $q->orWhereHas('tags', function ($query) use ($key) {
                    $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->where('tag', 'like', "%{$value}%");
                        };
                    });
                });
                $q->orWhereHas('category.parent', function ($query) use ($key) {
                    $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->where('name', 'like', "%{$value}%");
                        };
                    });
                });
                $q->orWhereHas('category', function ($query) use ($key) {
                    $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->where('name', 'like', "%{$value}%");
                        };
                    });
                });
            })
            ->limit(50)
            ->get(['id', 'name', 'image']);

        $stores = Store::whereHas('zone.modules', function ($query) {
            $query->where('modules.id', config('module.current_module_data')['id']);
        })->withOpen($longitude ?? 0, $latitude ?? 0)->with(['discount' => function ($q) {
            return $q->validate();
        }])->weekday()->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('name', 'like', "%{$value}%");
            }
        })
            ->when(config('module.current_module_data'), function ($query) use ($zone_id) {
                $query->module(config('module.current_module_data')['id']);
                if (!config('module.current_module_data')['all_zone_service']) {
                    $query->whereIn('zone_id', json_decode($zone_id, true));
                }
            })
            ->active()
            ->limit(50)
            ->select(['id', 'name', 'logo'])
            ->get();

        return [
            'items' => $items,
            'stores' => $stores
        ];
    }

    public function get_store_condition_products(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'store_id' => 'required',
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $zone_id = $request->header('zoneId');

        $type = $request->query('type', 'all');
        $limit = $request['limit'];
        $offset = $request['offset'];

        $paginator = Item::whereHas('module.zones', function ($query) use ($zone_id) {
            $query->whereIn('zones.id', json_decode($zone_id, true));
        })
            ->whereHas('store', function ($query) use ($zone_id) {
                $query->whereIn('zone_id', json_decode($zone_id, true))->whereHas('zone.modules', function ($query) {
                    $query->when(config('module.current_module_data'), function ($query) {
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                });
            })
            ->whereHas('pharmacy_item_details', function ($q) {
                return $q->whereNotNull('common_condition_id');
            })
            ->when(is_numeric($request->store_id), function ($qurey) use ($request) {
                $qurey->where('store_id', $request->store_id);
            })
            ->when(!is_numeric($request->store_id), function ($query) use ($request) {
                $query->whereHas('store', function ($q) use ($request) {
                    $q->where('slug', $request->store_id);
                });
            })
            ->active()->type($type)->latest()->paginate($limit, ['*'], 'page', $offset);
        $data = [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator->items()
        ];
        $data['products'] = Helpers::product_data_formatting($data['products'], true, false, app()->getLocale());
        return response()->json($data, 200);
    }

    public function get_popular_basic_products(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        $type = $request->query('type', 'all');
        $product_id = $request->query('product_id') ?? null;
        $min = $request->query('min_price');
        $max = $request->query('max_price');
        $limit = $request['limit'] ?? 25;
        $offset = $request['offset'] ?? 1;

        $items = ProductLogic::get_popular_basic_products($zone_id, $limit, $offset, $type, $request['store_id'], $request['category_id'], $min, $max, $product_id);
        $items['categories'] = $items['categories'];
        $items['products'] = Helpers::product_data_formatting($items['products'], true, false, app()->getLocale());
        return response()->json($items, 200);
    }

    public function get_products(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]];
            return response()->json(['errors' => $errors], 403);
        }

        $data_type = $request->query('data_type', 'all');

        $zone_id = $request->header('zoneId');
        $type = $request->query('type', 'all');
        $filter = $request->query('filter', '');
        $filter = $filter ? (is_array($filter) ? $filter : str_getcsv(trim($filter, "[]"), ',')) : '';
        $category_ids = $request->query('category_ids', '');

        // Common parameters for all product types
        $limit = $request->query('limit', 10);
        $offset = $request->query('offset', 1);
        $min_price = $request->query('min_price');
        $max_price = $request->query('max_price');
        $rating_count = $request->query('rating_count');
        $product_id = $request->query('product_id');

        switch ($data_type) {
            case 'searched':
                return $this->get_searched_products($request);
                break;
            case 'discounted':
                $items = ProductLogic::discounted_products($zone_id, $limit, $offset, $type, $category_ids, $filter, $min_price, $max_price, $rating_count);
                break;
            case 'new':
                $items = ProductLogic::get_new_products($zone_id, $type, $min_price, $max_price, $product_id, $limit, $offset, $filter, $rating_count);
                break;
            case 'category':
                $validator = Validator::make($request->all(), [
                    'category_ids' => 'required',
                ]);

                if ($validator->fails()) {
                    return response()->json(['errors' => Helpers::error_processor($validator)], 403);
                }

                $items = CategoryLogic::category_products($category_ids, $zone_id, $limit, $offset, $type, $filter, $min_price, $max_price, $rating_count);
                break;
            default:
                $items =  [
                    'total_size' => 0,
                    'limit' => $limit,
                    'offset' => $offset,
                    'products' => [],
                    'categories' => [],
                ];
        }

        $items['products'] = Helpers::product_data_formatting($items['products'], true, false, app()->getLocale());
        return response()->json($items, 200);
    }
}
