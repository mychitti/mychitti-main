<?php 
namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use App\Events\LocationUpdated;
use Pusher\Pusher;

class LocationController extends Controller
{
    public function update(Request $request, $userId)
    {
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        // Check if latitude and longitude are provided in the request
        if (is_null($latitude) || is_null($longitude)) {
            return response()->json(['error' => 'Latitude and longitude are required'], 400);
        }

        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => true
            ]
        );
        
        $pusher->trigger('locations', 'location-updated', [
            'userId' => $userId,
            'latitude' => $latitude, 
            'longitude' => $longitude 
        ]);
        
        return response()->json(['status' => 'Location broadcasted', '$userId' => $userId , 'latitude' => $latitude,'longitude' => $longitude   ]);
    }

    public function location_view(Request $request)
    {
        return view('front-views.location-check');
    }
    public function user_view(Request $request)
    {
        return view('front-views.send-location');
    }
}
