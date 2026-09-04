 @extends('layouts.vendor.app')

 @section('title', 'Task Settings')

 @push('css_or_js')
     <meta name="csrf-token" content="{{ csrf_token() }}">
     <style>
         .form-row {
             margin-top: 6px;
         }

         .ck.ck-reset {
             width: 100% !important;
         }

         .btn-outline-primary.active {
             background-color: #00868f !important;
             color: white !important;
         }
     </style>
     <style>
         /* Section Styling */
         .fb-section-box {
             border: 2px solid #e0e0e0;
             border-radius: 8px;
             padding: 14px;
             margin-bottom: 20px;
             background: #f9f9f9;
             cursor: pointer;
             font-size: 12px;
         }



         .fb-option-remove-btn:disabled {
             background: #ccc;
             cursor: not-allowed;
         }

         .fb-input-group {
             display: flex;
             gap: 10px;
             margin-top: 10px;
         }

         .fb-input-small {
             flex: 1;
             padding: 6px;
             border: 1px solid #ddd;
             border-radius: 4px;
         }

         /* Button Grid */
         .fb-button-grid {
             display: grid;
             grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
             gap: 10px;
             margin-bottom: 20px;
         }

         .fb-add-field-btn {
             border: 2px solid #2196F3;
             color: black;
             padding: 5px;
             border-radius: 4px;
             cursor: pointer;
             font-size: 14px;
             transition: background 0.3s ease;
             background: white;
         }

         .fb-add-field-btn:hover {
             background: #1976D2;
             color: white;

         }

         /* Preview Section */
         .fb-preview-section {
             margin-top: 30px;
             padding: 20px;
             border: 2px solid #e0e0e0;
             border-radius: 8px;
             background: white;
         }

         .fb-preview-title {
             font-size: 24px;
             margin-bottom: 20px;
             color: #333;
         }

         .fb-preview-field {
             margin-bottom: 20px;
         }

         .fb-preview-field label {
             display: block;
             margin-bottom: 6px;
             font-weight: 500;
             color: #555;
         }

         .fb-preview-field input[type="text"],
         .fb-preview-field input[type="email"],
         .fb-preview-field input[type="password"],
         .fb-preview-field input[type="number"],
         .fb-preview-field input[type="tel"],
         .fb-preview-field input[type="url"],
         .fb-preview-field input[type="date"],
         .fb-preview-field input[type="time"],
         .fb-preview-field input[type="datetime-local"],
         .fb-preview-field input[type="month"],
         .fb-preview-field input[type="week"],
         .fb-preview-field select,
         .fb-preview-field textarea {
             width: 100%;
             padding: 10px;
             border: 1px solid #ddd;
             border-radius: 4px;
             font-size: 14px;
         }

         .fb-preview-field input:focus,
         .fb-preview-field select:focus,
         .fb-preview-field textarea:focus {
             outline: none;
             border-color: #2196F3;
             box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
         }

         .fb-range-input {
             width: 100%;
         }

         .fb-range-display {
             display: flex;
             justify-content: space-between;
             font-size: 12px;
             color: #666;
             margin-top: 5px;
         }

         .fb-range-val {
             font-weight: bold;
             color: #2196F3;
         }

         .fb-radio-group,
         .fb-checkbox-group {
             display: flex;
             flex-direction: column;
             gap: 8px;
         }

         .fb-radio-item,
         .fb-checkbox-item {
             display: flex;
             align-items: center;
             gap: 8px;
         }

         .fb-radio-item input,
         .fb-checkbox-item input {
             cursor: pointer;
         }

         .fb-radio-item label,
         .fb-checkbox-item label {
             cursor: pointer;
             margin-bottom: 0;
         }

         .fb-file-info {
             padding: 10px;
             background: #f5f5f5;
             border-radius: 4px;
             color: #666;
             font-size: 13px;
         }

         .fb-button-secondary {
             background: #757575;
             color: white;
             border: none;
             padding: 10px 20px;
             border-radius: 4px;
             cursor: pointer;
             font-size: 14px;
         }

         .fb-button-secondary:hover {
             background: #616161;
         }


         .fb-section-box:hover {
             border-color: #2196F3;
             box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
         }

         .fb-section-active {
             border-color: #2196F3;
             background: #e3f2fd;
             box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
         }

         .fb-section-header {
             display: flex;
             justify-content: space-between;
             align-items: center;
             margin-bottom: 15px;
             padding-bottom: 15px;
         }

         .fb-section-title-input {
             flex: 1;
             font-size: 20px;
             font-weight: bold;
             border: 1px solid #ddd;
             border-radius: 4px;
             padding: 8px 12px;
             background: white;
             margin-right: 15px;
         }

         .fb-section-title-input:focus {
             outline: none;
             border-color: #2196F3;
             box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
         }

         {{-- .fb-remove-section-btn {
             background: #f44336;
             color: white;
             border: none;
             padding: 8px 16px;
             border-radius: 4px;
             cursor: pointer;
             font-size: 14px;
             transition: background 0.3s ease;
         }

         .fb-remove-section-btn:hover {
             background: #d32f2f;
         } --}} .fb-empty-section {
             text-align: center;
             color: #999;
             padding: 30px;
             font-style: italic;
         }

         .fb-label-text {
             font-size: 14px !important;
         }

         /* Preview Section Block */
         .fb-preview-section-block {
             margin-bottom: 30px;
             padding: 20px;
             border: 1px solid #e0e0e0;
             border-radius: 8px;
         }

         .fb-preview-section-title {
             font-size: 24px;
             font-weight: bold;
             color: #333;
             margin-bottom: 20px;
             padding-bottom: 10px;
             border-bottom: 2px solid #2196F3;
         }

         /* Existing styles remain the same */
         .fb-field-box {
             border: 1px solid #ddd;
             padding: 15px;
             border-radius: 6px;
             background: white;
             margin-bottom: 10px;
         }

         .fb-field-top {
             display: flex;
             justify-content: space-between;
             align-items: center;
             margin-bottom: 12px;
         }

         .fb-field-type-label {
             font-weight: bold;
             color: #2196F3;
             text-transform: capitalize;
         }

         {{-- .fb-remove-field-btn {
             background: #ff5252;
             color: white;
             border: none;
             padding: 6px 12px;
             border-radius: 4px;
             cursor: pointer;
             font-size: 12px;
         }

         .fb-remove-field-btn:hover {
             background: #ff1744;
         } --}} .fb-label-text {
             display: block;
             margin-top: 10px;
             margin-bottom: 5px;
             font-weight: 500;
             color: #555;
         }

         .fb-input-field {
             width: 100%;
             padding: 8px;
             margin-bottom: 10px;
             border: 1px solid #ddd;
             border-radius: 4px;
         }

         .fb-checkbox-inline {
             display: flex;
             align-items: center;
             gap: 8px;
             margin-top: 10px;
         }

         .fb-option-row {
             display: flex;
             gap: 8px;
             margin-bottom: 8px;
         }

         .fb-option-input {
             flex: 1;
         }

         .fb-submit-btn {
             width: 100%;
             padding: 15px;
             background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
             color: white;
             border: none;
             border-radius: 10px;
             font-size: 16px;
             font-weight: 600;
             cursor: pointer;
             margin-top: 20px;
             transition: transform 0.2s;
         }

         .fb-submit-btn:hover {
             transform: translateY(-2px);
             box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
         }

         .fb-preview-section {
             margin-top: 30px;
             padding-top: 30px;
             border-top: 2px solid #e0e0e0;
         }

         .fb-preview-title {
             font-size: 1.5em;
             color: #333;
             margin-bottom: 20px;
         }

         .fb-preview-field {
             margin-bottom: 20px;
         }

         .fb-preview-field label {
             display: block;
             margin-bottom: 8px;
             color: #333;
             font-weight: 600;
         }

         .fb-preview-field input,
         .fb-preview-field select,
         .fb-preview-field textarea {
             width: 100%;
             padding: 12px;
             border: 1px solid #ddd;
             border-radius: 8px;
             font-size: 14px;
             font-family: inherit;
         }

         .fb-radio-group,
         .fb-checkbox-group {
             display: flex;
             flex-direction: column;
             gap: 8px;
         }

         .fb-radio-item,
         .fb-checkbox-item {
             display: flex;
             align-items: center;
             gap: 8px;
         }

         .fb-radio-item input[type="radio"],
         .fb-checkbox-item input[type="checkbox"] {
             width: auto;
         }

         .fb-range-display {
             display: flex;
             justify-content: space-between;
             margin-top: 5px;
             font-size: 12px;
             color: #666;
         }

         .fb-color-preview {
             width: 50px;
             height: 50px;
             border: 2px solid #ddd;
             border-radius: 5px;
             margin-top: 5px;
         }

         .fb-file-info {
             font-size: 12px;
             color: #666;
             margin-top: 5px;
         }

         .fb-checkbox-inline {
             display: inline-flex;
             align-items: center;
             gap: 5px;
         }

         .fb-fieldset {
             border: 1px solid #ddd;
             border-radius: 8px;
             padding: 15px;
             margin-top: 10px;
         }

         .fb-legend {
             font-weight: 600;
             color: #667eea;
             padding: 0 10px;
         }

         .fb-input-group {
             display: flex;
             gap: 10px;
             margin-bottom: 10px;
         }

         .fb-input-small {
             flex: 1;
             padding: 10px;
             border: 1px solid #ddd;
             border-radius: 6px;
             font-size: 13px;
         }

         .fb-button-secondary {
             background: #10ac84;
             color: white;
             border: none;
             padding: 10px 15px;
             border-radius: 6px;
             cursor: pointer;
             font-size: 12px;
             font-weight: 600;
         }

         .fb-button-secondary:hover {
             background: #0e9670;
         }


         .section-title {
             font-size: 16px;
             font-weight: 600;
             color: #374151;
             margin-bottom: 16px;
             display: flex;
             align-items: center;
         }

         .section-title svg {
             margin-right: 8px;
         }

         .setting-item {
             display: flex;
             justify-content: space-between;
             align-items: center;
             padding: 6px;
             border: 1px solid #c8d2e0;
             border-radius: 8px;
             margin-bottom: 12px;
             transition: all 0.2s;
             width: 49%;
             height: 100%;
         }

         .setting-item:hover {
             border-color: #cbd5e1;
             background: #f9fafb;
         }

         .setting-item:last-child {
             margin-bottom: 0;
         }

         .setting-info {
             flex: 1;
         }

         .setting-label {
             font-size: 15px;
             font-weight: 500;
             color: #1f2937;
             margin-bottom: 4px;
         }

         .setting-description {
             font-size: 13px;
             color: #6b7280;
         }

         .toggle-switch {
             position: relative;
             width: 48px;
             height: 26px;
         }

         .toggle-switch input {
             opacity: 0;
             width: 0;
             height: 0;
         }

         .toggle-slider {
             position: absolute;
             cursor: pointer;
             top: 0;
             left: 0;
             right: 0;
             bottom: 0;
             background-color: #d1d5db;
             transition: 0.3s;
             border-radius: 34px;
         }

         .toggle-slider:before {
             position: absolute;
             content: "";
             height: 20px;
             width: 20px;
             left: 3px;
             bottom: 3px;
             background-color: white;
             transition: 0.3s;
             border-radius: 50%;
         }

         .toggle-switch input:checked+.toggle-slider {
             background-color: #3b82f6;
         }

         .toggle-switch input:checked+.toggle-slider:before {
             transform: translateX(22px);
         }

         .toggle-switch input:disabled+.toggle-slider {
             opacity: 0.5;
             cursor: not-allowed;
         }

         .status-badge {
             display: inline-block;
             padding: 4px 10px;
             border-radius: 12px;
             font-size: 12px;
             font-weight: 500;
             margin-left: 12px;
         }

         .status-enabled {
             background: #dcfce7;
             color: #16a34a;
         }

         .status-disabled {
             background: #fee2e2;
             color: #dc2626;
         }

         .save-button {
             width: 100%;
             padding: 12px 24px;
             background: #3b82f6;
             color: white;
             border: none;
             border-radius: 8px;
             font-size: 15px;
             font-weight: 500;
             cursor: pointer;
             transition: background 0.2s;
             margin-top: 24px;
         }

         .save-button:hover {
             background: #2563eb;
         }

         .divider {
             height: 1px;
             background: #e5e7eb;
             margin: 24px 0;
         }

         @media (max-width: 768px) {

             .setting-item {
                 width: 100%;
             }

             .main_sectionf {
                 padding: 10px !important;
             }

             .fb-button-grid {
                 grid-template-columns: repeat(auto-fill, minmax(76px, 1fr));
                 gap: 8px;
             }

             .fb-add-field-btn {
                 font-size: 11px;
             }

             .fb-section-title-input {
                 font-size: 14px;
                 font-weight: 500;
             }
             .fb-section-box{
                    padding: 7px;
             }
             .fb-section-header{
                    flex-wrap: wrap;
                         margin-bottom: 0;
    gap: 7px;
             }
             .fb-section-title-input{
                 margin-right: 0;
             }
             .fb-preview-section{
                    margin-top: 0;
                    padding-top: 0;
                        padding: 10px;
             }
             .fb-preview-title{
                    font-size: 14px;
    margin-bottom: 13px;
             }
             .fb-preview-section-block{
                margin-bottom: 16px;
    padding: 7px;
    border-radius: 5px;
             }
             .fb-preview-section-title{
                    font-size: 15px;
    font-weight: 500;
             }
         }
     </style>
 @endpush

 @section('content')
     <div class="content container-fluid">
         <!-- Page Header -->
         <div class="page-header">
             <h1 class="page-header-title"><i class="tio-filter-list"></i>Task Settings</h1>
         </div>
         <!-- End Page Header -->

         <form class="w-100 p-0 " enctype="multipart/form-data" action="{{ route('vendor.task.setting.update') }}"
             method="post">
             @csrf
             <div class="card mb-1">
                 <!-- Body -->
                 <div class="card-body row align-items-start main_sectionf">
                     <div class="col-md-6 row d-flex gap-1 g-0">
                         <div class="setting-item">
                             <div class="setting-info">
                                 <div class="setting-label">Invoice</div>
                                 <div class="setting-description">Create invoices to tasks</div>
                             </div>

                             <label class="switch toggle-switch-lg m-0">
                                 <input type="checkbox" name="task_invoice" class="toggle-switch-input " value="1"
                                     {{ $storeConfig && $storeConfig->task_invoice == '1' ? 'checked' : '' }}>
                                 <span class="toggle-switch-label">
                                     <span class="toggle-switch-indicator"></span>
                                 </span>
                             </label>
                         </div>

                         <div class="setting-item">
                             <div class="setting-info">
                                 <div class="setting-label">Quotation</div>
                                 <div class="setting-description">Create quotations to tasks</div>
                             </div>

                             <label class="switch toggle-switch-lg m-0">
                                 <input type="checkbox" name="task_quotation" class="toggle-switch-input " value="1"
                                     {{ $storeConfig && $storeConfig->task_quotation == '1' ? 'checked' : '' }}>
                                 <span class="toggle-switch-label">
                                     <span class="toggle-switch-indicator"></span>
                                 </span>
                             </label>

                         </div>
                         <div class="setting-item">
                             <div class="setting-info">
                                 <div class="setting-label">Receivable Receipt</div>
                                 <div class="setting-description">Create receivable receipts to
                                     tasks</div>
                             </div>
                             <label class="switch toggle-switch-lg m-0">
                                 <input type="checkbox" name="task_recievable_receipt" class="toggle-switch-input "
                                     value="1"
                                     {{ $storeConfig && $storeConfig->task_recievable_receipt == '1' ? 'checked' : '' }}>
                                 <span class="toggle-switch-label">
                                     <span class="toggle-switch-indicator"></span>
                                 </span>
                             </label>
                         </div>

                         <div class="setting-item">
                             <div class="setting-info">
                                 <div class="setting-label">Service Report</div>
                                 <div class="setting-description">Create service reports to tasks
                                 </div>
                             </div>

                             <label class="switch toggle-switch-lg m-0">
                                 <input type="checkbox" name="task_service_reports" class="toggle-switch-input "
                                     value="1"
                                     {{ $storeConfig && $storeConfig->task_service_reports == '1' ? 'checked' : '' }}>
                                 <span class="toggle-switch-label">
                                     <span class="toggle-switch-indicator"></span>
                                 </span>
                             </label>
                         </div>

                     </div>
                     <div class="col-md-2">
                         <label for="">Close Task : </label><br>
                         <div class="btn-group btn-group-toggle m-0" style="margin: 2px auto;" data-toggle="buttons">
                             <label class="btn btn-responsive btn-outline-primary  ">
                                 <input type="radio"
                                     {{ ($storeConfig && $storeConfig->close_task_with_otp == '1') || !$storeConfig ? 'checked' : '' }}
                                     class="account_type" value="1" name="close_task_with_otp" id="option1"> With OTP
                             </label>
                             <label class="btn btn-responsive btn-outline-primary ">
                                 <input type="radio"
                                     {{ $storeConfig && $storeConfig->close_task_with_otp == '0' ? 'checked' : '' }}
                                     class="account_type" value="0" name="close_task_with_otp" id="option3">
                                 Without OTP
                             </label>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <label for="">Task ID Format</label>
                         <div class="input-group tf-input-group">
                             @php  $default_task_id_format = \App\CentralLogics\Helpers::storePrefix() . '-' ;@endphp
                             <input type="text" class="form-control tf-input" name="task_id_format"
                                 value="{{ $storeConfig && $storeConfig->task_id_format !== null ? $storeConfig->task_id_format : $default_task_id_format }}"
                                 placeholder="Ex: SER-TASK-" aria-label="Ex: 3">
                             <input type="number" class="form-control tf-input" name="task_id_serial"
                                 value="{{ $storeConfig ? str_pad($storeConfig->task_id_serial, 3, '0', STR_PAD_LEFT) : 001 }}">
                         </div>
                     </div>

                     <div class="col-12 mb-3">
                         <button style="float:right" class="btn btn-primary my-2">Update</button>
                     </div>
                 </div>
         </form>
     </div>
     </div>
     <div class="content container-fluid">
         <!-- Page Header -->
         <div class="page-header">
             <h1 class="page-header-title"><i class="tio-filter-list"></i>Workflow Form</h1>
         </div>
         <!-- End Page Header -->
         <div class="">
             <div class="fb-main-container">
                 <div class="fb-content-area">
                     <!-- Add Section Button -->
                     <div class="mb-3">
                         <button class="btn btn--primary" onclick="fbAddSection()">+ Add Section</button>
                     </div>

                     <!-- Field Type Buttons -->
                     <div class="fb-button-grid">
                         <button class="fb-add-field-btn" onclick="fbAddField('text')">+ Text</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('email')">+ Email</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('password')">+ Password</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('number')">+ Number</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('tel')">+ Phone</button>
                         {{-- <button class="fb-add-field-btn" onclick="fbAddField('url')">+ URL</button> --}}
                         <button class="fb-add-field-btn" onclick="fbAddField('date')">+ Date</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('time')">+ Time</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('datetime-local')">+ DateTime</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('month')">+ Month</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('week')">+ Week</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('color')">+ Color</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('range')">+ Range</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('file')">+ File Upload</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('textarea')">+ Text Area</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('select')">+ Dropdown</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('radio')">+ Radio</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('checkbox')">+ Checkbox</button>
                         <button class="fb-add-field-btn" onclick="fbAddField('checkbox-group')">+ Checkbox Group</button>
                     </div>

                     <!-- Sections and Fields List -->
                     <div class="fb-fields-list row" data-form="task_form" id="fbFormFields"></div>

                     <!-- Preview Section -->
                     <div class="fb-preview-section">
                         <h2 class="fb-preview-title">Form Preview</h2>
                         <form id="fbPreviewForm">
                             <input type="hidden" name="form_id" class="form_id">
                             <div id="fbPreviewFields"></div>
                             <div class="w-100 d-flex justify-content-end">
                                 <button type="button" id="saveFormStructure" class="btn btn--primary btn-lg">Save
                                     Form</button>
                             </div>
                         </form>
                     </div>
                 </div>
             </div>
         </div>
     </div>
 @endsection

 @push('script_2')
     <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
     @include('vendor-views.js.form-builder')
 @endpush
