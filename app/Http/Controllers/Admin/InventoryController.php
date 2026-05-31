<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
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
use App\Models\StorageUnit;
use App\Models\StoreTnc;
use App\Models\TempInvItemImage;
use App\Models\VendorTermsCondition;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;

class InventoryController extends Controller
{
    /**
     * Use store_id 0 for admin inventory.
     */
    protected function getStoreId(): int
    {
        return 0;
    }

    public function dashboard(Request $request)
    {
        $preset = request('date_range') ?? 'last_30_days';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $storeId = $this->getStoreId();

        $inventory_products = InventoryItem::where('store_id', $storeId)
            ->where('item_type', 'product');
        $items = InventoryItem::where('store_id', $storeId)->get();

        $inventory_services = InventoryItem::where('store_id', $storeId)
            ->where('item_type', 'service');
        $data['totalStockValue'] = $items->sum(function ($item) {
            return $item->stock * $item->selling_price;
        });

        $data['product_count'] = (clone $inventory_products)->count();
        $data['product_stock'] = (clone $inventory_products)->sum('stock');

        $data['service_count'] = (clone $inventory_services)->count();
        $data['service_stock'] = (clone $inventory_services)->sum('stock');

        $data['low_stock_product'] = (clone $inventory_products)->where('stock', '<', 5)->count();
        $data['low_stock_services'] = (clone $inventory_services)->where('stock', '<', 5)->count();

        $data['entries'] = ItemEntry::with('item')
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->limit(5)
            ->get();

        $orders = InventoryOrder::with(['details.item.category'])
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$formatted_from, $formatted_to]);

        $data['top_sell'] = InventoryOrderDetail::with('item')
            ->whereHas('order', function ($q) use ($storeId, $formatted_from, $formatted_to) {
                $q->where('store_id', $storeId)
                    ->whereBetween('created_at', [$formatted_from, $formatted_to]);
            })
            ->whereHas('item')
            ->select('item_id', DB::raw('SUM(qty) as total_sold'))
            ->groupBy('item_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        $data['top_categories'] = InventoryOrderDetail::whereHas('order', function ($q) use ($storeId, $formatted_from, $formatted_to) {
            $q->where('store_id', $storeId)
                ->whereBetween('created_at', [$formatted_from, $formatted_to]);
        })
            ->join('inventory_items', 'inventory_items.id', '=', 'inventory_order_details.item_id')
            ->join('categories', 'categories.id', '=', 'inventory_items.category_id')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('SUM(inventory_order_details.qty) as total_sold')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        $data['total_order_cost'] = $orders->sum('total_amount');
        $data['total_tax_amount'] = $orders->sum('tax_amount');

        $data['total_ordered'] = $orders->clone()
            ->withSum('details as total_qty', 'qty')
            ->get()
            ->sum('total_qty');

        $leadsQ = AcceptedServiceRequest::query()
            ->where('accepted_service_requests.vendor_id', $storeId)
            ->join('service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
            ->whereBetween('service_requests.created_at', [$formatted_from, $formatted_to]);

        $data['leads_in_progress'] = (clone $leadsQ)
            ->whereNotNull('accepted_service_requests.current_status')
            ->whereNotIn('accepted_service_requests.current_status', ['Completed', 'Cancelled'])
            ->count('accepted_service_requests.id');

        $data['leads_to_be_invoiced'] = (clone $leadsQ)
            ->leftJoin('service_invoices', 'service_invoices.service_id', '=', 'service_requests.id')
            ->whereNull('service_invoices.id')
            ->where('accepted_service_requests.current_status', 'Completed')
            ->count('accepted_service_requests.id');

        $sales = Helpers::inv_order_calendar();

        return view('admin-views.inventory.dashboard', compact('sales', 'preset', 'data', 'items', 'sales'));
    }

    public function settings_save(Request $request)
    {
        $storeId = $this->getStoreId();

        DB::table('vendor_terms_conditions')
            ->updateOrInsert(
                [
                    'vendor_id' => $storeId,
                    'type'      => 'purchase_order'
                ],
                [
                    'terms_n_conditons' => $request->content,
                    'updated_at'        => now(),
                    'created_at'        => now(),
                ]
            );

        Toastr::success('Saved Successfully');
        return back();
    }

    public function settings(Request $request)
    {
        $tnc = VendorTermsCondition::where('vendor_id', $this->getStoreId())
            ->where('type', 'purchase_order')
            ->first();
        return view('admin-views.inventory.settings', compact('tnc'));
    }

    public function items_import(Request $request)
    {
        $file = $request->file('file');
        $import = new InvItemImport($this->getStoreId());
        Excel::import($import, $file);

        if (!empty($import->failedRows)) {
            foreach ($import->failedRows as $error) {
                Toastr::error('Row ' . $error['row'] . ': ' . $error['reason']);
            }
        }

        Toastr::success('Excel file imported successfully.');
        return redirect()->back();
    }

    public function entry_import(Request $request)
    {
        $file = $request->file('file');
        $import = new InvItemEntryImport($this->getStoreId());
        Excel::import($import, $file);

        if (!empty($import->failedRows)) {
            foreach ($import->failedRows as $error) {
                Toastr::error('Row ' . $error['row'] . ': ' . $error['reason']);
            }
        }

        Toastr::success('Excel file imported successfully.');
        return redirect()->back();
    }

    public function item_export_selected(Request $request)
    {
        return $this->item_export($request, $request->item_ids);
    }

    public function item_export(Request $request, $ids = null)
    {
        $items = InventoryItem::with('storage_unit')
            ->when($ids, function ($query) use ($ids) {
                $query->whereIn('id', $ids);
            })
            ->where('store_id', $this->getStoreId())
            ->get();

        $headings = [
            'sl',
            'ID',
            'Name',
            'SKU ID',
            'GST(%)',
            'GST Type',
            'MRP',
            'Selling Price',
            'Stock',
            'Storage Unit',
            'Variation Name',
            'Variation MRP',
            'Variation Selling Price',
            'Variation Purchase Price',
            'Variation Stock',
            'Model Number',
            'Brand',
            'Category',
            'HSN',
            'Created At'
        ];

        $data = [];
        $sl = 1;

        foreach ($items as $item) {
            $variations = json_decode($item->variations, true);
            $firstVariation = true;

            if (!empty($variations)) {
                foreach ($variations as $var) {
                    $data[] = [
                        $firstVariation ? $sl : '',
                        $firstVariation ? $item->id : '',
                        $firstVariation ? ucwords($item->item_name) : '',
                        $firstVariation ? $item->sku_id : '',
                        $firstVariation ? $item->gst_rate : '',
                        $firstVariation ? translate($item->gst_type) : '',
                        $firstVariation ? $item->mrp : '',
                        $firstVariation ? $item->selling_price : '',
                        $firstVariation ? $item->stock : '',
                        $firstVariation ? $item->storage_unit?->unit : '',
                        ucwords($var['type']) ?? '',
                        $var['mrpprice'] ?? '',
                        $var['askingprice'] ?? '',
                        $var['purchaseprice'] ?? '',
                        $var['stock'] ?? '',
                        $firstVariation ? $item->model_number : '',
                        $firstVariation ? $item->brand : '',
                        $firstVariation ? $item->category_name : '',
                        $firstVariation ? $item->hsn : '',
                        $firstVariation ? $item->created_at : '',
                    ];
                    $firstVariation = false;
                }
            } else {
                $data[] = [
                    $sl,
                    $item->id,
                    $item->item_name,
                    $item->sku_id,
                    $item->gst_rate,
                    translate($item->gst_type),
                    $item->mrp,
                    $item->selling_price,
                    $item->stock,
                    $item->storage_unit?->unit,
                    '',
                    '',
                    '',
                    '',
                    '',
                    $item->model_number,
                    $item->brand,
                    $item->category_name,
                    $item->hsn,
                    $item->created_at,
                ];
            }

            $sl++;
        }

        return Excel::download(new InventoryItemExport($data, $headings), 'Inventory_Items_Admin.xlsx');
    }

    public function entry_export_selected(Request $request)
    {
        return $this->entry_export($request, $request->item_ids);
    }

    public function entry_export(Request $request, $ids = null)
    {
        $entries = ItemEntry::with('item')
            ->when($ids, function ($query) use ($ids) {
                $query->whereIn('id', $ids);
            })
            ->where('store_id', $this->getStoreId())
            ->get();

        $headings = [
            'sl',
            'Date',
            'Bill No',
            'Item Name',
            'Variation Type',
            'Stock',
            'Product Condition',
            'Purchase Price',
            'Purchase Price GST',
            'Selling Price',
            'Created At'
        ];

        $data = [];

        foreach ($entries as $key => $entry) {
            $data[] = [
                $key + 1,
                $entry->date,
                $entry->bill_number,
                $entry->item?->item_name,
                $entry->variation,
                $entry->quantity,
                $entry->product_condition,
                $entry->landing_price,
                $entry->price_gst_status,
                $entry->selling_price,
                $entry->created_at
            ];
        }

        return Excel::download(new InventoryEntryExport($data, $headings), 'Inventory_Entries_Admin.xlsx');
    }

    public function item_images_store(Request $request)
    {
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $file) {
                $image = Helpers::upload('inventory-item/', 'png', $file);

                TempInvItemImage::create([
                    'store_id' => $this->getStoreId(),
                    'image'    => $image,
                ]);
            }
        }
        Toastr::success('Image Uploaded Successfully');
        return back();
    }

    public function item_images(Request $request)
    {
        $item_images = TempInvItemImage::where('store_id', $this->getStoreId())->paginate(50);
        return view('admin-views.inventory.item_images', compact('item_images'));
    }

    public function entry_bulk_delete(Request $request)
    {
        $items = ItemEntry::whereIn('id', $request->item_ids)->get();
        foreach ($items as $item) {
            $item->delete();
        }
    }

    public function item_bulk_delete(Request $request)
    {
        $items = InventoryItem::whereIn('id', $request->item_ids)->get();
        foreach ($items as $item) {
            $item->delete();
        }
    }

    public function item_delete(Request $request, $id)
    {
        InventoryItem::where('id', $id)->delete();
        Toastr::success('Deleted Successfully');
        return back();
    }

    public function inventory_management(Request $request, $tab = null)
    {
        $preset = request('date_range') ?? 'last_30_days';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $filter  = $request->filter ?? '';
        $search  = $request->search ?? '';
        $type  = $request->type ?? '';

        $categories = Category::where(['position' => 0])
            ->module(6)
            ->get();

        $storage_units = StorageUnit::with('parent')
            ->where('store_id', $this->getStoreId())
            ->get();

        $inventory_items = InventoryItem::with('entries')
            ->where('store_id', $this->getStoreId())
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('item_name', 'like', "%{$search}%")
                        ->orWhere('sku_id', 'like', "%{$search}%");
                });
            })
            ->when($type, function ($query) use ($type) {
                $query->where(function ($q) use ($type) {
                    $q->where('item_type', $type);
                });
            })
            ->when($filter, function ($query) {
                $query->where('stock', '<', 5);
            })
            ->orderBy('id', 'desc')
            ->paginate(50);

        $variations = AccountDropdownOption::where('type', 'item_variation')
            ->where('store_id', $this->getStoreId())
            ->get();

        $items = InventoryItem::where('inventory_items.store_id', $this->getStoreId())->get();

        $entries = ItemEntry::with('item')
            ->when($search, function ($query) use ($search) {
                $query->whereHas('item', function ($q) use ($search) {
                    $q->where('item_name', 'like', "%{$search}%")
                        ->orWhere('sku_id', 'like', "%{$search}%");
                });
            })
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->where('store_id', $this->getStoreId())
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin-views.inventory.inventory_management', compact('tab', 'preset', 'categories', 'entries', 'storage_units', 'inventory_items', 'items', 'variations'));
    }

    public function get_item_info(Request $request)
    {
        return InventoryItem::find($request->id);
    }

    public function edit_item(Request $request, $id)
    {
        $item = InventoryItem::find($id);
        $items = InventoryItem::where('inventory_items.store_id', $this->getStoreId())->get();

        $categories = Category::where(['position' => 0])
            ->module(6)
            ->get();
        $subcategories = Category::where(['position' => 1, 'parent_id' => $item->category_id])
            ->module(6)
            ->get();
        $variations = AccountDropdownOption::where('type', 'item_variation')
            ->where('store_id', $this->getStoreId())
            ->get();
        $storage_units = StorageUnit::with('parent')
            ->where('store_id', $this->getStoreId())
            ->get();

        return view('admin-views.inventory.item_edit', compact('items', 'categories', 'item', 'storage_units', 'subcategories', 'variations'));
    }

    public function entries(Request $request)
    {
        $entries = ItemEntry::where('store_id', $this->getStoreId())->get();
        $items = InventoryItem::where('store_id', $this->getStoreId())->get();

        return view('admin-views.inventory.item_entry', compact('entries', 'items'));
    }

    public function get_stands(Request $request)
    {
        echo 'fd';
    }

    public function variation_store(Request $request)
    {
        $vr = new AccountDropdownOption();
        $vr->type = 'item_variation';
        $vr->name = $request->name;
        $vr->store_id = $this->getStoreId();
        $vr->save();

        Toastr::success('Saved Successfully');
        return back();
    }

    public function get_my_fee_amount(Request $request)
    {
        $inventory_item = InventoryItem::find($request->item_id);
        if ($inventory_item) {
            return [
                'my_fee' => $inventory_item->my_fee,
                'fee_type' => $inventory_item->my_fee_type,
                'discount_type' => 'amount',
                'discount_value' => $inventory_item->discount_amount,
                'gst_percent' => $inventory_item->gst_rate
            ];
        } else {
            return ['my_fee' => 0, 'fee_type' => ''];
        }
    }

    public function save_entry(Request $request)
    {
        $request->validate([
            'item_id.*' => 'required|exists:inventory_items,id',
            'quantity.*' => 'required|numeric|min:0',
        ]);

        foreach ($request->item_id as $key => $item_id) {
            $inventory_item = InventoryItem::find($item_id);

            $selling_price = $request->selling_price[$key];
            $taxable_amount = 0;

            $kg_input = (int) $request->quantity[$key];
            $bag_capacity = $inventory_item->primary_qty ?? 1;
            $bags_from_kgs = intdiv($kg_input, $bag_capacity);
            $spare_kgs     = $kg_input % $bag_capacity;
            $grand_total_kg = $kg_input;

            $entry = new ItemEntry();
            $entry->store_id = $this->getStoreId();
            $entry->date = $request->date;
            $entry->item_id = $request->item_id[$key];
            $entry->variation_type = $request->variation_type[$key] ?? null;
            $entry->storage_unit = $request->storage_unit_id[$key] ?? null;
            $entry->discount_amount = $inventory_item->mrp -  $selling_price;
            $entry->quantity = $grand_total_kg;
            $entry->primary_quantity = $spare_kgs;
            $entry->secondary_quantity = $bags_from_kgs;
            $entry->landing_price = $request->landing_price[$key];
            $entry->price_gst_status = $request->price_gst_status[$key];
            $entry->bill_number = $request->bill_number;
            $entry->total_amount = $selling_price * $request->quantity[$key];
            $entry->product_condition = $request->product_conditon[$key];
            $entry->selling_price = $selling_price;
            $entry->taxable_amount = $taxable_amount;
            $entry->save();

            $old_stock = $inventory_item->stock;
            $inventory_item->stock = $old_stock + $request->quantity[$key];
            $inventory_item->selling_price = $selling_price;
            $inventory_item->save();

            \App\Models\Item::withoutGlobalScopes()
                ->where('inventory_item_id', $inventory_item->id)
                ->update(['price' => $selling_price]);
        }

        Toastr::success('Saved Successfully');
        return back();
    }

    public function print(Request $request, $item_id, $type)
    {
        $item = InventoryItem::findOrFail($item_id);
        if (!$item->sku_id && !$item->barcode) {
            Toastr::error('Item SKU Required to print barcode');
            return back();
        }

        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'tempDir' => $tempDir,
            'format' => 'A4',
            'margin_left'   => 0,
            'margin_right'  => 0,
            'margin_top'    => 0,
            'margin_bottom' => 0,
        ]);

        $pageWidth  = $mpdf->w;
        $pageHeight = $mpdf->h;

        if ($type == 'barcode') {
            $labelWidth = 38.313;
            $labelHeight = 30.908;
        } else if ($type == 'description') {
            $labelWidth = 58.313;
            $labelHeight = 35.908;
        } else {
            $labelWidth = 58.313;
            $labelHeight = 65.908;
        }

        $labelsPerRow = floor($pageWidth / $labelWidth);
        $rowsPerPage  = floor($pageHeight / $labelHeight);

        if ($type == 'barcode') {
            $html = View::make('admin-views.inventory.pdf_templates.label_barcode', compact('item', 'labelsPerRow', 'rowsPerPage', 'labelWidth', 'labelHeight'));
        } elseif ($type == 'description') {
            $html = View::make('admin-views.inventory.pdf_templates.label_description', compact('item', 'labelsPerRow', 'rowsPerPage', 'labelWidth', 'labelHeight'));
        } else {
            $html = View::make('admin-views.inventory.pdf_templates.label_full', compact('item', 'labelsPerRow', 'rowsPerPage', 'labelWidth', 'labelHeight'));
        }
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('', 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="labels_' . $item->item_name . '.pdf"');
    }

    public function update_item(Request $request)
    {
        $validator = FacadesValidator::make($request->all(), [
            'item_name'            => 'required|string|max:255',
            'mrp'                  => 'required',
            'unit'                 => 'required',
            'gst_rate'             => 'required|numeric|min:0',
            'item_type'            => 'required',
            'category'             => 'required',
        ]);
        $inventory_item =  InventoryItem::findOrFail($request->item_id);

        $labels =  $request->header_label ?? [];
        $fields = $request->header_field ?? [];

        $custom_attr = [];
        $show_on_store_page = (isset($request->show_on_store_page) &&  $request->show_on_store_page)  ? 1 : 0;

        foreach ($labels as $index => $label) {
            $field = $fields[$index] ?? null;
            $custom_attr[$label] = $field;
        }

        if ($request->has('image')) {
            $main_image = Helpers::upload('inventory-item/', 'png', $request->file('image'));
            if ($show_on_store_page) {
                Helpers::upload('product/', 'png', $request->file('image'), $main_image);
            }
        } else {
            $main_image = $inventory_item->image;
        }

        if ($request->has('barcode')) {
            $barcode =  Helpers::upload('inventory-item/barcode/', 'png', $request->file('barcode'));
        } else {
            $barcode = $inventory_item->barcode;
        }

        $images = $inventory_item->images ?? [];
        if (!empty($request->file('item_images'))) {
            foreach ($request->item_images as $img) {
                $image_name = Helpers::upload('inventory-item/', 'png', $img);
                if ($show_on_store_page) {
                    $image_name = Helpers::upload('product/', 'png', $img, $image_name);
                }
                $images[] = $image_name;
            }
        }

        $secondary_qty =  $request->secondary_qty ?? 1;
        $total_stock = $request->main_opening_stock;
        $primary_stock = $request->primary_qty / $secondary_qty;
        $secondary_stock = 1;

        $category_id = Helpers::_saveCategoryIfNotExists($request->category);

        $unitInput = $request->unit;
        $inv_unit = _saveUnitIfNotExist($unitInput);

        $inventory_item->item_type = $request->item_type;
        $inventory_item->item_name = $request->item_name;
        $inventory_item->image = $main_image;
        $inventory_item->images =  $images;
        $inventory_item->barcode =  $barcode;
        $inventory_item->brand = $request->brand;
        $inventory_item->model_number = $request->model_number;
        $inventory_item->sku_id =  $request->sku_id;
        $inventory_item->attributes = $request->has('attribute_id') ? json_encode($request->attribute_id) : $inventory_item->attributes;
        $inventory_item->category_id = $category_id;
        $inventory_item->unit = $inv_unit;
        $inventory_item->mrp = $request->main_mrp;
        $inventory_item->gst_rate = $request->gst_rate ?? 0;
        $inventory_item->gst_type = $request->gst_type;
        $inventory_item->hsn = $request->hsn;
        $inventory_item->my_fee = $request->my_fee ?? 0;
        $inventory_item->show_on_store_page = $show_on_store_page;
        $inventory_item->my_fee_type = $request->my_fee_type ?? 'amount';
        $inventory_item->custom_attributes = json_encode($custom_attr);

        if ($request->item_type == 'product') {
            $inventory_item->product_condition = $request->product_condition;
            $inventory_item->product_note = $request->product_note;
        }
        $inventory_item->landing_price = $request->main_landing_price;
        $inventory_item->selling_price = $request->main_selling_price;
        $inventory_item->storage_unit_id = $request->storage_unit_id;
        $inventory_item->description = $request->description;
        $specifications = isset($request->specifications) ? urldecode(base64_decode($request->specifications)) : null;

        $inventory_item->specifications = $specifications;
        $inventory_item->save();

        $choice_options = [];
        if ($request->has('choice')) {
            foreach ($request->choice_no as $key => $no) {
                $str = 'choice_options_' . $no;
                if ($request[$str][0] == null) {
                    $validator->getMessageBag()->add('name', translate('messages.attribute_choice_option_value_can_not_be_null'));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                $item['name'] = 'choice_' . $no;
                $item['title'] = $request->choice[$key];
                $item['options'] = explode(',', implode('|', preg_replace('/\s+/', ' ', $request[$str])));
                array_push($choice_options, $item);
            }
            $inventory_item->choice_options = json_encode($choice_options);
        }
        $inventory_item->save();

        $variations = [];
        $options = [];
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('|', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }

        $combinations = Helpers::combinations($options);
        if (count($combinations[0]) > 0) {
            $total_stock = 0;
            $primary_stock = 0;
            $secondary_stock = 0;

            foreach ($combinations as $key => $combination) {
                $str = '';
                foreach ($combination as $k => $item) {
                    if ($k > 0) {
                        $str .= '-' . str_replace(' ', '', $item);
                    } else {
                        $str .= str_replace(' ', '', $item);
                    }
                }
                $vrImages = [];
                if (!empty($request->file('imgs_' .  str_replace('.', '_', $str)))) {
                    $keyImg = 'imgs_' . str_replace('.', '_', $str);
                    if ($request->hasFile($keyImg)) {
                        foreach ($request->file($keyImg) as $img) {
                            $image_name = Helpers::upload('inventory-variations/', 'png', $img);
                            if ($show_on_store_page) {
                                Helpers::upload('product/', 'png', $img, $image_name);
                            }
                            $vrImages[] = $image_name;
                        }
                    }
                }
                $vrDetails = new InvItemVariationDetail();
                $vrDetails->description = $request['descs_' . str_replace('.', '_', $str)];
                $vrDetails->specifications = isset($request['specs_' . str_replace('.', '_', $str)]) ? urldecode(base64_decode($request['specs_' . str_replace('.', '_', $str)])) : null;
                $vrDetails->images = json_encode($vrImages);
                $vrDetails->type = $str;
                $vrDetails->item_id = $inventory_item->id;
                $vrDetails->sku = $request['sku_' . str_replace('.', '_', $str)];
                $vrDetails->save();

                $item = [];

                $input_kgs = abs($request['stock_' . str_replace('.', '_', $str)]);
                if ($input_kgs) {
                    $bag_capacity = $inventory_item->primary_qty;
                    $bags_from_kgs = $bag_capacity ? intdiv($input_kgs, $bag_capacity) : null;
                    $spare_kgs  = $bag_capacity ? $input_kgs % $bag_capacity : $input_kgs;
                    $total_bags = $bags_from_kgs;
                }

                $item['type'] = $str;
                $item['price'] = abs($request['askingprice_' . str_replace('.', '_', $str)]);
                $item['mrpprice'] = abs($request['mrpprice_' . str_replace('.', '_', $str)]);
                $item['askingprice'] =  0;
                $item['purchaseprice'] =  abs($request['purchaseprice_' . str_replace('.', '_', $str)]);
                $item['discount_percent'] = 0;
                $item['stock'] = $input_kgs ?? 0;
                $item['primary_qty'] = $spare_kgs ?? 0;
                $item['secondary_qty'] = $total_bags ?? 0;
                $item['variations_table_id'] = $vrDetails->id;

                $total_stock += $input_kgs ?? 0;
                array_push($variations, $item);
            }
            $inventory_item->variations =  json_encode($variations);
        }

        $inventory_item->stock =  $total_stock;
        $inventory_item->save();

        if ($show_on_store_page) {
            Helpers::_copyToItemTable($inventory_item, true);
        }

        if ($request->form_type == 'ajax') {
            return response()->json(['status' => true, 'msg' => "Updated  Successfully", 'action' => 'edit_inventory', 'item_id' => $inventory_item->id]);
        } else {
            Toastr::success('Updated Successfully');
            return back();
        }
    }

    public function remove_item_image(Request $request, $id, $image)
    {
        $inv_item = InventoryItem::findOrFail($id);

        $images = $inv_item->images ?? [];
        $images = array_filter($images, function ($img) use ($image) {
            return $img !== $image;
        });

        $images = array_values($images);
        $inv_item->images = $images;
        $inv_item->save();
        Toastr::success('Image removed successfully');
        return back();
    }

    public function save_item(Request $request)
    {
        $rules = [
            'item_name' => 'required|string|max:255',
            'item_type' => 'required',
            'category'  => 'required',
        ];

        if ($request->item_type !== 'service') {
            $rules = array_merge($rules, [
                'mrp'           => 'required',
                'unit'          => 'required',
                'gst_rate'      => 'required|numeric|min:0',
                'selling_price' => 'required|numeric|min:0',
            ]);
        }

        $validator = FacadesValidator::make($request->all(), $rules);

        $images = [];
        $show_on_store_page = (isset($request->show_on_store_page) &&  $request->show_on_store_page)  ? 1 : 0;
        if ($show_on_store_page && !$request->category) {
            if ($request->form_type == 'ajax') {
                return response()->json(['status' => false, 'msg' => "Category required if show on store page", 'action' => 'add_inventory', 'item_id' => null]);
            } else {
                Toastr::error('Category required if show on store page');
                return back();
            }
        }

        if (!empty($request->file('item_images'))) {
            foreach ($request->item_images as $img) {
                $image_name = Helpers::upload('inventory-item/', 'png', $img);
                if ($show_on_store_page) {
                    $image_name = Helpers::upload('product/', 'png', $img, $image_name);
                }
                $images[] = $image_name;
            }
        }

        if ($request->category) {
            $category_id = Helpers::_saveCategoryIfNotExists($request->category);
        } else {
            $category_id = 0;
        }
        $inventory_item = new InventoryItem();

        $secondary_qty =  $request->secondary_qty ?? 1;

        $total_stock = $request->main_opening_stock;

        $primary_stock = $request->primary_qty / $secondary_qty;
        $secondary_stock = 1;

        if ($request->item_type == 'product') {
            $labels =  $request->header_label ?? [];
            $fields = $request->header_field ?? [];
            $custom_attr = [];
            foreach ($labels as $index => $label) {
                $field = $fields[$index] ?? null;
                $custom_attr[$label] = $field;
            }

            $unitInput = $request->primary_unit;
            $inv_unit = _saveUnitIfNotExist($unitInput);
            $secondary_unit = _saveUnitIfNotExist($request->secondary_unit);

            $inventory_item->barcode =  $request->has('barcode') ? Helpers::upload('inventory-item/barcode/', 'png', $request->file('barcode')) :  null;
            $inventory_item->brand = $request->brand;
            $inventory_item->model_number = $request->model_number;
            $inventory_item->sku_id =  $request->sku_id;
            $inventory_item->attributes = $request->has('attribute_id') ? json_encode($request->attribute_id) : json_encode([]);
            $inventory_item->unit = $inv_unit;
            $inventory_item->primary_qty = $primary_stock;
            $inventory_item->secondary_unit = $secondary_unit;
            $inventory_item->secondary_qty = $secondary_stock;
            $inventory_item->mrp = $request->main_mrp;
            $inventory_item->gst_rate = $request->gst_rate ?? 0;
            $inventory_item->gst_type = $request->gst_type;
            $inventory_item->hsn = $request->hsn;
            $inventory_item->my_fee = $request->my_fee ?? 0;
            $inventory_item->my_fee_type = $request->my_fee_type ?? 'amount';
            $inventory_item->custom_attributes = json_encode($custom_attr);
            $inventory_item->product_condition = $request->product_condition;
            $inventory_item->product_note = $request->product_note;
            $inventory_item->landing_price = $request->main_landing_price;
            $inventory_item->selling_price = $request->main_selling_price;
            $inventory_item->storage_unit_id = $request->storage_unit_id;
            $inventory_item->description = $request->description;
            $specifications = isset($request->specifications) ? urldecode(base64_decode($request->specifications)) : null;
            $inventory_item->specifications = $specifications;
        }

        $inventory_item->show_on_store_page = $show_on_store_page;
        $inventory_item->store_id = $this->getStoreId();
        $inventory_item->module_id = 6;
        $inventory_item->item_type = $request->item_type;
        $inventory_item->item_name = $request->item_name;
        $inventory_item->image =  $request->has('image') ? Helpers::upload('inventory-item/', 'png', $request->file('image')) :  null;
        $inventory_item->images =  $images;
        $inventory_item->category_id = $category_id;

        if ($request->has('image') && $show_on_store_page) {
            Helpers::upload('product/', 'png', $request->file('image'), $inventory_item->image = $inventory_item->image);
        }

        $inventory_item->save();

        $choice_options = [];
        if ($request->has('choice')) {
            foreach ($request->choice_no as $key => $no) {
                $str = 'choice_options_' . $no;
                if ($request[$str][0] == null) {
                    $validator->getMessageBag()->add('name', translate('messages.attribute_choice_option_value_can_not_be_null'));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                $item['name'] = 'choice_' . $no;
                $item['title'] = $request->choice[$key];
                $item['options'] = explode(',', implode('|', preg_replace('/\s+/', ' ', $request[$str])));
                array_push($choice_options, $item);
            }
        }
        $inventory_item->choice_options = json_encode($choice_options);
        $inventory_item->save();

        $variations = [];
        $options = [];
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('|', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }

        $combinations = Helpers::combinations($options);
        if (count($combinations[0]) > 0) {
            $total_stock = 0;
            $primary_stock = 0;
            $secondary_stock = 0;

            foreach ($combinations as $key => $combination) {
                $str = '';
                foreach ($combination as $k => $item) {
                    if ($k > 0) {
                        $str .= '-' . str_replace(' ', '', $item);
                    } else {
                        $str .= str_replace(' ', '', $item);
                    }
                }
                $vrImages = [];
                if (!empty($request->file('imgs_' .  str_replace('.', '_', $str)))) {
                    $keyImg = 'imgs_' . str_replace('.', '_', $str);
                    if ($request->hasFile($keyImg)) {
                        foreach ($request->file($keyImg) as $img) {
                            $image_name = Helpers::upload('inventory-variations/', 'png', $img);
                            $vrImages[] = $image_name;

                            if ($show_on_store_page) {
                                Helpers::upload('product/', 'png', $img, $image_name);
                            }
                        }
                    }
                }
                $vrDetails = new InvItemVariationDetail();
                $vrDetails->description = $request['descs_' . str_replace('.', '_', $str)];
                $vrDetails->specifications = isset($request['specs_' . str_replace('.', '_', $str)]) ? urldecode(base64_decode($request['specs_' . str_replace('.', '_', $str)])) : null;
                $vrDetails->images = json_encode($vrImages);
                $vrDetails->type = $str;
                $vrDetails->item_id = $inventory_item->id;
                $vrDetails->sku = $request['sku_' . str_replace('.', '_', $str)];
                $vrDetails->save();
                $item = [];

                $input_kgs = abs($request['primary_qty_' . str_replace('.', '_', $str)]);
                $bag_capacity = $inventory_item->primary_qty;

                $bags_from_kgs = intdiv($input_kgs, $bag_capacity);
                $spare_kgs     = $input_kgs % $bag_capacity;

                $total_bags = $bags_from_kgs;

                $item['type'] = $str;
                $item['price'] = abs($request['askingprice_' . str_replace('.', '_', $str)]);
                $item['mrpprice'] = abs($request['mrpprice_' . str_replace('.', '_', $str)]);
                $item['askingprice'] =  0;
                $item['purchaseprice'] =  abs($request['purchaseprice_' . str_replace('.', '_', $str)]);
                $item['discount_percent'] = 0;
                $item['stock'] = $input_kgs;
                $item['secondary_qty'] = $total_bags;
                $item['primary_qty'] = $spare_kgs;
                $item['variations_table_id'] = $vrDetails->id;

                $total_stock += $input_kgs;
                array_push($variations, $item);
            }
        }

        $inventory_item->stock =  $total_stock;
        $inventory_item->variations =  json_encode($variations);
        $inventory_item->save();

        if ($show_on_store_page) {
            Helpers::_copyToItemTable($inventory_item, $uploaded_images = true);
        }

        if ($request->form_type == 'ajax') {
            return response()->json(['status' => true, 'msg' => "Added  Successfully", 'action' => 'add_inventory', 'item_id' => $inventory_item->id]);
        } else {
            Toastr::success('Saved Successfully');
            return back();
        }
    }

    public function fetch_item_by_sku(Request $request)
    {
        $item = InventoryItem::with('entries', 'itemunit', 'storage_unit')
            ->where('sku_id', $request->sku_id)
            ->where('store_id', $this->getStoreId())
            ->first();
        if ($item) {
            $html = View::make('admin-views.inventory.partials.product_card', compact('item'))->render();
            return response()->json(['status' => true, 'html' => $html, 'href' => route('admin.inventory.item.detail', [$item->id])]);
        } else {
            return response()->json(['status' => false, 'html' => '', 'href' => '#']);
        }
    }

    public function scan_barcode(Request $request)
    {
        return view('admin-views.inventory.scanner');
    }

    public function update_variant_combination(Request $request)
    {
        $item = InventoryItem::find($request->item_id);
        $existing_variations_type = [];

        if ($item && $item->variations) {
            $existing_variations = json_decode($item->variations);
            $existing_variations_type = collect($existing_variations)->pluck('type')->all();
        }

        $options = [];
        $price = $request->price;
        $product_name = $request->name;
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $values_array = is_array($request[$name]) ? $request[$name] : [$request[$name]];

                $my_str = implode('', $values_array);
                $vrs = explode(',', $my_str);
                $filtered_values = [];
                foreach ($vrs as $value) {
                    $filtered_values[] = trim($value);
                }

                if (!empty($filtered_values)) {
                    $options[] = $filtered_values;
                }
            }
        }

        $result = [[]];

        foreach ($options as $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, [$property_value]);
                }
            }
            $result = $tmp;
        }
        $product = DB::table('inventory_items')->where('id', $request->item_id)->first();
        if ($product) {
            $current_variations = json_decode($product->variations);
        } else {
            $current_variations = [];
        }
        $combinations = $result;

        $stock = $request->stock == 'true' ? true : false;
        return response()->json([
            'view' => view('admin-views.inventory.partials._addMoreCombinations', compact('existing_variations_type', 'combinations', 'price', 'product_name', 'current_variations', 'stock'))->render(),
            'length' => count($combinations),
            'stock' => $stock,
        ]);
    }

    public function show_on_website(Request $request, $id, $status)
    {
        $inventory_item = InventoryItem::where('id', $id)->where('store_id', $this->getStoreId())->first();
        $item = Item::withoutGlobalScopes()->where('inventory_item_id', $id)->first();

        if ($inventory_item) {
            if ($status == 1) {
                if ($item) {
                    if ($item->store_ids) {
                        $idArr = explode(',', $item->store_ids ?? '');
                        $idArr[] = $this->getStoreId();

                        $idArr = array_unique(array_filter($idArr));

                        $item->store_ids = implode(',', $idArr);
                        $item->save();
                    }
                } else {
                    Helpers::_copyToItemTable($inventory_item);
                }
            } else {
                if ($item) {
                    $storeId =  $this->getStoreId();
                    $idArr = explode(',', $item->store_ids ?? '');

                    $idArr = array_filter($idArr, function ($id) use ($storeId) {
                        return $id != $storeId;
                    });
                    $item->store_ids = implode(',', $idArr);
                    $item->save();
                }
            }
            $inventory_item->show_on_store_page = $status;
            $inventory_item->save();
            Toastr::success('Updated Successfully');
            return back();
        } else {
            Toastr::error('Inventory Item not found');
            return back();
        }
    }

    public function item_detail(Request $request, $id)
    {
        $item = InventoryItem::with('entries', 'itemunit', 'storage_unit', 'secondaryUnit')->find($id);
        return view("admin-views.inventory.item_detail", compact('item'));
    }

    public function variant_combination(Request $request)
    {
        $options = [];
        $price = $request->price;
        $product_name = $request->name;

        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }

        $result = [[]];
        foreach ($options as $property => $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, [$property => $property_value]);
                }
            }
            $result = $tmp;
        }
        $combinations = $result;
        $stock = (bool)$request->stock;

        $primary_unit = _saveUnitIfNotExist($request->primary_unit, 'name');
        $secondary_unit = _saveUnitIfNotExist($request->secondary_unit, 'name');
        return response()->json([
            'view' => view('admin-views.inventory._variant-combinations', compact('secondary_unit', 'primary_unit', 'combinations', 'price', 'product_name', 'stock'))->render(),
            'length' => count($combinations),
        ]);
    }

    public function storage_spaces_delete(Request $request)
    {
        $storage_unit =  StorageUnit::find($request->id);
        $storage_unit->delete();
        Toastr::success('Deleted Successfully');
        return back();
    }

    public function save_entry_pdf(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp']
        ]);
        $path = $request->file('file')->store('invoices');

        $filePath = storage_path('app/' . $path);

        $result = Helpers::parseInvoice($filePath);

        return response()->json($result);
    }

    private function matchOne($pattern, $text)
    {
        if (preg_match($pattern, $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    public function get_item_details(Request $request)
    {
        $item  = InventoryItem::find($request->item_id);

        if ($item->variations) {
            $variations = json_decode($item->variations);
            $firstVar = null;

            if ($request->has('vr_id') && $request->vr_id) {
                foreach ($variations as $v) {
                    if ($v->type == $request->vr_id) {
                        $firstVar = $v;
                        break;
                    }
                }
            }

            if (!$firstVar) {
                $firstVar = $variations[0] ?? null;
            }

            $variationHtml = '';
            if (count($variations) > 0) {
                $variationHtml .= '<div class="card p-2 w-100 position-relative">';
                if ($firstVar->image ?? false) {
                    $variationHtml .= '<img style="width:100px;margin:0 auto;" src="' . asset('storage/app/public/inventory-item/') . '/' . $firstVar->image . '" class="card-img-top" alt="...">';
                }
                $variationHtml .= '<div class="">
                <h4>' . $item->item_name . ' - ' . $firstVar->type . '</h4>
                <p class="mb-0 card-text">MRP : ' . \App\CentralLogics\Helpers::format_currency($firstVar->mrpprice) . '</p>
                <input id="item_mrp" value="' . $firstVar->mrpprice . '" type="hidden">
                <p class="mb-0 card-text">Purch. Price : ' . \App\CentralLogics\Helpers::format_currency($firstVar->landing_price ?? 0) . '</p>
                <p class="mb-0 card-text">GST % : ' . ($firstVar->gst_rate ?? $item->gst_rate) . '</p>
                <p class="mb-0 card-text">Sell. Price : <span id="sell_price_show">' . $firstVar->askingprice . '</span></p>
                <span class="discount_badge">Rs.<span id="discount_amount_show">0</span> Off</span>
            </div>
        </div>';
            }

            return response()->json([
                'status' => true,
                'html' => $variationHtml,
                'has_variation' => true,
                'vr_details' => $request->has('vr_id') && $request->vr_id,
                'variations' => $variations,
                'item_type' => $item->item_type,
                'primary_unit' => $item->unit ? _unitNaneById($item->unit) : '',
                'secondary_unit' => $item->secondary_unit ? _unitNaneById($item->secondary_unit) : '',
                'selected_variation' => $firstVar
            ]);
        }

        $html = '<div class="card p-2 w-100 position-relative" >';
        if ($item->image) {
            $html .= '<img style="width: 100px;margin: 0 auto;" src="' . asset('storage/app/public/inventory-item/') . '/' . $item->image . '" class="card-img-top" alt="...">';
        }

        $html .= '<div class="">
            <h4>' . $item->item_name . ($item->model_number ? ' (' . $item->model_number . ')' : '') . '</h4>
            <p class=" mb-0 card-text">MRP : ' . \App\CentralLogics\Helpers::format_currency($item->mrp) . '</p>
            <input id="item_mrp" value="' . $item->mrp . '" type="hidden">
            <p class=" mb-0 card-text">Purch. Price : ' . \App\CentralLogics\Helpers::format_currency($item->landing_price) . ' </p>
            <p class=" mb-0 card-text">GST % : ' . $item->gst_rate . '</p>
            <p class=" mb-0 card-text">Sell. Price : <span id="sell_price_show">' . $item->selling_price . '</span></p>
            <span class="discount_badge">Rs.<span id="discount_amount_show">0</span> Off</span>
        </div>
        </div>';

        return response()->json([
            'status' => true,
            'html' => $html,
            'has_variation' => false,
            'item_type' => $item->item_type
        ]);
    }

    public function get_variation_details(Request $request)
    {
        $item  = InventoryItem::find($request->item_id);

        $variations = json_decode($item->variations);
        $firstVar = null;

        if ($request->has('vr_id') && $request->vr_id) {
            foreach ($variations as $v) {
                if ($v->type == $request->vr_id) {
                    $firstVar = $v;
                    break;
                }
            }
        }

        if (!$firstVar) {
            $firstVar = $variations[0] ?? null;
        }

        $variationHtml = '';
        if (count($variations) > 0) {
            $variationHtml .= '<div class="card p-2 w-100 position-relative">';
            if ($firstVar->image ?? false) {
                $variationHtml .= '<img style="width:100px;margin:0 auto;" src="' . asset('storage/app/public/inventory-item/') . '/' . $firstVar->image . '" class="card-img-top" alt="...">';
            }
            $variationHtml .= '<div class="">
                <h4>' . $item->item_name . ' - ' . $firstVar->type . '</h4>
                <p class="mb-0 card-text">MRP : ' . \App\CentralLogics\Helpers::format_currency($firstVar->mrpprice) . '</p>
                <input id="item_mrp" value="' . $firstVar->mrpprice . '" type="hidden">
                <p class="mb-0 card-text">Purch. Price : ' . \App\CentralLogics\Helpers::format_currency($firstVar->landing_price ?? 0) . '</p>
                <p class="mb-0 card-text">GST % : ' . ($firstVar->gst_rate ?? $item->gst_rate) . '</p>
                <p class="mb-0 card-text">Sell. Price : <span id="sell_price_show">' . $firstVar->askingprice . '</span></p>
                <span class="discount_badge">Rs.<span id="discount_amount_show">0</span> Off</span>
            </div>
        </div>';
        }

        return response()->json([
            'status' => true,
            'html' => $variationHtml,
            'has_variation' => true,
            'vr_details' => $request->has('vr_id') && $request->vr_id,
            'variations' => $variations,
            'item_type' => $item->item_type,
            'selected_variation' => $firstVar
        ]);
    }

    public function storage_spaces(Request $request)
    {
        $storage_rooms = StorageUnit::with('children')->where('store_id', $this->getStoreId())->where('type', 'room')->get();
        $storage_stands = StorageUnit::with('children')->where('store_id', $this->getStoreId())->where('type', 'stand')->get();
        $storage_shelves = StorageUnit::with('children')->where('store_id', $this->getStoreId())->where('type', 'shelf')->get();
        $storage_containers = StorageUnit::where('store_id', $this->getStoreId())->where('type', 'container')->get();
        return view('admin-views.inventory.storage_spaces', compact('storage_rooms', 'storage_shelves', 'storage_stands', 'storage_containers'));
    }

    public function storage_spaces_update(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('storage_units')->where(function ($query) use ($request) {
                    return $query->where('type', $request->unit_type);
                })->ignore($request->unit_id),
            ],
            'unit_type' => ['required', Rule::in(['room', 'stand', 'shelf', 'container'])],
        ], [
            'name.unique' => 'A unit with this name and type already exists.',
        ]);

        $storage_unit =  StorageUnit::find($request->unit_id);
        $storage_unit->name = $request->name;
        $storage_unit->save();

        Toastr::success('Updated Successfully');
        return redirect()->back();
    }

    public function storage_spaces_store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('storage_units')->where(function ($query) use ($request) {
                    return $query->where('type', $request->unit_type);
                }),
            ],
            'unit_type' => ['required', Rule::in(['room', 'stand', 'shelf', 'container'])],
        ], [
            'name.unique' => 'Unit name with this type already exists.',
        ]);

        if ($request->unit_type != 'room' && $request->parent_id == null) {
            if ($request->unit_type == 'stand') {
                Toastr::warning('Room Required');
            }
            if ($request->unit_type == 'shelf') {
                Toastr::warning('Stand Required');
            }
            if ($request->unit_type == 'container') {
                Toastr::warning('Shelf Required');
            }
            return redirect()->back();
        }

        $storage_unit = new StorageUnit();
        $storage_unit->store_id = $this->getStoreId();
        $storage_unit->type = $request->unit_type;
        $storage_unit->parent_id = $request->parent_id ?? null;
        $storage_unit->name = $request->name;
        $storage_unit->save();

        Toastr::success('Added Successfully');
        return redirect()->back();
    }
}

