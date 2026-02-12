@extends('layouts.vendor.app')

@section('title', translate('messages.offer banner'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/banner.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.add_new_offer_banner') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
     
    </div>

    </div>

    <div class="col-sm-12 col-lg-6 mb-3 mb-lg-2">

    </div>
    <!-- End Table -->
    </div>
    </div>

@endsection

@push('script_2')

@endpush
