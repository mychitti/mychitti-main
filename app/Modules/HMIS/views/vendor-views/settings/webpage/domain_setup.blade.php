<style>
    .setup-steps {
        counter-reset: step-counter;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .setup-steps > li {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        align-items: flex-start;
    }
    .step-badge {
        min-width: 32px;
        height: 32px;
        background: #4e73df;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .step-content {
        flex: 1;
    }
    .step-content p {
        margin-bottom: 0.4rem;
    }
    .dns-block {
        background: #f4f6fc;
        border-left: 3px solid #4e73df;
        border-radius: 4px;
        padding: 0.6rem 1rem;
        font-family: monospace;
        font-size: 0.88rem;
        margin: 0.5rem 0 0;
        white-space: pre;
    }
    .step-highlight {
        background: #eaf0ff;
        border: 1.5px solid #4e73df;
        border-radius: 8px;
        padding: 1.25rem 1.5rem;
    }
    .step-badge.active {
        background: #1cc88a;
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
</style>

<div class="row w-100 justify-content-center">
    <div class="col-md-9">

        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fas fa-globe text-primary mr-2"></i>
                <h5 class="mb-0">Custom Domain Setup</h5>
            </div> 

            <div class="card-body px-4 py-4">

                @if(!empty($domain))
                <div class="d-flex align-items-center justify-content-between border rounded px-3 py-2 mb-4"
                     style="background:#f0fdf4; border-color:#bbf7d0 !important;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-check-circle text-success mr-2"></i>
                        <div>
                            <div class="font-weight-semibold" style="font-size:0.9rem;">Active Domain</div>
                            <code style="font-size:0.95rem;">{{ $domain }}</code>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('vendor.settings.domain.remove') }}"
                          onsubmit="return confirm('Remove {{ $domain }}? Your store will revert to the default URL.')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash-alt mr-1"></i> Remove Domain
                        </button>
                    </form>
                </div>
                @endif

                <p class="text-muted mb-4">
                    Follow these steps to connect your own domain to your store.
                    DNS changes may take <strong>5–30 minutes</strong> (occasionally a few hours) to propagate.
                </p>

                <ul class="setup-steps">

                    <!-- Step 1 -->
                    <li>
                        <div class="step-badge">1</div>
                        <div class="step-content">
                            <p>Login to your domain provider — e.g. <strong>GoDaddy</strong>, <strong>Namecheap</strong>, <strong>BigRock</strong>, or wherever you bought your domain.</p>
                        </div>
                    </li>

                    <!-- Step 2 --> 
                    <li>
                        <div class="step-badge">2</div>
                        <div class="step-content">
                            <p>Navigate to the <strong>DNS Settings</strong> (sometimes called DNS Management or DNS Records) for your domain.</p>
                        </div>
                    </li>

                    <!-- Step 3 -->
                    <li>
                        <div class="step-badge">3</div>
                        <div class="step-content">
                            <p class="mb-3">Add a <strong>CNAME record</strong> — the <code>Name</code> field depends on what you're connecting:</p>

                            <!-- Option A -->
                            <div class="border rounded p-3 mb-2" style="background:#f8f9ff;">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge badge-primary mr-2" style="font-size:0.78rem;">Option A</span>
                                    <strong>Root domain &mdash; e.g. <code>yourdomain.com</code></strong>
                                </div>
                                <p class="mb-1 text-muted" style="font-size:0.9rem;">Add this CNAME record:</p>
                                <div class="dns-block">Type  : CNAME
Name  : www
Value : stores.mychitti.net</div>
                                <p class="mb-1 mt-3 text-muted" style="font-size:0.9rem;">
                                    Then set up a <strong>redirect / forwarding</strong> so <code>yourdomain.com</code> forwards to <code>https://www.yourdomain.com</code>.<br>
                                    In GoDaddy: <strong>My Products → DNS → Forwarding</strong> — use Permanent (301):
                                </p>
                                <div class="dns-block">From  : yourdomain.com
To    : https://www.yourdomain.com
Type  : Permanent (301)</div> 
                            </div> 

                            <!-- Option B -->
                            <div class="border rounded p-3" style="background:#f8f9ff;">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge badge-secondary mr-2" style="font-size:0.78rem;">Option B</span>
                                    <strong>Subdomain &mdash; e.g. <code>shop.yourdomain.com</code></strong>
                                </div>
                                <p class="mb-1 text-muted" style="font-size:0.9rem;">
                                    Use the subdomain prefix as the Name (e.g. <code>shop</code> for <code>shop.yourdomain.com</code>):
                                </p>
                                <div class="dns-block" >Type  : CNAME
Name  : shop
Value : stores.mychitti.net</div>
                            </div>
                        </div>
                    </li>

                    <!-- Step 4 -->
                    <li>
                        <div class="step-badge">4</div>
                        <div class="step-content">
                            <p>
                                Remove any <strong>existing A or CNAME records</strong> for <code>www</code> that point elsewhere,
                                to avoid conflicts.
                            </p>
                        </div>
                    </li>

                    <!-- Step 5 — Form embedded here -->
                    <li>
                        <div class="step-badge active">5</div>
                        <div class="step-content step-highlight">
                            <p class="font-weight-bold mb-2">
                                <i class="fas fa-check-circle text-success mr-1"></i>
                                Enter your domain and save — SSL will be issued automatically.
                            </p>

                            @if(!empty($domainCharge) && $domainCharge > 0)
                            @php
                                if ($domainGstIncl && $domainGstPct > 0) {
                                    // GST is baked into the charge — extract it back out for display
                                    $gstAmount   = round($domainCharge - $domainCharge / (1 + $domainGstPct / 100), 2);
                                    $totalAmount = $domainCharge;
                                } else {
                                    // GST added on top
                                    $gstAmount   = $domainGstPct > 0 ? round($domainCharge * $domainGstPct / 100, 2) : 0;
                                    $totalAmount = $domainCharge + $gstAmount;
                                }
                            @endphp
                            <div class="border rounded px-3 py-2 mb-3" style="background:#fffbeb; border-color:#fde68a !important; font-size:0.9rem;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Custom domain charge</span>
                                    <span>
                                        ₹{{ number_format($domainCharge, 2) }}
                                        @if($domainGstIncl)
                                            <span class="text-muted" style="font-size:0.82rem;">(GST incl.)</span>
                                        @elseif($domainGstPct > 0)
                                            <span class="text-muted" style="font-size:0.82rem;">+ GST</span>
                                        @endif
                                    </span>
                                </div>
                                @if($domainGstPct > 0)
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">
                                        GST ({{ $domainGstPct }}%)
                                        @if($domainGstIncl)
                                            <span class="badge badge-secondary" style="font-size:0.7rem;">Included</span>
                                        @endif
                                    </span>
                                    <span>₹{{ number_format($gstAmount, 2) }}</span>
                                </div>
                                @endif
                                <div class="d-flex justify-content-between font-weight-bold border-top pt-1 mt-1">
                                    <span>Total</span>
                                    <span>₹{{ number_format($totalAmount, 2) }}</span>
                                </div>
                            </div>
                            @endif 

                            <form method="POST" action="{{ route('vendor.settings.domain.update') }}">
                                @csrf 

                                <div class="form-group mb-3">
                                    <label class="font-weight-semibold">Your Domain</label>
                                    <input
                                        type="text"
                                        name="domain"
                                        class="form-control @error('domain') is-invalid @enderror"
                                        placeholder="www.yourdomain.com"
                                        value="{{  (old('domain') ?? '') }}"
                                        required
                                    >
                                    <small class="text-muted">
                                        Enter without <code>http://</code> or <code>https://</code> &mdash; use <code>www.yourdomain.com</code>
                                    </small>
                                    @error('domain')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    @if(!empty($domainCharge) && $domainCharge > 0)
                                        <i class="fas fa-credit-card mr-1"></i> Pay & Activate Domain
                                    @else
                                        <i class="fas fa-save mr-1"></i> Save Domain
                                    @endif
                                </button>
                            </form>
                        </div>
                    </li>

                    <!-- Step 6 --> 
                    <li>
                        <div class="step-badge">6</div>
                        <div class="step-content">
                            <p>
                                Wait for DNS to propagate — usually <strong>5–30 minutes</strong>, but can take up to a few hours.
                                Once propagated, your SSL certificate will be issued and deployed automatically.
                            </p>
                        </div>
                    </li>

                </ul>

                <hr class="my-4">

                <!-- Extra tips -->
                <div class="alert alert-info mb-3">
                    <strong><i class="fas fa-cloud mr-1"></i> Using Cloudflare for DNS?</strong><br>
                    You can add a CNAME for <code>@</code> (root) directly — no forwarding rule needed.
                    Also add the same for <code>www</code>.
                    <div class="dns-block mt-2" style="color: #3b3b3b;">Type  : CNAME
Name  : @   (and also www)
Value : stores.mychitti.net
Proxy : DNS Only (grey cloud — do NOT enable proxy/orange cloud)</div>
                </div>

                <div class="alert alert-success mb-3">
                    <strong><i class="fas fa-lock mr-1"></i> SSL is automatic.</strong>
                    Once DNS is configured and your domain is saved, your SSL certificate will be issued and deployed within a few minutes — no separate purchase needed.
                </div>

            </div>
        </div>

    </div>
</div>
