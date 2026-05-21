@extends('layouts.vendor.app')
@section('title', 'Nurse Profile')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-user-outlined" style="font-size:22px;"></i></span>
            Nurse Profile
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('vendor.nurse.edit', $nurse->id) }}" class="btn btn-sm btn--warning">
                <i class="tio-edit"></i> Edit
            </a>
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
                                <span class="badge badge-soft-info">
                                    {{ \App\Models\NurseProfile::SHIFTS[$nurse->shift] ?? ucfirst($nurse->shift) }}
                                </span>
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
                                    <td>{{ \App\Models\NurseProfile::SHIFTS[$nurse->shift] ?? ucfirst($nurse->shift) }}</td>
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
        </div>
    </div>
</div>
@endsection
