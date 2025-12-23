@extends('layouts.admin.app')

@section('title', Config::get('module.vendor_role') . ' Config')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('/public/assets/admin/css/intlTelInput.css') }}" />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var radioInputs = document.querySelectorAll('input[name="plan_select"]');
            var uncheckButton = document.getElementById('uncheck_plans');

            if (uncheckButton) {
                uncheckButton.addEventListener('click', function() {
                    radioInputs.forEach(function(input) {
                        input.checked = false;
                    });
                });
            } else {
                console.error("Element with ID 'uncheck_plans' not found.");
            }
        });
    </script>
    <style>
        .check-mark {
            font-weight: bold;
            font-size: 1.2rem;
            color: #00aa6d;
        }

        .cross-mark {
            font-weight: bold;
            font-size: 1.2rem;
            color: #d41a1a;
        }
    </style>
@endpush



@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/store.png') }}" class="w--26" alt="">
                </span>
                <span>
                    Configuration
                </span>
            </h1>
        </div>
        <div class="row g-2">
            <div class="col-lg-6">
                <!-- End Page Header -->
                <form action="{{ route('admin.store.type_store') }}" method="post" enctype="multipart/form-data"
                    class="js-validate" id="vendor_form">
                    @csrf
                    <div class="card shadow--card-2">
                        <div class="card-header">
                            <h5 class="card-title">
                                <span class="card-header-icon mr-1"><i class="tio-dashboard"></i></span>
                                <span>Add Type </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="default-form">
                                <div class="form-group">
                                    <label class="input-label" for="exampleFormControlInput1">Business type</label>
                                    <input type="text" name="name" class="form-control" placeholder="Ex : Hospital"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label class="input-label" for="exampleFormControlInput1">Modules</label>
                                    <select name="sub_modules[]" id="sub_modules" class="form-control js-select2-custom"
                                        multiple="multiple" placeholder="{{ translate('messages.select_zone') }}">
                                        @foreach ($sub_modules as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="btn--container justify-content-end">
                                    <button type="submit"
                                        class="btn btn--primary">{{ translate('messages.submit') }}</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
                <div class="card  mt-3">
                    <div class="card-header">
                        <h5 class="card-title">
                            <span class="card-header-icon mr-1"><i class="tio-dashboard"></i></span>
                            <span>Module Pricing</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive datatable-custom">
                            <table id="columnSearchDatatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false }'>
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Module</th>
                                        <th class="border-0">Price</th>
                                        <th class="border-0">Discounts</th>
                                        <th class="border-0">Action</th>
                                    </tr>
                                </thead>

                                <tbody id="set-rows2">
                                    @foreach ($sub_modules as $type)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <div>
                                                    <div class="info">
                                                        <div class="text--title">
                                                            {{ $type->name }}
                                                        </div>

                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ _price($type->price_per_month) }}</td>
                                            <td>
                                                <b>1 Month: </b> {{ $type->discount_1_month }}% <br>
                                                <b>3 Month: </b> {{ $type->discount_3_month }}% <br>
                                                <b>6 Month: </b> {{ $type->discount_6_month }}% <br>
                                                <b>12 Month: </b> {{ $type->discount_12_month }}%
                                            </td>
                                            <td>
                                                <div class="btn--container justify-content-center">
                                                    <a class="btn action-btn btn--primary btn-outline-primary"
                                                        type="button" class="btn btn-primary" data-toggle="modal"
                                                        data-target="#editModal{{ $type->id }}"><i
                                                            class="tio-edit"></i>
                                                    </a>

                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Modal -->
                                        <div class="modal fade" id="editModal{{ $type->id }}" tabindex="-1"
                                            aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="exampleModalLabel">Edit Module Pricing
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h4>{{ $type->name }}</h4>
                                                        <form action="{{ route('admin.store.update_modules') }}"
                                                            method="post" enctype="multipart/form-data">
                                                            @csrf
                                                            <input type="hidden" name="module_id"
                                                                value="{{ $type->id }}">
                                                            <div class="row">
                                                                <div class="form-group col-md-12">
                                                                    <label class="input-label" for="name">
                                                                        Name</label>
                                                                    <input class="form-control" type="text"
                                                                        name="name" id="name"
                                                                        value="{{ $type->name }}">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label class="input-label" for="price_per_month">Price
                                                                        Per Month</label>
                                                                    <input class="form-control" type="number"
                                                                        name="price_per_month" id="price_per_month"
                                                                        value="{{ $type->price_per_month }}">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label class="input-label"
                                                                        for="price_per_month">Discount 1 Month</label>
                                                                    <input class="form-control" type="number"
                                                                        name="discount_1_month" id="discount_1_month"
                                                                        value="{{ $type->discount_1_month }}">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label class="input-label"
                                                                        for="price_per_month">Discount 3 Month</label>
                                                                    <input class="form-control" type="number"
                                                                        name="discount_3_month" id="discount_3_month"
                                                                        value="{{ $type->discount_3_month }}">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label class="input-label"
                                                                        for="price_per_month">Discount 6 Month</label>
                                                                    <input class="form-control" type="number"
                                                                        name="discount_6_month" id="discount_6_month"
                                                                        value="{{ $type->discount_6_month }}">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label class="input-label"
                                                                        for="price_per_month">Discount 12 Month</label>
                                                                    <input class="form-control" type="number"
                                                                        name="discount_12_month" id="discount_12_month"
                                                                        value="{{ $type->discount_12_month }}">
                                                                </div>

                                                            </div>
                                                            <div class="btn--container justify-content-end">
                                                                <button type="submit"
                                                                    class="btn btn--primary">{{ translate('messages.submit') }}</button>
                                                            </div>
                                                        </form>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                            @if (count($sub_modules))
                                <hr>
                            @else
                                <div class="page-area">
                                </div>
                                <div class="empty--data">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                        alt="public">
                                    <h5>
                                        {{ translate('no_data_found') }}
                                    </h5>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <!-- End Page Header -->
                <div class="card shadow--card-2">
                    <div class="card-header">
                        <h5 class="card-title">
                            <span class="card-header-icon mr-1"><i class="tio-dashboard"></i></span>
                            <span>Business Types List</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive datatable-custom">
                            <table id="columnSearchDatatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false }'>
                                <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('sl') }}</th>
                                        <th class="border-0">Type</th>
                                        <th class="border-0">Action</th>

                                    </tr>
                                </thead>

                                <tbody id="set-rows">
                                    @foreach ($store_types as $type)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <div>
                                                    <a href="" class="table-rest-info" alt="view store">

                                                        <div class="info">
                                                            <div class="text--title">
                                                                {{ $type->name }}
                                                            </div>

                                                        </div>
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn--container justify-content-center">
                                                    <a class="btn action-btn btn--primary btn-outline-primary"
                                                        type="button" class="btn btn-primary" data-toggle="modal"
                                                        data-target="#editModal{{ $type->id }}"><i
                                                            class="tio-edit"></i>
                                                    </a>

                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Modal -->
                                        <div class="modal fade" id="editModal{{ $type->id }}" tabindex="-1"
                                            aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h4>{{ $type->name }}</h4>
                                                        <form action="{{ route('admin.store.save_modules') }}"
                                                            method="post" enctype="multipart/form-data">
                                                            @csrf
                                                            <input type="hidden" name="business_type_id"
                                                                value="{{ $type->id }}">
                                                            <div class="form-group">
                                                                <label class="input-label"
                                                                    for="exampleFormControlInput1">Modules</label>
                                                                <select name="edit_sub_modules[]"
                                                                    id="edit_sub_modules{{ $type->id }}"
                                                                    class="form-control js-select2-custom"
                                                                    multiple="multiple"
                                                                    placeholder="{{ translate('messages.select_zone') }}">
                                                                    @php $permitted = explode(',' ,  $type->permitted_submodules) @endphp
                                                                    @foreach ($sub_modules as $type)
                                                                        <option
                                                                            {{ in_array($type->Key, $permitted) ? 'selected' : '' }}
                                                                            value="{{ $type->Key }}">
                                                                            {{ $type->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="btn--container justify-content-end">
                                                                <button type="submit"
                                                                    class="btn btn--primary">{{ translate('messages.submit') }}</button>
                                                            </div>

                                                        </form>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                            @if (count($store_types))
                                <hr>
                            @else
                                <div class="page-area">
                                </div>
                                <div class="empty--data">
                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                        alt="public">
                                    <h5>
                                        {{ translate('no_data_found') }}
                                    </h5>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&callback=initMap&v=3.45.8">
    </script>

    <script>
        "use strict";


        function readURL(input, viewer) {
            if (input.files && input.files[0]) {
                let reader = new FileReader();

                reader.onload = function(e) {
                    $('#' + viewer).attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }



        let zone_id = 0;
        $('#choice_zones').on('change', function() {
            if ($(this).val()) {
                zone_id = $(this).val();
            }
        });

        $('#module_id').select2({
            ajax: {
                url: '{{ url('/') }}/store/get-all-modules',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        page: params.page,
                        zone_id: zone_id
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
    </script>
@endpush
