@extends('layouts.vendor.app')
@section('title', 'Dispense Queue')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-medicine" style="font-size:22px;"></i></span>
            Pharmacy Dispense Queue
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('vendor.prescription.dispense.export', array_filter(['date_range'=>$preset,'custom_date_range'=>request('custom_date_range'),'patient'=>request('patient'),'filter'=>request('filter')])) }}"
               class="btn btn-sm btn-outline-success"><i class="tio-download"></i> Export</a>
            <a href="{{ route('vendor.prescription.list') }}" class="btn btn-sm btn-outline-secondary">
                <i class="tio-document-outlined"></i> All Prescriptions
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="card  mb-3 py-2 date-range-form">
        @include('vendor-views/form_modals/date_range')
        <div class="d-flex flex-wrap gap-2 align-items-end">
            <button type="button" class="btn btn-outline-warning " data-toggle="modal" data-target="#dateRangeModal">
                <i class="tio-calendar"></i> {{ translate($preset) }}
            </button>
            <div>
                <label class="input-label mb-1" style="font-size:12px;">Patient</label>
                <input type="text" name="patient" class="form-control form-control-sm" style="min-width:200px;"
                    placeholder="Name or UID..." value="{{ request('patient') }}">
            </div>
            <div>
                <label class="input-label mb-1" style="font-size:12px;">Show</label>
                <select name="filter" class="form-control form-control-sm">
                    <option value="pending" {{ request('filter','pending') === 'pending' ? 'selected' : '' }}>Pending dispense</option>
                    <option value="all"     {{ request('filter') === 'all' ? 'selected' : '' }}>All finalized</option>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn--primary">Filter</button>
            <a href="{{ route('vendor.prescription.dispense.queue') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Medicines</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($prescriptions as $rx)
                            @php
                                $total      = $rx->items->count();
                                $dispensed  = $rx->items->where('dispensed', true)->count();
                                $allDone    = $total > 0 && $dispensed === $total;
                            @endphp
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
                                <td>{{ $rx->created_at->format('d M Y') }}</td>
                                <td>{{ $total }} medicine(s)</td>
                                <td>
                                    @if($allDone)
                                        <span class="badge badge-success">Fully Dispensed</span>
                                    @elseif($dispensed > 0)
                                        <span class="badge badge-warning">Partial ({{ $dispensed }}/{{ $total }})</span>
                                    @else
                                        <span class="badge badge-soft-danger">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('vendor.prescription.dispense.show', $rx->id) }}"
                                        class="btn btn-sm btn--primary">
                                        <i class="tio-medicine"></i>
                                        {{ $allDone ? 'View' : 'Dispense' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No prescriptions in queue.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $prescriptions->links() }}</div>
</div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
