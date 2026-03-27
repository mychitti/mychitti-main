 <div class="vendor-header pb-2 mb-0">
     <div class="container position-relative d-flex justify-content-between px-5 align-items-center">
         <div class="store_content">
             <h2 class="vendor-name">{{ \App\Models\BusinessSetting::where('key', 'business_name')->first()->value }}</h2>
             <p class="vendor-address">
                 {{ \App\Models\BusinessSetting::where('key', 'address')->first()->value }}<br>
                 GST NO: {{ \App\Models\BusinessSetting::where('key', 'gst_number')->first()->value  }}
             </p>
         </div>
         <div class="logo-container">
             <div class="company-logo">
                 <img style="width: 100px;"
                     src="{{ asset('storage/app/public/business/' . \App\Models\BusinessSetting::where('key', 'logo')->first()->value) }}"
                     alt="">
             </div>
         </div>
     </div>
 </div>
 <form enctype="multipart/form-data" class="w-100 p-0" action="{{ route('admin.account.master-ledger.entry') }}"
     method="post">
     @csrf
     <div class="vendor-header py-1 mb-0 ">
         <div class="row px-5 py-2">
             <div class="col-md-3 ">
                 <div class="d-flex justify-content-between">
                     <label class="form-label">Company / Person Name, Phone <span class="text-danger">*</span></label>
                     <span class=" px-3 customer_acc_type_show"></span>
                 </div>

                 <select required name="customer_id" id="customer_id" class="form-control">
                     <option value="">---{{ translate('messages.select') }}---</option>
                     <option value="add_new">Add New</option>

                 </select>
             </div>
             <div class="col-md-6">

             </div>
             <div class="col-md-3 d-flex justify-content-end">
                 <table>
                     <tr>
                         <td>Date</td>
                         <td> <input type="date" value="{{ date('Y-m-d') }}" required name="date"
                                 class="form-control"></td>
                     </tr>
                 </table>
             </div>
         </div>

     </div>
     <div class="vendor-header py-1 mb-0">
         <div class="row px-5">
             <div class="col-md-3">
                 <label class="form-label">Status <span class="text-danger">*</span></label>
                 <select name="status" required class="form-control">
                     <option value="approved">Paid</option>
                     <option value="pending">Unpaid</option>
                 </select>
             </div>
             <div class="col-md-6 col-sm-6">

             </div>
             <div class="col-md-3 ">
                 {{-- <label class="form-label mt-5"></label> --}}
                 <div class="pt-2" style="text-align: end;">Receipt Voucher No <br>
                     <b class="voucher_number_show">#{{ $voucherNo }}</b>
                 </div>
             </div>
             <input type="hidden" name="voucher_number" class="voucher_number">

         </div>

     </div>

     <!-- Form Container -->
     <div class="">
         <div class="form-container">
             <input type="hidden" id="staff_id" name="account_id" value="">

             <div class="row g-1">

                 <!-- Description -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">Description<span class="text-danger">*</span></label>
                     <input type="text" name="description" required placeholder="Description" class="form-control">
                 </div>
                 @if (_storeAccountType() == 'ledger')
                     <!-- Category -->
                     @php $cost_centers = _costCenters()@endphp
                     <div class="col-md-3 col-sm-6">
                         <div class="d-flex justify-content-between">
                             <label class="form-label">Account Type <span class="text-danger">*</span></label>
                             <span class=" px-3 acc_type_show"></span>
                         </div>
                         <select data-placeholder="Select Category" required name="category" id="category"
                             class="form-control js-select2-custom">
                             <option value=""></option>
                             @foreach ($cost_centers as $cc)
                                 <option data-type="{{ $cc->acc_type }}"
                                     data-text = "{{ $cc->ledgerAccountType?->name }}" value="{{ $cc['id'] }}">
                                     {{ $cc->ledgerAccountType?->name . '/' . $cc->full_hierarchy }}
                                 </option>
                             @endforeach
                         </select>
                     </div>
                 @endif

                 <!-- GST Amount -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">GST Amount</label>
                     <input type="number" name="gst_amount" placeholder="GST Amount" class="form-control" step="0.001">
                 </div>

                 <!-- Payment Mode -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                     <select name="payment_mode" required class="form-control">
                         <option value="" selected disabled>--select--</option>
                         <option value="bank">Bank</option>
                         <option value="upi">UPI</option>
                         <option value="cash">Cash</option>
                     </select>
                 </div>

                 <!-- Additional Note -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">Additional Note <i>(Optional)</i></label>
                     <textarea name="note" placeholder="Additional Note" class="form-control" rows="1"></textarea>
                 </div>

                 <!-- Document -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">Document <i>(Optional)</i></label>
                     <input type="file" name="file" class="form-control">
                 </div>

                 <!-- Bill Number -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">Bill Number / Details</label>
                     <input type="text" name="bill_number" placeholder="Bill Number / Details"
                         class="form-control">
                 </div>
                 @if (_storeAccountType() == 'ledger')
                     <!-- Ledger Account Type -->
                     <div class="col-md-3 col-sm-6">
                         <label class="form-label">Ledger Account Type </label>
                         <div class="d-flex gap-2">
                             <input type="text" readonly class="form-control ledger_account_type"
                                 placeholder="Ledger Account Type">
                         </div>
                     </div>
                 @endif

                 <!-- Submit Button -->
                 <div class="col-12 mt-4">
                     <div class="d-flex justify-content-end flex-column align-items-end">
                         <div class="col-md-3 mb-2 pl-2 pr-0">
                             <label class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                             <input type="number" name="amount" required placeholder="EX: 1200"
                                 class="form-control amount_inp">
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
