@extends('layouts.admin.app')

@section('title', 'Lead List')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Leads</h1>
            <div class="page-header-select-wrapper">
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row">
            <!-- Card -->
            <div class="card col-12">
                <!-- Header -->
                <div class="card-header py-2">
                    <h5 class="card-title">Leads</h5>
                    <form action="" method="GET" class="date-range-form">
                        @include('admin-views.form_modals.date_range')
                        <div class="d-flex flex-wrap align-items-end" style="gap:12px">
                            <!-- Search -->
                            <div class="form-group mb-0">
                                {{-- <label class="input-label">Search</label> --}}
                                <input type="text" name="search" class="form-control form-control-sm"
                                    style="width:200px" placeholder="Lead ID / Service name" value="{{ $search }}">
                            </div>
                            <!-- Date Range -->
                            <div class="form-group mb-0">
                                {{-- <label class="input-label d-block">Date Range</label> --}}
                                <button style="white-space:nowrap" class="btn  btn-outline-warning" type="button"
                                    data-toggle="modal" data-target="#dateRangeModal">
                                    {{ translate($preset) }}
                                </button>
                            </div>
                            <!-- Zone -->
                            <div class="form-group mb-0">
                                {{-- <label class="input-label">Zone</label> --}}
                                <select id="filter_zone" name="zone_id" class="js-select2-custom form-control form-control-sm" style="width:180px">
                                    <option value="">All Zones</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}" {{ $zone_id == $zone->id ? 'selected' : '' }}>
                                            {{ $zone->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Status -->
                            <div class="form-group mb-0">
                                {{-- <label class="input-label">Status</label> --}}
                                <select id="filter_status" name="type" class="js-select2-custom form-control form-control-sm" style="width:200px">
                                    <option value="all" {{ $type == 'all' ? 'selected' : '' }}>All</option>
                                    <option value="new" {{ $type == 'new' ? 'selected' : '' }}>New</option>
                                    <option value="Confirmation Request Sent" {{ $type == 'Confirmation Request Sent' ? 'selected' : '' }}>Confirmation Request Sent</option>
                                    <option value="Confirmed" {{ $type == 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
                                    <option value="Completed" {{ $type == 'Completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="Cancelled" {{ $type == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                            <!-- Actions -->
                            <div class="form-group mb-0">
                                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                                <a href="{{ route('admin.service.lead-list') }}" class="btn btn-sm btn-secondary ml-1">Reset</a>
                            </div>
                        </div>
                    </form>
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
                                <th class="border-0">{{ translate('lead_id') }}</th>
                                <th class="border-0">Service </th>
                                {{-- <th class="border-0">Vendor </th> --}}
                                <th class="border-0">Status</th>
                                <th class="border-0">Requested At</th>
                                <th class="text-center border-0">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            @foreach ($leads as $lead)
                                <tr>
                                    <td>{{ $lead->service_id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center"><img class="img--60 mx-1"
                                                onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                src="{{ asset('storage/app/public/product') }}/{{ $lead->image }}">
                                            <div class="info ">
                                                <div class="text--title fw-bold">
                                                    {{ $lead->name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    {{-- <td>
                                        <div class="d-flex align-items-center">
                                            <img class="img--60 mx-1"
                                                onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                src="{{ asset('storage/app/public/store') }}/{{ $lead->logo }}">
                                            <div class="info ">
                                                <div class="text--title fw-bold">
                                                    {{ $lead->store_name }}
                                                </div>
                                            </div>
                                        </div>

                                    </td> --}}
                                    <td>
                                    @if(isset($lead->current_status ))
                                        @if ($lead->current_status == '')
                                            Accepted
                                        @elseif($lead->current_status == 'Confirmed' && $lead->status )
                                            {{ $lead->status }}
                                        @else
                                            {{ $lead->current_status }}
                                        @endif
                                        @else
                                        New Enquiry
                                        @endif
                                    </td>
                                    <td>
                                        {{ $lead->enquiry_date }}
                                    </td>
                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a style="min-width:50px;"
                                                class="btn action-btn btn--primary btn-outline-primary"
                                                href="{{ route('admin.service.lead-detail', [$lead->service_id]) }}"
                                                title="Edit Charges"><i class="tio-visible-outlined"></i>
                                            </a>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($leads))
                        <hr>
                        {{ $leads->links() }}
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
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    @include('admin-views.js.date_range')
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
                $.HSCore.components.HSSelect2.init($(this));
            });

            $('#filter_zone, #filter_status').on('change', function() {
                $(this).closest('form').submit();
            });
        });
    </script>
@endpush
