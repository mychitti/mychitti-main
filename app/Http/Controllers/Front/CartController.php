<?php

namespace App\Http\Controllers\Front;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Cart;
use App\Models\Item;
use App\Models\ItemCampaign;
use App\CentralLogics\Helpers;
use App\Models\CustomerAddress;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    
    public function __construct()
    {
        /*$this->middleware('auth');*/
    }
    public function add_to_cart(Request $request)
    {
        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id') ;
        $is_guest = auth('web')->user() ? 0 : 1;
        $item_id = $request->prId;
        $item = DB::table('items')->where('id',$item_id)->first();

        //reset cart if needed
        $cart = Cart::where('user_id' ,$user_id)->first();
        if($cart){
            //existing store
            $old_item = DB::table('items')->where('id', $cart->item_id)->first();
            $old_store = $old_item->store_id;

            // new item's store
            $new_store = $item->store_id;

            if($old_store != $new_store){
                $carts = Cart::where('user_id', $user_id)->where('is_guest',$is_guest)->get();
                foreach($carts as $cart){
                    $cart->delete();
                }
            }
        }

        // prx($item);
        $quantity = 1;
        $model = 'App\Models\Item'; 
        $price = $item->price;
        
        $variations = json_decode($item->variations);
        
        if($request->variation != ''){
            $variation =  '['.json_encode($variations[$request->variation]) . ']' ;
            $price = $variations[$request->variation]->price ;
        }else{
            $variation = json_encode([]);
        }

        $cart = Cart::where('item_id',$item_id)->where('item_type',$model)->where('variation',json_encode($variation))->where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',5)->first();

        if($cart){
            return response()->json(['status' => false, 'message' => 'Item already exists in cart']);
        }

        if($item->maximum_cart_quantity && ($quantity>$item->maximum_cart_quantity)){
            return response()->json(['status' => false, 'message' => 'Maximum cart quantity exceeded']);
        }
      
        $cart = new Cart();
        $cart->user_id = $user_id;
        $cart->module_id = 5;
        $cart->item_id = $item_id;
        $cart->is_guest = $is_guest;
        $cart->add_on_ids = json_encode([]);
        $cart->add_on_qtys = json_encode([]);
        $cart->item_type = $model;
        $cart->price = $price;
        $cart->quantity = $quantity;
        $cart->variation =  $variation ;
        $cart->save();

        // $item->carts()->save($cart);

        return response()->json(['status' => true, 'message' => _randomAddCartMsg(), 'cart_id' => $cart->id, 'firstvr' => 1 ]);
    }

    public function check_cart(Request $request){
        if($request->cart_id){ //update qty
           
        }else{
            $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id') ;
            $cart = Cart::where('user_id' ,$user_id)->first();
            if($cart){
                //existing store
                $item = DB::table('items')->where('id', $cart->item_id)->first();
                $store = $item->store_id;

                // new item's store
                $item2 = DB::table('items')->where('id', $request->prId)->first();
                $store2 = $item2->store_id;

                if($store != $store2){
                    return false;
                }
            }
        }
        return true;
    }
    public function get_delivery_charges(Request $request){
        $store =  Store::find($request->store_id);
        $user = User::find($request->user_id);
        $address = CustomerAddress::find($request->addr_id);
        $delivery_charges =  _calcDeliveryCharge($address, $store, $user, $coupon_code = null, $order_type =  $request->order_type);
        return json_encode($delivery_charges);

    }
    public function remove_from_cart(Request $request){

        $cart = Cart::find($request->cart_id);
        if( $cart->variation == '[]'){
            $vrCount = 0;
        }else{
            $vrCount = 1;
        }

        if($cart->delete()){
            return response()->json(['status' => true, 'message' => "Item removed from cart!", 'firstVr' => $vrCount]);
        }else{
            return response()->json(['status' => false, 'message' => "Some error occured", 'firstVr' => $vrCount]);
        }

    }

    public function change_cart_quantity(Request $request){
        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id') ;
        $is_guest = auth('web')->user() ? 0 : 1;
        $cart = Cart::where('id',$request->cartId)->where('user_id', $user_id)->where('is_guest',$is_guest)->where('module_id',5)->first();

        $item = DB::table('items')->where('id', $cart->item_id)->first();
        $price = $item->price;

        $variation = $cart->variation;
        if($variation && is_array(json_decode($variation)) ){
            $vrArr = json_decode($variation);
            if(!empty($vrArr) && isset($vrArr[0])){
                $price = $vrArr[0]->price;
            }
        }
        
        if($request->action == 'increase'){
            $cart->quantity += 1;
            $cart->price += _discountedPrice( $price, $item->discount,$item->discount_type );
        }else{
            $cart->quantity -= 1;
            $cart->price -= _discountedPrice( $price, $item->discount,$item->discount_type );
        }
        if($cart->save()){
            return response()->json(['status' => true, 'message' => "Cart Updated successfully"]);
        }else{
            return response()->json(['status' => false, 'message' => "Some error occured"]);
        }
    }
  
}


