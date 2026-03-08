<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
 
class AppConfigController extends Controller
{
    public function index(Request $request)
    {
            $app_config = DB::table('app_configs')->first();
            return response()->json($app_config, 200);
    }

    public function update(Request $request)
    { 
        $data = $request->except(['_token', '_method']);

        if (empty($data)) {
            return response()->json(['message' => 'No data provided to update'], 400);
        }

        $appConfig = DB::table('app_configs')->first();

        $data['updated_at'] = Carbon::now();

        if (!$appConfig) {
            $data['created_at'] = Carbon::now(); 
            DB::table('app_configs')->insert($data);
        } else {
            DB::table('app_configs')->where('id', $appConfig->id)->update($data);
        }

        $appConfig = DB::table('app_configs')->first();
        return response()->json(['message' => 'App config updated successfully', 'data' => $appConfig], 200);
    }


}
