@extends('layouts.admin.app')
@section('title', 'Petty Cashbook')
@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <style>
        .header-right {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .diff-cards {
            display: flex;
            gap: 16px;
        }

        .diff-card {
            background: #f7fafc;
            padding: 5px 24px;
            border-radius: 10px;
        }


        .diff-label {
            font-size: 12px;
            color: #718096;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .diff-value {
            font-size: 18px;
            font-weight: 700;
        }

        .diff-card.credit .diff-value {
            color: #2f855a;
        }

        .diff-card.debit .diff-value {
            color: #c53030;
        }

        .today-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .today-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.3);
        }

        .accounts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
            gap: 24px;
        }

        .account-card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }

        .account-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
        }

        .account-title {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
        }

        .date-range {
            font-size: 13px;
            color: #718096;
            background: #edf2f775;
            padding: 6px 14px;
            border-radius: 6px;
        }

        .totals {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            width: 100%;
        }

        .total-box {
            padding: 4px;
            /* width: 50%; */
            text-align: center;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }

        {{-- 
        .total-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 0px;
        } --}} .total-box.credit {
            background: linear-gradient(135deg, #f5fffd 0%, #c6f6d54a 100%);
        }

        .total-box.credit::before {
            background: linear-gradient(90deg, #48bb78, #38a169);
        }

        .total-box.debit {
            background: linear-gradient(135deg, #fff5f57a 0%, #fed7d761 100%);
        }

        .total-box.debit::before {
            background: linear-gradient(90deg, #f56565, #e53e3e);
        }

        .total-label {
            font-size: 11px;
            font-weight: 600;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0px;
        }

        .total-value {
            font-size: 21px;
            font-weight: 700;
        }

        .total-box.credit .total-value {
            color: #22543d;
        }

        .total-box.debit .total-value {
            color: #742a2a;
        }

        @media (max-width: 1200px) {
            .accounts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .header-right {
                flex-direction: column;
                width: 100%;
                align-items: stretch;
            }

            .diff-cards {
                flex-direction: column;
            }

            .account-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .totals {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h1 class="page-header-title mb-2">
                    <span class="page-header-icon">
                    </span>
                    <span>
                        Petty Cashbook
                        <span class="badge badge-soft-dark ml-2" id="itemCount"></span>
                    </span>
                </h1>
                <div class="d-flex gap-2">
                    @if (hasPermission('boa_petty_cashbook', 'add'))
                        <button class="btn btn-outline-primary bank-action-btn bank-secondary" type="button"
                            data-toggle="modal" data-target="#cashbookEntryModal">+ Entry</button>
                    @endif
                    {{-- <button class="btn btn-outline-primary bank-action-btn bank-secondary" type="button" data-toggle="modal"
                        data-target="#calendarModal">Calendar</button> --}}
                    <form action="" class=" date-range-form">
                        @include('vendor-views/form_modals/date_range')
                        <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                            data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                    </form>
                    {{--   <a class="btn btn-outline-primary  btn_sm "
                        href="{{ route('admin.account.banking.cash-book.export') }}?date={{ date('Y-m-d') }}"><i
                            class="tio-upload-outlined"></i> Export</a>

                    <button data-toggle="modal" data-target="#importExcelModal"
                        class=" btn_sm btn btn-outline-primary">Import
                    </button> --}}
                </div>


            </div>
        </div>
        @if (hasPermission('boa_petty_cashbook', 'list'))

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
                                    <th class="border-0">{{ translate('messages.type') }}</th>
                                    <th class="border-0">{{ translate('messages.Particulars') }}</th>
                                    <th class="border-0">{{ translate('messages.cash amount') }}</th>
                                    <th class="border-0">{{ translate('messages.reference') }}</th>
                                    <th class="border-0">{{ translate('messages.created_at') }}</th>
                                </tr>
                            </thead>
                            <tbody id="set-rows">
                                @foreach ($cashbook_entries as $k => $e)
                                    <tr>
                                        <td>{{ $k + 1 }}</td>
                                        <td>{{ date('Y-m-d', strtotime($e['entry_date'])) }}</td>


                                        <td>
                                            @if ($e['type'] == 'Received')
                                                <span class="badge badge-soft-success">Received</span>
                                            @elseif ($e['type'] == 'Paid')
                                                <span class="badge badge-soft-danger">Paid</span>
                                            @endif
                                        </td>
                                        <td>{{ $e['particular'] }}</td>
                                        <td>{{ _price($e['amount']) }}</td>
                                        <td>
                                            @if ($e['invoice_id'])
                                                @php $invoice = _manualInvoiceByInvoiceId($e['invoice_id']) @endphp
                                                Invoice: @if ($invoice && $invoice->pdf)
                                                    <a href="{{ asset('storage/app/public/invoice') . '/' . $invoice->pdf }}"
                                                        target="_blank">
                                                        {{ $e['invoice_id'] }}
                                                    </a>
                                                @else
                                                    {{ $e['invoice_id'] }}
                                                @endif
                                            @else
                                                {{ $e['reference_number'] ? 'Reference Number : ' . $e['reference_number'] : '' }}
                                            @endif
                                        </td>
                                        <td>{{ $e['created_at'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if (count($cashbook_entries) === 0)
                    <div class="empty--data">
                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div>
                @endif
            </div>
        @endif
        @if (hasPermission('boa_petty_cashbook', 'add'))

        <div class="modal fade" id="cashbookEntryModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog ">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Cashbook Entry</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.account.banking.cash-book.entry') }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" id="date"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="date">Type</label>
                                    <div class="pos--payment-options">
                                        <ul class="mb-0">
                                            <li>
                                                <label>
                                                    <input type="radio" name="type" value="Received" checked hidden>
                                                    <span>Received</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label>
                                                    <input type="radio" name="type" value="Paid" hidden>
                                                    <span>Paid</span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="amount">Amount</label>
                                    <input type="number" name="amount" class="form-control" id="amount"
                                        placeholder='Ex: 1200' step="0.001">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="particular">Particulars / Description</label>
                                    <input type="text" name="particular" class="form-control" id="particular"
                                        placeholder='Ex: Bill Payment'>
                                </div>
                                <div class="col-md-6">
                                    <div class="pos--payment-options">
                                        <ul class="mb-0">
                                            <li>
                                                <label>
                                                    <input type="radio" class="ref_type_inp" name="ref_type"
                                                        value="reference_number" checked hidden>
                                                    <span>Reference Number</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label>
                                                    <input type="radio" class="ref_type_inp" name="ref_type"
                                                        value="invoice_id" hidden>
                                                    <span>Invoice Id</span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="ref_inp_grp">
                                        <label for="particular">Reference Number</label>
                                        <input type="text" name="reference_number" class="form-control"
                                            id="particular" placeholder='Reference Number'>
                                    </div>
                                    <div class="inv_inp_grp" style="display: none;">
                                        <label for="particular">Invoice Id</label>
                                        <input type="text" name="invoice_id" class="form-control" id="particular"
                                            placeholder='Invoice Id'>
                                    </div>
                                </div>


                            </div>

                            <div class="form-group w-100 ">
                                <button type="submit" class="btn btn-primary float-right">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if (hasPermission('boa_petty_cashbook', 'import'))

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

                        <form action="{{ route('admin.account.banking.cash-book.import') }}" method="post"
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
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
    <script>
        $(".ref_type_inp").on('click', function() {
            if ($(this).val() == 'reference_number') {
                $('.ref_inp_grp').show();
                $('.inv_inp_grp').hide();
            } else {
                $('.ref_inp_grp').hide();
                $('.inv_inp_grp').show();
            }
        })
    </script>
@endpush
