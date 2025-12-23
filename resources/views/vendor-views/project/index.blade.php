@extends('layouts.vendor.app')

@section('title', translate('Project List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/project-task.css') }}">
    <style>
        .lead-stats {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            line-height: 1.6;
            border: 1px solid #e3e6ea;
            width: 100%;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }

        .stat-row .label {
            font-weight: 600;
            color: #333;
        }

        .stat-row .value {
            font-weight: 500;
            color: #555;
        }
    </style>

    <!--jquery-->
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Projects<span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($projects) }}</span></h1>
            <div class="page-header-select-wrapper">

                {{-- <div class="select-item">
                    <select name="module_id" class="form-control js-select2-custom"
                            onchange="set_filter('{{url()->full()}}',this.valuea,'module_id')" title="{{translate('messages.select')}} {{translate('messages.modules')}}">
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

            @if (!$empId)
                <div class="col-12">
                    <div class="row col-12 align-items-end">
                        <div class="col-md-3">
                            <label for="prog_status">Status</label>
                            @php $statuses = ['New', 'Open','In Progress' ,  'Completed', 'Cancelled', 'On Hold'  ]; @endphp
                            <form action="">

                                <select name="status" id="prog_status" data-placeholder="Status"
                                    class="form-control js-select2-custom" onchange="this.form.submit()">
                                    <option value="" selected disabled>--select--</option>
                                    @foreach ($statuses as $key => $value)
                                        <option {{ request('status') == $value ? 'selected' : '' }}
                                            value="{{ $value }}">
                                            {{ $value }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        <div class="col-md-3">
                            <label for="project_type">Type</label>
                            @php $types = ['Technical', 'Organizational','Economical' ,  'Social', 'Mixed' ]; @endphp
                            <form action="">

                                <select name="type" id="project_type" data-placeholder="Type"
                                    onchange="this.form.submit()" class="form-control js-select2-custom">
                                    <option value=""></option>
                                    @foreach ($types as $key => $value)
                                        <option {{ request('type') == $value ? 'selected' : '' }}
                                            value="{{ $value }}">
                                            {{ $value }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        <div class="col-md-3">
                            <label for="project_size">Project Size</label>
                            @php $sizes = ['Minor', 'Small','Medium' ,  'Large']; @endphp
                            <form action="">

                                <select id="project_size" data-placeholder="Project Size" name="size"
                                    onchange="this.form.submit()" class="form-control js-select2-custom">
                                    <option value=""></option>
                                    @foreach ($sizes as $key => $value)
                                        <option {{ request('type') == $value ? 'selected' : '' }}
                                            value="{{ strtolower($value) }}">
                                            {{ $value }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        <div class="col-md-2">

                            <form action="" class="date-range-form ">
                                <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning"
                                    type="button" data-toggle="modal"
                                    data-target="#dateRangeModal">{{ translate($preset) }}</button>
                                @include('vendor-views/form_modals/date_range')

                            </form>
                        </div>
                    </div>
                </div>
            @endif



            <!-- Card -->
            <div class="card  col-12 my-3">
                <!-- Header -->
                <div class="card-header py-2">
                    <div class="search--button-wrapper">
                        <h5 class="card-title"></h5>
                        @if (!$empId)
                            <form action="" id="search-form" class="search-form">
                                <!-- Search -->
                                <div class="input-group input--group">
                                    <input id="datatableSearch_" type="search" name="search" class="form-control"
                                        placeholder="Search Project" aria-label="{{ translate('messages.search') }}"
                                        value="{{ request('search') }}">
                                    <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                                </div>
                                <!-- End Search -->
                            </form>
                        @endif

                        <!-- End Unfold -->
                    </div>
                </div>
                <!-- End Header -->
                <div class="ptask-wrapper">
                    <div class="ptask-container">
                        <div class="d-flex justify-content-between">

                            {{-- @if (hasPermission('project_task', 'add'))
                                <button class="btn btn-primary">Add Task</button>
                            @endif --}}
                        </div>

                        <!-- Tasks Grid -->
                        <div class="ptask-grid">
                            @php $common_statuses = ['completed', 'in-progress', 'new', 'cancelled']; @endphp
                            @foreach ($projects as $key => $lead)
                                @php
                                    $slug_status = \Illuminate\Support\Str::slug($lead->progress_status);
                                $staus_class = in_array($slug_status, $common_statuses) ? $slug_status : 'other'; @endphp
                                <div class="ptask-card" style="cursor:pointer;"
                                    onclick="handleClick('{{ hasPermission('project', 'view') ? route('vendor.project.details', [$lead->id]) : '#' }}', event)">
                                    @if (hasAnyPermission(['project_task.list', 'project.view', 'project.edit', 'project.delete']))
                                        <div class="dropdown">
                                            <button class="btn p-1 dropdown-toggle"
                                                style="position: absolute; right: -5px; top: -22px;" type="button"
                                                data-toggle="dropdown" aria-expanded="false">
                                                <img style="width: 24px; filter: contrast(0)"
                                                    src="{{ asset('storage/app/public/util/10025520.png') }}"
                                                    alt="action" />
                                            </button>
                                            <div class="dropdown-menu">
                                                @if (hasPermission('project', 'view'))
                                                    <a href="{{ route('vendor.project.details', [$lead->id]) }}"
                                                        class="dropdown-item text-success" title="view">
                                                        <i class="tio-visible-outlined"></i>
                                                        View
                                                    </a>
                                                @endif
                                                @if (hasPermission('project_task', 'list'))
                                                    <a href="{{ route('vendor.project.task.list', [$lead->id]) }}"
                                                        class="dropdown-item text-warning" title="tasks">
                                                        <i class="tio-notebook-bookmarked"></i>
                                                        Tasks
                                                    </a>
                                                @endif
                                                @if (hasPermission('project', 'edit'))
                                                    @php
                                                        $task_closed = in_array($lead->status, [
                                                            'Completed',
                                                            'Cancelled',
                                                    ]); @endphp
                                                    @if (
                                                        !$task_closed &&
                                                            hasPermission('project_task', 'edit') &&
                                                            ($lead->employee_id == \App\CentralLogics\Helpers::get_loggedin_user()->id ||
                                                                hasPermission('project_task', 'edit_others')))
                                                        <a href="{{ route('vendor.project.edit', [$lead->id]) }}"
                                                            class="dropdown-item text-success" title="Edit">
                                                            <i class="tio-edit"></i>
                                                            Edit
                                                        </a>
                                                    @endif
                                                @endif
                                                @if (hasPermission('project', 'delete'))
                                                    <a class="dropdown-item  text-danger form-alert " href="javascript:"
                                                        data-id="task-{{ $lead['id'] }}"
                                                        data-message="If you delete this project, all its tasks, milestones and other data will be deleted ?"
                                                        title="{{ translate('messages.delete_project') }}"><i
                                                            class="tio-delete-outlined"></i>
                                                        Delete
                                                    </a>
                                                    <form action="{{ route('vendor.project.delete', [$lead->id]) }}"
                                                        method="get" id="task-{{ $lead['id'] }}">
                                                        @csrf @method('get')
                                                    </form>
                                                @endif

                                            </div>
                                        </div>
                                    @endif
                                    <div class="ptask-card-top">

                                    </div>
                                    <div class="ptask-title one-line-ellipsis">{{ ucfirst($lead->project_title) }}
                                    </div>
                                    <div class="lead-stats mb-2">

                                        <div class="stat-row">
                                            <span class="label">Deadline:</span>
                                            <span class="value">{{ $lead->end_date }}</span>
                                        </div>

                                        <div class="stat-row">
                                            <span class="label">Total Tasks:</span>
                                            <span class="value">{{ $lead->tasks->count() }}</span>
                                        </div>

                                        <div class="stat-row">
                                            <span class="label">Pending Tasks:</span>
                                            <span class="value">
                                                {{ $lead->tasks->where('status', '!=', 'Completed')->count() }}
                                            </span>
                                        </div>

                                        <div class="stat-row">
                                            <span class="label">Completed Tasks:</span>
                                            <span class="value">
                                                {{ $lead->tasks->where('status', 'Completed')->count() }}
                                            </span>
                                        </div>

                                        <div class="stat-row">
                                            <span class="label">Team Members:</span>
                                            <span class="value">{{ $lead->teamMembers->count() }}</span>
                                        </div>

                                    </div>

                                    <div class="ptask-meta">
                                        <div class="ptask-meta-item">
                                            <i class="fas fa-user"></i>
                                            @php
                                                if ($lead->project_manager) {
                                                    $empInfo = _getWhere('vendor_employees', [
                                                        'id' => $lead->project_manager,
                                                    ]);
                                                    if ($empInfo[0]) {
                                                        echo $empInfo[0]->f_name .
                                                            ' ' .
                                                            $empInfo[0]->l_name .
                                                            ' (ID: ' .
                                                            $lead->project_manager .
                                                            ')';
                                                    }
                                            } @endphp
                                        </div>
                                        <div class="ptask-meta-item">
                                            <i class="fas fa-calendar"></i> {{ _formatted_date($lead->created_at) }}
                                        </div>
                                    </div>
                                    <div class="ptask-actions align-items-center justify-content-between">
                                        <div class="ptask-actions align-items-center">
                                            <span
                                                class="ptask-priority ptask-priority-medium">{{ $lead->prog_percent ?? 0 }}%</span>
                                            @if ($lead->progress_status == 'Completed')
                                                <span
                                                    class="ptask-status-badge ptask-status-completed ">{{ $lead->progress_status }}</span>
                                            @elseif ($lead->progress_status == 'Cancelled')
                                                <span
                                                    class="ptask-status-badge ptask-status-cancelled ">{{ $lead->progress_status }}</span>
                                            @elseif (hasPermission('project', 'status_change'))
                                                <form class="status_change_form"
                                                    action="{{ route('vendor.project.progress-status-change', [$lead->id]) }}">
                                                    <select name="status" id="" data-placeholder="Status"
                                                        onchange="this.form.submit()" class="js-select2-custom">
                                                        <option value=""></option>
                                                        <option {{ $lead->progress_status == 'New' ? 'selected' : '' }}
                                                            value="New">New</option>
                                                        <option {{ $lead->progress_status == 'Open' ? 'selected' : '' }}
                                                            value="Open">Open</option>
                                                        <option
                                                            {{ $lead->progress_status == 'In Progress' ? 'selected' : '' }}
                                                            value="In Progress">In Progress</option>
                                                        <option
                                                            {{ $lead->progress_status == 'Completed' ? 'selected' : '' }}
                                                            value="Completed">Completed</option>
                                                        <option
                                                            {{ $lead->progress_status == 'Cancelled' ? 'selected' : '' }}
                                                            value="Cancelled">Cancelled</option>
                                                        <option {{ $lead->progress_status == 'On Hold' ? 'selected' : '' }}
                                                            value="On Hold">On Hold</option>
                                                    </select>
                                                </form>
                                            @endif
                                        </div>

                                        <span
                                            class="ptask-priority ptask-priority-{{ $lead->priority }}">{{ ucfirst($lead->priority) }}
                                            Priority</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if (!count($projects))
                            <div class="w-100 d-flex align-items-center justify-content-center flex-column">
                                No Tasks Yet..
                            </div>
                        @endif
                        <!-- Statistics -->
                    </div>
                </div>

                <!-- Table -->
                {{-- <div class="table-responsive datatable-custom">
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
                                <th class="border-0">Project Manager</th>
                                <th class="border-0">Client</th>
                                <th class="border-0">Progress Status</th>
                                <th class="border-0">Project Date</th>
                                <th class="border-0">Cost Est.</th>
                                <th class="border-0">Action</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($projects as $lead)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div>
                                            <a href="{{ route('vendor.project.details', [$lead->id]) }}"
                                                class="table-rest-info" alt="view store">

                                                <div class="info">
                                                    <div class="text--title">
                                                        {{ $lead->project_title }}
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <a href="javascript:;" class="" alt="view store">

                                                <div class="info">
                                                    <div class="text--title">
                                                        @php
                                                            if ($lead->project_manager) {
                                                                $empInfo = _getWhere('vendor_employees', [
                                                                    'id' => $lead->project_manager,
                                                                ]);
                                                                if ($empInfo[0]) {
                                                                    echo $empInfo[0]->f_name .
                                                                        ' ' .
                                                                        $empInfo[0]->l_name;
                                                                }
                                                        } @endphp

                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </td>
                                    <td>{{ $lead->client?->f_name }}</td>
                                    <td>
                                        <div>
                                            <a href="javascript:;" class="" alt="view store">

                                                <div class="info">
                                                    <div class="text--title">
                                                        {{ $lead->progress_status }}
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <a href="javascript:;" class="" alt="view store">

                                                <div class="info">
                                                    <div class="text--title">
                                                        {{ $lead->start_date . ' to ' . $lead->end_date }}
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <a href="javascript:;" class="" alt="view store">

                                                <div class="info">
                                                    <div class="text--title">
                                                        {{ \App\CentralLogics\Helpers::format_currency($lead->cost) }}
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn p-1 dropdown-toggle"
                                                style="position: absolute; right: -5px; top: -22px;" type="button"
                                                data-toggle="dropdown" aria-expanded="false">
                                                <img style="width: 24px; filter: contrast(0)"
                                                    src="{{ asset('storage/app/public/util/10025520.png') }}"
                                                    alt="action" />
                                            </button>
                                            <div class="dropdown-menu">

                                                <a href="{{ route('vendor.project.details', [$lead->id]) }}"
                                                    class="dropdown-item text-success" title="view">
                                                    <i class="tio-visible-outlined"></i>
                                                    View
                                                </a>
                                                @if (hasPermission('project_task', 'list'))
                                                    <a href="{{ route('vendor.project.task.list', [$lead->id]) }}"
                                                        class="dropdown-item text-warning" title="tasks">
                                                        <i class="tio-notebook-bookmarked"></i>
                                                        Tasks
                                                    </a>
                                                @endif
                                                <a href="{{ route('vendor.project.edit', [$lead->id]) }}"
                                                    class="dropdown-item text-success" title="Edit">
                                                    <i class="tio-edit"></i>
                                                    Edit
                                                </a>

                                                <a class="dropdown-item  text-danger form-alert " href="javascript:"
                                                    data-id="task-{{ $lead['id'] }}"
                                                    data-message="{{ translate('Want_to_delete_this_project_?') }}"
                                                    title="{{ translate('messages.delete_project') }}"><i
                                                        class="tio-delete-outlined"></i>
                                                    Delete
                                                </a>
                                                <form action="{{ route('vendor.project.delete', [$lead->id]) }}"
                                                    method="get" id="task-{{ $lead['id'] }}">
                                                    @csrf @method('get')
                                                </form>

                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($projects))
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
                </div> --}}
                <!-- End Table -->
            </div>
        </div>
        <!-- End Card -->
    </div>

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
    </script>
    <script>
        function handleClick(url, e) {
            if ($(e.target).closest('.status_change_form, .dropdown-menu, .dropdown-toggle, button, a')
                .length) {} else {
                window.location.href = url;
            }
        }
    </script>
@endpush
