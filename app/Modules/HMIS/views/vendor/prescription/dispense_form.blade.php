@extends('layouts.vendor.app')
@section('title', 'Dispense — Rx #' . $rx->id)

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-medicine" style="font-size:22px;"></i></span>
            Dispense Medicines
            <small class="text-muted font-size-14 ml-2">Rx #{{ $rx->id }}</small>
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('vendor.prescription.show', $rx->id) }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-print"></i> View Rx
            </a>
            <a href="{{ route('vendor.prescription.dispense.queue') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-arrow-backward"></i> Queue
            </a>
        </div>
    </div>

    {{-- Patient header --}}
    <div class="card mb-3" style="background:linear-gradient(90deg,#eff6ff,#f0fdf4); border:1px solid #bfdbfe;">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-4">
                <div>
                    <small class="text-muted d-block">Patient</small>
                    <strong>{{ $rx->patient?->name }}</strong>
                    <span class="text-muted">({{ $rx->patient?->patient_uid }})</span>
                </div>
                <div>
                    <small class="text-muted d-block">Doctor</small>
                    <strong>Dr. {{ $rx->doctorProfile?->employee?->f_name }} {{ $rx->doctorProfile?->employee?->l_name }}</strong>
                    <span class="text-muted">{{ $rx->doctorProfile?->specialization }}</span>
                </div>
                <div>
                    <small class="text-muted d-block">Prescription Date</small>
                    <strong>{{ $rx->created_at->format('d M Y') }}</strong>
                </div>
                @if($rx->diagnosis)
                <div>
                    <small class="text-muted d-block">Diagnosis</small>
                    <strong>{{ \Illuminate\Support\Str::limit($rx->diagnosis, 60) }}</strong>
                </div>
                @endif
            </div>
        </div>
    </div>

    @php $allDone = $rx->items->every(fn($i) => $i->dispensed); @endphp

    @if($allDone)
        <div class="alert alert-success">
            <i class="tio-checkmark-circle mr-1"></i>
            All medicines have been dispensed for this prescription.
        </div>
    @endif

    <form action="{{ route('vendor.prescription.dispense.process', $rx->id) }}" method="POST" id="dispenseForm">
        @csrf
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Medicines</h5>
                @if(!$allDone)
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllPending()">
                        Select All Pending
                    </button>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:40px;">Dispense</th>
                                <th>Medicine</th>
                                <th>Dosage</th>
                                <th>Frequency</th>
                                <th>Duration</th>
                                <th>Prescribed Qty</th>
                                <th>In Stock</th>
                                <th>Dispense Qty</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rx->items as $item)
                            <tr class="{{ $item->dispensed ? 'table-success' : '' }}" id="row_{{ $item->id }}">
                                <td class="text-center">
                                    @if($item->dispensed)
                                        <i class="tio-checkmark-circle text-success" style="font-size:18px;"
                                           title="Dispensed on {{ $item->dispensed_at?->format('d M Y H:i') }}"></i>
                                    @else
                                        <input type="checkbox" name="items[{{ $item->id }}][dispense]" value="1"
                                            class="dispense-check" data-id="{{ $item->id }}"
                                            onchange="toggleQty({{ $item->id }}, this.checked)"
                                            style="width:18px; height:18px; cursor:pointer;">
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $item->medicine_name }}</strong>
                                    @if($item->inventoryItem)
                                        <br><small class="text-muted">
                                            <i class="tio-tag-outlined"></i>
                                            Linked to inventory
                                        </small>
                                    @endif
                                    @if($item->dispensed && $item->dispensed_qty)
                                        <br><small class="text-success">
                                            Dispensed: {{ $item->dispensed_qty }} unit(s)
                                        </small>
                                    @endif
                                </td>
                                <td>{{ $item->dosage ?: '—' }}</td>
                                <td>{{ $item->frequency ?: '—' }}</td>
                                <td>{{ $item->duration ?: '—' }}</td>
                                <td>{{ $item->quantity ?: '—' }}</td>
                                <td>
                                    @if($item->inventoryItem)
                                        @php $stock = $item->inventoryItem->stock ?? 0; @endphp
                                        <span class="{{ $stock <= 0 ? 'text-danger' : ($stock <= 5 ? 'text-warning' : 'text-success') }}">
                                            {{ $stock }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="min-width:90px;">
                                    @if(!$item->dispensed)
                                        <input type="number" name="items[{{ $item->id }}][qty]"
                                            id="qty_{{ $item->id }}"
                                            class="form-control form-control-sm"
                                            value="{{ $item->quantity ?? 1 }}"
                                            min="1" style="width:80px; display:none;">
                                    @else
                                        <span class="text-muted">{{ $item->dispensed_qty ?? '—' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->dispensed)
                                        <span class="badge badge-success">Dispensed</span>
                                    @else
                                        <span class="badge badge-soft-warning">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if(!$allDone)
        @php
            $storeGstEnabled = \App\CentralLogics\Helpers::get_store_data()->gst &&
                json_decode(\App\CentralLogics\Helpers::get_store_data()->gst)->status;
        @endphp
        <div class="card mt-3">
            <div class="card-body py-2">
                <div class="d-flex flex-wrap align-items-center gap-4">
                    <strong style="font-size:13px;"><i class="tio-receipt mr-1"></i>Tax Type for Bill:</strong>
                    <label class="d-flex align-items-center gap-1 mb-0" style="cursor:pointer; font-size:13px;">
                        <input type="radio" name="tax_type" value="non-gst" checked> Non-GST
                    </label>
                    <label class="d-flex align-items-center gap-1 mb-0"
                        style="cursor:{{ $storeGstEnabled ? 'pointer' : 'not-allowed' }}; opacity:{{ $storeGstEnabled ? '1' : '0.5' }}; font-size:13px;">
                        <input type="radio" name="tax_type" value="gst" {{ $storeGstEnabled ? '' : 'disabled' }}> GST
                        @if($storeGstEnabled)
                        <small class="text-muted ml-1">(rates applied per item from inventory)</small>
                        @endif
                    </label>
                    @if(!$storeGstEnabled)
                    <span class="text-warning" style="font-size:11px;">
                        <i class="tio-info-outined"></i> GST not configured.
                        <a href="{{ route('vendor.shop.edit') }}" target="_blank">Set up GST in Shop Settings</a>
                    </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-success" id="dispenseBtn" disabled>
                <i class="tio-checkmark-circle"></i> Confirm Dispense
            </button>
            <small class="text-muted align-self-center">
                Select medicines to dispense, adjust quantities, then confirm.
            </small>
        </div>
        @endif
    </form>

    {{-- Billing note + invoice links --}}
    <div class="card mt-3" style="border-left:3px solid #2563eb;">
        <div class="card-body py-2" style="font-size:13px;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>
                    <i class="tio-invoice text-primary mr-1"></i>
                    <strong>Billing:</strong>
                    Medicines with a selling price in inventory are automatically added to the patient's pharmacy invoice upon dispense.
                </span>
                @if($pharmacyInvoices->isNotEmpty())
                <div class="d-flex flex-wrap gap-2">
                    @foreach($pharmacyInvoices as $inv)
                    <a href="{{ route('vendor.invoice.view-invoice', $inv->id) }}"
                       class="btn btn-xs btn-outline-primary" target="_blank">
                        <i class="tio-receipt"></i>
                        {{ $inv->invoice_id }}
                        <span class="badge badge-soft-{{ $inv->payment_status === 'Paid' ? 'success' : 'warning' }} ml-1">
                            {{ $inv->payment_status }}
                        </span>
                    </a>
                    @endforeach
                </div>
                @else
                <span class="text-muted" style="font-size:12px;">No invoice generated yet.</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
function toggleQty(id, checked) {
    const qtyInput = document.getElementById('qty_' + id);
    if (qtyInput) qtyInput.style.display = checked ? 'inline-block' : 'none';
    updateDispenseBtn();
}

function updateDispenseBtn() {
    const anyChecked = document.querySelectorAll('.dispense-check:checked').length > 0;
    const btn = document.getElementById('dispenseBtn');
    if (btn) btn.disabled = !anyChecked;
}

function selectAllPending() {
    document.querySelectorAll('.dispense-check').forEach(cb => {
        cb.checked = true;
        toggleQty(cb.dataset.id, true);
    });
    updateDispenseBtn();
}
</script>
@endpush
