@extends('layouts.vendor.app')
@section('title', 'School Reports')

@php $cur = fn($v) => \App\CentralLogics\Helpers::format_currency($v); @endphp

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h1 class="page-header-title mb-0"><i class="tio-chart-bar-1 mr-1"></i> Reports &amp; Analytics</h1>
            <form method="GET" class="mt-2 mt-sm-0 d-flex flex-wrap align-items-center" style="gap:8px;">
                <select name="session" class="form-control form-control-sm" onchange="this.form.submit()" style="width:92px;">
                    @foreach($sessions as $s)<option value="{{ $s->id }}" @selected((string)$sessionId===(string)$s->id)>{{ $s->name }}</option>@endforeach
                </select>
                @if($exams->isNotEmpty())
                <select name="exam" class="form-control form-control-sm" onchange="this.form.submit()" style="width:197px;">
                    @foreach($exams as $e)<option value="{{ $e->id }}" @selected((string)$examId===(string)$e->id)>{{ $e->schoolClass?->name ? $e->schoolClass->name.' — ' : '' }}{{ $e->name }}</option>@endforeach
                </select>
                @endif
                <a href="{{ route('vendor.school.reports.export', array_filter(['session' => $sessionId, 'exam' => $examId])) }}" class="btn  btn-outline-success">
                    <i class="tio-download-to mr-1"></i> Export CSV
                </a>
            </form>
        </div>
    </div>

    {{-- Headline cards --}}
    <div class="row">
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--indigo">
            <i class="tio-users sch-stat-ico"></i><div class="sch-stat-label">Active Students</div>
            <div class="sch-stat-value">{{ number_format($totalStudents) }}</div>
            <div class="sch-stat-sub">{{ $enrollTotal }} enrolled this session</div>
        </div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--green">
            <i class="tio-money sch-stat-ico"></i><div class="sch-stat-label">Collected This Month</div>
            <div class="sch-stat-value">{{ $cur($collectedMonth) }}</div>
            <div class="sch-stat-sub">{{ now()->format('F Y') }}</div>
        </div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--amber">
            <i class="tio-warning sch-stat-ico"></i><div class="sch-stat-label">Outstanding Dues</div>
            <div class="sch-stat-value">{{ $cur($totalDues) }}</div>
            <div class="sch-stat-sub">across unpaid invoices</div>
        </div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--sky">
            <i class="tio-checkmark-circle sch-stat-ico"></i><div class="sch-stat-label">Attendance (This Month)</div>
            <div class="sch-stat-value">{{ $attPct }}%</div>
            <div class="sch-stat-sub">{{ $attPresent }} present of {{ $attMarked }} marked</div>
        </div></div>
    </div>

    <div class="row">
        {{-- Enrollment by class --}}
        <div class="col-lg-7 mb-3">
            <div class="card h-100"><div class="card-header py-3 d-flex justify-content-between align-items-center">
                <span><i class="tio-poll mr-1 text-primary"></i> Enrollment by Class</span>
                <span class="badge badge-soft-info">{{ $enrollTotal }} students</span>
            </div>
            <div class="card-body">
                @forelse($enrollByClass as $row)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between" style="font-size:13px;">
                            <span class="font-weight-bold">{{ $row->name }}</span><span class="text-muted">{{ $row->count }}</span>
                        </div>
                        <div style="height:8px;background:#eef2ff;border-radius:5px;overflow:hidden;">
                            <div style="height:100%;width:{{ round($row->count / $enrollMax * 100) }}%;background:linear-gradient(90deg,#4f46e5,#7c3aed);border-radius:5px;"></div>
                        </div>
                    </div>
                @empty<div class="text-center text-muted py-4">No enrollment data.</div>@endforelse
            </div></div>
        </div>

        {{-- Gender + quick links --}}
        <div class="col-lg-5 mb-3">
            <div class="card mb-3"><div class="card-header justify-content-start py-3"><i class="tio-group-equal mr-1 text-primary"></i> Gender Split</div>
            <div class="card-body">
                @php $gTotal = max(1, $gender['male'] + $gender['female'] + $gender['other']); @endphp
                @foreach(['male' => ['Male','#0284c7'], 'female' => ['Female','#e11d48'], 'other' => ['Other','#64748b']] as $k => $meta)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between" style="font-size:13px;">
                            <span>{{ $meta[0] }}</span><span class="text-muted">{{ $gender[$k] }} ({{ round($gender[$k]/$gTotal*100) }}%)</span>
                        </div>
                        <div style="height:8px;background:#f1f5f9;border-radius:5px;overflow:hidden;">
                            <div style="height:100%;width:{{ round($gender[$k]/$gTotal*100) }}%;background:{{ $meta[1] }};border-radius:5px;"></div>
                        </div>
                    </div>
                @endforeach
            </div></div>
            <div class="card"><div class="card-header justify-content-start py-3"><i class="tio-folder mr-1 text-primary"></i> Detailed Reports</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('vendor.school.student-attendance.report') }}" class="list-group-item list-group-item-action"><i class="tio-checkmark-circle mr-2 text-primary"></i> Attendance Report</a>
                    <a href="{{ route('vendor.school.fees.payments') }}" class="list-group-item list-group-item-action"><i class="tio-money mr-2 text-primary"></i> Fee Collection Report</a>
                    <a href="{{ route('vendor.school.fees.index') }}" class="list-group-item list-group-item-action"><i class="tio-warning mr-2 text-primary"></i> Dues &amp; Defaulters</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Collection trend --}}
        <div class="col-lg-7 mb-3">
            <div class="card h-100"><div class="card-header  justify-content-start py-3"><i class="tio-chart-bar-1 mr-1 text-primary"></i> Fee Collection — Last 6 Months</div>
            <div class="card-body">
                <div class="d-flex align-items-end justify-content-between" style="height:170px; gap:10px;">
                    @forelse($collectionTrend as $t)
                        <div class="text-center" style="flex:1;">
                            <div title="{{ $cur($t->total) }}" style="height:130px; display:flex; align-items:flex-end;">
                                <div style="width:100%;border-radius:6px 6px 0 0;background:linear-gradient(180deg,#4f46e5,#7c3aed);height:{{ max(2, round($t->total / $trendMax * 130)) }}px;"></div>
                            </div>
                            <div class="text-muted mt-1" style="font-size:11px;">{{ $t->label }}</div>
                        </div>
                    @empty<div class="text-muted">No collection data.</div>@endforelse
                </div>
            </div></div>
        </div>

        {{-- Top defaulters --}}
        <div class="col-lg-5 mb-3">
            <div class="card h-100"><div class="card-header  justify-content-start py-3"><i class="tio-warning mr-1 text-danger"></i> Top Defaulters</div>
            <div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                    <thead class="thead-light"><tr><th>Student</th><th class="text-right">Due</th></tr></thead>
                    <tbody>
                    @forelse($defaulters as $d)
                        <tr>
                            <td>{{ $d->student?->name ?? '—' }}<br><small class="text-muted">{{ $d->student?->admission_no }}</small></td>
                            <td class="text-right text-danger font-weight-bold">{{ $cur($d->due) }}</td>
                        </tr>
                    @empty<tr><td colspan="2" class="text-center text-muted py-4">No outstanding dues 🎉</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>
        </div>
    </div>

    {{-- Attendance trend --}}
    <div class="row">
        <div class="col-12 mb-3">
            <div class="card"><div class="card-header justify-content-start py-3"><i class="tio-chart-line-up mr-1 text-primary"></i> Attendance % — Last 6 Months</div>
            <div class="card-body">
                <div class="d-flex align-items-end justify-content-between" style="height:170px; gap:10px;">
                    @forelse($attendanceTrend as $t)
                        <div class="text-center" style="flex:1;">
                            <div class="text-muted" style="font-size:11px;">{{ $t->pct }}%</div>
                            <div title="{{ $t->pct }}%" style="height:130px; display:flex; align-items:flex-end;">
                                <div style="width:100%;border-radius:6px 6px 0 0;background:linear-gradient(180deg,#0ea5e9,#0284c7);height:{{ max(2, round($t->pct / 100 * 130)) }}px;"></div>
                            </div>
                            <div class="text-muted mt-1" style="font-size:11px;">{{ $t->label }}</div>
                        </div>
                    @empty<div class="text-muted">No attendance data.</div>@endforelse
                </div>
            </div></div>
        </div>
    </div>

    {{-- Exam performance --}}
    @if($examStats)
    <div class="row">
        <div class="col-12 mb-2">
            <h5 class="mb-2"><i class="tio-poll-2 mr-1 text-primary"></i> Exam Performance
                <span class="text-muted" style="font-size:14px;">— {{ $examStats['name'] }}</span></h5>
        </div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--indigo">
            <i class="tio-user-screen sch-stat-ico"></i><div class="sch-stat-label">Appeared</div>
            <div class="sch-stat-value">{{ $examStats['appeared'] }}</div>
            <div class="sch-stat-sub">students with marks</div>
        </div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--green">
            <i class="tio-checkmark-circle sch-stat-ico"></i><div class="sch-stat-label">Pass Rate</div>
            <div class="sch-stat-value">{{ $examStats['pass_pct'] }}%</div>
            <div class="sch-stat-sub">{{ $examStats['pass'] }} of {{ $examStats['appeared'] }} passed</div>
        </div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--sky">
            <i class="tio-chart-bar-3 sch-stat-ico"></i><div class="sch-stat-label">Class Average</div>
            <div class="sch-stat-value">{{ $examStats['avg_pct'] }}%</div>
            <div class="sch-stat-sub">mean percentage</div>
        </div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--amber">
            <i class="tio-warning sch-stat-ico"></i><div class="sch-stat-label">Failed</div>
            <div class="sch-stat-value">{{ $examStats['appeared'] - $examStats['pass'] }}</div>
            <div class="sch-stat-sub">{{ $examStats['appeared'] > 0 ? 100 - $examStats['pass_pct'] : 0 }}% of appeared</div>
        </div></div>
    </div>
    <div class="row">
        {{-- Subject-wise average --}}
        <div class="col-lg-7 mb-3">
            <div class="card h-100"><div class="card-header justify-content-start py-3"><i class="tio-poll mr-1 text-primary"></i> Subject-wise Average</div>
            <div class="card-body">
                @forelse($subjectAvg as $row)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between" style="font-size:13px;">
                            <span class="font-weight-bold">{{ $row->name }}</span><span class="text-muted">{{ $row->avg }}%</span>
                        </div>
                        <div style="height:8px;background:#eef2ff;border-radius:5px;overflow:hidden;">
                            <div style="height:100%;width:{{ round($row->avg / $subjectAvgMax * 100) }}%;background:linear-gradient(90deg,#4f46e5,#7c3aed);border-radius:5px;"></div>
                        </div>
                    </div>
                @empty<div class="text-center text-muted py-4">No marks entered for this exam.</div>@endforelse
            </div></div>
        </div>

        {{-- Top performers --}}
        <div class="col-lg-5 mb-3">
            <div class="card h-100"><div class="card-header justify-content-start py-3"><i class="tio-award mr-1 text-warning"></i> Top Performers</div>
            <div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                    <thead class="thead-light"><tr><th>#</th><th>Student</th><th class="text-right">%</th></tr></thead>
                    <tbody>
                    @forelse($topPerformers as $i => $p)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>{{ $p->name }}<br><small class="text-muted">{{ $p->admission_no }}</small></td>
                            <td class="text-right font-weight-bold text-success">{{ $p->pct }}%</td>
                        </tr>
                    @empty<tr><td colspan="3" class="text-center text-muted py-4">No results yet.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>
        </div>
    </div>
    @endif
</div>
@endsection
