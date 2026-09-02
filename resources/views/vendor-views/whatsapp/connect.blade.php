{{-- Connection & Plan — linking the number, the numbers already linked, and what it all costs.
     These were three menu items that constantly referred to each other: the connect flow quotes
     the plan price, Numbers' only two buttons went back to Connect, and the plan gate is what
     decides whether a number may be linked at all.

     Bulk sending used to live here too; it is its own page now (Bulk Message). --}}
@extends('layouts.vendor.app')

@section('title', translate('Connection & Plan'))

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
    @include('vendor-views.whatsapp.partials._connect_css')
@endpush

@section('content')
    <div class="content container-fluid wa-page">
        <div class="page-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0"><i class="tio-chat"></i> {{ translate('Connection & Plan') }}</h1>
                <span class="wa-sub">
                    {{ $connected
                        ? translate('Manage your connected numbers, your plan and everything you are billed for.')
                        : translate('Link your business number and choose the plan it runs on.') }}
                </span>
            </div>
            @if ($connected)
                <span class="wa-chip badge-soft-success"><i class="tio-checkmark-circle"></i> {{ translate('Number connected') }}</span>
            @endif
        </div>

        {{-- Before a number is linked there is nothing to manage and no numbers to list, so only
             Connection and Plan are offered — the plan gate is part of getting connected. --}}
        <ul class="nav wa-tabs mb-3" role="tablist"
            style="background:#fff;border:1px solid var(--wa-line);border-radius:14px;">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'connection' ? 'active' : '' }}" data-toggle="tab" href="#waConnection" role="tab">
                    <i class="tio-settings-outlined"></i> {{ translate('Connection') }}
                </a>
            </li>
            @if ($connected)
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'numbers' ? 'active' : '' }}" data-toggle="tab" href="#waNumbers" role="tab">
                        <i class="tio-android-phone"></i> {{ translate('Numbers') }}
                        <span class="wa-chip badge-soft-secondary ml-1">{{ count($numbers) }}</span>
                    </a>
                </li>
            @endif
            @if (hasAnyModulePermission(['whatsapp_billing']))
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'billing' ? 'active' : '' }}" data-toggle="tab" href="#waBilling" role="tab">
                        <i class="tio-wallet"></i> {{ translate('Plan & Billing') }}
                    </a>
                </li>
            @endif
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade {{ $tab === 'connection' ? 'show active' : '' }}" id="waConnection" role="tabpanel">
                @include('vendor-views.whatsapp.partials._connect_connection')
            </div>

            @if ($connected)
                <div class="tab-pane fade {{ $tab === 'numbers' ? 'show active' : '' }}" id="waNumbers" role="tabpanel">
                    @include('vendor-views.whatsapp.partials._connect_numbers')
                </div>
            @endif

            @if (hasAnyModulePermission(['whatsapp_billing']))
                <div class="tab-pane fade {{ $tab === 'billing' ? 'show active' : '' }}" id="waBilling" role="tabpanel">
                    @include('vendor-views.whatsapp.partials._connect_billing')
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
    @include('vendor-views.whatsapp.partials._connect_billing_js')
    <script>
        // Keep the open tab in the URL so a reload, a gateway return or a shared link comes back
        // to the pane the vendor was on rather than resetting to Connection.
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var map = { '#waConnection': 'connection', '#waNumbers': 'numbers', '#waBilling': 'billing' };
            var tab = map[$(e.target).attr('href')];
            if (tab && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            }
        });
    </script>
@endpush

{{-- Loaded whenever Embedded Signup is configured, not only when the store is unconnected: the
     Connection pane now offers "Add another number" to a store that already has one, and that
     button is bound by this script. --}}
@if ($es['ready'])
@push('script_2')
    @include('vendor-views.whatsapp.partials._connect_es_js')
@endpush
@endif
