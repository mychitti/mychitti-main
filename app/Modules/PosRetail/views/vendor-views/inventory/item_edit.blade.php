@extends('layouts.vendor.app')

@section('title', 'Edit Inventory Item')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/tags-input.min.css') }}" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/inventory_management.css') }}">
    <style>
        /* Compact, modern upload zone so it doesn't dominate the Basic Info tab */
        #basic .custom-upload-zone.upload-area {
            min-height: 150px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            cursor: pointer;
            transition: border-color .2s ease, background .2s ease;
        }

        #basic .custom-upload-zone.upload-area:hover {
            border-color: #f43f5e;
            background: #fff5f6;
        }

        #basic .custom-upload-zone .custom-upload-icon i {
            font-size: 26px;
            color: #9ca3af;
        }

        #basic .custom-upload-zone h5 {
            font-size: 15px;
            margin: 6px 0 2px;
        }

        #basic .form-group-custom {
            margin-bottom: 14px;
        }

        /* Product / Service segmented radio set */
        #basic .custom-radio-wrapper {
            display: flex;
            gap: 10px;
        }

        #basic .custom-radio-item {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }

        #basic .custom-radio-item:hover {
            border-color: #c7d2fe;
            background: #f8faff;
        }

        #basic .custom-radio-item.active {
            border-color: #4f46e5;
            background: #eef2ff;
            box-shadow: 0 0 0 1px #4f46e5 inset;
        }

        #basic .custom-radio-item label {
            cursor: pointer;
            font-weight: 600;
            color: #374151;
            margin: 0;
        }

        #basic .custom-radio-item.active label {
            color: #4f46e5;
        }

        #basic .custom-radio-item input[type="radio"] {
            accent-color: #4f46e5;
            width: 16px;
            height: 16px;
        }

        .service_elem {
            display: {{ $item->item_type == 'service' ? '' : 'none' }};
        }

        .product_elem {
            display: {{ $item->item_type == 'product' ? '' : 'none' }};
        }

        .customer_add_form {
            width: 100%;
        }

        /* uom section */
        .uom-hint { font-size:12px; color:#8d97a5; margin:0 0 10px; line-height:1.45; }
        .stock-section.uom-plain { background:transparent; border:0; border-radius:0; padding:0 !important; }
        .stock-section.uom-plain .section-header { padding:0; border:0; margin-bottom:8px; justify-content:flex-start; }
        .secondary_unit_elem {
            display: {{ $item->secondary_unit ? 'block' : 'none' }};
        }

        /* stock section */
        :root {
            --success-color: #10b981;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        .stock-section {
            background: white;
            border-radius: 12px;
            border: 1px solid lightgrey;
            transition: all 0.2s ease;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 16px;
            margin: 0;
        }

        .add-alternate-btn {
            color: #272ec7;

            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
            background-color: #d6e2ffff;
        }

        .add_btn2 {
            display: {{ $item->secondary_unit ? 'none' : '' }};
        }

        .add-alternate-btn:hover {
            color: #272ec7;
            text-decoration: none;
        }

        .add-alternate-btn.remove {
            color: #c32424ff;
            background-color: #ffd6d6ff;
        }

        .unit-converter {
            background: var(--gray-50);
            border-radius: 10px;
            padding: 6px;

            border: 1px solid var(--gray-200);
        }

        .input-group-modern {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .input-field {
            position: relative;
            flex: 1;
            min-width: 80px;
        }

        .input-field input {
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            transition: all 0.2s ease;
            background: white;
        }



        .select-field {
            position: relative;
            flex: 1.5;
            min-width: 120px;
        }

        .select-field select {
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            background: white;
            transition: all 0.2s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }


        .connector {
            background: #24bac3;
            margin-top: 18px;
            padding: 5px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            color: white;
            white-space: nowrap;
            gap: 4px;
        }

        .info-display {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 16px;
            font-size: 14px;
            font-weight: 500;
            display: none;
            align-items: center;
            gap: 8px;
            animation: slideIn 0.3s ease;
        }

        .info-display.show {
            display: flex;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--gray-600);
            margin-bottom: 4px;
        }

        .conversion-example {
            background: #e5fcff;
            font-weight: bold;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
            font-size: 13px;
            color: var(--gray-600);
            display: none;
        }

        .conversion-example.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .input-group-modern {
                flex-direction: column;
                align-items: stretch;
            }

            .input-field,
            .select-field {
                min-width: auto;
                width: 100%;
            }

            .connector {
                align-self: center;
                margin: 4px 0;
            }
        }

        .icon-info {
            color: var(--success-color);
            font-size: 16px;
        }

        .example-badge {
            background: var(--primary-light-theme);
            color: var(--primary-color);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Edit Inventory Item</h1>
            <div class="page-header-select-wrapper">
                {{-- <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#variationModal">
                    + Add Variations
                </button> --}}
                <div class="modal fade" id="variationModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Add Variation</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form enctype="multipart/form-data" class="w-100"
                                action="{{ route('vendor.inventory.item.variation-store') }}" method="post">
                                @csrf
                                <div class="modal-body">
                                    <div class="form-row ">
                                        <label for="exampleInputEmail1">Name</label>
                                        <input type="text" name="name" required placeholder="Name"
                                            class="form-control">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row g-2">
            <form class="customer_add_form " id="item_form" enctype="multipart/form-data" class="w-100"
                action="{{ route('vendor.inventory.item.update') }}" method="post">
                @csrf
                <input type="hidden" name="item_id" value="{{ $item->id }}">
                <input type="hidden" name="form_type" value="ajax">
                <div class="custom-form-wrapper">
                    <!-- Header -->

                    <!-- Bootstrap Nav Tabs (unchanged) -->
                    <div class="custom-tabs-wrapper">
                        <ul class="nav nav-tabs" id="itemTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active " id="basic-tab" data-toggle="tab" href="#basic" role="tab">
                                    <i class="fas fa-info-circle mr-2"></i>Basic Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="attributes-tab" data-toggle="tab" href="#attributes" role="tab">
                                    <i class="fas fa-plus-circle mr-2"></i>Attributes
                                </a>
                            </li>
                            <li class="nav-item product_elem">
                                <a class="nav-link" id="sales-tab" data-toggle="tab" href="#sales" role="tab">
                                    <i class="fas fa-chart-line mr-2"></i>Sales Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="variations-tab" data-toggle="tab" href="#variations" role="tab">
                                    <i class="fas fa-layer-group mr-2"></i>Variations
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content custom-content-area" id="itemTabContent">
                        <!-- Basic Info Tab -->
                        <div class="tab-pane fade show active " id="basic" role="tabpanel">
                            <div class="row">
                                <!-- LEFT: details -->
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group form-group-custom">
                                                <label class="custom-label">Type <span class="custom-required">*</span></label>
                                                <div class="custom-radio-wrapper">
                                                    <div class="custom-radio-item {{ $item->item_type == 'product' ? 'active' : '' }}"
                                                        onclick="selectRadio('product', this)">
                                                        <input type="radio" name="item_type" id="product" value="product"
                                                            {{ $item->item_type == 'product' ? 'checked' : '' }} />
                                                        <label for="product" class="mb-0 ml-2">Product</label>
                                                    </div>
                                                    <div class="custom-radio-item {{ $item->item_type == 'service' ? 'active' : '' }}"
                                                        onclick="selectRadio('service', this)">
                                                        <input type="radio" {{ $item->item_type == 'service' ? 'checked' : '' }}
                                                            name="item_type" id="service" value="service" />
                                                        <label for="service" class="mb-0 ml-2">Service</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group form-group-custom">
                                                <div style="padding: 11px;"> </div>
                                                <label for="show_on_website" class="custom-label cursor-pointer">
                                                    <div class="badge badge-soft-success align-items-center"
                                                        style="height: 39px;display: flex;">
                                                        <div class="form-check d-flex mr-1">
                                                            <input id="show_on_website" name="show_on_store_page" value="1"
                                                                {{ $item->show_on_store_page ? 'checked' : '' }} type="checkbox"
                                                                class="form-check-input">
                                                            <span style="white-space: nowrap;" class="mt-1 form-check-label"
                                                                for="">Show on Website</span>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group form-group-custom">
                                                <label for="itemName" class="custom-label">Name <span
                                                        class="custom-required">*</span></label>
                                                <input value="{{ $item->item_name }}" name="item_name" type="text"
                                                    class="form-control" id="itemName" placeholder="Enter item name" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group form-group-custom">
                                                <label class="custom-label">Category</label>
                                                <select name="category" data-placeholder="Select or Type New Category"
                                                    id="category" class="form-control js-select2-custom-tags">
                                                    <option value=""></option>
                                                    @php $categories = \App\Models\Category::where('status', '1')->get(); @endphp
                                                    @foreach ($categories as $category)
                                                        <option {{ $item->category_id == $category['id'] ? 'selected' : '' }}
                                                            value="{{ $category['name'] }}">{{ $category['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 product_elem product_inp_group">
                                            <div class="form-group form-group-custom">
                                                <label class="custom-label">Brand</label>
                                                <input type="text" value="{{ $item->brand }}" name="brand" placeholder="Brand"
                                                    class="form-control" />
                                            </div>
                                        </div>
                                        <div class="col-md-6 product_elem product_inp_group">
                                            <div class="form-group form-group-custom">
                                                <label class="custom-label">Model Number</label>
                                                <input type="text" value="{{ $item->model_number }}" name="model_number"
                                                    placeholder="Model Number" class="form-control" />
                                            </div>
                                        </div>
                                        <div class="col-md-6 product_elem">
                                            <div class="form-group form-group-custom">
                                                <label class="custom-label">SKU ID</label>
                                                <input type="text" value="{{ $item->sku_id }}" name="sku_id"
                                                    placeholder="SKU ID" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- RIGHT: images -->
                                <div class="col-md-4">
                                    <div class="form-group form-group-custom">
                                        <label class="custom-label d-flex justify-content-between">Main Image <a
                                                href="{{ asset('storage/app/public/inventory-item/') . '/' . $item['image'] }}"
                                                class="text-underline">View Current</a></label>
                                        <div class="custom-upload-zone upload-area"
                                            onclick="document.getElementById('imageUpload').click()">
                                            <div class="custom-upload-icon">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                            </div>
                                            <h5 class="">Upload Image</h5>
                                            <p class="mb-1 text-muted">Click here to upload image</p>
                                            <small class="text-muted">Supported formats: JPG, PNG, JPEG (Max 5MB)</small>
                                        </div>
                                        <input type="file" id="imageUpload" name="image" accept="image/*"
                                            style="display: none" />
                                    </div>
                                    <div class="form-group form-group-custom" id="extra_images_group">
                                        <label class="custom-label d-block">
                                            {{ translate('messages.item_images') }}
                                            <small class="">( {{ translate('messages.ratio') }} 1:1 )</small>
                                        </label>
                                        <div class="d-flex flex-wrap" id="">
                                            @if ($item->images)
                                                @foreach ($item->images as $key => $photo)
                                                    <div style="max-width: 70px !important; margin: 5px;"
                                                        class=" spartan_item_wrapper"
                                                        id="product_images_{{ $key }}">
                                                        <img class="img--square onerror-image"
                                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($photo, asset('storage/app/public/inventory-item/') . '/' . $photo, asset('public/assets/admin/img/400x400/img2.jpg'), 'inventory-item/') }}"
                                                            data-onerror-image ="{{ asset('/public/assets/admin/img/400x400/img2.jpg') }}"
                                                            alt="Item image">
                                                        <a href="{{ route('vendor.inventory.item.remove-image', [$item['id'], $photo]) }}"
                                                            class="spartan_remove_row bg-white"
                                                            style="    top: 5px;right: 5px;"><i
                                                                class="tio-add-to-trash"></i></a>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        <input type="file" accept="image/*" name="item_images[]"
                                            class="form-control mt-2  item_images  " multiple id="">
                                    </div>
                                </div>

                                <div class="col-12" id="website_product_info" style="display:none;">
                                    <div class="row">
                                        <div class="col-12 my-1">
                                            <label for="exampleInputEmail1">Specifications</label>
                                            <textarea id="maineditor">{{ $item->specifications }}</textarea>
                                        </div>
                                        <div class="col-12 my-1">
                                            <label for="exampleInputEmail1">Highlights</label>
                                            <textarea class="form-control" name="description" placeholder="Highlights">{{ $item->description }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                @include('vendor-views.inventory.partials._repeat_reminder', [
                                    'repeatDays' => $item->repeat_days ?? 0,
                                ])
                                <div class="col-12 d-flex justify-content-end mt-3">
                                    <a class="btn btn--primary next_btn" data-next="attributes">
                                        Next
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade " id="attributes" role="tabpanel">
                            <div class="row">
                                <!-- Unit Selection -->
                                {{-- <div class="product_elem col-md-3">
                                    <div class="form-group form-group-custom">
                                        <label for="unit" class="custom-label">Unit <span
                                                class="text-danger">*</span></label>
                                        <select data-placeholder="Select or Type New" class="form-control js-example-tags"
                                            id="unit" name="unit">
                                            <option value=""></option>
                                            @foreach (\App\Models\Unit::all() as $unit)
                                                <option {{ $unit->id == $item->unit ? 'selected' : '' }}
                                                    value="{{ $unit->id }}">{{ $unit->unit }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div> --}}
                                <div class="col-md-6">
                                    <div class="w-100 p-0 mt-2">
                                        <div class="stock-section uom-plain p-2">
                                            <div class="section-header">

                                                <span class="secondary_unit_elem">Multi-UOM Setup</span>

                                                <a href="#" class="add-alternate-btn add_btn2">
                                                    <i class="fas fa-plus-circle me-1"></i>
                                                    Add Alternate Unit
                                                </a>
                                                <a href="#"
                                                    class="add-alternate-btn remove-alternate-btn remove secondary_unit_elem">
                                                    <i class="fas fa-minus-circle me-1"></i>
                                                    Remove Alternate Unit
                                                </a>
                                            </div>
                                            <p class="uom-hint uom-hint-open secondary_unit_elem">Enter how much of the base unit one alternate unit is worth.</p>

                                            <div class="unit-converter">
                                                <div class="input-group-modern">
                                                    <div class="input-field secondary_unit_elem">
                                                        <label class="field-label">Quantity</label>
                                                        <input type="number" class="form-control" id="secondary_qty"
                                                            name="secondary_qty" min="0" step="any"
                                                            value="{{ $item->secondary_qty ?? 1 }}"
                                                            placeholder="Enter quantity">
                                                    </div>

                                                    <div class="select-field secondary_unit_elem">
                                                        <label class="field-label">Unit</label>
                                                        <select data-placeholder="Select or Type New"
                                                            class="form-control js-example-tags" id="secondary_unit"
                                                            name="secondary_unit">
                                                            <option value=""></option>
                                                            @foreach (\App\Models\Unit::all() as $unit)
                                                                <option
                                                                    {{ $item->secondary_unit == $unit->id ? 'selected' : '' }}
                                                                    value="{{ $unit->id }}">{{ $unit->unit }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="connector secondary_unit_elem">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </div>

                                                    <div class="input-field secondary_unit_elem">
                                                        <label class="field-label">Quantity</label>
                                                        <input type="number" class="form-control" id="primary_qty"
                                                            name="primary_qty" min="0" step="any"
                                                            value="{{ $item->primary_qty ?? 1 }}" placeholder="Quantity">
                                                    </div>

                                                    <div class="select-field">
                                                        <label class="field-label">Base Unit</label>
                                                        <select data-placeholder="Select or Type New"
                                                            class="form-control js-example-tags" id="primary_unit"
                                                            name="primary_unit">
                                                            <option value=""></option>
                                                            @foreach (\App\Models\Unit::all() as $unit)
                                                                <option {{ $item->unit == $unit->id ? 'selected' : '' }}
                                                                    value="{{ $unit->id }}">{{ $unit->unit }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="conversion-example" id="conversion_example">
                                                    <strong>Example:</strong> This means you have <span
                                                        id="example_text"></span> Stock
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="product_elem col-md-8 my-2 mx-3"
                                    style="background: #f8fdff; border-radius: 10px; border: 2px dashed #e2e2e2;">
                                    <div class="mb-3">
                                        <label class="font-weight-bold d-block mb-2">Custom Attributes</label>
                                        <div id="custom-buttons">
                                            @php
                                                $custom_attributes = json_decode($item->custom_attributes, true) ?? [];
                                            @endphp

                                            <button type="button"
                                                style="{{ array_key_exists('Serial No', $custom_attributes) ? 'display:none;' : '' }}"
                                                class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                                data-label="Serial No">+ Serial No</button>

                                            <button type="button"
                                                style="{{ array_key_exists('Expiry Date', $custom_attributes) ? 'display:none;' : '' }}"
                                                class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                                data-label="Expiry Date">+ Expiry Date</button>

                                            <button type="button"
                                                style="{{ array_key_exists('Part No.', $custom_attributes) ? 'display:none;' : '' }}"
                                                class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                                data-label="Part No.">+ Part No.</button>

                                            <button type="button"
                                                class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                                data-label="Other">+ Other</button>
                                        </div>
                                    </div>

                                    <!-- Where dynamic fields appear -->
                                    <div id="custom-fields">
                                        @foreach ($custom_attributes as $label => $value)
                                            @php
                                                // Make a safe input name (no spaces)
                                                $inputName = Str::slug($label, '_');
                                            @endphp

                                            <div class="form-group custom-field" data-label="{{ $label }}">
                                                <label for="{{ $inputName }}">{{ $label }}</label>
                                                <div class="d-flex fld_grp">
                                                    <input type="hidden" name="header_label[]"
                                                        value="{{ $label }}" id="{{ $label }}">
                                                    <input type="text" class="form-control mr-2" name="header_field[]"
                                                        id="{{ $inputName }}" value="{{ $value }}">
                                                    <a type="button" class="text-danger remove-field">
                                                        <i class="tio-delete-outlined"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                @include('vendor-views.inventory.partials._description_attributes', [
                                    'description_attributes' => (array) ($item->description_attributes ?: []),
                                ])

                                <div class="col-12 service_elem">
                                    <div class=" col-12 d-flex justify-content-end mt-3">
                                        <button class="btn btn--primary ">
                                            Save
                                        </button>
                                    </div>
                                </div>
                                <div class="product_elem col-12">
                                    <div class=" col-12 d-flex justify-content-end mt-3 gap-2">
                                        <a class="btn btn--primary next_btn" id="attr_next_btn" data-next="sales">
                                            Next
                                        </a>
                                        <button type="submit" class="btn btn--primary" id="attr_save_btn" style="display:none;">
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Info Tab -->

                        </div>

                        <!-- Variations Tab -->
                        <div class="tab-pane fade" id="variations" role="tabpanel">
                            <div class="row">
                                <div class="col-12 p-1" id="variations_wrap" style="display:none;">
                                    <div class="alert alert-info py-2 px-3 mb-2" style="font-size:12px;">
                                        Set the main product price &amp; base unit (e.g. 1 kg = ₹1000) under <b>Sales Info</b> /
                                        <b>Multi-UOM</b>, then add a weight variation (e.g. 100g, 200g) — its MRP &amp; selling
                                        price fill in automatically. You can still edit any value manually.
                                    </div>
                                    <div class="col-md-12 p-0" id="attribute_section">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="form-group mb-0">
                                                    <label class="input-label" for="exampleFormControlSelect1">Add
                                                        Variation<span class="input-label-secondary"></span></label>
                                                    <select name="attribute_id[]" id="choice_attributes"
                                                        class="form-control js-select2-custom" multiple="multiple">
                                                        @foreach (\App\Models\Attribute::orderBy('name')->get() as $attribute)
                                                            <option value="{{ $attribute['id'] }}"
                                                                {{ $item['attributes'] && in_array($attribute->id, json_decode($item['attributes'], true)) ? 'selected' : '' }}>
                                                                {{ $attribute['name'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="customer_choice_options" id="customer_choice_options">
                                                    @include('vendor-views.inventory._choices', [
                                                        'choice_no' => json_decode($item['attributes']),
                                                        'choice_options' => json_decode($item['choice_options'], true),
                                                    ])
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="variant_combination" id="variant_combination">
                                                    @include('vendor-views.inventory._edit-combinations', [
                                                        'combinations' => json_decode($item['variations'], true),
                                                        'stock' => 1,
                                                        'primary_unit' => $item['unit']
                                                            ? _unitNaneById($item['unit'])
                                                            : '',
                                                        'secondary_unit' => $item['secondary_unit']
                                                            ? _unitNaneById($item['secondary_unit'])
                                                            : '',
                                                    ])
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div id="add_new_option">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-end mt-3 gap-2">
                                    <a class="btn btn-outline-primary next_btn" data-next="sales">Back</a>
                                    <button type="submit" class="btn btn--primary">Save</button>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="sales" role="tabpanel">
                            <div class="row">

                                <div class="col-md-3">
                                    <div class="form-group form-group-custom">
                                        <label for="hsn" class="custom-label">HSN</label>
                                        <input type="number" value="{{ $item->hsn }}" name="hsn"
                                            class="form-control" id="hsn" placeholder="HSN" />
                                    </div>
                                </div>
                                @php
                                    $sp_basis = $item->selling_price_basis ?? 'primary';
                                    $sp_factor = ($item->primary_qty > 0 && $item->secondary_qty > 0) ? ($item->primary_qty / $item->secondary_qty) : 1;
                                    $sp_display = $sp_basis === 'secondary' ? round($item->selling_price * $sp_factor, 4) : $item->selling_price;
                                    $mrp_display = $sp_basis === 'secondary' ? round(($item->mrp ?? 0) * $sp_factor, 4) : $item->mrp;
                                    $landing_display = $sp_basis === 'secondary' ? round(($item->landing_price ?? 0) * $sp_factor, 4) : $item->landing_price;
                                @endphp
                                @include('vendor-views.inventory.partials._selling_price_basis')
                                <div class="col-md-3">
                                    <div class="form-group form-group-custom">
                                        <label for="openingStock" class="custom-label">Selling Price<span
                                                class="text-danger">*</span></label>
                                        <input type="number" value="{{ $sp_display }}"
                                            placeholder="Selling Price" name="main_selling_price" class="form-control"
                                            step="0.001" />
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group form-group-custom">
                                        <label for="openingStock" class="custom-label">MRP<span
                                                class="text-danger">*</span></label>
                                        <input type="number" value="{{ $mrp_display }}" placeholder="MRP"
                                            name="main_mrp" class="form-control" step="0.001" />
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group form-group-custom">
                                        <label for="openingStock" class="custom-label">Landing Price</label>
                                        <input type="number" value="{{ $landing_display }}"
                                            placeholder="Landing Price" name="main_landing_price" class="form-control"
                                            step="0.001" />
                                    </div>
                                </div>
                                <input type="hidden" name="main_opening_stock" value="{{ $item->stock }}">

                                <div class="col-md-6">
                                    <label for="gstType" class="custom-label">GST <span
                                            class="text-danger">*</span></label>

                                    <div class="input-group flex-nowrap">
                                        <div class="input-group-prepend">
                                            <input type="number" value="{{ $item->gst_rate }}"
                                                onkeyup="checkPricing(this)" id="gst_rate" name="gst_rate"
                                                placeholder="%" class="form-control">
                                        </div>
                                        {{-- <select class="form-control js-select2-custom" name="gst_type"
                                            data-placeholder="Select GST Type" id="gstType">
                                            <option {{ $item->gst_type == 'cgst_sgst' ? 'selected' : '' }}
                                                value="cgst_sgst">GST (CGST + SGST) </option>
                                            <option {{ $item->gst_type == 'igst' ? 'selected' : '' }} value="igst">IGST
                                            </option>
                                            <option {{ $item->gst_type == 'no_gst' ? 'selected' : '' }} value="no_gst">No
                                                GST</option>
                                        </select> --}}
                                        <select class="form-control js-select2-custom" name="gst_status"
                                            data-placeholder="Select GST Status" id="gstStatus">
                                            <option {{ ($item->gst_status ?? 'excluding') == 'excluding' ? 'selected' : '' }}
                                                value="excluding">Excluding</option>
                                            <option {{ ($item->gst_status ?? 'excluding') == 'including' ? 'selected' : '' }}
                                                value="including">Including</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">Stock Type</label>
                                        @php $currentStockType = _stockTypeOf($item); @endphp
                                        <select name="stock_type" class="form-control js-select2-custom" id="stock_type">
                                            @foreach (_stockTypes() as $key => $label)
                                                <option value="{{ $key }}"
                                                    {{ $currentStockType === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Decides where a sale takes stock from — the product's
                                            own figure, or the variation that was sold.</small>
                                    </div>
                                </div>
                                <div class="col-12 mt-2" id="sell_loose_wrap">
                                    <div class="badge badge-soft-info p-2 d-block text-left">
                                        <label class="custom-label cursor-pointer mb-0 d-flex align-items-center">
                                            <input type="checkbox" id="sell_loose_cb" name="sell_loose" value="1"
                                                {{ !empty($item->sell_loose) ? 'checked' : '' }}
                                                class="form-check-input position-static ml-0 mr-2">
                                            <span id="sell_loose_note">Sell loose — weigh at the time of sale (POS asks for the weight; billed weight × price)</span>
                                        </label>
                                    </div>
                                </div>
                                @include('vendor-views.inventory.partials._stock_type_sync')


                            </div>

                            {{-- Leaves the controls alone for an item that already has variations —
                                 see _stock_type_sync. --}}
                            <div class="col-12 p-1" id="add_variations_wrap"
                                 data-has-variations="{{ !empty(json_decode($item['variations'] ?? '[]', true)) ? '1' : '0' }}">
                                <label for="add_variations_cb" class="custom-label cursor-pointer mb-0 d-inline-block">
                                    <div class="badge badge-soft-primary align-items-center"
                                        style="height: 39px;display: flex;">
                                        <div class="form-check d-flex mr-1">
                                            <input id="add_variations_cb" type="checkbox" class="form-check-input">
                                            <span style="white-space: nowrap;" class="mt-1 form-check-label">Add
                                                Variations</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-12 d-flex justify-content-end mt-3 gap-2">
                                <a class="btn btn-outline-primary next_btn" data-next="attributes">Back</a>
                                <a class="btn btn--primary next_btn" id="sales_next_btn" data-next="variations" style="display:none;">Next</a>
                                <button type="submit" class="btn btn--primary" id="sales_save_btn">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>

    </div>

@endsection

@push('script_2')
    @include('vendor-views/js/inventory_management')
    @include('vendor-views/js/uom_js')

    {{-- <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script> --}}
    <script>
        // Conditional Basic-Info layout (mirrors the Add form):
        //  • "Show on Website" ON  -> Specs, Highlights, extra Images, per-variation extras shown.
        //  • OFF -> only the main image is kept (Specs/Highlights/per-variation extras hidden).
        //  • The "Add Variations" checkbox is always visible for products; ticking it reveals the
        //    variation builder, independent of the website toggle.
        //  • Once variations exist, the Sales Info tab is hidden and Save is surfaced elsewhere.
        // Hidden required fields are disabled so they can't block submit; on Save we surface
        // any invalid field's tab.
        (function () {
            var form = document.getElementById('item_form');
            var box = document.getElementById('website_product_info');
            var varsWrap = document.getElementById('variations_wrap');
            var addVarWrap = document.getElementById('add_variations_wrap');
            var addVarCb = document.getElementById('add_variations_cb');
            var extraImages = document.getElementById('extra_images_group');
            var combos = document.getElementById('variant_combination');
            var salesTab = document.getElementById('sales-tab');
            var salesLi = salesTab ? salesTab.closest('li') : null;
            var salesPane = document.getElementById('sales');
            var variationsTab = document.getElementById('variations-tab');
            var variationsLi = variationsTab ? variationsTab.closest('li') : null;

            function isProduct() {
                var t = document.querySelector('input[name="item_type"]:checked');
                return t ? t.value === 'product' : true;
            }
            function websiteOn() {
                var web = document.getElementById('show_on_website');
                return !!(web && web.checked) && isProduct();
            }
            function variationsAdded() { return !!(combos && combos.querySelector('table')); }
            function showEl(el, on) { if (el) el.style.display = on ? '' : 'none'; }
            function setDisabled(el, disabled) {
                if (!el) return;
                el.querySelectorAll('input, select, textarea').forEach(function (c) { c.disabled = disabled; });
            }

            function sync() {
                var on = websiteOn();
                var prod = isProduct();
                var varsVisible = prod && (addVarCb && addVarCb.checked);
                var hasVars = varsVisible && variationsAdded();

                showEl(box, on); setDisabled(box, !on);
                showEl(extraImages, on); setDisabled(extraImages, !on);
                showEl(addVarWrap, prod);
                // Variations tab appears only when "Add Variations" is ticked.
                showEl(variationsLi, varsVisible);

                showEl(varsWrap, varsVisible); setDisabled(varsWrap, !varsVisible);
                document.querySelectorAll('.variant-extra-row').forEach(function (tr) {
                    showEl(tr, on);
                    tr.querySelectorAll('input, select, textarea').forEach(function (c) { c.disabled = !on; });
                });

                // Sales Info stays visible & editable. Footer shows Next → Variations when
                // variations are enabled, otherwise the Save button lives here.
                showEl(salesLi, prod);
                setDisabled(salesPane, false);
                showEl(document.getElementById('sales_next_btn'), varsVisible);
                showEl(document.getElementById('sales_save_btn'), !varsVisible);
                showEl(document.getElementById('attr_next_btn'), true);
                showEl(document.getElementById('attr_save_btn'), false);

                if (!varsVisible) {
                    var vt = document.getElementById('variations-tab');
                    if (vt && vt.classList.contains('active')) {
                        var s = document.getElementById('sales-tab'); if (s) s.click();
                    }
                }
            }

            document.addEventListener('change', function (e) {
                if (e.target && (e.target.id === 'show_on_website' || e.target.id === 'add_variations_cb' || e.target.name === 'item_type')) sync();
            });
            document.addEventListener('click', function (e) {
                if (e.target.closest && e.target.closest('.custom-radio-item')) setTimeout(sync, 0);
            });
            // Auto-fill each variation's SKU from the main SKU + a 2-char suffix (AB, CD, EF…).
            // Only fills blank / auto-generated inputs, so saved or manual SKUs are kept.
            function fillVariantSkus() {
                var mainEl = document.querySelector('input[name="sku_id"]');
                var main = mainEl ? mainEl.value.trim() : '';
                if (!main || !combos) return;
                var letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                combos.querySelectorAll('input[name^="sku_"]').forEach(function (inp, i) {
                    if (inp.dataset.autoSku !== '1' && inp.value) return;
                    var first = letters[(2 * i) % 26];
                    var second = letters[(2 * i + 1) % 26];
                    var cycle = Math.floor((2 * i) / 26);
                    inp.value = main + first + second + (cycle ? cycle : '');
                    inp.dataset.autoSku = '1';
                });
            }
            if (combos) {
                combos.addEventListener('input', function (e) {
                    if (e.target && e.target.name && e.target.name.indexOf('sku_') === 0) e.target.dataset.autoSku = '0';
                });
            }
            var mainSkuEl = document.querySelector('input[name="sku_id"]');
            if (mainSkuEl) mainSkuEl.addEventListener('input', fillVariantSkus);

            if (combos && window.MutationObserver) {
                new MutationObserver(function () { sync(); fillVariantSkus(); }).observe(combos, { childList: true });
            }
            // Re-sync on Bootstrap tab change. jQuery can load after this inline script, so
            // guard the binding (an unguarded $(...) here throws and aborts the whole setup).
            function bindTabSync() {
                if (window.jQuery) {
                    window.jQuery(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function () { sync(); });
                } else {
                    window.addEventListener('load', function () {
                        if (window.jQuery) window.jQuery(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function () { sync(); });
                    });
                }
            }
            bindTabSync();
            if (form) {
                Array.prototype.forEach.call(form.querySelectorAll('button'), function (btn) {
                    if (btn.type !== 'submit') return;
                    btn.addEventListener('click', function (e) {
                        sync();
                        // "Add Variations" off -> never submit variation data, even if it was
                        // filled in earlier. Disabling the whole block excludes it from the POST.
                        if (varsWrap && !(addVarCb && addVarCb.checked)) setDisabled(varsWrap, true);
                        var invalid = form.querySelector(':invalid');
                        if (invalid) {
                            var pane = invalid.closest('.tab-pane');
                            if (pane && !pane.classList.contains('active')) {
                                e.preventDefault();
                                var link = document.querySelector('a[href="#' + pane.id + '"]');
                                if (link) link.click();
                                setTimeout(function () { invalid.reportValidity ? invalid.reportValidity() : invalid.focus(); }, 80);
                            }
                        }
                    });
                });
            }
            // An item being edited may already have variations — reveal them even if website is off.
            if (addVarCb && variationsAdded()) addVarCb.checked = true;
            sync();
        })();
    </script>
@endpush
