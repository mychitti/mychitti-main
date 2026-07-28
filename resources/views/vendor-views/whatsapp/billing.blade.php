@extends('layouts.vendor.app')

@section('title', 'WhatsApp Plan & Billing')

@push('css_or_js')
    <style>
        .wb-card { border:1px solid #eef0f4; border-radius:14px; background:#fff; height:100%; overflow:hidden; margin-bottom:16px; }
        .wb-card-h { padding:16px 20px; border-bottom:1px solid #f1f3f7; font-weight:700; font-size:14px; color:#1e293b; }
        .wb-card-b { padding:20px; }
        .wb-price { font-size:28px; font-weight:800; color:#1e293b; line-height:1.1; }
        .wb-sub { font-size:12px; color:#8a94a6; }
        .wb-row { display:flex; justify-content:space-between; align-items:center; font-size:13px; padding:9px 0; border-bottom:1px dashed #eef0f4; }
        .wb-row:last-child { border-bottom:0; }
        .wb-chip { font-size:11px; padding:3px 10px; border-radius:20px; font-weight:600; }
        .wb-bar { height:8px; border-radius:6px; background:#f1f3f7; overflow:hidden; }
        .wb-bar > span { display:block; height:100%; background:#25d366; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <h1 class="page-header-title mb-0"><i class="tio-wallet"></i> WhatsApp Plan & Billing</h1>
            <div class="d-flex align-items-center" style="gap:10px;">
                <span class="wb-sub">Wallet balance: <b>{{ _price($walletBalance) }}</b></span>
                @if ($active)
                    <span class="wb-chip badge-soft-success">Active</span>
                @elseif ($subscription && $subscription->status === 'past_due')
                    <span class="wb-chip badge-soft-danger">Payment due</span>
                @elseif ($subscription && $subscription->status === 'cancelled')
                    <span class="wb-chip badge-soft-secondary">Cancelled</span>
                @else
                    <span class="wb-chip badge-soft-secondary">Not active</span>
                @endif
            </div>
        </div>

        @if ($subscription && $subscription->status === 'past_due')
            <div class="alert alert-danger" style="font-size:13px;">
                <b>Last renewal could not be charged.</b> {{ $subscription->last_error }}
                Recharge your wallet — we retry every day, and WhatsApp keeps working for
                {{ $graceDays }} days after {{ $subscription->current_period_end }}.
            </div>
        @endif

        <div class="row">
            {{-- ── Subscription ── --}}
            <div class="col-lg-7">
                <div class="wb-card">
                    <div class="wb-card-h">WhatsApp Business Platform</div>
                    <div class="wb-card-b">
                        <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap:16px;">
                            <div>
                                <div class="wb-price">{{ _price($pricing['monthly_total']) }}<span class="wb-sub"> / month</span></div>
                                <div class="wb-sub">{{ _price($pricing['monthly']) }} + {{ $pricing['gst'] }}% GST, billed from your wallet</div>
                            </div>
                            @if (!$active)
                                <form action="{{ route('vendor.whatsapp.billing.subscribe') }}" method="post" class="mb-0">
                                    @csrf
                                    <label class="d-block wb-sub mb-2">
                                        <input type="checkbox" name="account_manager" value="1">
                                        Add dedicated account manager ({{ _price($pricing['manager_total']) }}/month)
                                    </label>
                                    <button type="submit" class="btn btn--primary">
                                        {{ $subscription && $subscription->setup_fee_paid ? 'Reactivate' : 'Activate' }} WhatsApp
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="mt-3">
                            <div class="wb-row">
                                <span>One-time setup fee</span>
                                <span>
                                    @if ($subscription && $subscription->setup_fee_paid)
                                        <span class="badge badge-soft-success">Paid</span>
                                    @else
                                        <b>{{ _price($pricing['setup_total']) }}</b>
                                        <span class="wb-sub">({{ _price($pricing['setup']) }} + GST)</span>
                                    @endif
                                </span>
                            </div>
                            <div class="wb-row">
                                <span>Message templates included</span>
                                <span><b>{{ $included }}</b> <span class="wb-sub">(you have {{ $allowance }} slots)</span></span>
                            </div>
                            <div class="wb-row">
                                <span>Dedicated account manager</span>
                                <span>
                                    @if ($subscription && $subscription->status !== 'cancelled')
                                        <form action="{{ route('vendor.whatsapp.billing.account-manager') }}" method="post" class="d-inline mb-0">
                                            @csrf
                                            <input type="hidden" name="enable" value="{{ $subscription->account_manager ? 0 : 1 }}">
                                            <button type="submit" class="btn btn-sm {{ $subscription->account_manager ? 'btn-outline-danger' : 'btn-outline-primary' }}">
                                                {{ $subscription->account_manager ? 'Remove' : 'Add for ' . _price($pricing['manager_total']) . '/mo' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="wb-sub">{{ _price($pricing['manager_total']) }} / month</span>
                                    @endif
                                </span>
                            </div>
                            @if ($subscription && $subscription->current_period_end)
                                <div class="wb-row">
                                    <span>{{ $subscription->status === 'cancelled' ? 'Access ends on' : 'Next renewal' }}</span>
                                    <b>{{ \Carbon\Carbon::parse($subscription->current_period_end)->format('d M Y') }}</b>
                                </div>
                            @endif
                        </div>

                        @if ($subscription && $subscription->status !== 'cancelled' && $subscription->status !== 'templates_only')
                            <form action="{{ route('vendor.whatsapp.billing.cancel') }}" method="post" class="mt-3 mb-0"
                                  onsubmit="return confirm('Stop auto-renewal? WhatsApp stays active until the end of the period you have paid for.');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">Stop auto-renewal</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── Chatbot tokens (knowledge-base bot) ── --}}
            <div class="col-lg-5">
                <div class="wb-card">
                    <div class="wb-card-h">Chatbot Tokens <span class="wb-sub">— normal chatbot</span></div>
                    <div class="wb-card-b">
                        <div class="wb-price">{{ number_format($tokens['balance']) }}<span class="wb-sub"> tokens left</span></div>
                        <div class="wb-sub mb-2">{{ number_format($tokens['this_month']) }} used this month</div>
                        @php
                            $chatPct = $tokens['topup'] > 0 ? min(100, round($tokens['topup_used'] / $tokens['topup'] * 100)) : 100;
                        @endphp
                        <div class="wb-bar mb-3"><span style="width:{{ 100 - $chatPct }}%"></span></div>

                        <div class="wb-row">
                            <span>1M tokens</span>
                            <b>{{ _price($pricing['token_pack_total']) }}</b>
                        </div>
                        <div class="wb-sub mb-3">
                            ${{ $pricing['token_pack_usd'] }} per 1M tokens, converted at ₹{{ $pricing['usd_rate'] }}/USD, + {{ $pricing['gst'] }}% GST.
                        </div>

                        <form action="{{ route('vendor.whatsapp.billing.tokens') }}" method="post" class="d-flex mb-0" style="gap:8px;">
                            @csrf
                            <input type="number" name="packs" class="form-control" style="max-width:110px;" value="1" min="1" max="50">
                            <button type="submit" class="btn btn--primary flex-grow-1">Buy tokens (× 1M)</button>
                        </form>
                        <small class="wb-sub d-block mt-2">
                            Spent when the knowledge-base chatbot answers a customer. At zero, auto-replies
                            stop and the chat is handed to you instead.
                        </small>
                    </div>
                </div>

                <div class="wb-card">
                    <div class="wb-card-h">Extra Message Templates</div>
                    <div class="wb-card-b">
                        <div class="wb-sub mb-2">
                            {{ $included }} templates are included with your plan. Each extra template slot is a
                            one-time {{ _price($pricing['template_total']) }}
                            ({{ _price($pricing['template_slot']) }} + {{ $pricing['gst'] }}% GST).
                        </div>
                        <div class="wb-row">
                            <span>Your template slots</span>
                            <b>{{ $allowance }}</b>
                        </div>
                        <form action="{{ route('vendor.whatsapp.billing.template-slot') }}" method="post" class="mt-3 mb-0">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-block">
                                Add 1 template slot — {{ _price($pricing['template_total']) }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Message usage this month ── --}}
        <div class="wb-card">
            <div class="wb-card-h">
                Message Usage — {{ now()->format('F Y') }}
                <span class="wb-sub float-right">billed at the start of next month</span>
            </div>
            <div class="wb-card-b">
                <div class="row">
                    <div class="col-md-4">
                        <div class="wb-price">{{ number_format($usage['total']) }}<span class="wb-sub"> messages sent</span></div>
                        <div class="wb-sub">Counted when sent, including failed deliveries.</div>
                    </div>
                    <div class="col-md-8">
                        <div class="wb-row">
                            <span>To your own contact list — {{ _price($feeOwn) }} each</span>
                            <span>{{ number_format($usage['own']) }} × = <b>{{ _price($usage['own_amount']) }}</b></span>
                        </div>
                        <div class="wb-row">
                            <span>To the MyChitti customer database — {{ _price($feePlatform) }} each</span>
                            <span>{{ number_format($usage['platform']) }} × = <b>{{ _price($usage['platform_amount']) }}</b></span>
                        </div>
                        <div class="wb-row">
                            <span><b>Running total</b> <span class="wb-sub">+ {{ $pricing['gst'] }}% GST</span></span>
                            <b>{{ _price(\App\Services\WhatsAppBilling::withTax($usage['amount'])) }}</b>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── AI Agent ── --}}
        <div class="row">
            <div class="col-lg-7">
                <div class="wb-card">
                    <div class="wb-card-h">
                        AI Agent <span class="wb-sub">— lead &amp; appointment management</span>
                        @if ($agentActive)
                            <span class="wb-chip badge-soft-success float-right">Active</span>
                        @elseif ($agentSub && $agentSub->status === 'past_due')
                            <span class="wb-chip badge-soft-danger float-right">Payment due</span>
                        @endif
                    </div>
                    <div class="wb-card-b">
                        <p class="wb-sub">
                            Books and reschedules appointments, answers "what's the status of my request",
                            and shares only what you allow on the
                            <a href="{{ route('vendor.whatsapp.bot') }}">Chatbot</a> page.
                            Tokens reset every cycle; unused plan tokens do not carry over.
                        </p>

                        <div class="row">
                            @foreach ($agentPlans as $key => $plan)
                                @php $current = $agentSub && $agentSub->plan === $key && $agentActive; @endphp
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 h-100" style="{{ $current ? 'border-color:#25d366 !important;background:#f6fffa;' : '' }}">
                                        <div class="wb-price" style="font-size:22px;">
                                            {{ _price(\App\Services\WhatsAppBilling::withTax($plan['price'])) }}<span class="wb-sub"> / month</span>
                                        </div>
                                        <div class="wb-sub mb-2">
                                            {{ $plan['label'] }} — {{ _price($plan['price']) }} + {{ $pricing['gst'] }}% GST<br>
                                            <b>{{ number_format($plan['tokens']) }}</b> tokens per month
                                        </div>
                                        @if ($current)
                                            <span class="badge badge-soft-success">Your plan</span>
                                        @else
                                            <form action="{{ route('vendor.whatsapp.billing.agent.subscribe') }}" method="post" class="mb-0">
                                                @csrf
                                                <input type="hidden" name="plan" value="{{ $key }}">
                                                <button type="submit" class="btn btn-sm btn--primary btn-block">
                                                    {{ $agentSub && $agentSub->status !== 'cancelled' ? 'Switch to this plan' : 'Choose this plan' }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($agentSub && $agentSub->status !== 'cancelled')
                            <div class="wb-row">
                                <span>Next renewal</span>
                                <b>{{ \Carbon\Carbon::parse($agentSub->current_period_end)->format('d M Y') }}</b>
                            </div>
                            <form action="{{ route('vendor.whatsapp.billing.agent.cancel') }}" method="post" class="mt-3 mb-0"
                                  onsubmit="return confirm('Stop the AI Agent auto-renewal? It stays active until the end of the paid period, then the normal chatbot takes over.');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">Stop AI Agent auto-renewal</button>
                            </form>
                        @elseif (!$active)
                            <div class="wb-sub">Activate the WhatsApp Business Platform above before adding the AI Agent.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="wb-card">
                    <div class="wb-card-h">AI Agent Tokens</div>
                    <div class="wb-card-b">
                        <div class="wb-price">{{ number_format($agentTokens['balance']) }}<span class="wb-sub"> tokens left</span></div>
                        <div class="wb-sub mb-2">{{ number_format($agentTokens['this_month']) }} used this month</div>
                        @php
                            $granted = $agentTokens['plan'] + $agentTokens['topup'];
                            $spent   = $agentTokens['plan_used'] + $agentTokens['topup_used'];
                            $agentPct = $granted > 0 ? min(100, round($spent / $granted * 100)) : 100;
                        @endphp
                        <div class="wb-bar mb-3"><span style="width:{{ 100 - $agentPct }}%"></span></div>

                        <div class="wb-row">
                            <span>This cycle's plan tokens</span>
                            <b>{{ number_format(max(0, $agentTokens['plan'] - $agentTokens['plan_used'])) }} left</b>
                        </div>
                        <div class="wb-row">
                            <span>Top-up tokens (carry over)</span>
                            <b>{{ number_format(max(0, $agentTokens['topup'] - $agentTokens['topup_used'])) }} left</b>
                        </div>
                        <div class="wb-row">
                            <span>Extra tokens</span>
                            <b>{{ _price($agentTopup) }} / million</b>
                        </div>

                        <form action="{{ route('vendor.whatsapp.billing.agent.tokens') }}" method="post" class="d-flex mt-3 mb-0" style="gap:8px;">
                            @csrf
                            <input type="number" name="millions" class="form-control" style="max-width:110px;" value="1" min="1" max="50">
                            <button type="submit" class="btn btn--primary flex-grow-1">Top up (× 1M)</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Charge history ── --}}
        <div class="wb-card">
            <div class="wb-card-h">Billing History</div>
            <div class="wb-card-b">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Period</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">GST</th>
                                <th class="text-right">Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $inv)
                                <tr>
                                    <td class="text-nowrap">{{ \Carbon\Carbon::parse($inv->created_at)->format('d M Y') }}</td>
                                    <td>{{ $inv->description }}</td>
                                    <td class="text-nowrap wb-sub">
                                        @if ($inv->period_start)
                                            {{ \Carbon\Carbon::parse($inv->period_start)->format('d M') }} –
                                            {{ \Carbon\Carbon::parse($inv->period_end)->format('d M Y') }}
                                        @else
                                            One-time
                                        @endif
                                    </td>
                                    <td class="text-right">{{ _price($inv->amount) }}</td>
                                    <td class="text-right">{{ _price($inv->tax) }}</td>
                                    <td class="text-right"><b>{{ _price($inv->total) }}</b></td>
                                    <td>
                                        @if ($inv->status === 'paid')
                                            <span class="badge badge-soft-success">Paid</span>
                                        @else
                                            <span class="badge badge-soft-danger" title="{{ $inv->note }}">Failed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No WhatsApp charges yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
