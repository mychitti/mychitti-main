@extends('front-views.layout')
@section('title', $student->name)

@php $cur = fn($v) => \App\CentralLogics\Helpers::format_currency($v); @endphp

@push('css_or_js')
<style>
    .sd-wrap{ width:100%; padding:24px; color:#1e293b; }
    .sd-top{ display:flex; align-items:center; gap:16px; background:#81c408; color:#fff; border-radius:20px; padding:24px; margin-bottom:22px; }
    .sd-av{ width:64px; height:64px; border-radius:16px; background:rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:800; }
    .sd-top h1{ font-size:24px; font-weight:800; margin:0; }
    .sd-top .m{ opacity:.9; font-size:14px; }
    .sd-back{ color:#fff; opacity:.9; text-decoration:none; font-size:13px; }
    .sd-cards{ display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; margin-bottom:24px; }
    .sd-stat{ background:#fff; border:1px solid #e9edf5; border-radius:16px; padding:18px; box-shadow:0 4px 16px rgba(15,23,42,.05); }
    .sd-stat .l{ font-size:12.5px; color:#64748b; font-weight:600; }
    .sd-stat .v{ font-size:26px; font-weight:800; margin-top:4px; }
    .sd-sec{ background:#fff; border:1px solid #e9edf5; border-radius:16px; padding:22px; box-shadow:0 4px 16px rgba(15,23,42,.05); margin-bottom:18px; }
    .sd-sec h3{ font-size:16px; font-weight:700; margin:0 0 14px; display:flex; align-items:center; gap:8px; }
    .sd-sec h3 i{ color:#81c408; }
    .sd-table{ width:100%; border-collapse:collapse; font-size:14px; }
    .sd-table th, .sd-table td{ padding:9px 10px; border-bottom:1px solid #eef0f5; text-align:left; }
    .sd-table th{ color:#64748b; font-size:12px; text-transform:uppercase; letter-spacing:.4px; }
    .sd-badge{ padding:3px 9px; border-radius:7px; font-size:12px; font-weight:600; }
    .b-green{ background:#dcfce7; color:#15803d; } .b-red{ background:#fee2e2; color:#b91c1c; } .b-amber{ background:#fef3c7; color:#b45309; } .b-info{ background:#e0e7ff; color:#4338ca; }
    .sd-tt{ width:100%; border-collapse:collapse; font-size:12px; }
    .sd-tt th, .sd-tt td{ border:1px solid #e5e9f2; padding:6px; text-align:center; }
    .sd-tt th{ background:#f8fafc; }
    .sd-empty{ color:#94a3b8; font-size:14px; text-align:center; padding:16px; }
    .sd-pay{ background:linear-gradient(135deg,#059669,#10b981); color:#fff; border:none; padding:9px 18px; border-radius:9px; font-weight:600; font-size:13px; }
    .sd-lbl{ font-size:12px; color:#64748b; font-weight:600; display:block; margin-bottom:4px; }
    .sd-input{ width:100%; padding:8px 10px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; background:#fff; color:#1e293b; }
    .sd-btn{ background:#81c408; color:#fff; border:none; padding:9px 20px; border-radius:9px; font-weight:600; font-size:13px; cursor:pointer; }
    .spacer{ height:80px; }
</style>
@endpush

@section('content')
<div class="spacer"></div>
<div class="container">
    <div class="dash_div d-flex align-items-start shadow rounded">
        @include('front-views.partials.dashboard._nav')
        <div class="tab-content sd-wrap" style="width:100%; min-height:300px;">
    <a href="{{ route('school.portal.index') }}" class="sd-back" style="color:#64748b;"><i class="fas fa-arrow-left"></i> My School</a>

    <div class="sd-top">
        <div class="sd-av">
            @if($student->photo)
                <img src="{{ asset('storage/app/public/school/students/' . $student->photo) }}" alt="{{ $student->name }}" style="width:100%;height:100%;border-radius:16px;object-fit:cover;">
            @else
                {{ strtoupper(substr($student->name,0,1)) }}
            @endif
        </div>
        <div style="flex:1;">
            <h1 class="text-white">{{ $student->name }}</h1>
            <div class="m">{{ $store?->name }} · {{ $student->currentEnrollment?->schoolClass?->name }} {{ $student->currentEnrollment?->section ? '- '.$student->currentEnrollment->section->name : '' }} · Adm {{ $student->admission_no }} · Roll {{ $student->currentEnrollment?->roll_no ?? '—' }}</div>
        </div>
        <a href="{{ route('school.portal.id-card', $student->id) }}" target="_blank"
           style="background:rgba(255,255,255,.2);  border: 1px solid white; color:#fff; padding:9px 15px; border-radius:10px; font-size:13px; font-weight:600; text-decoration:none; white-space:nowrap;">
            <i class="fas fa-id-card"></i> ID Card
        </a>
    </div>

    {{-- Stat cards --}}
    <div class="sd-cards">
        <div class="sd-stat"><div class="l">Attendance (This Month)</div><div class="v" style="color:#0284c7;">{{ $att['pct'] }}%</div><div class="l">{{ $att['present'] }} / {{ $att['marked'] }} days</div></div>
        <div class="sd-stat"><div class="l">Outstanding Fees</div><div class="v" style="color:{{ $fees['due'] > 0 ? '#dc2626' : '#15803d' }};">{{ $cur($fees['due']) }}</div></div>
        <div class="sd-stat"><div class="l">Date of Birth</div><div class="v" style="font-size:18px;">{{ $student->dob?->format('d M Y') ?? '—' }}</div></div>
    </div>

    {{-- Results --}}
    <div class="sd-sec">
        <h3><i class="fas fa-award"></i> Latest Results @if($result && $result['exam'])<span class="sd-badge b-info" style="font-weight:600;">{{ $result['exam']->name }}</span>@endif</h3>
        @if($result && $result['marks']->count())
            <table class="sd-table">
                <thead><tr><th>Subject</th><th>Max</th><th>Obtained</th><th>Result</th></tr></thead>
                <tbody>
                @foreach($result['marks'] as $m)
                    @php $max = $m->examSubject?->max_marks ?? 0; $pass = $m->examSubject?->pass_marks ?? 0; @endphp
                    <tr>
                        <td>{{ $m->examSubject?->subject?->name ?? '—' }}</td>
                        <td>{{ rtrim(rtrim(number_format($max,2),'0'),'.') }}</td>
                        <td>{{ $m->is_absent ? 'AB' : rtrim(rtrim(number_format($m->marks_obtained,2),'0'),'.') }}</td>
                        <td>
                            @if($m->is_absent)<span class="sd-badge b-amber">Absent</span>
                            @elseif($m->marks_obtained >= $pass)<span class="sd-badge b-green">Pass</span>
                            @else<span class="sd-badge b-red">Fail</span>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else<div class="sd-empty">No results published yet.</div>@endif
    </div>

    {{-- Fees --}}
    <div class="sd-sec">
        <h3><i class="fas fa-receipt"></i> Fees</h3>
        @if($fees['invoices']->count())
            <table class="sd-table">
                <thead><tr><th>Invoice</th><th>Date</th><th>Total</th><th>Paid</th><th>Due</th></tr></thead>
                <tbody>
                @foreach($fees['invoices'] as $inv)
                    <tr>
                        <td>{{ $inv->invoice_no }}</td>
                        <td>{{ $inv->invoice_date?->format('d M Y') }}</td>
                        <td>{{ $cur($inv->total_amount) }}</td>
                        <td>{{ $cur($inv->paid_amount) }}</td>
                        <td>@if($inv->due_amount > 0)<span class="sd-badge b-red">{{ $cur($inv->due_amount) }}</span>@else<span class="sd-badge b-green">Paid</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if($fees['due'] > 0)
                <div style="margin-top:14px; display:flex; justify-content:space-between; align-items:center;">
                    <b>Total Due: {{ $cur($fees['due']) }}</b>
                    <span style="font-size:12px; color:#94a3b8;">Online payment coming soon — please pay at the school office.</span>
                </div>
            @endif
        @else<div class="sd-empty">No fee invoices yet.</div>@endif
    </div>

    {{-- Leave Requests --}}
    <div class="sd-sec">
        <h3><i class="fas fa-calendar-day"></i> Leave Requests</h3>
        <form action="{{ route('school.portal.leave', $student->id) }}" method="POST" style="margin-bottom:18px;">
            @csrf
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; align-items:end;">
                <div>
                    <label class="sd-lbl">Type</label>
                    <select name="leave_type" class="sd-input" required>
                        @foreach(\App\Models\StudentLeave::TYPES as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="sd-lbl">From</label>
                    <input type="date" name="from_date" id="lv_from" class="sd-input" value="{{ now()->toDateString() }}" required oninput="lvSync()">
                </div>
                <div>
                    <label class="sd-lbl">To</label>
                    <input type="date" name="to_date" id="lv_to" class="sd-input" value="{{ now()->toDateString() }}" required>
                </div>
                <div style="grid-column:1 / -1;">
                    <label class="sd-lbl">Reason (optional)</label>
                    <input name="reason" class="sd-input" maxlength="500" placeholder="e.g. Fever, out of station, family function">
                </div>
                <div><button type="submit" class="sd-btn"><i class="fas fa-paper-plane"></i> Submit Request</button></div>
            </div>
        </form>
        @if($leaves->count())
            <table class="sd-table">
                <thead><tr><th>Type</th><th>Dates</th><th>Days</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($leaves as $l)
                    <tr>
                        <td>{{ $l->leave_type }}@if($l->reason)<br><span style="font-size:12px;color:#94a3b8;">{{ $l->reason }}</span>@endif</td>
                        <td>{{ $l->from_date?->format('d M') }} – {{ $l->to_date?->format('d M Y') }}</td>
                        <td>{{ $l->days }}</td>
                        <td>
                            @php $bc = ['pending' => 'b-amber', 'approved' => 'b-green', 'rejected' => 'b-red'][$l->status] ?? 'b-info'; @endphp
                            <span class="sd-badge {{ $bc }}">{{ ucfirst($l->status) }}</span>
                            @if($l->status === 'rejected' && $l->remarks)<br><span style="font-size:12px;color:#94a3b8;">{{ $l->remarks }}</span>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else<div class="sd-empty">No leave requests yet. Use the form above to request leave.</div>@endif
    </div>

    {{-- Homework --}}
    <div class="sd-sec">
        <h3><i class="fas fa-book"></i> Homework</h3>
        @if($homework->count())
            <table class="sd-table">
                <thead><tr><th>Subject</th><th>Title</th><th>Assigned</th><th>Due</th></tr></thead>
                <tbody>
                @foreach($homework as $h)
                    <tr>
                        <td>{{ $h->subject?->name ?? '—' }}</td>
                        <td>{{ $h->title }}</td>
                        <td>{{ $h->assign_date?->format('d M') }}</td>
                        <td>{{ $h->submission_date?->format('d M Y') ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else<div class="sd-empty">No homework assigned.</div>@endif
    </div>

    {{-- Timetable --}}
    <div class="sd-sec">
        <h3><i class="fas fa-table"></i> Weekly Timetable</h3>
        @if($periods->count())
            <div style="overflow-x:auto;">
                <table class="sd-tt">
                    <thead><tr><th>Period</th>@foreach($days as $dn => $day)<th>{{ \Illuminate\Support\Str::limit($day,3,'') }}</th>@endforeach</tr></thead>
                    <tbody>
                    @foreach($periods as $p)
                        <tr>
                            <td style="font-weight:700; text-align:left;">{{ $p->name }}</td>
                            @foreach($days as $dn => $day)
                                @if($p->is_break)<td style="background:#fff7ed; color:#9a3412;">Break</td>
                                @else
                                    @php $cell = $grid[$dn][$p->id] ?? null; @endphp
                                    <td>@if($cell)<b>{{ $cell->subject?->name ?? '—' }}</b>@else—@endif</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else<div class="sd-empty">Timetable not published yet.</div>@endif
    </div>

    {{-- Notices --}}
    <div class="sd-sec">
        <h3><i class="fas fa-bullhorn"></i> School Notices</h3>
        @forelse($notices as $n)
            <div style="padding:12px 0; border-bottom:1px solid #eef0f5;">
                <div style="display:flex; justify-content:space-between;">
                    <b>{{ $n->title }}</b>
                    <span style="font-size:12px; color:#94a3b8;">{{ \Carbon\Carbon::parse($n->notice_date)->format('d M Y') }}</span>
                </div>
                @if($n->body)<div style="font-size:13px; color:#64748b; margin-top:3px;">{{ $n->body }}</div>@endif
            </div>
        @empty<div class="sd-empty">No notices.</div>@endforelse
    </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    function lvSync() {
        var f = document.getElementById('lv_from'), t = document.getElementById('lv_to');
        if (!f || !t) return;
        if (t.value < f.value) t.value = f.value;
        t.min = f.value;
    }
    lvSync();
</script>
@endpush
