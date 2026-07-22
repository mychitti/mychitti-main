{{-- WhatsApp receiving add-ons (Lead Notifications).
     Shared by every module's lead_settings view so the option appears identically for
     service, hospital, laundry and POS stores. Fed by ServiceController@lead_settings:
     $waFeatures (WhatsAppService::receivingFeatureStatus) and $walletBalance.
     Its own <form>s, so it must live OUTSIDE the settings form — nesting forms is invalid. --}}
<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="tio-chat"></i> WhatsApp Lead Notifications</h5>
        <span class="badge badge-soft-info">Wallet: {{ _price($walletBalance ?? 0) }}</span>
    </div>
    <div class="card-body">
        <p class="text-muted" style="font-size:13px;max-width:650px;">
            Get notified on WhatsApp when MyChitti sends you new business. Each add-on is billed
            monthly from your wallet and delivered to your registered phone number.
        </p>
        @foreach (($waFeatures ?? []) as $key => $f)
            <div class="border rounded p-3 mb-2" style="max-width:650px;">
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
