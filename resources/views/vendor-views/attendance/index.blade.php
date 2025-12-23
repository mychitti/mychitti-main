@extends('layouts.vendor.app')

@section('title', 'Attendance Manage')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    {{-- @include('vendor-views/sub-module/partials/attendance') --}}

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Attendance <span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($staff) }}</span></h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->



        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Staff List</h5>
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
                            <th class="border-0">Info</th>
                            <th class="border-0">Department</th>
                            <th class="border-0">Role</th>
                            <th class="text-uppercase border-0">Action</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($staff as $lead)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div>
                                        <a href="{{ route('vendor.employee.view', [$lead->id]) }}" class="table-rest-info"
                                            alt="view store">

                                            <div class="info">
                                                <div class="text--title">
                                                    {{ $lead->f_name . ' ' . $lead->l_name }}
                                                </div>
                                                <div class="font-light">
                                                    {{ $lead->phone }}
                                                </div>
                                                <div class="font-light">
                                                    {{ $lead->email }}
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        @php
                                            // print_r( _getWhere('departments', ['id'=> $lead->department_id])[0]);
                                            $depNm = _getWhere('departments', ['id' => $lead->department_id]);
                                            if (isset($depNm[0])) {
                                                echo $depNm[0]->title;
                                        } @endphp
                                    </span>

                                </td>
                                <td>
                                    <div>
                                        @php
                                            // print_r( _getWhere('departments', ['id'=> $lead->department_id])[0]);
                                            $roleNm = _getWhere('employee_roles', ['id' => $lead->employee_role_id]);
                                            if (isset($roleNm[0])) {
                                                echo $roleNm[0]->name;
                                        } @endphp
                                    </div>
                                </td>



                                <td>
                                    @if (hasPermission('attendance_manage', 'view'))
                                        <span class="d-block font-size-sm text-body">
                                            <a style="min-width:50px;" class="btn  btn--primary btn-outline-primary"
                                                href="{{ route('vendor.attendance.manage', [$lead['id']]) }}"title="{{ translate('messages.edit') }} Staff">Manage
                                                Attendance
                                            </a>
                                        </span>
                                    @endif
                                </td>
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
