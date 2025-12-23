@extends('layouts.admin.app')

@section('title', translate('messages.add_new_subscription'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/tags-input.min.css') }}" rel="stylesheet">
    <style>
        .check-item {
            max-width: 100% !important;
        }

        .btn-outline-primary.active {
            background-color: #00868f !important;
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
                    {{ $type && $type == 'customized' ? ' Customized Plan' : translate('messages.add_new_subscription') }}
                </span>
            </h1>

        </div>
        <!-- End Page Header -->
        <form action="javascript:" method="post" id="item_form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="req_id" value = "{{ $request_det?->id }}">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">

                            <div class="lang_form row" id="default-form">
                                <div class="form-group {{ $type && $type == 'customized' ? 'col-md-6' : 'col-12' }}">
                                    <label class="input-label" for="default_name">Title
                                        (Default)
                                    </label>
                                    <input type="text" name="name" id="default_name" class="form-control"
                                        placeholder="Plan Title">
                                </div>
                                @if ($type && $type == 'customized')
                                    <div class="col-md-6">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('messages.store') }}<span
                                                class="input-label-secondary"></span></label>
                                        <select name="store_id" id="store_id" class="js-data-example-ajax form-control"
                                            title="{{ translate('messages.select_store') }}">
                                            <option disabled selected>---{{ translate('messages.select_store') }}---
                                            </option>
                                            @foreach ($stores as $key => $store)
                                                <option value="{{ $store->id }}">{{ $store->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                @endif

                                <div class="form-group mb-0 col-12">
                                    <label class="input-label" for="exampleFormControlInput1">Short Description</label>
                                    <textarea type="text" name="description" class="form-control min-h-90px ckeditor"></textarea>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow--card-2 border-0">
                        <div class="card-header">
                            <h5 class="card-title">
                                <span class="card-header-icon mr-2">
                                    <i class="tio-dashboard-outlined"></i>
                                </span>
                                <span> Features </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                @foreach ($features as $key => $value)
                                    {{-- @php print_r(json_decode($request_det));
                                echo $name ;  @endphp --}}
                                    @if ($request_det && in_array($value->key, json_decode($request_det?->features, true)))
                                        <div class="col-sm-6 col-lg-6">
                                            <div class="check-item w-100">
                                                <div class="form-group form-check form--check">
                                                    <input type="checkbox" name="{{ $value->key }}" value="1"
                                                        checked class="form-check-input" id="{{ $value->key }}">

                                                    <label class="form-check-label qcont text-dark"
                                                        for="{{ $value->key }}">
                                                        {{ translate('messages.' . $value->key) }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        @else
                                         <div class="col-sm-6 col-lg-6">
                                            <div class="check-item w-100">
                                                <div class="form-group form-check form--check">
                                                    <input type="checkbox" name="{{ $value->key }}" value="1"
                                                         class="form-check-input" id="{{ $value->key }}">

                                                    <label class="form-check-label qcont text-dark"
                                                        for="{{ $value->key }}">
                                                        {{ translate('messages.' . $value->key) }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card shadow--card-2 border-0">
                        <div class="card-header">
                            <h5 class="card-title">
                                <span class="card-header-icon"><i class="tio-dollar-outlined"></i></span>
                                <span>Amount and Duration</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="input-label" for="exampleFormControlInput1">Duration</label>
                                    <select name="standard_duration[]" id="standard_duration"
                                        class="form-control js-select2-custom" multiple="multiple"
                                        data-placeholder="{{ translate('messages.select_duration') }}">
                                        <option value="1 Month">1 Month</option>
                                        <option value="3 Months">3 Months</option>
                                        <option value="6 Months">6 Months</option>
                                        <option value="12 Months">12 Months</option>
                                    </select>
                                    <div class="rows_parent row">

                                    </div>
                                </div>

                                {{-- <div class="col-sm-2 col-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.discount') }}
                                            (%)</label>
                                        <input type="number" min="0" max="99.99" step="0.001"
                                            name="discount" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 23">
                                    </div>
                                </div> --}}
                                <input type="hidden" value="standard" name="duration_style">
                                {{-- <div class="col-sm-3 col-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">Duration Type</label>
                                        <div class="btn-group btn-group-toggle m-0 w-100" style="    margin: 2px auto;"
                                            data-toggle="buttons">
                                            <label class="btn btn-responsive btn-outline-primary w-50"
                                                style="cursor: not-allowed;">
                                                <input type="radio" class="duraction_type" value="standard"
                                                    name="duration_style" checked id="option1"> Standard
                                            </label>
                                            <label class="btn btn-responsive btn-outline-primary active w-50">
                                                <input type="radio" class="duraction_type" value="custom"
                                                    name="duration_style" id="option3">
                                                Custom
                                            </label>
                                        </div>
                                    </div>
                                </div> --}}


                                {{-- <div class="col-sm-3 col-6 custom-duration" style="display: none;">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">Duration</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend w-50">
                                                <input type="number" min="1" max="999"
                                                    name="duration_count" class="form-control"
                                                    placeholder="{{ translate('messages.Ex:') }} 5">
                                            </div>
                                            <div class="input-group-prepend w-50">
                                                <select name="duration_type" id="duration_type"
                                                    class="form-control js-select2-custom">
                                                    <option value="Months">Months</option>
                                                    <option value="Days">Days</option>
                                                    <option value="Years">Years</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}


                                <div class="col-sm-2 col-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="gst_percent">GST Percentage</label>
                                        <input type="number" id="gst_percent" min="1" max="99"
                                            name="gst_percent" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 5">
                                    </div>
                                </div>

                                <div class="col-sm-2 col-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="hsn">HSN</label>
                                        <input type="text" name="hsn" id="hsn" class="form-control"
                                            placeholder="HSN">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="btn--container justify-content-end">
                        {{-- <button type="reset" id="reset_btn"
                            class="btn btn--reset">{{ translate('messages.reset') }}</button> --}}
                        <button type="submit" class="btn btn-primary">{{ translate('messages.submit') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>



@endsection


@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/tags-input.min.js"></script>


    <script>
        var module_id = {{ Config::get('module.current_module_id') }};
        var parent_category_id = 0;
        var module_data = null;
        var stock = true;


        $(document).on('ready', function() {
            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function() {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>

    <script>
        $('#item_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.plan.store') }}',
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
                        for (var i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        toastr.success("Plan Added Successfully", {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function() {
                            {{-- location.href = "{{ route('admin.plan.list') }}"; --}}
                        }, 2000);
                    }
                }
            });
        });


        $("#reset_btn").on('click', function() {
            $('#item_form').trigger('reset')
        })
        $(".duraction_type").on("change", function() {
            if ($(this).val() === "custom") {
                $(".standard-duration").hide();
                $(".custom-duration").show();
            } else {
                $(".standard-duration").show();
                $(".custom-duration").hide();
            }
        });
        $("#standard_duration").on('change', function() {
            let selected = $(this).val(); // Array of selected values
            let wrapper = $(".rows_parent");
            wrapper.empty(); // Clear old inputs

            selected.forEach(function(val) {
                wrapper.append(`
                    <div class="price-field mb-2 col-md-6">
                        <label>Price for ${val}</label>
                        <input type="number" name="price_${val}" class="form-control" placeholder="Enter price for ${val}" />
                    </div>
                    <div class="price-field mb-2 col-md-6">
                        <label>Discount for ${val}</label>
                        <input type="number" name="discount_${val}" class="form-control" placeholder="Enter Discount for ${val}" />
                    </div>
                `);
            });
        });
    </script>
@endpush
