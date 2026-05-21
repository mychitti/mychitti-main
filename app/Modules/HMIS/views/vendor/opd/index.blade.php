@extends('layouts.vendor.app')
@section('title', 'OPD Register')

@section('content')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">

    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
                OPD Daily Register
                <small class="text-muted font-size-14 ml-2">{{ translate($preset) }}</small>
            </h1>
            <div class="d-flex gap-2">
                @if (hasPermission('opd_register', 'export'))
                <a href="{{ route('vendor.opd.export', array_filter(['date_range'=>$preset,'custom_date_range'=>request('custom_date_range'),'doctor'=>request('doctor'),'search'=>request('search')])) }}"
                   class="btn btn-sm btn-outline-success"><i class="tio-download"></i> Export</a>
                @endif
                @if (hasPermission('opd_register', 'add'))
                <a href="{{ route('vendor.opd.create') }}" class="btn btn--primary btn-sm">
                    <i class="tio-add"></i> Register Visit
                </a>
                @endif
            </div>
        </div>

        @if(hasPermission('opd_register', 'list'))
        

        {{-- Filters --}}
        <div class="card  mb-3 py-2">
            <div class="d-flex flex-wrap gap-2 align-items-end ">

                <form method="GET" class=" date-range-form">
                    @include('vendor-views/form_modals/date_range')
                    <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning" type="button"
                        data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
                </form>
                <form method="GET" class="">
                    <div class="d-flex flex-wrap gap-2 align-items-end ">

                        <div>
                            <select name="doctor" class="form-control form-control-sm" onchange="this.form.submit()"
                                style="min-width:160px;">
                                <option value="">All Doctors</option>
                                @foreach ($doctors as $doc)
                                    <option value="{{ $doc->id }}"
                                        {{ request('doctor') == $doc->id ? 'selected' : '' }}>
                                        Dr. {{ $doc->employee?->f_name }} {{ $doc->employee?->l_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm"
                                style="min-width:180px;" placeholder="Search by Name or UID..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-sm btn--primary">Filter</button>
                        </div>
                    </div>
                    {{-- 
            <a href="{{ route('vendor.opd.index') }}" class="btn btn-sm btn-outline-secondary">Today</a> --}}
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Token</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Visit Type</th>
                                <th>Chief Complaint</th>
                                <th>Vitals</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($visits as $visit)
                                <tr>
                                    <td>
                                        <span class="badge badge-primary" style="font-size:14px; padding:6px 10px;">
                                            {{ $visit->token_number }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $visit->patient?->name }}</strong>
                                        <br><small class="text-muted">{{ $visit->patient?->patient_uid }}</small>
                                    </td>
                                    <td>Dr. {{ $visit->doctorProfile?->employee?->f_name }}
                                        {{ $visit->doctorProfile?->employee?->l_name }}</td>
                                    <td>
                                        <span class="badge badge-soft-primary">
                                            {{ \App\Models\OpdVisit::VISIT_TYPES[$visit->visit_type] ?? $visit->visit_type }}
                                        </span>
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($visit->chief_complaint, 50) ?: '—' }}</td>
                                    <td style="font-size:12px; white-space:nowrap;">
                                        @if ($visit->bp_systolic && $visit->bp_diastolic)
                                            <span title="Blood Pressure"><i class="tio-heart-outlined text-danger"></i>
                                                {{ $visit->bp_systolic }}/{{ $visit->bp_diastolic }}</span><br>
                                        @endif
                                        @if ($visit->temperature)
                                            <span title="Temperature"><i class="tio-thermometer text-warning"></i>
                                                {{ $visit->temperature }}°F</span><br>
                                        @endif
                                        @if ($visit->spo2)
                                            <span title="SpO2"><i class="tio-pulse text-info"></i>
                                                {{ $visit->spo2 }}%</span><br>
                                        @endif
                                        @if ($visit->pulse_rate)
                                            <span title="Pulse"><i class="tio-heart text-danger"></i>
                                                {{ $visit->pulse_rate }} bpm</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (hasPermission('opd_register', 'view'))
                                        <a href="{{ route('vendor.opd.show', $visit->id) }}"
                                            class="btn btn-xs btn--primary">
                                            <i class="tio-visible"></i> View
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No OPD visits recorded for this
                                        date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">{{ $visits->appends(request()->query())->links() }}</div>
        @endif
    </div>

@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
