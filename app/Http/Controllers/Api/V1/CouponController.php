<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\CentralLogics\CouponLogic;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    public function list(Request $request)
    {
        // prx('fds');
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $customer_id = Auth::user()?->id ?? $request->customer_id ?? null;

        $zone_id = $request->header('zoneId');
        $data = [];
        // try {
        $coupons = Coupon::withoutGlobalScopes()->with('store:id,name')->active()
            ->when(config('module.current_module_data'), function ($query) {
                $query->module(config('module.current_module_data')['id']);
            })
            ->whereDate('expire_date', '>=', date('Y-m-d'))->whereDate('start_date', '<=', date('Y-m-d'))->get();
        // prx($coupons);
        foreach ($coupons as $key => $coupon) {
            if ($coupon->coupon_type == 'store_wise') {
                $temp = Store::active()
                    ->when(config('module.current_module_data'), function ($query) use ($zone_id) {
                        if (!config('module.current_module_data')['all_zone_service']) {
                            $query->whereIn('zone_id', json_decode($zone_id, true));
                        }
                    })
                    ->whereIn('id', json_decode($coupon->data, true))->first();
                if ($temp && (in_array("all", json_decode($coupon->customer_id, true)) || in_array($customer_id, json_decode($coupon->customer_id, true)))) {
                    $coupon->data = $temp->name;
                    $coupon['store_id'] = (int)$temp->id;
                    $data[] = $coupon;
                }
            } else if ($coupon->coupon_type == 'zone_wise') {
                if (count(array_intersect(json_decode($zone_id, true), json_decode($coupon->data, true)))) {
                    $data[] = $coupon;
                }
            } else if (isset($coupon->store_id)) {
                $temp = Store::active()->when(config('module.current_module_data'), function ($query) use ($zone_id) {
                    if (!config('module.current_module_data')['all_zone_service']) {
                        $query->whereIn('zone_id', json_decode($zone_id, true));
                    }
                })->where('id', $coupon->store_id)->exists();

                if ($temp) {
                    $data[] = $coupon;
                }
            } else {
                if ((in_array("all", json_decode($coupon->customer_id, true)) || in_array($customer_id, json_decode($coupon->customer_id, true)))) {
                    $data[] = $coupon;
                }
            }
        }

        return response()->json($data, 200);
    }
    public function user(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'user_id' => 'required',
        // ]);
        $user_id =  $request->user()->id ??  $request->user_id;
        $coupons = Coupon::withoutGlobalScopes()
            ->with('store:id,name')
            ->active()
            ->whereDate('expire_date', '>=', now()->toDateString())
            ->whereJsonContains('customer_id', (string) $user_id) // ✅ MUST be string
            ->get();
        return response()->json(['data' =>  $coupons], 200);
    } 

    public function scratch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_id' => 'required',
        ]);
        $user_id = Auth::user()?->id;
        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        try {
            $coupon = Coupon::active()->where(['id' => $request['coupon_id']])->first();
            if (isset($coupon)) {
                $scratchedCustomerIds = json_decode($coupon->scratched_by, true);

                if (!is_array($scratchedCustomerIds)) {
                    $scratchedCustomerIds = [];
                }

                if (in_array((string)$user_id, $scratchedCustomerIds, true)) {
                    return response()->json(['status' => false, 'message'=> 'Already Scratched'], 403);
                }
                $scratchedCustomerIds[] = (string)$user_id;
                $coupon->scratched_by = json_encode(array_values($scratchedCustomerIds));
                $coupon->save();
                return response()->json(['status' => true, 'message' => 'Updated Successfully'], 200);
            } else {
                return response()->json([
                    'errors' => [
                        ['code' => 'coupon', 'message' => translate('messages.not_found')]
                    ]
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e], 403);
        }
    }
    public function apply(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'store_id' => 'required',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        try {
            $coupon = Coupon::active()->where(['code' => $request['code']])->first();
            if (isset($coupon)) {
                $staus = CouponLogic::is_valide($coupon, $request->user()->id, $request['store_id']);

                switch ($staus) {
                    case 200:
                        return response()->json($coupon, 200);
                    case 406:
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => translate('messages.coupon_usage_limit_over')]
                            ]
                        ], 406);
                    case 407:
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => translate('messages.coupon_expire')]
                            ]
                        ], 407);
                    case 408:
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => translate('messages.You_are_not_eligible_for_this_coupon')]
                            ]
                        ], 403);
                    default:
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => translate('messages.not_found')]
                            ]
                        ], 404);
                }
            } else {
                return response()->json([
                    'errors' => [
                        ['code' => 'coupon', 'message' => translate('messages.not_found')]
                    ]
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e], 403);
        }
    }
}
