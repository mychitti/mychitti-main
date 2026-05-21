@extends('layouts.vendor.app')
@section('title', 'Generate Hospital Bill')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-receipt" style="font-size:22px;"></i></span>
            Generate Hospital Bill
        </h1>
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward"></i> Back
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('vendor.hospital-bill.store') }}" id="billForm">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        <input type="hidden" name="context"    value="{{ $context }}">
        <input type="hidden" name="context_id" value="{{ $contextId }}">

        <div class="row">

            {{-- ── Left column: patient + context info + payment ──────── --}}
            <div class="col-md-4">

                {{-- Patient card --}}
                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0"><i class="tio-user mr-1"></i>Patient</h6></div>
                    <div class="card-body py-2">
                        <p class="mb-1 font-weight-bold" style="font-size:15px;">{{ $patient->name }}</p>
                        <p class="mb-1 text-muted" style="font-size:12px;">MUID: {{ $patient->patient_uid }}</p>
                        @if($patient->phone)
                            <p class="mb-1 text-muted" style="font-size:12px;"><i class="tio-call"></i> {{ $patient->phone }}</p>
                        @endif
                        @if($patient->address || $patient->city)
                            <p class="mb-0 text-muted" style="font-size:12px;">
                                {{ implode(', ', array_filter([$patient->address, $patient->city, $patient->state])) }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- IPD info --}}
                @if($context === 'ipd' && isset($admission))
                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0"><i class="tio-hospital mr-1"></i>IPD Admission</h6></div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0" style="font-size:12px;">
                            <tr><td class="text-muted" style="width:45%;">Admission #</td><td>{{ $admission->admission_number }}</td></tr>
                            <tr><td class="text-muted">Ward</td><td>{{ $admission->ward?->ward_name }}</td></tr>
                            <tr><td class="text-muted">Bed</td><td>{{ $admission->bed?->bed_number ?: '—' }}</td></tr>
                            <tr><td class="text-muted">Admitted</td><td>{{ $admission->admission_date?->format('d M Y') }}</td></tr>
                            <tr><td class="text-muted">Daily Charge</td><td>₹{{ number_format($admission->daily_charge, 2) }}</td></tr>
                            <tr><td class="text-muted">Status</td>
                                <td>
                                    <span class="badge badge-soft-{{ $admission->status === 'admitted' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($admission->status) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                @endif

                {{-- OPD info --}}
                @if($context === 'opd' && isset($visit))
                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0"><i class="tio-document-text mr-1"></i>OPD Visit</h6></div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0" style="font-size:12px;">
                            <tr><td class="text-muted" style="width:45%;">Token</td><td>{{ $visit->token_number }}</td></tr>
                            <tr><td class="text-muted">Date</td><td>{{ $visit->visit_date?->format('d M Y') }}</td></tr>
                            <tr><td class="text-muted">Doctor</td>
                                <td>Dr. {{ $visit->doctorProfile?->employee?->f_name }} {{ $visit->doctorProfile?->employee?->l_name }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                @endif

                {{-- Payment --}}
                @php
                    $storeGstEnabled = \App\CentralLogics\Helpers::get_store_data()->gst &&
                        json_decode(\App\CentralLogics\Helpers::get_store_data()->gst)->status;
                @endphp
                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0"><i class="tio-money mr-1"></i>Payment</h6></div>
                    <div class="card-body py-2">

                        {{-- Tax Type --}}
                        <div class="form-group mb-2">
                            <label class="input-label" style="font-size:12px;">Tax Type</label>
                            <div class="d-flex gap-3 mt-1">
                                <label class="d-flex align-items-center gap-1 mb-0" style="cursor:pointer; font-size:13px;">
                                    <input type="radio" name="tax_type" value="non-gst" checked
                                        onchange="toggleHospGst(this.value)"> Non-GST
                                </label>
                                <label class="d-flex align-items-center gap-1 mb-0"
                                    style="cursor:{{ $storeGstEnabled ? 'pointer' : 'not-allowed' }}; opacity:{{ $storeGstEnabled ? '1' : '0.5' }}; font-size:13px;">
                                    <input type="radio" name="tax_type" value="gst"
                                        {{ $storeGstEnabled ? 'onchange=toggleHospGst(this.value)' : 'disabled' }}> GST
                                </label>
                            </div>
                            @if(!$storeGstEnabled)
                            <p class="mb-0 mt-1 text-warning" style="font-size:11px;">
                                <i class="tio-info-outined"></i> GST not configured.
                                <a href="{{ route('vendor.shop.edit') }}" target="_blank">Set up GST in Shop Settings</a>
                            </p>
                            @endif
                        </div>
                        @if($storeGstEnabled)
                        <div class="form-group mb-2" id="gstPercentWrap" style="display:none;">
                            <label class="input-label" style="font-size:12px;">GST %</label>
                            <input type="number" name="gst_percent" id="gstPercent" class="form-control form-control-sm"
                                min="0" max="100" step="0.01" placeholder="e.g. 18">
                        </div>
                        @endif

                        <div class="form-group mb-2">
                            <label class="input-label" style="font-size:12px;">Status</label>
                            <div class="d-flex gap-3">
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" name="payment_status" value="Unpaid" id="ps_unpaid"
                                        class="custom-control-input" checked>
                                    <label class="custom-control-label" for="ps_unpaid">Unpaid</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" name="payment_status" value="Paid" id="ps_paid"
                                        class="custom-control-input">
                                    <label class="custom-control-label" for="ps_paid">Paid</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="input-label" style="font-size:12px;">Method</label>
                            <select name="payment_method" class="form-control form-control-sm">
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Card">Card</option>
                                <option value="Net Banking">Net Banking</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── Right column: line items ─────────────────────────── --}}
            <div class="col-md-8">

                {{-- ── Section 1: Service Charges ────────────────────── --}}
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="tio-hospital mr-1"></i>Service &amp; Room Charges</h6>
                        <button type="button" class="btn btn-xs btn-outline-primary" onclick="addServiceRow()">
                            <i class="tio-add"></i> Add Row
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Description</th>
                                    <th style="width:70px;">Qty</th>
                                    <th style="width:110px;">Rate (₹)</th>
                                    <th style="width:100px;" class="text-right">Amount</th>
                                    <th style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="serviceBody">
                                @foreach($serviceItems as $item)
                                <tr class="item-row">
                                    <td>
                                        <input type="hidden" name="inv_id[]" value="">
                                        <input type="hidden" name="item_hsn[]" value="">
                                        <input type="text" name="item_name[]" class="form-control form-control-sm"
                                            value="{{ $item['name'] }}" required>
                                    </td>
                                    <td>
                                        <input type="number" name="item_qty[]" class="form-control form-control-sm item-qty"
                                            value="{{ $item['qty'] }}" min="0" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="number" name="item_price[]" class="form-control form-control-sm item-price"
                                            value="{{ $item['price'] }}" min="0" step="0.01" required>
                                    </td>
                                    <td class="item-amount text-right align-middle">
                                        ₹{{ number_format($item['qty'] * $item['price'], 2) }}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow(this)">
                                            <i class="tio-delete-outlined"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Section 2: Medicines / Inventory Items ─────────── --}}
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="tio-medicine-bottle mr-1"></i>Medicines &amp; Supplies</h6>
                    </div>
                    <div class="card-body border-bottom pb-3">
                        {{-- Search bar --}}
                        <div class="input-group input-group-sm">
                            <input type="text" id="invSearch" class="form-control"
                                placeholder="Search medicine or inventory item by name…" autocomplete="off">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="tio-search"></i></span>
                            </div>
                        </div>
                        <div id="invSearchResults" class="border rounded mt-1" style="display:none; max-height:200px; overflow-y:auto; background:#fff; z-index:999; position:relative;"></div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Medicine / Item</th>
                                    <th style="width:70px;">Qty</th>
                                    <th style="width:110px;">Rate (₹)</th>
                                    <th style="width:100px;" class="text-right">Amount</th>
                                    <th style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="medicineBody">
                                @forelse($medicineItems as $item)
                                <tr class="item-row">
                                    <td>
                                        <input type="hidden" name="inv_id[]" value="{{ $item['inv_id'] ?? '' }}">
                                        <input type="hidden" name="item_hsn[]" value="{{ $item['hsn'] ?? '' }}">
                                        <input type="text" name="item_name[]" class="form-control form-control-sm"
                                            value="{{ $item['name'] }}" required>
                                    </td>
                                    <td>
                                        <input type="number" name="item_qty[]" class="form-control form-control-sm item-qty"
                                            value="{{ $item['qty'] }}" min="0" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="number" name="item_price[]" class="form-control form-control-sm item-price"
                                            value="{{ $item['price'] }}" min="0" step="0.01" required>
                                    </td>
                                    <td class="item-amount text-right align-middle">
                                        ₹{{ number_format($item['qty'] * $item['price'], 2) }}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow(this)">
                                            <i class="tio-delete-outlined"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr id="noMedRow">
                                    <td colspan="5" class="text-center text-muted py-2" style="font-size:12px;">
                                        Search above to add medicines / items
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Grand Total ─────────────────────────────────────── --}}
                <div class="card mb-3">
                    <div class="card-body py-2 d-flex justify-content-end align-items-center gap-4">
                        <span class="text-muted">Grand Total</span>
                        <h4 class="mb-0 font-weight-bold" id="grandTotal">₹0.00</h4>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sm btn--primary">
                        <i class="tio-receipt"></i> Save &amp; Generate Bill
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('script_2')
<script>
const INV_SEARCH_URL = "{{ route('vendor.hospital-bill.inventory-search') }}";

function toggleHospGst(val) {
    const wrap = document.getElementById('gstPercentWrap');
    const inp  = document.getElementById('gstPercent');
    if (wrap) { wrap.style.display = val === 'gst' ? '' : 'none'; }
    if (inp)  { inp.required = val === 'gst'; }
    recalc();
}

// ── Recalculate totals ────────────────────────────────────────────────
function recalc() {
    let base = 0;
    document.querySelectorAll('#billForm .item-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('.item-qty')?.value)   || 0;
        const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
        const amt   = qty * price;
        const amtCell = row.querySelector('.item-amount');
        if (amtCell) amtCell.textContent = '₹' + amt.toFixed(2);
        base += amt;
    });
    const taxType   = document.querySelector('input[name="tax_type"]:checked')?.value || 'non-gst';
    const gstPct    = taxType === 'gst' ? (parseFloat(document.getElementById('gstPercent')?.value) || 0) : 0;
    const total     = base * (1 + gstPct / 100);
    const gstLabel  = gstPct > 0 ? ` (incl. ${gstPct}% GST)` : '';
    document.getElementById('grandTotal').textContent = '₹' + total.toFixed(2) + gstLabel;
}

// ── Add blank service charge row ─────────────────────────────────────
function addServiceRow() {
    const tbody = document.getElementById('serviceBody');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
        <td>
            <input type="hidden" name="inv_id[]" value="">
            <input type="hidden" name="item_hsn[]" value="">
            <input type="text" name="item_name[]" class="form-control form-control-sm" placeholder="e.g. Nursing Charge" required>
        </td>
        <td><input type="number" name="item_qty[]" class="form-control form-control-sm item-qty" value="1" min="0" step="0.01" required></td>
        <td><input type="number" name="item_price[]" class="form-control form-control-sm item-price" value="0" min="0" step="0.01" required></td>
        <td class="item-amount text-right align-middle">₹0.00</td>
        <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow(this)"><i class="tio-delete-outlined"></i></button></td>
    `;
    tbody.appendChild(tr);
    tr.querySelectorAll('.item-qty, .item-price').forEach(el => el.addEventListener('input', recalc));
    recalc();
}

// ── Remove a row ──────────────────────────────────────────────────────
function removeRow(btn) {
    btn.closest('tr').remove();
    recalc();
}

// ── Add an inventory item row to the medicines table ─────────────────
function addMedicineRow(item) {
    // Remove placeholder row
    const placeholder = document.getElementById('noMedRow');
    if (placeholder) placeholder.remove();

    const tbody = document.getElementById('medicineBody');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
        <td>
            <input type="hidden" name="inv_id[]" value="${item.id}">
            <input type="hidden" name="item_hsn[]" value="${item.hsn || ''}">
            <input type="text" name="item_name[]" class="form-control form-control-sm"
                value="${item.item_name}" required>
        </td>
        <td><input type="number" name="item_qty[]" class="form-control form-control-sm item-qty" value="1" min="0" step="0.01" required></td>
        <td><input type="number" name="item_price[]" class="form-control form-control-sm item-price" value="${item.mrp || 0}" min="0" step="0.01" required></td>
        <td class="item-amount text-right align-middle">₹${parseFloat(item.mrp || 0).toFixed(2)}</td>
        <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow(this)"><i class="tio-delete-outlined"></i></button></td>
    `;
    tbody.appendChild(tr);
    tr.querySelectorAll('.item-qty, .item-price').forEach(el => el.addEventListener('input', recalc));
    recalc();
}

// ── Inventory search ──────────────────────────────────────────────────
let searchTimeout;
document.getElementById('invSearch').addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    const resultsBox = document.getElementById('invSearchResults');

    if (q.length < 2) { resultsBox.style.display = 'none'; return; }

    searchTimeout = setTimeout(() => {
        fetch(INV_SEARCH_URL + '?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(items => {
            if (!items.length) {
                resultsBox.innerHTML = '<div class="p-2 text-muted" style="font-size:12px;">No items found.</div>';
            } else {
                resultsBox.innerHTML = items.map(item => `
                    <div class="inv-result-item d-flex justify-content-between align-items-center px-3 py-2"
                         style="cursor:pointer; border-bottom:1px solid #f0f0f0; font-size:13px;"
                         onmouseover="this.style.background='#f0f7ff'"
                         onmouseout="this.style.background=''"
                         onclick='selectInventoryItem(${JSON.stringify(item)})'>
                        <span><strong>${item.item_name}</strong>
                            ${item.hsn ? '<span class="text-muted ml-2" style="font-size:11px;">HSN: ' + item.hsn + '</span>' : ''}
                        </span>
                        <span class="badge badge-soft-primary">₹${parseFloat(item.mrp || 0).toFixed(2)}</span>
                    </div>
                `).join('');
            }
            resultsBox.style.display = 'block';
        })
        .catch(() => { resultsBox.style.display = 'none'; });
    }, 300);
});

function selectInventoryItem(item) {
    addMedicineRow(item);
    document.getElementById('invSearch').value = '';
    document.getElementById('invSearchResults').style.display = 'none';
}

// Close search results on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('#invSearch') && !e.target.closest('#invSearchResults')) {
        document.getElementById('invSearchResults').style.display = 'none';
    }
});

// Attach listeners to pre-rendered rows and calculate on load
document.querySelectorAll('#billForm .item-qty, #billForm .item-price').forEach(el => {
    el.addEventListener('input', recalc);
});
const gstPctInput = document.getElementById('gstPercent');
if (gstPctInput) gstPctInput.addEventListener('input', recalc);

document.addEventListener('DOMContentLoaded', recalc);
</script>
@endpush
