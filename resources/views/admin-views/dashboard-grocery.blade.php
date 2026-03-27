@extends('layouts.admin.app')

@section('title',\App\Models\BusinessSetting::where(['key'=>'business_name'])->first()->value??translate('messages.dashboard'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
    
        /* Upcoming Events */
        .events-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px; 
    padding-bottom: 0px; 
        }

        .view-all-btn {
            background: linear-gradient(45deg, #ffa500, #ff6b6b);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .view-all-btn:hover {
            transform: translateY(-1px);
        }

        .events-date {
            color: #7f8c8d;
            font-size: 12px;
            margin-bottom: 16px;
        }

        .event-card {
              padding: 8px;
    font-size: 11px;
    margin: 4px 7px;
    border-radius: 12px;
    /* margin-bottom: 6px; */
    color: white;
    position: relative;
    overflow: hidden;
        }

        .event-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), transparent);
            pointer-events: none;
        }

        .event-card.design-review {
            background: linear-gradient(45deg, #2c3e50, #34495e);
        }

       
        .event-card.design-review-5 {
            color: black;
            background: linear-gradient(135deg, #def5ff 0%, #f0f9ffdf 100%);
        }</style>
@endpush

@section('content')
    <div class="content container-fluid">
        {{-- @if(auth('admin')->user()->role_id == 1) --}}
        @if(1)
        @php($mod = \App\Models\Module::find(Config::get('module.current_module_id')))
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center py-2">
                <div class="col-sm mb-2 mb-sm-0">
                    <div class="d-flex align-items-center">
                        <img class="onerror-image" data-onerror-image="{{asset('/public/assets/admin/img/grocery.svg')}}" src="{{\App\CentralLogics\Helpers::onerror_image_helper($mod->icon, asset('storage/app/public/module/').'/'.$mod->icon, asset('public/assets/admin/img/grocery.svg'), 'module/') }}"
                        width="38" alt="img">
                        <div class="w-0 flex-grow pl-2">
                            <h1 class="page-header-title mb-0">{{translate($mod->module_name)}} {{translate('messages.Dashboard')}}.</h1>
                            <p class="page-header-text m-0">{{translate('Hello, Here You Can Manage Your')}} {{translate($mod->module_name)}} {{translate('orders by Zone.')}}</p>
                        </div>
                    </div>
                </div>
                <div class="mb-2 mb-sm-0">
                 <a target="_blank" href="https://mychitti.net/generate-sitemap" class="btn btn-primary">Update Sitemap </a>
                </div> 
                <div class="col-sm-auto min--280"> 
                    <select name="zone_id" class="form-control js-select2-custom fetch_data_zone_wise" >
                        <option value="all">{{ translate('messages.All_Zones') }}</option>
                        @foreach(\App\Models\Zone::orderBy('name')->get() as $zone)
                            <option
                                value="{{$zone['id']}}" {{$params['zone_id'] == $zone['id']?'selected':''}}>
                                {{$zone['name']}}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div> 
        <!-- End Page Header -->

          @if(auth('admin')->user()->role_id != 1)
        <div class="row">
            <div class="card mb-3 col-md-4" >
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center h-100">
                        <div>
                            @if (_clockedInEmployee())
                                @if (_clockedInEmployeeDutyHours())
                                    <div class="d-flex align-items-center" style="gap: 8px;">
                                        <span style="font-size: 26px;">&#9200;</span>
                                        <span id="staffDashCurrentDateTime">
                                            <div style="text-align: center;"> 
                                                <span style="font-weight: 600; font-size: 16px;">Remaining Time</span><br>
                                                <span id="staffRemainingTime" style="font-size: 22px; font-weight: 600;">Loading...</span>
                                            </div>
                                        </span>
                                    </div>
                                @endif
                                <div style="margin-top: 8px; font-size: 13px;" id="staffDashPunchTimeDisplay">
                                    Punched in at: {{ date('H:i:s', strtotime(_inTime('timestamp'))) }}
                                </div>
                            @else
                                <div class="d-flex align-items-center" style="gap: 8px;">
                                    <span style="font-size: 26px;">&#9200;</span>
                                    <span id="staffDashCurrentDateTime">
                                        <span id="staffRemainingTime" style="font-size: 18px; font-weight: 600; color: #999;">Not Clocked In</span>
                                    </span>
                                </div>
                                <div style="margin-top: 8px; font-size: 13px; display:none;" id="staffDashPunchTimeDisplay"></div>
                            @endif
                        </div>
                        @if (_clockedInEmployee())
                            <button class="btn btn-danger" id="staffDashPunchBtn" style="padding: 12px 24px; font-weight: 600;">
                                <span id="staffDashPunchIcon">&#9632;</span>
                                <span id="staffDashPunchText">Punch Out</span>
                            </button>
                        @else
                            <button class="btn btn-success" id="staffDashPunchBtn" style="padding: 12px 24px; font-weight: 600;">
                                <span id="staffDashPunchIcon">&#9654;</span>
                                <span id="staffDashPunchText">Punch In</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                 <div class="card content-card">
                    <div class="events-header">  
                        <h5 class="section-title">Timecards</h5>
                        <a href="{{ route('admin.employee.timecards', [auth('admin')->id()]) }}" class="text-underline">View All
                            Timecards</a>
                    </div> 
                    @if(isset($recentTimecards) && count($recentTimecards) > 0)
                        @foreach($recentTimecards as $tc)
                            <div class="event-card design-review-5">
                                <div class="event-header">
                                    <div class="event-title">{{ $tc->date }}</div> 
                                </div>
                                <div class="event-time">
                                    In: {{ explode(' ', $tc->in_time)[1] }} | Out: {{ explode(' ', $tc->out_time)[1] }}
                                    | Duration: {{ (new \DateTime($tc->in_time))->diff(new \DateTime($tc->out_time))->format('%hh %im') }}
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">No timecards found</p>
                    @endif
                </div>
            </div>
        </div>

        @endif

        <!-- Stats -->
        <div class="card mb-3">
            <div class="card-body pt-0">
                <div class="d-flex flex-wrap align-items-center justify-content-end">
                    <div class="status-filter-wrap">
                        <div class="statistics-btn-grp">
                            <label>
                                <input type="radio" name="statistics" value="this_year" {{$params['statistics_type'] == 'this_year'?'checked':''}} class="order_stats_update" hidden>
                                <span>{{ translate('This_Year') }}</span>
                            </label>
                            <label>
                                <input type="radio" name="statistics" value="this_month" {{$params['statistics_type'] == 'this_month'?'checked':''}} class="order_stats_update" hidden>
                                <span>{{ translate('This_Month') }}</span>
                            </label>
                            <label>
                                <input type="radio" name="statistics" value="this_week" {{$params['statistics_type'] == 'this_week'?'checked':''}} class="order_stats_update" hidden>
                                <span>{{ translate('This_Week') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row g-2" id="order_stats">
                    @include('admin-views.partials._dashboard-grocery-stats', ['data' => $data])
                </div>
            </div>
        </div>
        <!-- End Stats -->

        <div class="row g-2">
            <div class="col-lg-8 col--xl-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Analytics Overview</h5>
                        <div class="d-flex align-items-center">
                            <select id="analytics-days" class="form-control form-control-sm" style="width:auto;">
                                <option value="7">Last 7 Days</option>
                                <option value="15">Last 15 Days</option>
                                <option value="30" selected>Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                            </select>
                            <a href="{{ route('admin.analytics.index') }}" class="btn btn-sm btn-outline-primary ml-2">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center __gap-12px">
                        @if(hasPermission('analytics', 'view'))
                            <canvas id="analyticsChart" height="80"></canvas>
                        @endif

                            {{-- <div class="__gross-amount" id="gross_sale">
                                <h6>{{\App\CentralLogics\Helpers::format_currency(array_sum($total_sell))}}</h6>
                                <span>{{ translate('messages.Gross Sale') }}</span>
                            </div>
                            <div class="chart--label __chart-label p-0 move-left-100 ml-auto">
                                <span class="indicator chart-bg-2"></span>
                                <span class="info">
                                    {{ translate('sale') }} ({{ date("Y") }})
                                </span>
                            </div>
                            <select class="custom-select border-0 text-center w-auto ml-auto commission_overview_stats_update" name="commission_overview">
                                    <option
                                    value="this_year" {{$params['commission_overview'] == 'this_year'?'selected':''}}>
                                    {{translate('This year')}}
                                </option>
                                <option
                                    value="this_month" {{$params['commission_overview'] == 'this_month'?'selected':''}}>
                                    {{translate('This month')}}
                                </option>
                                <option
                                    value="this_week" {{$params['commission_overview'] == 'this_week'?'selected':''}}>
                                    {{translate('This week')}}
                                </option>
                            </select> --}}
                        </div>
                        {{-- <div id="commission-overview-board">

                            <div id="grow-sale-chart"></div>
                        </div> --}}
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col--xl-4">
                <!-- Card -->
                <div class="card h-100">
                    <!-- Header -->
                    <div class="card-header border-0">
                        <h5 class="card-header-title">
                            {{translate('User Statistics')}}
                        </h5>
                        <div id="stat_zone">

                            @include('admin-views.partials._zone-change',['data'=>$data])


                        </div>
                        <select class="custom-select border-0 text-center w-auto user_overview_stats_update" name="user_overview">
                                <option
                                value="this_year" {{$params['user_overview'] == 'this_year'?'selected':''}}>
                                {{translate('This year')}}
                            </option>
                            <option
                                value="this_month" {{$params['user_overview'] == 'this_month'?'selected':''}}>
                                {{translate('This month')}}
                            </option>
                            <option
                                value="this_week" {{$params['user_overview'] == 'this_week'?'selected':''}}>
                                {{translate('This week')}}
                            </option>
                            <option
                                value="overall" {{$params['user_overview'] == 'overall'?'selected':''}}>
                                {{translate('messages.Overall')}}
                            </option>
                        </select>
                    </div>
                    <!-- End Header -->

                    <!-- Body -->
                    <div class="card-body" id="user-overview-board">
                        <div class="position-relative pie-chart">
                            <div id="dognut-pie"></div>
                            <!-- Total Orders -->
                            <div class="total--orders">
                                <h3 class="text-uppercase mb-xxl-2">{{ $data['customer'] + $data['stores'] + $data['delivery_man'] }}</h3>
                                <span class="text-capitalize">{{translate('messages.total_users')}}</span>
                            </div>
                            <!-- Total Orders -->
                        </div>
                        <div class="d-flex flex-wrap justify-content-center mt-4">
                            <div class="chart--label">
                                <span class="indicator chart-bg-1"></span>
                                <span class="info">
                                    {{translate('messages.customer')}} {{$data['customer']}}
                                </span>
                            </div>
                            <div class="chart--label">
                                <span class="indicator chart-bg-2"></span>
                                <span class="info">
                                    {{translate('messages.store')}} {{$data['stores']}}
                                </span>
                            </div>
                            <div class="chart--label">
                                <span class="indicator chart-bg-3"></span>
                                <span class="info">
                                    {{translate('messages.delivery_man')}} {{$data['delivery_man']}}
                                </span>
                            </div>
                        </div>

                    </div>
                    <!-- End Body -->
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card h-100" id="top-restaurants-view">
                    @include('admin-views.partials._top-restaurants',['top_restaurants'=>$data['top_restaurants']])
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card h-100" id="popular-restaurants-view">
                    @include('admin-views.partials._popular-restaurants',['popular'=>$data['popular']])
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card h-100" id="top-selling-foods-view">
                    @include('admin-views.partials._top-selling-foods',['top_sell'=>$data['top_sell']])
                </div>
                <!-- End Card -->
            </div>

            
             @include('admin-views.partials._top-rated-foods',['top_rated_foods'=>$data['top_rated_foods']])
            

            <div class="col-lg-4 col-md-6 d-none">
                 Card 
                <div class="card h-100" id="top-deliveryman-view">
                    @include('admin-views.partials._top-deliveryman',['top_deliveryman'=>$data['top_deliveryman']])
                </div>
                 End Card 
            </div>

            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card h-100" id="top-customer-view">
                    @include('admin-views.partials._top-customer',['top_customers'=>$data['top_customers']])
                </div>
                <!-- End Card -->
            </div>

        </div>

        {{-- Analytics Chart --}}
        @if(hasPermission('analytics', 'view'))
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Analytics Overview</h5>
                        <div class="d-flex align-items-center">
                            <select id="analytics-days" class="form-control form-control-sm" style="width:auto;">
                                <option value="7">Last 7 Days</option>
                                <option value="15">Last 15 Days</option>
                                <option value="30" selected>Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                            </select>
                            <a href="{{ route('admin.analytics.index') }}" class="btn btn-sm btn-outline-primary ml-2">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="analyticsChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @else
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">{{translate('messages.welcome')}}, {{auth('admin')->user()->f_name}}.</h1>
                    <p class="page-header-text">Welcome to MyChitti Dashboard</p>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        @endif
    </div>
@endsection

@push('script')
    <script src="{{asset('public/assets/admin')}}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/vendor/chart.js.extensions/chartjs-extensions.js"></script>
    <script src="{{asset('public/assets/admin')}}/vendor/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.min.js"></script>

    <!-- Apex Charts -->
    <script src="{{asset('/public/assets/admin/js/apex-charts/apexcharts.js')}}"></script>
    <!-- Apex Charts -->

@endpush


@push('script_2')

    {{-- Analytics Chart --}}
    @if(hasPermission('analytics', 'view'))
    <script>
        var analyticsChart = null;

        function loadAnalyticsChart(days) {
            $.get("{{ route('admin.analytics.chart-data') }}", { days: days }, function(data) {
                if (analyticsChart) {
                    analyticsChart.destroy();
                }

                var ctx = document.getElementById('analyticsChart');
                if (!ctx) return;

                analyticsChart = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Store Visits',
                                data: data.store_visits,
                                borderColor: '#45b6fe',
                                backgroundColor: 'rgba(69,182,254,0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'Banner Clicks',
                                data: data.banner_clicks,
                                borderColor: '#ff6b6b',
                                backgroundColor: 'rgba(255,107,107,0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'Ad Clicks',
                                data: data.ad_clicks,
                                borderColor: '#ffa500',
                                backgroundColor: 'rgba(255,165,0,0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'Location Views', 
                                data: data.location_views,
                                borderColor: '#9b59b6',
                                backgroundColor: 'rgba(155,89,182,0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                            },
                            { 
                                label: 'Phone Unmasks',
                                data: data.phone_unmasks,
                                borderColor: '#51cf66',
                                backgroundColor: 'rgba(81,207,102,0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            yAxes: [{
                                ticks: { beginAtZero: true, precision: 0 }
                            }]
                        },
                        tooltips: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                });
            });
        }

        $(document).ready(function() {
            loadAnalyticsChart(30);

            $('#analytics-days').on('change', function() {
                loadAnalyticsChart($(this).val());
            });
        });
    </script>
    @endif

    <!-- Dognut Pie Chart -->
    <script>
        "use strict";
        let options;
        let chart;
        options = {
            series: [{{ $data['customer']}}, {{$data['stores']}}, {{$data['delivery_man']}}],
            chart: {
                width: 320,
                type: 'donut',
            },
            labels: ['{{ translate('Customer') }}', '{{ translate('Store') }}', '{{ translate('Delivery man') }}'],
            dataLabels: {
                enabled: false,
                style: {
                    colors: ['#005555', '#00aa96', '#b9e0e0',]
                }
            },
            responsive: [{
                breakpoint: 1650,
                options: {
                    chart: {
                        width: 250
                    },
                }
            }],
            colors: ['#005555','#00aa96', '#111'],
            fill: {
                colors: ['#005555','#00aa96', '#b9e0e0']
            },
            legend: {
                show: false
            },
        };

        chart = new ApexCharts(document.querySelector("#dognut-pie"), options);
        chart.render();


    options = {
          series: [{
          name: '{{ translate('Gross Sale') }}',
          data: [{{ implode(",",$total_sell) }}]
        },{
          name: '{{ translate('Admin Comission') }}',
          data: [{{ implode(",",$commission) }}]
        }],
          chart: {
          height: 350,
          type: 'area',
          toolbar: {
            show:false
        },
            colors: ['#76ffcd','#ff6d6d', '#005555'],
        },
            colors: ['#76ffcd','#ff6d6d', '#005555'],
        dataLabels: {
          enabled: false,
            colors: ['#76ffcd','#ff6d6d', '#005555'],
        },
        stroke: {
          curve: 'smooth',
          width: 2,
            colors: ['#76ffcd','#ff6d6d', '#005555'],
        },
        fill: {
            type: 'gradient',
            colors: ['#76ffcd','#ff6d6d', '#005555'],
        },
        xaxis: {
        //   type: 'datetime',
          categories: [{!! implode(",",$data['label']) !!}]
        },
        tooltip: {
          x: {
            format: 'dd/MM/yy HH:mm'
          },
        },
        };

        chart = new ApexCharts(document.querySelector("#grow-sale-chart"), options);
        chart.render();


    <!-- Dognut Pie Chart -->

        // INITIALIZATION OF CHARTJS
        // =======================================================
        Chart.plugins.unregister(ChartDataLabels);

        $('.js-chart').each(function () {
            $.HSCore.components.HSChartJS.init($(this));
        });

        let updatingChart = $.HSCore.components.HSChartJS.init($('#updatingData'));


        $('.order_stats_update').on('change', function (){
            let type = $(this).val();
            order_stats_update(type);
        })

        function order_stats_update(type) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.order')}}',
                data: {
                    statistics_type: type
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    insert_param('statistics_type',type);
                    $('#order_stats').html(data.view)
                },
                complete: function () {
                    $('#loading').hide()
                }
            });
        }

        $('.fetch_data_zone_wise').on('change', function (){
            let zone_id = $(this).val();
            fetch_data_zone_wise(zone_id);
        })


        function fetch_data_zone_wise(zone_id) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.zone')}}',
                data: {
                    zone_id: zone_id
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    insert_param('zone_id', zone_id);
                    $('#order_stats').html(data.order_stats);
                    $('#user-overview-board').html(data.user_overview);
                    $('#monthly-earning-graph').html(data.monthly_graph);
                    $('#popular-restaurants-view').html(data.popular_restaurants);
                    $('#top-deliveryman-view').html(data.top_deliveryman);
                    $('#top-rated-foods-view').html(data.top_rated_foods);
                    $('#top-restaurants-view').html(data.top_restaurants);
                    $('#top-selling-foods-view').html(data.top_selling_foods);
                    $('#top-customer-view').html(data.top_customers);
                    $('#stat_zone').html(data.stat_zone);
                },
                complete: function () {
                    $('#loading').hide()
                }
            });
        }

        $('.user_overview_stats_update').on('change', function (){
            let type = $(this).val();
            user_overview_stats_update(type);
        })


        function user_overview_stats_update(type) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.user-overview')}}',
                data: {
                    user_overview: type
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    insert_param('user_overview',type);
                    $('#user-overview-board').html(data.view)
                },
                complete: function () {
                    $('#loading').hide()
                }
            });
        }

        $('.commission_overview_stats_update').on('change', function (){
            let type = $(this).val();
            commission_overview_stats_update(type);
        })


        function commission_overview_stats_update(type) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.commission-overview')}}',
                data: {
                    commission_overview: type
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    insert_param('commission_overview',type);
                    $('#commission-overview-board').html(data.view)
                    $('#gross_sale').html(data.gross_sale)
                },
                complete: function () {
                    $('#loading').hide()
                }
            });
        }

        function insert_param(key, value) {
            key = encodeURIComponent(key);
            value = encodeURIComponent(value);
            // kvp looks like ['key1=value1', 'key2=value2', ...]
            let kvp = document.location.search.substr(1).split('&');
            let i = 0;

            for (; i < kvp.length; i++) {
                if (kvp[i].startsWith(key + '=')) {
                    let pair = kvp[i].split('=');
                    pair[1] = value;
                    kvp[i] = pair.join('=');
                    break;
                }
            }
            if (i >= kvp.length) {
                kvp[kvp.length] = [key, value].join('=');
            }
            // can return this or...
            let params = kvp.join('&');
            // change url page with new params
            window.history.pushState('page2', 'Title', '{{url()->current()}}?' + params);
        }
    </script>

    @if(auth('admin')->user()->role_id != 1)
    <script>
        $(document).ready(function() {
            let staffDashIsPunchedIn = @json(_clockedInEmployee() ?? 0);
            let staffDashPunchInTime = @json(_inTime('timestamp') ?? null);
            let staffDashDutyHours = @json(_clockedInEmployeeDutyHours() ?? 8);

            if (staffDashPunchInTime) staffDashPunchInTime = new Date(staffDashPunchInTime);

            @if (_clockedInEmployeeDutyHours())
            function staffDashUpdateDateTime() {
                const now = new Date(); 
                if (staffDashIsPunchedIn && staffDashPunchInTime) {
                    const elapsedMs = now - staffDashPunchInTime;
                    const totalDutyMs = staffDashDutyHours * 60 * 60 * 1000;
                    const remainingMs = totalDutyMs - elapsedMs;
 
                    if (remainingMs > 0) {
                        const remainingHours = Math.floor(remainingMs / (1000 * 60 * 60));
                        const remainingMinutes = Math.floor((remainingMs % (1000 * 60 * 60)) / (1000 * 60));
                        const remainingSeconds = Math.floor((remainingMs % (1000 * 60)) / 1000);
                        $('#staffDashCurrentDateTime').html(
                            `<span style="font-weight: 600;">Remaining Time </span><br> <span style="font-size: 22px;"> ${String(remainingHours).padStart(2,'0')}:${String(remainingMinutes).padStart(2,'0')}:${String(remainingSeconds).padStart(2,'0')}</span>`
                        );
                    } else {
                        const overtimeMs = Math.abs(remainingMs);
                        const overtimeHours = Math.floor(overtimeMs / (1000 * 60 * 60));
                        const overtimeMinutes = Math.floor((overtimeMs % (1000 * 60 * 60)) / (1000 * 60));
                        const overtimeSeconds = Math.floor((overtimeMs % (1000 * 60)) / 1000);
                        $('#staffDashCurrentDateTime').html(
                            `<span style="color: #22c55e; font-weight: 600;">Duty Completed!</span> | ` +
                            `<span style="color: #F59E0B; font-weight: 600;">Overtime: ${String(overtimeHours).padStart(2,'0')}:${String(overtimeMinutes).padStart(2,'0')}:${String(overtimeSeconds).padStart(2,'0')}</span>`
                        );
                    }
                }
            }
            setInterval(staffDashUpdateDateTime, 1000);
            @endif

            $('#staffDashPunchBtn').on('click', function() {
                staffDashIsPunchedIn = !staffDashIsPunchedIn;
                const currentTime = new Date().toLocaleTimeString();

                if (staffDashIsPunchedIn) {
                    staffDashPunchInTime = new Date();
                    $(this).removeClass('btn-success').addClass('btn-danger');
                    $('#staffDashPunchIcon').html('&#9632;');
                    $('#staffDashPunchText').text('Punch Out');
                    $('#staffDashPunchTimeDisplay').show().text('Punched in at: ' + currentTime);
                } else {
                    staffDashPunchInTime = null;
                    $(this).removeClass('btn-danger').addClass('btn-success');
                    $('#staffDashPunchIcon').html('&#9654;');
                    $('#staffDashPunchText').text('Punch In');
                    $('#staffDashCurrentDateTime').html('<span style="font-size: 18px; font-weight: 600; color: #999;">Not Clocked In</span>');
                }
                adminClock(staffDashIsPunchedIn ? 'in' : 'out');
            });
        });

        function adminClock(action) {
            var url = action == 'in' ? '{{ route("admin.employee.clockin") }}' : '{{ route("admin.employee.clockout") }}';
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });
            $.get({
                url: url,
                data: { action: action },
                beforeSend: function() { $('#loading').show() },
                success: function(data) {
                    if (action == 'out') {
                        $("#staffDashCurrentDateTime").html('<span style="font-size: 18px; font-weight: 600; color: #999;">Not Clocked In</span>');
                    }
                    $('#loading').hide();
                },
                complete: function() {}
            });
        }
    </script>
    @endif
@endpush
