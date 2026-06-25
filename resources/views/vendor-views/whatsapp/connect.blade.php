@extends('layouts.vendor.app')

@section('title', 'Connect WhatsApp')

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title"><i class="tio-chat"></i> Connect WhatsApp</h1>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        @if ($store && $store->wa_enabled && $store->wa_phone_number_id)
                            <div class="alert alert-success">
                                <b>✓ Connected.</b> Your WhatsApp number is linked and messages will send from your own number.
                            </div>
                            <table class="table table-sm">
                                <tr><td class="text-muted">Phone Number ID</td><td><b>{{ $store->wa_phone_number_id }}</b></td></tr>
                                <tr><td class="text-muted">Business Account ID</td><td>{{ $store->wa_business_account_id }}</td></tr>
                            </table>
                            <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-outline-primary btn-sm mb-2"><i class="tio-receipt"></i> Manage Message Templates</a>
                            <form method="post" action="{{ route('vendor.whatsapp.disconnect') }}" onsubmit="return confirm('Disconnect WhatsApp? Messages will fall back to the platform number.')">
                                @csrf
                                <button class="btn btn-outline-danger btn-sm">Disconnect</button>
                            </form>
                        @else
                            <p>Connect your business WhatsApp number to send invoices, reminders and updates to your customers directly from your own number.</p>
                            @if (!$es['ready'])
                                <div class="alert alert-warning">WhatsApp onboarding isn’t available yet. Please contact support.</div>
                            @else
                                <button id="wa-connect-btn" class="btn btn-success btn-lg">
                                    <i class="tio-chat"></i> Connect WhatsApp
                                </button>
                                <div id="wa-status" class="mt-3 text-muted" style="display:none;"></div>
                            @endif
                            <hr>
                            <small class="text-muted d-block">
                                A secure Facebook window will guide you through connecting your number. You’ll need:
                                a phone number not currently active on the WhatsApp app, and access to receive its verification code.
                            </small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="tio-shopping-cart"></i> Message Receiving Add-ons</h5>
                        <span class="badge badge-soft-info">Wallet: {{ _price($walletBalance ?? 0) }}</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted" style="font-size:13px;">
                            Get notified on WhatsApp when MyChitti sends you new business. Each add-on is billed monthly from your wallet and delivered to your registered phone number.
                        </p>
                        @foreach (($features ?? []) as $key => $f)
                            <div class="border rounded p-3 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <b>{{ $f['meta']['label'] }}</b>
                                        <span class="text-muted">— {{ _price($f['meta']['price']) }}/mo</span>
                                        <div class="text-muted" style="font-size:12px;">{{ $f['meta']['desc'] }}</div>
                                    </div>
                                    <div class="text-right" style="min-width:90px;">
                                        @if ($f['live'])
                                            <span class="badge badge-soft-success">Active</span>
                                        @elseif ($f['paid_active'])
                                            <span class="badge badge-soft-warning">Paused</span>
                                        @else
                                            <span class="badge badge-soft-secondary">Inactive</span>
                                        @endif
                                    </div>
                                </div>

                                @if ($f['paid_active'])
                                    <div class="text-muted mt-2" style="font-size:12px;">Paid until <b>{{ $f['active_until'] }}</b></div>
                                    <div class="d-flex mt-2" style="gap:8px;">
                                        <form method="post" action="{{ route('vendor.whatsapp.features.toggle') }}">
                                            @csrf
                                            <input type="hidden" name="feature" value="{{ $key }}">
                                            <button class="btn btn-sm {{ $f['enabled'] ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                {{ $f['enabled'] ? 'Pause' : 'Resume' }}
                                            </button>
                                        </form>
                                        <form method="post" action="{{ route('vendor.whatsapp.features.subscribe') }}">
                                            @csrf
                                            <input type="hidden" name="feature" value="{{ $key }}">
                                            <button class="btn btn-sm btn-outline-primary">Renew (+1 month)</button>
                                        </form>
                                    </div>
                                @else
                                    <form method="post" action="{{ route('vendor.whatsapp.features.subscribe') }}" class="mt-2"
                                          onsubmit="return confirm('Subscribe to {{ $f['meta']['label'] }} for {{ _price($f['meta']['price']) }} from your wallet?');">
                                        @csrf
                                        <input type="hidden" name="feature" value="{{ $key }}">
                                        <button class="btn btn-sm btn--primary">Subscribe — {{ _price($f['meta']['price']) }}/mo</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@if (!($store && $store->wa_enabled) && $es['ready'])
@push('script_2')
    <script>
        window.fbAsyncInit = function () {
            FB.init({
                appId: '{{ $es['app_id'] }}',
                autoLogAppEvents: true,
                xfbml: true,
                version: '{{ $es['api_version'] }}'
            });
        };
    </script>
    <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>
    <script>
        var WA_SESSION = { phone_number_id: null, waba_id: null };

        // Embedded Signup posts the selected WABA + phone number id via window.postMessage.
        window.addEventListener('message', function (event) {
            if (event.origin !== 'https://www.facebook.com' && event.origin !== 'https://web.facebook.com') return;
            try {
                var data = JSON.parse(event.data);
                if (data.type === 'WA_EMBEDDED_SIGNUP' && data.event === 'FINISH') {
                    WA_SESSION.phone_number_id = data.data.phone_number_id;
                    WA_SESSION.waba_id = data.data.waba_id;
                }
            } catch (e) { /* not our message */ }
        });

        function waStatus(msg, kind) {
            var el = document.getElementById('wa-status');
            el.style.display = 'block';
            el.className = 'mt-3 ' + (kind === 'error' ? 'text-danger' : (kind === 'ok' ? 'text-success' : 'text-muted'));
            el.textContent = msg;
        }

        document.getElementById('wa-connect-btn').addEventListener('click', function () {
            if (typeof FB === 'undefined') { waStatus('Facebook SDK not loaded yet, please retry.', 'error'); return; }
            waStatus('Opening WhatsApp signup…');
            FB.login(function (response) {
                var code = response && response.authResponse && response.authResponse.code;
                if (!code) { waStatus('Signup cancelled or no code returned.', 'error'); return; }
                if (!WA_SESSION.phone_number_id || !WA_SESSION.waba_id) {
                    waStatus('Could not read the selected number. Please retry and complete all steps.', 'error');
                    return;
                }
                waStatus('Finalising connection…');
                fetch('{{ route('vendor.whatsapp.connect.finish') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        code: code,
                        phone_number_id: WA_SESSION.phone_number_id,
                        waba_id: WA_SESSION.waba_id
                    })
                })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.success) { waStatus('Connected! Reloading…', 'ok'); setTimeout(function () { location.reload(); }, 1200); }
                    else { waStatus(d.message || 'Connection failed.', 'error'); }
                })
                .catch(function () { waStatus('Network error while connecting.', 'error'); });
            }, {
                config_id: '{{ $es['config_id'] }}',
                response_type: 'code',
                override_default_response_type: true,
                extras: { setup: {}, sessionInfoVersion: '3' }
            });
        });
    </script>
@endpush
@endif
