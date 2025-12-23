@extends('layouts.vendor.app')

@section('title', 'Subscriptions')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/module_list.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/customize_plan.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Subscriptions</h1>

        </div>
        <div class="">


            <!-- Calculator Panel -->
            <div class="m-4">
                <h2 class="mb-3 font-weight-bold">Active Modules</h2>

                <div class="row">
                    @if (isset($subscriptions) && count($subscriptions) > 0)
                        @foreach ($subscriptions as $sub)
                            <div class="col-md-4 mb-3 ">
                                <div class="pm-card  ">
                                    <div class="pm-left">
                                        <h6 class="pm-title">{{ $sub->plan?->title ?? $sub->permitted_modules }}</h6>
                                        <p class="pm-expiry">Expires on:
                                            <strong>{{ _formatted_datetime($sub->plan_expiry) }}</strong>
                                        </p>
                                    </div>
                                    <div class="pm-right">
                                        <div class="pm-price">{{ $sub->duration_count }} {{ $sub->duration_type }}</div>
                                        <div class="pm-duration">{{ _price($sub->purchased_at) }}</div>
                                    </div>
                                </div>
                            </div>


                            {{-- <div class="pc-module-item" >
                                <div class="pc-module-top">
                                    <div class="pc-module-name">{{ $sub->plan?->title ?? $sub->permitted_modules }}</div>
                                    <div class="pc-price-amount">
                                        ₹{{ number_format($sub->purchased_at) }}
                                    </div>
                                    <div class="pc-price-amount">
                                      Duration  {{ $sub->duration_count }} {{$sub->duration_type}}
                                    </div>
                                    <div class="pc-price-amount">
                                      Expiry  {{ $sub->plan_expiry }}
                                    </div>
                                </div>
                            </div> --}}
                        @endforeach
                    @else
                        <p style="color: #666; text-align: center; padding: 40px;">No modules available yet. Please
                            try
                            again later.</p>
                    @endif


                </div>
            </div>
            @include('partials.module_buy')
        </div>
    </div>

@endsection

@push('script_2')

  @include('partials.module_buy_js')


    @if (request('flag') && request('flag') == 'success')
        <script>
            $(document).ready(function() {
                toastr.success('Plan purchased successfully!', 'Success');

                const url = new URL(window.location);
                url.searchParams.delete('flag'); // Remove flag
                url.searchParams.delete('token'); // Remove token

                window.history.replaceState({}, '', url);
            });
        </script>
    @endif
@endpush
