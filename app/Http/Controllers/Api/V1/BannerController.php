<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Banner;
use App\Models\Campaign;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\CentralLogics\BannerLogic;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\OfferBanner;
use App\Models\Store;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
 
class BannerController extends Controller
{
    public function get_banners(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        $banners = BannerLogic::get_banners($zone_id, $request->query('featured'), 'app');
        $campaigns = [];
        if (!$request->featured) {
            $campaigns = Campaign::whereHas('module.zones', function ($query) use ($zone_id) {
                $query->whereIn('zones.id', json_decode($zone_id, true));
            })
                ->when(config('module.current_module_data'), function ($query) use ($zone_id) {
                    $query->module(config('module.current_module_data')['id']);
                    if (!config('module.current_module_data')['all_zone_service']) {
                        $query->whereHas('stores', function ($q) use ($zone_id) {
                            $q->whereIn('zone_id', json_decode($zone_id, true));
                        });
                    }
                })
                ->running()->active()->get();
        }

        try {
            return response()->json(['campaigns' => Helpers::basic_campaign_data_formatting($campaigns, true), 'banners' => $banners], 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }
    public function get_item_banners(Request $request, $item_id)
    {
        // prx($request->all());
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_ids = json_decode($request->header('zoneId'), true);

        $banners = DB::table('banners')->where('platform', 'app')->where('status', 1)->whereIn('zone_id', $zone_ids)->where('type', 'item_wise')->where('data', $item_id)
            ->select('id','title', 'image', 'default_link', 'created_at')->orderBy('sort_order')->get();
        // prx($banners);

        foreach ($banners as $key => $value) {
            $banners[$key]->image =  asset('storage/app/public/banner/') . '/' . $value->image;
        }
        return response()->json($banners, 200);
    }
    public function id_by_slug(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'type' => 'required|in:store,category,item',
                'slug' => 'required',
                'zone_name' => 'nullable',
            ],
            [
                'type.in' => 'Only store, category or item are accepted.',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $zoneId = null;

        if ($request->filled('zone_name')) {
            $zoneId = Zone::where('name', $request->zone_name)->value('id');
        }

        $slug = $request->slug;

        $id = match ($request->type) {

            'store' => Store::withoutGlobalScopes()
                ->when($zoneId, fn($q) => $q->where('zone_id', $zoneId))
                ->where('slug', $slug)
                ->value('id'),

            'category' => Category::when($zoneId, fn($q) => $q->where('zone_id', $zoneId))
                ->where('slug', $slug)
                ->value('id'),

            'item' => Item::when($zoneId, fn($q) => $q->where('zone_id', $zoneId))
                ->where('slug', $slug)
                ->value('id'),
        };

        return response()->json([
            'id' => $id,
            'zone_id' => $zoneId
        ], 200);
    }

    public function get_module_banners(Request $request)
    {

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_ids = json_decode($request->header('zoneId'), true);

        $banners = DB::table('banners')->where('status', 1)->whereIn('zone_id', $zone_ids)->where('type', 'module_wise')->where('data', $request->module_id)
            ->orderBy('sort_order')->get();
        // prx($banners);

        foreach ($banners as $key => $value) {
            $banners[$key]->image =  asset('storage/app/public/banner/') . '/' . $value->image;
        }
        return response()->json($banners, 200);
    }
    public function get_default_banners(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_ids = json_decode($request->header('zoneId'), true);

        $banners = DB::table('banners')->where('status', 1)->whereIn('zone_id', $zone_ids)->where('type', 'default')->where('platform', 'app')
            ->select("id", "title", "image", "default_link", 'created_at')
            ->orderBy('sort_order')->get();

        foreach ($banners as $key => $value) {
            $banners[$key]->image =  asset('storage/banner/') . '/' . $value->image;
        }
        return response()->json($banners, 200);
    }
    public function category_banners(Request $request, $ctId)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json(['errors' => $errors], 403);
        }
        $zone_ids = json_decode($request->header('zoneId'), true);
        $banners = DB::table('banners')->where('status', 1)->where('type', 'category_wise')->where('platform', 'app')->where('data', $ctId)
            ->whereIn('zone_id', $zone_ids)
            ->select("id","title", "image", "default_link", 'created_at')
            ->orderBy('sort_order')->get();
        foreach ($banners as $key => $value) {
            $banners[$key]->image =  asset('storage/banner/') . '/' . $value->image;
        }
        return response()->json($banners, 200);
    }
    public function get_paid_banners(Request $request)
    {
        $banners = DB::table('banners')->where('status', 1)->where('paid', 1)->where('expiry_date', '>', date('Y-m-d H:i:s'))
            ->orderBy('sort_order')->get();
        foreach ($banners as $key => $value) {
            $banners[$key]->image =  asset('storage/banner/') . '/' . $value->image;
        }
        return response()->json($banners, 200);
    }

    public function get_offer_banners(Request $request, $store_id)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_ids = json_decode($request->header('zoneId'), true);
        $offer_banners = OfferBanner::where('approved', 1)
            ->where('status', 1)
            ->where('end_date', '>=', date('Y-m-d'))
            ->whereHas('store', function ($query) use ($zone_ids) {
                $query->whereIn('zone_id', $zone_ids);
            })
            ->get();
        if (!count($offer_banners)) {
            $offer_banners = OfferBanner::where('store_id', 0)
                ->whereDate('start_date', '<=', date('Y-m-d'))
                ->whereDate('end_date', '>=', date('Y-m-d'))->where(function ($query) use ($zone_ids) {
                    $query->whereIn('zone', $zone_ids)
                        ->orWhere('zone', 0);
                })
                ->get();
        }

        foreach ($offer_banners as $key => $banner) {
            $offer_banners[$key]->image = asset('storage/app/public/banners') . '/' . $banner->image;
        }
        return response()->json($offer_banners, 200);
    }
    public function get_store_banners(Request $request, $store_id)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        $banners = Banner::active();
        // if (config('module.current_module_data')) {
        //     $banners = $banners->whereHas('zone.modules', function ($query) {
        //         $query->where('modules.id', config('module.current_module_data')['id']);
        //     })
        //         ->module(config('module.current_module_data')['id'])
        //         ->when(!config('module.current_module_data')['all_zone_service'], function ($query) use ($zone_id) {
        //             $query->whereIn('zone_id', json_decode($zone_id, true));
        //         });
        // }

     $banners = $banners
    ->whereIn('zone_id', json_decode($zone_id, true))
    ->where('platform', 'app')
    ->where('data', $store_id)
    ->select("id","title", "image", "default_link", 'created_at')
    ->orderBy('sort_order')
    ->get()
    ->map(function ($banner) {
        $banner->image = asset('storage/banner/' . $banner->image);
        return $banner;
    });

        return response()->json($banners, 200);
    }
}
