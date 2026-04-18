@extends('layouts.vendor.app')
@section('title', 'Order — ' . $order->order_no)

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <h1 class="page-header-title"><span class="page-header-icon"><i class="tio-washing-machine"></i></span> {{ $order->order_no }}</h1>
        <div>
            <a href="{{ route('vendor.laundry.orders.receipt', $order->id) }}" target="_blank" class="btn btn-outline-secondary mr-2">
                <i class="tio-print"></i> Receipt
            </a>
            @if(!$order->invoice_id)
            <button type="button" class="btn btn-outline-primary mr-2" data-toggle="modal" data-target="#invoiceModal">
                <i class="tio-file-text"></i> Generate Invoice
            </button>
            @else
            <a href="{{ route('vendor.invoice.view-invoice', $order->invoice_id) }}" class="btn btn-outline-success mr-2">
                <i class="tio-visible"></i> View Invoice
            </a>
            @endif
            <a href="{{ route('vendor.laundry.orders') }}" class="btn btn-outline-secondary">
                <i class="tio-arrow-backward"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {{-- Customer & Order Info --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Order Info</h5></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><td class="text-muted" style="width:45%">Order No</td><td><strong>{{ $order->order_no }}</strong></td></tr>
                        <tr><td class="text-muted">Customer</td><td>{{ $order->customer_display_name }}</td></tr>
                        <tr><td class="text-muted">Phone</td><td>{{ $order->customer_display_phone }}</td></tr>
                        <tr><td class="text-muted">Drop Date</td><td>{{ $order->drop_date->format('d M Y') }}</td></tr>
                        <tr><td class="text-muted">Expected Ready</td><td>{{ $order->expected_ready_date ? $order->expected_ready_date->format('d M Y') : '-' }}</td></tr>
                        <tr><td class="text-muted">Pickup Date</td><td>{{ $order->pickup_date ? $order->pickup_date->format('d M Y') : '-' }}</td></tr>
                        <tr><td class="text-muted">Amount</td><td><strong>{{ number_format($order->total_amount, 2) }}</strong></td></tr>
                        <tr><td class="text-muted">Payment</td>
                            <td>
                                @if($order->payment_status == 'paid')
                                    <span class="badge badge-soft-success">Paid</span>
                                @else
                                    <span class="badge badge-soft-danger">Pending</span>
                                @endif
                            </td>
                        </tr>
                        @if($order->notes)
                        <tr><td class="text-muted">Notes</td><td>{{ $order->notes }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Status Update --}}
            @if(!in_array($order->status, ['delivered','cancelled']))
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Update Status</h5></div>
                <div class="card-body">
                    <form action="{{ route('vendor.laundry.orders.status') }}" method="POST">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <div class="form-group">
                            <select name="status"  class="form-control js-select2-custom" required>
                                <option value="received"   {{ $order->status == 'received'   ? 'selected' : '' }}>Received</option>
                                <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="ready"      {{ $order->status == 'ready'      ? 'selected' : '' }}>Ready</option>
                                <option value="delivered"  {{ $order->status == 'delivered'  ? 'selected' : '' }}>Delivered</option>
                                <option value="cancelled"  {{ $order->status == 'cancelled'  ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn--primary btn-block">Update Status</button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-8">
            {{-- Items --}}
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Items</h5>
                    @php
                        $statusColors = ['received'=>'info','processing'=>'warning','ready'=>'primary','delivered'=>'success','cancelled'=>'danger'];
                        $sc = $statusColors[$order->status] ?? 'secondary';
                    @endphp
                    <span class="badge badge-soft-{{ $sc }} px-3 py-2">{{ ucfirst($order->status) }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Rate</th>
                                    <th>Amount</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $key => $item)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $item->item_name }}</td>
                                    <td>{{ $item->qty }}</td>
                                    <td>{{ number_format($item->rate, 2) }}</td>
                                    <td>{{ number_format($item->amount, 2) }}</td>
                                    <td>{{ $item->notes ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="thead-light">
                                <tr>
                                    <td colspan="4" class="text-right font-weight-bold">Total</td>
                                    <td colspan="2"><strong>{{ number_format($order->total_amount, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- GST Invoice Modal --}}
@if(!$order->invoice_id)
<div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="{{ route('vendor.laundry.orders.invoice', $order->id) }}">
                @csrf
                <div class="modal-header py-2">
                    <h6 class="modal-title">Generate Invoice</h6>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                @php
                    $storeGstEnabled = \App\CentralLogics\Helpers::get_store_data()->gst &&
                        json_decode(\App\CentralLogics\Helpers::get_store_data()->gst)->status;
                @endphp
                <div class="modal-body">
                    <div class="form-group mb-2">
                        <label class="input-label">Tax Type</label>
                        <div class="d-flex gap-3 mt-1">
                            <label class="d-flex align-items-center gap-1" style="cursor:pointer;">
                                <input type="radio" name="tax_type" value="non-gst" checked onchange="toggleGst(this.value)"> Non-GST
                            </label>
                            <label class="d-flex align-items-center gap-1" style="cursor:{{ $storeGstEnabled ? 'pointer' : 'not-allowed' }}; opacity:{{ $storeGstEnabled ? '1' : '0.5' }};">
                                <input type="radio" name="tax_type" value="gst" {{ $storeGstEnabled ? 'onchange=toggleGst(this.value)' : 'disabled' }}> GST
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
                    <div class="form-group mb-0" id="gstPercentWrap" style="display:none;">
                        <label class="input-label">GST %</label>
                        <input type="number" name="gst_percent" id="gstPercent" class="form-control form-control-sm"
                            min="0" max="100" step="0.01" placeholder="e.g. 18">
                    </div>
                    @endif
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn--primary">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function toggleGst(val) {
    var wrap = document.getElementById('gstPercentWrap');
    var inp  = document.getElementById('gstPercent');
    wrap.style.display = val === 'gst' ? '' : 'none';
    inp.required = val === 'gst';
}
</script>
@endif
@endsection
