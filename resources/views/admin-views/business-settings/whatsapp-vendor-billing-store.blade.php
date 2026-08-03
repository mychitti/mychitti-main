@extends('layouts.admin.app')

@section('title', translate('WhatsApp Billing') . ' — ' . $store->name)

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <i class="tio-shopping-cart" style="font-size:22px;"></i>
                </span>
                <span>{{ $store->name }}</span>
            </h1>
            <a href="{{ route('admin.business-settings.third-party.whatsapp-vendor-billing') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-back-ui"></i> {{ translate('All stores') }}
            </a>
        </div>

        {{-- What this store holds right now. --}}
        <div class="row g-2 mb-3">
            <div class="col-sm-6 col-lg-3 mb-2">
                <div class="card h-100">
                    <div class="card-body py-3">
                        <div class="text-muted" style="font-size:12px;">{{ translate('Plan') }}</div>
                        <div class="h4 mb-0">{{ $hasPlan ? $planMeta['label'] : translate('None') }}</div>
                        @if ($freeGrant === 'lifetime')
                            <div style="font-size:12px;" class="text-success">{{ translate('Free for life — never billed') }}</div>
                        @elseif ($subscription && $subscription->current_period_end)
                            <div style="font-size:12px;" class="{{ $active ? 'text-success' : 'text-danger' }}">
                                {{ $freeGrant === 'trial' ? translate('Free trial till') : ($active ? translate('Active till') : translate('Expired')) }}
                                {{ \Carbon\Carbon::parse($subscription->current_period_end)->format('d M Y') }}
                            </div>
                        @endif
                        @if ($subscription && $subscription->account_manager)
                            <span class="badge badge-soft-info mt-1">{{ translate('Account manager') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-2">
                <div class="card h-100">
                    <div class="card-body py-3">
                        <div class="text-muted" style="font-size:12px;">{{ translate('Onboarding fee') }}</div>
                        <div class="h4 mb-0">
                            @if ($setupPaid)
                                <span class="text-success">{{ translate('Settled') }}</span>
                            @else
                                <span class="text-danger">{{ translate('Due') }}</span>
                            @endif
                        </div>
                        <div style="font-size:12px;" class="text-muted">
                            {{ $store->wa_enabled ? translate('Number connected') : translate('Number not connected') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-2">
                <div class="card h-100">
                    <div class="card-body py-3">
                        <div class="text-muted" style="font-size:12px;">{{ translate('Message templates') }}</div>
                        <div class="h4 mb-0">{{ $allowance }}</div>
                        <div style="font-size:12px;" class="text-muted">
                            {{ $included }} {{ translate('included') }} +
                            {{ (int) ($subscription->extra_template_slots ?? 0) }} {{ translate('bought') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-2">
                <div class="card h-100">
                    <div class="card-body py-3">
                        <div class="text-muted" style="font-size:12px;">{{ translate('AI tokens left') }}</div>
                        <div class="h4 mb-0">{{ number_format($tokens['in']) }} <small class="text-muted">{{ translate('in') }}</small></div>
                        <div style="font-size:12px;" class="text-muted">
                            {{ number_format($tokens['out']) }} {{ translate('out') }} ·
                            {{ translate('Wallet') }} {{ _price($walletBalance) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($gatewayBilled)
            <div class="alert alert-warning py-2" style="font-size:13px;">
                <i class="tio-warning"></i>
                {{ translate('This store is billed by a Razorpay auto-debit mandate. Granting plan months here does not stop that mandate — cancel it first, or the vendor is charged twice.') }}
            </div>
        @endif

        <div class="row">
            {{-- 1. Plan --}}
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0">{{ translate('Enable / extend WhatsApp plan') }}</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('admin.business-settings.third-party.whatsapp-vendor-billing.plan', $store->id) }}">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-7 mb-2">
                                    <label class="input-label mb-1" style="font-size:12px;">{{ translate('Plan') }}</label>
                                    <select name="plan" class="form-control form-control-sm">
                                        @foreach ($plans as $key => $plan)
                                            <option value="{{ $key }}" {{ $currentPlan === $key && $hasPlan ? 'selected' : '' }}>
                                                {{ $plan['label'] }} — {{ _price($plan['price']) }}/{{ translate('mo') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-5 mb-2 js-wa-months">
                                    <label class="input-label mb-1" style="font-size:12px;">{{ translate('Months') }}</label>
                                    <input type="number" name="months" value="1" min="1" max="36" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="form-group mb-2">
                                <label class="input-label mb-1" style="font-size:12px;">{{ translate('Grant as') }}</label>
                                <select name="grant" class="form-control form-control-sm js-wa-grant">
                                    <option value="trial">{{ translate('Free trial — free for the months above, then lapses') }}</option>
                                    <option value="lifetime">{{ translate('Free lifetime — never expires, never billed') }}</option>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label class="input-label mb-1" style="font-size:12px;">{{ translate('Note') }} <small class="text-muted">({{ translate('optional') }})</small></label>
                                <input type="text" name="note" maxlength="190" class="form-control form-control-sm"
                                    placeholder="{{ translate('Why this vendor is getting it free, who approved it…') }}">
                            </div>
                            <div class="form-check mb-1">
                                <input type="checkbox" name="account_manager" value="1" class="form-check-input" id="wa-am"
                                    {{ $subscription && $subscription->account_manager ? 'checked' : '' }}>
                                <label class="form-check-label" for="wa-am" style="font-size:13px;">
                                    {{ translate('Dedicated account manager') }} (+{{ _price($pricing['manager']) }}/{{ translate('mo') }})
                                </label>
                            </div>
                            @if ($gatewayBilled)
                                <div class="form-check mb-1">
                                    <input type="checkbox" name="override_mandate" value="1" class="form-check-input" id="wa-override">
                                    <label class="form-check-label text-danger" for="wa-override" style="font-size:13px;">
                                        {{ translate('Override the active Razorpay mandate') }}
                                    </label>
                                </div>
                            @endif
                            <p class="text-muted mb-0" style="font-size:12px;">
                                {{ translate('Months are added to whatever is left, so extending never shortens a live plan. The token allowance is re-granted for the cycle.') }}
                            </p>

                            <div class="alert alert-info py-2 mt-3 mb-0" style="font-size:12px;">
                                <i class="tio-gift"></i>
                                {{ translate('Plans are granted free from here — no bill is raised and the vendor is never charged. The token allowance is re-granted every month for as long as the grant runs. To sell a plan, the vendor subscribes themselves under WhatsApp → Plan & Billing.') }}
                            </div>

                            <button class="btn btn-sm btn--primary mt-2">{{ $hasPlan ? translate('Extend / switch plan') : translate('Enable plan') }}</button>
                        </form>

                        @if ($hasPlan && $active)
                            <form method="post" action="{{ route('admin.business-settings.third-party.whatsapp-vendor-billing.cancel', $store->id) }}"
                                class="mt-2" onsubmit="return confirm('{{ translate('Stop auto-renewal? The paid period is honoured.') }}');">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger">{{ translate('Stop auto-renewal') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 2. Onboarding fee --}}
            @if ($offerSetupFee)
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0">{{ translate('Vendor onboarding fee') }}</h5>
                    </div>
                    <div class="card-body">
                        @if ($setupPaid)
                            <div class="alert alert-success py-2 mb-0" style="font-size:13px;">
                                <i class="tio-checkmark-circle"></i>
                                {{ translate('This store has already settled the one-time onboarding fee. It cannot be charged again by any route.') }}
                            </div>
                        @else
                            <form method="post" action="{{ route('admin.business-settings.third-party.whatsapp-vendor-billing.setup-fee', $store->id) }}">
                                @csrf
                                <p class="text-muted mb-0" style="font-size:12px;">
                                    {{ translate('The one-time platform onboarding fee. Settling it unlocks Embedded Signup so the vendor can connect their WhatsApp number. List price') }}
                                    {{ _price($pricing['setup']) }}.
                                </p>

                                @include('admin-views.business-settings.partials._whatsapp-admin-terms', [
                                    'prefix'    => 'setup',
                                    'listPrice' => $pricing['setup'],
                                    'methods'   => $methods,
                                    'gst'       => $pricing['gst'],
                                ])

                                <button class="btn btn-sm btn--primary mt-2">{{ translate('Record onboarding fee') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            {{-- 3. Template slots --}}
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0">{{ translate('Add-on message templates') }}</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('admin.business-settings.third-party.whatsapp-vendor-billing.template-slots', $store->id) }}">
                            @csrf
                            <div class="form-group mb-2" style="max-width:200px;">
                                <label class="input-label mb-1" style="font-size:12px;">{{ translate('Extra slots') }}</label>
                                <input type="number" name="slots" value="1" min="1" max="50" class="form-control form-control-sm">
                            </div>
                            <p class="text-muted mb-0" style="font-size:12px;">
                                {{ translate('Each slot lets the vendor create one more message template beyond the') }}
                                {{ $included }} {{ translate('included in the platform fee. List price') }}
                                {{ _price($pricing['template_slot']) }} {{ translate('per slot.') }}
                            </p>

                            @include('admin-views.business-settings.partials._whatsapp-admin-terms', [
                                'prefix'    => 'tpl',
                                'listPrice' => $pricing['template_slot'],
                                'methods'   => $methods,
                                'gst'       => $pricing['gst'],
                            ])

                            <button class="btn btn-sm btn--primary mt-2">{{ translate('Add template slots') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- 4. Platform fee waiver --}}
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ translate('Monthly platform fee') }}</h5>
                        @if ($waiverLive)
                            <span class="badge badge-soft-success">
                                {{ $waiver->platform_fee_waiver === 'lifetime'
                                    ? translate('Free for life')
                                    : translate('Free till') . ' ' . \Carbon\Carbon::parse($waiver->platform_fee_waiver_until)->format('d M Y') }}
                            </span>
                        @else
                            <span class="badge badge-soft-secondary">{{ translate('Charged monthly') }}</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if ($waiver && !$waiverLive)
                            <div class="alert alert-warning py-2" style="font-size:12px;">
                                {{ translate('The free trial ended on') }}
                                {{ \Carbon\Carbon::parse($waiver->platform_fee_waiver_until)->format('d M Y') }} —
                                {{ translate('the fee is being charged again.') }}
                            </div>
                        @endif

                        <form method="post" action="{{ route('admin.business-settings.third-party.whatsapp-vendor-billing.platform-fee', $store->id) }}">
                            @csrf
                            <input type="hidden" name="action" value="grant">
                            <div class="form-row">
                                <div class="form-group col-md-6 mb-2">
                                    <label class="input-label mb-1" style="font-size:12px;">{{ translate('Waive as') }}</label>
                                    <select name="type" class="form-control form-control-sm js-pf-type">
                                        <option value="trial">{{ translate('Free trial — until a date') }}</option>
                                        <option value="lifetime">{{ translate('Free lifetime') }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6 mb-2 js-pf-until">
                                    <label class="input-label mb-1" style="font-size:12px;">{{ translate('Free until') }}</label>
                                    <input type="date" name="until" class="form-control form-control-sm"
                                        value="{{ $waiver && $waiver->platform_fee_waiver_until ? $waiver->platform_fee_waiver_until : now()->addMonths(3)->toDateString() }}">
                                </div>
                                <div class="form-group col-12 mb-2">
                                    <label class="input-label mb-1" style="font-size:12px;">{{ translate('Note') }} <small class="text-muted">({{ translate('optional') }})</small></label>
                                    <input type="text" name="note" maxlength="190" class="form-control form-control-sm"
                                        value="{{ $waiver->platform_fee_waiver_note ?? '' }}"
                                        placeholder="{{ translate('Why this vendor is exempt, who approved it…') }}">
                                </div>
                            </div>
                            <p class="text-muted mb-0" style="font-size:12px;">
                                {{ translate('Skips the monthly platform fee taken from the vendor wallet on the 1st. A trial resumes charging by itself the month after it ends.') }}
                            </p>
                            <button class="btn btn-sm btn--primary mt-2">{{ $waiverLive ? translate('Update waiver') : translate('Waive platform fee') }}</button>
                        </form>

                        @if ($waiver)
                            <form method="post" action="{{ route('admin.business-settings.third-party.whatsapp-vendor-billing.platform-fee', $store->id) }}"
                                class="mt-2" onsubmit="return confirm('{{ translate('Remove the waiver and charge this vendor the monthly platform fee again?') }}');">
                                @csrf
                                <input type="hidden" name="action" value="revoke">
                                <button class="btn btn-sm btn-outline-danger">{{ translate('Remove waiver') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 5. Token top-up --}}
            @if ( $offerTokens)
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0">{{ translate('Add-on AI tokens') }}</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('admin.business-settings.third-party.whatsapp-vendor-billing.tokens', $store->id) }}">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-7 mb-2">
                                    <label class="input-label mb-1" style="font-size:12px;">{{ translate('Direction') }}</label>
                                    <select name="direction" class="form-control form-control-sm">
                                        <option value="in">{{ translate('Input tokens') }} — {{ _price($pricing['topup_in']) }}/M</option>
                                        <option value="out">{{ translate('Output tokens') }} — {{ _price($pricing['topup_out']) }}/M</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-5 mb-2">
                                    <label class="input-label mb-1" style="font-size:12px;">{{ translate('Millions') }}</label>
                                    <input type="number" name="millions" value="1" min="1" max="50" class="form-control form-control-sm">
                                </div>
                            </div>
                            <p class="text-muted mb-0" style="font-size:12px;">
                                {{ translate('Input and output are separate buckets and never lend to each other. Bought tokens carry over between cycles. Set the Amount to match the direction and quantity you picked.') }}
                            </p>

                            @include('admin-views.business-settings.partials._whatsapp-admin-terms', [
                                'prefix'    => 'tok',
                                'listPrice' => $pricing['topup_in'],
                                'methods'   => $methods,
                                'gst'       => $pricing['gst'],
                            ])

                            <button class="btn btn-sm btn--primary mt-2">{{ translate('Add tokens') }}</button>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Everything this store has been charged or granted. --}}
        <div class="card">
            <div class="card-header py-2">
                <h5 class="card-title mb-0">{{ translate('WhatsApp charge history') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-align-middle table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Date') }}</th>
                                <th>{{ translate('Description') }}</th>
                                <th class="text-right">{{ translate('Amount') }}</th>
                                <th class="text-right">{{ translate('Tax') }}</th>
                                <th class="text-right">{{ translate('Total') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Invoice') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($invoice->created_at)->format('d M Y') }}</td>
                                    <td>
                                        {{ $invoice->description }}
                                        @if ($invoice->note)
                                            <div class="text-muted" style="font-size:11px;">{{ $invoice->note }}</div>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ _price($invoice->amount) }}</td>
                                    <td class="text-right">{{ _price($invoice->tax) }}</td>
                                    <td class="text-right">{{ _price($invoice->total) }}</td>
                                    <td>
                                        @if ($invoice->status === 'paid')
                                            <span class="badge badge-soft-success">{{ translate('Paid') }}</span>
                                        @elseif ($invoice->status === 'unpaid')
                                            <span class="badge badge-soft-warning">{{ translate('Unpaid') }}</span>
                                        @else
                                            <span class="badge badge-soft-danger">{{ translate($invoice->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($invoice->tax_invoice_no)
                                            <span style="font-size:12px;">{{ $invoice->tax_invoice_no }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">{{ translate('Nothing charged to this store yet.') }}</td>
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
    <script>
        // Retail raises no invoice, so the payment method / status fields have nothing to say.
        (function () {
            var RETAIL_NOTE = @json(translate('(no GST — no bill)'));
            var BILL_NOTE = @json('(' . translate('excl.') . ' ' . $pricing['gst'] . '% ' . translate('GST') . ')');

            function sync(select) {
                var retail = select.value === 'retail';
                var prefix = select.dataset.prefix;
                document.querySelectorAll('.js-wa-billing-only[data-prefix="' + prefix + '"]').forEach(function (el) {
                    el.style.display = retail ? 'none' : '';
                });
                document.querySelectorAll('.js-wa-tax-note[data-prefix="' + prefix + '"]').forEach(function (el) {
                    el.textContent = retail ? RETAIL_NOTE : BILL_NOTE;
                });
            }

            document.querySelectorAll('.js-wa-mode').forEach(function (select) {
                sync(select);
                select.addEventListener('change', function () { sync(select); });
            });

            // A lifetime grant has no term to set.
            var grant = document.querySelector('.js-wa-grant');
            if (grant) {
                var syncGrant = function () {
                    document.querySelectorAll('.js-wa-months').forEach(function (el) {
                        el.style.display = grant.value === 'lifetime' ? 'none' : '';
                    });
                };
                syncGrant();
                grant.addEventListener('change', syncGrant);
            }

            // The trial end date only means anything for a dated waiver.
            var pfType = document.querySelector('.js-pf-type');
            if (pfType) {
                var syncPf = function () {
                    document.querySelectorAll('.js-pf-until').forEach(function (el) {
                        el.style.display = pfType.value === 'lifetime' ? 'none' : '';
                    });
                };
                syncPf();
                pfType.addEventListener('change', syncPf);
            }
        })();
    </script>
@endpush
