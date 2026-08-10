 @extends('layouts.vendor.app')
 @section('title', 'Sales Report')
 @push('css_or_js')
     <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
     <style>
         .report-summary {
             display: flex;
             gap: 1rem;
             flex-wrap: wrap;
         }

         .report-card {
             flex: 1;
             min-width: 108px;
             background: #fff;
             border: 1px solid #eee;
             border-radius: 8px;
             padding: 1rem;
             box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
         }

         .report-card:nth-child(1) {
             background: #d9e4ffff;
         }

         .report-card:nth-child(4) {
             background: #d7fff4ff;
         }

         .report-card:nth-child(3) {
             background: #ffefddff;
         }

         .report-card:nth-child(2) {
             background: #f3ffe0ff;
         }

         .report-title {
             font-size: 0.9rem;
             color: #666;
             margin-bottom: 0.5rem;
         }

         .report-value {
             font-size: 1.2rem;
             font-weight: bold;
             color: #222;
         }
     </style>
 @endpush

 @section('content')

     <div class="content container-fluid p-3">
         <div class="page-header">
             <div class="d-flex flex-wrap px-3 w-100">
                 <div class="d-flex w-100 flex-wrap justify-content-between  align-orders-center">
                     <h1 class="page-header-title mb-2 d-flex gap-2">
                         <span class="page-header-icon">
                             <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                         </span>
                         <div class="d-flex align-items-start">
                             <div class="d-flex flex-column">
                                 <span>Sales Report</span>
                                 <span style="font-size: 15px;font-weight: normal;">({{ translate($preset) }})</span>
                             </div>
                             <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($invoices) }}</span>
                         </div>
                     </h1>
                 </div>
             </div>
         </div>
         <!-- Page Heading -->

         @if (hasPermission('sale_report', 'list'))
             <div class="card">
                 <div class="report-summary p-2">
                     <div class="report-card">
                         <h4 class="report-title">Total Sales Amount</h4>
                         <p class="report-value">{{ _price($data['totalOrderAmount'], 'ceil', 2) }}</p>
                     </div>

                     <div class="report-card">
                         <h4 class="report-title">Total Tax Collected</h4>
                         <p class="report-value">{{ _price($data['totalTaxAmount'], 'ceil', 2) }}</p>
                     </div>
                     <div class="report-card">
                         <h4 class="report-title">Total Items</h4>
                         <p class="report-value">{{ $data['unique_items_sold'] }}</p>
                     </div>
                 </div>
             </div>
         @endif

         <div class="card mt-3">
             <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-2">

                 <div class="d-flex gap-1 flex-wrap">
                     {{-- Search --}}
                     <form action="">
                         <div class="input-group flex-nowrap">
                             <input type="search" name="search" value="{{ request()?->search ?? '' }}"
                                 class="form-control" style="min-width:198px"
                                 placeholder="{{ translate('messages.search by invoice id') }}">
                             <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                         </div>
                     </form>

                     {{-- Date Range --}}
                     <form action="" class="date-range-form">
                         <input type="hidden" name="tab" value="entry">
                         <button type="button" class="btn btn-outline-warning" data-toggle="modal"
                             data-target="#dateRangeModal">
                             {{ translate($preset) }}
                         </button>
                         {{-- modal --}}
                         @include('vendor-views/form_modals/date_range')
                     </form>

                     {{-- Branch --}}
                     <form action="">
                         @include('vendor-views.inventory.report._branch_filter')
                     </form>


                     @if (hasPermission('sale_report', 'export') || hasPermission('sale_report', 'delete'))
                         <div class="badge badge-soft-success align-items-center"
                             style="    height: 39px;    display: flex;">
                             <div class="form-check mr-1">
                                 <input type="checkbox" class="form-check-input" id="check_all">
                                 <label style=" white-space: nowrap;" class="mt-1 form-check-label" id="check_all_label"
                                     for="check_all">Select All</label>
                             </div>
                         </div>
                     @endif

                     @if (hasPermission('sale_report', 'export'))
                         <!-- Download Button -->
                         <div class="mr-1 delete_selected_btn" style="display:none;">

                             <button id="download_selected" style=" white-space: nowrap;"
                                 class="btn btn-sm btn-outline-primary px-3 py-2 btn_sm " title="Download Selected">
                                 <i class="tio-download"></i> Download Selected
                             </button>
                         </div>
                     @endif

                     @if (hasPermission('sale_report', 'delete'))
                         @include('vendor-views.inventory.report._bulk_delete', [
                             'scope' => 'sale',
                             'deleteRoute' => route('vendor.inventory.report.sale-bulk-delete'),
                             'selectedText' => 'the selected sale invoices',
                             'allText' => 'every sale invoice in this report',
                             'checkAllId' => 'check_all',
                             'renderSelectAll' => false,
                             'restockLabel' => 'Add the sold stock back to inventory',
                         ])
                     @endif

                     @if (hasPermission('sale_report', 'export'))
                         <a href="{{ route('vendor.inventory.report.sale', ['export', 'pdf']) }}"
                             class="btn text-dark border border-dark btn-outline-light">
                             <img src="{{ asset('storage/app/public/util/pdf-icon.jpg') }}" height="18px" alt="">
                             Pdf</a>
                         <a href="{{ route('vendor.inventory.report.sale', ['export', 'excel']) }}"
                             class="btn text-dark border border-dark btn-outline-light">
                             <img src="{{ asset('storage/app/public/util/excel-icon.png') }}" height="18px"
                                 alt="">
                             Excel</a>
                     @endif
                 </div>
             </div>
             @if (hasPermission('sale_report', 'list'))

                 <div class="table-responsive datatable-custom" id="table">
                     <table id="datatable"
                         class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                         <thead class="thead-light">
                             <tr>
                                 <th class="border-0"></th>
                                 <th class="border-0">{{ translate('sl') }}</th>
                                 <th class="border-0">Invoice Id</th>
                                 <th class="border-0">Date</th>
                                 <th class="border-0">Customer Name</th>
                                 <th class="border-0">Total Amount</th>
                                 <th class="border-0">CGST Amt.</th>
                                 <th class="border-0">SGST Amt.</th>
                                 <th class="border-0">IGST Amt.</th>
                                 <th class="border-0">Payment Status</th>
                             </tr>
                         </thead>

                         <tbody id="">
                             @foreach ($invoices as $key => $invoice)
                                 <tr>
                                     <td class="text-center"> <input type="checkbox" onclick="event.stopPropagation()"
                                             name="invoice_id[]" value="{{ $invoice->id }}" class="check_select "
                                             id="">
                                     </td>
                                     <td>{{ $key + 1 }}</td>
                                     <td>
                                         <div style="">
                                             <a
                                                 href="{{ $invoice->invoice?->pdf ? asset('storage/app/public/invoice/') . '/' . $invoice->invoice?->pdf : 'javascript:;' }}">
                                                 {{ $invoice->invoice?->invoice_id ?? 'N/A' }}
                                             </a>
                                         </div>
                                     </td>
                                     <td>{{ $invoice->invoice_date ?? $invoice->created_at }}</td>
                                     <td>{{ ($customer = $invoice->invoice?->storeCustomer)
                                         ? $customer->f_name . ' ' . $customer->l_name
                                         : 'Customer Deleted' }}
                                     </td>
                                     <td><span
                                             class="badge badge-soft-success">{{ _price($invoice->total_amount) }}</span>
                                     </td>

                                     <td>{{ $invoice->invoice?->bill_gst_type == 'cgst_sgst' ? _price($invoice->invoice?->final_tax / 2) : '' }}
                                     </td>
                                     <td>{{ $invoice->invoice?->bill_gst_type == 'cgst_sgst' ? _price($invoice->invoice?->final_tax / 2) : '' }}
                                     </td>
                                     <td>{{ $invoice->invoice?->bill_gst_type == 'igst' ? _price($invoice->invoice?->final_tax) : '' }}
                                     </td>
                                     <td>
                                         @if (in_array($invoice->payment_status, ['unpaid', 'Unpaid']))
                                             <span class="badge badge-soft-danger">Unpaid</span>
                                         @else
                                             <span class="badge badge-soft-success">Paid</span>
                                         @endif
                                     </td>
                                    
                                 </tr>
                             @endforeach

                         </tbody>
                     </table>
                     @if (!count($invoices))
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

 @endsection
 @push('script_2')
     <script>
         $("#check_all").on('change', function() {
             if ($(this).prop('checked') == true) {
                 $("#check_all_label").text('Deselect All');
                 $(".check_select").prop('checked', true)
                 $('.delete_selected_btn').show()
             } else {
                 $("#check_all_label").text('Select All');
                 $(".check_select").prop('checked', false)
                 $('.delete_selected_btn').hide()
             }
         })
         $(".check_select").on('change', function() {
             if ($('.check_select:checked').length > 0) {
                 $('.delete_selected_btn').show();
             } else {
                 $('.delete_selected_btn').hide();
             }
         });
         $("#download_selected").on('click', function() {
             var selectedIds = [];
             $('.check_select:checked').each(function() {
                 selectedIds.push($(this).val());
             });

             if (selectedIds.length === 0) {
                 alert('Please select at least one item.');
                 return;
             }

             var form = $('<form>', {
                 action: '{{ route('vendor.inventory.report.sale', ['export', 'excel']) }}',
                 method: 'GET'
             });

             form.append($('<input>', {
                 type: 'hidden',
                 name: '_token',
                 value: $('meta[name="csrf-token"]').attr('content')
             }));

             selectedIds.forEach(function(id) {
                 form.append($('<input>', {
                     type: 'hidden',
                     name: 'invoice_ids[]', // Use array syntax
                     value: id
                 }));
             });

             form.appendTo('body').submit();
         });
     </script>
     @include('vendor-views/js/date_range')
 @endpush
