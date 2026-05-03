@extends('layouts.admin.app')
@section('title', 'Hospital Activity Logs')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">
    @include('admin-views/partials/_action-nav')

    <div class="page-header d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-header-title">
            <span class="page-header-icon"><i class="tio-history" style="font-size:22px;"></i></span>
            Hospital Activity Logs
        </h1>
        <span class="text-muted" style="font-size:13px;">
            {{ number_format($logs->total()) }} record{{ $logs->total() != 1 ? 's' : '' }}
        </span>
    </div>

    @php
        $tabs = [
            ''              => ['label' => 'All',            'icon' => 'tio-history'],
            'appointments'  => ['label' => 'Appointments',   'icon' => 'tio-calendar'],
            'prescriptions' => ['label' => 'Prescriptions',  'icon' => 'tio-medicine'],
            'patients'      => ['label' => 'Patients',       'icon' => 'tio-user'],
            'opd'           => ['label' => 'OPD Visits',     'icon' => 'tio-hospital'],
            'ipd'           => ['label' => 'IPD Admissions', 'icon' => 'tio-home'],
            'doctors_slots' => ['label' => 'Doctors & Slots','icon' => 'tio-user-add'],
        ];
        $countKeys = ['' => 'all', 'appointments' => 'appointments', 'prescriptions' => 'prescriptions',
                      'patients' => 'patients', 'opd' => 'opd', 'ipd' => 'ipd', 'doctors_slots' => 'doctors_slots'];
    @endphp

    {{-- Category tabs --}}
    <div class="card mb-0" style="border-bottom:none; border-radius:8px 8px 0 0;">
        <div class="card-body py-0 px-3">
            <ul class="nav nav-tabs border-0" style="gap:4px; flex-wrap:nowrap; overflow-x:auto;">
                @foreach($tabs as $slug => $tab)
                @php
                    $active = ($category === $slug);
                    $count  = $categoryCounts[$countKeys[$slug]] ?? 0;
                    $params = array_merge(request()->except('category','page'), $slug ? ['category' => $slug] : []);
                @endphp
                <li class="nav-item" style="white-space:nowrap;">
                    <a href="{{ route('admin.logs.hospital-activity-logs', $params) }}"
                       class="nav-link d-flex align-items-center gap-1 {{ $active ? 'active font-weight-bold' : 'text-muted' }}"
                       style="font-size:13px; padding:10px 14px; border-bottom: {{ $active ? '2px solid #377dff' : '2px solid transparent' }};">
                        <i class="{{ $tab['icon'] }}" style="font-size:14px;"></i>
                        {{ $tab['label'] }}
                        <span class="badge {{ $active ? 'badge-primary' : 'badge-soft-secondary' }} ml-1"
                              style="font-size:10px; min-width:20px;">
                            {{ number_format($count) }}
                        </span>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Filters bar --}}
    <div class="card mb-3" style="border-radius:0 0 8px 8px; border-top:1px solid #e7eaf3;">
        <div class="py-2 px-3">
            <form class="d-flex flex-wrap gap-2 align-items-end date-range-form" method="GET">
                @if($category)
                    <input type="hidden" name="category" value="{{ $category }}">
                @endif

                @include('admin-views/form_modals/date_range')
                <button type="button" class="btn btn-outline-warning" data-toggle="modal" data-target="#dateRangeModal">
                    <i class="tio-calendar"></i> {{ translate($preset) }}
                </button>

                {{-- Store filter --}}
                <div>
                    <label class="input-label d-block mb-1" style="font-size:11px; color:#8c98a4;">STORE</label>
                    <select name="store_id" id="store-search" class="form-control form-control-sm" style="min-width:220px;">
                        <option value="">All Stores</option>
                        @if($storeId)
                            @php $selectedStore = \App\Models\Store::find($storeId); @endphp
                            @if($selectedStore)
                                <option value="{{ $selectedStore->id }}" selected>{{ $selectedStore->name }}</option>
                            @endif
                        @endif
                    </select>
                </div>

                <div>
                    <label class="input-label d-block mb-1" style="font-size:11px; color:#8c98a4;">DONE BY</label>
                    <select name="causer_type" class="form-control form-control-sm" style="min-width:130px;">
                        <option value="">All Users</option>
                        <option value="vendor"          {{ request('causer_type') == 'vendor'          ? 'selected' : '' }}>Vendor</option>
                        <option value="vendor_employee" {{ request('causer_type') == 'vendor_employee' ? 'selected' : '' }}>Staff</option>
                    </select>
                </div>

                <div style="flex:1; min-width:200px;">
                    <label class="input-label d-block mb-1" style="font-size:11px; color:#8c98a4;">SEARCH</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="form-control form-control-sm" placeholder="Search by name or description...">
                </div>

                <button type="submit" class="btn btn--primary">
                    <i class="tio-filter-list"></i> Filter
                </button>
                <a href="{{ route('admin.logs.hospital-activity-logs', $category ? ['category' => $category] : []) }}"
                   class="btn btn-outline-secondary"><i class="tio-refresh"></i></a>
            </form>
        </div>
    </div>

    {{-- Log table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:150px;">Date &amp; Time</th>
                            <th style="width:180px;">Store</th>
                            <th style="width:170px;">Who</th>
                            <th style="width:130px;">Category</th>
                            <th style="width:90px;">Action</th>
                            <th>Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $subjectMeta = [
                                'appointment'   => ['color' => 'warning',   'icon' => 'tio-calendar',  'label' => 'Appointment'],
                                'prescription'  => ['color' => 'success',   'icon' => 'tio-medicine',  'label' => 'Prescription'],
                                'patient'       => ['color' => 'primary',   'icon' => 'tio-user',      'label' => 'Patient'],
                                'opd_visit'     => ['color' => 'info',      'icon' => 'tio-hospital',  'label' => 'OPD Visit'],
                                'ipd_admission' => ['color' => 'danger',    'icon' => 'tio-home',      'label' => 'IPD Admission'],
                                'doctor'        => ['color' => 'dark',      'icon' => 'tio-user-add',  'label' => 'Doctor'],
                                'slot'          => ['color' => 'secondary', 'icon' => 'tio-time',      'label' => 'Slot'],
                            ];
                            $actionMeta = [
                                'created'        => ['color' => 'success',   'label' => 'Created'],
                                'updated'        => ['color' => 'primary',   'label' => 'Updated'],
                                'finalized'      => ['color' => 'success',   'label' => 'Finalized'],
                                'status_changed' => ['color' => 'warning',   'label' => 'Status Changed'],
                                'rescheduled'    => ['color' => 'warning',   'label' => 'Rescheduled'],
                                'reassigned'     => ['color' => 'info',      'label' => 'Reassigned'],
                                'admitted'       => ['color' => 'danger',    'label' => 'Admitted'],
                                'discharged'     => ['color' => 'secondary', 'label' => 'Discharged'],
                                'cancelled'      => ['color' => 'danger',    'label' => 'Cancelled'],
                                'deleted'        => ['color' => 'danger',    'label' => 'Deleted'],
                                'toggled'        => ['color' => 'secondary', 'label' => 'Toggled'],
                            ];
                            $causerMeta = [
                                'vendor'          => ['color' => 'primary', 'label' => 'Vendor'],
                                'vendor_employee' => ['color' => 'info',    'label' => 'Staff'],
                            ];
                        @endphp

                        @forelse($logs as $log)
                        @php
                            $sm = $subjectMeta[$log->subject_type] ?? ['color' => 'secondary', 'icon' => 'tio-circle', 'label' => ucfirst($log->subject_type)];
                            $am = $actionMeta[$log->action]        ?? ['color' => 'secondary', 'label' => ucfirst($log->action)];
                            $cm = $causerMeta[$log->causer_type]   ?? ['color' => 'secondary', 'label' => $log->causer_type];
                        @endphp
                        <tr>
                            {{-- Date --}}
                            <td class="text-nowrap">
                                <span style="font-size:12px; font-weight:600; color:#1e2022;">
                                    {{ $log->created_at->format('d M Y') }}
                                </span><br>
                                <span class="text-muted" style="font-size:11px;">
                                    {{ $log->created_at->format('h:i A') }}
                                </span>
                            </td>

                            {{-- Store --}}
                            <td>
                                <div style="font-size:13px; font-weight:600; color:#1e2022;">
                                    {{ $log->store?->name ?? '—' }}
                                </div>
                                <span class="text-muted" style="font-size:11px;">#{{ $log->store_id }}</span>
                            </td>

                            {{-- Who --}}
                            <td>
                                <div style="font-size:13px; font-weight:600; color:#1e2022;">
                                    {{ $log->causer_name ?: '—' }}
                                </div>
                                <span class="badge badge-soft-{{ $cm['color'] }}" style="font-size:10px;">
                                    {{ $cm['label'] }}
                                </span>
                            </td>

                            {{-- Category --}}
                            <td>
                                <span class="badge badge-soft-{{ $sm['color'] }}"
                                      style="font-size:11px; padding:4px 8px;">
                                    <i class="{{ $sm['icon'] }} mr-1"></i>
                                    {{ $sm['label'] }}
                                    @if($log->subject_id)
                                        <span class="text-muted">#{{ $log->subject_id }}</span>
                                    @endif
                                </span>
                            </td>

                            {{-- Action --}}
                            <td>
                                <span class="badge badge-{{ $am['color'] }}" style="font-size:10px; padding:3px 7px;">
                                    {{ $am['label'] }}
                                </span>
                            </td>

                            {{-- Activity --}}
                            <td>
                                <div style="color:#1e2022;">{{ $log->description }}</div>
                                @if($log->properties)
                                <div class="mt-1 d-flex flex-wrap gap-1">
                                    @foreach($log->properties as $k => $v)
                                    @if($v !== null && $v !== '')
                                    <span style="font-size:11px; background:#f8fafc; border:1px solid #e7eaf3;
                                                 border-radius:4px; padding:1px 6px; color:#677788;">
                                        <strong>{{ ucwords(str_replace('_', ' ', $k)) }}:</strong>
                                        {{ is_array($v) ? implode(', ', $v) : $v }}
                                    </span>
                                    @endif
                                    @endforeach
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="tio-history" style="font-size:36px; color:#c8d6e5;"></i>
                                <p class="text-muted mt-2 mb-0">No activity found for the selected filters.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                 style="border-top:1px solid #e7eaf3; background:#fafbfc;">
                <small class="text-muted">
                    Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }}
                </small>
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('script_2')
    @include('admin-views/js/date_range')
    <script>
        "use strict";
        $('#store-search').select2({
            placeholder: 'Search store...',
            allowClear: true,
            ajax: {
                url: '{{ route('admin.store.get-vendor-log-matches') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) {
                    return {
                        results: $.map(data, function(s) {
                            var vendorName = s.vendor ? (s.vendor.f_name + ' ' + s.vendor.l_name).trim() : '';
                            var text = s.name;
                            if (vendorName) text += ' — ' + vendorName;
                            return { id: s.id, text: text };
                        })
                    };
                }
            }
        });
        $('#store-search').on('change', function() {
            var url = new URL(window.location.href);
            var val = $(this).val();
            if (val) url.searchParams.set('store_id', val);
            else url.searchParams.delete('store_id');
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    </script>
@endpush
