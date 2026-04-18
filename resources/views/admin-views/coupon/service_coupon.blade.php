@extends('layouts.admin.app')

@section('title', translate('messages.coupons'))

@section('content')
    <style>
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            height: fit-content;
        }
    </style>
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/add.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('Add new coupon') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row g-2">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.coupon.service-coupon.store') }}" method="POST">
                            @csrf
                            <div class="row">

                                <div class="col-md-4 col-lg-3 col-sm-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" name="for_all_existing" value="1"
                                            type="checkbox" id="for_Existing">
                                        <label class="form-check-label" for="for_Existing">
                                            For All Existing
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" name="for_upcoming" type="checkbox" value="1"
                                            id="for_upcoming">
                                        <label class="form-check-label" for="for_upcoming">
                                            For All Upcoming
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-12 col-lg-12 col-sm-12" id="store_wise">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('messages.stores') }}<span
                                                class="input-label-secondary"></span></label>
                                        <select multiple name="store_ids[]" id="store_id"
                                            class="js-data-example-ajax form-control"
                                            data-placeholder="{{ translate('messages.select_store') }}"
                                            title="{{ translate('messages.select_store') }}">
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.code') }}</label>
                                        <input type="text" name="code" class="form-control"
                                            placeholder="{{ \Illuminate\Support\Str::random(8) }}" required maxlength="100">
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.amount') }}</label>
                                        <input type="number" name="amount" id="amount" class="form-control"
                                            placeholder="EX: 1000" min="1" max="100000">
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.limit_for_same_user') }}</label>
                                        <input type="number" name="limit" id="coupon_limit" class="form-control"
                                            placeholder="EX: 10" min="1" max="100">
                                    </div>
                                </div>
                            </div>
                            <div class="btn--container justify-content-end">
                                <button type="reset" id="reset_btn"
                                    class="btn btn--reset">{{ translate('messages.reset') }}</button>
                                <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header py-2 border-0">
                        <div class="search--button-wrapper">
                            <h5 class="card-title">{{ translate('messages.coupon_list') }}<span
                                    class="badge badge-soft-dark ml-2" id="itemCount">{{ $coupons->total() }}</span></h5>


                        </div>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive datatable-custom" id="table-div">
                        <table id="columnSearchDatatable"
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            data-hs-datatables-options='{
                                "order": [],
                                "orderCellsTop": true,

                                "entries": "#datatableEntries",
                                "isResponsive": false,
                                "isShowPaging": false,
                                "paging":false
                               }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('sl') }}</th>
                                    <th class="border-0">{{ translate('messages.amount') }}</th>
                                    <th class="border-0">{{ translate('messages.code') }}</th>
                                    <th class="border-0">{{ translate('messages.for') }}</th>
                                    <th class="border-0">{{ translate('messages.total_uses') }}</th>
                                    <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                                </tr>
                            </thead>

                            <tbody id="set-rows">
                                @foreach ($coupons as $key => $coupon)
                                    <tr>
                                        <td>{{ $key + $coupons->firstItem() }}</td>
                                        <td>{{ \App\CentralLogics\Helpers::format_currency($coupon['amount']) }}</td>
                                        <td>{{ $coupon['code'] }}</td>
                                        <td>{{ $coupon['user_type'] }} ID {{ $coupon['user_type_id'] }} </td>
                                        <td>{{ $coupon['use_limit'] }}</td>
                                        <td>
                                            <div class="btn--container justify-content-center">

                                                <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                    href="javascript:" data-id="coupon-{{ $coupon['id'] }}"
                                                    data-message="{{ translate('Want to delete this coupon ?') }}"
                                                    title="{{ translate('messages.delete_coupon') }}"><i
                                                        class="tio-delete-outlined"></i>
                                                </a>
                                                <form
                                                    action="{{ route('admin.coupon.service-coupon.delete', [$coupon['id']]) }}"
                                                    method="post" id="coupon-{{ $coupon['id'] }}">
                                                    @csrf
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if (count($coupons) !== 0)
                        <hr>
                    @endif
                    <div class="page-area">
                        {!! $coupons->links() !!}
                    </div>
                    @if (count($coupons) === 0)
                        <div class="empty--data">
                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                            <h5>
                                {{ translate('no_data_found') }}
                            </h5>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header py-2 border-0">
                        <div class="search--button-wrapper">
                            <h5 class="card-title">{{ translate('messages.for_upcoming_stores') }}<span
                                    class="badge badge-soft-dark ml-2"
                                    id="itemCount">{{ count($upcoming_coupons) }}</span></h5>


                        </div>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive datatable-custom" id="table-div">
                        <table id="columnSearchDatatable"
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            data-hs-datatables-options='{
                                "order": [],
                                "orderCellsTop": true,

                                "entries": "#datatableEntries",
                                "isResponsive": false,
                                "isShowPaging": false,
                                "paging":false
                               }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('sl') }}</th>
                                    <th class="border-0">{{ translate('messages.amount') }}</th>
                                    <th class="border-0">{{ translate('messages.code') }}</th>
                                    <th class="border-0">{{ translate('messages.for') }}</th>
                                    <th class="border-0">{{ translate('messages.uses_per_store') }}</th>
                                    <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                                </tr>
                            </thead>

                            <tbody id="set-rows">
                                @foreach ($upcoming_coupons as $key => $coupon)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ \App\CentralLogics\Helpers::format_currency($coupon['amount']) }}</td>
                                        <td>{{ $coupon['code'] }}</td>
                                        <td>{{ $coupon['user_type'] }}</td>
                                        <td>{{ $coupon['use_limit'] }}</td>
                                        <td style="pointer-events:none;">
                                            <div class="btn--container justify-content-center">

                                                <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                    href="javascript:" data-id="coupon-{{ $coupon['id'] }}"
                                                    data-message="{{ translate('Want to delete this coupon ?') }}"
                                                    title="{{ translate('messages.delete_coupon') }}"><i
                                                        class="tio-delete-outlined"></i>
                                                </a>
                                                <form action="{{ route('admin.coupon.delete', [$coupon['id']]) }}"
                                                    method="post" id="coupon-{{ $coupon['id'] }}">
                                                    @csrf @method('delete')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if (count($upcoming_coupons) !== 0)
                        <hr>
                    @endif
                    <div class="page-area">
                    </div>
                    @if (count($upcoming_coupons) === 0)
                        <div class="empty--data">
                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                            <h5>
                                {{ translate('no_data_found') }}
                            </h5>
                        </div>
                    @endif
                </div>
            </div>
            <!-- End Table -->
        </div>
    </div>

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/coupon-index.js"></script>
    <script>
        "use strict";

        $(document).on('ready', function() {

            let module_id = {{ Config::get('module.current_module_id') }};
            var url = @if (Str::contains(request()->getHost(), 'staging.mychitti.net'))'{{ url('/') }}/admin/store/get-stores'@else'{{ url('/') }}/store/get-stores'@endif;

            $('.js-data-example-ajax').select2({
                minimumInputLength: 3,
                ajax: {
                    url: url,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page,
                            module_id: module_id
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    }
                }
            });

        });
        $('#select_customer').on('change', function() {
            let customer = $(this).val();
            if (Array.isArray(customer) && customer.includes("all")) {
                $('.select_customer_option').prop('disabled', true);
                customer = ["all"];
                $(this).val(customer);
            } else {
                $('.select_customer_option').prop('disabled', false);
            }
        });
        $("#for_Existing").on('change', function() {
            if ($(this).is(':checked')) {
                $('#store_wise').hide();
            } else {
                $('#store_wise').show();
            }
        });
    </script>
@endpush
