@extends('layouts.vendor.app')

@section('title', 'Create Jobcard')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .w_50 {
            width: 49%;
        }
        #custom-fields .form-group input {
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

        #custom-fields .form-group label {
            width: 165px;
            background: white;
            margin: 0px 7px;
            padding: 4px;
            font-weight: bold;
            border-radius: 4px;
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
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Create Jobcard</h1>

        </div>
        <!-- End Page Header -->
        <div class="row g-2">

            <form class="w-100 p-0 d-flex flex-wrap" id="task_form" enctype="multipart/form-data"
                action="{{ route('vendor.documents.job-card.store') }}" method="post">
                @csrf
                <div class="col-12 row mb-3">
                    <div class="form-row col-md-3">
                        <label for="exampleInputEmail1">Client</label>
                        <select name="customer" id="customer_id" class="form-control js-select2-custom">
                            <option value="0">---{{ translate('messages.select') }}---</option>
                            <option v alue="add_new">+ Add New Client</option>

                        </select>
                    </div>
                    <div class="form-row col-md-3">
                        <label for="exampleInputEmail1">Recieved By</label>
                        <select name="employee_id" id="employee_id" required
                            data-placeholder="{{ translate('messages.select _staff') }}"
                            class="form-control js-select2-custom ">
                            <option value="0" selected>---{{ translate('messages.select') }}---</option>
                            <option value="add_new">+ Add New Employee</option>
                            <option value="0">Self</option>
                            @foreach ($staff as $key => $s)
                                <option value="{{ $s->id }}">
                                    {{ $s->f_name . ' ' . $s->l_name . ' | ' . $s->role?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row col-md-2 d-flex flex-column">
                        <label class="d-block mb-2 font-weight-bold">For</label>
                        <div class="d-flex border" style="    padding: 12px;border-radius: 4px;">
                            <div class="form-check form-check-inline ">
                                <input class="form-check-input" type="radio" name="for_type" id="for_task" value="task"
                                    checked>
                                <label class="form-check-label" for="for_task">Task</label>
                            </div>
                            <div class="form-check form-check-inline ">
                                <input class="form-check-input" type="radio" name="for_type" id="for_lead"
                                    value="lead">
                                <label class="form-check-label" for="for_lead">Lead</label>
                            </div>
                        </div>
                    </div>

                    <div class=" form-row col-md-3" id="task_section">
                        <label for="task_id">Task <span class="text-danger">*</span></label>
                        <select name="task_id" id="task_id" class="form-control js-select2-custom"
                            data-placeholder="{{ translate('messages.select_task') }}" required>
                            <option value="" selected>--- {{ translate('messages.select') }} ---</option>
                            @foreach ($data['tasks'] as $key => $s)
                                <option value="{{ $s->id }}">{{ $s->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class=" form-row col-md-3" id="lead_section" style="display:none;">
                        <label for="lead_id">Lead <span class="text-danger">*</span></label>
                        <select name="lead_id" id="lead_id" class="form-control js-select2-custom"
                            data-placeholder="{{ translate('messages.select_lead') }}">
                            <option value="" selected>--- {{ translate('messages.select') }} ---</option>
                            @foreach ($data['leads'] as $key => $l)
                                <option value="{{ $l->service_request_id }}">#{{ $l->service_request_id }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @include('vendor-views.forms.job_card_add')
                <div class="d-flex justify-content-end w-100 mt-3 align-items-center">
                    <button type = "submit" class="mx-1 btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    @include('vendor-views/form_modals/employee_modal')

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
    <script>
        $(document).ready(function() {
            $('input[name="for_type"]').on('change', function() {
                if ($(this).val() === 'task') {
                    $('#task_section').show();
                    $('#lead_section').hide();
                    $('#lead_id').prop('required', false);
                    $('#task_id').prop('required', true);
                } else {
                    $('#lead_section').show();
                    $('#task_section').hide();
                    $('#task_id').prop('required', false);
                    $('#lead_id').prop('required', true);
                }
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


        $("#view_preview").on('click', function(e) {
            e.preventDefault();

            var originalForm = $('#task_form');

            // Create a new form
            var form = $('<form>', {
                action: '{{ route('vendor.documents.receivable-receipt.preview') }}',
                method: 'POST',
                enctype: 'multipart/form-data',
                target: '_blank'
            });

            // Add CSRF token
            form.append($('<input>', {
                type: 'hidden',
                name: '_token',
                value: $('meta[name="csrf-token"]').attr('content')
            }));

            // Append text inputs (from serializeArray)
            originalForm.serializeArray().forEach(function(input) {
                form.append($('<input>', {
                    type: 'hidden',
                    name: input.name,
                    value: input.value
                }));
            });

            // ✅ Manually append file inputs
            originalForm.find('input[type="file"]').each(function() {
                const name = $(this).attr('name');
                const files = $(this)[0].files;

                if (files.length > 0) {
                    for (let i = 0; i < files.length; i++) {
                        // Create a file input element and assign the file using DataTransfer (optional)
                        const fileInput = $('<input>', {
                            type: 'file',
                            name: name
                        })[0];

                        // Use DataTransfer if needed — for advanced browser support
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(files[i]);
                        fileInput.files = dataTransfer.files;

                        form.append(fileInput);
                    }
                }
            });

            // Add your custom fields
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

            // Append and submit
            $('body').append(form);
            form[0].submit();
            form.remove();
        });

        $(document).ready(function() {
            $('#statusSelect').select2({
                placeholder: 'Subcategory',
                maximumSelectionLength: 3,
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
        $(document).on('change', '#customer', function() {
            var selectedVal = $(this).val();
            if (selectedVal === 'add_new') {
                $('#addCustomerModal').modal('show');
                return;
            }
        });
        $(document).ready(function() {
            $('#employee_id ,#task_category, #customer').on('select2:open', function() {
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
        $(document).on('change', "#task_category", function() {
            var selectedVal = $(this).val();
            if (selectedVal === 'add_new') {
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
    </script>
    @include('vendor-views/js/jobcard-js');

    @include('vendor-views/js/custom-buttons-js')
@endpush
