@extends('layouts.admin.app')
@section('title', 'Laundry — All Orders')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-washing-machine"></i></span> Laundry Orders
        </h1>
        <a href="{{ route('admin.laundry.challans') }}" class="btn btn-outline-secondary">Hotel Challans</a>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form class="row align-items-end g-2" method="GET">
                <div class="col-md-2">
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Order no / customer / store">
                </div>
                <div class="col-md-2">
                    <select name="store_id" class="form-control" onchange="this.form.submit()">
                        <option value="">All Stores</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="received"   {{ $status == 'received'   ? 'selected' : '' }}>Received</option>
                        <option value="processing" {{ $status == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="ready"      {{ $status == 'ready'      ? 'selected' : '' }}>Ready</option>
                        <option value="delivered"  {{ $status == 'delivered'  ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled"  {{ $status == 'cancelled'  ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn--secondary w-100"><i class="tio-search"></i></button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('admin.laundry.orders') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Order No</th>
                            <th>Store</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Drop Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $key => $order)
                        @php
                            $statusColors = ['received'=>'info','processing'=>'warning','ready'=>'primary','delivered'=>'success','cancelled'=>'danger'];
                            $sc = $statusColors[$order->status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td>{{ $key + $orders->firstItem() }}</td>
                            <td><strong>{{ $order->order_no }}</strong></td>
                            <td>{{ $order->store_name }}</td>
                            <td>{{ $order->customer_display_name }}</td>
                            <td>{{ $order->customer_display_phone }}</td>
                            <td>{{ \Carbon\Carbon::parse($order->drop_date)->format('d M Y') }}</td>
                            <td>{{ number_format($order->total_amount, 2) }}</td>
                            <td><span class="badge badge-soft-{{ $sc }}">{{ ucfirst($order->status) }}</span></td>
                            <td>
                                @if($order->payment_status == 'paid')
                                    <span class="badge badge-soft-success">Paid</span>
                                @else
                                    <span class="badge badge-soft-danger">Pending</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-4">No orders found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
        <div class="card-footer border-0 pt-0">
            <div class="d-flex justify-content-end">{!! $orders->links() !!}</div>
        </div>
        @endif
    </div>
</div>
@endsection
