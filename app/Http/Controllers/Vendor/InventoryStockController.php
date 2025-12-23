<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\DTOs\StockEntry;
use App\Exports\InventoryEntryExport;
use App\Exports\InventoryItemExport;
use App\Imports\InvItemEntryImport;
use App\Imports\InvItemImport;
use App\Models\AcceptedServiceRequest;
use App\Models\AccountDropdownOption;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InvItemVariationDetail;
use App\Models\Item;
use App\Models\ItemEntry;
use App\Models\ManualInvoice;
use App\Models\PurchaseOrder;
use App\Models\ServiceInvoice;
use App\Models\ServiceRequest;
use App\Models\StorageUnit;
use App\Models\StoreCustomer;
use App\Models\SupplyOrder;
use App\Models\SupplyOrderItem;
use App\Models\TempInvItemImage;
use App\Models\Unit;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use DNS1D;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;


class InventoryStockController extends Controller
{
    public function stock_in_out(Request $request)
    {
        $storeId = Helpers::get_store_id();

        $preset  = $request->input('date_range', 'last_30_days');
        $custom  = $request->input('custom_date_range');
        $range   = Helpers::calculatePresetDates($preset, $custom);

        $formatted_from = $range['start'];
        $formatted_to   = $range['end'];

        // Stock-in: Supply Order Items
        $supply_order_items = SupplyOrderItem::with(['order.invoice', 'item'])
            ->whereHas('order', fn($q) => $q->where('store_vendor_id', $storeId))
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->get()
            ->map(fn($item) => StockEntry::fromSupplyOrderItem($item));

        // Stock-in: Item Entries
        $entries = ItemEntry::with(['item', 'invoice'])
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->get()
            ->map(fn($item) => StockEntry::fromItemEntry($item));

        // Stock-out: Inventory Orders
        $inventory_orders = InventoryOrderDetail::with(['order.invoice', 'item'])
            ->whereHas('order', fn($q) => $q->where('store_id', $storeId))
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->get()
            ->map(fn($item) => StockEntry::fromInventoryOrderDetail($item));

        // Merge all and sort by date
        $rows = collect()
            ->merge($supply_order_items)
            ->merge($entries)
            ->merge($inventory_orders)
            ->sortBy('date')
            ->values(); // reset keys

        return view("vendor-views.inventory.stock.stock_in_out", compact('preset', 'rows'));
    }
}
