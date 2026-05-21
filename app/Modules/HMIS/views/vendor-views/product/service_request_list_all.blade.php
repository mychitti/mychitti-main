@extends('layouts.vendor.app')

@section('title', $type . (_isHospital() ? 'Appointments' : ' Leads'))

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* ── Base ─────────────────────────────────────────── */
        .lp * {
            box-sizing: border-box;
        }

        .lp {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f7f7f7;
            min-height: 100vh;
            padding: 28px 24px;
            color: #18181b;
        }

        .content.container-fluid {
            background: #f7f7f7;
        }

        

        /* ── Page header ──────────────────────────────────── */
        .lp-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 22px;
        }

        .lp-header h1 {
            font-size: 22px;
            font-weight: 800;
            color: #18181b;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .lp-header h1 .count-pill {
            font-size: 13px;
            font-weight: 700;
            background: #18181b;
            color: #fff;
            border-radius: 99px;
            padding: 2px 11px;
        }

        .lp-header-sub {
            font-size: 11px;
            font-weight: 700;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 3px;
        }

        .lp-header-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* ── Stat bar ─────────────────────────────────────── */
        .lp-stat-bar {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            margin-bottom: 20px;
        }

        .lp-stat-bar::-webkit-scrollbar {
            height: 4px;
        }

        .lp-stat-bar::-webkit-scrollbar-thumb {
            background: #d4d4d8;
            border-radius: 99px;
        }

        .stat-card {
            flex-shrink: 0;
            padding: 9px 16px;
            border-radius: 10px;
            border: 1px solid #e4e4e7;
            background: #fff;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            transition: all 0.16s;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.09);
            text-decoration: none;
        }

        .stat-card.active {
            background: #18181b;
            border: 2px solid #18181b;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .stat-card .s-label {
            font-size: 10px;
            font-weight: 700;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .stat-card.active .s-label {
            color: rgba(255, 255, 255, 0.55);
        }

        .stat-card .s-num {
            font-size: 22px;
            font-weight: 800;
            color: #18181b;
            line-height: 1.15;
        }

        .stat-card.active .s-num {
            color: #fff;
        }

        /* ── Filter bar ───────────────────────────────────── */
        .lp-filter {
            background: #fff;
            border-radius: 11px;
            padding: 9px 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            border: 1px solid #e4e4e7;
            flex-wrap: wrap;
        }

        .lp-filter .search-wrap {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .lp-filter .search-wrap svg {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #a1a1aa;
        }

        .lp-filter input[type="search"] {
            width: 100%;
            padding: 8px 10px 8px 32px;
            border-radius: 8px;
            border: 1px solid #e4e4e7;
            font-size: 13px;
            font-family: inherit;
            background: #fafafa;
            color: #18181b;
            outline: none;
            transition: border-color 0.18s, box-shadow 0.18s;
        }

        .lp-filter input[type="search"]:focus {
            border-color: #18181b;
            box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.08);
        }

        .lp-filter select,
        .lp-filter .btn-date {
            padding: 8px 13px;
            border-radius: 8px;
            border: 1px solid #e4e4e7;
            background: #fafafa;
            font-size: 13px;
            font-family: inherit;
            font-weight: 600;
            color: #52525b;
            cursor: pointer;
            outline: none;
        }

        /* ── Results label ────────────────────────────────── */
        .lp-results {
            font-size: 11px;
            color: #a1a1aa;
            font-weight: 600;
            margin-bottom: 14px;
            padding-left: 2px;
        }

        /* ── Grid ─────────────────────────────────────────── */
        .lp-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        @media (max-width: 1200px) {
            .lp-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 680px) {
            .lp-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Lead card ────────────────────────────────────── */
        [id^="lead-wrap-"] {
            min-width: 0;
            overflow: hidden;
        }

        .lead-card {
            height: 100%;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e4e4e7;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.18s, border-color 0.18s, transform 0.15s;
            min-width: 0;
            overflow: hidden;
        }

        .lead-card.clickable {
            cursor: pointer;
        }

        .lead-card.clickable:hover {
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.10);
            border-color: #18181b;
            transform: translateY(-1px);
        }

        .lc-body {
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 7px;
            flex: 1;
        }

        /* card head */
        .lc-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }

        .lc-id {
            font-size: 10px;
            font-weight: 700;
            color: #a1a1aa;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .lc-name {
            font-size: 14px;
            font-weight: 700;
            color: #18181b;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lc-head-right {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        /* status badge */
        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .pill-new {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .pill-accepted {
            background: #fefce8;
            color: #854d0e;
            border: 1px solid #fde68a;
        }

        .pill-confirmed {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .pill-completed {
            background: #f0fdf4;
            color: #14532d;
            border: 1px solid #86efac;
        }

        .pill-cancelled {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .pill-missed {
            background: #fafafa;
            color: #52525b;
            border: 1px solid #e4e4e7;
        }

        .pill-in_progress {
            background: #faf5ff;
            color: #6b21a8;
            border: 1px solid #e9d5ff;
        }

        .pill-confirmation_request_sent {
            background: #fdf4ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
        }

        .pill-unassigned {
            background: #fefce8;
            color: #854d0e;
            border: 1px solid #fde68a;
        }

        .pill-alotted {
            background: #f0f9ff;
            color: #075985;
            border: 1px solid #bae6fd;
        }

        /* menu button */
        .lc-menu-btn {
            width: 28px;
            height: 28px;
            border: 1px solid #e4e4e7;
            border-radius: 7px;
            background: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            color: #71717a;
            letter-spacing: 1px;
            font-weight: 700;
            transition: background 0.15s;
        }

        .lc-menu-btn:hover {
            background: #f4f4f5;
        }

        .lc-menu .dropdown-menu {
            min-width: 165px;
            border-radius: 10px;
            border: 1px solid #e4e4e7;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.11);
            padding: 4px 0;
            top: 32px;
            right: 0;
        }

        .lc-menu .dropdown-item {
            font-size: 13px;
            font-weight: 500;
            padding: 9px 14px;
            font-family: inherit;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .lc-menu .dropdown-item:hover {
            background: #fafafa;
        }

        /* divider */
        .lc-divider {
            height: 1px;
            background: #f4f4f5;
        }

        /* client row */
        .lc-client {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .lc-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            flex-shrink: 0;
            background: #18181b;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
        }

        .lc-avatar.unknown {
            background: #f4f4f5;
            color: #a1a1aa;
        }

        .lc-client-name {
            font-size: 13px;
            font-weight: 700;
            color: #18181b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lc-client-phone {
            font-size: 11px;
            color: #71717a;
            font-weight: 500;
        }

        .lc-client-unknown {
            font-size: 12px;
            color: #a1a1aa;
            font-style: italic;
        }

        /* phone actions */
        .lc-phone-actions {
            display: flex;
            gap: 6px;
            min-width: 0;
            overflow: hidden;
        }

        .lc-btn-call,
        .lc-btn-copy {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 6px 10px;
            border-radius: 7px;
            border: 1px solid #e4e4e7;
            background: #fff;
            font-size: 12px;
            font-weight: 600;
            color: #18181b;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
            text-decoration: none;
        }

        .lc-btn-call {
            flex: 0 0 auto;
            background: #4ec03f;
            color: #fff !important;
        }

        .lc-btn-copy {
            flex: 1;
            overflow: hidden;
        }

        .lc-btn-copy span.num {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 130px;
            display: inline-block;
        }

        .lc-btn-call:hover,
        .lc-btn-copy:hover {
            background: #18181b;
            color: #fff;
            border-color: #18181b;
        }

        /* date / assigned */
        .lc-meta {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .lc-date {
            font-size: 11px;
            color: #a1a1aa;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .lc-assigned {
            font-size: 11px;
            color: #52525b;
            display: flex;
            align-items: center;
            gap: 5px;
            background: #fafafa;
            border-radius: 6px;
            padding: 4px 8px;
            width: fit-content;
        }

        .lc-assigned strong {
            font-weight: 600;
        }

        /* footer action */
        .lc-footer {
            padding: 0 18px 16px;
        }

        .btn-accept-lead,
        .btn-send-req,
        .btn-gen-bill {
            width: 100%;
            padding: 9px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.15s;
        }

        .btn-accept-lead {
            border: none;
            background: #18181b;
            color: #fff;
        }

        .btn-accept-lead:hover {
            opacity: 0.85;
        }

        .btn-send-req {
            border: 1px solid #18181b;
            background: transparent;
            color: #18181b;
        }

        .btn-send-req:hover {
            background: #18181b;
            color: #fff;
        }

        .btn-send-req--blink {
            border-color: #f97316;
            background: #f97316;
            color: #fff;
            animation: send-req-pulse 1.2s ease-in-out infinite;
        }

        .btn-send-req--blink:hover {
            background: #ea6c0a;
            color: #fff;
        }

        @keyframes send-req-pulse {
            0%, 100% { background: #ffb683; box-shadow: 0 0 0 0 rgba(249,115,22,0.5); }
            50%       { background: #fb923c; box-shadow: 0 0 0 6px rgba(249,115,22,0); }
        }

        .btn-gen-bill {
            border: 1px solid #e4e4e7;
            background: #fafafa;
            color: #18181b;
            font-weight: 600;
        }

        .btn-gen-bill:hover {
            border-color: #18181b;
        }

        .lc-footer-row {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .lc-footer-row .btn-send-req {
            flex: 0 0 auto;
            width: auto;
            padding: 9px 12px;
        }

        .lc-status-select {
            flex: 1;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #e4e4e7;
            background: #fafafa;
            font-size: 12px;
            font-weight: 600;
            color: #52525b;
            cursor: pointer;
            outline: none;
            font-family: inherit;
        }

        .lc-status-select:focus {
            border-color: #18181b;
        }

        /* empty state */
        .lp-empty {
            text-align: center;
            padding: 60px 20px;
            color: #a1a1aa;
        }

        .lp-empty .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .lp-empty h4 {
            font-size: 15px;
            font-weight: 700;
            color: #71717a;
        }

        /* button overrides */
        .btn-lp {
            padding: 8px 16px;
            border-radius: 9px;
            border: none;
            background: #18181b;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-lp:hover {
            opacity: 0.85;
            color: #fff;
            text-decoration: none;
        }

        .btn-lp-outline {
            padding: 8px 13px;
            border-radius: 9px;
            border: 1px solid #e4e4e7;
            background: #fff;
            font-size: 13px;
            font-weight: 600;
            color: #52525b;
            font-family: inherit;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-lp-outline:hover {
            background: #f4f4f5;
            color: #18181b;
            text-decoration: none;
        }

        /* modal overrides */
        .modal-content {
            border-radius: 14px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.16);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .modal-header {
            border-bottom: 1px solid #f4f4f5;
            padding: 18px 22px 14px;
        }

        .modal-title {
            font-size: 16px;
            font-weight: 800;
            color: #18181b;
        }

        .modal-body {
            padding: 20px 22px;
            background: #fafafa;
        }

        .modal-footer {
            border-top: none;
            padding: 0 22px 20px;
            background: #fafafa;
        }

        .modal-sub {
            font-size: 10px;
            font-weight: 700;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 3px;
        }

        .ci-box {
            background: #fff;
            padding: 14px;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 14px;
        }

        .ci-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #18181b;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .ci-contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 5px;
            border-radius: 6px;
            transition: background 0.15s;
        }

        .ci-contact-item:hover {
            background: #f4f4f5;
        }

        .ci-contact-item i {
            font-size: 17px;
            color: #18181b;
        }

        .ci-contact-item small {
            display: block;
            font-size: 11px;
            color: #a1a1aa;
        }

        .ci-contact-item a,
        .ci-contact-item span.val {
            font-size: 13px;
            font-weight: 600;
            color: #18181b;
        }

        .copy-btn {
            border: 1px solid #e4e4e7;
            background: #fff;
            padding: 5px 9px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.18s;
            font-size: 12px;
        }

        .copy-btn:hover {
            background: #18181b;
            color: #fff;
            border-color: #18181b;
        }

        .action-form {
            background: #fff;
            padding: 16px;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }

        .action-form label {
            font-size: 12px;
            font-weight: 700;
            color: #52525b;
        }

        .status-box {
            background: #fff;
            padding: 14px;
            border-radius: 10px;
            border-left: 4px solid #22c55e;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }

        .status-box i {
            font-size: 26px;
            color: #22c55e;
        }

        /* ── Doc bar ─────────────────────────────────────── */
        .lc-doc-bar {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .lc-doc-btn {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 5px;
            border: 1px solid #e4e4e7;
            background: #fafafa;
            color: #52525b;
            text-decoration: none;
            white-space: nowrap;
        }

        .lc-doc-btn:hover {
            background: #f0f0f0;
            color: #18181b;
            text-decoration: none;
        }

        .lc-doc-btn.lc-doc-exists {
            border-color: #000000d4;
            color: #000502;
            background: #f0fdf4;
        }

        .lc-doc-btn.lc-doc-exists:hover {
            background: #1a1a1a56;
        }

        /* ── Inline stat cards ───────────────────────────── */
        .ld-stat-row {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .ld-stat {
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 10px;
            padding: 11px 16px;
            flex: 1;
            min-width: 110px;
        }

        .ld-stat-label {
            font-size: 10px;
            font-weight: 700;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 3px;
        }

        .ld-stat-val {
            font-size: 22px;
            font-weight: 800;
            color: #18181b;
            line-height: 1;
        }

        .ld-stat-sub {
            font-size: 10px;
            color: #71717a;
            margin-top: 2px;
        }

        .ld-stat.ld-green {
            border-left: 3px solid #22c55e;
        }

        .ld-stat.ld-red {
            border-left: 3px solid #ef4444;
        }

        .ld-stat.ld-blue {
            border-left: 3px solid #3b82f6;
        }

        /* ── Note ────────────────────────────────────────── */
        {{-- .lc-notes-bar { padding: 5px 10px 6px; border-top: 1px solid #f0f0f0; } --}} .lc-note-input-row {
            display: flex;
            align-items: center;
            gap: 3px;
            min-width: 0;
            overflow: hidden;
        }

        .lc-note-input {
            flex: 1;
            font-size: 11px;
            border: 1px solid #e4e4e7;
            border-radius: 6px;
            padding: 3px 7px;
            font-family: inherit;
            outline: none;
            background: #fafafa;
            min-width: 0;
            color: #18181b;
        }

        .lc-note-input:focus {
            border-color: #a1a1aa;
            background: #fff;
        }

        .lc-note-bell-btn {
            border: none;
            background: none;
            font-size: 13px;
            cursor: pointer;
            padding: 0 2px;
            opacity: 0.4;
            flex-shrink: 0;
        }

        .lc-note-bell-btn:hover,
        .lc-note-bell-btn.bell-active {
            opacity: 1;
        }

        .lc-note-remind-input {
            font-size: 11px;
            border: 1px solid #e4e4e7;
            border-radius: 6px;
            padding: 2px 4px;
            font-family: inherit;
            outline: none;
            background: #fafafa;
            flex-shrink: 1;
            min-width: 0;
            width: 140px;
            max-width: 100%;
        }

        .top_group {
            gap: 10px;
        }
        @media (max-width: 800px) {
            .content.container-fluid {
                padding: 6px !important;
                overflow-x: hidden;
                max-width: 100vw;
            }

            .lp {
                padding: 12px 8px;
            }

            .btn-lp-outline {
                padding: 3px 9px;
                border-radius: 6px;
            }

            .top_group {
                gap: 3px;
            }

            .lc-head {
                flex-wrap: wrap;
            }

            .lc-name {
                white-space: normal;
                word-break: break-word;
            }

            .lc-btn-copy span.num {
                max-width: 80px;
            }

            .lc-footer-row {
                flex-wrap: wrap;
            }

            .lc-status-select {
                min-width: 0;
            }

            .lp-filter {
                flex-direction: column;
                align-items: stretch;
                gap: 6px;
            }

            .lp-filter .search-wrap {
                min-width: 0;
            }

            .lp-filter select,
            .lp-filter .btn-date {
                width: 100%;
            }

            .ld-stat-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .lp-header-actions {
                width: 100%;
            }
        }

        @media (max-width: 420px) {
            .lc-btn-copy span.num {
                max-width: 55px;
            }

            .status-pill {
                font-size: 10px;
                padding: 2px 7px;
                max-width: 90px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .lc-doc-bar {
                flex-wrap: wrap;
            }
        }
    </style>
    <style>
.new-leads-badge{
        position: fixed;
    top: 134px;
    right: 9px;
    z-index: 9999;
    background: #f97316;
    color: #fff;
    border-radius: 14px;
    padding: 5px 5px;
    font-size: 12px;
        font-size: 15px;
        font-weight: 700;
        box-shadow: 0 4px 18px rgba(249,115,22,0.5);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        animation: blink-badge 1.2s ease-in-out infinite;
        user-select: none;
    
}
.new-leads-span{
    background:#fff;
            color:#f97316;
            border-radius:50%;
            width:26px;
            height:26px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:13px;
            font-weight:800;
            flex-shrink:0;
}</style>
    <script>
        /* Inject hide rules before body renders — no flash */
        (function() {
            try {
                var cfg = JSON.parse(localStorage.getItem('leads_view_config') || '{}');
                var map = {
                    completed: 'ld-card-completed',
                    cancelled: 'ld-card-cancelled',
                    rate: 'ld-card-rate',
                    topServices: 'ld-card-top-services'
                };
                var css = '';
                Object.keys(map).forEach(function(k) {
                    if (cfg['hide_' + k]) css += '#' + map[k] + '{display:none!important;}';
                });
                if (css) document.write('<style id="ld-vc-init">' + css + '</style>');
            } catch (e) {}
        })();
    </script>
@endpush

@section('content')

    @php
        $statusCounts = ['new' => 0, 'accepted' => 0, 'completed' => 0, 'cancelled' => 0, 'missed' => 0];
    @endphp

    @if(($new_leads_count ?? 0) > 0)
    <div id="new-leads-badge" class="new-leads-badge" onclick="scrollToNewLead()" 
       >
        <span class="new-leads-span">{{ $new_leads_count }}</span>
        New {{ _isHospital() ? 'Appointments' : 'Leads' }}!
    </div>
    <style>
        @keyframes blink-badge {
            0%,100% { opacity:1; transform: scale(1); }
            50%      { opacity:.82; transform: scale(1.07); }
        }
    </style>
    <script>
        function scrollToNewLead() {
            var el = document.querySelector('.lp-new-outer');
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.style.outline = '3px solid #f97316';
                setTimeout(function(){ el.style.outline = ''; }, 1800);
            }
        }
    </script>
    @endif

    <div class="content container-fluid">

        {{-- ── Header ──────────────────────────────────────── --}}
        <!-- Page Header -->
        <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h1 class="page-header-title mb-0"><i
                    class="tio-filter-list"></i>{{ $type . (_isHospital() ? 'Appointments' : ' Leads') }} <span
                    class="badge badge-soft-dark ml-2" id="itemCount">{{ count($product) }}</span></h1>
            <div class="d-flex align-items-center top_group">
                @if (auth('vendor')->check())
                    <button type="button" class="btn-lp-outline" data-toggle="modal" data-target="#defaultDashboardModal"
                        style="font-size:17px;font-weight:600;">
                        <i class="tio-dashboard-outlined"></i>
                    </button>
                    @include('vendor-views/form_modals/default_dashboard')
                @endif
                <button onclick="window.location.reload()" class="btn-lp-outline" style="color:#ff0505">↺ Refresh</button>
                @if (!$empId && hasPermission('leads_manage', 'list'))
                    <button class="btn-lp-outline" onclick="openViewConfig()">⚙ View</button>
                @endif
                @if (empty($storeConfig->leads_guide_dismissed))
                    <div id="howItWorksBtn"
                        style="display:inline-flex;align-items:center;gap:0;border-radius:9px;overflow:hidden;border:1px solid #e4e4e7;">
                        <button type="button"
                            style="padding:7px 13px;background:#fff;border:none;font-size:13px;font-weight:600;color:#52525b;cursor:pointer;font-family:inherit;"
                            data-toggle="modal" data-target="#leadsGuideModal">
                            📋 How it Works
                        </button>
                        <button type="button"
                            style="padding:7px 10px;background:#fff;border:none;border-left:1px solid #e4e4e7;font-size:13px;color:#a1a1aa;cursor:pointer;line-height:1;"
                            title="Dismiss" onclick="dismissGuideButton()">×</button>
                    </div>
                @endif
            </div>
        </div>


        {{-- ── Inline Stat Cards ──────────────────────────────── --}}
        @if (!$empId && hasPermission('leads_manage', 'list'))
            <div class="ld-stat-row">
                <div id="ld-card-completed" class="ld-stat ld-green">
                    <div class="ld-stat-label">Completed</div>
                    <div class="ld-stat-val">{{ $ld_completed }}</div>
                </div>
                <div id="ld-card-cancelled" class="ld-stat ld-red">
                    <div class="ld-stat-label">Cancelled</div>
                    <div class="ld-stat-val">{{ $ld_cancelled }}</div>
                </div>
                <div id="ld-card-rate" class="ld-stat ld-blue">
                    <div class="ld-stat-label">Completion Rate</div>
                    <div class="ld-stat-val">{{ $ld_completionRate }}%</div>
                </div>
                <div id="ld-card-top-services" class="ld-stat" style="border-left:3px solid #a855f7;cursor:pointer;"
                    data-toggle="modal" data-target="#topServicesModal">
                    <div class="ld-stat-label">Top Services</div>
                    <div class="ld-stat-val" style="font-size:15px;color:#a855f7;margin-top:4px;">📊 View</div>
                </div>
            </div>
        @endif

        {{-- ── Filter bar ───────────────────────────────────── --}}
        @if (!$empId && hasPermission('leads_manage', 'list'))
            <div class="lp-filter">
                <div class="search-wrap" style="margin:0;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input type="search" id="filterSearch" value="{{ request()?->search ?? null }}"
                        placeholder="Search by service name…">
                </div>

                <select id="filterType" style="margin:0;">
                    <option {{ $type == 'All' ? 'selected' : '' }} value="All">All</option>
                    <option {{ $type == 'New' ? 'selected' : '' }} value="New">New</option>
                    <option {{ $type == 'Accepted' ? 'selected' : '' }} value="Accepted">Accepted</option>
                    <option {{ $type == 'Cancelled' ? 'selected' : '' }} value="Cancelled">Cancelled</option>
                    <option {{ $type == 'Completed' ? 'selected' : '' }} value="Completed">Completed</option>
                </select>

                <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning btn-sm" type="button"
                    data-toggle="modal" data-target="#dateRangeModal" id="dateRangeBtn">{{ translate($preset) }}</button>
                @include('vendor-views/form_modals/date_range')
            </div>

            {{-- hidden inputs carry current filter state for date range modal --}}
            <input type="hidden" id="filterDateRange" value="{{ $preset }}">
            <input type="hidden" id="filterCustomDate" value="{{ request('custom_date_range') ?? '' }}">
        @endif

        {{-- ── Results label ───────────────────────────────── --}}
        {{-- @if (hasPermission('leads_manage', 'list'))
            <div class="lp-results">
                {{ count($product) }} lead{{ count($product) != 1 ? 's' : '' }}
                @if ($type != 'All')
                    &middot; {{ $type }}
                @endif
                @if (request('search'))
                    &middot; "{{ request('search') }}"
                @endif
            </div>
        @endif --}}

        {{-- ── Leads grid ──────────────────────────────────── --}}
        <div id="leads-grid-container">
            @include('vendor-views.product._leads_grid')
        </div>

        {{-- ── Available Statuses Modal ─────────────────────── --}}
        {{-- @if (hasPermission('leads_manage', 'statuses'))
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
                            <form action="{{ route('vendor.business-settings.update-statuses') }}" method="post"
                                class="mt-3">
                                @csrf
                                <div class="form-group">
                                    <label style="font-size:12px;font-weight:700;color:#52525b;">Selected Statuses</label>
                                    <select name="statuses[]" multiple="multiple"
                                        class="form-control js-select2-custom js-example-basic-multiple"
                                        data-placeholder="Select statuses">
                                        <option value=""></option>
                                        @foreach ($statuses as $sc)
                                            <option
                                                {{ in_array($sc->id, explode(',', $store_data->lead_statuses)) ? 'selected' : '' }}
                                                value="{{ $sc->id }}">{{ $sc->status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn-lp" type="submit">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif --}}

    </div>{{-- /lp --}}

    {{-- ── Completion OTP modal (global, shared) ── --}}
    <div class="modal fade" id="completionOtpModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:360px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Job Completion</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p style="font-size:13px;color:#52525b;margin-bottom:16px;">
                        Send an OTP to the customer. Ask them to share it with you to confirm the job is complete.
                    </p>
                    <button type="button" class="btn-lp" id="sendOtpBtn"
                        style="width:100%;justify-content:center;margin-bottom:16px;" onclick="sendCompletionOtp()">
                        Send OTP to Customer
                    </button>
                    <div id="otpInputArea" style="display:none;">
                        <div class="form-group" style="margin-bottom:10px;">
                            <label style="font-size:12px;font-weight:700;color:#52525b;">Enter OTP shared by
                                customer</label>
                            <input type="number" id="completionOtpInput" class="form-control" placeholder="4-digit OTP"
                                style="border-radius:8px;font-family:inherit;letter-spacing:4px;font-size:18px;font-weight:700;text-align:center;">
                        </div>
                        <div class="form-group" style="margin-bottom:14px;">
                            <label style="font-size:12px;font-weight:700;color:#52525b;">
                                Photo <span style="font-weight:400;color:#a1a1aa;">(optional)</span>
                            </label>
                            <input type="file" id="completionPhotoInput" accept="image/*"
                                class="form-control" style="border-radius:8px;font-size:13px;">
                        </div>
                        <button type="button" class="btn-lp" id="verifyOtpBtn"
                            style="width:100%;justify-content:center;" onclick="verifyCompletionOtp()">
                            Verify &amp; Complete
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-lp-outline" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Top Services modal ──────────────────────────────── --}}
    <div class="modal fade" id="topServicesModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:500px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">📊 Top Services by Leads</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="padding:20px 24px;">
                    @if ($ld_topServices->isEmpty())
                        <p style="text-align:center;color:#a1a1aa;font-size:13px;">No data for the selected period.</p>
                    @else
                        <div style="position:relative;height:220px;">
                            <canvas id="topServicesChart"></canvas>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-lp-outline" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── View Config modal ────────────────────────────────── --}}
    <div class="modal fade" id="viewConfigModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:320px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">⚙ View Settings</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="padding:18px 22px;">
                    <p style="font-size:11px;color:#a1a1aa;margin-bottom:14px;">Toggle to show or hide each card.</p>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <label
                            style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:600;color:#18181b;margin:0;">
                            <input type="checkbox" id="vcCompleted" onchange="applyViewConfig()"
                                style="width:15px;height:15px;cursor:pointer;">
                            <span style="border-left:3px solid #22c55e;padding-left:8px;">Completed</span>
                        </label>
                        <label
                            style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:600;color:#18181b;margin:0;">
                            <input type="checkbox" id="vcCancelled" onchange="applyViewConfig()"
                                style="width:15px;height:15px;cursor:pointer;">
                            <span style="border-left:3px solid #ef4444;padding-left:8px;">Cancelled</span>
                        </label>
                        <label
                            style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:600;color:#18181b;margin:0;">
                            <input type="checkbox" id="vcRate" onchange="applyViewConfig()"
                                style="width:15px;height:15px;cursor:pointer;">
                            <span style="border-left:3px solid #3b82f6;padding-left:8px;">Completion Rate</span>
                        </label>
                        <label
                            style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:600;color:#18181b;margin:0;">
                            <input type="checkbox" id="vcTopServices" onchange="applyViewConfig()"
                                style="width:15px;height:15px;cursor:pointer;">
                            <span style="border-left:3px solid #a855f7;padding-left:8px;">Top Services</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer" style="padding:0 22px 18px;">
                    <button class="btn-lp-outline" data-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── How It Works modal ────────────────────────────── --}}
    <div class="modal fade" id="leadsGuideModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content" style="border-radius:16px;overflow:hidden;">
                <div class="modal-header" style="background:#18181b;border:none;padding:22px 28px;">
                    <div>
                        <h5 class="modal-title" style="color:#fff;font-weight:800;font-size:18px;">
                            📋 How Leads Work
                        </h5>
                        <small style="color:#a1a1aa;font-size:12px;">Step-by-step guide to managing your leads</small>
                    </div>
                    <button type="button" class="close" style="color:#fff;opacity:1;"
                        data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="padding:28px;background:#f7f7f7;max-height:65vh;overflow-y:auto;">
                    <div style="display:flex;flex-direction:column;gap:14px;">
                        @php
                            $steps = [
                                [
                                    'icon' => '📲',
                                    'title' => 'Customer Submits a Request',
                                    'desc' =>
                                        'A customer finds your service on the app and submits an enquiry. The request is sent to you and nearby vendors simultaneously.',
                                ],
                                [
                                    'icon' => '⏱️',
                                    'title' => 'New Lead Arrives (Time-Limited)',
                                    'desc' =>
                                        'You receive the lead as a "New" card. You must accept it before the timer expires — missed leads cannot be recovered.',
                                ],
                                [
                                    'icon' => '✅',
                                    'title' => 'Accept the Lead',
                                    'desc' =>
                                        'Click Accept Lead. The customer\'s contact details are revealed and the lead moves to "Accepted" status.',
                                ],
                                [
                                    'icon' => '💬',
                                    'title' => 'Send Confirmation Request',
                                    'desc' =>
                                        'Open the lead card, enter your visiting charges, and send a Confirmation Request. The customer gets a notification with your quote.',
                                ],
                                [
                                    'icon' => '🤝',
                                    'title' => 'Customer Confirms',
                                    'desc' =>
                                        'Once the customer accepts your quote, the lead becomes "Confirmed". You are now committed to deliver the service.',
                                ],
                                [
                                    'icon' => '👷',
                                    'title' => 'Assign Staff or Handle Yourself',
                                    'desc' =>
                                        'Assign the job to a staff member or handle it personally. The assigned person receives a notification to accept the job.',
                                ],
                                [
                                    'icon' => '🔑',
                                    'title' => 'Start Job (OTP)',
                                    'desc' =>
                                        'When the technician arrives, they start the job by entering the OTP shared by the customer.',
                                ],
                                [
                                    'icon' => '✔️',
                                    'title' => 'Complete Job (OTP)',
                                    'desc' =>
                                        'After the work is done, mark it "Completed" using the OTP the customer shares to confirm completion.',
                                ],
                                [
                                    'icon' => '🧾',
                                    'title' => 'Generate Bill',
                                    'desc' =>
                                        'Once completed, generate the invoice from the lead card. You can edit the bill as many times as needed.',
                                ],
                            ];
                        @endphp
                        @foreach ($steps as $i => $step)
                            <div
                                style="display:flex;gap:14px;align-items:flex-start;background:#fff;border-radius:10px;padding:14px 16px;border:1px solid #e4e4e7;">
                                <div
                                    style="width:34px;height:34px;border-radius:50%;background:#18181b;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;">
                                    {{ $i + 1 }}
                                </div>
                                <div>
                                    <div style="font-size:13px;font-weight:800;color:#18181b;margin-bottom:3px;">
                                        {{ $step['icon'] }} {{ $step['title'] }}</div>
                                    <div style="font-size:12px;color:#52525b;line-height:1.6;">{{ $step['desc'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer"
                    style="background:#f7f7f7;border-top:1px solid #e4e4e7;padding:16px 28px;justify-content:space-between;align-items:center;">
                    <small style="color:#a1a1aa;font-size:11px;">Always accessible from Lead Settings → How it
                        Works.</small>
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="btn-lp-outline" data-dismiss="modal">Close</button>
                        <button type="button" class="btn-lp" onclick="dismissLeadsGuide()">Got it, don't show
                            again</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        function handleClick(url, e) {
            if ($(e.target).closest('.lc-menu,.dropdown-menu,.copy-btn,.dropdown-toggle,.lc-phone-actions,button,a').length)
                return;
            window.location.href = url;
        }

        // ── Filter AJAX ────────────────────────────────────
        var _leadsBaseUrl = '{{ url()->current() }}';
        var _loadTimer = null;

        function updateLeadCount(n) {
            $('#itemCount').text(n);
        }

        function loadLeads(immediate) {
            clearTimeout(_loadTimer);
            _loadTimer = setTimeout(function() {
                var params = {
                    type: $('#filterType').val() || '',
                    search: $('#filterSearch').val() || '',
                    date_range: $('#filterDateRange').val() || '',
                    custom_date_range: $('#filterCustomDate').val() || '',
                };
                // Update date range button label
                var label = params.date_range.replace(/_/g, ' ');
                $('#dateRangeBtn').text(label || 'Date range');
                // Fetch grid
                $('#leads-grid-container').css('opacity', '0.5');
                $.ajax({
                    url: _leadsBaseUrl,
                    data: params,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(html) {
                        $('#leads-grid-container').html(html).css('opacity', '1');
                        initStatusSelects('#leads-grid-container');
                    },
                    error: function() {
                        $('#leads-grid-container').css('opacity', '1');
                        toastr.error('Failed to load leads.');
                    }
                });
            }, immediate ? 0 : 450);
        }

        // Wire up filters
        $(function() {
            $('#filterType').on('change', function() {
                loadLeads(true);
            });
            $('#filterSearch').on('input', function() {
                loadLeads(false);
            });
        });

        // ── End filter AJAX ───────────────────────────────

        // ── Leads guide ───────────────────────────────────
        function dismissGuideButton() {
            $('#howItWorksBtn').remove();
            $.post({
                url: '{{ route('vendor.service.dismiss-leads-guide') }}',
                headers: {
                    'X-CSRF-TOKEN': _csrf
                },
                success: function() {}
            });
        }
        // ── End leads guide ───────────────────────────────

        // ── Status Select2 tags ───────────────────────────
        var _addStatusUrl = '{{ route('vendor.service.add-custom-status') }}';

        function initStatusSelects(scope) {
            $(scope || document).find('[id^="statusSelect-"]').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) return;
                $(this).select2({
                    tags: true,
                    placeholder: 'Status…',
                    allowClear: false,
                    width: '100%',
                    createTag: function(params) {
                        var term = $.trim(params.term);
                        if (!term) return null;
                        return {
                            id: '__new__:' + term,
                            text: term,
                            newTag: true
                        };
                    }
                });
            });
        }

        $(function() {
            initStatusSelects();
        });

        // Intercept selection on any status select (delegated, works after card refresh)
        $(document).on('select2:select', '[id^="statusSelect-"]', function(e) {
            var $sel = $(this);
            var data = e.params.data;
            var serviceId = $sel.data('service-id');
            var accId = $sel.data('acc-id');

            if (data.newTag) {
                var name = data.text;
                $sel.prop('disabled', true);
                $.post({
                    url: _addStatusUrl,
                    headers: {
                        'X-CSRF-TOKEN': _csrf
                    },
                    data: {
                        name: name
                    },
                    success: function(res) {
                        if (res.status) {
                            // Replace the temp option with a real ID option
                            $sel.find('option[value="__new__:' + name + '"]').remove();
                            var opt = new Option(res.name, res.id, true, true);
                            $sel.append(opt).val(res.id).trigger('change.select2');
                            changeLeadStatus(res.id, serviceId, accId, $sel[0]);
                        } else {
                            toastr.error(res.message || 'Could not save status.');
                            $sel.val('').trigger('change.select2');
                        }
                    },
                    error: function() {
                        toastr.error('Failed to save custom status.');
                        $sel.val('').trigger('change.select2');
                    },
                    complete: function() {
                        $sel.prop('disabled', false);
                    }
                });
            } else {
                changeLeadStatus(data.id, serviceId, accId, $sel[0]);
            }
        });

        // Stop card click-through when Select2 dropdown is open
        $(document).on('click', '.select2-container', function(e) {
            e.stopPropagation();
        });
        // ── End Status Select2 tags ───────────────────────

        // ── AJAX helpers ──────────────────────────────────
        var _csrf = $('meta[name="csrf-token"]').attr('content');
        var _leadCardUrl = '{{ route('vendor.service.lead-card', ['id' => '__ID__']) }}';

        function refreshCard(leadId, callback) {
            var url = _leadCardUrl.replace('__ID__', leadId);
            $.get(url, function(html) {
                $('#lead-wrap-' + leadId).replaceWith(html);
                $('#staffSelect-' + leadId).select2({
                    placeholder: 'Select staff',
                    allowClear: true
                });
                initStatusSelects('#lead-wrap-' + leadId);
                if (typeof callback === 'function') callback();
            });
        }

        function toggleLeadMenu(btn) {
            var menu = $(btn).next('.dropdown-menu');
            var wasOpen = menu.hasClass('show');
            $('.lc-menu .dropdown-menu').removeClass('show');
            if (!wasOpen) menu.addClass('show');
        }
        $(document).on('click', function() {
            $('.lc-menu .dropdown-menu').removeClass('show');
        });

        function toggleRemindInput(leadId) {
            var inp = document.getElementById('noteRemind-' + leadId);
            var btn = document.querySelector('#lead-wrap-' + leadId + ' .lc-note-bell-btn');
            var open = inp.style.display === 'none';
            inp.style.display = open ? 'inline-block' : 'none';
            if (btn) btn.classList.toggle('bell-active', open);
            if (!open) {
                inp.value = '';
                scheduleNoteSave(leadId);
            }
        }

        var _noteSaveTimers = {};
        var _noteUrl = '{{ route('vendor.service.lead-note.add') }}';

        function scheduleNoteSave(leadId) {
            clearTimeout(_noteSaveTimers[leadId]);
            _noteSaveTimers[leadId] = setTimeout(function() {
                var text = $('#noteText-' + leadId).val().trim();
                var remindAt = $('#noteRemind-' + leadId).val() || '';
                $.post({
                    url: _noteUrl,
                    data: {
                        service_id: leadId,
                        note: text,
                        remind_at: remindAt,
                        _token: _csrf
                    }
                });
            }, 800);
        }

        function acceptLead(serviceId, btn) {
            $(btn).prop('disabled', true).text('Accepting…');
            $.ajax({
                url: '{{ route('vendor.service.accept', ['id' => '__ID__']) }}'.replace('__ID__', serviceId),
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(data) {
                    if (data.status) {
                        toastr.success(data.message);
                        refreshCard(serviceId);
                    } else {
                        toastr.error(data.message);
                        $(btn).prop('disabled', false).text('Accept Lead');
                    }
                },
                error: function() {
                    toastr.error('Failed to accept lead.');
                    $(btn).prop('disabled', false).text('Accept Lead');
                }
            });
        }

        function sendConfirmation(leadId, btn) {
            var price = $('#lp_' + leadId).val();
            if (!price || price <= 0) {
                toastr.warning('Enter a valid amount.');
                return;
            }
            $(btn).prop('disabled', true).text('Sending…');
            $.ajax({
                url: '{{ route('vendor.service.send-confirmation-notification') }}',
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: {
                    id: leadId,
                    price: price
                },
                success: function(data) {
                    if (data.status) {
                        toastr.success(data.message);
                        $('#userModal-' + leadId).modal('hide');
                        refreshCard(leadId);
                    } else {
                        toastr.error(data.message);
                        $(btn).prop('disabled', false).text('✓ Send Confirmation Request');
                    }
                },
                error: function() {
                    toastr.error('Failed to send.');
                    $(btn).prop('disabled', false).text('✓ Send Confirmation Request');
                }
            });
        }

        function saveAssignment(serviceId, accId, modalId, btn) {
            var staffId = $('#staffSelect-' + serviceId).val();
            if (!staffId) {
                toastr.warning('Please select a staff member.');
                return;
            }
            $(btn).prop('disabled', true).text('Assigning…');
            $.ajax({
                url: '{{ route('vendor.service.save-assignment') }}',
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': _csrf
                },
                data: {
                    service_id: serviceId,
                    id: accId,
                    staff_id: staffId
                },
                success: function(data) {
                    if (data.status) {
                        toastr.success(data.message);
                        $('#' + modalId).modal('hide');
                        refreshCard(serviceId);
                    } else {
                        toastr.error(data.message);
                        $(btn).prop('disabled', false).text('Assign');
                    }
                },
                error: function() {
                    toastr.error('Assignment failed.');
                    $(btn).prop('disabled', false).text('Assign');
                }
            });
        }
        // ── End AJAX helpers ───────────────────────────

        // Copy phone (card button)
        function copyPhone(btn) {
            var phone = btn.getAttribute('data-phone');
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(phone);
            } else {
                var t = document.createElement('textarea');
                t.value = phone;
                t.style.position = 'absolute';
                t.style.left = '-9999px';
                document.body.appendChild(t);
                t.select();
                document.execCommand('copy');
                document.body.removeChild(t);
            }
            var numSpan = btn.querySelector('.num');
            var orig = numSpan.textContent;
            numSpan.textContent = 'Copied!';
            btn.style.color = '#166534';
            btn.style.borderColor = '#bbf7d0';
            btn.style.background = '#f0fdf4';
            setTimeout(function() {
                numSpan.textContent = orig;
                btn.style.color = '';
                btn.style.borderColor = '';
                btn.style.background = '';
            }, 1500);
        }

        // Copy phone (modal button)
        $(document).ready(function() {
            $(".copy-btn").on("click", function() {
                var text = $(this).prev(".textToCopy").text().trim();
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text);
                } else {
                    var t = $("<textarea>").val(text).css({
                        position: "absolute",
                        left: "-9999px"
                    });
                    $("body").append(t);
                    t.select();
                    document.execCommand("copy");
                    t.remove();
                }
                $(this).html("Copied!");
                setTimeout(() => $(this).html('<i class="tio-copy"></i>'), 1200);
            });
        });

        // Stat cards
        let statusCounts = @json($statusCounts);
        let leadCounts = @json($product).length;

        function capitalize(s) {
            return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
        }

        {{-- $(document).ready(function() {
            let container = $("#statusCards");
            let currentType = '{{ strtolower($type) }}';

            container.append(`
                <a href="{{ route('vendor.service.leads_list') }}?type=All"
                   class="stat-card ${currentType==='all' ? 'active' : ''}">
                    <div class="s-label">All</div>
                    <div class="s-num">${leadCounts}</div>
                </a>
            `);

            $.each(statusCounts, function(status, count) {
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
        }); --}}

        // Change lead status (AJAX, no reload)
        // State for the completion OTP modal
        var _otpContext = {
            serviceId: null,
            accId: null,
            selectEl: null,
            prevVal: ''
        };

        function changeLeadStatus(statusId, serviceId, accId, selectEl) {
            if (!statusId) return;

            // Status 12 = Completed: require OTP
            if (statusId == 12) {
                _otpContext = {
                    serviceId: serviceId,
                    accId: accId,
                    selectEl: selectEl,
                    prevVal: selectEl.value
                };
                // Reset modal state
                $('#otpInputArea').hide();
                $('#completionOtpInput').val('');
                $('#completionPhotoInput').val('');
                $('#sendOtpBtn').prop('disabled', false).text('Send OTP to Customer');
                $('#verifyOtpBtn').prop('disabled', false).text('Verify & Complete');
                // Revert select so it doesn't show "Completed" yet
                selectEl.value = '';
                $('#completionOtpModal').modal('show');
                return;
            }

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': _csrf
                }
            });
            $.post({
                url: '{{ route('vendor.service.change-status') }}',
                data: {
                    status: statusId,
                    service_id: serviceId
                },
                beforeSend: () => {
                    $(selectEl).prop('disabled', true);
                },
                success: function(data) {
                    data.status ? toastr.success(data.message) : toastr.error(data.message);
                },
                error: function() {
                    toastr.error('Status update failed.');
                },
                complete: () => {
                    $(selectEl).prop('disabled', false);
                }
            });
        }

        function sendCompletionOtp() {
            var btn = document.getElementById('sendOtpBtn');
            $(btn).prop('disabled', true).text('Sending…');
            $.post({
                url: '{{ route('vendor.service.send-completion-otp') }}',
                headers: {
                    'X-CSRF-TOKEN': _csrf
                },
                data: {
                    service_id: _otpContext.serviceId
                },
                success: function(data) {
                    if (data.status) {
                        toastr.success(data.message);
                        $('#otpInputArea').slideDown(200);
                        $(btn).text('Resend OTP').prop('disabled', false);
                    } else {
                        toastr.error(data.message);
                        $(btn).text('Send OTP to Customer').prop('disabled', false);
                    }
                },
                error: function() {
                    toastr.error('Failed to send OTP.');
                    $(btn).text('Send OTP to Customer').prop('disabled', false);
                }
            });
        }

        function verifyCompletionOtp() {
            var otp = $('#completionOtpInput').val().trim();
            if (!otp || otp.length < 4) {
                toastr.warning('Enter the 4-digit OTP.');
                return;
            }
            var btn = document.getElementById('verifyOtpBtn');
            $(btn).prop('disabled', true).text('Verifying…');

            var fd = new FormData();
            fd.append('_token', _csrf);
            fd.append('acc_id', _otpContext.accId);
            fd.append('status', 12);
            fd.append('otp', otp);
            var fileInput = document.getElementById('completionPhotoInput');
            if (fileInput && fileInput.files.length > 0) {
                fd.append('file', fileInput.files[0]);
            }

            $.ajax({
                url: '{{ route('vendor.lead.job-otp-verify2') }}',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.status) {
                        toastr.success('Job marked as completed!');
                        $('#completionOtpModal').modal('hide');
                        refreshCard(_otpContext.serviceId);
                    } else {
                        toastr.error(data.message);
                        $(btn).prop('disabled', false).text('Verify & Complete');
                    }
                },
                error: function() {
                    toastr.error('Verification failed.');
                    $(btn).prop('disabled', false).text('Verify & Complete');
                }
            });
        }

        // Cancel lead
        function cancelLead(serviceId, accId) {
            Swal.fire({
                title: 'Cancel this lead?',
                input: 'textarea',
                inputLabel: 'Reason for cancellation',
                inputPlaceholder: 'Enter reason (optional)…',
                inputAttributes: { 'aria-label': 'Reason' },
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Go Back',
                confirmButtonText: 'Yes, Cancel',
                reverseButtons: true
            }).then((result) => {
                if (!result.dismiss) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{ route('vendor.service.cancel') }}',
                        data: {
                            service_id: serviceId,
                            id: accId,
                            reason: result.value
                        },
                        beforeSend: () => $('#loading').show(),
                        success: (data) => {
                            if (data.status) {
                                toastr.success(data.message);
                                $.get('{{ url('vendor/service/lead-card') }}/' + serviceId, function (html) {
                                    var $wrap = $('#lead-wrap-' + serviceId);
                                    var $outer = $wrap.closest('.lp-new-outer');
                                    if ($outer.length) {
                                        $outer.replaceWith(html);
                                    } else {
                                        $wrap.replaceWith(html);
                                    }
                                });
                            } else {
                                toastr.error(data.message);
                            }
                        },
                        complete: () => $('#loading').hide()
                    });
                }
            });
        }

        // Assignment history modal
        function loadAssignmentHistory(leadId) {
            $('#assignHistoryBody-' + leadId).html('<div class="text-center py-3"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
            $('#assignHistoryModal-' + leadId).modal('show');
            $.get('{{ route('vendor.service.assignment-logs', ':id') }}'.replace(':id', leadId), function(res) {
                var logs = res.logs;
                if (!logs || logs.length === 0) {
                    $('#assignHistoryBody-' + leadId).html('<p class="text-muted text-center py-3">No assignment history found.</p>');
                    return;
                }
                var html = '<div class="table-responsive"><table class="table table-sm table-bordered" style="font-size:0.85rem;">';
                html += '<thead class="thead-light"><tr><th>#</th><th>Assigned To</th><th>Type</th><th>Assigned By</th><th>Date &amp; Time</th></tr></thead><tbody>';
                logs.forEach(function(log, i) {
                    var badge = log.assigned_type === 'staff' ? 'badge-info' : 'badge-primary';
                    html += '<tr>';
                    html += '<td>' + (i + 1) + '</td>';
                    html += '<td>' + (log.assignee_name || '—') + '</td>';
                    html += '<td><span class="badge ' + badge + '">' + (log.assigned_type ? log.assigned_type.charAt(0).toUpperCase() + log.assigned_type.slice(1) : '—') + '</span></td>';
                    html += '<td>' + (log.assigner_name || 'System') + '</td>';
                    html += '<td>' + (log.assigned_at ? new Date(log.assigned_at).toLocaleString('en-IN', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '—') + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
                $('#assignHistoryBody-' + leadId).html(html);
            }).fail(function() {
                $('#assignHistoryBody-' + leadId).html('<p class="text-danger text-center py-3">Failed to load history.</p>');
            });
        }

        // Select2
        $(document).on('ready', function() {
            $('.js-select2-custom').each(function() {
                $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>
    @include('vendor-views/js/date_range')
    <script src="{{ asset('public/assets/admin/vendor/chart.js/dist/Chart.min.js') }}"></script>
    <script>
        // Override updateSelection AFTER date_range.blade.php defines it
        function updateSelection() {
            var selected = $('input[name="date_range"]:checked').val() || '';
            var custom = $('#daterange').val() || '';
            $('#filterDateRange').val(selected);
            $('#filterCustomDate').val(selected === 'custom' ? custom : '');
            $('#dateRangeModal').modal('hide');
            loadLeads(true);
        }

        // ── View Config ──────────────────────────────────────
        var _vcCards = [{
                key: 'completed',
                id: 'ld-card-completed',
                cb: 'vcCompleted'
            },
            {
                key: 'cancelled',
                id: 'ld-card-cancelled',
                cb: 'vcCancelled'
            },
            {
                key: 'rate',
                id: 'ld-card-rate',
                cb: 'vcRate'
            },
            {
                key: 'topServices',
                id: 'ld-card-top-services',
                cb: 'vcTopServices'
            },
        ];

        function loadViewConfig() {
            var cfg = JSON.parse(localStorage.getItem('leads_view_config') || '{}');
            _vcCards.forEach(function(c) {
                var el = document.getElementById(c.cb);
                if (el) el.checked = !cfg['hide_' + c.key];
                if (cfg['hide_' + c.key]) $('#' + c.id).hide();
                else $('#' + c.id).show();
            });
        }

        function applyViewConfig() {
            var cfg = {};
            _vcCards.forEach(function(c) {
                var el = document.getElementById(c.cb);
                if (el && !el.checked) cfg['hide_' + c.key] = true;
                el && !el.checked ? $('#' + c.id).hide() : $('#' + c.id).show();
            });
            localStorage.setItem('leads_view_config', JSON.stringify(cfg));
        }

        function openViewConfig() {
            loadViewConfig();
            $('#viewConfigModal').modal('show');
        }

        // ── Top Services chart ────────────────────────────────
        var _topServicesChartInst = null;
        $('#topServicesModal').on('shown.bs.modal', function() {
            if (_topServicesChartInst) {
                _topServicesChartInst.destroy();
                _topServicesChartInst = null;
            }
            var canvas = document.getElementById('topServicesChart');
            if (!canvas) return;
            Chart.defaults.global.defaultFontFamily = 'Plus Jakarta Sans, sans-serif';
            Chart.defaults.global.defaultFontSize = 12;
            _topServicesChartInst = new Chart(canvas, {
                type: 'horizontalBar',
                data: {
                    labels: @json($ld_topServices->pluck('name')),
                    datasets: [{
                        label: 'Leads',
                        data: @json($ld_topServices->pluck('cnt')),
                        backgroundColor: 'rgba(59,130,246,0.75)',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false
                    },
                    scales: {
                        xAxes: [{
                            ticks: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }],
                        yAxes: [{
                            gridLines: {
                                display: false
                            }
                        }]
                    }
                }
            });
        });
        $('#topServicesModal').on('hidden.bs.modal', function() {
            if (_topServicesChartInst) {
                _topServicesChartInst.destroy();
                _topServicesChartInst = null;
            }
        });
    </script>
@endpush
