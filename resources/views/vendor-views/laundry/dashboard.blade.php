@extends('layouts.vendor.app')
@section('title', 'Laundry Dashboard')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-washing-machine"></i></span> Laundry Dashboard
        </h1>
        <div class="d-flex gap-2">
        <form class="date-range-form">
            <button style="white-space:nowrap" class="btn btn-outline-warning btn-sm" type="button"
                data-toggle="modal" data-target="#dateRangeModal">
                <i class="tio-calendar"></i> {{ translate($preset) }}
            </button>
            @include('vendor-views/form_modals/date_range')
        </form>
         <button class="btn btn-sm btn-outline-info mr-2" data-toggle="modal" data-target="#calendarModal">
                <i class="tio-calendar-month"></i> Calendar View
            </button>
        </div>

    </div>

    {{-- Stats Row --}}
    <div class="row mb-3">
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card h-100 border-left-primary">
                <div class="card-body text-center py-3">
                    <div class="text-primary mb-1"><i class="tio-shopping-cart-outlined" style="font-size:1.8rem"></i></div>
                    <h3 class="mb-0">{{ $ordersInRange }}</h3>
                    <small class="text-muted">Walk-in Orders</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card h-100 border-left-warning">
                <div class="card-body text-center py-3">
                    <div class="text-warning mb-1"><i class="tio-time" style="font-size:1.8rem"></i></div>
                    <h3 class="mb-0">{{ $ordersPending }}</h3>
                    <small class="text-muted">Pending Orders</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card h-100 border-left-success">
                <div class="card-body text-center py-3">
                    <div class="text-success mb-1"><i class="tio-dollar" style="font-size:1.8rem"></i></div>
                    <h3 class="mb-0">{{ number_format($revenueInRange, 0) }}</h3>
                    <small class="text-muted">Revenue</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card h-100 border-left-danger">
                <div class="card-body text-center py-3">
                    <div class="text-danger mb-1"><i class="tio-file-text" style="font-size:1.8rem"></i></div>
                    <h3 class="mb-0">{{ $challansPending }}</h3>
                    <small class="text-muted">Challans Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card h-100" style="border-left: 3px solid #17a2b8">
                <div class="card-body text-center py-3">
                    <div class="text-info mb-1"><i class="tio-layers-outlined" style="font-size:1.8rem"></i></div>
                    <h3 class="mb-0">{{ $challansPartial }}</h3>
                    <small class="text-muted">Partial Returns</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card h-100 border-left-primary">
                <div class="card-body text-center py-3">
                    <div class="text-primary mb-1"><i class="tio-calendar-month" style="font-size:1.8rem"></i></div>
                    <h3 class="mb-0">{{ $challansInRange }}</h3>
                    <small class="text-muted">Challans</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row mb-3">
        <div class="col-12">
           
            <a href="{{ route('vendor.laundry.orders.create') }}" class="btn btn--primary mr-2">
                <i class="tio-add"></i> New Walk-in Order
            </a>
            <a href="{{ route('vendor.laundry.challans.create') }}" class="btn btn-outline-primary mr-2">
                <i class="tio-add"></i> New Challan
            </a>
            <a href="{{ route('vendor.laundry.orders') }}" class="btn btn-outline-secondary mr-2">
                <i class="tio-list"></i> All Orders
            </a>
            <a href="{{ route('vendor.laundry.challans') }}" class="btn btn-outline-secondary mr-2">
                <i class="tio-list"></i> All Challans
            </a>
            <a href="{{ route('vendor.laundry.register') }}" class="btn btn-outline-secondary">
                <i class="tio-calendar"></i> Monthly Register
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Recent Walk-in Orders --}}
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-2">
                    <h5 class="card-title mb-0">Recent Walk-in Orders</h5>
                    <a href="{{ route('vendor.laundry.orders') }}" class="btn btn-xs btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Order No</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                            @php
                                $sc = ['received'=>'info','processing'=>'warning','ready'=>'primary','delivered'=>'success','cancelled'=>'danger'][$order->status] ?? 'secondary';
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('vendor.laundry.orders.show', $order->id) }}">
                                        <strong>{{ $order->order_no }}</strong>
                                    </a>
                                </td>
                                <td>{{ $order->customer_display_name }}</td>
                                <td>{{ number_format($order->total_amount, 0) }}</td>
                                <td><span class="badge badge-soft-{{ $sc }}">{{ ucfirst($order->status) }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-3 text-muted">No orders yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Recent Challans --}}
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-2">
                    <h5 class="card-title mb-0">Recent Hotel Challans</h5>
                    <a href="{{ route('vendor.laundry.challans') }}" class="btn btn-xs btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Challan No</th>
                                <th>Hotel</th>
                                <th>Outward</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentChallans as $challan)
                            @php
                                $cs = ['pending'=>'warning','partial'=>'info','returned'=>'success'][$challan->status] ?? 'secondary';
                                $cl = ['pending'=>'Pending','partial'=>'Partial','returned'=>'Returned'][$challan->status] ?? $challan->status;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('vendor.laundry.challans.show', $challan->id) }}">
                                        <strong>{{ $challan->challan_no }}</strong>
                                    </a>
                                </td>
                                <td>{{ $challan->hotel->f_name ?? '-' }}</td>
                                <td>{{ $challan->outward_date->format('d M') }}</td>
                                <td><span class="badge badge-soft-{{ $cs }}">{{ $cl }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-3 text-muted">No challans yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('vendor-views.form_modals.pos_calendar')
</div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
    <script>window.calendarDayLinkBase = '{{ route("vendor.laundry.orders") }}';</script>
    @include('vendor-views.salespoint.calendar-js')
@endpush
