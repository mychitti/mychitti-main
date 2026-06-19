@extends('layouts.vendor.app')
@section('title', 'Pharmacy — Sale Orders')

@push('css_or_js')
    <style>
        .pill { font-size:10px; font-weight:700; padding:3px 9px; border-radius:100px; }
        .pill.pending{background:#fef3c7;color:#92400e}.pill.completed{background:#dcfce7;color:#15803d}.pill.cancelled{background:#fee2e2;color:#b91c1c}
        .so-empty { text-align:center; color:#9aa1ab; padding:40px 16px; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        @include('hmis::vendor-views.partials._pharmacy_header')

        <div class="pharmacy-page-content">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between" style="gap:10px;">
                    <h3 class="mb-0" style="font-size:15px; font-weight:700;">Sale Orders</h3>
                    <form action="" method="get" class="input-group input-group-sm mb-0" style="max-width:260px;">
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search order / invoice ID...">
                        <div class="input-group-append"><button class="btn btn-outline-secondary"><i class="tio-search"></i></button></div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Order ID</th><th>Date</th><th>Invoice</th><th>Items</th><th class="text-right">Total</th><th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $o)
                                    <tr>
                                        <td><span class="font-weight-bold">{{ $o->order_id }}</span></td>
                                        <td>{{ $o->created_at?->format('d M Y, h:i A') }}</td>
                                        <td>{{ $o->invoice_id ?: '—' }}</td>
                                        <td style="white-space:normal; max-width:360px;">
                                            {{ $o->details->map(fn($d) => ($d->item->item_name ?? 'Item') . ' ×' . (int) $d->qty)->take(4)->implode(', ') }}
                                            @if ($o->details->count() > 4) <span class="text-muted">+{{ $o->details->count() - 4 }} more</span>@endif
                                        </td>
                                        <td class="text-right">{{ _price($o->total_amount ?? $o->details->sum('total_price')) }}</td>
                                        <td><span class="pill {{ $o->status }}">{{ ucfirst($o->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><div class="so-empty">
                                        No sale orders yet. Walk-in pharmacy sales appear here automatically.
                                    </div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end">{{ $orders->appends(request()->query())->links() }}</div>
        </div>
    </div>
@endsection
