<?php

namespace App\Http\Controllers\Vendor;

use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\Review;
use App\Http\Controllers\Controller;
use App\Models\StoreReview;
use Brian2694\Toastr\Facades\Toastr;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::whereHas('item', function($query){
            return $query->where('store_id', Helpers::get_store_id());
        })->latest()->paginate(config('default_pagination'));
        return view('vendor-views.review.index', compact('reviews'));
    }
    public function submit_reply(Request $request) {
        // prx($request->all());
        $rev = StoreReview::find($request->rev_id);
        $rev->reply = $request->reply;
        $rev->replied_at = now();
        $rev->save();

        Toastr::success(translate('replied successfully'));
        return redirect()->back();
    }
}
