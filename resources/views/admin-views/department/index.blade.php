@extends('layouts.admin.app')

@section('title', translate('Department List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Departments<span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($departments) }}</span></h1>
            <div class="page-header-select-wrapper">

                {{-- <div class="select-item">
                    <select name="module_id" class="form-control js-select2-custom"
                            onchange="set_filter('{{url()->full()}}',this.value,'module_id')" title="{{translate('messages.select')}} {{translate('messages.modules')}}">
                        <option value="" {{!request('module_id') ? 'selected':''}}>{{translate('messages.all')}} {{translate('messages.modules')}}</option>
                        @foreach (\App\Models\Module::notParcel()->get() as $module)
                            <option
                                value="{{$module->id}}" {{request('module_id') == $module->id?'selected':''}}>
                                {{$module['module_name']}}
                            </option>
                        @endforeach
                    </select>
                </div> --}}
              
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row">
        <div class="card col-6">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Add Department</h5>

                </div>
            </div>
            <form class="w-100" action="{{ route('admin.staff-department.save') }}" method="post">
                @csrf
                <input type="hidden" id="lead_id" name="lead_id" value="">
                <div class="col-md-12">
                    <div class="card h-100">
                        <h4 class="m-3 mb-0">Department Details</h4>
                        <div class="card-body row">
                            <div class="form-row col-12">
                                <label for="exampleInputEmail1">Department Title</label>
                                <input type="text" name="title" placeholder="Department Title" class="form-control">
                            </div>

                            <div class="form-row col-12">
                                <label for="inputState">Status</label>
                                <select name="status" id="inputState" class="form-control">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="form-row d-flex align-items-end col-12 mt-2">
                                <button class="btn btn--primary ">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- Card -->
        <div class="card  col-6">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Department List</h5>
                    <form action="javascript:" id="search-form" class="search-form">
                        <!-- Search -->
                        @csrf
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control"
                                placeholder="Search Department" aria-label="{{ translate('messages.search') }}" required>
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
                            <th class="border-0">Title</th>
                            <th class="border-0">Status</th>
                            <th class="border-0">Action</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($departments as $lead)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div>
                                        <a href="javascript:;" class="table-rest-info" alt="view store">

                                            <div class="info">
                                                <div class="text--title">
                                                    {{ $lead->title }}
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </td>
                              
                            
                                <td>
                                    <form id="status-form{{ $lead->id }}" method="post"
                                        action="{{ route('admin.staff-department.status-change') }}">
                                        @csrf
                                        <input type="hidden" name="d_id" value="{{ $lead->id }}">
                                        <select name="status" class="form-control js-select2-custom"
                                            onchange="document.getElementById('status-form{{ $lead->id }}').submit()">
                                            <option value="1" {{ $lead->status ? 'selected' : '' }}>Active
                                            </option>
                                            <option value="0" {{ !$lead->status ? 'selected' : '' }}>Inactive
                                            </option>
                                           
                                        </select>
                                    </form>
                                </td>
                             
                           
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a style="min-width:50px;" class="btn  btn--danger btn-outline-danger"
                                            href="{{ route('admin.staff-department.delete', [$lead->id]) }}"
                                            title="{{ translate('messages.delete') }} Department"><i
                                                class="tio-delete-outlined"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if (count($departments))
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
        </div></div>
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
