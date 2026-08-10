 @extends('layouts.vendor.app')
 @section('title', 'GST Report')
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
                         GST
                         <span class="badge badge-soft-dark ml-2" id="itemCount"></span>
                     </span>
                 </h1>


                 <div class="d-flex align-items-center">

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

         <div class="card-header d-flex flex-column align-items-start shadow rounded mb-3">
             <div class="account-header">
             </div>
             <div class="totals">
                 <div class="total-box credit">
                     <div class="total-label">Total Tax</div>
                     <div class="total-value">{{ _price($invoices->sum('final_tax')) }}</div>
                 </div>
                 <div class="total-box debit">
                     <div class="total-label">Total Taxable</div>
                     <div class="total-value">{{ _price($invoices->sum('taxable_amount')) }}</div>
                 </div>
             </div>
         </div>
         <div class="row">
             <div class="col-md-6 p-1">
                 <div class="card">


                     <div class="card-body p-0">
                         <div class="d-flex justify-content-between align-items-center m-2">

                             <h2>Sale Invoices</h2>
                             <div class="d-flex gap-1 flex-wrap align-items-center">
                                 @if (hasPermission('gst_report', 'delete'))
                                     @include('vendor-views.inventory.report._bulk_delete', [
                                         'scope' => 'gstsale',
                                         'deleteRoute' => route('vendor.inventory.report.gst-bulk-delete', ['side' => 'sale']),
                                         'checkboxClass' => 'check_select_gst_sale',
                                         'selectedText' => 'the selected sale invoices',
                                         'allText' => 'every sale invoice in this report',
                                         'restockLabel' => 'Add the sold stock back to inventory',
                                     ])
                                 @endif

                                 @if (hasPermission('gst_report', 'export'))
                                     <a class="btn btn-outline-primary mr-1"
                                         href="{{ route('vendor.inventory.report.gst', ['export-sale']) }}">
                                         Export
                                     </a>
                                 @endif
                             </div>
                         </div>
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
                                         @if (hasPermission('gst_report', 'delete'))
                                             <th class="border-0"></th>
                                         @endif
                                         <th class="border-0">{{ translate('messages.#') }}</th>
                                         <th class="border-0">{{ translate('messages.Date') }}</th>
                                         <th class="border-0">{{ translate('messages.Invoice Id') }}</th>
                                         <th class="border-0">{{ translate('messages.Bill Type') }}</th>
                                         <th class="border-0">{{ translate('messages.Customer / Vendor Name') }}</th>
                                         <th class="border-0">{{ translate('messages.GST Number') }}</th>
                                         <th class="border-0">{{ translate('messages.Taxable Value') }}</th>
                                         <th class="border-0">{{ translate('messages.Tax Rate') }}</th>
                                         <th class="border-0">{{ translate('messages.Tax Amount') }}</th>
                                         <th class="border-0">{{ translate('messages.CGST') }}</th>
                                         <th class="border-0">{{ translate('messages.SGST') }}</th>
                                         <th class="border-0">{{ translate('messages.IGST / UGST') }}</th>
                                         <th class="border-0">{{ translate('messages.Invoice Total') }}</th>
                                     </tr>
                                 </thead>
                                 <tbody id="set-rows">
                                     @php $key = 0; @endphp
                                     @foreach ($saleInvoices as $k => $e)
                                         @php $key++; @endphp
                                         <tr>
                                             @if (hasPermission('gst_report', 'delete'))
                                                 <td class="text-center">
                                                     {{-- Manual and Service invoices share this table, so the id carries its type --}}
                                                     <input type="checkbox" onclick="event.stopPropagation()"
                                                         class="check_select_gst_sale"
                                                         value="{{ ($e['invoice_type'] ?? 'Manual') === 'Service' ? 'service' : 'manual' }}:{{ $e->id }}">
                                                 </td>
                                             @endif
                                             <td>{{ $key }}</td>
                                             <td>{{ $e['invoice_date'] ?? date('Y-m-d', strtotime($e['created_at'])) }}
                                             </td>
                                             <td>
                                                 @if ($e->pdf)
                                                     <a href="{{ asset('storage/app/public/invoice') . '/' . $e->pdf }}"
                                                         target="_blank">
                                                         {{ $e->invoice_id }}
                                                     </a>
                                                 @endif
                                             </td>
                                             <td>{{ $e['bill_type'] . ' - ' . $e['invoice_type'] }}</td>
                                             <td>
                                                 @php
                                                     $name = null;
                                                     $profileLink = null;

                                                     if ($e['invoice_type'] === 'Service' && $e['websiteUser']) {
                                                         $name = trim(
                                                             $e['websiteUser']?->f_name .
                                                                 ' ' .
                                                                 $e['websiteUser']?->l_name,
                                                         );
                                                     } elseif ($e['bill_type'] === 'Sale' && $e['storeCustomer']) {
                                                         $name = trim(
                                                             $e['storeCustomer']?->f_name .
                                                                 ' ' .
                                                                 $e['storeCustomer']?->l_name,
                                                         );
                                                         $profileLink = route('vendor.customer.view', [
                                                             $e['storeCustomer']->id,
                                                         ]);
                                                     } elseif ($e['seller']) {
                                                         $name = trim(
                                                             $e['seller']?->f_name . ' ' . $e['seller']?->l_name,
                                                         );
                                                         $profileLink = route('vendor.customer.view', [
                                                             $e['seller']->id,
                                                         ]);
                                                     }
                                                 @endphp

                                                 @if ($name)
                                                     @if ($profileLink)
                                                         <a href="{{ $profileLink }}">{{ $name }}</a>
                                                     @else
                                                         {{ $name }}
                                                     @endif
                                                 @endif


                                             </td>
                                             <td>
                                                 @if ($e['invoice_type'] === 'Service')
                                                     {{ $e['websiteUser']?->gst ?? '' }}
                                                 @elseif ($e['bill_type'] === 'Sale')
                                                     {{ $e['storeCustomer']?->gst ?? '' }}
                                                 @else
                                                     {{ $e['seller']?->gst ?? '' }}
                                                 @endif

                                             </td>
                                             <td>
                                                 {{ _price($e['taxable_amount'] ?? $e['total_amount']) }}
                                             </td>
                                             <td>
                                                 @if (!empty($e['invoiceItems']))
                                                     @php
                                                         $taxes = $e['invoiceItems']
                                                             ->whereNotNull('tax')
                                                             ->where('tax', '!=', '')
                                                             ->pluck('tax')
                                                             ->toArray();
                                                     @endphp

                                                     @if (!empty($taxes))
                                                         {{ implode('%, ', $taxes) }}%
                                                     @endif
                                                 @endif
                                             </td>

                                             <td> {{ _price($e['final_tax']) }}</td>
                                             <td>
                                                 {{ _price($e['cgst']) }}
                                             </td>
                                             <td>
                                                 {{ _price($e['sgst']) }}
                                             </td>
                                             <td>
                                                 {{ _price($e['igst']) }}
                                             </td>
                                             <td>
                                                 {{ _price($e['total_amount']) }}
                                             </td>
                                         </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                     </div>
                     @if (count($saleInvoices) === 0)
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
                     <div class="card-body p-0">
                         <div class="d-flex justify-content-between align-items-center m-2">
                             <h2>Purchase Invoices</h2>
                             <div class="d-flex gap-1 flex-wrap align-items-center">
                                 @if (hasPermission('gst_report', 'delete'))
                                     @include('vendor-views.inventory.report._bulk_delete', [
                                         'scope' => 'gstpurchase',
                                         'deleteRoute' => route('vendor.inventory.report.gst-bulk-delete', ['side' => 'purchase']),
                                         'checkboxClass' => 'check_select_gst_purchase',
                                         'selectedText' => 'the selected purchase bills',
                                         'allText' => 'every purchase bill in this report',
                                         'restockLabel' => 'Remove the purchased stock from inventory',
                                     ])
                                 @endif

                                 @if (hasPermission('gst_report', 'export'))
                                     <a class="btn btn-outline-primary mr-1"
                                         href="{{ route('vendor.inventory.report.gst', ['export-purchase']) }}">
                                         Export
                                     </a>
                                 @endif
                             </div>
                         </div>
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
                                         @if (hasPermission('gst_report', 'delete'))
                                             <th class="border-0"></th>
                                         @endif
                                         <th class="border-0">{{ translate('messages.#') }}</th>
                                         <th class="border-0">{{ translate('messages.Date') }}</th>
                                         <th class="border-0">{{ translate('messages.Invoice Id') }}</th>
                                         <th class="border-0">{{ translate('messages.Bill Type') }}</th>
                                         <th class="border-0">{{ translate('messages.Customer / Vendor Name') }}</th>
                                         <th class="border-0">{{ translate('messages.GST Number') }}</th>
                                         <th class="border-0">{{ translate('messages.Taxable Value') }}</th>
                                         <th class="border-0">{{ translate('messages.Tax Rate') }}</th>
                                         <th class="border-0">{{ translate('messages.Tax Amount') }}</th>
                                         <th class="border-0">{{ translate('messages.CGST') }}</th>
                                         <th class="border-0">{{ translate('messages.SGST') }}</th>
                                         <th class="border-0">{{ translate('messages.IGST / UGST') }}</th>
                                         <th class="border-0">{{ translate('messages.Invoice Total') }}</th>
                                     </tr>
                                 </thead>
                                 <tbody id="set-rows">
                                     @php $key = 0; @endphp

                                     @foreach ($purchaseInvoices as $k => $e)
                                         @php $key ++ ; @endphp

                                         <tr>
                                             @if (hasPermission('gst_report', 'delete'))
                                                 <td class="text-center">
                                                     <input type="checkbox" onclick="event.stopPropagation()"
                                                         class="check_select_gst_purchase" value="manual:{{ $e->id }}">
                                                 </td>
                                             @endif
                                             <td>{{ $key }}</td>
                                             <td>{{ $e['invoice_date'] ?? date('Y-m-d', strtotime($e['created_at'])) }}
                                             </td>
                                             <td>
                                                 @if ($e->pdf)
                                                     <a href="{{ asset('storage/app/public/invoice') . '/' . $e->pdf }}"
                                                         target="_blank">
                                                         {{ $e->invoice_id }}
                                                     </a>
                                                 @endif
                                             </td>
                                             <td>{{ $e['bill_type'] . ' - ' . $e['invoice_type'] }}</td>
                                             <td>
                                                 @php
                                                     $name = null;
                                                     $profileLink = null;

                                                     if ($e['invoice_type'] === 'Service' && $e['websiteUser']) {
                                                         $name = trim(
                                                             $e['websiteUser']?->f_name .
                                                                 ' ' .
                                                                 $e['websiteUser']?->l_name,
                                                         );
                                                     } elseif ($e['bill_type'] === 'Sale' && $e['storeCustomer']) {
                                                         $name = trim(
                                                             $e['storeCustomer']?->f_name .
                                                                 ' ' .
                                                                 $e['storeCustomer']?->l_name,
                                                         );
                                                         $profileLink = route('vendor.customer.view', [
                                                             $e['storeCustomer']->id,
                                                         ]);
                                                     } elseif ($e['seller']) {
                                                         $name = trim(
                                                             $e['seller']?->f_name . ' ' . $e['seller']?->l_name,
                                                         );
                                                         $profileLink = route('vendor.customer.view', [
                                                             $e['seller']->id,
                                                         ]);
                                                     }
                                                 @endphp

                                                 @if ($name)
                                                     @if ($profileLink)
                                                         <a href="{{ $profileLink }}">{{ $name }}</a>
                                                     @else
                                                         {{ $name }}
                                                     @endif
                                                 @endif


                                             </td>
                                             <td>
                                                 @if ($e['invoice_type'] === 'Service')
                                                     {{ $e['websiteUser']?->gst ?? '' }}
                                                 @elseif ($e['bill_type'] === 'Sale')
                                                     {{ $e['storeCustomer']?->gst ?? '' }}
                                                 @else
                                                     {{ $e['seller']?->gst ?? '' }}
                                                 @endif

                                             </td>
                                             <td>
                                                 {{ _price($e['taxable_amount'] ?? $e['total_amount']) }}
                                             </td>
                                             <td>
                                                 @if (!empty($e['invoiceItems']))
                                                     @php
                                                         $taxes = $e['invoiceItems']
                                                             ->whereNotNull('tax')
                                                             ->where('tax', '!=', '')
                                                             ->pluck('tax')
                                                             ->toArray();
                                                     @endphp

                                                     @if (!empty($taxes))
                                                         {{ implode('%, ', $taxes) }}%
                                                     @endif
                                                 @endif
                                             </td>

                                             <td> {{ _price($e['final_tax']) }}</td>
                                             <td>
                                                 {{ _price($e['cgst']) }}
                                             </td>
                                             <td>
                                                 {{ _price($e['sgst']) }}
                                             </td>
                                             <td>
                                                 {{ _price($e['igst']) }}
                                             </td>
                                             <td>
                                                 {{ _price($e['total_amount']) }}
                                             </td>
                                         </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                     </div>
                     @if (count($purchaseInvoices) === 0)
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

     </div>
 @endsection

 @push('script_2')
     @include('vendor-views/js/date_range')
 @endpush
