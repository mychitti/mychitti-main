<?php

namespace App\Http\Middleware;

use App\CentralLogics\Helpers;
use App\Models\Guest;
use Brian2694\Toastr\Facades\Toastr;
use Closure;
use Illuminate\Support\Facades\Log;

class FrontUserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!auth('web')->user() && !$request->session()->has('guest_id')){
            $gId =  Guest::where('ip_address', $request->ip())->first();
            if($gId){ 
                session(['guest_id' => $gId->id]);
            }else{
                $guest = new Guest();
                $guest->ip_address = $request->ip();
                $guest->fcm_token = null;
                $guest->save();
                session(['guest_id' => $guest->id]);
            }
        }
        return $next($request);
    }
}
