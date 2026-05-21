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
                @php
                    if (isset($subscriptions) && count($subscriptions) > 0){

                    $allFreeTrial = $subscriptions->every(fn($sub) => $sub->free_trial == 1);

                    $trialStart = $subscriptions->first()->created_at;
                    $expiryDate = $trialStart->copy()->addDays(15)->format('d M Y');
                    }
                @endphp

                @if (isset($allFreeTrial) && $allFreeTrial)
                    <div class="alert alert-info w-100" role="alert">
                        ℹ️ Your 15-day free trial is currently active and will expire on {{ $expiryDate }}.

                    </div>
                @endif
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
                                        @if ($sub->free_trial)
                                            <span class="badge badge-soft-success">Free Trial</span>
                                        @else
                                            <div class="pm-duration">{{ _price($sub->purchased_at) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                    @if($StoreConfig && (int) $StoreConfig->free_trial_consumed === 0)
                        <div class="alert alert-warning" role="alert">
                            🎉 Start your free trial today! Get 15 days of free access with no automatic charges. You can
                            upgrade whenever you’re ready.
                            <a href="{{ route('vendor.profile.enable-free-trial') }}" class="btn btn-primary">Start Free
                                Trial</a>
                        </div>
                        @endif
                        <p style="color: #666; text-align: center; padding: 40px;">No modules available active yet.</p>
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
