 @extends('layouts.admin.app')

 @section('title', translate('mcvendor_terms_and_conditions'))


 @section('content')
     <div class="content container-fluid">
         <!-- Page Header -->
         <div class="page-header">
             <h1 class="page-header-title mr-3">
                 <span class="page-header-icon">
                     <img src="{{ asset('public/assets/admin/img/business.png') }}" class="w--26" alt="">
                 </span>
                 <span>
                     {{ translate('messages.mcvendor_terms_and_conditions') }}
                 </span>
             </h1>
             @include('admin-views.mcvendor-settings.partials.nav-menu')
         </div>
         <!-- End Page Header -->


         @php($name = \App\Models\BusinessSetting::where('key', 'business_name')->first())
         <div class="row g-3">
             <div class="col-lg-12">
                 <div class="card">
                     <div class="card-body">
                         <div class="row g-3">
                             <div class="row gx-2 gx-lg-3">
                                 <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                                     <form action="{{ route('admin.business-settings.terms-and-conditions') }}"
                                         method="post" id="ck_editor_form2">
                                         @csrf
                                         <div class="form-group lang_form" id="default-form">
                                             <textarea class="ck_editor2 form-control" name="vendorhub_terms_and_conditions">{!! $vendorhub_terms_and_conditions?->getRawOriginal('value') ?? '' !!}</textarea>
                                         </div>
                                         <div class="btn--container justify-content-end">
                                             <button type="submit"
                                                 class="btn btn--primary">{{ translate('messages.submit') }}</button>
                                         </div>
                                     </form>
                                 </div>
                             </div>
                         </div>

                     </div>
                 </div>
             </div>
         </div>
     </div>
 @endsection
 @push('script_2')
     <script
         src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&v=3.45.8">
     </script>
     @include('vendor-views/multiple_ck_editor');
 @endpush
