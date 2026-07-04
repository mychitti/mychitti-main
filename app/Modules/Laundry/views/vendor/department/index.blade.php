@extends('layouts.vendor.app')

@section('title', translate('Department List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        @include('vendor-views.partials._hr_header')
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Departments<span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($departments) }}</span></h1>
        </div>
        @php $both = hasPermission('staff_department', 'add') && hasPermission('staff_department', 'list') ? 1 : 0;@endphp
        <div class="row">
            @if (hasPermission('staff_department', 'add'))
                <div class="card my-1 {{ $both ? 'col-md-6' : 'col-md-12' }} p-1">
                    <div class="card-header py-2">
                        <div class="search--button-wrapper">
                            <h5 class="card-title">Add Department</h5>
                        </div>
                    </div>
                    <form class="custom_ajax_form w-100" action="{{ route('vendor.staff-department.store') }}" method="post">
                        @csrf
                        <div class="col-md-12 p-0">
                            <div class="card h-100">
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
                                        <button class="btn btn--primary">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            @endif
            @if (hasPermission('staff_department', 'status_change'))
                <div class="card my-1 {{ $both ? 'col-md-6' : 'col-md-12' }} p-1">
                    <div class="card-header py-2">
                        <div class="search--button-wrapper">
                            <h5 class="card-title">Department List</h5>
                            <form action="javascript:" id="search-form" class="search-form">
                                @csrf
                                <div class="input-group input--group">
                                    <input id="datatableSearch_" type="search" name="search" class="form-control"
                                        placeholder="Search Department" aria-label="{{ translate('messages.search') }}" required>
                                    <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            data-hs-datatables-options='{"order": [], "orderCellsTop": true, "paging":false}'>
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
                                            <div class="text--title">{{ $lead->title }}</div>
                                        </td>
                                        <td>
                                            @if (hasPermission('staff_department', 'status_change'))
                                                <form id="status-form{{ $lead->id }}" method="post"
                                                    action="{{ route('vendor.staff-department.status-change') }}">
                                                    @csrf
                                                    <input type="hidden" name="d_id" value="{{ $lead->id }}">
                                                    <select name="status" class="form-control js-select2-custom"
                                                        onchange="document.getElementById('status-form{{ $lead->id }}').submit()">
                                                        <option value="1" {{ $lead->status ? 'selected' : '' }}>Active</option>
                                                        <option value="0" {{ !$lead->status ? 'selected' : '' }}>Inactive</option>
                                                    </select>
                                                </form>
                                            @else
                                                {{ $lead->status ? 'Active' : 'Inactive' }}
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn--container justify-content-center">
                                                @if (hasPermission('staff_department', 'delete'))
                                                    <a style="min-width:50px;" class="btn btn--danger btn-outline-danger"
                                                        href="{{ route('vendor.staff-department.delete', [$lead->id]) }}"
                                                        title="{{ translate('messages.delete') }} Department">
                                                        <i class="tio-delete-outlined"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if (!count($departments))
                            <div class="empty--data">
                                <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                                <h5>{{ translate('no_data_found') }}</h5>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(document).on('ready', function() {
            $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));
            $('.js-select2-custom').each(function() {
                $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>
@endpush
