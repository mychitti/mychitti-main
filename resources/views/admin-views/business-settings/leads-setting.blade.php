 @extends('layouts.admin.app')

 @section('title', 'Lead Settings')

 @push('css_or_js')
     <meta name="csrf-token" content="{{ csrf_token() }}">
 @endpush

 @section('content')
     <div class="content container-fluid">
         <!-- Page Header -->
         <div class="page-header">
             <h1 class="page-header-title mr-3">
                 <span class="page-header-icon">
                     <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                 </span>
                 <span>
                     {{ translate('messages.business_settings') }}
                 </span>
             </h1>
             @include('admin-views.business-settings.partials.nav-menu')
         </div>
         <!-- End Page Header -->



         <div class="card  ">
             <!-- Header -->
             <div class="card-header py-2">
                 <div class="search--button-wrapper d-flex justify-content-between">
                     <h5 class="card-title">Lead Settings</h5>
                     <!-- End Unfold -->
                 </div>
             </div>
             <form action="{{ route('admin.business-settings.update-setup2') }}" method="post"
                 enctype="multipart/form-data">
                 @csrf
                 @php($name = \App\Models\BusinessSetting::where('key', 'business_name')->first())

                 <div class="row g-3">

                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-body p-0">

                                 <div class="__bg-F8F9FC-card p-0 mt-4">
                                     <div class="card-body">
                                         <div class="row g-3 align-items-end">

                                             <div class="col-sm-6 col-lg-4">
                                                 @php($gatepass_max_image = \App\Models\BusinessSetting::where('key', 'gatepass_max_image')->first())
                                                 <div class="form-group mb-0">
                                                     <label
                                                         class="form-label d-flex justify-content-between text-capitalize mb-1"
                                                         for="gatepass_max_image">
                                                         <span
                                                             class="line--limit-1">{{ translate('messages.gatepass_max_image') }}
                                                             <small class="text-danger"><span class="form-label-secondary"
                                                                       data-original-title="Maximum number of images a vendor can upload per gatepass item."  data-toggle="tooltip" data-placement="right"><img
                                                                         src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"></span>
                                                                 *</small></span>
                                                     </label>

                                                     <input type="text" name="gatepass_max_image" class="form-control"
                                                         id="gatepass_max_image"
                                                         placeholder="{{ translate('messages.Ex:4') }}"
                                                         value="{{ $gatepass_max_image ? $gatepass_max_image->value : '' }}"
                                                         required>
                                                 </div>
                                             </div>
                                             <div class="col-sm-6 col-lg-4">
                                                 @php($gatepass_image_max_size = \App\Models\BusinessSetting::where('key', 'gatepass_image_max_size')->first())
                                                 <div class="form-group mb-0">
                                                     <label
                                                         class="form-label d-flex justify-content-between text-capitalize mb-1"
                                                         for="gatepass_image_max_size">
                                                         <span
                                                             class="line--limit-1">{{ translate('messages.gatepass_image_max_size') }} (in MB)
                                                             <small class="text-danger"><span class="form-label-secondary"
                                                                       data-original-title="Maximum size of eachl image a vendor can upload in gatepass item."  data-toggle="tooltip" data-placement="right"><img
                                                                         src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"></span>
                                                                 *</small></span>
                                                     </label>

                                                     <input type="text" name="gatepass_image_max_size" class="form-control"
                                                         id="gatepass_image_max_size"
                                                         placeholder="{{ translate('messages.Ex:5') }}"
                                                         value="{{ $gatepass_image_max_size ? $gatepass_image_max_size->value : '' }}"
                                                         required>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>


                                 <div class="btn--container justify-content-end mt-3">
                                     <button type="reset"
                                         class="btn btn--reset">{{ translate('messages.reset') }}</button>
                                     <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                                         class="btn btn--primary call-demo">{{ translate('save_information') }}</button>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </form>
         </div>
     </div>

 @endsection

 @push('script_2')
 @endpush
