@extends('layouts.vendor.app')
@section('title', 'Pharmacy — Walk-in Sale')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .wk-grid { display:grid; grid-template-columns: 1.7fr 1fr; gap:20px; align-items:start; }
        @media (max-width: 1100px){ .wk-grid { grid-template-columns:1fr; } }
        .wk-search-wrap { position:relative; }
        .wk-results { position:absolute; z-index:50; left:0; right:0; top:100%; background:#fff; border:1px solid #c8d2e0; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.12); max-height:280px; overflow:auto; display:none; }
        .wk-results .wk-opt { padding:8px 12px; cursor:pointer; font-size:13px; border-bottom:1px solid #dfe6ee; }
        .wk-results .wk-opt:hover { background:#f1f5f9; }
        .wk-opt .wk-banned { color:#b91c1c; font-weight:700; font-size:10px; margin-left:6px; }
        .wk-cart td, .wk-cart th { vertical-align:middle; }
        .wk-empty { text-align:center; color:#9aa1ab; padding:26px 16px; }
        .wk-banner { background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:10px 14px; margin-bottom:14px; color:#b91c1c; font-size:12.5px; font-weight:600; display:none; }
        .wk-summary-row { display:flex; justify-content:space-between; padding:6px 0; font-size:13px; }
        .wk-summary-row.total { border-top:1px solid #c8d2e0; margin-top:6px; padding-top:10px; font-weight:800; font-size:16px; }

        /* Phones. This is the pharmacy's landing screen now, and a counter sale gets rung up
           one-handed — so the cart stops being a five-column table (330px of fixed track before
           the medicine name even starts) and each line becomes a card. The name leads, price and
           qty sit side by side as the two things actually typed, and remove is a full-width
           control rather than a 40px target next to them. */
        @media (max-width: 575.98px) {
            .pharmacy-page-content { padding: 12px !important; }
            .wk-grid { gap: 12px; }

            .wk-cart, .wk-cart tbody, .wk-cart tr, .wk-cart td { display: block; width: 100%; }
            .wk-cart thead { display: none; }
            .table-responsive { overflow: visible; }

            .wk-cart tr.wk-line {
                border: 1px solid #c8d2e0;
                border-radius: 10px;
                padding: 10px 12px;
                margin-bottom: 10px;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            .wk-cart tr.wk-line > td { border: none !important; padding: 0 !important; }
            .wk-cart td.wk-c-name { grid-column: 1 / -1; font-weight: 700; font-size: 13.5px; }
            .wk-cart td[data-label]::before {
                content: attr(data-label);
                display: block;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: #94a3b8;
                margin-bottom: 3px;
            }
            .wk-cart td.wk-c-amt {
                grid-column: 1 / -1;
                text-align: left !important;
                font-weight: 700;
                border-top: 1px solid #dfe6ee !important;
                padding-top: 8px !important;
            }
            .wk-cart td.wk-c-rm { grid-column: 1 / -1; }
            .wk-cart td.wk-c-rm .btn { width: 100%; padding: 8px; }

            /* The empty-state row is one cell and must not be gridded into a card. */
            .wk-cart tr#wkEmptyRow { display: block; border: none; padding: 0; }
            .wk-cart tr#wkEmptyRow td { display: block; border: none !important; }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        @include('hmis::vendor-views.partials._pharmacy_header')

        <div class="pharmacy-page-content">
            <form action="{{ route('vendor.pharmacy.walkin.store') }}" method="post" id="walkinForm">
                @csrf
                <div class="wk-grid">

                    {{-- LEFT: medicine picker + cart --}}
                    <div class="card">
                        <div class="card-header"><h3 class="mb-0" style="font-size:15px; font-weight:700;">Walk-in Sale — Cart</h3></div>
                        <div class="card-body">
                            @if(!empty($dispenseToBearer))
                                <div class="alert py-2 px-3" style="background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; font-size:12.5px;">
                                    <i class="tio-info-outined mr-1"></i> Store rule: medicines may be given to <strong>whoever brings the prescription</strong>.
                                </div>
                            @endif
                            <div id="wkBanner" class="wk-banner"></div>

                            <div class="wk-search-wrap mb-3">
                                <input type="text" id="wkSearch" class="form-control" autocomplete="off" placeholder="Search medicine by name or SKU to add…">
                                <div id="wkResults" class="wk-results"></div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered wk-cart mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Medicine</th>
                                            <th style="width:90px;">Price (₹)</th>
                                            <th style="width:90px;">Qty</th>
                                            <th style="width:110px;" class="text-right">Amount</th>
                                            <th style="width:40px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="wkCartBody">
                                        <tr id="wkEmptyRow"><td colspan="5"><div class="wk-empty">No medicines added yet. Use the search box above.</div></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: customer, prescription, payment --}}
                    <div>
                        <div class="card">
                            <div class="card-header"><h3 class="mb-0" style="font-size:14px; font-weight:700;">Customer</h3></div>
                            <div class="card-body">
                                <div class="form-group"><label>Customer Name</label><input type="text" name="customer_name" class="form-control" placeholder="Walk-in customer"></div>
                                <div class="form-group mb-0"><label>Phone</label><input type="text" name="customer_phone" class="form-control" placeholder="Optional"></div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><h3 class="mb-0" style="font-size:14px; font-weight:700;">Prescription Details</h3></div>
                            <div class="card-body">
                                <div class="form-group"><label>Prescribing Doctor</label><input type="text" name="rx_doctor" class="form-control" placeholder="Dr. name (as on prescription)"></div>
                                <div class="form-group"><label>Prescription No. / Ref</label><input type="text" name="rx_number" class="form-control" placeholder="Optional"></div>
                                <div class="form-group mb-0"><label>Notes</label><textarea name="rx_notes" class="form-control" rows="2" placeholder="Diagnosis, advice, etc. (optional)"></textarea></div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><h3 class="mb-0" style="font-size:14px; font-weight:700;">Payment</h3></div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group col-6"><label>Payment Mode <span class="text-danger">*</span></label>
                                        <select name="payment_mode" id="wkPayMode" class="form-control" required>
                                            <option value="Cash">Cash</option>
                                            <option value="Online">Online</option>
                                            <option value="Card">Card</option>
                                            <option value="UPI">UPI</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-6"><label>Discount (₹)</label><input type="number" step="0.01" min="0" name="discount" id="wkDiscount" class="form-control" value="0"></div>
                                </div>
                                <div class="form-group mb-0" id="wkTxnWrap" style="display:none;">
                                    <label>Transaction ID <span class="text-danger">*</span></label>
                                    <input type="text" name="transaction_id" id="wkTxnId" class="form-control" placeholder="UPI / card / online reference">
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="wk-summary-row"><span>Subtotal</span><span id="wkSubtotal">{{ \App\CentralLogics\Helpers::format_currency(0) }}</span></div>
                                <div class="wk-summary-row"><span>Discount</span><span id="wkDiscShow">- {{ \App\CentralLogics\Helpers::format_currency(0) }}</span></div>
                                <div class="wk-summary-row total"><span>Payable</span><span id="wkPayable">{{ \App\CentralLogics\Helpers::format_currency(0) }}</span></div>
                                <button type="submit" class="btn btn--primary btn-block mt-3" id="wkSubmit" disabled>
                                    <i class="tio-shopping-cart mr-1"></i> Complete Sale
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script_2')
    @php
        $wkItems = $items->map(fn($i) => [
            'id' => $i->id, 'name' => $i->item_name, 'brand' => $i->brand, 'sku' => $i->sku_id,
            'price' => (float) ($i->selling_price ?: $i->mrp ?: 0), 'stock' => (float) $i->stock,
            'banned' => (int) ($i->is_banned ?? 0) === 1,
        ])->values();
    @endphp
    <script>
        const WK_ITEMS = @json($wkItems);
        const wkCurrency = (n) => '₹' + (Number(n) || 0).toFixed(2);

        const cart = []; // {id,name,price,qty,banned}
        const searchEl = document.getElementById('wkSearch');
        const resultsEl = document.getElementById('wkResults');
        const bodyEl = document.getElementById('wkCartBody');
        const emptyRow = document.getElementById('wkEmptyRow');
        const bannerEl = document.getElementById('wkBanner');

        searchEl.addEventListener('input', function(){
            const q = this.value.trim().toLowerCase();
            if(!q){ resultsEl.style.display='none'; return; }
            const matches = WK_ITEMS.filter(i =>
                (i.name||'').toLowerCase().includes(q) || (i.sku||'').toLowerCase().includes(q)
            ).slice(0, 20);
            resultsEl.innerHTML = matches.length
                ? matches.map(i => `<div class="wk-opt" data-id="${i.id}">
                        ${i.name} ${i.brand ? '· '+i.brand : ''}
                        <span style="color:#64748b;font-size:11px;">(${wkCurrency(i.price)} · stock ${i.stock})</span>
                        ${i.banned ? '<span class="wk-banned">⛔ BANNED</span>' : ''}
                    </div>`).join('')
                : '<div class="wk-opt text-muted">No match</div>';
            resultsEl.style.display='block';
        });
        document.addEventListener('click', e => { if(!resultsEl.contains(e.target) && e.target!==searchEl) resultsEl.style.display='none'; });

        resultsEl.addEventListener('click', function(e){
            const opt = e.target.closest('.wk-opt'); if(!opt || !opt.dataset.id) return;
            const item = WK_ITEMS.find(i => i.id == opt.dataset.id); if(!item) return;
            const existing = cart.find(c => c.id === item.id);
            if(existing){ existing.qty += 1; }
            else { cart.push({ id:item.id, name:item.name, price:item.price, qty:1, banned:item.banned }); }
            searchEl.value=''; resultsEl.style.display='none';
            render();
        });

        // Rebuilds the cart rows. Only call on add/remove — it replaces the row
        // inputs, so calling it while a field has focus would kill the caret.
        function render(){
            if(emptyRow) emptyRow.style.display = cart.length ? 'none' : '';
            bodyEl.querySelectorAll('.wk-line').forEach(r => r.remove());
            cart.forEach((c, idx) => {
                const tr = document.createElement('tr');
                tr.className = 'wk-line';
                tr.innerHTML = `
                    <td class="wk-c-name">${c.name} ${c.banned ? '<span class="wk-banned">⛔ Banned</span>' : ''}
                        <input type="hidden" name="items[${idx}][id]" value="${c.id}">
                    </td>
                    <td data-label="Price (₹)"><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="items[${idx}][price]" value="${c.price.toFixed(2)}" data-idx="${idx}" data-f="price"></td>
                    <td data-label="Qty"><input type="number" step="1" min="1" class="form-control form-control-sm" name="items[${idx}][qty]" value="${c.qty}" data-idx="${idx}" data-f="qty"></td>
                    <td class="text-right wk-c-amt" data-label="Amount" data-amt="${idx}"></td>
                    <td class="wk-c-rm"><button type="button" class="btn btn-xs btn-outline-danger" data-rm="${idx}"><i class="tio-clear"></i></button></td>`;
                bodyEl.appendChild(tr);
            });
            recalc();
        }

        // Updates amounts, totals and the banner from the cart model without
        // touching the row inputs, so it is safe to run on every keystroke.
        function recalc(){
            let subtotal = 0, hasBanned = false;
            cart.forEach((c, idx) => {
                const amount = c.price * c.qty; subtotal += amount;
                if(c.banned) hasBanned = true;
                const cell = bodyEl.querySelector(`[data-amt="${idx}"]`);
                if(cell) cell.textContent = wkCurrency(amount);
            });

            const discount = parseFloat(document.getElementById('wkDiscount').value) || 0;
            const payable = Math.max(0, subtotal - discount);
            document.getElementById('wkSubtotal').textContent = wkCurrency(subtotal);
            document.getElementById('wkDiscShow').textContent = '- ' + wkCurrency(discount);
            document.getElementById('wkPayable').textContent = wkCurrency(payable);
            document.getElementById('wkSubmit').disabled = cart.length === 0;

            if(hasBanned){
                bannerEl.style.display='block';
                bannerEl.innerHTML = '⛔ This sale contains banned/blocked medicine(s). You can still proceed, but please confirm it is clinically justified.';
            } else { bannerEl.style.display='none'; }
        }

        bodyEl.addEventListener('input', function(e){
            const el = e.target; if(el.dataset.idx === undefined) return;
            const c = cart[+el.dataset.idx]; if(!c) return;
            // Read the raw value while typing — an empty or half-typed field
            // counts as 0/1 for the running total but is left alone on screen.
            if(el.dataset.f === 'price') c.price = parseFloat(el.value) || 0;
            if(el.dataset.f === 'qty')   c.qty   = Math.max(1, parseInt(el.value, 10) || 1);
            recalc();
        });

        // Normalise what the user left behind once they leave the field, so the
        // posted values always match the totals shown.
        bodyEl.addEventListener('change', function(e){
            const el = e.target; if(el.dataset.idx === undefined) return;
            const c = cart[+el.dataset.idx]; if(!c) return;
            if(el.dataset.f === 'price'){ c.price = Math.max(0, parseFloat(el.value) || 0); el.value = c.price.toFixed(2); }
            if(el.dataset.f === 'qty'){ c.qty = Math.max(1, parseInt(el.value, 10) || 1); el.value = c.qty; }
            recalc();
        });

        bodyEl.addEventListener('click', function(e){
            const btn = e.target.closest('[data-rm]'); if(!btn) return;
            cart.splice(+btn.dataset.rm, 1); render();
        });
        document.getElementById('wkDiscount').addEventListener('input', recalc);

        // Payment mode → require transaction id for non-cash.
        const payMode = document.getElementById('wkPayMode');
        const txnWrap = document.getElementById('wkTxnWrap');
        const txnId = document.getElementById('wkTxnId');
        function syncPay(){
            const online = payMode.value.toLowerCase() !== 'cash';
            txnWrap.style.display = online ? '' : 'none';
            txnId.required = online;
            if(!online) txnId.value = '';
        }
        payMode.addEventListener('change', syncPay); syncPay();

        // Billing without a prescription is allowed, but must be confirmed first.
        var wkRxConfirmed = false;
        document.getElementById('walkinForm').addEventListener('submit', function(e){
            if(cart.length === 0){ e.preventDefault(); alert('Add at least one medicine.'); return; }

            var form  = this;
            var rxDoc = (form.querySelector('[name="rx_doctor"]').value || '').trim();
            var rxNum = (form.querySelector('[name="rx_number"]').value || '').trim();
            if(rxDoc !== '' || rxNum !== '' || wkRxConfirmed) return;

            e.preventDefault();
            var proceed = function(){ wkRxConfirmed = true; form.submit(); };

            if(window.Swal){
                Swal.fire({
                    title: 'Bill without prescription?',
                    text: 'No prescribing doctor or prescription number has been entered for this sale.',
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#FC6A57',
                    cancelButtonText: 'Add prescription',
                    confirmButtonText: 'Bill anyway',
                    reverseButtons: true
                }).then(function(result){ if(result.value){ proceed(); } });
            } else if(confirm('No prescription entered for this sale. Generate the bill anyway?')){
                proceed();
            }
        });
    </script>
@endpush
