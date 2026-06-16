@extends('layouts.vendor.app')

@section('title', 'Attendance')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .al-hero {
            background: linear-gradient(100deg, #1d4ed8 0%, #2563eb 45%, #4f46e5 100%);
            border-radius: 14px; color: #fff; padding: 22px 26px;
            box-shadow: 0 8px 24px rgba(37, 99, 235, .22); margin-bottom: 18px;
        }
        .al-hero h1 { color: #fff; font-weight: 700; font-size: 22px; margin: 0; }
        .al-hero .al-sub { color: rgba(255, 255, 255, .82); font-size: 13px; margin-top: 3px; }
        .al-chip {
            background: rgba(255, 255, 255, .15); border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 10px; padding: 8px 14px; text-align: center; min-width: 88px;
        }
        .al-chip .n { font-size: 20px; font-weight: 700; line-height: 1; }
        .al-chip .l { font-size: 11px; opacity: .85; text-transform: uppercase; letter-spacing: .4px; }
        .al-card { border: 1px solid #eef0f4; border-top: none; border-radius: 0 0 12px 12px; }
        .al-empty { text-align: center; padding: 44px 16px; color: #9aa1ab; }
        .al-empty img { width: 92px; opacity: .8; margin-bottom: 10px; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        @include('vendor-views.partials._hr_header')

        <div class="card al-card mb-0">
            <div class="card-body p-0">
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                    <h5 class="card-title mb-0">Staff List <span class="badge badge-soft-dark ml-1">{{ $staff->count() }}</span></h5>
                    <div class="input-group input-group-sm" style="max-width:260px;">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><i class="tio-search"></i></span>
                        </div>
                        <input type="text" class="form-control al-search" data-target="#staffTable" placeholder="Search staff...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table mb-0" id="staffTable">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('sl') }}</th>
                                <th>Info</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th class="text-uppercase text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($staff as $lead)
                                @php
                                    $depNm = _getWhere('departments', ['id' => $lead->department_id]);
                                    $roleNm = _getWhere('employee_roles', ['id' => $lead->employee_role_id]);
                                    $pending = _pendingLeavesCount($lead->id);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('vendor.employee.view', [$lead->id]) }}" class="table-rest-info">
                                            <div class="info">
                                                <div class="text--title">{{ $lead->f_name . ' ' . $lead->l_name }}</div>
                                                <div class="font-light">{{ $lead->phone }}</div>
                                                <div class="font-light">{{ $lead->email }}</div>
                                            </div>
                                        </a>
                                    </td>
                                    <td><span class="font-size-sm text-body">{{ $depNm[0]->title ?? '—' }}</span></td>
                                    <td><span class="font-size-sm text-body">{{ $roleNm[0]->name ?? '—' }}</span></td>
                                    <td class="text-right">
                                        <div class="d-inline-flex" style="gap:6px;">
                                            @if (hasPermission('attendance_manage', 'view'))
                                                <a class="btn btn-sm btn--primary" href="{{ route('vendor.attendance.manage', [$lead->id]) }}">
                                                    <i class="tio-event mr-1"></i> Manage Attendance
                                                </a>
                                            @endif
                                            @if (hasAnyModulePermission(['leave_manage', 'attendance_manage']))
                                                <a class="btn btn-sm btn-outline-primary position-relative" href="{{ route('vendor.leave.manage', [$lead->id]) }}">
                                                    <i class="tio-calendar-note mr-1"></i> Leave
                                                    @if ($pending)
                                                        <span class="badge badge-danger" style="position:absolute; top:-7px; right:-7px; border-radius:50%; padding:3px 6px;">{{ $pending }}</span>
                                                    @endif
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5">
                                    <div class="al-empty">
                                        <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="">
                                        <h5>{{ translate('no_data_found') }}</h5>
                                    </div>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        document.querySelectorAll('.al-search').forEach(box => {
            box.addEventListener('keyup', function () {
                const q = this.value.toLowerCase();
                document.querySelectorAll(this.dataset.target + ' tbody tr').forEach(row => {
                    if (row.querySelector('.al-empty')) return;
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        });
    </script>
@endpush
