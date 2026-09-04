@extends('layouts.vendor.app')

@section('title', 'Task Details')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lightgallery.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-thumbnail.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-video.css"> --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lightgallery-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/lightgallery/lightgallery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lightgallery/plugins/zoom/lg-zoom.min.js"></script>

    <link
        href="https://fonts.googleapis.com/css2?family=Chakra+Petch:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet">
    @php
        $progress = $task->progress; // Dynamic from DB or logic
        $hue = intval(($progress / 100) * 120); // green (120) to red (0)
        $color = "hsl($hue, 80%, 50%)";
    @endphp
    <style>
        /* LIGHT GALLERY */
        .lg-toolbar .lg-icon {
            color: white !important;
            font-size: 31px !important;
        }

        /* LIGHT GALLERY END*/

        .webcam_section video,
        .webcam_section canvas,
        .webcam_section img {
            width: 100%;
            border: 2px solid #ccc;
            border-radius: 10px;
            object-fit: cover;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {

            .rrrows_parent .item_row td,
            .item_row_inv td {
                width: 100% !important;
            }

            .webcam_section video,
            .webcam_section canvas,
            .webcam_section img {
                max-width: 215px;
            }
        }

        .webcam_section img {
            width: 120px;
        }

        /* otp element styling  */
        .otp-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
        }

        .otp-container h2 {
            margin-bottom: 20px;
        }

        .otp-container p {
            margin-bottom: 20px;
            color: #666;
        }

        .otp-form {
            display: flex;
            justify-content: space-between;
        }

        .otp-input {
            width: 45px;
            height: 45px;
            margin: 3px;
            text-align: center;
            font-size: 26px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .otp-input:focus {
            border-color: #007bff;
            outline: none;
        }


        .time_text {
            font-family: "Chakra Petch", sans-serif;
            font-weight: 600;
            font-style: normal;
            font-size: 25px;
        }

        #countdown-container {
            text-align: center;
        }

        #countdown {
            font-size: 18px;
            font-weight: 600;
        }

        #countdown.expired {
            color: #b30000;
        }

        #countdown.active {
            color: #2e7d32;
        }
    </style>

    <style>
        .lg-object.lg-image {
            height: 100% !important;
        }

        .lg-counter {
            background: #ffffff24 !important;
            padding: 7px !important;
            height: fit-content !important;
            border-radius: 10px;
            margin: 10px !important;
            font-size: 23px !important;
            color: white !important;
        }

        .lg-single-item .lg-next,
        .lg-single-item .lg-prev {
            display: block !important;
        }

        .lg-next.lg-icon,
        .lg-prev.lg-icon {
            background: #ffffffb3;
            border: 1px solid white;
            color: black;
            border-radius: 50%;
        }

        .add-task-section {
            margin-bottom: 24px;
        }

        .add-task-form {
            display: flex;
            gap: 8px;
        }

        .task-input {
            flex: 1;
            padding: 8px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }

        .task-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .add-btn {
            padding: 8px 16px;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }

        .add-btn:hover {
            background-color: #2563eb;
        }

        .tasks-area {
            min-height: 200px;
            {{-- padding: 16px; --}} border: 2px dashed transparent;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .tasks-area.drag-over-root {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .task-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 8px;
            background: white;
            border: 1px solid #c8d2e0;
            border-radius: 8px;
            cursor: move;
            transition: all 0.2s;
            user-select: none;
            position: relative;
        }

        .task-item:nth-child(4n+1) {
            background: #feeded;
        }

        /* red */
        .task-item:nth-child(4n+2) {
            background: #e9fdff;
        }

        /* blue */
        .task-item:nth-child(4n+3) {
            background: #e7fff0;
        }

        /* green */
        .task-item:nth-child(4n+4) {
            background: #fff8e0;
        }

        /* yellow */

        .task-item:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .task-item.drag-over {
            border-color: #3b82f6;
            background-color: #eff6ff;
            box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .task-item.drag-over-above {
            border-top: 3px solid #3b82f6;
        }

        .task-item.drag-over-below {
            border-bottom: 3px solid #3b82f6;
        }

        .task-item.dragging {
            opacity: 0.5;
        }

        .grip-icon {
            width: 16px;
            height: 16px;
            color: #9ca3af;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .expand-btn {
            width: 24px;
            height: 24px;
            border: none;
            background: none;
            cursor: pointer;
            margin-right: 8px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .expand-btn:hover {
            background-color: #f3f4f6;
        }

        .chevron {
            width: 16px;
            height: 16px;
            color: #6b7280;
            transition: transform 0.2s;
        }

        .chevron.expanded {
            transform: rotate(90deg);
        }

        .task-content {
            flex: 1;
        }

        .task-title {
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 2px;
        }

        .task-subtitle {
            font-size: 12px;
            color: #6b7280;
        }

        .delete-btn {
            width: 24px;
            height: 24px;
            border: none;
            background: none;
            cursor: pointer;
            margin-left: 8px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #ef4444;
            opacity: 1;
            transition: opacity 0.2s;
        }

        .task-item:hover .delete-btn {
            opacity: 1;
        }

        .delete-btn:hover {
            background-color: #fee2e2;
        }

        .empty-state {
            text-align: center;
            padding: 48px 0;
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            color: #d1d5db;
            margin: 0 auto 8px;
        }

        .empty-text {
            color: #6b7280;
        }

        .instructions {
            margin-top: 32px;
            padding: 16px;
            background: white;
            border: 1px solid #c8d2e0;
            border-radius: 8px;
        }

        .instructions h3 {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .instructions ul {
            list-style: none;
            color: #6b7280;
            font-size: 14px;
        }

        .instructions li {
            margin-bottom: 4px;
        }
    </style>
    <style>
        .timeline {
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: relative;
            padding: 10px;
            height: fit-content;


        }

        .timeline-item h4 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .timeline-item .time {
            float: right;
            font-size: 14px;
            color: #888;
        }

        .timeline-item p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #666;
        }

        .red {
            background-color: rgba(249, 240, 240, 1);
        }

        .blue {
            background-color: rgba(245, 247, 253, 1);
        }

        .yellow {
            background-color: rgba(252, 250, 243, 1);
        }

        .green {
            background-color: rgba(240, 249, 244, 1);
        }

        .circle-container {
            width: 140px;
            {{-- margin: 40px auto; --}} text-align: center;
            font-family: sans-serif;
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring circle {
            fill: none;
            stroke-width: 10;
        }

        .progress-ring .bg {
            stroke: #e6e6e6;
        }

        .progress-ring .progress {
            stroke: {{ $color }};
            stroke-linecap: round;
            stroke-dasharray: 282.6;
            /* 2 * PI * radius (r=45) */
            stroke-dashoffset: 282.6;
            animation: animateProgress 1s ease-out forwards;
        }

        @keyframes animateProgress {
            to {
                stroke-dashoffset: <?=282.6 - ($progress / 100) * 282.6 ?>;
            }
        }

        .circle-text {
            position: relative;
            top: -76px;
            font-size: 20px;
            color: <?=$color ?>;
            font-weight: bold;
        }

        .progress_label {
            margin-top: -30px;
            font-size: 14px;
            color: #444;
        }

        .profile_card .profile_img {
            width: 100px;
            aspect-ratio: 1;
            object-fit: cover;
        }

        .profile_card {
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 15px;
            {{-- height: fit-content; --}}
        }

        .af-progress-wrap-xy12 {
            width: 100%;
            max-width: 500px;
            margin: auto;
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .af-progress-wrap-xy12 h2 {
            text-align: center;
            margin-bottom: 20px;
            font-family: 'Segoe UI', sans-serif;
        }

        .af-progress-input-wrap-xy12 {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            justify-content: space-between;
        }



        .af-bar-wrapper-xy12 {
            position: relative;
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }

        .af-bar-fill-xy12 {
            height: 100%;
            width: 0%;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            transition: width 0.3s ease-in-out;
        }

        .af-range-slider-xy12 {
            width: 100%;
            margin-top: 20px;
        }

        .af-label-xy12 {
            text-align: center;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
    <style>
        .timeline_container {
            position: relative;
            padding: 20px 0;
        }

        .timeline_container::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 94px;
            width: 3px;
            background: #b4b4b4;
        }

        .timeline-item {
            position: relative;
            padding: 5px;
            margin-bottom: 15px;
            margin-left: 32px;
            background: #fff;
            border-radius: 4px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 25px;
            width: 15px;
            height: 15px;
            background: #4a90e2;
            border: 3px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 0 3px #4a90e2;
            z-index: 1;
        }

        .timeline-item small {
            color: #666;
            font-size: 12px;
        }

        .timeline-item h4 {
            margin: 10px 0;
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }

        .timeline-item p {
            margin: 8px 0;
            color: #555;
            font-size: 14px;
        }

        .created_by {
            color: #888;
            font-style: italic;
        }

        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .gap-1 {
            gap: 8px;
        }

        .img--40 {
            width: 40px;
            height: 40px;
            object-fit: cover;
        }

        .rounded {
            border-radius: 4px;
        }

        .lightgallery {
            display: flex;
            flex-wrap: wrap;
        }

        .lightgallery a {
            text-decoration: none;
        }

        .lightgallery a img {
            transition: transform 0.2s;
        }

        .lightgallery a img:hover {
            transform: scale(1.1);
        }

        .no-status {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        .task_img {
            width: 100%;
            max-width: 150px;
            aspect-ratio: 1;
        }

        .reassign_section {
            margin-right: 147px;
        }

        .estimation_card {
            padding: 10px 3px;
        }

        .modal_btn_hjs {
            display: block;
        }

        .mobile_modal_btn_hjs {
            display: none;
        }

        .modal_btn_add_info {
            display: block;
        }

        .mobile_modal_btn_add_info {
            display: none;
        }

        @media (max-width: 768px) {
            .reassign_section {
                margin-right: 0;
            }

            .modal_btn_hjs {
                display: none;
            }

            .mobile_modal_btn_hjs {
                display: block;
            }

            .modal_btn_add_info {
                display: none;
            }

            .mobile_modal_btn_add_info {
                display: block;
            }

            .task_info_section {
                flex-direction: column-reverse;
            }

            .task_img {
                max-width: 100px;
            }

            .estimation_card {
                padding: 5px;
            }

            .est_small_text {
                font-size: 11px !important;
            }

        }
    </style>
@endpush

@section('content')
    @php
        $task_type = $task->parent_id ? 'subtask' : 'task';
    $task_closed = in_array($task->status, ['Completed', 'Cancelled']); @endphp
    @if (hasPermission('project_' . $task_type, 'view'))
        <div class="">
            <!-- Page Header -->
            <div class="page-header d-flex w-100 justify-content-between flex-wrap">
                <div class="d-flex flex-wrap">
                    <div>
                        <h1 class="page-header-title mb-0"><i class="tio-filter-list"></i>{{ $task->title }}
                            #{{ $task->task_id }}</h1>
                        <i class="created_by ml-4">Created By : {{ _vendorOrStaffName($task->created_by) }}</i>
                    </div>
                    <div class="ml-2">
                        @if (
                            !$task_closed &&
                                hasPermission('project_' . $task_type, 'edit') &&
                                ($task->employee_id == \App\CentralLogics\Helpers::get_loggedin_user()->id ||
                                    hasPermission('project_task', 'edit_others')))
                            <a href="{{ $task->parent_id ? route('vendor.project.task.subtask.edit', [$task->id]) : route('vendor.project.task.edit', [$task->id]) }}"
                                class="btn action-btn btn-outline-primary"><i class="tio-edit"></i></a>
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap align-items-center reassign_section" style="">

                    <div class="card" style=" min-width: 200px;">
                        {{-- background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%); --}}
                        <div class="card-body p-1">
                            <div class="d-flex align-items-center gap-2">
                                @if ($task->emp_name == 'Self')
                                    <img class="rounded-circle onerror-image me-3"
                                        style="width: 50px;height: 50px;object-fit: cover;border: 3px solid white;box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                            $task->emp_image ?? '',
                                            asset('storage/app/public/store') . '/' . $task->emp_image ?? '',
                                            asset('public/assets/admin/img/160x160/img1.jpg'),
                                            'store/',
                                        ) }}"
                                        alt="Assignee">
                                @else
                                    <img class="rounded-circle onerror-image me-3"
                                        style="width: 50px;height: 50px;object-fit: cover;border: 3px solid white;box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                            $task->emp_image ?? '',
                                            asset('storage/app/public/vendor') . '/' . $task->emp_image ?? '',
                                            asset('public/assets/admin/img/160x160/img1.jpg'),
                                            'vendor/',
                                        ) }}"
                                        alt="Assignee">
                                @endif
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1 small fw-semibold">
                                        {{ $task->employee_id || $task->employee_id === 0 ? 'ASSIGNEE' : 'OFFERED TO' }}
                                    </p>
                                    <h5 class="mb-1 fw-bold">{{ ucwords($task->emp_name) }}</h5>
                                    <p class="mb-0 small text-muted">
                                        <i class="tio-briefcase-outlined me-1"></i>{{ ucwords($task->emp_role) }}
                                    </p>
                                    {{-- <p class="mb-0 small text-muted">
                                                    <i class="tio-call-outlined me-1"></i>{{ $task->emp_phone }}
                                                </p> --}}
                                </div>
                            </div>
                        </div>
                    </div>
                    @if (!$task->status == 'Completed' && $task->employee_id == null && $task->offered_to == null)
                        <button class="btn btn-primary reassign_modal_btn" data-id="{{ $task->id }}"
                            data-toggle="modal" data-target="#assignmentModal">Reassign</button>
                        @include('vendor-views.form_modals.assign_task_modal')
                    @endif 

                </div>
            </div>
            <!-- End Page Header -->

            <div class="row align-items-start g-0">
                <div class="col-md-8 pr-0">

                    @if ($data['quotation'] && $data['quotation']->pdf)
                    @else
                        <form action="{{ route('vendor.quotation.save-info-task', [$task->id]) }}" method="post">
                            @csrf
                            @include('vendor-views/form_modals/quote_add_modal')
                        </form>
                    @endif
                    @php $existingItems = $data['existing_jobcard_items']; @endphp
                    @include('vendor-views/form_modals/basic_invoice')

                    <div class="row g-0 p-2">
                        <div class="col-md-4 p-2">
                            <div class="card h-100 border">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center gap-2">

                                        <img class="rounded-circle onerror-image me-3"
                                            style="width: 70px; height: 70px; object-fit: cover; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                $task->user?->profile_pic ?? '',
                                                asset('storage/app/public/profile') . '/' . $task->user?->profile_pic ?? '',
                                                asset('public/assets/admin/img/160x160/img1.jpg'),
                                                'profile/',
                                            ) }}"
                                            alt="Client">

                                        <div class="flex-grow-1">
                                            <p class="text-muted mb-1 small fw-semibold">CLIENT</p>
                                            <h5 class="mb-1 fw-bold">
                                                {{ ucwords($task->user?->f_name . ' ' . $task->user?->l_name) }}</h5>

                                            <p class="mb-0 small text-muted">
                                                <i class="tio-call-outlined me-1"></i>{{ $task->user?->phone }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="col-md-4 p-2">
                            <div class="card h-100 border shadow-sm">
                                <div class="card-body p-2 d-flex justify-content-between align-items-start gap-3">
                                    <div class="mb-3">
                                        <p class="text-muted mb-2 small fw-semibold">
                                            <i class="tio-chart-bar-4 me-1"></i>TASK PROGRESS
                                        </p>
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <input type="number"
                                                style="width: 70px; font-size: 1.5rem; font-weight: bold; {{ $task_closed ? 'pointer-events:none' : '' }}"
                                                id="af-input-xy12"
                                                class="form-control text-center border-0 bg-white shadow-sm" min="0"
                                                max="100" value="<?= $progress ?>">
                                            <span style="    font-size: 20px;" class=" fw-semibold">%</span>
                                        </div>
                                        <div class="af-bar-wrapper-xy12 position-relative"
                                            style="height: 12px; background: rgba(255,255,255,0.5); border-radius: 10px; overflow: hidden;">
                                            <div class="af-bar-fill-xy12 bg-success" id="af-bar-xy12"
                                                style="height: 100%; width: <?= $progress ?>%; transition: width 0.3s ease;">
                                            </div>
                                        </div>
                                        <span class="text-success info_text small d-block mt-1"></span>
                                    </div>

                                    <div class="border-top ">
                                        <p class="text-muted mb-2 small fw-semibold">
                                            <i class="tio-checkmark-circle-outlined me-1"></i>TASK STATUS
                                        </p>
                                        @if (hasPermission('project_' .$task_type, 'status_change'))
                                            @if ($task->status == 'Completed')
                                                <span class="badge badge-soft-success px-3 py-2 ">
                                                    <i class="tio-done me-1"></i>Completed
                                                </span>
                                            @elseif($task->status == 'Cancelled')
                                                <span class="badge badge-soft-danger px-3 py-2">
                                                    <i class="tio-clear me-1"></i>Cancelled
                                                </span>
                                            @else
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge badge-soft-primary px-3 py-2 flex-grow-1 ">
                                                        {{ $task->status }}
                                                    </span>
                                                    {{-- <button data-toggle="modal" data-target="#taskStatusModal"
                                                        class="btn btn-sm btn-outline-primary" title="Edit Status">
                                                        <i class="tio-edit"></i>
                                                    </button> --}}
                                                </div>
                                            @endif
                                        @else
                                            {{ $task->status }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 p-2">
                            <div class="card h-100 border shadow-sm">
                                {{-- style="background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);" --}}
                                <div
                                    class="card-body p-2 d-flex flex-column justify-content-center align-items-center text-center">
                                    <p class="text-muted mb-2 small fw-semibold">
                                        <i class="tio-time me-1"></i>TIMER
                                    </p>
                                    <div id="countdown-container">
                                        <input type="hidden" id="task_status" value="{{ $task->status }}">
                                        <div id="countdown" data-target="{{ $data['target_time'] }}">
                                            @if ($task->status == 'Completed' || $task->status == 'Cancelled')
                                                <p class="text-muted mb-2 small">{{ $task->status }} in</p>
                                                <h2 class="mb-0 fw-bold text-dark" style="font-size: 2rem;">
                                                    {{ formatTimeDifference($task->created_at, $task->cancelled_at) }}
                                                </h2>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div class="row g-0">
                        @php
                            $file = $task['file'] ?? null;
                            $file_path = $file ? asset('storage/app/public/task/' . $file) : null;
                            $extension = $file ? strtolower(pathinfo($file, PATHINFO_EXTENSION)) : null;
                            $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            $doc_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'];
                        @endphp
                        <div class="col-md-8 p-2">
                            <div class=" card border shadow-sm h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex gap-2 task_info_section">
                                        @if ($file)
                                            <div class="d-flex align-items-center   ">
                                                <div class="">
                                                    <div class="task-file-preview">
                                                        @if (in_array($extension, $image_extensions))
                                                            <div class="position-relative rounded overflow-hidden">
                                                                <img class="object-fit-cover onerror-image task_img"
                                                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                    src="{{ $file_path }}" alt="Task Image">
                                                            </div>
                                                        @elseif($extension == 'pdf')
                                                            <div class="border rounded p-3 text-center bg-light">
                                                                <i class="tio-document text-primary"
                                                                    style="font-size: 2.5rem;"></i>
                                                                <p class="mt-2 mb-2 small fw-semibold">PDF Document</p>
                                                                <a href="{{ $file_path }}" target="_blank"
                                                                    class="btn btn-sm btn-outline-primary">
                                                                    <i class="tio-visible me-1"></i> View
                                                                </a>
                                                            </div>
                                                        @elseif(in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'csv']))
                                                            <div class="border rounded p-3 text-center bg-light">
                                                                <i class="tio-attachment text-success"
                                                                    style="font-size: 2.5rem;"></i>
                                                                <p class="mt-2 mb-2 small fw-semibold">
                                                                    {{ strtoupper($extension) }}
                                                                    File
                                                                </p>
                                                                <a href="{{ $file_path }}" target="_blank"
                                                                    class="btn btn-sm btn-outline-success">
                                                                    <i class="tio-download me-1"></i> Download
                                                                </a>
                                                            </div>
                                                        @else
                                                            <div class="border rounded p-3 text-center bg-light">
                                                                <i class="tio-clear text-muted"
                                                                    style="font-size: 2.5rem;"></i>
                                                                <p class="text-muted mt-2 mb-0 small">Unsupported file type
                                                                </p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button type="button" data-toggle="modal" data-target="#basicDetModal"
                                                class="btn btn-outline-primary mobile_modal_btn_hjs">View</button>
                                            <button style="width: fit-content; padding: 0 5px;" type="button"
                                                data-toggle="modal" data-target="#additionalDetModal"
                                                class="btn btn-outline-primary mobile_modal_btn_add_info">Additonal
                                                Info</button>
                                        </div>

                                        <div class=" d-flex flex-column align-items-center justify-content-center    ">
                                            <h3>{{ $task->title }}</h3>
                                            <p>{{ $task->description }}</p>
                                            <div class="d-flex gap-2 ">
                                                <div class="alert alert--primary mb-0 estimation_card">
                                                    <label class="text-muted mb-2 small fw-semibold">
                                                        TIME ESTIMATION
                                                    </label>
                                                    <div class="d-flex align-items-center">
                                                        <i class="tio-alarm me-2"></i>
                                                        <span
                                                            class="fw-semibold est_small_text">{{ $task->time_count . ' ' . $task->time_unit . '(s)' }}</span>
                                                    </div>
                                                </div>
                                                <div class="alert alert--primary mb-0 estimation_card">
                                                    <label class="text-muted mb-2 small fw-semibold">
                                                        CREATED AT
                                                    </label>
                                                    <div class="d-flex align-items-center">
                                                        <i class="tio-alarm me-2"></i>
                                                        <span
                                                            class="fw-semibold est_small_text">{{ $task->created_at }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- <p><b>Time Estimation :</b>{{ $task->time_count . ' ' . $task->time_unit . '(s)' }}
                                        </p> --}}
                                            {{-- <p><b>Created At :</b>{{ $task->created_at }}</p> --}}
                                        </div>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button type="button" data-toggle="modal" data-target="#basicDetModal"
                                                class="btn btn-outline-primary action-btn modal_btn_hjs"><i
                                                    class="tio-visible"></i></button>
                                            <button style="width: fit-content; padding: 0 5px;" type="button"
                                                data-toggle="modal" data-target="#additionalDetModal"
                                                class="btn btn-outline-warning action-btn modal_btn_add_info"><i
                                                    class="tio-visible"></i>Additional Info</button>

                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 p-2">
                            <div class="card border shadow-sm h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex  justify-content-between">
                                        <h4>Attachments</h4>
                                        <button type="button" data-toggle="modal" data-target="#addAttachmentModal"
                                            class="btn btn-outline-primary action-btn">+</button>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        @if ($data['service_report'] && $data['service_report']->pdf)
                                            <a class="btn btn-info btn_sm px-3 py-2" target="_blank"
                                                href="{{ asset('storage/app/public/store/service_reports/' . $data['service_report']->pdf) }}">
                                                <i class="tio-visible-outlined me-1"></i> Service Report
                                            </a>
                                        @endif
                                        @if (hasPermission('project_'. $task_type, 'receivable_receipt'))
                                            @if ($data['receipt'] && $data['receipt']->pdf)
                                                <a class="btn btn-warning btn_sm px-3 py-2" target="_blank"
                                                    href="{{ asset('storage/app/public/store/recivable-receipts/' . $data['receipt']->pdf) }}">
                                                    <i class="tio-visible-outlined me-1"></i> Receivable Receipt
                                                </a>
                                            @endif
                                        @endif
                                        @if (hasPermission('project_'.$task_type, 'quotation'))
                                            @if ($data['quotation'] && $data['quotation']->pdf)
                                                <a class="btn btn-success btn_sm px-3 py-2" target="_blank"
                                                    href="{{ asset('storage/app/public/invoice/' . $data['quotation']->pdf) }}">
                                                    <i class="tio-visible-outlined me-1"></i> Quotation
                                                </a>
                                            @endif
                                        @endif
                                        @if (hasPermission('project_'. $task_type, 'invoice'))
                                            @if ($data['invoice'] && $data['invoice']->pdf)
                                                <a class="btn btn-primary btn_sm px-3 py-2" target="_blank"
                                                    href="{{ asset('storage/app/public/invoice/' . $data['invoice']->pdf) }}">
                                                    <i class="tio-visible-outlined me-1"></i> Invoice
                                                </a>
                                            @endif
                                        @endif
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 p-2">
                            <div class="timeline card border">
                                <div class="d-flex justify-content-between">
                                    <h3>Timeline</h3>
                                </div>

                                <div class="timeline_container">
                                    @if (count($task_statuses))
                                        @foreach ($task_statuses as $key => $status)
                                            @php
                                                // Handle normal uploaded files
                                                $files = $status->file;

                                                if (is_string($files) && str_starts_with($files, '[')) {
                                                    $files = json_decode($files, true);
                                                } elseif (!empty($files)) {
                                                    $files = [$files];
                                                } else {
                                                    $files = [];
                                                }

                                                $webcamFiles = $status->webcam_file;

                                                if (is_string($webcamFiles) && str_starts_with($webcamFiles, '[')) {
                                                    $webcamFiles = json_decode($webcamFiles, true);
                                                } elseif (!empty($webcamFiles)) {
                                                    $webcamFiles = [$webcamFiles];
                                                } else {
                                                    $webcamFiles = [];
                                                }
                                            @endphp

                                            <div class="timeline-item border">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <small>{{ _formatted_datetime($status['created_at']) }}</small><br>
                                                        <small><i class="created_by">Updated By:
                                                                {{ _vendorOrStaffName($status->created_by) }}</i></small>
                                                        <h4>{{ $status['status'] }}</h4>
                                                    </div>
                                                    <div class="d-flex gap-1 ">
                                                        @if (count($files) || count($webcamFiles) || $status['note'])
                                                            <a class="btn btn-outline-primary action-btn"
                                                                data-toggle="collapse"
                                                                href="#collapseExample_l{{ $key }}"
                                                                role="button" aria-expanded="false"
                                                                aria-controls="collapseExample">
                                                                <i class="tio-visible"></i></a>
                                                        @endif
                                                    </div>

                                                </div>

                                                @if (count($files) || count($webcamFiles) || $status['note'])
                                                    <div class="collapse" id="collapseExample_l{{ $key }}">
                                                        <p>{{ $status['note'] }}</p>
                                                        <div class="p-1 card card-body lightgallery d-flex gap-2 flex-row">


                                                            @if (!empty($files))
                                                                @foreach ($files as $file)
                                                                    <a href="{{ asset('storage/app/public/task/' . $file) }}"
                                                                        style="cursor: zoom-in" target="_blank">
                                                                        <img src="{{ asset('storage/app/public/task/' . $file) }}"
                                                                            alt="status update" class="img--40 rounded">
                                                                    </a>
                                                                @endforeach
                                                            @endif


                                                            @if (!empty($webcamFiles))
                                                                @foreach ($webcamFiles as $file)
                                                                    <a href="{{ asset('storage/app/public/task/' . $file) }}"
                                                                        style="cursor: zoom-in" target="_blank">
                                                                        <img src="{{ asset('storage/app/public/task/' . $file) }}"
                                                                            alt="status update" class="img--40 rounded">
                                                                    </a>
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="no-status">No Status Updates ...</div>
                                    @endif
                                </div>
                            </div>
                        </div>


                        <div class="col-md-6 p-2">
                            <div class="timeline border card h-100">
                                <div class="d-flex justify-content-between">
                                    <h3>Subtasks</h3>
                                    @if (!$task_closed && hasPermission('project_subtask', 'add'))
                                        <a data-toggle="modal" data-target="#addSubtaskModal"
                                            class="btn action-btn btn--primary btn-outline-primary" title="Add Subtask"><i
                                                class="tio-add"></i></a>
                                    @endif
                                </div>
                                <div id="tasksArea" class="tasks-area">
                                    @if (hasPermission('project_subtask', 'list'))
                                        <div id="tasksContainer"></div>
                                        @if (!$task_closed && hasPermission('project_subtask', 'add'))
                                            <span class="text-muted d-none">Drag and drop tasks to make sub sub
                                                tasks</span>
                                            <div id="emptyState" class="empty-state"
                                                style="display: none;visibility:hidden;">
                                                <div class="empty-icon">+</div>
                                                <p class="empty-text">No tasks yet. Add your first task above!</p>
                                            </div>
                                        @else
                                            <span class="text-muted">Drag and drop tasks to make sub sub tasks</span>
                                            <div id="emptyState" class="empty-state" style="display: none;">
                                                <div class="empty-icon">+</div>
                                                <p class="empty-text">No tasks yet. Add your first task above!</p>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class=" card border shadow-sm h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <div class="w-100 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold"> 
                                    <i class="tio-timeline me-2"></i>Status Updates
                                </h5>
                                <div class="d-flex gap-2 align-items-start">
                                    @if (hasPermission('project_'.$task_type, 'status_change') && $task->status !== 'Completed' && $task->status != 'Cancelled')
                                        <button data-toggle="modal" data-target="#taskStatusModal"
                                            class="btn btn-sm btn-outline-primary" title="Edit Status">
                                            Status <i class="tio-edit"></i>
                                        </button>
                                    @endif
                                    {{-- @if (!$task_closed && hasPermission('project_'.$task_type . '_update', 'add'))
                                        <button data-toggle="modal" data-target="#addTaskCommentModal"
                                            class="btn action-btn btn-outline-primary" title="Add Update">
                                            <i class="tio-add me-1"></i>
                                        </button>
                                    @endif --}}
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            @php
                                $card_colors = [
                                    'primary' => '#e3f2fd',
                                    'success' => '#e8f5e9',
                                    'warning' => '#fff3e0',
                                    'info' => '#e0f7fa',
                                ];
                                $border_colors = [
                                    'primary' => '#2196f3',
                                    'success' => '#4caf50',
                                    'warning' => '#ff9800',
                                    'info' => '#00bcd4',
                                ];
                            @endphp

                            @if (isset($task_comments) && count($task_comments))
                                <div class="timeline position-relative">
                                    @foreach ($task_comments as $key => $comment)
                                        @php
                                            $color_key = array_keys($card_colors)[$key % count($card_colors)];
                                            $bg_color = $card_colors[$color_key];
                                            $border_color = $border_colors[$color_key];
                                        @endphp
                                        <div class=" mb-3 position-relative ps-4"
                                            style="border-left: 3px solid {{ $border_color }};border-radius: 10px;">
                                            <div class="card border-0 shadow-sm"
                                                style="background: {{ $bg_color }};">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1 fw-bold">{{ $comment['title'] }}</h6>
                                                            <div class="d-flex align-items-center gap-3 text-muted small">

                                                                <span>
                                                                    <i
                                                                        class="tio-calendar me-1"></i>{{ _formatted_datetime($comment['created_at']) }}
                                                                </span>
                                                            </div>
                                                            <div class="d-flex align-items-center gap-3 text-muted small">

                                                                <span>
                                                                    <i class="tio-edit me-1"></i><i
                                                                        class="created_by">Created
                                                                        By :
                                                                        {{ _vendorOrStaffName($comment['created_by']) }}</i>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <button class="btn btn-sm btn-light" type="button"
                                                            data-toggle="collapse"
                                                            data-target="#collapseExample{{ $key }}"
                                                            aria-expanded="false">
                                                            <i class="tio-chevron-down"></i>
                                                        </button>
                                                    </div>

                                                    <div class="collapse" id="collapseExample{{ $key }}">
                                                        <div class="border-top pt-3 mt-2">
                                                            <div class="row g-3">
                                                                <div class="col-md-7">
                                                                    <p class="text-muted mb-1 small fw-semibold">
                                                                        DESCRIPTION
                                                                    </p>
                                                                    <p class="mb-0">{{ $comment['comment'] }}
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-5">
                                                                    <p class="text-muted mb-2 small fw-semibold">
                                                                        FILES
                                                                    </p>
                                                                    @php
                                                                        $files = json_decode($comment['files'], true);
                                                                        $imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
                                                                    @endphp

                                                                    @if (!empty($files))
                                                                        <div class="d-flex flex-wrap gap-2 lightgallery">
                                                                            @foreach ($files as $file)
                                                                                @php
                                                                                    $ext = strtolower(
                                                                                        pathinfo(
                                                                                            $file,
                                                                                            PATHINFO_EXTENSION,
                                                                                        ),
                                                                                    );
                                                                                    $fileUrl = asset(
                                                                                        'storage/app/public/task/' .
                                                                                            $file,
                                                                                    );
                                                                                @endphp

                                                                                @if (in_array($ext, $imageTypes))
                                                                                    <a href="{{ $fileUrl }}"
                                                                                        class="d-block">
                                                                                        <img src="{{ $fileUrl }}"
                                                                                            alt="File"
                                                                                            class="rounded shadow-sm"
                                                                                            style="width: 60px; height: 60px; object-fit: cover;">
                                                                                    </a>
                                                                                @else
                                                                                    <a href="{{ $fileUrl }}"
                                                                                        target="_blank"
                                                                                        class="btn btn-sm btn-outline-secondary">
                                                                                        <i
                                                                                            class="tio-document me-1"></i>{{ strtoupper($ext) }}
                                                                                    </a>
                                                                                @endif
                                                                            @endforeach
                                                                        </div>
                                                                    @else
                                                                        <p class="text-muted small mb-0">No files
                                                                            attached
                                                                        </p>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div
                                                                class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">
                                                                <small class="text-muted">
                                                                    <i class="tio-refresh me-1"></i>Updated:
                                                                    {{ _formatted_datetime($comment['updated_at']) }}
                                                                </small>
                                                                <div class="d-flex gap-2">
                                                                    @if (!$task_closed && hasPermission('project_'. $task_type . '_update', 'edit'))
                                                                        <a href="{{ $task->parent_id ? route('vendor.project.task.subtask-update.edit', [$comment['id']]) : route('vendor.project.project.task.comment.edit', [$comment['id']]) }}"
                                                                            class="btn btn-sm btn-outline-primary">
                                                                            <i class="tio-edit"></i>
                                                                        </a>
                                                                    @endif
                                                                    @if (!$task_closed && hasPermission('project_' . $task_type . '_update', 'delete'))
                                                                        <a href="{{ $task->parent_id ? route('vendor.project.task.subtask-update.delete', [$comment['id']]) : route('vendor.project.task.comment.delete', [$comment['id']]) }}"
                                                                            class="btn btn-sm btn-outline-danger">
                                                                            <i class="tio-delete"></i>
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="tio-announcement text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-3 mb-0">No status updates yet</p>
                                    {{-- @if (!$task_closed && hasPermission('project_' . $task_type . '_update', 'add'))
                                        <button data-toggle="modal" data-target="#addTaskCommentModal"
                                            class="btn btn-sm btn-outline-primary mt-3">
                                            <i class="tio-add me-1"></i>Add First Update
                                        </button>
                                    @endif --}}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>


        </div>
        @if (hasPermission('project_subtask', 'add'))
            <div class="modal fade" id="addSubtaskModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Subtask</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="add-task-section">
                                <form method="post" id="add_task_form" action="{{ route('vendor.project.task.subtask.add') }}">
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $task->id }}">
                                    <div class="add-task-form row  d-flex align-items-end">
                                        <div class=" col-md-12 ">
                                            <label for="">Title</label>
                                            <input type="text" required id="newTaskInput" name="title"
                                                class="task-input form-control" placeholder="Add a subtask..." />
                                        </div>
                                        @if (!auth('vendor_employee')->check())
                                            <div class=" col-md-12 ">
                                                <label for="">Assignee (Optional)</label> <select
                                                    name="employee_id" id="employee_id" required
                                                    data-placeholder="{{ translate('messages.select _staff') }}"
                                                    class="form-control js-select2-custom ">
                                                    <option value="0" selected>
                                                        ---{{ translate('messages.select') }}---
                                                    </option>
                                                    <option value="add_new">+ Add New Employee</option>
                                                    <option value="0">Self</option>
                                                    @foreach ($staff as $key => $s)
                                                        <option value="{{ $s->id }}">
                                                            {{ $s->f_name . ' ' . $s->l_name . ' | ' . $s->role?->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            <input type="hidden" name="employee_id"
                                                value="{{ auth('vendor_employee')->id() }}">
                                        @endif
                                        <div class="col-12 d-flex justify-content-end">
                                            <button type="button" onclick="addNewTask()" class="btn btn--primary">
                                                <span>+</span>
                                                Add Subtask
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if (hasPermission('project_'.$task_type . '_update', 'add'))
            <div class="modal fade" id="addTaskCommentModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add an Update</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form class="customer_add_form" enctype="multipart/form-data" class="w-100"
                                action="{{ $task->parent_id ? route('vendor.project.task.subtask-update.add') : route('vendor.project.task.comment.add') }}"
                                method="post">
                                @csrf
                                <input type="hidden" name="task_id" value="{{ $task->id }}">
                                <div class="row align-items-end">
                                    <div class="form-row col-md-12">
                                        <label for="">Title <span class="text-danger">*</span></label>
                                        <input type="text" required name="title" class="form-control"
                                            placeholder="Ex: Analyze">
                                    </div>
                                    <div class="form-row col-md-12 my-2">
                                        <label for="">Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="comment"></textarea>
                                    </div>
                                    <div class="form-row col-md-12 my-2">
                                        <label for="photo_upld">Photo Upload (Optional, Max 5) <i class="tio-info-outined"
                                                title="   Allowed file types: JPG, JPEG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX, CSV.
                                    Maximum file size: 2 MB each.
                                    This field is optional."></i>
                                        </label>
                                        <input type="file" class="form-control" name="files[]" multiple
                                            id="photo_upld">
                                    </div>
                                    <div class="d-flex justify-content-end w-100">
                                        <button class="btn btn-primary">Save</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if (hasPermission('project_' .$task_type, 'status_change'))
            <div class="modal fade" id="taskStatusModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Update Status</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row align-items-end">
                                <form class="customer_add_form status_update_form" enctype="multipart/form-data"
                                    class="w-100"
                                    action="{{ $task->parent_id ? route('vendor.project.task.subtask.status.update') : route('vendor.project.task.status.update') }}"
                                    method="post">
                                    @csrf
                                    <div style="display:none;" class="otp_inp col-12">
                                        <h6 class="text-center">Enter Otp</h6>
                                        <div class=" d-flex justify-content-center ">
                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                            <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                        </div>
                                        <div class=" mb-2"> <label for="">Assignee</label> <select
                                                name="employee_id" id="employee_id2"
                                                data-placeholder="{{ translate('messages.select delivery person') }}"
                                                class="form-control js-select2-custom ">
                                                <option></option>
                                                <option value="0">Self</option>
                                                @foreach ($staff as $key => $s)
                                                    <option value="{{ $s->id }}">
                                                        {{ $s->f_name . ' ' . $s->l_name . ' | ' . $s->role?->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div> <input type="hidden" name="request_type" value="ajax"> <input
                                                type="checkbox" name="delivered" value="1" id="product_delivered">
                                            <label for="product_delivered">Task Completed</label>
                                        </div>
                                    </div>
                                    <div class="stts_form row p-2">
                                        <input type="hidden" name="action" id="action_inp" value="send_otp">
                                        <input type="hidden" name="task_id" value="{{ $task->id }}">
                                        <input type="hidden" name="request_type" value="ajax">
                                        <div class="form-row col-md-5">
                                            <label for="">Status</label>
                                            <select required name="status" id="task_status"
                                                class="form-control js-select2-custom">
                                                <option {{ $task->status == 'New' ? 'selected' : '' }} value="New">New
                                                </option>

                                                @if ($statuses)
                                                    @foreach (explode(',', $statuses) as $key => $stts)
                                                        <option {{ $task->status == $stts ? 'selected' : '' }}
                                                            value="{{ $stts }}">{{ $stts }}</option>
                                                    @endforeach
                                                @endif
                                                <option {{ $task->status == 'Completed' ? 'selected' : '' }}
                                                    value="Completed">Completed</option>
                                                <option {{ $task->status == 'Cancelled' ? 'selected' : '' }}
                                                    value="Cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="form-row col-md-6">
                                            <label for="">Files (optional) <i><i class="tio-info-outined"></i>Max
                                                    5 photos</i></label>
                                            <input type="file" name="file[]" multiple accept="image/*"
                                                class="form-control statusImage">
                                            <span class="text-danger img_err"></span>
                                        </div>
                                        <div class="form-row col-md-12 my-2">
                                            <label>Description (optional)</label>
                                            <textarea class="form-control" name="note"></textarea>
                                        </div>
                                        <div class="webcam_wrapper row col-12 p-3">
                                            <div class="col-md-4">
                                                <label>Webcam</label>
                                                <div>
                                                    <button type="button" class="btn btn-primary openWebcam">Open
                                                        Webcam</button>
                                                    <button type="button" class="btn btn-primary capture"
                                                        style="display:none;">Capture Photo</button>
                                                    <button type="button" class="btn btn-primary takePhoto"
                                                        style="display:none;">Take Photo (Mobile)</button>
                                                </div>
                                            </div>

                                            <div class="form-row col-md-12 my-2 webcam_section">
                                                <input type="file" name="webcam_file[]" class="hiddenFile" multiple
                                                    hidden>
                                                <video class="webcam" autoplay playsinline
                                                    style="display:none; width:300px;"></video>
                                                <canvas class="snapshot" style="display:none;"></canvas>
                                                <div class="previewContainer" style="margin-top:10px; "></div>
                                            </div>
                                        </div>


                                    </div>
                                    <div class="d-flex justify-content-end w-100">
                                        {{-- <button class="btn btn-secondary mx-2">/Ca/ncel</button> --}}
                                        <button class="btn btn-primary">Update</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (hasPermission('project_'.$task_type, 'jobcard'))
            <div class="modal fade" id="addJobCardModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-lg ">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Job Card</h5>
                            <button type="button" class="close close_jc" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form class="w-100 p-0" id="task_form" enctype="multipart/form-data"
                                action="{{ route('vendor.documents.job-card.store', ['save', $task->id]) }}"
                                method="post">
                                @csrf
                                <input type="hidden" name="task_id" value="{{ $task->id }}">
                                <input type="hidden" name="customer" value="{{ $task->client_id }}">
                                <input type="hidden" name="employee_id" value="{{ $task->employee_id }}">
                                @include('vendor-views.forms.job_card_add')
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary done_jc">Save</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        @endif
        <div class="modal fade" id="serviceReportModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-lg ">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add Service Report</h5>
                        <button type="button" class="close close_jc" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0"> 
                        <form class="w-100 p-0" id="ck_editor_form" enctype="multipart/form-data"
                            action="{{ route('vendor.documents.service-report.store-lead', ['save', $task->id]) }}"
                            method="post">
                            @csrf
                            <input type="hidden" name="task_id" value="{{ $task->id }}">
                            <input type="hidden" name="customer" value="{{ $task->client_id }}">
                            <input type="hidden" name="employee_id" value="{{ $task->employee_id }}">
                            @include('document_templates.service_report_generate')
                            <div class="modal-footer">
                                <button type="submit" id="formSubmitButton" class="btn btn-primary ">Save</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
        @include('vendor-views.form_modals.inventory_item_select')
        @if (hasPermission('project_'.$task_type, 'receivable_receipt'))
            <div class="modal fade" id="addReceivableRModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Receivable Receipt</h5>
                            <button type="button" class="close close_rr" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>  
                        <div class="modal-body p-1">
                            <form class="w-100 p-0" id="task_form" enctype="multipart/form-data"
                                action="{{ route('vendor.documents.receivable-receipt.store-lead', ['save', $task->id]) }}"
                                method="post">
                                @csrf

                                <input type="hidden" name="task_id" value="{{ $task->id }}">
                                <input type="hidden" name="customer" value="{{ $task->client_id }}">
                                <input type="hidden" name="employee_id" value="{{ $task->employee_id }}">
                                @include('vendor-views.forms.receivable_receipt_add')
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary done_rr">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
    <!-- Modal -->
    <div class="modal fade" id="addAttachmentModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Attachment</h5>
                    <button type="button" class="close close_att_modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if (!$task_closed)
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            @if (_isEnabled('task_service_reports'))
                                @if ($data['service_report'] && $data['service_report']->pdf)
                                @else
                                    <button class="btn btn-info btn_sm px-3 py-2 add_attachment" data-toggle="modal"
                                        data-target="#serviceReportModal">
                                        <i class="tio-add me-1"></i> Add Service Report
                                    </button>
                                @endif
                            @endif
                            @if (_isEnabled('task_recievable_receipt'))

                                @if (hasPermission('project_'.$task_type, 'receivable_receipt'))
                                    @if ($data['receipt'] && $data['receipt']->pdf)
                                    @else
                                        <button class="btn btn--warning btn_sm px-3 py-2 add_attachment"
                                            data-toggle="modal" data-target="#addReceivableRModal">
                                            <i class="tio-add me-1"></i> Add Receipt
                                        </button>
                                    @endif
                                @endif
                            @endif
                            @if (_isEnabled('task_quotation'))

                                @if (hasPermission('project_'.$task_type, 'quotation'))
                                    @if ($data['quotation'] && $data['quotation']->pdf)
                                    @else
                                        <button class="btn btn-success btn_sm px-3 py-2 add_attachment"
                                            data-toggle="modal" data-target="#quotationModal">
                                            <i class="tio-add me-1"></i> Add Quotation
                                        </button>
                                    @endif
                                @endif
                            @endif
                            @if (_isEnabled('task_invoice'))

                                @if (hasPermission('project_'.$task_type, 'invoice'))
                                    @if ($data['invoice'] && $data['invoice']->pdf)
                                    @else
                                        <button class="btn btn--primary btn_sm px-3 py-2 add_attachment" data-toggle="modal"
                                            data-target="#addInvoiceModal">
                                            <i class="tio-add me-1"></i> Add Invoice
                                        </button>
                                    @endif
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="basicDetModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-2">
                        @if ($file)
                            <div class="col-lg-3 col-md-4">
                                <div class="task-file-preview">
                                    @if (in_array($extension, $image_extensions))
                                        <div class="position-relative rounded overflow-hidden">
                                            <img style="width: 100%; max-width: 150px; aspect-ratio: 1;"
                                                class="object-fit-cover onerror-image"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                src="{{ $file_path }}" alt="Task Image">
                                        </div>
                                    @elseif($extension == 'pdf')
                                        <div class="border rounded p-3 text-center bg-light">
                                            <i class="tio-document text-primary" style="font-size: 2.5rem;"></i>
                                            <p class="mt-2 mb-2 small fw-semibold">PDF Document</p>
                                            <a href="{{ $file_path }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="tio-visible me-1"></i> View
                                            </a>
                                        </div>
                                    @elseif(in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'csv']))
                                        <div class="border rounded p-3 text-center bg-light">
                                            <i class="tio-attachment text-success" style="font-size: 2.5rem;"></i>
                                            <p class="mt-2 mb-2 small fw-semibold">{{ strtoupper($extension) }}
                                                File
                                            </p>
                                            <a href="{{ $file_path }}" target="_blank"
                                                class="btn btn-sm btn-outline-success">
                                                <i class="tio-download me-1"></i> Download
                                            </a>
                                        </div>
                                    @else
                                        <div class="border rounded p-3 text-center bg-light">
                                            <i class="tio-clear text-muted" style="font-size: 2.5rem;"></i>
                                            <p class="text-muted mt-2 mb-0 small">Unsupported file type</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class=" d-flex flex-column align-items-center justify-content-center    ">

                            <h3>{{ $task->title }}</h3>
                            <p>{{ $task->description }}</p>
                            <p><b>Time Estimation :</b>{{ $task->time_count . ' ' . $task->time_unit . '(s)' }}</p>
                            <p><b>Created At :</b>{{ $task->created_at }}</p>
                        </div>
                        <a href="{{ route('vendor.project.task.edit', [$task->id]) }}"
                            class="btn btn-outline-primary action-btn"><i class="tio-edit"></i></a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <a href="{{ route('vendor.project.task.edit', [$task->id]) }}" class="btn btn-primary">Edit</a>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="additionalDetModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Additional Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if (!empty($dynamicFieldsBySections))

                        @foreach ($dynamicFieldsBySections as $section)
                            <div class="section-wrapper mb-4">

                                <div class="section-fields ps-3">
                                    @foreach ($section['fields'] as $field)
                                        <div class="field-row mb-3">
                                            <strong>{{ $field['label'] }}:</strong>
                                            <div class="ms-2 d-inline-block">
                                                @if ($field['value'] === null || $field['value'] === '')
                                                    <span class="text-muted">Not provided</span>
                                                @elseif($field['type'] === 'number')
                                                    {{ number_format($field['value']) }}
                                                @elseif($field['type'] === 'select')
                                                    <span class="badge badge-soft-primary">{{ $field['value'] }}</span>
                                                @elseif($field['type'] === 'textarea')
                                                    <p class="mb-0">{{ $field['value'] }}</p>
                                                @elseif($field['type'] === 'checkbox')
                                                    {{ $field['value'] ? '✓ Yes' : '✗ No' }}
                                                @elseif($field['type'] === 'file')
                                                    <a href="{{ $field['value'] }}" target="_blank"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-download"></i> View File
                                                    </a>
                                                @elseif($field['type'] === 'date')
                                                    {{ date('M d, Y', strtotime($field['value'])) }}
                                                @else
                                                    {{ $field['value'] }}
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        No Additional Details
                    @endif

                </div>
                @if (!empty($dynamicFieldsBySections))
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <a href="{{ route('vendor.project.task.edit', [$task->id]) }}" class="btn btn-primary">Edit</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    @include('vendor-views/js/custom-buttons-js')

    <script>
        $(".add_attachment").on('click', function() {
            $('.close_att_modal').click()
        })

        function addMoreRRRow(item = null) {
            var $lastItemRow = $('.item_row').last();
            var dataId = $lastItemRow.length ? Number($lastItemRow.data('id')) + 1 : 1;

            var item_name = item?.item_name ?? '';
            var item_id = item?.id ?? '';
            var item_price = item?.selling_price ?? 0;
            var model_number = item?.model_number ?? '';
            var readonly = item ? 'readonly' : '';

            var html = `
    <tr class="item_row row_${dataId}" data-id="${dataId}">
        <td class="p-1">
            <input type="file" accept="image/*" name="image[${dataId - 1}]" class="form-control">
        </td>

        <td class="p-1">
            <div class="webcam_wrapper">
                <div>
                    <button type="button" class="btn btn-primary openWebcam">Open Webcam</button>
                    <button type="button" class="btn btn-primary capture" style="display:none;">Capture Photo</button>
                    <button type="button" class="btn btn-primary takePhoto" style="display:none;">Take Photo (Mobile)</button>
                </div>

                <div class="form-row my-2 webcam_section">
                    <!-- ✅ Corrected multiple file naming -->
                    <input type="file" name="webcam_file[${dataId - 1}][]" class="hiddenFile" multiple hidden>
                    <video class="webcam" autoplay playsinline style="display:none; width:300px;"></video>
                    <canvas class="snapshot" style="display:none;"></canvas>
                    <div class="previewContainer" style="margin-top:10px; "></div>
                </div>
            </div>
        </td>

        <td class="p-1">
            <input type="text" name="pr_name[]" value="${item_name}" placeholder="Product Name" class="form-control" ${readonly}>
        </td>

        <td class="p-1">
            <input type="text" name="brand[]" placeholder="Brand / Model" class="form-control" value="${model_number}">
        </td>

        <td class="p-1">
            <input type="number" step="0.001" value="${item_price}" name="value[]" placeholder="Value (₹)" class="form-control">
        </td>

        <td class="p-1">
            <input type="text" name="serial_no[]" placeholder="Serial No" class="form-control">
        </td>

        <td class="p-1">
            <textarea name="issue_for[]" class="form-control" placeholder="Received For (Issue)"></textarea>
        </td>

        <td class="p-1">
            <textarea name="accessories_given[]" class="form-control" placeholder="Accessories Given"></textarea>
        </td>

        <td class="p-1">
            <button type="button" onclick="deleteRow(${dataId})" class="btn action-btn btn--danger btn-outline-danger">
                <i class="tio-delete-outlined"></i>
            </button>
        </td>
    </tr>`;

            $('.rrrows_parent').append(html);
        }

        var tasks = [{
                id: 1,
                title: 'Design Homepage',
                children: [{
                        id: 3,
                        title: 'Create wireframes',
                        children: []
                    },
                    {
                        id: 4,
                        title: 'Design mockups',
                        children: []
                    }
                ]
            },
            {
                id: 2,
                title: 'Setup Database',
                children: []
            }
        ];
        var expandedTasks = new Set([1]); // Expand first task by default to show example
        var draggedTask = null;
        var dropPosition = null; // 'above', 'below', 'inside', or 'root'

        function findTaskById(id, taskList) {
            if (!taskList) taskList = tasks;
            for (var i = 0; i < taskList.length; i++) {
                var task = taskList[i];
                if (task.id === id) return task;
                var found = findTaskById(id, task.children);
                if (found) return found;
            }
            return null;
        }

        function findTaskParent(taskId, taskList, parent) {
            if (!taskList) taskList = tasks;
            for (var i = 0; i < taskList.length; i++) {
                var task = taskList[i];
                if (task.id === taskId) return parent;
                var found = findTaskParent(taskId, task.children, task);
                if (found) return found;
            }
            return null;
        }

        function removeTaskFromParent(taskId, taskList) {
            if (!taskList) taskList = tasks;
            var result = [];
            for (var i = 0; i < taskList.length; i++) {
                var task = taskList[i];
                if (task.id !== taskId) {
                    var newChildren = removeTaskFromParent(taskId, task.children);
                    result.push({
                        id: task.id,
                        title: task.title,
                        children: newChildren
                    });
                }
            }
            return result;
        }

        function insertTaskAtPosition(taskToInsert, targetTaskId, position, taskList) {
            if (!taskList) taskList = tasks;

            if (position === 'root') {
                return taskList.concat([taskToInsert]);
            }

            var result = [];
            for (var i = 0; i < taskList.length; i++) {
                var task = taskList[i];

                if (task.id === targetTaskId) {
                    if (position === 'above') {
                        result.push(taskToInsert);
                        result.push(task);
                    } else if (position === 'below') {
                        result.push(task);
                        result.push(taskToInsert);
                    } else if (position === 'inside') {
                        var newChildren = task.children.slice();
                        newChildren.push(taskToInsert);
                        result.push({
                            id: task.id,
                            title: task.title,
                            children: newChildren
                        });
                    }
                } else {
                    result.push({
                        id: task.id,
                        title: task.title,
                        children: insertTaskAtPosition(taskToInsert, targetTaskId, position, task.children)
                    });
                }
            }
            return result;
        }

        function isDescendant(parent, potentialChild) {
            for (var i = 0; i < parent.children.length; i++) {
                var child = parent.children[i];
                if (child.id === potentialChild.id) return true;
                if (isDescendant(child, potentialChild)) return true;
            }
            return false;
        }

        function handleDragStart(e, task) {
            draggedTask = task;
            e.target.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        }

        function handleDragEnd(e) {
            e.target.classList.remove('dragging');
            clearAllDropIndicators();
            draggedTask = null;
            dropPosition = null;
        }

        function clearAllDropIndicators() {
            var elements = document.querySelectorAll('.drag-over, .drag-over-above, .drag-over-below, .drag-over-root');
            for (var i = 0; i < elements.length; i++) {
                elements[i].classList.remove('drag-over', 'drag-over-above', 'drag-over-below', 'drag-over-root');
            }
        }

        function getDropPosition(e, taskElement) {
            var rect = taskElement.getBoundingClientRect();
            var y = e.clientY - rect.top;
            var height = rect.height;

            if (y < height * 0.25) {
                return 'above';
            } else if (y > height * 0.75) {
                return 'below';
            } else {
                return 'inside';
            }
        }

        function handleTaskDragOver(e, task) {
            e.preventDefault();
            e.stopPropagation();

            if (!draggedTask) return;

            clearAllDropIndicators();

            var position = getDropPosition(e, e.currentTarget);

            // Prevent dropping on self or descendants
            if (draggedTask.id === task.id || isDescendant(draggedTask, task)) {
                return;
            }

            dropPosition = position;

            if (position === 'above') {
                e.currentTarget.classList.add('drag-over-above');
            } else if (position === 'below') {
                e.currentTarget.classList.add('drag-over-below');
            } else {
                e.currentTarget.classList.add('drag-over');
            }
        }

        function handleTaskDrop(e, targetTask) {
            var parent_id = targetTask.id;
            var child_id = draggedTask.id;

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.get({
                url: "{{ route('vendor.project.task.subtask.update-level') }}",
                data: {
                    parent_id: parent_id,
                    child_id: child_id
                },
                success: function(data) {

                },
            });

            e.preventDefault();
            e.stopPropagation();

            if (!draggedTask || !dropPosition) return;

            clearAllDropIndicators();

            // Prevent dropping on self or descendants
            if (draggedTask.id === targetTask.id || isDescendant(draggedTask, targetTask)) {
                return;
            }

            tasks = removeTaskFromParent(draggedTask.id);
            tasks = insertTaskAtPosition(draggedTask, targetTask.id, dropPosition, tasks);

            if (dropPosition === 'inside') {
                expandedTasks.add(targetTask.id);
            }

            draggedTask = null;
            dropPosition = null;
            renderTasks();
        }

        function handleAreaDragOver(e) {
            e.preventDefault();

            if (!draggedTask) return;

            // Check if we're over a task element
            var taskElement = e.target.closest('.task-item');
            if (taskElement) return;

            clearAllDropIndicators();
            e.currentTarget.classList.add('drag-over-root');
            dropPosition = 'root';
        }

        function handleAreaDrop(e) {
            e.preventDefault();

            if (!draggedTask || !dropPosition) return;

            clearAllDropIndicators();

            if (dropPosition === 'root') {
                tasks = removeTaskFromParent(draggedTask.id);
                tasks.push(draggedTask);
            }

            draggedTask = null;
            dropPosition = null;
            renderTasks();
        }

        function toggleExpand(taskId) {
            if (expandedTasks.has(taskId)) {
                expandedTasks.delete(taskId);
            } else {
                expandedTasks.add(taskId);
            }
            renderTasks();
        }

        function deleteTask(taskId) {
            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: 'You want to delete this subtask?',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    fetch('/task/subtask/delete/' + taskId)
                        .then(response => response.json())
                        .then(data => {
                            tasks = removeTaskFromParent(taskId);
                            renderTasks();
                        });
                }
            })
        }

        function addNewTask() {
            var input = document.getElementById('newTaskInput');
            var title = input.value.trim();

            // submit form 
            let formData = new FormData($("#add_task_form")[0]);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: $("#add_task_form").attr('action'),
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(data) {
                    if (data.errors) {
                        for (let i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else if (data.status) {
                        $("#addSubtaskModal").modal('hide')
                        if (title) {
                            var newTask = {
                                id: data.id,
                                title: title,
                                children: []
                            };
                            tasks.push(newTask);
                            input.value = '';
                            renderTasks();
                        }
                    }
                },
            });


        }

        function createTaskElement(task, level) {
            if (level === undefined) level = 0;
            var isExpanded = expandedTasks.has(task.id);
            var hasChildren = task.children.length > 0;

            var taskDiv = document.createElement('div');
            taskDiv.className = 'task-item';
            taskDiv.draggable = true;
            taskDiv.style.marginLeft = (level * 32) + 'px';

            taskDiv.addEventListener('dragstart', function(e) {
                handleDragStart(e, task);
            });
            taskDiv.addEventListener('dragend', handleDragEnd);
            taskDiv.addEventListener('dragover', function(e) {
                handleTaskDragOver(e, task);
            });
            taskDiv.addEventListener('drop', function(e) {
                handleTaskDrop(e, task);
            });
            const taskDetailUrl = "{{ route('vendor.project.task.subtask.detail', [':id']) }}";


            var html = '<svg class="grip-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            html += '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>';
            html += '</svg>';

            if (hasChildren) {
                html += '<button class="expand-btn" onclick="toggleExpand(' + task.id + ')">';
                html += '<svg class="chevron ' + (isExpanded ? 'expanded' : '') +
                    '" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                html += '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>';
                html += '</svg>';
                html += '</button>';
            } else {
                html += '<div style="width: 32px;"></div>';
            }

            html += '<div class="task-content">';
            html += '<div>' + (task.title ?? '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>';
            if (hasChildren) {
                html += '<div class="task-subtitle">' + task.children.length + ' subtask';
                if (task.children.length !== 1) html += 's';
                html += '</div>';
            }
            console.log(task)
            @if (hasPermission('project_subtask', 'view'))
                html += '<a class="text-primary text-underline" href="' + taskDetailUrl.replace(':id', task.id) +
                    '">View</a>';
            @endif
            html += '<br><small><i class="created_by">Created By :  ' + task.created_by_name + '</i></small>';
            html += '</div>';

            @if (hasPermission('project_subtask', 'delete'))
                html += '<button class="delete-btn" onclick="deleteTask(' + task.id + ')">';
                html += '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                html +=
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
                html += '</svg>';
                html += '</button>';
            @endif

            taskDiv.innerHTML = html;
            return taskDiv;
        }

        function renderTasks() {
            var container = document.getElementById('tasksContainer');
            var emptyState = document.getElementById('emptyState');

            container.innerHTML = '';

            if (tasks.length === 0) {
                emptyState.style.display = 'block';
                return;
            }

            emptyState.style.display = 'none';

            function renderTaskRecursive(task, level) {
                if (level === undefined) level = 0;
                var taskElement = createTaskElement(task, level);
                container.appendChild(taskElement);

                if (task.children.length > 0 && expandedTasks.has(task.id)) {
                    for (var i = 0; i < task.children.length; i++) {
                        renderTaskRecursive(task.children[i], level + 1);
                    }
                }
            }

            for (var i = 0; i < tasks.length; i++) {
                renderTaskRecursive(tasks[i]);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var tasksArea = document.getElementById('tasksArea');

            tasksArea.addEventListener('dragover', handleAreaDragOver);
            tasksArea.addEventListener('drop', handleAreaDrop);
            tasksArea.addEventListener('dragleave', function(e) {
                if (!tasksArea.contains(e.relatedTarget)) {
                    clearAllDropIndicators();
                }
            });

            document.getElementById('newTaskInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    addNewTask();
                }
            });

            fetch('{{ route('vendor.project.task.subtask.getTasks', $task->id) }}')
                .then(response => response.json())
                .then(data => {
                    tasks = data;
                    renderTasks();
                });
        });
    </script>
    <script>
        // Pass data from Laravel to JavaScript
        window.initialTasks = @json($tasks);
    </script>
    <script>
        $(document).ready(function() {
            const $afInput = $('#af-input-xy12');
            const $afRange = $('#af-range-xy12');
            const $afBar = $('#af-bar-xy12');
            const $afLabel = $('#af-label-xy12');
            const $taskId = {{ $task->id }};

            function afUpdateProgress(value, save = false) {

                const safeValue = Math.min(Math.max(parseInt(value) || 0, 0), 100);
                $afBar.css('width', safeValue + '%');
                $afInput.val(safeValue);
                $afRange.val(safeValue);
                $afLabel.text(safeValue + '%');

                if (save) {
                    $.ajax({
                        url: '{{ route('vendor.project.task.save-progress') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            task_id: $taskId,
                            progress: safeValue
                        },
                        success: function(response) {
                            $(".info_text").text('Saved')
                            setTimeout(() => {
                                $(".info_text").text('')
                            }, 1000);
                        },
                        error: function(xhr) {
                            console.error('Save failed', xhr.responseText);
                        }
                    });
                }
            }

            $afInput.on('input', function() {
                afUpdateProgress($(this).val(), true);
            });

            $afRange.on('input', function() {
                afUpdateProgress($(this).val(), true);
            });

            afUpdateProgress({{ $progress }});
        });
        $(document).on('keyup change', ".cash_amount, .online_amount, .price, .qty, .payment_mode", function() {
            validateAmount($(this).closest('form'));

        });

        function validateAmount(form) {
            console.log(form)
            amountElem = $(form).find("#totalWithGST");
            console.log(amountElem)

            if ($("input[name='payment_mode']:checked").val() == 'Cash and Online') {
                var totalAmt = parseFloat(amountElem.text().replace(/,/g, '')) || 0;

                var cash = parseFloat($(".cash_amount").val()) || 0;
                var online = parseFloat($(".online_amount").val()) || 0;

                var entered = cash + online;

                if (entered > totalAmt) {
                    $(".amount_error").text("Amount is greater than total!");
                    $(".submit_btn").attr('disabled', true)
                } else if (entered < totalAmt) {
                    $(".amount_error").text("Amount is less than total!");
                    $(".submit_btn").prop("disabled", true);
                } else {
                    $(".amount_error").text("");
                    $(".submit_btn").removeAttr("disabled");
                }
            } else {
                $(".amount_error").text("");
                $(".submit_btn").removeAttr("disabled");
            }
        }
    </script>
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/video/lg-video.umd.min.js"></script> --}}
    {{-- <script>
        document.querySelectorAll('.lightgallery').forEach(gallery => {
            lightGallery(gallery, {
                plugins: [lgVideo], // Add lgVideo plugin
                thumbnail: true,
                animateThumb: true,
                showThumbByDefault: true,
                thumbWidth: 80,
                thumbHeight: "auto",
                videojs: true // Enable video support
            });
        });
    </script> --}}
    @include('vendor-views/billing/basic-inoice-js')
    @include('vendor-views/quote/quote-js')
    <script>
        if ($("#task_status").val() != 'Completed' && $("#task_status").val() != 'Cancelled') {
            const countdownEl = document.getElementById("countdown");
            const targetTime = parseInt(countdownEl.dataset.target) * 1000;

            function formatTime(ms) {
                const totalSec = Math.abs(Math.floor(ms / 1000));
                const days = Math.floor(totalSec / (3600 * 24));
                const hours = Math.floor((totalSec % (3600 * 24)) / 3600);
                const minutes = Math.floor((totalSec % 3600) / 60);
                const seconds = totalSec % 60;

                return [
                    days > 0 ? `${days}d` : '',
                    hours > 0 ? `${hours}h` : '',
                    `${minutes}m`,
                    `${seconds}s`
                ].filter(Boolean).join(' ');
            }

            function updateCountdown() {
                const now = new Date().getTime();
                const diff = targetTime - now;

                if (diff <= 0) {
                    countdownEl.classList.remove("active");
                    countdownEl.classList.add("expired");
                    countdownEl.innerHTML =
                        `<small>Exceeded by</small> <br><span class="time_text">${formatTime(diff)}</span>`;
                } else {
                    countdownEl.classList.remove("expired");
                    countdownEl.classList.add("active");
                    countdownEl.innerHTML =
                        ` <small>Time left</small><br><span class="time_text">${formatTime(diff)}</span>`;
                }
            }

            updateCountdown(); // Initial call
            setInterval(updateCountdown, 1000);
        }

        $(".status_update_form").on("submit", function(e) {
            console.log('fsdf')
            e.preventDefault();
            let formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
            });
            $.post({
                url: $(this).attr("action"),
                processData: false,
                contentType: false,
                async: false,
                cache: false,
                data: formData,
                success: function(data) {
                    console.log(data)
                    console.log(data.action)
                    if (data.action == 'otp_sent') {
                        $("#action_inp").val('verify_otp')
                        $(".stts_form").css('visibility', 'hidden').css('height', '0px')
                        $('.otp_inp').show()
                        $('.action').val('verify_otp')
                        $('#product_delivered').attr('required', true)
                    } else if (data.status) {
                        toastr.success(data.msg);
                        setTimeout(() => {
                            window.location.reload()
                        }, 500);
                    } else {
                        toastr.error(data.msg);

                    }
                },
            });

        });
        $(document).on('input', '.otp-input', function(e) {
            const $inputs = $('.otp-input');
            const index = $inputs.index(this);

            if (this.value.length === this.maxLength && index < $inputs.length - 1) {
                $inputs.eq(index + 1).focus();
            }
        });
        $('#addMoreBtn').on('click', function() {
            var lastid = $(".service_table .service_tr:last").attr('id');
            var myArray = lastid.split("_");

            var id = Number(myArray[myArray.length - 1]) + 1;
            //   <td><input type="text" placeholder="rate" class="form-control sr_inp" name="service_rate[]"/></td>
            var selectHtml = $('.sr_pr_name').html();
            var invSelectHtml = $('.inv_select').html();
            var html = `<tr class="service_tr" id="tr_` + id +
                `">
                <td style="width:250px ; padding:3px;"><select  class="form-control sr_pr_name  js-select2-custom" id="item_` +
                id + `" name="service_name[]">` +
                selectHtml +
                `</select></td>
                        <td style="width:250px ; padding:3px;"><select  class="form-control  js-select2-custom inv_select" id="inv_item_` +
                id + `" name="inventory_item[]">` +
                invSelectHtml + `</select></td>
                <td style="width: 120px; ; padding:3px;"><input type="text" placeholder="qty" class="form-control sr_inp" name="service_qty[]"/></td>
                <td style="padding:3px;"><input type="text" placeholder="unit" class="form-control sr_inp" name="service_unit[]"/></td>
              
                <td  style="padding:3px;"><input type="text" placeholder="amount" class="form-control sr_inp" name="service_amount[]"/></td>
                <td><button class="btn btn-sm btn-danger" type="button" onclick="deleteRow(` + id + `)">x</button></td>
            </tr>`;
            $('.service_table').append(html)
            $('.inv_item_' + id).select2()
            $('.item_' + id).select2()
        })


        $('.add_from_inv').on('click', function() {
            $('#inventoryItemModal').modal('show');
            $('#addJobCardModal').modal('hide');

            $('#inventoryItemModal').on('hidden.bs.modal', function() {
                $('#addJobCardModal').modal('show'); // ✅ show again
                $(this).off('hidden.bs.modal');
            });
        });

        function add_inv_items() {
            var selectedData = $('#inventory_items').select2('data');
            let totalRequests = selectedData.length;
            let completed = 0;

            if (totalRequests === 0) {
                $('#inventoryItemModal').modal('hide');
                return;
            }
            $(".inv_item_add_btn").attr('disabled', true);
            $('.inv_item_add_btn').html('<i class="tio-spinner tio-spin"></i> Adding...');
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            selectedData.forEach(function(item) {
                $.post({
                    url: "{{ route('vendor.inventory.get-item-info') }}",
                    data: {
                        id: item.id,
                    },
                    success: function(data) {
                        addMoreJCRow(data);
                    },
                    complete: function() {
                        completed++;
                        if (completed === totalRequests) {
                            $('#inventory_items').val(null).trigger('change');
                            $('.inv_modal_close').click()

                            $(".inv_item_add_btn").removeAttr('disabled');
                            $('.inv_item_add_btn').html('Add');
                        }

                    }
                });
            });
        }



        // Initialize LightGallery for all elements with a specific class
        document.querySelectorAll('.lightgallery').forEach(el => {
            lightGallery(el, {
                plugins: [lgZoom],
                zoom: true
            });
        });
        $('.add_from_inv').on('click', function() {
            $('#inventoryItemModal').modal('show');
            $('#addJobCardModal').modal('hide');

            $('#inventoryItemModal').on('hidden.bs.modal', function() {
                $('#addJobCardModal').modal('hide');
                $(this).off('hidden.bs.modal'); // Unbind to avoid duplicate events
            });
        });
        $(".statusImage").on('change', function() {
            if (this.files.length > 5) {
                toastr.error('You can upload a maximum of 5 images.');
                $(".img_err").text('You can upload a maximum of 5 images.')
                $(this).val(''); // reset input
            } else {
                $(".img_err").text('')

            }
        });
    </script>

    @include('vendor-views/js/jobcard-js');
    @include('vendor-views/multiple_ck_editor');
@endpush
