@extends('layouts.vendor.app')
@section('title', 'Appointments')

@section('content')
<div class="content container-fluid">
    @include('hmis::vendor.hospital._hospital_submenu_header')
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title mb-2">
                <span class="page-header-icon"><i class="tio-calendar" style="font-size:22px;"></i></span>
                Appointments
                <span class="badge badge-soft-dark ml-2">{{ $appointments->total() }}</span>
            </h1>
            @if (hasPermission('hmis_appointment', 'add'))
                <a href="{{ route('vendor.appointment.create') }}" class="btn btn--primary mb-2">
                    <i class="tio-add-circle"></i> Book Appointment
                </a>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form class="row align-items-end g-2" method="GET">
                <div class="col-md-3">
                    <label class="input-label">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm"
                        value="{{ $filters['date'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="input-label">Doctor</label>
                    <select name="doctor_id" class="form-control form-control-sm">
                        <option value="">All Doctors</option>
                        @foreach($doctors as $d)
                            <option value="{{ $d->id }}" {{ ($filters['doctor_id'] ?? '') == $d->id ? 'selected' : '' }}>
                                Dr. {{ $d->employee?->f_name }} {{ $d->employee?->l_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="input-label">Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $s)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn--secondary btn-sm">Filter</button>
                    <a href="{{ route('vendor.appointment.list') }}" class="btn btn-outline-secondary btn-sm ml-1">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>Token</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $apt)
                    <tr>
                        <td>
                            @if($apt->token)
                                <span class="badge badge-soft-info font-size-14">#{{ $apt->token->token_number }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $apt->patient?->name }}</div>
                            <small class="text-muted">{{ $apt->patient?->patient_uid }}</small>
                        </td>
                        <td>
                            Dr. {{ $apt->doctorProfile?->employee?->f_name }}
                            {{ $apt->doctorProfile?->employee?->l_name }}
                            <br><small class="text-muted">{{ $apt->doctorProfile?->specialization }}</small>
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($apt->appointment_date)->format('d M Y') }}
                            <br><small>{{ \Carbon\Carbon::parse($apt->appointment_time)->format('h:i A') }}</small>
                        </td>
                        <td>
                            <span class="badge badge-soft-{{ $apt->booking_type === 'online' ? 'primary' : 'secondary' }}">
                                {{ ucfirst(str_replace('_', ' ', $apt->booking_type)) }}
                            </span>
                        </td>
                        <td>
                            @php
                                $colors = [
                                    'scheduled'  => 'warning',
                                    'checked_in' => 'info',
                                    'consulting' => 'primary',
                                    'completed'  => 'success',
                                    'cancelled'  => 'danger',
                                    'no_show'    => 'secondary',
                                ];
                            @endphp
                            <span class="badge badge-{{ $colors[$apt->status] ?? 'secondary' }}">
                                {{ ucfirst(str_replace('_', ' ', $apt->status)) }}
                            </span>
                        </td>
                        <td>
                            @if (hasPermission('hmis_appointment', 'view'))
                                <a href="{{ route('vendor.appointment.show', $apt->id) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="tio-visible"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No appointments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($appointments->hasPages())
        <div class="card-footer">
            {{ $appointments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
