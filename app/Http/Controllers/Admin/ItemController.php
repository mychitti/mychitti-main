<?php

namespace App\Http\Controllers\Admin;


use Carbon\Carbon;
use App\Models\Tag;
use App\Models\Item;
use App\Models\LeadCharge;
use App\Models\Store;
use App\Models\Review;
use App\Models\Category;
use App\Models\ServiceStatus; 
use App\Scopes\StoreScope; 
use App\Models\TempProduct; 
use App\Models\Translation;
use Illuminate\Support\Str;
use App\Models\ItemCampaign;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers; 
use App\Exports\ItemListExport;
use App\Models\CommonCondition;
use Illuminate\Validation\Rule;
use App\Exports\StoreItemExport;
use App\Exports\ItemReviewExport;
use Illuminate\Support\Facades\DB;
use App\CentralLogics\ProductLogic;
use App\Models\PharmacyItemDetails;
use App\Http\Controllers\Controller;
use App\Imports\KeywordsImport;
use App\Models\ActionLog;
use App\Models\BusinessSetting;
use App\Models\FeeCategory;
use App\Models\ItemAreaKeyword;
use App\Models\ItemFaq;
use App\Models\ItemVariationDetail;
use App\Models\LocationKeyword;
use App\Models\ProductKeyword;
use App\Models\ServiceKeyword;
use App\Models\Zone;
use App\Models\SeoContent;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where(['position' => 0])->get();
        $keywords = ProductKeyword::where('status', 1)->get();
        $fee_categories = FeeCategory::all();
        $zones = Zone::active()->get();
        return view('admin-views.product.index', compact('zones', 'categories', 'keywords', 'fee_categories'));
    }
    public function saveSEO($request, $item_id, $edit = false)
    {

        try {
            if ($edit) {
                // delete existing 
                SeoContent::where('data', $item_id)->delete();
            }

            if (empty($request)) {
                return;
            }
            foreach ($request->all() as $key => $value) {

                // Determine seo_type based on key prefix
                if (str_starts_with($key, 'seo_editor_')) {
                    $seoType = 'item';
                } elseif (str_starts_with($key, 'faq_editor_')) {
                    $seoType = 'faq';
                } else {
                    continue;
                }

                if (empty($value)) {
                    continue;
                }

                $decoded = base64_decode($value, true);
                if ($decoded === false) {
                    continue;
                }

                $content = urldecode($decoded);
                if (trim($content) === '') {
                    continue;
                }
                SeoContent::create([
                    'data'     => $item_id,
                    'seo_type' => $seoType,
                    'content'  => $content,
                ]);
            }
        } catch (\Throwable $th) {
            // log error if needed
            // Log::error($th);
        }
    }

    public function terms_and_condtions_store(Request $request)
    {
        $request->validate([
            'gatepass_tnc' => 'required',
            'quotation_tnc' => 'required',
        ], [
            'gatepass_tnc.required' => translate('messages.gatepass_terms_and_conditions_required'),
            'quotation_tnc.required' => translate('messages.quotation_terms_and_conditions_required'),
        ]);
        $gatepass_tnc =  $this->base64UrlDecode($request->gatepass_tnc);
        $quotation_tnc =  $this->base64UrlDecode($request->quotation_tnc);

        BusinessSetting::updateOrInsert(
            ['key' => 'gatepass_terms_and_conditions'],
            ['value' => $gatepass_tnc]
        );
        BusinessSetting::updateOrInsert(
            ['key' => 'quotation_terms_and_conditions'],
            ['value' => $quotation_tnc]
        );
        return response()->json(['status' => true, 'message' => translate('messages.terms_and_conditions_updated_successfully')]);
    }
    public function terms_and_condtions(Request $request)
    {

        $quotattion_tnc = BusinessSetting::where('key', 'quotation_terms_and_conditions')->first()?->value ?? '';
        $gatepass_tnc = BusinessSetting::where('key', 'gatepass_terms_and_conditions')->first()?->value ?? '';

        return view('admin-views.product.terms-and-conditions', compact('quotattion_tnc', 'gatepass_tnc'));
    }
    function base64UrlDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $data = strtr($data, '-_', '+/');
        return base64_decode($data);
    }
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name.0' => 'required',
            'name.*' => 'max:191',
            'category_id' => 'required',
            'image' => [
                Rule::requiredIf(function () use ($request) {
                    return (Config::get('module.current_module_type') != 'food' && $request?->product_gellary == null);
                })
            ],
            'faqs.*.question' => 'nullable|string|max:500',
            'faqs.*.answer'   => 'nullable|string',
            'mrp_price' => 'required|numeric|between:.01,999999999999.99',
            'price' => 'required|numeric',
            'asking_price' => 'required|numeric|between:.01,999999999999.99',
            'discount' => 'required|numeric|min:0',
            'description.*' => 'max:1000',
            'fee_category' => 'required',
            'hsn_code' => 'required',
            'name.0' => 'required',
            'description.0' => 'required',
        ], [
            'description.*.max' => translate('messages.description_length_warning'),
            'name.0.required' => translate('messages.item_name_required'),
            'category_id.required' => translate('messages.category_required'),
            'image.required' => translate('messages.thumbnail image is required'),
            'name.0.required' => translate('default_name_is_required'),
            'description.0.required' => translate('default_description_is_required'),
        ]);
        $dis = ($request['mrp_price'] / 100) * $request['discount'];

        if ($request['mrp_price'] <= $dis) {
            $validator->getMessageBag()->add('unit_price', translate("Discount amount can't be greater than 100%"));
        }
        if (Config::get('module.current_module_id') != 6 && ($request->store_id == '' || $request->store_id == null)) {
            $validator->getMessageBag()->add('store_id', "Store is required");
        }
        if ($request['mrp_price'] <= $dis || $validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
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

        $item = new Item;
        $item->name = $request->name[array_search('default', $request->lang)];

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
        $item->category_ids = json_encode($category);
        $item->category_id = $request->sub_category_id ? $request->sub_category_id : $request->category_id;
        $item->description =  $request->description[array_search('default', $request->lang)];
        $specifications = isset($request->specifications) ? urldecode(base64_decode($request->specifications)) : null;
        $item->specifications =  $specifications;

        // $item->keywords =  implode(',',$request->keywords);

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
        $item->choice_options = json_encode($choice_options);
        $variations = [];
        $options = [];
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('|', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }

        // prx($request->all());
        //combinations end

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

        $item->food_variations = json_encode($food_variations);
        $item->variations = json_encode($variations);

        $item->price = $request->price;
        $item->mrp_price = $request->mrp_price;
        $item->asking_price = $request->asking_price;
        $item->mychitty_fee = $request->mychitty_fee;
        $item->fee_category = $request->fee_category;

        $item->image =  $request->has('image') ? Helpers::upload('product/', 'png', $request->file('image')) : $newFileName ?? null;
        $item->available_time_starts = $request->available_time_starts ?? '00:00:00';
        $item->available_time_ends = $request->available_time_ends ?? '23:59:59';
        $item->discount = $request->discount_type == 'amount' ? $request->discount : $request->discount;
        $item->hsn_code = $request->hsn_code;
        $item->tax = $request->gst_percent;
        $item->tax_type = 'percent';
        $item->discount_type = 'percent';
        $item->unit_id = $request->unit;
        $item->attributes = $request->has('attribute_id') ? json_encode($request->attribute_id) : json_encode([]);
        $item->add_ons = $request->has('addon_ids') ? json_encode($request->addon_ids) : json_encode([]);
        $item->store_id = $request->store_id;
        $item->maximum_cart_quantity = $request->maximum_cart_quantity;
        if (Config::get('module.current_module_type') == 'ecommerce') {
            $item->veg = $request->veg;
        }

        $item->module_id = Config::get('module.current_module_id');
        $module_type = Config::get('module.current_module_type');
        if ($module_type == 'grocery') {
            $item->organic = $request->organic ?? 0;
        }
        $item->stock = $request->current_stock ?? 0;
        $item->images = $images;

        //SEO 
        $item->meta_title = $request->meta_title;
        $item->seo_heading = $request->seo_heading;
        $item->meta_desc = $request->meta_desc;
        $item->short_desc = $request->short_desc;

        $item->save();

        // faq for google 
        DB::transaction(function () use ($request, $item) {

            $faqs = $request->faqs ?? [];

            foreach ($faqs as $index => $row) {

                if (
                    empty(trim($row['question'] ?? '')) &&
                    empty(trim($row['answer'] ?? ''))
                ) {
                    continue;
                }

                ItemFaq::create([
                    'item_id'   => $item->id,
                    'question'  => $row['question'],
                    'answer'    => $row['answer'],
                    'sort_order' => $index
                ]);
            }
        });


        try {
            ActionLog::create([
                'user_id' => auth('admin')->id(),
                'user_type' => 'admin',
                'action' => 'created',
                'model_type' => 'item', // e.g. App\Models\Category
                'model_id' => $item->id,
                'description' => Config::get('module.current_module_id') == 5 ? 'Created product: ' . $item->name : 'Created service: ' . $item->name,
                'created_at' => now(),
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }

        // add variations 
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
                $vrDetails->item_id = $item->id;
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
        $item->update([
            'variations' => json_encode($variations)
        ]);
        // add vairations end 


        // AREA KEYWORDS UPDATE
        // if ($request->filled('areas')) {

        //     ItemAreaKeyword::create([
        //         'item_id' => $item->id,
        //         'keyword' => $request->areas,
        //     ]);
        // }

        // export service keywords
        if ($request->hasFile('keyword_excel')) {
            Excel::import(new KeywordsImport($item->id), $request->file('keyword_excel'));
        }
        // additional keywords 
        if ($request->has('more_keywords') && $request->more_keywords != '') {
            $rows = explode(',', $request->more_keywords);
            foreach ($rows as $kw) {
                ServiceKeyword::create([
                    'service_id' => $item->id,
                    'keyword' => $kw,
                ]);
            }
        }

        $item->tags()->sync($tag_ids);
        if ($module_type == 'pharmacy') {
            $item_details = new PharmacyItemDetails();
            $item_details->item_id = $item->id;
            $item_details->common_condition_id = $request->condition_id;
            $item_details->is_basic = $request->basic ?? 0;
            $item_details->save();
        }
        $this->saveSEO($request, $item->id);

        Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'Item', data_id: $item->id, data_value: $item->name);
        Helpers::add_or_update_translations(request: $request, key_data: 'description', name_field: 'description', model_name: 'Item', data_id: $item->id, data_value: $item->description);

        return response()->json(['success' => translate('messages.product_added_successfully')], 200);
    }

    public function keywords(Request $request)
    {
        $keywords = ProductKeyword::all();
        $locationKeywords = LocationKeyword::all();

        return view('admin-views.product.keywords', compact('keywords', 'locationKeywords'));
    }

    public function delete_keyword($id)
    {

        $query =  ProductKeyword::find($id)->delete();
        Toastr::success('Keyword Deleted Successfully');
        return back();
    }
    public function delete_location_keyword($id)
    {

        $query =  LocationKeyword::find($id)->delete();
        Toastr::success('Keyword Deleted Successfully');
        return back();
    }

    public function keyword_save(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'keyword' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $product = new ProductKeyword;
        $product->keyword = ucfirst($request->keyword);
        $product->created_at = date('Y-m-d H:i:s');
        if ($product->save()) {
            Toastr::success('Service Keyword saved successfully');
        } else {
            Toastr::error('Some error occured');
        };
        return back();
    }
    public function location_keyword_save(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'keyword' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $product = new LocationKeyword;
        $product->keyword = ucfirst($request->keyword);
        $product->created_at = date('Y-m-d H:i:s');
        if ($product->save()) {
            Toastr::success('Location Keyword saved successfully');
        } else {
            Toastr::error('Some error occured');
        };
        return back();
    }
    public function request_status(Request $request)
    {
        $statuses = ServiceStatus::orderBy('removable', 'asc')->get();
        return view('admin-views.service.status', compact('statuses'));
    }


    public function delete_status($id)
    {

        $query =  ServiceStatus::find($id)->delete();
        Toastr::success('Status Deleted Successfully');
        return back();
    }

    public function status_save(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'status' => 'required',
        ], [
            'status.required' => 'Status field is required'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $product = new ServiceStatus;
        $product->status = ucfirst($request->status);
        $product->created_at = date('Y-m-d H:i:s');
        if ($product->save()) {
            Toastr::success('Status saved successfully');
        } else {
            Toastr::error('Some error occured');
        };
        return back();
    }


    public function view($id)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->where(['id' => $id])->first();
        $reviews = Review::where(['item_id' => $id])->latest()->paginate(config('default_pagination'));
        return view('admin-views.product.view', compact('product', 'reviews'));
    }

    public function edit(Request $request, $id)
    {
        $temp_product = false;
        if ($request->temp_product) {
            $product = TempProduct::withoutGlobalScope(StoreScope::class)->withoutGlobalScope('translate')->with('store', 'category', 'module')->findOrFail($id);
            $temp_product = true;
        } else {
            $product = Item::withoutGlobalScope(StoreScope::class)->withoutGlobalScope('translate')->with('store', 'category', 'module')->findOrFail($id);
        }
        if (!$product) {
            Toastr::error(translate('messages.item_not_found'));
            return back();
        }
        $temp = $product->category;
        if ($temp?->position) {
            $sub_category = $temp;
            $category = $temp->parent;
        } else {
            $category = $temp;
            $sub_category = null;
        }
        $fee_categories = FeeCategory::all();
        $zones = Zone::active()->get();
        $item_area_keywords = ItemAreaKeyword::where('item_id', $id)->first();
        $keywords = ServiceKeyword::where('service_id', $id)->get();
        $seoContents = SeoContent::where('data', $id)->where('seo_type', 'item')->get();
        $faqContents  = SeoContent::where('data', $id)->where('seo_type', 'faq')->get();
        $faqs = ItemFaq::where('item_id', $id)
            ->orderBy('sort_order')
            ->get();

        // prx($item_area_keywords->keyword);
        return view('admin-views.product.edit', compact('faqs', 'seoContents', 'faqContents', 'product', 'item_area_keywords', 'zones', 'sub_category', 'category', 'temp_product', 'keywords', 'fee_categories'));
    }

    public function status(Request $request)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->findOrFail($request->id);
        $product->status = $request->status;
        $product->save();
        Toastr::success(translate('messages.item_status_updated'));
        return back();
    }



    public function get_areas_in_zone(Request $request)
    {
        $zone = Zone::find($request->zone_id);
        $coordinates = $zone->coordinates;

        $areas = Helpers::getAreasFromGoogleForZone($coordinates);

        return collect($areas)->pluck('name');
    }

    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'array',
            'name.0' => 'required',
            'name.*' => 'max:191',
            'category_id' => 'required',
            'price' => 'required|numeric|between:.01,999999999999.99',
            // 'store_id' => 'required',
            'description' => 'array',
            'description.*' => 'max:1000',
            'discount' => 'required|numeric|min:0',
            'fee_category' => 'required',
            'name.0' => 'required',
            'hsn_code' => 'required',
            'keyword_excel' => 'nullable|mimes:xlsx,xls',
            'description.0' => 'required',
            'faqs.*.question' => 'nullable|string|max:500',
            'faqs.*.answer'   => 'nullable|string',
        ], [
            'description.*.max' => translate('messages.description_length_warning'),
            'category_id.required' => translate('messages.category_required'),
            'name.0.required' => translate('default_name_is_required'),
            'description.0.required' => translate('default_description_is_required'),
        ]);
        $request['discount_type'] = 'percent';
        if ($request['discount_type'] == 'percent') {
            $dis = ($request['price'] / 100) * $request['discount'];
        } else {
            $dis = $request['discount'];
        }

        if ($request['price'] <= $dis) {
            $validator->getMessageBag()->add('unit_price', translate("Discount amount can't be greater than 100%"));
        }

        if ($request['price'] <= $dis || $validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        if (Config::get('module.current_module_id') != 6 && ($request->store_id == '' || $request->store_id == null)) {
            $validator->getMessageBag()->add('store_id', "Store is required");
        }

        $item = Item::withoutGlobalScope(StoreScope::class)->find($id);
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

        $item->name = $request->name[array_search('default', $request->lang)];

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

        $item->category_id = $request->sub_category_id ? $request->sub_category_id : $request->category_id;
        $item->category_ids = json_encode($category);
        $item->description =  $request->description[array_search('default', $request->lang)];

        $specifications = isset($request->specifications) ? urldecode(base64_decode($request->specifications)) : null;

        $item->specifications = $specifications;
        $item->keywords = $request->keywords ?  implode(',', $request->keywords) : '';
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
        $item->choice_options = $request->has('attribute_id') ? json_encode($choice_options) : json_encode([]);
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

                $vrDetails = ItemVariationDetail::where('type', $str)->where('item_id', $item->id)->first();
                if ($vrDetails) {
                    $vrImages = json_decode($vrDetails->images); // Initialize the array
                } else {
                    $vrDetails = new ItemVariationDetail();
                    $vrImages = []; // Initialize the array
                    $vrDetails->item_id =  $item->id;;
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
                }
                $vrDetails->description = $request['descs_' . str_replace('.', '_', $str)];
                $vrDetails->specifications = isset($request['specs_' . str_replace('.', '_', $str)]) ? urldecode(base64_decode($request['specs_' . str_replace('.', '_', $str)])) : null;;

                $vrDetails->save();

                $temp = [];
                $temp['type'] = $str;
                $temp['tax'] = $request->gst_percent;
                $temp['price'] = abs($request['price_' . str_replace('.', '_', $str)]);
                $temp['mrpprice'] = abs($request['mrpprice_' . str_replace('.', '_', $str)]);
                $temp['askingprice'] = abs($request['askingprice_' . str_replace('.', '_', $str)]) + abs($request['remainingprice_' . str_replace('.', '_', $str)]);
                $temp['remainingprice'] =  abs($request['remainingprice_' . str_replace('.', '_', $str)]);
                $temp['discount'] = abs($request['discount_' . str_replace('.', '_', $str)]);
                $temp['stock'] = abs($request['stock_' . str_replace('.', '_', $str)]);
                $temp['variations_table_id'] = $vrDetails->id;
                array_push($variations, $temp);
            }
        }
        //combinations end
        $images = $item['images'];
        if ($request->has('item_images')) {
            foreach ($request->item_images as $img) {
                $image = Helpers::upload('product/', 'png', $img);
                array_push($images, $image);
            }
        }

        $food_variations = [];
        if (isset($request->options)) {
            foreach (array_values($request->options) as $key => $option) {
                $temp_variation['name'] = $option['name'];
                $temp_variation['type'] = $option['type'];
                $temp_variation['min'] = $option['min'] ?? 0;
                $temp_variation['max'] = $option['max'] ?? 0;
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
                $temp_variation['required'] = $option['required'] ?? 'off';
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
        // prx($request->has('attribute_id'));
        $slug = Str::slug($request->name[array_search('default', $request->lang)]);
        $item->slug = $item->slug ? $item->slug : "{$slug}{$item->id}";
        $item->food_variations = json_encode($food_variations);
        $item->variations = $request->has('attribute_id') ? json_encode($variations) : json_encode([]);
        $item->price = $request->price;
        $item->fee_category = $request->fee_category;
        $item->mrp_price = $request->mrp_price;
        $item->asking_price = $request->asking_price + $request->remaining_price;
        $item->remaining_amount =  $request->remaining_price;
        $item->discount = $request->discount;
        $item->discount_type = 'percent';
        $item->mychitty_fee = $request->mychitty_fee;

        $item->tax = $request->gst_percent;
        $item->tax_type = 'percent';
        $item->hsn_code = $request->hsn_code;

        $item->image = $request->has('image') ? Helpers::update('product/', $item->image, 'png', $request->file('image')) : $item->image;
        $item->available_time_starts = $request->available_time_starts ?? '00:00:00';
        $item->available_time_ends = $request->available_time_ends ?? '23:59:59';

        $item->discount = $request->discount_type == 'amount' ? $request->discount : $request->discount;
        $item->discount_type = $request->discount_type;
        $item->unit_id = $request->unit;
        $item->attributes = $request->has('attribute_id') ? json_encode($request->attribute_id) : json_encode([]);
        $item->add_ons = $request->has('addon_ids') ? json_encode($request->addon_ids) : json_encode([]);
        $item->store_id = $request->store_id;
        $item->maximum_cart_quantity = $request->maximum_cart_quantity;
        // $item->module_id= $request->module_id;
        $item->stock = $request->current_stock ?? 0;
        $item->organic = $request->organic ?? 0;
        $item->veg = $request->veg;
        $item->images = $images;


        if (Helpers::get_mail_status('product_approval') && $request?->temp_product) {
            $item->temp_product?->translations()->delete();
            $item?->pharmacy_item_details()?->delete();
            if ($item->module->module_type == 'pharmacy') {
                DB::table('pharmacy_item_details')->where('temp_product_id', $item->temp_product?->id)->update([
                    'item_id' => $item->id,
                    'temp_product_id' => null
                ]);
            }
            $item->temp_product?->delete();
            $item->is_approved = 1;
            try {
                $mail_status = Helpers::get_mail_status('product_approve_mail_status_store');
                if (config('mail.status') && $mail_status == '1') {
                    Mail::to($item?->store?->vendor?->email)->send(new \App\Mail\VendorProductMail($item?->store?->name, 'approved'));
                }
            } catch (\Exception $e) {
                info($e->getMessage());
            }
        }

        //SEO 
        $item->meta_title = $request->meta_title;
        $item->seo_heading = $request->seo_heading;
        $item->meta_desc = $request->meta_desc;
        $item->short_desc = $request->short_desc;

        $item->save();

        // FAQ (FORMATTED)
        DB::transaction(function () use ($request, $item) {

            $faqs = $request->faqs ?? [];

            // delete all old FAQs of this item
            ItemFaq::where('item_id', $item->id)->delete();

            foreach ($faqs as $index => $row) {

                if (
                    empty(trim($row['question'] ?? '')) &&
                    empty(trim($row['answer'] ?? ''))
                ) {
                    continue;
                }

                ItemFaq::create([
                    'item_id'    => $item->id,
                    'question'   => $row['question'],
                    'answer'     => $row['answer'],
                    'sort_order' => $index
                ]);
            }
        });

        try {
            ActionLog::create([
                'user_id' => auth('admin')->id(),
                'user_type' => 'admin',
                'action' => 'updated',
                'model_type' => 'item', // e.g. App\Models\Category
                'model_id' => $item->id,
                'description' => Config::get('module.current_module_id') == 5 ? 'Updated product: ' . $item->name : 'Updated service: ' . $item->name,
                'created_at' => now(),
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }


        if ($request->has('keyword_excel')) {
            // delete old keywords
            ServiceKeyword::where('service_id', $item->id)->delete();
            Excel::import(new KeywordsImport($item->id), $request->file('keyword_excel'));
        }
        // additional keywords 
        if ($request->has('more_keywords') && $request->more_keywords != '') {
            $rows = explode(',', $request->more_keywords);
            foreach ($rows as $kw) {
                ServiceKeyword::create([
                    'service_id' => $item->id,
                    'keyword' => $kw,
                ]);
            }
        }

        $item->tags()->sync($tag_ids);
        if ($item->module->module_type == 'pharmacy') {
            DB::table('pharmacy_item_details')
                ->updateOrInsert(
                    ['item_id' => $item->id],
                    [
                        'common_condition_id' => $request->condition_id,
                        'is_basic' => $request->basic ?? 0,
                    ]
                );
        }
        $this->saveSEO($request, $id, true);

        Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'Item', data_id: $item->id, data_value: $item->name);
        Helpers::add_or_update_translations(request: $request, key_data: 'description', name_field: 'description', model_name: 'Item', data_id: $item->id, data_value: $item->description);

        return response()->json(['success' => translate('messages.product_updated_successfully')], 200);
    }

    public function permanent_delete_item(Request $request, $id)
    {
        Item::withoutGlobalScopes()->onlyTrashed()->find($id)?->forceDelete();
        Toastr::success(translate('messages.deleted_successfully'));
        return back();
    }
    public function restore_item(Request $request, $id)
    {
        Item::withoutGlobalScopes()->onlyTrashed()->find($id)?->restore();
        Toastr::success(translate('messages.restored_successfully'));
        return back();
    }
    public function permanent_delete_category(Request $request, $id)
    {
        Category::withoutGlobalScopes()->onlyTrashed()->find($id)?->forceDelete();
        Toastr::success(translate('messages.deleted_successfully'));
        return back();
    }
    public function restore_category(Request $request, $id)
    {
        Category::withoutGlobalScopes()->onlyTrashed()->find($id)?->restore();
        Toastr::success(translate('messages.restored_successfully'));
        return back();
    }
    public function view_trashed_item(Request $request, $id)
    {
        $product = Item::withoutGlobalScopes()->onlyTrashed()->find($id);
        $reviews = Review::where(['item_id' => $id])->latest()->paginate(config('default_pagination'));
        return view('admin-views.product.view', compact('product', 'reviews'));
    }
    public function view_trashed_category(Request $request, $id)
    {
        $category = Category::withoutGlobalScopes()->onlyTrashed()->find($id);
        return view('admin-views.category.view', compact('category'));
    }
    public function delete(Request $request)
    {
        if ($request?->temp_product) {
            $product = TempProduct::find($request->id);
        } else {
            $product = Item::withoutGlobalScope(StoreScope::class)->withoutGlobalScope('translate')->find($request->id);
            $product?->temp_product?->translations()?->delete();
            $product?->temp_product()?->delete();
            $product?->carts()?->delete();
        }

        if ($product->image) {
            if (Storage::disk('public')->exists('product/' . $product['image'])) {
                Storage::disk('public')->delete('product/' . $product['image']);
            }
        }
        try {
            ActionLog::create([
                'user_id' => auth('admin')->id(),
                'user_type' => 'admin',
                'action' => 'trashed',
                'model_type' => 'item', // e.g. App\Models\Category
                'model_id' => $product->id,
                'description' => $product->module_id == 5 ? 'Product "' . $product->name . '" trashed' : 'Service "' . $product->name . '" trashed',
                'created_at' => now(),
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }

        $product?->translations()->delete();
        $product->deleted_by = auth('admin')->id();
        $product->save();
        $product->delete();

        Toastr::success(translate('messages.product_deleted_successfully'));
        return back();
    }
    public function trash(Request $request)
    {
        $categories = Category::onlyTrashed()->paginate(10);

        $items = Item::withoutGlobalScopes()->onlyTrashed()->paginate(10);

        return view('admin-views.trash.service', compact('categories', 'items'));
    }
    public function bulk_delete(Request $request)
    {
        if ($request->has('item_id') && is_array($request->item_id)) {
            $ids = $request->item_id;

            foreach ($ids as $id) {
                if ($request?->temp_product) {
                    $product = TempProduct::find($id);
                } else {
                    $product = Item::withoutGlobalScope(StoreScope::class)
                        ->withoutGlobalScope('translate')
                        ->find($id);

                    $product?->temp_product?->translations()?->delete();
                    $product?->temp_product()?->delete();
                    $product?->carts()?->delete();
                }

                if ($product && $product->image) {
                    if (Storage::disk('public')->exists('product/' . $product['image'])) {
                        Storage::disk('public')->delete('product/' . $product['image']);
                    }
                }

                $product?->translations()->delete();
                $product?->delete();
            }

            Toastr::success(translate('messages.selected_products_deleted_successfully.'));
        } else {
            Toastr::error(translate('messages.no_product_selected'));
        }

        return back();
    }

    public function variant_combination(Request $request)
    {
            // prx($request->all())
        ;
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
        $product = DB::table('items')->where('id', $request->item_id)->first();
        // prx($product);
        if ($product) {

            $current_variations = json_decode($product->variations);
        } else {
            $current_variations = [];
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
        $stock = $request->stock == 'true' ? true : false;
        return response()->json([
            'view' => view('admin-views.product.partials._variant-combinations', compact('combinations', 'price', 'product_name', 'stock', 'current_variations'))->render(),
            'length' => count($combinations),
            'stock' => $stock,
        ]);
    }
    public function update_variant_combination(Request $request)
    {

        $item = Item::find($request->item_id);
        $existing_variations_type = [];

        if ($item && $item->variations) {
            $existing_variations = json_decode($item->variations);
            $existing_variations_type = collect($existing_variations)->pluck('type')->all();
        }

        // prx($existing_variations_type);

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
                    // if (!in_array(str_replace(' ', '', trim($value)), $request->type)) {
                    $filtered_values[] = trim($value);
                    // }
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
        $product = DB::table('items')->where('id', $request->item_id)->first();
        // prx($product);
        if ($product) {
            $current_variations = json_decode($product->variations);
        } else {
            $current_variations = [];
        }
        $combinations = $result;

        $stock = $request->stock == 'true' ? true : false;
        return response()->json([
            'view' => view('admin-views.product.partials._addMoreCombinations', compact('existing_variations_type', 'combinations', 'price', 'product_name', 'current_variations', 'stock'))->render(),
            'length' => count($combinations),
            'stock' => $stock,
        ]);
    }

    public function variant_price(Request $request)
    {
        if ($request->item_type == 'item') {
            $product = Item::withoutGlobalScope(StoreScope::class)->find($request->id);
        } else {
            $product = ItemCampaign::find($request->id);
        }
        // $product = Item::withoutGlobalScope(StoreScope::class)->find($request->id);
        if (isset($product->module_id) && $product->module->module_type == 'food' && $product->food_variations) {
            $price = $product->price;
            $addon_price = 0;
            if ($request['addon_id']) {
                foreach ($request['addon_id'] as $id) {
                    $addon_price += $request['addon-price' . $id] * $request['addon-quantity' . $id];
                }
            }
            $product_variations = json_decode($product->food_variations, true);
            if ($request->variations && count($product_variations)) {

                $price += Helpers::food_variation_price($product_variations, $request->variations);
            } else {
                $price = $product->price - Helpers::product_discount_calculate($product, $product->price, $product->store)['discount_amount'];
            }
        } else {
            $str = '';
            $quantity = 0;
            $price = 0;
            $addon_price = 0;

            foreach (json_decode($product->choice_options) as $key => $choice) {
                if ($str != null) {
                    $str .= '-' . str_replace(' ', '', $request[$choice->name]);
                } else {
                    $str .= str_replace(' ', '', $request[$choice->name]);
                }
            }

            if ($request['addon_id']) {
                foreach ($request['addon_id'] as $id) {
                    $addon_price += $request['addon-price' . $id] * $request['addon-quantity' . $id];
                }
            }

            if ($str != null) {
                $count = count(json_decode($product->variations));
                for ($i = 0; $i < $count; $i++) {
                    if (json_decode($product->variations)[$i]->type == $str) {
                        $price = json_decode($product->variations)[$i]->price - Helpers::product_discount_calculate($product, json_decode($product->variations)[$i]->price, $product->store)['discount_amount'];
                    }
                }
            } else {
                $price = $product->price - Helpers::product_discount_calculate($product, $product->price, $product->store)['discount_amount'];
            }
        }

        return array('price' => Helpers::format_currency(($price * $request->quantity) + $addon_price));
    }

    public function lead_charge()
    { // add page
        $categories =  Category::where('module_id', 6)->get();

        $zones = DB::table('zones')
            ->join('module_zone', 'module_zone.zone_id', '=', 'zones.id')
            ->where('module_zone.module_id', 6)
            ->select('zones.*')
            ->get();

        return view('admin-views.service.charge-add', compact('categories', 'zones'));
    }

   public function lead_charge_save(Request $request)
{
    $validator = Validator::make($request->all(), [
        'zone' => 'required',
        'category' => 'required|array',
        'item_id' => 'nullable|array',
        'first_ven_charge' => 'required|numeric',
        'sec_ven_charge' => 'required|numeric',
        'third_ven_charge' => 'required|numeric',
        'other_ven_charge' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => Helpers::error_processor($validator)]);
    }

    $itemIds = $request->item_id ?? [];

    foreach ($request->category as $categoryId) {
        if (!empty($itemIds)) {
            foreach ($itemIds as $itemId) {
                LeadCharge::updateOrCreate(
                    [
                        'zone_id' => $request->zone,
                        'category_id' => $categoryId,
                        'item_id' => $itemId
                    ],
                    [
                        'vendor_count' => $request->vendor_count,
                        'ven_1_charges' => $request->first_ven_charge,
                        'ven_2_charges' => $request->sec_ven_charge,
                        'ven_3_charges' => $request->third_ven_charge,
                        'ven_other_charges' => $request->other_ven_charge,
                        'ven_same_charges' => $request->same_charge,
                        'dedicated_lead_charge' => $request->dedicated_lead_charge,
                        'confirmation_charge' => $request->confirmation_charge,
                        'completion_charge' => $request->completion_charge,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        } else {
            LeadCharge::updateOrCreate(
                [
                    'zone_id' => $request->zone,
                    'category_id' => $categoryId,
                    'item_id' => null
                ],
                [
                    'vendor_count' => $request->vendor_count,
                    'ven_1_charges' => $request->first_ven_charge,
                    'ven_2_charges' => $request->sec_ven_charge,
                    'ven_3_charges' => $request->third_ven_charge,
                    'ven_other_charges' => $request->other_ven_charge,
                    'ven_same_charges' => $request->same_charge,
                    'dedicated_lead_charge' => $request->dedicated_lead_charge,
                    'confirmation_charge' => $request->confirmation_charge,
                    'completion_charge' => $request->completion_charge,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    Toastr::success('Lead charges saved/updated successfully');

    return redirect('admin/service/lead-charges');
}

    public function lead_charge_update(Request $request)
    {

        $validator = Validator::make($request->all(), [
            // 'vendor_count' => 'required|not_in:0',
            'first_ven_charge' => 'required|numeric',
            'sec_ven_charge' => 'required|numeric',
            'third_ven_charge' => 'required|numeric',
            'other_ven_charge' => 'required|numeric',
            // 'same_charge' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $id = $request->charge_id;
        $charge  = LeadCharge::find($id);
        // $charge->zone_id = $request->zone;
        // $charge->category_id = $request->category;
        $charge->vendor_count = $request->vendor_count;
        $charge->ven_1_charges = $request->first_ven_charge;
        $charge->ven_2_charges = $request->sec_ven_charge;
        $charge->ven_3_charges = $request->third_ven_charge;
        $charge->ven_other_charges = $request->other_ven_charge;
        $charge->ven_same_charges = $request->same_charge;
        $charge->dedicated_lead_charge = $request->dedicated_lead_charge;
        $charge->confirmation_charge = $request->confirmation_charge;
        $charge->completion_charge = $request->completion_charge;

        if ($charge->update()) {
            Toastr::success('Lead charges updated successfully');
        }
        return back();
    }

    public function lead_charge_list()
    {
        $charges = DB::table('lead_charges')
            ->join('categories', 'categories.id', '=', 'lead_charges.category_id')
            ->join('zones', 'zones.id', '=',  'lead_charges.zone_id')
            ->leftJoin('items', 'items.id', '=', 'lead_charges.item_id')
            ->select('lead_charges.*', 'categories.name as cat_name', 'zones.name as zone_name', 'items.name as item_name')
            ->get();
        return view('admin-views.service.charges-list', compact('charges'));
    }

    public function edit_charges($id)
    {
        $charges = LeadCharge::find($id);
        $categories =  Category::where('module_id', 6)->get();
        $items = $charges->category_id ? Item::withoutGlobalScopes()->where('category_id', $charges->category_id)->get() : collect();
        $zones = DB::table('zones')
            ->join('module_zone', 'module_zone.zone_id', '=', 'zones.id')
            ->where('module_zone.module_id', 6)
            ->select('zones.*')
            ->get();
        return view('admin-views.service.charge-edit', compact('categories', 'zones', 'charges', 'items'));
    }

    public function get_items_by_category($category_id)
    {
        $items = Item::withoutGlobalScopes()->where('category_id', $category_id)->select('id', 'name')->get();
        return response()->json($items);
    }

    public function get_items_by_categories(Request $request)
    {
        $categoryIds = $request->input('category_ids', []);
        $items = Item::withoutGlobalScopes()->whereIn('category_id', $categoryIds)->select('id', 'name')->whereNull("inventory_item_id")->get();
        return response()->json($items);
    }

    public function get_categories(Request $request)
    {
        $key = explode(' ', $request['q']);
        $cat = Category::when(isset($request->module_id), function ($query) use ($request) {
            $query->where('module_id', $request->module_id);
        })
            ->when($request->sub_category, function ($query) {
                $query->where('position', '>', '0');
            })
            ->where(['parent_id' => $request->parent_id])
            ->where(['status' => 1])
            ->when(isset($key), function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->where('name', 'like', "%{$value}%");
                }
            })
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'text' => $category->name,
                ];
            });

        return response()->json($cat);
    }

    public function get_items(Request $request)
    {
        $items = Item::withoutGlobalScope(StoreScope::class)
            ->join('stores', function ($join) {
                $join->on(DB::raw("FIND_IN_SET(stores.id, items.store_ids)"), '>', DB::raw('0'));
            })
            ->when($request->zone_id, function ($q) use ($request) {
                $q->where('stores.zone_id', $request->zone_id);
            })
            ->when($request->module_id, function ($q) use ($request) {
                $q->where('items.module_id', $request->module_id);
            })
            ->select('items.*') // Avoid duplicate column names from join
            ->distinct('items.id')
            ->get();

        $res = '';

        if (count($items) > 0 && !$request->data) {
            $res = '<option value="' . 0 . '" disabled selected>---Select---</option>';
        }

        foreach ($items as $row) {
            $res .= '<option value="' . $row->id . '" ';
            if ($request->data) {
                $res .= in_array($row->id, $request->data) ? 'selected ' : '';
            }
            $res .= '>' . $row->name . ' (' . $row->store?->name . ')' . '</option>';
        }
        return response()->json([
            'options' => $res,
        ]);
    }

    public function get_items_flashsale(Request $request)
    {
        $items = Item::withoutGlobalScope(StoreScope::class)->with('store')->active()
            ->when($request->zone_id, function ($q) use ($request) {
                $q->whereHas('store', function ($query) use ($request) {
                    $query->where('zone_id', $request->zone_id);
                });
            })
            ->when($request->module_id, function ($q) use ($request) {
                $q->where('module_id', $request->module_id);
            })->whereDoesntHave('flashSaleItems.flashSale', function ($query) {
                $now = now();
                $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
            })->get();
        $res = '';
        if (count($items) > 0 && !$request->data) {
            $res = '<option value="' . 0 . '" disabled selected>---Select---</option>';
        }

        foreach ($items as $row) {
            $res .= '<option value="' . $row->id . '" ';
            if ($request->data) {
                $res .= in_array($row->id, $request->data) ? 'selected ' : '';
            }
            $res .= '>' . $row->name . ' (' . $row->store->name . ')' . '</option>';
        }
        return response()->json([
            'options' => $res,
        ]);
    }

    public function list(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $sub_category_id = $request->query('sub_category_id', 'all');
        $zone_id = $request->query('zone_id', 'all');
        $condition_id = $request->query('condition_id', 'all');

        $type = $request->query('type', 'all');
        $key = explode(' ', $request['search']);
        $items = Item::withoutGlobalScope(StoreScope::class)
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(is_numeric($store_id), function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->when(is_numeric($sub_category_id), function ($query) use ($sub_category_id) {
                return $query->where('category_id', $sub_category_id);
            })
            ->when(is_numeric($category_id), function ($query) use ($category_id) {
                return $query->whereHas('category', function ($q) use ($category_id) {
                    return $q->whereId($category_id)->orWhere('parent_id', $category_id);
                });
            })
            ->when(is_numeric($zone_id), function ($query) use ($zone_id) {
                return $query->whereHas('store', function ($q) use ($zone_id) {
                    return $q->where('zone_id', $zone_id);
                });
            })
            ->when(is_numeric($condition_id), function ($query) use ($condition_id) {
                return $query->whereHas('pharmacy_item_details', function ($q) use ($condition_id) {
                    return $q->where('common_condition_id', $condition_id);
                });
            })
            ->when($request['search'], function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%")->orWhereHas('category', function ($q) use ($value) {
                            return $q->where('name', 'like', "%{$value}%");
                        });
                    }
                });
            })
            ->whereNull('inventory_item_id')
            ->where('is_approved', 1)
            ->module(Config::get('module.current_module_id'))
            ->type($type)
            ->latest()->paginate(config('default_pagination'));
        $store = $store_id != 'all' ? Store::findOrFail($store_id) : null;
        $category = $category_id != 'all' ? Category::findOrFail($category_id) : null;
        $sub_categories = $category_id != 'all' ? Category::where('parent_id', $category_id)->get(['id', 'name']) : [];
        $condition = $condition_id != 'all' ? CommonCondition::findOrFail($condition_id) : [];
        $allitems = Item::where('is_approved', 1)
            ->module(Config::get('module.current_module_id'))->latest()->get();
        // prx($allitems);
        return view('admin-views.product.list', compact('items', 'store', 'category', 'type', 'sub_categories', 'condition', 'allitems'));
    }

    public function update_homepage_item(Request $request)
    {
        $item =  $request->item_id;
        BusinessSetting::updateOrInsert(['key' => 'homepage_item'], [
            'value' => $item
        ]);
        Toastr::success('Updated Successfully');
        return back();
    }
    public function remove_image(Request $request)
    {
        if (Storage::disk('public')->exists('product/' . $request['name'])) {
            Storage::disk('public')->delete('product/' . $request['name']);
        }
        if ($request?->temp_product) {
            $item = TempProduct::withoutGlobalScope(StoreScope::class)->find($request['id']);
        } else {
            $item = Item::withoutGlobalScope(StoreScope::class)->find($request['id']);
        }

        $array = [];
        if (count($item['images']) < 2) {
            Toastr::warning(translate('all_image_delete_warning'));
            return back();
        }
        foreach ($item['images'] as $image) {
            if ($image != $request['name']) {
                array_push($array, $image);
            }
        }


        if ($request?->temp_product) {
            TempProduct::withoutGlobalScope(StoreScope::class)->where('id', $request['id'])->update([
                'images' => json_encode($array),
            ]);
        } else {
            Item::withoutGlobalScope(StoreScope::class)->where('id', $request['id'])->update([
                'images' => json_encode($array),
            ]);
        }
        Toastr::success(translate('item_image_removed_successfully'));
        return back();
    }

    public function search(Request $request)
    {
        $view = 'admin-views.product.partials._table';
        $key = explode(' ', $request['search']);
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $items = Item::withoutGlobalScope(StoreScope::class)
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->where('name', 'like', "%{$value}%");
                }
            })->when(is_numeric($store_id), function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->when(is_numeric($category_id), function ($query) use ($category_id) {
                return $query->whereHas('category', function ($q) use ($category_id) {
                    return $q->whereId($category_id)->orWhere('parent_id', $category_id);
                });
            })->module(Config::get('module.current_module_id'))->where('is_approved', 1);

        if (isset($request->product_gallery) && $request->product_gallery == 1) {
            $items =   $items->limit(12)->get();
            $view = 'admin-views.product.partials._gallery';
        } else {
            $items = $items->latest()->limit(50)->get();
        }

        return response()->json([
            'count' => $items->count(),
            'view' => view($view, compact('items'))->render()
        ]);
    }

    public function review_list(Request $request)
    {

        $key = explode(' ', $request['search']);
        $reviews = Review::with('item')
            ->when(isset($key), function ($query) use ($key, $request) {
                $query->where(function ($query) use ($key, $request) {

                    $query->whereHas('item', function ($query) use ($key) {
                        foreach ($key as $value) {
                            $query->where('name', 'like', "%{$value}%");
                        }
                    })->orWhereHas('customer', function ($query) use ($key) {
                        foreach ($key as $value) {
                            $query->where('f_name', 'like', "%{$value}%")->orwhere('l_name', 'like', "%{$value}%");
                        }
                    })->orwhere('rating', $request['search']);
                });
            })
            ->whereHas('item', function ($q) {
                return $q->where('module_id', Config::get('module.current_module_id'))->withoutGlobalScope(StoreScope::class);
            })

            ->latest()->paginate(config('default_pagination'));

        return view('admin-views.product.reviews-list', compact('reviews'));
    }

    public function reviews_status(Request $request)
    {
        $review = Review::find($request->id);
        $review->status = $request->status;
        $review->save();
        Toastr::success(translate('messages.review_visibility_updated'));
        return back();
    }

    // public function review_search(Request $request)
    // {
    //     $key = explode(' ', $request['search']);
    //     $reviews = Review::with('item')
    //     ->when(isset($key), function($query) use($key){
    //         $query->whereHas('item', function ($query) use ($key) {
    //             foreach ($key as $value) {
    //                 $query->where('name', 'like', "%{$value}%");
    //             }
    //         });
    //     })
    //     ->whereHas('item', function ($q) use ($request) {
    //         return $q->where('module_id', Config::get('module.current_module_id'))->withoutGlobalScope(StoreScope::class);
    //     })->limit(50)->get();
    //     return response()->json([
    //         'count' => count($reviews),
    //         'view' => view('admin-views.product.partials._review-table', compact('reviews'))->render()
    //     ]);
    // }

    public function reviews_export(Request $request)
    {
        $key = explode(' ', $request['search']);
        $reviews = Review::with('item')
            ->when(isset($key), function ($query) use ($key) {
                $query->whereHas('item', function ($query) use ($key) {
                    foreach ($key as $value) {
                        $query->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->whereHas('item', function ($q) {
                return $q->where('module_id', Config::get('module.current_module_id'))->withoutGlobalScope(StoreScope::class);
            })

            ->latest()->get();

        $data = [
            'data' => $reviews,
            'search' => $request['search'] ?? null,
        ];
        $typ = 'Item';
        if (Config::get('module.current_module_type') == 'food') {
            $typ = 'Food';
        }
        if ($request->type == 'csv') {
            return Excel::download(new ItemReviewExport($data), $typ . 'Review.csv');
        }
        return Excel::download(new ItemReviewExport($data), $typ . 'Review.xlsx');
    }

    public function item_wise_reviews_export(Request $request)
    {
        $reviews = Review::where(['item_id' => $request->id])->latest()->get();
        $Item = Item::where('id', $request->id)->first()?->category_ids;
        $data = [
            'type' => 'single',
            'category' => \App\CentralLogics\Helpers::get_category_name($Item),
            'data' => $reviews,
            'search' => $request['search'] ?? null,
            'store' => $request['store'] ?? null,
        ];
        $typ = 'ItemWise';
        if (Config::get('module.current_module_type') == 'food') {
            $typ = 'FoodWise';
        }
        if ($request->type == 'csv') {
            return Excel::download(new ItemReviewExport($data), $typ . 'Review.csv');
        }
        return Excel::download(new ItemReviewExport($data), $typ . 'Review.xlsx');
    }

    public function bulk_import_index()
    {
        $module_type = Config::get('module.current_module_type');
        return view('admin-views.product.bulk-import', compact('module_type'));
    }

    public function bulk_import_data(Request $request)
    {
        $request->validate([
            'products_file' => 'required|max:2048'
        ]);
        $module_id = Config::get('module.current_module_id');
        $module_type = Config::get('module.current_module_type');
        try {
            $collections = (new FastExcel)->import($request->file('products_file'));
        } catch (\Exception $exception) {
            Toastr::error(translate('messages.you_have_uploaded_a_wrong_format_file'));
            return back();
        }
        if ($request->button == 'import') {
            $data = [];
            try {
                foreach ($collections as $collection) {
                    if ($collection['Id'] === "" || $collection['Name'] === "" || $collection['CategoryId'] === "" || $collection['SubCategoryId'] === "" || $collection['Price'] === "" || $collection['StoreId'] === "" || $collection['ModuleId'] === "" || $collection['Discount'] === "" || $collection['DiscountType'] === "") {
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
                        'choice_options' => $module_type == 'food' ? json_encode([]) : $collection['ChoiceOptions'] ?? json_encode([]),
                        'food_variations' => $module_type == 'food' ? $collection['Variations'] ?? json_encode([]) : json_encode([]),
                        'add_ons' => $collection['AddOns'] ? ($collection['AddOns'] == "" ? json_encode([]) : $collection['AddOns']) : json_encode([]),
                        'attributes' => $collection['Attributes'] ? ($collection['Attributes'] == "" ? json_encode([]) : $collection['Attributes']) : json_encode([]),
                        'store_id' => $collection['StoreId'],
                        'module_id' => $module_id,
                        'status' => $collection['Status'] == 'active' ? 1 : 0,
                        'veg' => $collection['Veg'] == 'yes' ? 1 : 0,
                        'recommended' => $collection['Recommended'] == 'yes' ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
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
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                info(["line___{$e->getLine()}", $e->getMessage()]);
                Toastr::error(translate('messages.failed_to_import_data'));
                return back();
            }
            Toastr::success(translate('messages.product_imported_successfully', ['count' => count($data)]));
            return back();
        }
        $data = [];
        try {
            foreach ($collections as $collection) {
                if ($collection['Id'] === "" || $collection['Name'] === "" || $collection['CategoryId'] === "" || $collection['SubCategoryId'] === "" || $collection['Price'] === "" || $collection['StoreId'] === "" || $collection['ModuleId'] === "" || $collection['Discount'] === "" || $collection['DiscountType'] === "") {
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
                    'choice_options' => $module_type == 'food' ? json_encode([]) : $collection['ChoiceOptions'] ?? json_encode([]),
                    'food_variations' => $module_type == 'food' ? $collection['Variations'] ?? json_encode([]) : json_encode([]),
                    'add_ons' => $collection['AddOns'] ? ($collection['AddOns'] == "" ? json_encode([]) : $collection['AddOns']) : json_encode([]),
                    'attributes' => $collection['Attributes'] ? ($collection['Attributes'] == "" ? json_encode([]) : $collection['Attributes']) : json_encode([]),
                    'store_id' => $collection['StoreId'],
                    'module_id' => $module_id,
                    'status' => $collection['Status'] == 'active' ? 1 : 0,
                    'veg' => $collection['Veg'] == 'yes' ? 1 : 0,
                    'recommended' => $collection['Recommended'] == 'yes' ? 1 : 0,
                    'updated_at' => now()
                ]);
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
            $chunk_items = array_chunk($data, $chunkSize);
            foreach ($chunk_items as $key => $chunk_item) {
                DB::table('items')->upsert($chunk_item, ['id', 'module_id'], ['name', 'description', 'image', 'images', 'category_id', 'category_ids', 'unit_id', 'stock', 'price', 'discount', 'discount_type', 'available_time_starts', 'available_time_ends', 'choice_options', 'variations', 'food_variations', 'add_ons', 'attributes', 'store_id', 'status', 'veg', 'recommended']);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            info(["line___{$e->getLine()}", $e->getMessage()]);
            Toastr::error(translate('messages.failed_to_import_data'));
            return back();
        }
        Toastr::success(translate('messages.product_imported_successfully', ['count' => count($data)]));
        return back();
    }

    public function bulk_export_index()
    {
        return view('admin-views.product.bulk-export');
    }

    public function bulk_export_data(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'start_id' => 'required_if:type,id_wise',
            'end_id' => 'required_if:type,id_wise',
            'from_date' => 'required_if:type,date_wise',
            'to_date' => 'required_if:type,date_wise'
        ]);
        $module_type = Config::get('module.current_module_type');
        $products = Item::when($request['type'] == 'date_wise', function ($query) use ($request) {
            $query->whereBetween('created_at', [$request['from_date'] . ' 00:00:00', $request['to_date'] . ' 23:59:59']);
        })
            ->when($request['type'] == 'id_wise', function ($query) use ($request) {
                $query->whereBetween('id', [$request['start_id'], $request['end_id']]);
            })
            ->module(Config::get('module.current_module_id'))
            ->withoutGlobalScope(StoreScope::class)->get();
        return (new FastExcel(ProductLogic::format_export_items(Helpers::Export_generator($products), $module_type)))->download('Items.xlsx');
    }

    public function get_variations(Request $request)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->find($request['id']);

        return response()->json([
            'view' => view('admin-views.product.partials._update_stock', compact('product'))->render()
        ]);
    }
    public function get_price_variations(Request $request)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->find($request['id']);

        return response()->json([
            'view' => view('admin-views.product.partials._update_stock', compact('product'))->render()
        ]);
    }

    public function stock_update(Request $request)
    {
        $variations = [];
        $stock_count = $request['current_stock'];
        if ($request->has('type')) {
            foreach ($request['type'] as $key => $str) {
                $item = [];
                $item['type'] = $str;
                $item['price'] = abs($request['price_' . str_replace('.', '_', $str)]);
                $item['stock'] = abs($request['stock_' . str_replace('.', '_', $str)]);
                array_push($variations, $item);
            }
        }


        $product = Item::withoutGlobalScope(StoreScope::class)->find($request['product_id']);

        $product->stock = $stock_count ?? 0;
        $product->variations = json_encode($variations);
        $product->save();
        Toastr::success(translate("messages.product_updated_successfully"));
        return back();
    }

    public function search_vendor(Request $request)
    {
        $key = explode(' ', $request['search']);
        if ($request->has('store_id')) {

            $foods = Item::withoutGlobalScope(StoreScope::class)
                ->where('store_id', $request->store_id)
                ->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                })->limit(50)->get();
            return response()->json([
                'count' => count($foods),
                'view' => view('admin-views.vendor.view.partials._product', compact('foods'))->render()
            ]);
        }
        $foods = Item::withoutGlobalScope(StoreScope::class)->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->where('name', 'like', "%{$value}%");
            }
        })->limit(50)->get();
        return response()->json([
            'count' => count($foods),
            'view' => view('admin-views.vendor.view.partials._product', compact('foods'))->render()
        ]);
    }

    public function store_item_export(Request $request)
    {
        $key = explode(' ', request()->search);
        $model = app("\\App\\Models\\Item");
        if ($request?->table && $request?->table == 'TempProduct') {
            $model = app("\\App\\Models\\TempProduct");
        }

        $foods = $model->withoutGlobalScope(StoreScope::class)->where('store_id', $request->store_id)
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->when($request?->sub_tab == 'active-items', function ($q) {
                $q->where('status', 1);
            })
            ->when($request?->sub_tab == 'inactive-items', function ($q) {
                $q->where('status', 0);
            })
            ->when($request?->sub_tab == 'pending-items', function ($q) {
                $q->where('is_rejected', 0);
            })
            ->when($request?->sub_tab == 'rejected-items', function ($q) {
                $q->where('is_rejected', 1);
            })
            ->latest()->get();

        // dd($request?->sub_tab,$foods,);

        $store = Store::where('id', $request->store_id)->select(['name', 'zone_id'])->first();
        $typ = 'Item';
        if (Config::get('module.current_module_type') == 'food') {
            $typ = 'Food';
        }

        $data = [
            'sub_tab' => $request?->sub_tab,
            'data' => $foods,
            'search' => $request['search'] ?? null,
            'zone' => Helpers::get_zones_name($store->zone_id),
            'store_name' => $store->name,
        ];
        if ($request->type == 'csv') {
            return Excel::download(new StoreItemExport($data), $typ . 'List.csv');
        }
        return Excel::download(new StoreItemExport($data), $typ . 'List.xlsx');

        // if ($request->type == 'excel') {
        //     return (new FastExcel(Helpers::export_store_item($item)))->download('Items.xlsx');
        // } elseif ($request->type == 'csv') {
        //     return (new FastExcel(Helpers::export_store_item($item)))->download('Items.csv');
        // }
    }

    public function export(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $sub_category_id = $request->query('sub_category_id', 'all');
        $zone_id = $request->query('zone_id', 'all');

        $model = app("\\App\\Models\\Item");
        if ($request?->table && $request?->table == 'TempProduct') {
            $model = app("\\App\\Models\\TempProduct");
        }

        $type = $request->query('type', 'all');
        $key = explode(' ', $request['search']);
        $item = $model->withoutGlobalScope(StoreScope::class)
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(is_numeric($store_id), function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->when(is_numeric($sub_category_id), function ($query) use ($sub_category_id) {
                return $query->where('category_id', $sub_category_id);
            })
            ->when(is_numeric($category_id), function ($query) use ($category_id) {
                return $query->whereHas('category', function ($q) use ($category_id) {
                    return $q->whereId($category_id)->orWhere('parent_id', $category_id);
                });
            })
            ->when(is_numeric($zone_id), function ($query) use ($zone_id) {
                return $query->whereHas('store', function ($q) use ($zone_id) {
                    return $q->where('zone_id', $zone_id);
                });
            })
            ->when($request['search'], function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->approved()
            ->module(Config::get('module.current_module_id'))
            ->type($type)
            ->with('category', 'store')
            ->type($type)->latest()->get();



        $format_type = 'Item';
        if (Config::get('module.current_module_type') == 'food') {
            $format_type = 'Food';
        }

        $data = [
            'table' => $request?->table,
            'data' => $item,
            'search' => $request['search'] ?? null,
            'store' => $store_id != 'all' ? Store::findOrFail($store_id)?->name : null,
            'category' => $category_id != 'all' ? Category::findOrFail($category_id)?->name : null,
            'module_name' => Helpers::get_module_name(Config::get('module.current_module_id')),
        ];
        if ($request->type == 'csv') {
            return Excel::download(new ItemListExport($data), $format_type . 'List.csv');
        }
        return Excel::download(new ItemListExport($data), $format_type . 'List.xlsx');


        // if ($types == 'excel') {
        //     return (new FastExcel(Helpers::export_items(Helpers::Export_generator($item),$module_type)))->download('Items.xlsx');
        // } elseif ($types == 'csv') {
        //     return (new FastExcel(Helpers::export_items(Helpers::Export_generator($item),$module_type)))->download('Items.csv');
        // }



    }

    public function search_store(Request $request, $store_id)
    {
        $key = explode(' ', $request['search']);
        $foods = Item::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $store_id)
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->where('name', 'like', "%{$value}%");
                }
            })->limit(50)->get();
        return response()->json([
            'count' => count($foods),
            'view' => view('admin-views.vendor.view.partials._product', compact('foods'))->render()
        ]);
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


    public function approval_list(Request $request)
    {
        abort_if(Helpers::get_mail_status('product_approval') != 1, 404);
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $sub_category_id = $request->query('sub_category_id', 'all');
        $zone_id = $request->query('zone_id', 'all');
        $type = $request->query('type', 'all');
        $filter = $request->query('filter');
        $key = explode(' ', $request['search']);
        $from =  $request->query('from');
        $to =  $request->query('to');

        $items = TempProduct::withoutGlobalScope(StoreScope::class)
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(is_numeric($store_id), function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->when(is_numeric($sub_category_id), function ($query) use ($sub_category_id) {
                return $query->where('category_id', $sub_category_id);
            })
            ->when(is_numeric($category_id), function ($query) use ($category_id) {
                return $query->whereHas('category', function ($q) use ($category_id) {
                    return $q->whereId($category_id)->orWhere('parent_id', $category_id);
                });
            })
            ->when(is_numeric($zone_id), function ($query) use ($zone_id) {
                return $query->whereHas('store', function ($q) use ($zone_id) {
                    return $q->where('zone_id', $zone_id);
                });
            })
            ->when($request['search'], function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->when(isset($filter) && $filter == 'pending', function ($query) {
                return $query->where('is_rejected', 0);
            })
            ->when(isset($filter) && $filter == 'rejected', function ($query) {
                return $query->where('is_rejected', 1);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && isset($filter) && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('updated_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })

            ->module(Config::get('module.current_module_id'))
            ->type($type)
            ->orderBy('is_rejected', 'asc')
            ->orderBy('updated_at', 'desc')
            ->paginate(config('default_pagination'));
        $store = $store_id != 'all' ? Store::findOrFail($store_id) : null;
        $category = $category_id != 'all' ? Category::findOrFail($category_id) : null;
        $sub_categories = $category_id != 'all' ? Category::where('parent_id', $category_id)->get(['id', 'name']) : [];

        return view('admin-views.product.approv_list', compact('items', 'store', 'category', 'type', 'sub_categories', 'filter'));
    }


    public function requested_item_view($id)
    {
        $product = TempProduct::withoutGlobalScope(StoreScope::class)->withoutGlobalScope('translate')->with(['translations', 'store', 'unit'])->findOrFail($id);
        return view('admin-views.product.requested_product_view', compact('product'));
    }

    public function deny(Request $request)
    {
        $data = TempProduct::findOrfail($request->id);
        $data->is_rejected = 1;
        $data->note = $request->note;
        $data->save();
        Toastr::success(translate('messages.Product_denied'));

        try {
            $mail_status = Helpers::get_mail_status('product_deny_mail_status_store');
            if (config('mail.status') && $mail_status == '1') {
                Mail::to($data?->store?->vendor?->email)->send(new \App\Mail\VendorProductMail($data?->store?->name, 'denied'));
            }
        } catch (\Exception $e) {
            info($e->getMessage());
        }
        return to_route('admin.item.approval_list');
    }
    public function approved(Request $request)
    {
        $data = TempProduct::withoutGlobalScopes()->findOrFail($request->id);
        $item = Item::withoutGlobalScopes()->findOrfail($data->item_id);

        $item->name = $data->name;
        $item->description =  $data->description;
        $item->specifications =  $data->specifications;
        $item->image = $data->image;
        $item->images = $data->images;

        $item->store_id = $data->store_id;
        $item->module_id = $data->module_id;
        $item->unit_id = $data->unit_id;

        $item->category_id = $data->category_id;
        $item->category_ids = $data->category_ids;

        $item->choice_options = $data->choice_options;
        $item->food_variations = $data->food_variations;
        $item->variations = $data->variations;
        $item->add_ons = $data->add_ons;
        $item->attributes = $data->attributes;

        $item->price = $data->price;
        $item->discount = $data->discount;
        $item->discount_type = $data->discount_type;

        $item->available_time_starts = $data->available_time_starts;
        $item->available_time_ends = $data->available_time_ends;
        $item->maximum_cart_quantity = $data->maximum_cart_quantity;
        $item->veg = $data->veg;

        $item->organic = $data->organic;
        $item->stock =  $data->stock;
        $item->is_approved = 1;

        $item->save();
        $item->tags()->sync(json_decode($data->tag_ids));

        $item?->pharmacy_item_details()?->delete();

        if ($item->module->module_type == 'pharmacy') {
            DB::table('pharmacy_item_details')->where('temp_product_id', $data->id)->update([
                'item_id' => $item->id,
                'temp_product_id' => null
            ]);
        }

        $item?->translations()?->delete();
        Translation::where('translationable_type', 'App\Models\TempProduct')->where('translationable_id', $data->id)->update([
            'translationable_type' => 'App\Models\Item',
            'translationable_id' => $item->id
        ]);

        $data->delete();

        try {
            $mail_status = Helpers::get_mail_status('product_approve_mail_status_store');
            if (config('mail.status') && $mail_status == '1') {
                Mail::to($data?->store?->vendor?->email)->send(new \App\Mail\VendorProductMail($data?->store?->name, 'approved'));
            }
        } catch (\Exception $e) {
            info($e->getMessage());
        }

        Toastr::success(translate('messages.Product_approved'));
        return to_route('admin.item.approval_list');
    }

    public function product_gallery(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $type = $request->query('type', 'all');
        $key = explode(' ', $request['search']);
        $items = Item::withoutGlobalScope(StoreScope::class)
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(is_numeric($store_id), function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
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
            ->orderByRaw("FIELD(name, ?) DESC", [$request['name']])
            ->where('is_approved', 1)
            ->module(Config::get('module.current_module_id'))
            ->type($type)
            // ->latest()->paginate(config('default_pagination'));
            ->inRandomOrder()->limit(12)->get();
        $store = $store_id != 'all' ? Store::findOrFail($store_id) : null;
        $category = $category_id != 'all' ? Category::findOrFail($category_id) : null;
        return view('admin-views.product.product_gallery', compact('items', 'store', 'category', 'type'));
    }
}
