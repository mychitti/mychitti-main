@extends('layouts.vendor.app')
@section('title', 'School Dashboard')

@php $cur = fn($v) => \App\CentralLogics\Helpers::format_currency($v); @endphp

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-school"></i></span>
                <span>School Dashboard <small class="d-block">{{ now()->format('l, d M Y') }}</small></span>
            </h1>
            <div class="mt-2 mt-sm-0">
                <a href="{{ route('vendor.school.students.create') }}" class="btn btn-sm btn--primary"><i class="tio-add"></i> Admit Student</a>
                <a href="{{ route('vendor.school.fees.index') }}" class="btn btn-sm btn-outline-primary"><i class="tio-money"></i> Collect Fee</a>
            </div>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row">
        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="sch-stat sch-stat--indigo">
                <i class="tio-users sch-stat-ico"></i>
                <div class="sch-stat-label">Active Students</div>
                <div class="sch-stat-value">{{ number_format($stats['students']) }}</div>
                <div class="sch-stat-sub">{{ $stats['classes'] }} classes · {{ $stats['sections'] }} sections</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="sch-stat sch-stat--green">
                <i class="tio-money sch-stat-ico"></i>
                <div class="sch-stat-label">Fees Collected Today</div>
                <div class="sch-stat-value">{{ $cur($stats['fees_today']) }}</div>
                <div class="sch-stat-sub">This month: {{ $cur($stats['fees_month']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="sch-stat sch-stat--sky">
                <i class="tio-checkmark-circle sch-stat-ico"></i>
                <div class="sch-stat-label">Attendance Today</div>
                <div class="sch-stat-value">{{ $stats['attendance_pct'] }}%</div>
                <div class="sch-stat-sub">{{ $stats['present'] }} present of {{ $stats['marked'] }} marked</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="sch-stat sch-stat--amber">
                <i class="tio-warning sch-stat-ico"></i>
                <div class="sch-stat-label">Outstanding Dues</div>
                <div class="sch-stat-value">{{ $cur($stats['dues']) }}</div>
                <div class="sch-stat-sub">Across all unpaid invoices</div>
            </div>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="sch-section-title mt-2"><i class="tio-flash"></i> Quick Actions</div>
    <div class="row">
        @php
            $quick = [
                ['Academic Setup', 'Classes, sections, subjects', 'tio-book', 'vendor.school.academic.index'],
                ['Students', 'Admissions & profiles', 'tio-user', 'vendor.school.students.index'],
                ['Attendance', 'Mark daily attendance', 'tio-checkmark-circle-outlined', 'vendor.school.student-attendance.mark'],
                ['Fees', 'Collect & track dues', 'tio-money', 'vendor.school.fees.index'],
                ['Exams & Results', 'Marks & report cards', 'tio-album', 'vendor.school.exams.index'],
                ['Certificates', 'TC, Bonafide, Character', 'tio-receipt-outlined', 'vendor.school.certificates.index'],
            ];
        @endphp
        @foreach($quick as [$t, $s, $ic, $route])
            <div class="col-sm-6 col-lg-4 mb-3">
                <a href="{{ route($route) }}" class="sch-quick">
                    <span class="sch-quick-ico"><i class="{{ $ic }}"></i></span>
                    <span>
                        <span class="sch-quick-t d-block">{{ $t }}</span>
                        <span class="sch-quick-s">{{ $s }}</span>
                    </span>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Recent activity --}}
    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <span><i class="tio-user-add mr-1 text-primary"></i> Recent Admissions</span>
                    <a href="{{ route('vendor.school.students.index') }}" class="btn btn-xs btn-outline-primary">View all</a>
                </div>
                <div class="card-body p-0"><div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                        <thead class="thead-light"><tr><th>Admission No</th><th>Student</th><th>Class</th></tr></thead>
                        <tbody>
                        @forelse($recentStudents as $s)
                            <tr>
                                <td class="font-weight-bold">{{ $s->admission_no ?? '—' }}</td>
                                <td>{{ $s->name }}</td>
                                <td>{{ $s->currentEnrollment?->schoolClass?->name ?? '—' }}</td>
                            </tr>
                        @empty<tr><td colspan="3" class="text-center text-muted py-4">No students admitted yet.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div></div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <span><i class="tio-receipt mr-1 text-success"></i> Recent Fee Payments</span>
                    <a href="{{ route('vendor.school.fees.payments') }}" class="btn btn-xs btn-outline-primary">View all</a>
                </div>
                <div class="card-body p-0"><div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                        <thead class="thead-light"><tr><th>Receipt</th><th>Student</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                        @forelse($recentPayments as $p)
                            <tr>
                                <td class="font-weight-bold">{{ $p->receipt_no ?? '—' }}</td>
                                <td>{{ $p->student?->name ?? '—' }}</td>
                                <td class="text-right text-success font-weight-bold">{{ $cur($p->amount) }}</td>
                            </tr>
                        @empty<tr><td colspan="3" class="text-center text-muted py-4">No payments recorded yet.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div></div>
            </div>
        </div>
    </div>
</div>
@endsection
