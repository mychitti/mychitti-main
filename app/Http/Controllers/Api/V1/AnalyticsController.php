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
            'items'                => 'required|array|min:1',
            'items.*.ref_id'       => 'required|integer',
            'items.*.screen_type'  => 'required|in:call,location,banner,store,ad,share,copy',
            'items.*.sub_type'     => 'nullable|string|max:50',
        ], [
            'items.required'              => 'items array is required',
            'items.*.ref_id.required'     => 'ref_id is required for each item',
            'items.*.ref_id.integer'      => 'ref_id must be an integer',
            'items.*.screen_type.required'=> 'screen_type is required for each item',
            'items.*.screen_type.in'      => 'screen_type must be one of: call, location, banner, store, ad, share, copy',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ip     = $request->ip();
        $userId = auth('api')->check() ? auth('api')->id() : null;

        $tableConfig = [
            'store'    => ['table' => 'stores',        'count_col' => 'total_visits', 'unique_col' => 'unique_visits', 'label' => 'Store'],
            'call'     => ['table' => 'stores',        'count_col' => 'total_visits', 'unique_col' => 'unique_visits', 'label' => 'Store'],
            'location' => ['table' => 'stores',        'count_col' => 'total_visits', 'unique_col' => 'unique_visits', 'label' => 'Store'],
            'banner'   => ['table' => 'banners',       'count_col' => 'total_clicks', 'unique_col' => 'unique_clicks', 'label' => 'Banner'],
            'ad'       => ['table' => 'notifications', 'count_col' => 'total_clicks', 'unique_col' => 'unique_clicks', 'label' => 'Ad'],
        ];

        // share sub_type → which table to validate ref_id against
        $shareTableMap = [
            'store'   => ['table' => 'stores',  'label' => 'Store'],
            'service' => ['table' => 'items',   'label' => 'Service'],
        ];

        foreach ($request->items as $index => $item) {
            $refId      = $item['ref_id'];
            $screenType = $item['screen_type'];
            $subType    = $item['sub_type'] ?? null;

            if ($screenType === 'share') {
                // Validate ref_id against the correct table based on sub_type
                $shareConfig = $shareTableMap[$subType] ?? null;
                if ($shareConfig) {
                    $record = DB::table($shareConfig['table'])->where('id', $refId)->first();
                    if (!$record) {
                        return response()->json(['message' => "{$shareConfig['label']} not found (index: {$index}, id: {$refId})"], 404);
                    }
                }

                DB::table('analytics_logs')->insert([
                    'screen_type' => $screenType,
                    'sub_type'    => $subType,
                    'ref_id'      => $refId,
                    'user_id'     => $userId,
                    'ip'          => $ip,
                    'created_at'  => now(),
                ]);
                continue;
            }

            // For non-share events, sub_type records the source platform.
            $subType = $subType ?: 'app';

            if ($screenType === 'copy') {
                // Phone-number copy: log-only (no denormalized counter column).
                $record = DB::table('stores')->where('id', $refId)->first();
                if (!$record) {
                    return response()->json(['message' => "Store not found (index: {$index}, id: {$refId})"], 404);
                }

                DB::table('analytics_logs')->insert([
                    'screen_type' => $screenType,
                    'sub_type'    => $subType,
                    'ref_id'      => $refId,
                    'user_id'     => $userId,
                    'ip'          => $ip,
                    'created_at'  => now(),
                ]);
                continue;
            }

            $config = $tableConfig[$screenType];

            $record = DB::table($config['table'])->where('id', $refId)->first();
            if (!$record) {
                return response()->json(['message' => "{$config['label']} not found (index: {$index}, id: {$refId})"], 404);
            }

            $isUnique = !DB::table('analytics_logs')
                ->where('screen_type', $screenType)
                ->where('ref_id', $refId)
                ->where('ip', $ip)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            DB::table($config['table'])->where('id', $refId)->increment($config['count_col']);
            if ($isUnique) {
                DB::table($config['table'])->where('id', $refId)->increment($config['unique_col']);
            }

            DB::table('analytics_logs')->insert([
                'screen_type' => $screenType,
                'sub_type'    => $subType,
                'ref_id'      => $refId,
                'user_id'     => $userId,
                'ip'          => $ip,
                'created_at'  => now(),
            ]);
        }

        return response()->json(['message' => 'Analytics recorded successfully']);
    }
}
