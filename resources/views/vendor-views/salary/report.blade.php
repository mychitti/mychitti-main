@extends('layouts.vendor.app')

@section('title', 'Salary Report')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Report <span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($salary) }} </span></h1>

        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Salary Report of {{ _monthNYear($month . '-01') }}</h5>
                    @if (hasPermission('salary_report', 'export'))
                        <!-- Unfold -->
                        <div class="hs-unfold mr-2">
                            <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40"
                                href="javascript:;"
                                data-hs-unfold-options='{
                                    "target": "#usersExportDropdown",
                                    "type": "css-animation"
                                }'>
                                <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                            </a>

                            <div id="usersExportDropdown"
                                class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">

                                <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                                <a id="export-excel" class="dropdown-item"
                                    href="{{ route('vendor.salary.export-salaries', ['type' => 'excel', request()->getQueryString()]) }}">
                                    <img class="avatar avatar-xss avatar-4by3 mr-2"
                                        src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                        alt="Image Description">
                                    {{ translate('messages.excel') }}
                                </a>
                            </div>
                        </div>
                        <!-- End Unfold -->
                    @endif
                    <form action="" id="search-form" class="search-form">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input type="month" value="{{ $month }}" name="month" class="form-control" required>
                            <button type="submit" class="btn btn--secondary"><i class="tio-filter"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>

                    <!-- End Unfold -->
                </div>
            </div>
            <!-- End Header -->
            @if (hasPermission('salary_report', 'list'))
                <!-- Table -->
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false

                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">Employee</th>
                                <th class="border-0">Base Salary</th>
                                <th class="border-0">Allowance</th>
                                <th class="border-0">Deductions</th>
                                <th class="border-0">Bonus Incentives</th>
                                <th class="border-0">Pay Status</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($salary as $lead)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div>
                                            <a href="#" class="table-rest-info" alt="view store">

                                                <div class="info">
                                                    <div class="text--title">
                                                        @php
                                                            $depNm = _getWhere('vendor_employees', [
                                                                'id' => $lead->ven_id,
                                                            ]);
                                                            if (isset($depNm[0])) {
                                                                echo $depNm[0]->f_name .
                                                                    ' ' .
                                                                    $depNm[0]->l_name .
                                                                    ' #' .
                                                                    $lead->employee_id;
                                                        } @endphp
                                                    </div>

                                                </div>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        {{ \App\CentralLogics\Helpers::format_currency($lead->base_salary) }}
                                    </td>
                                    <td>
                                        {{ \App\CentralLogics\Helpers::format_currency($lead->allowance_amount) }}
                                    </td>
                                    <td>
                                        {{ \App\CentralLogics\Helpers::format_currency($lead->deductions) }}
                                    </td>
                                    <td>
                                        {{ \App\CentralLogics\Helpers::format_currency($lead->bonus_incentives) }}
                                    </td>
                                    <td>
                                        {{ ucfirst($lead->pay_status) }}
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($salary))
                        <hr>
                    @else
                        <div class="page-area">
                        </div>
                        <div class="empty--data">
                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                            <h5>
                                {{ translate('no_data_found') }}
                            </h5>
                        </div>
                    @endif
                </div>
                <!-- End Table -->
            @endif
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script>
        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }
        $(document).on('ready', function() {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function() {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function() {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('keyup', function() {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function() {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function() {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>
@endpush
