 <div class=" container-fluid tf-compact-wrapper">
        <!-- Page Header -->
        <div class="page-header w-100 flex-wrap d-flex justify-content-between tf-page-header">
            <h1 class="page-header-title tf-page-title"> <i class="tio-add-square-outlined"></i> Task
            </h1>
            <div class="page-header-select-wrapper">
                <div class="d-flex">
                <h4>Task Id : 
                #{{ $data['task_id'] ?? '' }}
                </h4>
                    {{-- <label for="exampleInputEmail1" class="tf-label">Task ID</label> --}}
                    {{-- <input type="text" readonly placeholder="Ex: MYS-TASK-001" value="{{ $data['task_id'] ?? '' }}" name="task_id"
                        class="form-control tf-input"> --}}
                </div>
                {{-- <button  class="btn mx-2 btn_sm btn--primary  " data-toggle="modal"
                    data-target="#taskStatusModal">Statuses</button> --}}
            </div>
        </div>

        @include('vendor-views/form_modals/task_status')
        @include('vendor-views.form_modals.inventory_item_select')

        <div class="row g-2">
            <form class="w-100" id="task_form" enctype="multipart/form-data" action="{{ route('vendor.project.task.store') }}"
                method="post">
                @csrf
                <input type="hidden" name="quotation_check" id="quotation_check">
                <input type="hidden" name="receivable_receipt" id="receivable_receipt">
                <input type="hidden" name="job_card" id="job_card">

                <!-- Receivable Receipt -->
                {{-- <div class="mb-2 tf-receipt-bar">
                    <div class="h-100">
                        <div class="d-flex align-items-center">
                            
                        </div>
                    </div>
                </div> --}}

                <!-- Client & Assignee Information -->
                <div class="mb-2">
                    <div class="card h-100 tf-card p-0">
                        <h4 class=" tf-card-title m-0 p-3">Client & Task Source Information</h4>
                        <div class="p-1 pt-0">
                            <div class="upgrade-card cust_det_card col-md-3 col-sm-6 m-3 tf-customer-card"
                                style="display:none">
                                <div class="close-btn card_close_btn tf-close-btn">&times;</div>
                                <div class="customer_info"></div>
                            </div>

                            <div class="d-flex row g-0 ">
                                <div class=" col-md-4 p-2 ">
                                    <div class="customer_elem_inner">
                                        <label for="exampleInputEmail1" class="tf-label">Client<span
                                                class="text-danger tf-required">*</span></label>
                                        <select name="customer" id="customer_id" required data-placeholder="Select client"
                                            class="form-control js-select2-custom tf-select">
                                            <option value=""></option>
                                            <option value="add_new">+ Add New Client</option>
                                        </select>
                                    </div>
                                </div>

                                <div class=" col-md-4 p-2">
                                    <label for="exampleInputEmail1" class="tf-label">
                                        Assignee
                                        <span class="input-label-secondary text--title tf-info-icon" data-toggle="tooltip"
                                            data-placement="right"
                                            data-original-title="{{ translate('The assignment will remain pending until the assignee accepts it.') }}">
                                            <i class="tio-info-outined"></i>
                                        </span>
                                    </label>
                                    <select name="employee_id" id="employee_id"
                                        data-placeholder="{{ translate('messages.select staff') }}"
                                        class=" js-select2-custom tf-select">
                                        <option value=""></option>
                                        <option value="add_new">+ Add New Employee</option>
                                        <option value="0">Self</option>
                                        @foreach ($staff as $key => $s)
                                            <option value="{{ $s->id }}">
                                                {{ $s->f_name . ' ' . $s->l_name . ' | ' . $s->role?->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                 <div class="col-md-4 p-2">
                                    <label for="exampleInputEmail1" class="tf-label">Where From</label>
                                    <select name="where_from" data-placeholder="Select Where From" id="where_from"
                                        class="form-control js-select2-custom-tags tf-select">
                                        <option value="amc">AMC</option>
                                        <option value="on_call">On Call</option>
                                        <option value="my_chitti">My Chitti</option>
                                    </select>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="mb-2">
                    <div class="card h-100 tf-card p-0">
                        <h4 class=" tf-card-title m-0 p-3">Basic Information</h4>
                        <div class="card-body row p-3 g-0 pt-0">
                            <div class="row g-0 d-flex ">
                                <div class=" col-md-3 p-2">
                                    <label for="exampleInputEmail1" class="tf-label">
                                        Title / Task Name <span class="text-danger tf-required">*</span>
                                    </label>
                                    <select required data-placeholder="Select or type new" name="title" id="title"
                                        class="form-control js-select2-custom-tags tf-select">
                                        <option value=""></option>
                                        @foreach ($titles as $key => $title)
                                            <option value="{{ $title->title }}">{{ $title->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 p-2">
                                    <label for="exampleInputEmail1" class="tf-label">Description</label>
                                    <select name="description" data-placeholder="Select or type new" id="description2"
                                        class="form-control js-select2-custom-tags tf-select">
                                        <option value=""></option>
                                        @foreach ($data['descriptions'] as $key => $desc)
                                            <option value="{{ $desc->description }}">{{ $desc->description }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 p-2 ">
                                    <label for="exampleInputEmail1" class="tf-label">File</label>
                                    <input type="file" name="file" class="form-control tf-input">
                                </div>

                                <div class="col-md-3 p-2 ">
                                    <label for="exampleInputEmail1" class="tf-label">Estimated Cost</label>
                                    <input type="number" name="task_amount" placeholder="Ex: 1200" step="0.001"
                                        class="form-control tf-input">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress and Status -->
                <div class="mb-2">
                    <div class="card h-100 tf-card p-0">
                        <h4 class=" tf-card-title m-0 p-3">Progress and Status</h4>
                        <div class="card-body pt-0">
                            <div class="row g-0 d-flex">
                                <div class=" col-md-4 p-2">
                                    <label for="exampleInputEmail1" class="tf-label">Time Estimation</label>
                                    <div class="input-group tf-input-group">
                                        <input type="number" class="form-control tf-input" name="time_count"
                                            placeholder="Ex: 3" aria-label="Ex: 3">
                                        <select name="time_unit" class="form-control tf-select">
                                            <option value="hour">Hours</option>
                                            <option value="day">Days</option>
                                            <option value="week">Weeks</option>
                                            <option value="month">Months</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="  col-md-4 p-2">
                                    <label for="exampleInputEmail1" class="tf-label">Progress (%)</label>
                                    <input type="number" name="progress" placeholder="Ex: 10"
                                        class="form-control tf-input">
                                </div>

                                <div class="  col-md-4 p-2">
                                    <label for="exampleInputEmail1" class="tf-label d-flex justify-content-between">Status
                                        <a class=" text-primary  text-underline" data-toggle="modal"
                                            data-target="#taskStatusModal">Add more status</a>
                                    </label>
                                    <select name="status" id="status"
                                        class="form-control js-select2-custom tf-select">
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
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end w-100 mt-3 align-items-center tf-actions">
                    {{-- <button id="view_job_card" type="button" class="mx-1 btn btn-outline-primary  -outline">
                        View as Jobcard
                    </button> --}}
                    <a class="btn btn--warning cursor-pointer btn_sm  -warning" data-toggle="modal"
                        data-target="#addReceivableRModal">Receivable Receipt</a>
                    <button type="submit" class="mx-1 btn btn-primary  ">Save Task</button>
                </div>

                @include('vendor-views/form_modals/job_card_modal')
                @include('vendor-views/form_modals/receivable_receipt_modal')
            </form>
        </div>
    </div>