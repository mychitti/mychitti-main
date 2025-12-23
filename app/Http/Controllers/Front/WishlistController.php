<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Cart;
use App\Models\Item;
use App\Models\ItemCampaign;
use App\CentralLogics\Helpers;
use App\Models\Wishlist;

class WishlistController extends Controller
{

    public function __construct()
    {
        /*$this->middleware('auth');*/
    }

    public function update_wishlist(Request $request)
    {
        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');

        if ($request->action == 'add') {

            if ($request->type == 'item') {
                $wishlist = Wishlist::where('user_id', $user_id)->where('item_id', $request->item_id)->whereNull('store_id')->first();
            } else {
                $wishlist = Wishlist::where('user_id', $user_id)->where('store_id', $request->store_id)->whereNull('item_id')->first();
            }
            if (empty($wishlist)) {
                $wishlist = new Wishlist;
                $wishlist->user_id = $user_id;

                if ($request->type == 'item') {
                    $wishlist->item_id = $request->item_id;
                    $wishlist->store_id = null;
                } else {
                    $wishlist->item_id = null;
                    $wishlist->store_id =  $request->store_id;
                }

                if ($wishlist->save()) {
                    return response()->json(['status' => true, 'message' => translate('messages.added_successfully')]);
                } else {
                    return response()->json(['status' => false, 'message' => 'Some error occured']);
                }
            }
            return response()->json(['status' => false, 'message' => translate('messages.already_in_wishlist')]);
        } else {
            if ($request->type == 'item') {
                $wishlist = Wishlist::where('user_id', $user_id)->where('item_id', $request->item_id)->whereNull('store_id')->first();
            } else {
                $wishlist = Wishlist::where('user_id', $user_id)->where('store_id', $request->store_id)->whereNull('item_id')->first();
            }
            if (!empty($wishlist)) {
                if ($wishlist->delete()) {
                    return response()->json(['status' => true, 'message' => 'Removed Successfully']);
                } else {
                    return response()->json(['status' => false, 'message' => 'Some error occured']);
                }
            }
        }
    }
}
