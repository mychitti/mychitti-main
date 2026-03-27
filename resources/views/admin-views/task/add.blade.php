@extends('layouts.admin.app')

@section('title', 'Add Task')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .select2-selection.select2-selection--multiple {
            height: fit-content !important;
        }

        .upgrade-card {
            background-color: #f0f4ff;
            color: #333;
            border-radius: 12px;
            padding: 15px;
            width: 320px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        .upgrade-card h4 {
            margin-top: 0;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 18px;
        }

        .upgrade-card p {
            font-size: 14px;
            margin-bottom: 16px;
            line-height: 1.4;
        }

        .upgrade-card .btn {
            background-color: #5a75f8;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }

        .upgrade-card .btn:hover {
            background-color: #4a65e0;
        }

        .upgrade-card .close-btn {
            position: absolute;
            top: 14px;
            right: 16px;
            font-size: 16px;
            color: #777;
            cursor: pointer;
        }

        .upgrade-card .close-btn:hover {
            color: #333;
        }

        .card-body {
            padding: 0.5rem !important;
        }

        {{-- .select2-results__option:last-child {
            color: rgb(90, 123, 186) !important;
            font-weight: bold;
        } --}} #custom-fields . input {
            width: 165px;
            padding: 5px;
            height: 31px;
            margin-left: 5px;
        }

        .small_field {
            width: 170px;
            padding: 5px;
            height: 31px;
        }

        #custom-fields . label {
            width: 165px;
            background: white;
            margin: 0px 7px;
            padding: 4px;
            font-weight: bold;
            border-radius: 4px;
        }

        #custom-fields . {
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

        {{-- . {
            margin-top: 6px;
        } --}} #custom-fields {}
    </style>

    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/vendor/task_add.css">
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Header --> 
        <div class="tf-page-header mt-2">
            <h1 class="tf-page-title"> {{ $project ? $project->project_title : 'Task Manager' }} </h1>
            @if (!$project)
                <div class="task-id">Task Id: #{{ $data['task_id'] }}</div>
            @endif
        </div>

        <form class="w-100" id="task_form" enctype="multipart/form-data" action="{{ route('admin.task.store') }}"
            method="post">
            @csrf
            @if ($project)
                <input type="hidden" name="project_id" value="{{ $project->id }}">
            @endif

            <div class="form-wrapper">
                <!-- Left Panel - Quick Info -->
                <div class="left-panel">
                    <!-- Client & Task Source -->
                    <div class="card">
                        <h4 class="tf-card-title">👥 {{ !$project ? 'Client' : 'Assignee' }} Info</h4>
                        <div class="card-body p-3">
                            <div class="row g-0">
                                @if (!$project)
                                    <div class=" col-md-6 p-1">
                                        <label for="client" class="tf-label">
                                            Client <span class="tf-required">*</span>
                                        </label>
                                        <div class="upgrade-card cust_det_card" style="display:none;">
                                            <div class="close-btn card_close_btn">&times;</div>
                                            <div class="customer_info">
                                            </div>
                                        </div>
                                        <div class="info-group customer_select_grp with_add_new ">
                                            <div class="customer_elem_inner">
                                                <select id="customer_id" name="customer" data-placeholder="Select client"
                                                    class="tf-select">
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
                                            <option value="{{ $s->id }}">
                                                {{ $s->f_name . ' ' . $s->l_name . ' | ' . $s->role?->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if (!$project)
                                    <div class=" col-md-6 p-1">
                                        <label for="where_from" class="tf-label">📍 Source</label>
                                        <select id="where_from" name="where_from" class="tf-select js-select2-custom-tags">
                                            <option value="amc">AMC</option>
                                            <option value="on_call">On Call</option>
                                            <option value="my_chitti">My Chitti</option>
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
                                            <option value="{{ $title->title }}">{{ $title->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class=" col-md-4 p-1">
                                    <label for="cost" class="tf-label">💰 Est. Cost</label>
                                    <input type="number" id="cost" name="task_amount" placeholder="0.00"
                                        step="0.001" class="tf-input">
                                </div>
                                <div class="col-md-4 p-1">
                                    <label for="file" class="tf-label">📎 File</label>
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
                                            <option value="{{ $desc->description }}">{{ $desc->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 p-1">
                                    <label for="time" class="tf-label">⏱ Est. Time </label>
                                    <div class="input-group">
                                        <input type="number" id="time" name="time_count" placeholder="Ex: 24"
                                            class="tf-input">
                                        <select name="time_unit" class="tf-select">
                                            <option value="hour">Hours</option>
                                            <option value="day">Days</option>
                                            <option value="week">Weeks</option>
                                            <option value="month">Months</option>
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
                                max="100" class="tf-input" value="0">
                        </div>
                        <div class="col-md-3 p-1">
                            <label for="status" class="tf-label d-flex justify-content-between">Current Status
                                <a class=" text-primary  text-underline" data-toggle="modal"
                                    data-target="#taskStatusModal">Add more status</a>
                            </label>
                            <select id="status" name="status" class="tf-select">
                                <option value="New">New</option>
                                @if ($statuses)
                                    @foreach (explode(',', $statuses) as $key => $stts)
                                        <option value="{{ $stts }}">{{ $stts }}</option>
                                    @endforeach
                                @endif
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
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
                @if (!$project && _isEnabled('task_recievable_receipt') && hasPermission('task', 'receivable_receipt'))
                    <button type="button" data-toggle="modal" data-target="#addReceivableRModal"
                        class="btn btn--warning">📄 Receivable Receipt</button>
                @endif
                <button type="submit" class="btn btn-primary">💾 Save Task</button>
            </div>
            @include('admin-views/form_modals/job_card_modal')
            @include('admin-views/form_modals/receivable_receipt_modal')
        </form>
    </div>
    @include('admin-views/form_modals/task_status')
    @include('admin-views.form_modals.inventory_item_select')
    @include('admin-views/form_modals/employee_modal')

    @include('admin-views/form_modals/add_task_category')

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    <script>
        {{-- $(".customer_add_form").on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData($(this).get(0));
            formData.append('form_type', 'ajax');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.status) {
                        $('#addCustomerModal').modal('hide')
                        $('#addSignModal').modal('hide')
                        $('.account_modal_close_btn').click()
                        toasterNotification(data.msg)
                        var url = window.location.href;
                        if (data.action == 'add_customer') {
                            $('.with_add_new').load(url + ' .customer_elem_inner', function() {
                                $("#customer_id").select2();
                                $('#customer_id').val(data.customer_id);
                                showUserCard(data.customer)
                            });
                        } else if (data.action == 'add_sign') {
                            $('.sign_section').load(url + ' .sign_section_inner', function() {
                                $("#sign_id2").select2();
                            })
                        } else if (data.action == 'add_bankaccount') {
                            $('.account_section').load(url + ' .account_section_inner', function() {
                                $("#sign_id2").select2();
                            })
                        } else if (data.action == 'add_inventory') {
                            $('.inventory_section').load(url + ' .inventory_section_inner', function() {
                                $(".inv_close_btn").click()
                                $("#inventory_items").select2();
                                $('#inventory_items').val(data.item_id).trigger('change');
                                add_inv_items()
                            })
                        }
                    } else if (data.errors) {
                        for (let i = 0; i < data.errors.length; i++) {
                            toasterNotification(data.errors[i])

                        }
                    }
                },
                error: function(xhr) {
                    console.log('error', xhr.responseText);
                }
            });
        }); --}}

        $(".card_close_btn").on('click', function() {
            $(".cust_det_card").hide()
            $(".customer_select_grp").show()
            $("#customer_id").val('').trigger('change')
            $("#selected_customer_id").val('')
        })
        $(document).ready(function() {
            $('#customer_id').select2({

                placeholder: 'Search for a customer',
                minimumInputLength: 0,
                ajax: {
                    url: "{{ route('admin.client.get-matches') }}", // Change to your endpoint
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term || '' // search term
                        };
                    },
                    processResults: function(data) {
                        // Map your real customers
                        let results = data.map(customer => ({
                            id: customer.id,
                            text: customer.f_name + ' (' + customer.phone + ')'
                        }));

                        // Push the "+ Add New Client" option at the end
                        results.push({
                            id: 'add_new',
                            text: '+ Add New Client'
                        });

                        return {
                            results: results
                        };
                    },
                    cache: true
                }
            });
        });

        $('.done_rr').on('click', function() {
            $('#receivable_receipt').val(1)
            $(".close_rr").click()
        })
        $('.save_quote').on('click', function() {
            $('#quotation_check').val(1)
            $(".inv_close_btn").click()
        })
        $('.done_jc').on('click', function() {
            $('#job_card').val(1)
            $(".close_jc").click()
        })

        function addMoreRRRow(item = null) {

            var $lastItemRow = $('.item_row').last();
            if (!$lastItemRow.length) {
                var dataId = 1;
            } else {
                var dataId = Number($lastItemRow.data('id')) + 1;
            }


            var html = `<tr class="item_row row_` + dataId + `" data-id="` + dataId + `">
                                            <td class="p-1"><input type="file" accept="image/*" name="image[` + (
                dataId - 1) + `]" class="form-control">

    
                      <td class="p-1"><input type="text" name="pr_name[]" placeholder="Product Name"
                                class="form-control">
                        </td>
                        <td class="p-1"><input type="text" name="brand[]" placeholder="Brand / Model"
                                class="form-control">
                        </td>
                          <td class="p-1"><input type="number" step="0.001" name="value[]" placeholder="Value (₹)" class="form-control"></td>

                        <td class="p-1"><input type="text" name="serial_no[]"
                                placeholder="Serial No" class="form-control">
                        </td>
                        <td class="p-1">
                        <textarea name="issue_for[]" class="form-control" id="" placeholder="Received For (Issue)"></textarea>
                        </td>
                        <td class="p-1">
                        <textarea name="accessories_given[]" id="" placeholder="Accessories Given" class="form-control"></textarea>
                        </td>

                      <td class="p-1"><button type="button" onclick="deleteRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

            $('.rrrows_parent').append(html)
        }

        $('.add_from_inv').on('click', function() {
            $('#inventoryItemModal').modal('show');
            $('#addJobCardModal').modal('hide');

            $('#inventoryItemModal').on('hidden.bs.modal', function() {
                $('#addJobCardModal').modal('hide');
                $(this).off('hidden.bs.modal'); // Unbind to avoid duplicate events
            });
        });

        $('#inventoryItemModal').on('hidden.bs.modal', function() {
            $('#addJobCardModal').modal('show');
        });

        $("#view_job_card").on('click', function(e) {
            e.preventDefault();

            // Clone your original form
            var originalForm = $('#task_form');
            var cloneForm = originalForm.clone();

            // Create a new form dynamically
            var form = $('<form>', {
                action: '{{ route('admin.jobcard.view') }}',
                method: 'POST',
                target: '_blank' // open PDF in new tab
            });

            // Add CSRF token
            form.append($('<input>', {
                type: 'hidden',
                name: '_token',
                value: $('meta[name="csrf-token"]').attr('content')
            }));

            // Append all original form inputs
            originalForm.serializeArray().forEach(function(input) {
                form.append($('<input>', {
                    type: 'hidden',
                    name: input.name,
                    value: input.value
                }));
            });

            // Add your extra fields
            form.append($('<input>', {
                type: 'hidden',
                name: 'type',
                value: 'task'
            }));
            form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'view'
            }));

            // Append to body and submit
            $('body').append(form);
            form.submit();
            form.remove();
        });

        $(".customer_add_form").on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData($(this).get(0));
            formData.append('form_type', 'ajax');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.status) {
                        console.log(data)
                        $('#addCustomerModal').modal('hide')
                        toasterNotification(data.msg)
                        var url = window.location.href;

                        if (data.action == 'add_customer') {
                            $('.with_add_new').load(url + ' .customer_elem_inner', function() {
                                $("#customer_id").select2();
                                $('#selected_customer_id').val(data.customer.id);
                                $('#customer_id').val(data.customer.id);
                                showUserCard(data.customer)
                            });
                        }
                    }
                }
            })
        })

        function toasterNotification(msg) {
            $("#toast").text(msg);
            $("#toast").addClass("show");
            setTimeout(function() {
                $("#toast").removeClass("show");
            }, 3000);
        }
        $(document).on('change', '#customer_id', function() {
            var selectedVal = $(this).val();

            if (selectedVal === 'add_new') {
                $('#addCustomerModal').modal('show');
                return;
            } else if (!selectedVal) {
                return;

            }

            var selectedOption = $(this).find('option:selected');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: "{{ route('admin.client.fetch-details') }}",
                type: 'POST',
                data: {
                    id: selectedVal,
                    type: selectedOption.data('type')
                },
                success: function(data) {
                    if(data && data.id) {
                        showUserCard(data);
                    }
                },
                error: function(xhr) {
                    console.log('fetch-details error', xhr.responseText);
                }
            });
        });



        function showUserCard(data) {
            console.log('in showUserCard')
            var html = `<h6>` + data.f_name + `</h6>
                                        <p class="mb-0" style="font-size:12px">` + data.phone + `</p> ` +
                (data.email ? `<p class="mb-0" style="font-size:12px">` + data.email + `</p>` : '');

            $(".customer_select_grp").hide()
            $(".cust_det_card").show()
            $(".customer_info").html(html)
            $("#selected_customer_id").val(data.id)
        }



        $(document).ready(function() {
            $('#statusSelect').select2({
                placeholder: 'Subcategory',
                {{-- maximumSelectionLength: 3, --}}
                tags: true,
                width: '100%'
            });
        });
        $(document).on('change', '#employee_id', function() {
            var selectedVal = $(this).val();
            if (selectedVal === 'add_new') {
                $('#addEmployeeModal').modal('show');
                return;
            }
        });
        $(document).ready(function() {
            $('#employee_id ,#task_category, #customer_id').on('select2:open', function() {
                setTimeout(function() {
                    const $addNewOption = $(
                        'li.select2-results__option[id$="-add_new"]');
                    console.log($addNewOption);

                    $addNewOption.css({
                        'color': 'rgb(13, 96, 252)',
                        'font-weight': 'bold'
                    });
                }, 0);
            });

        });
        $(document).on('change', "#task_category", function() {
            console.log('task_category changed');
            var selectedVal = $(this).val();
            if (selectedVal === 'add_new') {
                console
                $('#taskCatAddModal').modal('show');

            }
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
                    url: "{{ route('admin.inventory.get-item-info') }}",
                    data: {
                        id: item.id,
                    },
                    success: function(data) {
                        addMoreRow(data);
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
        $('#addMoreBtn').on('click', function() {
            var lastid = $(".service_table .service_tr:last").attr('id');
            var myArray = lastid.split("_");

            var id = Number(myArray[myArray.length - 1]) + 1;
            //   <td><input type="text" placeholder="rate" class="form-control sr_inp" name="service_rate[]"/></td>
            var selectHtml = $('.sr_pr_name').html();
            var html = `<tr class="service_tr" id="tr_` + id +
                `">
                <td style="width:250px; padding:3px;"><select  class="form-control sr_pr_name" name="service_name[]">` +
                selectHtml + `</select></td>
                <td style="padding:3px;"><input type="text" placeholder="qty" class="form-control sr_inp" name="service_qty[]"/></td>
                <td style="padding:3px;"><input type="text" placeholder="unit" class="form-control sr_inp" name="service_unit[]"/></td>
              
                <td style="padding:3px;"> <input type="text" placeholder="amount" class="form-control sr_inp" name="service_amount[]"/></td>
                <td style="padding:3px;"><button class="btn btn-sm btn-danger" type="button" onclick="deleteRow2(` +
                id + `)">x</button></td>
            </tr>`;
            $('.service_table').append(html)
        })

        function deleteRow2(id) {
            console.log(id)
            $('#tr_' + id).remove();
        }

        $(".save_quote").on('click', function(e) {
            e.preventDefault();
            console.log('fsd')
        })

        function deleteRow(rowId) {
            $('[data-id="' + rowId + '"]').remove()
        }

        function addMoreRow(item = null) {

            var $lastItemRow = $('.item_row').last();
            if (!$lastItemRow.length) {
                var dataId = 1;
            } else {
                var dataId = Number($lastItemRow.data('id')) + 1;
            }
            if (item) {
                item_name = item.item_name;
                readonly = 'readonly';
                item_id = item.id;
            } else {
                item_name = '';
                item_id = null;
                readonly = '';
            }

            var html = `<tr class="item_row row_` + dataId + `" data-id="` + dataId + `">
            <input type="hidden" name="inventory_item_id[]" value="` + item_id +
                `"  class="form-control">
                      <td class="py-1"><input type="text" ` + readonly + ` name="name[]" placeholder="Name" value="` +
                item_name + `" class="form-control"></td>
                      <td class="py-1"><input type="number" name="qty[]" placeholder="Qty" class="form-control"></td>
                      <td class="py-1"><button type="button" onclick="deleteRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

            $('.rows_parent').append(html)
        }
    </script>
    <script>
        $(document).ready(function() {
            $('#employee_id').on('select2:open', function() {
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
        $(document).on('change', '#employee_id', function() {
            var selectedVal = $(this).val();
            console.log(selectedVal)
            if (selectedVal === 'add_new') {
                $('#addEmployeeModal').modal('show');
                return;
            }
        });
    </script>
    @include('admin-views/js/custom-buttons-js')
    @include('admin-views.js.form-render')
@endpush
