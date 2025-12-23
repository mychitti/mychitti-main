<?php

namespace App\Http\Controllers\Vendor;

use DateTime;
use Carbon\Carbon;
use App\Models\Tag;
use App\Models\Item;
use App\Models\Review;
use App\Models\Category;
use App\Scopes\StoreScope;
use App\Models\TempProduct;
use App\Models\Translation;
use App\Models\ServiceRequest;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\FlashSaleItem;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\CommonCondition;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\CentralLogics\ProductLogic;
use App\Models\PharmacyItemDetails;
use App\Http\Controllers\Controller;
use App\Imports\KeywordsImport;
use App\Models\FeeCategory;
use App\Models\ItemVariationDetail;
use App\Models\ProductKeyword;
use App\Models\ServiceKeyword;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\File;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ItemController extends Controller
{
    public function index()
    {
        if (!Helpers::get_store_data()->item_section) {
            Toastr::warning(translate('messages.permission_denied'));
            return back();
        }
        $keywords = ProductKeyword::where('status', 1)->get();
        $store_data = Helpers::get_store_data();
        if ($store_data->module_id == 5) {
            $ven_cats = $store_data->shop_categories;
        } else {
            $ven_cats = $store_data->category_1;
            if ($store_data->category_2) {
                $ven_cats .= ',' . $store_data->category_2;
            }
        }
        // prx($ven_cats);
        $categories = Category::where(['position' => 0])->whereIn('id', explode(',', $ven_cats))->module(Helpers::get_store_data()->module_id)->get();

        $conditions = CommonCondition::all();
        $module_data = config('module.' . Helpers::get_store_data()->module->module_type);
        return view('vendor-views.product.index', compact('keywords', 'categories', 'module_data', 'conditions'));
    }


    public function select_view(Request $request)
    {

        $search_term = '';
        $store_data = Helpers::get_store_data();
        $ven_cats = $store_data->category_1;
        if ($store_data->category_2) {
            $ven_cats .= (',' . $store_data->category_2);
        }
        if ($store_data->subcategories) {
            $ven_cats .= (',' . $store_data->subcategories);
        }
        // print_r(explode(',',$ven_cats)); 
        $product = Item::withoutGlobalScope('translate');

        $product->withoutGlobalScope(StoreScope::class)->where('module_id', 6);
        if ($request->get("search")) {
            $search_term = $request->get("search");
            $product->where('name', 'like', '%' . $request->get("search") . '%');
            $product->where('name', 'like', '%' . $request->get("search") . '%');
        }
        $product->whereIn('category_id', explode(',', $ven_cats));
        $product = $product->get();

        // print_r($product); die();
        return view('vendor-views.product.select_view', compact('product', 'search_term'));
    }

    public function service_save(Request $request)
    {
        //  prx('service_save');
        $selectedSer = $request->services;
        // print_r($selectedSer);
        foreach ($selectedSer as $s) {
            $item =  Item::withoutGlobalScope(StoreScope::class)->where('id', $s)->get();

            if ($item[0]->store_ids == '') {
                $ids = Helpers::get_store_id();
            } else {
                $idArr = explode(',', $item[0]->store_ids);
                if (!in_array(Helpers::get_store_id(), $idArr)) {
                    $ids = $item[0]->store_ids . ',' . Helpers::get_store_id();
                } else {
                    $ids = $item[0]->store_ids;
                }
            }
            // prx($ids);
            $oldIds = _getIdsFrist($s);
            Item::withoutGlobalScope(StoreScope::class)->where('id', $s)->update(['store_ids' => $ids]);
            _trackStoreIds('test 11', $ids, $s, '-', Helpers::get_store_id() . '_vendor', $oldIds);
        }
        return true;
    }


    public function store(Request $request)
    {
        if (!Helpers::get_store_data()->item_section) {
            return response()->json([
                'errors' => [
                    ['code' => 'unauthorized', 'message' => translate('messages.permission_denied')]
                ]
            ]);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'array',
            'name.0' => 'required',
            'name.*' => 'max:191',
            'category_id' => 'required',
            'image' => [
                Rule::requiredIf(function () use ($request) {
                    return (Helpers::get_store_data()->module->module_type != 'food' && $request?->product_gellary == null);
                })
            ],
            'mrp' => 'required|numeric|between:.01,999999999999.99',
            'asking_price' => 'required|numeric|between:.01,999999999999.99',
            'hsn_code' => 'required',
            'description.*' => 'max:1000',
            'description.0' => 'required',
            'discount' => 'required|numeric|min:0',
        ], [
            'name.0.required' => translate('messages.item_default_name_required'),
            'description.0.required' => translate('messages.item_default_description_required'),
            'category_id.required' => translate('messages.category_required'),
            'description.*.max' => translate('messages.description_length_warning'),
        ]);

        // ================== commented out when added asking price and fee category =========================
        // if ($request['discount_type'] == 'percent') {
        //     $dis = ($request['price'] / 100) * $request['discount'];
        // } else {
        //     $dis = $request['discount'];
        // }

        // if ($request['price'] <= $dis) {
        //     $validator->getMessageBag()->add('unit_price', translate('messages.discount_can_not_be_more_than_or_equal'));
        // }

        // if ($request['price'] <= $dis || $validator->fails()) {
        //     return response()->json(['errors' => Helpers::error_processor($validator)]);
        // }
        // ================================================

        $dis = 0;
        $tag_ids = [];
        if ($request->tags != null) {
            $tags = explode(",", $request->tags);
        }
        if (isset($tags)) {
            foreach ($tags as $key => $value) {
                $tag = Tag::firstOrNew(
                    ['tag' => $value]
                );
                $tag->save();
                array_push($tag_ids, $tag->id);
            }
        }


        $images = [];

        if ($request->item_id && $request?->product_gellary == 1) {
            $item_data = Item::withoutGlobalScope(StoreScope::class)->select(['image', 'images'])->findOrfail($request->item_id);

            if (!$request->has('image')) {
                $oldPath = storage_path("app/public/product/{$item_data->image}");
                $newFileName = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . ".png";
                $newPath = storage_path("app/public/product/{$newFileName}");
                if (File::exists($oldPath)) {
                    File::copy($oldPath, $newPath);
                }
            }

            $uniqueValues = array_diff($item_data->images, explode(",", $request->removedImageKeys));

            foreach ($uniqueValues as $key => $value) {
                $oldPath = storage_path("app/public/product/{$value}");
                $newFileName = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . ".png";
                $newPath = storage_path("app/public/product/{$newFileName}");
                if (File::exists($oldPath)) {
                    File::copy($oldPath, $newPath);
                }
                $images[] = $newFileName;
            }
        }

        $food = new Item;
        $food->name = $request->name[array_search('default', $request->lang)];

        $category = [];
        if ($request->category_id != null) {
            array_push($category, [
                'id' => $request->category_id,
                'position' => 1,
            ]);
        }
        if ($request->sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_category_id,
                'position' => 2,
            ]);
        }
        if ($request->sub_sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_sub_category_id,
                'position' => 3,
            ]);
        }
        $food->category_id = $request->sub_category_id ? $request->sub_category_id : $request->category_id;
        $food->category_ids = json_encode($category);
        $food->description = $request->description[array_search('default', $request->lang)];

        $specifications = isset($request->specifications) ? urldecode(base64_decode($request->specifications)) : null;
        $food->specifications =  $specifications;

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
        $food->choice_options = json_encode($choice_options);
        $variations = [];
        $options = [];
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('|', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }

        //combinations end

        // $img_names = [];
        // $images = [];
        // if (!empty($request->file('item_images'))) {
        //     foreach ($request->item_images as $img) {
        //         $image_name = Helpers::upload('product/', 'png', $img);
        //         array_push($img_names, $image_name);
        //     }
        //     $images = $img_names;
        // }


        if (!empty($request->file('item_images'))) {
            foreach ($request->item_images as $img) {
                $image_name = Helpers::upload('product/', 'png', $img);
                $images[] = $image_name;
            }
        }


        // food variation
        $food_variations = [];
        if (isset($request->options)) {
            foreach (array_values($request->options) as $key => $option) {

                $temp_variation['name'] = $option['name'];
                $temp_variation['type'] = $option['type'];
                $temp_variation['min'] = $option['min'] ?? 0;
                $temp_variation['max'] = $option['max'] ?? 0;
                $temp_variation['required'] = $option['required'] ?? 'off';
                if ($option['min'] > 0 &&  $option['min'] > $option['max']) {
                    $validator->getMessageBag()->add('name', translate('messages.minimum_value_can_not_be_greater_then_maximum_value'));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                if (!isset($option['values'])) {
                    $validator->getMessageBag()->add('name', translate('messages.please_add_options_for') . $option['name']);
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                if ($option['max'] > count($option['values'])) {
                    $validator->getMessageBag()->add('name', translate('messages.please_add_more_options_or_change_the_max_value_for') . $option['name']);
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                $temp_value = [];

                foreach (array_values($option['values']) as $value) {
                    if (isset($value['label'])) {
                        $temp_option['label'] = $value['label'];
                    }
                    $temp_option['optionPrice'] = $value['optionPrice'];
                    array_push($temp_value, $temp_option);
                }
                $temp_variation['values'] = $temp_value;
                array_push($food_variations, $temp_variation);
            }
        }

        $food->food_variations = json_encode($food_variations);

        $food->variations = json_encode($variations);
        $food->price = 0;
        $food->mrp_price = $request->mrp;
        $food->asking_price = $request->asking_price;

        $food->tax_type = 'percent';
        $food->tax = $request->gst_percent;
        $food->hsn_code = $request->hsn_code;
        
        $food->veg = $request->veg ?? 0;
        $food->image =  $request->has('image') ? Helpers::upload('product/', 'png', $request->file('image')) : $newFileName ?? null;
        $food->available_time_starts = $request->available_time_starts ?? '00:00:00';
        $food->available_time_ends = $request->available_time_ends ?? '23:59:59';
        $food->discount = 0;
        $food->discount_type = 'percent';
        $food->maximum_cart_quantity = $request->maximum_cart_quantity;
        $food->attributes = $request->has('attribute_id') ? json_encode($request->attribute_id) : json_encode([]);
        $food->add_ons = $request->has('addon_ids') ? json_encode($request->addon_ids) : json_encode([]);
        $food->store_id = Helpers::get_store_id();
        $food->module_id = Helpers::get_store_data()->module_id;
        $food->images = $images;
        $food->keywords =  '';
        // $food->keywords =  implode(',', $request->keywords);
        $food->stock = $request->current_stock ?? 0;
        $module_type = Helpers::get_store_data()->module->module_type;
        if ($module_type == 'grocery') {
            $food->organic = $request->organic ?? 0;
        }
        $food->save();
        $food->tags()->sync($tag_ids);

        //Generates the combinations of customer choice options
        $combinations = Helpers::combinations($options);
        if (count($combinations[0]) > 0) {
            foreach ($combinations as $key => $combination) {
                $str = '';
                foreach ($combination as $k => $item) {
                    if ($k > 0) {
                        $str .= '-' . str_replace(' ', '', $item);
                    } else {
                        $str .= str_replace(' ', '', $item);
                    }
                }
                $vrImages = []; // Initialize the array
                if (!empty($request->file('imgs_' .  str_replace('.', '_', $str)))) {

                    $key = 'imgs_' . str_replace('.', '_', $str);
                    if ($request->hasFile($key)) {
                        foreach ($request->file($key) as $img) {
                            $image_name = Helpers::upload('product-variations/', 'png', $img);
                            $vrImages[] = $image_name;
                        }
                    }
                }
                $vrDetails = new ItemVariationDetail();
                $vrDetails->description = $request['descs_' . str_replace('.', '_', $str)];
                $vrDetails->specifications = isset($request['specs_' . str_replace('.', '_', $str)]) ? urldecode(base64_decode($request['specs_' . str_replace('.', '_', $str)])) : null;;
                $vrDetails->images = json_encode($vrImages);
                $vrDetails->type = $str;
                $vrDetails->item_id = $food->id;
                $vrDetails->save();

                $item = [];
                $item['type'] = $str;
                $item['price'] =  0;
                $item['mrpprice'] = abs($request['mrpprice_' . str_replace('.', '_', $str)]);
                $item['askingprice'] =  abs($request['askingprice_' . str_replace('.', '_', $str)]);
                $item['discount_percent'] = 0;
                $item['tax'] = $request->gst_percent;
                $item['stock'] = abs($request['stock_' . str_replace('.', '_', $str)]);
                $item['variations_table_id'] = $vrDetails->id;

                array_push($variations, $item);
            }
        }
        $food->update([
            'variations' => json_encode($variations)
        ]);
        // export service keywords
        if ($request->hasFile('keyword_excel')) {
            Excel::import(new KeywordsImport($food->id), $request->file('keyword_excel'));
        }

        if ($module_type == 'pharmacy') {
            $item_details = new PharmacyItemDetails();
            $item_details->item_id = $food->id;
            $item_details->common_condition_id = $request->condition_id;
            $item_details->is_basic = $request->basic ?? 0;
            $item_details->save();
        }   

        $item = Helpers::get_store_data()->module->id == 5 ? 'Product' : 'Service';
        $url = route('admin.item.list');  
        _inAppNotification("New ".$item." Added", 'New '.$item.' Added! Please check for details.', null, 0, $url, 'admin');

        Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'Item', data_id: $food->id, data_value: $food->name);
        Helpers::add_or_update_translations(request: $request, key_data: 'description', name_field: 'description', model_name: 'Item', data_id: $food->id, data_value: $food->description);


        $product_approval_datas = \App\Models\BusinessSetting::where('key', 'product_approval_datas')->first()?->value ?? '';
        $product_approval_datas = json_decode($product_approval_datas, true);
        if (Helpers::get_mail_status('product_approval') && data_get($product_approval_datas, 'Add_new_product', null) == 1) {
            $this->store_temp_data($food, $request, $tag_ids);
            $food->is_approved = 0;
            $food->save();
            return response()->json(['product_approval' => translate('messages.The_product_will_be_published_once_it_receives_approval_from_the_admin.')], 200);
        }


        return response()->json(['success' => translate('messages.product_added_successfully')], 200);
    }

    public function view($id)
    {
        if (Helpers::get_store_data()->module->id == 5) {

            $product = Item::findOrFail($id);
            $reviews = Review::where(['item_id' => $id])->latest()->paginate(config('default_pagination'));
            return view('vendor-views.product.view', compact('product', 'reviews'));
        } else {
            $product = DB::table('items')->where('id', $id)->first();
            $reviews = DB::table('reviews')->where(['item_id' => $id])->latest()->paginate(config('default_pagination'));
            return view('vendor-views.product.service_view', compact('product', 'reviews'));
        }
    }

    public function edit(Request $request, $id)
    {
        if (!Helpers::get_store_data()->item_section) {
            Toastr::warning(translate('messages.permission_denied'));
            return back();
        }
        $temp_product = false;
        if ($request->temp_product) {
            $product = TempProduct::withoutGlobalScope('translate')->findOrFail($id);
            $temp_product = true;
        } else {
            $product = Item::withoutGlobalScope('translate');
            if (isset($request->product_gellary) && $request->product_gellary == 1) {

                $product->withoutGlobalScope(StoreScope::class)->where('is_approved', 1);
            }
            $product = $product->findOrFail($id);
        }
        $product_category = json_decode($product->category_ids);
        $store_data = Helpers::get_store_data();

        $store_data = Helpers::get_store_data();
        if ($store_data->module_id == 5) {
            $ven_cats = $store_data->shop_categories;
        } else {
            $ven_cats = $store_data->category_1;
            if ($store_data->category_2) {
                $ven_cats .= (',' . $store_data->category_2);
            }
            if ($store_data->subcategories) {
                $ven_cats .= (',' . $store_data->subcategories);
            }
        }
        $fee_category = FeeCategory::find($product->fee_category);
        $categories = Category::where(['position' => 0])->whereIn('id', explode(',', $ven_cats))->module(Helpers::get_store_data()->module_id)->get();
        // $keywords = ProductKeyword::where('status', 1)->get();
        $module_data = config('module.' . Helpers::get_store_data()->module->module_type);
        $conditions = CommonCondition::all();
        $keywords = ServiceKeyword::where('service_id', $id)->get();
        return view('vendor-views.product.edit', compact('product', 'keywords', 'product_category', 'categories', 'module_data', 'temp_product', 'conditions', 'fee_category'));
    }

    public function status(Request $request)
    {
        if (!Helpers::get_store_data()->item_section) {
            Toastr::warning(translate('messages.permission_denied'));
            return back();
        }
        $product = Item::find($request->id);
        $product->status = $request->status;
        $product->save();
        Toastr::success('Item status updated!');
        return back();
    }

    public function recommended(Request $request)
    {
        if (!Helpers::get_store_data()->item_section) {
            Toastr::warning(translate('messages.permission_denied'));
            return back();
        }
        $product = Item::find($request->id);
        $product->recommended = $request->status;
        $product->save();
        Toastr::success(translate('Item recommendation updated!'));
        return back();
    }

    public function update(Request $request, $id)
    {
        // prx($request->all());
        if (!Helpers::get_store_data()->item_section) {
            return response()->json([
                'errors' => [
                    ['code' => 'unauthorized', 'message' => translate('messages.permission_denied')]
                ]
            ]);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'array',
            'name.0' => 'required',
            'name.*' => 'max:191',
            'category_id' => 'required',
            'price' => 'required|numeric|between:0.01,999999999999.99',
            'description.*' => 'max:1000',
            'description.0' => 'required',
            'hsn_code' => 'required',
            'discount' => 'required|numeric|min:0',
        ], [
            'name.0.required' => translate('messages.item_default_name_required'),
            'description.0.required' => translate('messages.item_default_description_required'),
            'category_id.required' => translate('messages.category_required'),
            'description.*.max' => translate('messages.description_length_warning'),
        ]);
        $main_det_changed = false;
        if ($request['discount_type'] == 'percent') {
            $dis = ($request['price'] / 100) * $request['discount'];
        } else {
            $dis = $request['discount'];
        }

        if ($request['price'] <= $dis) {
            $validator->getMessageBag()->add('unit_price', translate('messages.discount_can_not_be_more_than_or_equal'));
        }

        if ($request['price'] <= $dis || $validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $tag_ids = [];
        if ($request->tags != null) {
            $tags = explode(",", $request->tags);
        }
        if (isset($tags)) {
            foreach ($tags as $key => $value) {
                $tag = Tag::firstOrNew(
                    ['tag' => $value]
                );
                $tag->save();
                array_push($tag_ids, $tag->id);
            }
        }

        $p = Item::find($id);

        if ((isset($request->specifications) &&  $p->specifications != urldecode(base64_decode($request->specifications))) ||
            ($p->description != $request->description[array_search('default', $request->lang)]) ||
            ($p->name != $request->name[array_search('default', $request->lang)])
        ) {
            $main_det_changed = true;
        }

        $p->name = $request->name[array_search('default', $request->lang)];

        $category = [];
        if ($request->category_id != null) {
            array_push($category, [
                'id' => $request->category_id,
                'position' => 1,
            ]);
        }
        if ($request->sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_category_id,
                'position' => 2,
            ]);
        }
        if ($request->sub_sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_sub_category_id,
                'position' => 3,
            ]);
        }

        if ($p->category_ids != json_encode($category)) {
            $main_det_changed = true;
        }


        $p->category_id = $request->sub_category_id ? $request->sub_category_id : $request->category_id;
        $p->category_ids = json_encode($category);
        $p->description = $request->description[array_search('default', $request->lang)];
        // $p->specifications =  $request->specifications;
        $specifications = isset($request->specifications) ? urldecode(base64_decode($request->specifications)) : null;
        $p->specifications =  $specifications;

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
        $p->choice_options = json_encode($choice_options);
        $variations = [];
        $details_changed = false;
        $options = [];
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('|', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }

        //Generates the combinations of customer choice options
        $combinations = Helpers::combinations($options);
        if (count($combinations[0]) > 0) {
            foreach ($combinations as $key => $combination) {
                $str = '';
                foreach ($combination as $k => $temp) {
                    if ($k > 0) {
                        $str .= '-' . str_replace(' ', '', $temp);
                    } else {
                        $str .= str_replace(' ', '', $temp);
                    }
                }

                $vrDetails = ItemVariationDetail::where('type', $str)->where('item_id', $p->id)->first();
                if ($vrDetails) {
                    $vrImages = json_decode($vrDetails->images); // Initialize the array

                } else {
                    $vrDetails = new ItemVariationDetail();
                    $vrImages = []; // Initialize the array
                    $vrDetails->item_id =  $p->id;;
                    $vrDetails->type =  $str;
                }

                if (!empty($request->file('imgs_' .  str_replace('.', '_', $str)))) {
                    $key = 'imgs_' . str_replace('.', '_', $str);
                    if ($request->hasFile($key)) {
                        foreach ($request->file($key) as $img) {
                            $image_name = Helpers::upload('product-variations/', 'png', $img);
                            $vrImages[] = $image_name;
                        }
                        $vrDetails->images = json_encode($vrImages);
                    }
                    $details_changed = true;
                }
                if ($vrDetails->description != $request['descs_' . str_replace('.', '_', $str)]) {
                    $details_changed = true;
                }
                if (isset($request['specs_' . str_replace('.', '_', $str)]) && urldecode(base64_decode($request['specs_' . str_replace('.', '_', $str)])) != $vrDetails->specifications) {
                    $details_changed = true;
                }

                $vrDetails->description =  $request['descs_' . str_replace('.', '_', $str)];
                $vrDetails->specifications =  isset($request['specs_' . str_replace('.', '_', $str)]) ? urldecode(base64_decode($request['specs_' . str_replace('.', '_', $str)])) : null;;
                $vrDetails->save();

                $temp = [];
                $temp['type'] = $str;
                $temp['tax'] = $request->gst_percent;
                $temp['price'] = abs($request['price_' . str_replace('.', '_', $str)]);
                $temp['mrpprice'] = abs($request['mrpprice_' . str_replace('.', '_', $str)]);
                $temp['askingprice'] = abs($request['askingprice_' . str_replace('.', '_', $str)]);
                $temp['discount'] = abs($request['discount_' . str_replace('.', '_', $str)]);
                $temp['stock'] = abs($request['stock_' . str_replace('.', '_', $str)]);
                $temp['variations_table_id'] = $vrDetails->id;
                array_push($variations, $temp);
            }
        }

        //combinations end
        //Generates the combinations of customer choice options 

        // $food_variations = [];
        // if (isset($request->options)) {
        //     foreach (array_values($request->options) as $key => $option) { 
        //         $temp_variation['name'] = $option['name'];
        //         $temp_variation['type'] = $option['type'];
        //         $temp_variation['min'] = $option['min'] ?? 0;
        //         $temp_variation['max'] = $option['max'] ?? 0;
        //         if ($option['min'] > 0 &&  $option['min'] > $option['max']) {
        //             $validator->getMessageBag()->add('name', translate('messages.minimum_value_can_not_be_greater_then_maximum_value'));
        //             return response()->json(['errors' => Helpers::error_processor($validator)]);
        //         }
        //         if (!isset($option['values'])) {
        //             $validator->getMessageBag()->add('name', translate('messages.please_add_options_for') . $option['name']);
        //             return response()->json(['errors' => Helpers::error_processor($validator)]);
        //         }
        //         if ($option['max'] > count($option['values'])) {
        //             $validator->getMessageBag()->add('name', translate('messages.please_add_more_options_or_change_the_max_value_for') . $option['name']);
        //             return response()->json(['errors' => Helpers::error_processor($validator)]);
        //         }
        //         $temp_variation['required'] = $option['required'] ?? 'off';
        //         $temp_value = [];
        //         foreach (array_values($option['values']) as $value) {
        //             if (isset($value['label'])) {
        //                 $temp_option['label'] = $value['label'];
        //             }
        //             $temp_option['optionPrice'] = $value['optionPrice'];
        //             array_push($temp_value, $temp_option);
        //         }
        //         $temp_variation['values'] = $temp_value;
        //         array_push($food_variations, $temp_variation);
        //     }
        // }

        $variation_changed = false;
        $original = self::normalizeVariations($p->variations);
        $current = self::normalizeVariations(json_encode($variations));

        if ((($p->variations != null && $variations != '[]') && strcmp($original, $current) !== 0)  ||
            $details_changed
        ) {
            $variation_changed = true;
        }


        $old_price = $p->price;
        $slug = Str::slug($request->name[array_search('default', $request->lang)]);
        $p->slug = $p->slug ? $p->slug : "{$slug}-{$p->id}";
        $p->food_variations = json_encode([]);
        $p->variations = json_encode($variations);


        // price calculations ===================================
        $asking_price = $request->asking_price;
        $mrp_price = $request->mrp_price;
        $flooredDiscount = 0;
        $selling_price = 0;
        $mychitty_fee = 0;
        if ($p->fee_category) {
            $feeCat = FeeCategory::find($p->fee_category);
            $feePercent = $feeCat->total_fee;

            $mychitty_fee = $asking_price * $feePercent / 100;

            $selling_price = $asking_price + $mychitty_fee;

            if ($mrp_price > 0) {
                $discountPercent = (($mrp_price - $selling_price) / $mrp_price) * 100;
                $selling_price = $mrp_price - ($mrp_price * $discountPercent / 100);
            }
        }
        // price calculations end===================================

        $p->price = $selling_price;
        $p->mrp_price = $request->mrp_price;
        $p->asking_price = $request->asking_price;
        $p->discount = $flooredDiscount;
        $p->mychitty_fee =  $mychitty_fee;

        $p->tax =  $request->gst_percent;
        $p->tax_type =  'percent';
        $p->hsn_code =  $request->hsn_code;

        $p->veg = $request->veg ?? 0;
        $p->available_time_starts = $request->available_time_starts ?? '00:00:00';
        $p->available_time_ends = $request->available_time_ends ?? '23:59:59';
        $p->discount = $request->discount_type == 'amount' ? $request->discount : $request->discount;
        $p->discount_type = $request->discount_type;
        $p->maximum_cart_quantity = $request->maximum_cart_quantity;
        $p->attributes = $request->has('attribute_id') ? json_encode($request->attribute_id) : json_encode([]);
        $p->add_ons = $request->has('addon_ids') ? json_encode($request->addon_ids) : json_encode([]);
        $p->stock = $request->current_stock ?? 0;
        $p->organic = $request->organic ?? 0;
        $p->keywords = '';

        $product_approval_datas = \App\Models\BusinessSetting::where('key', 'product_approval_datas')->first()?->value ?? '';
        $product_approval_datas = json_decode($product_approval_datas, true);

        // if($main_det_changed){
        //     echo 'main det changed ';

        // }
        // if( (data_get($product_approval_datas, 'Update_product_price', null) == 1) && $old_price !=  $request->price){
        //     echo 'updated pro price';

        // }
        // if((data_get($product_approval_datas, 'Update_product_variation', null) == 1 &&  $variation_changed)){
        //     echo 'vr changed';
        // }
        // if($details_changed){
        //     echo 'det chnaged';

        // }

        if (1) {
            // if (($main_det_changed || (data_get($product_approval_datas, 'Update_product_price', null) == 1 && $old_price !=  $request->price) || (data_get($product_approval_datas, 'Update_product_variation', null) == 1 &&  $variation_changed) || $details_changed)) {

            $this->store_temp_data($p, $request, $tag_ids, true);
            return response()->json(['product_approval' => translate('your_product_added_for_approval')], 200);
        } else {

            $p->image = $request->has('image') ? Helpers::update('product/', $p->image, 'png', $request->file('image')) : $p->image;
            $images = $p['images'];
            if ($request->has('item_images')) {
                foreach ($request->item_images as $img) {
                    $image = Helpers::upload('product/', 'png', $img);
                    array_push($images, $image);
                }
            }
            $p->images = $images;
        }
        if ($request->has('keyword_excel')) {
            // delete old keywords
            ServiceKeyword::where('service_id', $p->id)->delete();

            // export service keywords
            Excel::import(new KeywordsImport($p->id), $request->file('keyword_excel'));
        }

        if ($p->module->module_type == 'pharmacy') {
            DB::table('pharmacy_item_details')
                ->updateOrInsert(
                    ['item_id' => $p->id],
                    [
                        'common_condition_id' => $request->condition_id,
                        'is_basic' => $request->basic ?? 0,
                    ]
                );
        }

        $p->save();
        $p->tags()->sync($tag_ids);

        Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'Item', data_id: $p->id, data_value: $p->name);
        Helpers::add_or_update_translations(request: $request, key_data: 'description', name_field: 'description', model_name: 'Item', data_id: $p->id, data_value: $p->description);

        return response()->json(['success' => translate('messages.product_updated_successfully')], 200);
    }
    function priceCalculater($request) {}
    function normalizeVariations($json)
    {
        $variations = json_decode($json, true);

        foreach ($variations as &$v) {
            unset($v['price']); // Remove price field
        }

        return json_encode($variations);
    }
    public function delete(Request $request)
    {
        if (!Helpers::get_store_data()->item_section) {
            Toastr::warning(translate('messages.permission_denied'));
            return back();
        }

        if ($request?->temp_product) {
            $product = TempProduct::find($request->id);
        } else {
            $product = Item::find($request->id);
            $product?->temp_product?->translations()?->delete();
            $product?->temp_product()?->delete();
            $product?->carts()?->delete();
        }

        if ($product->image) {
            if (Storage::disk('public')->exists('product/' . $product['image'])) {
                Storage::disk('public')->delete('product/' . $product['image']);
            }
        }
        $product->translations()->delete();
        $product->delete();
        Toastr::success('Item removed!');
        return back();
    }
    public function update_variant_combination(Request $request)
    {
        // prx($request->all());
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
                    // echo str_replace(' ', '', trim($value)) . '<br>';
                    if (!in_array(str_replace(' ', '', trim($value)), $request->type)) {
                        $filtered_values[] = trim($value);
                    }
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
        $product = DB::table('items')->find($request->item_id);
        $current_variations = $product ? json_decode($product->variations) : [];
        $types = array_map(fn($v) => $v->type, $current_variations);

        $combinations = $result;

        $stock = $request->stock == 'true' ? true : false;

        return response()->json([
            'view' => view('vendor-views.product.partials._addMoreCombinations', compact('combinations', 'price', 'product_name', 'stock', 'types'))->render(),
            'length' => count($combinations),
            'stock' => $stock,
        ]);
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
        return response()->json([
            'view' => view('vendor-views.product.partials._variant-combinations', compact('combinations', 'price', 'product_name', 'stock'))->render(),
            'length' => count($combinations),
        ]);
    }

    public function get_categories(Request $request)
    {
        $cat = Category::where(['parent_id' => $request->parent_id])->get();
        $res = '<option value="' . 0 . '" disabled selected>---Select---</option>';
        foreach ($cat as $row) {
            if ($row->id == $request->sub_category) {
                $res .= '<option value="' . $row->id . '" selected >' . $row->name . '</option>';
            } else {
                $res .= '<option value="' . $row->id . '">' . $row->name . '</option>';
            }
        }
        return response()->json([
            'options' => $res,
        ]);
    }

    public function list(Request $request)
    {
        $category_id = $request->query('category_id', 'all');
        $type = $request->query('type', 'all');
        $sub_category_id = $request->query('sub_category_id', 'all');

        $items = Item::when(is_numeric($category_id), function ($query) use ($category_id) {
            return $query->whereHas('category', function ($q) use ($category_id) {
                return $q->whereId($category_id)->orWhere('parent_id', $category_id);
            });
        })
            ->when(is_numeric($sub_category_id), function ($query) use ($sub_category_id) {
                return $query->where('category_id', $sub_category_id);
            })
            ->where('is_approved', 1)
            ->type($type)->latest()->paginate(config('default_pagination'));
        $sub_categories = $category_id != 'all' ? Category::where('parent_id', $category_id)->get(['id', 'name']) : [];

        $category = $category_id != 'all' ? Category::findOrFail($category_id) : null;
        return view('vendor-views.product.list', compact('items', 'category', 'type', 'sub_categories'));
    }

    public function search(Request $request)
    {
        $view = 'vendor-views.product.partials._table';
        $key = explode(' ', $request['search']);
        $settings_access = Helpers::get_mail_status('access_all_products');
        $items = Item::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->where('name', 'like', "%{$value}%");
            }
        })
            ->module(Helpers::get_store_data()->module_id)
            ->where('is_approved', 1);

        if (isset($request->product_gallery) && $request->product_gallery == 1 && $settings_access == 1) {

            $items = $items->withoutGlobalScope(StoreScope::class)->limit(12)->get();

            $view = 'vendor-views.product.partials._gallery';
        } elseif (isset($request->product_gallery) && $request->product_gallery == 1 && $settings_access == 0) {
            $items = $items->limit(12)->get();
            $view = 'vendor-views.product.partials._gallery';
        } else {
            $items = $items->latest()->limit(50)->get();
        }

        return response()->json([
            'view' => view($view, compact('items'))->render(),
            'count' => $items->count()
        ]);
    }

    public function remove_image(Request $request)
    {
        if (Storage::disk('public')->exists('product/' . $request['name'])) {
            Storage::disk('public')->delete('product/' . $request['name']);
        }
        if ($request?->temp_product) {
            $item = TempProduct::find($request['id']);
        } else {
            $item = Item::find($request['id']);
        }

        $array = [];
        if (count($item['images']) < 2) {
            Toastr::warning('You cannot delete all images!');
            return back();
        }
        foreach ($item['images'] as $image) {
            if ($image != $request['name']) {
                array_push($array, $image);
            }
        }
        if ($request?->temp_product) {
            TempProduct::where('id', $request['id'])->update([
                'images' => json_encode($array),
            ]);
        } else {
            Item::where('id', $request['id'])->update([
                'images' => json_encode($array),
            ]);
        }
        Toastr::success('Item image removed successfully!');
        return back();
    }

    public function bulk_import_index()
    {
        $module_type = Helpers::get_store_data()->module->module_type;
        return view('vendor-views.product.bulk-import', compact('module_type'));
    }

    public function bulk_import_data(Request $request)
    {
        $request->validate([
            'products_file' => 'required|max:2048'
        ]);
        $module_id = Helpers::get_store_data()->module->id;
        $module_type = Helpers::get_store_data()->module->module_type;
        if (!Helpers::get_store_data()->item_section) {
            Toastr::warning(translate('messages.permission_denied'));
            return back();
        }
        try {
            $collections = (new FastExcel)->import($request->file('products_file'));
        } catch (\Exception $exception) {
            Toastr::error(translate('messages.you_have_uploaded_a_wrong_format_file'));
            return back();
        }
        $item_id = Item::withoutGlobalScopes()->orderby('id', 'desc')->select('id')->first()?->id;

        $product_approval_datas = \App\Models\BusinessSetting::where('key', 'product_approval_datas')->first()?->value ?? '';
        $product_approval_datas = json_decode($product_approval_datas, true);

        $product_approval_active = Helpers::get_mail_status('product_approval');
        $message = translate('messages.Products_imported_successfully');

        if ($request->button == 'import') {
            $data = [];
            $temp_data = [];
            try {
                foreach ($collections as $key => $collection) {
                    if ($collection['Id'] === "" || $collection['Name'] === "" || $collection['CategoryId'] === "" || $collection['SubCategoryId'] === "" || $collection['Price'] === "" || $collection['Discount'] === "" || $collection['DiscountType'] === "") {
                        Toastr::error(translate('messages.please_fill_all_required_fields'));
                        return back();
                    }

                    if (isset($collection['Price']) && ($collection['Price'] < 0)) {
                        Toastr::error(translate('messages.Price_must_be_greater_then_0') . ' ' . $collection['Id']);
                        return back();
                    }
                    if (isset($collection['Discount']) && ($collection['Discount'] < 0)) {
                        Toastr::error(translate('messages.Discount_must_be_greater_then_0') . ' ' . $collection['Id']);
                        return back();
                    }

                    try {
                        $t1 = Carbon::parse($collection['AvailableTimeStarts']);
                        $t2 = Carbon::parse($collection['AvailableTimeEnds']);
                        if ($t1->gt($t2)) {
                            Toastr::error(translate('messages.AvailableTimeEnds_must_be_greater_then_AvailableTimeStarts_on_id') . ' ' . $collection['Id']);
                            return back();
                        }
                    } catch (\Exception $e) {
                        info(["line___{$e->getLine()}", $e->getMessage()]);
                        Toastr::error(translate('messages.Invalid_AvailableTimeEnds_or_AvailableTimeStarts_on_id') . ' ' . $collection['Id']);
                        return back();
                    }


                    array_push($data, [
                        'id' => $item_id + $key + 1,
                        'name' => $collection['Name'],
                        'description' => $collection['Description'],
                        'image' => $collection['Image'],
                        'images' => $collection['Images'] ?? json_encode([]),
                        'category_id' => $collection['SubCategoryId'] ? $collection['SubCategoryId'] : $collection['CategoryId'],
                        'category_ids' => json_encode([['id' => $collection['CategoryId'], 'position' => 0], ['id' => $collection['SubCategoryId'], 'position' => 1]]),

                        'unit_id' => is_int($collection['UnitId']) ? $collection['UnitId'] : null,
                        'stock' => is_numeric($collection['Stock']) ? abs($collection['Stock']) : 0,
                        'price' => $collection['Price'],
                        'discount' => $collection['Discount'],
                        'discount_type' => $collection['DiscountType'],
                        'available_time_starts' => $collection['AvailableTimeStarts'] ?? '00:00:00',
                        'available_time_ends' => $collection['AvailableTimeEnds'] ?? '23:59:59',
                        'variations' => $module_type == 'food' ? json_encode([]) : $collection['Variations'] ?? json_encode([]),
                        'food_variations' => $module_type == 'food' ? $collection['Variations'] ?? json_encode([]) : json_encode([]),
                        'add_ons' => $collection['AddOns'] ? ($collection['AddOns'] == "" ? json_encode([]) : $collection['AddOns']) : json_encode([]),
                        'attributes' => $collection['Attributes'] ? ($collection['Attributes'] == "" ? json_encode([]) : $collection['Attributes']) : json_encode([]),
                        'store_id' => Helpers::get_store_id(),
                        'module_id' => Helpers::get_store_data()->module_id,
                        'choice_options' => $module_type == 'food' ? json_encode([]) : $collection['ChoiceOptions'] ?? json_encode([]),
                        'status' => $collection['Status'] == 'active' ? 1 : 0,
                        'veg' => $collection['Veg'] == 'yes' ? 1 : 0,
                        'recommended' => $collection['Recommended'] == 'yes' ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);


                    if ($product_approval_active && data_get($product_approval_datas, 'Add_new_product', null) == 1) {
                        // $this->store_temp_data($food, $request,$tag_ids);
                        $data[$key]['is_approved'] = 0;

                        $slug = Str::slug($data[$key]['name']) . '_' . $data[$key]['store_id'];

                        array_push($temp_data, [
                            // 'id' => $item_id + $key +1,
                            'name' => $data[$key]['name'],
                            'description' => $data[$key]['description'],
                            'image' => $data[$key]['image'],
                            'images' =>  $data[$key]['images'],
                            'category_id' =>  $data[$key]['category_id'],
                            'category_ids' => $data[$key]['category_ids'],
                            'store_id' => $data[$key]['store_id'],
                            'module_id' => $data[$key]['module_id'],
                            'unit_id' => $data[$key]['unit_id'],
                            'item_id' => $data[$key]['id'],
                            'slug' => $slug,
                            'tag_ids' => json_encode([]),
                            'choice_options' => $data[$key]['choice_options'],
                            'food_variations' => $data[$key]['food_variations'],
                            'variations' => $data[$key]['variations'],
                            'add_ons' =>  $data[$key]['add_ons'],
                            'attributes' =>  $data[$key]['attributes'],
                            'price' =>  $data[$key]['price'],
                            'discount' =>  $data[$key]['discount'],
                            'discount_type' =>   $data[$key]['discount_type'],
                            'available_time_starts' => $data[$key]['available_time_starts'],
                            'available_time_ends' => $data[$key]['available_time_ends'],
                            'veg' => $data[$key]['veg'],
                            'stock' => $data[$key]['stock'],
                            'status' => $data[$key]['status'],
                            'recommended' => $data[$key]['recommended'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            } catch (\Exception $e) {
                info(["line___{$e->getLine()}", $e->getMessage()]);
                Toastr::error(translate('messages.failed_to_import_data'));
                return back();
            }
            try {
                DB::beginTransaction();

                $chunkSize = 100;
                $chunk_items = array_chunk($data, $chunkSize);
                foreach ($chunk_items as $key => $chunk_item) {
                    DB::table('items')->insert($chunk_item);
                }
                if (count($temp_data) > 0) {
                    $message = translate('messages.Products_are_added_for_the_admin_approval');

                    $chunk_temp_items = array_chunk($temp_data, $chunkSize);
                    foreach ($chunk_temp_items as $key => $chunk_item) {
                        DB::table('temp_products')->insert($chunk_item);
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                info(["line___{$e->getLine()}", $e->getMessage()]);
                Toastr::error(translate('messages.failed_to_import_data'));
                return back();
            }

            Toastr::success(count($data) . ' ' . $message);
            return back();
        }

        $data = [];
        $temp_data = [];
        try {
            foreach ($collections as $key => $collection) {
                if ($collection['Id'] === "" || $collection['Name'] === "" || $collection['CategoryId'] === "" || $collection['SubCategoryId'] === "" || $collection['Price'] === "" || $collection['Discount'] === "" || $collection['DiscountType'] === "") {
                    Toastr::error(translate('messages.please_fill_all_required_fields'));
                    return back();
                }
                if (isset($collection['Price']) && ($collection['Price'] < 0)) {
                    Toastr::error(translate('messages.Price_must_be_greater_then_0') . ' ' . $collection['Id']);
                    return back();
                }
                if (isset($collection['Discount']) && ($collection['Discount'] < 0)) {
                    Toastr::error(translate('messages.Discount_must_be_greater_then_0') . ' ' . $collection['Id']);
                    return back();
                }
                if (isset($collection['Discount']) && ($collection['Discount'] > 100)) {
                    Toastr::error(translate('messages.Discount_must_be_less_then_100') . ' ' . $collection['Id']);
                    return back();
                }

                try {
                    $t1 = Carbon::parse($collection['AvailableTimeStarts']);
                    $t2 = Carbon::parse($collection['AvailableTimeEnds']);
                    if ($t1->gt($t2)) {
                        Toastr::error(translate('messages.AvailableTimeEnds_must_be_greater_then_AvailableTimeStarts_on_id') . ' ' . $collection['Id']);
                        return back();
                    }
                } catch (\Exception $e) {
                    info(["line___{$e->getLine()}", $e->getMessage()]);
                    Toastr::error(translate('messages.Invalid_AvailableTimeEnds_or_AvailableTimeStarts_on_id') . ' ' . $collection['Id']);
                    return back();
                }



                array_push($data, [
                    'id' => $collection['Id'],
                    'name' => $collection['Name'],
                    'description' => $collection['Description'],
                    'image' => $collection['Image'],
                    'images' => $collection['Images'] ?? json_encode([]),
                    'category_id' => $collection['SubCategoryId'] ? $collection['SubCategoryId'] : $collection['CategoryId'],
                    'category_ids' => json_encode([['id' => $collection['CategoryId'], 'position' => 0], ['id' => $collection['SubCategoryId'], 'position' => 1]]),
                    'unit_id' => is_int($collection['UnitId']) ? $collection['UnitId'] : null,
                    'stock' => is_numeric($collection['Stock']) ? abs($collection['Stock']) : 0,
                    'price' => $collection['Price'],
                    'discount' => $collection['Discount'],
                    'discount_type' => $collection['DiscountType'],
                    'available_time_starts' => $collection['AvailableTimeStarts'] ?? '00:00:00',
                    'available_time_ends' => $collection['AvailableTimeEnds'] ?? '23:59:59',
                    'variations' => $module_type == 'food' ? json_encode([]) : $collection['Variations'] ?? json_encode([]),
                    'food_variations' => $module_type == 'food' ? $collection['Variations'] ?? json_encode([]) : json_encode([]),
                    'add_ons' => $collection['AddOns'] ? ($collection['AddOns'] == "" ? json_encode([]) : $collection['AddOns']) : json_encode([]),
                    'attributes' => $collection['Attributes'] ? ($collection['Attributes'] == "" ? json_encode([]) : $collection['Attributes']) : json_encode([]),
                    'store_id' => Helpers::get_store_id(),
                    'module_id' => Helpers::get_store_data()->module_id,
                    'status' => $collection['Status'] == 'active' ? 1 : 0,
                    'veg' => $collection['Veg'] == 'yes' ? 1 : 0,
                    'recommended' => $collection['Recommended'] == 'yes' ? 1 : 0,
                    'updated_at' => now(),
                    'choice_options' => $module_type == 'food' ? json_encode([]) : $collection['ChoiceOptions'] ?? json_encode([]),
                ]);

                if ($product_approval_active && ((data_get($product_approval_datas, 'Update_anything_in_product_details', null) == 1) || (data_get($product_approval_datas, 'Update_product_price', null) == 1) || (data_get($product_approval_datas, 'Update_product_variation', null) == 1))) {

                    array_push($temp_data, [
                        // 'id' => $item_id + $key +1,
                        'name' => $data[$key]['name'],
                        'description' => $data[$key]['description'],
                        'image' => $data[$key]['image'],
                        'images' =>  $data[$key]['images'],
                        'category_id' =>  $data[$key]['category_id'],
                        'category_ids' => $data[$key]['category_ids'],
                        'unit_id' => $data[$key]['unit_id'],
                        'price' =>  $data[$key]['price'],
                        'discount' =>  $data[$key]['discount'],
                        'stock' => $data[$key]['stock'],
                        'discount_type' =>   $data[$key]['discount_type'],
                        'available_time_starts' => $data[$key]['available_time_starts'],
                        'available_time_ends' => $data[$key]['available_time_ends'],
                        'variations' => $data[$key]['variations'],
                        'food_variations' => $data[$key]['food_variations'],
                        'add_ons' =>  $data[$key]['add_ons'],
                        'store_id' => $data[$key]['store_id'],
                        'attributes' =>  $data[$key]['attributes'],
                        'veg' => $data[$key]['veg'],
                        'status' => $data[$key]['status'],
                        'recommended' => $data[$key]['recommended'],
                        'module_id' => $data[$key]['module_id'],
                        'item_id' => $data[$key]['id'],
                        // 'slug' => null,
                        'tag_ids' => json_encode([]),
                        'choice_options' => $data[$key]['choice_options'],

                        'updated_at' => now()
                    ]);
                }
            }
            $id = $collections->pluck('Id')->toArray();
            if (Item::whereIn('id', $id)->doesntExist()) {
                Toastr::error(translate('messages.Item_doesnt_exist_at_the_database'));
                return back();
            }
        } catch (\Exception $e) {
            info(["line___{$e->getLine()}", $e->getMessage()]);
            Toastr::error(translate('messages.failed_to_import_data'));
            return back();
        }
        try {
            DB::beginTransaction();

            $chunkSize = 100;

            if (count($temp_data) > 0) {
                $message = translate('messages.Products_are_added_for_the_admin_approval');
                $chunk_items = array_chunk($temp_data, $chunkSize);

                foreach ($chunk_items as $key => $chunk_item) {
                    DB::table('temp_products')->upsert($chunk_item, ['item_id', 'module_id'], ['name', 'description', 'image', 'images', 'category_id', 'category_ids', 'unit_id', 'stock', 'price', 'discount', 'discount_type', 'available_time_starts', 'available_time_ends', 'variations', 'food_variations', 'add_ons', 'attributes', 'store_id', 'status', 'veg', 'recommended', 'tag_ids', 'choice_options']);
                }
            } else {
                $chunk_items = array_chunk($data, $chunkSize);
                foreach ($chunk_items as $key => $chunk_item) {
                    DB::table('items')->upsert($chunk_item, ['id', 'module_id'], ['name', 'description', 'image', 'images', 'category_id', 'category_ids', 'unit_id', 'stock', 'price', 'discount', 'discount_type', 'available_time_starts', 'available_time_ends', 'variations', 'food_variations', 'add_ons', 'attributes', 'store_id', 'status', 'veg', 'recommended', 'updated_at', 'choice_options']);
                }
            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            info(["line___{$e->getLine()}", $e->getMessage()]);
            Toastr::error(translate('messages.failed_to_import_data'));
            return back();
        }

        Toastr::success(count($data) . ' ' . $message);
        return back();
    }

    public function bulk_export_index()
    {
        return view('vendor-views.product.bulk-export');
    }

    public function bulk_export_data(Request $request)
    {
        if (!Helpers::get_store_data()->item_section) {
            Toastr::warning(translate('messages.permission_denied'));
            return back();
        }

        $request->validate([
            'type' => 'required',
            'start_id' => 'required_if:type,id_wise',
            'end_id' => 'required_if:type,id_wise',
            'from_date' => 'required_if:type,date_wise',
            'to_date' => 'required_if:type,date_wise'
        ]);
        $products = Item::when($request['type'] == 'date_wise', function ($query) use ($request) {
            $query->whereBetween('created_at', [$request['from_date'] . ' 00:00:00', $request['to_date'] . ' 23:59:59']);
        })
            ->when($request['type'] == 'id_wise', function ($query) use ($request) {
                $query->whereBetween('id', [$request['start_id'], $request['end_id']]);
            })
            ->where('store_id', Helpers::get_store_id())
            ->get();
        return (new FastExcel(ProductLogic::format_export_items(Helpers::Export_generator($products), Helpers::get_store_data()->module->module_type)))->download('Items.xlsx');
    }

    public function stock_limit_list(Request $request)
    {
        $category_id = $request->query('category_id', 'all');
        $type = $request->query('type', 'all');
        $items = Item::when(is_numeric($category_id), function ($query) use ($category_id) {
            return $query->whereHas('category', function ($q) use ($category_id) {
                return $q->whereId($category_id)->orWhere('parent_id', $category_id);
            });
        })
            ->type($type)->latest()->paginate(config('default_pagination'));
        $category = $category_id != 'all' ? Category::findOrFail($category_id) : null;
        return view('vendor-views.product.stock_limit_list', compact('items', 'category', 'type'));
    }
    public function price_update_list(Request $request)
    {
        $category_id = $request->query('category_id', 'all');
        $type = $request->query('type', 'all');
        $items = Item::when(is_numeric($category_id), function ($query) use ($category_id) {
            return $query->whereHas('category', function ($q) use ($category_id) {
                return $q->whereId($category_id)->orWhere('parent_id', $category_id);
            });
        })
            ->type($type)->latest()->paginate(config('default_pagination'));
        $category = $category_id != 'all' ? Category::findOrFail($category_id) : null;
        return view('vendor-views.product.price_update_list', compact('items', 'category', 'type'));
    }

    public function get_variations(Request $request)
    {
        $product = Item::find($request['id']);

        return response()->json([
            'view' => view('vendor-views.product.partials._update_stock', compact('product'))->render()
        ]);
    }
    public function get_price_variations(Request $request)
    {
        $product = Item::find($request['id']);

        return response()->json([
            'view' => view('vendor-views.product.partials._update_price', compact('product'))->render()
        ]);
    }

    public function service_request_list()
    {

        // ->leftJoin('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
        // ->whereNull('accepted_service_requests.tieup')
        // ->where('accepted_service_requests.vendor_id', Helpers::get_store_id())
        $d =  DB::table('service_requests')->orderBy('id', 'desc')->get();
        //    prx($d);
        // echo 'fd2ss22   ddd123';
        $product = DB::table('service_requests')
            ->join('items', 'service_requests.item_id', '=', 'items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->join('users', 'service_requests.user_id', '=', 'users.id')
            ->where(function ($query) {
                $query->whereRaw('NOT FIND_IN_SET(?, service_requests.accepted_by)', [Helpers::get_store_id()])
                    ->orWhereNull('service_requests.accepted_by');
            })
            // ->whereNull('accepted_service_requests.tieup')
            ->whereRaw('FIND_IN_SET(?, items.store_ids)', [Helpers::get_store_id()])
            ->where('service_requests.created_at', '>', now()->subMinutes(Helpers::get_lead_exp_minutes()))
            ->select(
                'service_requests.*',
                'items.name as item_name',
                'items.image as image',
                'categories.name as category_name',
                'users.f_name as f_name',
                'users.id as uid'
            )
            ->distinct('service_requests.id')
            ->get();
        // prx($product);
        $heading = 'New Leads';
        return view('vendor-views.product.service_request_list', compact('product', 'heading'));
    }
    public function service_request_accepted()
    {
        // ->where('accepted_service_requests.vendor_id', Helpers::get_store_id())
        // ->whereRaw('FIND_IN_SET(?, service_requests.accepted_by)', [Helpers::get_store_id()])
        // ->where('service_requests.created_at', '>', now()->subMinutes(Helpers::get_lead_exp_minutes()))

        $product = DB::table('service_requests')
            ->join('items', 'service_requests.item_id', '=', 'items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->join('users', 'service_requests.user_id', '=', 'users.id')
            ->join('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->where(function ($query) {
                $query->whereNull('accepted_service_requests.tieup')
                    ->orWhere('accepted_service_requests.current_status', 'Confirmation Request Sent');
            })
            ->whereRaw('FIND_IN_SET(?, items.store_ids)', [Helpers::get_store_id()])
            ->select('accepted_service_requests.tieup', 'service_requests.*', 'items.name as item_name', 'items.image as image', 'categories.name as category_name', 'users.f_name as f_name', 'users.id as uid')
            ->distinct('service_requests.id')
            ->get();

        // prx($product);
        // ->toSql();      
        // echo '2w';
        $heading = 'Accepted Leads';

        return view('vendor-views.product.service_request_list', compact('product', 'heading'));
    }

    public function stock_update(Request $request)
    {
        $product = Item::find($request['product_id']);

        // Decode existing variations
        $existingVariations = json_decode($product->variations, true) ?? [];

        if ($request->has('type')) {
            foreach ($request['type'] as $str) {
                $key = array_search($str, array_column($existingVariations, 'type'));

                if ($key !== false) {
                    $stockValue = abs($request['stock_' . str_replace('.', '_', $str)]);
                    $existingVariations[$key]['stock'] = $stockValue;
                }
            }

            $product->variations = json_encode($existingVariations);
        }

        // Update overall stock if needed
        if ($request->has('current_stock')) {
            $product->stock = $request['current_stock'];
        }

        $product->save();

        Toastr::success(translate("messages.product_stock_updated_successfully"));
        return back();
    }


    public function price_update(Request $request)
    {
        $product = Item::find($request['product_id']);

        // Decode existing variations
        $existingVariations = json_decode($product->variations, true) ?? [];

        if ($request->has('type')) {
            foreach ($request['type'] as $str) {
                $key = array_search($str, array_column($existingVariations, 'type'));

                if ($key !== false) {
                    $stockValue = abs($request['price_' . str_replace('.', '_', $str)]);
                    $existingVariations[$key]['price'] = $stockValue;
                }
            }

            $product->variations = json_encode($existingVariations);
        }

        // Update overall stock if needed
        if ($request->has('main_price')) {
            $product->price = $request['main_price'];
        }

        $product->save();

        Toastr::success(translate("messages.product_price_updated_successfully"));
        return back();
    }

    public function food_variation_generator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'options' => 'required',
        ]);

        $food_variations = [];
        if (isset($request->options)) {
            foreach (array_values($request->options) as $key => $option) {

                $temp_variation['name'] = $option['name'];
                $temp_variation['type'] = $option['type'];
                $temp_variation['min'] = $option['min'] ?? 0;
                $temp_variation['max'] = $option['max'] ?? 0;
                $temp_variation['required'] = $option['required'] ?? 'off';
                if ($option['min'] > 0 &&  $option['min'] > $option['max']) {
                    $validator->getMessageBag()->add('name', translate('messages.minimum_value_can_not_be_greater_then_maximum_value'));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                if (!isset($option['values'])) {
                    $validator->getMessageBag()->add('name', translate('messages.please_add_options_for') . $option['name']);
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                if ($option['max'] > count($option['values'])) {
                    $validator->getMessageBag()->add('name', translate('messages.please_add_more_options_or_change_the_max_value_for') . $option['name']);
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                $temp_value = [];

                foreach (array_values($option['values']) as $value) {
                    if (isset($value['label'])) {
                        $temp_option['label'] = $value['label'];
                    }
                    $temp_option['optionPrice'] = $value['optionPrice'];
                    array_push($temp_value, $temp_option);
                }
                $temp_variation['values'] = $temp_value;
                array_push($food_variations, $temp_variation);
            }
        }

        return response()->json([
            'variation' => json_encode($food_variations)
        ]);
    }

    public function variation_generator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'choice' => 'required',
        ]);
        $choice_options = [];
        if ($request->has('choice')) {
            foreach ($request->choice_no as $key => $no) {
                $str = 'choice_options_' . $no;
                if ($request[$str][0] == null) {
                    $validator->getMessageBag()->add('name', translate('messages.attribute_choice_option_value_can_not_be_null'));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                $temp['name'] = 'choice_' . $no;
                $temp['title'] = $request->choice[$key];
                $temp['options'] = explode(',', implode('|', preg_replace('/\s+/', ' ', $request[$str])));
                array_push($choice_options, $temp);
            }
        }

        $variations = [];
        $options = [];
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('|', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }
        //Generates the combinations of customer choice options
        $combinations = Helpers::combinations($options);
        if (count($combinations[0]) > 0) {
            foreach ($combinations as $key => $combination) {
                $str = '';
                foreach ($combination as $k => $temp) {
                    if ($k > 0) {
                        $str .= '-' . str_replace(' ', '', $temp);
                    } else {
                        $str .= str_replace(' ', '', $temp);
                    }
                }
                $temp = [];
                $temp['type'] = $str;
                $temp['price'] = abs($request['price_' . str_replace('.', '_', $str)]);
                $temp['stock'] = abs($request['stock_' . str_replace('.', '_', $str)]);
                array_push($variations, $temp);
            }
        }
        //combinations end

        return response()->json([
            'choice_options' => json_encode($choice_options),
            'variation' => json_encode($variations),
            'attributes' => $request->has('attribute_id') ? json_encode($request->attribute_id) : json_encode([])
        ]);
    }



    public function pending_item_list(Request $request)
    {

        abort_if(Helpers::get_mail_status('product_approval') != 1, 404);

        $key = explode(' ', $request['search']);
        $sub_category_id = $request->query('sub_category_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $type = $request->query('type', 'all');
        $items = TempProduct::when(is_numeric($category_id), function ($query) use ($category_id) {
            return $query->whereHas('category', function ($q) use ($category_id) {
                return $q->whereId($category_id)->orWhere('parent_id', $category_id);
            });
        })
            ->where('store_id', Helpers::get_store_id())
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->when(is_numeric($sub_category_id), function ($query) use ($sub_category_id) {
                return $query->where('category_id', $sub_category_id);
            })

            ->type($type)->latest()->paginate(config('default_pagination'));
        $sub_categories = $category_id != 'all' ? Category::where('parent_id', $category_id)->get(['id', 'name']) : [];
        $category = $category_id != 'all' ? Category::findOrFail($category_id) : null;
        return view('vendor-views.product.pending_list', compact('items', 'category', 'type', 'sub_categories'));
    }

    public function requested_item_view($id)
    {
        $product = TempProduct::withoutGlobalScope('translate')->with(['translations', 'store', 'unit'])->findOrFail($id);
        return view('vendor-views.product.requested_product_view', compact('product'));
    }
    public function store_temp_data($data, $request, $tag_ids, $update = null)
    {
        $temp_item = TempProduct::firstOrNew(
            ['item_id' => $data->id]
        );

        $old_img = $temp_item->image ?? null;
        $old_images = $temp_item->images ?? [];
        // $temp_item->image = $data->image;
        // $temp_item->images = $data->images;

        $temp_item->name = $request->name[array_search('default', $request->lang)];
        $temp_item->description =   $request->description[array_search('default', $request->lang)];
        $temp_item->specifications =  $data->specifications;

        $temp_item->store_id = $data->store_id;
        $temp_item->module_id = $data->module_id;
        $temp_item->unit_id = $data->unit_id;
        $temp_item->item_id = $data->id;

        $temp_item->category_id = $data->category_id;
        $temp_item->category_ids = $data->category_ids;
        $temp_item->slug = $data->slug;

        $temp_item->choice_options = $data->choice_options;
        $temp_item->food_variations = $data->food_variations;
        $temp_item->variations = $data->variations;
        $temp_item->add_ons = $data->add_ons;
        $temp_item->attributes = $data->attributes;

        $temp_item->price = $data->price;
        $temp_item->mrp_price = $data->mrp_price;
        $temp_item->asking_price = $data->asking_price;
        $temp_item->discount = $data->discount;
        $temp_item->discount_type = $data->discount_type;
        $temp_item->tag_ids = json_encode($tag_ids);

        $temp_item->tax = $data->tax;
        $temp_item->tax_type = $data->tax_type;
        $temp_item->hsn_code = $data->hsn_code;

        $temp_item->available_time_starts = $data->available_time_starts;
        $temp_item->available_time_ends = $data->available_time_ends;
        $temp_item->maximum_cart_quantity = $data->maximum_cart_quantity;
        $temp_item->veg = $data->veg ?? 0;
        $temp_item->organic = $data->organic ?? 0;
        $temp_item->basic =  $data->basic ?? 0;
        $temp_item->common_condition_id =  $data->common_condition_id;
        $temp_item->stock =  $data->stock ?? 0;
        $module_type = Helpers::get_store_data()->module->module_type;
        if ($module_type == 'pharmacy') {
            $temp_item->common_condition_id =  $request->condition_id ?? 0;
            $temp_item->basic =  $request->basic ?? 0;
        }

        if ($request->has('image')) {

            if ($old_img) {
                $temp_image_name =   Helpers::update('product/', $old_img, 'png', $request->file('image'));
            } else {
                $temp_image_name =   Helpers::upload('product/', 'png', $request->file('image'));
            }
            $temp_item->image = $temp_image_name;
        } else {
            $oldPath = storage_path("app/public/product/{$data->image}");
            $temp_image_name = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . ".png";
            $newPath = storage_path("app/public/product/{$temp_image_name}");
            if (File::exists($oldPath)) {
                File::copy($oldPath, $newPath);
            }
            $temp_item->image = $temp_image_name;
        }


        // $old_images =  count($old_images) > 0  ? json_decode($old_images , true) : [];
        // $old_images =  count($data->images) > 0  ? json_decode($$data->images , true) : [];


        // $uniqueValues = array_diff($data->images, $old_images);


        // $images = $data->images;
        // if ($request->has('item_images')){
        //     foreach ($request->item_images as $img) {
        //         $image = Helpers::upload('product/', 'png', $img);
        //         array_push($images, $image);
        //     }
        // }








        // foreach($images as $key=> $value){
        //     $oldPath = storage_path("app/public/product/{$value}");
        //     $newFileName =\Carbon\Carbon::now()->toDateString() . "-" . uniqid() . ".png" ;
        //     $newPath = storage_path("app/public/product/{$newFileName}");
        //     if (File::exists($oldPath)) {
        //         File::copy($oldPath, $newPath);
        //     }
        //     $images[]=$newFileName;
        // }


        // $p->images = $images;


        $temp_item->images = $data->images;
        if ($update) {
            $temp_item->is_rejected = 0;
        }

        $temp_item->save();
        if ($module_type == 'pharmacy') {
            DB::table('pharmacy_item_details')
                ->updateOrInsert(
                    ['temp_product_id' => $temp_item->id],
                    [
                        'common_condition_id' => $request->condition_id,
                        'is_basic' => $request->basic ?? 0,
                        'item_id' => null
                    ]
                );
        }
        Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'TempProduct', data_id: $temp_item->id, data_value: $temp_item->name);
        Helpers::add_or_update_translations(request: $request, key_data: 'description', name_field: 'description', model_name: 'TempProduct', data_id: $temp_item->id, data_value: $temp_item->description);
        return true;
    }



    public function product_gallery(Request $request)
    {
        $key = explode(' ', $request['search']);
        $category_id = $request->query('category_id', 'all');
        $type = $request->query('type', 'all');
        $settings = Helpers::get_mail_status('product_gallery');
        $settings_access = Helpers::get_mail_status('access_all_products');

        $items = Item::when($settings_access == 1, function ($q) {
            $q->withoutGlobalScope(StoreScope::class);
        })
            ->where('is_approved', 1)
            ->when(is_numeric($category_id), function ($query) use ($category_id) {
                return $query->whereHas('category', function ($q) use ($category_id) {
                    return $q->whereId($category_id)->orWhere('parent_id', $category_id);
                });
            })
            ->when($request['search'], function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->type($type)
            ->inRandomOrder()
            ->module(Helpers::get_store_data()->module_id)
            ->limit(12)
            ->get();

        $category = $category_id != 'all' ? Category::findOrFail($category_id) : null;

        return view('vendor-views.product.product_gallery', compact('items', 'category', 'type'));
    }

    public function flash_sale(Request $request)
    {
        $key = explode(' ', $request['search']);

        $items = FlashSaleItem::with('flashSale')
            ->wherehas('item', function ($q) {
                $q->where('store_id', Helpers::get_store_id());
            })
            ->when(isset($key), function ($q) use ($key) {
                $q->whereHas('item', function ($q) use ($key) {
                    $q->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('name', 'like', "%{$value}%");
                        }
                    });
                });
            })

            ->paginate(config('default_pagination'));

        return view('vendor-views.product.flash_sale.list', compact('items'));
    }
}
