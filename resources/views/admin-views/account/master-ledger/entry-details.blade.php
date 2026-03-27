 <div class="vendor-header pb-2 mb-0">
     <div class="container position-relative d-flex justify-content-between px-5 align-items-center">
         <div class="store_content">
             <h2 class="vendor-name">{{ \App\Models\BusinessSetting::where('key', 'business_name')->first()->value }}</h2>
             <p class="vendor-address">
                 {{ \App\Models\BusinessSetting::where('key', 'address')->first()->value }}<br>
                 GST NO: {{ \App\Models\BusinessSetting::where('key', 'gst_number')->first()->value }}
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
                 <label class="form-label">Company / Person Name, Phone </label><br>
                 {{ $entry->storeUser?->f_name  }}
             </div>
             <div class="col-md-6">

             </div>
             <div class="col-md-3 d-flex justify-content-end">
                 <table>
                     <tr>
                         <td><b>Date</b></td>
                         <td> {{ $entry->entry_date ?? $entry->created_at }}</td>
                     </tr>
                 </table>
             </div>
         </div>

     </div>
     <div class="vendor-header py-1 mb-0">
         <div class="row px-5">
             <div class="col-md-3">
                 <label class="form-label">Status </label><br>
                 {{ $entry->status }}
             </div>
             <div class="col-md-6 col-sm-6">

             </div>
             <div class="col-md-3 ">
                 {{-- <label class="form-label mt-5"></label> --}}
                 <div class="pt-2" style="text-align: end;">Receipt Voucher No <br>
                     <b class="voucher_number_show">#{{ $entry->voucher?->voucher_number }}</b>
                 </div>
             </div>

         </div>

     </div>

     <!-- Form Container -->
     <div class="">
         <div class="form-container">
             <input type="hidden" id="staff_id" name="account_id" value="">

             <div class="row g-1">
                 <!-- Customer Selection -->


                 <!-- Description -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">Description</label>
                     {{ $entry->description }}
                 </div>

                 <!-- Category -->
                 <div class="col-md-3 col-sm-6">
                     <div class="d-flex justify-content-between">
                         <label class="form-label">Account Type </label>
                         <span class=" px-3 acc_type_show"></span>
                     </div>
                       {{ $entry->account?->ledgerAccountType?->name . '/' . $entry->account?->full_hierarchy }}
                 </div>

                 <!-- GST Amount -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">GST Amount</label> <br>
                     {{ _price($entry->gst_amount) }}
                 </div>

                 <!-- Payment Mode -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">Payment Mode </label> <br>
                     {{ $entry->payment_mode}}
                 </div>



                 <!-- Additional Note -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">Additional Note</label><br>
                     {{ $entry->note }}
                 </div>

                 <!-- Document -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">Document</label><br>
                     @if($entry->document) <a href="{{asset('storage/app/public/store/docs/' . '/' . $entry->document)}}">{{$entry->document}}</a> @endif
                 </div>

                 <!-- Bill Number -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">Bill Number / Details</label><br>
                    {{$entry->bill_number}}
                 </div>

                 <!-- Ledger Account Type -->
                 <div class="col-md-3 col-sm-6">
                     <label class="form-label">Ledger Account Type </label><br>
                     {{$entry->account?->ledgerAccountType?->name}}
                 </div>


                 <!-- Submit Button -->
                 <div class="col-12 mt-4">
                     <div class="d-flex justify-content-end flex-column align-items-end">
                         <div class="col-md-3 mb-2 pl-2 pr-0">
                             <label class="form-label">Amount</label><br>
                         <p style="    font-size: 20px; font-weight: bold;">{{$entry->debit > 0 ? _price($entry->debit) : _price($entry->credit)}}</p> 
                         </div>
                       
                     </div>
                 </div>
             </div>
         </div>
     </div>
 </form>
