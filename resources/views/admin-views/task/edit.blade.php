@extends('layouts.admin.app')

@section('title', 'Edit Task')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        #custom-fields .form-group input {
            width: 230px;
            padding: 5px;
            height: 31px;
            margin-left: 5px;
        }

        .small_field {
            width: 170px;
            padding: 5px;
            height: 31px;
        }

        #custom-fields .form-group label {
            width: 230px;
        }

        #custom-fields .form-group {
            display: flex;
            margin-bottom: 1rem;
        }

        .custom-header-btn {
            margin: 5px 5px 5px 0;
            font-size: 10px;
            padding: 2px;
            font-weight: 500;
            border-radius: 20px;
        }

        .form-row {
            margin-top: 6px;
        }

        #custom-fields {}
    </style>
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/vendor/task_add.css">
@endpush

@section('content')

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="tf-page-header mt-2">
            <h1 class="tf-page-title">{{ $project ? $project->project_title : 'Task Manager' }}</h1>
            @if (!$project)
                <div class="task-id">Task Id: #{{ $task->task_id }}</div>
            @endif
        </div>

        <form class="w-100" id="task_form" enctype="multipart/form-data"
            action="{{ $task->parent_id ? route('admin.task.subtask.update') : route('admin.task.update') }}"
            method="post">
            @csrf
            @if ($project)
                <input type="hidden" name="project_id" value="{{ $project->id }}">
            @endif

            <input type="hidden" name="task_id_old" value="{{ $task->id }}">

            <div class="form-wrapper">
                <!-- Left Panel - Quick Info -->
                <div class="left-panel">
                    <!-- Client & Task Source -->
                    <div class="card">
                        <h4 class="tf-card-title">👥 {{!$project ? 'Client' : 'Assignee'}} Info</h4>
                        <div class="card-body p-3">
                            <div class="row g-0">
                            @if(!$project)

                                <div class=" col-md-6 p-1">
                                    @php
                                        if ($task->user) {
                                            $style = 'style=display:none';
                                            $style2 = '';
                                        } else {
                                            $style = '';
                                            $style2 = 'style=display:none';
                                        }
                                    @endphp

                                    <label for="client" class="tf-label">
                                        Client <span class="tf-required">*</span>
                                    </label>
                                    <div class="upgrade-card cust_det_card" {{ $style2 }}>
                                        <div class="close-btn card_close_btn">&times;</div>
                                        <div class="customer_info">
                                            <h6 class="mb-0">{{ $task->user?->f_name . ' ' . $task->user?->l_name }}</h6>
                                            <p class="mb-0" style="font-size:12px">{{ $task->user?->phone }}</p>
                                        </div>
                                    </div>
                                    <input type="hidden" name="customer" id="old_customer">
                                    <div class="info-group customer_select_grp with_add_new " {{ $style }}>
                                        <div class="customer_elem_inner">
                                            <select id="customer_id" data-placeholder="Select client" name="customer"
                                                class="tf-select js-select2-custom tf-select">
                                                <option value=""></option>
                                                <option value="add_new">+ Add New Client</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                @endif


                                <div class=" col-md-6 p-1">
                                    <label for="assignee" class="tf-label">
                                        Assignee
                                        <span class="info-icon"
                                            title="The assignment will remain pending until accepted">?</span>
                                    </label>
                                    <select id="employee_id" name="employee_id" class="tf-select js-select2-custom ">
                                        <option value=""></option>
                                        <option value="add_new">+ Add New Employee</option>
                                        {{-- <option value="0">Self</option> --}}
                                        @foreach ($staff as $key => $s)
                                            <option {{ $s->id == $task->employee_id ? 'selected' : '' }}
                                                {{ $s->id == $task->offered_to ? 'selected' : '' }}
                                                value="{{ $s->id }}">
                                                {{ $s->f_name . ' ' . $s->l_name . ' | ' . $s->role?->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @if(!$project)

                                <div class=" col-md-6 p-1">
                                    <label for="where_from" class="tf-label">📍 Source</label>
                                    <select id="where_from" name="where_from" class="tf-select">
                                        @foreach ($data['where_from'] as $key => $value)
                                            {{ $value }}
                                            <option {{ $task->where_from == $value ? 'selected' : '' }}
                                                value="{{ $value }}">{{ $value }}
                                            </option>
                                        @endforeach
                                        <option {{ $task->where_from == 'amc' ? 'selected' : '' }} value="amc">AMC
                                        </option>
                                        <option {{ $task->where_from == 'on_call' ? 'selected' : '' }} value="on_call">On
                                            Call</option>
                                        <option {{ $task->where_from == 'my_chitti' ? 'selected' : '' }} value="my_chitti">
                                            My Chitti</option>
                                    </select>
                                </div>
                                @endif

                            </div>
                        </div>
                    </div>

                    <!-- Status Card -->


                </div>

                <!-- Right Panel - Details -->
                <div class="right-panel">
                    <!-- Basic Information -->
                    <div class="card">
                        <h4 class="tf-card-title">📋 Task Details</h4>
                        <div class="card-body p-3">
                            <div class="row g-0">
                                <div class=" col-md-4 p-1">
                                    <label for="title" class="tf-label">
                                        Title / Task Name <span class="tf-required">*</span>
                                    </label>
                                    <select id="title" name="title" required
                                        class="tf-select js-select2-custom-tags tf-select">
                                        <option value=""></option>
                                        @foreach ($titles as $key => $title)
                                            <option {{ $title->title == $task->title ? 'selected' : '' }}
                                                value="{{ $title->title }}">{{ $title->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class=" col-md-4 p-1">
                                    <label for="cost" class="tf-label">💰 Est. Cost</label>
                                    <input value="{{ $task->task_amount }}" type="number" id="cost"
                                        name="task_amount" placeholder="0.000" step="0.001" class="tf-input">
                                </div>
                                <div class="col-md-4 p-1">
                                    <label for="file" class="tf-label">📎 File @if ($task->file)
                                            <a target="_blank"
                                                href="{{ asset('storage/app/public/task') . '/' . $task->file }}"
                                                title="View Current File" class="text-underline">View Current File
                                            </a>
                                        @endif
                                    </label>
                                    <input type="file" id="file" name="file" class="tf-input">
                                </div>
                            </div>

                            <div class="row g-0">
                                <div class="col-md-4 p-1">
                                    <label for="description" class="tf-label">📝 Description</label>
                                    <select id="description" name="description" data-placeholder="Select or type new"
                                        class="tf-select js-select2-custom-tags tf-select">
                                        <option value=""></option>
                                        @foreach ($data['descriptions'] as $key => $desc)
                                            <option {{ $desc->description == $task->description ? 'selected' : '' }}
                                                value="{{ $desc->description }}">{{ $desc->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 p-1">
                                    <label for="time" class="tf-label">⏱ Est. Time </label>
                                    <div class="input-group">
                                        <input type="number" value="{{ $task->time_count }}" id="time"
                                            name="time_count" placeholder="Ex: 24" class="tf-input">
                                        <select name="time_unit" class="tf-select">
                                            <option {{ $task->time_unit == 'hour' ? 'selected' : '' }} value="hour">
                                                Hours
                                            </option>
                                            <option {{ $task->time_unit == 'day' ? 'selected' : '' }} value="day">Days
                                            </option>
                                            <option {{ $task->time_unit == 'week' ? 'selected' : '' }} value="week">
                                                Weeks
                                            </option>
                                            <option {{ $task->time_unit == 'month' ? 'selected' : '' }} value="month">
                                                Months
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div id="dynamicFormContainer" data-form="task_form">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden"></span>
                    </div>
                </div>
            </div>
            <div class="card my-2">
                <h4 class="tf-card-title">🏷 Status & Progress</h4>
                <div class="card-body p-3">
                    <div class="row g-0">
                        <div class="col-md-3 p-1">
                            <label for="progress" class="tf-label">📊 Progress (%)</label>
                            <input type="number" id="progress" name="progress" placeholder="0 - 100" min="0"
                                max="100" class="tf-input" value="{{ $task->progress }}">
                        </div>
                        <div class="col-md-3 p-1">
                            <label for="status" class="tf-label d-flex justify-content-between">Current Status
                                <a class=" text-primary  text-underline" data-toggle="modal"
                                    data-target="#taskStatusModal">Add more status</a>
                            </label>
                            <select id="status" name="status" class="tf-select">
                                <option {{ $task->status == 'New' ? 'selected' : '' }} value="New">New</option>
                                @if ($statuses)
                                    @foreach (explode(',', $statuses) as $key => $stts)
                                        <option {{ $task->status == $stts ? 'selected' : '' }}
                                            value="{{ $stts }}">{{ $stts }}</option>
                                    @endforeach
                                @endif
                                <option {{ $task->status == 'Completed' ? 'selected' : '' }} value="Completed">
                                    Completed</option>
                                <option {{ $task->status == 'Cancelled' ? 'selected' : '' }} value="Cancelled">
                                    Cancelled</option>
                            </select>
                        </div>


                        {{-- <div class="col-md-3 p-1">
                            <label for="start_date" class="tf-label">Start Date</label>
                            <input type="date" id="start_date" class="tf-input">
                        </div>

                        <div class="col-md-3 p-1">
                            <label for="due_date" class="tf-label">Due Date</label>
                            <input type="date" id="due_date" class="tf-input">
                        </div> --}}
                    </div>
                </div>
            </div>
            <!-- Action Buttons -->
            <div class="tf-actions">
                {{-- <button type="button" data-toggle="modal" data-target="#addReceivableRModal"
                    class="btn btn--warning">📄 Receivable Receipt</button> --}}
                <button type="submit" class="btn btn-primary">💾 Update Task</button>
            </div>
            @include('admin-views/form_modals/job_card_modal')
            @include('admin-views/form_modals/receivable_receipt_modal')
        </form>
    </div>

    @include('admin-views/form_modals/task_status')
    @include('admin-views.form_modals.inventory_item_select')

    @include('admin-views/form_modals/employee_modal')

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    <script>
        $(document).ready(function() {
            $('#statusSelect').select2({
                placeholder: 'Subcategory',
                {{-- maximumSelectionLength: 3, --}}
                tags: true,
                width: '100%'
            });
        });
        $(".card_close_btn").on('click', function() {
            $(".cust_det_card").hide()
            $(".customer_select_grp").show()
            $("#old_customer").val('').trigger('change')
        })
        $(document).on('change', '#employee_id', function() {
            var selectedVal = $(this).val();
            if (selectedVal === 'add_new') {
                $('#addEmployeeModal').modal('show');
                return;
            }
        });
        $(document).on('change', '#customer', function() {
            var selectedVal = $(this).val();
            if (selectedVal === 'add_new') {
                $('#addCustomerModal').modal('show');
                return;
            }
        });
        $(document).ready(function() {
            $('#employee_id , #customer').on('select2:open', function() {
                setTimeout(function() {
                    const $addNewOption = $('li.select2-results__option[id$="-add_new"]');
                    console.log($addNewOption);

                    $addNewOption.css({
                        'color': 'rgb(13, 96, 252)',
                        'font-weight': 'bold'
                    });
                }, 0);
            });

        });
        $(document).ready(function() {
            $('.js-example-tags').select2({
                tags: true,
                tokenSeparators: [','],
                placeholder: "Select or add tags",
                allowClear: true,
                maximumSelectionLength: 6
            });
        });
        $(document).ready(function() {
            $('#custom-buttons').on('click', 'button', function() {
                const label = $(this).data('label');
                let inputGroup = '';

                if (label === 'Other') {
                    inputGroup = `
        <div class="form-group custom-field" data-label="${label}">
            <div class="d-flex mb-2">
                <input type="text" class="form-control mr-2" placeholder="Label" name="header_label[]">
                <input type="text" class="form-control mr-2" name="header_field[]">
                <a type="button" class="text-danger remove-field"><i class="tio-delete-outlined"></i></a>
            </div>
        </div>
        `;

                } else {
                    const inputName = label.toLowerCase().replace(/\s+/g, '_'); // e.g., vehicle_no

                    inputGroup = `
        <div class="form-group custom-field" data-label="${label}">
            <label for="${inputName}">${label}</label>
            <div class="d-flex fld_grp">
                <input type="hidden" name="header_label[]" value="${label}" id="${label}">
                <input type="text" class="form-control mr-2" name="header_field[]" id="${inputName}">
                <a type="button" class="text-danger remove-field"><i class="tio-delete-outlined"></i></a>
            </div>
        </div>
        `;
                    // Hide the clicked button
                    $(this).hide();
                }
                console.log(label)

                $('#custom-fields').append(inputGroup);
            });

            //Handle remove
            $('#custom-fields').on('click', '.remove-field', function() {
                console.log('remove')
                const $fieldGroup = $(this).closest('.custom-field');
                const label = $fieldGroup.data('label');

                // Show back the corresponding button
                $('#custom-buttons button').each(function() {
                    if ($(this).data('label') === label) {
                        $(this).show();
                    }
                });

                $fieldGroup.remove();
            });

        });
    </script>
    @include('admin-views.js.edit-form-render')
@endpush
