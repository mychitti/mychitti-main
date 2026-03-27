 @extends('layouts.admin.app')

 @section('title', 'Master Ledger Request Form')

 @push('css_or_js')
     <meta name="csrf-token" content="{{ csrf_token() }}">
     <style>
         .form-row {
             margin-top: 6px;
         }

         /* Journal Entry Modal */
         .vendor-header {
             background: white;
             border-bottom: 1px solid #ddd;
             padding: 15px 0;
             margin-bottom: 20px;
         }

         .vendor-name {
             font-size: 24px;
             font-weight: bold;
             color: #333;
             margin: 0;
             {{-- text-align: center; --}}
         }

         .vendor-address {
             color: #666;
             font-size: 12px;
             {{-- text-align: center; --}} margin: 5px 0 0 0;
             line-height: 1.3;
         }

         .logo-container {
             {{-- position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%); --}}
         }

         .company-logo {
             {{-- width: 60px;
            height: 60px; --}} background: #f0f0f0;
             border: 1px solid #ddd;
             border-radius: 50%;
             display: flex;
             align-items: center;
             justify-content: center;
             font-size: 12px;
             color: #666;
             font-weight: bold;
         }

         .form-container {
             background: white;
             padding: 30px;
         }

         .card {
             border: none;
             box-shadow: none;
         }

         .form-control,
         .form-control {
             border: 1px solid #e9ecef;
             border-radius: 8px;
             padding: 12px 16px;
             transition: all 0.3s ease;
         }

         .form-control:focus,
         .form-control:focus {
             border-color: #667eea;
             box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
         }

         .form-label {
             font-weight: 600;
             color: #495057;
             margin-bottom: 8px;
         }

         .text-danger {
             color: #dc3545 !important;
         }



         @media (max-width: 768px) {
             .header-title {
                 font-size: 1.5rem;
             }

             .header-icon {
                 font-size: 2rem;
             }

             .form-container {
                 padding: 20px;
             }
         }

         .row_clickable {
             cursor: pointer;
         }

         .row_clickable:hover {
             background: #e7fffdff;
         }

         .form_section {
             max-width: 900px;
         }

         .remove_user {
             cursor: pointer;
         }
     </style>
 @endpush

 @section('content')
     <div class="content container-fluid">
         <!-- Page Header -->
         <div class="page-header d-flex flex-wrap justify-content-between align-items-center w-100">
             <div>
                 <h1 class="page-header-title"><i class="tio-filter-list"></i> Master Ledger Request Form</h1>
             </div>

         </div>
         <!-- End Page Header -->
         <div class="row ">
             <div class="col-md-7 p-4">
                 <div class="shadow">
                     <div class="vendor-header pb-2 mb-0">
                         <div class="container position-relative d-flex justify-content-between px-5 align-items-center">
                             <div class="store_content">
                                 <h2 class="vendor-name">{{ \App\Models\BusinessSetting::where('key', 'business_name')->first()?->value }}</h2>
                                 <p class="vendor-address">
                                     {{ \App\Models\BusinessSetting::where('key', 'address')->first()?->value }}<br>
                                     GST NO: {{ \App\Models\BusinessSetting::where('key', 'gst_number')->first()?->value }}
                                 </p>
                             </div>
                             <div class="logo-container">
                                 <div class="company-logo">
                                     <img style="width: 100px;"
                                         src="{{ asset('storage/app/public/store/' . \App\Models\BusinessSetting::where('key', 'logo')->first()?->value) }}"
                                         alt="">
                                 </div>
                             </div>
                         </div>
                     </div>
                     <form enctype="multipart/form-data" class="w-100 p-0"
                         action="{{ $edit ? route('admin.account.request-form.master-ledger.update') : route('admin.account.request-form.master-ledger.store') }}"
                         method="post">
                         @csrf
                         <input type="hidden" name="rf_id" value="{{ $edit ? $edit->id : '' }}">
                         <div class="vendor-header py-1 mb-0 ">
                             <div class="row px-5 py-2">
                                 <div class="col-md-3 ">
                                     <div class="d-flex justify-content-between">
                                         <label class="form-label">Company / Person Name, Phone <span
                                                 class="text-danger">*</span></label>
                                         <span class=" px-3 customer_acc_type_show"></span>
                                     </div>

                                     @if ($edit)
                                         <input type="hidden" class="customer_id_old" name="customer_id_old"
                                             value="{{ $edit->store_user }}">
                                         <div class="card shadow-sm p-3 bg-light position-relative customer_card">
                                             <a class="remove_user position-absolute" style="    right: 14px;">x</a>
                                             {{ $edit->customer?->f_name }}
                                         </div>
                                         @php
                                             $display = 'style=display:none';
                                         $required = ''; @endphp
                                     @else
                                         @php
                                             $display = '';
                                         $required = 'required'; @endphp
                                     @endif
                                     <div {{ $display }} class="user_select">
                                         <select {{ $required }} name="customer_id" id="customer_id"
                                             class="form-control">
                                             <option value="">---{{ translate('messages.select') }}---</option>
                                             <option value="add_new">Add New</option>
                                         </select>
                                     </div>
                                 </div>
                                 <div class="col-md-6">

                                 </div>
                                 <div class="col-md-3 d-flex justify-content-end">
                                     <table>
                                         <tr>
                                             <td>Date</td>
                                             <td> <input type="date"
                                                     value="{{ $edit && $edit->date ? $edit->date : date('Y-m-d') }}"
                                                     required name="date" class="form-control"></td>
                                         </tr>
                                     </table>
                                 </div>
                             </div>

                         </div>
                         <div class="vendor-header py-1 mb-0">
                             <div class="row px-5">
                                 <div class="col-md-3">

                                     <label class="form-label">Status</label><br>
                                     Pending
                                 </div>
                                 <div class="col-md-6 col-sm-6">

                                 </div>
                                 <div class="col-md-3 ">
                                     {{-- <label class="form-label mt-5"></label> --}}
                                     <div class="pt-2" style="text-align: end;">Receipt Voucher No <br>
                                         <b class="voucher_number_show">#{{ $voucher?->voucher_number ?? $voucherNo }}</b>
                                     </div>
                                 </div>
                                 <input type="hidden" name="voucher_number" class="voucher_number">
                                 {{-- <div class="col-md-4 col-sm-6 d-flex justify-content-end align-items-center gap-2">

                                <label class="form-label " style="white-space: nowrap">Receipt Type <span
                                        class="text-danger">*</span></label>
                                <select required name="type" class="form-control">
                                    <option value="income">Credit Note / Income</option>
                                    <option value="expense">Debit Note / Expense</option>
                                </select>
                            </div> --}}
                                 <!-- Status -->

                             </div>

                         </div>

                         <!-- Form Container -->
                         <div class="">
                             <div class="form-container">
                                 <input type="hidden" id="staff_id" name="account_id" value="">

                                 <div class="row g-1">
                                     <div class="col-md-3 ">
                                         <label class="form-label label_lg" style="white-space: nowrap">Requested
                                             By</label>
                                         <select name="requested_by" id="" data-placeholder="Select Requested By"
                                             class="js-select2-custom">
                                             <option value=""></option>
                                             @foreach ($employees as $key => $value)
                                                 <option {{ $edit && $edit->requested_by == $value->id ? 'selected' : '' }}
                                                     value="{{ $value->id }}">
                                                     {{ $value->f_name . ' ' . $value->l_name }}
                                                 </option>
                                             @endforeach
                                         </select>
                                     </div>
                                     <div class="col-md-3">
                                         <label class="form-label label_lg" style="white-space: nowrap">Requested
                                             To</label>
                                         <select name="request_to" id="" data-placeholder="Select Requested To"
                                             class="js-select2-custom">
                                             <option value=""></option>
                                             @foreach ($employees as $key => $value)
                                                 <option value="{{ $value->id }}"
                                                     {{ $edit && $edit->request_to == $value->id ? 'selected' : '' }}>
                                                     {{ $value->f_name . ' ' . $value->l_name }}
                                                 </option>
                                             @endforeach
                                         </select>
                                     </div>
                                     <!-- Description -->
                                     <div class="col-md-3 col-sm-6">
                                         <label class="form-label">Description<span class="text-danger">*</span></label>
                                         <input type="text" name="description" value="{{ $edit?->description }}"
                                             required placeholder="Description" class="form-control">
                                     </div>

                                     <!-- Category -->
                                     <div class="col-md-3 col-sm-6">
                                         <div class="d-flex justify-content-between">
                                             <label class="form-label">Account Type  <a href="{{route('admin.account.setting.chart-of-account.index')}}">Add</a><span
                                                     class="text-danger">*</span></label>
                                             <span class=" px-3 acc_type_show"></span>
                                         </div>
                                         <select data-placeholder="Select Category" required name="category"
                                             id="category" class="form-control js-select2-custom">
                                             <option value=""></option>
                                             @foreach ($accounts as $cc)
                                                 <option data-type="{{ $cc->acc_type }}"
                                                     {{ $edit && $edit->account_id == $cc->id ? 'selected' : '' }}
                                                     data-text = "{{ $cc->ledgerAccountType?->name }}"
                                                     value="{{ $cc['id'] }}">
                                                     {{ $cc->ledgerAccountType?->name . '/' . $cc->full_hierarchy }}
                                                 </option>
                                             @endforeach
                                         </select>
                                     </div>

                                     <!-- GST Amount -->
                                     <div class="col-md-3 col-sm-6">
                                         <label class="form-label">GST Amount</label>
                                         <input type="number" name="gst_amount" value="{{ $edit?->gst_amount }}"
                                             step="0.001" placeholder="GST Amount" class="form-control">
                                     </div>

                                     <!-- Payment Mode -->
                                     <div class="col-md-3 col-sm-6">
                                         <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                                         <select name="payment_mode" required class="form-control">
                                             <option value=""></option>
                                             <option {{ $edit && $edit->payment_mode == 'bank' ? 'selected' : '' }}
                                                 value="bank">Bank</option>
                                             <option {{ $edit && $edit->payment_mode == 'upi' ? 'selected' : '' }}
                                                 value="upi">UPI</option>
                                             <option {{ $edit && $edit->payment_mode == 'cash' ? 'selected' : '' }}
                                                 value="cash">Cash</option>
                                         </select>
                                     </div>

                                     <!-- Additional Note -->
                                     <div class="col-md-3 col-sm-6">
                                         <label class="form-label">Additional Note <i>(Optional)</i></label>
                                         <textarea name="note" placeholder="Additional Note" class="form-control" rows="1">{{ $edit?->note }}</textarea>
                                     </div>

                                     <!-- Document -->
                                     <div class="col-md-3 col-sm-6">
                                         <label class="form-label">Document <i>(Optional)</i>
                                             @if ($edit && $edit->doc_file)
                                                 <a class="text-decoration-underline"
                                                     href="{{ asset('storage/app/public/store/docs/' . $edit->doc_file) }}"
                                                     target="_blank"> View Document</a>
                                             @endif
                                         </label>
                                         <input type="file" name="file" class="form-control">
                                     </div>

                                     <!-- Bill Number -->
                                     <div class="col-md-3 col-sm-6">
                                         <label class="form-label">Bill Number / Details</label>
                                         <input type="text" name="bill_number" value="{{ $edit?->bill_number }}"
                                             placeholder="Bill Number / Details" class="form-control">
                                     </div>

                                     <!-- Ledger Account Type -->
                                     <div class="col-md-3 col-sm-6">
                                         <label class="form-label">Ledger Account Type </label>
                                         <div class="d-flex gap-2">
                                             <input type="text" readonly class="form-control ledger_account_type"
                                                 placeholder="Ledger Account Type">
                                         </div>
                                     </div>

                                     <!-- Submit Button -->
                                     <div class="col-12 mt-4">
                                         <div class="d-flex justify-content-end flex-column align-items-end">
                                             <div class="col-md-3 mb-2 pl-2 pr-0">
                                                 <label class="form-label">Amount (₹) <span
                                                         class="text-danger">*</span></label>
                                                 <input type="number" name="amount" value="{{ $edit?->amount }}"
                                                     required placeholder="EX: 1200" class="form-control amount_inp">
                                             </div>
                                             <div class="col-md-3 mb-2 pl-2 pr-0">
                                                 <button type="submit" class="btn btn-primary w-100">
                                                     Submit
                                                 </button>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </form>
                 </div>
             </div>

                 @if (!$edit)
                     <div class="card shadow rounded col-md-5 ">
                         <h3 class="my-2">My Requests</h3>
                         <div class="row">
                             @forelse($data['submitted_req'] as $req)
                                 <div class="col-md-6 mb-4">
                                     <div class="card shadow-sm">
                                         <div class="card-body">
                                             <h5 class="card-title">
                                                 Request #: {{ $req->request_number }}
                                             </h5>
                                             <p class="card-text">
                                                 <strong>Date:</strong>
                                                 {{ \Carbon\Carbon::parse($req->date)->format('d M, Y') }}<br>
                                                 <strong>Requested By:</strong>
                                                 {{ $req->requestedBy?->f_name . ' ' . $req->requestedBy?->l_name }}<br>
                                                 <strong>Requested To:</strong>
                                                 {{ $req->requestedTo?->f_name . ' ' . $req->requestedTo?->l_name }}<br>
                                                 <strong>Amount:</strong> ₹{{ number_format($req->amount, 2) }}<br>
                                                 <strong>Status:</strong>
                                                 @if ($req->status == 'pending')
                                                     <span class="badge bg-warning text-dark">Pending</span>
                                                 @elseif($req->status == 'approved')
                                                     <span class="badge bg-success ">Approved</span>
                                                 @elseif($req->status == 'rejected')
                                                     <span class="badge bg-danger text-white">Rejected</span>
                                                     @if ($req->resubmit <= $permitted_resubmit)
                                                         @if (hasPermission('apporval_form_master_ledger', 'edit'))
                                                             <a style="padding: 0 10px ; width: fit-content;"
                                                                 class="my-2 btn action-btn btn--primary btn-outline-primary edit_n_resubmit"
                                                                 href="{{ route('admin.account.request-form.master-ledger.index', [$req->id]) }}"
                                                                 title="{{ translate('messages.edit') }}"><i
                                                                     class="tio-edit"></i>
                                                                 Edit &
                                                                 Resubmit
                                                             </a>
                                                         @endif
                                                     @endif
                                                 @else
                                                     <span
                                                         class="badge bg-primary text-white">{{ ucfirst($req->status) }}</span>
                                                 @endif
                                             </p>
                                             @if ($req->doc_file)
                                                 <a href="{{ asset('storage/app/public/store/docs/' . $req->doc_file) }}"
                                                     target="_blank" class="btn btn-sm btn-primary">View Document</a>
                                             @endif
                                         </div>
                                     </div>
                                 </div>
                             @empty
                                 <div class="col-12">
                                     <p class="text-center text-muted">No requests found.</p>
                                 </div>
                             @endforelse
                         </div>

                     </div>
                 @endif
         </div>
         <button type="button" class="btn btn-primary d-none open_requests" data-toggle="modal"
             data-target="#requestsModal">

         </button>

             <!-- Modal -->
             <div class="modal fade" id="requestsModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                 aria-hidden="true">
                 <div class="modal-dialog modal-lg">
                     <div class="modal-content">
                         <div class="modal-header">
                             <h5 class="modal-title" id="exampleModalLabel">My Requests</h5>
                             <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                 <span aria-hidden="true">&times;</span>
                             </button>
                         </div>
                         <div class="modal-body">
                             <div class="row">
                                 @forelse($data['submitted_req'] as $req)
                                     <div class="col-md-6 mb-4">
                                         <div class="card shadow-sm">
                                             <div class="card-body">
                                                 <h5 class="card-title">
                                                     Request #: {{ $req->request_number }}
                                                 </h5>
                                                 <p class="card-text">
                                                     <strong>Date:</strong>
                                                     {{ \Carbon\Carbon::parse($req->date)->format('d M, Y') }}<br>
                                                     <strong>Requested By:</strong>
                                                     {{ $req->requestedBy?->f_name . ' ' . $req->requestedBy?->l_name }}<br>
                                                     <strong>Requested To:</strong>
                                                     {{ $req->requestedTo?->f_name . ' ' . $req->requestedTo?->l_name }}<br>
                                                     <strong>Amount:</strong> ₹{{ number_format($req->amount, 2) }}<br>
                                                     <strong>Status:</strong>
                                                     @if ($req->status == 'pending')
                                                         <span class="badge bg-warning text-dark">Pending</span>
                                                     @elseif($req->status == 'approved')
                                                         <span class="badge bg-success ">Approved</span>
                                                     @elseif($req->status == 'rejected')
                                                         <span class="badge bg-danger text-white">Rejected</span>
                                                         @if ($req->resubmit <= $permitted_resubmit)
                                                             @if (hasPermission('apporval_form_master_ledger', 'edit'))
                                                                 <a style="padding: 0 10px ; width: fit-content;"
                                                                     class="my-2 btn action-btn btn--primary btn-outline-primary edit_n_resubmit"
                                                                     href="{{ route('admin.account.request-form.master-ledger.index', [$req->id]) }}"
                                                                     title="{{ translate('messages.edit') }}"><i
                                                                         class="tio-edit"></i>
                                                                     Edit &
                                                                     Resubmit
                                                                 </a>
                                                             @endif
                                                         @endif
                                                     @else
                                                         <span
                                                             class="badge bg-primary text-white">{{ ucfirst($req->status) }}</span>
                                                     @endif
                                                 </p>
                                                 @if ($req->doc_file)
                                                     <a href="{{ asset('storage/app/public/store/docs/' . $req->doc_file) }}"
                                                         target="_blank" class="btn btn-sm btn-primary">View Document</a>
                                                 @endif
                                             </div>
                                         </div>
                                     </div>
                                 @empty
                                     <div class="col-12">
                                         <p class="text-center text-muted">No requests found.</p>
                                     </div>
                                 @endforelse
                             </div>
                         </div>
                         <div class="modal-footer">
                             <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                         </div>
                     </div>
                 </div>
             </div>


     @endsection

     @push('script_2')
         <script>
             @if ($tab == 'approvals')
                 $(".open_requests").click()
             @endif

             $(".remove_user").on('click', function() {
                 $(".user_select").show();
                 $(".customer_card").hide()
                 $(".customer_id_old").val('')
                 $("#customer_id").attr('required', true)
             })
             $(document).ready(function() {
                 $("#category").trigger('change')
             })
             $("#category").on('change', function() {
                 const selectedValue = $('#category option:selected').data('value');
                 const selectedText = $('#category option:selected').data('text');
                 const selectedType = $('#category option:selected').data('type');
                 // Update account type display
                 $(".acc_type_show")
                     .text(selectedType.toUpperCase())
                     .removeClass('text-success text-danger')
                     .addClass(selectedType === 'credit' ? 'text-success' : 'text-danger');

                 // Determine opposite type for customer
                 let customerType = selectedType === 'credit' ? 'debit' : 'credit';

                 $(".customer_acc_type_show")
                     .text(customerType.toUpperCase())
                     .removeClass('text-success text-danger')
                     .addClass(customerType === 'credit' ? 'text-success' : 'text-danger');

                 $(".ledger_account_type").val(selectedText)

                 $("#ledger_account_type2").val(selectedValue).trigger('change');
             });
         </script>
     @endpush
