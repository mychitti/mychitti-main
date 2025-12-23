@extends('layouts.admin.app')

@section('title', \App\Models\BusinessSetting::where(['key' => 'business_name'])->first()->value ??
    translate('messages.dashboard'))

    @push('css_or_js')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

@section('content')
    <div class="content container-fluid">
        @if (auth('admin')->user()->role_id == 1)
            @php($mod = \App\Models\Module::find(Config::get('module.current_module_id')))
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center py-2">
                    <div class="col-sm mb-2 mb-sm-0">
                        <div class="d-flex align-items-center">
                         @php($logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value)
                            <img class="onerror-image"
                                data-onerror-image="{{ asset('/public/assets/admin/img/grocery.svg') }}"
                                src="{{ asset('storage/app/public/business/' . $logo ?? '') }}"
                                width="38" alt="img">
                            <div class="w-0 flex-grow pl-2">
                                <h1 class="page-header-title mb-0">Common
                                    {{ translate('messages.Dashboard') }}.</h1>
                                <p class="page-header-text m-0">{{ translate('Hello, Here You Can Manage Your') }} Common modules</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <!-- End Page Header -->
        @else
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm mb-2 mb-sm-0">
                        <h1 class="page-header-title">{{ translate('messages.welcome') }},
                            {{ auth('admin')->user()->f_name }}.</h1>
                        <p class="page-header-text">{{ translate('messages.employee_welcome_message') }}</p>
                    </div>
                </div>
            </div>
            <!-- End Page Header -->
        @endif
    </div>
@endsection

@push('script')
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js.extensions/chartjs-extensions.js"></script>
    <script
        src="{{ asset('public/assets/admin') }}/vendor/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.min.js">
    </script>

    <!-- Apex Charts -->
    <script src="{{ asset('/public/assets/admin/js/apex-charts/apexcharts.js') }}"></script>
    <!-- Apex Charts -->
@endpush
