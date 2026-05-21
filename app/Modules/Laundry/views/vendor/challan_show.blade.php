@extends('layouts.vendor.app')
@section('title', 'Challan — ' . $challan->challan_no)

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <h1 class="page-header-title"><span class="page-header-icon"><i class="tio-file-text"></i></span> {{ $challan->challan_no }}</h1>
        <div>
            @if(in_array($challan->status, ['pending','partial']))
            <a href="{{ route('vendor.laundry.challans.receive', $challan->id) }}" class="btn btn-success mr-2">
                <i class="tio-checkmark-circle"></i> Receive / Return
            </a>
            @endif
            <a href="{{ route('vendor.laundry.challans.print', $challan->id) }}" target="_blank" class="btn btn-outline-secondary mr-2">
                <i class="tio-print"></i> Print
            </a>
            @if($challan->status === 'returned' && !$challan->invoice_id)
            <button type="button" class="btn btn-outline-primary mr-2" data-toggle="modal" data-target="#invoiceModal">
                <i class="tio-file-text"></i> Generate Invoice
            </button>
            @elseif($challan->invoice_id)
            <a href="{{ route('vendor.invoice.view-invoice', $challan->invoice_id) }}" class="btn btn-outline-success mr-2">
                <i class="tio-visible"></i> View Invoice
            </a>
            @endif
            <a href="{{ route('vendor.laundry.challans') }}" class="btn btn-outline-secondary">
                <i class="tio-arrow-backward"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Challan Info</h5></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><td class="text-muted" style="width:50%">Challan No</td><td><strong>{{ $challan->challan_no }}</strong></td></tr>
                        <tr><td class="text-muted">Hotel</td><td>{{ $challan->hotel->f_name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Hotel Phone</td><td>{{ $challan->hotel->phone ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Outward Date</td><td>{{ $challan->outward_date->format('d M Y') }}</td></tr>
                        <tr><td class="text-muted">Expected Inward</td><td>{{ $challan->expected_inward_date ? $challan->expected_inward_date->format('d M Y') : '-' }}</td></tr>
                        <tr><td class="text-muted">Inward Date</td><td>{{ $challan->inward_date ? $challan->inward_date->format('d M Y') : '-' }}</td></tr>
                        <tr><td class="text-muted">Status</td>
                            <td>
                                @if($challan->status == 'pending') <span class="badge badge-soft-warning">Pending</span>
                                @elseif($challan->status == 'partial') <span class="badge badge-soft-info">Partial Return</span>
                                @else <span class="badge badge-soft-success">Returned</span>
                                @endif
                            </td>
                        </tr>
                        @if($challan->notes)
                        <tr><td class="text-muted">Notes</td><td>{{ $challan->notes }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Items</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th class="text-center">Prev Balance</th>
                                    <th class="text-center">Sent</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Received</th>
                                    <th class="text-center">Damaged</th>
                                    <th class="text-center">Balance</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($challan->items as $key => $item)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $item->item_name }}</td>
                                    <td class="text-center">{{ $item->previous_balance }}</td>
                                    <td class="text-center">{{ $item->sent_qty }}</td>
                                    <td class="text-center">{{ $item->total }}</td>
                                    <td class="text-center">{{ $item->received_qty }}</td>
                                    <td class="text-center">{{ $item->damaged_qty }}</td>
                                    <td class="text-center">
                                        <strong class="{{ $item->balance > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $item->balance }}
                                        </strong>
                                    </td>
                                    <td>{{ $item->remarks ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="thead-light">
                                <tr>
                                    <td colspan="2" class="font-weight-bold">Total</td>
                                    <td class="text-center font-weight-bold">{{ $challan->items->sum('previous_balance') }}</td>
                                    <td class="text-center font-weight-bold">{{ $challan->items->sum('sent_qty') }}</td>
                                    <td class="text-center font-weight-bold">{{ $challan->items->sum('total') }}</td>
                                    <td class="text-center font-weight-bold">{{ $challan->items->sum('received_qty') }}</td>
                                    <td class="text-center font-weight-bold">{{ $challan->items->sum('damaged_qty') }}</td>
                                    <td class="text-center font-weight-bold {{ $challan->items->sum('balance') > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $challan->items->sum('balance') }}
                                    </td>
                                    <td></td>
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
@if($challan->status === 'returned' && !$challan->invoice_id)
<div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="{{ route('vendor.laundry.challans.invoice', $challan->id) }}">
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
