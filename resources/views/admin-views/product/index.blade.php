@extends('layouts.admin.app')

@section('title', translate('messages.add_new_item'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/tags-input.min.css') }}" rel="stylesheet">

    <style>
        .seo-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .seo-header {
            background: #f4f4f4;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .seo-header:hover {
            background: #e4e4e4;
        }

        .seo-header h4 {
            margin: 0;
            font-weight: 600;
        }

        .seo-header i {
            transition: transform 0.3s;
        }

        .seo-header[aria-expanded="true"] i {
            transform: rotate(180deg);
        }

        .seo-body {
            padding: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control,
        .form-control:focus {
            border-radius: 4px;
        }

        .char-counter {
            font-size: 12px;
            color: #6c757d;
            text-align: right;
            margin-top: 5px;
        }

        .info-text {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
        }

        .faq-item {
            background-color: #f8f9fa;
            position: relative;
        }

        .seo-content-item {
            background-color: #f8f9fa;
            position: relative;
        }

        .remove-faq,
        .remove-seo-content {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap __gap-15px justify-content-between align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/items.png') }}" class="w--22" alt="">
                </span>
                <span>
                    {{ translate('messages.add_new_item') }}
                </span>
            </h1>
            <div class="d-flex align-items-end flex-wrap">
                <!-- <div class="text--primary-2 d-flex flex-wrap align-items-center mr-2">
                                                                                                                        <a href="{{ route('admin.item.product_gallery') }}" class="btn btn-outline-primary btn--primary d-flex align-items-center bg-not-hover-primary-ash rounded-8 gap-2">
                                                                                                                            <img src="{{ asset('public/assets/admin/img/product-gallery.png') }}" class="w--22" alt="">
                                                                                                                            <span>{{ translate('Add Info From Gallery') }}</span>
                                                                                                                        </a>
                                                                                                                    </div> -->

            </div>
        </div>
        <!-- End Page Header -->
        <form action="javascript:" method="post" id="product_form" enctype="multipart/form-data">
            @csrf
            @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
            @php($language = $language->value ?? null)
            @php($defaultLang = str_replace('_', '-', app()->getLocale()))
            <input type="hidden" class="route_url" value="{{ route('admin.item.store') }}">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body row">


                            <div id="default-form" class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="input-label"
                                        for="exampleFormControlInput1">{{ translate('messages.name') }} </label>
                                    <input type="text" name="name[]" class="form-control"
                                        placeholder="{{ translate('messages.new_item') }}">
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                                @if (Config::get('module.current_module_id') == 5)
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.highlights') }}</label>
                                        <textarea type="text" name="description[]" class="form-control min-h-90px ckeditor"></textarea>
                                    </div>
                                @else
                                    <input type="hidden" name="description[]" value="service">
                                @endif
                            </div>

                            <div class="form-group mb-0 col-md-6">
                                <label class="input-label" for="category_id">{{ translate('messages.category') }}<span
                                        class="form-label-secondary text-danger" data-toggle="tooltip"
                                        data-placement="right" data-original-title="{{ translate('messages.Required.') }}">
                                        *
                                    </span><span class="input-label-secondary" title=""><img
                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                            alt=""></span></label>
                                <select name="category_id" id="category_id"
                                    data-placeholder="{{ translate('messages.select_category') }}"
                                    class="js-data-example-ajax form-control">
                                </select>
                            </div>
                            <div class="form-group mb-0 col-md-6">
                                <label class="input-label"
                                    for="short_desc">{{ translate('messages.short_description') }}</label>
                                <input type="text" name="short_desc" placeholder="Professional {SERVICE_NAME} Experts"
                                    class="form-control" id="short_desc">
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-wrap align-items-center">
                            <div class="w-100 d-flex flex-wrap __gap-15px">
                                @if (Config::get('module.current_module_id') == 5)
                                    <div class="flex-grow-1 mx-auto">
                                        <label class="text-dark d-block mb-4 mb-xl-5">
                                            {{ translate('messages.item_image') }}
                                            <small class="">( {{ translate('messages.ratio') }} 1:1 )</small>
                                        </label>
                                        <div class="d-flex flex-wrap __gap-12px __new-coba" id="coba"></div>
                                    </div>
                                @endif
                                <div class="flex-grow-1 mx-auto">
                                    <label class="text-dark d-block mb-4 mb-xl-5">
                                        {{ translate('messages.item_thumbnail') }}
                                        @if (Config::get('module.current_module_type') == 'food')
                                            <small class="">( {{ translate('messages.ratio') }} 1:1 )</small>
                                        @else
                                            <small class="text-danger">* ( {{ translate('messages.ratio') }} 1:1 )</small>
                                        @endif
                                    </label>
                                    <label class="d-inline-block m-0 position-relative">
                                        <img class="img--176 border" id="viewer"
                                            src="{{ asset('public/assets/admin/img/upload-img.png') }}" alt="thumbnail" />
                                        <div class="icon-file-group">
                                            <div class="icon-file"><input type="file" name="image" id="customFileEg1"
                                                    class="custom-file-input d-none"
                                                    accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                <i class="tio-edit"></i>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if (Config::get('module.current_module_id') == 5)
                    <div class="col-md-12">
                        <div class="card shadow--card-2 border-0">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <span class="card-header-icon mr-2">
                                        <i class="tio-tune-horizontal"></i>
                                    </span>
                                    <span>Specifications</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <textarea id="maineditor"></textarea>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- <div class="col-md-12">
                    <div class="card shadow--card-2 border-0">
                        <div class="card-header">
                            <h5 class="card-title">
                                <span class="card-header-icon mr-2">
                                    <i class="tio-tune-horizontal"></i>
                                </span>
                                <span>
                                    @if (Config::get('module.current_module_id') == 6)
                                        {{ translate('Category_Info') }}
                                    @else
                                        {{ translate('Store_&_Category_Info') }}
                                    @endif
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                @if (Config::get('module.current_module_id') != 6)
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="form-group mb-0">
                                            <label class="input-label" for="store_id">{{ translate('messages.store') }}
                                                <span class="form-label-secondary text-danger" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.Required.') }}"> *
                                                </span><span class="input-label-secondary"></span></label>
                                            <select name="store_id" id="store_id"
                                                data-placeholder="{{ translate('messages.select_store') }}"
                                                class="js-data-example-ajax form-control"
                                                oninvalid="this.setCustomValidity('{{ translate('messages.please_select_store') }}')">

                                            </select>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-sm-6 col-lg-3">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="zone_id">Zone
                                            <span class="form-label-secondary text-danger" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('messages.Required.') }}"> *
                                            </span></label>
                                        <select name="zone_id" class="js-select2-custom form-control" id="zone_id">
                                            <option value=""></option>
                                            @foreach ($zones as $key => $value)
                                                <option value="{{ $value['id'] }}">{{ $value['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="areas_input">Areas
                                            <textarea class="form-control auto-expand" name="areas" id="areas_input"></textarea>
                                    </div>
                                </div>
                                @if (Config::get('module.current_module_id') == 5)
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="sub-categories">{{ translate('messages.sub_category') }}<span
                                                    class="input-label-secondary"
                                                    title="{{ translate('messages.category_required_warning') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.category_required_warning') }}"></span></label>
                                            <select name="sub_category_id" class="js-data-example-ajax form-control"
                                                data-placeholder="{{ translate('messages.select_sub_category') }}"
                                                id="sub-categories">

                                            </select>
                                        </div>
                                    </div>
                                @endif

                                @if (Config::get('module.current_module_type') == 'ecommerce')
                                    <div class="col-sm-6 col-lg-3" id="unit_input">
                                        <div class="form-group mb-0">
                                            <label class="input-label text-capitalize"
                                                for="unit">{{ translate('messages.unit') }}</label>
                                            <select name="unit" id="unit" class="form-control js-select2-custom">
                                                @foreach (\App\Models\Unit::all() as $unit)
                                                    <option value="{{ $unit->id }}">{{ $unit->unit }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-sm-{{ Config::get('module.current_module_type') == 'food' ? '4' : '3' }} col-6 {{ Config::get('module.current_module_type') == 'ecommerce' ? '' : 'invisible' }}"
                                        id="stock_input">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="total_stock">{{ translate('messages.total_stock') }}</label>
                                            <input type="number" placeholder="{{ translate('messages.Ex:_10') }}"
                                                class="form-control" name="current_stock" min="0" id="quantity">
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="exampleFormControlInput1">{{ translate('messages.HSN Code') }}</label>
                                            <input type="number" min="0" max="999999999999" name="hsn_code"
                                                id="" class="form-control"
                                                placeholder="{{ translate('messages.Ex:') }} 6109" required>
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-lg-3" id="maximum_cart_quantity">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="maximum_cart_quantity">{{ translate('messages.Maximum_Purchase_Quantity_Limit') }}
                                                <span class="input-label-secondary text--title" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('If_this_limit_is_exceeded,_customers_can_not_buy_the_item_in_a_single_purchase.') }}">
                                                    <i class="tio-info-outined"></i>
                                                </span>
                                            </label>
                                            <input type="number" placeholder="{{ translate('messages.Ex:_10') }}"
                                                class="form-control" name="maximum_cart_quantity" min="0"
                                                id="cart_quantity">
                                        </div>
                                    </div>

                                @endif
                            </div>
                        </div>
                    </div>
                </div> --}}


                @if (Config::get('module.current_module_id') == 5)
                    <div class="col-md-12">
                        <div class="card shadow--card-2 border-0">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <span class="card-header-icon"><i class="tio-label-outlined"></i></span>
                                    <span>{{ translate('Price Information') }}</span>
                                </h5>
                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                    data-target="#priceCalcModal">Price Calculator</button>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-sm-3 col-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="exampleFormControlInput1">{{ translate('messages.fee_category') }}
                                                <span class="form-label-secondary text-danger" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.Required.') }}"> *
                                                </span></label>
                                            <select name="fee_category" id="fee_category" onchange="calcValues()"
                                                class="form-control js-select2-custom" required>
                                                <option value=""></option>
                                                @foreach ($fee_categories as $key => $fee)
                                                    <option data-value="{{ $fee->total_fee }}"
                                                        value="{{ $fee->id }}">
                                                        {{ $fee->name . ' | ' . $fee->platform_fee . ' + ' . $fee->payment_gateway_fee . '%' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-3 col-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="exampleFormControlInput1">{{ translate('messages.MRP') }} <span
                                                    class="form-label-secondary text-danger" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.Required.') }}"> *
                                                </span></label>
                                            <input type="number" min="0" max="999999999999.99" id="priceInp"
                                                step="0.001" value="1" name="mrp_price" class="form-control"
                                                placeholder="{{ translate('messages.Ex:') }} 100" required>
                                        </div>
                                    </div>
                                    <div class="col-sm-3 col-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="exampleFormControlInput1">{{ translate('messages.asking_price') }}
                                                <span class="form-label-secondary text-danger" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.Required.') }}"> *
                                                </span></label>
                                            <div class="input-group mb-3">
                                                <input oninput="calcValues()" type="number" min="0"
                                                    max="999999999999.99" name="asking_price" class="form-control"
                                                    step="0.000001" placeholder="{{ translate('messages.Ex:') }} 100"
                                                    required>
                                                {{-- <span class="input-group-text" id="remaining_price_show"></span> --}}
                                            </div>

                                            <input type="hidden" min="0" max="999999999999.99"
                                                name="remaining_price" value='0' class="form-control"
                                                step="0.000001">
                                        </div>
                                    </div>

                                    <div class="col-sm-3 col-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="exampleFormControlInput1">{{ translate('messages.selling_price') }}
                                                <span class="form-label-secondary text-danger" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.Required.') }}"> *
                                                </span></label>
                                            <input style="pointer-events:none;" type="number" id="selling_price_inp"
                                                min="0" max="999999999999.99" name="price"
                                                class="form-control non_changeable" step="0.001"
                                                placeholder="Calculated Automatically" required>
                                        </div>
                                    </div>

                                    <div class="col-sm-3 col-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="exampleFormControlInput1">{{ translate('messages.discount') }}
                                                <span id=symble>(%)</span>
                                                <span class="form-label-secondary text-danger" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.Required.') }}"> *
                                                </span></label>
                                            <input style="pointer-events:none;" type="number" min="0"
                                                step="0.000001" max="100000" name="discount"
                                                class="form-control non_changeable" placeholder="Calculated Automatically"
                                                id="discount_value_inp">
                                        </div>
                                    </div>
                                    <div class="col-sm-3 col-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label" for="exampleFormControlInput1">MyChitti Fee</label>
                                            <input type="number" min="0" max="100000"
                                                class="form-control non_changeable" name="mychitty_fee"
                                                placeholder="Calculated Automatically" id="mychitty_fee" readonly>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="exampleFormControlInput1">{{ translate('messages.GST') }}(%)</label>
                                            <input type="number" min="0" max="999999999999" name="gst_percent"
                                                id="" class="form-control"
                                                placeholder="{{ translate('messages.Ex:') }} 12">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <input type="hidden" value="1" name="price">
                    <input type="hidden" value="2" name="mrp_price">
                    <input type="hidden" value="1" name="asking_price">
                    <input type="hidden" value="0" name="discount">
                    <input type="hidden" value="0" name="gst_percent">
                    <input type="hidden" value="percent" name="discount_type">
                    <input type="hidden" value="0000" name="hsn_code">
                    <input type="hidden" value="0" name="fee_category">
                @endif
                @if (Config::get('module.current_module_type') == 'ecommerce')
                    <div class="col-md-12" id="attribute_section">
                        <div class="card shadow--card-2 border-0">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <span class="card-header-icon"><i class="tio-canvas-text"></i></span>
                                    <span>{{ translate('attribute') }}</span>
                                </h5>
                            </div>
                            <div class="card-body pb-0">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="exampleFormControlSelect1">{{ translate('messages.attribute') }}<span
                                                    class="input-label-secondary"></span></label>
                                            <select name="attribute_id[]" id="choice_attributes"
                                                class="form-control js-select2-custom" multiple="multiple">
                                                @foreach (\App\Models\Attribute::orderBy('name')->get() as $attribute)
                                                    <option value="{{ $attribute['id'] }}">{{ $attribute['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <div class="customer_choice_options d-flex __gap-24px"
                                                id="customer_choice_options">

                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="variant_combination" id="variant_combination">

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="col-12">
                    <!-- SEO Section -->
                    <div class="seo-section">
                        <div class="seo-header" data-toggle="collapse" data-target="#seoCollapse" aria-expanded="false"
                            aria-controls="seoCollapse">
                            <h4><i class="fas fa-search-dollar mr-2"></i> SEO Settings</h4>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="collapse show" id="seoCollapse">
                            <div class="pt-3 px-3">
                                <h4>Placeholders: </h4>
                                <p><code>{CITY_NAME}</code>, <code>{SERVICE_NAME}</code>, <code>{LOCALITIES}</code>,
                                    <code>{SAME_CATEGORY_SERVICES}</code></p>
                            </div>
                            <div class="seo-body">

                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-file-alt mr-2 text-primary"></i>Common SEO
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="border rounded p-3 mb-3 row">
                                            <div class="form-group mb-0 col-md-4">
                                                <label class="form-label">Meta Title</label>
                                                <input type="text" class="form-control"
                                                    placeholder="{SERVICE_NAME} in {CITY_NAME} | Trusted Experts"
                                                    name ="meta_title" id="">
                                            </div>
                                            <div class="form-group mb-0 col-md-8">
                                                <label class="form-label">Meta Description</label>
                                                <textarea type="text" class="form-control" rows="1"
                                                    placeholder="Get reliable {SERVICE_NAME} in {CITY_NAME} with fast service, expert technicians, and affordable pricing."
                                                    name="meta_desc" id=""></textarea>
                                            </div>
                                            <div class="form-group mb-0 col-md-4">
                                                <label class="form-label">Service Heading <i class="tio-help-outlined"
                                                        data-toggle="tooltip" data-placement="right"
                                                        title="Will show on service detail page in website as main service name"></i></label>
                                                <input type="text" class="form-control" name="seo_heading"
                                                    placeholder="{SERVICE_NAME} in {CITY_NAME}" id="">
                                            </div>


                                            <div class="form-group mb-0 col-md-4">
                                                <div class="d-flex justify-content-between">
                                                    <label class="form-label">Meta Keywords <img
                                                            class="avatar avatar-xss avatar-4by3 mr-2"
                                                            src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                                            alt="Image Description"></label>
                                                    <a href="{{ asset('storage/app/public/export-keywords.xlsx') }}"
                                                        class="text-underline">Download Example Excel</a>
                                                </div>
                                                <input type="file" name="keyword_excel[]" id="import_excel"
                                                    class="form-control" multiple>
                                            </div>
                                            <div class="form-group mb-0 col-md-4">
                                                <div class="d-flex justify-content-between">

                                                    <label class="form-label"><span>Keywords Preview</span></label> <a
                                                        style="display:none;"
                                                        class="cursor-pointer text-underline add_more_keywords_btn">Add
                                                        more</a>
                                                </div>

                                                <div class="">
                                                    <textarea class="form-control" readonly id="keywords"></textarea>
                                                    <textarea style="display:none;" name="more_keywords" class="form-control more_keywords_field mt-2"
                                                        placeholder="More Keywords..." id="keywords"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{--  --}}



                                <!-- SEO Content Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-file-alt mr-2 text-primary"></i> SEO
                                            Footer Content</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="seoContentContainer">
                                            <!-- SEO Content Item 1 -->
                                            <div class="seo-content-item border rounded p-3 mb-3" data-index="1">
                                                <div class="form-group mb-0">
                                                    <label class="form-label">SEO Content (Option 1)</label>
                                                    <textarea class="form-control ck_editor" id="seo_editor_1" placeholder="Enter SEO-friendly content for your service"></textarea>
                                                </div>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger mt-2 remove-seo-content">
                                                    <i class="tio-delete"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="addSeoContent">
                                            <i class="fas fa-plus"></i> Add More SEO Content
                                        </button>
                                    </div>
                                </div>

                                <!-- FAQ Section -->
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-question-circle mr-2 text-success"></i>
                                            FAQ Section</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="faqContainer">
                                            <!-- FAQ Item 1 -->
                                            <div class="faq-item border rounded p-3 mb-3" data-index="1">
                                                <div class="form-group mb-0">
                                                    <label class="form-label">FAQ (Option 1)</label>
                                                    <textarea class="form-control ck_editor" id="faq_editor_1" placeholder="Enter FAQ content"></textarea>
                                                </div>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger mt-2 remove-faq">
                                                    <i class="tio-delete"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="addFaq">
                                            <i class="fas fa-plus"></i> Add More FAQ
                                        </button>
                                    </div>
                                </div>
                                <!-- FAQ Section -->
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-question-circle mr-2 text-success"></i>
                                            FAQs (For Google Schema)</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="faqGoogleContainer">
                                            <div id="item-faq-wrapper">

                                                @forelse($itemFaqs ?? [] as $i => $faq)
                                                    <div class="item-faq-row card mb-2">
                                                        <div class="card-body p-3">

                                                            <div class="form-group mb-2">
                                                                <label>Question</label>
                                                                <input type="text"
                                                                    name="faqs[{{ $i }}][question]"
                                                                    class="form-control"
                                                                    value="{{ old('faqs.' . $i . '.question', $faq->question) }}">
                                                            </div>

                                                            <div class="form-group mb-2">
                                                                <label>Answer</label>
                                                                <textarea name="faqs[{{ $i }}][answer]" class="form-control" rows="2">{{ old('faqs.' . $i . '.answer', $faq->answer) }}</textarea>
                                                            </div>

                                                            <button type="button"
                                                                class="btn btn-sm btn-danger remove-item-faq">
                                                                Remove
                                                            </button>

                                                        </div>
                                                    </div>

                                                @empty
                                                    {{-- start with one empty row if needed --}}
                                                @endforelse

                                            </div>

                                            <button type="button" class="btn btn-sm btn-primary" id="add-item-faq">
                                                + Add FAQ
                                            </button>
                                        </div>
                                    
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- <div class="col-md-12">
                    <div class="card shadow--card-2 border-0">
                        <div class="card-header">
                            <h5 class="card-title">
                                <span class="card-header-icon"><i class="tio-label"></i></span>
                                <span>Keywords</span>
                            </h5>
                        </div>

                        <div class="card-body pb-0">
                            <div class="row g-2">
                                <div class="col-6">

                                    <img class="avatar avatar-xss avatar-4by3 mr-2"
                                        src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                        alt="Image Description">
                                    Import {{ translate('messages.excel') }} <br>
                                    <input type="file" name="keyword_excel" class="form-control" id="">

                                </div>
                                <div class="col-6 d-flex align-items-end mb-3">
                                    <a href="{{ asset('storage/app/public/export-keywords.xlsx') }}"
                                        class="btn btn-outline-primary">Download Example Excel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}
                <div class="col-md-12">
                    <div class="btn--container justify-content-end">
                        <button type="reset" id="reset_btn"
                            class="btn btn--reset">{{ translate('messages.reset') }}</button>
                        <button type="submit" id="submitButton"
                            class="btn btn--primary">{{ translate('messages.submit') }}</button>
                    </div>
                </div>
            </div>
        </form>


        <!-- Modal -->
        <div class="modal fade" id="priceCalcModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Price Calculator</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label class="input-label" for="deliveryCalcInp">Minimum Delivery Charge</label>

                            <input type="number" id="deliveryCalcInp"
                                data-value="{{ \App\Models\BusinessSetting::where(['key' => 'minimum_shipping_charge'])->first()->value }}"
                                value="{{ \App\Models\BusinessSetting::where('key', 'minimum_shipping_charge')->first()->value + (\App\Models\BusinessSetting::where('key', 'minimum_shipping_charge')->first()->value * \App\Models\BusinessSetting::where('key', 'delivery_charge_comission')->first()->value) / 100 }}"
                                min="0" class="form-control" placeholder="{{ translate('messages.Ex:') }} 100">
                        </div>

                        <div class="form-group mb-0">
                            <label class="input-label" for="saleCommCalcInp">Sale Commission</label>
                            <input type="number" value="" style="pointer-events: none;" id="saleCommCalcInp"
                                min="0" class="form-control" placeholder="{{ translate('messages.Ex:') }} 100">
                        </div>
                        <div class="form-group mb-0">
                            <label class="input-label" for="priceCalcInp">Price</label>
                            <input type="number" id="priceCalcInp" value="" min="0" class="form-control"
                                placeholder="{{ translate('messages.Ex:') }} 100">
                        </div>
                        <input type="hidden" id="deliveryChargeCommision"
                            value="{{ \App\Models\BusinessSetting::where('key', 'delivery_charge_comission')->first()->value }}">
                        <input type="hidden" id="saleCommisionPercent"
                            value="{{ \App\Models\BusinessSetting::where('key', 'admin_commission')->first()->value }}">

                        <div class="form-group mb-0">
                            <label class="input-label" for="discountTypeCalcInp">Discount Type</label>
                            <select name="discount_type2" id="discount_type2" class="form-control js-select2-custom">
                                <option value="percent">{{ translate('messages.percent') }} (%)</option>
                                <option value="amount">{{ translate('messages.amount') }}
                                    ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                </option>
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label class="input-label" for="discountValueCalcInp">Discount Value</label>
                            <input type="number" id="discountValueCalcInp" value="0" min="0"
                                class="form-control" placeholder="{{ translate('messages.Ex:') }} 100">
                        </div>

                        <div class="form-group mb-0">
                            <label class="input-label" for="">Final Price</label>
                            {{ \App\CentralLogics\Helpers::currency_symbol() }}<span id="finalCalcAmount">0</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
                    </div>
                </div>
            </div>
        </div>
    </div>



@endsection


@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/tags-input.min.js"></script>
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/product-index.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        "use strict";

        $(".add_more_keywords_btn").on('click', function() {
            $(".more_keywords_field").show()
            $(".add_more_keywords_btn").hide()
        })

        $("#import_excel").on("change", function(e) {
            const file = e.target.files[0];

            if (!file) return;

            const reader = new FileReader();

            reader.onload = function(e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, {
                    type: "array"
                });

                const sheetName = workbook.SheetNames[0];
                const sheet = workbook.Sheets[sheetName];

                const rows = XLSX.utils.sheet_to_json(sheet, {
                    header: 1
                });

                const keywords = rows
                    .flat()
                    .map(k => String(k).trim())
                    .filter(k => k.length)
                    .slice(0, 20);

                $("#keywords").val(keywords.join(", ") + '...');
            };

            $(".add_more_keywords_btn").show()

            reader.readAsArrayBuffer(file);
        });


        $(document).on('change', '#discount_type', function() {
            let data = document.getElementById("discount_type");
            if (data.value === 'amount') {
                $('#symble').text("({{ \App\CentralLogics\Helpers::currency_symbol() }})");
            } else {
                $('#symble').text("(%)");
            }
        });


        $(document).ready(function() {
            $("#add_new_option_button").click(function(e) {
                $('#empty-variation').hide();
                count++;
                let add_option_view = `
                    <div class="__bg-F8F9FC-card view_new_option mb-2">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <label class="form-check form--check">
                                    <input id="options[` + count + `][required]" name="options[` + count + `][required]" class="form-check-input" type="checkbox">
                                    <span class="form-check-label">{{ translate('Required') }}</span>
                                </label>
                                <div>
                                    <button type="button" class="btn btn-danger btn-sm delete_input_button"
                                        title="{{ translate('Delete') }}">
                                        <i class="tio-add-to-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-xl-4 col-lg-6">
                                    <label for="">{{ translate('name') }}</label>
                                    <input required name=options[` + count +
                    `][name] class="form-control new_option_name" type="text" data-count="` +
                    count + `">
                                </div>

                                <div class="col-xl-4 col-lg-6">
                                    <div>
                                        <label class="input-label text-capitalize d-flex align-items-center"><span class="line--limit-1">{{ translate('messages.selcetion_type') }} </span>
                                        </label>
                                        <div class="resturant-type-group px-0">
                                            <label class="form-check form--check mr-2 mr-md-4">
                                                <input class="form-check-input show_min_max" data-count="` + count + `" type="radio" value="multi"
                                                name="options[` + count + `][type]" id="type` + count +
                    `" checked
                                                >
                                                <span class="form-check-label">
                                                    {{ translate('Multiple Selection') }}
                    </span>
                </label>

                <label class="form-check form--check mr-2 mr-md-4">
                    <input class="form-check-input hide_min_max" data-count="` + count + `" type="radio" value="single"
                    name="options[` + count + `][type]" id="type` + count +
                    `"
                                                >
                                                <span class="form-check-label">
                                                    {{ translate('Single Selection') }}
                    </span>
                </label>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="row g-2">
            <div class="col-6">
                <label for="">{{ translate('Min') }}</label>
                                            <input id="min_max1_` + count + `" required  name="options[` + count + `][min]" class="form-control" type="number" min="1">
                                        </div>
                                        <div class="col-6">
                                            <label for="">{{ translate('Max') }}</label>
                                            <input id="min_max2_` + count + `"   required name="options[` + count + `][max]" class="form-control" type="number" min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="option_price_` + count + `" >
                                <div class="bg-white border rounded p-3 pb-0 mt-3">
                                    <div  id="option_price_view_` + count + `">
                                        <div class="row g-3 add_new_view_row_class mb-3">
                                            <div class="col-md-4 col-sm-6">
                                                <label for="">{{ translate('Option_name') }}</label>
                                                <input class="form-control" required type="text" name="options[` +
                    count +
                    `][values][0][label]" id="">
                                            </div>
                                            <div class="col-md-4 col-sm-6">
                                                <label for="">{{ translate('Additional_price') }}</label>
                                                <input class="form-control" required type="number" min="0" step="0.001" name="options[` +
                    count + `][values][0][optionPrice]" id="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3 p-3 mr-1 d-flex "  id="add_new_button_` + count +
                    `">
                                        <button type="button" class="btn btn--primary btn-outline-primary add_new_row_button" data-count="` +
                    count + `">{{ translate('Add_New_Option') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;

                $("#add_new_option").append(add_option_view);
            });

            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function() {
                let select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });

        function add_new_row_button(data) {
            count = data;
            countRow = 1 + $('#option_price_view_' + data).children('.add_new_view_row_class').length;
            let add_new_row_view = `
            <div class="row add_new_view_row_class mb-3 position-relative pt-3 pt-sm-0">
                <div class="col-md-4 col-sm-5">
                        <label for="">{{ translate('Option_name') }}</label>
                        <input class="form-control" required type="text" name="options[` + count + `][values][` +
                countRow + `][label]" id="">
                    </div>
                    <div class="col-md-4 col-sm-5">
                        <label for="">{{ translate('Additional_price') }}</label>
                        <input class="form-control"  required type="number" min="0" step="0.001" name="options[` +
                count +
                `][values][` + countRow + `][optionPrice]" id="">
                    </div>
                    <div class="col-sm-2 max-sm-absolute">
                        <label class="d-none d-sm-block">&nbsp;</label>
                        <div class="mt-1">
                            <button type="button" class="btn btn-danger btn-sm deleteRow"
                                title="{{ translate('Delete') }}">
                                <i class="tio-add-to-trash"></i>
                            </button>
                        </div>
                </div>
            </div>`;
            $('#option_price_view_' + data).append(add_new_row_view);

        }


        $('#store_id').on('change', function() {
            let route = '{{ url('/') }}/store/get-addons?data[]=0&store_id=' + $(this).val();
            let id = 'add_on';
            getRestaurantData(route, id);
        });

        function modulChange(id) {
            $.get({
                url: "{{ url('/') }}/business-settings/module/show/" + id,
                dataType: 'json',
                success: function(data) {
                    module_data = data.data;
                    stock = module_data.stock;
                    module_type = data.type;
                    if (stock) {
                        $('#stock_input').show();
                    } else {
                        $('#stock_input').hide();
                    }
                    if (module_data.add_on) {
                        $('#addon_input').show();
                    } else {
                        $('#addon_input').hide();
                    }

                    if (module_data.item_available_time) {
                        $('#time_input').show();
                    } else {
                        $('#time_input').hide();
                    }

                    if (module_data.veg_non_veg) {
                        $('#veg_input').show();
                    } else {
                        $('#veg_input').hide();
                    }
                    if (module_data.unit) {
                        $('#unit_input').show();
                    } else {
                        $('#unit_input').hide();
                    }
                    if (module_data.common_condition) {
                        $('#condition_input').show();
                    } else {
                        $('#condition_input').hide();
                    }
                    combination_update();
                    if (module_type == 'food') {
                        $('#food_variation_section').show();
                        $('#attribute_section').hide();
                    } else {
                        $('#food_variation_section').hide();
                        $('#attribute_section').show();
                    }

                },
            });
            module_id = id;
        }

        modulChange({{ Config::get('module.current_module_id') }});

        $('#condition_id').select2({
            ajax: {
                url: '{{ url('/') }}/common-condition/get-all',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        page: params.page,
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                __port: function(params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });

        $('#store_id').select2({
            ajax: {
                url: '{{ url('/') }}/store/get-stores',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        page: params.page,
                        module_id: {{ Config::get('module.current_module_id') }},
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                __port: function(params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });
        $("#deliveryCalcInp").on('keyup', function() {
            $(this).attr('data-value', $(this).val());
            calculateFinalTotal();
        })
        $("#priceInp").on('keyup', function() {
            $('#priceCalcInp').val($(this).val())
            calculateFinalTotal();
        })
        $("#discount_type").on('change', function() {
            $('#discount_type2').val($(this).val()).trigger('change');
            calculateFinalTotal();
        })
        $("#discountInp").on('keyup', function() {
            $('#discountValueCalcInp').val($(this).val());
            calculateFinalTotal();
        })
        $('#priceCalcInp').on('keyup', function() {
            calculateFinalTotal();
        })
        $('#saleCommCalcInp').on('keyup', function() {
            calculateFinalTotal();
        })
        $('#deliveryCalcInp').on('keyup', function() {
            $(this).attr('data-value', $(this).val())
            calculateFinalTotal();
        })
        $('#discount_type2').on('change', function() {
            calculateFinalTotal();
        })
        $('#discountValueCalcInp').on('keyup', function() {
            calculateFinalTotal();
        })

        function calculateFinalTotal() {
            var finalAmount = 0;
            var discountType = $('#discount_type2').val() ?? 'percent';
            var discountValue = parseFloat($('#discountValueCalcInp').val()) ?? 0;
            var price = parseFloat($('#priceCalcInp').val());
            var discountAmount = 0;
            if (discountType === 'percent') {
                discountAmount = (price * discountValue) / 100;
            } else {
                discountAmount = discountValue;
            }
            var deliveryCharge = parseFloat($('#deliveryCalcInp').attr('data-value'));
            var deliveryChargeCommissionPercent = parseFloat($('#deliveryChargeCommision').val());
            var deliveryChargeCommission = (deliveryCharge * deliveryChargeCommissionPercent) / 100;
            var saleCommisionPercent = parseFloat($('#saleCommisionPercent').val());
            var saleCommission = ((price - discountAmount) * saleCommisionPercent) / 100

            finalAmount += price;
            finalAmount -= discountAmount;
            finalAmount += saleCommission;
            finalAmount += deliveryCharge;
            finalAmount += deliveryChargeCommission;
            $('#saleCommCalcInp').val(saleCommission)
            $("#finalCalcAmount").text(finalAmount)
        }
        const fullUrl =
            '{{ request()->getHost() === 'staging.mychitti.net' ? url('admin/item/get-categories?parent_id=0') : url('item/get-categories?parent_id=0') }}';

        $('#category_id').select2({
            ajax: {
                url: fullUrl,
                data: function(params) {
                    return {
                        q: params.term, // search term
                        page: params.page,
                        module_id: {{ Config::get('module.current_module_id') }},
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                __port: function(params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });

        $('#sub-categories').select2({
            ajax: {
                url: '{{ url('/') }}/item/get-categories',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        page: params.page,
                        module_id: {{ Config::get('module.current_module_id') }},
                        parent_id: parent_category_id,
                        sub_category: true
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                __port: function(params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });

        $('#choice_attributes').on('change', function() {
            if (module_id == 0) {
                toastr.error('{{ translate('messages.select_a_module') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                $(this).val("");
                return false;
            }
            $('#customer_choice_options').html(null);
            $('#variant_combination').html(null);
            $.each($("#choice_attributes option:selected"), function() {
                if ($(this).val().length > 50) {
                    toastr.error(
                        '{{ translate('validation.max.string', ['attribute' => translate('messages.variation'), 'max' => '50']) }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    return false;
                }
                add_more_customer_choice_option($(this).val(), $(this).text());
            });
        });

        function add_more_customer_choice_option(i, name) {
            let n = name;

            $('#customer_choice_options').append(
                `<div class="__choos-item"><div><input type="hidden" name="choice_no[]" value="${i}"><input type="text" class="form-control d-none" name="choice[]" value="${n}" placeholder="{{ translate('messages.choice_title') }}" readonly> <label class="form-label">${n}</label> </div><div><input type="text" class="form-control combination_update" name="choice_options_${i}[]" placeholder="{{ translate('messages.enter_choice_values') }}" data-role="tagsinput"></div></div>`
            );
            $("input[data-role=tagsinput], select[multiple][data-role=tagsinput]").tagsinput();
        }

        function combination_update() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            console.log($('.editor'))
            $.ajax({
                type: "POST",
                url: "{{ route('admin.item.variant-combination') }}",
                data: $('#product_form').serialize() + '&stock=' + stock,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#loading').hide();
                    $('#variant_combination').html(data.view);
                    if (data.length < 1) {
                        $('input[name="current_stock"]').attr("readonly", false);
                    }
                }
            });
        }

        $(function() {
            $("#coba").spartanMultiImagePicker({
                fieldName: 'item_images[]',
                maxCount: 5,
                rowHeight: '176px !important',
                groupClassName: 'spartan_item_wrapper min-w-176px max-w-176px',
                maxFileSize: '',
                placeholderImage: {
                    image: "{{ asset('public/assets/admin/img/upload-img.png') }}",
                    width: '176px'
                },
                dropFileLabel: "Drop Here",
                onAddRow: function(index, file) {

                },
                onRenderedPreview: function(index) {

                },
                onRemoveRow: function(index) {

                },
                onExtensionErr: function(index, file) {
                    toastr.error(
                        "{{ translate('messages.please_only_input_png_or_jpg_type_file') }}", {
                            CloseButton: true,
                            ProgressBar: true
                        });
                },
                onSizeErr: function(index, file) {
                    toastr.error("{{ translate('messages.file_size_too_big') }}", {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        });

        $('#reset_btn').click(function() {
            $('#module_id').val(null).trigger('change');
            $('#store_id').val(null).trigger('change');
            $('#category_id').val(null).trigger('change');
            $('#sub-categories').val(null).trigger('change');
            $('#unit').val(null).trigger('change');
            $('#veg').val(0).trigger('change');
            $('#add_on').val(null).trigger('change');
            $('#discount_type').val(null).trigger('change');
            $('#choice_attributes').val(null).trigger('change');
            $('#customer_choice_options').empty().trigger('change');
            $('#variant_combination').empty().trigger('change');
            $('#viewer').attr('src', "{{ asset('public/assets/admin/img/upload.png') }}");
            $('#customFileEg1').val(null).trigger('change');
            $("#coba").empty().spartanMultiImagePicker({
                fieldName: 'item_images[]',
                maxCount: 6,
                rowHeight: '176px !important',
                groupClassName: 'spartan_item_wrapper min-w-176px max-w-176px',
                maxFileSize: '',
                placeholderImage: {
                    image: "{{ asset('public/assets/admin/img/upload-img.png') }}",
                    width: '100%'
                },
                dropFileLabel: "Drop Here",
                onAddRow: function(index, file) {

                },
                onRenderedPreview: function(index) {

                },
                onRemoveRow: function(index) {

                },
                onExtensionErr: function(index, file) {
                    toastr.error(
                        "{{ translate('messages.please_only_input_png_or_jpg_type_file') }}", {
                            CloseButton: true,
                            ProgressBar: true
                        });
                },
                onSizeErr: function(index, file) {
                    toastr.error("{{ translate('messages.file_size_too_big') }}", {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        })

        function calcValues() {
            let feePercent = parseFloat($('#fee_category option:selected').data('value')) || 0;
            console.log(feePercent)
            let asking_price = parseFloat($('input[name=asking_price]').val()) || 0;
            let mrp_price = parseFloat($('input[name=mrp_price]').val()) || 0;

            let selling_price_inp = $('#selling_price_inp');
            let discount_value_inp = $('#discount_value_inp');

            let mychitti_fee = asking_price * feePercent / 100

            let selling_price = asking_price + mychitti_fee;
            selling_price_inp.val(selling_price.toFixed(2));

            let discountPercent = ((mrp_price - selling_price) / mrp_price) * 100;
            discount_value_inp.val(discountPercent.toFixed(6));
            let flooredDiscount = Math.floor(discountPercent);

            $('#mychitty_fee').val(mychitti_fee.toFixed(2));

            // let remainingDiscount = discountPercent - flooredDiscount; // Remaining percentage
            // let remainingAmount = (remainingDiscount / 100) * mrp_price;
            // $('input[name=remaining_price]').val(remainingAmount)
            // $('#remaining_price_show').text('+' + remainingAmount.toFixed(2))


            // === Variation Pricing ===
            $('input[name^="mrpprice_"]').each(function() {
                let $this = $(this);
                let priceVal = parseFloat($this.val()) || 0; // MRP Price
                let variationId = $this.attr('name').split('mrpprice_')[1];
                variationId = variationId.replace(/\+/g, '\\+');

                let vrAskingPrice = parseFloat($(`input[name="askingprice_${variationId}"]`).val()) || 0;

                let var_mychitty_fee = vrAskingPrice * feePercent / 100;
                let var_selling_price = vrAskingPrice + var_mychitty_fee;

                $(`input[name="price_${variationId}"]`).val(var_selling_price.toFixed(2));

                let var_discountPercent = ((priceVal - var_selling_price) / priceVal) * 100;
                $(`input[name="discount_${variationId}"]`).val(var_discountPercent.toFixed(6));
                console.log('discount is ' + var_discountPercent)
                let var_flooredDiscount = Math.floor(var_discountPercent);

                //let var_remainingDiscount = var_discountPercent - var_flooredDiscount; // Remaining percentage

                //if (!isNaN(var_remainingDiscount) && !isNaN(priceVal)) {
                //    let var_remainingAmount = (var_remainingDiscount / 100) * priceVal;
                //    $(`input[name=remainingprice_${variationId}]`).val(var_remainingAmount.toFixed(
                //    5)); 
                //    $(`#remaining_price_show_${variationId}`).text('+' + var_remainingAmount.toFixed(
                //    2)); 
                //}

                // Uncomment if needed
                // $(`input[name="mychitty_fee_${variationId}"]`).val(var_mychitty_fee.toFixed(2));
            });

        }

        $('.auto-expand').each(function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        $(document).on('input change', '.auto-expand', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
        $("#zone_id").on('change', function() {
            var zone_id = $(this).val();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.item.get-areas-in-zone') }}',
                data: {
                    zone_id: zone_id
                },
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(data) {
                    console.log(data)
                    $('#areas_input').val(data).trigger('input');
                },
                complete: function() {
                    $('#loading').hide()
                }
            });
        })
    </script>
    <script>
let itemFaqIndex = {{ isset($itemFaqs) ? $itemFaqs->count() : 0 }};

document.getElementById('add-item-faq').addEventListener('click', function () {

    let html = `
    <div class="item-faq-row card mb-2">
        <div class="card-body p-3">

            <div class="form-group mb-2">
                <label>Question</label>
                <input type="text"
                       name="faqs[${itemFaqIndex}][question]"
                       class="form-control">
            </div>

            <div class="form-group mb-2">
                <label>Answer</label>
                <textarea name="faqs[${itemFaqIndex}][answer]"
                          class="form-control"
                          rows="2"></textarea>
            </div>

            <button type="button" class="btn btn-sm btn-danger remove-item-faq">
                Remove
            </button>

        </div>
    </div>
    `;

    document.getElementById('item-faq-wrapper')
        .insertAdjacentHTML('beforeend', html);

    itemFaqIndex++;
});

document.addEventListener('click', function(e){
    if(e.target.classList.contains('remove-item-faq')){
        e.target.closest('.item-faq-row').remove();
    }
});
</script>

    @include('vendor-views/multiple_ck_editor');
@endpush
