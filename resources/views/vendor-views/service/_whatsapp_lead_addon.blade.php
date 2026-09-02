{{-- WhatsApp receiving add-ons (Lead Notifications).
     Shared by every module's lead_settings view so the option appears identically for
     service, hospital, laundry and POS stores. Fed by ServiceController@lead_settings:
     $waFeatures (WhatsAppService::receivingFeatureStatus) and $walletBalance.
     Its own <form>s, so it must live OUTSIDE the settings form — nesting forms is invalid. --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="tio-chat"></i> WhatsApp Lead Notifications</h5>
        <span class="badge badge-soft-info">Wallet: {{ _price($walletBalance ?? 0) }}</span>
    </div>
    <div class="card-body">
        <p class="text-muted" style="font-size:13px;">
            Get notified on WhatsApp when MyChitti sends you new business. Each add-on is billed
            monthly from your wallet and delivered to your registered phone number.
        </p>
        @foreach (($waFeatures ?? []) as $key => $f)
            <div class="border rounded p-3 mb-2" style="">
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
                        @if (hasPermission('whatsapp_billing', 'pay'))
                        <form method="post" action="{{ route('vendor.whatsapp.features.toggle') }}">
                            @csrf
                            <input type="hidden" name="feature" value="{{ $key }}">
                            <button class="btn btn-sm {{ $f['enabled'] ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                {{ $f['enabled'] ? 'Pause' : 'Resume' }}
                            </button>
                        </form>
                        @endif
                        @if (hasPermission('whatsapp_billing', 'pay'))
                        <form method="post" action="{{ route('vendor.whatsapp.features.subscribe') }}">
                            @csrf
                            <input type="hidden" name="feature" value="{{ $key }}">
                            <button class="btn btn-sm btn-outline-primary">Renew (+1 month)</button>
                        </form>
                        @endif
                    </div>
                @else
                    @if ($f['subscribed'])
                        <div class="text-muted mt-2" style="font-size:12px;">
                            Expired on <b>{{ $f['active_until'] }}</b> — you are not receiving these alerts.
                        </div>
                    @endif
                    @if (hasPermission('whatsapp_billing', 'pay'))
                    <form method="post" action="{{ route('vendor.whatsapp.features.subscribe') }}" class="mt-2"
                          onsubmit="return confirm('Subscribe to {{ $f['meta']['label'] }} for {{ _price($f['meta']['price']) }} from your wallet?');">
                        @csrf
                        <input type="hidden" name="feature" value="{{ $key }}">
                        <button class="btn btn-sm btn--primary">
                            {{ $f['subscribed'] ? 'Reactivate' : 'Subscribe' }} — {{ _price($f['meta']['price']) }}/mo
                        </button>
                    </form>
                    @endif
                @endif

                {{-- Monthly wallet deduction. Shown for any store that has ever subscribed,
                     expired included, so auto-renew can be switched off without buying a month
                     first. Separate from Pause above: that mutes alerts inside a paid period,
                     this decides whether the wallet is charged again at all. --}}
                @if ($f['subscribed'])
                    <div class="border-top mt-2 pt-2 d-flex justify-content-between align-items-center" style="gap:8px;">
                        <div style="font-size:12px;">
                            <b>Auto-renew</b>
                            <div class="text-muted">
                                @if ($f['auto_renew'])
                                    {{ _price($f['meta']['price']) }} is deducted from your wallet on
                                    <b>{{ $f['renews_on'] }}</b>. We message you on WhatsApp if your wallet is short.
                                @else
                                    Off — your wallet will not be charged, and this add-on stops on
                                    <b>{{ $f['active_until'] }}</b>.
                                @endif
                            </div>
                        </div>
                        @if (hasPermission('whatsapp_billing', 'pay'))
                        <form method="post" action="{{ route('vendor.whatsapp.features.auto-renew') }}"
                              onsubmit="return confirm('{{ $f['auto_renew']
                                    ? 'Turn off auto-renew? This add-on will stop when the current month ends.'
                                    : 'Turn on auto-renew? ' . _price($f['meta']['price']) . ' will be deducted from your wallet each month.' }}');">
                            @csrf
                            <input type="hidden" name="feature" value="{{ $key }}">
                            <button class="btn btn-sm {{ $f['auto_renew'] ? 'btn-outline-secondary' : 'btn-outline-success' }}"
                                    style="white-space:nowrap;">
                                {{ $f['auto_renew'] ? 'Turn off' : 'Turn on' }}
                            </button>
                        </form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach

        {{-- What the add-on does BESIDES notifying you, spelled out and chooseable.
             It is sold as "tell me on WhatsApp when a lead arrives", and it also accepted every
             lead — spending the lead charge from the wallet — and quoted the customer off a figure
             saved in settings, with nobody looking. Three named behaviours instead, because a
             store that only wants the alert should not have to pause the add-on to get it.
             Shown whether or not the add-on is active: worth setting before it starts. --}}
        @php
            $leadAutoAccept  = lead_auto_accept_enabled();
            $leadAutoConfirm = lead_auto_confirm_enabled();
            $leadMode = !$leadAutoAccept ? 'notify' : ($leadAutoConfirm ? 'accept_quote' : 'accept');
            $leadModes = [
                'accept_quote' => [
                    'title' => 'Accept and quote automatically',
                    'desc'  => 'The lead is accepted the moment it arrives and the customer is sent a confirmation '
                             . 'request at your saved visiting charge (' . _price($storeConfig->lead_visiting_charge ?? 0) . ').',
                ],
                'accept' => [
                    'title' => 'Accept automatically, quote by hand',
                    'desc'  => 'The lead is accepted for you, the customer is told nothing about price, and you send '
                             . 'the quote from the lead card when you are ready.',
                ],
                'notify' => [
                    'title' => 'Just notify me',
                    'desc'  => 'Nothing is accepted and nothing is charged to your wallet. The lead arrives as New and '
                             . 'waits for you to accept it — you still get the WhatsApp alert this add-on is for.',
                ],
            ];
        @endphp
        <div class="border rounded p-3 mt-3" style="background:#f8fafc;">
            <b style="font-size:13px;">When a new lead arrives</b>
            <form method="post" action="{{ route('vendor.service.lead-auto-confirm') }}" class="mt-2 mb-0">
                @csrf
                @foreach($leadModes as $modeKey => $mode)
                    <label class="d-flex align-items-start mb-2" style="cursor:pointer; gap:8px;">
                        <input type="radio" name="mode" value="{{ $modeKey }}" class="mt-1"
                               {{ $leadMode === $modeKey ? 'checked' : '' }}>
                        <span>
                            <span style="font-weight:600; font-size:12.5px;">{{ $mode['title'] }}</span>
                            <small class="text-muted d-block" style="font-size:11.5px;">{{ $mode['desc'] }}</small>
                        </span>
                    </label>
                @endforeach
                <button class="btn btn-sm btn-outline-primary">Save</button>
            </form>
        </div>
    </div>
</div>
