@extends('layouts.vendor.app')
@section('title', 'Laundry — Walk-in Orders')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex align-items-center justify-content-between">
            <h1 class="page-header-title"><span class="page-header-icon"><i class="tio-washing-machine"></i></span> Walk-in
                Orders</h1>
            <a href="{{ route('vendor.laundry.orders.create') }}" class="btn btn--primary"><i class="tio-add"></i> New
                Order</a>
        </div>

        {{-- Filters --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="row">
                    <form class="search-form col-md-3 ">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input value="{{ request()?->search ?? null }}" type="search" name="search"
                                class="form-control"
                                placeholder="{{ translate('messages.Ex:') }} {{ translate('Search by name or email..') }}"
                                aria-label="Search">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>
                    <form class="col-md-2 align-items-end g-2 " method="GET">

                        <div class="">
                            <select name="status" class="form-control js-select2-custom" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="received" {{ $status == 'received' ? 'selected' : '' }}>Received</option>
                                <option value="processing" {{ $status == 'processing' ? 'selected' : '' }}>Processing
                                </option>
                                <option value="ready" {{ $status == 'ready' ? 'selected' : '' }}>Ready</option>
                                <option value="delivered" {{ $status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                    </form>

                    <form action="" class="col-md-2 date-range-form">
                        @include('vendor-views/form_modals/date_range')
                        <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                            data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                    </form>

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
                                <th>Order No</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Drop Date</th>
                                <th>Expected Ready</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $key => $order)
                                <tr>
                                    <td>{{ $key + $orders->firstItem() }}</td>
                                    <td><strong>{{ $order->order_no }}</strong></td>
                                    <td>{{ $order->customer_display_name }}</td>
                                    <td>{{ $order->customer_display_phone }}</td>
                                    <td>{{ $order->drop_date->format('d M Y') }}</td>
                                    <td>{{ $order->expected_ready_date ? $order->expected_ready_date->format('d M Y') : '-' }}
                                    </td>
                                    <td>{{ number_format($order->total_amount, 2) }}</td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'received' => 'info',
                                                'processing' => 'warning',
                                                'ready' => 'primary',
                                                'delivered' => 'success',
                                                'cancelled' => 'danger',
                                            ];
                                            $sc = $statusColors[$order->status] ?? 'secondary';
                                        @endphp
                                        <span
                                            class="badge badge-soft-{{ $sc }}">{{ ucfirst($order->status) }}</span>
                                    </td>
                                    <td>
                                        @if ($order->payment_status == 'paid')
                                            <span class="badge badge-soft-success">Paid</span>
                                        @else
                                            <span class="badge badge-soft-danger">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('vendor.laundry.orders.show', $order->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="tio-visible"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">No orders found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($orders->hasPages())
                <div class="card-footer border-0 pt-0">
                    <div class="d-flex justify-content-end">{!! $orders->links() !!}</div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
