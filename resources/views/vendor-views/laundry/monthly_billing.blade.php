@extends('layouts.vendor.app')
@section('title', 'Monthly Billing')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-receipt"></i></span>
            Monthly Bill — {{ $hotel->f_name }}
        </h1>
        <a href="{{ route('vendor.laundry.register', array_filter(['hotel_id' => $hotelId, 'date_range' => $preset])) }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> Back to Register
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-3">
                <div><span class="text-muted" style="font-size:12px;">Hotel</span><br><strong>{{ $hotel->f_name }}</strong></div>
                <div><span class="text-muted" style="font-size:12px;">Period</span><br><strong>{{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</strong></div>
                <div><span class="text-muted" style="font-size:12px;">Items</span><br><strong>{{ count($items) }}</strong></div>
            </div>
        </div>
    </div>

    @if(empty($items))
        <div class="alert alert-warning">No billable items found for this period and hotel.</div>
    @else
    <form method="POST" action="{{ route('vendor.laundry.register.billing.store') }}" id="billingForm">
        @csrf
        <input type="hidden" name="hotel_id" value="{{ $hotelId }}">
        <input type="hidden" name="from"     value="{{ $from }}">
        <input type="hidden" name="to"       value="{{ $to }}">

        <div class="card">
            <div class="card-header py-2">
                <h6 class="mb-0">Edit prices before generating bill</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" id="billingTable">
                        <thead class="thead-light">
                            <tr>
                                <th style="min-width:180px;">Item</th>
                                <th class="text-center" style="width:90px;">Sent</th>
                                <th class="text-center" style="width:90px;">Damaged</th>
                                <th class="text-center" style="width:90px;">Net Qty</th>
                                <th class="text-center" style="width:130px;">Rate (₹)</th>
                                <th class="text-right"  style="width:130px;">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $name => $item)
                            <tr>
                                <td>
                                    {{ $name }}
                                    <input type="hidden" name="items[{{ $loop->index }}][name]" value="{{ $name }}">
                                    <input type="hidden" name="items[{{ $loop->index }}][qty]"  value="{{ $item['qty'] }}" class="item-qty">
                                </td>
                                <td class="text-center text-muted">{{ $item['sent'] }}</td>
                                <td class="text-center {{ $item['damaged'] > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                    {{ $item['damaged'] > 0 ? $item['damaged'] : '—' }}
                                </td>
                                <td class="text-center font-weight-bold">{{ $item['qty'] }}</td>
                                <td class="text-center">
                                    <input type="number" step="0.01" min="0"
                                        name="items[{{ $loop->index }}][rate]"
                                        value="{{ number_format($item['rate'], 2, '.', '') }}"
                                        class="form-control form-control-sm text-right item-rate"
                                        style="max-width:110px; margin:auto;">
                                </td>
                                <td class="text-right font-weight-bold item-amount">
                                    {{ number_format($item['qty'] * $item['rate'], 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="5" class="text-right font-weight-bold">Grand Total</td>
                                <td class="text-right font-weight-bold text-primary" id="grandTotal">
                                    {{ number_format(collect($items)->sum(fn($i) => $i['qty'] * $i['rate']), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    {{-- GST selector --}}
                    @php
                        $storeGstEnabled = \App\CentralLogics\Helpers::get_store_data()->gst &&
                            json_decode(\App\CentralLogics\Helpers::get_store_data()->gst)->status;
                    @endphp
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <strong style="font-size:13px;">Tax Type:</strong>
                        <label class="d-flex align-items-center gap-1 mb-0" style="cursor:pointer;">
                            <input type="radio" name="tax_type" value="non-gst" checked onchange="toggleGst(this.value)"> Non-GST
                        </label>
                        <label class="d-flex align-items-center gap-1 mb-0" style="cursor:{{ $storeGstEnabled ? 'pointer' : 'not-allowed' }}; opacity:{{ $storeGstEnabled ? '1' : '0.5' }};">
                            <input type="radio" name="tax_type" value="gst" {{ $storeGstEnabled ? 'onchange=toggleGst(this.value)' : 'disabled' }}> GST
                        </label>
                        @if($storeGstEnabled)
                        <div id="gstPercentWrap" style="display:none;" class="d-flex align-items-center gap-1">
                            <input type="number" name="gst_percent" id="gstPercent" class="form-control form-control-sm"
                                style="width:90px;" min="0" max="100" step="0.01" placeholder="GST %">
                        </div>
                        @endif
                        @if(!$storeGstEnabled)
                        <span class="text-warning" style="font-size:11px;">
                            <i class="tio-info-outined"></i> GST not configured.
                            <a href="{{ route('vendor.shop.edit') }}" target="_blank">Set up GST in Shop Settings</a>
                        </span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('vendor.laundry.register', array_filter(['hotel_id' => $hotelId, 'date_range' => $preset])) }}"
                           class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn--primary">
                            <i class="tio-receipt"></i> Generate Bill
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection

@push('script_2')
<script>
function toggleGst(val) {
    var wrap = document.getElementById('gstPercentWrap');
    var inp  = document.getElementById('gstPercent');
    wrap.style.display = val === 'gst' ? '' : 'none';
    inp.required = val === 'gst';
}

(function () {
    function recalc() {
        var grand = 0;
        document.querySelectorAll('#billingTable tbody tr').forEach(function (row) {
            var qty    = parseFloat(row.querySelector('.item-qty').value) || 0;
            var rate   = parseFloat(row.querySelector('.item-rate').value) || 0;
            var amount = qty * rate;
            row.querySelector('.item-amount').textContent = amount.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
            grand += amount;
        });
        document.getElementById('grandTotal').textContent = grand.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    document.querySelectorAll('.item-rate').forEach(function (inp) {
        inp.addEventListener('input', recalc);
    });
})();
</script>
@endpush
