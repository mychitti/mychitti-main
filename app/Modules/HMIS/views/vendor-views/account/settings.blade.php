 @extends('layouts.vendor.app')

 @section('title', 'Account Settings')

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
 @endpush

 @section('content')
     <div class="content container-fluid">
         <!-- Page Header -->
         <div class="page-header">
             <h1 class="page-header-title"><i class="tio-filter-list"></i>Account Settings</h1>
         </div>
         <!-- End Page Header -->
@if(hasPermission('settings_account_type', 'edit') )
         <form class="w-100 p-0 " enctype="multipart/form-data" action="{{ route('vendor.account.settings.update') }}"
             method="post">
             @csrf
             <div class="card mb-1">
                 <!-- Body -->
                 <div class="card-body row align-items-end">
                     <div class="col-md-3">
                         <div class="btn-group btn-group-toggle m-0" style="margin: 2px auto;" data-toggle="buttons">
                             <label class="btn btn-responsive btn-outline-primary  ">
                                 <input type="radio" {{($store && $store->account_type  == 'normal' ) || (!$store || !$store->account_type)  ? 'checked' : ''}} class="account_type" value="normal" name="account_type" id="option1"> Normal Account
                             </label>
                             <label class="btn btn-responsive btn-outline-primary ">
                                 <input type="radio" {{$store && $store->account_type == 'ledger' ? 'checked' : ''}} class="account_type" value="ledger" name="account_type"
                                     id="option3">
                                 Ledger Account
                             </label>
                         </div>
                     </div>
                     <div class="col-12 mb-3">
                         <button style="float:right" class="btn btn-primary my-2">Update</button>
                     </div>
                 </div>
         </form>
         @endif
     </div>
 @endsection

 @push('script_2')
     <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/product-index.js"></script>
 @endpush
