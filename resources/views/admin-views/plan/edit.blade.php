@extends('layouts.admin.app')

@section('title', 'Edit Subscription Plan')

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
                    {{ translate('messages.add_new_subscription') }}
                </span>
            </h1>

        </div>
        <!-- End Page Header -->
        <form action="javascript:" method="post" id="item_form" enctype="multipart/form-data">
            @csrf

            <div class="row g-2">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">

                            <div class="lang_form" id="default-form">
                                <div class="form-group">
                                    <label class="input-label" for="default_name">Title
                                        (Default)
                                    </label>
                                    <input type="text" value="{{ $plan->title }}" name="name" id="default_name"
                                        class="form-control" placeholder="Plan Title">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="input-label" for="exampleFormControlInput1">Short Description</label>
                                    <textarea type="text" name="description" class="form-control min-h-90px ckeditor">{{ $plan->description }} </textarea>
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
                                <div class="col-sm-6 col-lg-6">
                                    <div class="check-item w-100">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" {{ $plan->advanced_leads_manage ? 'checked' : '' }}
                                                name="advanced_leads_manage" value="1" class="form-check-input"
                                                id="advanced_leads_manage">
                                            <label class="form-check-label qcont text-dark"
                                                for="advanced_leads_manage">{{ translate('messages.advanced_leads_management') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-6">
                                    <div class="check-item w-100">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" {{ $plan->projects_manage ? 'checked' : '' }}
                                                name="projects_manage" value="1" class="form-check-input"
                                                id="projects_manage">
                                            <label class="form-check-label qcont text-dark"
                                                for="projects_manage">{{ translate('messages.project_management') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-6">
                                    <div class="check-item w-100">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" {{ $plan->quotaiton_manage ? 'checked' : '' }}
                                                name="quotaiton_manage" value="1" class="form-check-input"
                                                id="quotaiton_manage">
                                            <label class="form-check-label qcont text-dark" for="quotaiton_manage">
                                                {{ translate('messages.quotation_management') }}</label>
                                        </div>
                                    </div>
                                </div>
                                {{-- <div class="col-sm-6 col-lg-6">
                                     <div class="check-item w-100">
                                        <div class="form-group form-check form--check">
                                                <input type="checkbox" {{$plan->salary_manage ? 'checked' : ''}}  name="salary_manage" value="1" class="form-check-input"
                                                   id="salary_manage">
                                            <label class="form-check-label qcont text-dark" for="salary_manage">{{ translate('messages.salary_management') }}</label>
                                        </div>
                                    </div>
                                </div>
                                 <div class="col-sm-6 col-lg-6">
                                     <div class="check-item w-100">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" {{$plan->staff_manage ? 'checked' : ''}}  name="staff_manage" value="1" class="form-check-input"
                                                   id="staff_manage">
                                            <label class="form-check-label qcont text-dark" for="staff_manage"> {{ translate('messages.staff_management') }}</label>
                                        </div>
                                    </div>
                                </div>
                                 <div class="col-sm-6 col-lg-6">
                                     <div class="check-item w-100">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" {{$plan->att_manage ? 'checked' : ''}}  name="att_manage" value="1" class="form-check-input"
                                                   id="att_manage">
                                            <label class="form-check-label qcont text-dark" for="att_manage">{{ translate('messages.attendance_management') }}</label>
                                        </div>
                                    </div>
                                </div>
                                 <div class="col-sm-6 col-lg-6">
                                     <div class="check-item w-100">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" {{$plan->leave_manage ? 'checked' : ''}}  name="leave_manage" value="1" class="form-check-input"
                                                   id="leave_manage">
                                            <label class="form-check-label qcont text-dark" for="leave_manage">{{ translate('messages.leave_management') }}</label>
                                        </div>
                                    </div>
                                </div> --}}
                                <div class="col-sm-6 col-lg-6">
                                    <div class="check-item w-100">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="hr_manage" {{ $plan->hr_manage ? 'checked' : '' }}
                                                value="1" class="form-check-input" id="hr_manage">
                                            <label class="form-check-label qcont text-dark"
                                                for="hr_manage">{{ translate('messages.hr_management') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-6">
                                    <div class="check-item w-100">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="billing"
                                                {{ $plan->billing ? 'checked' : '' }} value="1"
                                                class="form-check-input" id="billing">
                                            <label class="form-check-label qcont text-dark"
                                                for="billing">{{ translate('messages.billing') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-6">
                                    <div class="check-item w-100">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" {{ $plan->account_manage ? 'checked' : '' }}
                                                name="account_manage" value="1" class="form-check-input"
                                                id="account_manage">
                                            <label class="form-check-label qcont text-dark"
                                                for="account_manage">{{ translate('messages.account_management') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-6">
                                    <div class="check-item w-100">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" {{ $plan->inventory_manage ? 'checked' : '' }}
                                                name="inventory_manage" value="1" class="form-check-input"
                                                id="inventory_manage">
                                            <label class="form-check-label qcont text-dark"
                                                for="inventory_manage">{{ translate('messages.inventory_management') }}</label>
                                        </div>
                                    </div>
                                </div>
                                 <div class="col-sm-6 col-lg-6">
                                    <div class="check-item w-100">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" {{ $plan->task_manage ? 'checked' : '' }} name="task_manage" value="1"
                                                class="form-check-input" id="task_manage">
                                            <label class="form-check-label qcont text-dark"
                                                for="task_manage">{{ translate('messages.task_management') }}</label>
                                        </div>
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
                                <span class="card-header-icon"><i class="tio-dollar-outlined"></i></span>
                                <span>{{ translate('amount') }} and Duration</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                {{-- <div class="col-sm-3 col-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.price') }}</label>
                                        <input type="number" min="0" value="{{$plan->price}}"  max="999999999999.99" step="0.001"
                                             name="price" class="form-control" 
                                            placeholder="{{ translate('messages.Ex:') }} 1000" > 
                                    </div>
                                </div> --}}
                                <div class="col-md-6">
                                    <label class="input-label" for="exampleFormControlInput1">Duration</label>
                                    <select name="standard_duration[]" id="standard_duration"
                                        class="form-control js-select2-custom" multiple="multiple"
                                        data-placeholder="{{ translate('messages.select_duration') }}">
                                        @php
                                            $selectedDurations = collect(json_decode($plan->price_variations, true))
                                                ->pluck('duration')
                                                ->toArray();
                                            $Variations = json_decode($plan->price_variations, true) ?: [];
                                        @endphp

                                        <option {{ in_array('1 Month', $selectedDurations) ? 'selected' : '' }}
                                            value="1 Month">1 Month</option>
                                        <option {{ in_array('3 Months', $selectedDurations) ? 'selected' : '' }}
                                            value="3 Months">3 Months</option>
                                        <option {{ in_array('6 Months', $selectedDurations) ? 'selected' : '' }}
                                            value="6 Months">6 Months</option>
                                        <option {{ in_array('12 Months', $selectedDurations) ? 'selected' : '' }}
                                            value="12 Months">12 Months</option>

                                    </select>
                                    <div class="rows_parent row">
                                        @foreach ($Variations as $key => $value)
                                            <div class="price-field mb-2 col-md-6 "
                                                data-duration="{{ $value['duration'] }}">
                                                <label>Price for {{ $value['duration'] }}</label>
                                                <input type="number" value="{{ $value['price'] }}"
                                                    name="price_{{ $value['duration'] }}" class="form-control"
                                                    placeholder="Enter price for {{ $value['duration'] }}" />
                                            </div>
                                            <div class="price-field mb-2 col-md-6" data-duration="{{ $value['duration'] }}">
                                                <label>Discount for {{ $value['duration'] }}</label>
                                                <input type="number" name="discount_{{ $value['duration'] }}" class="form-control"
                                                    placeholder="Enter Discount for {{ $value['duration'] }}" value="{{ $value['discount'] ?? 0 }}" />
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                {{-- <div class="col-sm-2 col-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.discount') }}
                                            (%)</label>
                                        <input type="number" min="0" value="{{ $plan->discount }}"
                                            max="99.99" step="0.001" name="discount" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 23">
                                    </div>
                                </div> --}}
                                {{-- <div class="col-sm-3 col-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">Duration Type</label>
                                        <div class="btn-group btn-group-toggle m-0 w-100" style="    margin: 2px auto;"
                                            data-toggle="buttons">
                                            <label class="btn btn-responsive btn-outline-primary w-50"
                                                style="cursor: not-allowed;">
                                                <input type="radio" class="duraction_type" value="standard"
                                                    name="duration_style" {{$plan->duration_style == 'standard' ? 'checked' : ''}} id="option1"> Standard
                                            </label>
                                            <label class="btn btn-responsive btn-outline-primary active w-50">
                                                <input type="radio" class="duraction_type" value="custom"
                                                    name="duration_style" {{$plan->duration_style == 'custom' ? 'checked' : ''}} id="option3">
                                                Custom
                                            </label>
                                        </div>
                                    </div>
                                </div> --}}
                                {{-- <div class="col-sm-3 col-6 standard-duration" {{ $plan->duration_style == 'standard' ? '' : 'style=display:none;' }}>
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">Duration</label>
                                        <select name="standard_duration" id="standard_duration"
                                            class="form-control js-select2-custom">
                                            <option {{$plan->standard_duration == 'Monthly' ? 'selected' : ''}} value="Monthly">Monthly</option>
                                            <option {{$plan->standard_duration == 'Quarterly' ? 'selected' : ''}} value="Quarterly">Quarterly</option>
                                            <option {{$plan->standard_duration == 'Half-Yearly' ? 'selected' : ''}} value="Half-Yearly">Half-Yearly</option>
                                            <option {{$plan->standard_duration == 'Yearly' ? 'selected' : ''}} value="Yearly">Yearly</option>
                                        </select>
                                    </div>
                                </div> --}}
                                {{-- <div class="col-sm-3 col-6 custom-duration" {{ $plan->duration_style == 'custom' ? '' : 'style=display:none;' }} >
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
                                        <input type="number" value="{{ $plan->gst_percent }}" id="gst_percent"
                                            min="1" max="99" name="gst_percent" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 5">
                                    </div>
                                </div>

                                <div class="col-sm-2 col-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="hsn">HSN</label>
                                        <input value="{{ $plan->hsn }}" type="text" name="hsn" id="hsn"
                                            class="form-control" placeholder="HSN">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="btn--container justify-content-end">
                        {{-- <button type="button" id="reset_btn"
                            class="btn btn--reset">{{ translate('messages.reset') }}</button> --}}
                        <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
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

        $("#standard_duration").on('change', function() {
            const selected = $(this).val() || [];
            const wrapper = $(".rows_parent");

            // Step 1: Store current values before any DOM change
            const existingValues = {};
            wrapper.find('.price-field').each(function() {
                const duration = $(this).data('duration');
                const inputVal = $(this).find('input').val();
                existingValues[duration] = inputVal;
            });

            // Step 2: Remove fields not in selected list
            wrapper.find('.price-field').each(function() {
                const duration = $(this).data('duration');
                if (!selected.includes(duration)) {
                    $(this).remove();
                }
            });

            // Step 3: Add missing fields and reapply values if they existed
            selected.forEach(function(val) {
                if (wrapper.find(`.price-field[data-duration="${val}"]`).length === 0) {
                    const safeName = val.replace(/\s+/g, '_');
                    const value = existingValues[val] || '';

                    wrapper.append(`
                <div class="price-field mb-3 col-md-6" data-duration="${val}">
                    <label>Price for ${val}</label>
                    <input type="number" name="price_${safeName}" class="form-control" placeholder="Enter price for ${val}" value="${value}" />
                </div>
                 <div class="price-field mb-2 col-md-6" data-duration="${val}">
                        <label>Discount for ${val}</label>
                        <input type="number" name="discount_${val}" class="form-control" placeholder="Enter Discount for ${val}" value="${value}" />
                    </div>
            `);
                }
            });
        });



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
                url: '{{ route('admin.plan.update', [$plan['id']]) }}',
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
                        toastr.success("Plan Updated Successfully", {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function() {
                            window.location.href = "{{ route('admin.plan.list') }}";
                        }, 2000);
                    }
                }
            });
        });
    </script>
@endpush
