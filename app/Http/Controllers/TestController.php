<?php

namespace App\Http\Controllers;

use App\Events\TestPusherEvent;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        return view('front-views.notification_test');
    }

    public function trigger(Request $request)
    {
        $message = $request->input('message', 'Hello from Laravel Pusher! ' . now());
        event(new TestPusherEvent($message));
        return response()->json(['status' => 'ok', 'message' => $message]);
    }
}
