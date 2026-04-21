@extends('layouts.admin.app')
@section('title', translate('Employee Edit'))
@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Heading -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/edit.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.Employee_update') }}
                </span>
            </h1>
        </div>
        <!-- Page Heading -->
        <!-- Content Row -->
        <form action="{{ route('admin.users.employee.update', [$employee['id']]) }}" method="post"
            enctype="multipart/form-data" class="js-validate">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <span class="card-header-icon">
                            <i class="tio-user"></i>
                        </span>
                        <span>{{ translate('messages.general_information') }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="row g-3">
                                <div class="col-sm-4">
                                    <label class="input-label text-capitalize"
                                        for="emp_id_field">{{ translate('messages.employee_id') }}
                                        @if (hasPermission('staff_manage', 'settings'))
                                            <span class="text-danger"><a href="{{ route('admin.staff.settings') }}"
                                                    class="text-underline">Edit Prefix?</a></span>
                                        @endif
                                    </label>
                                    <input type="text" name="" class="form-control" id="emp_id_field"
                                        placeholder="Employee ID" readonly value="{{ $employee['employee_id'] ?? '' }}">
                                </div>
                                <div class="col-sm-4">
                                    <label class="input-label qcont" for="name">{{ translate('messages.first_name') }}
                                        <span class="form-label-secondary text-danger" data-toggle="tooltip"
                                            data-placement="right"
                                            data-original-title="{{ translate('messages.Required.') }}"> *
                                        </span> </label>
                                    <input type="text" name="f_name" value="{{ $employee['f_name'] }}"
                                        class="form-control" id="f_name"
                                        placeholder="{{ translate('messages.first_name') }}" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="input-label qcont" for="name">{{ translate('messages.last_name') }}
                                        <span class="form-label-secondary text-danger" data-toggle="tooltip"
                                            data-placement="right"
                                            data-original-title="{{ translate('messages.Required.') }}"> *
                                        </span> </label>
                                    <input type="text" name="l_name" value="{{ $employee['l_name'] }}"
                                        class="form-control" id="l_name"
                                        placeholder="{{ translate('messages.last_name') }}">
                                </div>
                                <div class="col-sm-6">
                                    <div>
                                        <label class="input-label" for="title">{{ translate('messages.zone') }} <span
                                                class="form-label-secondary text-danger" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('messages.Required.') }}"> *
                                            </span> </label>
                                        <select name="zone_id" id="zone_id" class="form-control js-select2-custom">
                                            @if (!isset(auth('admin')->user()->zone_id))
                                                <option value="" {{ !isset($employee->zone_id) ? 'selected' : '' }}>
                                                    {{ translate('messages.all') }}</option>
                                            @endif
                                            @foreach ($zones as $zone)
                                                <option value="{{ $zone['id'] }}"
                                                    {{ $employee->zone_id == $zone->id ? 'selected' : '' }}>
                                                    {{ $zone['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div>
                                        <label class="input-label qcont" for="name">{{ translate('messages.Role') }}
                                            <span class="form-label-secondary text-danger" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('messages.Required.') }}"> *
                                            </span> </label>
                                        <select class="form-control js-select2-custom w-100" name="role_id" id="role_id">
                                            <option value="" selected disabled>
                                                {{ translate('messages.select_Role') }}</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}"
                                                    {{ $role['id'] == $employee['role_id'] ? 'selected' : '' }}>
                                                    {{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div>
                                        <label class="input-label"
                                            for="department_id">{{ translate('messages.department') }}</label>
                                        <select class="form-control js-select2-custom w-100" name="department_id"
                                            id="department_id">
                                            <option value="">{{ translate('messages.select') }}
                                                {{ translate('messages.department') }}</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}"
                                                    {{ $employee['department_id'] == $department->id ? 'selected' : '' }}>
                                                    {{ $department->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="input-label qcont" for="name">{{ translate('messages.phone') }} <span
                                            class="form-label-secondary text-danger" data-toggle="tooltip"
                                            data-placement="right"
                                            data-original-title="{{ translate('messages.Required.') }}"> *
                                        </span> </label>
                                    <input type="number" value="{{ $employee['phone'] }}" required name="phone"
                                        class="form-control" id="phone"
                                        placeholder="{{ translate('messages.Ex:') }} +88017********">
                                </div>
                                <div class="col-md-6">
                                    <label class="input-label text-capitalize" for="documents">Documents <a
                                            type="button" data-toggle="modal" data-target="#imgModal"
                                            class="btn action-btn btn--danger btn-outline-danger "><i
                                                class="tio-visible-outlined"></i></a></label>
                                    <input type="file" multiple name="documents[]" class="form-control"
                                        id="documents">
                                </div>
                                <div class="col-sm-6">
                                    <label class="input-label text-capitalize" for="salary_type">Salary Type</label>
                                    <select name="salary_type" id="salary_type" class="form-control">
                                        <option value="">Select</option>
                                        <option value="Monthly"
                                            {{ ($employee['salary_type'] ?? '') == 'Monthly' ? 'selected' : '' }}>Monthly
                                        </option>
                                        <option value="Hourly"
                                            {{ ($employee['salary_type'] ?? '') == 'Hourly' ? 'selected' : '' }}>Hourly
                                        </option>
                                        <option value="Task-Wise"
                                            {{ ($employee['salary_type'] ?? '') == 'Task-Wise' ? 'selected' : '' }}>
                                            Task-Wise</option>
                                    </select>
                                </div>
                                <div class="col-sm-6" id="base_salary_group">
                                    <label class="input-label text-capitalize" for="base_salary">Base Salary</label>
                                    <input type="number" name="base_salary" placeholder="Ex : 42000" step="0.01"
                                        value="{{ $employee['base_salary'] ?? old('base_salary') }}" class="form-control"
                                        id="base_salary">
                                </div>

                                <div class="modal fade" id="imgModal" tabindex="-1" role="dialog"
                                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel">Uploaded Documnets</h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="d-flex">
                                                    @php $imgs = json_decode($employee['documents']);@endphp
                                                    @if ($imgs)
                                                        @foreach ($imgs as $key => $value)
                                                            @if (in_array(explode('.', $value)[1], ['png', 'jpg', 'jpeg', 'webp']))
                                                                <a class="m-2" style="cursor: zoom-in;"
                                                                    target="_blank"
                                                                    href="{{ asset('storage/app/public/admin/emp_documents/') . '/' . $value }}"><img
                                                                        src="{{ asset('storage/app/public/vendor/documents/') . '/' . $value }}"
                                                                        alt="" width="100px"
                                                                        style="height: 100px ; object-fit:cover;"><br>Image</a>
                                                            @else
                                                                <a class="m-2" target="_blank"
                                                                    href="{{ asset('storage/app/public/admin/emp_documents/') . '/' . $value }}">
                                                                    <div style="width : 100px; border : 1px solid grey ; height: 100px ; object-fit:cover;"
                                                                        class="d-flex align-items-center justify-content-center">
                                                                        file</div> File
                                                                </a>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="h-100 d-flex flex-column">


                                <div class="text-center input-label qcont py-3 my-auto">
                                    {{ translate('messages.Employee_image') }} <small class="text-danger"> (
                                        {{ translate('messages.ratio') }} 1:1 )</small>

                                </div>
                                <div class="text-center py-3 my-auto">
                                    <img class="img--100 onerror-image" id="viewer"
                                        data-onerror-image="{{ asset('/public/assets/admin/img/admin.png') }}"
                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($employee['image'], asset('storage/app/public/admin/') . '/' . $employee['image'], asset('public/assets/admin/img/admin.png'), 'admin/') }}"
                                        alt="Employee thumbnail" />
                                </div>
                                <div class="custom-file">
                                    <input type="file" name="image" id="customFileUpload" class="custom-file-input"
                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                    <span class="custom-file-label">{{ translate('messages.choose_file') }}</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card h-100 my-2">
                <a data-toggle="collapse" href="#collapse2" role="button" aria-expanded="false"
                    aria-controls="collapseExample">

                    <div class="card-header" style="background-color: #e7ebbe;">
                        <h5 class="card-title">
                            <span class="card-header-icon"><i class="tio-user"></i></span>
                            <span>Professional Information <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                        </h5>
                    </div>
                </a>
                <div class="collapse" id="collapse2">
                    <div class="card-body" style="border: 2px solid  #e7ebbe;">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="input-label text-capitalize" for="designation">Designation / Job
                                    Title</label>
                                <input type="text" name="designation" value="{{ $employee['designation'] ?? old('designation') }}"
                                    placeholder="Designation / Job Title" class="form-control" id="designation">
                            </div>
                            <div class="col-md-3">
                                <label class="input-label text-capitalize" for="qualifiexperienceation">Experience</label>
                                <input type="text" name="experience" value="{{ $employee['experience_yrs'] ?? old('experience') }}"
                                    placeholder="Ex: 2 Years" class="form-control" id="experience">
                            </div>
                            <div class="col-md-3">
                                <label class="input-label text-capitalize" for="source">Source of Hire</label>
                                <select name="source" id="source"
                                    class="form-control js-example-tags js-select2-custom">
                                    <option {{$employee['source']  == 'Direct' ? 'selected' : '' }} value="Direct">Direct</option>
                                    <option {{$employee['source']  == 'Newspaper' ? 'selected' : '' }} value="Newspaper">Newspaper</option>
                                    <option {{$employee['source']  == 'Job Portal' ? 'selected' : '' }} value="Job Portal">Job Portal</option>
                                    <option {{$employee['source']  == 'External Referral' ? 'selected' : '' }} value="External Referral">External Referral</option>
                                    <option {{$employee['source']  == 'Employee Referral' ? 'selected' : '' }} value="Employee Referral">Employee Referral</option>
                                    <option {{$employee['source']  == 'Indeed' ? 'selected' : '' }} value="Indeed">Indeed</option>
                                    <option {{$employee['source']  == 'Twitter' ? 'selected' : '' }} value="Twitter">Twitter</option>
                                    <option {{$employee['source']  == 'Facebook' ? 'selected' : '' }} value="Facebook">Facebook</option>
                                    <option {{$employee['source']  == 'Advertisement' ? 'selected' : '' }} value="Advertisement">Advertisement</option>
                                    <option {{$employee['source']  == 'Company Website' ? 'selected' : '' }} value="Company Website">Company Website</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="input-label text-capitalize" for="qualification">Highest
                                    Qualification</label>
                                <input type="text" name="highest_qualification"
                                    placeholder="Enter highest qualification" value="{{ $employee['qualification'] ?? old('highest_qualification') }}"
                                    class="form-control" id="highest_qualification">
                            </div>
                            <div class="col-md-6">
                                <label class="input-label text-capitalize" for="email">Skill Set</label>
                                <select class="js-example-tags" name="skills[]" multiple="multiple">
                                    @php $emp_skills = $employee['skills'] ? explode(',', $employee['skills']) : []; @endphp
                                    <option {{ in_array('Communication', $emp_skills) ? 'selected' : '' }}
                                        value="Communication">Communication</option>
                                    <option {{ in_array('Teamwork', $emp_skills) ? 'selected' : '' }} value="Teamwork">
                                        Teamwork</option>
                                    <option {{ in_array('Problem Solving', $emp_skills) ? 'selected' : '' }}
                                        value="Problem Solving">Problem Solving</option>
                                    <option {{ in_array('Time Management', $emp_skills) ? 'selected' : '' }}
                                        value="Time Management">Time Management</option>
                                </select>

                            </div>

                            <div class="col-md-3">
                                <label class="input-label text-capitalize" for="qualification">Additional
                                    information</label>
                                <input type="text" name="additional_information"
                                    placeholder="Enter additional information"
                                    value="{{  $employee['additional_information'] ?? old('additional_information') }}" class="form-control"
                                    id="additional_information">
                            </div>
                           
                            <div class="form-group mb-0 col-md-3">
                                <label class="input-label text-capitalize " for="main_department">Deparetment </label>
                                <select name="main_department" id="inputState"
                                    class="form-control js-select2-custom js-example-basic-single">
                                    <option {{$employee['main_department'] == 'HR' ? 'selected' :''}} value="HR">HR</option>
                                    <option {{$employee['main_department'] == 'Management' ? 'selected' :''}} value="Management">Management</option>
                                    <option {{$employee['main_department'] == 'Finance' ? 'selected' :''}} value="Finance">Finance</option>
                                    <option {{$employee['main_department'] == 'Marketing' ? 'selected' :''}} value="Marketing">Marketing</option>
                                    <option {{$employee['main_department'] == 'IT' ? 'selected' :''}} value="IT">IT</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="input-label text-capitalize" for="offer_letter">Offer Letter</label>
                                <input type="file" name="offer_letter" class="form-control" id="documents">
                            </div>
                            <div class="col-md-3">
                                <label class="input-label text-capitalize" for="qualification">Tentative Joining
                                    Date</label>
                                <input type="date" name="tentative_joining_date"
                                    value="{{ $employee['tentative_joining_date'] ??  old('tentative_joining_date') }}" class="form-control"
                                    id="tentative_joining_date">
                            </div>
                            {{-- <div class="col-md-3">
                                <label class="input-label text-capitalize" for="employee_type">Employee Type</label>
                                <select name="employee_type" id="employee_type"
                                    class="form-control js-select2-custom js-example-basic-single">
                                    <option {{$employee['employee_type'] == 'permanent' ? 'selected' : ''}} value="permanent" {{ old('employee_type') == 'permanent' ? 'selected' : '' }}>
                                        Permanent</option>
                                    <option {{$employee['employee_type'] == 'temporary' ? 'selected' : ''}} value="temporary" {{ old('employee_type') == 'temporary' ? 'selected' : '' }}>
                                        Temporary</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="employment_end_date_wrap" style="display:none;">
                                <label class="input-label text-capitalize" for="employment_end_date">Employment End
                                    Date</label>
                                <input type="date" name="employment_end_date" id="employment_end_date"
                                    value="{{ $employee['employment_end_date'] ??  old('employment_end_date') }}" class="form-control"
                                    min="{{ date('Y-m-d') }}">
                                <small class="text-muted">Login will be blocked after this date.</small>
                            </div> --}}
                            <div class="col-md-3">
                                <label class="input-label text-capitalize" for="shift">Shift</label>
                                <select name="shift" id="shift"
                                    class="form-control js-select2-custom js-example-basic-single">
                                    <option value=""></option>
                                    @php $shifts = _getStoreShifts() ; @endphp
                                    @foreach ($shifts as $key => $shift)
                                        <option value="{{ $shift->id }}">
                                            {{ $shift->name . ' (' . $shift->start_time . ' to ' . $shift->end_time . ')' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mt-4">
                <a data-toggle="collapse" href="#collapse3" role="button" aria-expanded="false"
                    aria-controls="collapse3">
                    <div class="card-header" style="background-color: #ffddc6;">
                        <h5 class="card-title">
                            <span class="card-header-icon"><i class="tio-user"></i></span>
                            <span>Educational Information <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                        </h5>
                    </div>
                </a>
                <div class="collapse" id="collapse3">
                    <div class="card-body p-2" style="border: 2px solid #ffddc6;">
                        <table class="table edu_tabel table-responsive">
                            <thead style="background:rgb(244, 244, 244);">
                                <tr>
                                    <th scope="col">School Name</th>
                                    <th scope="col">Degree/Diploma</th>
                                    <th scope="col">Fields(s) of Study</th>
                                    <th scope="col">Start Month</th>
                                    <th scope="col">End Month</th>
                                    <th scope="col">Additional Notes</th>
                                    <th scope="col" style="padding:7px;"><button type="button"
                                            class="btn btn-dark btn-sm add_more_btn"
                                            onclick="addMoreRowEmp('education')">Add More</button></th>
                                </tr>
                            </thead>
                            <tbody class="rows_parent">
                                @php $education = json_decode($employee['education']); @endphp
                                @if ($education)
                                    @foreach ($education as $key => $value)
                                        <tr class="item_row_education" data-id="{{ $key + 1 }}">
                                            <td><input type="text" value="{{ $value->school_name ?? '' }}"
                                                    name="school_name[]" placeholder="School Name" class="form-control">
                                            </td>
                                            <td><input type="text" name="degree_diploma[]"
                                                    value="{{ $value->degree_diploma ?? '' }}"
                                                    placeholder="Degree / Diploma / Certificate" class="form-control">
                                            </td>
                                            <td><input type="text" name="field_of_study[]"
                                                    placeholder="Field of Study"
                                                    value="{{ $value->field_of_study ?? '' }}" class="form-control"></td>
                                            <td><input value="{{ $value->start_month ?? '' }}" type="date"
                                                    name="start_month[]" class="form-control"></td>
                                            <td><input value="{{ $value->end_month ?? '' }}" type="date"
                                                    name="end_month[]" class="form-control"></td>
                                            <td><input value="{{ $value->additional_notes ?? '' }}" type="text"
                                                    name="additional_notes[]" placeholder="Additional Notes"
                                                    class="form-control"></td>
                                            <td><a onclick="deleteNewRowEmp({{ $key + 1 }})"
                                                    class="btn action-btn btn--danger btn-outline-danger"><i
                                                        class="tio-delete-outlined"></i></a></td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <a data-toggle="collapse" href="#collapse4" role="button" aria-expanded="false"
                    aria-controls="collapse4">
                    <div class="card-header" style="background-color: #e3efe2;">
                        <h5 class="card-title">
                            <span class="card-header-icon"><i class="tio-user"></i></span>
                            <span>Experience <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                        </h5>
                    </div>
                </a>
                <div class="collapse" id="collapse4">
                    <div class="card-body p-2" style="border: 2px solid #c0e9e6;">
                        <table class="table expr_tabel table-responsive">
                            <thead style="background:rgb(243, 243, 243);">
                                <tr>
                                    <th scope="col">Company Name</th>
                                    <th scope="col">Role</th>
                                    <th scope="col">Summary</th>
                                    <th scope="col">Start Date</th>
                                    <th scope="col">End Date</th>
                                    <th scope="col">Experience Letter</th>
                                    <th scope="col" style="padding:7px;"><button type="button"
                                            class="btn btn-dark btn-sm add_more_btn"
                                            onclick="addMoreRowEmp('experience')">Add More</button></th>
                                </tr>
                            </thead>
                            <tbody class="rows_parent_experience">
                                @php $experience = json_decode($employee['experience']); @endphp
                                @if ($experience)
                                    @foreach ($experience as $key => $value)
                                        <tr class="item_row_experience" data-id="{{ $key + 1 }}">
                                            <td><input value="{{ $value->company_name ?? '' }}" type="text"
                                                    name="company_name[]" placeholder="Company Name"
                                                    class="form-control"></td>
                                            <td><input value="{{ $value->occupation ?? '' }}" type="text"
                                                    name="occupation[]" placeholder="Role" class="form-control"></td>
                                            <td>
                                                <textarea name="summary[]" class="form-control" placeholder="Summary">{{ $value->summary ?? '' }}</textarea>
                                            </td>
                                            <td>
                                                <input value="{{ $value->exp_start_date ?? '' }}" type="date"
                                                    name="exp_start_date[]" class="form-control">
                                                <input type="hidden" name="currently_working[{{ $key + 1 }}]"
                                                    value="0">
                                                <input {{ $value->currently_working ?? 0 ? 'checked' : '' }}
                                                    type="checkbox" onchange="tillPresent(this, {{ $key + 1 }})"
                                                    class="till_present_{{ $key + 1 }}"
                                                    name="currently_working[{{ $key + 1 }}]"
                                                    value="{{ $key + 1 }}">
                                                <label>Till Present</label>
                                            </td>
                                            <td><input value="{{ $value->exp_end_date ?? '' }}"
                                                    {{ $value->currently_working ?? 0 ? 'disabled' : '' }} type="date"
                                                    name="exp_end_date[]"
                                                    class="form-control end_month_{{ $key + 1 }}"></td>
                                            <td>
                                                <input type="file" name="experience_letter[{{ $key + 1 }}]"
                                                    class="form-control">
                                                <input type="hidden" name="existing_exp_letter[]"
                                                    value="{{ $value->exp_letter ?? '' }}">
                                                @if (isset($value->exp_letter) && $value->exp_letter)
                                                    <a target="_blank"
                                                        href="{{ asset('storage/app/public/admin/emp_documents') . '/' . $value->exp_letter }}">View
                                                        Current</a>
                                                @endif
                                            </td>
                                            <td><a onclick="deleteNewRowExp({{ $key + 1 }})"
                                                    class="btn action-btn btn--danger btn-outline-danger"><i
                                                        class="tio-delete-outlined"></i></a></td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">
                        <span class="card-header-icon">
                            <i class="tio-user"></i>
                        </span>
                        <span>{{ translate('messages.account_information') }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="input-label qcont" for="name">{{ translate('messages.email') }} <span
                                    class="form-label-secondary text-danger" data-toggle="tooltip" data-placement="right"
                                    data-original-title="{{ translate('messages.Required.') }}"> *
                                </span> </label>
                            <input type="email" value="{{ $employee['email'] }}" name="email" class="form-control"
                                id="email" placeholder="{{ translate('messages.Ex:') }} ex@gmail.com">
                        </div>
                        <div class="col-md-4">
                            <div class="js-form-message form-group mb-0">
                                <label class="input-label"
                                    for="signupSrPassword">{{ translate('messages.password') }}<span
                                        class="form-label-secondary" data-toggle="tooltip" data-placement="top"
                                        data-original-title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"><img
                                            src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                            alt="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"></span></label>

                                <div class="input-group input-group-merge">
                                    <input type="password" class="js-toggle-password form-control" name="password"
                                        id="signupSrPassword" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                        title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"
                                        placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                        aria-label="8+ characters required"
                                        data-msg="Your password is invalid. Please try again."
                                        data-hs-toggle-password-options='{
                                "target": [".js-toggle-password-target-1", ".js-toggle-password-target-2"],
                                "defaultClass": "tio-hidden-outlined",
                                "showClass": "tio-visible-outlined",
                                "classChangeTarget": ".js-toggle-passowrd-show-icon-1"
                                }'>
                                    <div class="js-toggle-password-target-1 input-group-append">
                                        <a class="input-group-text" href="javascript:">
                                            <i class="js-toggle-passowrd-show-icon-1 tio-visible-outlined"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="js-form-message form-group mb-0">
                                <label class="input-label"
                                    for="signupSrConfirmPassword">{{ translate('messages.confirm_password') }} </label>
                                <div class="input-group input-group-merge">
                                    <input type="password" class="js-toggle-password form-control" name="confirmPassword"
                                        id="signupSrConfirmPassword" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                        title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"
                                        placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                        aria-label="8+ characters required"
                                        data-msg="Password does not match the confirm password."
                                        data-hs-toggle-password-options='{
                                    "target": [".js-toggle-password-target-1", ".js-toggle-password-target-2"],
                                    "defaultClass": "tio-hidden-outlined",
                                    "showClass": "tio-visible-outlined",
                                    "classChangeTarget": ".js-toggle-passowrd-show-icon-2"
                                    }'>
                                    <div class="js-toggle-password-target-2 input-group-append">
                                        <a class="input-group-text" href="javascript:">
                                            <i class="js-toggle-passowrd-show-icon-2 tio-visible-outlined"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="btn--container justify-content-end mt-4">
                <button type="reset" id="reset_btn" class="btn btn--reset">{{ translate('messages.reset') }}</button>
                <button type="submit" class="btn btn--primary">{{ translate('messages.update') }}</button>
            </div>
        </form>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/employee.js"></script>
    <script>
        "use strict";
        $(document).on('ready', function() {
            // INITIALIZATION OF SHOW PASSWORD
            // =======================================================
            $('.js-toggle-password').each(function() {
                new HSTogglePassword(this).init()
            });


            // INITIALIZATION OF FORM VALIDATION
            // =======================================================
            $('.js-validate').each(function() {
                $.HSCore.components.HSValidation.init($(this), {
                    rules: {
                        confirmPassword: {
                            equalTo: '#signupSrPassword'
                        }
                    }
                });
            });
        });
        $('#reset_btn').click(function() {
            $('#viewer').attr('src', "{{ asset('storage/app/public/admin') }}/{{ $employee['image'] }}') }}");
            $('#customFileUpload').val(null);
            $('#zone_id').val("{{ $employee->zone_id }}").trigger('change');
            $('#role_id').val("{{ $employee['role_id'] }}").trigger('change');
        })
        $('#salary_type').on('change', function() {
            if ($(this).val() == 'Task-Wise') {
                $('#base_salary_group').hide();
            } else {
                $('#base_salary_group').show();
            }
        }).trigger('change');

        // Select2 tags for skills
        $(document).ready(function() {
            $('.js-example-tags').select2({
                tags: true,
                placeholder: "Select or add tags",
            });
        });

        // Dynamic education/experience rows
        function tillPresent(elem, dataId) {
            if ($(elem).prop('checked') == true) {
                $('.end_month_' + dataId).attr('readonly', true).val('');
            } else {
                $('.end_month_' + dataId).removeAttr('readonly');
            }
        }

        function deleteNewRowEmp(rowId) {
            $(".item_row_education[data-id='" + rowId + "']").remove();
        }

        function deleteNewRowExp(rowId) {
            $(".item_row_experience[data-id='" + rowId + "']").remove();
        }

        function addMoreRowEmp(section) {
            var $lastItemRow = $('.item_row_' + section).last();
            if (!$lastItemRow.length) {
                var dataId = 1;
            } else {
                var dataId = Number($lastItemRow.data('id')) + 1;
            }

            if (section == 'education') {
                var html = `<tr class="item_row_` + section + `" data-id="` + dataId + `">
                    <td><input type="text" name="school_name[]" placeholder="School Name" class="form-control"></td>
                    <td><input type="text" name="degree_diploma[]" placeholder="Degree / Diploma / Certificate" class="form-control"></td>
                    <td><input type="text" name="field_of_study[]" placeholder="Field of Study" class="form-control"></td>
                    <td><input type="date" name="start_month[]" class="form-control"></td>
                    <td><input type="date" name="end_month[]" class="form-control"></td>
                    <td><input type="text" name="additional_notes[]" placeholder="Additional Notes" class="form-control"></td>
                    <td><a onclick="deleteNewRowEmp(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></a></td>
                </tr>`;
                $('.rows_parent').append(html);
            } else if (section == 'experience') {
                var html = `<tr class="item_row_` + section + `" data-id="` + dataId + `">
                    <td><input type="text" name="company_name[]" placeholder="Company Name" class="form-control"></td>
                    <td><input type="text" name="occupation[]" placeholder="Role" class="form-control"></td>
                    <td><textarea name="summary[]" class="form-control" placeholder="Summary"></textarea></td>
                    <td><input type="date" name="exp_start_date[]" class="form-control">
                        <input type="hidden" class="hidden_check" name="currently_working[` + dataId + `]" value="0">
                        <input type="checkbox" onchange="tillPresent(this, ` + dataId + `)" class="till_present_` +
                    dataId + `" name="currently_working[` + dataId + `]" value="1">
                        <label>Till Present</label>
                    </td>
                    <td><input type="date" name="exp_end_date[]" class="form-control end_month_` + dataId + `"></td>
                    <td>
                        <input type="hidden" name="existing_exp_letter[]" value="">
                        <input type="file" name="experience_letter[` + (dataId - 1) + `]" class="form-control">
                    </td>
                    <td><a onclick="deleteNewRowExp(` + dataId + `)" class="btn action-btn btn--danger btn-outline-danger"><i class="tio-delete-outlined"></i></a></td>
                </tr>`;
                $('.rows_parent_experience').append(html);
            }
        }
    </script>
@endpush
