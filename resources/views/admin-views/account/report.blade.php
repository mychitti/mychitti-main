@extends('layouts.admin.app')

@section('title', 'Account Report')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Report <span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($accountReport) }} </span></h1>

        </div>
        <!-- End Page Header -->
@if(hasPermission('reports_account_report', 'list'))
        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Accounts Report </h5>

                    <form action="" class=" date-range-form">
                        @include('vendor-views/form_modals/date_range')
                        <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                            data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                    </form>

                </div>
            </div>
            <!-- End Header -->

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
                            <th class="border-0">Date</th>
                            <th class="border-0">Voucher Numeber</th>
                            <th class="border-0">Type</th>
                            <th class="border-0">Description</th>
                            <th class="border-0">Payment Method</th>
                            <th class="border-0">Income</th>
                            <th class="border-0">Expenses</th>
                            <th class="border-0">Balance</th>
                            <th class="border-0">Status</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($accountReport as $key => $a)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $a['voucher_date'] }}</td>
                                <td>
                                    {{ $a['voucher_number'] }}
                                </td>
                                <td>
                                    {{ $a['voucher_type'] }}
                                </td>
                                <td> {{ $a['narration'] }} </td>
                                <td> {{ ucfirst(isset($a->ledgerEntries[0]) ? $a->ledgerEntries[0]->payment_mode : '') }}
                                </td>
                                <td> {{ $a['credit_entity_type'] == 'store' ? $a['total_amount'] : 0.0 }} </td>
                                <td> {{ $a['debit_entity_type'] == 'store' ? $a['total_amount'] : 0.0 }} </td>
                                <td> {{ $a['total_amount'] }} </td>
                                <td>
                                    @if ($a->status == 'approved')
                                        <span class="badge badge-soft-success">
                                            Completed
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger">
                                            Pending
                                        </span>
                                    @endif
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if (count($accountReport))
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
        </div>
        <!-- End Card -->
    </div>
    @endif

@endsection

@push('script_2')
    @include('vendor-views/js/date_range')

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
