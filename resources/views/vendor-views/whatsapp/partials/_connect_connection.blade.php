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

                            {{-- A WABA holds many numbers, and wa_numbers has always been able to
                                 store them — but the signup button used to exist only in the "not
                                 connected" branch below, so the second number could never be
                                 added from here. finish() already counts the store's numbers
                                 against the limit and treats a repeat id as a token refresh. --}}
                            @php($atLimit = $limit > 0 && count($numbers) >= $limit)
                            <div class="mt-3">
                                @if (!$es['ready'])
                                    <div class="alert alert-warning mb-0" style="font-size:13px;">
                                        WhatsApp onboarding isn’t available yet, so another number can’t be added.
                                    </div>
                                @elseif ($atLimit)
                                    <div class="alert alert-secondary mb-0" style="font-size:13px;">
                                        You have connected the maximum of <b>{{ $limit }}</b>
                                        {{ $limit === 1 ? 'number' : 'numbers' }}.
                                        @if ($metaCap > 0 && $metaCap <= $limit)
                                            {{-- Meta's ceiling, not ours: it starts at 2 and becomes 20
                                                 once the business is verified, so say what lifts it. --}}
                                            This is the cap WhatsApp allows your business right now. It rises
                                            once your business is verified with Meta — until then, disconnect a
                                            number under <b>Numbers</b> to free a slot.
                                        @else
                                            Disconnect one under <b>Numbers</b> before adding another.
                                        @endif
                                    </div>
                                @else
                                    <button id="wa-connect-btn" class="btn btn-success">
                                        <i class="tio-add-circle"></i> Add another number
                                    </button>
                                    <div class="wa-sub mt-2 mb-2">
                                        Links a second WhatsApp Business number to this store — a reception line and a
                                        clinic line, say. Assign what each one sends under <b>Numbers</b>.
                                    </div>
                                    @include('vendor-views.whatsapp.partials._number_cap_note')
                                    <div id="wa-status" class="mt-3 text-muted" style="display:none;"></div>
                                @endif
                            </div>

                            <div class="d-flex flex-wrap align-items-center mt-3" style="gap:8px;">
                                <a href="{{ route('vendor.whatsapp.templates') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="tio-receipt"></i> Message templates
                                </a>
                                <a href="{{ route('vendor.whatsapp.connect', ['tab' => 'billing']) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="tio-wallet"></i> Plan &amp; billing
                                </a>
                                <form method="post" action="{{ route('vendor.whatsapp.disconnect') }}" class="mb-0 ml-auto"
                                      onsubmit="return confirm('Disconnect WhatsApp? You will not be able to send anything to your customers until you connect a number again.')">
                                    @csrf
                                    <button class="btn btn-outline-danger btn-sm">Disconnect all numbers</button>
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
