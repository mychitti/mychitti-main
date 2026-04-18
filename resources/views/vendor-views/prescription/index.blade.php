@extends('layouts.vendor.app')
@section('title', 'Prescriptions')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <h1 class="page-header-title mb-2">
            <span class="page-header-icon"><i class="tio-medicine" style="font-size:22px;"></i></span>
            Prescriptions
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('vendor.prescription.export', array_filter(['date_range'=>$preset,'custom_date_range'=>request('custom_date_range'),'patient'=>request('patient'),'doctor'=>request('doctor')])) }}"
               class="btn btn-outline-success"><i class="tio-download"></i> Export</a>
            <a href="{{ route('vendor.prescription.create') }}" class="btn btn--primary btn-sm">
                <i class="tio-add"></i> New Prescription
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class=" py-2">
            <form class="d-flex flex-wrap gap-2 align-items-end date-range-form" method="GET">
                @include('vendor-views/form_modals/date_range')
                <button type="button" class="btn btn-outline-warning " data-toggle="modal" data-target="#dateRangeModal">
                    <i class="tio-calendar"></i> {{ translate($preset) }}
                </button>
                <div>
                    <label class="input-label d-block mb-1" style="font-size:12px;">Patient</label>
                    <input type="text" name="patient" value="{{ request('patient') }}"
                        class="form-control form-control-sm" placeholder="Name or UID" style="min-width:180px;">
                </div>
                <div>
                    <label class="input-label d-block mb-1" style="font-size:12px;">Doctor</label>
                    <select name="doctor" class="form-control form-control-sm" style="min-width:160px;">
                        <option value="">All Doctors</option>
                        @foreach($doctors as $d)
                            <option value="{{ $d->id }}" {{ request('doctor') == $d->id ? 'selected' : '' }}>
                                Dr. {{ $d->employee?->f_name }} {{ $d->employee?->l_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                    <button type="submit" class="btn  btn--primary">Filter</button>
                    <a href="{{ route('vendor.prescription.list') }}" class="btn  btn-outline-secondary">Reset</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Diagnosis</th>
                            <th>Medicines</th>
                            <th>Follow-Up</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($prescriptions as $rx)
                        <tr>
                            <td>{{ $rx->id }}</td>
                            <td>
                                <strong>{{ $rx->patient?->name }}</strong>
                                <br><small class="text-muted">{{ $rx->patient?->patient_uid }}</small>
                            </td>
                            <td>
                                Dr. {{ $rx->doctorProfile?->employee?->f_name }}
                                {{ $rx->doctorProfile?->employee?->l_name }}
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($rx->diagnosis, 40) ?: '—' }}</td>
                            <td>{{ $rx->items_count ?? $rx->items->count() }}</td>
                            <td>{{ $rx->follow_up_date ? $rx->follow_up_date->format('d M Y') : '—' }}</td>
                            <td>
                                @if($rx->is_finalized)
                                    <span class="badge badge-soft-success">Finalized</span>
                                @else
                                    <span class="badge badge-soft-warning">Draft</span>
                                @endif
                            </td>
                            <td>{{ $rx->created_at->format('d M Y') }}</td>
                            <td class="text-right">
                                <a href="{{ route('vendor.prescription.show', $rx->id) }}"
                                   class="btn btn-sm btn-outline-primary" title="View / Print">
                                    <i class="tio-print"></i>
                                </a>
                                @if(!$rx->is_finalized)
                                <a href="{{ route('vendor.prescription.edit', $rx->id) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="tio-edit"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No prescriptions found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($prescriptions->hasPages())
            <div class="p-3">{{ $prescriptions->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
