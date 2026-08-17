@extends('layouts.admin.app')

@section('title', 'AI Agent')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --pm-white: #ffffff; 
            --pm-border: #e5e7eb;
            --pm-border2: #d1d5db;
            --pm-text: #111827;
            --pm-muted: #6b7280;
            --pm-light: white;
            --pm-success: #16a34a;
            --pm-success-bg: #f0fdf4;
            --pm-danger: #dc2626;
            --pm-danger-bg: #fef2f2;
            --pm-warning: #d97706;
            --pm-warning-bg: #fffbeb;
            --pm-shadow: 0 1px 3px rgba(0, 0, 0, .06), 0 1px 2px rgba(0, 0, 0, .04);
            --pm-radius: 8px;
        }

        /* ── PAGE ── */
        .pm-wrap {
            font-family: 'Inter', sans-serif;
        }

        .pm-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .pm-header h1 {
            font-size: 20px;
            font-weight: 700;
            color: var(--pm-text);
            line-height: 1.3;
        }

        .pm-header p {
            font-size: 13px;
            color: white;
            margin-top: 2px;
        }

        /* ── BUTTONS ── */
        .pm-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
            text-decoration: none;
            line-height: 1.4;
        }

        .pm-btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .pm-btn-primary:hover {
            background: var(--primary);
            color: #fff;
        }

        .pm-btn-outline {
            background: var(--pm-white);
            color: var(--pm-text);
            border: 1px solid var(--pm-border2);
        }

        .pm-btn-outline:hover {
            background: var(--primary);
        }

        .pm-btn-ghost {
            background: transparent;
            color: white;
        }

        .pm-btn-ghost:hover {
            background: var(--primary);
            color: var(--pm-text);
        }

        .pm-btn-danger {
            background: var(--pm-danger-bg);
            color: var(--pm-danger);
            border: 1px solid #fecaca;
        }

        .pm-btn-danger:hover {
            background: #fee2e2;
        }

        .pm-btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* ── TABS ── */
        .pm-tabs {
            display: flex;
            gap: 2px;
            background: var(--pm-white);
            border: 1px solid var(--pm-border);
            border-radius: var(--pm-radius);
            padding: 4px;
            margin-bottom: 20px;
            box-shadow: var(--pm-shadow);
            width: fit-content;
        }

        .pm-tab {
            padding: 6px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            color: black;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: all 0.15s;
        }

        .pm-tab:hover {
            color: var(--pm-text);
            background: var(--primary);
        }

        .pm-tab.active {
            background: var(--primary);
            color: #fff;
        }

        .pm-tab-count {
            font-size: 11px;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .25);
        }

        .pm-tab:not(.active) .pm-tab-count {
            background: var(--primary);
            color: white;
        }

        /* ── LAYOUT ── */
        .pm-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 16px;
            align-items: start;
        }

        /* ── SKILL LIST CARD ── */
        .pm-list-card {
            background: var(--pm-white);
            border: 1px solid var(--pm-border);
            border-radius: var(--pm-radius);
            box-shadow: var(--pm-shadow);
            overflow: hidden;
        }

        .pm-list-head {
            padding: 12px 16px;
            border-bottom: 1px solid var(--pm-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .pm-list-title {
            font-size: 13px;
            font-weight: 600;
        }

        /* ── SEARCH ── */
        .pm-search-wrap {
            padding: 10px 12px;
            border-bottom: 1px solid var(--pm-border);
        }

        .pm-search-form {
            display: flex;
            gap: 6px;
        }

        .pm-search-input {
            flex: 1;
            border: 1px solid var(--pm-border);
            border-radius: 6px;
            padding: 7px 10px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            color: var(--pm-text);
            outline: none;
            background: var(--primary);
            transition: border-color .15s;
        }

        .pm-search-input:focus {
            border-color: var(--primary);
            background: var(--pm-white);
            box-shadow: 0 0 0 3px var(--primary);
        }

        /* ── SKILL ITEMS ── */
        .pm-skill-items {
            max-height: 580px;
            overflow-y: auto;
        }

        .pm-skill-item {
            display: flex;
            align-items: center;
            padding: 11px 16px;
            gap: 10px;
            border-bottom: 1px solid var(--pm-border);
            text-decoration: none;
            transition: background .1s;
        }

        .pm-skill-item:last-child {
            border-bottom: none;
        }

        .pm-skill-item:hover {
            background: var(--primary);
        }

        .pm-skill-item.active {
                color: white;
            background: var(--primary);
        }

        .pm-skill-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .pm-skill-info {
            flex: 1;
            min-width: 0;
        }

        .pm-skill-name {
            font-size: 13px;
            font-weight: 500;
            color: var(--pm-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pm-skill-item.active .pm-skill-name {
            color:white;
            font-weight: 600;
        }

        .pm-skill-meta {
            font-size: 11px;
            margin-top: 1px;
        }
        .pm-skill-meta:hover {
           color:white;
        }

        .pm-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 10px;
            flex-shrink: 0;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .badge-active {
            background: var(--pm-success-bg);
            color: var(--pm-success);
        }

        .badge-draft {
            background: var(--pm-warning-bg);
            color: var(--pm-warning);
        }

        .badge-archived {
            background: var(--primary);
            color: white;
        }

        .pm-add-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 11px;
            border-top: 1px solid var(--pm-border);
            color: var(--primary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: background .1s;
        }

        .pm-add-row:hover {
            background: #eeeeee;
        }

        /* ── EDITOR CARD ── */
        .pm-editor-card {
            background: var(--pm-white);
            border: 1px solid var(--pm-border);
            border-radius: var(--pm-radius);
            box-shadow: var(--pm-shadow);
            overflow: hidden;
        }

        .pm-editor-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 40px;
            text-align: center;
        }

        .pm-editor-empty-icon {
            width: 48px;
            height: 48px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            font-size: 22px;
        }

        .pm-editor-empty h3 {
            font-size: 15px;
            font-weight: 600;
        }

        .pm-editor-empty p {
            font-size: 13px;
            margin-top: 4px;
        }

        /* ── EDITOR FORM ── */
        .pm-editor-top {
            padding: 14px 20px;
            border-bottom: 1px solid var(--pm-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .pm-editor-top-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 0;
        }

        .pm-editor-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .pm-editor-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--pm-text);
        }

        .pm-editor-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }

        .pm-editor-body {
            padding: 20px;
        }

        .pm-form-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .pm-form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .pm-form-row {
            margin-bottom: 16px;
        }

        .pm-form-label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .pm-input,
        .pm-select,
        .pm-textarea {
            width: 100%;
            border: 1px solid var(--pm-border);
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            color: var(--pm-text);
            background: var(--pm-white);
            outline: none;
            transition: border-color .15s;
        }

        .pm-input:focus,
        .pm-select:focus,
        .pm-textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary);
        }

        .pm-textarea {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 12.5px;
            line-height: 1.7;
            resize: vertical;
            min-height: 220px;
            background: #fafafa;
            color: #374151;
        }

        .pm-textarea:focus {
            background: var(--pm-white);
        }

        /* ── SECTION LABEL ── */
        .pm-section {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0 10px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .pm-section::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--pm-border);
        }

        /* ── TOKEN BAR ── */
        .pm-token-row {
            color:white;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 8px 12px;
            background: var(--primary);
            border: 1px solid var(--pm-border);
            border-radius: 6px;
            margin-top: 8px;
        }

        .pm-token-stat {
            font-size: 12px;
            color: white;
            white-space: nowrap;
        }

        .pm-token-stat strong {
            font-weight: 600;
            color: var(--pm-text);
            font-family: 'JetBrains Mono', monospace;
        }

        .pm-token-bar-wrap {
            flex: 1;
        }

        .pm-token-bar-bg {
            height: 4px;
            background: var(--pm-border);
            border-radius: 4px;
            overflow: hidden;
        }

        .pm-token-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width .3s;
        }

        /* ── VARIABLES TABLE ── */
        .pm-var-table {
            width: 100%;
            border: 1px solid var(--pm-border);
            border-radius: 6px;
            overflow: hidden;
        }

        .pm-var-row {
            display: grid;
            grid-template-columns: 180px 1fr 34px;
            border-bottom: 1px solid var(--pm-border);
        }

        .pm-var-row:last-child {
            border-bottom: none;
        }

        .pm-var-row.pm-var-head {
            background: var(--primary);
        }

        .pm-var-cell {
            padding: 7px 10px;
            font-size: 12px;
        }

        .pm-var-cell:not(:last-child) {
            border-right: 1px solid var(--pm-border);
        }

        .pm-var-row.pm-var-head .pm-var-cell {
            font-size: 11px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .pm-var-input {
            width: 100%;
            border: none;
            outline: none;
            font-size: 12px;
            color: var(--pm-text);
            font-family: 'JetBrains Mono', monospace;
            background: transparent;
        }

        .pm-var-input.key {
            color: var(--primary);
            font-weight: 500;
        }

        .pm-var-input::placeholder {
            color: var(--pm-light);
        }

        .pm-var-del-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            background: transparent;
            border: none;
            color: var(--pm-light);
            cursor: pointer;
            font-size: 16px;
            transition: color .1s;
            width: 100%;
        }

        .pm-var-del-btn:hover {
            color: var(--pm-danger);
        }

        .pm-var-add-row {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 10px;
            border-top: 1px solid var(--pm-border);
            color: var(--primary);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: background .1s;
            border-radius: 0 0 6px 6px;
            background: none;
            width: 100%;
            font-family: 'Inter', sans-serif;
        }

        .pm-var-add-row:hover {
            background: var(--primary);
        }

        /* ── TOGGLES ── */
        .pm-toggle-list {
            border: 1px solid var(--pm-border);
            border-radius: 6px;
            overflow: hidden;
        }

        .pm-toggle-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            border-bottom: 1px solid var(--pm-border);
        }

        .pm-toggle-item:last-child {
            border-bottom: none;
        }

        .pm-toggle-label {
            font-size: 13px;
            font-weight: 500;
        }

        .pm-toggle-sub {
            font-size: 12px;
            color: var(--pm-light);
            margin-top: 1px;
        }

        .pm-toggle-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pm-toggle-input {
            display: none;
        }

        .pm-toggle-knob {
            width: 36px;
            height: 20px;
            background: var(--pm-border2);
            border-radius: 20px;
            position: relative;
            cursor: pointer;
            transition: background .2s;
            flex-shrink: 0;
            display: block;
        }

        .pm-toggle-knob::after {
            content: '';
            position: absolute;
            left: 2px;
            top: 2px;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            transition: left .2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .2);
        }

        .pm-toggle-input:checked+.pm-toggle-knob {
            background: var(--primary);
        }

        .pm-toggle-input:checked+.pm-toggle-knob::after {
            left: 18px;
        }

        /* ── VERSIONS ── */
        .pm-version-list {
            border: 1px solid var(--pm-border);
            border-radius: 6px;
            overflow: hidden;
        }

        .pm-version-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            border-bottom: 1px solid var(--pm-border);
        }

        .pm-version-item:last-child {
            border-bottom: none;
        }

        .pm-version-tag {
            font-size: 11px;
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
            background: var(--primary);
            color: var(--primary);
            padding: 2px 8px;
            border-radius: 4px;
            flex-shrink: 0;
        }

        .pm-version-date {
            font-size: 12px;
            color: white;
        }

        .pm-version-by {
            font-size: 12px;
            color: var(--pm-light);
        }

        .pm-version-restore {
            margin-left: auto;
            font-size: 12px;
            color: var(--primary);
            font-weight: 500;
            background: none;
            border: none;
            cursor: pointer;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            transition: background .1s;
        }

        .pm-version-restore:hover {
            background: var(--primary);
        }

        /* ── FOOTER ── */
        .pm-editor-footer {
            padding: 12px 20px;
            border-top: 1px solid var(--pm-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .pm-footer-meta {
            font-size: 12px;
            color: var(--pm-light);
        }

        /* ── ALERT ── */
        .pm-alert {
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pm-alert-success {
            background: var(--pm-success-bg);
            color: var(--pm-success);
            border: 1px solid #bbf7d0;
        }

        .pm-alert-error {
            background: var(--pm-danger-bg);
            color: var(--pm-danger);
            border: 1px solid #fecaca;
        }

        /* ── MODAL ── */
        .pm-modal-bg {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .4);
            backdrop-filter: blur(2px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pm-modal {
            background: var(--pm-white);
            border-radius: 10px;
            width: 460px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
        }

        .pm-modal h2 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .pm-modal-sub {
            font-size: 13px;
            color: white;
            margin-bottom: 20px;
        }

        .pm-modal-foot {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--pm-border);
        }

        /* scrollbar */
        .pm-skill-items::-webkit-scrollbar {
            width: 4px;
        }

        .pm-skill-items::-webkit-scrollbar-track {
            background: var(--primary);
        }

        .pm-skill-items::-webkit-scrollbar-thumb {
            background: var(--pm-border2);
            border-radius: 4px;
        }
    </style>
@endpush

@section('content')

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between">
            <h1 class="page-header-title"><i class="tio-filter-list"></i> Add New </h1>
            <div class="page-header-select-wrapper">
 <button class="pm-btn pm-btn-outline"
                        onclick="document.getElementById('modal-new').style.display='flex'">
                        + New Skill
                    </button>
            </div>
        </div>

        <div class="pm-wrap">

            {{-- Flash messages --}} 
            @if (session('success'))
                <div class="pm-alert pm-alert-success">✓ {{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="pm-alert pm-alert-error">{{ $errors->first() }}</div>
            @endif  

            {{-- user_type TABS --}}
            <div class="pm-tabs">
                @foreach (['admin' => 'Admin', 'user' => 'User', 'vendor' => 'Vendor'] as $cat => $label)
                    <a href="{{ route('admin.prompt.index', array_merge(request()->except('user_type', 'selected'), ['user_type' => $cat])) }}"
                        class="pm-tab {{ $user_type === $cat ? 'active' : '' }}">
                        {{ $label }}
                        <span class="pm-tab-count">{{ $counts[$cat] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>

            {{-- MAIN GRID --}}
            <div class="pm-grid">

                {{-- ── LEFT: SKILL LIST ── --}}
                <div class="pm-list-card">

                    <div class="pm-list-head">
                        <span class="pm-list-title">{{ ucfirst($user_type) }} Skills</span>
                        <span style="font-size:12px;">{{ $skills->count() }}
                            skill{{ $skills->count() !== 1 ? 's' : '' }}</span>
                    </div>

                  
                    {{-- Skill list --}}
                    <div class="pm-skill-items">
                        @forelse($skills as $skill)
                            <a href="{{ route('admin.prompt.index', array_merge(request()->only('user_type', 'search'), ['selected' => $skill->id])) }}"
                                class="pm-skill-item {{ $selected?->id === $skill->id ? 'active' : '' }}">
                                <div class="pm-skill-dot"
                                    style="background:{{ $skill->status === 'active' ? '#22c55e' : ($skill->status === 'draft' ? '#f59e0b' : '#d1d5db') }}">
                                </div>
                                <div class="pm-skill-info">
                                    <div class="pm-skill-name">{{ $skill->name }}</div>
                                    <div class="pm-skill-meta">{{ $skill->skill_type }}</div>
                                </div>
                                <span class="pm-badge {{ $skill->status }}">{{ $skill->status }}</span>
                            </a>
                        @empty
                            <div style="padding:24px;text-align:center;font-size:13px;">
                                No skills found
                            </div>
                        @endforelse
                    </div>

                    {{-- Add row --}}
                    <div class="pm-add-row" onclick="document.getElementById('modal-new').style.display='flex'">
                        <span style="font-size:16px;line-height:1">+</span> Add Skill
                    </div>

                </div>

                {{-- ── RIGHT: EDITOR ── --}}
                <div class="pm-editor-card">

                    @if (!$selected)
                        {{-- Empty state --}}
                        <div class="pm-editor-empty">
                            <div class="pm-editor-empty-icon">✦</div>
                            <h3>Select a skill to edit</h3>
                            <p>Choose from the list or create a new one</p>
                        </div>
                    @else
                    {{-- TOP BAR --}}
                            <div class="pm-editor-top">
                                <div class="pm-editor-top-left">
                                    @php
                                        $dotColors = ['admin' => '#6366f1', 'user' => '#0ea5e9', 'vendor' => '#f59e0b'];
                                    @endphp
                                    <div class="pm-editor-dot"
                                        style="background:{{ $dotColors[$selected->user_type] ?? '#6b7280' }}">
                                    </div>
                                    <span class="pm-editor-name">{{ $selected->name }}</span>
                                </div>
                                <div class="pm-editor-actions">
                                   
                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('admin.prompt.delete', $selected) }}"
                                        style="display:inline"
                                        onsubmit="return confirm('Delete \'{{ addslashes($selected->name) }}\'?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="pm-btn pm-btn-danger pm-btn-sm">Delete</button>
                                    </form>
                                    {{-- Save --}}
                                    <button type="submit" form="editor-form"
                                        class="pm-btn pm-btn-primary pm-btn-sm">Save</button>
                                </div>
                            </div>

                        {{-- ── EDITOR FORM ── --}}
                     
                        <form method="POST" action="{{ route('admin.prompt.update') }}" id="editor-form">
                            @csrf

                            <input type="hidden" name="prompt_id" value="{{ $selected->id}}">

                            {{-- BODY --}}
                            <div class="pm-editor-body">

                                {{-- Row 1: user_type / skill type / status --}}
                                <div class="pm-form-grid-3">
                                    <div>
                                        <label class="pm-form-label">Agent User Type</label>
                                        <select class="pm-select" name="user_type" disabled>
                                            <option value="{{ $selected->user_type }}" selected>
                                                {{ $selected->user_type }}</option>
                                        </select>
                                        {{-- hidden so it still submits --}}
                                        <input type="hidden" name="user_type" value="{{ $selected->user_type }}">
                                    </div>
                                    <div>
                                        <label class="pm-form-label">Skill Type</label>
                                        <select class="pm-select" name="skill_type">
                                            @foreach ($skillTypes as $val => $lbl)
                                                <option value="{{ $val }}"
                                                    {{ $selected->skill_type === $val ? 'selected' : '' }}>
                                                    {{ $lbl }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="pm-form-label">Status</label>
                                        <select class="pm-select" name="status">
                                            @foreach (['active' => 'Active', 'draft' => 'Draft'] as $val => $lbl)
                                                <option value="{{ $val }}"
                                                    {{ $selected->status === $val ? 'selected' : '' }}>
                                                    {{ $lbl }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- Name + Description --}}
                                <div class="pm-form-grid-2">
                                    <div>
                                        <label class="pm-form-label">Skill Name</label>
                                        <input class="pm-input" type="text" name="name"
                                            value="{{ old('name', $selected->name) }}" required>
                                    </div>
                                    <div>
                                        <label class="pm-form-label">Description</label>
                                        <input class="pm-input" type="text" name="description"
                                            value="{{ old('description', $selected->description) }}"
                                            placeholder="Brief description">
                                    </div>
                                </div>

                                {{-- System Prompt --}}
                                <div class="pm-section">System Prompt</div>

                                @php
                                    $promptFile = 'storage/app/prompts/' . $selected->user_type . '.txt';
                                    $promptFileExists = is_file(storage_path('app/prompts/' . $selected->user_type . '.txt'));
                                @endphp
                                @if ($selected->user_type === 'admin' && array_key_exists($selected->skill_type, \App\Services\AdminAiPersona::PERSONAS))
                                    {{-- The three admin assistants carry their brief in this column, so it is
                                         editable here. Everything after it — the live figures and the rules
                                         that stop the assistant inventing a number — is assembled in
                                         AdminAiPersona::systemPrompt() and deliberately cannot be edited from
                                         this screen. --}}
                                    <textarea name="prompt" class="pm-input" rows="7"
                                        style="width:100%;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;line-height:1.55;"
                                        placeholder="How this assistant should introduce itself and what it is for.">{{ old('prompt', $selected->prompt) }}</textarea>

                                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:12px 14px;font-size:13px;color:#1e40af;display:flex;align-items:flex-start;gap:10px;margin-top:10px;">
                                        <span style="font-size:16px;line-height:1.4">🔒</span>
                                        <div>
                                            <div style="font-weight:600;margin-bottom:4px;">Only the intro above is editable</div>
                                            <div style="color:#1d4ed8;">
                                                The live figures this assistant may quote, and the rules governing how it
                                                uses them, are built in code
                                                (<code style="background:#dbeafe;padding:2px 6px;border-radius:4px;font-size:12px;">AdminAiPersona::systemPrompt()</code>)
                                                and are appended to whatever you write here. Changing them is a developer
                                                change — they are what keep the assistant from estimating or inventing a number.
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:12px 14px;font-size:13px;color:#166534;display:flex;align-items:flex-start;gap:10px;">
                                        <span style="font-size:16px;line-height:1.4">📄</span>
                                        <div>
                                            <div style="font-weight:600;margin-bottom:4px;">Prompt loaded from file</div>
                                            <code style="background:#dcfce7;padding:3px 7px;border-radius:4px;font-size:12px;">{{ $promptFile }}</code>
                                            <div style="margin-top:6px;color:#15803d;">
                                                @if($promptFileExists)
                                                    ✓ File exists — edit it in your IDE and deploy to update the prompt.
                                                @else
                                                    ⚠ File not found — create <code>{{ $promptFile }}</code> on the server.
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Nothing on this form edits `prompt` for a file-backed agent, and update()
                                         writes whatever is posted — so without this the column would be emptied
                                         every time the agent's name or status was saved. --}}
                                    <input type="hidden" name="prompt" value="{{ $selected->prompt }}">
                                @endif

                              

                                {{-- Settings --}}
                                <div class="pm-section">Settings</div>

                                @php
                                    $settings = $selected->settings ?? [];
                                @endphp
                                <div class="pm-toggle-list">
                                    @foreach ([['inject_context', 'Inject User Context', 'Auto-include user name, ID, and role'], ['memory', 'Conversation Memory', 'Keep message history across turns'], ['tool_calling', 'Tool Calling', 'Allow AI to invoke defined tools']] as [$key, $label, $sub])
                                        <div class="pm-toggle-item">
                                            <div>
                                                <div class="pm-toggle-label">{{ $label }}</div>
                                                <div class="pm-toggle-sub">{{ $sub }}</div>
                                            </div>
                                            <div class="pm-toggle-wrap">
                                                <input class="pm-toggle-input" type="checkbox"
                                                    id="toggle-{{ $key }}" name="settings[{{ $key }}]"
                                                    value="1" {{ !empty($settings[$key]) ? 'checked' : '' }}>
                                                <label class="pm-toggle-knob" for="toggle-{{ $key }}"></label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                            

                            </div>{{-- /pm-editor-body --}}

                            {{-- FOOTER --}}
                            <div class="pm-editor-footer">
                               
                                <button type="submit" form="editor-form" class="pm-btn pm-btn-primary pm-btn-sm">
                                    ✓ Save Changes
                                </button>
                            </div>

                        </form>
                    @endif

                </div>{{-- /editor-card --}}

            </div>{{-- /pm-grid --}}

        </div>{{-- /pm-wrap --}}


        {{-- ── NEW SKILL MODAL ── --}}
        <div id="modal-new" class="pm-modal-bg" style="display:none"
            onclick="if(event.target===this)this.style.display='none'">
            <div class="pm-modal">
                <h2>New Skill</h2>
                <div class="pm-modal-sub">Add a new AI skill prompt to an agent</div>

                <form method="POST" action="{{ route('admin.prompt.store') }}">
                    @csrf

                    <div class="pm-form-row">
                        <label class="pm-form-label">Skill Name *</label>
                        <input class="pm-input" type="text" name="name" placeholder="e.g. Billing Support"
                            required>
                    </div>

                    <div class="pm-form-grid-2">
                        <div>
                            <label class="pm-form-label">Agent User Type *</label>
                            <select class="pm-select" name="user_type">
                                @foreach (['admin' => 'Admin', 'user' => 'User', 'vendor' => 'Vendor'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ $user_type === $val ? 'selected' : '' }}>
                                        {{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="pm-form-label">Skill Type *</label>
                            <select class="pm-select" name="skill_type">
                                @foreach ($skillTypes as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="pm-form-row">
                        <label class="pm-form-label">Description</label>
                        <input class="pm-input" type="text" name="description"
                            placeholder="What does this skill handle?">
                    </div>

                    <input type="hidden" name="status" value="draft">

                    <div class="pm-modal-foot">
                        <button type="button" class="pm-btn pm-btn-outline"
                            onclick="document.getElementById('modal-new').style.display='none'">
                            Cancel
                        </button>
                        <button type="submit" class="pm-btn pm-btn-primary">Create Skill</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>

        function pmRemoveVar(i) {
            const row = document.getElementById('pm-var-' + i);
            if (row) row.remove();
        }
    </script>
@endpush
