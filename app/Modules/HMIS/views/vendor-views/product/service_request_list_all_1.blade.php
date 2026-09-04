@extends('layouts.vendor.app')

@section('title', $type . (_isHospital() ? 'Appointments' : ' Leads'))

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* ── Reset & Base ───────────────────────────────────── */
        .leads-page * { box-sizing: border-box; }
        .leads-page { font-family: 'Plus Jakarta Sans', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 28px 24px; }

        /* ── Page Header ───────────────────────────────────── */
        .leads-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .leads-header h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .leads-header h1 .count-badge {
            font-size: 13px;
            font-weight: 700;
            background: #1e293b;
            color: #fff;
            border-radius: 99px;
            padding: 2px 11px;
        }
        .leads-header-sub {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 3px;
        }
        .leads-header-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        /* ── Stat Cards ────────────────────────────────────── */
        .stat-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .stat-bar::-webkit-scrollbar { height: 4px; }
        .stat-bar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
        .stat-card {
            flex-shrink: 0;
            padding: 10px 18px;
            border-radius: 12px;
            border: 2px solid transparent;
            background: #fff;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            transition: all 0.18s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.07);
        }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-decoration: none; }
        .stat-card.active { background: #0f766e; border-color: #0f766e; box-shadow: 0 4px 14px rgba(15,118,110,0.35); }
        .stat-card .stat-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; }
        .stat-card.active .stat-label { color: rgba(255,255,255,0.75); }
        .stat-card .stat-num { font-size: 24px; font-weight: 800; color: #0f172a; line-height: 1.1; }
        .stat-card.active .stat-num { color: #fff; }

        /* ── Filter Bar ────────────────────────────────────── */
        .filter-bar {
            background: #fff;
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            border: 1px solid #e8edf2;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            flex-wrap: wrap;
        }
        .filter-bar .search-wrap {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        .filter-bar .search-wrap .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
            pointer-events: none;
        }
        .filter-bar input[type="search"] {
            width: 100%;
            padding: 9px 12px 9px 36px;
            border-radius: 8px;
            border: 1px solid #c8d2e0;
            font-size: 13px;
            font-family: inherit;
            color: #0f172a;
            background: #f8fafc;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .filter-bar input[type="search"]:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15,118,110,0.12);
        }
        .filter-bar select {
            padding: 9px 14px;
            border-radius: 8px;
            border: 1px solid #c8d2e0;
            background: #f8fafc;
            font-size: 13px;
            font-family: inherit;
            font-weight: 600;
            color: #475569;
            outline: none;
            cursor: pointer;
        }
        .filter-bar .btn-date {
            padding: 9px 14px;
            border-radius: 8px;
            border: 1px solid #c8d2e0;
            background: #f8fafc;
            font-size: 13px;
            font-family: inherit;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            white-space: nowrap;
        }

        /* ── Results Label ─────────────────────────────────── */
        .results-label {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 600;
            margin-bottom: 14px;
            padding-left: 2px;
        }

        /* ── Lead Cards Grid ───────────────────────────────── */
        .leads-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        @media (max-width: 1200px) { .leads-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 700px)  { .leads-grid { grid-template-columns: 1fr; } }

        /* ── Lead Card ─────────────────────────────────────── */
        .lead-card {
            height: 100%;
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e8edf2;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.2s, transform 0.15s, border-color 0.2s;
        }
        .lead-card.clickable { cursor: pointer; }
        .lead-card.clickable:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.10);
            transform: translateY(-2px);
        }
        .lead-card-strip { height: 3px; opacity: 0.8; }
        .lead-card-body { padding: 16px 18px; flex: 1; display: flex; flex-direction: column; gap: 10px; }

        /* Strip + badge colors by status */
        .lead-card.status-new    .lead-card-strip { background: #22c55e; }
        .lead-card.status-accepted .lead-card-strip { background: #f59e0b; }
        .lead-card.status-confirmed .lead-card-strip { background: #3b82f6; }
        .lead-card.status-completed .lead-card-strip { background: #16a34a; }
        .lead-card.status-cancelled .lead-card-strip { background: #ef4444; }
        .lead-card.status-missed  .lead-card-strip { background: #9ca3af; }
        .lead-card.status-in_progress .lead-card-strip { background: #8b5cf6; }
        .lead-card.status-unassigned .lead-card-strip { background: #f59e0b; }
        .lead-card.status-alotted .lead-card-strip { background: #0ea5e9; }
        .lead-card.status-confirmation_request_sent .lead-card-strip { background: #a855f7; }

        /* ── Card Header ── */
        .card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .card-service-id { font-size: 10px; font-weight: 600; color: #94a3b8; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 3px; }
        .card-service-name { font-size: 14px; font-weight: 700; color: #0f172a; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* ── Dropdown menu ── */
        .card-menu { position: relative; flex-shrink: 0; }
        .card-menu-btn {
            border: 1px solid #c8d2e0;
            background: transparent;
            border-radius: 7px;
            width: 30px; height: 30px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: #64748b; font-weight: 700;
            transition: all 0.15s; line-height: 1;
            font-family: inherit;
        }
        .card-menu-btn:hover { background: #f8fafc; }
        .card-menu .dropdown-menu {
            min-width: 170px;
            border-radius: 10px;
            border: 1px solid #c8d2e0;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            padding: 4px 0;
            top: 36px; right: 0;
        }
        .card-menu .dropdown-item {
            font-size: 13px;
            font-weight: 500;
            padding: 9px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }
        .card-menu .dropdown-item:hover { background: #f8fafc; }

        /* ── Client row ── */
        .card-client { display: flex; align-items: center; gap: 9px; }
        .client-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: #0f766e; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 800; flex-shrink: 0;
        }
        .client-name { font-size: 12px; font-weight: 600; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .client-phone { font-size: 11px; color: #64748b; }
        .client-unknown { font-size: 12px; font-weight: 500; color: #94a3b8; font-style: italic; }

        /* ── Date row ── */
        .card-date { font-size: 11px; color: #94a3b8; display: flex; align-items: center; gap: 5px; }

        /* ── Assigned row ── */
        .card-assigned {
            font-size: 11px; color: #64748b;
            display: flex; align-items: center; gap: 5px;
            background: #f8fafc; border-radius: 6px; padding: 4px 8px;
        }
        .card-assigned strong { font-weight: 600; }

        /* ── Card Footer ── */
        .card-footer {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: auto; padding-top: 10px;
            border-top: 1px solid #dfe6ee;
        }

        /* ── Status Badges ── */
        .status-pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 99px;
            font-size: 11px; font-weight: 700; letter-spacing: 0.03em;
        }
        .status-pill .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

        .pill-new    { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .pill-new    .dot { background: #22c55e; }
        .pill-accepted { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .pill-accepted .dot { background: #f59e0b; }
        .pill-confirmed { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .pill-confirmed .dot { background: #3b82f6; }
        .pill-completed { background: #f0fdf4; color: #166534; border: 1px solid #86efac; }
        .pill-completed .dot { background: #16a34a; }
        .pill-cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .pill-cancelled .dot { background: #ef4444; }
        .pill-missed { background: #f9fafb; color: #6b7280; border: 1px solid #c8d2e0; }
        .pill-missed .dot { background: #9ca3af; }
        .pill-in_progress { background: #faf5ff; color: #7c3aed; border: 1px solid #ddd6fe; }
        .pill-in_progress .dot { background: #8b5cf6; }
        .pill-confirmation_request_sent { background: #fdf4ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .pill-confirmation_request_sent .dot { background: #a855f7; }
        .pill-unassigned { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .pill-unassigned .dot { background: #f59e0b; }
        .pill-alotted { background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; }
        .pill-alotted .dot { background: #0ea5e9; }

        /* ── Action Buttons ── */
        .btn-accept {
            padding: 5px 13px; border-radius: 8px; border: none;
            background: #0f766e; color: #fff;
            font-size: 12px; font-weight: 700; cursor: pointer;
            font-family: inherit; display: inline-flex; align-items: center; gap: 5px;
            transition: opacity 0.15s;
        }
        .btn-accept:hover { opacity: 0.85; color: #fff; text-decoration: none; }

        .btn-send-req {
            padding: 5px 11px; border-radius: 8px;
            border: 1px solid #0f766e; background: transparent;
            color: #0f766e; font-size: 11px; font-weight: 700;
            cursor: pointer; font-family: inherit;
            transition: all 0.15s;
        }
        .btn-send-req:hover { background: #0f766e; color: #fff; text-decoration: none; }

        .btn-bill {
            padding: 5px 11px; border-radius: 8px;
            border: 1px solid #0369a1; background: transparent;
            color: #0369a1; font-size: 11px; font-weight: 700;
            cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; gap: 5px;
        }
        .btn-bill:hover { background: #0369a1; color: #fff; text-decoration: none; }

        .btn-assign {
            padding: 5px 11px; border-radius: 8px;
            border: 1px solid #7c3aed; background: transparent;
            color: #7c3aed; font-size: 11px; font-weight: 700;
            cursor: pointer; font-family: inherit;
        }
        .btn-assign:hover { background: #7c3aed; color: #fff; text-decoration: none; }

        /* ── Btn overrides ── */
        .btn-leads-primary {
            padding: 9px 18px; border-radius: 9px; border: none;
            background: #0f766e; color: #fff;
            font-size: 13px; font-weight: 700; font-family: inherit;
            cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
            transition: opacity 0.15s;
        }
        .btn-leads-primary:hover { opacity: 0.88; color: #fff; text-decoration: none; }
        .btn-leads-outline {
            padding: 9px 14px; border-radius: 9px;
            border: 1px solid #c8d2e0; background: #fff;
            font-size: 13px; font-weight: 600; color: #475569; font-family: inherit;
            cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-leads-outline:hover { background: #f8fafc; color: #334155; text-decoration: none; }

        /* ── Modals ── */
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.18); font-family: 'Plus Jakarta Sans', sans-serif; }
        .modal-header { border-bottom: 1px solid #dfe6ee; padding: 18px 24px 14px; }
        .modal-title { font-size: 17px; font-weight: 800; color: #0f172a; }
        .modal-body { padding: 22px 24px; background: #f8fafc; }
        .modal-footer { border-top: none; padding: 0 24px 20px; background: #f8fafc; }
        .modal-subtitle { font-size: 11px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 2px; }

        .client-info-box {
            background: #fff; padding: 16px; border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05); margin-bottom: 16px;
        }
        .client-info-box .avatar-lg {
            width: 44px; height: 44px; border-radius: 50%;
            background: #0f766e; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; font-weight: 800; flex-shrink: 0;
        }
        .contact-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 6px; border-radius: 6px; transition: background 0.15s;
        }
        .contact-item:hover { background: #f8fafc; }
        .contact-item i { font-size: 18px; color: #0f766e; }
        .contact-item small { display: block; font-size: 11px; color: #94a3b8; }
        .contact-item a, .contact-item span { font-size: 13px; font-weight: 600; color: #1e293b; }
        .contact-item .copy-btn {
            border: 1px solid #c8d2e0; background: #fff;
            padding: 5px 9px; border-radius: 6px; cursor: pointer;
            transition: all 0.2s; font-size: 12px;
        }
        .contact-item .copy-btn:hover { background: #0f766e; color: #fff; border-color: #0f766e; }

        .action-form { background: #fff; padding: 18px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
        .action-form label { font-size: 12px; font-weight: 700; color: #475569; }
        .action-form .input-group-text { border-radius: 8px 0 0 8px; }
        .action-form .form-control { border-radius: 0 8px 8px 0; }

        .status-box {
            background: #fff; padding: 16px; border-radius: 10px;
            border-left: 4px solid #22c55e;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .status-box i { font-size: 28px; color: #22c55e; }

        /* ── Empty state ── */
        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-state .empty-icon { font-size: 40px; margin-bottom: 12px; }
        .empty-state h4 { font-size: 16px; font-weight: 700; color: #64748b; }
        .empty-state p { font-size: 13px; margin-top: 4px; }
    </style>
@endpush

@section('content')
    @php
        $statusCounts = [
            'new' => 0,
            'accepted' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'missed' => 0,
        ];
    @endphp

    <div class="leads-page">

        {{-- ── Page Header ────────────────────────────────────── --}}
        <div class="leads-header">
            <div>
                <div class="leads-header-sub">Vendor Portal</div>
                <h1>
                    {{ $type . (_isHospital() ? 'Appointments' : ' Leads') }}
                    <span class="count-badge" id="itemCount">{{ count($product) }}</span>
                </h1>
            </div>
            <div class="leads-header-actions">
                <button onclick="window.location.reload()" class="btn-leads-outline" title="Refresh">
                    ↺ Refresh
                </button>
                @if (hasPermission('leads_manage', 'statuses') && !$empId)
                    <button type="button" class="btn-leads-primary" data-toggle="modal" data-target="#sttsMOdal">
                        ⚙ Available Statuses
                    </button>
                @endif
            </div>
        </div>

        {{-- ── Stat Cards ─────────────────────────────────────── --}}
        @if (hasPermission('leads_manage', 'list'))
            <div class="stat-bar" id="statusCards"></div>
        @endif

        {{-- ── Filter Bar ──────────────────────────────────────── --}}
        @if (!$empId && hasPermission('leads_manage', 'list'))
            <div class="filter-bar">
                <form action="" class="search-wrap" style="margin:0;">
                    {{-- carry other query params --}}
                    @if(request('type')) <input type="hidden" name="type" value="{{ request('type') }}"> @endif
                    @if(request('date_range')) <input type="hidden" name="date_range" value="{{ request('date_range') }}"> @endif
                    @if(request('custom_date_range')) <input type="hidden" name="custom_date_range" value="{{ request('custom_date_range') }}"> @endif
                    <span class="search-icon">🔍</span>
                    <input type="search" name="search" value="{{ request()?->search ?? null }}"
                        placeholder="Search by service name…">
                </form>

                <form action="" style="margin:0;">
                    @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                    @if(request('date_range')) <input type="hidden" name="date_range" value="{{ request('date_range') }}"> @endif
                    <select name="type" onchange="this.form.submit()" class="filter-bar" style="padding:9px 14px; border-radius:8px; border:1px solid #c8d2e0; background:#f8fafc; font-size:13px; font-weight:600; color:#475569; outline:none;">
                        <option {{ $type == 'All'       ? 'selected' : '' }} value="All">All</option>
                        <option {{ $type == 'New'       ? 'selected' : '' }} value="New">New</option>
                        <option {{ $type == 'Accepted'  ? 'selected' : '' }} value="Accepted">Accepted</option>
                        <option {{ $type == 'Cancelled' ? 'selected' : '' }} value="Cancelled">Cancelled</option>
                        <option {{ $type == 'Completed' ? 'selected' : '' }} value="Completed">Completed</option>
                    </select>
                </form>

                <button type="button" class="btn-date" data-toggle="modal" data-target="#dateRangeModal">
                    📅 {{ translate($preset) }}
                </button>
                @include('vendor-views/form_modals/date_range')
            </div>
        @endif

        {{-- ── Results label ───────────────────────────────────── --}}
        @if (hasPermission('leads_manage', 'list'))
            <div class="results-label">
                Showing {{ count($product) }} lead{{ count($product) != 1 ? 's' : '' }}
                @if ($type != 'All') &middot; {{ $type }} @endif
                @if(request('search')) &middot; matching "{{ request('search') }}" @endif
            </div>
        @endif

        {{-- ── Leads Grid ──────────────────────────────────────── --}}
        @if (hasPermission('leads_manage', 'list'))
            @if(count($product) === 0)
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <h4>No leads found</h4>
                    <p>Try adjusting your filters or date range.</p>
                </div>
            @else
                <div class="leads-grid">
                    @foreach ($product as $key => $lead)
                        @php
                            $status        = $lead->current_status;
                            $invoiceStatus = _serviceInvoiceStatus($lead->id);
                            $isCompleted   = $status === 'Completed';
                            $isCancelled   = _isCancelled($lead->id);
                            $isConfirmed   = $status === 'Confirmed';
                            $canViewDetails= ($isConfirmed || $isCancelled || $isCompleted) && $lead->status != 'cancelled';
                            $isConfirmed2  = $status === 'Confirmed' || $canViewDetails;
                            $isAcceptedReq = _acceptedReq($lead->id);
                            $canAccept     = !isset($lead->additional_status) || $lead->additional_status !== 'missed';
                            $currentServiceStatus = _getCurrentServiceStatus($lead->id);
                            $isMissed      = isset($lead->additional_status) && $lead->additional_status == 'missed';
                            $isClickable   = !$isMissed && $lead->status != 'new' && !(!$canViewDetails && !$isCancelled && !$isCompleted && !$isAcceptedReq);

                            $class = \Illuminate\Support\Str::slug(
                                strtolower($lead->current_status ?? ($lead->additional_status ?? $lead->assigned_status)),
                                '_'
                            );
                            if ($isCancelled) { $class = 'cancelled'; }
                            if ($class == '')  { $class = 'new'; }

                            $user_details = _getUserDetails($lead->uid);
                            $serviceNum   = 'SRV-' . str_pad($lead->id, 4, '0', STR_PAD_LEFT);
                        @endphp

                        <div class="lead-card status-{{ $class }} {{ $isClickable ? 'clickable' : '' }}"
                            @if ($isClickable) onclick="handleClick('{{ route('vendor.service.lead-details', [$lead->id]) }}', event)" @endif>

                            <div class="lead-card-strip"></div>
                            <div class="lead-card-body">

                                {{-- Head: ID + Name + Menu --}}
                                <div class="card-head">
                                    <div style="flex:1; min-width:0;">
                                        <div class="card-service-id">{{ $serviceNum }}</div>
                                        <div class="card-service-name" title="{{ $lead->item_name }}">{{ $lead->item_name }}</div>
                                    </div>

                                    {{-- Dropdown menu (only when not new/missed) --}}
                                    @if (!$isMissed && $lead->status != 'new' && !(isset($lead->additional_status) && $lead->additional_status == 'missed'))
                                        <div class="card-menu" onclick="event.stopPropagation()">
                                            <button class="card-menu-btn dropdown-toggle" data-toggle="dropdown" aria-expanded="false" style="letter-spacing:2px;">···</button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                @if ($isCompleted)
                                                    @if (in_array($invoiceStatus, ['new', 'editable']))
                                                        <a href="{{ route('vendor.business-settings.generate-bill', [$lead->id]) }}"
                                                            class="dropdown-item {{ $invoiceStatus === 'new' ? 'text-primary' : 'text-warning' }}">
                                                            <i class="fas fa-file-invoice"></i>
                                                            {{ $invoiceStatus === 'new' ? 'Generate' : 'Edit' }} Bill
                                                        </a>
                                                    @else
                                                        <a target="_blank"
                                                            href="{{ asset('storage/app/public/invoice/' . $invoiceStatus) }}"
                                                            class="dropdown-item text-success">
                                                            <i class="tio-document-outlined"></i> View Bill
                                                        </a>
                                                    @endif
                                                @endif

                                                @if ($canViewDetails)
                                                    <a href="{{ route('vendor.service.lead-details', [$lead->id]) }}"
                                                        class="dropdown-item text-primary">
                                                        <i class="tio-visible-outlined"></i> View Details
                                                    </a>
                                                @endif

                                                @if ($isConfirmed2 && !$isCompleted && !$isCancelled)
                                                    @if (hasPermission('leads_manage', 'cancel'))
                                                        <a onclick="cancelLead({{ $lead->id }}, {{ $lead->acc_id }})"
                                                            class="dropdown-item text-danger" style="cursor:pointer;">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </a>
                                                    @endif
                                                @endif

                                                @if ($lead->current_status === 'Confirmed' && $lead->assigned_status !== 'Unassigned' && $lead->assigned_type === 'staff' && isset($lead->assigned_to))
                                                    <a href="{{ route('vendor.track-location', [$lead->assigned_to]) }}"
                                                        target="_blank" class="dropdown-item text-primary">
                                                        <i class="tio-location-search"></i> Track Location
                                                    </a>
                                                @endif

                                                @if (_isHospital() && ($isConfirmed || $isCompleted))
                                                    @php $leadRx = \App\Models\Prescription::where('service_request_id', $lead->id)->first(); @endphp
                                                    @if ($leadRx)
                                                        <a href="{{ route('vendor.prescription.show', $leadRx->id) }}" class="dropdown-item text-success">
                                                            <i class="tio-print"></i> View Prescription
                                                        </a>
                                                        @if (!$leadRx->is_finalized)
                                                            <a href="{{ route('vendor.prescription.edit', $leadRx->id) }}" class="dropdown-item text-primary">
                                                                <i class="tio-edit"></i> Edit Prescription
                                                            </a>
                                                        @endif
                                                    @else
                                                        <a href="{{ route('vendor.prescription.create', ['service_request_id' => $lead->id]) }}" class="dropdown-item text-success">
                                                            <i class="tio-medicine"></i> Write Prescription
                                                        </a>
                                                    @endif
                                                @endif

                                                @if (!$canViewDetails && $isAcceptedReq)
                                                    <a href="#" class="dropdown-item text-primary"
                                                        data-toggle="modal" data-target="#userModal-{{ $lead->id }}">
                                                        <i class="fas fa-user"></i> User Details
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Client info --}}
                                <div class="card-client">
                                    @if ($isAcceptedReq && $user_details)
                                        <div class="client-avatar" style="background: {{ collect(['#0f766e','#0369a1','#7c3aed','#b45309','#be185d'])->get(crc32($user_details->f_name) % 5, '#0f766e') }}">
                                            {{ strtoupper(substr($user_details->f_name, 0, 1) . substr($user_details->l_name, 0, 1)) }}
                                        </div>
                                        <div style="flex:1; min-width:0;">
                                            <div class="client-name">{{ $user_details->f_name . ' ' . $user_details->l_name }}</div>
                                            <div class="client-phone">{{ $user_details->phone }}</div>
                                        </div>
                                    @else
                                        <div class="client-avatar" style="background:#e2e8f0; color:#94a3b8; font-size:14px;">?</div>
                                        <div class="client-unknown">Client not revealed yet</div>
                                    @endif
                                </div>

                                {{-- Date --}}
                                <div class="card-date">
                                    🕐 {{ $lead->created_at }}
                                </div>

                                {{-- Assigned staff --}}
                                @if ($lead->assigned_status == 'Assigned' && isset($lead->assigned_to))
                                    @php $empInfo = _getWhereOne('vendor_employees', ['id' => $lead->assigned_to]); @endphp
                                    @if ($empInfo)
                                        <div class="card-assigned">
                                            👤 <strong>{{ $empInfo->f_name . ' ' . $empInfo->l_name }}</strong>
                                            <span style="color:#94a3b8; font-size:10px;">
                                                ({{ !$lead->accepted_by_staff ? 'Pending' : ($lead->accepted_by_staff == 2 ? 'Rejected' : 'Accepted') }})
                                            </span>
                                        </div>
                                    @endif
                                @endif

                                {{-- Footer: badge + action --}}
                                <div class="card-footer">
                                    {{-- Status badge --}}
                                    @if ($isMissed)
                                        <span class="status-pill pill-missed"><span class="dot"></span> Missed</span>
                                        @php addStatus($statusCounts, 'missed'); @endphp
                                    @elseif ($isCompleted)
                                        <span class="status-pill pill-completed"><span class="dot"></span> Completed</span>
                                        @php addStatus($statusCounts, 'completed'); @endphp
                                    @elseif ($isCancelled)
                                        <span class="status-pill pill-cancelled"><span class="dot"></span> Cancelled</span>
                                        @php addStatus($statusCounts, 'cancelled'); @endphp
                                    @elseif ($lead->current_status == 'Confirmation Request Sent')
                                        <span class="status-pill pill-confirmation_request_sent"><span class="dot"></span> Confirmation Sent</span>
                                        @php addStatus($statusCounts, 'new'); @endphp
                                    @elseif ($lead->current_status == 'Confirmed' || $lead->assigned_status == 'Assigned' || $lead->assigned_status == 'Unassigned')
                                        @if ($lead->assigned_status == 'Unassigned')
                                            <span class="status-pill pill-unassigned"><span class="dot"></span> Unassigned</span>
                                            @php addStatus($statusCounts, 'unassigned'); @endphp
                                        @else
                                            <span class="status-pill pill-alotted"><span class="dot"></span> Assigned{{ $lead->assigned_type == 'vendor' ? ' (Self)' : '' }}</span>
                                            @php addStatus($statusCounts, 'alotted'); @endphp
                                        @endif
                                    @elseif ($lead->current_status != '')
                                        <span class="status-pill pill-{{ $class }}"><span class="dot"></span> {{ $lead->current_status }}</span>
                                        @php addStatus($statusCounts, strtolower($lead->current_status)); @endphp
                                    @elseif ($lead->status != '')
                                        <span class="status-pill pill-{{ $class }}"><span class="dot"></span> {{ ucfirst($lead->status) }}</span>
                                        @php addStatus($statusCounts, $lead->status); @endphp
                                    @endif

                                    {{-- Action buttons --}}
                                    @if (!$isMissed)
                                        @if (!$canViewDetails && !$isCancelled && !$isCompleted)
                                            @if ($isAcceptedReq)
                                                <a class="btn-send-req" onclick="event.stopPropagation()" data-toggle="modal" data-target="#userModal-{{ $lead->id }}">
                                                    Send Request →
                                                </a>
                                                @php addStatus($statusCounts, 'accepted'); @endphp
                                            @elseif (hasPermission('leads_manage', 'accept') && $canAccept && $lead->status == 'new')
                                                <a href="{{ route('vendor.service.accept', [$lead->id]) }}"
                                                    class="btn-accept" onclick="event.stopPropagation()">
                                                    ✓ Accept
                                                </a>
                                                @php addStatus($statusCounts, 'new'); @endphp
                                            @endif
                                        @endif

                                        @if ($isCompleted && hasPermission('leads_manage', 'edit') && in_array($invoiceStatus, ['new', 'editable']))
                                            <a href="{{ route('vendor.business-settings.generate-bill', [$lead->id]) }}"
                                                class="btn-bill" onclick="event.stopPropagation()">
                                                🧾 {{ $invoiceStatus === 'new' ? 'Generate Bill' : 'Edit Bill' }}
                                            </a>
                                        @elseif ($isCompleted && hasPermission('leads_manage', 'invoice'))
                                            <a target="_blank" href="{{ asset('storage/app/public/invoice/' . $invoiceStatus) }}"
                                                class="btn-bill" onclick="event.stopPropagation()">
                                                🧾 View Bill
                                            </a>
                                        @endif

                                        @if (hasPermission('leads_manage', 'alot') && ($lead->current_status == 'Confirmed' || $lead->assigned_status == 'Assigned' || $lead->assigned_status == 'Unassigned') && !$isCompleted && !$isCancelled)
                                            <a type="button" onclick="event.stopPropagation()" data-toggle="modal"
                                                data-target="#assignModal{{ $key }}"
                                                class="btn-assign">
                                                {{ $lead->assigned_status == 'Unassigned' ? '+ Assign' : '✎ Reassign' }}
                                            </a>
                                        @endif
                                    @endif
                                </div>

                            </div>{{-- /card-body --}}
                        </div>{{-- /lead-card --}}

                        {{-- ── Assign Modal ── --}}
                        <div class="modal fade" id="assignModal{{ $key }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div>
                                            <div class="modal-subtitle">Service Request</div>
                                            <h5 class="modal-title">Assign Staff</h5>
                                        </div>
                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                    </div>
                                    <div class="modal-body">
                                        @if ($lead->assigned_status == 'Unassigned' || $lead->assigned_type == 'vendor')
                                            @if ($lead->assigned_type == 'vendor')
                                                <div style="background:#f0fdf4; border-radius:8px; padding:10px 14px; font-size:13px; font-weight:600; color:#166534; margin-bottom:14px;">
                                                    Currently assigned to: Self (Vendor)
                                                </div>
                                            @endif
                                            <form action="{{ route('vendor.service.save-assignment') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="service_id" value="{{ $lead->id }}">
                                                <input type="hidden" name="id" value="{{ $lead->acc_id }}">
                                                <div class="form-group">
                                                    <label style="font-size:12px; font-weight:700; color:#475569;">
                                                        {{ $lead->assigned_status == 'Assigned' ? 'Reassign' : 'Assign' }} To
                                                    </label>
                                                    <select name="staff_id" class="form-control js-select2-custom" style="border-radius:8px; font-family:inherit;">
                                                        <option></option>
                                                        <option value="vendor">Self (Vendor)</option>
                                                        @foreach ($allStaff as $staff)
                                                            <option value="{{ $staff->id }}">{{ $staff->f_name . ' ' . $staff->l_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button class="btn-leads-primary" type="submit">Assign Staff</button>
                                            </form>
                                        @else
                                            @php $empInfo = _getWhereOne('vendor_employees', ['id' => $lead->assigned_to]); @endphp
                                            @if ($empInfo)
                                                <div style="background:#f8fafc; border-radius:10px; padding:14px; font-size:14px;">
                                                    <strong>Assigned To:</strong>
                                                    {{ $empInfo->f_name . ' ' . $empInfo->l_name }} #{{ $lead->assigned_to }}
                                                    <span style="color:#64748b;">
                                                        ({{ !$lead->accepted_by_staff ? 'Acceptance Pending' : ($lead->accepted_by_staff == 2 ? 'Rejected' : 'Accepted') }})
                                                    </span>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ── User Details / Confirmation Modal ── --}}
                        @if ($user_details)
                            <div class="modal fade" id="userModal-{{ $lead->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div>
                                                <div class="modal-subtitle">{{ $serviceNum }}</div>
                                                <h5 class="modal-title">{{ $lead->item_name }}</h5>
                                            </div>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="client-info-box">
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="avatar-lg mr-3">
                                                        {{ strtoupper(substr($user_details->f_name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <h6 style="font-weight:800; margin:0;">{{ $user_details->f_name . ' ' . $user_details->l_name }}</h6>
                                                        <small style="color:#94a3b8;">Customer Details</small>
                                                    </div>
                                                </div>
                                                <div class="contact-item">
                                                    <i class="tio-email"></i>
                                                    <div>
                                                        <small>Email</small>
                                                        <a href="mailto:{{ $user_details->email }}">{{ $user_details->email }}</a>
                                                    </div>
                                                </div>
                                                <div class="contact-item">
                                                    <i class="tio-call"></i>
                                                    <div class="flex-grow-1">
                                                        <small>Mobile</small>
                                                        <span class="textToCopy">{{ $user_details->phone }}</span>
                                                    </div>
                                                    <button class="copy-btn"><i class="tio-copy"></i></button>
                                                </div>
                                            </div>

                                            @if (!_getCurrentServiceStatus($lead->id))
                                                @if (hasPermission('leads_manage', 'send_confirmation_request'))
                                                    <form action="{{ route('vendor.service.send-confirmation-notification', ['id' => $lead->id]) }}" class="action-form">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $lead->id }}">
                                                        <div class="form-group">
                                                            <label for="lead_price_{{ $lead->id }}">Visiting Charges</label>
                                                            <div class="input-group mt-1">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="tio-money"></i></span>
                                                                </div>
                                                                <input type="number" name="price" id="lead_price_{{ $lead->id }}"
                                                                    class="form-control" placeholder="Enter amount" required>
                                                            </div>
                                                        </div>
                                                        <button type="submit" class="btn-leads-primary" style="width:100%;">
                                                            ✓ Send Confirmation Request
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <div class="status-box">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <i class="tio-checkmark-circle-outlined"></i>
                                                            <div>
                                                                <small style="color:#94a3b8; display:block;">Status</small>
                                                                <strong>{{ _getCurrentServiceStatus($lead->id) }}</strong>
                                                            </div>
                                                        </div>
                                                        @if (_getCurrentServiceStatus($lead->id) == 'Confirmed')
                                                            <a href="{{ route('vendor.service.cancel', [$lead->id]) }}"
                                                                class="btn btn-outline-danger btn-sm">
                                                                <i class="tio-clear"></i> Cancel
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn-leads-outline" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @endforeach
                </div>{{-- /leads-grid --}}
            @endif
        @endif{{-- /hasPermission --}}

        {{-- ── Available Statuses Modal ─────────────────────── --}}
        @if (hasPermission('leads_manage', 'statuses'))
            <div class="modal fade" id="sttsMOdal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Available Statuses</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <b>Default Statuses:</b>
                            @foreach ($default_statuses as $stts)
                                <span class="badge badge-light ml-1">{{ $stts->status }}</span>
                            @endforeach

                            @if (count($approval_pending))
                                <br><br><b>Approval Pending:</b>
                                @foreach ($approval_pending as $stts)
                                    <span class="badge badge-light ml-1">{{ $stts->serviceStatus->status }}</span>
                                @endforeach
                            @endif

                            <form action="{{ route('vendor.business-settings.update-statuses') }}" method="post" class="mt-3">
                                @csrf
                                <div class="form-group">
                                    <label style="font-size:12px; font-weight:700; color:#475569;">Selected Statuses</label>
                                    <select name="statuses[]" multiple="multiple"
                                        class="form-control js-select2-custom js-example-basic-multiple"
                                        data-placeholder="Select statuses">
                                        <option value=""></option>
                                        @foreach ($statuses as $sc)
                                            <option {{ in_array($sc->id, explode(',', $store_data->lead_statuses)) ? 'selected' : '' }}
                                                value="{{ $sc->id }}">{{ $sc->status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn-leads-primary" type="submit">Update Statuses</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>{{-- /leads-page --}}
@endsection

@push('script_2')
    <script>
        function handleClick(url, e) {
            if ($(e.target).closest('.card-menu, .dropdown-menu, .copy-btn, .dropdown-toggle, button, a').length) {}
            else { window.location.href = url; }
        }

        let statusCounts = @json($statusCounts);
        let leadCounts   = @json($product).length;

        function capitalize(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        $(document).ready(function () {
            let container = $("#statusCards");

            // All card
            container.append(`
                <a href="{{ route('vendor.service.leads_list') }}?type=All"
                   class="stat-card {{ $type == 'All' ? 'active' : '' }}">
                    <div class="stat-label">All</div>
                    <div class="stat-num">${leadCounts}</div>
                </a>
            `);

            $.each(statusCounts, function (status, count) {
                if (count === 0) return;
                let currentType = '{{ strtolower($type) }}';
                let isActive    = currentType === status.toLowerCase();
                container.append(`
                    <a href="{{ route('vendor.service.leads_list') }}?type=${capitalize(status)}"
                       class="stat-card ${isActive ? 'active' : ''}">
                        <div class="stat-label">${status}</div>
                        <div class="stat-num">${count}</div>
                    </a>
                `);
            });
        });

        // Cancel Lead
        function cancelLead(serviceId, accId) {
            Swal.fire({
                title: 'Cancel this lead?',
                text: 'This action cannot be undone.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonText: 'Go Back',
                confirmButtonText: 'Yes, Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
                    $.post({
                        url: '{{ route('vendor.service.cancel') }}',
                        data: { service_id: serviceId, id: accId },
                        beforeSend: function () { $('#loading').show(); },
                        success: function (data) {
                            if (data.status) { toastr.success(data.message); }
                            else             { toastr.error(data.message); }
                            setTimeout(() => window.location.reload(), 1000);
                        },
                        complete: function () { $('#loading').hide(); }
                    });
                }
            });
        }

        // Copy phone
        $(document).ready(function () {
            $(".copy-btn").on("click", function () {
                var text = $(this).prev(".textToCopy").text().trim();
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text);
                } else {
                    var t = $("<textarea>").val(text).css({ position: "absolute", left: "-9999px" });
                    $("body").append(t); t.select(); document.execCommand("copy"); t.remove();
                }
                $(this).html("Copied!");
                setTimeout(() => $(this).html('<i class="tio-copy"></i>'), 1000);
            });
        });

        // Select2
        $(document).on('ready', function () {
            $('.js-select2-custom').each(function () {
                $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>
    @include('vendor-views/js/date_range')
@endpush
