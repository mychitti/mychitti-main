<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\CentralLogics\Helpers;
class NotificationController extends Controller
{
    public function get_notifications(Request $request){

        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => 'Zone id is required!']);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id= $request->header('zoneId');
        
        try {
            $notifications = Notification::active()
            ->select('id', 'title', 'description', 'image', 'created_at')
            ->where('tergat', 'customer')
            ->where(function($q)use($zone_id){
                $q->whereNull('zone_id')->orWhereIn('zone_id', json_decode($zone_id))->orWhere('zone_id', 0);
            })
            ->where('updated_at', '>=', \Carbon\Carbon::today()->subDays(15))->where('added_by' ,'!=' ,'vendor')
            ->get();
            $notifications->append('data');
            $notifications = $notifications->map(function($n) {
                if ($n->image) {
                    $n->image = asset('storage/notification/' . ltrim($n->image, '/'));
                }
                return $n;
            }); 

            $user_notifications = UserNotification::select('id', 'data', 'created_at')->where('user_id', $request->user()->id)->where('updated_at', '>=', \Carbon\Carbon::today()->subDays(15))->get();
            $user_notifications = $user_notifications->map(function($n) {
                $data = $n->data;
                $image = !empty($data['image']) ? asset('storage/notification/' . ltrim($data['image'], '/')) : null;
                if ($image) $data['image'] = $image;
                return [
                    'id' => $n->id,
                    'title' => $data['title'] ?? null,
                    'description' => $data['description'] ?? null, 
                    'image' => $image,
                    'created_at' => $n->created_at,
                    'data' => $data, 
                ];
            });
            $all = collect($notifications->toArray())->merge($user_notifications)->sortByDesc('created_at')->values();
            $perPage = $request->input('limit', 15);
            $page = $request->input('offset', 1);
            $notifications = new LengthAwarePaginator(
                $all->forPage($page, $perPage)->values(),
                $all->count(),
                $perPage,
                $page 
            );
            return response()->json($notifications, 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    } 
    public function get_ads(Request $request){
 
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => 'Zone id is required!']);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id = $request->header('zoneId');
        try {
            $notifications = Notification::active()
            ->select('id', 'title', 'description', 'image', 'created_at')
            ->where('tergat', 'customer')
            ->where(function($q)use($zone_id){
                $q->whereNull('zone_id')->orWhereIn('zone_id', json_decode($zone_id))->orWhere('zone_id', 0);
            })
            ->where('updated_at', '>=', \Carbon\Carbon::today()->subDays(15))->where('added_by','vendor')
            ->get();
            $notifications->append('data'); 
            $notifications = $notifications->map(function($n) {
                if ($n->image) {
                    $n->image = asset('storage/notification/' . ltrim($n->image, '/'));
                }
                return $n;
            });

            return response()->json($notifications, 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

}
