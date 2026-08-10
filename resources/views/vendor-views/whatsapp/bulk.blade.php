{{-- Bulk Message — composing a batch, the audience it goes to, and what previous batches did.
     These were two menu items whose buttons pointed at each other: History's only action was
     "New bulk message" and the composer had no way back to what it had already sent. --}}
@extends('layouts.vendor.app')

@section('title', translate('Bulk Message'))

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0"><i class="tio-send"></i> {{ translate('Bulk Message') }}</h1>
                <span class="wa-sub">
                    {{ translate('Send a message to your customers, manage who is in your book, and review what you have already sent.') }}
                </span>
            </div>
            @if ($connected)
                <span class="wa-chip badge-soft-success"><i class="tio-checkmark-circle"></i> {{ translate('Number connected') }}</span>
            @endif
        </div>

        @if (!$connected)
            {{-- Nothing on this page can run without a connected number, and the composer would
                 render as an empty shell. Send them to the one place that fixes it. --}}
            <div class="wa-card">
                <div class="wa-card-b text-center py-5">
                    <i class="tio-android-phone" style="font-size:38px; color:#bdc5d1;"></i>
                    <h4 class="mt-3 mb-1">{{ translate('No number connected yet') }}</h4>
                    <p class="wa-sub mb-3">
                        {{ translate('Link your WhatsApp Business number before sending a bulk message.') }}
                    </p>
                    <a href="{{ route('vendor.whatsapp.connect') }}" class="btn btn--primary">
                        <i class="tio-add-circle mr-1"></i> {{ translate('Connect a number') }}
                    </a>
                </div>
            </div>
        @else
            <ul class="nav wa-tabs mb-3" role="tablist"
                style="background:#fff;border:1px solid var(--wa-line);border-radius:14px;">
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'compose' ? 'active' : '' }}" data-toggle="tab" href="#waCompose" role="tab">
                        <i class="tio-send"></i> {{ translate('Send a message') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'audience' ? 'active' : '' }}" data-toggle="tab" href="#waAudience" role="tab">
                        <i class="tio-user-big-outlined"></i> {{ translate('Your customers') }}
                        <span class="wa-chip badge-soft-secondary ml-1">{{ number_format($customerStats['with_phone']) }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'history' ? 'active' : '' }}" data-toggle="tab" href="#waHistory" role="tab">
                        <i class="tio-history"></i> {{ translate('History') }}
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ $tab === 'compose' ? 'show active' : '' }}" id="waCompose" role="tabpanel">
                    @include('vendor-views.whatsapp.partials._bulk_compose')
                </div>

                <div class="tab-pane fade {{ $tab === 'audience' ? 'show active' : '' }}" id="waAudience" role="tabpanel">
                    @include('vendor-views.whatsapp.partials._bulk_audience')
                </div>

                <div class="tab-pane fade {{ $tab === 'history' ? 'show active' : '' }}" id="waHistory" role="tabpanel">
                    @include('vendor-views.whatsapp.partials._bulk_history')
                </div>
            </div>
        @endif
    </div>
@endsection

@if ($connected && !empty($templates))
@push('script_2')
    @include('vendor-views.whatsapp.partials._bulk_js')
@endpush
@endif

@push('script_2')
    <script>
        // Keep the open tab in the URL so a reload or a shared link comes back to the same pane.
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var map = { '#waCompose': 'compose', '#waAudience': 'audience', '#waHistory': 'history' };
            var tab = map[$(e.target).attr('href')];
            if (tab && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            }
        });
    </script>
@endpush
