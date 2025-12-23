<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AccountOption;
use App\Models\AssetAlotment;
use App\Models\InventoryItem;
use App\Models\StoreAsset;
use App\Models\StoreCustomer;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

class AssetsController extends Controller
{
    public function index(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $inventory_items = InventoryItem::where('store_id', $store_id)
            ->where(function ($q) {
                $q->where('is_asset', '!=', 1)
                    ->orWhereNull('is_asset');
            })
            ->where('stock' , '>',0)
            ->get();
        $categories = AccountOption::where('store_id', $store_id)->where('type', 'asset_category')->get();
        $assets = StoreAsset::with('inventoryItem')->where('store_id', $store_id)->paginate(10);
        $asset_alotments = AssetAlotment::with('inventoryItem')->where('store_id', $store_id)->paginate(10);
        $staff = VendorEmployee::where('store_id', $store_id)->get();
        return view('vendor-views.assets.index', compact('assets', 'categories', 'inventory_items', 'staff', 'asset_alotments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id' => 'required',
        ]);
        $store_id = Helpers::get_store_id();
        $inventoryItem = InventoryItem::findOrFail($request->item_id);

        $requestQty = $request->qty ?? 1;
        if ($inventoryItem->stock < $requestQty) {
            Toastr::error("Only " . $inventoryItem->stock . ' In Stock');
            return back();
        }
        $debit_account = Helpers::ensureFixedAssetAccount($request->category);
        $credit_account = Helpers::ensureInventoryAccount();
        
        $data = [
            'date' => now(),
            'amount' => $inventoryItem->landing_price * $requestQty,
            'voucher_type' => 'Journal',
            'status' => 'approved',
        ];
        $amount = $inventoryItem->landing_price * $requestQty;
        _masterLedgerEntry( $data, $credit_account, $debit_account,'','',  null, $store_id);

        $asset = new StoreAsset();
        $asset->store_id = Helpers::get_store_id();
        $asset->inventory_item_id = $request->item_id;
        $asset->current_value = $inventoryItem->landing_price;
        $asset->cost = $inventoryItem->landing_price;
        $asset->quantity = $requestQty;
        $asset->alotted_quantity = 0;
        $asset->category = $request->category;
        $asset->ledger_account_id = $debit_account->id;
        $asset->depreciation_method = $request->depreciation_method;
        $asset->useful_life_count = $request->useful_life_count;
        $asset->useful_life_unit = $request->useful_life_unit;
        $asset->save();

        // category
        AccountOption::firstOrCreate(
            [
                'store_id' => Helpers::get_store_id(),
                'name' => $request->category,
                'type' => 'asset_category'
            ]
        );

        $inventoryItem->stock = $inventoryItem->stock - $requestQty;
        $inventoryItem->is_asset = 1;
        $inventoryItem->save();

        Toastr::success("Asset Saved Successfully");
        return back();
    }
    public function alotToStaff(Request $request)
    {
        $request->validate([
            'staff_id' => 'required',
        ]);
        $store_id = Helpers::get_store_id();
        $requestQty = $request->qty ?? 1;

        $inventoryItem = InventoryItem::findOrFail($request->item_id);

        $asset = StoreAsset::where('inventory_item_id', $request->item_id)->first();

        $alottedQty = AssetAlotment::where('store_id', $store_id)
            ->where('inventory_item_id', $request->item_id)
            ->sum('alotted_qty');

        $leftQty = $asset->quantity - $alottedQty;

        if ($requestQty > $leftQty) {
            Toastr::error("Only " . $leftQty . ' In Stock');
            return back();
        }

        $asset->alotted_quantity = $asset->alotted_quantity +  $requestQty;
        $asset->save();

        if ($request->has('staff_id') && $request->staff_id) {

            $asset_alot = AssetAlotment::where('employee_id', $request->staff_id)
                ->where('inventory_item_id', $request->item_id)
                ->first();
            if ($asset_alot) {
                $asset_alot->alotted_qty =  $asset_alot->alotted_qty + $requestQty;
            } else {
                $asset_alot = new AssetAlotment();
                $asset_alot->store_id = Helpers::get_store_id();
                $asset_alot->asset_id = $asset->id;
                $asset_alot->employee_id = $request->staff_id;
                $asset_alot->inventory_item_id = $request->item_id;
                $asset_alot->alotted_qty = $requestQty;
                $asset_alot->file_when_alotted = $request->has('file') ?  Helpers::upload('assets-alot/', 'png', $request->file('file')) : null;
                $asset_alot->condition_when_alotted = $request->condition;
                $asset_alot->save();
            }
        }

        Toastr::success("Alotted Successfully");
        return back();
    }
    public function return_asset(Request $request)
    {
        // prx($request->all());
        $assetAl  = AssetAlotment::find($request->al_id);
        $assetAl->returned = 1;
        $assetAl->returned_at = NOW();
        $assetAl->file_when_returned = $request->has('file') ?  Helpers::upload('assets-alot/', 'png', $request->file('file')) : null;
        $assetAl->condition_when_returned = $request->condition;
        $assetAl->save();

        $asset = StoreAsset::find($assetAl->asset_id);
        $asset->alotted_quantity = $asset->alotted_quantity - $assetAl->alotted_qty;
        $asset->save();

        Toastr::success("Return Saved Successfully");
        return back();
    }
    public function get_alotment_details(Request $request)
    {
        $id = $request->id;
        $assetAl = AssetAlotment::with("inventoryItem")->where('id', $id)->first();
        $html = ' <table class="borderless">
                                 <tbody>
                                     <tr>
                                         <th><b>When Alotted: </b></th>
                                         <td style="padding:10px;">
                                             <img class="avatar avatar-lg mr-3 onerror-image"
                                                 src="' . Helpers::onerror_image_helper(
            $assetAl->file_when_alotted,
            asset('storage/app/public/assets-alot/') . '/' . $assetAl->file_when_alotted,
            asset('public/assets/admin/img/160x160/img2.jpg'),
            'assets-alot/'
        ) . '"
                                                 data-onerror-image="' . asset('public/assets/admin/img/160x160/img2.jpg') . '"
                                                 alt=" image">
                                         </td>
                                         <td>' . $assetAl->condition_when_alotted . '</td>
                                     </tr>
                                     <tr>
                                         <th><b>When Returned: </b></th>
                                         <td style="padding:10px;"> <img class="avatar avatar-lg mr-3 onerror-image"
                                                 src="' . Helpers::onerror_image_helper(
            $assetAl->file_when_returned,
            asset('storage/app/public/assets-alot/') . '/' . $assetAl->file_when_returned,
            asset('public/assets/admin/img/160x160/img2.jpg'),
            'assets-alot/'
        ) . '"
                                                 data-onerror-image="' . asset('public/assets/admin/img/160x160/img2.jpg') . '"
                                                 alt=" image"></td>
                                         <td>' . $assetAl->condition_when_returned . '</td>
                                     </tr>
                                 </tbody>
                             </table>';
        echo $html;
    }
    public function alotted_assets(Request $request)
    {
        $assets = AssetAlotment::with('inventoryItem')->where('employee_id', Helpers::get_loggedin_user()->id)->paginate(10);
        // prx($assets);
        return view('vendor-views.assets.alotted', compact('assets'));
    }
    public function delete(Request $request, $id)
    {
        $asset = StoreAsset::find($id);

        $inventory_item = InventoryItem::find($asset->inventory_item_id);
        $inventory_item->is_asset = 0;
        $inventory_item->save();

        $asset->delete();

        Toastr::success("Deleted Saved Successfully");
        return back();
    }
}
