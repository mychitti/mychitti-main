 @extends('layouts.vendor.app')
 @section('title', 'Stock Report')
 @push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/inventory_report.css') }}" rel="stylesheet">
 @endpush

 @section('content')
 
     <div class="content container-fluid p-3">
         @include('hmis::vendor-views.partials._pharmacy_header')
         <div class="pharmacy-page-content">
             <div class="page-header">
             <div class="d-flex flex-wrap px-3 w-100">
                 <div class="d-flex w-100 flex-wrap justify-content-between  align-orders-center">
                     <h1 class="page-header-title mb-2 d-flex gap-2">
                         <span class="page-header-icon">
                             <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                         </span>
                         <div class="d-flex align-items-start">
                             <a href="javascript:history.back()" class="mr-2" style="color: inherit;" title="Back">
                                 <i class="tio-chevron-left"></i>
                             </a>
                             <div class="d-flex flex-column">
                                 <span>Stock Report</span>
                                 <span style="font-size: 15px; font-weight: normal;">({{ translate($preset) }})</span>
                             </div>

                             <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($items) }}</span>

                         </div>
                     </h1>

                 </div>
             </div>
         </div>
         <!-- Page Heading -->

         @if (hasPermission('stock_report', 'list'))
             <div class="card">
                 <div class="report-summary p-2">
                     <div class="report-card">
                         <h4 class="report-title">Total Items</h4>
                         <p class="report-value"><i class="fs-1 tio-fruit-apple-outlined"></i> {{ count($items) }}</p>
                     </div>

                     <div class="report-card">
                         <h4 class="report-title">Total Stock Quantity</h4>
                         <p class="report-value"> <i class="fs-1 tio-stairs-up"></i> {{ $items->sum('stock') }}</p>
                     </div>

                     <div class="report-card">
                         <h4 class="report-title">Low Stock Items</h4>
                         <p class="report-value"><i class="fs-1 tio-report-outlined"></i>
                             {{ $items->whereBetween('stock', [1, 5])->count() }}</p>
                     </div>

                     <div class="report-card">
                         <h4 class="report-title">Out of Stock Items</h4>
                         <p class="report-value"><i class="fs-1 tio-stairs-down"></i>
                             {{ $items->where('stock', '<', 1)->count() }}</p>
                     </div>
                     <div class="report-card" style="border-color:#f0ad4e;">
                         <h4 class="report-title">Expiring Soon</h4>
                         <p class="report-value text-warning">
                             <i class="fs-1 tio-date-range"></i>
                             {{ \App\Models\ItemEntry::where('store_id', \App\CentralLogics\Helpers::get_store_id())->whereNotNull('expiry_date')->whereDate('expiry_date', '>=', now())->whereDate('expiry_date', '<=', now()->addDays(90))->count() }}
                         </p>
                         <a href="{{ route('vendor.inventory.report.batch-expiry', ['filter' => 'expiring_soon']) }}" class="small">View Batches →</a>
                     </div>
                 </div>
             </div>
         @endif

         <div class="card mt-3">
             <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-2">

                 {{-- Vendor & Status Filter --}}
                 <form action="" class="d-flex gap-2 flex-wrap">
                     <div style="min-width: 200px !important;">
                         <select name="category" onchange="this.form.submit()" data-placeholder="Category"
                             class="js-select2-custom form-select">
                             {{-- <option value=""></option> --}}
                             <option value="all" {{ request()?->category == 'all' ? 'selected' : '' }}>All</option>
                             @foreach ($categories as $key => $value)
                                 <option value="{{ $value->id }}"
                                     {{ request()?->category == $value->id ? 'selected' : '' }}>
                                     {{ $value->name }}
                                 </option>
                             @endforeach
                         </select>
                     </div>
                     <div style="min-width: 200px !important;">
                         <select name="stock_staus" onchange="this.form.submit()" data-placeholder="Status"
                             class="js-select2-custom form-select">
                             {{-- <option value=""></option> --}}
                             <option value="all" {{ request()?->stock_staus == 'all' ? 'selected' : '' }}>All</option>
                             <option value="low_stock" {{ request()?->stock_staus == 'low_stock' ? 'selected' : '' }}>Low
                                 Stock</option>
                             <option value="out_of_stock"
                                 {{ request()?->stock_staus == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                             <option value="in_stock" {{ request()?->stock_staus == 'in_stock' ? 'selected' : '' }}>In
                                 Stock</option>
                         </select>
                     </div>

                 </form>

                 <div class="d-flex gap-1 flex-wrap">
                     @if (hasPermission('stock_report', 'export'))
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
                      @if (hasPermission('stock_report', 'export'))
                             <a href="{{ route('vendor.inventory.report.stock', ['export', 'pdf']) }}"
                                 class="btn text-dark border border-dark btn-outline-light">
                                 <img src="{{ asset('storage/app/public/util/pdf-icon.jpg') }}" height="18px"
                                     alt="">
                                 Pdf</a>
                             <a href="{{ route('vendor.inventory.report.stock', ['export', 'excel']) }}"
                                 class="btn text-dark border border-dark btn-outline-light">
                                 <img src="{{ asset('storage/app/public/util/excel-icon.png') }}" height="18px"
                                     alt="">
                                 Excel</a>
                     @endif
                     {{-- Search --}}
                     <form action="">
                         <div class="input-group flex-nowrap">
                             <input type="search" name="search" value="{{ request()?->search ?? '' }}"
                                 class="form-control" style="min-width:198px"
                                 placeholder="{{ translate('messages.search by item name') }}">
                             <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                         </div>
                     </form>
                    
                 </div>

             </div>


             @if (hasPermission('stock_report', 'list'))

                 <div class="table-responsive datatable-custom" id="table">
                     <table id="datatable"
                         class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                         <thead class="thead-light">
                             <tr>
                                 @if (hasPermission('stock_report', 'export'))
                                     <th class="border-0"></th>
                                 @endif
                                 <th class="border-0">{{ translate('sl') }}</th>
                                 <th class="border-0">Item</th>
                                 <th class="border-0">Category</th>
                                 <th class="border-0">Unit Price</th>
                                 <th class="border-0">Stock Value</th>
                                 <th class="border-0">Stock</th>
                                 <th class="border-0">Action</th>
                             </tr>
                         </thead>

                         <tbody id="">
                             @foreach ($items as $key => $item)
                                 <tr>
                                     @if (hasPermission('stock_report', 'export'))
                                         <td class="text-center"> <input type="checkbox"
                                                 onclick="event.stopPropagation()" name="item_id[]"
                                                 value="{{ $item->id }}" class="check_select " id="">
                                     @endif
                                     </td>
                                     </td>
                                     <td>{{ $key + 1 }}</td>
                                     <td>
                                         <div style="">
                                             <a href="{{ route('vendor.inventory.item.detail', [$item->id]) }}">
                                                 {{ ucwords($item->item_name) ?? 'N/A' }}
                                             </a>
                                         </div>
                                     </td>
                                     <td>{{ $item->category?->name ?? 'Category Deleted' }}</td>
                                     <td>{{ _price($item->selling_price) }}</td>
                                     <td>{{ _price($item->selling_price * $item->stock) }}</td>

                                     <td>
                                         @if ($item->stock < 1)
                                             <span class="badge badge-soft-danger">{{ $item->stock }}</span>
                                         @elseif($item->stock < 5)
                                             <span class="badge badge-soft-warning">{{ $item->stock }}</span>
                                         @else
                                             <span class="badge badge-soft-success">{{ $item->stock }}</span>
                                         @endif
                                     </td>
                                     <td>
                                         <div class="dropdown">
                                             <button class="btn p-1 dropdown-toggle" type="button"
                                                 data-toggle="dropdown" aria-expanded="false">
                                                 <i class="fa-solid fa-bars"></i>
                                             </button>
                                             <div class="dropdown-menu">
                                                 <a class="dropdown-item text-success"
                                                     href="{{ route('vendor.inventory.item.detail', [$item->id]) }}"
                                                     title="{{ translate('messages.view') }}"><i class="tio-visible"></i>
                                                     View
                                                 </a>
                                             </div>
                                         </div>
                                     </td>
                                 </tr>
                             @endforeach

                         </tbody>
                     </table>
                     @if (!count($items))
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
                 action: '{{ route('vendor.inventory.report.stock', ['export', 'excel']) }}',
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
                     name: 'item_ids[]', // Use array syntax
                     value: id
                 }));
             });

             form.appendTo('body').submit();
         });
     </script>
 @endpush
