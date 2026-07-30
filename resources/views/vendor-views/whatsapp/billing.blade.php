@extends('layouts.vendor.app')

@section('title', 'WhatsApp Plan & Billing')

@push('css_or_js')
    <style>
        .wb {
            --wb-green:#25d366; --wb-green-d:#128c7e; --wb-ink:#0f172a; --wb-ink-2:#334155;
            --wb-mute:#7c8798; --wb-line:#e9edf3; --wb-soft:#f6f8fb; --wb-radius:16px;
        }
        .wb-card { border:1px solid var(--wb-line); border-radius:var(--wb-radius); background:#fff;
                   margin-bottom:20px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
        .wb-card-h { padding:16px 22px; border-bottom:1px solid var(--wb-line); display:flex;
                     align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px; }
        .wb-card-t { font-weight:700; font-size:14.5px; color:var(--wb-ink); margin:0; }
        .wb-card-b { padding:22px; }
        .wb-sub { font-size:12px; color:var(--wb-mute); line-height:1.55; }
        .wb-price { font-size:30px; font-weight:800; color:var(--wb-ink); line-height:1.05; letter-spacing:-.02em; }

        /* ── summary tiles ────────────────────────────────────────────── */
        .wb-tiles { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:1px;
                    background:var(--wb-line); border-radius:var(--wb-radius); overflow:hidden;
                    border:1px solid var(--wb-line); margin-bottom:20px; }
        .wb-tile { background:#fff; padding:18px 20px; }
        .wb-tile-k { font-size:11px; font-weight:600; letter-spacing:.06em; text-transform:uppercase;
                     color:var(--wb-mute); margin-bottom:7px; }
        .wb-tile-v { font-size:19px; font-weight:750; color:var(--wb-ink); line-height:1.2; }
        .wb-tile-n { font-size:11.5px; color:var(--wb-mute); margin-top:4px; }

        .wb-chip { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; padding:4px 11px;
                   border-radius:999px; font-weight:650; line-height:1.4; }
        .wb-chip i { font-size:12px; }
        .wb-chip-ok { background:#e7f9ef; color:#0f7a43; }
        .wb-chip-no { background:#fdeaea; color:#b42318; }
        .wb-chip-off { background:#eef1f6; color:#5b6675; }
        .wb-chip-warn { background:#fff4e5; color:#a35b00; }

        /* ── plan cards ──────────────────────────────────────────────── */
        .wb-plan { position:relative; border:1.5px solid var(--wb-line); border-radius:14px;
                   padding:22px 20px 20px; height:100%; display:flex; flex-direction:column;
                   background:#fff; transition:border-color .15s, box-shadow .15s, transform .15s; }
        .wb-plan:hover { border-color:#cbd5e1; box-shadow:0 6px 20px rgba(15,23,42,.07); transform:translateY(-2px); }
        .wb-plan.is-current { border-color:var(--wb-green); background:linear-gradient(180deg,#f4fdf8 0%,#fff 46%); }
        .wb-plan.is-current:hover { transform:none; box-shadow:none; }
        .wb-plan-tag { position:absolute; top:-11px; left:50%; transform:translateX(-50%); font-size:10.5px;
                       font-weight:700; letter-spacing:.05em; text-transform:uppercase; padding:4px 12px;
                       border-radius:999px; white-space:nowrap; }
        .wb-plan-tag.cur { background:var(--wb-green); color:#fff; }
        .wb-plan-tag.pop { background:var(--wb-ink); color:#fff; }
        .wb-plan-name { font-size:14px; font-weight:750; color:var(--wb-ink); }
        .wb-plan-amt { font-size:29px; font-weight:800; color:var(--wb-ink); letter-spacing:-.02em; line-height:1.1; margin-top:10px; }
        .wb-plan-amt small { font-size:13px; font-weight:600; color:var(--wb-mute); letter-spacing:0; }
        .wb-feat { list-style:none; padding:0; margin:16px 0 0; flex-grow:1; }
        .wb-feat li { display:flex; gap:9px; font-size:12.5px; color:var(--wb-ink-2); padding:5px 0; line-height:1.5; }
        .wb-feat i { color:var(--wb-green); font-size:14px; flex-shrink:0; margin-top:1px; }
        .wb-feat li.off { color:var(--wb-mute); }
        .wb-feat li.off i { color:#cbd5e1; }

        /* ── rows & meters ───────────────────────────────────────────── */
        .wb-row { display:flex; justify-content:space-between; align-items:center; gap:14px;
                  font-size:13px; color:var(--wb-ink-2); padding:11px 0; border-bottom:1px solid #f2f5f9; }
        .wb-row:last-child { border-bottom:0; }
        .wb-row b { color:var(--wb-ink); font-weight:650; }

        .wb-meter { background:var(--wb-soft); border:1px solid var(--wb-line); border-radius:12px; padding:16px; }
        .wb-meter + .wb-meter { margin-top:12px; }
        .wb-meter-top { display:flex; justify-content:space-between; align-items:baseline; gap:10px; margin-bottom:8px; }
        .wb-meter-n { font-size:12.5px; font-weight:700; color:var(--wb-ink); }
        .wb-meter-v { font-size:22px; font-weight:800; color:var(--wb-ink); line-height:1.1; letter-spacing:-.02em; }
        .wb-meter-v small { font-size:12px; font-weight:600; color:var(--wb-mute); letter-spacing:0; }
        .wb-bar { height:7px; border-radius:99px; background:#e4e9f0; overflow:hidden; margin:10px 0 4px; }
        .wb-bar > span { display:block; height:100%; border-radius:99px;
                         background:linear-gradient(90deg,var(--wb-green-d),var(--wb-green)); transition:width .3s; }
        .wb-bar.low > span { background:linear-gradient(90deg,#dc6803,#f79009); }
        .wb-bar.out > span { background:#d92d20; }
        .wb-split { display:flex; gap:16px; font-size:11.5px; color:var(--wb-mute); }
        .wb-split b { color:var(--wb-ink-2); font-weight:650; }

        .wb-buy { display:flex; gap:8px; margin-top:12px; }
        .wb-buy .form-control { max-width:82px; height:38px; font-size:13px; text-align:center; }
        .wb-buy .btn { height:38px; font-size:12.5px; font-weight:650; padding:0 14px; flex-grow:1; white-space:nowrap; }

        .wb-note { font-size:11.5px; color:var(--wb-mute); line-height:1.6; background:var(--wb-soft);
                   border-radius:10px; padding:12px 14px; margin-top:16px; }

        /* onboarding gate — nothing on this page works until the setup fee lands */
        .wb-gate { display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start; padding:22px;
                   border:1.5px solid #fec84b; border-radius:var(--wb-radius); margin-bottom:20px;
                   background:linear-gradient(135deg,#fffaeb,#fff); }
        .wb-gate-i { width:44px; height:44px; border-radius:12px; flex-shrink:0; display:flex;
                     align-items:center; justify-content:center; font-size:21px;
                     background:#fef0c7; color:#b54708; }
        .wb-gate-t { font-weight:750; font-size:15px; color:var(--wb-ink); }
        .wb-gate-l { list-style:none; padding:0; margin:12px 0 0; }
        .wb-gate-l li { display:flex; gap:9px; font-size:12.5px; color:var(--wb-ink-2);
                        padding:4px 0; line-height:1.5; }
        .wb-gate-l i { color:#d92d20; font-size:13px; flex-shrink:0; margin-top:2px; }

        /* plan card with no CTA yet — onboarding is unpaid */
        .wb-soon { display:flex; align-items:center; justify-content:center; gap:7px;
                   border:1px dashed var(--wb-line); border-radius:9px; padding:11px;
                   font-size:12px; font-weight:600; color:var(--wb-mute); }

        /* a card whose actions cannot work yet */
        .wb-locked { position:relative; }
        .wb-locked .wb-card-b > *:not(.wb-lock) { opacity:.4; pointer-events:none; user-select:none; }
        .wb-lock { display:flex; gap:11px; align-items:flex-start; background:#fffaeb;
                   border:1px solid #fec84b; border-radius:10px; padding:12px 14px;
                   font-size:12px; color:#7a4a06; line-height:1.6; margin-bottom:16px; }
        .wb-lock i { font-size:15px; flex-shrink:0; margin-top:1px; }

        .wb-empty { text-align:center; padding:34px 16px; color:var(--wb-mute); }
        .wb-empty i { font-size:30px; color:#cbd5e1; display:block; margin-bottom:10px; }

        .wb-tbl thead th { font-size:11px; letter-spacing:.05em; text-transform:uppercase;
                           color:var(--wb-mute); font-weight:650; border-top:0; }
        .wb-tbl td { font-size:13px; color:var(--wb-ink-2); vertical-align:middle; }
        .wb-ico { width:30px; height:30px; border-radius:8px; display:inline-flex; align-items:center;
                  justify-content:center; font-size:14px; flex-shrink:0; }
        .wb-inv { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:650;
                  color:var(--wb-green-d); white-space:nowrap; }
        .wb-inv:hover { color:var(--wb-green); text-decoration:underline; }

        @media (max-width:575px) {
            .wb-card-b { padding:18px 16px; }
            .wb-card-h { padding:14px 16px; }
            .wb-price, .wb-plan-amt { font-size:25px; }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid wb">

        @php
            $setupPaid = $subscription && $subscription->setup_fee_paid;
            $mandate   = $subscription->mandate_status ?? null;
            // Two separate checkouts, in order: the one-off onboarding fee (its only button lives
            // in the gate box), then the monthly mandate the vendor authorises on Razorpay. Plan
            // buttons only exist once the first is done, so this route is always the second.
            $planAction = route('vendor.whatsapp.billing.authorize-mandate');

            // Messages are charged as they are sent now, so the balance is an operational number,
            // not just an accounting one — say how far it actually goes.
            $perMsg   = \App\Services\WhatsAppBilling::withTax($feeOwn);
            $msgsLeft = $perMsg > 0 ? (int) floor($walletBalance / $perMsg) : 0;
            $lowFunds = $active && $msgsLeft < 500;
        @endphp

        <div class="page-header d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:12px;">
            <div>
                <h1 class="page-header-title mb-1"><i class="tio-wallet"></i> WhatsApp Plan &amp; Billing</h1>
                <span class="wb-sub">Your plan, AI tokens, message charges and every invoice in one place.</span>
            </div>
            <a href="{{ route('vendor.whatsapp.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-chart-bar-4"></i> WhatsApp dashboard
            </a>
        </div>

        {{-- ── At a glance ───────────────────────────────────────────── --}}
        <div class="wb-tiles">
            <div class="wb-tile">
                <div class="wb-tile-k">Current plan</div>
                @if ($hasPlan)
                    <div class="wb-tile-v">{{ $planMeta['label'] }}</div>
                    <div class="wb-tile-n">{{ _price($planMeta['price']) }} + {{ $pricing['gst'] }}% GST / month</div>
                @else
                    <div class="wb-tile-v" style="color:var(--wb-mute);">None yet</div>
                    <div class="wb-tile-n">Pick one below to get started</div>
                @endif
            </div>
            <div class="wb-tile">
                <div class="wb-tile-k">Status</div>
                <div class="wb-tile-v">
                    @if ($active)
                        <span class="wb-chip wb-chip-ok"><i class="tio-checkmark-circle"></i> Active</span>
                    @elseif ($subscription && $subscription->status === 'past_due')
                        <span class="wb-chip wb-chip-no"><i class="tio-warning"></i> Payment due</span>
                    @elseif ($subscription && $subscription->status === 'cancelled')
                        <span class="wb-chip wb-chip-off"><i class="tio-remove-circle"></i> Cancelled</span>
                    @elseif ($mandate === 'pending')
                        <span class="wb-chip wb-chip-warn"><i class="tio-time"></i> Awaiting authorisation</span>
                    @else
                        <span class="wb-chip wb-chip-off"><i class="tio-remove-circle"></i> Not active</span>
                    @endif
                </div>
                <div class="wb-tile-n">
                    @if ($subscription && $subscription->status === 'cancelled')
                        No further renewals
                    @elseif ($active)
                        Auto-debit via Razorpay
                    @else
                        Choose a plan to start
                    @endif
                </div>
            </div>
            <div class="wb-tile">
                <div class="wb-tile-k">{{ $subscription && $subscription->status === 'cancelled' ? 'Access ends' : 'Next renewal' }}</div>
                <div class="wb-tile-v">
                    {{ $subscription && $subscription->current_period_end
                        ? \Carbon\Carbon::parse($subscription->current_period_end)->format('d M Y')
                        : '—' }}
                </div>
                <div class="wb-tile-n">
                    @if ($subscription && $subscription->current_period_end && $subscription->status !== 'cancelled')
                        {{ $graceDays }}-day grace if a payment fails
                    @else
                        Nothing scheduled
                    @endif
                </div>
            </div>
            <div class="wb-tile">
                <div class="wb-tile-k">Wallet balance</div>
                <div class="wb-tile-v" style="{{ $lowFunds ? 'color:#b42318;' : '' }}">{{ _price($walletBalance) }}</div>
                <div class="wb-tile-n">
                    @if ($active)
                        ≈ {{ number_format($msgsLeft) }} more messages to your customers
                    @else
                        Message charges come out of here
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Alerts ────────────────────────────────────────────────── --}}
        @if ($subscription && in_array($mandate, ['pending', 'halted'], true) && $subscription->last_error)
            <div class="alert alert-danger d-flex" style="font-size:13px;gap:10px;border-radius:12px;">
                <i class="tio-warning" style="font-size:18px;"></i>
                <span>
                    <b>Monthly auto-debit could not be collected.</b> {{ $subscription->last_error }}
                    @if ($subscription->current_period_end)
                        WhatsApp keeps working for {{ $graceDays }} days after
                        {{ \Carbon\Carbon::parse($subscription->current_period_end)->format('d M Y') }}.
                    @endif
                </span>
            </div>
        @elseif ($subscription && $subscription->status === 'past_due')
            <div class="alert alert-danger d-flex" style="font-size:13px;gap:10px;border-radius:12px;">
                <i class="tio-warning" style="font-size:18px;"></i>
                <span>
                    <b>Last renewal could not be charged.</b> {{ $subscription->last_error }}
                    Recharge your wallet — we retry every day, and WhatsApp keeps working for
                    {{ $graceDays }} days after
                    {{ \Carbon\Carbon::parse($subscription->current_period_end)->format('d M Y') }}.
                </span>
            </div>
        @elseif ($setupPaid && !$active)
            <div class="alert alert-warning d-flex" style="font-size:13px;gap:10px;border-radius:12px;">
                <i class="tio-time" style="font-size:18px;"></i>
                <span>
                    <b>Step 1 done — onboarding fee received.</b>
                    Now pick your plan below and authorise the monthly auto-debit. WhatsApp switches on the
                    moment Razorpay collects that first month; nothing monthly has been charged yet.
                </span>
            </div>
        @endif

        @if ($lowFunds)
            <div class="alert alert-warning d-flex" style="font-size:13px;gap:10px;border-radius:12px;">
                <i class="tio-wallet-outlined" style="font-size:18px;"></i>
                <span>
                    <b>Your wallet is running low.</b> Messages are charged as they are sent, and sending
                    stops when the balance cannot cover the next one. Recharge to keep your campaigns running.
                </span>
            </div>
        @endif

        {{-- ── Nothing works until onboarding is paid — say so once, loudly, at the top ── --}}
        @if (!$setupPaid)
            <div class="wb-gate">
                <div class="wb-gate-i"><i class="tio-lock-outlined"></i></div>
                <div style="flex:1 1 300px;">
                    <div class="wb-gate-t">Pay the one-time onboarding fee to unlock WhatsApp</div>
                    <div class="wb-sub mt-1">
                        Until it is paid, <b>none of this is live</b> — you cannot connect your number, so
                        plans, message templates and AI tokens all stay switched off:
                    </div>
                    <ul class="wb-gate-l">
                        <li><i class="tio-clear-circle"></i> <b>Plans</b> — no plan can start, so nothing renews and no tokens are granted</li>
                        <li><i class="tio-clear-circle"></i> <b>Message templates</b> — extra slots cannot be used until a number is connected</li>
                        <li><i class="tio-clear-circle"></i> <b>AI tokens</b> — top-ups need a live AI Agent plan, which needs this first</li>
                        <li><i class="tio-clear-circle"></i> <b>Sending</b> — campaigns, replies and auto-replies are all blocked</li>
                    </ul>
                    <div class="wb-sub mt-2">
                        It takes two steps: this fee is collected first, then you authorise the monthly
                        auto-debit for whichever plan you pick. <b>Your plan does not start, and no monthly
                        amount is charged, until that second step.</b>
                    </div>
                </div>
                <div class="text-right" style="flex:0 0 auto;">
                    {{-- Base price leads, GST-inclusive total follows — the same order the plan
                         cards use, so the two are read the same way. --}}
                    <div class="wb-price" style="font-size:28px;">
                        {{ _price($pricing['setup']) }}<small class="wb-sub" style="font-size:13px;font-weight:600;"> one time</small>
                    </div>
                    <div class="wb-sub mt-1">
                        + {{ $pricing['gst'] }}% GST — <b style="color:var(--wb-ink-2);">{{ _price($pricing['setup_total']) }}</b> to pay
                    </div>
                    <form action="{{ route('vendor.whatsapp.billing.subscribe') }}" method="post" class="mb-0 mt-3">
                        @csrf
                        <button type="submit" class="btn btn--primary" style="font-weight:650;font-size:13px;">
                            <i class="tio-shopping-cart"></i> Pay {{ _price($pricing['setup_total']) }} now
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- ── Plans ─────────────────────────────────────────────────── --}}
        <div class="wb-card">
            <div class="wb-card-h">
                <div>
                    <h2 class="wb-card-t">Choose your plan</h2>
                    <span class="wb-sub">One plan at a time — moving up replaces your monthly fee, it never adds to it.</span>
                </div>
            </div>
            <div class="wb-card-b">
                <div class="row">
                    @foreach ($plans as $key => $plan)
                        @php
                            $isCurrent = $active && $key === $currentPlan;
                            $isPopular = $key === 'starter' && !$isCurrent;

                            $features = [
                                ['on' => true, 'text' => 'Bulk campaigns to your own customers'],
                                ['on' => true, 'text' => $included . ' message templates included'],
                                ['on' => true, 'text' => 'Shared team inbox'],
                            ];
                            if ($plan['bot']) {
                                $features[] = ['on' => true, 'text' => 'Knowledge-base chatbot answers customers'];
                                $features[] = ['on' => true, 'text' => 'AI Agent books, reschedules &amp; reports status'];
                                $features[] = ['on' => true, 'text' => '<b>' . number_format($plan['tokens_in']) . '</b> input + <b>'
                                    . number_format($plan['tokens_out']) . '</b> output tokens each month'];
                            } else {
                                $features[] = ['on' => false, 'text' => 'No chatbot — replies go to your team'];
                                $features[] = ['on' => false, 'text' => 'No AI Agent'];
                            }
                        @endphp
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="wb-plan {{ $isCurrent ? 'is-current' : '' }}">
                                @if ($isCurrent)
                                    <span class="wb-plan-tag cur">Your plan</span>
                                @elseif ($isPopular)
                                    <span class="wb-plan-tag pop">Most popular</span>
                                @endif

                                <div class="wb-plan-name">{{ $plan['label'] }}</div>
                                <div class="wb-plan-amt">
                                    {{ _price($plan['price']) }}<small> / month</small>
                                </div>
                                <div class="wb-sub mt-1">
                                    {{ _price(\App\Services\WhatsAppBilling::withTax($plan['price'])) }} with
                                    {{ $pricing['gst'] }}% GST, auto-debited
                                </div>

                                <ul class="wb-feat">
                                    @foreach ($features as $f)
                                        <li class="{{ $f['on'] ? '' : 'off' }}">
                                            <i class="{{ $f['on'] ? 'tio-checkmark-circle' : 'tio-remove-circle' }}"></i>
                                            <span>{!! $f['text'] !!}</span>
                                        </li>
                                    @endforeach
                                </ul>

                                @if ($isCurrent)
                                    <button type="button" class="btn btn-block mt-3" disabled
                                            style="background:#e7f9ef;color:#0f7a43;font-weight:650;font-size:13px;border:0;">
                                        <i class="tio-checkmark-circle"></i> Currently active
                                    </button>
                                @elseif (!$setupPaid)
                                    {{-- No plan can be started before onboarding is paid, so there is no button
                                         to offer — the single call to action lives in the gate box above. --}}
                                    <div class="wb-soon mt-3">
                                        <i class="tio-lock-outlined"></i> Available after the onboarding fee
                                    </div>
                                @else
                                    <form action="{{ $planAction }}" method="post"
                                          class="mb-0 mt-3 wb-mandate-form"
                                          data-label="WhatsApp {{ $plan['label'] }}">
                                        @csrf
                                        <input type="hidden" name="plan" value="{{ $key }}">
                                        <input type="hidden" name="account_manager"
                                               value="{{ $subscription && $subscription->account_manager ? 1 : 0 }}">
                                        <button type="submit" class="btn btn-block btn--primary"
                                                style="font-weight:650;font-size:13px;">
                                            @if ($active)
                                                {{ $plan['price'] > ($planMeta['price'] ?? 0) ? 'Upgrade to this plan' : 'Move to this plan' }}
                                            @elseif ($mandate === 'pending')
                                                Finish auto-debit setup
                                            @else
                                                Start {{ $plan['label'] }}
                                            @endif
                                        </button>
                                        <div class="wb-mandate-status wb-sub mt-2" style="display:none;"></div>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="wb-note mb-0">
                    @if (!$setupPaid)
                        These are what you will be able to choose from. Pay the
                        <b>one-time onboarding fee of {{ _price($pricing['setup_total']) }}</b> above first —
                        no plan can start until it lands, and no monthly amount is charged before you pick one.
                    @elseif ($active)
                        Switching charges the new plan in full and re-grants its token allowance straight away.
                        There is no part-month credit for the plan you are leaving.
                    @else
                        Your setup fee is already paid. Choosing a plan only authorises the monthly auto-debit.
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            {{-- ── Subscription detail ───────────────────────────────── --}}
            <div class="col-xl-7">
                <div class="wb-card">
                    <div class="wb-card-h"><h2 class="wb-card-t">Subscription details</h2></div>
                    <div class="wb-card-b">
                        <div class="wb-row">
                            <span>Current plan</span>
                            <span>
                                @if ($hasPlan)
                                    <b>{{ $planMeta['label'] }}</b>
                                    <span class="wb-sub">{{ _price($planMeta['price']) }} + {{ $pricing['gst'] }}% GST / month</span>
                                @else
                                    <span class="wb-sub">No plan started yet</span>
                                @endif
                            </span>
                        </div>
                        <div class="wb-row">
                            <span>One-time setup fee</span>
                            <span>
                                @if ($setupPaid)
                                    <span class="wb-chip wb-chip-ok"><i class="tio-checkmark-circle"></i> Paid</span>
                                @else
                                    <b>{{ _price($pricing['setup_total']) }}</b>
                                    <span class="wb-sub">({{ _price($pricing['setup']) }} + GST)</span>
                                @endif
                            </span>
                        </div>
                        <div class="wb-row">
                            <span>Message templates</span>
                            <span><b>{{ $allowance }}</b> <span class="wb-sub">slots ({{ $included }} included)</span></span>
                        </div>
                        <div class="wb-row">
                            <span>
                                Dedicated account manager
                                <span class="wb-sub d-block">{{ _price($pricing['manager_total']) }} / month, added from your next renewal</span>
                            </span>
                            <span>
                                @if ($subscription && $subscription->status !== 'cancelled')
                                    <form action="{{ route('vendor.whatsapp.billing.account-manager') }}" method="post" class="d-inline mb-0">
                                        @csrf
                                        <input type="hidden" name="enable" value="{{ $subscription->account_manager ? 0 : 1 }}">
                                        <button type="submit" class="btn btn-sm {{ $subscription->account_manager ? 'btn-outline-danger' : 'btn-outline-primary' }}">
                                            {{ $subscription->account_manager ? 'Remove' : 'Add' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="wb-sub">Not available</span>
                                @endif
                            </span>
                        </div>
                        @if ($subscription && $subscription->current_period_end)
                            <div class="wb-row">
                                <span>{{ $subscription->status === 'cancelled' ? 'Access ends on' : 'Next renewal' }}</span>
                                <b>{{ \Carbon\Carbon::parse($subscription->current_period_end)->format('d M Y') }}</b>
                            </div>
                        @endif

                        @if ($subscription && $subscription->status !== 'cancelled' && $subscription->status !== 'templates_only')
                            <form action="{{ route('vendor.whatsapp.billing.cancel') }}" method="post" class="mt-3 mb-0"
                                  onsubmit="return confirm('Stop auto-renewal? WhatsApp stays active until the end of the period you have paid for.');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="tio-clear-circle"></i> Stop auto-renewal
                                </button>
                                <span class="wb-sub d-block mt-2">
                                    You keep everything until {{ $subscription->current_period_end
                                        ? \Carbon\Carbon::parse($subscription->current_period_end)->format('d M Y') : 'the end of the paid period' }}.
                                </span>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- ── Extra templates ───────────────────────────────── --}}
                <div class="wb-card {{ $setupPaid ? '' : 'wb-locked' }}">
                    <div class="wb-card-h">
                        <h2 class="wb-card-t">Extra message templates</h2>
                        <span class="wb-chip wb-chip-off">{{ $allowance }} slots</span>
                    </div>
                    <div class="wb-card-b">
                        @if (!$setupPaid)
                            <div class="wb-lock">
                                <i class="tio-lock-outlined"></i>
                                <span>
                                    <b>Locked until onboarding is paid.</b> Templates live on your own connected
                                    WhatsApp number, and you cannot connect one until the fee is settled — so extra
                                    slots would have nothing to attach to.
                                </span>
                            </div>
                        @endif
                        <p class="wb-sub mb-3">
                            {{ $included }} templates come with your plan. Each extra slot is a one-time
                            <b>{{ _price($pricing['template_total']) }}</b>
                            ({{ _price($pricing['template_slot']) }} + {{ $pricing['gst'] }}% GST), yours to keep.
                        </p>
                        <form action="{{ route('vendor.whatsapp.billing.template-slot') }}" method="post"
                              class="wb-buy mb-0" id="wb-tpl-form"
                              data-unit="{{ $pricing['template_total'] }}"
                              data-currency="{{ \App\CentralLogics\Helpers::currency_symbol() }}">
                            @csrf
                            <input type="number" name="slots" id="wb-tpl-slots" class="form-control"
                                   value="1" min="1" max="50">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="tio-add-circle"></i>
                                Add <span id="wb-tpl-count">1</span> slot<span id="wb-tpl-plural"></span>
                                — <span id="wb-tpl-total">{{ _price($pricing['template_total']) }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ── AI tokens ─────────────────────────────────────────── --}}
            <div class="col-xl-5">
                <div class="wb-card">
                    <div class="wb-card-h">
                        <h2 class="wb-card-t">AI tokens</h2>
                        @if ($planMeta['bot'])
                            <span class="wb-sub">resets every cycle</span>
                        @endif
                    </div>
                    <div class="wb-card-b">
                        @if (!$hasPlan)
                            <div class="wb-empty">
                                <i class="tio-lock-outlined"></i>
                                <div style="font-weight:650;color:var(--wb-ink-2);font-size:13px;">
                                    No plan yet, so no tokens
                                </div>
                                <div class="wb-sub mt-2" style="max-width:320px;margin:0 auto;">
                                    AI tokens come with <b>AI Agent Starter</b> or <b>Pro</b>, and top-ups can only
                                    be bought once one of those is running. Choose a plan above to get started.
                                </div>
                            </div>
                        @elseif (!$planMeta['bot'])
                            <div class="wb-empty">
                                <i class="tio-android"></i>
                                <div style="font-weight:650;color:var(--wb-ink-2);font-size:13px;">
                                    {{ $planMeta['label'] }} has no chatbot
                                </div>
                                <div class="wb-sub mt-2" style="max-width:320px;margin:0 auto;">
                                    Incoming messages go straight to your team in the
                                    <a href="{{ route('vendor.whatsapp.inbox') }}">Inbox</a>. Move to
                                    <b>AI Agent Starter</b> or <b>Pro</b> above to switch a chatbot on.
                                </div>
                            </div>
                        @else
                            @foreach ([
                                'in'  => ['label' => 'Input tokens',  'note' => 'the prompt, thread history and the customer’s message', 'rate' => $pricing['topup_in_total']],
                                'out' => ['label' => 'Output tokens', 'note' => 'the reply the model writes back', 'rate' => $pricing['topup_out_total']],
                            ] as $dir => $meta)
                                @php
                                    $t       = $tokens[$dir];
                                    $granted = $t['plan'] + $t['topup'];
                                    $spent   = $t['plan_used'] + $t['topup_used'];
                                    $left    = $granted > 0 ? max(0, 100 - round($spent / $granted * 100)) : 0;
                                    $state   = $t['balance'] <= 0 ? 'out' : ($left <= 20 ? 'low' : '');
                                @endphp
                                <div class="wb-meter">
                                    <div class="wb-meter-top">
                                        <span class="wb-meter-n">{{ $meta['label'] }}</span>
                                        <span class="wb-sub">{{ number_format($t['this_month']) }} used this month</span>
                                    </div>
                                    <div class="wb-meter-v">{{ number_format($t['balance']) }}<small> left</small></div>
                                    <div class="wb-sub">{{ $meta['note'] }}</div>

                                    <div class="wb-bar {{ $state }}"><span style="width:{{ $left }}%"></span></div>
                                    <div class="wb-split">
                                        <span>Plan <b>{{ number_format(max(0, $t['plan'] - $t['plan_used'])) }}</b></span>
                                        <span>Top-up <b>{{ number_format(max(0, $t['topup'] - $t['topup_used'])) }}</b></span>
                                    </div>

                                    <form action="{{ route('vendor.whatsapp.billing.tokens') }}" method="post" class="wb-buy mb-0">
                                        @csrf
                                        <input type="hidden" name="direction" value="{{ $dir }}">
                                        <input type="number" name="millions" class="form-control" value="1" min="1" max="50">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="tio-add-circle"></i> Top up × 1M — {{ _price($meta['rate']) }}
                                        </button>
                                    </form>
                                </div>
                            @endforeach

                            <div class="wb-note">
                                Input and output are separate buckets and never borrow from each other — either one
                                hitting zero stops auto-replies and hands the chat to your team. Plan tokens reset
                                each cycle; top-ups you buy carry over.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Message usage ─────────────────────────────────────────── --}}
        <div class="wb-card">
            <div class="wb-card-h">
                <div>
                    <h2 class="wb-card-t">Message usage — {{ now()->format('F Y') }}</h2>
                    <span class="wb-sub">Already paid — every message is charged to your wallet the moment it is sent.</span>
                </div>
                <span class="wb-chip wb-chip-ok"><i class="tio-checkmark-circle"></i> Settled</span>
            </div>
            <div class="wb-card-b">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="wb-price">{{ number_format($usage['total']) }}</div>
                        <div class="wb-sub" style="margin-top:2px;">messages sent this month</div>
                        <div class="wb-sub mt-3">
                            Counted at dispatch, including failed deliveries — the message leaves the platform
                            either way. Sending stops when your wallet cannot cover the next one.
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="wb-row">
                            <span>
                                To your own customers
                                <span class="wb-sub d-block">{{ _price($feeOwn) }} each</span>
                            </span>
                            <span class="text-right">
                                <b>{{ _price($usage['own_amount']) }}</b>
                                <span class="wb-sub d-block">{{ number_format($usage['own']) }} messages</span>
                            </span>
                        </div>
                        <div class="wb-row">
                            <span>
                                To MyChitti customers
                                <span class="wb-sub d-block">{{ _price($feePlatform) }} each</span>
                            </span>
                            <span class="text-right">
                                <b>{{ _price($usage['platform_amount']) }}</b>
                                <span class="wb-sub d-block">{{ number_format($usage['platform']) }} messages</span>
                            </span>
                        </div>
                        <div class="wb-row" style="border-top:2px solid var(--wb-line);margin-top:4px;padding-top:14px;">
                            <span><b>Charged so far this month</b> <span class="wb-sub">incl. {{ $pricing['gst'] }}% GST</span></span>
                            <span class="wb-price" style="font-size:20px;">
                                {{ _price(\App\Services\WhatsAppBilling::withTax($usage['amount'])) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Billing history ───────────────────────────────────────── --}}
        <div class="wb-card">
            <div class="wb-card-h">
                <h2 class="wb-card-t">Billing history</h2>
                <span class="wb-sub">Last {{ count($invoices) }} charges</span>
            </div>
            <div class="wb-card-b" style="padding-top:8px;">
                <div class="table-responsive">
                    <table class="table table-borderless table-align-middle wb-tbl mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Invoice</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">GST</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $inv)
                                @php
                                    $ico = match ($inv->type) {
                                        'monthly'         => ['tio-repeat', '#e8f0fe', '#1a56db'],
                                        'setup'           => ['tio-rocket', '#f3e8ff', '#7e22ce'],
                                        'usage'           => ['tio-chat', '#e7f9ef', '#0f7a43'],
                                        'template_slot'   => ['tio-receipt-outlined', '#fff4e5', '#a35b00'],
                                        default           => ['tio-flash', '#eef1f6', '#5b6675'],
                                    };
                                @endphp
                                <tr>
                                    <td class="text-nowrap">
                                        {{ \Carbon\Carbon::parse($inv->created_at)->format('d M Y') }}
                                        @if ($inv->period_start)
                                            <span class="wb-sub d-block">
                                                {{ \Carbon\Carbon::parse($inv->period_start)->format('d M') }} –
                                                {{ \Carbon\Carbon::parse($inv->period_end)->format('d M Y') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="d-flex align-items-center" style="gap:10px;">
                                            <span class="wb-ico" style="background:{{ $ico[1] }};color:{{ $ico[2] }};">
                                                <i class="{{ $ico[0] }}"></i>
                                            </span>
                                            <span>{{ $inv->description }}</span>
                                        </span>
                                    </td>
                                    <td class="text-nowrap">
                                        @if (!empty($inv->tax_invoice_no))
                                            @if (!empty($inv->tax_invoice_pdf))
                                                <a class="wb-inv"
                                                   href="{{ asset('storage/app/public/invoice') . '/' . $inv->tax_invoice_pdf }}"
                                                   target="_blank" rel="noopener"
                                                   title="Open the GST invoice">
                                                    <i class="tio-download-to"></i> {{ $inv->tax_invoice_no }}
                                                </a>
                                            @else
                                                <span class="wb-sub">{{ $inv->tax_invoice_no }}</span>
                                            @endif
                                        @else
                                            {{-- Wallet-funded charges carry no invoice of their own: the GST
                                                 document was issued when the wallet was recharged. --}}
                                            <span class="wb-sub" title="Covered by your wallet recharge invoice">—</span>
                                        @endif
                                    </td>
                                    <td class="text-right text-nowrap">{{ _price($inv->amount) }}</td>
                                    <td class="text-right text-nowrap wb-sub">{{ _price($inv->tax) }}</td>
                                    <td class="text-right text-nowrap"><b>{{ _price($inv->total) }}</b></td>
                                    <td class="text-right">
                                        @if ($inv->status === 'paid')
                                            <span class="wb-chip wb-chip-ok">Paid</span>
                                        @else
                                            <span class="wb-chip wb-chip-no" title="{{ $inv->note }}">Failed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="wb-empty">
                                            <i class="tio-receipt-outlined"></i>
                                            <div style="font-weight:650;color:var(--wb-ink-2);font-size:13px;">No charges yet</div>
                                            <div class="wb-sub mt-1">Your invoices will appear here once your plan starts.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    {{-- Razorpay Checkout. The Subscriptions API rejects callback_url, so the hosted page has no
         way back to us; opening the mandate in this modal instead keeps the vendor on MyChitti
         and gives us a handler to return to. Falls back to the hosted page if the script is
         blocked, which is the behaviour we had before. --}}
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        (function () {
            var MERCHANT = '{{ addslashes(\App\Models\BusinessSetting::where('key', 'business_name')->value('value') ?: 'MyChitti') }}';
            var BACK = '{{ route('vendor.whatsapp.billing') }}?flag=success';
            var CSRF = '{{ csrf_token() }}';

            // Keep the template-slot button honest about what the vendor is about to pay. Mirrors
            // _price(): the currency symbol followed by number_format(x, 2).
            (function () {
                var $form = document.getElementById('wb-tpl-form');
                if (!$form) return;

                var $qty    = document.getElementById('wb-tpl-slots');
                var $count  = document.getElementById('wb-tpl-count');
                var $total  = document.getElementById('wb-tpl-total');
                var $plural = document.getElementById('wb-tpl-plural');
                var unit    = parseFloat($form.dataset.unit) || 0;
                var symbol  = $form.dataset.currency || '';

                function refresh() {
                    var n = Math.max(1, Math.min(50, parseInt($qty.value, 10) || 1));
                    $count.textContent = n;
                    $plural.textContent = n === 1 ? '' : 's';
                    $total.textContent = symbol + (unit * n).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                $qty.addEventListener('input', refresh);
                $qty.addEventListener('change', refresh);
                refresh();
            })();

            // Every plan button on this page goes through the same modal. They differ only in
            // which tier the form carries.
            Array.prototype.forEach.call(document.querySelectorAll('.wb-mandate-form'), function ($form) {
                var $status = $form.querySelector('.wb-mandate-status');
                var $button = $form.querySelector('button[type="submit"]');
                var label   = $button ? $button.innerHTML : '';

                function status(msg, kind) {
                    if (!$status) return;
                    $status.style.display = msg ? 'block' : 'none';
                    $status.className = 'wb-mandate-status mt-2 ' + (kind === 'error' ? 'text-danger' : 'wb-sub');
                    $status.textContent = msg || '';
                }

                function busy(on) {
                    $button.disabled = on;
                    $button.innerHTML = on
                        ? '<span class="spinner-border spinner-border-sm mr-1"></span> Preparing…'
                        : label;
                }

                $form.addEventListener('submit', function (e) {
                    if (typeof Razorpay === 'undefined') return;   // let the plain post through
                    e.preventDefault();

                    // Whatever the form carries — the plan and account_manager — goes to the
                    // endpoint as-is.
                    var body = {};
                    Array.prototype.forEach.call($form.querySelectorAll('input[name]'), function (input) {
                        if (input.name === '_token') return;
                        body[input.name] = input.type === 'checkbox' ? (input.checked ? 1 : 0) : input.value;
                    });

                    busy(true);
                    status('Setting up your auto-debit…');

                    fetch($form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(body)
                    })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        busy(false);
                        if (!res.ok || !res.d.success) {
                            status(res.d.message || 'Could not start the auto-debit.', 'error');
                            return;
                        }
                        // No key means the gateway isn't readable from here — the hosted page still works.
                        if (!res.d.key) {
                            window.location.href = res.d.url;
                            return;
                        }

                        status('');
                        new Razorpay({
                            key: res.d.key,
                            subscription_id: res.d.subscription_id,
                            name: MERCHANT,
                            description: ($form.dataset.label || 'Subscription') + ' — monthly',
                            handler: function () {
                                // Authorised. The subscription.activated / .charged webhooks are what
                                // actually move our records, so just come back and let the page read them.
                                window.location.href = BACK;
                            },
                            modal: {
                                ondismiss: function () {
                                    status('Auto-debit setup was cancelled — nothing was charged.', 'error');
                                }
                            },
                            theme: { color: '#25d366' }
                        }).open();
                    })
                    .catch(function () {
                        busy(false);
                        status('Network error while starting the auto-debit.', 'error');
                    });
                });
            });
        })();
    </script>
@endpush
