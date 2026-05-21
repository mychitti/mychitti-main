@extends('layouts.vendor.app')
@section('title', 'Stock In / Stock Out')
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
                            Stock In / Stock Out
                            <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($rows) }}</span>
                        </span>
                    </h1>
                    <div class="d-flex gap-2 flex-wrap">
                       <form action="" class="d-flex date-range-form">
                            <input type="hidden" name="tab" value="entry">
                            <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning"
                                type="button" data-toggle="modal"
                                data-target="#dateRangeModal">{{translate($preset)}}</button>
                            @include('vendor-views/form_modals/date_range')
                        </form>

                       {{--   <form action="" class="h-100">
                            <div class="input-group input--group" style="flex-wrap: nowrap !important; ">
                                <input type="search" style="min-width:220px;height: 100%;     padding: 11px 10px;"
                                    name="search" value="{{ request()?->search ?? null }}" class="form-control "
                                    placeholder="{{ translate('messages.search by item or invoice id') }}">
                                <button type="submit" class="btn btn--secondary "><i class="tio-search"></i></button>
                            </div>
                        </form>
                     --}}
                    </div>
                </div>
            </div>
        </div>
        <!-- Page Heading -->

        <div class="card">

            <div class="table-responsive datatable-custom" id="table-div">
                <table id="datatable"
                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">{{ translate('sl') }}</th>
                            <th class="border-0">Date</th>
                            <th class="border-0">Item</th>
                            <th class="border-0">Invoice Id</th>
                            <th class="border-0">Stock Status</th>
                            <th class="border-0">Qty</th>
                            <th class="border-0">Remaining Stock</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($rows as $key => $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->date }}</td>
                                 <td>
                                    @if ($item->item_id)
                                    <div style="width: 200px;text-align: start !important;white-space: normal;">

                                        <a href="{{ route('vendor.inventory.item.detail', [$item->item_name]) }}">
                                            {{ ucwords($item->item_name) }}</a>
                                   </div>
                                    @else
                                        Deleted
                                    @endif
                                </td>
                                <td>
                                    @if ($item->invoice_pdf)
                                        <a target="_blank"
                                            href="{{ asset('storage/app/public/invoice') . '/' . $item->invoice_pdf }}">
                                            {{ $item->invoice_id }}</a>
                                    @else
                                        {{ $item->invoice_id }}
                                    @endif
                                </td>
                               
                                <td>
                                @if($item->type == 'Stock-in')
                                    <span class="badge badge-soft-success rounded ml-1">Stock-in</span>
                                    @else
                                    <span class="badge badge-soft-danger rounded ml-1">Stock-out</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-soft-dark rounded ml-1">{{$item->qty}}</span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-dark rounded ml-1">{{$item->remaining_stock}}</span>
                                </td>
                                
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            
                @if (count($rows) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>

        </div>
    </div>

@endsection
@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
