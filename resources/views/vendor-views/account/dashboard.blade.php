@extends('layouts.vendor.app')

@section('title', translate('messages.account_dashboard'))

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .brand-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: bold;
        }

        .brand-icon {
            font-size: 32px;
        }

        .header-actions {
            display: flex;
            gap: 40px;
        }

        .header-action-btn {
            padding: 8px 18px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .header-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .date-btn {
            background: #ec4899;
            color: white;
        }

        .calendar-btn {
            background: #60a5fa;
            color: white;
        }

        .metrics-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 15px;
        }

        .metric-box {
            padding: 18px;
            border-radius: 15px;
            color: white;
            text-align: center;
            transition: transform 0.3s;
        }

        .metric-box:hover {
            transform: translateY(-5px);
        }

        .metric-label {
            font-size: 13px;
            margin-bottom: 6px;
            opacity: 0.95;
            font-weight: 600;
        }

        .metric-number {
            font-size: 26px;
            font-weight: bold;
        }

        .income-box {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .expenses-box {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .pending-box {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .profit-box {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }

        {{-- .content-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 15px;
            margin-bottom: 15px;
        }
--}} .chart-container {
            background: #f8fafc;
            padding: 18px;
            border-radius: 15px;
        }

        .chart-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .chart-heading {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }

        .chart-legend {
            display: flex;
            gap: 12px;
        }

        .legend-dot {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
        }

        .dot-circle {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .profit-dot {
            background: #fb923c;
        }

        .loss-dot {
            background: #a855f7;
        }

        .bars-visual {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 200px;
            gap: 20px;
            padding: 10px 0;
        }

        .bar-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .bar-pair {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            width: 100%;
            justify-content: center;
            height: 160px;
        }

        .single-bar {
            width: 45px;
            border-radius: 8px 8px 0 0;
            transition: all 0.3s;
        }

        .single-bar:hover {
            opacity: 0.8;
        }

        .profit-bar {
            background: #fb923c;
        }

        .loss-bar {
            background: #a855f7;
        }

        .month-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
        }

        .actions-panel {
            background: #f8fafc;
            padding: 15px;
            border-radius: 15px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .panel-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .add-icon {
            width: 28px;
            height: 28px;
            background: white;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .add-icon:hover {
            background: #667eea;
            border-color: #667eea;
            color: white;
        }

        .action-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .action-item {
            background: white;
            padding: 12px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid #e2e8f0;
        }

        .action-item:hover {
            transform: translateX(5px);
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .action-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 20px;
        }

        .expenses-icon {
            background: #fef3c7;
            color: #f59e0b;
        }

        .approvals-icon {
            background: #d1fae5;
            color: #10b981;
        }

        .bill-icon {
            background: #ddd6fe;
            color: #8b5cf6;
        }

        .download-icon {
            background: #fecaca;
            color: #ef4444;
        }

        .action-text {
            font-weight: 600;
            font-size: 14px;
            color: #1e293b;
        }

        .download-sub {
            font-size: 12px;
            color: #64748b;
        }

        .table-section {
            background: #f8fafc;
            padding: 15px;
            border-radius: 15px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }

        .data-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .data-table thead {
            background: linear-gradient(135deg, #fca5a5, #fecaca);
        }

        .data-table th {
            padding: 12px;
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            text-align: center;
            border: none;
        }

        .data-table td {
            padding: 12px;
            font-size: 13px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }

        .data-table tbody tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-paid {
            background: #d1fae5;
            color: #059669;
        }

        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }

        @media (max-width: 968px) {
            {{-- .content-layout {
                grid-template-columns: 1fr;
            } --}} .metrics-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .metrics-row {
                grid-template-columns: 1fr;
            }

            .top-header {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
@endpush

@section('content')
    {{-- @include('vendor-views/sub-module/partials/account') --}}

    <div class="dashboard-wrapper p-3">
        <div class="top-header">
            <div class="brand-title">
                <span>Accounts Dashboard</span>
            </div>
            <div class="header-actions">
                <form action="" class=" date-range-form">
                    @include('vendor-views/form_modals/date_range')
                    <button style="width:fit-content; white-space:nowrap;    margin-left: -78%;"
                        class="btn btn-outline-warning" type="button" data-toggle="modal" data-target="#dateRangeModal"><i
                            class="fas fa-filter"></i> {{ translate($preset) }}</button>
                </form>

                {{-- <button class="btn btn-outline-primary">
                    <i class="fas fa-calendar"></i> Calendar
                </button> --}}
            </div>
        </div>

        <div class="metrics-row">
            <div class="metric-box income-box">
                <div class="metric-label">Total Income</div>
                <div class="metric-number">{{ _price($data['income']->sum('total_amount')) }}</div>
            </div>
            <div class="metric-box expenses-box">
                <div class="metric-label">Total Expenses</div>
                <div class="metric-number">{{ _price($data['expense']->sum('total_amount')) }}</div>
            </div>
            <div class="metric-box pending-box">
                <div class="metric-label">Pending Payments</div>
                <div class="metric-number">{{ _price($data['pending_payments']->sum('total_amount')) }}</div>
            </div>
            <div class="metric-box profit-box">
                <div class="metric-label">Net Profit</div>
                <div class="metric-number">
                    {{ _price($data['income']->sum('total_amount') - $data['expense']->sum('total_amount')) }}</div>
            </div>
        </div>

        <div class="row content-layout">
            <div class="col-md-6 chart-container">
                <div class="chart-title-row">
                    <h2 class="chart-heading">Profit & Loss Report</h2>
                    <div class="chart-legend">
                        <div class="legend-dot">
                            <span class="dot-circle profit-dot"></span>
                            <span>Profit</span>
                        </div>
                        <div class="legend-dot">
                            <span class="dot-circle loss-dot"></span>
                            <span>Loss</span>
                        </div>
                    </div>
                </div>
                <canvas id="profitLossChart" style="max-height: 250px;"></canvas>

            </div>
            <div class="col-md-3">

            </div>

            <div class=" col-md-3 actions-panel">
                <div class="panel-header">
                    <h3 class="panel-title">Quick Actions</h3>
                    {{-- <div class="add-icon">
                        <i class="fas fa-plus"></i>
                    </div> --}}
                </div>
                <div class="action-list">
                    @if (hasPermission('boa_master_ledger', 'add'))
                        <a href="{{ route('vendor.account.add', ['add']) }}" class="action-item">
                            <div class="action-icon expenses-icon">
                                <i class="tio-receipt-outlined"></i>
                            </div>
                            <div class="action-text">Add Expenses</div>
                        </a>
                    @endif
                    @if (hasPermission('apporval_form_master_ledger', 'list'))
                        <a href="{{ route('vendor.account.request-form.master-ledger.index', ['id' => 0, 'tab' => 'approvals']) }}"
                            class="action-item">
                            <div class="action-icon approvals-icon">
                                <i class="tio-verified-outlined"></i>
                            </div>
                            <div class="action-text">Check Approvals</div>
                        </a>
                    @endif

                    <a href ="{{ route('vendor.invoice.manual-bill') }}" class="action-item">
                        <div class="action-icon bill-icon">
                            <i class="tio-document-text"></i>
                        </div>
                        <div class="action-text">Add Bill</div>
                    </a>
                    <div class="action-item">
                        <div class="action-icon download-icon">
                            <i class="tio-download-to"></i>
                        </div>
                        <div>
                            <div class="action-text">Download</div>
                            <div class="download-sub">Monthly report</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if (hasPermission('boa_master_ledger', 'list'))

            <div class="table-section">
                <h2 class="section-title">Master Ledger Entries</h2>
                <table class="data-table table table-responsive" style="overflow: scroll">
                    <thead>
                        <tr>
                            <th class="border-0">{{ translate('sl') }}</th>
                            <th class="border-0">Date</th>
                            <th class="border-0">Account</th>
                            <th class="border-0">Debit</th>
                            <th class="border-0">Credit</th>
                            <th class="border-0">Narration</th>
                            <th class="border-0">Voucher No.</th>
                            <th class="border-0">Status</th>
                        </tr>

                    </thead>
                    <tbody>
                        @foreach ($ledger_entries as $entry)
                            <tr class="row_clickable" data-id="{{ $entry->id }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        {{ $entry->entry_date }}
                                    </span>
                                </td>
                                <td>
                                    {{ $entry->account?->name }}
                                </td>

                                <td>
                                    @if ($entry->debit > 0)
                                        <span class="text-danger fs-1 fw-bold" style="font-size: 14px ; font-weight: bold;">
                                            {{ _price($entry->debit, null, 3) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($entry->credit > 0)
                                        <span class="text-success fs-1 fw-bold"
                                            style="font-size: 14px ; font-weight: bold;">
                                            {{ _price($entry->credit, null, 3) }}
                                        </span>
                                    @endif
                                </td>
                                </td>
                                <td>
                                    {{ $entry->voucher?->narration }}
                                </td>
                                <td>
                                    <span class="status-badge status-pending">{{ $entry->voucher?->voucher_number }}</span>
                                </td>

                                <td>

                                    @if ($entry->status == 'pending')
                                        <span class="badge badge-soft-warning">
                                            {{ translate('messages.pending') }}
                                        </span>
                                    @elseif($entry->status == 'rejected')
                                        <span class="badge badge-soft-danger">
                                            {{ translate('messages.rejected') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-success">
                                            {{ translate('messages.approved') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
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
        const ctx = document.getElementById('profitLossChart').getContext('2d');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($data['days']->map(fn($d) => date('d M', strtotime($d)))) !!},
                datasets: [{
                        label: 'Income',
                        data: {!! json_encode($data['income_daywise']) !!},
                        backgroundColor: '#22c55e',
                        borderRadius: 5
                    },
                    {
                        label: 'Expense',
                        data: {!! json_encode($data['expense_daywise']) !!},
                        backgroundColor: '#ef4444',
                        borderRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
    <script>
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
    @include('vendor-views/js/date_range')
@endpush
