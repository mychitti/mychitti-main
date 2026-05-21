@extends('layouts.vendor.app')

@section('title', translate('messages.add_new_item'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/tags-input.min.css') }}" rel="stylesheet">
@endpush

@section('content')
    @php($module_type = \App\CentralLogics\Helpers::get_store_data()->module->module_type)
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/items.png') }}" class="w--22" alt="">
                </span>
                <span>
                    {{ translate('messages.add_new_item') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <form action="javascript:" method="post" id="item_form" enctype="multipart/form-data">
            @csrf
            @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
            @php($language = $language->value ?? null)
            @php($defaultLang = str_replace('_', '-', app()->getLocale()))
            <div class="row g-2">

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title">
                                <span class="card-header-icon">
                                    <i class="tio-dashboard-outlined"></i>
                                </span>
                                <span>{{ translate('item_info') }}</span>
                            </h5>
                        </div>
                        <div class="card-body">

                            <div id="default-form">
                                <div class="form-group">
                                    <label class="input-label"
                                        for="exampleFormControlInput1">{{ translate('messages.name') }} </label>
                                    <input type="text" name="name[]" class="form-control"
                                        placeholder="{{ translate('messages.new_item') }}" required>
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                                <div class="form-group mb-0">
                                    <label class="input-label"
                                        for="exampleFormControlInput1">{{ translate('messages.highlights') }}</label>
                                    <textarea type="text" name="description[]" class="form-control min-h-90px ckeditor"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title">
                                <span class="card-header-icon">
                                    <i class="tio-image"></i>
                                </span>
                                <span>{{ translate('item_image') }}</span>
                            </h5>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="mb-auto">
                                <label class="input-label"
                                    for="exampleFormControlInput1">{{ translate('messages.item_images') }}</label>
                                <div class="row py-2" id="coba"></div>
                            </div>
                            <div class="mt-3">
                                <label class="text-dark">
                                    {{ translate('messages.item_thumbnail') }}
                                    <small class="text-danger">* ( {{ translate('messages.ratio') }} 1:1 )</small>
                                </label>
                                <div id="image-viewer-section" class="text-center pt-2 pb-3 text-left">
                                    <img class="img--100" id="viewer"
                                        src="{{ asset('public/assets/admin/img/100x100/2.png') }}" alt="banner image" />
                                </div>
                                <div class="custom-file">
                                    <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                                    <label class="custom-file-label"
                                        for="customFileEg1">{{ translate('messages.choose_file') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if (\App\CentralLogics\Helpers::get_store_data()->module_id == 5)
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

                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <span class="card-header-icon">
                                    <i class="tio-dollar-outlined"></i>
                                </span>
                                <span> {{ translate('item_details') }} </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('messages.category') }}<span
                                                class="input-label-secondary">*</span></label>
                                        <select name="category_id" id="category_id"
                                            class="form-control js-select2-custom get-request"
                                            data-url="{{ url('/') }}/item/get-categories?parent_id="
                                            data-id="sub-categories">
                                            <option value="">---{{ translate('messages.select') }}---</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('messages.sub_category') }}<span
                                                class="input-label-secondary"></span></label>
                                        <select name="sub_category_id" id="sub-categories"
                                            class="form-control js-select2-custom get-request"
                                            data-url="{{ url('/') }}/item/get-categories?parent_id="
                                            data-id="sub-sub-categories">
                                        </select>
                                    </div>
                                </div>
                                @if ($module_data['unit'])
                                    <div class="col-sm-6 col-lg-4">
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
                                @endif
                              
                          
                                @if ($module_data['stock'])
                                    <div class="col-sm-6 col-lg-4">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="total_stock">{{ translate('messages.total_stock') }}</label>
                                            <input type="number" class="form-control" name="current_stock"
                                                min="0" id="quantity">
                                        </div>
                                    </div>
                                @endif
                                <div class="col-sm-6 col-lg-4" id="maximum_cart_quantity">
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
                                
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.HSN Code') }}</label>
                                        <input type="number" min="0" max="999999999999" 
                                             name="hsn_code" id="" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 6109" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <span class="card-header-icon">
                                    <i class="tio-dollar-outlined"></i>
                                </span>
                                <span> {{ translate('price_details') }} </span>
                            </h5>
                            <button type="button" class="btn btn-primary" data-toggle="modal"
                                data-target="#priceCalcModal">Price Calculator</button>

                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                               
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.MRP') }}</label>
                                        <input type="number" min="0" max="999999999999" step="0.001"
                                             name="mrp" id="" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 100" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.asking_price') }}</label>
                                        <input type="number" min="0" max="999999999999" step="0.001"
                                             name="asking_price" id="" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 80" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.GST') }}(%)</label>
                                        <input type="number" min="0" max="999999999999" 
                                             name="gst_percent" id="" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 12" >
                                    </div>
                                </div>
                               
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12" id="food_variation_section">
                    <div class="card shadow--card-2 border-0">
                        <div class="card-header flex-wrap">
                            <h5 class="card-title">
                                <span class="card-header-icon mr-2">
                                    <i class="tio-canvas-text"></i>
                                </span>
                                <span>{{ translate('messages.food_variations') }}</span>
                            </h5>
                            <a class="btn text--primary-2" id="add_new_option_button">
                                {{ translate('add_new_variation') }}
                                <i class="tio-add"></i>
                            </a>
                        </div>
                        <div class="card-body">
                            <!-- Empty Variation -->
                            <div id="empty-variation">
                                <div class="text-center">
                                    <img src="{{ asset('/public/assets/admin/img/variation.png') }}" alt="">
                                    <div>{{ translate('No variation added') }}</div>
                                </div>
                            </div>
                            <div id="add_new_option">
                            </div>
                        </div>
                    </div>
                </div>
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
                                                <option value="{{ $attribute['id'] }}">{{ $attribute['name'] }}</option>
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
               
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header"> 
                            <h5 class="card-title">
                                <span class="card-header-icon"><i class="tio-label"></i></span>
                                <span>{{ translate('Keywords') }}</span>
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
                </div>
              
                <div class="col-12">
                    <div class="btn--container justify-content-end">
                        <button type="reset" id="reset_btn"
                            class="btn btn--reset">{{ translate('messages.reset') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="priceCalcModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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

@endsection

@push('script')
@endpush

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/tags-input.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css" />
    <script type="importmap">
    {
        "imports": {
            "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.js",
            "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.0.0/"
        }
    }
</script>

    <script type="module">
        import {
            ClassicEditor,
            Essentials,
            Bold,
            Italic,
            Font,
            Paragraph,
            Table,
            TableToolbar
        } from 'ckeditor5';

        let editorInstance;

        ClassicEditor
            .create(document.querySelector('#maineditor'), {
                plugins: [Essentials, Bold, Italic, Font, Paragraph, Table, TableToolbar],
                toolbar: {
                    items: [
                        'undo', 'redo', '|', 'bold', 'italic', '|',
                        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                        'insertTable', 'tableColumn', 'tableRow', 'mergeTableCells'
                    ]
                }
            })
            .then(editor => {
                editorInstance = editor;
            })
            .catch(error => {
                console.error('There was a problem initializing the editor.', error);
            });

        $('#item_form').on('submit', function(e) {
            $('#submitButton').attr('disabled', true);
            e.preventDefault();
            let formData = new FormData(this);
            if (editorInstance) {
                let encodedData = btoa(unescape(encodeURIComponent(editorInstance.getData())));
                formData.append('specifications', encodedData);
            }

            //variation editors 
            let editors2 = window.ckeditorInstances || {};
            document.querySelectorAll('.editor').forEach(editorElement => {
                if (editors2[editorElement.id]) {
                    let encodedData = btoa(unescape(encodeURIComponent(editors2[editorElement.id]
                        .getData())));
                    formData.append(editorElement.id, encodedData);
                }
            });
            //variation editors end

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('vendor.item.store') }}',
                data: $('#item_form').serialize(),
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#loading').hide();
                    if (data.errors) {
                        for (let i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    }
                    if (data.product_approval) {
                        toastr.success(data.product_approval, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function() {
                            location.href = '{{ route('vendor.item.pending_item_list') }}';
                        }, 2000);
                    }
                    if (data.success) {
                        toastr.success(data.success, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function() {
                            location.href = '{{ route('vendor.item.list') }}';
                        }, 2000);
                    }
                }
            });
        });
    </script>
    <script>
        "use strict";

        mod_type = "{{ $module_type }}";

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

            $.ajax({
                type: "POST",
                url: '{{ route('vendor.item.variant-combination') }}',
                data: $('#item_form').serialize() + '&stock={{ $module_data['stock'] }}',
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
            $("#finalCalcAmount").text(finalAmount.toFixed(2))
        }




        $(function() {
            $("#coba").spartanMultiImagePicker({
                fieldName: 'item_images[]',
                maxCount: 5,
                rowHeight: '100px !important',
                groupClassName: 'col-lg-2 col-md-4 col-sm-4 col-6',
                maxFileSize: '',
                placeholderImage: {
                    image: "{{ asset('public/assets/admin/img/upload.png') }}",
                    width: '100px'
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
            $('#category_id').val(null).trigger('change');
            $('#sub-categories').val(null).trigger('change');
            $('#unit').val(null).trigger('change');
            $('#veg').val(0).trigger('change');
            $('#addons').val(null).trigger('change');
            $('#discount_type').val(null).trigger('change');
            $('#choice_attributes').val(null).trigger('change');
            $('#customer_choice_options').empty().trigger('change');
            $('#variant_combination').empty().trigger('change');
            $('#viewer').attr('src', "{{ asset('public/assets/admin/img/upload.png') }}");
            $("#coba").empty().spartanMultiImagePicker({
                fieldName: 'item_images[]',
                maxCount: 6,
                rowHeight: '120px',
                groupClassName: 'col-lg-2 col-md-4 col-sm-4 col-6',
                maxFileSize: '',
                placeholderImage: {
                    image: "{{ asset('public/assets/admin/img/400x400/img2.jpg') }}",
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
    </script>
@endpush
