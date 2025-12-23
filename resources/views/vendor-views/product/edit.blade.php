@extends('layouts.vendor.app')

@section('title', request()->product_gellary == 1 ? translate('Add item') : translate('Update_item'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/tags-input.min.css') }}" rel="stylesheet">
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

        let variatonEditors = {};

        document.querySelectorAll('.editor').forEach((editorElement, index) => {
            ClassicEditor
                .create(editorElement, {
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
                    variatonEditors[editorElement.id] = editor;
                })
                .catch(error => {
                    console.error('There was a problem initializing the editor.', error);
                });
        });

        $('#item_form').on('submit', function(e) {
            $('#submitButton').attr('disabled', true);
            e.preventDefault();
            let formData = new FormData(this);
            if (editorInstance) {
                console.log(editorInstance.getData())
                let encodedData = btoa(unescape(encodeURIComponent(editorInstance.getData())));
                {{-- let encodedData = editorInstance.getData(); --}}
                formData.append('specifications', encodedData);
            }

            document.querySelectorAll('.editor').forEach(editorElement => {
                if (variatonEditors[editorElement.id]) {
                    let encodedData = btoa(unescape(encodeURIComponent(variatonEditors[editorElement.id]
                        .getData())));
                    formData.append(editorElement.id, encodedData);
                }
            });

            let editors2 = window.ckeditorInstances || {};
            document.querySelectorAll('.editor2').forEach(editorElement => {
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
                url: $('.route_url').val(),
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
@endpush

@section('content')
    @php($module_type = \App\CentralLogics\Helpers::get_store_data()->module->module_type)
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/edit.png') }}" class="w--22" alt="">
                </span>
                <span>
                    {{ request()->product_gellary == 1 ? translate('Add_item') : translate('item_update') }}
                </span>
            </h1>
        </div>

        @if (isset($temp_product) && $temp_product == 1 && $product->note)
            <div class="card-header border-0 align-items-start flex-wrap">
                <div class="order-invoice-left d-flex d-sm-block justify-content-between">
                    <div class="d-flex align-items-center __gap-5px">
                        <h1 class="page-header-title text-danger ">
                            {{ translate('messages.Rejection_Note') }} :
                        </h1>
                        <h3 class="">
                            {{ $product->note }}
                        </h3>
                    </div>
                </div>
            </div>
        @endif
        <!-- End Page Header -->
        <form action="javascript:" method="post" id="item_form" enctype="multipart/form-data">
            @csrf


            @if (request()->product_gellary == 1)
                @php($route = route('vendor.item.store', ['product_gellary' => request()->product_gellary]))
                @php($product->price = 0)
            @else
                @php($route = route('vendor.item.update', [isset($temp_product) && $temp_product == 1 ? $product['item_id'] : $product['id']]))
            @endif

            <input type="hidden" class="route_url"
                value="{{ $route ?? route('vendor.item.update', [isset($temp_product) && $temp_product == 1 ? $product['item_id'] : $product['id']]) }}">
            <input type="hidden" value="{{ $temp_product ?? 0 }}" name="temp_product">
            <input type="hidden" value="{{ $product['id'] ?? null }}" name="item_id">

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
                                        for="exampleFormControlInput1">{{ translate('messages.name') }}
                                        ({{ translate('messages.default') }})</label>
                                    <input type="text" name="name[]" class="form-control"
                                        placeholder="{{ translate('messages.new_food') }}" value="{{ $product['name'] }}"
                                        required>
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                                <div class="form-group pt-2 mb-0">
                                    <label class="input-label"
                                        for="exampleFormControlInput1">{{ translate('messages.highlights') }}</label>
                                    <textarea type="text" name="description[]" class="form-control ckeditor min--height-200">{!! $product['description'] !!}</textarea>
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
                                <input type="hidden" id="removedImageKeysInput" name="removedImageKeys" value="">

                                <label class="input-label"
                                    for="exampleFormControlInput1">{{ translate('messages.item_images') }}</label>
                                <div class="row" id="coba">
                                    @foreach ($product->images as $key => $photo)
                                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-4 col-6 spartan_item_wrapper"
                                            id="product_images_{{ $key }}">
                                            <img class="img--square onerror-image"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($photo, asset('storage/app/public/product/') . '/' . $photo, asset('public/assets/admin/img/400x400/img2.jpg'), 'product/') }}"
                                                data-onerror-image ="{{ asset('/public/assets/admin/img/400x400/img2.jpg') }}"
                                                alt="Product image">


                                            @if (request()->product_gellary == 1)
                                                <a href="#" data-key={{ $key }}
                                                    data-photo="{{ $photo }}" class="spartan_remove_row"><i
                                                        class="tio-add-to-trash"></i></a>
                                            @else
                                                <a href="{{ route('vendor.item.remove-image', ['id' => $product['id'], 'name' => $photo, 'temp_product' => $temp_product]) }}"
                                                    class="spartan_remove_row"><i class="tio-add-to-trash"></i></a>
                                            @endif
                                        </div>
                                    @endforeach

                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="text-dark">{{ translate('messages.item_thumbnail') }} <small
                                        class="text-danger">* ( {{ translate('messages.ratio') }} 1:1 )</small></label>
                                <div class="text-center d-block" id="image-viewer-section" class="pt-2">
                                    <img class="img--100 onerror-image" id="viewer"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($product['image'], asset('storage/app/public/product/') . '/' . $product['image'], asset('public/assets/admin/img/400x400/img2.jpg'), 'product/') }}"
                                        data-onerror-image ="{{ asset('/public/assets/admin/img/400x400/img2.jpg') }}"
                                        alt="product image" />
                                </div>
                                <div class="custom-file mt-3">
                                    <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
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
                                <textarea id="maineditor">{{ $product->specifications }}</textarea>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <span class="card-header-icon">
                                    <i class="tio-dashboard-outlined"></i>
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
                                        <select name="category_id" id="category-id"
                                            class="form-control js-select2-custom get-request"
                                            data-url="{{ url('/') }}/item/get-categories?parent_id="
                                            data-id="sub-categories">
                                            @foreach ($categories as $category)
                                                <option value="{{ $category['id'] }}"
                                                    {{ $category->id == $product_category[0]->id ? 'selected' : '' }}>
                                                    {{ $category['name'] }}</option>
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
                                            data-id="{{ count($product_category) >= 2 ? $product_category[1]->id : '' }}"
                                            class="form-control js-select2-custom get-request"
                                            data-url="{{ url('/') }}/item/get-categories?parent_id="
                                            data-id="sub-sub-categories">

                                        </select>
                                    </div>
                                </div>
                               
                                @if ($module_data['unit'])
                                    <div class="col-sm-6 col-lg-4" id="unit_input">
                                        <div class="form-group mb-0">
                                            <label class="input-label text-capitalize"
                                                for="unit">{{ translate('messages.unit') }}</label>
                                            <select name="unit" class="form-control js-select2-custom">
                                                @foreach (\App\Models\Unit::all() as $unit)
                                                    <option value="{{ $unit->id }}"
                                                        {{ $unit->id == $product->unit_id ? 'selected' : '' }}>
                                                        {{ $unit->unit }}</option>
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
                                                min="0" value="{{ $product->stock }}" id="quantity">
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
                                            value="{{ $product->maximum_cart_quantity }}" id="cart_quantity">
                                    </div>
                                </div>
                                 <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.HSN Code') }}</label>
                                        <input type="number" min="0" max="999999999999" 
                                             name="hsn_code" id="" class="form-control"
                                          value="{{ $product->hsn_code }}"  placeholder="{{ translate('messages.Ex:') }} 6109" required>
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
                                    <i class="tio-dashboard-outlined"></i>
                                </span>
                                <span> {{ translate('price_details') }} </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                
                                <input type="hidden" id="fee_percent" value = "{{$fee_category ? $fee_category->total_fee : 0}}">
                               
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.mrp') }}</label>
                                        <input {{ $product->fee_category ?  'oninput=calcValues()': ''}} type="number" value="{{ $product->mrp_price }}" min="0"
                                            max="999999999999" name="mrp_price" class="form-control" step="0.001"
                                            placeholder="{{ translate('messages.Ex:') }} 100" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.asking_price') }}</label>

                                             <div class="input-group mb-3">
                                                <input {{ $product->fee_category ?  'oninput=calcValues()': ''}} type="number"
                                                    value="{{ $product->asking_price - $product->remaining_amount }}" min="0"
                                                    max="999999999999.99" name="asking_price" class="form-control"
                                                    step="0.000001" placeholder="{{ translate('messages.Ex:') }} 100"
                                                    required>
                                                {{-- <span class="input-group-text" id="remaining_price_show">+{{ number_format($product->remaining_amount, 2) }}</span> --}}
                                            </div>

                                            {{-- <input type="hidden" min="0" max="999999999999.99"
                                                name="remaining_price" value='{{ $product->remaining_amount }}' class="form-control"
                                                step="0.000001"> --}}

                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.discount') }}</label>
                                        <input id="discount_value_inp" type="number" min="0" value="{{ $product['discount'] }}"
                                            max="100000" name="discount"  class="form-control" step="0.000001"
                                            placeholder="will show from backend" readonly >
                                    </div>
                                </div>
                               
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.mychitti_fee') }}</label>
                                        <input type="number"  step="0.001" value="{{ $product['mychitty_fee'] }}"
                                           class="form-control"  id="mychitty_fee"
                                            placeholder="will show from backend" readonly >
                                    </div>
                                </div>
                               
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.selling_price') }}</label>
                                        <input id="selling_price_inp" type="number" min="0" value="{{ $product['price'] }}"
                                            max="100000" name="price" class="form-control"
                                            placeholder="will show from backend" readonly >
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.GST') }}(%)</label>
                                        <input id="" type="number" min="0" value="{{ $product['tax'] }}"
                                            max="100000" name="gst_percent" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 12"  >
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-4 d-none">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.discount_type') }}</label>
                                        <select name="discount_type" class="form-control js-select2-custom">
                                            <option value="percent"
                                                {{ $product['discount_type'] == 'percent' ? 'selected' : '' }}>
                                                {{ translate('messages.percent') }}
                                            </option>
                                            <option value="amount"
                                                {{ $product['discount_type'] == 'amount' ? 'selected' : '' }}>
                                                {{ translate('messages.amount') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

               
                <div class="col-md-12" id="attribute_section">
                    <div class="card">
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
                                                <option value="{{ $attribute['id'] }}"
                                                    {{ in_array($attribute->id, json_decode($product['attributes'], true)) ? 'selected' : '' }}>
                                                    {{ $attribute['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="customer_choice_options" id="customer_choice_options">
                                        @include('vendor-views.product.partials._choices', [
                                            'choice_no' => json_decode($product['attributes']),
                                            'choice_options' => json_decode($product['choice_options'], true),
                                        ])
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="variant_combination" id="variant_combination">
                                        @include('vendor-views.product.partials._edit-combinations', [
                                            'combinations' => json_decode($product['variations'], true),
                                            'stock' => $module_data['stock'],
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
         
                  <div class="col-md-12">
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
                                    <input type="file" name="keyword_excel" class="form-control mb-2" id="">
                                   
                                </div>
                                <div class="col-6 d-flex align-items-end mb-3">
                                    <a href="{{ asset('storage/app/public/export-keywords.xlsx') }}"
                                        class="btn btn-outline-primary">Download Example Excel</a>
                                    <button type="button" class="mx-1 btn btn-outline-primary" data-toggle="modal"
                                        data-target="#exampleModalk">
                                        View Current Keywords
                                    </button>

                                    <!-- Modal -->
                                    <div class="modal fade" id="exampleModalk" tabindex="-1"
                                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">Keywords</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">

                                                    @foreach ($keywords as $key => $k)
                                                        {{ $k->keyword }},
                                                    @endforeach
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
             
                <div class="col-12">
                    <div class="btn--container justify-content-end">
                        <button type="reset" id="reset_btn"
                            class="btn btn--reset">{{ translate('messages.reset') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('messages.update') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection

@push('script')
@endpush

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/tags-input.min.js"></script>
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
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



        $(document).ready(function() {
            setTimeout(function() {
                let category = $("#category-id").val();
                let sub_category = '{{ count($product_category) >= 2 ? $product_category[1]->id : '' }}';
                let sub_sub_category = '{{ count($product_category) >= 3 ? $product_category[2]->id : '' }}';
                getRequest('{{ url('/') }}/item/get-categories?parent_id=' + category +
                    '&&sub_category=' + sub_category, 'sub-categories');
                getRequest('{{ url('/') }}/item/get-categories?parent_id=' +
                    sub_category + '&&sub_category=' + sub_sub_category, 'sub-sub-categories');
            }, 1000)
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

            $.ajax({
                type: "POST",
                url: '{{ route('vendor.item.update-variant-combination') }}',
                data: $('#item_form').serialize() + '&stock={{ $module_data['stock'] }}',
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#loading').hide();
                      $('#newVariationsRows').html(data.view);
                    {{-- $('#variant_combination').html(data.view); --}}
                    if (data.length > 1) {
                        $('#quantity').hide();
                    } else {
                        $('#quantity').show();
                    }
                }
            });
        }

        {{-- $('#item_form').on('submit', function() {
            let formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: $('.route_url').val(),
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
        }); --}}

        $(function() {
            $("#coba").spartanMultiImagePicker({
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
        });

         function calcValues() {
            let feePercent = $('#fee_percent').val();

            let asking_price = parseFloat($('input[name=asking_price]').val()) || 0;
            let mrp_price = parseFloat($('input[name=mrp_price]').val()) || 0;

            let selling_price_inp = $('#selling_price_inp');
            let discount_value_inp = $('#discount_value_inp');

            let mychitti_fee = asking_price * feePercent / 100
            console.log(mychitti_fee)

            let selling_price = asking_price + mychitti_fee;

            let discountPercent = ((mrp_price - selling_price) / mrp_price) * 100;
            let flooredDiscount = Math.floor(discountPercent);

            selling_price = mrp_price - (mrp_price * flooredDiscount / 100);
            selling_price_inp.val(selling_price.toFixed(2));

            discount_value_inp.val(flooredDiscount);

            $('#mychitty_fee').val(mychitti_fee)
        }
    </script>
@endpush
