@extends('layouts.vendor.app')

@section('title', $type . (_isHospital() ? 'Appointments' : ' Leads'))

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* ── Base ─────────────────────────────────────────── */
        .lp * { box-sizing: border-box; }
        .lp {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f4f4f5;
            min-height: 100vh;
            padding: 28px 24px;
            color: #18181b;
        }

        /* ── Page header ──────────────────────────────────── */
        .lp-header {
            display: flex; align-items: center;
            justify-content: space-between;
            flex-wrap: wrap; gap: 12px;
            margin-bottom: 22px;
        }
        .lp-header h1 {
            font-size: 22px; font-weight: 800; color: #18181b;
            display: flex; align-items: center; gap: 10px; margin: 0;
        }
        .lp-header h1 .count-pill {
            font-size: 13px; font-weight: 700;
            background: #18181b; color: #fff;
            border-radius: 99px; padding: 2px 11px;
        }
        .lp-header-sub {
            font-size: 11px; font-weight: 700; color: #a1a1aa;
            text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 3px;
        }
        .lp-header-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

        /* ── Stat bar ─────────────────────────────────────── */
        .lp-stat-bar {
            display: flex; gap: 8px;
            overflow-x: auto; padding-bottom: 4px;
            margin-bottom: 20px;
        }
        .lp-stat-bar::-webkit-scrollbar { height: 4px; }
        .lp-stat-bar::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 99px; }
        .stat-card {
            flex-shrink: 0; padding: 9px 16px;
            border-radius: 10px; border: 1px solid #e4e4e7;
            background: #fff; text-decoration: none; display: flex;
            flex-direction: column; cursor: pointer; transition: all 0.16s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.09); text-decoration: none; }
        .stat-card.active {
            background: #18181b; border: 2px solid #18181b;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .stat-card .s-label {
            font-size: 10px; font-weight: 700; color: #a1a1aa;
            text-transform: uppercase; letter-spacing: 0.06em;
        }
        .stat-card.active .s-label { color: rgba(255,255,255,0.55); }
        .stat-card .s-num { font-size: 22px; font-weight: 800; color: #18181b; line-height: 1.15; }
        .stat-card.active .s-num { color: #fff; }

        /* ── Filter bar ───────────────────────────────────── */
        .lp-filter {
            background: #fff; border-radius: 11px;
            padding: 9px 12px; margin-bottom: 20px;
            display: flex; gap: 10px; align-items: center;
            border: 1px solid #e4e4e7; flex-wrap: wrap;
        }
        .lp-filter .search-wrap { flex: 1; min-width: 200px; position: relative; }
        .lp-filter .search-wrap svg {
            position: absolute; left: 10px; top: 50%;
            transform: translateY(-50%); color: #a1a1aa;
        }
        .lp-filter input[type="search"] {
            width: 100%; padding: 8px 10px 8px 32px;
            border-radius: 8px; border: 1px solid #e4e4e7;
            font-size: 13px; font-family: inherit;
            background: #fafafa; color: #18181b; outline: none;
            transition: border-color 0.18s, box-shadow 0.18s;
        }
        .lp-filter input[type="search"]:focus {
            border-color: #18181b;
            box-shadow: 0 0 0 3px rgba(24,24,27,0.08);
        }
        .lp-filter select, .lp-filter .btn-date {
            padding: 8px 13px; border-radius: 8px;
            border: 1px solid #e4e4e7; background: #fafafa;
            font-size: 13px; font-family: inherit;
            font-weight: 600; color: #52525b; cursor: pointer; outline: none;
        }

        /* ── Results label ────────────────────────────────── */
        .lp-results { font-size: 11px; color: #a1a1aa; font-weight: 600; margin-bottom: 14px; padding-left: 2px; }

        /* ── Grid ─────────────────────────────────────────── */
        .lp-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        @media (max-width: 1200px) { .lp-grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 680px)  { .lp-grid { grid-template-columns: 1fr; } }

        /* ── Lead card ────────────────────────────────────── */
        .lead-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e4e4e7;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex; flex-direction: column;
            transition: box-shadow 0.18s, border-color 0.18s, transform 0.15s;
            overflow: hidden;
        }
        .lead-card.clickable { cursor: pointer; }
        .lead-card.clickable:hover {
            box-shadow: 0 5px 18px rgba(0,0,0,0.10);
            border-color: #18181b;
            transform: translateY(-1px);
        }
        .lc-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 12px; flex: 1; }

        /* card head */
        .lc-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .lc-id { font-size: 10px; font-weight: 700; color: #a1a1aa; letter-spacing: 0.07em; text-transform: uppercase; margin-bottom: 4px; }
        .lc-name { font-size: 14px; font-weight: 700; color: #18181b; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lc-head-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

        /* status badge */
        .status-pill {
            display: inline-flex; align-items: center;
            padding: 3px 9px; border-radius: 99px;
            font-size: 11px; font-weight: 700; letter-spacing: 0.02em;
        }
        .pill-new        { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
        .pill-accepted   { background:#fefce8; color:#854d0e; border:1px solid #fde68a; }
        .pill-confirmed  { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; }
        .pill-completed  { background:#f0fdf4; color:#14532d; border:1px solid #86efac; }
        .pill-cancelled  { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .pill-missed     { background:#fafafa; color:#52525b; border:1px solid #e4e4e7; }
        .pill-in_progress{ background:#faf5ff; color:#6b21a8; border:1px solid #e9d5ff; }
        .pill-confirmation_request_sent { background:#fdf4ff; color:#7e22ce; border:1px solid #e9d5ff; }
        .pill-unassigned { background:#fefce8; color:#854d0e; border:1px solid #fde68a; }
        .pill-alotted    { background:#f0f9ff; color:#075985; border:1px solid #bae6fd; }

        /* menu button */
        .lc-menu-btn {
            width: 28px; height: 28px; border: 1px solid #e4e4e7;
            border-radius: 7px; background: #fff; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; color: #71717a; letter-spacing: 1px; font-weight: 700;
            transition: background 0.15s;
        }
        .lc-menu-btn:hover { background: #f4f4f5; }
        .lc-menu .dropdown-menu {
            min-width: 165px; border-radius: 10px;
            border: 1px solid #e4e4e7;
            box-shadow: 0 8px 24px rgba(0,0,0,0.11);
            padding: 4px 0; top: 32px; right: 0;
        }
        .lc-menu .dropdown-item {
            font-size: 13px; font-weight: 500;
            padding: 9px 14px; font-family: inherit;
            display: flex; align-items: center; gap: 8px;
        }
        .lc-menu .dropdown-item:hover { background: #fafafa; }

        /* divider */
        .lc-divider { height: 1px; background: #f4f4f5; }

        /* client row */
        .lc-client { display: flex; align-items: center; gap: 10px; }
        .lc-avatar {
            width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
            background: #18181b; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 800;
        }
        .lc-avatar.unknown { background: #f4f4f5; color: #a1a1aa; }
        .lc-client-name { font-size: 13px; font-weight: 700; color: #18181b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lc-client-phone { font-size: 11px; color: #71717a; font-weight: 500; }
        .lc-client-unknown { font-size: 12px; color: #a1a1aa; font-style: italic; }

        /* phone actions */
        .lc-phone-actions { display: flex; gap: 6px; }
        .lc-btn-call, .lc-btn-copy {
            display: inline-flex; align-items: center; justify-content: center; gap: 5px;
            padding: 6px 10px; border-radius: 7px;
            border: 1px solid #e4e4e7; background: #fff;
            font-size: 12px; font-weight: 600; color: #18181b;
            cursor: pointer; font-family: inherit; transition: all 0.15s;
            text-decoration: none;
        }
        .lc-btn-call { flex: 0 0 auto; }
        .lc-btn-copy { flex: 1; overflow: hidden; }
        .lc-btn-copy span.num { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; display: inline-block; }
        .lc-btn-call:hover, .lc-btn-copy:hover {
            background: #18181b; color: #fff; border-color: #18181b;
        }

        /* date / assigned */
        .lc-meta { display: flex; flex-direction: column; gap: 6px; }
        .lc-date { font-size: 11px; color: #a1a1aa; display: flex; align-items: center; gap: 5px; }
        .lc-assigned {
            font-size: 11px; color: #52525b;
            display: flex; align-items: center; gap: 5px;
            background: #fafafa; border-radius: 6px; padding: 4px 8px;
            width: fit-content;
        }
        .lc-assigned strong { font-weight: 600; }

        /* footer action */
        .lc-footer { padding: 0 18px 16px; }
        .btn-accept-lead, .btn-send-req, .btn-gen-bill {
            width: 100%; padding: 9px; border-radius: 8px;
            font-size: 13px; font-weight: 700; cursor: pointer;
            font-family: inherit; display: flex; align-items: center;
            justify-content: center; gap: 6px; transition: all 0.15s;
        }
        .btn-accept-lead { border: none; background: #18181b; color: #fff; }
        .btn-accept-lead:hover { opacity: 0.85; }
        .btn-send-req { border: 1px solid #18181b; background: transparent; color: #18181b; }
        .btn-send-req:hover { background: #18181b; color: #fff; }
        .btn-gen-bill { border: 1px solid #e4e4e7; background: #fafafa; color: #18181b; font-weight: 600; }
        .btn-gen-bill:hover { border-color: #18181b; }

        /* empty state */
        .lp-empty { text-align: center; padding: 60px 20px; color: #a1a1aa; }
        .lp-empty .icon { font-size: 36px; margin-bottom: 10px; }
        .lp-empty h4 { font-size: 15px; font-weight: 700; color: #71717a; }

        /* button overrides */
        .btn-lp {
            padding: 8px 16px; border-radius: 9px; border: none;
            background: #18181b; color: #fff;
            font-size: 13px; font-weight: 700; font-family: inherit; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-lp:hover { opacity: 0.85; color: #fff; text-decoration: none; }
        .btn-lp-outline {
            padding: 8px 13px; border-radius: 9px;
            border: 1px solid #e4e4e7; background: #fff;
            font-size: 13px; font-weight: 600; color: #52525b;
            font-family: inherit; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-lp-outline:hover { background: #f4f4f5; color: #18181b; text-decoration: none; }

        /* modal overrides */
        .modal-content { border-radius: 14px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.16); font-family: 'Plus Jakarta Sans', sans-serif; }
        .modal-header { border-bottom: 1px solid #f4f4f5; padding: 18px 22px 14px; }
        .modal-title { font-size: 16px; font-weight: 800; color: #18181b; }
        .modal-body { padding: 20px 22px; background: #fafafa; }
        .modal-footer { border-top: none; padding: 0 22px 20px; background: #fafafa; }
        .modal-sub { font-size: 10px; font-weight: 700; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 3px; }

        .ci-box { background: #fff; padding: 14px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); margin-bottom: 14px; }
        .ci-avatar { width: 42px; height: 42px; border-radius: 50%; background: #18181b; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 800; flex-shrink: 0; }
        .ci-contact-item { display: flex; align-items: center; gap: 10px; padding: 7px 5px; border-radius: 6px; transition: background 0.15s; }
        .ci-contact-item:hover { background: #f4f4f5; }
        .ci-contact-item i { font-size: 17px; color: #18181b; }
        .ci-contact-item small { display: block; font-size: 11px; color: #a1a1aa; }
        .ci-contact-item a, .ci-contact-item span.val { font-size: 13px; font-weight: 600; color: #18181b; }
        .copy-btn { border: 1px solid #e4e4e7; background: #fff; padding: 5px 9px; border-radius: 6px; cursor: pointer; transition: all 0.18s; font-size: 12px; }
        .copy-btn:hover { background: #18181b; color: #fff; border-color: #18181b; }

        .action-form { background: #fff; padding: 16px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
        .action-form label { font-size: 12px; font-weight: 700; color: #52525b; }
        .status-box { background: #fff; padding: 14px; border-radius: 10px; border-left: 4px solid #22c55e; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
        .status-box i { font-size: 26px; color: #22c55e; }
    </style>
@endpush

@section('content')
    @php
        $statusCounts = ['new'=>0,'accepted'=>0,'completed'=>0,'cancelled'=>0,'missed'=>0];
    @endphp

    <div class="lp">

        {{-- ── Header ──────────────────────────────────────── --}}
        <div class="lp-header">
            <div>
                <div class="lp-header-sub">Vendor Portal</div>
                <h1>
                    {{ $type . (_isHospital() ? 'Appointments' : ' Leads') }}
                    <span class="count-pill" id="itemCount">{{ count($product) }}</span>
                </h1>
            </div>
            <div class="lp-header-actions">
                <button onclick="window.location.reload()" class="btn-lp-outline">↺ Refresh</button>
                @if (hasPermission('leads_manage', 'statuses') && !$empId)
                    <button type="button" class="btn-lp" data-toggle="modal" data-target="#sttsMOdal">
                        ⚙ Statuses
                    </button>
                @endif
            </div>
        </div>

        {{-- ── Stat bar ─────────────────────────────────────── --}}
        @if (hasPermission('leads_manage', 'list'))
            <div class="lp-stat-bar" id="statusCards"></div>
        @endif

        {{-- ── Filter bar ───────────────────────────────────── --}}
        @if (!$empId && hasPermission('leads_manage', 'list'))
            <div class="lp-filter">
                <form action="" class="search-wrap" style="margin:0;">
                    @if(request('type'))        <input type="hidden" name="type"              value="{{ request('type') }}"> @endif
                    @if(request('date_range'))  <input type="hidden" name="date_range"        value="{{ request('date_range') }}"> @endif
                    @if(request('custom_date_range')) <input type="hidden" name="custom_date_range" value="{{ request('custom_date_range') }}"> @endif
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" name="search" value="{{ request()?->search ?? null }}" placeholder="Search by service name…">
                </form>

                <form action="" style="margin:0;">
                    @if(request('search'))     <input type="hidden" name="search"     value="{{ request('search') }}"> @endif
                    @if(request('date_range')) <input type="hidden" name="date_range" value="{{ request('date_range') }}"> @endif
                    <select name="type" onchange="this.form.submit()">
                        <option {{ $type=='All'       ? 'selected':'' }} value="All">All</option>
                        <option {{ $type=='New'       ? 'selected':'' }} value="New">New</option>
                        <option {{ $type=='Accepted'  ? 'selected':'' }} value="Accepted">Accepted</option>
                        <option {{ $type=='Cancelled' ? 'selected':'' }} value="Cancelled">Cancelled</option>
                        <option {{ $type=='Completed' ? 'selected':'' }} value="Completed">Completed</option>
                    </select>
                </form>

                <button type="button" class="btn-date" data-toggle="modal" data-target="#dateRangeModal">
                    📅 {{ translate($preset) }}
                </button>
                @include('vendor-views/form_modals/date_range')
            </div>
        @endif

        {{-- ── Results label ───────────────────────────────── --}}
        @if (hasPermission('leads_manage', 'list'))
            <div class="lp-results">
                {{ count($product) }} lead{{ count($product)!=1?'s':'' }}
                @if ($type != 'All') &middot; {{ $type }} @endif
                @if(request('search')) &middot; "{{ request('search') }}" @endif
            </div>
        @endif

        {{-- ── Leads grid ──────────────────────────────────── --}}
        @if (hasPermission('leads_manage', 'list'))
            @if(count($product) === 0)
                <div class="lp-empty">
                    <div class="icon">🔍</div>
                    <h4>No leads found</h4>
                    <p>Try adjusting your filters.</p>
                </div>
            @else
                <div class="lp-grid">
                    @foreach ($product as $key => $lead)
                        @php
                            $status               = $lead->current_status;
                            $invoiceStatus        = _serviceInvoiceStatus($lead->id);
                            $isCompleted          = $status === 'Completed';
                            $isCancelled          = _isCancelled($lead->id);
                            $isConfirmed          = $status === 'Confirmed';
                            $canViewDetails       = ($isConfirmed || $isCancelled || $isCompleted) && $lead->status != 'cancelled';
                            $isConfirmed2         = $status === 'Confirmed' || $canViewDetails;
                            $isAcceptedReq        = _acceptedReq($lead->id);
                            $canAccept            = !isset($lead->additional_status) || $lead->additional_status !== 'missed';
                            $currentServiceStatus = _getCurrentServiceStatus($lead->id);
                            $isMissed             = isset($lead->additional_status) && $lead->additional_status == 'missed';
                            $isClickable          = !$isMissed && !($lead->status=='new' && !$isAcceptedReq);

                            $class = \Illuminate\Support\Str::slug(
                                strtolower($lead->current_status ?? ($lead->additional_status ?? $lead->assigned_status)), '_'
                            );
                            if ($isCancelled)  { $class = 'cancelled'; }
                            if ($class == '')  { $class = 'new'; }

                            $user_details = _getUserDetails($lead->uid);
                            $serviceNum   = 'SRV-' . str_pad($lead->id, 4, '0', STR_PAD_LEFT);

                            // Format date: "28 Apr, 9:14 am"
                            $dt      = \Carbon\Carbon::parse($lead->created_at);
                            $fmtDate = $dt->format('j M, g:i a');
                        @endphp

                        <div class="lead-card {{ $isClickable ? 'clickable' : '' }}"
                            @if ($isClickable) onclick="handleClick('{{ route('vendor.service.lead-details', [$lead->id]) }}', event)" @endif>

                            <div class="lc-body">

                                {{-- Head --}}
                                <div class="lc-head">
                                    <div style="flex:1;min-width:0;">
                                        <div class="lc-id">{{ $serviceNum }}</div>
                                        <div class="lc-name" title="{{ $lead->item_name }}">{{ $lead->item_name }}</div>
                                    </div>
                                    <div class="lc-head-right">
                                        {{-- Badge --}}
                                        @if ($isMissed)
                                            <span class="status-pill pill-missed">Missed</span>
                                            @php addStatus($statusCounts,'missed'); @endphp
                                        @elseif ($isCompleted)
                                            <span class="status-pill pill-completed">Completed</span>
                                            @php addStatus($statusCounts,'completed'); @endphp
                                        @elseif ($isCancelled)
                                            <span class="status-pill pill-cancelled">Cancelled</span>
                                            @php addStatus($statusCounts,'cancelled'); @endphp
                                        @elseif ($lead->current_status == 'Confirmation Request Sent')
                                            <span class="status-pill pill-confirmation_request_sent">Req. Sent</span>
                                            @php addStatus($statusCounts,'new'); @endphp
                                        @elseif ($isAcceptedReq && !$isConfirmed)
                                            <span class="status-pill pill-accepted">Accepted</span>
                                            @php addStatus($statusCounts,'accepted'); @endphp
                                        @elseif ($lead->current_status == 'Confirmed' || $lead->assigned_status == 'Assigned' || $lead->assigned_status == 'Unassigned')
                                            @if ($lead->assigned_status == 'Unassigned')
                                                <span class="status-pill pill-unassigned">Unassigned</span>
                                                @php addStatus($statusCounts,'unassigned'); @endphp
                                            @else
                                                <span class="status-pill pill-alotted">Assigned{{ $lead->assigned_type=='vendor' ? ' (Self)' : '' }}</span>
                                                @php addStatus($statusCounts,'alotted'); @endphp
                                            @endif
                                        @elseif ($lead->current_status != '')
                                            <span class="status-pill pill-{{ $class }}">{{ $lead->current_status }}</span>
                                            @php addStatus($statusCounts, strtolower($lead->current_status)); @endphp
                                        @elseif ($lead->status != '')
                                            <span class="status-pill pill-{{ $class }}">{{ ucfirst($lead->status) }}</span>
                                            @php addStatus($statusCounts, $lead->status); @endphp
                                        @endif

                                        {{-- Menu --}}
                                        @if (!$isMissed && $lead->status != 'new')
                                            <div class="lc-menu" onclick="event.stopPropagation()" style="position:relative;">
                                                <button class="lc-menu-btn dropdown-toggle" data-toggle="dropdown" aria-expanded="false">···</button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    @if ($isCompleted)
                                                        @if (in_array($invoiceStatus, ['new','editable']))
                                                            <a href="{{ route('vendor.business-settings.generate-bill', [$lead->id]) }}"
                                                                class="dropdown-item text-primary">
                                                                <i class="fas fa-file-invoice"></i>
                                                                {{ $invoiceStatus==='new' ? 'Generate' : 'Edit' }} Bill
                                                            </a>
                                                        @else
                                                            <a target="_blank" href="{{ asset('storage/app/public/invoice/'.$invoiceStatus) }}"
                                                                class="dropdown-item text-success">
                                                                <i class="tio-document-outlined"></i> View Bill
                                                            </a>
                                                        @endif
                                                    @endif
                                                    @if ($canViewDetails)
                                                        <a href="{{ route('vendor.service.lead-details', [$lead->id]) }}"
                                                            class="dropdown-item" style="color:#18181b;">
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
                                                    @if ($lead->current_status==='Confirmed' && $lead->assigned_status!=='Unassigned' && $lead->assigned_type==='staff' && isset($lead->assigned_to))
                                                        <a href="{{ route('vendor.track-location', [$lead->assigned_to]) }}"
                                                            target="_blank" class="dropdown-item text-primary">
                                                            <i class="tio-location-search"></i> Track Location
                                                        </a>
                                                    @endif
                                                    @if (_isHospital() && ($isConfirmed || $isCompleted))
                                                        @php $leadRx = \App\Models\Prescription::where('service_request_id',$lead->id)->first(); @endphp
                                                        @if ($leadRx)
                                                            <a href="{{ route('vendor.prescription.show',$leadRx->id) }}" class="dropdown-item text-success"><i class="tio-print"></i> View Prescription</a>
                                                            @if (!$leadRx->is_finalized)
                                                                <a href="{{ route('vendor.prescription.edit',$leadRx->id) }}" class="dropdown-item text-primary"><i class="tio-edit"></i> Edit Prescription</a>
                                                            @endif
                                                        @else
                                                            <a href="{{ route('vendor.prescription.create',['service_request_id'=>$lead->id]) }}" class="dropdown-item text-success"><i class="tio-medicine"></i> Write Prescription</a>
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
                                </div>

                                {{-- Divider --}}
                                <div class="lc-divider"></div>

                                {{-- Client row --}}
                                <div class="lc-client">
                                    @if ($isAcceptedReq && $user_details)
                                        @php
                                            $av = strtoupper(substr($user_details->f_name,0,1) . substr($user_details->l_name,0,1));
                                        @endphp
                                        <div class="lc-avatar">{{ $av }}</div>
                                        <div style="flex:1;min-width:0;">
                                            <div class="lc-client-name">{{ $user_details->f_name.' '.$user_details->l_name }}</div>
                                            @if ($user_details->phone)
                                                <div class="lc-client-phone">{{ $user_details->phone }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="lc-avatar unknown" style="font-size:14px;">?</div>
                                        <div class="lc-client-unknown">Client not revealed yet</div>
                                    @endif
                                </div>

                                {{-- Phone actions --}}
                                @if ($isAcceptedReq && $user_details && $user_details->phone)
                                    <div class="lc-phone-actions" onclick="event.stopPropagation()">
                                        <a href="tel:{{ preg_replace('/\s+/','',$user_details->phone) }}"
                                            class="lc-btn-call" onclick="event.stopPropagation()">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.68A2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                                            Call
                                        </a>
                                        <button class="lc-btn-copy textToCopyBtn" data-phone="{{ $user_details->phone }}"
                                            onclick="event.stopPropagation(); copyPhone(this)">
                                            <span class="num">{{ $user_details->phone }}</span>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                        </button>
                                    </div>
                                @endif

                                {{-- Date + assigned --}}
                                <div class="lc-meta">
                                    <div class="lc-date">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        {{ $fmtDate }}
                                    </div>
                                    @if ($lead->assigned_status == 'Assigned' && isset($lead->assigned_to))
                                        @php $empInfo = _getWhereOne('vendor_employees', ['id' => $lead->assigned_to]); @endphp
                                        @if ($empInfo)
                                            <div class="lc-assigned">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                                <strong>{{ $empInfo->f_name.' '.$empInfo->l_name }}</strong>
                                                <span style="color:#a1a1aa;font-size:10px;">({{ !$lead->accepted_by_staff ? 'Pending' : ($lead->accepted_by_staff==2 ? 'Rejected' : 'Accepted') }})</span>
                                            </div>
                                        @endif
                                    @endif
                                </div>

                            </div>{{-- /lc-body --}}

                            {{-- Footer action pinned to bottom --}}
                            @if (!$isMissed)
                                @if (!$canViewDetails && !$isCancelled && !$isCompleted && !$isAcceptedReq && hasPermission('leads_manage','accept') && $canAccept && $lead->status=='new')
                                    <div class="lc-footer" onclick="event.stopPropagation()">
                                        <a href="{{ route('vendor.service.accept', [$lead->id]) }}" class="btn-accept-lead" style="text-decoration:none;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            Accept Lead
                                        </a>
                                    </div>
                                @elseif ($isAcceptedReq && !$isConfirmed && !$isCompleted && !$isCancelled)
                                    <div class="lc-footer" onclick="event.stopPropagation()">
                                        <button class="btn-send-req" data-toggle="modal" data-target="#userModal-{{ $lead->id }}">
                                            Send Confirmation Request
                                        </button>
                                    </div>
                                @elseif ($isCompleted && hasPermission('leads_manage','edit') && in_array($invoiceStatus,['new','editable']))
                                    <div class="lc-footer" onclick="event.stopPropagation()">
                                        <a href="{{ route('vendor.business-settings.generate-bill', [$lead->id]) }}" class="btn-gen-bill" style="text-decoration:none;">
                                            🧾 {{ $invoiceStatus==='new' ? 'Generate Bill' : 'Edit Bill' }}
                                        </a>
                                    </div>
                                @elseif ($isCompleted && hasPermission('leads_manage','invoice'))
                                    <div class="lc-footer" onclick="event.stopPropagation()">
                                        <a target="_blank" href="{{ asset('storage/app/public/invoice/'.$invoiceStatus) }}" class="btn-gen-bill" style="text-decoration:none;">
                                            🧾 View Bill
                                        </a>
                                    </div>
                                @elseif (hasPermission('leads_manage','alot') && ($lead->current_status=='Confirmed' || $lead->assigned_status=='Assigned' || $lead->assigned_status=='Unassigned') && !$isCompleted && !$isCancelled)
                                    <div class="lc-footer" onclick="event.stopPropagation()">
                                        <button class="btn-send-req" data-toggle="modal" data-target="#assignModal{{ $key }}">
                                            {{ $lead->assigned_status=='Unassigned' ? '+ Assign Staff' : '✎ Reassign Staff' }}
                                        </button>
                                    </div>
                                @endif
                            @endif

                        </div>{{-- /lead-card --}}

                        {{-- ── Assign modal ── --}}
                        <div class="modal fade" id="assignModal{{ $key }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div>
                                            <div class="modal-sub">{{ $serviceNum }}</div>
                                            <h5 class="modal-title">Assign Staff</h5>
                                        </div>
                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                    </div>
                                    <div class="modal-body">
                                        @if ($lead->assigned_status == 'Unassigned' || $lead->assigned_type == 'vendor')
                                            @if ($lead->assigned_type == 'vendor')
                                                <div style="background:#f0fdf4;border-radius:8px;padding:10px 14px;font-size:13px;font-weight:600;color:#166534;margin-bottom:14px;">
                                                    Currently assigned to: Self (Vendor)
                                                </div>
                                            @endif
                                            <form action="{{ route('vendor.service.save-assignment') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="service_id" value="{{ $lead->id }}">
                                                <input type="hidden" name="id" value="{{ $lead->acc_id }}">
                                                <div class="form-group">
                                                    <label style="font-size:12px;font-weight:700;color:#52525b;">{{ $lead->assigned_status=='Assigned' ? 'Reassign':'Assign' }} To</label>
                                                    <select name="staff_id" class="form-control js-select2-custom" style="border-radius:8px;font-family:inherit;">
                                                        <option></option>
                                                        <option value="vendor">Self (Vendor)</option>
                                                        @foreach ($allStaff as $staff)
                                                            <option value="{{ $staff->id }}">{{ $staff->f_name.' '.$staff->l_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button class="btn-lp" type="submit">Assign</button>
                                            </form>
                                        @else
                                            @php $empInfo = _getWhereOne('vendor_employees', ['id' => $lead->assigned_to]); @endphp
                                            @if ($empInfo)
                                                <div style="background:#fafafa;border-radius:10px;padding:14px;font-size:14px;">
                                                    <strong>Assigned To:</strong>
                                                    {{ $empInfo->f_name.' '.$empInfo->l_name }} #{{ $lead->assigned_to }}
                                                    <span style="color:#71717a;">({{ !$lead->accepted_by_staff ? 'Acceptance Pending' : ($lead->accepted_by_staff==2 ? 'Rejected':'Accepted') }})</span>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ── User details / confirmation modal ── --}}
                        @if ($user_details)
                            <div class="modal fade" id="userModal-{{ $lead->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div>
                                                <div class="modal-sub">{{ $serviceNum }}</div>
                                                <h5 class="modal-title">{{ $lead->item_name }}</h5>
                                            </div>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="ci-box">
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="ci-avatar mr-3">{{ strtoupper(substr($user_details->f_name,0,1)) }}</div>
                                                    <div>
                                                        <h6 style="font-weight:800;margin:0;">{{ $user_details->f_name.' '.$user_details->l_name }}</h6>
                                                        <small style="color:#a1a1aa;">Customer Details</small>
                                                    </div>
                                                </div>
                                                <div class="ci-contact-item">
                                                    <i class="tio-email"></i>
                                                    <div>
                                                        <small>Email</small>
                                                        <a href="mailto:{{ $user_details->email }}" class="val">{{ $user_details->email }}</a>
                                                    </div>
                                                </div>
                                                <div class="ci-contact-item">
                                                    <i class="tio-call"></i>
                                                    <div class="flex-grow-1">
                                                        <small>Mobile</small>
                                                        <span class="val textToCopy">{{ $user_details->phone }}</span>
                                                    </div>
                                                    <button class="copy-btn"><i class="tio-copy"></i></button>
                                                </div>
                                                <div class="mt-2" style="display:flex;gap:8px;">
                                                    <a href="tel:{{ preg_replace('/\s+/','',$user_details->phone) }}" class="btn-lp" style="flex:1;justify-content:center;text-decoration:none;">
                                                        📞 Call
                                                    </a>
                                                </div>
                                            </div>

                                            @if (!_getCurrentServiceStatus($lead->id))
                                                @if (hasPermission('leads_manage','send_confirmation_request'))
                                                    <form action="{{ route('vendor.service.send-confirmation-notification', ['id'=>$lead->id]) }}" class="action-form">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $lead->id }}">
                                                        <div class="form-group">
                                                            <label for="lp_{{ $lead->id }}">Visiting Charges</label>
                                                            <div class="input-group mt-1">
                                                                <div class="input-group-prepend"><span class="input-group-text"><i class="tio-money"></i></span></div>
                                                                <input type="number" name="price" id="lp_{{ $lead->id }}" class="form-control" placeholder="Enter amount" required>
                                                            </div>
                                                        </div>
                                                        <button type="submit" class="btn-lp" style="width:100%;justify-content:center;">✓ Send Confirmation Request</button>
                                                    </form>
                                                @endif
                                            @else
                                                <div class="status-box">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <i class="tio-checkmark-circle-outlined"></i>
                                                            <div>
                                                                <small style="color:#a1a1aa;display:block;">Status</small>
                                                                <strong>{{ _getCurrentServiceStatus($lead->id) }}</strong>
                                                            </div>
                                                        </div>
                                                        @if (_getCurrentServiceStatus($lead->id)=='Confirmed')
                                                            <a href="{{ route('vendor.service.cancel',[$lead->id]) }}" class="btn btn-outline-danger btn-sm"><i class="tio-clear"></i> Cancel</a>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn-lp-outline" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @endforeach
                </div>{{-- /lp-grid --}}
            @endif
        @endif

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
                            <strong>Default:</strong>
                            @foreach ($default_statuses as $stts)
                                <span class="badge badge-light ml-1">{{ $stts->status }}</span>
                            @endforeach
                            @if (count($approval_pending))
                                <br><br><strong>Approval Pending:</strong>
                                @foreach ($approval_pending as $stts)
                                    <span class="badge badge-light ml-1">{{ $stts->serviceStatus->status }}</span>
                                @endforeach
                            @endif
                            <form action="{{ route('vendor.business-settings.update-statuses') }}" method="post" class="mt-3">
                                @csrf
                                <div class="form-group">
                                    <label style="font-size:12px;font-weight:700;color:#52525b;">Selected Statuses</label>
                                    <select name="statuses[]" multiple="multiple"
                                        class="form-control js-select2-custom js-example-basic-multiple"
                                        data-placeholder="Select statuses">
                                        <option value=""></option>
                                        @foreach ($statuses as $sc)
                                            <option {{ in_array($sc->id,explode(',',$store_data->lead_statuses)) ? 'selected':'' }} value="{{ $sc->id }}">{{ $sc->status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn-lp" type="submit">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>{{-- /lp --}}
@endsection

@push('script_2')
    <script>
        function handleClick(url, e) {
            if ($(e.target).closest('.lc-menu,.dropdown-menu,.copy-btn,.dropdown-toggle,.lc-phone-actions,button,a').length) return;
            window.location.href = url;
        }

        // Copy phone (card button)
        function copyPhone(btn) {
            var phone = btn.getAttribute('data-phone');
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(phone);
            } else {
                var t = document.createElement('textarea');
                t.value = phone; t.style.position = 'absolute'; t.style.left = '-9999px';
                document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t);
            }
            var numSpan = btn.querySelector('.num');
            var orig = numSpan.textContent;
            numSpan.textContent = 'Copied!';
            btn.style.color = '#166534'; btn.style.borderColor = '#bbf7d0'; btn.style.background = '#f0fdf4';
            setTimeout(function() {
                numSpan.textContent = orig;
                btn.style.color = ''; btn.style.borderColor = ''; btn.style.background = '';
            }, 1500);
        }

        // Copy phone (modal button)
        $(document).ready(function () {
            $(".copy-btn").on("click", function () {
                var text = $(this).prev(".textToCopy").text().trim();
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text);
                } else {
                    var t = $("<textarea>").val(text).css({ position:"absolute", left:"-9999px" });
                    $("body").append(t); t.select(); document.execCommand("copy"); t.remove();
                }
                $(this).html("Copied!");
                setTimeout(() => $(this).html('<i class="tio-copy"></i>'), 1200);
            });
        });

        // Stat cards
        let statusCounts = @json($statusCounts);
        let leadCounts   = @json($product).length;
        function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

        $(document).ready(function () {
            let container = $("#statusCards");
            let currentType = '{{ strtolower($type) }}';

            container.append(`
                <a href="{{ route('vendor.service.leads_list') }}?type=All"
                   class="stat-card ${currentType==='all' ? 'active' : ''}">
                    <div class="s-label">All</div>
                    <div class="s-num">${leadCounts}</div>
                </a>
            `);

            $.each(statusCounts, function (status, count) {
                if (count === 0) return;
                let isActive = currentType === status.toLowerCase();
                container.append(`
                    <a href="{{ route('vendor.service.leads_list') }}?type=${capitalize(status)}"
                       class="stat-card ${isActive ? 'active' : ''}">
                        <div class="s-label">${status}</div>
                        <div class="s-num">${count}</div>
                    </a>
                `);
            });
        });

        // Cancel lead
        function cancelLead(serviceId, accId) {
            Swal.fire({
                title: 'Cancel this lead?',
                text: 'This action cannot be undone.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Go Back',
                confirmButtonText: 'Yes, Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
                    $.post({
                        url: '{{ route('vendor.service.cancel') }}',
                        data: { service_id: serviceId, id: accId },
                        beforeSend: () => $('#loading').show(),
                        success: (data) => {
                            data.status ? toastr.success(data.message) : toastr.error(data.message);
                            setTimeout(() => window.location.reload(), 1000);
                        },
                        complete: () => $('#loading').hide()
                    });
                }
            });
        }

        // Select2
        $(document).on('ready', function () {
            $('.js-select2-custom').each(function () { $.HSCore.components.HSSelect2.init($(this)); });
        });
    </script>
    @include('vendor-views/js/date_range')
@endpush
