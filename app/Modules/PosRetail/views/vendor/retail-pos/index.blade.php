@extends('layouts.vendor.app')

@section('title', 'Retail POS')

@push('css_or_js')
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        /* Bind to the panel's theme color (set in theme-colors.blade) so POS matches the store brand. */
        .rpos { --ink:#212b36; --muted:#8893a3; --line:#edf0f5; --soft:#f6f7fb;
                --accent:var(--primary,#754BFF); --accent2:var(--primary-light-theme,#A099FF); --accent-dark:var(--primary-dark,#6e44fa);
                color:var(--ink); }
        .rpos .pos-topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px; }
        .rpos .pos-topbar h1 { font-size:22px; font-weight:700; margin:0; }
        .rpos .pos-topbar .sub { font-size:12px; color:var(--muted); }

        .pos-wrap { display:flex; gap:16px; align-items:flex-start; }
        .pos-left { flex:1 1 56%; min-width:0; }
        .pos-right { flex:1 1 44%; position:sticky; top:12px; min-width:0; }
        .pos-card { background:#fff; border:1px solid var(--line); border-radius:16px; box-shadow:0 1px 3px rgba(16,24,40,.05); overflow:hidden; }
        .pc-hd { padding:13px 16px; border-bottom:1px solid var(--line); font-weight:700; font-size:14px; display:flex; justify-content:space-between; align-items:center;
                 background:var(--accent); color:#fff; }
        .pc-bd { padding:14px 16px; }

        #pos-search { border-radius:12px; border:1.5px solid #e3e7ef; height:50px; font-size:15px; padding:0 16px; }
        #pos-search:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(0,0,0,.05); }
        /* Header action buttons — solid white so they pop on the colored header */
        .btn-scan { border:0; background:#fff; color:var(--accent); border-radius:10px; padding:7px 13px; font-size:12px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,.15); }
        .btn-scan:hover { background:#f4f4f8; transform:translateY(-1px); }
        .pc-hd .btn-outline-secondary { background:#fff !important; border:0 !important; color:var(--accent) !important; font-weight:700; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.15); }
        .pc-hd .btn-outline-secondary:hover { background:#f4f4f8 !important; }
        .pc-hd .badge { background:var(--accent) !important; color:#fff !important; margin-left:4px; }

        .pos-results { max-height:260px; overflow:auto; border:1px solid var(--line); border-radius:12px; margin-top:8px; background:#fff; }
        .pos-results .res-row { padding:9px 14px; cursor:pointer; border-bottom:1px solid #f4f4f7; display:flex; justify-content:space-between; align-items:center; font-size:13px; }
        .pos-results .res-row:last-child { border-bottom:0; }
        .pos-results .res-row:hover { background:var(--soft); }

        .items-row { display:flex; gap:12px; margin-top:14px; align-items:flex-start; }
        .cat-bar { flex:0 0 156px; max-height:420px; overflow-y:auto; display:flex; flex-direction:column; gap:5px; padding-right:10px; border-right:1px solid var(--line); }
        .cat-tab { padding:9px 12px; border:1px solid #e7eaf3; border-radius:10px; cursor:pointer; font-size:12.5px; background:#fff; text-align:left; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; transition:.12s; }
        .cat-tab:hover { background:var(--soft); }
        .cat-tab.active { background:var(--accent); color:#fff; border-color:var(--accent); }

        .quick-grid { flex:1; display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:10px; max-height:420px; overflow-y:auto; padding:2px; }
        .quick-item { border:1px solid #e7eaf3; border-radius:12px; padding:12px 10px; cursor:pointer; text-align:center; font-size:12px; background:#fff; transition:.12s; display:flex; flex-direction:column; gap:6px; min-height:74px; justify-content:center; }
        .quick-item:hover { border-color:var(--accent); box-shadow:0 6px 16px rgba(16,24,40,.10); transform:translateY(-2px); }
        .quick-item .font-weight-bold { font-size:12.5px; line-height:1.25; }
        .quick-item .text-muted { color:var(--accent) !important; font-weight:700; font-size:13px; }

        .cust-wrap { position:relative; margin-bottom:12px; }
        #cust-search { border-radius:10px; height:42px; }
        #cust-info { background:var(--soft); border:1px solid var(--line); border-radius:8px; padding:6px 10px; }

        .cart-table { margin-bottom:0; }
        .cart-table thead th { font-size:10.5px; text-transform:uppercase; letter-spacing:.03em; color:var(--muted); border-top:0; border-bottom:1px solid var(--line); padding:8px 6px; }
        .cart-table td { vertical-align:middle; font-size:13px; padding:9px 6px; border-color:#f4f4f7; }
        .cart-name { font-weight:600; line-height:1.25; }
        .cart-sub { font-size:11px; color:#c0392b; }
        .qty-stepper { display:inline-flex; align-items:center; gap:2px; border:1px solid var(--line); border-radius:9px; padding:2px; background:#fff; }
        .qty-stepper span { min-width:24px; text-align:center; font-weight:700; font-size:13px; }
        .qty-btn { width:26px; height:26px; line-height:1; padding:0; border-radius:7px; border:0; background:var(--soft); color:var(--ink); font-weight:700; cursor:pointer; }
        .qty-btn:hover { background:var(--accent); color:#fff; }
        .cart-del { color:#c0392b; cursor:pointer; font-size:16px; }
        .cart-del:hover { opacity:.7; }

        .totals { background:var(--soft); border:1px solid var(--line); border-radius:12px; padding:10px 14px; margin-top:12px; }
        .totals-row { display:flex; justify-content:space-between; padding:3px 0; font-size:13.5px; }
        .grand-bar { display:flex; justify-content:space-between; align-items:center; background:var(--accent); color:#fff; border-radius:12px; padding:12px 16px; margin-top:10px; }
        .grand-bar .lbl { font-size:13px; opacity:.92; } .grand-bar .val { font-size:24px; font-weight:800; }

        .pay-head { font-weight:700; font-size:13px; margin:16px 0 8px; }
        .denoms { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px; }
        .denoms .btn { border-radius:8px; }
        .pay-leg { display:flex; gap:8px; margin-bottom:6px; flex-wrap:wrap; }
        .pay-leg .form-control { border-radius:8px; }

        #btn-finalize, #btn-finalize:hover, #btn-finalize:focus, #btn-finalize:active {
            border-radius:12px; font-weight:700; border:0; color:#fff;
            background:var(--accent) !important; box-shadow:0 6px 16px rgba(0,0,0,.12); }
        #btn-finalize:hover { filter:brightness(.93); }
        #btn-hold { border-radius:12px; font-weight:600; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid rpos">
        <div class="pos-topbar">
            <div>
                <h1 class="page-header-title">Retail POS</h1>
                <div class="sub">Billing &amp; checkout</div>
            </div>
            @if (hasPermission('pos_bills', 'view'))
                <a href="{{ route('vendor.retail-pos.today') }}" class="btn btn-sm btn-outline-primary">📋 Today's Bills</a>
            @endif
        </div>

        <div class="pos-wrap">
            <!-- LEFT: scan / search / quick items -->
            <div class="pos-left">
                <div class="pos-card">
                    <div class="pc-hd">
                        <span>🔎 Add Items</span>
                        <button type="button" class="btn-scan" id="btn-cam-scan">📷 Scan with camera</button>
                    </div>
                    <div class="pc-bd">
                        <input type="text" id="pos-search" class="form-control" autofocus autocomplete="off"
                            placeholder="Scan with USB scanner, or type name / SKU / barcode, then Enter">
                        <div class="pos-results" id="pos-results" style="display:none;"></div>

                        {{-- Camera scanner overlay --}}
                        <div id="cam-scan" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.75); z-index:1080; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:14px; padding:16px; width:340px; max-width:92vw; text-align:center;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <b>Scan a barcode</b>
                                    <a href="javascript:;" id="cam-close" class="text-danger" style="font-size:20px;">&times;</a>
                                </div>
                                <div id="cam-reader" style="width:100%;"></div>
                                <div class="small text-muted mt-2" id="cam-msg">Point the camera at the product barcode.</div>
                            </div>
                        </div>

                        <div class="items-row">
                            <div class="cat-bar" id="cat-tabs">
                                <div class="cat-tab active" data-cat="all">All</div>
                                <div class="cat-tab" data-cat="">★ Popular</div>
                                @foreach ($categories as $c)
                                    <div class="cat-tab" data-cat="{{ $c->id }}">{{ $c->name }}</div>
                                @endforeach
                            </div>

                            <div class="quick-grid" id="quick-grid">
                                @foreach ($quickItems as $qi)
                                    @php
                                        $qiData = [
                                            'id' => $qi->id,
                                            'name' => $qi->item_name,
                                            'price' => (float) ($qi->selling_price ?? 0),
                                            'hsn' => $qi->hsn,
                                            'gst_rate' => (float) ($qi->gst_rate ?? 0),
                                            'gst_status' => $qi->gst_status ?? 'excluding',
                                            'unit' => $qi->unit,
                                        ];
                                    @endphp
                                    <div class="quick-item" data-item='@json($qiData)'>
                                        <div class="font-weight-bold text-truncate">{{ $qi->item_name }}</div>
                                        <div class="text-muted">₹{{ number_format((float) ($qi->selling_price ?? 0), 2) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: cart + payment -->
            <div class="pos-right">
                <div class="pos-card">
                    <div class="pc-hd">
                        <span>🧾 Current Sale</span>
                        @if (hasPermission('pos_billing', 'resume'))
                            <button type="button" class="btn btn-xs btn-outline-secondary" id="btn-held">Held Bills <span class="badge badge-primary" id="held-count">{{ count($heldBills) }}</span></button>
                        @endif
                    </div>
                    <div class="pc-bd">
                        @if ($branchLocked)
                            <input type="hidden" id="pos-branch" value="{{ $myBranchId }}">
                            <div class="rp-mini mb-2">🏬 Billing at <b>{{ optional($branches->firstWhere('id', $myBranchId))->name }}</b></div>
                        @elseif ($branches->count())
                            <div class="form-group mb-2">
                                <select id="pos-branch" class="form-control" required>
                                    <option value="">🏬 Select branch *</option>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b->id }}" {{ $defaultBranchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" id="pos-branch" value="">
                        @endif

                        <div class="cust-wrap">
                            <input type="hidden" id="pos-customer" value="0">
                            <input type="text" id="cust-search" class="form-control" autocomplete="off"
                                placeholder="👤 Walk-in Customer — type name/phone to link">
                            <div class="pos-results" id="cust-results" style="display:none; position:absolute; z-index:20; width:100%;"></div>
                            <div id="cust-info" class="small text-muted mt-1" style="display:none;"></div>
                        </div>

                        <div id="held-panel" class="pos-results mb-2" style="display:none;">
                            @forelse ($heldBills as $h)
                                <div class="res-row">
                                    <span><b>{{ $h['hold_code'] }}</b> · {{ $h['customer'] }} · {{ $h['item_count'] }} items · {{ $h['held_at'] }}</span>
                                    <span>
                                        @if (hasPermission('pos_billing', 'resume'))
                                            <a class="btn btn-sm btn-primary mr-2" style="cursor:pointer" onclick="resumeHeld({{ $h['id'] }})">Resume</a>
                                        @endif
                                        @if (hasPermission('pos_billing', 'hold'))
                                            <a class="text-danger" style="font-size:16px;cursor:pointer" onclick="deleteHeld({{ $h['id'] }})"><i class='tio-delete'></i></a>
                                        @endif
                                    </span>
                                </div>
                            @empty
                                <div class="res-row text-muted">No held bills</div>
                            @endforelse
                        </div>

                        <table class="table cart-table">
                            <thead>
                                <tr>
                                    <th>Item</th><th width="120">Qty</th><th width="70" class="text-right">Rate</th><th width="80" class="text-right">Total</th><th width="28"></th>
                                </tr>
                            </thead>
                            <tbody id="cart-body">
                                <tr id="cart-empty"><td colspan="5" class="text-center text-muted py-4">🛒 Cart is empty — scan or pick an item</td></tr>
                            </tbody>
                        </table>

                        <div class="totals">
                            <div class="totals-row"><span>Subtotal (taxable)</span><span>₹<span id="t-subtotal">0.00</span></span></div>
                            <div class="totals-row"><span>GST</span><span>₹<span id="t-tax">0.00</span></span></div>
                            @if (hasPermission('pos_bill_discount', 'apply'))
                                <div class="totals-row align-items-center">
                                    <span>Bill Discount</span>
                                    <span>₹<input type="number" id="bill-discount" value="0" min="0" step="0.01" style="width:90px" class="form-control form-control-sm d-inline-block"></span>
                                </div>
                            @else
                                <input type="hidden" id="bill-discount" value="0">
                            @endif
                        </div>
                        <div class="grand-bar"><span class="lbl">Total Payable</span><span class="val">₹<span id="t-grand">0.00</span></span></div>

                        <div class="pay-head">💳 Payment</div>

                        <div class="denoms" id="denoms">
                        <button type="button" class="btn btn-sm btn-outline-secondary denom" data-d="500">₹500</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary denom" data-d="200">₹200</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary denom" data-d="100">₹100</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary denom" data-d="50">₹50</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary denom" data-d="20">₹20</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="denom-exact">Exact ₹</button>
                    </div>

                    <div id="pay-legs">
                        <div class="pay-leg">
                            <select class="form-control form-control-sm pay-mode">
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="card">Credit Card</option>
                                <option value="debit">Debit Card</option>
                                <option value="wallet">Wallet</option>
                            </select>
                            <input type="number" class="form-control form-control-sm pay-amount" placeholder="Amount" step="0.01" data-auto="1">
                            <input type="text" class="form-control form-control-sm pay-ref" placeholder="Ref (optional)">
                            <input type="text" class="form-control form-control-sm pay-approval" placeholder="Last 4 / Approval" style="display:none">
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link p-0" id="add-leg">+ Add payment</button>

                    <div id="upi-qr" class="text-center my-2" style="display:none;">
                        <img id="upi-qr-img" alt="UPI QR" style="width:150px;height:150px;border:1px solid #eee;border-radius:8px">
                        <div class="small text-muted mt-1" id="upi-qr-note">Scan to pay via any UPI app</div>
                    </div>

                    <div class="totals-row mt-1"><span>Tendered</span><span>₹<span id="t-paid">0.00</span></span></div>
                    <div class="totals-row" id="change-row" style="display:none;color:#1e7e34;font-weight:600;"><span>Change to return</span><span>₹<span id="t-change">0.00</span></span></div>
                    <div class="totals-row" id="due-row" style="display:none;color:#c0392b;"><span>Balance (credit)</span><span>₹<span id="t-due">0.00</span></span></div>

                        <div class="d-flex gap-2 mt-3">
                            @if (hasPermission('pos_billing', 'hold'))
                                <button type="button" class="btn btn-outline-warning btn-lg" id="btn-hold" style="flex:0 0 32%;">Hold</button>
                            @endif
                            @if (hasPermission('pos_billing', 'create'))
                                <button type="button" class="btn btn--primary btn-lg" id="btn-finalize" style="flex:1;">
                                    Finalize &amp; Print (F12)
                                </button>
                            @endif
                        </div>
                    </div>{{-- /pc-bd --}}
                </div>{{-- /pos-card --}}
            </div>{{-- /pos-right --}}
        </div>{{-- /pos-wrap --}}
    </div>
@endsection

@push('script')
    <script>
        const POS = {
            products: "{{ route('vendor.retail-pos.products') }}",
            finalize: "{{ route('vendor.retail-pos.finalize') }}",
            customers: "{{ route('vendor.retail-pos.customers') }}",
            hold: "{{ route('vendor.retail-pos.hold') }}",
            defaultBranch: "{{ route('vendor.retail-pos.default-branch') }}",
            held: "{{ route('vendor.retail-pos.held') }}",
            resumeUrl: "{{ route('vendor.retail-pos.resume', ['id' => '__ID__']) }}",
            heldDelUrl: "{{ route('vendor.retail-pos.held.delete', ['id' => '__ID__']) }}",
            csrf: "{{ csrf_token() }}",
            cart: [],
            holdId: null,
            upiId: @json($upiId ?? null),
            storeName: @json($storeName ?? 'Store'),
            canHold: @json(hasPermission('pos_billing', 'hold')),
            canResume: @json(hasPermission('pos_billing', 'resume')),
            canCreate: @json(hasPermission('pos_billing', 'create')),
        };

        // Hardware desktop agent (Electron/localhost bridge). All calls fail silently
        // if the agent isn't running — the browser thermal window remains the fallback.
        // Contract documented in app/Modules/PosRetail/HARDWARE_AGENT.md
        const POSAgent = {
            base: 'http://localhost:9100',
            call: function (path, body) {
                return fetch(this.base + path, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body || {}),
                }).then(r => r.json()).catch(() => null);
            },
            openDrawer: function () { return this.call('/drawer/open'); },
            printReceipt: function (url) { return this.call('/print', { url: url, format: '80mm' }); },
            readScale: function () { return this.call('/scale/read').then(r => r && r.weight); },
            afterSale: function (d, cashUsed) {
                if (cashUsed) this.openDrawer();
                if (d.thermal_url) this.printReceipt(d.thermal_url);
            },
        };

        function money(n) { return (Math.round(n * 100) / 100).toFixed(2); }
        function posBranch() { const el = document.getElementById('pos-branch'); return el ? (el.value || '') : ''; }
        function branchMissing() { const el = document.getElementById('pos-branch'); return el && el.tagName === 'SELECT' && !el.value; }

        function addToCart(item) {
            if (window.toastr) {
                if (item.expiry_warn) toastr.warning(item.name + ' expires ' + (item.expiry || 'soon'), 'Expiry warning');
                if (item.low_stock) toastr.warning(item.name + ' is out of stock', 'Stock');
            }
            let line = POS.cart.find(l => l.id == item.id);
            if (line) { line.qty += 1; }
            else {
                if (POS.cart.length >= 500) { (window.toastr ? toastr.error : alert)('Maximum 500 line items per bill'); return; }
                POS.cart.push({
                    id: item.id, name: item.name, price: parseFloat(item.price) || 0,
                    qty: 1, discount: 0, hsn: item.hsn || '',
                    gst_rate: parseFloat(item.gst_rate) || 0, gst_status: item.gst_status || 'excluding',
                });
            }
            renderCart();
        }

        // Weighing scale → set this line's qty from the connected scale (loose items).
        function weighLine(i) {
            POSAgent.readScale().then(w => {
                if (w && w > 0) { POS.cart[i].qty = Math.round(w * 1000) / 1000; renderCart(); }
                else { (window.toastr ? toastr.error : alert)('Scale not connected'); }
            });
        }

        function renderCart() {
            const body = document.getElementById('cart-body');
            if (!POS.cart.length) {
                body.innerHTML = '<tr id="cart-empty"><td colspan="5" class="text-center text-muted py-3">Cart is empty</td></tr>';
            } else {
                body.innerHTML = POS.cart.map((l, i) => `
                    <tr>
                        <td>
                            <div class="cart-name">${l.name}</div>
                            ${l.discount > 0 ? `<div class="cart-sub">− ₹${money(l.discount)} off</div>` : ''}
                        </td>
                        <td>
                            <div class="qty-stepper">
                                <button class="qty-btn" onclick="chQty(${i},-1)">−</button>
                                <span>${l.qty}</span>
                                <button class="qty-btn" onclick="chQty(${i},1)">+</button>
                                <button class="qty-btn" title="Weigh loose item" onclick="weighLine(${i})">⚖</button>
                            </div>
                        </td>
                        <td class="text-right">${money(l.price)}</td>
                        <td class="text-right font-weight-bold">${money(l.price * l.qty - l.discount)}</td>
                        <td class="text-right"><a class="cart-del" title="Remove" onclick="rmLine(${i})"><i class="tio-delete"></i></a></td>
                    </tr>`).join('');
            }
            recalc();
        }

        function chQty(i, d) { POS.cart[i].qty = Math.max(1, POS.cart[i].qty + d); renderCart(); }
        function rmLine(i) { POS.cart.splice(i, 1); renderCart(); }

        function recalc() {
            let subtotal = 0, tax = 0;
            POS.cart.forEach(l => {
                let gross = Math.max(0, l.price * l.qty - l.discount);
                if (l.gst_status === 'including') {
                    let taxable = l.gst_rate > 0 ? gross / (1 + l.gst_rate / 100) : gross;
                    subtotal += taxable; tax += gross - taxable;
                } else {
                    subtotal += gross; tax += gross * l.gst_rate / 100;
                }
            });
            let disc = parseFloat(document.getElementById('bill-discount').value) || 0;
            let grand = Math.round(Math.max(0, subtotal + tax - disc));
            document.getElementById('t-subtotal').textContent = money(subtotal);
            document.getElementById('t-tax').textContent = money(tax);
            document.getElementById('t-grand').textContent = money(grand);

            // Default the amount to the bill total (single leg, until manually changed).
            const legEls = document.querySelectorAll('.pay-leg');
            if (legEls.length === 1) {
                const amt0 = legEls[0].querySelector('.pay-amount');
                if (amt0.dataset.auto === '1') amt0.value = grand > 0 ? grand : '';
            }

            let paid = 0;
            document.querySelectorAll('.pay-leg .pay-amount').forEach(a => paid += parseFloat(a.value) || 0);
            document.getElementById('t-paid').textContent = money(paid);
            let diff = Math.round((grand - paid) * 100) / 100;
            const dueRow = document.getElementById('due-row');
            const changeRow = document.getElementById('change-row');
            if (paid > 0 && diff > 0.01) { dueRow.style.display = 'flex'; document.getElementById('t-due').textContent = money(diff); }
            else { dueRow.style.display = 'none'; }
            if (diff < -0.01) { changeRow.style.display = 'flex'; document.getElementById('t-change').textContent = money(-diff); }
            else { changeRow.style.display = 'none'; }
            window.__posGrand = grand;
            if (typeof updateUpiQr === 'function') updateUpiQr();
        }

        // ── Search / scan ──
        let searchTimer = null;
        const searchBox = document.getElementById('pos-search');
        const resultsBox = document.getElementById('pos-results');

        searchBox.addEventListener('input', function () {
            clearTimeout(searchTimer);
            const q = this.value.trim();
            if (q.length < 2) { resultsBox.style.display = 'none'; return; }
            searchTimer = setTimeout(() => lookup(q, false), 200);
        });

        searchBox.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); lookup(this.value.trim(), true); }
        });

        function lookup(q, exact) {
            if (!q) return;
            fetch(POS.products + '?q=' + encodeURIComponent(q) + (exact ? '&exact=1' : '') + '&branch=' + encodeURIComponent(posBranch()))
                .then(r => r.json())
                .then(d => {
                    if (exact && d.items.length === 1) {
                        addToCart(d.items[0]); searchBox.value = ''; resultsBox.style.display = 'none'; return;
                    }
                    if (!d.items.length) { resultsBox.innerHTML = '<div class="res-row text-muted">No products</div>'; resultsBox.style.display = 'block'; return; }
                    resultsBox.innerHTML = d.items.map(it =>
                        `<div class="res-row" data-it='${JSON.stringify(it)}'>
                            <span>${it.name} <small class="text-muted">${it.sku || ''}</small></span>
                            <span>₹${money(it.price)} · stk ${it.stock}</span>
                        </div>`).join('');
                    resultsBox.style.display = 'block';
                    resultsBox.querySelectorAll('.res-row[data-it]').forEach(row => {
                        row.addEventListener('click', () => {
                            addToCart(JSON.parse(row.getAttribute('data-it')));
                            searchBox.value = ''; resultsBox.style.display = 'none'; searchBox.focus();
                        });
                    });
                });
        }

        // Quick-grid: delegated click (works for server-rendered Popular + AJAX category items).
        const quickGrid = document.getElementById('quick-grid');
        const popularHtml = quickGrid.innerHTML; // remember the Popular grid
        quickGrid.addEventListener('click', function (e) {
            const card = e.target.closest('.quick-item');
            if (card) addToCart(JSON.parse(card.getAttribute('data-item')));
        });

        // Category tabs → load that category's items into the grid.
        document.querySelectorAll('.cat-tab').forEach(tab => tab.addEventListener('click', function () {
            document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const cat = this.getAttribute('data-cat');
            if (!cat) { quickGrid.innerHTML = popularHtml; return; }
            quickGrid.innerHTML = '<div class="text-muted p-2">Loading…</div>';
            fetch(POS.products + '?category=' + encodeURIComponent(cat) + '&branch=' + encodeURIComponent(posBranch()))
                .then(r => r.json())
                .then(d => {
                    if (!d.items.length) { quickGrid.innerHTML = '<div class="text-muted p-2">No items in this category.</div>'; return; }
                    quickGrid.innerHTML = d.items.map(it => {
                        const data = { id: it.id, name: it.name, price: it.price, hsn: it.hsn, gst_rate: it.gst_rate, gst_status: it.gst_status, unit: it.unit, expiry_warn: it.expiry_warn, expiry: it.expiry, low_stock: it.low_stock };
                        return '<div class="quick-item" data-item=\'' + JSON.stringify(data).replace(/'/g, '&#39;') + '\'>' +
                            '<div class="font-weight-bold text-truncate">' + it.name + '</div>' +
                            '<div class="text-muted">₹' + money(it.price) + '</div></div>';
                    }).join('');
                });
        }));

        // Default tab = All → load all items on open.
        const allTab = document.querySelector('.cat-tab[data-cat="all"]');
        if (allTab) allTab.click();

        // Owner switches branch → reload the grid so stock reflects that branch.
        const branchSel = document.getElementById('pos-branch');
        if (branchSel && branchSel.tagName === 'SELECT') {
            branchSel.addEventListener('change', function () {
                // Remember this branch for next time.
                if (this.value) fetch(POS.defaultBranch, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': POS.csrf },
                    body: new URLSearchParams({ branch_id: this.value }),
                }).catch(() => {});
                const active = document.querySelector('.cat-tab.active') || allTab;
                if (active) active.click();
            });
        }

        document.getElementById('bill-discount').addEventListener('input', recalc);

        // ── Payment legs ──
        document.getElementById('add-leg').addEventListener('click', function () {
            // Splitting: the first leg becomes manual so its auto-total doesn't fight the split.
            const first = document.querySelector('.pay-leg .pay-amount');
            if (first) first.dataset.auto = '0';
            const leg = document.querySelector('.pay-leg').cloneNode(true);
            leg.querySelectorAll('input').forEach(i => i.value = '');
            leg.querySelector('.pay-mode').value = 'cash';
            leg.querySelector('.pay-amount').dataset.auto = '0';
            leg.querySelector('.pay-approval').style.display = 'none';
            document.getElementById('pay-legs').appendChild(leg);
        });
        document.getElementById('pay-legs').addEventListener('input', e => {
            if (e.target.classList.contains('pay-amount')) {
                e.target.dataset.auto = '0'; // user typed → stop auto-filling
                recalc();
            }
        });

        // Mode change → card capture field + UPI QR.
        document.getElementById('pay-legs').addEventListener('change', e => {
            if (!e.target.classList.contains('pay-mode')) return;
            const leg = e.target.closest('.pay-leg');
            const mode = e.target.value;
            const appr = leg.querySelector('.pay-approval');
            const ref = leg.querySelector('.pay-ref');
            appr.style.display = (mode === 'card' || mode === 'debit') ? '' : 'none';
            ref.placeholder = mode === 'upi' ? 'UPI txn ref' : (mode === 'card' || mode === 'debit' ? 'Card network' : 'Ref (optional)');
            updateUpiQr();
        });

        // Cash denomination quick-keys → fill the first cash leg.
        function firstAmountInput() {
            let leg = [...document.querySelectorAll('.pay-leg')].find(l => l.querySelector('.pay-mode').value === 'cash')
                || document.querySelector('.pay-leg');
            return leg.querySelector('.pay-amount');
        }
        document.querySelectorAll('.denom').forEach(b => b.addEventListener('click', function () {
            const inp = firstAmountInput();
            const base = inp.dataset.auto === '1' ? 0 : (parseFloat(inp.value) || 0); // start fresh from auto
            inp.value = base + parseFloat(this.dataset.d);
            inp.dataset.auto = '0';
            recalc();
        }));
        document.getElementById('denom-exact').addEventListener('click', function () {
            const inp = firstAmountInput();
            inp.value = window.__posGrand || 0;
            inp.dataset.auto = '1'; // keep tracking the exact total
            recalc();
        });

        // UPI QR — render store QR (upi:// intent) for the current bill total.
        function updateUpiQr() {
            const box = document.getElementById('upi-qr');
            const hasUpiLeg = [...document.querySelectorAll('.pay-mode')].some(s => s.value === 'upi');
            if (!hasUpiLeg) { box.style.display = 'none'; return; }
            const amt = window.__posGrand || 0;
            const note = document.getElementById('upi-qr-note');
            if (!POS.upiId) {
                box.style.display = 'block';
                document.getElementById('upi-qr-img').style.display = 'none';
                note.textContent = 'Set the store UPI ID under Terminals to show a QR.';
                return;
            }
            const intent = 'upi://pay?pa=' + encodeURIComponent(POS.upiId) + '&pn=' + encodeURIComponent(POS.storeName) + '&am=' + amt + '&cu=INR';
            document.getElementById('upi-qr-img').style.display = '';
            document.getElementById('upi-qr-img').src = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(intent);
            note.textContent = 'Scan to pay ₹' + money(amt) + ' · ' + POS.upiId;
            box.style.display = 'block';
        }

        // ── Customer search / link ──
        let custTimer = null;
        const custBox = document.getElementById('cust-search');
        const custResults = document.getElementById('cust-results');
        const custInfo = document.getElementById('cust-info');

        custBox.addEventListener('input', function () {
            clearTimeout(custTimer);
            const q = this.value.trim();
            document.getElementById('pos-customer').value = 0;
            custInfo.style.display = 'none';
            if (q.length < 2) { custResults.style.display = 'none'; return; }
            custTimer = setTimeout(() => {
                fetch(POS.customers + '?q=' + encodeURIComponent(q)).then(r => r.json()).then(d => {
                    if (!d.customers.length) { custResults.innerHTML = '<div class="res-row text-muted">No customers</div>'; }
                    else custResults.innerHTML = d.customers.map(c =>
                        `<div class="res-row" data-c='${JSON.stringify(c)}'>
                            <span>${c.name} <small class="text-muted">${c.phone || ''}</small></span>
                            <span>★${c.loyalty_points} · ₹${money(c.wallet_balance)}</span>
                        </div>`).join('');
                    custResults.style.display = 'block';
                    custResults.querySelectorAll('.res-row[data-c]').forEach(row =>
                        row.addEventListener('click', () => selectCustomer(JSON.parse(row.getAttribute('data-c')))));
                });
            }, 200);
        });

        function selectCustomer(c) {
            document.getElementById('pos-customer').value = c.id;
            custBox.value = c.name + (c.phone ? ' · ' + c.phone : '');
            custResults.style.display = 'none';
            custInfo.style.display = 'block';
            custInfo.innerHTML = `Points: <b>★${c.loyalty_points}</b> · Wallet: <b>₹${money(c.wallet_balance)}</b> · Credit: <b>₹${money(c.credit_balance)}</b> / ₹${money(c.credit_limit)}`;
        }

        // ── Hold & Resume ──
        function refreshHeld() {
            fetch(POS.held).then(r => r.json()).then(d => {
                document.getElementById('held-count').textContent = d.held.length;
                const panel = document.getElementById('held-panel');
                if (!d.held.length) { panel.innerHTML = '<div class="res-row text-muted">No held bills</div>'; }
                else panel.innerHTML = d.held.map(h =>
                    `<div class="res-row">
                        <span><b>${h.hold_code}</b> · ${h.customer} · ${h.item_count} items · ${h.held_at}</span>
                        <span>
                            ${POS.canResume ? `<a class="btn btn-sm btn-primary mr-2" style="cursor:pointer" onclick="resumeHeld(${h.id})">Resume</a>` : ''}
                            ${POS.canHold ? `<a class="text-danger" style="    font-size: 16px;cursor:pointer" onclick="deleteHeld(${h.id})"><i class='tio-delete'></i></a>` : ''}
                        </span>
                    </div>`).join('');
            });
        }

        document.getElementById('btn-held')?.addEventListener('click', function () {
            const p = document.getElementById('held-panel');
            p.style.display = p.style.display === 'none' ? 'block' : 'none';
            if (p.style.display === 'block') refreshHeld();
        });

        document.getElementById('btn-hold')?.addEventListener('click', function () {
            if (!POS.cart.length) { (window.toastr ? toastr.error : alert)('Nothing to hold'); return; }
            if (branchMissing()) { (window.toastr ? toastr.error : alert)('Please select a branch'); return; }
            fetch(POS.hold, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': POS.csrf },
                body: new URLSearchParams({
                    items: JSON.stringify(POS.cart),
                    customer_id: document.getElementById('pos-customer').value || 0,
                    bill_discount: document.getElementById('bill-discount').value || 0,
                }),
            }).then(r => r.json()).then(d => {
                if (!d.status) { (window.toastr ? toastr.error : alert)(d.msg || 'Failed'); return; }
                if (window.toastr) toastr.success('Held as ' + d.hold_code);
                resetSale(); refreshHeld();
            });
        });

        function resumeHeld(id) {
            fetch(POS.resumeUrl.replace('__ID__', id), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(r => r.json()).then(d => {
                if (!d.status) return;
                POS.cart = (d.items || []).map(l => ({
                    id: l.id, name: l.name, price: parseFloat(l.price) || 0, qty: parseFloat(l.qty) || 1,
                    discount: parseFloat(l.discount) || 0, hsn: l.hsn || '',
                    gst_rate: parseFloat(l.gst_rate) || 0, gst_status: l.gst_status || 'excluding',
                }));
                POS.holdId = d.hold_id;
                document.getElementById('pos-customer').value = d.customer_id || 0;
                document.getElementById('bill-discount').value = (d.meta && d.meta.bill_discount) || 0;
                document.getElementById('held-panel').style.display = 'none';
                renderCart();
            });
        }

        function deleteHeld(id) {
            fetch(POS.heldDelUrl.replace('__ID__', id), {
                method: 'POST', headers: { 'X-CSRF-TOKEN': POS.csrf },
            }).then(() => refreshHeld());
        }

        function resetSale() {
            POS.cart = []; POS.holdId = null;
            document.getElementById('bill-discount').value = 0;
            document.getElementById('pos-customer').value = 0;
            custBox.value = ''; custInfo.style.display = 'none';
            // Drop extra split legs, keep one, restore auto-fill.
            const legs = document.querySelectorAll('.pay-leg');
            legs.forEach((l, i) => { if (i > 0) l.remove(); });
            const first = document.querySelector('.pay-leg');
            first.querySelector('.pay-mode').value = 'cash';
            first.querySelectorAll('input').forEach(i => i.value = '');
            first.querySelector('.pay-approval').style.display = 'none';
            first.querySelector('.pay-amount').dataset.auto = '1';
            renderCart();
        }

        // ── Finalize ──
        function finalize(allowOos) {
            if (!POS.cart.length) { toastr ? toastr.error('Cart is empty') : alert('Cart is empty'); return; }
            if (branchMissing()) { (window.toastr ? toastr.error : alert)('Please select a branch'); document.getElementById('pos-branch').focus(); return; }
            const payments = [];
            document.querySelectorAll('.pay-leg').forEach(leg => {
                const amt = parseFloat(leg.querySelector('.pay-amount').value) || 0;
                if (amt > 0) {
                    const mode = leg.querySelector('.pay-mode').value;
                    payments.push({
                        mode: mode,
                        amount: amt,
                        reference: leg.querySelector('.pay-ref').value || null,
                        sub_type: (mode === 'card' || mode === 'debit') ? (leg.querySelector('.pay-ref').value || null) : null,
                        approval_code: leg.querySelector('.pay-approval') ? (leg.querySelector('.pay-approval').value || null) : null,
                    });
                }
            });
            const btn = document.getElementById('btn-finalize');
            if (btn) { btn.disabled = true; btn.textContent = 'Processing…'; }

            fetch(POS.finalize, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': POS.csrf },
                body: new URLSearchParams({
                    items: JSON.stringify(POS.cart),
                    payments: JSON.stringify(payments),
                    customer_id: document.getElementById('pos-customer').value || 0,
                    bill_discount: document.getElementById('bill-discount').value || 0,
                    branch_id: posBranch(),
                    hold_id: POS.holdId || '',
                    allow_oos: allowOos ? 1 : '',
                }),
            })
                .then(r => r.json())
                .then(d => {
                    if (btn) { btn.disabled = false; btn.textContent = 'Finalize & Print (F12)'; }
                    if (!d.status) {
                        if (d.oos && !allowOos && confirm((d.msg || 'Insufficient stock') + '\n\nOverride with manager approval?')) {
                            finalize(true); return;
                        }
                        (window.toastr ? toastr.error : alert)(d.msg || 'Failed'); return;
                    }
                    if (window.toastr) {
                        let m = d.invoice_id;
                        if (d.change > 0) m += ' · Change ₹' + money(d.change);
                        if (d.due > 0) m += ' · Credit ₹' + money(d.due);
                        if (d.points_earned > 0) m += ' · +' + d.points_earned + ' pts';
                        toastr.success(m);
                    }
                    if (d.thermal_url) window.open(d.thermal_url, '_blank');
                    POSAgent.afterSale(d, payments.some(p => p.mode === 'cash') || payments.length === 0);
                    resetSale(); refreshHeld(); searchBox.focus();
                })
                .catch(() => { if (btn) { btn.disabled = false; btn.textContent = 'Finalize & Print (F12)'; } alert('Error'); });
        }

        document.getElementById('btn-finalize')?.addEventListener('click', finalize);
        document.addEventListener('keydown', e => { if (e.key === 'F12' && POS.canCreate) { e.preventDefault(); finalize(); } });
        // Held bills render server-side on load; JS only refreshes after hold / resume / finalize.

        // ── Camera barcode scanner (for phones/tablets/webcam, no USB scanner) ──
        (function () {
            const overlay = document.getElementById('cam-scan');
            const msg = document.getElementById('cam-msg');
            let cam = null, lastCode = '', lastAt = 0;

            function open() {
                if (typeof Html5Qrcode === 'undefined') { (window.toastr ? toastr.error : alert)('Scanner library not loaded'); return; }
                overlay.style.display = 'flex';
                cam = new Html5Qrcode('cam-reader');
                cam.start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, onScan, () => {})
                    .catch(e => { msg.textContent = 'Camera unavailable: ' + e; });
            }
            function close() {
                overlay.style.display = 'none';
                if (cam) { cam.stop().then(() => cam.clear()).catch(() => {}); cam = null; }
            }
            function onScan(code) {
                const now = Date.now();
                if (code === lastCode && now - lastAt < 1500) return; // de-dupe rapid frames
                lastCode = code; lastAt = now;
                lookup(code, true);                 // exact match → adds to cart
                msg.textContent = 'Added: ' + code;
                if (navigator.vibrate) navigator.vibrate(60);
            }

            document.getElementById('btn-cam-scan').addEventListener('click', open);
            document.getElementById('cam-close').addEventListener('click', close);
            overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
        })();
    </script>
@endpush
