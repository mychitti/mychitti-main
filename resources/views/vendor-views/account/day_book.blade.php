@extends('layouts.vendor.app')
@section('title', 'Day Book')
@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h1 class="page-header-title mb-2">
                    <span class="page-header-icon">
                    </span>
                    <span>
                        Day Book
                        <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($data) }}</span>
                    </span>
                </h1>
                <div class="d-flex align-items-center">
                    {{-- <form action="">
                        <input type="date" name="date" value="{{ $date ?? date('Y-m-d') }}" class="form-control"
                            onchange="this.form.submit()">
                    </form> --}}

                    <div class="d-flex gap-2">
                        <form action="" class=" date-range-form">
                            @include('vendor-views/form_modals/date_range')
                            <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning"
                                type="button" data-toggle="modal"
                                data-target="#dateRangeModal">{{ translate($preset) }}</button>
                        </form>
@if(hasPermission('boa_day_book', 'export'))
                        <a class="btn btn--primary  btn_sm "
                            href="{{ route('vendor.account.day-book.export') }}?date={{ date('Y-m-d') }}"><i
                                class="tio-upload-outlined"></i> Export</a>
@endif
@if(hasPermission('boa_day_book', 'import'))
                        <button data-toggle="modal" data-target="#importExcelModal" class=" btn_sm btn btn--primary">Import
                            Excel</button>
@endif
                    </div>
                </div>
                    @if(hasPermission('boa_day_book', 'import'))
                <div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog ">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Import Excel</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('vendor.account.day-book.import-excel') }}" method="post"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <a href="{{ asset('storage/app/public/util/example-daybook-import.xlsx') }}" download
                                        class="btn btn-outline-primary mb-2">View Example</a>
                                    <div class="form-group">
                                        <label for="file">Upload Excel File</label>
                                        <input type="file" name="file" class="form-control" id="file"
                                            accept=".xlsx,.xls">
                                    </div>
                                    <div class="form-group w-100 ">
                                        <button type="submit" class="btn btn-primary float-right">Import</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        <!-- Page Heading -->
    @if(hasPermission('boa_day_book', 'list'))
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="datatable"
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('messages.#') }}</th>
                                <th class="border-0">{{ translate('messages.Date') }}</th>
                                <th class="border-0">{{ translate('messages.Reference') }}</th>
                                <th class="border-0">{{ translate('messages.Particulars') }}</th>
                                <th class="border-0">{{ translate('messages.credit') }}</th>
                                <th class="border-0">{{ translate('messages.debit') }}</th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            @foreach ($data as $k => $e)
                                <tr>
                                    <td>{{ $k + 1 }}</td>
                                    <td>{{ date('Y-m-d', strtotime($e['entry_date'])) }}</td>
                                    <td>
                                        @if ($e['voucher_id'] || $e['invoice_id'])
                                            @php $invoice = _manualInvoice($e['invoice_id']) @endphp
                                            @if ($e['invoice_id'] != null && $invoice && $invoice->pdf)
                                                Invoice :
                                                <a href="{{ asset('storage/app/public/invoice') . '/' . $invoice->pdf }}"
                                                    target="_blank">
                                                    {{ $invoice->invoice_id }}
                                                </a>
                                            @elseif($e['voucher_id'])
                                                Voucher : {{ $e['voucher_id'] }}
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{ $e['particular'] }}</td>
                                    <td>
                                        @if ($e['type'] == 'credit')
                                            <span class="badge badge-soft-success">{{ _price($e['amount']) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($e['type'] == 'debit')
                                            <span class="badge badge-soft-danger">{{ _price($e['amount']) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if (count($data) === 0)
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
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
