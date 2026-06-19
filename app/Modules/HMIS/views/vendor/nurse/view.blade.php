@extends('layouts.vendor.app')
@section('title', 'Nurse Profile')

@section('content')
<div class="content container-fluid">
    @include('hmis::vendor.hospital._hospital_submenu_header')
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-user-outlined" style="font-size:22px;"></i></span>
            Nurse Profile
        </h1>
        <div class="d-flex gap-2">
            @if (hasPermission('staff_nurse', 'edit'))
                <a href="{{ route('vendor.nurse.edit', $nurse->id) }}" class="btn btn-sm btn--warning">
                    <i class="tio-edit"></i> Edit
                </a>
            @endif
            <a href="{{ route('vendor.nurse.list') }}" class="btn btn-sm btn-soft-secondary">
                <i class="tio-arrow-backward"></i> Back
            </a> 
        </div>
    </div>

    <div class="row">
        {{-- Left: Staff info --}}
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body text-center">
                    @if($nurse->employee?->image)
                        <img src="{{ asset('storage/vendor') . '/' . $nurse->employee->image }}"
                            class="rounded-circle mb-3" width="90" height="90" style="object-fit:cover;">
                    @else
                        <div class="rounded-circle bg-soft-success d-inline-flex align-items-center justify-content-center mb-3"
                            style="width:90px; height:90px; font-size:32px;">
                            <i class="tio-user"></i>
                        </div>
                    @endif

                    <h5 class="mb-0">{{ $nurse->employee?->f_name }} {{ $nurse->employee?->l_name }}</h5>
                    <span class="badge badge-soft-success mt-1">Nurse</span>

                    <hr>

                    <table class="table table-sm table-borderless text-left mb-0">
                        <tr>
                            <th>Phone</th>
                            <td>{{ $nurse->employee?->phone ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td style="word-break:break-all;">{{ $nurse->employee?->email ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th>Shift</th>
                            <td>
                                @if($nurse->employee?->storeShift)
                                    <span class="badge badge-soft-info">{{ $nurse->employee->storeShift->name }}</span>
                                @else
                                    <span class="text-muted">Not assigned</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right: Professional details --}}
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Professional Details</h6></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th class="text-muted" style="width:45%;">Qualification</th>
                                    <td>{{ $nurse->qualification ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Registration No.</th>
                                    <td>{{ $nurse->registration_number ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Department</th>
                                    <td>{{ $nurse->department ?: '—' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th class="text-muted" style="width:45%;">Assigned Ward</th>
                                    <td>
                                        @if($nurse->ward)
                                            <a href="{{ route('vendor.ward.beds', $nurse->ward_id) }}">
                                                {{ $nurse->ward->ward_name }}
                                            </a>
                                            <br>
                                            <small class="text-muted">
                                                {{ \App\Models\Ward::TYPES[$nurse->ward->ward_type] ?? $nurse->ward->ward_type }}
                                                @if($nurse->ward->floor) · Floor {{ $nurse->ward->floor }} @endif
                                            </small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Shift</th>
                                    <td>{{ $nurse->employee?->storeShift?->name ?? 'Not assigned' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($nurse->notes)
                    <hr class="my-2">
                    <div>
                        <strong class="text-muted" style="font-size:12px;">NOTES</strong>
                        <p class="mb-0 mt-1" style="white-space:pre-wrap;">{{ $nurse->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Today's Duty: punch in / out + extra duty --}}
            @isset($duty)
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="tio-time mr-1"></i> Today's Duty
                    @if($duty['shift_name'])<span class="badge badge-soft-info ml-1">{{ $duty['shift_name'] }}</span>@endif
                </h6></div>
                <div class="card-body">
                    @if($duty['has'])
                        <div class="d-flex flex-wrap" style="gap:24px;">
                            <div><span class="text-muted" style="font-size:12px;">Punch In</span><br>
                                <strong style="color:#16a34a;">{{ $duty['in_time'] ? $duty['in_time']->format('h:i A') : '—' }}</strong></div>
                            <div><span class="text-muted" style="font-size:12px;">Punch Out</span><br>
                                <strong style="color:#dc2626;">{{ $duty['out_time'] ? $duty['out_time']->format('h:i A') : 'Still on duty' }}</strong></div>
                            <div><span class="text-muted" style="font-size:12px;">Worked</span><br>
                                <strong>{{ $duty['worked_label'] ?: '—' }}</strong></div>
                            <div><span class="text-muted" style="font-size:12px;">Extra Duty</span><br>
                                <strong style="color:{{ $duty['extra_label'] ? '#b45309' : '#9ca3af' }};">{{ $duty['extra_label'] ?: 'None' }}</strong></div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No clock-in recorded today.</p>
                    @endif
                </div>
            </div>
            @endisset

            {{-- This month's duty / overtime history --}}
            @isset($dutyHistory)
            <div class="card mb-3">
                <div class="card-header py-2"><h6 class="mb-0"><i class="tio-calendar-month mr-1"></i> Duty History — {{ now()->format('F Y') }}</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless table-thead-bordered mb-0">
                            <thead class="thead-light">
                                <tr><th>Date</th><th>Punch In</th><th>Punch Out</th><th>Worked</th><th>Extra Duty</th></tr>
                            </thead>
                            <tbody>
                                @forelse($dutyHistory as $row)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                                        <td style="color:#16a34a;">{{ $row['in_time'] ? $row['in_time']->format('h:i A') : '—' }}</td>
                                        <td style="color:#dc2626;">{{ $row['out_time'] ? $row['out_time']->format('h:i A') : '—' }}</td>
                                        <td>{{ $row['worked_label'] }}</td>
                                        <td>@if($row['extra_label'])<span class="badge badge-soft-warning">+{{ $row['extra_label'] }}</span>@else<span class="text-muted">—</span>@endif</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">No clock-in records this month.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endisset
        </div>
    </div>
</div>
@endsection
