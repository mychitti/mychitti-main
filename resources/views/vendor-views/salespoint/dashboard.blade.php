 @extends('layouts.vendor.app')

 @section('title', 'POS Dashboard')

 @push('css_or_js')
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     {{-- <script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script> --}}
     <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


     <style>
         .charts-wrapper {
             display: flex;
             gap: 50px;
         }

         .chart-container {
             width: 3050px;
             height: 310px;
             position: relative;
             background: #fff;
             border-radius: 20px;
         }


         .center-icon {
             position: absolute;
             top: 45%;
             left: 50%;
             transform: translate(-50%, -50%);
             font-size: 45px;
         }

         .dashboard-card {
             border-radius: 15px;
             box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
             transition: transform 0.3s ease, box-shadow 0.3s ease;
             border: none;
         }

         .dashboard-card:hover {
             {{-- transform: translateY(-5px); --}} box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
         }

         .total-sales-card {
             background: {{ $colors['main_card'] }};
             color: #2c3e50;
         }

         .branch-card-1 {
             background: {{ $colors['branch_1_color'] }};
             color: #2c3e50;
         }

         .branch-card-2 {
             background: {{ $colors['branch_2_color'] }};
             color: #2c3e50;
         }

         .branch-card-3 {
             background: {{ $colors['branch_3_color'] }};
             color: #2c3e50;
         }

         .stats-number {
             font-size: 2.5rem;
             font-weight: bold;
             margin: 0;
         }

         .stats-label {
             font-size: 0.9rem;
             opacity: 0.9;
             margin: 0;
         }

         .branch-amount {
             font-size: 1.8rem;
             font-weight: bold;
         }

         .branch-name {
             font-size: 1.5rem;
             font-weight: bold;
         }

         .top-product-item {
             background: #f8f9fa;
             border-radius: 10px;
             padding: 15px;
             margin-bottom: 10px;
             border-left: 4px solid;
             transition: all 0.3s ease;
             display: flex;

         }

         .top-product-item:hover {
             background: #e9ecef;
             transform: translateX(5px);
         }

         .top-product-item:nth-child(1) {
             border-left-color: #a8e6cf;
         }

         .top-product-item:nth-child(2) {
             border-left-color: #ffd3a5;
         }

         .top-product-item:nth-child(3) {
             border-left-color: #fd9cb8;
         }

         .top-product-item:nth-child(4) {
             border-left-color: #b8d4f0;
         }

         .top-product-item:nth-child(5) {
             border-left-color: #d4b5f7;
         }

         .product-name {
             font-weight: bold;
             color: #495057;
             margin-bottom: 5px;
         }

         .product-stats {
             font-size: 0.9rem;
             color: #6c757d;
         }

         .period-selector {
             border-radius: 25px;
             border: 2px solid #a8d8ea;
             background: white;
             color: #5a6c7d;
             transition: all 0.3s ease;
         }

         .period-selector.active {
             background: #a8d8ea;
             color: #2c3e50;
         }



         .icon-wrapper {
             width: 60px;
             height: 60px;
             border-radius: 15px;
             display: flex;
             align-items: center;
             justify-content: center;
             font-size: 1.5rem;
             background: rgba(255, 255, 255, 0.5);
             margin-bottom: 15px;
         }
     </style>
     <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
 @endpush

 @section('content')

     <div class="content container-fluid ">
         <!-- Period Selector -->
         <div class="page-header d-flex justify-content-between flex-wrap">
             <div>
                 <h1 class="page-header-title">
                     <span class="page-header-icon">
                         <img src="{{ asset('public/assets/admin/img/category.png') }}" class="w--20" alt="">
                     </span>
                     <span>
                         {{ translate('POS_Dashboard') }}
                     </span>
                 </h1>
                 <p class="mb-0">Sales analytics and performance metrics</p>
             </div>
             <div class="d-flex gap-1 align-items-center">
                 <button style="width:fit-content; white-space:nowrap;" class="btn btn-outline-warning " type="button"
                     data-toggle="modal" data-target="#dateRangeModal"><i class="fa-solid fa-filter "></i>
                     {{ translate($preset) }}</button>

                 <form action="" class="date-range-form">
                     @include('vendor-views/form_modals/date_range')
                 </form>
                 <button type="button" class="btn btn--primary mb-0" data-toggle="modal" data-target="#calendarModal">
                     Calendar
                 </button>
             </div>
         </div>


         <!-- Total Sales -->
         <div class="row mb-4 gap-2">


             <div class="col-md-4">
                 <div class="card dashboard-card total-sales-card ">
                     <div class="card-body text-center">
                         <div class="icon-wrapper mx-auto">
                             {{ \App\CentralLogics\Helpers::currency_symbol() }}  
                         </div>  
                         <h2 class="stats-number" id="totalSales">{{ shortAmount($data['totalSales']) }}</h2>
                                     <span class="">({{ _price($data['totalSales']) }}) in sales ({{ translate($preset) }})</span>

                         <p class="stats-label">Total Sales</p>
                         <p class=" mb-1">{{ $data['tokensSold'] }} tokens sold</p>
                         <p class=" mb-0">{{ _price($data['avgValue']) }} avg. token value</p>
                         <p class=" mb-1 text-danger"> + {{ $data['tokensCancelled'] }} tokens cancelled</p>
                         <p class=" mb-1 text-danger"> + {{ _price($data['cancelledSales']) }} Cancelled</p>
                     </div>
                 </div>
             </div>
             <div class="col-md-6">
                 <div class="card dashboard-card ">
                     <div class="card-body p-1">
                         <div class="charts-wrapper">
                             <div class="chart-container">
                                 <div class="card-header py-2 border-0">
                                     <h5 class="card-title">Branch Sales Comparision<span
                                             class="badge badge-soft-dark ml-2"></span>
                                     </h5>
                                 </div>
                                 <div style="width: 100%; max-width: 100%; height: 88%;">
                                     <canvas id="branchSalesChart" height="100"></canvas>
                                 </div>
                                 {{-- <div id="chart1" style="width:100%;height:100%;"></div> --}}

                             </div>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
         <!-- Branch-wise Sales -->
         <div class="row mb-4">
             @foreach ($data['branches'] as $key => $branch)
                 <div class="col-md-4 my-1" data-branch="downtown">
                     <div class="card dashboard-card branch-card-{{ $key + 1 }} h-100">
                         <div class="card-body d-flex">
                             <!-- Icon -->
                             <div class="icon-wrapper w-25 ">
                                 <i class="fas fa-store"></i>
                             </div>

                             <!-- Content -->
                             <div class="px-2 flex-grow-1">
                                 <!-- Branch Name -->
                                 <p class="branch-name fw-bold mb-1">{{ ucfirst($branch->name) }}</p>

                                 <!-- Sales -->
                                 <div class=" align-items-center gap-1 mb-2">
                                     <h3 class="branch-amount mb-0"> {{ shortAmount($branch->totalSales) }}</h3>
                                     <span class="">({{ _price($branch->totalSales) }}) in sales ({{ translate($preset) }})</span>
                                 </div>
                                 <div class="d-flex align-items-center gap-1 mb-2">
                                 </div>

                                 <!-- Sub-metrics -->   
                                 <p class=" mb-1">{{ _price($branch->totalGSTAmount) }} GST (incl.) </p>
                                 <p class=" mb-1">{{ $branch->totalSalesPercent }}% of overall sales</p>
                                 <p class=" mb-1">{{ $branch->tokensSold }} tokens sold</p>
                                 <p class=" mb-0">{{ _price($branch->avgValue) }} avg. token value</p>
                                 <p class=" mb-1 text-danger">+ {{ $branch->tokensCancelled }} tokens cancelled</p>
                                 <p class=" mb-1 text-danger"> + {{ _price($branch->cancelledSales) }} Cancelled</p>
                             </div>
                         </div>
                     </div>
                 </div>
             @endforeach
         </div>

         <!-- Top Selling Products by Branch -->
         <div class="row gap-2">
             @foreach ($data['branches'] as $key => $branch)
                 <div class="col-md-4">
                     <div class="card dashboard-card h-100">
                         <div class="card-header bg-transparent border-0">
                             <h6 class="card-title mb-0"><i
                                     class="fas fa-crown text-warning me-2"></i>{{ ucfirst($branch->name) }} - Top Products</h6>
                             @php
                                 $dFilter = '';
                                 if ($preset) {
                                     $dFilter .= '&date_range=' . $preset;
                             } @endphp
                             @php
                                 if ($custom) {
                                     $dFilter .= '&custom_date_range=' . $custom;
                                 }
                             @endphp
                             @if (hasPermission('pos', 'report'))
                                 <a href="{{ route('vendor.pos.report') }}?branch={{ $branch->id }}{{ $dFilter }}"
                                     class="text-underline">View
                                     All</a>
                             @endif
                         </div>
                         <div class="card-body">
                             @foreach ($branch->topSellingItems as $key => $item)
                                 <div class="top-product-item">
                                     <div class="item_img">
                                         <img class="avatar avatar-lg mr-3 onerror-image"
                                             src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                 $item->image ?? '',
                                                 asset('storage/app/public/inventory-item') . '/' . $item->image ?? '',
                                                 asset('public/assets/admin/img/160x160/img2.jpg'),
                                                 'inventory-item/',
                                             ) }}"
                                             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                             alt="{{ $item->item_name }} image">
                                     </div>
                                     <div>
                                         <div class="product-name">{{ $item->item_name }}</div>
                                         <div class="product-stats">
                                             <span class="me-3"><i class="fas fa-shopping-cart me-1"></i>
                                                 {{ $item->total_qty }}
                                                 sold</span>
                                             <span>{{ _price($item->total_sales) }}</span>
                                         </div>
                                     </div>
                                 </div>
                             @endforeach
                         </div>
                     </div>
                 </div>
             @endforeach
         </div>
     </div>
     @include('vendor-views.form_modals.pos_calendar')
 @endsection

 @push('script_2')
     @include('vendor-views.salespoint.calendar-js')
     {{-- @include('vendor-views/js/pos_doughnut_chart') --}}
     @include('vendor-views/js/date_range')
     <script>
         let chartData = {!! json_encode(
             $data['branches']->map(function ($b) {
                 return [
                     'name' => $b->name,
                     'value' => $b->totalSales,
                 ];
             }),
         ) !!};

         const colors = [
             "{{ $colors['branch_1_color'] }}", // branch-card-1
             "{{ $colors['branch_2_color'] }}", // branch-card-2
             "{{ $colors['branch_3_color'] }}" // branch-card-3
         ];

         const ctx = document.getElementById('branchSalesChart').getContext('2d');
         let values = chartData.map(item => item.value);

         let maxValue = Math.max(...values);

         let minBar = maxValue * 0.01;

         let displayValues = values.map(v => v > 0 ? v : minBar);
         new Chart(ctx, {
             type: 'bar',
             data: {
                 labels: chartData.map(item => item.name),
                 datasets: [{
                     label: 'Total Sales',
                     data: displayValues,
                     backgroundColor: colors,
                     borderRadius: 8,
                    barThickness: window.innerWidth <= 768 ? 40 : 140
                 }]
             },
             options: {
                maintainAspectRatio: false,
                 responsive: true,
                 plugins: {
                     legend: {
                         display: false
                     },
                     tooltip: {
                         callbacks: {
                             label: function(context) {
                                 let value = chartData[context.dataIndex].value; // real value
                                 return `₹ ${value.toLocaleString()}`;
                             }
                         }
                     }
                 },
                 scales: {
                     y: {
                         beginAtZero: true,
                         ticks: {
                             callback: function(value) {
                                 return '₹ ' + value.toLocaleString();
                             }
                         }
                     }
                 }
             }
         });
     </script>
 @endpush
