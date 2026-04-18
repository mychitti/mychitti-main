<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\CentralLogics\StoreLogic;
use App\CentralLogics\CategoryLogic;
use App\Http\Controllers\Controller;
use App\Models\AcceptedServiceRequest;
use App\Models\Category;
use App\Models\Item; 
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Review;
use App\Models\StoreGallery;
use App\Models\StoreReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    public function get_stores(Request $request, $filter_data = "all")
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $type = $request->query('type', 'all');
        $store_type = $request->query('store_type', 'all');
        $zone_id = $request->header('zoneId');
        $longitude = (float)$request->header('longitude');
        $latitude = (float)$request->header('latitude');
        $stores = StoreLogic::get_stores($zone_id, $filter_data, $type, $store_type, $request['limit'], $request['offset'], $request->query('featured'), $longitude, $latitude);
        $stores['stores'] = Helpers::store_data_formatting($stores['stores'], true);

        return response()->json($stores, 200);
    }

    public function get_nearby_stores(Request $request)
    {
        $radius = 10;
        $limit = $request->query('limit', 10);
        $offset = $request->query('offset', 1);

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        if (!$request->hasHeader('latitude')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.latitude_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        if (!$request->hasHeader('longitude')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.longitude_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        $userLat = (float)$request->header('latitude');
        $userLng = (float)$request->header('longitude');

        $zoneIds = json_decode($zone_id, true);

        // Active subscribed store IDs
        $subscribedStoreIds = DB::table('stores as s')
            ->join('vendor_subscriptions as vs', 'vs.vendor_id', '=', 's.id')
            ->where('vs.plan_expiry', '>', now())
            ->distinct()
            ->pluck('s.id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        // Prevent SQL error if empty
        $subscribedIdsSql = count($subscribedStoreIds)
            ? implode(',', $subscribedStoreIds)
            : '0';

        // Distance + subscription flag
        $distanceSql = "(6371 * acos(
        cos(radians(?)) *
        cos(radians(stores.latitude)) *
        cos(radians(stores.longitude) - radians(?)) +
        sin(radians(?)) *
        sin(radians(stores.latitude))
         ))";

        $stores = Store::select('stores.*')
            ->leftJoin('categories as c1', 'c1.id', '=', 'stores.category_1')
            ->leftJoin('categories as c2', 'c2.id', '=', 'stores.category_2')
            ->select('stores.id', 'stores.name', 'stores.logo', 'stores.cover_photo',  'c1.name as category_1_name',  'c2.name as category_2_name')
            ->selectRaw("$distanceSql AS distance", [$userLat, $userLng, $userLat])
            ->selectRaw("CASE 
            WHEN stores.id IN ($subscribedIdsSql) THEN 1 ELSE 0 
            END AS subscribed")
            ->where([
                'stores.active' => 1,
                'stores.status' => 1,
                'stores.module_id' => 6
            ])
            ->whereIn('stores.zone_id', $zoneIds)
            ->whereNotNull('stores.latitude')
            ->whereNotNull('stores.longitude')
            ->groupBy('stores.id')
            ->having('distance', '<=', $radius)
            ->orderByDesc('subscribed')   // subscribed first
            ->orderBy('distance')         // nearest first
            ->paginate($limit, ['*'], 'page', $offset);;

        return response()->json($stores, 200);
    }
    public function get_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required',
        ]);

        $store = Store::find($request->store_id);
        if (isset($store) == false) {
            $validator->errors()->add('store_id', translate('messages.store_not_found'));
        }
        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $multi_review = DB::table('store_reviews')->join('stores', 'stores.id', 'store_reviews.store_id')->join('users', 'users.id', 'store_reviews.user_id')->where('stores.id', $request->store_id)->select('users.f_name', 'users.l_name', 'users.image as profile_image',  'store_reviews.comment', 'store_reviews.attachment', 'store_reviews.created_at', 'store_reviews.rating', 'store_reviews.reply', DB::raw('CASE WHEN store_reviews.reply IS NULL THEN NULL ELSE store_reviews.replied_at END as replied_at'))->where('store_reviews.status', 1)->get();

        foreach ($multi_review as $key => $value) {
            $attachment = json_decode($value->attachment);
            if (!empty($attachment)) {
                $multi_review[$key]->attachment = array_map(function ($file) {
                    return asset('storage/') . '/' . $file; 
                }, $attachment);
            } else {
                $multi_review[$key]->attachment = [];
            }
            $multi_review[$key]->profile_image = $value->profile_image ? asset('storage/profile') . '/' . $value->profile_image : null;
        } 

        return response()->json(['message' => translate('messages.data_retrieved_successfully'), 'data' => $multi_review], 200);
    }
    public function add_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'store_id' => 'required',
            'acceptance_id' => 'required',
            'comment' => 'required', 
            'rating' => 'required|numeric|max:5',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpeg,png,jpg,gif|max:30720',
        ]);
     if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }   
        $order = AcceptedServiceRequest::find($request->acceptance_id);
        if (isset($order) == false) {
            $validator->errors()->add('acc_id', translate('messages.service_data_not_found'));
        } else if ($order->current_status != 'Completed') {
            $validator->errors()->add('not_completed', 'Service not completed yet');
        }

        $store = Store::find($request->store_id);
        if (isset($store) == false) {
            $validator->errors()->add('store_id', translate('messages.store_not_found'));
        }

        $multi_review = StoreReview::where(['store_id' => $request->store_id, 'user_id' => $request->user_id, 'order_id' => $request->acceptance_id])->first();
        if (isset($multi_review)) {
            return response()->json([
                'errors' => [
                    ['code' => 'review', 'message' => translate('messages.already_submitted')]
                ]
            ], 403);
            // $review = new StoreReview;
        } else {
            $review = new StoreReview;
        }

   

        $image_array = [];
        if (!empty($request->file('attachments'))) {
            foreach ($request->file('attachments') as $image) {
                if ($image != null) {
                    if (!Storage::disk('public')->exists('review')) {
                        Storage::disk('public')->makeDirectory('review');
                    }
                    array_push($image_array, Storage::disk('public')->put('review', $image));
                }
            }
        }

        $review->user_id = $request->user_id;
        $review->store_id = $request->store_id;
        $review->order_id = $request->acceptance_id;
        $review->comment = $request->comment;
        $review->rating = $request->rating;
        $review->attachment = json_encode($image_array);
        $review->save();

        $ratingData = DB::table('store_reviews')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(rating) as rating_count')
            ->where('store_id', $request->store_id)
            ->first();

        $store->rating_count = $ratingData->rating_count;
        $store->average_rating = $ratingData->avg_rating;
        $store_rating = StoreLogic::update_store_rating($store->rating, (int)$request->rating);
        $store->rating = $store_rating;
        $store->save();


        return response()->json(['message' => translate('messages.review_submited_successfully')], 200);
    }
    public function add_service_review(Request $request)
    {
        $authUser = auth()->user();

        $validator = Validator::make($request->all(), [
            'store_id'      => 'required|integer',
            'acceptance_id' => 'nullable|integer',
            'service_name'  => 'required|string|max:255',
            'service_date'  => 'required|date',
            'experience'    => 'required|in:good,bad',
            'comment'       => 'required|string',
            'rating'        => 'required|numeric|min:1|max:5',
            'attachments'   => 'nullable|array',
            'attachments.*' => 'file|mimes:jpeg,png,jpg,gif|max:30720',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $store = Store::find($request->store_id);
        if (!$store) {
            return response()->json(['errors' => [['code' => 'store_id', 'message' => translate('messages.store_not_found')]]], 403);
        }

        if ($request->acceptance_id) {
            $order = AcceptedServiceRequest::find($request->acceptance_id);
            if (!$order) {
                return response()->json(['errors' => [['code' => 'acceptance_id', 'message' => translate('messages.service_data_not_found')]]], 403);
            }
            if ($order->current_status !== 'Completed') {
                return response()->json(['errors' => [['code' => 'acceptance_id', 'message' => 'Service not completed yet']]], 403);
            }
        }

        $duplicateQuery = StoreReview::where('store_id', $request->store_id)
            ->where('user_id', $authUser->id)
            ->where('service_name', $request->service_name)
            ->where('service_date', $request->service_date);
        if ($request->acceptance_id) {
            $duplicateQuery->where('order_id', $request->acceptance_id);
        }
        if ($duplicateQuery->exists()) {
            return response()->json(['errors' => [['code' => 'review', 'message' => translate('messages.already_submitted')]]], 403);
        }

        $image_array = [];
        if (!empty($request->file('attachments'))) {
            foreach ($request->file('attachments') as $image) {
                if ($image != null) {
                    if (!Storage::disk('public')->exists('review')) {
                        Storage::disk('public')->makeDirectory('review');
                    }
                    array_push($image_array, Storage::disk('public')->put('review', $image));
                }
            }
        }

        $review = new StoreReview;
        $review->user_id      = $authUser->id;
        $review->store_id     = $request->store_id;
        $review->order_id     = $request->acceptance_id;
        $review->service_name = $request->service_name;
        $review->service_date = $request->service_date;
        $review->experience   = $request->experience;
        $review->comment      = $request->comment;
        $review->rating       = $request->rating;
        $review->attachment   = $image_array;
        $review->save();

        $ratingData = DB::table('store_reviews')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(rating) as rating_count')
            ->where('store_id', $request->store_id)
            ->first();

        $store->rating_count   = $ratingData->rating_count;
        $store->average_rating = $ratingData->avg_rating;
        $store_rating = StoreLogic::update_store_rating($store->rating, (int)$request->rating);
        $store->rating = $store_rating;
        $store->save();

        return response()->json(['message' => translate('messages.review_submited_successfully')], 200);
    }

    public function get_service_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|integer',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $store = Store::find($request->store_id);
        if (!$store) {
            return response()->json(['errors' => [['code' => 'store_id', 'message' => translate('messages.store_not_found')]]], 403);
        }

        $reviews = DB::table('store_reviews')
            ->join('users', 'users.id', 'store_reviews.user_id')
            ->where('store_reviews.store_id', $request->store_id)
            ->where('store_reviews.status', 1)
            ->whereNotNull('store_reviews.service_name')
            ->select(
                'users.f_name', 'users.l_name', 'users.image as profile_image',
                'store_reviews.service_name', 'store_reviews.service_date',
                'store_reviews.experience', 'store_reviews.comment',
                'store_reviews.rating', 'store_reviews.attachment',
                'store_reviews.reply', 'store_reviews.created_at',
                DB::raw('CASE WHEN store_reviews.reply IS NULL THEN NULL ELSE store_reviews.replied_at END as replied_at')
            )
            ->orderByDesc('store_reviews.created_at')
            ->get();

        foreach ($reviews as $key => $value) {
            $attachment = json_decode($value->attachment, true);
            if (!empty($attachment) && is_array($attachment)) {
                $reviews[$key]->attachment = array_map(function ($file) {
                    return asset('storage/') . '/' . $file;
                }, $attachment);
            } else {
                $reviews[$key]->attachment = [];
            }
            $reviews[$key]->profile_image = $value->profile_image ? asset('storage/profile') . '/' . $value->profile_image : null;
        }

        return response()->json(['message' => translate('messages.data_retrieved_successfully'), 'data' => $reviews], 200);
    }

    public function get_latest_stores(Request $request, $filter_data = "all")
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
        $longitude = (float)$request->header('longitude');
        $latitude = (float)$request->header('latitude');
        $stores = StoreLogic::get_latest_stores($zone_id, $request['limit'], $request['offset'], $type, $longitude, $latitude);
        $stores['stores'] = Helpers::store_data_formatting($stores['stores'], true);

        return response()->json($stores, 200);
    }

    public function get_popular_stores(Request $request)
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
        $longitude = (float)$request->header('longitude');
        $latitude = (float)$request->header('latitude');
        $stores = StoreLogic::get_popular_stores($zone_id, $request['limit'], $request['offset'], $type, $longitude, $latitude);
        $stores['stores'] = Helpers::store_data_formatting($stores['stores'], true);

        return response()->json($stores, 200);
    }

    public function get_discounted_stores(Request $request)
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
        $longitude = (float)$request->header('longitude');
        $latitude = (float)$request->header('latitude');
        $stores = StoreLogic::get_discounted_stores($zone_id, $request['limit'], $request['offset'], $type, $longitude, $latitude);
        $stores['stores'] = Helpers::store_data_formatting($stores['stores'], true);

        return response()->json($stores, 200);
    }

    public function get_top_rated_stores(Request $request)
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
        $longitude = (float)$request->header('longitude');
        $latitude = (float)$request->header('latitude');
        $stores = StoreLogic::get_top_rated_stores($zone_id, $request['limit'], $request['offset'], $type, $longitude, $latitude);
        $stores['stores'] = Helpers::store_data_formatting($stores['stores'], true);

        usort($stores['stores'], function ($a, $b) {
            $key = 'avg_rating';
            return $b[$key] - $a[$key];
        });

        return response()->json($stores, 200);
    }

    public function get_popular_store_items($id)
    {
        $items = Item::when(is_numeric($id), function ($qurey) use ($id) {
            $qurey->where('store_id', $id);
        })
            ->when(!is_numeric($id), function ($query) use ($id) {
                $query->whereHas('store', function ($q) use ($id) {
                    $q->where('slug', $id);
                });
            })
            ->active()->popular()->limit(10)->get();
        $items = Helpers::product_data_formatting($items, true, true, app()->getLocale());

        return response()->json($items, 200);
    }

    public function get_details(Request $request, $id)
    {
        $longitude = (float)$request->header('longitude');
        $latitude = (float)$request->header('latitude');
        $store = StoreLogic::get_store_details($id, $longitude, $latitude);
        if ($store) {
            $category_ids = DB::table('items')
                ->join('categories', 'items.category_id', '=', 'categories.id')
                ->selectRaw('categories.position as positions, IF((categories.position = "0"), categories.id, categories.parent_id) as categories')
                ->where('items.store_id', $store->id)
                ->where('categories.status', 1)
                ->groupBy('categories', 'positions')
                ->get();

            $store = Helpers::store_data_formatting($store);
            $store['category_ids'] = array_map('intval', $category_ids->pluck('categories')->toArray());
            $store['category_details'] = Category::whereIn('id', $store['category_ids'])->get();
            $store['price_range']  = Item::withoutGlobalScopes()->where('store_id', $store->id)
                ->select(DB::raw('MIN(price) AS min_price, MAX(price) AS max_price'))
                ->get(['min_price', 'max_price']);
        }
        return response()->json($store, 200);
    }
    public function gallery(Request $request, $id){
        $store = Store::find($id);
        if (!$store) {
            return response()->json(['errors' => [['code' => 'store_id', 'message' => translate('messages.store_not_found')]]], 403);
        }
        $image_path = asset('storage/store/gallery/') . '/';
        $gallery = StoreGallery::where('store_id', $id)->select('id', 'image', 'created_at')->get()->map(function ($item) use ($image_path) {
            $item->image_path = $image_path . $item->image;
            unset($item->image);
            return $item;
        });
        return response()->json($gallery, 200);
    }
    public function get_details_limited(Request $request, $id)
    {
        $longitude = (float)$request->header('longitude');
        $latitude = (float)$request->header('latitude');
        $store = StoreLogic::get_store_details_limited($id, $longitude, $latitude);
        if ($store) {
            $store2 = store_data_formatting_limited($store);
        }
        return response()->json($store2, 200);
    }

    public function get_searched_stores(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $type = $request->query('type', 'all');

        $zone_id = $request->header('zoneId');
        $longitude = (float)$request->header('longitude');
        $latitude = (float)$request->header('latitude');
        $stores = StoreLogic::search_stores($request['name'], $zone_id, $request->category_id, $request['limit'], $request['offset'], $type, $longitude, $latitude);
        $stores['stores'] = Helpers::store_data_formatting($stores['stores'], true);
        return response()->json($stores, 200);
    }

    public function reviews(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $id = $request['store_id'];


        $reviews = Review::with(['customer', 'item'])
            ->whereHas('item', function ($query) use ($id) {
                return $query->where('store_id', $id);
            })
            ->active()->latest()->get();

        $storage = [];
        foreach ($reviews as $temp) {
            $temp['attachment'] = json_decode($temp['attachment']);
            $temp['item_name'] = null;
            $temp['item_image'] = null;
            $temp['customer_name'] = null;
            if ($temp->item) {
                $temp['item_name'] = $temp->item->name;
                $temp['item_image'] = $temp->item->image;
                if (count($temp->item->translations) > 0) {
                    $translate = array_column($temp->item->translations->toArray(), 'value', 'key');
                    $temp['item_name'] = $translate['name'];
                }
            }
            if ($temp->customer) {
                $temp['customer_name'] = $temp->customer->f_name . ' ' . $temp->customer->l_name;
            }

            unset($temp['item']);
            unset($temp['customer']);
            array_push($storage, $temp);
        }

        return response()->json($storage, 200);
    }


    public function get_recommended_stores(Request $request)
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
        $longitude = (float)$request->header('longitude') ?? 0;
        $latitude = (float)$request->header('latitude') ?? 0;
        $stores = StoreLogic::get_recommended_stores($zone_id, $request['limit'], $request['offset'], $type, $longitude, $latitude);
        $stores['stores'] = Helpers::store_data_formatting($stores['stores'], true);

        return response()->json($stores, 200);
    }

    public function get_services_list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        if (!$request->hasHeader('zoneId')) {
            $errors = [['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]];
            return response()->json(['errors' => $errors], 403);
        }
        $serviceData1 = DB::table('items')
            ->join('categories', 'items.category_id', 'categories.id')
            ->whereRaw('FIND_IN_SET(?, items.store_ids)', [$request->store_id])
            ->whereNull('items.inventory_item_id')
            ->where('categories.status', 1)
            ->select('categories.id', 'categories.name', 'categories.slug as cat_slug')
            ->distinct()
            ->get();

        foreach ($serviceData1 as $cat) {
            $cat->items = DB::table('items')
                ->whereRaw('FIND_IN_SET(?, store_ids)', [$request->store_id])
                ->whereNull('items.inventory_item_id')
                ->where('category_id', $cat->id)
                ->where('status', 1)
                ->select('items.id', 'items.name', 'items.description', 'items.image')
                ->get()
                ->map(function ($item) {
                    $item->image = $item->image ? asset('storage/app/public/product/') . '/' . $item->image : null;
                    return $item;
                });
        }

        //INVENTORY ITEMS
        $invItemdata = DB::table('items')
            ->join('categories', 'items.category_id', 'categories.id')
            ->whereRaw('FIND_IN_SET(?, items.store_ids)', [$request->store_id])
            ->whereNotNull('items.inventory_item_id')
            ->select('categories.id', 'categories.name', 'categories.slug as cat_slug')
            ->distinct()
            ->get();

        foreach ($invItemdata as $cat) {
            $cat->items = DB::table('items')
                ->leftJoin('inventory_items', 'inventory_items.id', '=', 'items.inventory_item_id')
                ->whereRaw('FIND_IN_SET(?, items.store_ids)', [$request->store_id])
                ->whereNotNull('items.inventory_item_id')
                ->where('items.category_id', $cat->id)
                ->where('items.status', 1)
                ->select(
                    'items.id',
                    'items.name',
                    'items.description',
                    'items.image',
                    'items.inventory_item_id',
                    'items.price as item_price',
                    'items.mrp_price as item_mrp_price',
                    'inventory_items.stock',
                    'inventory_items.unit',
                    'inventory_items.secondary_unit',
                    'inventory_items.hsn',
                    'inventory_items.gst_rate',
                    'inventory_items.gst_type',
                    'inventory_items.gst_status',
                    'inventory_items.variations',
                )
                ->get()
                ->map(function ($item) {
                    $item->image = $item->image ? asset('storage/app/public/product/') . '/' . $item->image : null;
                    $variations = $item->variations ? json_decode($item->variations) : [];
                    $firstVr = !empty($variations) ? $variations[0] : null;
                    if ($firstVr) {
                        $item->selling_price = $firstVr->price ?? $item->item_price;
                        $item->mrp           = $firstVr->mrpprice ?? $firstVr->price ?? $item->item_price;
                    } else {
                        $item->selling_price = $item->item_price;
                        $item->mrp           = $item->item_mrp_price;
                    }
                    unset($item->item_price, $item->item_mrp_price, $item->variations);
                    return $item;
                });
        }
        $productdata = $serviceData1->merge($invItemdata); // paginate this

        return response()->json($productdata, 200);
    }
    public function unmask_phone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:stores,id',
        ]);
 
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $request->user()->id;
        $store_id = $request->store_id;
        $ip = $request->ip();

        $isUnique = !DB::table('analytics_logs')
            ->where('screen_type', 'call')
            ->where('ref_id', $store_id)
            ->where('ip', $ip)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        DB::table('analytics_logs')->insert([
            'screen_type' => 'call',
            'ref_id' => $store_id,
            'user_id' => $user_id,
            'ip' => $ip,
            'created_at' => now(),
        ]);

        Store::where('id', $store_id)->increment('total_visits');
        if ($isUnique) {
            Store::where('id', $store_id)->increment('unique_visits');
        }

        $store = Store::find($store_id);

        return response()->json([
            'phone' => $store->phone,
        ], 200);
    }

    public function get_combined_data(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]];
            return response()->json(['errors' => $errors], 403);
        }

        $zone_id = $request->header('zoneId');
        $data_type = $request->query('data_type', 'all');
        $type = $request->query('type', 'all');
        $limit = $request->query('limit', 10);
        $offset = $request->query('offset', 1);
        $longitude = (float) $request->header('longitude') ?? 0;
        $latitude = (float) $request->header('latitude') ?? 0;
        $filter = $request->query('filter', '');
        $filter = $filter ? (is_array($filter) ? $filter : str_getcsv(trim($filter, "[]"), ',')) : '';
        $rating_count = $request->query('rating_count');

        switch ($data_type) {
            case 'searched':
                $validator = Validator::make($request->all(), ['name' => 'required']);
                if ($validator->fails()) {
                    return response()->json(['errors' => Helpers::error_processor($validator)], 403);
                }
                $name = $request->input('name');

                $paginator = StoreLogic::search_stores($name, $zone_id, $request->category_id, $limit, $offset, $type, $longitude, $latitude, $filter, $rating_count);
                break;

            case 'discounted':

                $paginator = StoreLogic::get_discounted_stores($zone_id, $limit, $offset, $type, $longitude, $latitude, $filter, $rating_count);
                break;

            case 'category':
                $validator = Validator::make($request->all(), [
                    'category_ids' => 'required|array',
                    'category_ids.*' => 'integer'
                ]);

                if ($validator->fails()) {
                    return response()->json(['errors' => Helpers::error_processor($validator)], 403);
                }

                $category_ids = $request->input('category_ids');

                $paginator = CategoryLogic::category_stores($category_ids, $zone_id, $limit, $offset, $type, $longitude, $latitude, $filter, $rating_count);
                break;

            default:
                $filter_data = $request->query('filter_data', 'all');
                $store_type = $request->query('store_type', 'all');
                $featured = $request->query('featured');
                $paginator = StoreLogic::get_stores($zone_id, $filter_data, $type, $store_type, $limit, $offset, $featured, $longitude, $latitude, $filter, $rating_count);
                break;
        }

        $paginator['stores'] = Helpers::store_data_formatting($paginator['stores'], true);
        return response()->json($paginator, 200);
    }
}
