<style>
    .service_elem {
        display: none;
    }

    .customer_add_form {
        width: 100%;
    }

    .secondary_unit_elem {
        display: none;
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
<form class="customer_add_form" id="item_form" enctype="multipart/form-data" class="w-100"
    action="{{ route('vendor.inventory.item.save') }}" method="post">
    @csrf
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
                <li class="nav-item">
                    <a class="nav-link product_elem" id="sales-tab" data-toggle="tab" href="#sales" role="tab">
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
            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                <div class="row">
                    <!-- Type Selection -->
                    <div class="col-md-4">
                        <div class="form-group form-group-custom">
                            <label class="custom-label">Item Type <span class="custom-required">*</span></label>
                            <div class="custom-radio-wrapper">
                                <div class="custom-radio-item active" onclick="selectRadio('product', this)">
                                    <input type="radio" name="item_type" id="product" value="product" checked />
                                    <label for="product" class="mb-0 ml-2">Product</label>
                                </div>
                                <div class="custom-radio-item" onclick="selectRadio('service', this)">
                                    <input type="radio" name="item_type" id="service" value="service" />
                                    <label for="service" class="mb-0 ml-2">Service</label>
                                </div>
                            </div>
                        </div>
                    </div>


                    
                    <div class="col-md-5">
                        <div class="form-group form-group-custom">
                            <label for="itemName" class="custom-label">Name <span
                                    class="custom-required">*</span></label>
                            <input name="item_name" type="text" class="form-control" id="itemName"
                                placeholder="Enter item name" />
                        </div>
                    </div>
                    <div class="col-md-3 ">
                        <div class="form-group  form-group-custom">
                            <div style="padding: 11px;" class="hide_on_phone"> </div>
                            <label for="show_on_website" class="custom-label cursor-pointer">
                                <div class="badge badge-soft-success align-items-center"
                                    style="height: 39px;display: flex;">
                                    <div class="form-check d-flex mr-1">
                                        <input id="show_on_website" name="show_on_store_page" value="1"
                                            type="checkbox" class="form-check-input">
                                        <span style=" white-space: nowrap;" class="mt-1 form-check-label"
                                            for="">Show on Website</span>
                                    </div>
                                </div>
                            </label>
                        </div>

                    </div>
                    <div class="col-md-4 ">
                        <div class="form-group form-group-custom">
                            <label class="custom-label">Main Image</label>
                            <div class="custom-upload-zone upload-area"
                                onclick="document.getElementById('imageUpload').click()">
                                <div class="custom-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <h5 class="">Upload Image</h5>
                                <p class="mb-1 text-muted">
                                    Click here to upload image
                                </p>
                                <small class="text-muted">Supported formats: JPG, PNG, JPEG (Max
                                    5MB)</small>
                            </div>
                            <input type="file" id="imageUpload" name="image" accept="image/*"
                                style="display: none" />
                        </div>
                    </div>
                    <div class="col-md-4" id="extra_images_group">
                        <div class="form-group form-group-custom">
                            <label for="itemName" class="custom-label">Images</label>
                            <input type="file" accept="image/*" multiple name="item_images[]"
                                class="form-control item_images" />
                        </div>

                    </div>
                    <div class="col-12" id="website_product_info" style="display:none;">
                        <div class="row">
                            <div class="col-12 my-1">
                                <label for="exampleInputEmail1">Specifications</label>
                                <textarea id="maineditor"></textarea>
                            </div>
                            <div class="col-12 my-1">
                                <label for="exampleInputEmail1">Highlights</label>
                                <textarea class="form-control" name="description" placeholder="Highlights"></textarea>
                            </div>
                        </div>
                    </div>
                    @include('vendor-views.inventory.partials._repeat_reminder')
                    <div class="col-12 d-flex justify-content-end mt-3">
                        <a class="btn btn-primary next_btn" data-next="attributes">
                            Next
                        </a>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade " id="attributes" role="tabpanel">
                <div class="row g-0">
                    <!-- Type Selection -->
                    <div class="col-lg-3 col-sm-6 p-1">
                        <label for="exampleInputEmail1 ">Category </label>
                        <select name="category" data-placeholder="Select or Type New Category" id="category"
                            class="form-control js-select2-custom-tags">
                            <option value=""></option>
                            @php
                            $categories = \App\Models\Category::where('status', '1')->get(); @endphp @foreach ($categories as $category)
                                <option value="{{ $category['name'] }}">
                                    {{ $category['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="product_elem col-lg-3 col-sm-6 p-1 product_inp_group">
                        <label for="exampleInputEmail1">Brand</label>
                        <input type="text" name="brand" placeholder="Brand" class="form-control" />
                    </div>

                    <div class="product_elem col-lg-3 col-sm-6 p-1 product_inp_group">
                        <label for="exampleInputEmail1">Model Number</label>
                        <input type="text" name="model_number" placeholder="Model Number" class="form-control" />
                    </div>
                    <div class="product_elem col-lg-3 col-sm-6 p-1">
                        <label for="exampleInputEmail1">SKU ID</label>
                        <input type="text" name="sku_id" placeholder="SKU ID" class="form-control" />
                    </div>
                    <div class="product_elem col-lg-3 col-sm-6 p-1">
                        <label for="exampleInputEmail1">Barcode</label>
                        <input type="file" name="barcode" class="form-control" />
                    </div>
                    <div class="col-12">
                        <div class="col-md-8 p-0 mt-2">
                            <div class="stock-section p-2 ">
                                <div class="section-header">

                                    <span>Multi-UOM Setup</span>

                                    <a href="#" class="add-alternate-btn">
                                        <i class="fas fa-plus-circle me-1"></i>
                                        Add Alternate Unit
                                    </a>
                                    <a href="#"
                                        class="add-alternate-btn remove-alternate-btn remove secondary_unit_elem">
                                        <i class="fas fa-minus-circle me-1"></i>
                                        Remove Alternate Unit
                                    </a>
                                </div>

                                <div class="unit-converter">
                                    <div class="input-group-modern">
                                        <div class="input-field secondary_unit_elem">
                                            <label class="field-label">Quantity</label>
                                            <input type="number" class="form-control" id="secondary_qty"
                                                name="secondary_qty" min="1" value="1"
                                                placeholder="Enter quantity">
                                        </div>

                                        <div class="select-field secondary_unit_elem">
                                            <label class="field-label">Unit</label>
                                            <select data-placeholder="Select or Type New"
                                                class="form-control js-example-tags" id="secondary_unit"
                                                name="secondary_unit">
                                                <option value=""></option>
                                                @foreach (\App\Models\Unit::all() as $unit)
                                                    <option value="{{ $unit->id }}">{{ $unit->unit }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="connector secondary_unit_elem">
                                            <i class="fas fa-exchange-alt"></i>
                                        </div>

                                        <div class="input-field">
                                            <label class="field-label">Quantity</label>
                                            <input type="number" class="form-control" id="primary_qty"
                                                name="primary_qty" min="1" value="1"
                                                placeholder="Quantity">
                                        </div>

                                        <div class="select-field">
                                            <label class="field-label">Base Unit</label>
                                            <select data-placeholder="Select or Type New"
                                                class="form-control js-example-tags" id="primary_unit"
                                                name="primary_unit">
                                                <option value=""></option>
                                                @foreach (\App\Models\Unit::all() as $unit)
                                                    <option value="{{ $unit->id }}">{{ $unit->unit }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="conversion-example" id="conversion_example">
                                        <strong>Example:</strong> This means you have <span id="example_text"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="product_elem col-md-8 mt-2 p-2 custom_attributes"
                        style="background: #f8fdff;
                            border-radius: 10px;
                            border: 2px dashed #e2e2e2;">
                        <div class="mb-3">
                            <label class="font-weight-bold d-block mb-2">Custom Attributes</label>
                            <div id="custom-buttons">
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                    data-label="Serial No">+ Serial No</button>
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                    data-label="Expiry Date">+ Expiry Date</button>
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                    data-label="Part No.">+ Part No.</button>
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                    data-label="Other">+ Other</button>
                            </div>
                        </div>

                        <!-- Where dynamic fields appear -->
                        <div id="custom-fields"></div>
                    </div>

                    @include('vendor-views.inventory.partials._description_attributes', [
                        'description_attributes' => [],
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
                            <a class="btn btn-outline-primary next_btn" data-next="basic">
                                Back
                            </a>
                            <a class="btn btn-primary next_btn" id="attr_next_btn" data-next="sales">
                                Next
                            </a>
                            <button type="submit" class="btn btn-primary" id="attr_save_btn" style="display:none;">
                                Save
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sales Info Tab -->

            </div>
            <div class="tab-pane fade" id="sales" role="tabpanel">
                <div class="row">

                    <div class="col-md-3">
                        <div class="form-group form-group-custom">
                            <label for="hsn" class="custom-label">HSN</label>
                            <input type="number" name="hsn" class="form-control" id="hsn"
                                placeholder="HSN" />
                        </div>
                    </div>
                    @include('vendor-views.inventory.partials._selling_price_basis')
                    <div class="col-md-3">
                        <div class="form-group form-group-custom">
                            <label for="openingStock" class="custom-label">Selling Price<span
                                    class="text-danger">*</span></label>
                            <input type="number" placeholder="Selling Price" name="main_selling_price"
                                class="form-control" step="0.001" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group form-group-custom">
                            <label for="openingStock" class="custom-label">MRP<span
                                    class="text-danger">*</span></label>
                            <input type="number" placeholder="MRP" name="main_mrp" class="form-control"
                                step="0.001" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group form-group-custom">
                            <label for="openingStock" class="custom-label">Landing Price</label>
                            <input type="number" placeholder="Landing Price" name="main_landing_price"
                                class="form-control" step="0.001" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group form-group-custom">
                            <label for="openingStock" class="custom-label">Opening Stock</label>
                            <input type="number" placeholder="Opening Stock" name="main_opening_stock"
                                class="form-control" step="1" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="gstType" class="custom-label">GST <span class="text-danger">*</span></label>

                        <div class="input-group flex-nowrap">
                            <div class="input-group-prepend">
                                <input type="number" onkeyup="checkPricing(this)" value="18" id="gst_rate" name="gst_rate"
                                    placeholder="%" class="form-control">
                            </div>
                            {{-- <select class="form-control js-select2-custom" name="gst_type"
                                data-placeholder="Select GST Type" id="gstType">
                                <option value="cgst_sgst">GST (CGST + SGST) </option>
                                <option value="igst">IGST</option>
                                <option value="no_gst">No GST</option>
                            </select> --}}
                            <select class="form-control js-select2-custom" name="gst_status"
                                data-placeholder="Select GST Satuts" id="gstStatus">
                                <option value="excluding">Excluding</option>
                                <option value="including">Including</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="badge badge-soft-info p-2 d-block text-left">
                            <label class="custom-label cursor-pointer mb-0 d-flex align-items-center">
                                <input type="checkbox" id="sell_loose_cb" name="sell_loose" value="1"
                                    class="form-check-input position-static ml-0 mr-2">
                                Sell loose — weigh at the time of sale (POS asks for the weight; billed weight × price)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-12 p-1" id="add_variations_wrap">
                    <label for="add_variations_cb" class="custom-label cursor-pointer mb-0 d-inline-block">
                        <div class="badge badge-soft-primary align-items-center" style="height: 39px;display: flex;">
                            <div class="form-check d-flex mr-1">
                                <input id="add_variations_cb" type="checkbox" class="form-check-input">
                                <span style="white-space: nowrap;" class="mt-1 form-check-label">Add Variations</span>
                            </div>
                        </div>
                    </label>
                </div>
                <div class="col-12 d-flex justify-content-end mt-3 gap-2">
                    <a class="btn btn-outline-primary next_btn" data-next="attributes">
                        Back
                    </a>
                    <a class="btn btn-primary next_btn" id="sales_next_btn" data-next="variations" style="display:none;">
                        Next
                    </a>
                    <button type="submit" class="btn btn-primary" id="sales_save_btn">
                        Save
                    </button>
                </div>
            </div>
            <div class="tab-pane fade" id="variations" role="tabpanel">
                <div class="row g-0">
                    <div class="col-12" id="variations_wrap" style="display:none;">
                        <div class="alert alert-info py-2 px-3 mb-2" style="font-size:12px;">
                            Set the main product price &amp; base unit under <b>Sales Info</b>, then add a weight
                            variation (e.g. 100g, 200g) — its MRP &amp; selling price fill in automatically.
                        </div>
                        <div class="col-md-12 p-0" id="attribute_section">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlSelect1">Add Variation<span
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
                                <div class="card-body">
                                    <div id="add_new_option">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end mt-3 gap-2">
                        <a class="btn btn-outline-primary next_btn" data-next="sales">Back</a>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
    // Conditional Basic-Info layout:
    //  • "Show on Website" ON  -> Specs, Highlights, extra Images, and per-variation
    //    Highlights/Specs/Images are shown; variations are available.
    //  • "Show on Website" OFF -> only the main image is kept; an "Add Variations" checkbox
    //    appears and, when ticked, reveals the variation builder (without per-variation
    //    Highlights/Specs/Images).
    //  • Once variations are added, the Sales Info tab is not needed and is hidden, so a Save
    //    button is surfaced in the variations block and the Attributes tab.
    // Required fields inside a hidden block can't be focused and silently block submit, so
    // hidden sections are disabled; on Save we also surface any invalid field's tab.
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
            // Per-variation Highlights/Specs/Images — website only.
            document.querySelectorAll('.variant-extra-row').forEach(function (tr) {
                showEl(tr, on);
                tr.querySelectorAll('input, select, textarea').forEach(function (c) { c.disabled = !on; });
            });

            // Sales Info stays visible; its footer shows Next -> Variations when enabled.
            showEl(salesLi, prod);
            setDisabled(salesPane, false);
            showEl(document.getElementById('sales_next_btn'), varsVisible);
            showEl(document.getElementById('sales_save_btn'), !varsVisible);
            showEl(document.getElementById('attr_next_btn'), true);
            showEl(document.getElementById('attr_save_btn'), false);
            if (!varsVisible) {
                var vt = document.getElementById('variations-tab');
                if (vt && vt.classList.contains('active')) {
                    var st = document.getElementById('sales-tab'); if (st) st.click();
                }
            }
        }

        $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
            sync();
        });

        document.addEventListener('change', function (e) {
            if (e.target && (e.target.id === 'show_on_website' || e.target.id === 'add_variations_cb' || e.target.name === 'item_type')) sync();
        });
        document.addEventListener('click', function (e) {
            if (e.target.closest && e.target.closest('.custom-radio-item')) setTimeout(sync, 0);
        });
        // Re-sync when variant combinations are (re)rendered via AJAX.
        if (combos && window.MutationObserver) {
            new MutationObserver(function () { sync(); }).observe(combos, { childList: true });
        }

        if (form) {
            Array.prototype.forEach.call(form.querySelectorAll('button'), function (btn) {
                if (btn.type !== 'submit') return;
                btn.addEventListener('click', function (e) {
                    sync();
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
        sync();
    })();
</script>
