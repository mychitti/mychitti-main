<?php

namespace App\Modules\POS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\OrderType;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class OrderTypeController extends Controller
{
    public function store(Request $request)
    {
        $order_type = new OrderType();
        $order_type->store_id = Helpers::get_store_id();
        $order_type->name = $request->order_type_name;
        $order_type->icon = Helpers::upload('store/order_type/', 'png', $request->file('icon'));
        $order_type->save();

        Toastr::success('order_type_created_successfully');
        return back();
    }

    public function delete(Request $request, $id)
    {
        OrderType::where(['id' => $id, 'store_id' => Helpers::get_store_id()])->delete();
        Toastr::success('order_type_deleted_successfully');
        return back();
    }

    public function update(Request $request)
    {
        $order_type = OrderType::where(['id' => $request->edit_id, 'store_id' => Helpers::get_store_id()])->first();

        $order_type->name = $request->name;
        if ($request->file('icon')) {
            Helpers::delete_file('store/order_type/', $order_type->icon);
            $order_type->icon = Helpers::upload('store/order_type/', 'png', $request->file('icon'));
        }
        $order_type->save();

        Toastr::success('order_type_updated_successfully');
        return back();
    }
}
