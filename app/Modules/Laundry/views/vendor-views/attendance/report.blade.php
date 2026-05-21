@extends('layouts.vendor.app')

@section('title', 'Attendance Reports')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Reports <span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($staff) }}</span></h1>
            @if (hasPermission('attendance_report', 'list'))
                <form action="" class="row">
                    <div class="col-md-3">
                        <input type="date" name="from" value="{{ $fromdate }}" class="form-control"
                            id="">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="to" value="{{ $todate }}" class="form-control"
                            id="">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-sm">Filter</button>
                    </div>
                </form>
            @endif
            @if (hasPermission('attendance_report', 'export'))
                <div class="hs-unfold mr-2">
                    <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40" href="javascript:;"
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
                            href="{{ route('vendor.attendance.export', ['type' => 'excel', request()->getQueryString()]) }}">
                            <img class="avatar avatar-xss avatar-4by3 mr-2"
                                src="{{ asset('public/assets/admin') }}/svg/components/excel.svg" alt="Image Description">
                            {{ translate('messages.excel') }}
                        </a>

                    </div>
                </div>
            @endif

        </div>
        <!-- End Page Header -->


        @if (hasPermission('attendance_report', 'list'))
            <!-- Card -->
            <div class="card">
                <!-- Header -->
                <div class="card-header py-2">
                    <div class="search--button-wrapper">
                        <h5 class="card-title">Attendance Reports</h5>

                        <!-- End Unfold -->
                    </div>
                </div>
                <!-- End Header -->

                <!-- Table -->
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">Employee</th>
                                <th class="border-0">P</th>
                                <th class="border-0">A</th>
                                <th class="border-0">L</th>
                                @foreach ($formattedDate as $date)
                                    <th class="border-0">{{ $date }}</th>
                                @endforeach

                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($staff as $key => $lead)
                                <tr>
                                    <td>{{ $key + 1 }}</td>

                                    <td>
                                        <div>
                                            <a href="#" class="table-rest-info" alt="view store">

                                                <div class="info">
                                                    <div class="text--title">
                                                        {{ $lead['f_name'] . ' ' . $lead['l_name'] }}
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </td>
                                    <td>{{ $lead['present_days'] }}</td>
                                    <td>{{ $lead['absent_days'] }}</td>
                                    <td title="Total Leaves taken during {{ $fromdate }} to {{ $todate }}">
                                        {{ $lead['casual_leaves'] }}</td>
                                    @foreach ($dates as $date)
                                        <td> {{ !empty($lead[$date]) ? $lead[$date][0]['label'] : '-' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($staff))
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
        @endif
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
