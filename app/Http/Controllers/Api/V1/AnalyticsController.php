<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AnalyticsController extends Controller
{
    public function incrementCount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'nullable|integer|prohibited_unless:banner_id,null|prohibited_unless:ad_id,null',
            'banner_id' => 'nullable|integer|prohibited_unless:store_id,null|prohibited_unless:ad_id,null',
            'ad_id' => 'nullable|integer|prohibited_unless:store_id,null|prohibited_unless:banner_id,null',
            'screen_type' => 'required|in:call,location,banner,store,ad',
            'sub_type' => 'nullable|string|max:50',
        ], [ 
            'screen_type.required' => 'screen type is required',
            'screen_type.in' => 'screen_type must be one of: call, location, banner, store, ad',
            'store_id.prohibited_unless' => 'Only one of store_id, banner_id, or ad_id is allowed',
            'banner_id.prohibited_unless' => 'Only one of store_id, banner_id, or ad_id is allowed',
            'ad_id.prohibited_unless' => 'Only one of store_id, banner_id, or ad_id is allowed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!$request->store_id && !$request->banner_id && !$request->ad_id) {
            return response()->json(['message' => 'Exactly one of store_id, banner_id, or ad_id is required'], 422);
        }

        $ip = $request->ip();
        $userId = auth('api')->check() ? auth('api')->id() : null;

        if ($request->store_id) {
            $store = DB::table('stores')->where('id', $request->store_id)->first();
            if (!$store) {
                return response()->json(['message' => 'Store not found'], 404);
            }

            $isUnique = !DB::table('analytics_logs')
                ->where('screen_type', $request->screen_type)
                ->where('ref_id', $request->store_id)
                ->where('ip', $ip)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            DB::table('stores')->where('id', $request->store_id)->increment('total_visits');
            if ($isUnique) {
                DB::table('stores')->where('id', $request->store_id)->increment('unique_visits');
            }

            DB::table('analytics_logs')->insert([
                'screen_type' => $request->screen_type,
                'sub_type' => $request->sub_type,
                'ref_id' => $request->store_id,
                'user_id' => $userId,
                'ip' => $ip,
                'created_at' => now(),
            ]);

            return response()->json(['message' => 'Store visit counted']);
        }

        if ($request->banner_id) {
            $banner = DB::table('banners')->where('id', $request->banner_id)->first();
            if (!$banner) {
                return response()->json(['message' => 'Banner not found'], 404);
            }

            $isUnique = !DB::table('analytics_logs')
                ->where('screen_type', 'banner')
                ->where('ref_id', $request->banner_id)
                ->where('ip', $ip)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            DB::table('banners')->where('id', $request->banner_id)->increment('total_clicks');
            if ($isUnique) {
                DB::table('banners')->where('id', $request->banner_id)->increment('unique_clicks');
            }

            DB::table('analytics_logs')->insert([
                'screen_type' => 'banner',
                'sub_type' => $request->sub_type,
                'ref_id' => $request->banner_id,
                'user_id' => $userId,
                'ip' => $ip,
                'created_at' => now(),
            ]);

            return response()->json(['message' => 'Banner click counted']);
        }

        if ($request->ad_id) {
            $ad = DB::table('notifications')->where('id', $request->ad_id)->first();
            if (!$ad) {
                return response()->json(['message' => 'Ad not found'], 404);
            }

            $isUnique = !DB::table('analytics_logs')
                ->where('screen_type', 'ad')
                ->where('ref_id', $request->ad_id)
                ->where('ip', $ip)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            DB::table('notifications')->where('id', $request->ad_id)->increment('total_clicks');
            if ($isUnique) {
                DB::table('notifications')->where('id', $request->ad_id)->increment('unique_clicks');
            }

            DB::table('analytics_logs')->insert([
                'screen_type' => 'ad',
                'sub_type' => $request->sub_type,
                'ref_id' => $request->ad_id,
                'user_id' => $userId,
                'ip' => $ip,
                'created_at' => now(),
            ]);

            return response()->json(['message' => 'Ad click counted']);
        }

        return response()->json(['message' => 'No valid parameter provided'], 422);
    }
}
