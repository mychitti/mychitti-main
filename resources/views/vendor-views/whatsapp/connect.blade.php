@extends('layouts.vendor.app')

@section('title', 'Connect WhatsApp')

@push('css_or_js')
    @include('vendor-views.whatsapp.partials._ui')
@endpush

@section('content')
<style>
.waba-payment-guide{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:24px;
    color:#374151;
    font-size:15px;
    line-height:1.7;
}

.waba-payment-guide__header{
    margin-bottom:20px;
}

.waba-payment-guide__header h3{
    margin:0 0 8px;
    font-size:22px;
    color:#111827;
}

.waba-payment-guide__header p{
    margin:0;
    color:#6b7280;
}

.waba-payment-guide__notice{
    background:#fff8e6;
    border-left:4px solid #f59e0b;
    padding:14px 16px;
    border-radius:6px;
    margin-bottom:24px;
}

.waba-payment-guide h4{
    margin:22px 0 12px;
    font-size:17px;
    color:#111827;
}

.waba-payment-guide__steps{
    padding-left:22px;
    margin:0;
}

.waba-payment-guide__steps li{
    margin-bottom:10px;
}

.waba-payment-guide__checklist{
    list-style:none;
    padding:0;
    margin:0;
}

.waba-payment-guide__checklist li{
    position:relative;
    padding-left:28px;
    margin-bottom:10px;
}

.waba-payment-guide__checklist li::before{
    content:"✔";
    position:absolute;
    left:0;
    top:0;
    color:#16a34a;
    font-weight:bold;
}

.waba-payment-guide__footer{
    margin-top:24px;
    padding:14px 16px;
    background:#f9fafb;
    border:1px solid #e5e7eb;
    border-radius:6px;
    color:#4b5563;
}

.waba-payment-guide a{
    color:#1877f2;
    text-decoration:underline;
}

.waba-payment-guide a:hover{
    color:#0f5bd7;
}

.waba-payment-guide a .tio-open-in-new{
    font-size:12px;
    margin-left:2px;
    vertical-align:baseline;
}

</style>
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <div>
                <h1 class="page-header-title mb-0"><i class="tio-chat"></i> Connection &amp; Bulk Message</h1>
                <span class="wa-sub">
                    {{ $connected ? 'Send a campaign to your customers, or manage your connected number.' : 'Link your business number to send from it.' }}
                </span>
            </div>
            @if ($connected)
                <span class="wa-chip badge-soft-success"><i class="tio-checkmark-circle"></i> Number connected</span>
            @endif
        </div>

        {{-- This page does three separate jobs — sending, managing the audience, and managing the
             connection. Tabs keep each one whole instead of stacking them into one long scroll.
             Before the number is linked there is only one job, so the tabs stay out of the way. --}}
        @if ($connected)
            <ul class="nav wa-tabs mb-3" role="tablist" style="background:#fff;border:1px solid var(--wa-line);border-radius:14px;">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#waCompose" role="tab">
                        <i class="tio-send"></i> Send a message
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#waAudience" role="tab">
                        <i class="tio-user-big-outlined"></i> Your customers
                        <span class="wa-chip badge-soft-secondary ml-1">{{ number_format($customerStats['with_phone']) }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#waConnection" role="tab">
                        <i class="tio-settings-outlined"></i> Connection
                    </a>
                </li>
            </ul>
        @endif

        <div class="tab-content">
            <div class="tab-pane fade {{ $connected ? '' : 'show active' }}" id="waConnection" role="tabpanel">
            <div class="row">
            <div class="col-lg-7">
                <div class="wa-card">
                    <div class="wa-card-b">
                        @if ($connected)
                            <div class="d-flex align-items-center mb-3" style="gap:12px;">
                                <div class="wa-stat-ico badge-soft-success"><i class="tio-checkmark-circle"></i></div>
                                <div>
                                    <div style="font-weight:700;font-size:14px;color:#1e293b;">Your number is linked</div>
                                    <div class="wa-sub">Everything you send to customers goes out under your own business name.</div>
                                </div>
                            </div>

                            <div class="wa-row">
                                <span class="text-muted">Phone Number ID</span>
                                <b>{{ $store->wa_phone_number_id }}</b>
                            </div>
                            <div class="wa-row">
                                <span class="text-muted">Business Account ID</span>
                                <b>{{ $store->wa_business_account_id }}</b>
                            </div>

                            <div class="d-flex flex-wrap align-items-center mt-3" style="gap:8px;">
                                <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="tio-receipt"></i> Message templates
                                </a>
                                <a href="{{ route('vendor.whatsapp.billing') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="tio-wallet"></i> Plan &amp; billing
                                </a>
                                <form method="post" action="{{ route('vendor.whatsapp.disconnect') }}" class="mb-0 ml-auto"
                                      onsubmit="return confirm('Disconnect WhatsApp? You will not be able to send anything to your customers until you connect a number again.')">
                                    @csrf
                                    <button class="btn btn-outline-danger btn-sm">Disconnect</button>
                                </form>
                            </div>
                        @else
                            <p>Connect your business WhatsApp number to send invoices, reminders and updates to your customers directly from your own number.</p>
                            @if (!$es['ready'])
                                <div class="alert alert-warning">WhatsApp onboarding isn’t available yet. Please contact support.</div>
                            @elseif (!$setupPaid)
                                {{-- Eligibility first, price second, payment last: a vendor who can't
                                     meet the requirements shouldn't reach the checkout and then ask
                                     for a refund. --}}
                                <div class="alert alert-warning" style="font-size:13px;">
                                    <b>Before you pay, make sure you have both:</b>
                                    <ul class="mb-0 mt-1 pl-3">
                                        <li>a phone number that is <b>not currently active on the WhatsApp app</b> —
                                            if it is, delete that WhatsApp account first or use a different number</li>
                                        <li>access to receive that number’s verification code <b>right now</b></li>
                                    </ul>
                                </div>

                                <div class="border rounded p-3 mb-3" style="background:#f8fafc;">
                                    <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;">
                                        One-time onboarding fee
                                    </div>
                                    <div style="font-size:28px;font-weight:800;color:#1e293b;line-height:1.2;">
                                        {{ _price($pricing['setup']) }}
                                    </div>
                                    <div class="text-muted" style="font-size:12px;">
                                        + {{ $pricing['gst'] }}% GST — {{ _price($pricing['setup_total']) }} charged once,
                                        by card / UPI. Not taken from your wallet.
                                    </div>

                                    <hr class="my-3">

                                    <div class="mb-2" style="font-size:13px;"><b>What the fee covers</b></div>
                                    <ul class="text-muted mb-3 pl-3" style="font-size:13px;">
                                        <li>Setting your business number up on the WhatsApp Business Platform (Meta Cloud API)</li>
                                        <li>Registering it and linking it to MyChitti</li>
                                        <li>Charged <b>once per store</b> — if you disconnect and reconnect later, you don’t pay it again</li>
                                    </ul>

                                    <div class="mb-2" style="font-size:13px;"><b>What happens after you pay</b></div>
                                    <ol class="text-muted mb-0 pl-3" style="font-size:13px;">
                                        <li>You come straight back to this page.</li>
                                        <li>A secure Facebook window walks you through linking your number — keep the
                                            verification code handy.</li>
                                        <li>You pick a monthly plan and authorise it under
                                            <b>WhatsApp → Plan &amp; Billing</b> — from
                                            {{ _price($pricing['monthly']) }} + {{ $pricing['gst'] }}% GST =
                                            {{ _price($pricing['monthly_total']) }}/month for Basic, with
                                            AI Agent tiers above it. Messages start sending once that first
                                            month is collected.</li>
                                    </ol>
                                </div>

                                <form action="{{ route('vendor.whatsapp.connect.setup-fee') }}" method="post" class="mb-0">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="tio-chat"></i> Pay &amp; Connect
                                    </button>
                                    <small class="text-muted d-block mt-2">
                                        You’ll be taken to Razorpay and charged {{ _price($pricing['setup_total']) }} now.
                                        The monthly plan is authorised separately — nothing recurring is set up by this step.
                                    </small>
                                </form>
                            @else
                                {{-- The fee is settled but the number isn't linked yet — say so, or
                                     the vendor has no way to tell the payment landed. --}}
                                <div class="d-flex align-items-start border rounded p-3 mb-3"
                                     style="background:#f0fdf4;border-color:#bbf7d0 !important;gap:10px;">
                                    <i class="tio-checkmark-circle" style="color:#16a34a;font-size:20px;line-height:1.2;"></i>
                                    <div style="font-size:13px;">
                                        <b class="d-block" style="color:#15803d;">One-time onboarding fee paid</b>
                                        <span class="text-muted">
                                            Charged once — you won’t be asked for it again, even if you disconnect and
                                            reconnect later. Last step is linking your number below; the monthly plan is
                                            authorised after that under <b>WhatsApp → Plan &amp; Billing</b>.
                                        </span>
                                    </div>
                                </div>
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

                {{-- The "test WhatsApp delivery" card used to live here. It sent from MyChitti's
                     platform number, which is ours and has no place in the vendor panel. The
                     route and sendTestMessage() are still there for admin-side use. --}}
            </div>

            <div class="col-lg-5">
                {{-- Meta's own billing setup. Long, rarely needed, and not something MyChitti can do
                     for them — collapsed so it stops dominating a page about sending messages. --}}
                <div class="wa-card">
                    <button class="wa-toggle" type="button" data-toggle="collapse" data-target="#waMetaPay"
                            aria-expanded="false" aria-controls="waMetaPay">
                        <span>💳 Add a payment method at Meta</span>
                        <i class="tio-chevron-down"></i>
                    </button>
                    <div class="collapse" id="waMetaPay">
                        <div class="wa-card-b">
                <div class="waba-payment-guide" style="border:0;padding:0;">

    <div class="waba-payment-guide__header">
        <h3>💳 Add a Payment Method</h3>
        <p>To continue sending WhatsApp messages, Meta may require a valid payment method on your WhatsApp Business Account (WABA).</p>
    </div>

    <div class="waba-payment-guide__notice">
        <strong>Important:</strong> Payment methods are managed directly by Meta. For security reasons, we cannot add or modify your payment method on your behalf.
    </div>

    <h4>Steps to Add a Payment Method</h4>

    <ol class="waba-payment-guide__steps">
        <li>Log in to
            <a href="https://business.facebook.com/" target="_blank" rel="noopener noreferrer">
                <strong>Meta Business Suite</strong> <i class="tio-open-in-new"></i>
            </a>.
        </li>
        <li>Select the <strong>Business Portfolio</strong> that contains your WhatsApp Business Account.</li>
        <li>Go to
            <a href="https://business.facebook.com/settings" target="_blank" rel="noopener noreferrer">
                <strong>Settings (⚙️)</strong> <i class="tio-open-in-new"></i>
            </a>.
        </li>
        <li>Navigate to
            <a href="https://business.facebook.com/settings/whatsapp-business-accounts" target="_blank" rel="noopener noreferrer">
                <strong>Accounts → WhatsApp Accounts</strong> <i class="tio-open-in-new"></i>
            </a>.
        </li>
        <li>Select your <strong>WhatsApp Business Account (WABA)</strong> — you can also open it directly in
            <a href="https://business.facebook.com/wa/manage/" target="_blank" rel="noopener noreferrer">
                WhatsApp Manager <i class="tio-open-in-new"></i>
            </a>.
        </li>
        <li>Open
            <a href="https://business.facebook.com/billing_hub/payment_settings" target="_blank" rel="noopener noreferrer">
                <strong>Payment Settings</strong> <i class="tio-open-in-new"></i>
            </a>.
        </li>
        <li>Click <strong>Add Payment Method</strong>.</li>
        <li>Enter your payment details and complete any bank verification.</li>
        <li>Save the payment method.</li>
    </ol>

    <p class="mb-0">
        Step-by-step help from Meta:
        <a href="https://www.facebook.com/business/help/488291839463771" target="_blank" rel="noopener noreferrer">
            Add a payment method to your WhatsApp Business Account <i class="tio-open-in-new"></i>
        </a>
    </p>

    <h4>Can't find the option?</h4>

    <ul class="waba-payment-guide__checklist">
        <li>You're logged into the correct <strong>Business Portfolio</strong>.</li>
        <li>You have <strong>Admin</strong> access to the
            <a href="https://business.facebook.com/settings/people" target="_blank" rel="noopener noreferrer">
                Business Portfolio <i class="tio-open-in-new"></i>
            </a>
            and WhatsApp Business Account.
        </li>
        <li>Your WhatsApp Business Account setup is complete.</li>
    </ul>

    <div class="waba-payment-guide__footer">
        If the option is still unavailable, please contact
        <a href="https://www.facebook.com/business/help/support" target="_blank" rel="noopener noreferrer">
            <strong>Meta Support</strong> <i class="tio-open-in-new"></i>
        </a>
        or your Meta Business administrator.
    </div>

</div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            </div>

            @if ($connected)
            {{-- ── Your customers ─────────────────────────────────────────────── --}}
            <div class="tab-pane fade" id="waAudience" role="tabpanel">
                <div class="row">
                    <div class="col-lg-7 wa-col">
                        <div class="wa-card h-100">
                            <div class="wa-card-h">
                                <span>Import customers</span>
                                <span class="wa-chip badge-soft-primary">{{ number_format($customerStats['with_phone']) }} reachable</span>
                            </div>
                            <div class="wa-card-b">
                                <p class="wa-sub mb-3">
                                    {{ number_format($customerStats['total']) }} people in your book,
                                    {{ number_format($customerStats['with_phone']) }} with a phone number — those are the
                                    ones <b>Send a message</b> can reach.
                                </p>

                                <form method="post" action="{{ route('vendor.whatsapp.customers.import') }}" enctype="multipart/form-data">
                                    @csrf
                                    <label class="wa-eyebrow d-block mb-1">Upload a spreadsheet</label>
                                    <div class="d-flex align-items-center flex-wrap mb-2" style="gap:8px;">
                                        <input type="file" name="file" class="form-control form-control-sm"
                                               style="flex:1 1 220px;min-width:0;" accept=".xlsx,.xls,.csv" required>
                                        <button class="btn btn--primary btn-sm text-nowrap" type="submit">
                                            <i class="tio-upload"></i> Import
                                        </button>
                                    </div>
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" class="custom-control-input" id="wdSendWelcome" name="send_welcome" value="1">
                                        <label class="custom-control-label" for="wdSendWelcome" style="font-size:13px;">
                                            Send a welcome message to newly imported customers
                                            <small class="text-muted d-block">Goes out in the background from your connected number, using your approved welcome template.</small>
                                        </label>
                                    </div>
                                    <div class="wa-note">
                                        Columns: <b>Name, Phone, Email, GST, Address</b> — only Name and Phone are required.
                                        <a href="{{ route('vendor.whatsapp.customers.template') }}">Download a template</a>.
                                        Duplicates (same phone) are skipped automatically.
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 wa-col">
                        <div class="wa-card h-100">
                            <div class="wa-card-h">Recently added</div>
                            @if ($recentCustomers->isEmpty())
                                <div class="wa-empty">
                                    <i class="tio-user-big-outlined"></i>
                                    <div class="wa-empty-t">No customers yet</div>
                                    <div class="wa-empty-s">Import a sheet to build your audience.</div>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table wa-table">
                                        <tbody>
                                            @foreach ($recentCustomers as $c)
                                                <tr>
                                                    <td>{{ $c->f_name ?: '—' }}</td>
                                                    <td class="text-muted text-right">{{ $c->phone ?: '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Send a message ─────────────────────────────────────────────── --}}
            <div class="tab-pane fade show active" id="waCompose" role="tabpanel">
                <div class="row">
                <div class="col-lg-9">
                    <div class="wa-card">
                        {{-- Audience counts live in the header, not the picker below: the picker is
                             hidden until an approved template exists, and the vendor still needs to
                             see who they could reach while deciding whether to make one. --}}
                        <div class="wa-card-h">
                            <span><i class="tio-send"></i> Bulk message</span>
                            <div class="d-flex flex-wrap" style="gap:6px;">
                                <span class="wa-chip badge-soft-info">
                                    {{ $clientCount }} of your {{ $clientCount == 1 ? 'customer' : 'customers' }}
                                </span>
                                <span class="wa-chip badge-soft-primary">
                                    {{ $platformUserCount }} MyChitti {{ $platformUserCount == 1 ? 'user' : 'users' }}
                                </span>
                            </div>
                        </div>
                        <div class="wa-card-b">
                            @if ($templateError)
                                <div class="alert alert-warning" style="font-size:13px;">
                                    Couldn’t load your templates: {{ $templateError }}
                                </div>
                            @endif

                            @if (empty($templates))
                                <p class="text-muted mb-2">
                                    You could reach <b>{{ $clientCount + $platformUserCount }}</b> people —
                                    {{ $clientCount }} of your own {{ $clientCount == 1 ? 'customer' : 'customers' }}
                                    and {{ $platformUserCount }} MyChitti {{ $platformUserCount == 1 ? 'user' : 'users' }}
                                    — but you have no approved message templates yet. WhatsApp only allows
                                    business-initiated messages using a template Meta has approved.
                                </p>
                                <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="tio-receipt"></i> Create a Template
                                </a>
                            @else
                                <div class="form-group">
                                    <label class="font-weight-bold" style="font-size:13px;">Template</label>
                                    <select id="wb-template" class="form-control">
                                        <option value="">— Select a template —</option>
                                        @foreach ($templates as $i => $t)
                                            <option value="{{ $i }}" @if ($t['unsupported']) disabled @endif>
                                                {{ $t['name'] }} ({{ $t['language'] }})@if (($t['status'] ?? 'APPROVED') !== 'APPROVED') — {{ $t['status'] }}, Meta will reject the send @elseif ($t['unsupported']) — not supported here, {{ $t['unsupported'] }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @if (\App\Http\Controllers\Vendor\WhatsAppController::BULK_SHOW_UNAPPROVED)
                                    <div class="alert alert-warning" style="font-size:12px;">
                                        Testing mode — templates that Meta hasn’t approved yet are listed too.
                                        Picking one lets you check the composer, but the send itself will come
                                        back as failed until the template’s status is APPROVED.
                                    </div>
                                @endif

                                <div id="wb-preview" class="border rounded p-3 mb-3 bg-light" style="display:none;">
                                    <div class="text-muted mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;">Message preview</div>
                                    <div id="wb-preview-body" style="font-size:13px;white-space:pre-wrap;"></div>
                                </div>

                                <div id="wb-vars" class="mb-3"></div>

                                <div class="form-group">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="font-weight-bold mb-0" style="font-size:13px;">Recipients</label>
                                        <span id="wb-selected-count" class="badge badge-soft-secondary">0 selected</span>
                                    </div>

                                    <ul class="nav nav-pills mb-3" style="gap:6px;">
                                        <li class="nav-item">
                                            <a href="javascript:;" class="nav-link active wb-mode" data-mode="clients" style="font-size:13px;padding:6px 14px;">
                                                My customers <span class="badge badge-soft-light ml-1">{{ $clientCount }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="javascript:;" class="nav-link wb-mode" data-mode="platform" style="font-size:13px;padding:6px 14px;">
                                                MyChitti users <span class="badge badge-soft-light ml-1">{{ $platformUserCount }}</span>
                                            </a>
                                        </li>
                                    </ul>

                                    <div id="wb-pane-clients">
                                        <div class="d-flex mb-2" style="gap:8px;">
                                            <input id="wb-search" type="text" class="form-control form-control-sm"
                                                   placeholder="Search customers by name or phone…">
                                            <button id="wb-select-all" type="button" class="btn btn-sm btn-outline-secondary text-nowrap">Select all</button>
                                            <button id="wb-clear" type="button" class="btn btn-sm btn-outline-secondary text-nowrap">Clear</button>
                                        </div>
                                        <div id="wb-clients" class="border rounded" style="max-height:260px;overflow-y:auto;">
                                            <div class="text-muted text-center p-3" style="font-size:13px;">Loading clients…</div>
                                        </div>
                                        <small id="wb-truncated" class="text-muted" style="display:none;"></small>
                                    </div>

                                    <div id="wb-pane-platform" style="display:none;">
                                        <div class="border rounded p-3">
                                            <p class="text-muted mb-2" style="font-size:13px;">
                                                MyChitti users in your city. You choose how many to reach — their phone
                                                numbers stay private and are never shown to you.
                                            </p>
                                            @if ($platformUserCount == 0)
                                                <div class="alert alert-info mb-0" style="font-size:13px;">
                                                    No MyChitti users match your city yet, so there is nobody to reach
                                                    here right now. This grows as customers in your area start using
                                                    MyChitti — your own customers are unaffected.
                                                </div>
                                            @else
                                                <label style="font-size:12px;" class="mb-1">How many users to message</label>
                                                <input id="wb-platform-count" type="number" class="form-control form-control-sm"
                                                       style="max-width:200px;" min="1" max="{{ $platformUserCount }}"
                                                       value="{{ min(50, $platformUserCount) }}">
                                                <small class="text-muted d-block mt-1">
                                                    Maximum {{ $platformUserCount }} available now. Anyone who has already
                                                    received {{ \App\Http\Controllers\Vendor\WhatsAppController::NEARBY_MONTHLY_CAP }}
                                                    offers from any business this month is excluded automatically, so the
                                                    number moves as other vendors send.
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <small class="text-muted d-block mb-3">
                                    Anyone who replies <b>STOP</b> is removed automatically and excluded from every
                                    future send.@if (($optOutCount ?? 0) > 0) {{ $optOutCount }} {{ $optOutCount == 1 ? 'person has' : 'people have' }} opted out and {{ $optOutCount == 1 ? 'is' : 'are' }} already excluded from the counts above.@endif
                                    Keeping unwanted messages down protects your number's WhatsApp quality rating.
                                </small>

                                <div class="d-flex align-items-center" style="gap:12px;">
                                    <button id="wb-send" class="btn btn--primary" disabled>Send</button>
                                    <div id="wb-progress" class="flex-grow-1" style="display:none;">
                                        <div class="progress" style="height:6px;">
                                            <div id="wb-progress-bar" class="progress-bar bg-success" style="width:0%;"></div>
                                        </div>
                                        <small id="wb-progress-text" class="text-muted"></small>
                                    </div>
                                </div>

                                <div id="wb-results" class="mt-3" style="display:none;"></div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="wa-card">
                        <div class="wa-card-h">Before you send</div>
                        <div class="wa-card-b">
                            <ul class="pl-3 mb-0 wa-sub" style="line-height:1.7;">
                                <li>WhatsApp only allows business-initiated messages from a template Meta has approved.</li>
                                <li>Anyone who replies <b>STOP</b> is excluded from this and every future send, automatically.</li>
                                <li>MyChitti users never expose their number to you — results come back masked.</li>
                                <li>Keeping unwanted messages down protects your number's quality rating.</li>
                            </ul>
                            <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-sm btn-outline-primary btn-block mt-3">
                                <i class="tio-receipt"></i> Manage templates
                            </a>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@if ($connected && !empty($templates))
@push('script_2')
    <script>
        (function () {
            var TEMPLATES = @json($templates);
            var BATCH = {{ \App\Http\Controllers\Vendor\WhatsAppController::BULK_BATCH_LIMIT }};
            var RECIPIENTS_URL = '{{ route('vendor.whatsapp.bulk.recipients') }}';
            var SEND_URL = '{{ route('vendor.whatsapp.bulk.send') }}';
            var CSRF = '{{ csrf_token() }}';

            var PLATFORM_MAX = {{ $platformUserCount }};

            var selected = new Set();
            var loaded = [];
            var searchTimer = null;
            var mode = 'clients';

            // Built by concatenation so Blade never sees a literal double-brace in this script.
            var OPEN = '{' + '{', CLOSE = '}' + '}';

            var $tpl = document.getElementById('wb-template');
            var $vars = document.getElementById('wb-vars');
            var $preview = document.getElementById('wb-preview');
            var $previewBody = document.getElementById('wb-preview-body');
            var $list = document.getElementById('wb-clients');
            var $search = document.getElementById('wb-search');
            var $count = document.getElementById('wb-selected-count');
            var $truncated = document.getElementById('wb-truncated');
            var $send = document.getElementById('wb-send');
            var $progress = document.getElementById('wb-progress');
            var $bar = document.getElementById('wb-progress-bar');
            var $ptext = document.getElementById('wb-progress-text');
            var $results = document.getElementById('wb-results');

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function currentTemplate() {
                return $tpl.value === '' ? null : TEMPLATES[parseInt($tpl.value, 10)];
            }

            // One entry per body slot, in send order: {key, value}. Auto slots carry no value —
            // the server fills them from the recipient row.
            function paramValues() {
                return Array.prototype.map.call($vars.querySelectorAll('.wb-var'), function (i) {
                    var auto = i.dataset.auto === '1';
                    return { key: i.dataset.key, auto: auto, value: auto ? '' : i.value };
                });
            }

            function templateVars(t) {
                if (t && t.vars && t.vars.length) return t.vars;
                // Older cached payloads only carried a count.
                var out = [], n = (t && t.var_count) || 0;
                for (var i = 1; i <= n; i++) out.push({ key: String(i), label: 'Variable ' + i, auto: false });
                return out;
            }

            // A real recipient out of the current selection, so the preview reads the way the
            // first person will actually receive it instead of showing placeholders. Platform
            // recipients are anonymous to the vendor, so there is nothing to sample there.
            function sampleClient() {
                if (mode === 'clients') {
                    for (var i = 0; i < loaded.length; i++) {
                        if (selected.has(loaded[i].id)) return loaded[i];
                    }
                }
                return null;
            }

            function renderPreview() {
                var t = currentTemplate();
                if (!t) { $preview.style.display = 'none'; return; }

                // Auto slots resolve to the {name} / {phone} markers first so the pass below
                // can bold them, whatever the vendor typed elsewhere.
                var body = t.body;
                paramValues().forEach(function (p) {
                    var slot = OPEN + p.key + CLOSE;
                    var shown = p.key === 'customer_name' ? '{name}'
                        : p.key === 'customer_phone' ? '{phone}'
                        : (p.value || slot);
                    body = body.split(slot).join(shown);
                });
                var c = sampleClient();
                $previewBody.innerHTML = esc(body)
                    .replace(/\{name\}/g, '<b>' + esc((c && c.f_name) || 'each customer’s name') + '</b>')
                    .replace(/\{(customer_)?phone\}/g, '<b>' + esc((c && c.phone) || 'each customer’s number') + '</b>');
                $preview.style.display = 'block';
            }

            /**
             * Templates greet with a variable ("Hi" followed by the first token) and label the
             * contact slot ("Phone: " followed by a token) — prefill those with {name} / {phone}
             * so the vendor never has to know the tokens exist. The greeting test is anchored to
             * the start of a line, and the phone test only fires on a label right before the
             * token, so a variable buried mid-sentence is left alone.
             */
            function defaultVarValue(body, key) {
                var at = String(body || '').indexOf(OPEN + key + CLOSE);
                if (at < 0) return '';
                var before = body.slice(0, at);

                if (/(^|\n)\s*(hi+|hey+|hello|dear|namaste|greetings)\b[\s,!:.-]*$/i.test(before)) {
                    return '{name}';
                }
                if (/(phone|mobile|contact|whats\s*app|cell)\s*(number|no\.?|#)?\s*[:\-–]?\s*$/i.test(before)) {
                    return '{phone}';
                }
                return '';
            }

            function renderVars() {
                var t = currentTemplate();
                $vars.innerHTML = '';
                var vars = templateVars(t);
                if (!t || !vars.length) { syncSend(); return; }

                var help = document.createElement('small');
                help.className = 'text-muted d-block mb-2';
                help.innerHTML = 'Fill each variable. <code>{name}</code> and <code>{phone}</code> are replaced ' +
                    'with each recipient’s own name and number — use them in any variable that should be ' +
                    'personalised.';
                $vars.appendChild(help);

                vars.forEach(function (v) {
                    var wrap = document.createElement('div');
                    wrap.className = 'form-group mb-2';

                    if (v.auto) {
                        // Filled from the recipient row on the server — showing an editable box
                        // would only invite a value that gets thrown away.
                        wrap.innerHTML = '<label style="font-size:12px;" class="mb-1">' + esc(v.label) +
                            ' <code>' + OPEN + esc(v.key) + CLOSE + '</code></label>' +
                            '<input type="text" class="form-control form-control-sm wb-var" readonly ' +
                            'data-key="' + esc(v.key) + '" data-auto="1" ' +
                            'value="Filled in automatically for each recipient">';
                    } else {
                        wrap.innerHTML = '<label style="font-size:12px;" class="mb-1">Variable ' +
                            OPEN + esc(v.key) + CLOSE + '</label>' +
                            '<input type="text" class="form-control form-control-sm wb-var" ' +
                            'data-key="' + esc(v.key) + '" data-auto="0" ' +
                            'value="' + esc(defaultVarValue(t.body, v.key)) + '" ' +
                            'placeholder="Value for ' + OPEN + esc(v.key) + CLOSE + '">';
                    }
                    $vars.appendChild(wrap);
                });

                Array.prototype.forEach.call($vars.querySelectorAll('.wb-var'), function (input) {
                    input.addEventListener('input', syncSend);
                });
                syncSend();
            }

            function countFrom(inputId, max) {
                var $input = document.getElementById(inputId);
                if (!$input) return 0;
                var n = parseInt($input.value, 10);
                if (isNaN(n) || n < 1) return 0;
                return Math.min(n, max);
            }

            function recipientCount() {
                if (mode === 'platform') return countFrom('wb-platform-count', PLATFORM_MAX);
                return selected.size;
            }

            function syncSend() {
                renderPreview();
                var t = currentTemplate();
                var filled = !t || paramValues().every(function (p) { return p.auto || p.value.trim() !== ''; });
                var n = recipientCount();
                $send.disabled = !t || !filled || n === 0;
                $count.textContent = mode === 'clients'
                    ? selected.size + ' selected'
                    : n + ' MyChitti user' + (n === 1 ? '' : 's');
                $send.textContent = n
                    ? 'Send to ' + n + ' recipient' + (n === 1 ? '' : 's')
                    : 'Send';
            }

            function setMode(next) {
                mode = next;
                Array.prototype.forEach.call(document.querySelectorAll('.wb-mode'), function (el) {
                    el.classList.toggle('active', el.dataset.mode === next);
                });
                document.getElementById('wb-pane-clients').style.display = next === 'clients' ? 'block' : 'none';
                document.getElementById('wb-pane-platform').style.display = next === 'platform' ? 'block' : 'none';
                syncSend();
            }

            function renderClients() {
                if (!loaded.length) {
                    $list.innerHTML = '<div class="text-muted text-center p-3" style="font-size:13px;">No clients match.</div>';
                    return;
                }
                $list.innerHTML = loaded.map(function (c) {
                    return '<label class="d-flex align-items-center px-3 py-2 mb-0 border-bottom" style="cursor:pointer;gap:10px;">' +
                        '<input type="checkbox" class="wb-client" value="' + c.id + '"' + (selected.has(c.id) ? ' checked' : '') + '>' +
                        '<span style="font-size:13px;"><b>' + esc(c.f_name || 'Unnamed') + '</b> ' +
                        '<span class="text-muted">' + esc(c.phone) + '</span></span></label>';
                }).join('');

                Array.prototype.forEach.call($list.querySelectorAll('.wb-client'), function (box) {
                    box.addEventListener('change', function () {
                        var id = parseInt(this.value, 10);
                        this.checked ? selected.add(id) : selected.delete(id);
                        syncSend();
                    });
                });
            }

            function loadClients() {
                $list.innerHTML = '<div class="text-muted text-center p-3" style="font-size:13px;">Loading clients…</div>';
                fetch(RECIPIENTS_URL + '?search=' + encodeURIComponent($search.value), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    loaded = d.clients || [];
                    if (d.truncated) {
                        $truncated.style.display = 'block';
                        $truncated.textContent = 'Showing the first ' + loaded.length + ' of ' + d.total + ' clients — search to narrow the list.';
                    } else {
                        $truncated.style.display = 'none';
                    }
                    renderClients();
                    syncSend();
                })
                .catch(function () {
                    $list.innerHTML = '<div class="text-danger text-center p-3" style="font-size:13px;">Could not load clients.</div>';
                });
            }

            function sendBatches() {
                var t = currentTemplate();
                var total = recipientCount();
                var batches = [];

                if (mode === 'platform') {
                    // The server walks the audience in a fixed order, so an offset/limit pair
                    // addresses each recipient exactly once without the browser ever seeing a
                    // number.
                    for (var o = 0; o < total; o += BATCH) {
                        batches.push({ mode: mode, offset: o, limit: Math.min(BATCH, total - o) });
                    }
                } else {
                    var ids = Array.from(selected);
                    for (var i = 0; i < ids.length; i += BATCH) {
                        batches.push({ mode: 'clients', client_ids: ids.slice(i, i + BATCH) });
                    }
                }

                var done = 0, sent = 0, failures = [];
                $send.disabled = true;
                $progress.style.display = 'block';
                $results.style.display = 'none';

                function batchSize(b) {
                    return b.mode === 'clients' ? b.client_ids.length : b.limit;
                }

                function step(index) {
                    if (index >= batches.length) {
                        $ptext.textContent = 'Finished — ' + sent + ' sent, ' + failures.length + ' failed.';
                        showResults(sent, failures);
                        $send.disabled = false;
                        return;
                    }
                    fetch(SEND_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(Object.assign({
                            template: t.name,
                            language: t.language,
                            params: paramValues()
                        }, batches[index]))
                    })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        if (!res.ok) {
                            failures.push({ name: '—', phone: '—', error: res.d.message || 'Request rejected.' });
                        } else {
                            sent += res.d.sent || 0;
                            (res.d.results || []).forEach(function (r) { if (!r.success) failures.push(r); });
                        }
                        done += batchSize(batches[index]);
                        var pct = Math.round((done / total) * 100);
                        $bar.style.width = pct + '%';
                        $ptext.textContent = done + ' of ' + total + ' processed…';
                        step(index + 1);
                    })
                    .catch(function () {
                        failures.push({ name: '—', phone: '—', error: 'Network error on a batch of ' + batchSize(batches[index]) + '.' });
                        done += batchSize(batches[index]);
                        step(index + 1);
                    });
                }
                step(0);
            }

            function showResults(sent, failures) {
                var html = '<div class="alert ' + (failures.length ? 'alert-warning' : 'alert-success') + '" style="font-size:13px;">' +
                    '<b>' + sent + '</b> message' + (sent === 1 ? '' : 's') + ' sent' +
                    (failures.length ? ', <b>' + failures.length + '</b> failed.' : '.') + '</div>';

                if (failures.length) {
                    html += '<div class="border rounded" style="max-height:200px;overflow-y:auto;">' +
                        failures.map(function (f) {
                            return '<div class="px-3 py-2 border-bottom" style="font-size:12px;">' +
                                '<b>' + esc(f.name) + '</b> <span class="text-muted">' + esc(f.phone) + '</span><br>' +
                                '<span class="text-danger">' + esc(f.error) + '</span></div>';
                        }).join('') + '</div>';
                }
                $results.innerHTML = html;
                $results.style.display = 'block';
            }

            $tpl.addEventListener('change', renderVars);
            $search.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(loadClients, 300);
            });
            document.getElementById('wb-select-all').addEventListener('click', function () {
                loaded.forEach(function (c) { selected.add(c.id); });
                renderClients();
                syncSend();
            });
            document.getElementById('wb-clear').addEventListener('click', function () {
                selected.clear();
                renderClients();
                syncSend();
            });
            Array.prototype.forEach.call(document.querySelectorAll('.wb-mode'), function (el) {
                el.addEventListener('click', function () { setMode(this.dataset.mode); });
            });
            ['wb-platform-count'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.addEventListener('input', syncSend);
            });
            $send.addEventListener('click', function () {
                var n = recipientCount();
                var who = mode === 'clients' ? 'of your customers' : 'MyChitti user(s)';
                if (!confirm('Send this template to ' + n + ' ' + who + '?')) return;
                sendBatches();
            });

            loadClients();
        })();
    </script>
@endpush
@endif

@if (!$connected && $es['ready'])
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

        // Absent until the onboarding fee is paid — the button is a checkout form until then.
        var $connectBtn = document.getElementById('wa-connect-btn');
        if ($connectBtn) $connectBtn.addEventListener('click', function () {
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
