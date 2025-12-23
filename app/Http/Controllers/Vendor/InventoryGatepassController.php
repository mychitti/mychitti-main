<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\InventoryGatepass;
use App\Models\InventoryGatepassItem;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryGatepassController extends Controller
{
    public function gatepass_delete(Request $request, $id)
    {
        $gp = InventoryGatepass::findOrFail($id);
        if ($gp->pdf) {
            Helpers::delete_file('inventory/gatepass/',  $gp->pdf);
        }
        $gp->delete();
        Toastr::success("Deleted Gatepass Successfully");
        return back();
    }
    public function store(Request $request)
    {
        // prx($request->all());
        $type = $request->type ?? 'purchase';
        $invoice = null;
        if ($request->invoice) {
            if ($type == 'purchase') {
                $request->validate([
                    'invoice_id' => [
                        'required',
                        Rule::exists('manual_invoices', 'invoice_id')
                            ->where(function ($q) {
                                $q->where('bill_to', Helpers::get_store_id())
                                    ->where('bill_to_type', 'vendor');
                            }),
                    ],
                ]);
            } else {
                $request->validate([
                    'invoice_id' => [
                        'required',
                        Rule::exists('manual_invoices', 'invoice_id')
                            ->where(function ($q) {
                                $q->where('vendor_id', Helpers::get_store_id());
                            }),
                    ],
                ]);
            }
            $invoice = ManualInvoice::where('invoice_id', $request->invoice_id)->first();
        }

        if ($request->staff_id == 'add_new') {
            $name = $request->name;
            $phone = $request->phone;
            $address =  $request->address;
        } else {
            $staff = VendorEmployee::where('store_id', Helpers::get_store_id())->where("id", $request->staff_id)->first();
            $name = $staff ? $staff->f_name . ' ' . $staff->l_name : '';
            $phone = $staff ? $staff->phone : '';
            $address = $staff ? ($staff->residential_address ?? $staff->permanent_address) : '';
        }
        if ($request->salesman_id == 'add_new') {
            $salesman_name = $request->salesman_name;
            $salesman_phone = $request->salesman_phone;
        } else {
            $staff = VendorEmployee::where('store_id', Helpers::get_store_id())->where("id", $request->salesman_id)->first();
            $salesman_name = $staff ? $staff->f_name . ' ' . $staff->l_name : '';
            $salesman_phone = $staff ? $staff->phone : '';
        }
        $driver_data = [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
      
            'salesman_name' => $salesman_name,
            'salesman_phone' => $salesman_phone,
        ];
        $docData = Helpers::generateInventoryGatepass($invoice, $driver_data, $type, $request);

        try {
            return redirect($docData['url']);
        } catch (\Throwable $th) {
            //
        }
    }
    public function return_store(Request $request)
    {
        // prx($request->all());
        $request->validate([
            'stock' => 'required|array',
        ]);

        $hasAtLeastOne = collect($request->stock)
            ->filter(fn($value) => (int) $value >= 1)
            ->isNotEmpty();

        if (!$hasAtLeastOne) {
            return back()->withErrors([
                'stock' => 'At least one item must have return stock greater than or equal to 1.',
            ])->withInput();
        }

        $gatepass = InventoryGatepass::find($request->gatepass_id);
        $gatepass->returned = 1;
        $gatepass->save();

        $return_items = [];
        if ($request->has('gp_item_id') && !empty($request->gp_item_id)) {
            foreach ($request->gp_item_id as $key => $value) {
                if ($request->stock[$key] != '' && $request->stock[$key] != 0) {
                    $gpItem = InventoryGatepassItem::where('id', $value)->first();
                    $gpItem->returned = 1;
                    $gpItem->return_stock = $request->stock[$key];
                    $gpItem->return_reason = $request->reason[$key];
                    $gpItem->save();
                }
            }
        }
        $docData = Helpers::generateInventoryReturnGatepass($gatepass);
        try {
            return redirect($docData['url']);
        } catch (\Throwable $th) {
            //
        }
    }
    public function return(Request $request, $id)
    {
        $gatepass = InventoryGatepass::find($id);
        $store = Helpers::get_store_data();
        $invoice = ManualInvoice::where("invoice_id", $gatepass->invoice_id)->first();
        $gp_items = InventoryGatepassItem::with('unitId')->where('gatepass_id', $gatepass->id)->get();
        // prx($invoice_items);
        return view('vendor-views.inventory.gatepass.return', compact('gatepass', 'gp_items', 'invoice', 'store'));
    }
    public function gatepass_list(Request $request, $tab)
    {
        $type = 'gp';
        switch ($tab) {

            case 'purchase':
                $gatepass = InventoryGatepass::with('invoice')->where('type', 'purchase')->where('store_id', Helpers::get_store_id())->get();
                break;
            case 'purchase-return':
                $type = 'return-gp';
                $gatepass = InventoryGatepass::with('invoice', 'gpItems')->where('type', 'purchase')->where('returned', 1)->where('store_id', Helpers::get_store_id())->get();
                break;
            case 'sale':
                $gatepass = InventoryGatepass::with('invoice')->where('type', 'sale')->where('store_id', Helpers::get_store_id())->get();
                break;
            case 'sale-return':
                $type = 'return-gp';
                $gatepass = InventoryGatepass::with('invoice', 'gpItems')->where('type', 'sale')->where('returned', 1)->where('store_id', Helpers::get_store_id())->get();
                break;
            default:
                $gatepass = collect(); // safe fallback
                break;
        }
        return view('vendor-views.inventory.gatepass.purchase', compact('gatepass', 'type'));
    }
}
