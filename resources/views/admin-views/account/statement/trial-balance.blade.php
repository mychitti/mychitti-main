 @extends('layouts.admin.app')
 @section('title', 'Trial Balance')
 @push('css_or_js')
     <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
     <style>
         .tb-wrapper {
             {{-- font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; --}} max-width: 1400px;
             margin: 0 auto;
             padding: 20px;
             {{-- background: #f5f7fa; --}} min-height: 100vh;
         }

         .tb-header {
             padding: 30px;
             border-radius: 12px;
             margin-bottom: 30px;
             box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
         }

         .tb-title {
             margin: 0;
             font-size: 28px;
             font-weight: 600;
         }

         .tb-subtitle {
             margin: 8px 0 0 0;
             opacity: 0.9;
             font-size: 14px;
         }

         .tb-controls {
             display: flex;
             gap: 15px;
             margin-bottom: 20px;
             flex-wrap: wrap;
             align-items: center;
         }

         .tb-date-picker {
             padding: 10px 15px;
             border: 2px solid #e1e8ed;
             border-radius: 8px;
             font-size: 14px;
             background: white;
         }

         .tb-btn {
             padding: 10px 20px;
             background: #667eea;
             color: white;
             border: none;
             border-radius: 8px;
             cursor: pointer;
             font-size: 14px;
             font-weight: 500;
             transition: background 0.3s;
         }

         .tb-btn:hover {
             background: #5568d3;
         }

         .tb-btn-secondary {
             background: #48bb78;
         }

         .tb-btn-secondary:hover {
             background: #38a169;
         }

         .tb-grid {
             display: grid;
             grid-template-columns: 1fr 1fr;
             gap: 20px;
             margin-bottom: 20px;
         }

         .tb-card {
             background: white;
             border-radius: 12px;
             padding: 25px;
             box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
         }

         .tb-card-title {
             font-size: 18px;
             font-weight: 600;
             margin: 0 0 15px 0;
             color: #2d3748;
         }

         .tb-table-container {
             overflow-x: auto;
         }

         .tb-table {
             width: 100%;
             border-collapse: collapse;
         }

         .tb-table th {
             background: #f8f9fa;
             padding: 12px 15px;
             text-align: left;
             font-weight: 600;
             color: #2d3748;
             border-bottom: 2px solid #e2e8f0;
             font-size: 13px;
             text-transform: uppercase;
             letter-spacing: 0.5px;
         }

         .tb-table td {
             padding: 12px 15px;
             border-bottom: 1px solid #e2e8f0;
             color: #4a5568;
             font-size: 14px;
         }

         .tb-table tbody tr:hover {
             background: #f7fafc;
         }

         .tb-amount {
             font-weight: 500;
         }

         .tb-total-row {
             background: #edf2f7 !important;
             font-weight: 600;
             color: #2d3748;
         }

         .tb-total-row td {
             border-top: 2px solid #667eea;
             border-bottom: 3px double #667eea;
             padding: 15px;
         }

         .tb-balanced {
             display: inline-block;
             padding: 4px 12px;
             background: #c6f6d5;
             color: #22543d;
             border-radius: 20px;
             font-size: 12px;
             font-weight: 600;
             margin-left: 10px;
         }

         .tb-unbalanced {
             display: inline-block;
             padding: 4px 12px;
             background: #fed7d7;
             color: #742a2a;
             border-radius: 20px;
             font-size: 12px;
             font-weight: 600;
             margin-left: 10px;
         }

         .tb-account-code {
             color: #718096;
             font-size: 12px;
         }

         .tb-summary {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
             gap: 15px;
             margin-bottom: 20px;
         }

         .tb-stat-card {
             background: white;
             padding: 20px;
             border-radius: 10px;
             box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
         }

         .tb-stat-label {
             font-size: 12px;
             color: #718096;
             text-transform: uppercase;
             letter-spacing: 0.5px;
             margin-bottom: 8px;
         }

         .tb-stat-value {
             font-size: 24px;
             font-weight: 600;
             color: #2d3748;
         }

         @media (max-width: 768px) {
             .tb-grid {
                 grid-template-columns: 1fr;
             }
         }
     </style>
 @endpush

 @section('content')
     <div class="content container-fluid">
         <div class="page-header">
             <div class="d-flex flex-wrap justify-content-between align-items-center">
                 <div>
                     <h1 class="page-header-title mb-2">
                         <span class="page-header-icon">
                         </span>
                         <span>
                             Trial Balance
                             <span class="badge badge-soft-dark ml-2" id="itemCount"></span>
                         </span>
                     </h1><br>
                     <p class="tb-subtitle">Automated from Master Ledger</p>
                 </div>



                 <div class="d-flex align-items-center">

                     <div class="d-flex gap-2">

                         {{-- <a href="#" class="btn btn_sm btn-outline-primary ">
                             Export
                         </a> --}}
                     </div>
                 </div>
             </div>
         </div>
         <!-- Page Heading -->
@if(hasPermission('statements_trial_balance', 'list'))
         <div class="">
             <div class="">

                 <div class="tb-summary" id="tbSummary">
                     <div class="tb-stat-card">
                         <div class="tb-stat-label">Total Debit</div>
                         <div class="tb-stat-value text-danger" id="tbStatDebit">
                             {{ _price($ledgerAccounts->sum('total_debit')) }}</div>
                     </div>
                     <div class="tb-stat-card">
                         <div class="tb-stat-label">Total Credit</div>
                         <div class="tb-stat-value text-success" id="tbStatCredit">
                             {{ _price($ledgerAccounts->sum('total_credit')) }}</div>
                     </div>
                     <div class="tb-stat-card">
                         <div class="tb-stat-label">Difference</div>
                         @php $difference = abs($ledgerAccounts->sum('total_credit')  - $ledgerAccounts->sum('total_debit'));@endphp
                         <div class="tb-stat-value {{ $difference > 0 ? 'text-danger' : '' }}" id="tbStatDiff">
                             {{ _price($difference) }}</div>
                     </div>
                     <div class="tb-stat-card">
                         <div class="tb-stat-label">Status</div>
                         <div class="tb-stat-value" style="font-size: 18px;" id="tbStatStatus">
                             @if ($difference > 0)
                                 <span class="tb-unbalanced">✗ Unbalanced</span>
                             @else
                                 <span class="tb-balanced">✓ Balanced</span>
                             @endif

                         </div>
                     </div>
                 </div>

                 <div class="tb-card">
                     <h2 class="tb-card-title">Account Balances</h2>
                     <div class="tb-table-container">
                         <table class="tb-table">
                             <thead>
                                 <tr>
                                     <th>Account Code</th>
                                     <th>Account Name</th>
                                     <th class="tb-amount">Debit Balance</th>
                                     <th class="tb-amount">Credit Balance</th>
                                 </tr>
                             </thead>
                             <tbody id="tbTableBody">
                                 @foreach ($ledgerAccounts as $key => $account)
                                     <tr>
                                         <td><span class="tb-account-code">{{ $account['code'] }}</span></td>
                                         <td>{{ $account['name'] }}</td>
                                         <td class="tb-amount text-danger">
                                             {{ $account->total_debit > 0 ? _price($account->total_debit) : '' }}</td>
                                         <td class="tb-amount text-success">
                                             {{ $account->total_credit > 0 ? _price($account->total_credit) : '' }}</td>
                                     </tr>
                                 @endforeach
                                 {{-- <tr>
                                     <td><span class="tb-account-code">${row.code}</span></td>
                                     <td>${row.name}</td>
                                     <td class="tb-amount">${row.debit > 0 ? formatAmount(row.debit) : '-'}</td>
                                     <td class="tb-amount">${row.credit > 0 ? formatAmount(row.credit) : '-'}</td>
                                 </tr> --}}

                             </tbody>
                             <tfoot id="tbTableFoot" style="display: none;">
                                 <tr class="tb-total-row">
                                     <td colspan="2"><strong>Total</strong> <span id="tbBalanceStatus"></span></td>
                                     <td class="tb-amount" id="tbTotalDebit">0.00</td>
                                     <td class="tb-amount" id="tbTotalCredit">0.00</td>
                                 </tr>
                             </tfoot>
                         </table>
                     </div>
                 </div>
             </div>

         </div>
         @endif
     </div>
 @endsection

 @push('script_2')
     @include('vendor-views/js/date_range')
 @endpush
