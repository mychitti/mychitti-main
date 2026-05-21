@extends('layouts.vendor.app')

@section('title', 'Confirmed Leads')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Confirmed Leads<span
                    class="badge badge-soft-dark ml-2" id="itemCount">{{ count($confirmed) }}</span></h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->


        <!-- Resturent Card Wrapper -->
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-1">
                    <h4 class="title">{{ $count['total_confirmed'] }}</h4>
                    <span class="subtitle">Total Confirmed Services</span>
                    <img class="resturant-icon" src="{{ asset('/public/assets/admin/img/dashboard/dd.png') }}"
                        alt="store">
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-2">
                    <h4 class="title">{{ $count['total_assigned'] }}</h4>
                    <span class="subtitle">Assigned Services</span>
                    <img class="resturant-icon" src="{{ asset('/public/assets/admin/img/dashboard/dd.png') }}"
                        alt="store">
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-3">
                    <h4 class="title">{{ $count['total_unassigned'] }}</h4>
                    <span class="subtitle">Unassigned Services</span>
                    <img class="resturant-icon" src="{{ asset('/public/assets/admin/img/dashboard/dd.png') }}"
                        alt="store">
                </div>
            </div>

        </div>
        <!-- Resturent Card Wrapper -->

        <form action="">
            <ul class="transaction--information justify-content-start">
                <style>
                    .selection {
                        width: 100%;
                    }
                </style>
                <li class="text--info">
                    <div class="form-group date_wise" style="margin-bottom:0px !important;">
                        <label class="input-label" for="exampleFormControlSelect1">Status<span
                                class="input-label-secondary"></span></label>
                        <select name="status" class="form-control js-select2-custom">
                            <option {{ $filter_status == 'All' ? 'selected' : '' }} value="All">All</option>
                            <option {{ $filter_status == 'Assigned' ? 'selected' : '' }} value="Assigned">Assigned</option>
                            <option {{ $filter_status == 'Unassigned' ? 'selected' : '' }} value="Unassigned">Unassigned
                            </option>
                        </select>
                    </div>
                </li>
                <li>
                    <button type="submit" class="btn btn--primary btn-outline-primary">Filter</button>
                </li>
            </ul>
        </form>


        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">List</h5>

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
                            <th class="border-0">Service Info</th>
                            <th class="border-0">Confirmed at</th>
                            <th class="border-0">Status</th>
                            <th class="border-0">Action</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($confirmed as $key => $conf)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <div>
                                        <a href="" style="cursor:default;" class="table-rest-info" alt="view store">
                                            <img class="img--60 circle"
                                                onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                src="{{ asset('storage/app/public/product') }}/{{ $conf->item_image }}">
                                            <div class="info">
                                                <div class="text--title">
                                                    {{ $conf->item_name }}
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        {{ _formatted_datetime($conf->confirmed_at) }}
                                    </span>
                                </td>

                                <td>
                                    @if ($conf->assigned_status == 'Unassigned')
                                        <a style="width:fit-content; padding: 2px 8px !important" type="button"
                                            data-toggle="modal"
                                            data-target="{{ \App\CentralLogics\Helpers::existingVendorPlan() ? '#exampleModal' . $key : '#subsModal' }}"
                                            class="btn action-btn btn--danger btn-outline-danger"
                                            title="{{ translate('messages.view') }}">Unassigned
                                        </a>
                                    @else
                                        <a style="width:fit-content; padding: 2px 8px !important" type="button"
                                            data-toggle="modal"
                                            data-target="{{ \App\CentralLogics\Helpers::existingVendorPlan() ? '#exampleModal' . $key : '#subsModal' }}"
                                            class="btn action-btn btn--primary btn-outline-primary"
                                            title="{{ translate('messages.view') }}">Assigned
                                        </a>
                                    @endif

                                </td>

                                <td>
                                    <div class="btn--container justify-content-center">
                                        @if (\App\CentralLogics\Helpers::existingVendorPlan())
                                            <a href="{{ route('vendor.service.lead-details', [$conf->service_id]) }}"
                                                class="btn action-btn btn--warning btn-outline-warning"
                                                title="{{ translate('messages.view') }}"><i
                                                    class="tio-visible-outlined"></i>
                                            </a>
                                            <a onclick="cancelLead({{ $conf->service_id }})"
                                                style="padding: 0px 20px !important; width: fit-content;"
                                                class="btn action-btn btn--danger btn-outline-danger"
                                                title="{{ translate('messages.view') }}">Cancel
                                            </a>
                                        @else
                                            <a type="button"  data-target="#subsModal" data-toggle="modal"
                                                class="btn action-btn btn--warning btn-outline-warning"
                                                title="{{ translate('messages.view') }}"><i
                                                    class="tio-visible-outlined"></i>
                                            </a>
                                            <a type="button"  data-target="#subsModal" data-toggle="modal"
                                                style="padding: 0px 20px !important; width: fit-content;"
                                                class="btn action-btn btn--danger btn-outline-danger"
                                                title="{{ translate('messages.view') }}">Cancel
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="exampleModal{{ $key }}" tabindex="-1" role="dialog"
                                aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="indicator"></div>
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel">Assign Staff to Service </h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            @if ($conf->assigned_status == 'Unassigned')
                                                <form action="{{ route('vendor.service.save-assignment') }}"
                                                    method="post">
                                                    @csrf
                                                    <input type="hidden" name="service_id"
                                                        value = "{{ $conf->service_id }}" hidden>
                                                    <input type="hidden" name="id" value = "{{ $conf->id }}"
                                                        hidden>

                                                    <div class="form-group">
                                                        <div class="custom-file">
                                                            <label class="form-label" for="staff_id">Assign To</label><br> 

                                                                 <select name="staff_id" id="staff_id"
                                                                class="js-select2-custom form-control">
                                                                <option></option>
                                                                @foreach ($allStaff as $staff)
                                                                    <option value="{{ $staff->id }}">
                                                                        {{ $staff->f_name . ' ' . $staff->l_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group mb-0">
                                                        <input class="btn btn--primary text-white" type="submit"
                                                            value="Assign">
                                                    </div>
                                                </form>
                                            @else
                                                <span style="font-size: 17px">
                                                    <b>Assigned To : </b>
                                                    @php
                                                        $empInfo = _getWhereOne('vendor_employees', [
                                                            'id' => $conf->assigned_to,
                                                        ]);
                                                    @endphp

                                                    @if ($empInfo)
                                                        <span>
                                                            {{ $empInfo->f_name . ' ' . $empInfo->l_name . ' #' . $conf->assigned_to }}
                                                            ({{ !$conf->accepted_by_staff ? 'Acceptance Pending' : ($conf->accepted_by_staff == 2 ? 'Rejected' : 'Accepted') }})
                                                        </span>
                                                    @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>

                <hr>

                <div class="page-area">
                </div>
                @if (!count($confirmed))
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
        function cancelLead(serviceId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to cancel this lead?',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: 'No',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{ route('vendor.service.cancel') }}',
                        data: {
                            service_id: serviceId
                        },
                        beforeSend: function() {
                            $('#loading').show();
                        },
                        success: function(data) {
                            if (data.status) {
                                toastr.success(data.message);
                            } else {
                                toastr.error(data.message);
                            }
                            setTimeout(() => {

                                window.location.reload()
                            }, 1000)
                        },
                        complete: function() {
                            $('#loading').hide();
                        },
                    });
                }
            })
        }

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

    <script>
        $('#search-form').on('submit', function() {
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.store.search') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#set-rows').html(data.view);
                    $('#itemCount').html(data.total);
                    $('.page-area').hide();
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        });
    </script>
@endpush
