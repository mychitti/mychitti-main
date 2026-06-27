@extends('layouts.vendor.app')
@section('title', 'Sale Orders')
@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')

    <div class="content container-fluid p-1">
        <div class="page-header">
            <div class="d-flex flex-wrap px-3 w-100">
                <div class="d-flex w-100 flex-wrap justify-content-between  align-items-center">
                    <h1 class="page-header-title mb-2">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                        </span>
                        <span>
                            Sale Orders
                            <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($sale_order_items) }}</span>
                        </span>
                    </h1>
                    <div class="d-flex gap-2 flex-wrap">
                        <form action="" class="d-flex date-range-form">
                            <input type="hidden" name="tab" value="entry">
                            <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning"
                                type="button" data-toggle="modal"
                                data-target="#dateRangeModal">{{ translate($preset) }}</button>
                            {{-- date range modal --}}
                            @include('vendor-views/form_modals/date_range')
                        </form>
                        <button type="button" class="btn btn--primary mb-0" data-toggle="modal"
                            data-target="#calendarModal">
                            Calendar
                        </button>

                        <form action="" class="h-100">
                            <!-- Search -->
                            <div class="input-group input--group" style="flex-wrap: nowrap !important; ">
                                <input type="search" style="min-width:220px;height: 100%;     padding: 11px 10px;"
                                    name="search" value="{{ request()?->search ?? null }}" class="form-control "
                                    placeholder="{{ translate('messages.search by item or invoice id') }}">
                                <button type="submit" class="btn btn--secondary "><i class="tio-search"></i></button>
                            </div>
                            <!-- End Search -->
                        </form>
                        @if (hasPermission('inventory_sale_order', 'export'))
                            <a href="{{ route('vendor.inventory.sale.order-export') }}"
                                class="btn btn-outline-primary btn_sm">Export Sale Orders</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- Page Heading -->

        @if (hasPermission('inventory_sale_order', 'list'))
            <div class="card">

                <div class="table-responsive datatable-custom" id="table-div">
                    <table id="datatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">Invoice Id</th>
                                <th class="border-0">Item</th>
                                <th class="border-0">Qty</th>
                                <th class="border-0">Unit Price</th>
                                <th class="border-0">Status</th>
                                <th class="border-0">Created at</th>
                                <th class="border-0">Action</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @foreach ($sale_order_items as $key => $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if ($item->order?->invoice?->pdf)
                                            <a target="_blank"
                                                href="{{ asset('storage/app/public/invoice') . '/' . $item->order?->invoice?->pdf }}">
                                                {{ $item->order?->invoice?->invoice_id }}</a>
                                        @else
                                            {{ $item->order?->invoice?->invoice_id }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->item?->id)
                                            <div style="width: 300px;text-align: start !important;white-space: normal;">

                                                <a href="{{ route('vendor.inventory.item.detail', [$item->item?->id]) }}">
                                                    {{ $item->item?->item_name }}</a>
                                            </div>
                                        @else
                                            Deleted
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-success rounded ml-1"> {{ $item->qty }}</span>
                                    </td>
                                    <td>
                                        {{ _price($item->unit_price) }}
                                    </td>
                                    <td>
                                        @if ($item->status == 'completed')
                                            <span
                                                class="badge badge-soft-success rounded ml-1">{{ ucfirst($item->status) }}</span>
                                        @elseif($item->status == 'returned')
                                            <span
                                                class="badge badge-soft-warning rounded ml-1">{{ ucfirst($item->status) }}</span>
                                        @elseif($item->status == 'cancelled')
                                            <span
                                                class="badge badge-soft-danger rounded ml-1">{{ ucfirst($item->status) }}</span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $item->created_at }}
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                                aria-expanded="false">
                                                <i class="fa-solid fa-bars"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                @if (hasPermission('inventory_sale', 'status_change'))
                                                    @if ($item->status != 'completed')
                                                        <a class="dropdown-item text-success form-alert" href="javascript:;"
                                                            data-id="completed-{{ $item['id'] }}"
                                                            data-message="{{ translate('Want to mark this order item as completed ') }}"
                                                            title="{{ translate('messages.completed') }}"></i>
                                                            <i class="tio-checkmark-circle-outlined"></i> Completed
                                                        </a>
                                                        <form
                                                            action="{{ route('vendor.inventory.sale.order-status', [$item->id, 'completed']) }}"
                                                            method="get" id="completed-{{ $item['id'] }}">
                                                            @csrf @method('get')
                                                        </form>
                                                    @endif
                                                    @if ($item->status != 'returned')
                                                        <a class="dropdown-item text-warning form-alert" href="javascript:;"
                                                            data-id="returned-{{ $item['id'] }}"
                                                            data-message="{{ translate('Want to mark this order item as returned ') }}"
                                                            title="{{ translate('messages.returned') }}"></i>
                                                            <i class="tio-replay"></i> Return
                                                        </a>
                                                        <form
                                                            action="{{ route('vendor.inventory.sale.order-status', [$item->id, 'returned']) }}"
                                                            method="get" id="returned-{{ $item['id'] }}">
                                                            @csrf @method('get')
                                                        </form>
                                                    @endif
                                                    @if ($item->status != 'cancelled')
                                                        <a class="dropdown-item text-danger form-alert" href="javascript:;"
                                                            data-id="cancelled-{{ $item['id'] }}"
                                                            data-message="{{ translate('Want to mark this order item as cancelled ') }}"
                                                            title="{{ translate('messages.cancelled') }}"></i>
                                                            <i class="tio-blocked"></i> Cancelled
                                                        </a>
                                                        <form
                                                            action="{{ route('vendor.inventory.sale.order-status', [$item->id, 'cancelled']) }}"
                                                            method="get" id="cancelled-{{ $item['id'] }}">
                                                            @csrf @method('get')
                                                        </form>
                                                    @endif
                                                @endif
                                                <a class="dropdown-item text-danger inv-delete-btn" href="javascript:;"
                                                    data-action="{{ route('vendor.inventory.sale.order-delete', [$item->id]) }}"
                                                    data-stock-label="Add this item's stock back to inventory">
                                                    <i class="tio-delete"></i> Delete
                                                </a>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="page-area">
                        {!! $sale_order_items->links() !!}
                    </div>
                    @if (count($sale_order_items) === 0)
                        <div class="empty--data">
                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                            <h5>
                                {{ translate('no_data_found') }}
                            </h5>
                        </div>
                    @endif
                </div>

            </div>
        @endif
    </div>
    @include('vendor-views.form_modals.pos_calendar')
    @include('vendor-views.inventory.partials._delete-with-stock')

@endsection
@push('script_2')
    @include('vendor-views/js/date_range')
    @include('vendor-views.salespoint.calendar-js')
@endpush
