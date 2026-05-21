@extends('layouts.vendor.app')

@section('title',translate('messages.Invoices'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
           
                <span>
                   Invoices
                </span>
            </h1>
        </div>
        <!-- End Page Header -->


        <div class="card mt-3">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{translate('messages.invoice_list')}}<span class="badge badge-soft-dark ml-2" id="itemCount">{{count($invoices)}}</span></h5>

                </div>
                <form action="" class="row search-form"> 
                        <div class="col">
                            <input type="date" name="from" value="{{$fromdate}}" class="form-control" id="">
                        </div>
                        <div class="col">
                            <input type="date" name="to"  value="{{$todate}}" class="form-control" id="">
                        </div>
                        <div class="col">
                            <button class="btn btn-primary btn-sm">Filter</button>
                        </div>
                    </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{translate('sl')}}</th>
                                <th class="border-0">Invoice Id</th>
                                <th class="border-0 ">Bill to</th>
                                <th class="border-0 ">Type</th>
                                <th class="border-0 ">Total Amount</th>
                                <th class="border-0 ">Payment Method</th>
                                <th class="border-0 ">Payment Status</th>
                                <th class="border-0 ">Payment Date</th>
                                <th class="border-0 ">Created At</th>
                                <th class="border-0 ">Action</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                        @foreach($invoices as $key=>$invoice)
                            <tr>
                                <td>{{$key+1}}</td> 
                                <td>{{$invoice->invoice_id}}</td>
                                <td>{{$invoice->f_name}} {{$invoice->l_name}}</td>
                                <td>{{$invoice->type}}</td>
                                <td>{{ \App\CentralLogics\Helpers::currency_symbol(). number_format($invoice->total_amount)}}</td> 
                                <td>{{$invoice->payment_method}}</td>
                                <td>{{$invoice->payment_status}}</td>
                                <td>{{$invoice->payment_date ? $invoice->payment_date : explode(' ', $invoice->created_at)[0]}}</td>
                                <td>{{$invoice->created_at}}</td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                    @if($invoice->pdf)
                                      <a class="btn action-btn btn--primary btn-outline-primary" target="_blank" href="{{asset('storage/app/public/invoice') . '/' . $invoice->pdf}}" title="View">
                                            <i class="tio-visible"></i>
                                        </a>
                                        @else
                                        <a class="btn action-btn btn--primary btn-outline-primary" target="_blank" href="{{route('vendor.invoice.invoice-view', [ $invoice->service_id ? 'service' : 'manual', $invoice->service_id ? $invoice->service_id : $invoice->invoice_id])}}" title="View">
                                            <i class="tio-visible"></i>
                                        </a>
                                        @endif
                                    </div>
                                </td>

                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if(count($invoices) !== 0)
            <hr>
            @endif
            @if(count($invoices) === 0)
            <div class="empty--data">
                <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                <h5>
                    {{translate('no_data_found')}}
                </h5>
            </div>
            @endif
        </div>

    </div>

@endsection

@push('script_2')

@endpush
