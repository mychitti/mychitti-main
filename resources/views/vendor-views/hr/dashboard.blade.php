@extends('layouts.vendor.app')

@section('title', 'common HR Management')

@push('css_or_js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

    <style>
        .dashboard {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            {{-- margin-bottom: 30px; --}}
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }



        .main-content {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 30px;
        }

        .left-section {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .welcome-section {
            background: white;
            padding: 15px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .welcome-text h2 {
            font-size: 28px;
            color: #4285f4;
            margin-bottom: 5px;
        }

        .welcome-text p {
            color: #666;
            font-size: 14px;
        }

        .welcome-illustration {
            width: 150px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }

        .welcome-illustration::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 20px;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        }

        .stat-card.attendance {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
        }

        .stat-card.leaves {
            background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
        }

        .stat-card.leaves2 {
            background: linear-gradient(135deg, #beb9ff 0%, #43dee8 100%);
        }

        .stat-card.feedback {
            background: white;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .stat-card h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            opacity: 0.8;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
        }

        .feedback-button {
            background: #4285f4;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .happiness-section {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        }

        .happiness-section h3 {
            font-size: 14px;
            margin-bottom: 20px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .happiness-graph {
            height: 120px;
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }

        .happiness-graph::after {
            content: '';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
        }

        .calendar-team-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .calendar-section,
        .team-section {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        }

        .calendar-section h3,
        .team-section h3 {
            font-size: 14px;
            margin-bottom: 20px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .calendar-date {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .date-circle {
            width: 60px;
            height: 60px;
            background: #4285f4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        .calendar-dates {
            display: flex;
            gap: 15px;
        }

        .calendar-dates span {
            padding: 8px 12px;
            background: #f1f3f4;
            border-radius: 8px;
            font-size: 12px;
            color: #666;
        }

        .scrum-event {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 12px;
            margin-top: 15px;
        }

        .scrum-event h4 {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .scrum-event p {
            font-size: 12px;
            color: #666;
        }

        .team-stats {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .team-count {
            font-size: 32px;
            font-weight: 700;
            color: #333;
        }

        .team-progress {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: conic-gradient(#4285f4 0% 40%, #e8f0fe 40% 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: #4285f4;
        }

        .team-members {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .team-member {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .member-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            {{-- background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); --}}
        }

        .member-img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
        }

        .member-info h4 {
            font-size: 14px;
            margin-bottom: 2px;
        }

        .member-info p {
            font-size: 12px;
            color: #666;
        }

        .right-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .updates-section {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        }

        .updates-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .updates-header h3 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #666;
        }

        .appreciation-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #e3f2fd;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #1976d2;
        }

        .appreciation-badge::before {
            content: '🏆';
        }

        .update-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .update-stat {
            text-align: center;
        }

        .update-stat h4 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .update-stat p {
            font-size: 12px;
            color: #666;
        }

        .april-summary {
            background: #4285f4;
            color: white;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
        }

        .april-summary h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .april-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .april-circle {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
        }

        .april-breakdown {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .april-breakdown div {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }

        .week-schedule {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .schedule-row {
            display: flex;
            margin: 8px 0;
            justify-content: space-between;
            padding: 20px 15px;
            border-bottom: 1px solid #f1f3f4;
            align-items: center;
            font-size: 12px;
        }

        .schedule-row:last-child {
            border-bottom: none;
        }

        .schedule-row.mon {
            background: #fff3e0;
        }

        .schedule-row.tue {
            background: #fce4ec;
        }

        .schedule-row.wed {
            background: #e8f5e8;
        }

        .schedule-row.thu {
            background: #fff3e0;
        }

        .schedule-row.fri {
            background: #f3e5f5;
        }

        .day-label {
            font-size: 16px;
            font-weight: 600;
        }

        .holidays-section {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        }

        .holidays-section h3 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #666;
            margin-bottom: 15px;
        }

        .holiday-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .holiday-count {
            font-size: 32px;
            font-weight: 700;
            color: #4285f4;
        }

        .holiday-icons {
            display: flex;
            gap: 8px;
        }

        .holiday-icon {
            width: 30px;
            height: 30px;
            background: #4285f4;
            border-radius: 50%;
        }

        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .calendar-team-section {
                grid-template-columns: 1fr;
            }
        }

        #myChart {
            height: 400px;
        }

        .chart_wrapper {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        @media (max-width: 1024px) {
            #myChart {
                height: 400px;
            }

            .chart_wrapper {
                position: relative;
                width: 100%;
                max-width: 250px;
            }

            .stat-card h4 {
                font-size: 10px;
            }

            .stat-card {
                padding: 10px;
            }

            .stat-value {

                font-size: 16px;
            }
        }
    </style>
@endpush

@section('content')

    @if (auth('vendor')->check())
        <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#defaultDashboardModal"
            style="font-size:11px;font-weight:600;    position: absolute;
    right: 79px;
    background: white !important;">
            <i class="tio-dashboard-outlined"></i>
        </button>
        @include('layouts.vendor.partials._default_dashboard_modal')
    @endif


    @include('vendor-views.partials._hr_header')

    <div class="dashboard">


        <div class="main-content">
            <div class="left-section">
                <div class="welcome-section">
                    <div class="welcome-text">
                        <h2>HR Management</h2>
                        <p>Your summary for {{ date('d M y') }}</p>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card attendance">
                        <h3>Total Staff</h3>
                        <div class="stat-value">{{ count($staff) }}</div>
                    </div>
                    <div class="stat-card leaves">
                        <h3>Present Today</h3>
                        <div class="stat-value">{{ count($attendances) }}</div>
                    </div>
                    <div class="stat-card leaves2">
                        <h3>Leaves This Month</h3>
                        <div class="stat-value">{{ count($data['approved_leaves']) }} </div>
                        (+ {{ count($data['unapproved_leaves']) }} Unapproved)
                    </div>


                </div>


                <div class="calendar-team-section">
                    <div class="calendar-section">
                        <h3>Staff Performance</h3>
                        <div class="chart_wrapper">
                            <canvas id="myChart"></canvas>
                        </div>

                    </div>

                    <div class="team-section">
                        <div class="d-flex justify-content-between">
                            <h3>Staff Members</h3>
                            @if (hasPermission('staff_manage', 'list'))
                                <a href="{{ route('vendor.employee.list') }}">view all</a>
                            @endif
                        </div>
                        <div class="team-stats">
                            <div class="team-count">{{ count($staff) }}</div>
                            <div style="font-size: 12px; color: #666;">STAFF MEMBERS</div>
                        </div>
                        <div class="team-members">
                            @foreach ($staff as $key => $value)
                                @break($key > 7)
                                <div class="team-member d-flex justify-content-between">
                                    <div class="d-flex justify-content-start">
                                        <div class="member-avatar mr-2">
                                            <a type="button" data-toggle="modal"
                                                data-target="#exampleModal{{ $key }}"> <img class="member-img"
                                                    src="{{ asset('storage/app/public/vendor') . '/' . $value->image }}"
                                                    alt=""></a>
                                            <div class="modal fade" id="exampleModal{{ $key }}" tabindex="-1"
                                                aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel">ID Card</h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            @php $emp = $value @endphp
                                                            @include('vendor-views.form_modals.id_card')
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="member-info">
                                            <h4>{{ $value->f_name . ' ' . $value->l_name }}</h4>
                                            <p>{{ $value->role?->name }}</p>
                                        </div>
                                    </div>
                                    <div>
                                        @if (hasPermission('staff_manage', 'view'))
                                            <a href="{{ route('vendor.employee.view', [$value->id]) }}"
                                                class="btn action-btn btn-outline-primary"><i class="tio-visible"></i></a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-section">
                <div class="updates-section">
                    <div class="updates-header">
                        <h3>Resignations Pending</h3>
                    </div>
                    <div class="d-flex justify-content-between">
                        <h3>{{ count($data['resignations']) }}</h3>
                        @if (hasPermission('staff_manage', 'resignation'))
                            <a type="button" data-toggle="modal" data-target="#resignationModal"
                                class="text-primary text-underline">View All</a>
                        @endif
                    </div>
                </div>
                <div class="updates-section">
                    <div class="updates-header">
                        <h3>Total Base Salary this month</h3>
                    </div>
                    <h3>{{ \App\CentralLogics\Helpers::format_currency($data['total_base_salary']) }}</h3>
                </div>
                <div class="updates-section">
                    <div class="updates-header">
                        <h3>Advance Payment Requests</h3>
                    </div>
                    <h3>{{ $data['advance_requests'] }}</h3>
                    @if (hasPermission('salary_manage', 'advance_requests'))
                        <a href="{{ route('vendor.salary.all-advance-requests') }}" class="text-underline">View All</a>
                    @endif
                </div>
                <div class="updates-section">
                    <div class="updates-header">
                        <h3>Leaves</h3>
                    </div>
                    @foreach ($data['future_unapproved_leaves'] as $key => $value)
                        <div class="appreciation-badge my-1">
                            Unapproved leave of {{ $value->employee?->f_name . ' ' . $value->employee?->l_name }}
                        </div>
                    @endforeach
                </div>

                <div class="week-schedule">
                    <div class="schedule-row fri">
                        <div class="day-label">Staff Information</div>
                        <div>
                            @if (hasPermission('staff_manage', 'list'))
                                <a href="{{ route('vendor.staff.list') }}" class="btn btn-primary">-></a>
                            @endif
                        </div>
                    </div>
                    <div class="schedule-row mon">
                        <div class="day-label">Leaves Manage</div>
                        <div>
                            @if (hasPermission('leave_manage', 'list'))
                                <a href="{{ route('vendor.leave.all') }}" class="btn btn-primary">-></a>
                            @endif
                        </div>
                    </div>
                    <div class="schedule-row wed">
                        <div class="day-label">Tasks Tracker</div>
                        <div>
                            @if (hasPermission('task_manage', 'list'))
                                <a href="{{ route('vendor.task.list') }}" class="btn btn-primary">-></a>
                            @endif
                        </div>

                    </div>

                    <div class="holidays-section">
                        <div class="d-flex justify-content-between">
                            <h3>Upcoming Leaves</h3>
                            <a type="button" class="text-primary text-underline" data-toggle="modal"
                                data-target="#upLeaveModal">View All</a>
                        </div>

                        @foreach ($data['holidays'] as $key => $value)
                            @if ($key < 3)
                                <div class="holiday-info">
                                    <div class="holiday-count">*</div>
                                    <div>
                                        <div style="font-size: 12px; color: #666;">{{ $value->title }}</div>
                                        <div style="font-size: 10px; color: #666;">{{ $value->date }}</div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        <div class="modal fade" id="upLeaveModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                            aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLabel">Leaves This Year</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        @foreach ($data['holidays'] as $key => $value)
                                            <div class="holiday-info">
                                                <div class="holiday-count">*</div>
                                                <div>
                                                    <div style="font-size: 12px; color: #666;">{{ $value->title }}</div>
                                                    <div style="font-size: 10px; color: #666;">{{ $value->date }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if (hasPermission('staff_manage', 'resignation'))

            <div class="modal fade" id="resignationModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Resignations</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-responsive">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Staff Name</th>
                                        <th scope="col">Reason</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Submitted at</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data['resignations'] as $key => $value)
                                        <tr>
                                            <th scope="row">{{ $key + 1 }}</th>
                                            <td>{{ _userInfo($value->employee_id, 'staff')?->f_name . ' ' . _userInfo($value->employee_id, 'staff')?->l_name }}
                                            </td>
                                            <td>{{ $value->reason }}</td>
                                            <td>{{ ucfirst($value->status) }}</td>
                                            <td>{{ $value->created_at }}</td>
                                            <td>
                                                @if ($value->status == 'pending')
                                                    <div class="btn--container">
                                                        <a class="btn  btn--danger btn-outline-danger form-alert"
                                                            href="javascript:" data-id="category-{{ $value->id }}"
                                                            data-message="{{ translate('Want to approve this resignation') }}"
                                                            title="{{ translate('messages.approve') }}">Approve
                                                        </a>
                                                        <form
                                                            action="{{ route('vendor.employee.resignation-action', [$value->id, 'approved']) }}"
                                                            method="get" id="category-{{ $value->id }}">
                                                            @csrf @method('get')
                                                        </form>
                                                        <a class="btn  btn-dark form-alert" href="javascript:"
                                                            data-id="category2-{{ $value->id }}"
                                                            data-message="{{ translate('Want to reject this resignation') }}"
                                                            title="{{ translate('messages.reject') }}">Reject
                                                        </a>
                                                        <form
                                                            action="{{ route('vendor.employee.resignation-action', [$value->id, 'rejected']) }}"
                                                            method="get" id="category2-{{ $value->id }}">
                                                            @csrf @method('get')
                                                        </form>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endsection

    @push('script_2')
        <script>
            const canvas = document.getElementById('myChart');
            const ctx = canvas.getContext('2d');

            // Wait for DOM/canvas to layout first
            const gradient = ctx.createLinearGradient(0, 0, 0, canvas.offsetHeight || 200);
            gradient.addColorStop(0, 'rgba(215, 202, 98, 0.4)'); // green top
            gradient.addColorStop(1, 'rgba(192, 187, 85, 0)'); // transparent bottom

            const gradient2 = ctx.createLinearGradient(0, 0, 0, canvas.offsetHeight || 200);
            gradient2.addColorStop(0, 'rgba(222, 94, 94, 0.4)'); // green top
            gradient2.addColorStop(1, 'rgba(175, 76, 76, 0)'); // transparent bottom

            const gradient3 = ctx.createLinearGradient(0, 0, 0, canvas.offsetHeight || 200);
            gradient3.addColorStop(0, 'rgba(105, 94, 222, 0.4)'); // green top
            gradient3.addColorStop(1, 'rgba(76, 78, 175, 0)'); // transparent bottom

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chart_data['months']),
                    datasets: [{
                        label: 'Leads Completed',
                        data: @json($chart_data['leads']),
                        fill: true,
                        backgroundColor: gradient,
                        borderColor: 'rgba(218, 200, 98, 1)',
                        borderWidth: 2,
                        tension: 0.4
                    }, {
                        label: 'Tasks Completed',
                        data: @json($chart_data['tasks']),
                        fill: true,
                        backgroundColor: gradient2,
                        borderColor: 'rgba(202, 82, 82, 1)',
                        borderWidth: 2,
                        tension: 0.4
                    }, {
                        label: 'Projects Completed',
                        data: @json($chart_data['projects']),
                        fill: true,
                        backgroundColor: gradient3,
                        borderColor: 'rgba(82, 82, 202, 1)',
                        borderWidth: 2,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Performance This Year'
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            color: '#000',
                            font: {
                                weight: 'bold'
                            },
                            formatter: value => value > 0 ? value : ''
                        }

                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        </script>
    @endpush
