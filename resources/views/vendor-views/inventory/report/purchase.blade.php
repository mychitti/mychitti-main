 @extends('layouts.vendor.app')
 @section('title', 'Purchase Report')
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

         .report-card:nth-child(3) {
             background: #d7fff4ff;
         }

         .report-card:nth-child(2) {
             background: #ffefddff;
         }

         .report-card:nth-child(4) {
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
                     <h1 class="page-header-title mb-2 d-flex gap-2 flex-wrap">
                         <span class="page-header-icon">
                             <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                         </span>
                         <div class="d-flex align-items-start">
                             <div class="d-flex flex-column">
                                 <span>Purchase Report</span>
                                 <span style="font-size: 15px;font-weight: normal;">({{ translate($preset) }})</span>
                             </div>

                             <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($invoices) }}</span>

                         </div>
                     </h1>

                 </div>
             </div>
         </div>
         <!-- Page Heading -->
         @if (hasPermission('purchase_report', 'list'))
             <div class="card">
                 <div class="report-summary p-2">
                     <div class="report-card">
                         <h4 class="report-title">Total Invoices</h4>
                         <p class="report-value">{{ count($invoices) }}</p>
                     </div>

                     <div class="report-card">
                         <h4 class="report-title">Total Purchase Amount</h4>
                         <p class="report-value">{{ _price($invoices->sum('total_amount'), 'ceil', 2) }}</p>
                     </div>

                     <div class="report-card">
                         <h4 class="report-title">Total Tax Paid</h4>
                         <p class="report-value">{{ _price($invoices->sum('final_tax'), 'ceil', 2) }}</p>
                     </div>

                     <div class="report-card">
                         <h4 class="report-title">Total Items Purchased</h4>
                         <p class="report-value">{{ $invoices->sum('invoice_items_count') }}</p>
                     </div>
                 </div>
             </div>
         @endif

         <div class="card mt-3">
             <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-2">

                 {{-- Vendor & Status Filter --}}
                 <form action="" class="d-flex gap-1 flex-wrap">
                     {{-- <div style="min-width: 200px !important;">
                         <select name="vendor" onchange="this.form.submit()" data-placeholder="Vendor"
                             class="js-select2-custom form-select">
                             <option value="all" {{ request()?->vendor == 'all' ? 'selected' : '' }}>All</option>
                             @foreach ($stores as $key => $value)
                                 <option value="{{ $value->id }}"
                                     {{ request()?->vendor == $value->id ? 'selected' : '' }}>
                                     {{ $value->name . ' | ' . $value->phone }}
                                 </option>
                             @endforeach
                         </select>
                     </div> --}}
                     <div style="min-width: 200px !important;">
                         <select name="status" onchange="this.form.submit()" data-placeholder="Status"
                             class="js-select2-custom form-select">
                             {{-- <option value=""></option> --}}
                             <option value="all" {{ request()?->status == 'all' ? 'selected' : '' }}>All</option>
                             <option value="paid" {{ request()?->status == 'paid' ? 'selected' : '' }}>Paid</option>
                             <option value="unpaid" {{ request()?->status == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                         </select>
                     </div>
                 </form>

                 <div class="d-flex gap-1 flex-wrap">
                     @if (hasPermission('purchase_report', 'export'))
                         <div class="badge badge-soft-success align-items-center"
                             style="    height: 39px;    display: flex;">
                             <div class="form-check mr-1">
                                 <input type="checkbox" class="form-check-input" id="check_all">
                                 <label style=" white-space: nowrap;" class="mt-1 form-check-label" id="check_all_label"
                                     for="check_all">Select All</label>
                             </div>
                         </div>
                         <!-- Delete Button -->
                         <div class="mr-1 delete_selected_btn" style="display:none;">
                             <button id="download_selected" style=" white-space: nowrap;"
                                 class="btn btn-sm btn-outline-primary px-3 py-2 btn_sm " title="Download Selected">
                                 <i class="tio-download"></i> Download Selected
                             </button>
                         </div>
                     @endif
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
                     <form action="" class="date-range-form h-100">
                         <input type="hidden" name="tab" value="entry">
                         <button type="button" class="btn btn-outline-warning" data-toggle="modal"
                             data-target="#dateRangeModal">
                             {{ translate($preset) }}
                         </button>
                         {{-- modal --}}
                         @include('vendor-views/form_modals/date_range')
                     </form>
                     @if (hasPermission('purchase_report', 'export'))
                         <a href="{{ route('vendor.inventory.report.purchase', ['export', 'excel']) }}"
                             class="btn text-dark border border-dark btn-outline-light">
                             <img src="{{ asset('storage/app/public/util/excel-icon.png') }}" height="18px"
                                 alt="">
                             Excel</a>
                     @endif
                 </div>

             </div>


                 @if(hasPermission('purchase_report', 'list'))

             <div class="table-responsive datatable-custom" id="table">
                 <table id="datatable"
                     class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                     <thead class="thead-light">
                         <tr>
                             <th class="border-0"></th>
                             <th class="border-0">{{ translate('sl') }}</th>
                             <th class="border-0">Invoice Id</th>
                             <th class="border-0">Date</th>
                             <th class="border-0">Vendor Name</th>
                             <th class="border-0">Total Amount</th>
                             <th class="border-0">CGST Amt.</th>
                             <th class="border-0">SGST Amt.</th>
                             <th class="border-0">IGST Amt.</th>
                             <th class="border-0">Payment Status</th>
                             <th class="border-0">Total Items</th>
                         </tr>
                     </thead>

                     <tbody id="">
                         @foreach ($invoices as $key => $invoice)
                             <tr>
                                 <td class="text-center"> <input type="checkbox" onclick="event.stopPropagation()"
                                         name="invoice_id[]" value="{{ $invoice->id }}" class="check_select"
                                         id="">
                                 </td>
                                 <td>{{ $key + 1 }}</td>
                                 <td>
                                     <div style="">
                                         <a href="{{ $invoice->pdf ? asset('storage/app/public/invoice/') . '/' . $invoice->pdf : 'javascript:;' }}">
                                             {{ $invoice->invoice_id ?? 'N/A' }}
                                         </a>
                                     </div>
                                 </td>
                                 <td>{{ $invoice->invoice_date ?? $invoice->created_at }}</td>
                                 <td>{{ $invoice->seller?->f_name ?? 'Store Deleted' }}</td>
                                 <td><span class="badge badge-soft-success">{{ _price($invoice->total_amount) }}</span>
                                 </td>

                                 <td>{{ $invoice->bill_gst_type == 'cgst_sgst' ? _price($invoice->final_tax / 2) : '' }}
                                 </td>
                                 <td>{{ $invoice->bill_gst_type == 'cgst_sgst' ? _price($invoice->final_tax / 2) : '' }}
                                 </td>
                                 <td>{{ $invoice->bill_gst_type == 'igst' ? _price($invoice->final_tax) : '' }}</td>
                                 <td>
                                     @if (in_array($invoice->payment_status, ['unpaid', 'Unpaid']))
                                         <span class="badge badge-soft-danger">Unpaid</span>
                                     @else
                                         <span class="badge badge-soft-success">Paid</span>
                                     @endif
                                 </td>
                                 <td>{{ count($invoice->invoiceItems) }}</td>
                              
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
                 action: '{{ route('vendor.inventory.report.purchase', ['export', 'excel']) }}',
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
