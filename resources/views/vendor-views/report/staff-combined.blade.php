@extends('layouts.vendor.app')

@section('title', 'My Performance')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .rep-wrap { padding: 0 4px 40px; }
        .date-range-form { align-items: center; gap: 8px; }

        /* tabs */
        .rep-tabs { display: flex; gap: 3px; border-bottom: 2px solid #e4e4e7; margin-bottom: 16px; }
        .rep-tab {
            padding: 8px 18px; border-radius: 7px 7px 0 0; font-size: 13px; font-weight: 700;
            color: #71717a; cursor: pointer; background: none; border: 1px solid transparent;
            border-bottom: none; transition: all .13s;
        }
        .rep-tab.active { background: #fff; color: #18181b; border-color: #e4e4e7; margin-bottom: -2px; }
        .rep-tab-content { display: none; }
        .rep-tab-content.active { display: block; }

        /* table */
        .rep-card { background: #fff; border: 1px solid #e4e4e7; border-radius: 10px; overflow: hidden; }
        .rep-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .rep-table thead th {
            background: #f4f4f5; color: #71717a; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em; padding: 9px 14px;
            border-bottom: 1px solid #e4e4e7; text-align: left; white-space: nowrap;
        }
        .rep-table tbody tr { border-bottom: 1px solid #f4f4f5; }
        .rep-table tbody tr:last-child { border-bottom: none; }
        .rep-table tbody tr:hover { background: #fafafa; }
        .rep-table tbody td { padding: 10px 14px; color: #18181b; vertical-align: middle; }
        .rep-table .muted { color: #a1a1aa; font-size: 12px; }

        .status-badge {
            display: inline-flex; padding: 3px 9px; border-radius: 99px;
            font-size: 11px; font-weight: 700; white-space: nowrap;
        }
        .badge-green   { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
        .badge-yellow  { background: #fefce8; color: #92400e; border: 1px solid #fde68a; }
        .badge-red     { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-blue    { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-grey    { background: #f4f4f5; color: #52525b; border: 1px solid #e4e4e7; }

        .empty-state { text-align: center; padding: 48px 20px; color: #a1a1aa; font-size: 13px; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <h1 class="page-header-title"><i class="tio-chart-bar-4"></i> My Performance</h1>
            {{-- ── Date filter ── --}}
            <form action="" class="d-flex date-range-form mb-3">
                @include('vendor-views/form_modals/date_range')
                <button style="width:fit-content; white-space:nowrap"
                        class="btn btn-outline-warning" type="button"
                        data-toggle="modal" data-target="#dateRangeModal">
                    {{ translate($preset) }}
                </button>
            </form>
        </div>

        <div class="rep-wrap">

            {{-- ── Tabs ── --}}
            <div class="rep-tabs">
                <button class="rep-tab active" onclick="showTab('leads', this)">
                    Leads <span class="ml-1" style="background:#18181b;color:#fff;border-radius:99px;padding:1px 7px;font-size:10px;">{{ count($leads) }}</span>
                </button>
                <button class="rep-tab" onclick="showTab('tasks', this)">
                    Tasks <span class="ml-1" style="background:#18181b;color:#fff;border-radius:99px;padding:1px 7px;font-size:10px;">{{ count($tasks) }}</span>
                </button>
                <button class="rep-tab" onclick="showTab('projects', this)">
                    Projects <span class="ml-1" style="background:#18181b;color:#fff;border-radius:99px;padding:1px 7px;font-size:10px;">{{ count($projects) }}</span>
                </button>
            </div>

            {{-- ── Leads tab ── --}}
            <div id="tab-leads" class="rep-tab-content active">
                <div class="rep-card">
                    @if ($leads->count())
                        <table class="rep-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Assigned At</th>
                                    <th>Completed At</th>
                                    @if (hasPermission('leads_manage', 'view'))<th></th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($leads as $i => $lead)
                                    @php
                                        $status = $lead->current_status ?: 'Pending';
                                        $badgeClass = match(true) {
                                            str_contains($status, 'Cancel') => 'badge-red',
                                            $status === 'Completed'         => 'badge-green',
                                            $status === 'Confirmed'         => 'badge-blue',
                                            default                         => 'badge-yellow',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="muted">{{ $i + 1 }}</td>
                                        <td>{{ $lead->item_name }}</td>
                                        <td><span class="status-badge {{ $badgeClass }}">{{ $status }}</span></td>
                                        <td class="muted">{{ $lead->assigned_at ? \Carbon\Carbon::parse($lead->assigned_at)->format('d M Y, g:i a') : '—' }}</td>
                                        <td class="muted">{{ $lead->completed_at ? \Carbon\Carbon::parse($lead->completed_at)->format('d M Y, g:i a') : '—' }}</td>
                                        @if (hasPermission('leads_manage', 'view'))
                                            <td><a href="{{ route('vendor.service.lead-details', $lead->service_request_id) }}" class="btn action-btn btn-outline-primary"><i class="tio-visible"></i></a></td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">No leads found for this period.</div>
                    @endif
                </div>
            </div>

            {{-- ── Tasks tab ── --}}
            <div id="tab-tasks" class="rep-tab-content">
                <div class="rep-card">
                    @if ($tasks->count())
                        <table class="rep-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Task</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Completed At</th>
                                    @if (hasPermission('task', 'view'))<th></th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tasks as $i => $task)
                                    @php
                                        $badgeClass = match(true) {
                                            str_contains(strtolower($task->status ?? ''), 'complet') => 'badge-green',
                                            str_contains(strtolower($task->status ?? ''), 'cancel')  => 'badge-red',
                                            str_contains(strtolower($task->status ?? ''), 'progress') => 'badge-blue',
                                            default => 'badge-yellow',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="muted">{{ $i + 1 }}</td>
                                        <td>{{ $task->title }}</td>
                                        <td><span class="status-badge {{ $badgeClass }}">{{ $task->status ?: 'Pending' }}</span></td>
                                        <td class="muted">{{ $task->created_at ? \Carbon\Carbon::parse($task->created_at)->format('d M Y') : '—' }}</td>
                                        <td class="muted">{{ $task->completed_at ? \Carbon\Carbon::parse($task->completed_at)->format('d M Y') : '—' }}</td>
                                        @if (hasPermission('task', 'view'))
                                            <td><a href="{{ route('vendor.task.detail', $task->id) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">No tasks found for this period.</div>
                    @endif
                </div>
            </div>

            {{-- ── Projects tab ── --}}
            <div id="tab-projects" class="rep-tab-content">
                <div class="rep-card">
                    @if ($projects->count())
                        <table class="rep-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Project</th>
                                    <th>Status</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    @if (hasPermission('project', 'view'))<th></th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($projects as $i => $proj)
                                    @php
                                        $badgeClass = match(true) {
                                            $proj->progress_status === 'Completed' => 'badge-green',
                                            $proj->progress_status === 'Open'      => 'badge-blue',
                                            default                                => 'badge-grey',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="muted">{{ $i + 1 }}</td>
                                        <td>{{ $proj->project_title }}</td>
                                        <td><span class="status-badge {{ $badgeClass }}">{{ $proj->progress_status ?: '—' }}</span></td>
                                        <td class="muted">{{ $proj->start_date ? \Carbon\Carbon::parse($proj->start_date)->format('d M Y') : '—' }}</td>
                                        <td class="muted">{{ $proj->end_date   ? \Carbon\Carbon::parse($proj->end_date)->format('d M Y')   : '—' }}</td>
                                        @if (hasPermission('project', 'view'))
                                            <td><a href="{{ route('vendor.project.details', $proj->id) }}" class="btn action-btn btn-outline-primary"><i class="tio-visible"></i></a></td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">No projects found for this period.</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection

@push('script_2')
    <script>
        function showTab(name, btn) {
            document.querySelectorAll('.rep-tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.rep-tab').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + name).classList.add('active');
            btn.classList.add('active');
        }
    </script>
@endpush
