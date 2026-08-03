 @extends('layouts.vendor.app')
 @section('title', 'Profit and Loss Summary')
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
             min-width: 175px;
             background: #fff;
             border: 1px solid #eee;
             border-radius: 8px;
             padding: 1rem;
             box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
         }

         .report-card:nth-child(1) {
             background: #ffe3e0ff;
         }

         .report-card:nth-child(4) {
             background: #ecffd7ff;
         }

         .report-card:nth-child(2) {
             background: #d7fff4ff;
         }

         .report-card:nth-child(3) {
             background: #fff6e0ff;
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
                     <h1 class="page-header-title mb-2 d-flex gap-2 align-items-start ">
                         <span class="page-header-icon">
                             <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                         </span>
                         <div class="d-flex align-items-start">
                             <div class="d-flex flex-column">
                                 <span>Profit and Loss Summary</span>
                                 <span style="font-size: 15px; font-weight: normal;">({{ translate($preset) }})</span>
                             </div>

                             <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($orderItems) }}</span>

                         </div>
                     </h1>

                 </div>
             </div>
         </div>
         <!-- Page Heading -->

                 @if(hasPermission('profit_loss_summary', 'list'))

         <div class="card">
             <div class="report-summary p-2">
                 <div class="report-card d-flex align-items-center gap-3">
                     <img src="{{ asset('storage/app/public/util/revenue.png') }}" style="width: 44px;" alt="">
                     <div>
                         <h4 class="report-title">Total Revenue</h4>
                         <p class="report-value mb-0">{{ _price($orderItems->sum('total_revenue'), 'ceil', 2) }}</p>
                         @if (($round_off ?? 0) != 0)
                             <small class="text-muted">excludes {{ _price(abs($round_off), 'ceil', 2) }}
                                 round-off {{ $round_off > 0 ? 'collected' : 'given back' }}</small>
                         @endif
                     </div>
                 </div>
                 <div class="report-card d-flex align-items-center gap-3">
                     <img src="{{ asset('storage/app/public/util/cogs.png') }}" style="width: 44px;" alt="">
                     <div>
                         <h4 class="report-title">Total COGS</h4>
                         <p class="report-value">{{ _price($orderItems->sum('total_cost'), 'ceil', 2) }}</p>
                     </div>
                 </div>
                 <div class="report-card d-flex align-items-center gap-3">
                     @php $pl_status = $orderItems->sum('total_revenue') - $orderItems->sum('total_cost') >= 0 ? 'profit' : 'loss' ;@endphp
                     <img src="{{ asset('storage/app/public/util/' . $pl_status . '.png') }}" style="width: 44px;"
                         alt="">
                     <div>
                         <h4 class="report-title">Total Profit / Loss</h4>
                         <p class="report-value {{ $pl_status == 'profit' ? 'text-success' : 'text-danger' }}">
                             {{ _price(abs($orderItems->sum('total_profit_loss')), 'ceil', 2) }} {{ ucfirst($pl_status) }}
                         </p>
                     </div>
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
                         <select name="status" onchange="this.form.submit()" data-placeholder="Status"
                             class="js-select2-custom form-select">
                             <option value="all" {{ request()?->status == 'all' ? 'selected' : '' }}>All</option>
                             <option value="profit" {{ request()?->status == 'profit' ? 'selected' : '' }}>Profit</option>
                             <option value="loss" {{ request()?->status == 'loss' ? 'selected' : '' }}>Loss</option>
                         </select>
                     </div>
                     @include('vendor-views.inventory.report._branch_filter')
                 </form>

                 <div class="d-flex gap-1 flex-wrap">
                     {{-- Search --}}
                     <form action="">
                         <div class="input-group flex-nowrap">
                             <input type="search" name="search" value="{{ request()?->search ?? '' }}"
                                 class="form-control" style="min-width:198px"
                                 placeholder="{{ translate('messages.search by item name') }}">
                             <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                         </div>
                     </form>

                     {{-- Date Range --}}
                     <form action="" class="date-range-form">
                         <button type="button" style="height: 100%;" class="btn btn-outline-warning" data-toggle="modal"
                             data-target="#dateRangeModal">
                             {{ translate($preset) }}
                         </button>
                         @include('vendor-views/form_modals/date_range')
                     </form>
                 @if(hasPermission('profit_loss_summary', 'export'))

                     <div class="d-flex gap-2">
                         <a href="{{ route('vendor.inventory.report.profit-and-loss', ['export', 'pdf']) }}"
                             class="btn text-dark border border-dark btn-outline-light">
                             <img src="{{ asset('storage/app/public/util/pdf-icon.jpg') }}" height="18px" alt="">
                             Pdf</a>
                         <a href="{{ route('vendor.inventory.report.profit-and-loss', ['export', 'excel']) }}"
                             class="btn text-dark border border-dark btn-outline-light">
                             <img src="{{ asset('storage/app/public/util/excel-icon.png') }}" height="18px"
                                 alt="">
                             Excel</a>
                     </div>
                     @endif
                 </div>

             </div>

                 @if(hasPermission('profit_loss_summary', 'list'))


             <div class="table-responsive datatable-custom" id="table">
                 <table id="datatable"
                     class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                     <thead class="thead-light">
                         <tr>
                             <th class="border-0">{{ translate('sl') }}</th>
                             <th class="border-0">Item Name</th>
                             <th class="border-0">Category</th>
                             <th class="border-0">Revenue</th>
                             <th class="border-0">COGS</th>
                             <th class="border-0">Profit / Loss</th>
                             <th class="border-0">Action</th>
                         </tr>
                     </thead>

                     <tbody id="">
                         @foreach ($orderItems as $key => $orderItem)
                             @php   $pnl_status = $orderItem->total_revenue - $orderItem->total_cost > 0 ? 'Profit' : 'Loss'; @endphp

                             <tr>
                                 <td>{{ $key + 1 }}</td> 
                                 <td>
                                     <div style="white-space: normal; min-width: 150px; max-width: 250px; word-break: break-word;">
                                         <a href="{{ route('vendor.inventory.item.detail', [$orderItem->item_id]) }}">
                                             {{ ucwords($orderItem->item_name) ?? 'N/A' }}
                                         </a>
                                     </div>
                                 </td>
                                 <td>{{ $orderItem->cat_name ?? 'Deleted' }}</td>
                                 <td>{{ _price($orderItem->total_revenue, 'ceil', 2) }}</td>
                                 <td>{{ _price($orderItem->total_cost, 'ceil', 2) }}</td>
                                 <td>
                                     @if ($pnl_status == 'Profit')
                                         <span class="text-success fw-bold">
                                             {{ _price(abs($orderItem->total_profit_loss), 'ceil', 2) }}
                                             {{ $pnl_status }}
                                         </span>
                                     @else
                                         <span class="text-danger fw-bold">
                                             {{ _price(abs($orderItem->total_profit_loss), 'ceil', 2) }}
                                             {{ $pnl_status }}
                                         </span>
                                     @endif
                                 </td>
                                 <td>
                                     <div class="dropdown">
                                         <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                             aria-expanded="false">
                                             <i class="fa-solid fa-bars"></i>
                                         </button>
                                         <div class="dropdown-menu">
                                             <a class="dropdown-item text-success"
                                                 href="{{ route('vendor.inventory.item.detail', [$orderItem->item_id]) }}"
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
                 @if (!count($orderItems))
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
     @include('vendor-views/js/date_range')
 @endpush
