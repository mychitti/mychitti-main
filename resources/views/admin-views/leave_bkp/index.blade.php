@extends('layouts.admin.app')

@section('title', 'Employee List')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Employee <span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($staff) }}</span></h1>
            <div class="page-header-select-wrapper">

                @if (!isset(auth('vendor')->user()->zone_id))
                    <div class="select-item">
                        <select name="zone_id" class="form-control js-select2-custom"
                            onchange="set_filter('{{ url()->full() }}',this.value,'zone_id')">
                            <option value="" {{ !request('zone_id') ? 'selected' : '' }}>
                                {{ translate('messages.All_Zones') }}</option>
                            @foreach (\App\Models\Zone::orderBy('name')->get() as $z)
                                <option value="{{ $z['id'] }}"
                                    {{ isset($zone) && $zone->id == $z['id'] ? 'selected' : '' }}>
                                    {{ $z['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>
        <!-- End Page Header -->



        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Employee List</h5>
                    <form action="javascript:" id="search-form" class="search-form">
                        <!-- Search -->
                        @csrf
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control"
                                placeholder="Search Employee" aria-label="{{ translate('messages.search') }}" required>
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
                            <th class="border-0">Mobile</th>
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
                                                    {{ $lead->email }}
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                       {{ $lead->phone }}
                                    </span>

                                </td>
                                <td>
                                    <div>
                                       @php
                                    $roleNm = _getWhere('admin_roles', ['id' => $lead->role_id]);
                                    if (isset($roleNm[0])) {
                                        echo $roleNm[0]->name;
                                } @endphp
                                    </div>
                                </td>
                        
                               

                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        <a style="min-width:50px;" class="btn  btn--primary btn-outline-primary"
                                        href="{{route('admin.users.leave.manage',[$lead['id']])}}"title="{{translate('messages.edit')}} Employee">Manage Leave
                                        </a>
                                    </span>
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
