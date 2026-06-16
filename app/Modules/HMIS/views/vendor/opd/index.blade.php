@extends('layouts.vendor.app')
@section('title', $myScope ? 'My OPD Appointments' : 'OPD Register')

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Main Wrapper Styling */
        .content.container-fluid {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            padding: 1.5rem;
        }

        /* Header Styling */
        .page-header-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .page-header-icon {
            color: #0d47a1;
            margin-right: 8px;
        }

        /* Custom HMIS Premium Buttons */
        .btn-hmis-export {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #f0fdf4 !important;
            border: 1.5px solid #10b981 !important;
            color: #047857 !important;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 13px !important;
            padding: 6px 16px !important;
            border-radius: 8px !important;
            transition: all 0.2s ease;
            text-decoration: none !important;
        }
        .btn-hmis-export:hover {
            background-color: #dcfce7 !important;
            color: #065f46 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.1), 0 2px 4px -1px rgba(16, 185, 129, 0.06);
        }

        .btn-hmis-primary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #6366f1 !important; /* Premium Indigo-blue */
            border: 1.5px solid #6366f1 !important;
            color: #ffffff !important;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 13px !important;
            padding: 6px 16px !important;
            border-radius: 8px !important;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.15), 0 2px 4px -1px rgba(99, 102, 241, 0.1);
            text-decoration: none !important;
        }
        .btn-hmis-primary:hover {
            background-color: #4f46e5 !important;
            border-color: #4f46e5 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2), 0 4px 6px -4px rgba(99, 102, 241, 0.1);
        }

        .btn-hmis-date-filter {
            background-color: #fff7ed !important;
            border: 1.5px solid #f97316 !important;
            color: #ea580c !important;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 13px !important;
            padding: 6px 16px !important;
            border-radius: 8px !important;
            transition: all 0.2s ease;
        }
        .btn-hmis-date-filter:hover {
            background-color: #ffedd5 !important;
            color: #c2410c !important;
        } 

        /* Form inputs & Select dropdowns */
        .hmis-select, .hmis-input {
            background-color: #ffffff !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            font-family: 'Inter', sans-serif;
            font-size: 13px !important;
            height: 38px !important;
            color: #1e293b !important;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .hmis-select:focus, .hmis-input:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
            outline: none !important;
        }

        /* Card Styling */
        .hmis-card {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -1px rgba(0, 0, 0, 0.02) !important;
            overflow: hidden;
        }

        /* Table Styling */
        .table.table-hover {
            border-collapse: separate !important;
            border-spacing: 0 !important;
        }
        .table.table-hover thead th {
            background-color: #f8fafc !important;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 13px;
            color: #475569;
            border-bottom: 2px solid #e2e8f0 !important;
            padding: 14px 16px !important;
            letter-spacing: 0.2px;
        }
        .table.table-hover tbody td {
            padding: 14px 16px !important;
            vertical-align: middle !important;
            border-bottom: 1px solid #f1f5f9 !important;
            color: #334155;
            font-size: 13px;
        }
        .table.table-hover tbody tr:hover td {
            background-color: #f8fafc !important;
        }

        /* Red Token Badge */
        .hmis-token-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #ef4444 !important; /* Bright Red */
            color: #ffffff !important;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 14px !important;
            width: 32px;
            height: 32px;
            border-radius: 6px !important;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
        }

        /* Visit Type badge */
        .hmis-badge-visit-type {
            display: inline-flex;
            align-items: center;
            background-color: #f0fdfa !important; /* Light teal */
            color: #0d9488 !important; /* Teal text */
            border: 1px solid #ccfbf1 !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            padding: 4px 10px !important;
            border-radius: 8px !important;
        }

        /* Status badge */
        .hmis-badge-status {
            display: inline-flex;
            align-items: center;
            font-size: 12px !important;
            font-weight: 600 !important;
            padding: 4px 10px !important;
            border-radius: 100px !important;
        }
        .hmis-badge-status.waiting { background-color: #fffbeb !important; color: #b45309 !important; border: 1px solid #fde68a !important; }
        .hmis-badge-status.completed { background-color: #dcfce7 !important; color: #15803d !important; border: 1px solid #bbf7d0 !important; }

        /* Action Buttons */
        .btn-hmis-action-view {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #818cf8 !important; /* Soft blue-purple */
            color: #ffffff !important;
            border: none !important;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 12px !important;
            padding: 6px 14px !important;
            border-radius: 8px !important;
            transition: all 0.2s ease;
            text-decoration: none !important;
        }
        .btn-hmis-action-view:hover {
            background-color: #6366f1 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.15);
        }

        .btn-hmis-action-receipt {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #ffffff !important;
            border: 1.5px solid #06b6d4 !important; /* Cyan */
            color: #0891b2 !important;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 12px !important;
            padding: 5px 14px !important;
            border-radius: 8px !important;
            transition: all 0.2s ease;
            text-decoration: none !important;
        }
        .btn-hmis-action-receipt:hover {
            background-color: #ecfeff !important;
            color: #0e7490 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(6, 182, 212, 0.1);
        }

        /* Vitals Icons Colors */
        .vit-bp { color: #f43f5e; }
        .vit-temp { color: #f59e0b; }
        .vit-spo2 { color: #06b6d4; }
        .vit-pulse { color: #e11d48; }

        .vitals-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .vital-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
                {{ $myScope ? 'My OPD Appointments' : 'OPD Daily Register' }}
                <small class="text-muted font-size-14 ml-2">{{ translate($preset) }}</small>
            </h1>
            <div class="d-flex gap-2"> 
                @if (!$myScope && hasPermission('opd_register', 'export'))
                <a href="{{ route('vendor.opd.export', array_filter(['date_range'=>$preset,'custom_date_range'=>request('custom_date_range'),'doctor'=>request('doctor'),'search'=>request('search')])) }}"
                   class="btn btn-hmis-export"><i class="tio-download"></i> Export</a>
                @endif
                @if (hasPermission('opd_register', 'add'))
                <a href="{{ route('vendor.opd.create') }}" class="btn btn-hmis-primary">
                    <i class="tio-add"></i> Register Visit
                </a>
                @endif
            </div>
        </div>

        @if( hasPermission('opd_register', 'list'))

        {{-- Filters --}}
        <div class="card hmis-card mb-4">
            <div class="d-flex flex-wrap gap-2 align-items-center px-4 py-3">
                <form method="GET" class="date-range-form mb-0">
                    @if($myScope)<input type="hidden" name="scope" value="my">@endif
                    @include('vendor-views/form_modals/date_range')
                    <button style="width:fit-content; white-space:nowrap" class="btn btn-hmis-date-filter" type="button"
                        data-toggle="modal" data-target="#dateRangeModal"><i class="tio-calendar"></i> {{ translate($preset) }}</button>
                </form>
                <form method="GET" class="mb-0 flex-grow-1">
                    @if($myScope)<input type="hidden" name="scope" value="my">@endif
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @if(!$myScope)
                        <div>
                            <select name="doctor" class="form-control form-control-sm hmis-select" onchange="this.form.submit()"
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
                        @endif
                        <div class="d-flex gap-2 align-items-center flex-grow-1" style="max-width: 450px;">
                            <input type="text" name="search" class="form-control form-control-sm hmis-input flex-grow-1"
                                placeholder="Search by Name or UID..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-hmis-primary">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card hmis-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Token</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Visit Type</th>
                                <th>Chief Complaint</th>
                                <th>Vitals</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($visits as $visit)
                                <tr>
                                    <td>
                                        <span class="hmis-token-badge">
                                            {{ $visit->token_number }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: #0f172a; font-weight: 600;">{{ $visit->patient?->name }}</strong>
                                        <br><small class="text-muted">{{ $visit->patient?->patient_uid }}</small>
                                    </td>
                                    <td>Dr. {{ $visit->doctorProfile?->employee?->f_name }}
                                        {{ $visit->doctorProfile?->employee?->l_name }}</td>
                                    <td>
                                        <span class="hmis-badge-visit-type">
                                            {{ \App\Models\OpdVisit::VISIT_TYPES[$visit->visit_type] ?? $visit->visit_type }}
                                        </span>
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($visit->chief_complaint, 50) ?: '—' }}</td>
                                    <td>
                                        <div class="vitals-list">
                                            @if ($visit->bp_systolic && $visit->bp_diastolic)
                                                <span class="vital-item" title="Blood Pressure">
                                                    <i class="tio-heart-outlined vit-bp"></i>
                                                    {{ $visit->bp_systolic }}/{{ $visit->bp_diastolic }}
                                                </span>
                                            @endif
                                            @if ($visit->temperature)
                                                <span class="vital-item" title="Temperature">
                                                    <i class="tio-thermometer vit-temp"></i>
                                                    {{ $visit->temperature }}°F
                                                </span>
                                            @endif
                                            @if ($visit->spo2)
                                                <span class="vital-item" title="SpO2">
                                                    <i class="tio-pulse vit-spo2"></i>
                                                    {{ $visit->spo2 }}%
                                                </span>
                                            @endif
                                            @if ($visit->pulse_rate)
                                                <span class="vital-item" title="Pulse">
                                                    <i class="tio-heart vit-pulse"></i>
                                                    {{ $visit->pulse_rate }} bpm
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($visit->consultation_receipt_id)
                                            <span class="hmis-badge-status completed">Completed</span>
                                        @else
                                            <span class="hmis-badge-status waiting">Waiting</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ( hasPermission('opd_register', 'view'))
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('vendor.opd.show', $visit->id) }}"
                                                class="btn btn-hmis-action-view">
                                                <i class="tio-visible"></i> View
                                            </a>
                                            <a href="{{ route('vendor.opd.consultation-receipt', $visit->id) }}"
                                                class="btn btn-hmis-action-receipt" title="OP Consultation Receipt">
                                                <i class="tio-receipt"></i> Receipt
                                            </a>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No OPD visits recorded for this date.</td>
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
