@extends('layouts.vendor.app')
@section('title', 'Return Orders')
@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content') 

    <div class="content container-fluid p-3">
        @include('hmis::vendor-views.partials._pharmacy_header')
        <div class="pharmacy-page-content">
            <div class="page-header">
                <div class="d-flex flex-wrap px-3 w-100">
                    <div class="d-flex w-100 flex-wrap justify-content-between  align-items-center">
                        <h1 class="page-header-title mb-2">
                            <a href="javascript:history.back()" class="mr-2" style="color: inherit;" title="Back">
                                <i class="tio-chevron-left"></i>
                            </a>
                            <span class="page-header-icon">
                                <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                            </span>
                            <span> 
                                Return Order
                            </span>
                        </h1>
                    </div>
                </div>
            </div>
            <!-- Page Heading -->
                        @if (hasPermission('inventory_sale_return', 'add'))

            <div class="card">
                <div class="col-md-3 mb-3">
                    <label for="">Invoice Id</label>
                    <select name="" id="order_select" data-placeholder="Invoice Id" class="js-select2-custom">
                        <option value=""></option>
                        @foreach ($orders as $key => $order)
                            <option value="{{ $order->order_id }}">{{ $order->invoice_id }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="table-responsive datatable-custom" style="display:none;" id="table-div">
                    <table id="datatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">Date</th>
                                <th class="border-0">Invoice Id</th>
                                <th class="border-0">Item</th>
                                <th class="border-0">Qty</th>
                                <th class="border-0">Unit Price</th>
                                <th class="border-0">Status</th>
                                <th class="border-0">Action</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                        </tbody>
                    </table>

                    <div class="empty--data_order">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                </div>
            </div>
            @endif

            <div class="card mt-3">
                <div class="d-flex justify-content-between flex-wrap">
                    <h1 class="page-header-title mb-2 mt-2 mx-2">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                        </span>
                        <span>
                            Returned Orders
                            <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($returned_orders) }}</span>
                        </span>
                    </h1>
                    <div class="d-flex gap-2 flex-wrap py-2 px-1">

                        <form action="" class="">
                            <!-- Search -->
                            <div class="input-group input--group" style="flex-wrap: nowrap !important; ">
                                <input type="search" style="min-width:220px;height: 100%;     padding: 11px 10px;"
                                    name="search" value="{{ request()?->search ?? null }}" class="form-control "
                                    placeholder="{{ translate('messages.search by item or invoice id') }}">
                                <button type="submit" class="btn btn--secondary "><i class="tio-search"></i></button>
                            </div>
                            <!-- End Search -->
                        </form>
                        <form action="" class="d-flex date-range-form">
                            <input type="hidden" name="tab" value="entry">
                            <button style="width:fit-content; white-space:nowrap" class="btn_sm btn btn-outline-warning" type="button"
                                data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                            {{-- date range modal --}}
                            @include('vendor-views/form_modals/date_range')
                        </form>
                        @if (hasPermission('inventory_sale_return', 'export'))
                            <a href="{{ route('vendor.inventory.sale.order-export', ['return']) }}"
                                class="btn btn-outline-primary btn_sm">Export Return Orders</a>
                        @endif
                    </div>
                </div>
                        @if (hasPermission('inventory_sale_return', 'list'))

                <div class="table-responsive datatable-custom" id="table">
                    <table id="datatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">Date</th>
                                <th class="border-0">Invoice Id</th>
                                <th class="border-0">Item</th>
                                <th class="border-0">Qty</th>
                                <th class="border-0">Unit Price</th>
                                <th class="border-0">Status</th>
                                <th class="border-0">Action</th>
                            </tr>
                        </thead>

                        <tbody id="">
                            @foreach ($returned_orders as $key => $detail)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $detail->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        @if ($detail->order?->invoice?->pdf)
                                            <a target="_blank"
                                                href="{{ asset('storage/app/public/invoice/' . $detail->order->invoice->pdf) }}">
                                                {{ $detail->order->invoice->invoice_id }}
                                            </a>
                                        @else
                                            {{ $detail->order?->invoice?->invoice_id }}
                                        @endif
                                    </td>
                                    <td>
                                        <div style="width: 300px;text-align: start !important;white-space: normal;">
                                            <a href="{{ $detail->item?->id ? route('vendor.inventory.item.detail', [$detail->item?->id]) : '#' }}">
                                                {{ $detail->item->item_name ?? 'Deleted' }}
                                            </a>
                                        </div>
                                    </td>
                                    <td>{{ $detail->qty }}</td>
                                    <td>{{ _price($detail->unit_price) }}</td>
                                    <td>{{ _price($detail->total_price) }}</td>
                                    <td>
                                        <span class="text-danger">
                                            {{ ucfirst($detail->status) }}
                                        </span>

                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                    @if (!count($returned_orders))
                        <div class="empty--data">
                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                            <h5>
                                {{ translate('no_data_found') }}
                            </h5>
                        </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
    @include('vendor-views.form_modals.pos_calendar')

@endsection
@push('script_2')
    <script>
        $('#order_select').on('change', function() {
            console.log('fsdf')
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: "{{ route('vendor.inventory.sale.order-details-fetch') }}",
                data: {
                    order_id: $(this).val()
                },
                type: 'post',
                success: function(response) {
                    //  console.table(response)
                    //  console.table(response.data)
                    $("#table-div").show()
                    let html = response.data;
                    $("#set-rows").html(html)
                    if (html != '') {
                        $(".empty--data_order").hide()
                    } else {
                        $(".empty--data").show()

                    }
                }
            });
        })
    </script>
    @include('vendor-views/js/date_range')
@endpush
