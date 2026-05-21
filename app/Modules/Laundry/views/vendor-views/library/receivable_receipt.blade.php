@extends('layouts.vendor.app')

@section('title', 'Create Recivable Receipt')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
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
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Create Receivable Receipt</h1>

        </div>
        <!-- End Page Header -->
        <div class="row g-2">
            <form class="w-100 p-0" id="task_form" enctype="multipart/form-data"
                action="{{ route('vendor.documents.receivable-receipt.store') }}" method="post">
                @csrf
                <div class=" mb-2">
                    <div class="card h-100">
                        <div class="card-body row pt-0">

                            <div class="form-row col-md-3">
                                <label for="exampleInputEmail1">Client</label>
                                <select name="customer" id="customer_id" class="form-control js-select2-custom">
                                    <option value="0">---{{ translate('messages.select') }}---</option>
                                    <option value="add_new">+ Add New Client</option>

                                </select>
                            </div>
                            {{-- <div class="form-row col-md-3">
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
                            </div> --}}
                            <div class="form-row col-md-2 d-flex flex-column">
                                <label class="d-block mb-2 font-weight-bold">For</label>
                                <div class="d-flex border" style="    padding: 12px;border-radius: 4px;">
                                    <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="for_type" id="for_task"
                                            value="task" checked>
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
                        @include('vendor-views/forms/receivable_receipt_add')
                    </div>
                </div>
        </div>

        <div class="d-flex justify-content-end mt-3 align-items-center">
            <button id="view_preview" type="button" class="mx-1 btn btn-outline-primary">View Preview</button>
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


        function deleteRow(rowId) {
            $('[data-id="' + rowId + '"]').remove()
        }

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

        function addMoreRRRow(item = null) {
            console.table(item)
            var $lastItemRow = $('.item_row').last();
            if (!$lastItemRow.length) {
                var dataId = 1;
            } else {
                var dataId = Number($lastItemRow.data('id')) + 1;
            }
            var item_name = '';
            var item_id = '';
            var item_price = '';
            var item_hsn = '';
            var readonly = '';
            var model_number = '';
            if (item) {
                item_name = item.item_name ?? '';
                item_price = item.selling_price ?? 0;
                readonly = 'readonly';
                item_id = item.id;
                model_number = item.model_number ?? '';
            }
            var html = `<tr class="item_row row_` + dataId + `" data-id="` + dataId + `">
                                            <td class="p-1"><input type="file" accept="image/*" name="image[` + (
                dataId - 1) + `]" class="form-control">
                <td>  <div class="webcam_wrapper row col-md-4 p-3">
                        <div class="">
                            <label>Webcam</label><br>
                            <button type="button" class="btn btn-primary openWebcam">Open Webcam</button>
                            <button type="button" class="btn btn-primary capture" style="display:none;">Capture Photo</button>
                            <button type="button" class="btn btn-primary takePhoto" style="display:none;">Take Photo
                                (Mobile)</button>
                        </div>

                        <div class="form-row my-2 webcam_section">
                            <input type="file" name="webcam_file[${dataId - 1}][]" class="hiddenFile" multiple hidden>
                            <video class="webcam" autoplay playsinline style="display:none; width:300px;"></video>
                            <canvas class="snapshot" style="display:none;"></canvas>
                            <div class="previewContainer" style="margin-top:10px; "></div>
                        </div>
                    </div>
                </td>

    
                      <td class="p-1"><input type="text" name="pr_name[]" value="` + item_name + `" placeholder="Product Name"
                                class="form-control">
                        </td>
                        <td class="p-1"><input type="text" name="brand[]" placeholder="Brand / Model"
                                class="form-control" value="` + model_number + `">
                        </td>
                          <td class="p-1"><input type="number" step="0.001" value="` + item_price + `" name="value[]" placeholder="Value (₹)" class="form-control"></td>

                        <td class="p-1"><input type="text" name="serial_no[]"
                                placeholder="Serial No" class="form-control">
                        </td>
                        <td class="p-1">
                        <textarea name="issue_for[]" class="form-control" id=""  placeholder="Received For (Issue)"></textarea>
                        </td>
                        <td class="p-1">
                        <textarea name="accessories_given[]" id="" placeholder="Accessories Given" class="form-control"></textarea>
                        </td>

                      <td class="p-1"><button type="button" onclick="deleteRow(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></button></td>
                    </tr>`;

            $('.rrrows_parent').append(html)
        }

        function add_inv_items() {
            var selectedData = $('#inventory_items').select2('data');
            let totalRequests = selectedData.length;
            let completed = 0;

            if (totalRequests === 0) {
                $('#inventoryItemModal').modal('hide');
                return;
            }

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
                        addMoreRRRow(data);
                    },
                    complete: function() {
                        completed++;
                        if (completed === totalRequests) {
                            $('#inventory_items').val(null).trigger('change');
                            $('.inv_modal_close').click()
                        }
                    }
                });
            });
        }
    </script>
@endpush
