@extends('layouts.admin.app')

@section('title', translate('Add Project'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!--jquery-->
    <style>
        .select2-container--default .select2-selection--single {
            height: 43px;
            border: 1px solid #f0f0f0;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border: solid #e4e4e4 1px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            width: 17px;
            outline: none;
            height: 100%;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            padding: 5px !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
            padding-left: 15px;
        }

        .select2-container--default .select2-selection--multiple {
            border: 1px solid #dfdfdf;
        }
    </style>
    <style>
        #dropArea.drag-over {
            background: #e2f2feff;
            border-color: #0d6efd;
        }

        #dropArea {
            background: #f5fbffff;
            border-color: #0d6efd;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Add Project</h1>

        </div>
        <!-- End Page Header -->
        <div class="row">
            <form class="w-100" action="{{ route('admin.project.save-info') }}" enctype="multipart/form-data" method="post">
                @csrf
                <a data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false"
                    aria-controls="collapseExample">

                    <div class="card-header" style="background-color: #f9d9e9; ">
                        <h5 class="card-title">
                            <span class="card-header-icon"><i class="tio-user"></i></span>
                            <span>Project Details</span>
                        </h5>
                    </div>
                </a>
                <div class="" id="">
                    <div class="card-body" style="border: 2px solid #f9d9e9; ">
                        <div class="row p-0">

                            <!-- Customer -->
                            <div class="form-row col-md-3 py-2">
                                <label>Client <i>(Optional)</i></label>
                                <select name="customer_id" id="customer_id" class="form-control">
                                    <option value="">--- Select ---</option>
                                    <option value="add_new">+ Add New Client</option>
                                </select>
                            </div>

                            <!-- Title -->
                            <div class="form-row col-md-3 py-2">
                                <label>Project Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="Project Title">
                            </div>

                            <!-- Category -->
                            <div class="form-row col-md-3 py-2">
                                <label>Project Category <span class="text-danger">*</span></label>
                                <select data-placeholder="Type or select category" id="project_category"
                                    name="project_category" required class="tf-select js-select2-custom-tags tf-select">
                                    <option value=""></option>
                                    @foreach ($categories as $key => $cat)
                                        <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Priority -->
                            <div class="form-row col-md-3 py-2">
                                <label>Priority Level <span class="text-danger">*</span></label>
                                <select name="priority" class="form-control">
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>

                            <!-- Project Type -->
                            <div class="form-row col-md-3 py-2">
                                <label>Project Type <span class="text-danger">*</span></label>
                                <select name="project_type" class="form-control">
                                    <option value="Technical">Technical</option>
                                    <option value="Organizational">Organizational</option>
                                    <option value="Economical">Economical</option>
                                    <option value="Social">Social</option>
                                    <option value="Mixed">Mixed</option>
                                </select>
                            </div>

                            <!-- Project Manager -->
                            <div class="form-row col-md-3 py-2">
                                <label>Project Manager</label>
                                <select name="project_manager" class="form-control js-select2-custom">
                                    <option value="">-- select --</option>
                                    @foreach ($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->f_name }} {{ $emp->l_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Project Size -->
                            <div class="form-row col-md-3 py-2">
                                <label>Project Size <span class="text-danger">*</span></label>
                                <select name="project_size" class="form-control">
                                    <option value="minor">Minor</option>
                                    <option value="small">Small</option>
                                    <option value="medium">Medium</option>
                                    <option value="large">Large</option>
                                </select>
                            </div>

                            <!-- Progress Status -->
                            <div class="form-row col-md-3 py-2">
                                <label>Progress Status</label>
                                <select name="progress_status" class="form-control js-select2-custom">
                                    <option value="New">New</option>
                                    <option value="Open">Open</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="On Hold">On Hold</option>
                                </select>
                            </div>



                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <a data-toggle="collapse" href="#collapse2222" role="button" aria-expanded="false"
                    aria-controls="collapseExample">

                    <div class="card-header mt-2" style="background-color: #d9f9daff; ">
                        <h5 class="card-title">
                            <span class="card-header-icon"><i class="tio-user"></i></span>
                            <span>Timeline <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                        </h5>
                    </div>
                </a>

                <div class="collapse show" id="collapse2">
                    <div class="card-body" style="border: 2px solid #d9f9daff; ">
                        <div class="row p-2">
                            <div class="form-row col-md-3 py-2">
                                <label>Start Date <span class="text-danger">*</span></label>
                                <input type="date" required name="start_date" class="form-control">
                            </div>

                            <div class="form-row col-md-3 py-2">
                                <label>End Date <span class="text-danger">*</span></label>
                                <input type="date" required name="end_date" class="form-control">
                            </div>

                            <!-- Duration -->
                            <div class="col-md-3">
                                <div class="form-group mb-0">
                                    <label class="input-label" for="exampleFormControlInput1">Duration</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend w-50">
                                            <input type="number" min="1" max="999" name="duration_count"
                                                class="form-control" placeholder="{{ translate('messages.Ex:') }} 5" />
                                        </div>
                                        <div class="input-group-prepend w-50">
                                            <select name="duration_type" id="duration_type"
                                                class="form-control js-select2-custom">
                                                <option value="Months">Months</option>
                                                <option value="Days">Days</option>
                                                <option value="Years">Years</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financials -->
                <a data-toggle="collapse" href="#collapse3" role="button" aria-expanded="false"
                    aria-controls="collapseExample">

                    <div class="card-header mt-2" style="background-color: #f9d9f3ff; ">
                        <h5 class="card-title">
                            <span class="card-header-icon"><i class="tio-user"></i></span>
                            <span>Financials <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                        </h5>
                    </div>
                </a>

                <div class="collapse" id="collapse3">
                    <div class="card-body" style="border: 2px solid #f9d9f3ff; ">

                        <div class="row p-2">
                            <div class="form-row col-md-3 py-2">
                                <label>Payment Type </label>
                                <select name="payment_type" class="form-control js-select2-custom">
                                    <option value="milestones">Milestones</option>
                                    <option value="on_completion">At Completion</option>
                                    <option value="advance">Advance</option>
                                </select>
                            </div>

                            <div class="form-row col-md-3 py-2">
                                <label>Cost Estimate</label>
                                <input type="number" name="cost_estimate" step="0.001" placeholder="Ex: 1000" class="form-control">
                            </div>

                            <div class="form-row col-md-3 py-2">
                                <label>Advance Pay</label>
                                <input type="number" name="advance_pay" step="0.001" placeholder="Ex: 1000" class="form-control">
                            </div>

                            <div class="form-row col-md-3 py-2">
                                <label>Payment Terms </label>
                                <input type="text" name="payment_terms" placeholder="Ex: 30% upfront"
                                    class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team & Roles -->
                <a data-toggle="collapse" href="#collapse4" role="button" aria-expanded="false"
                    aria-controls="collapseExample">

                    <div class="card-header mt-2" style="background-color: #f9ebd9ff; ">
                        <h5 class="card-title">
                            <span class="card-header-icon"><i class="tio-user"></i></span>
                            <span>Team and Roles <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                        </h5>
                    </div>
                </a>

                <div class="collapse" id="collapse4">
                    <div class="card-body" style="border: 2px solid #f9ebd9ff; ">
                        <div class="row p-3">
                            <div class="col-md-3">
                                <label>Team Members </label>
                                <select name="team_members[]" multiple class="form-control js-select2-custom">
                                    @foreach ($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->f_name }} {{ $emp->l_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label>Departments Involved </label>
                                <select name="departments[]" multiple class="form-control js-select2-custom">
                                    @foreach ($departments as $dep)
                                        <option value="{{ $dep->id }}">{{ $dep->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Overview -->
                <a data-toggle="collapse" href="#collapse5" role="button" aria-expanded="false"
                    aria-controls="collapseExample">

                    <div class="card-header mt-2" style="background-color: #d9dbf9ff; ">
                        <h5 class="card-title">
                            <span class="card-header-icon"><i class="tio-user"></i></span>
                            <span>Project Overview <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                        </h5>
                    </div>
                </a>

                <div class="collapse" id="collapse5">
                    <div class="card-body" style="border: 2px solid #d9dbf9ff; ">

                        <div class="row p-3">
                            <div class="form-row col-md-6">
                                <label>Short Description</label>
                                <textarea name="short_description" class="form-control"></textarea>
                            </div>

                            <div class="form-row col-md-6">
                                <label>Detailed Specifications</label>
                                <textarea name="detailed_specs" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                @if (hasPermission('project_milestone', 'add'))
                    <a data-toggle="collapse" href="#collapse6" role="button" aria-expanded="false"
                        aria-controls="collapseExample">

                        <div class="card-header d-flex justify-content-between align-items-center mt-2"
                            style="background-color: #f1f9d9ff; ">
                            <h5 class="card-title">
                                <span class="card-header-icon"><i class="tio-user"></i></span>
                                <span>Milestones <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                            </h5>

                        </div>
                    </a>

                    <div class="collapse" id="collapse6">

                        <div class="card-body" id="milestoneContainer"style="border: 2px solid #f1f9d9ff; ">
                            <div class="d-flex w-100 justify-content-end">
                                <button type="button" class="btn btn-sm btn--primary" id="addMilestone">
                                    + Add Milestone
                                </button>
                            </div>
                            <!-- Default milestone row -->
                            <div class="row milestone-row  p-2 mb-2">
                                <div class="col-md-4">
                                    <label>Milestone Title <span class="text-danger">*</span></label>
                                    <input type="text" name="milestones[0][title]" class="form-control"
                                        placeholder="Ex: Design Phase">
                                </div>

                                <div class="col-md-3">
                                    <label>Due Date <span class="text-danger">*</span></label>
                                    <input type="date" name="milestones[0][due_date]" class="form-control">
                                </div>

                                <div class="col-md-3">
                                    <label>Status</label>
                                    <select name="milestones[0][status]" class="form-control">
                                        <option value="Pending">Pending</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>

                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger removeMilestone "><i
                                            class="tio-delete"></i></button>
                                </div>
                            </div>

                        </div>
                    </div>
                @endif

                <a data-toggle="collapse" href="#collapse7" role="button" aria-expanded="false"
                    aria-controls="collapseExample">

                    <div class="card-header mt-2" style="background-color: #f9dcd9ff; ">
                        <h5 class="card-title">
                            <span class="card-header-icon"><i class="tio-user"></i></span>
                            <span>Atachments <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                        </h5>
                    </div>
                </a>

                <div class="collapse" id="collapse7">
                    <div class="card-body" style="border: 2px solid #f9dcd9ff; ">
                        <div class=" row">

                            <div id="dropArea" class="col-md-6 border border-primary rounded p-4 text-center "
                                style="cursor:pointer;">
                                <h6>Drag & Drop Files Here</h6>
                                <p class="text-muted">PDF, JPG, PNG, Excel, Word (Max 5MB each)</p>
                                <button type="button" class="btn btn-sm btn-primary mt-2">Browse Files</button>
                            </div>

                            <!-- Hidden file input -->
                            <input type="file" id="fileInput" name="attachments[]" multiple
                                accept="
        application/pdf,
        image/jpeg,
        image/jpg,
        image/png,
        application/vnd.ms-excel,
        application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,
        application/msword,
        application/vnd.openxmlformats-officedocument.wordprocessingml.document
    "
                                class="d-none">

                            <!-- Preview List -->
                            <div id="attachmentList" class="mt-3 col-md-6 align-items-start gap-2 row p-2 g-0"></div>

                        </div>
                    </div>
                </div>
                @if (hasPermission('project_internal_note', 'add'))
                    <a data-toggle="collapse" href="#collapse8" role="button" aria-expanded="false"
                        aria-controls="collapseExample">

                        <div class="card-header mt-2" style="background-color: #d9e2f9ff; ">
                            <h5 class="card-title">
                                <span class="card-header-icon"><i class="tio-user"></i></span>
                                <span>Internal Info <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                            </h5>
                        </div>
                    </a>

                    <div class="collapse" id="collapse8">
                        <div class="card-body" style="border: 2px solid #d9e2f9ff; ">
                            <div class="row">

                                <!-- TAGS / LABELS -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Tags / Labels</label>
                                    <div class="d-flex">
                                        <input type="text" id="tagInput" class="form-control"
                                            placeholder="Enter tag and press Add">
                                        <button type="button" id="addTagBtn"
                                            class="btn btn-sm btn--primary mx-2">Add</button>
                                    </div>
                                    <div id="tagContainer" class="mt-2 d-flex gap-1"></div>
                                </div>

                                <!-- INTERNAL NOTES -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Internal Notes (Private)</label>

                                    <div id="internalNotesWrapper">
                                        <div class="note-item mb-2 d-flex">
                                            <input type="text" name="internal_notes[]" class="form-control"
                                                placeholder="Enter internal note">
                                            <button type="button"
                                                class="btn btn-outline-danger btn-sm removeNote ml-2 py-0"><i
                                                    class="tio-delete"></i></button>
                                        </div>
                                    </div>
                                    <div class="d-flex w-100 justify-content-end">
                                        <button type="button" id="addNoteBtn" class="btn btn--primary btn-sm mt-2">Add
                                            Note</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="form-row d-flex justify-content-end col-12 mt-2">
                    <button class="btn btn-primary btn-lg">Save</button>
                </div>
            </form>



        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>

    <script>
        $(document).ready(function() {

            // Add new note
            $("#addNoteBtn").click(function() {
                $("#internalNotesWrapper").append(`
            <div class="note-item mb-2 d-flex">
                <input type="text" name="internal_notes[]" class="form-control" placeholder="Enter internal note">
                <button type="button" class="btn btn-outline-danger btn-sm removeNote ml-2"><i class="tio-delete></i></button>
            </div>
        `);
            });

            // Remove note (works for dynamically added items)
            $(document).on("click", ".removeNote", function() {
                $(this).closest('.note-item').remove();
            });

        });


        let tagIndex = 0;

        $("#addTagBtn").click(function() {
            let tag = $("#tagInput").val().trim();

            if (tag === "") {
                $("#tagInput").focus();
                console.log('fs')
                return;

            }
            let tagHtml = `
            <span class="badge-soft-primary me-2 mb-2 py-0 px-2 tag-item">
                ${tag}
                <input type="hidden" name="tags[]" value="${tag}">
                <button type="button" class="btn btn-close btn-close-white btn-sm ms-1 removeTag">x</button>
            </span>
        `;

            $("#tagContainer").append(tagHtml);
            $("#tagInput").val("");
        });

        // Remove tag
        $(document).on("click", ".removeTag", function() {
            $(this).closest(".tag-item").remove();
        });
    </script>

    @include('admin-views.js.project_milestone_add')


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

    {{-- <script src="https://code.jquery.com/jquery-3.7.0.js" integrity="sha256-JlqSTELeR4TLqP0OG9dxM7yDPqX1ox/HfgiSLBj8+kM="
        crossorigin="anonymous"></script>

    <!--select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}


    <script>
        $(".select2_tags").select2({
            placeholder: "Select Team Members",
        });
        $(".js-select2-custom").select2({
            placeholder: "Select Team Members",
        });
    </script>


    <script>
        {{-- document.getElementById('myNumberInput').addEventListener('keydown', function(event) {
            var value = parseInt(this.value + event.key);

            if (value > 100) {
                event.preventDefault();
            }
        }); --}}
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
    </script>
    <script>
        let maxFileSize = 5 * 1024 * 1024; // 5MB

        $("#dropArea").click(function() {
            $("#fileInput").click();
        });

        $("#fileInput").change(function(e) {
            handleFiles(e.target.files);
        });

        $("#dropArea").on("dragover", function(e) {
            e.preventDefault();
            $(this).addClass("drag-over");
        });

        $("#dropArea").on("dragleave", function(e) {
            $(this).removeClass("drag-over");
        });

        $("#dropArea").on("drop", function(e) {
            e.preventDefault();
            $(this).removeClass("drag-over");

            let files = e.originalEvent.dataTransfer.files;
            handleFiles(files);
        });


        function handleFiles(files) {
            $.each(files, function(i, file) {

                let allowed = [
                    "application/pdf",
                    "image/jpeg",
                    "image/jpg",
                    "image/png",
                    "application/vnd.ms-excel",
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    "application/msword",
                    "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                ];


                if ($.inArray(file.type, allowed) === -1) {
                    alert("Invalid file type: " + file.name);
                    return;
                }

                if (file.size > maxFileSize) {
                    alert("File too large (Max 5MB): " + file.name);
                    return;
                }

                let fileId = Date.now() + "_" + i;

                $("#attachmentList").append(`
                <div class="col-md-5 d-flex justify-content-between align-items-center border rounded p-2 mb-2"
                     id="file_${fileId}">

                    <div>
                        <strong>${file.name}</strong> 
                        <small class="text-muted">(${(file.size/1024/1024).toFixed(2)} MB)</small>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-danger removeAttachment" data-id="${fileId}">
                        <i class="tio-delete"></i>
                    </button>

                </div>
            `);

                // add file to hidden input list 
                appendFileToInput(file);
            });
        }


        let dt = new DataTransfer();

        function appendFileToInput(file) {
            dt.items.add(file);
            document.getElementById('fileInput').files = dt.files;
        }

        $(document).on("click", ".removeAttachment", function() {
            let id = $(this).data("id");

            $("#file_" + id).remove();

            dt.items.remove(0);

            let newDT = new DataTransfer();
            $("#attachmentList div").each(function() {
                let index = $(this).index();
                newDT.items.add(dt.files[index]);
            });

            dt = newDT;
            document.getElementById('fileInput').files = dt.files;
        });
    </script>
@endpush
