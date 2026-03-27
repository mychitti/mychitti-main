@extends('layouts.admin.app')
@section('title', translate('messages.Employee Profile'))

@push('css_or_js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 24px;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .overlay.show {
            display: block;
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #6b7280;
        }

        .close-btn:hover {
            color: #374151;
        }

        .customer-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 1000;
            padding: 15px;
        }

        .customer-panel.open {
            right: 0;
        }

        .customer-info-btn {
            background: white;
            border: 2px solid #e5e7eb;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            color: #374151;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .customer-info-btn:hover {
            border-color: #60a5fa;
            background: #f8fafc;
        }

        .items_p_0 th,
        .items_p_0 td {
            padding: 4px;
        }

        .personal-info-label {
            margin-bottom: 0px;
        }

        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 768px) {
            .card {
                padding: 16px;
                border-radius: 12px;
            }
        }

        /* Profile Header */
        .profile-header {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }



        .profile-image {
            position: relative;
            flex-shrink: 0;
        }

        .profile-image img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ff6b6b;
        }

        .profile-badge {
            position: absolute;
            bottom: -8px;
            right: -8px;
            width: 36px;
            height: 36px;
            background: linear-gradient(45deg, #ff6b6b, #ffa500);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            border: 3px solid white;
        }

        .profile-info h1 {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        @media (max-width: 768px) {
            .profile-info h1 {
                font-size: 24px;
                flex-direction: column;
                gap: 8px;
            }
        }

        .status-badge {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .error-badge {
            background: linear-gradient(45deg, #cd4e4e, #a04444);

        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }

        .info-item {
            display: flex;
            gap: 4px;
        }

        .info-label {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 500;
        }

        .info-value {
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }

        .social-links {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }

        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: transform 0.2s;
        }

        .social-link:hover {
            transform: translateY(-2px);
        }

        .social-link.linkedin {
            background: linear-gradient(45deg, #0077b5, #0099cc);
        }

        .social-link.facebook {
            background: linear-gradient(45deg, #1877f2, #42a5f5);
        }

        .social-link.email {
            background: linear-gradient(45deg, #ff6b6b, #ffa500);
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            color: #2c3e50;
            font-weight: 700;
        }

        .section-subtitle {
            color: #95a5a6;
            font-size: 14px;
            font-style: italic;
        }

        /* Basic Information */
        .basic-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        @media (max-width: 768px) {
            .basic-info-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .basic-info-grid {
                grid-template-columns: 1fr;
            }
        }

        .basic-info-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .basic-info-label {
            color: #3498db;
            font-size: 14px;
            font-weight: 600;
        }

        .basic-info-value {
            background: linear-gradient(45deg, #2c3e50, #34495e);
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        /* Personal Information */
        .personal-info-grid {
            display: grid;
            grid-template-columns: 1fr 400px;

            gap: 24px;
        }

        @media (max-width: 768px) {
            .personal-info-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }

        .personal-info-item {
            display: flex;
            flex-direction: column;
        }

        .personal-info-label {
            color: #3498db;
            font-size: 14px;
            font-weight: 600;
        }

        .personal-info-value {
            color: #2c3e50;
            font-size: 16px;
            font-weight: 500;
        }

        .address-container {
            display: flex;
            flex-wrap: wrap;
        }

        .view-link {
            text-decoration: none;
            font-size: 14px;
            color: black;
            font-weight: 500;
        }

        .view-link:hover {
            text-decoration: underline;
        }

        /* Occupation Information */
        .occupation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 24px;
        }

        @media (max-width: 768px) {
            .occupation-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }

        .occupation-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 7px;
            background: linear-gradient(45deg, #f8f9fa, #ffffff);
            border-radius: 12px;
            border: 2px solid #e9ecef;
            transition: transform 0.2s;
        }

        .occupation-item:hover {
            transform: translateY(-2px);
        }

        .occupation-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            flex-shrink: 0;
        }

        .occupation-icon.fulltime {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
        }

        .occupation-icon.engineering {
            background: linear-gradient(45deg, #667eea, #764ba2);
        }

        .occupation-icon.location {
            background: linear-gradient(45deg, #ffeaa7, #fdcb6e);
        }

        .occupation-text {
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }

        /* Right Column */
        .right-column {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Calendar */
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .calendar-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
        }

        .calendar-nav {
            background: #f8f9fa;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .calendar-nav:hover {
            background: #e9ecef;
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            margin-bottom: 8px;
        }

        .calendar-weekday {
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            font-weight: 600;
            padding: 8px 4px;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .calendar-day.today {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            font-weight: 600;
        }

        .calendar-day.selected {
            background: linear-gradient(45deg, #95a5a6, #7f8c8d);
            color: white;
        }

        .calendar-day.other-month {
            color: #bdc3c7;
        }

        .calendar-day:hover:not(.today):not(.selected) {
            background: #f8f9fa;
        }

        /* Upcoming Events */
        .events-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
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
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
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

        .event-card.design-review-2 {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
        }

        .event-card.design-review-3 {
            color: black;
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
        }

        .event-card.design-review-4 {
            color: black;
            background: linear-gradient(135deg, #d1fde8 0%, #d8ffccdf 100%);
        }

        .event-card.design-review-5 {
            color: black;
            background: linear-gradient(135deg, #def5ff 0%, #f0f9ffdf 100%);
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .event-title {
            font-size: 16px;
            font-weight: 600;
        }

        .event-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        .event-icon.teal {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
        }

        .event-icon.pink {
            background: linear-gradient(45deg, #ff6b6b, #ffa500);
        }

        .event-time {
            font-size: 14px;
            opacity: 0.9;
        }

        .event-participants {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .participant-avatar {
            padding: 2px 8px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.3);
        }

        /* Onboarding */
        .onboarding-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .completion-status {
            color: #7f8c8d;
            font-size: 14px;
        }

        .onboarding-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .onboarding-table th {
            text-align: left;
            font-size: 12px;
            color: #7f8c8d;
            font-weight: 600;
            padding: 8px 4px;
            border-bottom: 2px solid #f8f9fa;
        }

        .onboarding-table td {
            padding: 12px 4px;
            border-bottom: 1px solid #f8f9fa;
            vertical-align: middle;
        }

        .task-checkbox {
            width: 16px;
            height: 16px;
            border: 2px solid #bdc3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .task-checkbox.completed {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            border-color: #2ecc71;
            color: white;
        }

        .task-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .task-text {
            font-size: 14px;
            color: #2c3e50;
        }

        .assignee-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .assignee-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: linear-gradient(45deg, #95a5a6, #7f8c8d);
        }

        .assignee-name {
            font-size: 14px;
            color: #2c3e50;
        }

        .due-date {
            font-size: 14px;
            color: #7f8c8d;
        }

        .attachment-link {
            color: #3498db;
            text-decoration: none;
            font-size: 12px;
        }

        .attachment-link:hover {
            text-decoration: underline;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            background: none;
            border: none;
            color: #bdc3c7;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: color 0.2s;
        }

        .action-btn:hover {
            color: #7f8c8d;
        }

        .add-task-btn {
            width: 100%;
            background: linear-gradient(45deg, #ffa500, #ff6b6b);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .add-task-btn:hover {
            transform: translateY(-1px);
        }

        .profile-header {
            width: 50%;
            display: flex;
            flex-direction: row;
        }

        @media (max-width: 768px) {
            .onboarding-table {
                font-size: 12px;
            }

            .onboarding-table th,
            .onboarding-table td {
                padding: 8px 2px;
            }

            .task-text,
            .assignee-name,
            .due-date {
                font-size: 12px;
            }
        }

        .content-card {
            width: 100%;

            margin: 4px auto;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 16px;
                width: 100%;
            }

            .main_row {
                flex-wrap: wrap;
            }

            .info-item {
                display: block;
                text-align: start;
            }

            .content-card {
                width: 99%;
            }
        }
    </style>
@endpush

@section('content')


    <div class="container">
        <!-- Left Column -->
        <div class="left-column">
            <!-- Profile Header -->
            <div class="  content-card flex-row d-flex main_row" style="gap:9px;">
                <div class="card profile-header">
                    <div class="profile-image">
                        <img src="{{ asset('storage/app/public/vendor') . '/' . $emp->image }}"
                            alt="{{ $emp->f_name . ' ' . $emp->l_name }}">
                        <div class="profile-badge">{{ substr($emp->f_name, 0, 1) }}</div>
                    </div>
                    <div class="profile-info">
                        <h2>
                            @if ($emp['terminate'])
                                <span class="status-badge error-badge">Terminated</span>
                            @else
                                {{ $emp->f_name . ' ' . $emp->l_name }}
                                @if ($emp->status)
                                    <span class="status-badge">Active</span>
                                @else
                                    <span class="status-badge error-badge">Inactive</span>
                                @endif

                            @endif
                        </h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Role:</span>
                                <span class="info-value">{{ $emp->role?->name }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Position:</span>
                                <span class="info-value">{{ $emp->designation }}</span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Phone:</span>
                                <span class="info-value">{{ $emp->phone }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Department:</span>
                                <span class="info-value">{{ $emp->department?->title }}</span>
                            </div>
                            <div class="info-item flex-column">
                                <span class="info-label">E-mail:</span>
                                <span class="info-value">{{ $emp->email }}</span>
                            </div>
                            <div class="info-item">
                                @if ($emp['terminate'])
                                    <h4 class="text-danger">Terminated</h4>
                                @elseif ($emp['resignation'])
                                    <h4 class="text-warning">Resigned</h4>
                                @else

                                    @if (auth('admin')->user()->role_id == 1 && hasPermission('staff_manage', 'terminate'))
                                        <a class="btn btn--danger btn-outline-danger form-alert" href="javascript:"
                                            data-id="category-{{ $emp['id'] }}"
                                            data-message="{{ translate('Want to terminate this staff') }}"
                                            title="{{ translate('messages.terminate') }}">Terminate
                                        </a>
                                        <form action="{{ route('admin.employee.terminate', [$emp['id']]) }}" method="get"
                                            id="category-{{ $emp['id'] }}">
                                            @csrf @method('get')
                                        </form>
                                    @endif
                                    @if (auth('admin')->id() == $emp['id'])
                                        @if (hasPermission('staff_manage', 'resignation'))
                                            <a class="btn btn-warning btn-outline-warning ml-2" href="javascript:"
                                                data-toggle="modal" data-target="#resignModal">Resign
                                            </a>
                                        @endif
                                    @endif
                                @endif
                            </div>
                        </div>
                        {{-- <div class="social-links">
                            <a href="#" class="social-link linkedin">in</a>
                            <a href="#" class="social-link facebook">f</a>
                            <a href="#" class="social-link email">@</a>
                        </div> --}}
                    </div>
                </div>
                <div class=" card profile-header">
                    <canvas id="myChart" height="300"></canvas>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="card content-card">
                <div class="section-header">
                    <h2 class="section-title">Basic Information</h2>
                </div>
                <div class="basic-info-grid">
                    <div class="basic-info-item">
                        <label class="basic-info-label">Employee Id</label>
                        <div class="basic-info-value">
                            {{ $emp->employee_id }}
                        </div>
                    </div>
                    <div class="basic-info-item">
                        <label class="basic-info-label">Base Salary</label>
                        <div class="basic-info-value">
                            {{ ($emp->base_salary ? _price($emp->base_salary) : '') . ' ' . $emp->salary_type }}
                            <br>
                            @if (hasPermission('salary_manage', 'view'))
                                <a href="{{ route('admin.salary.edit', [$emp->id]) }}"
                                    class="text-white text-underline">View Details</a>
                            @endif
                        </div>
                    </div>
                    <div class="basic-info-item">
                        <label class="basic-info-label">Hire Date</label>
                        <div class="basic-info-value">{{ $emp->created_at->format('d F Y') }}</div>
                    </div>
                    <div class="basic-info-item">
                        <label class="basic-info-label">Worked for</label>
                        @php
                            use Carbon\Carbon;

                            $start = $emp->tentative_joining_date
                                ? Carbon::parse($emp->tentative_joining_date)
                                : Carbon::parse($emp->created_at);
                            $end = Carbon::now();

                            $years = $start->diff($end)->y;
                            $months = $start->diff($end)->m;

                            $diffString = '';
                            if ($years > 0) {
                                $diffString .= $years . ' year' . ($years > 1 ? 's' : '');
                            }
                            if ($months > 0) {
                                if ($diffString) {
                                    $diffString .= ', ';
                                }
                                $diffString .= $months . ' month' . ($months > 1 ? 's' : '');
                            }
                        @endphp

                        <div class="basic-info-value">
                            {{ $diffString ?: 'Less than a month' }}
                        </div>

                    </div>
                    <div class="basic-info-item">
                        <label class="basic-info-label">Shift Timing</label>
                        @if ($emp->store_shift_id)
                            <div class="basic-info-value">{!! $emp->storeShift?->name .
                                ' <br> (' .
                                $emp->storeShift?->start_time .
                                ' to ' .
                                $emp->storeShift?->end_time .
                                ')' !!}</div>
                        @else
                            <div class="basic-info-value">Shift not Assigned</div>
                        @endif
                    </div>
                    <div class="basic-info-item">
                        <label class="basic-info-label">Branch</label>
                        @if ($emp->branch)
                            <div class="basic-info-value">{{ $emp->branch?->name }}</div>
                        @else
                            <div class="basic-info-value">Branch not Assigned</div>
                        @endif
                    </div>

                </div>
            </div>

            <!-- Personal Information -->
            <div class="card content-card">
                <div class="section-header">
                    <h2 class="section-title">Personal Information</h2>
                </div>
                <div class="personal-info-grid">
                    <div class="personal-info-item">
                        <label class="personal-info-label">Date of Birth</label>
                        <div class="personal-info-value">{{ $emp->dob }}</div>

                        @if ($emp->documentation && is_array(json_decode($emp->documentation)))
                            @foreach (json_decode($emp->documentation) as $key => $value)
                                <label class="personal-info-label">{{ $value->doc_name }} Number</label>
                                <div class="personal-info-value">{{ $value->doc_number }}</div>
                            @endforeach
                        @endif

                        @if ($emp->id_number)
                            <label class="personal-info-label">ID Number</label>
                            <div class="personal-info-value">{{ $emp->id_number }}</div>
                        @endif
                    </div>
                    @php $emergency_contact = json_decode($emp->emergency_contact_details)  ; @endphp
                    <div class="personal-info-item">
                        <label class="personal-info-label">Emergency Contact</label>
                        @if ($emergency_contact)
                            <div class="address-container d-flex flex-column">
                                <span class="personal-info-value"><b>{{ $emergency_contact->name ?? '' }}</b></span>
                                <span class="personal-info-value">{{ $permanent_address->relationship ?? '' }}</span>
                                @if ($emergency_contact->phone)
                                    <span class="fs-6 "><i class="tio-call"></i><span
                                            class="textToCopy">{{ $emergency_contact->phone }}</span><button
                                            class="copy-btn bg-transparent outline-none border-0">
                                            <i class="tio-copy"></i>
                                        </button>
                                    </span>
                                @endif
                                <span class="fs-6">{!! $emergency_contact->alt_phone ? '<i class="tio-call"></i>' . $emergency_contact->alt_phone : '' !!}</span>
                                <span class="fs-6">{!! $emergency_contact->address ? '<i class="tio-map"></i>' . $emergency_contact->address : '' !!}</span>
                                <span class="fs-6">{!! $emergency_contact->language ? '<i class="tio-speaker"></i>' . $emergency_contact->language : '' !!}</span>

                            </div>
                        @else
                            Not Provided
                        @endif
                    </div>
                    @php $residential_address = json_decode($emp->residential_address)  ; @endphp
                    @if ($residential_address)
                        <div class="personal-info-item">
                            <label class="personal-info-label">Residential Address</label>
                            <div class="address-container d-flex flex-column">
                                <span class="personal-info-value">{{ $residential_address->ra_address1 ?? '' }}
                                    {{ $residential_address->ra_address2 ?? '' }}</span>
                                <span class="personal-info-value">{{ $residential_address->ra_city ?? '' }}
                                    {{ isset($residential_address->ra_state) ? _stateName($residential_address->ra_state) : '' }}
                                    - {{ $residential_address->ra_pincode ?? '' }} </span>
                                <span class="fs-6"><i
                                        class="tio-call"></i>{{ $residential_address->ra_phone ?? '' }}</span>
                                <a href="mailto:{{ $residential_address->ra_email ?? '' }}"
                                    class="view-link">{{ $residential_address->ra_email ?? '' }}</a>

                            </div>
                        </div>
                    @endif
                    @php $permanent_address = json_decode($emp->permanent_address)  ; @endphp
                    @if ($permanent_address)
                        <div class="personal-info-item">
                            <label class="personal-info-label">Permanent Address</label>
                            <div class="address-container d-flex flex-column">
                                <span class="personal-info-value">{{ $permanent_address->pa_address1 ?? '' }}
                                    {{ $permanent_address->pa_address2 ?? '' }}</span>
                                <span class="personal-info-value">{{ $permanent_address->pa_city ?? '' }}
                                    {{ isset($permanent_address->pa_state) ? _stateName($permanent_address->pa_state) : '' }}
                                    - {{ $permanent_address->pa_pincode ?? '' }}</span>
                                <span class="fs-6"><i
                                        class="tio-call"></i>{{ $permanent_address->pa_phone ?? '' }}</span>
                                <a href="mailto:{{ $permanent_address->pa_email ?? '' }}"
                                    class="view-link">{{ $permanent_address->pa_email ?? '' }}</a>

                            </div>
                        </div>
                    @endif

                </div>
            </div>
            <div class="card content-card">
                <div class="section-header">
                    <h2 class="section-title">Educational Information</h2>
                    <span class="section-subtitle"></span>
                </div>
                <div class="occupation-grid">
                    <table class="table items_p_0">
                        <tbody>
                            @php $education = json_decode($emp->education); @endphp
                            @if ($education)
                                @foreach ($education as $key => $value)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <th><b>{{ $value->degree_diploma }}</b></th>
                                        <td>{{ $value->degree_diploma }}</td>
                                        <td>{{ $value->field_of_study }}</td>
                                        <td>{{ $value->start_month }} to {{ $value->end_month }}</td>
                                        <td>{{ $value->additional_notes }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card content-card">
                <div class="section-header">
                    <h2 class="section-title">Experience</h2>
                    <span class="section-subtitle"></span>
                </div>
                <div class="occupation-grid">
                    <table class="table items_p_0">
                        <tbody>
                            @php $experience = json_decode($emp->experience); @endphp
                            @if ($experience)
                                @foreach ($experience as $key => $value)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <th><b>{{ $value->occupation }}</b></th>
                                        <td>{{ $value->company_name }}</td>
                                        <td>{{ $value->summary }}</td>
                                        <td>{{ $value->exp_start_date }} to
                                            {{ $value->exp_end_date ? $value->exp_end_date : 'Present' }}</td>
                                        <td>
                                            @if ($value->exp_letter)
                                                <a
                                                    href="{{ asset('storage/app/public/vendor/documents/') . '/' . $value->exp_letter }}">Veiw
                                                    Experience Letter</a>
                                            @endif
                                        </td>

                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>



            <!-- Occupation Information -->
            <div class="card content-card">
                <div class="section-header">
                    <h2 class="section-title">Other Information</h2>
                    <span class="section-subtitle"></span>
                </div>
                <div class="occupation-grid">
                    <div class="occupation-item">
                        <div class="occupation-text"><b>Total Experience</b><br>
                            {{ $emp->experience_yrs }}

                        </div>
                    </div>
                    @if ($emp->source)
                        <div class="occupation-item">
                            <div class="occupation-text"><b>Source of Hire</b><br>
                                {{ $emp->source }}

                            </div>
                        </div>
                    @endif
                    @if ($emp->main_department)
                        <div class="occupation-item">
                            <div class="occupation-text"><b>Department</b><br>
                                {{ $emp->main_department }}

                            </div>
                        </div>
                    @endif
                    @if ($emp->additional_information)
                        <div class="occupation-item">
                            <div class="occupation-text"><b>Additional Information</b><br>
                                {{ $emp->additional_information }}

                            </div>
                        </div>
                    @endif
                    <div class="occupation-item">
                        <div class="occupation-text"><b>Skills</b><br>
                            @if ($emp->skills)
                                @foreach (explode(',', $emp->skills) as $key => $value)
                                    <span class="badge badge-soft-success">{{ $value }}</span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <div class="occupation-item">
                        <div class="occupation-text"><b> Qualification </b> <br>
                            <p>{{ $emp->qualification }}</p>
                        </div>

                    </div>
                    <div class="occupation-item">
                        <div class="occupation-text"><b>Documents</b><br>
                            <div>
                                <i class="tio-file"></i> <a href="{{ route('admin.employee.view-id-card', [$emp->id]) }}"
                                    target="_blank">ID
                                    Card</a>
                            </div>
                            <div>
                                <i class="tio-file"></i> <a target="_blank"
                                    href="{{ $emp->offer_letter ? asset('storage/app/public/vendor/documents') . '/' . $emp->offer_letter : '#' }}">Offer
                                    Letter</a><br>
                            </div>
                            <div>
                                @if ($emp->id_document)
                                    <i class="tio-file"></i> <a target="_blank"
                                        href="{{ $emp->id_document ? asset('storage/app/public/vendor/documents') . '/' . $emp->id_document : '#' }}">ID
                                        Document</a><br>
                                @endif
                                @if ($emp->documentation && is_array(json_decode($emp->documentation)))
                                    @foreach (json_decode($emp->documentation) as $key => $value)
                                        <i class="tio-file"></i> <a target="_blank"
                                            href="{{ $value->doc_file ? asset('storage/app/public/vendor/documents') . '/' . $value->doc_file : '#' }}">{{ ucfirst($value->doc_name) }}</a><br>
                                    @endforeach
                                @endif

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>



        <!-- Right Column -->
        <div class="right-column">
            @if (hasPermission('staff_manage', 'comment'))
                <button class="btn btn-outline-primary" type="button" data-toggle="modal"
                    data-target="#staffCommentModal" data-whatever="{{ $emp->id }}"><i class="tio-edit"></i> Add
                    Comment</button>
                <button class="customer-info-btn" id="customerInfoBtn">
                    <i class="fas fa-user"></i>
                    Comments
                </button>
            @endif
            <!-- Calendar -->
            @if ($data['advance_payment_deductions'] && $data['advance_payment_deductions'] > 0)
                <div class="card content-card">
                    <div class="events-header">
                        <h5 class="section-title">Advance Payment</h5>
                    </div>
                    <span class="badge badge-soft-danger  "
                        style="font-size: 16px;">{{ _price($data['advance_payment_deductions']) }}</span>
                </div>
            @endif
            <div class="card content-card">
                <div class="events-header">
                    <h5 class="section-title">Alotted Company Assets (Properties) </h5>
                </div>
                @foreach ($data['alotted_assets'] as $key => $value)
                    <div class="d-flex event-card design-review-5">
                        <div>
                            <img class="avatar avatar-lg mr-3 onerror-image"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($value->inventoryItem?->image, asset('storage/app/public/inventory-item/') . '/' . $value->inventoryItem?->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'inventory-item/') }}"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                alt="{{ $value->inventoryItem?->name }} image">
                        </div>
                        <div>
                            <div class="event-header">
                                <div class="event-title">{{ $value->inventoryItem?->item_name }}</div>
                            </div>
                            <div class="event-time">
                                {{ $value->inventoryItem?->brand . ' | ' . $value->inventoryItem?->model_number }}
                            </div>
                            <div class="event-time">
                                Issue Date: {{ _formatted_date($value->created_at) }}
                            </div>
                            <div class="event-participants">
                                <div class="participant-avatar">Alotted Qty : {{ $value->alotted_qty }}</div>
                                @if ($value->returned)
                                    <div class="participant-avatar text-success">Returned</div>
                                @else
                                    <div class="participant-avatar text-danger">Alotted</div>
                                @endif

                            </div>
                        </div>

                    </div>
                @endforeach

            </div>
            <!-- Timecards -->
            <div class="card content-card">
                <div class="events-header">
                    <h5 class="section-title">Timecards</h5>
                    <a href="{{ route('admin.employee.timecards', [$emp->id]) }}" class="text-underline">View All
                        Timecards</a>
                </div>
                @php
                    $recentTimecards = \App\Models\EmployeeTimeCard::where('emp_id', $emp->id)
                        ->where('vendor_id', 0)
                        ->whereNotNull('in_time')
                        ->whereNotNull('out_time')
                        ->orderBy('id', 'desc')
                        ->take(5)
                        ->get();
                @endphp
                @forelse($recentTimecards as $tc)
                    <div class="event-card design-review-5">
                        <div class="event-header">
                            <div class="event-title">{{ $tc->date }}</div>
                        </div>
                        <div class="event-time">
                            In: {{ explode(' ', $tc->in_time)[1] }} | Out: {{ explode(' ', $tc->out_time)[1] }}
                            @php
                                $start = new \DateTime($tc->in_time);
                                $end = new \DateTime($tc->out_time);
                                $interval = $start->diff($end);
                                $hours = $interval->days * 24 + $interval->h;
                                $mins = $interval->i;
                            @endphp
                            | Duration: {{ $hours }}h {{ $mins }}m
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No timecards found</p>
                @endforelse
            </div>
            <!-- Upcoming Events -->
            <div class="card content-card">
                <div class="events-header">
                    <h5 class="section-title">Recent Leads</h5>
                    @if (hasPermission('leads_manage', 'list'))
                        <a href="#" class="text-underline">View
                            All Leads</a>
                    @endif
                </div>
                @foreach ($leads as $key => $lead)
                    <div class="event-card design-review-4">
                        <div class="event-header">
                            <div class="event-title">{{ ucfirst($lead->name) }}</div>
                            <div class="event-icon teal">💼</div>
                        </div>
                        <div class="event-time">{{ \Carbon\Carbon::parse($lead->assigned_at)->format('d F Y') }} </div>
                        <div class="event-participants">
                            <div class="participant-avatar">{{ ucfirst($lead->current_status) }}</div>
                            <a target="_blank"
                                href="{{ route('admin.service.lead-details', [$lead->service_request_id]) }}">View ></a>
                        </div>
                    </div>
                @endforeach

            </div>

            <div class="card content-card">
                <div class="events-header">
                    <h5 class="section-title">Recent Projects</h5>
                    @if (hasPermission('project', 'list'))
                        <a href="{{ route('admin.project.all', ['id' => $emp->id]) }}" class="text-underline">View All
                            Projects</a>
                    @endif
                </div>
                @foreach ($projects as $key => $proj)
                    <div class="event-card design-review{{ $key % 2 == 0 ? '-2' : '' }}">
                        <div class="event-header">
                            <div class="event-title">{{ $proj->project_title }}</div>
                            <div class="event-icon teal">💼</div>
                        </div>
                        <div class="event-time">{{ \Carbon\Carbon::parse($proj->start_date)->format('d F Y') }} -
                            {{ \Carbon\Carbon::parse($proj->end_date)->format('d F Y') }}</div>
                        <div class="event-participants">
                            <div class="participant-avatar">{{ ucfirst($proj->progress_status) }}</div>
                            <div class="participant-avatar">{{ $proj->teams ? count(json_decode($proj->teams)) : 0 }}
                                teams </div>
                            <a target="_blank" href="{{ route('admin.project.details', [$proj->id]) }}"
                                class="text-white">View ></a>
                        </div>
                    </div>
                @endforeach

            </div>
            <div class="card content-card">
                <div class="events-header">
                    <h5 class="section-title">Recent Tasks</h5>
                    @if (hasPermission('task', 'list'))
                        <a href="{{ route('admin.task.list', ['id' => $emp->id]) }}" class="text-underline">View All
                            Tasks</a>
                    @endif
                </div>
                @foreach ($tasks as $key => $task)
                    <div class="event-card design-review-3">
                        <div class="event-header">
                            <div class="event-title">{{ $task->title }}</div>
                            <div class="event-icon teal">📋</div>
                        </div>
                        <div class="event-time">Total Duration : {{ $task->time_count . ' ' . $task->time_unit . '(s)' }}
                        </div>
                        <div class="event-participants">
                            <div class="participant-avatar">{{ ucfirst($task->status) }}</div>
                            <div class="participant-avatar">{{ $task->progress }}% completed</div>
                            <a target="_blank"
                                href="{{ $task->parent_id ? route('admin.task.subtask.detail', [$task->id]) : route('admin.task.detail', [$task->id]) }}">View
                                ></a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @if (hasPermission('staff_manage', 'comment'))
        <div class="modal fade" id="staffCommentModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add Comment</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.employee.comment-save') }}" method="post">
                            @csrf
                            <input type="hidden" name="staff_id" id="staff_id">
                            <div class="form-group">
                                <label for="exampleInputEmail1">Comment</label>
                                <textarea placeholder="Start typing..." required name="comment" class="form-control" id=""></textarea>
                            </div>
                            <div class="d-flex w-100 justify-content-end">
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
        <div class="overlay" id="overlay"></div>
        <div class="customer-panel" id="customerPanel">
            <button class="close-btn" id="closeBtn">×</button>
            <h3>Comments</h3>
            <div class="customer-info">
                @foreach ($emp->comments as $key => $value)
                    <div class="card m-1 p-2">
                        {{ $value->comment }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Resign Modal -->
    @if (hasPermission('staff_manage', 'resignation'))
        <div class="modal fade" id="resignModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Resignation</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.employee.resign', [$emp['id']]) }}" method="post">
                            @csrf
                            <label>Reason</label>
                            <textarea name="reason" class="form-control" required></textarea>
                            <div class="d-flex justify-content-end w-100 mt-2">
                                <button class="btn btn-danger">Submit Resignation</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection


@push('script_2')
    <script>
        // Add interactive functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Calendar day click handler
            const calendarDays = document.querySelectorAll('.calendar-day');
            calendarDays.forEach(day => {
                day.addEventListener('click', function() {
                    // Remove previous selected
                    document.querySelectorAll('.calendar-day.selected').forEach(d => d.classList
                        .remove('selected'));
                    // Add selected to clicked day (unless it's today)
                    if (!this.classList.contains('today') && !this.classList.contains(
                            'other-month')) {
                        this.classList.add('selected');
                    }
                });
            });

            // Task checkbox toggle
            const taskCheckboxes = document.querySelectorAll('.task-checkbox:not(.completed)');
            taskCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('click', function() {
                    this.classList.toggle('completed');
                    if (this.classList.contains('completed')) {
                        this.innerHTML = '✓';
                        // Update completion status
                        const completedTasks = document.querySelectorAll('.task-checkbox.completed')
                            .length;
                        const totalTasks = document.querySelectorAll('.task-checkbox').length;
                        document.querySelector('.completion-status').textContent =
                            `${completedTasks}/${totalTasks} completed`;
                    } else {
                        this.innerHTML = '';
                        // Update completion status
                        const completedTasks = document.querySelectorAll('.task-checkbox.completed')
                            .length;
                        const totalTasks = document.querySelectorAll('.task-checkbox').length;
                        document.querySelector('.completion-status').textContent =
                            `${completedTasks}/${totalTasks} completed`;
                    }
                });
            });

            // Add hover effects to buttons
            const buttons = document.querySelectorAll('button, .social-link, .view-link');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Add click handlers for navigation
            document.querySelector('.add-task-btn').addEventListener('click', function() {
                alert('Add New Task functionality would be implemented here');
            });

            document.querySelector('.view-all-btn').addEventListener('click', function() {
                alert('View All Events functionality would be implemented here');
            });
        });
    </script>
    <script>
        const canvas = document.getElementById('myChart');
        const ctx = canvas.getContext('2d');

        // Wait for DOM/canvas to layout first
        const gradient = ctx.createLinearGradient(0, 0, 0, canvas.offsetHeight || 200);
        gradient.addColorStop(0, 'rgba(175, 163, 76, 0.4)'); // green top
        gradient.addColorStop(1, 'rgba(175, 170, 76, 0)'); // transparent bottom

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
                    borderColor: 'rgba(175, 160, 76, 1)',
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
                {{-- responsive: true, --}}
                plugins: {
                    title: {
                        display: true,
                        text: 'Performance This Year'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        $('#staffCommentModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var id = button.data('whatever')
            var modal = $(this)
            modal.find('.modal-body #staff_id').val(id)
        })
        $('#customerInfoBtn').click(function() {
            $('#customerPanel').addClass('open');
            $('#overlay').addClass('show');
        });
        $('#closeBtn, #overlay').click(function() {
            $('#customerPanel').removeClass('open');
            $('#overlay').removeClass('show');
        });
    </script>
@endpush
