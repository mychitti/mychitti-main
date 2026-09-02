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
        /* Sits under the visit-type badge: how the visit is paid for, quieter than the
           clinical label above it because the desk reads it second. */
        .opd-op-type {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            margin-top: 4px;
        }

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
        .hmis-badge-status.cancelled { background-color: #fef2f2 !important; color: #b91c1c !important; border: 1px solid #fecaca !important; }

        /* A cancelled visit keeps its row so the token is still accounted for, but must not read
           as part of the working queue. */
        tr.row-cancelled td { opacity: .55; }
        tr.row-cancelled td .hmis-badge-status { opacity: 1; }
        tr.row-cancelled .hmis-token-badge { background-color: #94a3b8 !important; box-shadow: none; }
        .cancel-reason-note { font-size: 11px; color: #64748b; margin-top: 4px; max-width: 180px; white-space: normal; }

        .btn-hmis-action-more {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #ffffff !important;
            border: 1.5px solid #e2e8f0 !important;
            color: #64748b !important;
            font-size: 13px !important;
            width: 30px;
            height: 28px;
            border-radius: 8px !important;
            transition: all 0.2s ease;
            padding: 0 !important;
        }
        .btn-hmis-action-more:hover { background-color: #f1f5f9 !important; color: #334155 !important; }
        .opd-action-menu { font-size: 13px; min-width: 170px; }
        .opd-action-menu .dropdown-item { padding: 7px 14px; }
        .opd-action-menu button.dropdown-item { border: 0; background: none; width: 100%; text-align: left; }

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

        /* ── Phone / small tablet ──
           The header keeps the title and up to five action buttons on one
           non-wrapping row, and the filter bar has fixed 160px/450px tracks —
           together they push the page wider than the viewport and squeeze the
           title into a one-word-per-line column. Let both wrap instead. */
        @media (max-width: 767px) {
            .content.container-fluid { padding: 0.75rem; }
            .page-header { flex-wrap: wrap; gap: 10px; }
            .page-header-title { font-size: 18px; width: 100%; }
            .page-header .d-flex.gap-2 { flex-wrap: wrap; gap: 6px !important; width: 100%; }
            .btn-hmis-export, .btn-hmis-primary { font-size: 12px !important; padding: 6px 12px !important; }
            .hmis-card .d-flex.flex-wrap { padding: 10px 12px !important; }
            .date-range-form { width: 100%; }
            .hmis-select { min-width: 100% !important; width: 100%; }
            .hmis-card form .d-flex.gap-2.align-items-center.flex-grow-1 { max-width: 100% !important; width: 100%; }
            .opd-action-menu { min-width: 150px; }
            .cancel-reason-note { max-width: 100%; }
        }
    </style>
@endpush

@section('content')
    @php $showVitals = hmis_vitals_enabled(); @endphp
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
                @if (hmis_lab_work_enabled() && hasPermission('opd_register', 'view'))
                <a href="{{ route('vendor.opd.lab-work.index') }}" class="btn btn-hmis-export"
                   title="Every lab work job across all patients, and who carried each one in or out">
                    <i class="tio-lab"></i> Lab Work
                </a>
                @endif
                @if (hasPermission('opd_register', 'edit'))
                <a href="{{ route('vendor.opd.terms') }}" class="btn btn-hmis-export"
                   title="Choose which diagnosis and treatment terms your doctors are offered">
                    <i class="tio-label"></i> Clinical Terms
                </a>
                <a href="{{ route('vendor.opd.treatment-catalog') }}" class="btn btn-hmis-export"
                   title="What each treatment costs — used to fill the amount during a consultation">
                    <i class="tio-money"></i> Treatment Catalog
                </a>
                <a href="{{ route('vendor.hospital.settings') }}#opTypes" class="btn btn-hmis-export"
                   title="Choose how visits can be paid for — cash, insurance, government schemes">
                    <i class="tio-card"></i> OP Types
                </a>
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
                                <th>SI</th>
                                <th>Token</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Visit Type</th>
                                <th>Chief Complaint</th>
                                @if($showVitals)<th>Vitals</th>@endif
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($visits as $visit)
                                <tr class="{{ $visit->is_cancelled ? 'row-cancelled' : '' }}">
                                    <td class="text-muted">{{ $visits->firstItem() + $loop->index }}</td>
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
                                        @if ($visit->op_type)
                                            <div class="opd-op-type">{{ $visit->op_type }}</div>
                                        @endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($visit->chief_complaint, 50) ?: '—' }}</td>
                                    @if($showVitals)
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
                                    @endif
                                    <td>
                                        @if ($visit->is_cancelled)
                                            <span class="hmis-badge-status cancelled">Cancelled</span>
                                            @if ($visit->cancel_reason)
                                                <div class="cancel-reason-note">{{ $visit->cancel_reason }}</div>
                                            @endif
                                        @elseif ($visit->consultation_receipt_id)
                                            <span class="hmis-badge-status completed">Completed</span>
                                        @else
                                            <span class="hmis-badge-status waiting">Waiting</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ( hasPermission('opd_register', 'view'))
                                        @php($canCancel = !$visit->is_cancelled && hasPermission('opd_register', 'cancel'))
                                        @php($canDelete = hasPermission('opd_register', 'delete'))
                                        <div class="d-flex gap-2 align-items-center">
                                            <a href="{{ route('vendor.opd.show', $visit->id) }}"
                                                class="btn btn-hmis-action-view">
                                                <i class="tio-visible"></i> View
                                            </a>
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-hmis-action-more" data-toggle="dropdown"
                                                    aria-haspopup="true" aria-expanded="false" title="More Actions">
                                                    <i class="tio-more-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right opd-action-menu">
                                                    @if (!$visit->is_cancelled && _canViewOpdReceipt())
                                                        <a href="{{ route('vendor.opd.consultation-receipt', $visit->id) }}"
                                                            class="dropdown-item" target="_blank">
                                                            <i class="tio-receipt mr-2 text-info"></i> OP Receipt
                                                        </a>
                                                    @endif
                                                    @if (!$visit->is_cancelled && hasPermission('opd_register', 'generate_bill'))
                                                        <a href="{{ route('vendor.hospital-bill.create-opd', $visit->id) }}"
                                                            class="dropdown-item">
                                                            <i class="tio-receipt-outlined mr-2 text-primary"></i> Generate Bill
                                                        </a>
                                                    @endif
                                                    @if (!$visit->is_cancelled && hasPermission('opd_register', 'edit'))
                                                        <button type="button" class="dropdown-item text-info" data-toggle="modal" data-target="#rescheduleOpdModal{{ $visit->id }}">
                                                            <i class="tio-time mr-2"></i> Reschedule visit
                                                        </button>
                                                    @endif
                                                    @if ($canCancel)
                                                        <button type="button" class="dropdown-item text-danger"
                                                            onclick="opdOpenCancel({{ $visit->id }}, '{{ $visit->token_number }}', @js($visit->patient?->name ?? 'this patient'))">
                                                            <i class="tio-clear-circle mr-2"></i> Cancel visit
                                                        </button>
                                                    @endif
                                                    @if ($canDelete)
                                                        {{-- The register's filters ride on the action URL so the redirect
                                                             afterwards lands back on the same date range and search. --}}
                                                        <form method="POST" action="{{ route('vendor.opd.destroy', ['id' => $visit->id] + request()->query()) }}"
                                                            onsubmit="return confirm('Delete this visit outright? This cannot be undone. If anything has been recorded against it, cancel it instead.')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="tio-delete mr-2"></i> Delete visit
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    {{-- Reschedule Modal for this visit --}}
                                    @if (!$visit->is_cancelled && hasPermission('opd_register', 'edit'))
                                    <div class="modal fade" id="rescheduleOpdModal{{ $visit->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content" style="border-radius:12px;">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" style="font-family:'Outfit',sans-serif;font-weight:700;">
                                                        <i class="tio-time text-info mr-1"></i> Reschedule OPD Visit #{{ $visit->token_number }}
                                                    </h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                </div>
                                                <form action="{{ route('vendor.opd.reschedule', $visit->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body text-left">
                                                        <p class="small text-muted mb-3">Patient: <strong>{{ $visit->patient?->name }}</strong> ({{ $visit->patient?->patient_uid }})</p>
                                                        <div class="form-group">
                                                            <label class="input-label font-weight-bold">New Visit Date <span class="text-danger">*</span></label>
                                                            <input type="date" name="visit_date" class="form-control" value="{{ $visit->visit_date?->format('Y-m-d') }}" min="{{ now()->toDateString() }}" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="input-label font-weight-bold">New Visit Time</label>
                                                            <input type="time" name="visit_time" class="form-control" value="{{ $visit->visit_time ? \Carbon\Carbon::parse($visit->visit_time)->format('H:i') : '' }}">
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="input-label font-weight-bold">Reason / Note (Optional)</label>
                                                            <textarea name="reason" class="form-control" rows="2" placeholder="e.g. Patient requested time change"></textarea>
                                                        </div>
                                                        <div class="alert alert-soft-info py-2 px-3 small mb-0">
                                                            <i class="tio-info-outlined"></i> Rescheduling will send a WhatsApp/SMS notification to the patient automatically.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-info"><i class="tio-send"></i> Reschedule &amp; Notify Patient</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $showVitals ? 9 : 8 }}" class="text-center text-muted py-4">No OPD visits recorded for this date.</td>
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

    {{-- One modal for the whole table, retargeted per row — a modal per visit would put a
         hidden form in the DOM for every line on the page. --}}
    <div class="modal fade" id="opdCancelModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:12px;">
                <form method="POST" id="opdCancelForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" style="font-family:'Outfit',sans-serif;font-weight:700;">Cancel OPD visit</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3" style="font-size:13px;color:#475569;" id="opdCancelSummary"></p>
                        <label class="form-label" style="font-size:13px;font-weight:600;">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="cancel_reason" id="opdCancelReason" class="form-control hmis-input"
                               maxlength="255" required placeholder="e.g. Patient left without consulting">
                        <div class="mt-3" style="font-size:12px;color:#64748b;">
                            The token stays on the register and the visit drops out of the day's counts.
                            Any linked appointment is cancelled with it.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Keep visit</button>
                        <button type="submit" class="btn btn-sm btn-danger">Cancel visit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
    <script>
        function opdOpenCancel(visitId, token, patientName) {
            var form = document.getElementById('opdCancelForm');
            // Built by the js directive rather than a plain echo: the filters make this a
            // multi-parameter URL, and Blade's HTML escaping would leave a literal &amp; in the
            // string — entities are not decoded inside a script block.
            form.action = @js(route('vendor.opd.cancel', ['id' => '__ID__'] + request()->query())).replace('__ID__', visitId);
            document.getElementById('opdCancelSummary').textContent =
                'Token #' + token + ' — ' + patientName + '. This marks the visit as not having happened.';
            document.getElementById('opdCancelReason').value = '';
            $('#opdCancelModal').modal('show');
        }
    </script>
@endpush
