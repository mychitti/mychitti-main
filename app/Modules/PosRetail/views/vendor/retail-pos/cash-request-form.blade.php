@extends('layouts.vendor.app')

@section('title', 'Cash Flow Request')

@push('css_or_js')
    @include('posretail::vendor.retail-pos._styles')
    <style>
        .cfr { max-width: 920px; margin: 0 auto; }
        .cfr .sec { border: 1px solid var(--line); border-radius: 12px; margin-bottom: 14px; overflow: hidden; background: #fff; }
        .cfr .sec > .hd { background: #f4f6fb; padding: 9px 14px; font-weight: 700; color: #0f3460; font-size: 13.5px; border-bottom: 1px solid var(--line); }
        .cfr .sec > .bd { padding: 14px; }
        .cfr .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .cfr .grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
        .cfr label.fl { font-size: 12px; color: var(--muted); display: block; margin-bottom: 3px; }
        .cfr .req { color: #dc3545; }
        .cfr .opt { display: inline-flex; align-items: center; gap: 6px; margin-right: 16px; font-size: 13px; }
        .cfr .ttl { text-align: center; font-size: 22px; font-weight: 800; color: #0f3460; letter-spacing: .5px; }
        .cfr .denom td, .cfr .denom th { padding: 7px 10px; }
        .cfr .denom input { width: 90px; }
        .cfr .totrow { background: #eef3ff; font-weight: 800; }
        .cfr .foot { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-top: 6px; }
        .cfr .foot .rp-btn { min-width: 130px; justify-content: center; }
    </style>
@endpush

@section('content')
    @php
        $den = $req && $req->denominations ? (json_decode($req->denominations, true) ?: []) : [];
        $purposeLabels = $purposes;
        $statusBadge = ['draft' => 'muted', 'pending' => 'info', 'approved' => 'info', 'received' => 'ok', 'closed' => 'ok', 'rejected' => 'bad'];
        $toValue = $req ? ($req->to_role === 'staff' ? 'staff:' . $req->to_id : ($req->to_role === 'manager' ? 'manager:0' : 'owner:0')) : '';
        $iAmRecipient = $req && (((int) $req->to_id === $meId && $req->to_role === $meRole) || ($req->to_role === 'manager' && $isManager));
        $iAmRequester = $req && ((int) $req->from_id === $meId && $req->from_role === $meRole);
        // Editable when creating, or editing your own still-draft request.
        $editable = !$req || ($req->status === 'draft' && $iAmRequester);
        $ro = $editable ? '' : 'disabled';
    @endphp
 
    <div class="content container-fluid rp">
        <div class="cfr">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <a href="{{ route('vendor.retail-pos.cash-flow') }}" class="rp-btn o sm">← Back</a>
            </div>

            <form method="post" action="{{ route('vendor.retail-pos.cash-flow.save') }}" enctype="multipart/form-data" id="cfrForm">
                @csrf
                <input type="hidden" name="save_mode" id="cfr_save_mode" value="submit">
                @if ($req && $editable)<input type="hidden" name="id" value="{{ $req->id }}">@endif

                <div class="sec">
                    <div class="bd">
                        <div class="ttl">CASH FLOW REQUEST</div>
                        <div class="d-flex justify-content-between mt-1 small text-muted">
                            <div>Request ID: <b style="color:#dc3545;">{{ $req->request_no ?? $nextNo }}</b></div>
                            <div>{{ \Carbon\Carbon::parse($req->created_at ?? now())->format('d-m-Y · h:i A') }}</div>
                        </div>
                    </div>
                </div>

                {{-- BASIC INFORMATION --}}
                <div class="sec">
                    <div class="hd">📋 Basic Information</div>
                    <div class="bd">
                        <div class="grid2">
                            <div>
                                <label class="fl">Branch / Store <span class="req">*</span></label>
                                <select name="branch_id" class="rp-input" {{ $ro }}>
                                    <option value="">Main Store</option>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b->id }}" {{ $req && $req->branch_id == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="fl">Counter (optional)</label>
                                <select name="terminal_id" class="rp-input" {{ $ro }}>
                                    <option value="">— None —</option>
                                    @foreach ($counters as $c)
                                        <option value="{{ $c->id }}" {{ $req && $req->terminal_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <label class="fl mt-2">Purpose <span class="req">*</span></label>
                        <div>
                            @foreach ($purposes as $k => $lbl)
                                <label class="opt"><input type="radio" name="purpose" value="{{ $k }}" {{ $ro }}
                                        {{ ($req->purpose ?? 'opening_cash') === $k ? 'checked' : '' }}> {{ $lbl }}</label>
                            @endforeach
                        </div>
                        <input type="text" name="purpose_other" class="rp-input mt-2" placeholder="If Other, specify…"
                            value="{{ $req->purpose_other ?? '' }}" {{ $ro }}>
                    </div>
                </div>

                {{-- REQUESTED BY / TO --}}
                <div class="grid2">
                    <div class="sec">
                        <div class="hd">👤 Requested By</div>
                        <div class="bd"> 
                            <label class="fl">Role</label>
                            <input type="text" id="from_role_display" class="rp-input mb-2" value="{{ $req->from_label ?? ($meRole === 'owner' ? 'Owner' : 'Cashier') }}" readonly style="background:#f4f6fb;">
                            <input type="hidden" name="from_label" id="from_role_hidden" value="{{ $req->from_label ?? ($meRole === 'owner' ? 'Owner' : 'Cashier') }}">
                            <label class="fl">Requested By</label>
                            @php
                                $fromValue = $req ? ($req->from_role === 'staff' ? 'staff:' . $req->from_id : ($req->from_role === 'manager' ? 'manager:0' : 'owner:0')) : ($meRole === 'owner' ? 'owner:0' : 'staff:' . $meId);
                            @endphp
                            <select name="requested_by" id="requested_by_select" class="rp-input" {{ $ro }}>
                                <option value="owner:0" data-role="Owner" {{ (isset($fromValue) && ($fromValue === 'owner:0' || $fromValue === 'manager:0')) ? 'selected' : '' }}>Owner / Manager</option>
                                @foreach ($staff as $s)
                                    <option value="staff:{{ $s->id }}" data-role="{{ $s->role->name ?? 'Staff' }}" {{ (isset($fromValue) && $fromValue === 'staff:' . $s->id) ? 'selected' : '' }}>{{ trim($s->f_name . ' ' . $s->l_name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="sec">
                        <div class="hd">🧑‍💼 Requested To</div>
                        <div class="bd">
                            <label class="fl">Role</label>
                            <input type="text" id="to_role_display" class="rp-input mb-2" value="{{ $req->to_label ?? 'Manager' }}" readonly style="background:#f4f6fb;">
                            <input type="hidden" name="to_label" id="to_role_hidden" value="{{ $req->to_label ?? 'Manager' }}">
                            <label class="fl">Requested To <span class="req">*</span></label>
                            <select name="requested_to" id="requested_to_select" class="rp-input" {{ $ro }} required>
                                <option value="manager:0" data-role="Manager" {{ $toValue === 'manager:0' || $toValue === 'owner:0' ? 'selected' : '' }}>Owner / Manager</option>
                                @foreach ($staff as $s)
                                    <option value="staff:{{ $s->id }}" data-role="{{ $s->role->name ?? 'Staff' }}" {{ $toValue === 'staff:' . $s->id ? 'selected' : '' }}>{{ trim($s->f_name . ' ' . $s->l_name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- PAYMENT MODE --}}
                <div class="sec">
                    <div class="hd">💳 Payment Mode</div>
                    <div class="bd">
                        @foreach (['cash' => 'Cash', 'upi' => 'UPI', 'bank_transfer' => 'Bank Transfer', 'mixed' => 'Mixed'] as $k => $lbl)
                            <label class="opt"><input type="radio" name="payment_mode" value="{{ $k }}" {{ $ro }}
                                    {{ ($req->payment_mode ?? 'cash') === $k ? 'checked' : '' }}> {{ $lbl }}</label>
                        @endforeach
                    </div>
                </div>

                {{-- AMOUNT DETAILS --}}
                <div class="sec">
                    <div class="hd">🧮 Amount Details</div>
                    <div class="bd grid3">
                        <div>
                            <label class="fl">Requested Amount (₹) <span class="req">*</span></label>
                            <input type="number" step="0.01" min="0" name="requested_amount" id="cfr_requested" class="rp-input"
                                value="{{ $req->requested_amount ?? '' }}" {{ $ro }} required>
                        </div>
                        <div>
                            <label class="fl">Cash Amount (₹)</label>
                            <input type="number" step="0.01" min="0" name="cash_amount" id="cfr_cash" class="rp-input"
                                value="{{ $req->cash_amount ?? '' }}" {{ $ro }}>
                        </div>
                        <div>
                            <label class="fl">UPI Amount (₹)</label>
                            <input type="number" step="0.01" min="0" name="upi_amount" id="cfr_upi" class="rp-input"
                                value="{{ $req->upi_amount ?? '' }}" {{ $ro }}>
                        </div>
                    </div>
                </div>

                {{-- DENOMINATIONS --}}
                <div class="sec">
                    <div class="hd">💵 Cash Denomination Details</div>
                    <div class="table-responsive">
                        <table class="rp-table denom">
                            <thead><tr><th>Denomination</th><th>Quantity</th><th class="text-right">Amount (₹)</th></tr></thead>
                            <tbody>
                                @foreach ($denoms as $dv)
                                    <tr>
                                        <td>₹ {{ $dv }}</td>
                                        <td><input type="number" min="0" name="denom[{{ $dv }}]" class="rp-input cfr-denom" data-denom="{{ $dv }}"
                                                value="{{ $den[(string) $dv] ?? '' }}" {{ $ro }}></td>
                                        <td class="text-right cf-amt" id="cfr_amt_{{ $dv }}">₹ {{ number_format((($den[(string) $dv] ?? 0) * $dv), 0) }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td>Coins</td>
                                    <td><input type="number" step="0.01" min="0" name="coins" id="cfr_coins" class="rp-input"
                                            value="{{ $den['coins'] ?? '' }}" {{ $ro }}></td>
                                    <td class="text-right cf-amt" id="cfr_amt_coins">₹ {{ number_format(($den['coins'] ?? 0), 0) }}</td>
                                </tr>
                                <tr class="totrow"><td>Total Cash Amount</td><td></td><td class="text-right" id="cfr_denom_total">₹ {{ number_format($req->cash_amount ?? 0, 0) }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- NOTES & ATTACHMENTS --}}
                <div class="sec">
                    <div class="hd">📝 Notes &amp; Attachments</div>
                    <div class="bd grid2">
                        <div>
                            <label class="fl">Remarks</label>
                            <textarea name="note" class="rp-input" rows="3" {{ $ro }} placeholder="Remarks…">{{ $req->note ?? '' }}</textarea>
                        </div>
                        <div>
                            <label class="fl">Attachment (JPG, PNG, PDF — max 5MB)</label>
                            @if ($req && $req->attachment)
                                <div class="mb-1"><a href="{{ asset('storage/app/public/cash-flow/' . $req->attachment) }}" target="_blank" class="rp-btn o sm">📎 View attachment</a></div>
                            @endif
                            @if ($editable)
                                <input type="file" name="attachment" class="rp-input" accept=".jpg,.jpeg,.png,.pdf">
                            @endif
                        </div>
                    </div>
                </div>

                {{-- APPROVAL SECTION --}}
                <div class="sec">
                    <div class="hd">✔️ Approval Section</div>
                    <div class="bd grid3">
                        <div><label class="fl">Status</label>
                            <span class="rp-badge {{ $statusBadge[$req->status ?? 'draft'] ?? 'muted' }}">{{ ucfirst($req->status ?? 'New') }}</span>
                        </div>
                        <div><label class="fl">Approved By</label>
                            <div class="small">{{ $req && $req->approved_by ? ($req->approved_by_role === 'owner' ? 'Owner' : ($staffNames[(int) $req->approved_by] ?? 'Manager')) : '—' }}</div>
                        </div>
                        <div><label class="fl">Approved Time</label>
                            <div class="small">{{ $req && $req->approved_at ? \Carbon\Carbon::parse($req->approved_at)->format('d-m-Y h:i A') : '—' }}</div>
                        </div>
                    </div>
                </div>

                {{-- FOOTER ACTIONS --}}
                <div class="foot">
                    @if ($editable)
                        <button type="submit" class="rp-btn p" onclick="document.getElementById('cfr_save_mode').value='submit'">✓ Submit Request</button>
                        <button type="submit" class="rp-btn o" onclick="document.getElementById('cfr_save_mode').value='draft'">💾 Save Draft</button>
                    @endif
                </div>
            </form>

            {{-- Contextual workflow actions (existing request) --}}
            @if ($req)
                <div class="foot">
                    <a href="{{ route('vendor.retail-pos.cash-flow.slip', $req->id) }}" target="_blank" class="rp-btn o">🧾 Print Slip</a>
                    @if ($req->status === 'pending' && !$editable && ($isManager || $iAmRecipient))
                        <form method="post" action="{{ route('vendor.retail-pos.cash-flow.action', $req->id) }}">@csrf<input type="hidden" name="action" value="approve"><button class="rp-btn p">✔ Approve</button></form>
                    @endif
                    @if (in_array($req->status, ['pending', 'approved']) && $iAmRecipient)
                        <form method="post" action="{{ route('vendor.retail-pos.cash-flow.action', $req->id) }}" onsubmit="return confirm('Confirm you have physically received this cash?');">@csrf<input type="hidden" name="action" value="receive"><button class="rp-btn p" style="background:#1b7a43;border-color:#1b7a43;">⬇ Receive Cash</button></form>
                    @endif
                    @if (in_array($req->status, ['received', 'approved']) && $isManager)
                        <form method="post" action="{{ route('vendor.retail-pos.cash-flow.action', $req->id) }}">@csrf<input type="hidden" name="action" value="close"><button class="rp-btn o" style="color:#6f42c1;">⊗ Close Request</button></form>
                    @endif
                    @if (in_array($req->status, ['pending', 'approved']) && ($iAmRecipient || $isManager))
                        <form method="post" action="{{ route('vendor.retail-pos.cash-flow.action', $req->id) }}" onsubmit="return confirm('Reject this cash request?');">@csrf<input type="hidden" name="action" value="reject"><button class="rp-btn o" style="color:#dc3545;">✕ Reject</button></form>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        (function () {
            var denomInputs = document.querySelectorAll('.cfr-denom');
            var coins = document.getElementById('cfr_coins');
            var cash = document.getElementById('cfr_cash');
            var upi = document.getElementById('cfr_upi');
            var requested = document.getElementById('cfr_requested');
            function recalc() {
                var total = 0;
                denomInputs.forEach(function (i) {
                    var d = parseFloat(i.dataset.denom) || 0, q = parseFloat(i.value) || 0;
                    var amt = d * q; total += amt;
                    var cell = document.getElementById('cfr_amt_' + i.dataset.denom);
                    if (cell) cell.textContent = '₹ ' + amt.toLocaleString('en-IN');
                });
                var cn = parseFloat(coins && coins.value) || 0; total += cn;
                var coinCell = document.getElementById('cfr_amt_coins'); if (coinCell) coinCell.textContent = '₹ ' + cn.toLocaleString('en-IN');
                var tot = document.getElementById('cfr_denom_total'); if (tot) tot.textContent = '₹ ' + total.toLocaleString('en-IN');
                if (cash && !cash.disabled) cash.value = total ? total.toFixed(2) : cash.value;
                if (requested && !requested.disabled) {
                    var u = parseFloat(upi && upi.value) || 0;
                    var c = parseFloat(cash && cash.value) || 0;
                    if (total || u) requested.value = (c + u).toFixed(2);
                } 
            }
            denomInputs.forEach(function (i) { i.addEventListener('input', recalc); });
            if (coins) coins.addEventListener('input', recalc);
            if (upi) upi.addEventListener('input', recalc);
 
            // Auto-set "Requested By" role when staff is selected
            var fromSelect = document.getElementById('requested_by_select');
            var fromRoleDisplay = document.getElementById('from_role_display');
            var fromRoleHidden = document.getElementById('from_role_hidden');
            if (fromSelect) {
                fromSelect.addEventListener('change', function () {
                    var opt = this.options[this.selectedIndex];
                    var role = opt ? (opt.getAttribute('data-role') || 'Staff') : 'Staff';
                    if (fromRoleDisplay) fromRoleDisplay.value = role;
                    if (fromRoleHidden) fromRoleHidden.value = role;
                });
            }

            // Auto-set "Requested To" role when staff is selected
            var toSelect = document.getElementById('requested_to_select');
            var toRoleDisplay = document.getElementById('to_role_display');
            var toRoleHidden = document.getElementById('to_role_hidden');
            if (toSelect) {
                toSelect.addEventListener('change', function () {
                    var opt = this.options[this.selectedIndex];
                    var role = opt ? (opt.getAttribute('data-role') || 'Staff') : 'Staff';
                    if (toRoleDisplay) toRoleDisplay.value = role;
                    if (toRoleHidden) toRoleHidden.value = role;
                });
            }
        })();
    </script>
@endpush
