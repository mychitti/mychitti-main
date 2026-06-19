@extends('layouts.vendor.app')

@section('title', 'Staff List')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

    {{-- @include('vendor-views/sub-module/partials/leave') --}}

    <div class="content container-fluid">
        @include('vendor-views.partials._hr_header')
        <!-- Page Header -->
        <div class="page-header d-flex align-items-center justify-content-between w-100 mt-3">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Leaves <span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($leaves) }}</span></h1>
            <div class="">
                @if (hasPermission('leave_manage', 'settings'))
                
                    <div class="modal fade" id="leaveModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Leave Allowance</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ route('vendor.business-settings.edit-leaves') }}" method="post">
                                        @csrf
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Casual Leaves</label>
                                            <input name="cl"
                                                value="{{ isset($store_config) ? $store_config->cl_for_employees : '' }}"
                                                type="number" class="form-control" id="exampleInputEmail1"
                                                aria-describedby="emailHelp">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Sick Leaves</label>
                                            <input name="sl"
                                                value="{{ isset($store_config) ? $store_config->sl_for_employees : '' }}"
                                                type="number" class="form-control" id="exampleInputEmail1"
                                                aria-describedby="emailHelp">
                                        </div>
                                        <div class="d-flex justify-content-end w-100 gap-2">
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Save changes</button>
                                        </div>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        @if (hasPermission('leave_manage', 'list'))
            <div class="card mb-2">
                <!-- Header -->
                <div class="card-header py-2">
                    <div class="search--button-wrapper">
                        <h5 class="card-title">Leaves List</h5>

                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":true

                               }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('sl') }}</th>
                                    <th class="border-0">Staff</th>
                                    <th class="border-0">Leave Type</th>
                                    <th class="border-0">Leave Date</th>
                                    <th class="border-0">Reason</th>
                                    <th class="border-0">Action</th>
                                </tr>
                            </thead>

                            <tbody id="set-rows">
                                @foreach ($leaves as $leave)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><a href="{{ $leave->employee?->id ? route('vendor.employee.view', ['id' => $leave->employee?->id]) : '#' }}">{{ $leave->employee?->f_name . ' ' . $leave->employee?->l_name . ' #' . $leave->employee?->id }}</a></td>
                                        <td>
                                            <div>
                                                <a href="#" class="table-rest-info" alt="view store">

                                                    <div class="info">
                                                        <div class="text--title">
                                                            @if ($leave['leave_type'] == 'CL')
                                                                {{ 'Casual Leave' }}
                                                            @elseif($leave['leave_type'] == 'SL')
                                                                {{ 'Sick Leave' }}
                                                            @elseif($leave['leave_type'] == 'HCL')
                                                                {{ 'Half Day Casual Leave' }}
                                                            @elseif($leave['leave_type'] == 'HSL')
                                                                {{ 'Half Day Sick Leave' }}
                                                            @elseif($leave['leave_type'] == 'HDF')
                                                                {{ 'Half Day LOP (first half)' }}
                                                            @elseif($leave['leave_type'] == 'HDS')
                                                                {{ 'Half Day LOP (second half)' }}
                                                            @elseif($leave['leave_type'] == 'HL')
                                                                {{ 'Holiday' }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        </td>
                                        <td>{{ $leave['leave_date'] }}</td>
                                        <td> {{ $leave['reason'] }}</td>
                                        <td>
                                            @if (hasPermission('leave_manage', 'status_change') )
                                                @if($leave['status'] == 'approved' || $leave['status'] == 'rejected')
                                                    <span class="badge badge-{{ $leave['status'] == 'approved' ? 'success' : 'danger' }}">{{ ucfirst($leave['status']) }}</span>
                                                @else
                                                    <a href="{{ route('vendor.approve-leave', ['id' => $leave['id']]) }}"
                                                        class="btn btn--primary">Approve</a>
                                                    <a href="{{ route('vendor.reject-leave', ['id' => $leave['id']]) }}"
                                                        class="btn btn--danger ">Reject</a>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>

                </div>
            </div>

            <div class="card">
                <!-- Header -->
                <div class="card-header py-2">
                    <div class="search--button-wrapper">
                        <h5 class="card-title">Manage</h5>
                        <form action="javascript:" id="search-form" class="search-form">
                            <!-- Search -->
                            @csrf
                            <div class="input-group input--group">
                                <input id="datatableSearch_" type="search" name="search" class="form-control"
                                    placeholder="Search Staff" aria-label="{{ translate('messages.search') }}" required>
                                <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>

                            </div>
                            <!-- End Search -->
                        </form>

                        <!-- End Unfold -->
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
                                            <a href="#" class="table-rest-info" alt="view store">

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
                                                $roleNm = _getWhere('employee_roles', [
                                                    'id' => $lead->employee_role_id,
                                                ]);
                                                if (isset($roleNm[0])) {
                                                    echo $roleNm[0]->name;
                                            } @endphp
                                        </div>
                                    </td>



                                    <td>
                                        @if (hasPermission('leave_manage', 'view'))
                                            <span class="d-block font-size-sm text-body">
                                                <a style="min-width:50px;" class="btn  btn--primary btn-outline-primary"
                                                    href="{{ route('vendor.leave.manage', [$lead['id']]) }}"title="{{ translate('messages.edit') }} Staff">Manage
                                                    Leave
                                                    @if (_pendingLeavesCount($lead['id']))
                                                        <span class="badge badge-danger"
                                                            style="    position: absolute;
                                            min-width: fit-content;
                                            top: -4px;
                                            border-radius: 50%;
                                            padding: 4px 8px;
                                            right: -4px;">{{ _pendingLeavesCount($lead['id']) }}</span>
                                                    @endif
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
