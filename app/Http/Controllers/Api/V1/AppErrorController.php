<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AppError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppErrorController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'type' => 'required|string|max:255',
        ]); 

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $error = AppError::create([
            'message' => $request->message,
            'stack' => $request->stack,
            'api_url' => $request->api_url,
            'user_id' => $request->user_id,
            'type' => $request->type,
            'device' => $request->device,
            'app_version' => $request->app_version,
            'platform' => $request->platform,
        ]);

        return response()->json(['message' => 'Error logged successfully'], 200);
    }
}
