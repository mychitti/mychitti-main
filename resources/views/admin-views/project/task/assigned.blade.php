@extends('layouts.admin.app')

@section('title', translate('Tasks List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @media (max-width:550px) {

            .mini-date {
                width: 30px;
                padding: 5px;
            }
        }

        .word-wrap {
            white-space: normal;
            word-wrap: break-word;
            word-break: break-word;
        }
    </style>
    <style>
        .status_dot {
            height: 13px;
            width: 13px;
            border-radius: 50%;
        }

        .New {
            background-color: #00d3d3;
        }

        .Completed {
            background-color: #22c322;
        }

        .Cancelled {
            background-color: #f32a2a;
        }

        .progress-container {
            width: 50px;
            margin: 15px auto;
            font-size: 14px;
            text-align: center;
        }

        .progress-text {
            margin-bottom: 5px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #eee;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 8px;
            transition: width 0.4s ease;
        }
    </style>
    <style>
        .repair-card-container {
            cursor: pointer;
            border-radius: 14px;
            padding: 13px;
            width: 100%;
            max-width: 282px;
            box-shadow: 0 10px 12px rgb(0 0 0 / 17%);
            position: relative;
            height: fit-content;
        }

        .card-ring {
            position: absolute;
            width: 20px;
            height: 45px;
            top: -18px;
        }

        /* Rounded bottom */
        .card-ring::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: #b8c4cf;
            background: radial-gradient(circle at 30% 30%, #d8dfe5, #a8b4bf);
            bottom: 9px;
            left: -5px;
            border-radius: 50%;
        }

        /* Main cylinder body */
        .card-ring::after {
            content: '';
            position: absolute;
            width: 6px;
            height: 27px;
            background: #a8b4bf;
            background: linear-gradient(90deg, #98a4af 0%, #c8d0d8 50%, #98a4af 100%);
            top: 0;
            left: 2px;
            border-radius: 8px;
        }

        .ring-left {
            left: 70px;
        }

        .ring-right {
            right: 70px;
        }

        .repair-card-title {
            text-align: center;
            margin-top: 7px;
            font-size: 13px;
            font-weight: bold;
            color: black;
            margin-bottom: 7px;
        }

        .repair-card-content {
            background: white;
            border-radius: 12px;
            padding: 15px;
            height: 80%;
            min-height: 200px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .info-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .info-value {
            color: #34495e;
        }

        .status-badge {
            color: #2c3e50;
            padding: 6px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            display: inline-block;
        }

        .status-options {
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-size: 12px;
            color: #7f8c8d;
        }

        .menu-dropdown {
            color: #2c3e50;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            position: relative;
        }

        .menu-options {
            display: flex;
            flex-direction: column;
            gap: 3px;
            font-size: 12px;
            color: #2c3e50;
            margin-top: 5px;
        }

        .custom-action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .custom-action-btn {
            border: none;
            padding: 4px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 11px;
            cursor: pointer;
            transition: transform 0.2s;
            min-width: 71px;
            white-space: nowrap;
            background: linear-gradient(135deg, #ffb657ff 0%, #ff964bff 100%);
        }

        .custom-action-btn:hover {
            transform: translateY(-2px);
        }

        .progress-section {
            margin-top: 20px;
        }

        .progress-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .progress-bar-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .custom-progress {
            flex: 1;
            height: 12px;
            background: #ecf0f1;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }

        .custom-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db 0%, #5dade2 100%);
            border-radius: 15px;
            transition: width 0.3s ease;
        }

        .progress-percentage {
            font-size: 15px;
            font-weight: bold;
            color: #2c3e50;
        }

        .theme-pink-color {
            background: linear-gradient(135deg, #ffcee0ff 0%, #ffb3cbff 100%);
        }

        .theme-skyblue-color {
            background: linear-gradient(135deg, #b6e8ffff 0%, #a6e3ffff 100%);
        }

        .theme-purple-color {
            background: linear-gradient(135deg, #ebc4ffff 0%, #e7aeffff 100%);
        }

        .theme-grey-color {
            background: linear-gradient(135deg, #f2d8ffff 0%, #e9beffff 100%);
        }

        .theme-green-color {
            background: linear-gradient(135deg, #b5f1b8ff 0%, #9ced9fff 100%);
        }

        /* Orange theme */
        .repair-card-container.New-color,
        .repair-card-container.New-color .status-badge,
        .repair-card-container.New-color .menu-dropdown {
            background: linear-gradient(135deg, #ffc374ff 0%, #ffb078ff 100%);
        }

        /* Orange theme */
        .repair-card-container.other-color,
        .repair-card-container.other-color .status-badge,
        .repair-card-container.other-color .menu-dropdown {
            background: linear-gradient(135deg, #fcffb2ff 0%, #f2ff78ff 100%);
        }

        /* Yellow theme */
        .repair-card-container.Cancelled-color,
        .repair-card-container.Cancelled-color .status-badge,
        .repair-card-container.Cancelled-color .menu-dropdown {
            background: linear-gradient(135deg, #ffcee0ff 0%, #ffb3cbff 100%);
        }

        /* Sky Blue theme */
        .repair-card-container.Allotted-color,
        .repair-card-container.Allotted-color .status-badge,
        .repair-card-container.Allotted-color .menu-dropdown {
            background: linear-gradient(135deg, #b6e8ffff 0%, #a6e3ffff 100%);
        }

        /* Green theme */
        .repair-card-container.Completed-color,
        .repair-card-container.Completed-color .status-badge,
        .repair-card-container.Completed-color .menu-dropdown {
            background: linear-gradient(135deg, #b5f1b8ff 0%, #9ced9fff 100%);
        }

        /* purple theme */
        .repair-card-container.Inprogress-color,
        .repair-card-container.Inprogress-color .status-badge,
        .repair-card-container.Inprogress-color .menu-dropdown {
            background: linear-gradient(135deg, #e0c4ffff 0%, #d9aeffff 100%);
        }

        .card-caption {
            font-size: 14px;
            font-weight: bold;
            color: black;
        }

        .card-main-text {
            font-size: 20px;
            font-weight: bold;
            color: black;
        }

        .stat-card {
            padding: 20px;
        }

        .menu_section {
            position: absolute;
            margin-bottom: 5px;
            top: 10px;
            right: 12px;
        }
    </style>
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Assigned Task<span class="badge badge-soft-dark ml-2"
                    id="itemCount">{{ count($tasks) }}</span></h1>
        </div>


        <div class="page-header d-flex justify-content-between flex-wrap">
            <div class="card-header d-flex py-2 align-items-center">

                @if (!$empId) <!-- if not showing employee specific -->
                    <form action="" class="d-flex date-range-form">
                        @include('admin-views/form_modals/date_range')
                        <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                            data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>

                        <select class="form-control mx-1" name="status" onchange="this.form.submit()">
                            <option {{ $status == 'All' ? 'selected' : '' }} value="All">All</option>
                            @foreach ($statuses as $key => $value)
                                <option {{ $status == $value->status ? 'selected' : '' }} value="{{ $value->status }}">
                                    {{ ucfirst($value->status) }}</option>
                            @endforeach
                        </select>
                    </form>
                    <a class="btn btn-outline-primary mr-1" href="{{ route('admin.project.task.export') }}">
                        Export
                    </a>
                    <form action="" class="input-group" style="max-width: 270px;">
                        <input type="text" value="{{ $search ?? '' }}" name="search" class="form-control"
                            placeholder="Search By Title">
                        <div class="input-group-append">
                            <button class="btn btn-secondary" type="submit">
                                <i class="tio-search"></i>
                            </button>
                        </div>
                    </form>
                @endif

            </div>
        </div>

        <div class="card">

            <div class="d-flex gap-4 py-4 px-2 flex-wrap">
                @php
                    $colors = ['theme-green', 'theme-skyblue', 'theme-pink', 'theme-purple'];
                    $index = 0;
                @endphp
                @foreach ($tasks as $key => $task)
                    @php
                        if (!in_array($task->status, ['Completed', 'Allotted', 'Cancelled', 'New', 'Inprogress'])) {
                            $status = 'other';
                        } else {
                            $status = $task->status;
                    } @endphp
                    <div class="repair-card-container {{ $status . '-color' }} "
                        onclick="handleClick('{{ $task->parent_id ?  ($task->employee_id == null ? '#' : route('admin.project.task.subtask.detail', [$task->id]) ) : ($task->employee_id == null ? '#' : route('admin.project.task.detail', [$task->id]) )}}', event)">

                        <div class="repair-card-title">{{ $task->title }}</div>
                        <div class="repair-card-title">Task Id : {{ $task->task_id }}</div>


                        <div class="repair-card-content">
                            @if ($task->employee_id !== null)
                                <div style="margin-bottom: 5px;" class="menu_section">
                                    <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                        aria-expanded="false">
                                        <img style="width: 24px; filter: contrast(0)"
                                            src="{{ asset('storage/app/public/util/10025520.png') }}" alt="action" />
                                    </button>
                                    {{-- <button class="status-badge dropdown-toggle border-0" type="button" data-toggle="dropdown"
                                    aria-expanded="false">Menu</button> --}}
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item text-warning"
                                            href="{{ $task->parent_id ? route('admin.project.task.subtask.detail', [$task->id]) : route('admin.project.task.detail', [$task->id]) }}"
                                            title="{{ translate('messages.view') }}"><i class="tio-visible-outlined"></i>
                                            View
                                        </a>

                                    </div>
                                </div>
                            @endif

                            <div class="info-row">
                                <span class="info-label">Created at:</span>
                                <span class="info-value">{{ $task->created_at }}</span>
                            </div>
                            @if ($task->employee_id !== null)
                                <div class="info-row" style="align-items: flex-start;">
                                    <span class="info-label">Client Info:</span>
                                    <div style="text-align: right;">
                                        <div class="info-value">
                                            {{ $task->user?->f_name . ' ' . $task->user?->l_name }} <br>
                                           <span class="text--primary"> {{ $task->user?->phone }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif


                            <div class="info-row" style="align-items: flex-start;">
                                <span class="info-label">Status:</span>
                                <div style="text-align: right;">
                                    <div style="margin-bottom: 5px;">
                                        <span class="status-badge">{{ $task->status }}</span>
                                    </div>
                                </div>
                            </div>
                            @if ($task->employee_id == null && ($task->status == 'Completed' || $task->status == 'Cancelled'))
                            @elseif($task->employee_id == null)
                                <div class="d-flex gap-2 w-100 justify-content-center mt-3">
                                    <button style="width:fit-content; padding: 0 10px !important;"
                                        class="btn action-btn btn--primary form-alert" href="javascript:"
                                        data-id="category-{{ $task['id'] }}"
                                        data-message="{{ translate('Want to accept this task') }}"
                                        title="{{ translate('messages.task_accept') }}">Accept</button>

                                    <form action="{{ route('admin.project.task.accept', [$task['id']]) }}" method="get"
                                        id="category-{{ $task['id'] }}">
                                        @csrf @method('get')
                                    </form>
                                    <button style="width:fit-content; padding: 0 10px !important;"
                                        class="btn action-btn btn--danger form-alert" href="javascript:"
                                        data-id="category2-{{ $task['id'] }}"
                                        data-message="{{ translate('Want to reject this task') }}"
                                        title="{{ translate('messages.task_reject') }}">Reject</button>

                                    <form action="{{ route('admin.project.task.reject', [$task['id']]) }}" method="get"
                                        id="category2-{{ $task['id'] }}">
                                        @csrf @method('get')
                                    </form>
                                </div>
                            @else
                                <a class="text-primary">Accepted</a>
                            @endif

                            {{-- <div class="info-row" style="align-items: flex-start; margin-top: 0px;">
                                <div></div>
                                <div style="text-align: right;">
                                 

                                </div>
                            </div> --}}

                            {{-- <div class="custom-action-buttons">
                                @if ($task->jobcard)
                                    <button class="custom-action-btn">Job Card</button>
                                @endif
                                @if ($task->recievableReciept)
                                    <button class="custom-action-btn">Receiable receipt</button>
                                @endif

                                @if ($task->quotation)
                                    <button class="custom-action-btn">Quotation</button>
                                @endif
                                @if ($task->invoice)
                                    <button class="custom-action-btn">Invoice</button>
                                @endif
                            </div> --}}
                            @if ($task->employee_id !== null)
                                <div class="progress-section">
                                    <div class="progress-label">Progress ( {{ $task->progress }}%)</div>
                                    <div class="progress-bar-container">
                                        <div class="custom-progress">
                                            <div class="custom-progress-fill" style='width:{{ $task->progress }}%'></div>
                                        </div>
                                        <div class="progress-percentage">{{ $task->progress }}%</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>


        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script>
        function handleClick(url, e) {
            if ($(e.target).closest('.menu_section, .dropdown-menu, .dropdown-toggle, button, a').length) {
                console.log('sdf')
            } else {
                window.location.href = url;
            }

        }
    </script>
@endpush
