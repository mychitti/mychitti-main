@extends('layouts.vendor.app')
@section('title', $student->name . ' — Dashboard')

@php
    $cur = fn($v) => \App\CentralLogics\Helpers::format_currency($v);
    $examsAppeared = $examPerf->count();
    $avgExam = $examsAppeared ? round($examPerf->avg('pct'), 1) : 0;
    $examMax = max(1, (float) ($examPerf->max('pct') ?? 0));
    $attTrendMax = 100;
@endphp

@section('content')
<div class="content container-fluid school-page"> 
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-chart-bar-1 mr-1"></i> {{ $student->name }}
            <small class="text-muted" style="font-size:14px;">· {{ $student->currentEnrollment?->schoolClass?->name }}
                {{ $student->currentEnrollment?->section ? '- '.$student->currentEnrollment->section->name : '' }} · Adm {{ $student->admission_no }}</small>
        </h1>
        <div class="d-flex" style="gap:8px;">
            <a href="{{ route('vendor.school.students.show', $student->id) }}" class="btn btn-sm btn-outline-secondary">Profile</a>
            <a href="{{ route('vendor.school.students.id-card', $student->id) }}" target="_blank" class="btn btn-sm btn-outline-info">ID Card</a>
        </div>
    </div>

    {{-- Headline tiles --}}
    <div class="row">
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--sky">
            <i class="tio-checkmark-circle sch-stat-ico"></i><div class="sch-stat-label">Attendance (overall)</div>
            <div class="sch-stat-value">{{ $attPct }}%</div><div class="sch-stat-sub">{{ $attMarked }} days marked</div>
        </div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--amber">
            <i class="tio-warning sch-stat-ico"></i><div class="sch-stat-label">Outstanding Dues</div>
            <div class="sch-stat-value">{{ $cur($fee['due']) }}</div><div class="sch-stat-sub">{{ $cur($fee['paid']) }} paid of {{ $cur($fee['billed']) }}</div>
        </div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--indigo">
            <i class="tio-poll sch-stat-ico"></i><div class="sch-stat-label">Exams Appeared</div>
            <div class="sch-stat-value">{{ $examsAppeared }}</div><div class="sch-stat-sub">across this record</div>
        </div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--green">
            <i class="tio-chart-bar-3 sch-stat-ico"></i><div class="sch-stat-label">Average Exam %</div>
            <div class="sch-stat-value">{{ $avgExam }}%</div><div class="sch-stat-sub">mean across exams</div>
        </div></div>
    </div>

    <div class="row">
        {{-- Attendance trend --}}
        <div class="col-lg-7 mb-3">
            <div class="card h-100"><div class="card-header py-3"><i class="tio-chart-line-up mr-1 text-primary"></i> Attendance % — Last 6 Months</div>
            <div class="card-body">
                <div class="d-flex align-items-end justify-content-between" style="height:170px; gap:10px;">
                    @forelse($attTrend as $t)
                        <div class="text-center" style="flex:1;">
                            <div class="text-muted" style="font-size:11px;">{{ $t->pct }}%</div>
                            <div style="height:130px; display:flex; align-items:flex-end;">
                                <div style="width:100%;border-radius:6px 6px 0 0;background:linear-gradient(180deg,#0ea5e9,#0284c7);height:{{ max(2, round($t->pct / $attTrendMax * 130)) }}px;"></div>
                            </div>
                            <div class="text-muted mt-1" style="font-size:11px;">{{ $t->label }}</div>
                        </div>
                    @empty<div class="text-muted">No attendance data.</div>@endforelse
                </div>
            </div></div>
        </div>

        {{-- Attendance tally --}}
        <div class="col-lg-5 mb-3">
            <div class="card h-100"><div class="card-header py-3"><i class="tio-pie-chart mr-1 text-primary"></i> Attendance Breakdown</div>
            <div class="card-body">
                @php $tally = ['present'=>['Present','#16a34a'],'absent'=>['Absent','#dc2626'],'late'=>['Late','#d97706'],'half_day'=>['Half-Day','#0891b2'],'leave'=>['Leave','#6366f1']]; @endphp
                @foreach($tally as $k => $meta)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between" style="font-size:13px;">
                            <span>{{ $meta[0] }}</span><span class="text-muted">{{ $attTotals[$k] }} ({{ $attMarked ? round($attTotals[$k]/$attMarked*100) : 0 }}%)</span>
                        </div>
                        <div style="height:8px;background:#f1f5f9;border-radius:5px;overflow:hidden;">
                            <div style="height:100%;width:{{ $attMarked ? round($attTotals[$k]/$attMarked*100) : 0 }}%;background:{{ $meta[1] }};border-radius:5px;"></div>
                        </div>
                    </div>
                @endforeach
            </div></div>
        </div>
    </div>

    <div class="row">
        {{-- Exam performance --}}
        <div class="col-lg-7 mb-3">
            <div class="card h-100"><div class="card-header py-3"><i class="tio-poll-2 mr-1 text-primary"></i> Exam Performance</div>
            <div class="card-body">
                @forelse($examPerf as $e)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between" style="font-size:13px;">
                            <span class="font-weight-bold">{{ $e->name }}
                                <span class="badge badge-soft-{{ $e->result === 'Pass' ? 'success' : 'danger' }}">{{ $e->result }}</span></span>
                            <span class="text-muted">{{ $e->pct }}%</span>
                        </div>
                        <div style="height:8px;background:#eef2ff;border-radius:5px;overflow:hidden;">
                            <div style="height:100%;width:{{ round($e->pct / $examMax * 100) }}%;background:linear-gradient(90deg,#4f46e5,#7c3aed);border-radius:5px;"></div>
                        </div>
                    </div>
                @empty<div class="text-center text-muted py-4">No exam results recorded yet.</div>@endforelse
            </div></div>
        </div>

        {{-- Leave history --}}
        <div class="col-lg-5 mb-3">
            <div class="card h-100"><div class="card-header py-3"><i class="tio-calendar-note mr-1 text-primary"></i> Recent Leave</div>
            <div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                    <thead class="thead-light"><tr><th>Type</th><th>Dates</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($leaves as $l)
                        <tr>
                            <td>{{ $l->leave_type }}</td>
                            <td><small>{{ $l->from_date?->format('d M') }} – {{ $l->to_date?->format('d M') }}</small></td>
                            <td>@php $bc=['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$l->status]??'secondary'; @endphp
                                <span class="badge badge-soft-{{ $bc }}">{{ ucfirst($l->status) }}</span></td>
                        </tr>
                    @empty<tr><td colspan="3" class="text-center text-muted py-4">No leave records.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>
        </div>
    </div>

    {{-- Fees --}}
    <div class="card mb-3"><div class="card-header py-3"><i class="tio-money mr-1 text-primary"></i> Fee Invoices</div>
    <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
            <thead class="thead-light"><tr><th>Invoice</th><th>Date</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Due</th></tr></thead>
            <tbody>
            @forelse($fee['invoices'] as $inv)
                <tr>
                    <td>{{ $inv->invoice_no }}</td>
                    <td>{{ $inv->invoice_date?->format('d M Y') }}</td>
                    <td class="text-right">{{ $cur($inv->total_amount) }}</td>
                    <td class="text-right">{{ $cur($inv->paid_amount) }}</td>
                    <td class="text-right">@if($inv->due_amount > 0)<span class="badge badge-soft-danger">{{ $cur($inv->due_amount) }}</span>@else<span class="badge badge-soft-success">Paid</span>@endif</td>
                </tr>
            @empty<tr><td colspan="5" class="text-center text-muted py-4">No fee invoices yet.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>
</div>
@endsection
