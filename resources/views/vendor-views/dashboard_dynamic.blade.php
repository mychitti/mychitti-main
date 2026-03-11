@extends('layouts.vendor.app')

@section('title', translate('messages.dashboard'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .app_dwnld_div img {
            width: 150px;
        }

        .app_dwnld_div {
            text-align: center;
        }

        .btn-analysis2 {
            border-radius: 10px;
            padding: 7px;
            margin: 0 2px;

        }

        .card-box {
            background-color: #ffffff;
            border-radius: 1rem;
            padding: 1.5rem 19px;
            text-align: center;
            height: 100%;
            box-shadow: 0 6px 12px rgb(140 152 164 / 18%) !important;
        }

        .metric-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }

        .metric-value {
            font-size: 1.6rem;
            font-weight: bold;
            background-color: #dcdaed4a;
            border-radius: 10px;
            margin-top: 6px;
            width: 100%;
            display: block;
        }

        .small-text {
            font-size: 0.85rem;
            color: #666;
        }

        .text-green {
            color: #28a745;
        }

        .text-red {
            color: #dc3545;
        }

        .btn-analysis {
            border-radius: 999px;
            padding: 0.4rem 1rem;
            width: 100%;
        }
    </style>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 1.5rem;
        }

        .activity-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
        }

        .d-card-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .d-card-header i {
            color: #666;
            font-size: 1.2rem;
        }

        .d-card-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #333;
        }

        .card-content {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .stat-badge {
            min-width: 135px;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .stat-badge:hover {
            color: white;
        }

        .stat-value {
            font-size: 1.75rem;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
        }

        /* Color Variants */
        .badge-green {
            background-color: #10b981;
            color: white;
        }

        .badge-blue {
            background-color: #3b82f6;
            color: white;
        }

        .badge-purple {
            background-color: #7d10b9;
            color: white;
        }

        .badge-blue2 {
            background-color: #4f3cfb;
            color: white;
        }

        .badge-orange2 {
            background-color: #f6683b;
            color: white;
        }

        .badge-green2 {
            background-color: #10b924;
            color: white;
        }

        .badge-pink {
            background-color: #f63b89;
            color: white;
        }

        .badge-aqua {
            background-color: #10a8b9;
            color: white;
        }

        .badge-darkblue {
            background-color: #103eb9;
            color: white;
        }

        .badge-voilet {
            background-color: #863bf6;
            color: white;
        }

        .badge-yellow {
            background-color: #fbd03c;
            color: white;
        }

        .badge-orange {
            background-color: #fb923c;
            color: white;
        }

        .badge-red {
            background-color: #ef4444;
            color: white;
        }

        .badge-light-blue {
            background-color: #60a5fa;
            color: white;
        }

        .badge-gray {
            background-color: #f3f4f6;
            color: #333;
            border: 2px solid #d1d5db;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .stat-badge {
                min-width: 100px;
                padding: 0.75rem 1rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        @if (auth('vendor')->check())
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center w-100">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <h1 class="page-header-title">
                            <span class="page-header-icon">
                                <img src="{{ asset('public/assets/admin/img/category.png') }}" class="w--20" alt="">
                            </span>
                            <span>{{ translate('messages.dashboard') }}</span>
                        </h1>
                        <button class="d-none d-sm-block btn btn-primary btn_sm" type="button" data-toggle="modal"
                            data-target="#exampleModal">Apply
                            Coupon for Customer</button>
                        <button class="d-block d-sm-none btn btn-primary btn_sm" type="button" data-toggle="modal"
                            data-target="#exampleModal">Apply
                            Coupon</button>
                    </div>

                </div>
            </div>
            <!-- End Page Header -->
            <div class="dashboard-grid">

                <!-- Leads Activity -->
                <div class="activity-card">
                    <div class="d-card-header">
                        <i class="fas fa-box"></i>
                        <h2 class="d-card-title">Leads Activity</h2> 

                    </div>
                    <div class="card-content">
                        <a href="{{ route('vendor.service.leads_list') }}?type=Completed" class="stat-badge badge-green">
                            <span class="stat-value">{{ $leadStatistics['completed'] }}</span>
                            <span class="stat-label">Completed Leads</span>
                        </a>
                        <a href="{{ route('vendor.service.leads_list') }}?type={{ urlencode('In Progress') }}"
                            class="stat-badge badge-blue">
                            <span class="stat-value">{{ $leadStatistics['in_progress'] }}</span>
                            <span class="stat-label">Inprogress Leads</span>
                        </a>
                        <a href="{{ route('vendor.service.leads_list') }}?type=New" class="stat-badge badge-orange">
                            <span class="stat-value">{{ $leadStatistics['new'] }}</span>
                            <span class="stat-label">New Leads</span>
                        </a>
                    </div>
                </div>

                <!-- Task Activity -->
                <div class="activity-card">
                    <div class="d-card-header">
                        <i class="fas fa-clipboard-list"></i>
                        <h2 class="d-card-title">Task Activity</h2>
                    </div> 
                    <div class="card-content">
                        <a href="{{ route('vendor.task.list') }}?status=Completed" class="stat-badge badge-purple">
                            <span class="stat-value">{{ $taskStats['completed'] }}</span>
                            <span class="stat-label">Completed Tasks</span>
                        </a>
                        <a href="{{ route('vendor.task.list') }}?status={{ urlencode('In Progress') }}"
                            class="stat-badge badge-aqua">
                            <span class="stat-value">{{ $taskStats['in_progress'] }}</span>
                            <span class="stat-label">Inprogress Tasks</span>
                        </a>
                        <a href="{{ route('vendor.task.list') }}?status=New" class="stat-badge badge-pink">
                            <span class="stat-value">{{ $taskStats['new'] }}</span>
                            <span class="stat-label">New Tasks</span>
                        </a>
                    </div>
                </div>

                <!-- Inventory Activity -->
                <div class="activity-card">
                    <div class="d-card-header">
                        <i class="fas fa-warehouse"></i>
                        <h2 class="d-card-title">Inventory Activity</h2>
                    </div>
                    <div class="card-content">
                        <a href="{{ route('vendor.inventory.index') }}" class="stat-badge badge-darkblue">
                            <span class="stat-value">{{ $inventoryStats['total'] }}</span>
                            <span class="stat-label">Total Items</span>
                        </a>
                        <a href="{{ route('vendor.inventory.index') }}?type=product" class="stat-badge badge-yellow">
                            <span class="stat-value">{{ $inventoryStats['products'] }}</span>
                            <span class="stat-label">Products</span>
                        </a>
                        <a href="{{ route('vendor.inventory.index') }}?type=service" class="stat-badge badge-voilet">
                            <span class="stat-value">{{ $inventoryStats['services'] }}</span>
                            <span class="stat-label">Service</span>
                        </a>
                    </div>
                </div>

                <!-- Order Activity -->
                {{-- <div class="activity-card">
                    <div class="d-card-header">
                        <i class="fas fa-shopping-cart"></i>
                        <h2 class="d-card-title">Order Activity</h2>
                    </div>
                    <div class="card-content">
                        <div class="stat-badge badge-green2">
                            <span class="stat-value">0</span>
                            <span class="stat-label">Completed Orders</span>
                        </div>
                        <div class="stat-badge badge-orange2">
                            <span class="stat-value">0</span>
                            <span class="stat-label">In Progress Orders</span>
                        </div>
                        <div class="stat-badge badge-blue2">
                            <span class="stat-value">0</span>
                            <span class="stat-label">New Orders</span>
                        </div>
                    </div>
                </div> --}}

                <!-- Accounts Summary -->
                <div class="activity-card">
                    <div class="d-card-header">
                        <i class="fas fa-dollar-sign"></i>
                        <h2 class="d-card-title">Accounts Summary</h2>
                    </div>
                    <div class="card-content">
                        <a href="{{ route('vendor.account.dashboard') }}" class="stat-badge badge-green">
                            <span class="stat-value">{{ _price($accountstats['income'], 'round', 3) }}</span>
                            <span class="stat-label">Total Income</span>
                        </a>
                        <a href="{{ route('vendor.account.dashboard') }}" class="stat-badge badge-orange">
                            <span class="stat-value">{{ _price($accountstats['expense'], 'round', 3) }}</span>
                            <span class="stat-label">Total Expenses</span>
                        </a>
                        <a href="{{ route('vendor.account.dashboard') }}" class="stat-badge badge-red">
                            <span class="stat-value">{{ _price($accountstats['pending_payments'], 'round', 3) }}</span>
                            <span class="stat-label">Pending Payments</span>
                        </a>
                        <a href="{{ route('vendor.account.dashboard') }}" class="stat-badge badge-light-blue">
                            <span
                                class="stat-value">{{ _price($accountstats['income'] - $accountstats['expense'], 'round', 3) }}</span>
                            <span class="stat-label">Net Profit</span>
                        </a>
                    </div>
                </div>

                <!-- Employees Activity -->
                <div class="activity-card">
                    <div class="d-card-header">
                        <i class="fas fa-users"></i>
                        <h2 class="d-card-title">Employees Activity</h2>
                    </div>
                    <div class="card-content">
                        <a href="{{ route('vendor.staff.list') }}" class="stat-badge badge-green">
                            <span class="stat-value">{{ $empStats['total_employees'] }}</span>
                            <span class="stat-label">Total Employees</span>
                        </a>
                        <a href="{{ route('vendor.staff.list') }}?status=on_duty" class="stat-badge badge-light-blue">
                            <span class="stat-value">{{ $empStats['present_employees'] }}</span>
                            <span class="stat-label">On Duty Emp.</span>
                        </a>
                    </div>
                </div>
            </div>

            @if (0 && \App\CentralLogics\Helpers::get_store_data()['module_id'] == 5)
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row gx-2 gx-lg-3 mb-2">
                            <div class="col-md-9">
                                <h4><i
                                        class="tio-chart-bar-4 fz-30px"></i>{{ translate('messages.dashboard_order_statistics') }}
                                </h4>
                            </div>
                            <div class="col-md-3">
                                <select class="custom-select order_stats_update" name="statistics_type">
                                    <option value="overall"
                                        {{ $params['statistics_type'] == 'overall' ? 'selected' : '' }}>
                                        {{ translate('messages.Overall Statistics') }}
                                    </option>
                                    <option value="today" {{ $params['statistics_type'] == 'today' ? 'selected' : '' }}>
                                        {{ translate("messages.Today's Statistics") }}
                                    </option>
                                    <option value="this_month"
                                        {{ $params['statistics_type'] == 'this_month' ? 'selected' : '' }}>
                                        {{ translate("messages.This Month's Statistics") }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2" id="order_stats">
                            @include('vendor-views.partials._dashboard-order-stats', ['data' => $data])
                        </div>
                    </div>
                </div>
            @endif 


            <!-- End Row -->
        @else
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm mb-2 mb-sm-0">
                        <h1 class="page-header-title">{{ translate('messages.welcome') }},
                            {{ auth('vendor_employee')->user()->f_name }}.</h1>
                        <p class="page-header-text">Welcome to MyChitti Dashboard</p>
                    </div>

                </div>
                <div class="row">
                    <div class="widget-container col-md-4">
                        <div id="js-clock-in-out" class="card dashboard-icon-widget clock-in-out-card time_det_outer">
                            <div class="card-body d-flex justify-content-between  timing_det">
                                <div class="widget-icon {{ _clockedInEmployee() ? 'bg-info' : 'bg-danger' }}  "
                                    style="    display: flex;align-items: center;padding: 10px 13px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="feather feather-clock icon"
                                        style="color:white;">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </div>
                                <div class="widget-details ">
                                    @if (_clockedInEmployee())
                                        <button type="button" class="btn btn-default text-primary"
                                            id="timecard-clock-btn" onclick="clock('out')" style="float:right">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="feather feather-log-out icon-16">
                                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                                <polyline points="16 17 21 12 16 7"></polyline>
                                                <line x1="21" y1="12" x2="9" y2="12">
                                                </line>
                                            </svg> Clock Out
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-default text-danger"
                                            id="timecard-clock-btn" onclick="clock('in')" style="float:right">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="feather feather-log-out icon-16">
                                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                                <polyline points="16 17 21 12 16 7"></polyline>
                                                <line x1="21" y1="12" x2="9" y2="12">
                                                </line>
                                            </svg> Clock In
                                        </button>
                                    @endif
                                    <div class="mt5 bg-transparent-white" title="24-05-2024 10:08:27 am"
                                        style="float:right;width: 100%; text-align: right;"><?= _inTime() ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="widget-container col-md-4">
                        <div id="js-clock-in-out" class="card dashboard-icon-widget clock-in-out-card ">
                            <div class="card-body d-flex justify-content-between ">

                                <div class="widget-details d-flex flex-column align-items-center w-100">
                                    My Attendance
                                    <a href="{{ route('vendor.employee-attendance') }}"
                                        class="btn btn--primary mb-0">View Attendance</a>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Page Header -->
        @endif
    </div>


    <div class="p-2">
        <h3>Download Android Apps</h3>
        <div class="d-flex flex-wrap">
            <div class="app_dwnld_div">
                <a target="_blank" href="https://play.google.com/store/apps/details?id=com.mcvendor">
                    <img src="{{ asset('storage/app/public/util/android_app_download.png') }}">
                    <p>Vendor App</p>
                </a>
            </div>
            <div class="app_dwnld_div">
                <a target="_blank"
                    href="https://play.google.com/store/apps/details?id=com.mychitti.staff&pcampaignid=web_share">
                    <img src="{{ asset('storage/app/public/util/android_app_download.png') }}">
                    <p>Staff App</p>
                </a>
            </div>
            <div class="app_dwnld_div">
                <a target="_blank" href="https://play.google.com/store/apps/details?id=com.mychittiappuser">
                    <img src="{{ asset('storage/app/public/util/android_app_download.png') }}">
                    <p>User App</p>
                </a>
            </div>
            {{-- <div class="app_dwnld_div">
                <a target="_blank" href="https://play.google.com/store/apps/details?id=com.mychitti_delivery_app">
                    <img src="{{ asset('storage/app/public/util/android_app_download.png') }}">
                    <p>Delivery App</p>
                </a>
            </div> --}}
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Apply Coupon</h5>
                    <button type="button" class="close close_coupon_modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form class="applyCouponForm">
                    @csrf
                    <div class="modal-body">
                        <label>Coupon Code</label>

                        <input type="text" name="coupon_code" id="app_coupon_code" class="form-control" required>

                        <span class="text-danger coupon_error"></span>
                        <span class="text-success coupon_success"></span>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                        <button type="button" class="btn btn-primary applyCouponBtn">Apply Coupon</button>
                    </div>
                </form>


            </div>
        </div>
    </div>
    @include('vendor-views.form_modals.customer_add')

@endsection

@push('script')
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js.extensions/chartjs-extensions.js"></script>
    <script
        src="{{ asset('public/assets/admin') }}/vendor/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.min.js">
    </script>
@endpush


@push('script_2')
    <script> 
        @if (auth('vendor')->check())
            window.ReactNativeWebView?.postMessage(
                JSON.stringify({
                    type: 'USER_LOGIN',
                    vendor_id: {{ auth('vendor')->id() }}
                })
            );
        @endif
    </script>
    <script>
        $(document).on('click', '.applyCouponBtn', function(e) {
            console.log('fsdf')
            e.preventDefault();
            e.stopImmediatePropagation();
            $(".applyCouponBtn").attr('disabled', true)

            $('.coupon_error').text('');
            $('.coupon_success').text('');

            let btn = $(this);
            btn.prop('disabled', true).text('Applying...');

            $.ajax({
                url: '{{ route('vendor.applyCoupon') }}',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    coupon_code: $("#app_coupon_code").val()
                },
                success: function(data) {
                    console.log(data);

                    if (data.status) {
                        $('.coupon_success').text(data.message);
                        setTimeout(() => {
                            $(".applyCouponForm").trigger('reset')

                            $(".close_coupon_modal").click()
                            $(".applyCouponBtn").removeAttr('disabled')
                        }, 1000);
                    } else {
                        $('.coupon_error').text(data.message);
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    $('.coupon_error').text('Server error');
                },
                complete: function() {
                    btn.prop('disabled', false).text('Apply Coupon');
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#custom-buttons').on('click', 'button', function() {
                const label = $(this).data('label');
                let inputGroup = '';

                if (label === 'Other') {
                    inputGroup = `
        <div class="form-group custom-field" data-label="${label}">
            <div class="d-flex mb-2">
                <input type="text" class="form-control mr-2" placeholder="Label" name="header_label[]">
                <input type="text" class="form-control mr-2" name="header_field[]">
                <a type="button" class="text-danger remove-field"><i class="tio-delete-outlined"></i></a>
            </div>
        </div>
        `;

                } else {
                    $('.' + label)
                        .show() // Hide the clicked button
                    $(this).hide();
                }
                console.log(label)

                $('#custom-fields').append(inputGroup);
            });

            //Handle remove
            $('#custom-fields').on('click', '.remove-field', function() {
                console.log('remove')
                const $fieldGroup = $(this).closest('.custom-field');
                const label = $fieldGroup.data('label');

                // Show back the corresponding button
                $('#custom-buttons button').each(function() {
                    if ($(this).data('label') === label) {
                        $(this).show();
                    }
                });

                $fieldGroup.remove();
            });

        });
        // INITIALIZATION OF CHARTJS
        // =======================================================
        Chart.plugins.unregister(ChartDataLabels);

        $('.js-chart').each(function() {
            $.HSCore.components.HSChartJS.init($(this));
        });

        let updatingChart = $.HSCore.components.HSChartJS.init($('#updatingData'));

        $('.order_stats_update').on('change', function() {
            let type = $(this).val();
            order_stats_update(type);
        })

        function clock(action) {

            if (action == 'in') {
                var url = '{{ route('vendor.clockin') }}'
            } else {
                var url = '{{ route('vendor.clockout') }}'
            }

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.get({
                url: url,
                data: {
                    action: action
                },
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(data) {
                    console.log(data)
                    if (data.status) {
                        $('.time_det_outer').load(window.location.href + ' .timing_det');
                    }
                    $('#loading').hide()
                },
                complete: function() {

                }
            });
        }

        function order_stats_update(type) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('vendor.dashboard.order-stats') }}',
                data: {
                    statistics_type: type
                },
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(data) {
                    insert_param('statistics_type', type);
                    $('#order_stats').html(data.view)
                },
                complete: function() {
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
