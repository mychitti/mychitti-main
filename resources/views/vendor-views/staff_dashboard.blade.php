@extends('layouts.vendor.app')

@section('title', translate('messages.dashboard'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --staff-dash-primary: var(--primary);
            --staff-dash-secondary: var(--primary-light-theme);
        }

        .staff-dashboard-wrapper {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #F9FAFB;
            color: #000000;
        }

        .staff-dash-header {
            background: #FFFFFF;
            border-bottom: 1px solid #E5E7EB;
            padding: 12px 16px;
        }

        .staff-dash-header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .staff-dash-header-title {
            font-size: 20px;
            font-weight: 600;
            color: #000000;
        }

        .staff-dash-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .staff-dash-user-name {
            font-size: 14px;
            color: #000000;
        }

        .staff-dash-user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--staff-dash-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-size: 14px;
            font-weight: 600;
        }

        .staff-dash-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 16px;
        }

        .staff-dash-card {
            background: #FFFFFF;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 16px;
        }

        .staff-dash-punch-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .staff-dash-punch-info h2 {
            font-size: 16px;
            font-weight: 600;
            color: #000000;
            margin-bottom: 8px;
        }

        .staff-dash-time-display {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #000000;
            font-size: 14px;
        }

        .staff-dash-punch-time {
            margin-top: 8px;
            font-size: 13px;
            color: #000000;
        }

        .staff-dash-punch-btn {
            border: none;
            border-radius: 6px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #FFFFFF;
            transition: opacity 0.2s;
        }

        .staff-dash-punch-btn:hover {
            opacity: 0.9;
        }

        .staff-dash-punch-btn.staff-dash-punch-in {
            background: var(--staff-dash-secondary);
        }

        .staff-dash-punch-btn.staff-dash-punch-out {
            background: #EF4444;
        }

        .staff-dash-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .staff-dash-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .staff-dash-card-header h3 {
            font-size: 15px;
            font-weight: 600;
            color: #000000;
        }

        .staff-dash-stats-grid-inner {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .staff-dash-stat-item {
            text-align: left;
        }

        .staff-dash-stat-value {
            font-size: 24px;
            font-weight: 700;
        }

        .staff-dash-stat-label {
            font-size: 12px;
            color: #000000;
            margin-top: 4px;
        }

        .staff-dash-content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 16px;
        }

        .staff-dash-task-list,
        .staff-dash-project-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .staff-dash-task-item,
        .staff-dash-project-item {
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            padding: 12px;
        }

        .staff-dash-task-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 6px;
        }

        .staff-dash-task-title {
            font-size: 14px;
            font-weight: 500;
            color: #000000;
            flex: 1;
        }

        .staff-dash-task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .staff-dash-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 500;
            color: #000000;
        }

        .staff-dash-badge-high {
            background: #FEE2E2;
        }

        .staff-dash-badge-medium {
            background: #FEF3C7;
        }

        .staff-dash-badge-low {
            background: #DBEAFE;
        }

        .staff-dash-badge-completed {
            background: #D1FAE5;
        }

        .staff-dash-badge-in-progress {
            background: #DBEAFE;
        }

        .staff-dash-badge-pending {
            background: #F3F4F6;
        }

        .staff-dash-badge-active {
            background: #D1FAE5;
        }

        .staff-dash-due-date {
            font-size: 12px;
            color: #000000;
        }

        .staff-dash-project-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }

        .staff-dash-project-name {
            font-size: 14px;
            font-weight: 500;
            color: #000000;
        }

        .staff-dash-progress-section {
            margin-bottom: 8px;
        }

        .staff-dash-progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .staff-dash-progress-label {
            font-size: 12px;
            color: #000000;
        }

        .staff-dash-progress-value {
            font-size: 12px;
            font-weight: 600;
            color: #000000;
        }

        .staff-dash-progress-bar {
            background: #E5E7EB;
            border-radius: 4px;
            height: 6px;
            overflow: hidden;
        }

        .staff-dash-progress-fill {
            background: var(--staff-dash-primary);
            height: 100%;
            transition: width 0.3s;
        }

        .staff-dash-project-deadline {
            font-size: 12px;
            color: #000000;
        }

        .staff-dash-icon {
            width: 18px;
            height: 18px;
            color: var(--staff-dash-primary);
        }

        .staff-dash-hidden {
            display: none;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="">

            <div class="staff-dash-container p-0">
                <!-- Punch In/Out Section -->
                <div class="row g-0">
                    <div class="staff-dash-card col-md-4">
                        <div class="staff-dash-punch-section">
                            <div class="staff-dash-punch-info">
                                @if (_clockedInEmployee())
                                    @if (_clockedInEmployeeDutyHours())
                                        <div class="staff-dash-time-display">
                                            <span style="font-size: 26px;">⏰</span>
                                            <span id="staffDashCurrentDateTime">
                                                <div style="text-align: center;">
                                                    <span style="font-weight: 600; font-size: 16px;">Remaining
                                                        Time</span><br>
                                                    <span id="staffRemainingTime" class="remaining-time">
                                                        Loading...
                                                    </span>

                                                </div>
                                            </span>
                                        </div>
                                    @endif

                                    <div class="staff-dash-punch-time " id="staffDashPunchTimeDisplay">
                                        Punched in at: {{ date('H:i:s', strtotime(_inTime('timestamp'))) }}
                                    </div>
                                @else
                                    <div class="staff-dash-time-display">
                                        <span style="font-size: 26px;">⏰</span>
                                        <span id="staffDashCurrentDateTime">
                                            <span id="staffRemainingTime" class="remaining-time">
                                                Not Clocked In
                                            </span>
                                        </span>
                                    </div>
                                @endif
                            </div>
                            @if (_clockedInEmployee())
                                <button class="staff-dash-punch-btn staff-dash-punch-out" id="staffDashPunchBtn">
                                    <span id="staffDashPunchIcon">■</span>
                                    <span id="staffDashPunchText">Punch Out</span>
                                </button>
                            @else
                                <button class="staff-dash-punch-btn staff-dash-punch-in" id="staffDashPunchBtn">
                                    <span id="staffDashPunchIcon">▶</span>
                                    <span id="staffDashPunchText">Punch In</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4"></div>
                </div>

                <!-- Stats Grid -->
                <div class="staff-dash-stats-grid">
                    <!-- Attendance -->
                    <div class="staff-dash-card">
                        <div class="staff-dash-card-header d-flex justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="staff-dash-icon">📅</span>
                                <h3 class="mb-0">My Attendance</h3>
                            </div>
                            <a href="{{ route('vendor.employee-attendance') }}" class="text-primary">View Time Cards</a>
                        </div>
                        <div class="staff-dash-stats-grid-inner">
                            <div class="staff-dash-stat-item">
                                <div class="staff-dash-stat-value" style="color: var(--staff-dash-secondary);"
                                    id="staffDashPresentDays">{{ $att['present'] }}</div>
                                <div class="staff-dash-stat-label">Present</div>
                            </div>
                            <div class="staff-dash-stat-item">
                                <div class="staff-dash-stat-value" style="color: #EF4444;" id="staffDashAbsentDays">
                                    {{ $att['absent'] }}</div>
                                <div class="staff-dash-stat-label">Absent</div>
                            </div>
                            <div class="staff-dash-stat-item">
                                <div class="staff-dash-stat-value" style="color: #F59E0B;" id="staffDashLateDays">
                                    {{ $att['holiday'] }}</div>
                                <div class="staff-dash-stat-label">Holidays</div>
                            </div>
                            <div class="staff-dash-stat-item">
                                <div class="staff-dash-stat-value" style="color: var(--staff-dash-primary);"
                                    id="staffDashTotalDays">{{ $att['totalDays'] }}</div>
                                <div class="staff-dash-stat-label">Total Days</div>
                            </div>
                        </div>
                    </div>

                    <!-- Leaves -->
                    <div class="staff-dash-card">

                        <div class="staff-dash-card-header d-flex justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="staff-dash-icon">📄</span>
                                <h3 class="mb-0">My Leaves</h3>
                            </div>
                            <a href="{{ route('vendor.employee-leave') }}" class="text-primary">View Leaves</a>
                        </div>
                        <div class="staff-dash-stats-grid-inner">
                            <div class="staff-dash-stat-item">
                                <div class="staff-dash-stat-value" style="color: var(--staff-dash-primary);"
                                    id="staffDashTotalLeaves">{{ $leaves['allowed_leaves'] }}</div>
                                <div class="staff-dash-stat-label">Total Paid Leaves</div>
                            </div>
                            <div class="staff-dash-stat-item">
                                <div class="staff-dash-stat-value" style="color: #EF4444;" id="staffDashTakenLeaves">
                                    {{ $leaves['taken'] }}</div>
                                <div class="staff-dash-stat-label">Taken (
                                    CL:{{ $leaves['CL'] . ', SL:' . $leaves['SL'] . ', Half Day:' . $leaves['HD'] }})</div>
                            </div>

                            <div class="staff-dash-stat-item">
                                <div class="staff-dash-stat-value" style="color: var(--staff-dash-secondary);"
                                    id="staffDashAvailableLeaves">{{ $leaves['allowed_leaves'] - $leaves['taken'] }}</div>
                                <div class="staff-dash-stat-label">Available</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasks and Projects -->
                <div class="staff-dash-content-grid">
                    <!-- Assigned Tasks -->
                    <div class="staff-dash-card">
                        <div class="staff-dash-card-header d-flex justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="staff-dash-icon">✓</span>
                                <h3 class="mb-0">Assigned Tasks</h3>
                            </div>
                            <a href="{{ route('vendor.task.assigned_tasks') }}" class="text-primary">View All</a>
                        </div>
                        <div class="staff-dash-task-list" id="staffDashTaskList">
                            @foreach ($assingned_tasks as $key => $task)
                                <a href="{{ route('vendor.task.detail', [$task->id]) }}"
                                    class="staff-dash-task-item text-dark">
                                    <div class="staff-dash-task-header">
                                        <div class="staff-dash-task-title">{{ ucfirst($task->title) }}</div>
                                        <span
                                            class="staff-dash-badge staff-dash-badge-{{ $task->priority }}">{{ $task->priority }}</span>
                                    </div>
                                    <p>{{ $task->description }}</p>
                                    <div class="staff-dash-task-footer">
                                        <span class="badge badge-soft-success">{{ $task->status }}</span>
                                        <span class="staff-dash-due-date">Duration: {{ $task->time_count }}
                                            {{ $task->time_unit }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Assigned Projects -->
                    <div class="staff-dash-card">

                        <div class="staff-dash-card-header d-flex justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="staff-dash-icon">💼</span>
                                <h3 class="mb-0">Assigned Projects</h3>
                            </div>
                            <a href="{{ route('vendor.service.assigned_projects') }}" class="text-primary">View All</a>
                        </div>
                        <div class="staff-dash-project-list" id="staffDashProjectList">
                            @foreach ($assigned_projects as $key => $project)
                                <a href="{{ route('vendor.project.details', [$project->id]) }}"
                                    class="staff-dash-project-item">
                                    <div class="staff-dash-project-header">
                                        <div class="staff-dash-project-name">{{ ucfirst($project->project_title) }}
                                            @if ($project->project_manager == $employee_id)
                                                <span class="badge badge-soft-dark">Project Manager 👑</span>
                                            @endif
                                        </div>
                                        <span
                                            class="staff-dash-badge staff-dash-badge-{{ $project->priority }}">{{ $project->priority }}</span>
                                    </div>
                                    <p>{{ $project->short_description }}</p>
                                    <div class="staff-dash-progress-section">
                                        <div class="staff-dash-progress-header">
                                            <span class="staff-dash-progress-label">Progress</span>
                                            <span
                                                class="staff-dash-progress-value">{{ $project->prog_percent ?? 0 }}%</span>
                                        </div>
                                        <div class="staff-dash-progress-bar">
                                            <div class="staff-dash-progress-fill"
                                                style="width: {{ $project->prog_percent ?? 0 }}%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="staff-dash-project-deadline">Deadline: {{ $project->end_date }}</div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
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

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js.extensions/chartjs-extensions.js"></script>
    <script
        src="{{ asset('public/assets/admin') }}/vendor/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.min.js">
    </script>
    <script>
        @if (auth('vendor_employee')->check()) {
            window.ReactNativeWebView?.postMessage(
                JSON.stringify({
                    type: 'STAFF_LOGIN',
                    vendor_id: {{ auth('vendor_employee')->id() }}
                })
            );
        }
        @endif

        $(document).ready(function() {
            let staffDashIsPunchedIn = @json(_clockedInEmployee() ?? 0);
            let staffDashPunchInTime = @json(_inTime('timestamp') ?? null);
            let staffDashDutyHours = @json(_clockedInEmployeeDutyHours() ?? 8);

            if (staffDashPunchInTime) staffDashPunchInTime = new Date(staffDashPunchInTime);
            @if (_clockedInEmployeeDutyHours())
                function staffDashUpdateDateTime() {
                    const now = new Date();
                    const dateTimeStr = now.toLocaleDateString() + ' - ' + now.toLocaleTimeString();

                    if (staffDashIsPunchedIn && staffDashPunchInTime) {
                        const elapsedMs = now - staffDashPunchInTime;
                        const elapsedHours = Math.floor(elapsedMs / (1000 * 60 * 60));
                        const elapsedMinutes = Math.floor((elapsedMs % (1000 * 60 * 60)) / (1000 * 60));
                        const elapsedSeconds = Math.floor((elapsedMs % (1000 * 60)) / 1000);

                        const totalDutyMs = staffDashDutyHours * 60 * 60 * 1000;
                        const remainingMs = totalDutyMs - elapsedMs;

                        if (remainingMs > 0) {
                            const remainingHours = Math.floor(remainingMs / (1000 * 60 * 60));
                            const remainingMinutes = Math.floor((remainingMs % (1000 * 60 * 60)) / (1000 * 60));
                            const remainingSeconds = Math.floor((remainingMs % (1000 * 60)) / 1000);

                            $('#staffDashCurrentDateTime').html(
                                //  `<span style="color: var(--staff-dash-secondary); font-weight: 600;">Worked: ${String(elapsedHours).padStart(2,'0')}:${String(elapsedMinutes).padStart(2,'0')}:${String(elapsedSeconds).padStart(2,'0')}</span> | ` +
                                `<span style=" font-weight: 600;">Remaining Time </span><br> <span style="font-size : 22px;"> ${String(remainingHours).padStart(2,'0')}:${String(remainingMinutes).padStart(2,'0')}:${String(remainingSeconds).padStart(2,'0')}</span>`
                            );
                        } else {
                            const overtimeMs = Math.abs(remainingMs);
                            const overtimeHours = Math.floor(overtimeMs / (1000 * 60 * 60));
                            const overtimeMinutes = Math.floor((overtimeMs % (1000 * 60 * 60)) / (1000 * 60));
                            const overtimeSeconds = Math.floor((overtimeMs % (1000 * 60)) / 1000);

                            $('#staffDashCurrentDateTime').html(
                                `<span style="color: var(--staff-dash-secondary); font-weight: 600;">Duty Completed!</span> | ` +
                                `<span style="color: #F59E0B; font-weight: 600;">Overtime: ${String(overtimeHours).padStart(2,'0')}:${String(overtimeMinutes).padStart(2,'0')}:${String(overtimeSeconds).padStart(2,'0')}</span>`
                            );
                        }
                    }
                }
                // Update every second
                setInterval(staffDashUpdateDateTime, 1000);
            @endif


            $('#staffDashPunchBtn').on('click', function() {
                staffDashIsPunchedIn = !staffDashIsPunchedIn;
                const currentTime = new Date().toLocaleTimeString();

                if (staffDashIsPunchedIn) {
                    staffDashPunchInTime = new Date(); // Record punch in time
                    $(this).removeClass('staff-dash-punch-in').addClass('staff-dash-punch-out');
                    $('#staffDashPunchIcon').text('■');
                    $('#staffDashPunchText').text('Punch Out');
                    $('#staffDashPunchTimeDisplay').text('Punched in at: ' + currentTime).removeClass(
                        'staff-dash-hidden');
                } else {
                    staffDashPunchInTime = null; // Reset punch in time
                    $(this).removeClass('staff-dash-punch-out').addClass('staff-dash-punch-in');
                    $('#staffDashPunchIcon').text('▶');
                    $('#staffDashPunchText').text('Punch In');
                    $('#staffDashPunchTimeDisplay').removeClass('staff-dash-hidden');
                    staffDashUpdateDateTime();
                }

                // Update display immediately

                // Here you would make an AJAX call to save punch in/out to backend
                clock(staffDashIsPunchedIn ? 'in' : 'out')
                // $.ajax({
                //     url: '/api/punch',
                //     method: 'POST',
                //     data: { action: staffDashIsPunchedIn ? 'in' : 'out', time: currentTime },
                //     success: function(response) {
                //         console.log('Punch recorded');
                //     }
                // });
            });

        });

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
                    if (action == 'out') {
                        $("#staffDashCurrentDateTime").html(`<div style="text-align: center;">
                                                                                            <span id="staffRemainingTime" class="remaining-time">
                                                    Not Clocked In
                                                </span>
                                                                                    </div>`);
                    }
                    if (data.status) {
                        $('.time_det_outer').load(window.location.href + ' .timing_det');
                    }
                    $('#loading').hide()
                },
                complete: function() {

                }
            });
        }
    </script>
@endpush
