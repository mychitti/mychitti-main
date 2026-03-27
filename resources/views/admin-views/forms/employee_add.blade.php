 <form class="js-validate " action="{{ route('admin.users.employee.add-new.post') }}" method="post" enctype="multipart/form-data">
     @csrf
     <style>
         .hidden_check {
             display: none;
         }

         .select2-container .select2-selection--single {
             height: 43px !important;
         }

         .expr_tabel,
         .edu_tabel {
             color: #4f4f4f;
         }
     </style>
     <div class="card h-100 my-2">
         <div class="card-header" style="background-color: #c0e9e6;">
             <h5 class="card-title">
                 <span class="card-header-icon"><i class="tio-user"></i></span>
                 <span>{{ translate('messages.general_information') }}</span>
             </h5>
         </div>
         <div class="card-body" style="border: 2px solid #c0e9e6;">
             <div class="row g-3 align-items-start">
                 <div class="col-md-9 row">

                     <div class="form-group col-md-4">
                         <label class="input-label text-capitalize"
                             for="f_name">{{ translate('messages.employee_id') }} @if (hasPermission('staff_manage', 'settings'))
                                 <span class="text-danger"><a href="{{ route('admin.staff.settings') }}"
                                         class="text-underline">Edit
                                         Prefix?</a></span>
                             @endif
                         </label>
                         <input type="text" name="" class="form-control" id=""
                             placeholder="Auto Generated" readonly value="{{ _newEmpId() }}">
                     </div>
                     <div class="form-group col-md-4">
                         <label class="input-label text-capitalize"
                             for="f_name">{{ translate('messages.first_name') }}<span
                                 class="text-danger">*</span></label>
                         <input type="text" name="f_name" class="form-control" id="f_name"
                             placeholder="{{ translate('messages.Ex:') }} Sakeef Ameer" value="{{ old('f_name') }}"
                             required>
                     </div>
                     <div class="form-group col-md-4">
                         <label class="input-label text-capitalize"
                             for="l_name">{{ translate('messages.last_name') }}</label>
                         <input type="text" name="l_name" class="form-control" id="l_name"
                             value="{{ old('l_name') }}" placeholder="{{ translate('messages.Ex:') }} Prodhan">
                     </div>
                     <div class="form-group col-md-4">
                         <label class="input-label text-capitalize"
                             for="phone">{{ translate('messages.phone') }}<span class="text-danger">*</span></label>
                         <input type="number" name="phone" value="{{ old('phone') }}" class="form-control"
                             id="phone" placeholder="{{ translate('messages.Ex:') }} +88017********" required>
                     </div>
                     <div class="form-group col-md-4 role_elem_outer">
                         <div class="role_elem_inner">
                             <label class="input-label text-capitalize"
                                 for="role_id">{{ translate('messages.Role') }}<span
                                     class="text-danger">*</span></label>
                             <select id="role_id" class="form-control js-select2-custom " name="role_id" required>
                                 <option value="" selected disabled>{{ translate('messages.select_Role') }}
                                 </option>
                                 @if (hasPermission('staff_department', 'add')) 
                                 <option value="add_new">+ Add New</option>
                                 @endif
                                 @foreach ($rls as $r)
                                     <option value="{{ $r->id }}">{{ $r->name }}</option>
                                 @endforeach
                             </select>
                         </div>
                     </div>

                     <div class="form-group mb-0 col-md-4 dep_elem_outer">
                         <div class="dep_elem_inner">
                             <label class="input-label text-capitalize" for="role_id">Deparetment <span
                                     class="text-danger">*</span></label>
                             <select name="department_id" required id="department_id"
                                 class="form-control js-select2-custom ">
                                 <option>Select Department</option>
                                 @if (hasPermission('staff_department', 'add')) 
                                 <option value="add_new">+ Add New</option>
                                 @endif
                                 @foreach ($departments as $dep)
                                     <option value="{{ $dep->id }}">{{ $dep->title }}</option>
                                 @endforeach
                             </select>
                         </div>

                     </div>
                     <div class="form-group mb-0 col-md-4">
                         <label class="input-label " for="dob">Date of Birth</label>
                         <input type="date" name="dob" id="dob" class="form-control">
                     </div>
                     <div class="form-group mb-0 col-md-4">
                         <label class="input-label " for="branch_id">Branch</label>
                         <select name="branch_id" id="branch_id" data-placeholder="Select Branch"
                             class="form-control js-select2-custom ">
                             <option></option>
                             @php $branches = _storeBranches() @endphp
                             @foreach ($branches as $branch)
                                 <option value="{{ $branch->id }}">
                                     {{ ucfirst($branch->name) }}</option>
                             @endforeach
                         </select>
                     </div>
                     <div class="form-group mb-0 col-md-4">
                         <a class="text-primary text-underline cursor-pointer" type="button" style="font-size: 18px;"
                             data-toggle="modal" data-target="#documentsUPloadModal">Upload Documents</a>
                     </div>
                     <div class="modal fade" id="documentsUPloadModal" tabindex="-1"
                         aria-labelledby="exampleModalLabel" aria-hidden="true">
                         <div class="modal-dialog modal-lg">
                             <div class="modal-content">
                                 <div class="modal-header">
                                     <h5 class="modal-title" id="exampleModalLabel">Documents</h5>
                                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                         <span aria-hidden="true">&times;</span>
                                     </button>
                                 </div>
                                 <div class="modal-body ">
                                     <div class="row">

                                         <div class="form-group col-md-6">
                                             <input type="hidden" name="doc_name[]" value="ID Number">
                                             <label class="input-label text-capitalize" for="role_id">ID
                                                 Number</label>
                                             <input type="text" name="doc_number[]" id=""
                                                 class="form-control" placeholder="Enter ID Number">
                                         </div>
                                         <div class="form-group col-md-6">
                                             <label class="input-label text-capitalize" for="role_id">ID
                                                 Document</label>
                                             <input type="file" name="doc_file[0]" id=""
                                                 class="form-control">
                                         </div>
                                         <div class="form-group col-md-6">
                                             <label class="input-label text-capitalize" for="role_id">PAN Card
                                                 Number</label>
                                             <input type="hidden" name="doc_name[]"
                                                 value="PAN Card
                                                 Number">

                                             <input type="text" name="doc_number[]" id=""
                                                 placeholder="Enter PAN Card No." class="form-control">
                                         </div>
                                         <div class="form-group col-md-6">
                                             <label class="input-label text-capitalize" for="role_id">PAN Card
                                                 Document</label>
                                             <input type="file" name="doc_file[1]" id=""
                                                 class="form-control">
                                         </div>
                                         <div class="form-group col-md-6">
                                             <label class="input-label text-capitalize" for="role_id">Adhaar Card
                                                 Number</label>
                                             <input type="hidden" name="doc_name[]"
                                                 value="Adhaar Card
                                                 Number">

                                             <input type="text" name="doc_number[]" id=""
                                                 placeholder="Enter Adhaar Card No." class="form-control">
                                         </div>
                                         <div class="form-group col-md-6">
                                             <label class="input-label text-capitalize" for="role_id">Adhaar Card
                                                 Document</label>
                                             <input type="file" name="doc_file[2]" id=""
                                                 class="form-control">
                                         </div>
                                         <a class="add-more add_more_doc cursor-pointer mb-3"
                                             style="font-size: 16px;">+ Add More Documents</a>
                                         <div class="other_documents row col-12">

                                         </div>
                                     </div>

                                 </div>
                                 <div class="modal-footer">
                                     <button type="button" class="btn btn-secondary"
                                         data-dismiss="modal">Close</button>
                                     <button type="button" class="btn btn-primary"
                                         data-dismiss="modal">Done</button>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="col-md-3">
                     <div class="  h-100">
                         <div class="card-body d-flex flex-column upload-img-2 p-1">
                             <h5 class="form-label text-center mb-3">
                                 Staff Image
                                 <span class="text-danger">{{ translate('messages.Ratio (1:1)') }}</span>, size max 2
                                 MB
                             </h5>
                             <div class="text-center my-auto">
                                 <img class="store-banner onerror-image" id="viewer"
                                     data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                     src="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                     alt="Employee thumbnail" />
                             </div>
                             <div class="form-group mt-3 mb-0">
                                 <div class="custom-file">
                                     <input type="file" name="image" id="customFileUpload"
                                         class="custom-file-input read-url"
                                         accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*"
                                         value="{{ old('image') }}" required>
                                     <label class="custom-file-label"
                                         for="customFileUpload">{{ translate('messages.choose_file') }}</label>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>

             </div>

         </div>

     </div>
     <div class="card h-100 my-2">
         <a data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false"
             aria-controls="collapseExample">

             <div class="card-header" style="background-color: #f9d9e9; ">
                 <h5 class="card-title">
                     <span class="card-header-icon"><i class="tio-user"></i></span>
                     <span>Contact Details <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                 </h5>
                 <button type="button" class="btn btn-danger" data-toggle="modal"
                     data-target="#emergencyContactModal">Emergency
                     Contact</button>
             </div>
         </a>

         <div class="collapse" id="collapse1">
             <div class="card-body" style="border: 2px solid #f9d9e9; ">
                 <div class="row g-3">
                     <div class="col-md-6 card p-4">
                         <h4 class="py-0 mb-0">Residential Address</h4>
                         <div class="form-row pb-0">
                             {{-- <div class=" col-md-6">
                             <label class="input-label " for="ra_email">Email
                             </label>
                             <input type="text" class="form-control" id="ra_email" name="ra_email"
                                 placeholder="Email">

                         </div> --}}
                             <div class=" col-md-6">
                                 <label class="input-label " for="ra_phone">Phone
                                 </label>
                                 <input type="number" class="form-control" id="ra_phone" name="ra_phone"
                                     placeholder="Phone">

                             </div>
                             <div class=" col-md-6">
                                 <label class="input-label " for="ra_address1">Address Line 1
                                 </label>
                                 <input type="text" class="form-control" id="ra_address1" name="ra_address1"
                                     placeholder="Address Line 1">

                             </div>

                             <div class=" col-md-6">
                                 <label class="input-label " for="ra_address2">Address Line 2</label>
                                 <input type="text" class="form-control" id="ra_address2" name="ra_address2"
                                     placeholder="Address Line 2">
                             </div>

                             <div class="col-md-6">
                                 <label class="input-label " for="ra_pincode">Pincode</label>
                                 <input type="number" class="form-control" id="ra_pincode" name="ra_pincode"
                                     placeholder="Pincode">
                             </div>

                             <div class=" col-md-6">
                                 <label class="input-label " for="ra_city">City</label>
                                 <input type="text" class="form-control" id="ra_city" name="ra_city"
                                     placeholder="City">
                             </div>

                             <div class="col-md-6">
                                 <label class="input-label " for="ra_state">State</label>
                                 <select class="form-control js-select2-custom js-example-basic-single" id="ra_state"
                                     name="ra_state">
                                     <option selected disabled>Select State</option>
                                     @foreach (_states() as $key => $state)
                                         <option value="{{ $state->id }}">
                                             {{ $state->state_name . ' (' . $state->state_abbr . ')' }}
                                         </option>
                                     @endforeach
                                 </select>
                             </div>

                         </div>
                     </div>
                     <div class="col-md-6 card p-4">
                         <h4 class="py-0 mb-0">Permanent Address</h4>
                         <div class="form-row pb-0">
                             {{-- <div class=" col-md-6">
                             <label class="input-label " for="pa_email">Email
                             </label>
                             <input type="text" class="form-control" id="pa_email" name="pa_email"
                                 placeholder="Email">

                         </div> --}}
                             <div class=" col-md-6">
                                 <label class="input-label " for="pa_phone">Phone
                                 </label>
                                 <input type="number" class="form-control" id="pa_phone" name="pa_phone"
                                     placeholder="Phone">

                             </div>
                             <div class=" col-md-6">
                                 <label class="input-label " for="pa_address1">Address Line 1
                                 </label>
                                 <input type="text" class="form-control" id="pa_address1" name="pa_address1"
                                     placeholder="Address Line 1">

                             </div>

                             <div class=" col-md-6">
                                 <label class="input-label " for="pa_address2">Address Line 2</label>
                                 <input type="text" class="form-control" id="pa_address2" name="pa_address2"
                                     placeholder="Address Line 2">
                             </div>

                             <div class="col-md-6">
                                 <label class="input-label " for="pa_pincode">Pincode</label>
                                 <input type="number" class="form-control" id="pa_pincode" name="pa_pincode"
                                     placeholder="Pincode">
                             </div>

                             <div class=" col-md-6">
                                 <label class="input-label " for="pa_city">City</label>
                                 <input type="text" class="form-control" id="pa_city" name="pa_city"
                                     placeholder="City">
                             </div>

                             <div class="col-md-6">
                                 <label class="input-label " for="pa_state">State</label>
                                 <select class="form-control js-select2-custom js-example-basic-single" id="pa_state"
                                     name="pa_state">
                                     <option selected disabled>Select State</option>
                                     @foreach (_states() as $key => $state)
                                         <option value="{{ $state->id }}">
                                             {{ $state->state_name . ' (' . $state->state_abbr . ')' }}
                                         </option>
                                     @endforeach
                                 </select>
                             </div>

                         </div>
                     </div>

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
                         <label class="input-label text-capitalize" for="designation">Designation / Job Title</label>
                         <input type="text" name="designation" value="{{ old('designation') }}"
                             placeholder="Designation / Job Title" class="form-control" id="designation">
                     </div>
                     <div class="col-md-3">
                         <label class="input-label text-capitalize" for="qualifiexperienceation">Experience</label>
                         <input type="text" name="experience" value="{{ old('experience') }}"
                             placeholder="Ex: 2 Years" class="form-control" id="experience">
                     </div>
                     <div class="col-md-3">
                         <label class="input-label text-capitalize" for="source">Source of Hire</label>
                         <select name="source" id="source"
                             class="form-control js-example-tags js-select2-custom">
                             <option value="Direct">Direct</option>
                             <option value="Newspaper">Newspaper</option>
                             <option value="Job Portal">Job Portal</option>
                             <option value="External Referral">External Referral</option>
                             <option value="Employee Referral">Employee Referral</option>
                             <option value="Indeed">Indeed</option>
                             <option value="Twitter">Twitter</option>
                             <option value="Facebook">Facebook</option>
                             <option value="Advertisement">Advertisement</option>
                             <option value="Company Website">Company Website</option>
                         </select>
                     </div>
                     <div class="col-md-3">
                         <label class="input-label text-capitalize" for="qualification">Highest Qualification</label>
                         <input type="text" name="highest_qualification" placeholder="Enter highest qualification"
                             value="{{ old('highest_qualification') }}" class="form-control"
                             id="highest_qualification">
                     </div>
                     <div class="col-md-6">
                         <label class="input-label text-capitalize" for="email">Skill Set</label>
                         <select class="js-example-tags" name="skills[]" multiple="multiple">
                             <option value="Communication">Communication</option>
                             <option value="Teamwork">Teamwork</option>
                             <option value="Problem Solving">Problem Solving</option>
                             <option value="Time Management">Time Management</option>
                         </select>

                     </div>

                     <div class="col-md-3">
                         <label class="input-label text-capitalize" for="qualification">Additional information</label>
                         <input type="text" name="additional_information"
                             placeholder="Enter additional information" value="{{ old('additional_information') }}"
                             class="form-control" id="additional_information">
                     </div>
                     {{-- <div class="col-md-3">
                     <label class="input-label text-capitalize" for="qualification">Old Salary (per month)</label>
                     <input type="number" name="current_salary" placeholder="Ex: 32000"
                         value="{{ old('current_salary') }}" class="form-control" id="current_salary">
                 </div> --}}

                     <div class="col-md-3">
                         <label class="input-label text-capitalize" for="salary_type">Salary Type</label>
                         <select name="salary_type" id="salary_type"
                             class="form-control js-select2-custom js-example-basic-single">
                             <option value="">Select</option>
                             <option selected value="Monthly">Monthly</option>
                             <option value="Hourly">Hourly</option>
                             <option value="Task-Wise">Task-Wise</option>
                         </select>
                     </div>
                     <div class="col-md-3 base_salary_inp">
                         <label class="input-label text-capitalize" for="base_salary">Salary</label>
                         <input type="number" name="base_salary" placeholder="Ex: 42000"
                             value="{{ old('base_salary') }}" class="form-control" id="base_salary">
                     </div>
                     <div class="form-group mb-0 col-md-3">
                         <label class="input-label text-capitalize " for="main_department">Deparetment </label>
                         <select name="main_department" id="inputState"
                             class="form-control js-select2-custom js-example-basic-single">
                             <option value="HR">HR</option>
                             <option value="Management">Management</option>
                             <option value="Finance">Finance</option>
                             <option value="Marketing">Marketing</option>
                             <option value="IT">IT</option>
                         </select>
                     </div>
                     <div class="col-md-3">
                         <label class="input-label text-capitalize" for="offer_letter">Offer Letter</label>
                         <input type="file" name="offer_letter" class="form-control" id="documents">
                     </div>
                     <div class="col-md-3">
                         <label class="input-label text-capitalize" for="qualification">Tentative Joining Date</label>
                         <input type="date" name="tentative_joining_date"
                             value="{{ old('tentative_joining_date') }}" class="form-control"
                             id="tentative_joining_date">
                     </div>
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
     <div class="card h-100 my-2">
         <a data-toggle="collapse" href="#collapse3" role="button" aria-expanded="false"
             aria-controls="collapseExample">
             <div class="card-header" style="background-color: #ffddc6;">
                 <h5 class="card-title">
                     <span class="card-header-icon"><i class="tio-user"></i></span>
                     <span>Educational Information <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                 </h5>
             </div>
         </a>
         <div class="collapse" id="collapse3">

             <div class="card-body p-2 " style="border: 2px solid  #ffddc6;">
                 <table class="table edu_tabel table-responsive">
                     <thead class="" style=" background:rgb(244, 244, 244);">
                         <tr>
                             <th scope="col">School Name</th>
                             <th scope="col">Degree/Diploma</th>
                             <th scope="col">Fields(s) of Study</th>
                             <th scope="col">Start Month</th>
                             <th scope="col">End Month</th>
                             <th scope="col">Additional Notes</th>
                             <th scope="col" style="padding:7px;"><button type="button"
                                     class="btn btn-dark btn-sm add_more_btn" onclick="addMoreRowEmp('education')">Add
                                     More</button>
                             </th>
                         </tr>
                     </thead>
                     <tbody class="rows_parent">

                         <tr class="item_row_education" data-id="1">
                             <td><input type="text" name="school_name[]" placeholder="School Name"
                                     class="form-control">
                             </td>
                             <td><input type="text" name="degree_diploma[]"
                                     placeholder="Degree / Diploma / Cetificate" class="form-control">
                             </td>
                             <td><input type="text" name="field_of_study[]" placeholder="Field of Study"
                                     class="form-control">
                             </td>
                             <td><input type="date" name="start_month[]" class="form-control"></td>
                             <td><input type="date" name="end_month[]" class="form-control"></td>
                             <td><input type="text" name="additional_notes[]" placeholder="Additional Notes"
                                     class="form-control"></td>
                             <td><a onclick="deleteNewRowEmp(1)"
                                     class="btn action-btn btn--danger btn-outline-danger"><i
                                         class="tio-delete-outlined"></i></a></td>
                         </tr>
                     </tbody>
                 </table>
             </div>
         </div>
     </div>
     <div class="card h-100 my-2">
         <a data-toggle="collapse" href="#collapse4" role="button" aria-expanded="false"
             aria-controls="collapseExample">
             <div class="card-header" style="background-color: #e3efe2;">
                 <h5 class="card-title">
                     <span class="card-header-icon"><i class="tio-user"></i></span>
                     <span>Experience <i class="tio-arrow-drop-down-circle-outlined"></i></span>
                 </h5>
             </div>
         </a>
         <div class="collapse" id="collapse4">

             <div class="card-body p-2 " style="border: 2px solid #c0e9e6;">
                 <table class="table expr_tabel table-responsive">
                     <thead class="" style=" background:rgb(243, 243, 243);">
                         <tr>
                             <th scope="col">Company Name</th>
                             <th scope="col">Role</th>
                             <th scope="col">Summary</th>
                             <th scope="col">Start Date</th>
                             <th scope="col">End Date</th>
                             <th scope="col">Experience Letter</th>
                             <th scope="col" style="padding:7px;"><button type="button"
                                     class="btn btn-dark btn-sm add_more_btn"
                                     onclick="addMoreRowEmp('experience')">Add
                                     More</button>
                             </th>
                         </tr>
                     </thead>
                     <tbody class="rows_parent_experience">

                         <tr class="item_row_experience" data-id="1">

                             <td><input type="text" name="company_name[]" placeholder="Company Name"
                                     class="form-control">
                             </td>
                             <td><input type="text" name="occupation[]" placeholder="Role" class="form-control">
                             </td>
                             <td>
                                 <textarea name="summary[]" id="" class="form-control" placeholder="Summary"></textarea>
                             </td>
                             <td><input type="date" name="exp_start_date[]" class="form-control">

                                 <input type="hidden" class="hidden_check" name="currently_working[1]"
                                     value="0">

                                 <input type="checkbox" onchange="tillPresent(this, 1)" class="till_present_1"
                                     name="currently_working[1]" value="1" id="">

                                 <label for="">Till Present</label>
                             </td>

                             <td><input type="date" name="exp_end_date[]" class="form-control end_month_1"></td>
                             <td>
                                 <input type="file" name="experience_letter[0]" id=""
                                     class="form-control">
                             </td>
                             <td><a onclick="deleteNewRowExp(1)"
                                     class="btn action-btn btn--danger btn-outline-danger"><i
                                         class="tio-delete-outlined"></i></a></td>
                         </tr>
                     </tbody>
                 </table>
             </div>
         </div>
     </div>
     <div class="card h-100 my-2">

         <div class="card-header" style="background-color:rgb(230, 230, 230);">
             <h5 class="card-title">
                 <span class="card-header-icon"><i class="tio-user"></i></span>
                 <span>{{ translate('messages.login_information') }} </span>
             </h5>
         </div>

         <div class="card-body" style="border: 2px solid rgb(230, 230, 230);">
             <div class="row g-3">
                 {{-- <div class="col-md-3">
                     <label class="input-label " for="id_number">{{ translate('messages.id_number') }}</label>
                     <input type="text" name="id_number" placeholder="Enter ID Number"
                         value="{{ old('id_number') }}" class="form-control" id="id_number">
                 </div>
                 <div class="col-md-3">
                     <label class="input-label " for="id_document">{{ translate('messages.id_document') }}</label>
                     <input type="file" name="id_document" class="form-control" id="id_document">
                 </div> --}}
                 <div class="col-md-3">
                     <label class="input-label" for="email">{{ translate('messages.email') }}<span
                             class="text-danger">*</span></label>
                     <input readonly onfocus="this.removeAttribute('readonly')" type="email" name="email"
                         value="{{ old('email') }}" class="form-control" id="email"
                         placeholder="{{ translate('messages.Ex:') }} ex@gmail.com" required>
                 </div>
                 <div class="col-md-3">
                     <div class="js-form-message form-group mb-0">
                         <label class="input-label" for="signupSrPassword">{{ translate('messages.password') }}
                             <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                 data-original-title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"><img
                                     src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                     alt="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"></span></label>

                         <div class="input-group input-group-merge">
                             <input type="password" class="js-toggle-password form-control" name="password"
                                 id="signupSrPassword" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                 title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"
                                 placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                 aria-label="8+ characters required" required
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
                 <div class="col-md-3">
                     <div class="js-form-message form-group mb-0">
                         <label class="input-label"
                             for="signupSrConfirmPassword">{{ translate('messages.confirm_password') }}</label>
                         <div class="input-group input-group-merge">
                             <input type="password" class="js-toggle-password form-control" name="confirmPassword"
                                 id="signupSrConfirmPassword" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                 title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"
                                 placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                 aria-label="8+ characters required" required
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
                 <!-- Copy of Password -->
             </div>
         </div>
     </div>
     <div class="btn--container justify-content-end">
         <button type="reset" id="reset_btn" class="btn btn--reset">{{ translate('messages.reset') }}</button>
         <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
     </div>
     <div class="modal fade" id="emergencyContactModal" tabindex="-1" aria-labelledby="exampleModalLabel"
         aria-hidden="true">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="exampleModalLabel">Emergency Contact Details (Optional)</h5>
                     <button type="button" class="close close_em_btn" data-dismiss="modal" aria-label="Close">
                         <span aria-hidden="true">&times;</span>
                     </button>
                 </div>
                 <div class="modal-body">

                     <div class="row mb-3">
                         <div class="col-md-6">
                             <label for="emergencyName" class="form-label">Full Name<span
                                     class="text-danger">*</span></label>
                             <input type="text" class="form-control" id="emergencyName"
                                 name="emergency_contact_name" placeholder="Enter full name" required>
                         </div>
                         <div class="col-md-6">
                             <label for="relationship" class="form-label">Relationship</label>
                             <input type="text" class="form-control" id="relationship"
                                 name="emergency_contact_relationship" placeholder="e.g. Father, Friend" required>
                         </div>
                     </div>

                     <div class="row mb-3">
                         <div class="col-md-6">
                             <label for="primaryPhone" class="form-label">Primary Phone Number<span
                                     class="text-danger">*</span></label>
                             <input type="number" class="form-control" id="primaryPhone"
                                 name="emergency_contact_phone" placeholder="Enter phone number" required>
                         </div>
                         <div class="col-md-6">
                             <label for="altPhone" class="form-label">Alternate Phone Number</label>
                             <input type="number" class="form-control" id="altPhone"
                                 name="emergency_contact_alt_phone" placeholder="Enter alternate phone number">
                         </div>
                     </div>

                     <div class="row mb-3">
                         <div class="col-md-6">
                             <label for="language" class="form-label">Preferred Language</label>
                             <input type="text" class="form-control" id="language"
                                 name="emergency_contact_language" placeholder="e.g. Hindi, English">
                         </div>
                     </div>

                     <div class="mb-3">
                         <label for="address" class="form-label">Address</label>
                         <textarea class="form-control" id="address" name="emergency_contact_address" rows="3"
                             placeholder="Enter full address"></textarea>
                     </div>

                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                     <button type="button" class="btn btn-primary emergency_contact_form_btn">Done</button>
                 </div>
             </div>
         </div>
     </div>
 </form>
