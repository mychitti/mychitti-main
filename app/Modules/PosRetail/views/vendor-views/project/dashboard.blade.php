@extends('layouts.vendor.app')

@section('title', translate('messages.project_dashboard'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
   {{-- @include('vendor-views/sub-module/partials/project') --}}
   

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm">
                    <h1 class="page-header-title">
                        <span>Project Dashboard</span>
                    </h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="card mb-3">
            <div class="card-body">
                <div class="row gx-2 gx-lg-3 mb-2">
                    <div class="col-md-9">
                        <h4><i class="tio-chart-bar-4 fz-30px"></i>Project Statistics</h4>
                    </div>

                </div>
                <div class="py-2"></div>
                <div class="" id="pnl_stats">
                    <div class="row g-2" id="pnl_stats_inner">
                        <div class="col-sm-6 col-lg-3">
                            <a class="order--card h-100" href="javascript:;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                        <span>Total Projects</span>
                                    </h6>
                                    <span class="card-title text-success earning_elem">
                                        {{ $stats['total_projects'] }}
                                    </span>
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <a class="order--card h-100" href="javascript:;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                        <span>In Progress</span>
                                    </h6>
                                    <span class="card-title text-warning earning_elem">
                                        {{ $stats['inProgress_projects'] }}
                                    </span>
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <a class="order--card h-100" href="javascript:;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                        <span>Completed Projects</span>
                                    </h6>
                                    <span class="card-title text-success earning_elem">
                                        {{ $stats['completed_projects'] }}
                                    </span>
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <a class="order--card h-100" href="javascript:;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                        <span>Delayed Projects</span>
                                    </h6>
                                    <span class="card-title text-danger expense_elem">
                                        {{ $stats['delayed_projects'] }}
                                    </span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row gx-2 gx-lg-3">
            <div class="col-lg-12 mb-3 mb-lg-12">
                <!-- Card -->
                <div class="card h-100">
                    <!-- Body -->
                    <div class="card-body">
                        <div class="col-sm-auto">
                            <!-- Legend Indicators -->
                            <h5 class="text-center">
                                Progress
                                <i class="tio-chart-bar-4 fz--40px"></i>
                            </h5>
                            <!-- End Legend Indicators -->
                        </div>
                        <!-- End Row -->

                        <!-- Bar Chart -->
                        <div class="chartjs-custom">
                            <canvas id="updatingData" class="h-20rem"
                                data-hs-chartjs-options='{
                            "type": "bar",
                            "data": {
                              "labels": {{ json_encode($projectNames) }},
                              "datasets": [{
                                "data": {{ json_encode($progress) }},
                                "backgroundColor": "#00AA96",
                                "hoverBackgroundColor": "#00AA96",
                                "borderColor": "#00AA96"
                              }]
                            },
                            "options": {
                              "scales": {
                                "yAxes": [{
                                  "gridLines": {
                                    "color": "#e7eaf3",
                                    "drawBorder": false,
                                    "zeroLineColor": "#e7eaf3"
                                  },
                                  "ticks": {
                                    "beginAtZero": true,
                                    "stepSize": {{ $progress > 1 ? 20000 : 1 }},
                                    "fontSize": 12,
                                    "fontColor": "#97a4af",
                                    "fontFamily": "Open Sans, sans-serif",
                                    "padding": 10,
                                    "postfix": " %"
                                  }
                                }],
                                "xAxes": [{
                                  "gridLines": {
                                    "display": false,
                                    "drawBorder": false
                                  },
                                  "ticks": {
                                    "fontSize": 12,
                                    "fontColor": "#97a4af",
                                    "fontFamily": "Open Sans, sans-serif",
                                    "padding": 5
                                  },
                                  "categoryPercentage": 0.5,
                                  "maxBarThickness": "10"
                                }]
                              },
                              "cornerRadius": 2,
                              "tooltips": {
                                "prefix": " ",
                                "hasIndicator": true,
                                "mode": "index",
                                "intersect": false
                              },
                              "hover": {
                                "mode": "nearest",
                                "intersect": true
                              }
                            }
                          }'></canvas>
                        </div>
                        <!-- End Bar Chart -->
                    </div>
                    <!-- End Body -->
                </div>
                <!-- End Card -->
            </div>


        </div>
    </div>
@endsection

@push('script')
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js.extensions/chartjs-extensions.js"></script>
    <script
        src="{{ asset('public/assets/admin') }}/vendor/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.min.js">
    </script>
@endpush


@push('script_2')
    <script></script>
    <script>
        // INITIALIZATION OF CHARTJS
        // =======================================================
        Chart.plugins.unregister(ChartDataLabels);

        $('.js-chart').each(function() {
            $.HSCore.components.HSChartJS.init($(this));
        });

        let updatingChart = $.HSCore.components.HSChartJS.init($('#updatingData'));

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
            window.history.pushState('page2', 'Title', '{{ url()->current() }}?' + params);
        }

        function filterPNL(elem) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "@php echo route('vendor.dashboard.filter-pnl') @endphp",
                data: {
                    month: $(elem).val()
                },
                success: function(data) {
                    $('.final_pl').html(data.html)
                    $('.earning_elem').html(data.earning)
                    $('.commission_elem').html(data.commission)
                },

            });
        }
    </script>
@endpush
