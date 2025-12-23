@extends('layouts.vendor.app')

@section('title',translate('messages.Invoice List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title text-capitalize">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/order.png')}}" class="w--26" alt="">
                </span>
                <span>
                    {{str_replace('_',' ',$status)}} Invoices
                    <span class="badge badge-soft-dark ml-2">{{$orders->total()}}</span>
                </span>
            </h1>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper justify-content-end">
                       
                </div>
                <!-- End Row -->
            </div>
            <!-- End Header -->
            <div class="card-body p-0">
                <!-- Table -->
                <div class="table-responsive datatable-custom">
                    <table id="datatable" class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                       >
                        <thead class="thead-light">
                        <tr>
                            <th class="border-0">
                                {{translate('messages.#')}}
                            </th>
                            <th class="border-0 table-column-pl-0">{{translate('messages.order_id')}}</th>
                            <th class="border-0 table-column-pl-0">{{translate('messages.invoice_id')}}</th>
                            <th class="border-0">{{translate('messages.order_date')}}</th>
                            <th class="border-0">{{translate('messages.customer_information')}}</th>
                            <th class="border-0">{{translate('messages.total_amount')}}</th>
                            <th class="border-0 text-center">{{translate('messages.order_status')}}</th>
                            <th class="border-0 text-center">{{translate('messages.actions')}}</th>
                        </tr>
                        </thead>

                        <tbody id="set-rows">
                        @foreach($orders as $key=>$order)
                            <tr class="status-{{$order['order_status']}} class-all">
                                <td class="">
                                    {{$key+$orders->firstItem()}}
                                </td>
                                <td class="table-column-pl-0">
                                    <a href="{{route('vendor.order.details',['id'=>$order['id']])}}">{{$order['id']}}</a>
                                </td>
                                <td class="table-column-pl-0">
                                    <a href="{{route('vendor.order.details',['id'=>$order['id']])}}">{{$order['invoice_id']}}</a>
                                </td>
                                <td>
                                    <div>
                                        {{date('d M Y',strtotime($order['created_at']))}}
                                    </div>
                                    <div class="d-block text-uppercase">
                                        {{date(config('timeformat'),strtotime($order['created_at']))}}
                                    </div>
                                </td>
                                <td>
                                    @if($order->is_guest)
                                    @php($customer_details = json_decode($order['delivery_address'],true))
                                    <strong>{{$customer_details['contact_person_name']}}</strong>
                                    <div>{{$customer_details['contact_person_number']}}</div>
                                    @elseif($order->customer)

                                    <strong>
                                        {{$order->customer['f_name'].' '.$order->customer['l_name']}}
                                    </strong>
                                    <div>
                                        {{$order->customer['phone']}}
                                    </div>
                                    @else
                                        <label
                                            class="badge badge-danger">{{translate('messages.invalid_customer_data')}}</label>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-right mw--85px">
                                        <div>
                                            {{\App\CentralLogics\Helpers::format_currency($order['order_amount'])}}
                                        </div>
                                        @if($order->payment_status=='paid')
                                        <strong class="text-success">
                                            {{translate('messages.paid')}}
                                        </strong>
                                        @elseif($order->payment_status=='partially_paid')
                                        <strong class="text-success">
                                            {{translate('messages.partially_paid')}}
                                        </strong>
                                        @else
                                        <strong class="text-danger">
                                            {{translate('messages.unpaid')}}
                                        </strong>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-capitalize text-center">
                                    @if($order['order_status']=='pending')
                                        <span class="badge badge-soft-info">
                                        {{translate('messages.pending')}}
                                        </span>
                                    @elseif($order['order_status']=='confirmed')
                                        <span class="badge badge-soft-info">
                                        {{translate('messages.confirmed')}}
                                        </span>
                                    @elseif($order['order_status']=='processing')
                                        <span class="badge badge-soft-warning">
                                        {{translate('messages.processing')}}
                                        </span>
                                    @elseif($order['order_status']=='picked_up')
                                        <span class="badge badge-soft-warning">
                                        {{translate('messages.out_for_delivery')}}
                                        </span>
                                    @elseif($order['order_status']=='delivered')
                                        <span class="badge badge-soft-success">
                                        {{translate('messages.delivered')}}
                                        </span>
                                    @elseif($order['order_status']=='failed')
                                        <span class="badge badge-soft-danger">
                                        {{translate('messages.payment_failed')}}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger">
                                        {{str_replace('_',' ',$order['order_status'])}}
                                        </span>
                                    @endif
                                    @if($order['order_type']=='take_away')
                                        <div class="text-info mt-1">
                                            {{translate('messages.take_away')}}
                                        </div>
                                    @else
                                        <div class="text-title mt-1">
                                        {{translate('messages.home Delivery')}}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                         <a class="btn btn-sm btn--primary btn-outline-primary action-btn" style="width:fit-content;padding: 0px 7px!important; " target="_blank" href="{{route('vendor.order.generate-order-invoice',[$order['id']])}}">Invoice</a>
                                         <a class="btn btn-sm btn--primary btn-outline-primary action-btn" target="_blank" href="{{route('vendor.order.generate-invoice',[$order['id']])}}"><i class="tio-print"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @if(count($orders) === 0)
                    <div class="empty--data">
                        <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                        <h5>
                            {{translate('no_data_found')}}
                        </h5>
                    </div>
                    @endif
                </div>
                <!-- End Table -->
            </div>
            <!-- Footer -->
            <div class="card-footer">
                {!! $orders->links() !!}
            </div>
            <!-- End Footer -->
        </div>
        <!-- End Card -->

@endsection

@push('script_2')
    <script>
        "use strict";
        $(document).on('ready', function () {

            let datatable = $.HSCore.components.HSDatatables.init($('#datatable'), {
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copy',
                        className: 'd-none'
                    },
                    {
                        extend: 'excel',
                        className: 'd-none',
                        action: function ()
                        {
                            window.location.href = '{{route("vendor.order.export",['status'=>$status,'file_type'=>'excel','type'=>'order', request()->getQueryString()])}}';
                        }
                    },
                    {
                        extend: 'csv',
                        className: 'd-none',
                        action: function ()
                        {
                            window.location.href = '{{route("vendor.order.export",['status'=>$status,'file_type'=>'csv','type'=>'order', request()->getQueryString()])}}';
                        }
                    },
                    {
                        extend: 'pdf',
                        className: 'd-none'
                    },
                    {
                        extend: 'print',
                        className: 'd-none'
                    },
                ],
                select: {
                    style: 'multi',
                    selector: 'td:first-child input[type="checkbox"]',
                    classMap: {
                        checkAll: '#datatableCheckAll',
                        counter: '#datatableCounter',
                        counterInfo: '#datatableCounterInfo'
                    }
                },
                language: {
                    zeroRecords: '<div class="text-center p-4">' +
                        '<img class="w-7rem mb-3" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="Image Description">' +

                        '</div>'
                }
            });

            $('#export-copy').click(function () {
                datatable.button('.buttons-copy').trigger()
            });

            $('#export-excel').click(function () {
                datatable.button('.buttons-excel').trigger()
            });

            $('#export-csv').click(function () {
                datatable.button('.buttons-csv').trigger()
            });

            $('#export-pdf').click(function () {
                datatable.button('.buttons-pdf').trigger()
            });

            $('#export-print').click(function () {
                datatable.button('.buttons-print').trigger()
            });

            $('#toggleColumn_order').change(function (e) {
                datatable.columns(1).visible(e.target.checked)
            })

            $('#toggleColumn_date').change(function (e) {
                datatable.columns(2).visible(e.target.checked)
            })

            $('#toggleColumn_customer').change(function (e) {
                datatable.columns(3).visible(e.target.checked)
            })

            $('#toggleColumn_payment_status').change(function (e) {
                datatable.columns(4).visible(e.target.checked)
            })

            $('#toggleColumn_order_status').change(function (e) {
                datatable.columns(5).visible(e.target.checked)
            })

            $('#toggleColumn_actions').change(function (e) {
                datatable.columns(6).visible(e.target.checked)
            })

        });
    </script>

@endpush
