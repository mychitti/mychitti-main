 @extends('layouts.vendor.app')
 @section('title', 'Bank Reconciliation')
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

         .mismatch-row {
             background: #ff6e6e42;
             border: 1px solid white;
         }

         .mismatch-row:hover {
             background: #ff6e6e5a !important;
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
                         Bank Reconciliation
                         <span class="badge badge-soft-dark ml-2" id="itemCount"></span>
                     </span>
                 </h1>

                 <div class="card shadow p-2 header-right">
                     <div class="diff-cards">
                         <div class="diff-card credit">
                             <div class="diff-label">Diff in Credit</div>
                             <div class="diff-value">
                                 {{ _price($bank_entries->where('type', 'credit')->sum('amount') - $daybook_entries->where('type', 'credit')->sum('amount')) }}
                             </div>
                         </div>
                         <div class="diff-card debit">
                             <div class="diff-label">Diff in Debit</div>
                             <div class="diff-value">
                                 {{ _price($bank_entries->where('type', 'debit')->sum('amount') - $daybook_entries->where('type', 'debit')->sum('amount')) }}
                             </div>
                         </div>
                     </div>
                 </div>
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
                     </div>
                 </div>
             </div>
         </div>
         <!-- Page Heading -->
         @if(hasPermission('banking_bank_reconciliation', 'list'))
         <div class="row">
             <div class="col-md-6 p-1">
                 <div class="card">
                     <div class="card-header d-flex flex-column align-items-start">
                         <div class="account-header">
                             <h2 class="account-title">Cash Book Entries</h2>
                             <span class="date-range">{{ $from }} to {{ $to }}
                                 ({{ $preset }})</span>
                         </div>
                         <div class="totals">
                             <div class="total-box credit">
                                 <div class="total-label">Total Credit</div>
                                 <div class="total-value">
                                     {{ _price($daybook_entries->where('type', 'credit')->sum('amount')) }}</div>
                             </div>
                             <div class="total-box debit">
                                 <div class="total-label">Total Debit</div>
                                 <div class="total-value">
                                     {{ _price($daybook_entries->where('type', 'debit')->sum('amount')) }}</div>
                             </div>
                         </div>

                     </div>

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
                                         <th class="border-0">{{ translate('messages.Reference') }}</th>
                                         <th class="border-0">{{ translate('messages.Particulars') }}</th>
                                         <th class="border-0">{{ translate('messages.credit') }}</th>
                                         <th class="border-0">{{ translate('messages.debit') }}</th>
                                         <th class="border-0">{{ translate('messages.Action') }}</th>
                                         <th class="border-0">{{ translate('messages.Created At') }}</th>
                                     </tr>
                                 </thead>
                                 <tbody id="set-rows">
                                     @foreach ($daybook_entries as $k => $e)
                                         <tr class="{{ $e['mismatch'] ? 'mismatch-row' : '' }}">
                                             <td>{{ $k + 1 }}</td>
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
                                                     <span class="badge badge-soft-dark">{{ _price($e['amount']) }}</span>
                                                 @endif
                                             </td>
                                             <td>
                                                 @if ($e['type'] == 'debit')
                                                     <span class="badge badge-soft-dark">{{ _price($e['amount']) }}</span>
                                                 @endif
                                             </td>
                                             <td>
                                                 @if ($e['mismatch'])
                                                     <a href="{{ route('vendor.account.banking.bank-account.index') }}"
                                                         class="btn btn-primary btn-sm">Add Transaction</a>
                                                 @endif
                                             </td>
                                             <td>{{ $e['created_at'] }}</td>
                                         </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                     </div>
                     @if (count($daybook_entries) === 0)
                         <div class="empty--data">
                             <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                             <h5>
                                 {{ translate('no_data_found') }}
                             </h5>
                         </div>
                     @endif
                 </div>
             </div>
             <div class="col-md-6 p-1">
                 <div class="card">
                     <div class="card-header d-flex flex-column align-items-start">
                         <div class="account-header">
                             <h2 class="account-title">Bank Account Entries</h2>
                             <span class="date-range">{{ $from }} to {{ $to }}
                                 ({{ $preset }})</span>
                         </div>
                         <div class="totals">
                             <div class="total-box credit">
                                 <div class="total-label">Total Credit</div>
                                 <div class="total-value">
                                     {{ _price($bank_entries->where('type', 'credit')->sum('amount')) }}</div>
                             </div>
                             <div class="total-box debit">
                                 <div class="total-label">Total Debit</div>
                                 <div class="total-value">
                                     {{ _price($bank_entries->where('type', 'debit')->sum('amount')) }}</div>
                             </div>
                         </div>
                     </div>

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
                                         <th class="border-0">{{ translate('messages.Reference Number') }}</th>
                                         <th class="border-0">{{ translate('messages.Transaction Id') }}</th>
                                         <th class="border-0">{{ translate('messages.Particulars') }}</th>
                                         <th class="border-0">{{ translate('messages.credit') }}</th>
                                         <th class="border-0">{{ translate('messages.debit') }}</th>
                                         <th class="border-0">{{ translate('messages.bank_name') }}</th>
                                         <th class="border-0">{{ translate('messages.Action') }}</th>
                                         <th class="border-0">{{ translate('messages.Created At') }}</th>
                                     </tr>
                                 </thead>
                                 <tbody id="set-rows">
                                     @foreach ($bank_entries as $k => $e)
                                         <tr class="{{ $e['mismatch'] ? 'mismatch-row' : '' }} ">
                                             <td>{{ $k + 1 }}</td>
                                             <td>
                                                 {{ $e['reference_number'] }}
                                             </td>
                                             <td>
                                                 {{ $e['txn_id'] }}
                                             </td>
                                             <td style="white-space: normal; word-break: normal; max-width: 250px;">
                                                 {{ $e['particulars'] }}</td>

                                             <td>
                                                 @if ($e['type'] == 'credit')
                                                     <span class="badge badge-soft-dark">{{ _price($e['amount']) }}</span>
                                                 @endif
                                             </td>
                                             <td>
                                                 @if ($e['type'] == 'debit')
                                                     <span class="badge badge-soft-dark">{{ _price($e['amount']) }}</span>
                                                 @endif
                                             </td>
                                             <td><b>{{ $e['bankAccount']?->bank_name }}</b><br>{{ $e['bankAccount']?->account_number }}
                                             </td>
                                             <td>
                                                 @if ($e['mismatch'])
                                                     <a href="{{ route('vendor.invoice.manual-bill') }}"
                                                         class="btn btn-primary btn-sm">Add Bill</a>
                                                 @endif
                                             </td>
                                             <td>{{ $e['created_at'] }}</td>
                                         </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                     </div>
                     @if (count($bank_entries) === 0)
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
         @endif
     </div>
 @endsection

 @push('script_2')
     @include('vendor-views/js/date_range')
 @endpush
