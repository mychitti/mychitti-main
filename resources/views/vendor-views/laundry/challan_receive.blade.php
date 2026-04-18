@extends('layouts.vendor.app')
@section('title', 'Receive — ' . $challan->challan_no)

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <h1 class="page-header-title"><span class="page-header-icon"><i class="tio-checkmark-circle"></i></span> Receive Return — {{ $challan->challan_no }}</h1>
        <a href="{{ route('vendor.laundry.challans.show', $challan->id) }}" class="btn btn-outline-secondary"><i class="tio-arrow-backward"></i> Back</a>
    </div>

    <form action="{{ route('vendor.laundry.challans.receive-update') }}" method="POST">
        @csrf
        <input type="hidden" name="challan_id" value="{{ $challan->id }}">

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Inward Date <span class="text-danger">*</span></label>
                    <input type="date" name="inward_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div>
                    <p class="mb-1 text-muted">Hotel: <strong>{{ $challan->hotel->f_name ?? '-' }}</strong></p>
                    <p class="mb-0 text-muted">Outward: <strong>{{ $challan->outward_date->format('d M Y') }}</strong></p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th class="text-center">Sent</th>
                                <th class="text-center">Total Outstanding</th>
                                <th class="text-center" style="width:130px">Received Qty <span class="text-danger">*</span></th>
                                <th class="text-center" style="width:130px">Damaged Qty</th>
                                <th class="text-center">Balance</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($challan->items as $key => $item)
                            <input type="hidden" name="items[{{ $key }}][id]" value="{{ $item->id }}">
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $item->item_name }}</td>
                                <td class="text-center">{{ $item->sent_qty }}</td>
                                <td class="text-center"><strong>{{ $item->total }}</strong></td>
                                <td>
                                    <input type="number" name="items[{{ $key }}][received_qty]"
                                        class="form-control form-control-sm text-center received-qty"
                                        data-total="{{ $item->total }}"
                                        value="{{ $item->received_qty }}" min="0" max="{{ $item->total }}" required>
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $key }}][damaged_qty]"
                                        class="form-control form-control-sm text-center damaged-qty"
                                        value="{{ $item->damaged_qty }}" min="0">
                                </td>
                                <td class="text-center">
                                    <strong class="balance-display">{{ $item->balance }}</strong>
                                </td>
                                <td>
                                    <input type="text" name="items[{{ $key }}][remarks]"
                                        class="form-control form-control-sm"
                                        value="{{ $item->remarks }}" placeholder="Remarks">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn--primary">Save Return</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script_2')
<script>
    function recalcBalance(row) {
        var total    = parseInt(row.find('.received-qty').data('total')) || 0;
        var received = parseInt(row.find('.received-qty').val()) || 0;
        var damaged  = parseInt(row.find('.damaged-qty').val()) || 0;
        var balance  = total - received - damaged;
        row.find('.balance-display').text(balance < 0 ? 0 : balance);
    }

    $(document).on('input', '.received-qty, .damaged-qty', function () {
        recalcBalance($(this).closest('tr'));
    });
</script>
@endpush
