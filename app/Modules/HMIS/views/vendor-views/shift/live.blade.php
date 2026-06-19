@extends('layouts.vendor.app')
@section('title', 'Live Work Updates')

@push('css_or_js')
    <style>
        .lwu { --line:#e9edf3; --muted:#6b7280; --ink:#0f172a; font-size:13px; }
        .lwu .card { background:#fff; border:1px solid var(--line); border-radius:12px; margin-bottom:16px; }
        .lwu .card > .hd { padding:14px 18px; border-bottom:1px solid var(--line); font-weight:700; font-size:14px; color:var(--ink); display:flex; align-items:center; justify-content:space-between; }
        .lwu .card > .hd .sub { font-weight:500; font-size:12px; color:var(--muted); }
        .lwu .ico { width:26px; height:26px; border-radius:7px; display:inline-flex; align-items:center; justify-content:center; margin-right:8px; color:#fff; font-size:13px; }

        .lwu-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:16px; }
        @media(max-width:768px){ .lwu-stats{ grid-template-columns:1fr; } }
        .lwu-stat { background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; }
        .lwu-stat .b { width:44px; height:44px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:20px; color:#fff; flex-shrink:0; }
        .lwu-stat .v { font-size:20px; font-weight:800; color:var(--ink); line-height:1.1; }
        .lwu-stat .l { font-size:12px; color:var(--muted); }

        .lwu table { width:100%; border-collapse:collapse; }
        .lwu th { text-align:left; padding:10px 18px; font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:var(--muted); background:#f8fafc; font-weight:700; }
        .lwu td { padding:11px 18px; border-top:1px solid #f3f4f6; vertical-align:middle; }

        .lwu .person { display:flex; align-items:center; gap:10px; }
        .lwu .av { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:11px; color:#fff; flex-shrink:0; }
        .lwu .nm { font-weight:600; color:var(--ink); }
        .lwu .mt { font-size:11px; color:var(--muted); }

        .chip { font-size:11px; font-weight:600; padding:3px 9px; border-radius:7px; display:inline-block; }
        .chip.g{background:#dcfce7;color:#15803d}.chip.s{background:#f1f5f9;color:#475569}.chip.r{background:#fee2e2;color:#b91c1c}
        .chip.b{background:#dbeafe;color:#1d4ed8}.chip.v{background:#ede9fe;color:#6d28d9}.chip.a{background:#fef3c7;color:#b45309}
        .lwu-empty { color:#9aa1ab; font-size:13px; padding:26px; text-align:center; }
        .lwu-fl { font-size:12px; font-weight:600; color:#475569; margin-bottom:5px; display:block; }
        .lwu-in { width:100%; border:1px solid #d6dbe4; border-radius:9px; padding:9px 11px; font-size:13px; }
        .lwu-in:focus{ outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    </style>
@endpush

@section('content')
<div class="content container-fluid lwu">
    @include('vendor-views.partials._hr_header')

    @php
        $fmtT  = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('h:i A') : '—';
        $range = fn($s) => $s ? ($fmtT($s->start_time) . ' – ' . $fmtT($s->end_time)) : '—';
        $isLive = fn($emp) => $emp && in_array($emp->id, $liveEmpIds);
        $palette = ['#2563eb','#16a34a','#7c3aed','#db2777','#ea580c','#0891b2','#475569'];
        $av = function($name) use ($palette) {
            $name = trim($name) ?: 'NA';
            $ini = strtoupper(mb_substr($name,0,1) . (str_contains($name,' ') ? mb_substr(strrchr($name,' '),1,1) : ''));
            return ['ini'=>$ini ?: 'N','color'=>$palette[crc32($name) % count($palette)]];
        };
        $fullname = fn($e) => trim(($e->f_name ?? '') . ' ' . ($e->l_name ?? '')) ?: '—';
        $pending = $swaps->where('status','pending')->count();
    @endphp

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mt-3 mb-3">
        <h1 class="page-header-title mb-0"><span class="page-header-icon"><i class="tio-online"></i></span> Live Work Updates</h1>
        <span class="chip s"><i class="tio-time mr-1"></i> {{ $now->format('d M Y · h:i A') }}</span>
    </div>

    {{-- Summary --}}
    <div class="lwu-stats">
        <div class="lwu-stat"><div class="b" style="background:#16a34a"><i class="tio-record"></i></div>
            <div><div class="v">{{ $liveEmployees->count() }}</div><div class="l">On duty right now</div></div></div>
        <div class="lwu-stat"><div class="b" style="background:#2563eb"><i class="tio-time"></i></div>
            <div><div class="v" style="font-size:16px">{{ $present->name ?? 'No active shift' }}</div><div class="l">Present shift · {{ $range($present) }}</div></div></div>
        <div class="lwu-stat"><div class="b" style="background:#7c3aed"><i class="tio-shuffle"></i></div>
            <div><div class="v">{{ $pending }}</div><div class="l">Pending shift changes</div></div></div>
    </div>

    <div class="row">
        {{-- On duty now --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="hd"><span><span class="ico" style="background:#16a34a"><i class="tio-record"></i></span> On Duty Now</span><span class="sub">{{ $liveEmployees->count() }} clocked in</span></div>
                <table>
                    <thead><tr><th>Staff</th><th>Shift</th><th>Since</th></tr></thead>
                    <tbody>
                        @forelse($liveEmployees as $row)
                            @php $e=$row['employee']; $a=$av($fullname($e)); @endphp
                            <tr>
                                <td><div class="person"><span class="av" style="background:{{ $a['color'] }}">{{ $a['ini'] }}</span><span class="nm">{{ $fullname($e) }}</span></div></td>
                                <td>{{ $e->storeShift->name ?? '—' }}</td>
                                <td><span class="chip g">{{ $fmtT(optional($row['in_time'])) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="lwu-empty">No one is clocked in right now.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Present + next staff --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="hd"><span><span class="ico" style="background:#2563eb"><i class="tio-time"></i></span> Present Shift</span><span class="sub">{{ $present->name ?? 'None' }} · {{ $range($present) }}</span></div>
                <table>
                    <tbody>
                        @if($present)
                            @forelse($presentStaff as $r)
                                @php $emp=$r['emp']; @endphp
                                <tr>
                                    <td><span class="nm" style="{{ $r['covered_out'] ? 'text-decoration:line-through;opacity:.55' : '' }}">{{ $fullname($emp) }}</span>
                                        @if($r['cover_for'])<div class="mt"><span class="chip v">covering {{ $fullname($r['cover_for']) }}</span></div>@endif</td>
                                    <td style="text-align:right">
                                        @if($r['covered_out'])<span class="chip r">Swapped out</span>
                                        @else<span class="chip {{ $isLive($emp) ? 'g' : 's' }}">{{ $isLive($emp) ? 'On duty' : 'Not in' }}</span>@endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="lwu-empty">No staff assigned.</td></tr>
                            @endforelse
                        @else
                            <tr><td class="lwu-empty">No shift active now.</td></tr>
                        @endif
                    </tbody>
                </table>
                @if($next)
                    <div class="hd" style="border-top:1px solid var(--line)"><span><span class="ico" style="background:#64748b"><i class="tio-skip-next"></i></span> Next Shift</span><span class="sub">{{ $next->name }} · {{ $range($next) }}</span></div>
                    <table><tbody>
                        @forelse($nextStaff as $r)
                            <tr><td><span class="nm">{{ $fullname($r['emp']) }}</span>
                                @if($r['cover_for'])<span class="chip v ml-1">covering {{ $fullname($r['cover_for']) }}</span>@endif</td></tr>
                        @empty
                            <tr><td class="lwu-empty">No staff assigned.</td></tr>
                        @endforelse
                    </tbody></table>
                @endif
            </div>
        </div>
    </div>

    {{-- Today's schedule --}}
    <div class="card">
        <div class="hd"><span><span class="ico" style="background:#0891b2"><i class="tio-calendar"></i></span> Today's Shift Schedule</span></div>
        <table>
            <thead><tr><th>Shift</th><th>Timing</th><th>Assigned Staff</th><th style="text-align:center">On Duty</th></tr></thead>
            <tbody>
                @forelse($shifts as $s)
                    @php $on=$s->employees->filter(fn($e)=>$isLive($e))->count(); @endphp
                    <tr style="{{ $present && $present->id === $s->id ? 'background:#eff6ff' : '' }}">
                        <td><strong>{{ $s->name }}</strong> @if($present && $present->id === $s->id)<span class="chip b ml-1">Now</span>@endif</td>
                        <td>{{ $range($s) }}</td>
                        <td style="white-space:normal">{{ $s->employees->map(fn($e)=>$fullname($e))->filter()->implode(', ') ?: '—' }}</td>
                        <td style="text-align:center"><span class="chip {{ $on ? 'g' : 's' }}">{{ $on }} / {{ $s->employees->count() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="lwu-empty">No shifts configured. Add them under the Shifts tab.</td></tr>
                @endforelse
                @if(($unassignedStaff ?? collect())->count())
                    <tr style="background:#fffbeb">
                        <td><strong style="color:#b45309">No shift assigned</strong></td><td>—</td>
                        <td style="white-space:normal">{{ $unassignedStaff->map(fn($e)=>$fullname($e))->implode(', ') }}</td>
                        <td style="text-align:center"><span class="chip a">{{ $unassignedStaff->count() }}</span></td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Shift change --}}
    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="hd"><span><span class="ico" style="background:#7c3aed"><i class="tio-shuffle"></i></span> Request Shift Change</span></div>
                <div style="padding:16px 18px">
                    <form action="{{ route('vendor.shifts.swap.store') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-6 mb-3"><label class="lwu-fl">Date</label><input type="date" name="swap_date" class="lwu-in" value="{{ date('Y-m-d') }}" required></div>
                            <div class="col-6 mb-3"><label class="lwu-fl">Shift</label>
                                <select name="store_shift_id" class="lwu-in"><option value="">Auto</option>@foreach($shifts as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
                        </div>
                        <div class="mb-3"><label class="lwu-fl">Whose shift</label>
                            @if(auth('vendor')->check())
                                <select name="from_emp_id" class="lwu-in" required><option value="">Select staff…</option>@foreach($staff as $e)<option value="{{ $e->id }}">{{ $fullname($e) }}{{ $e->storeShift ? ' · '.$e->storeShift->name : '' }}</option>@endforeach</select>
                            @else
                                @php $me = auth('vendor_employee')->user(); @endphp
                                <input type="text" class="lwu-in" value="{{ $fullname($me) }} (you)" readonly>
                                <small class="text-muted">You can only change your own shift.</small>
                            @endif
                        </div>
                        <div class="mb-3"><label class="lwu-fl">Assign to (cover)</label>
                            <select name="to_emp_id" class="lwu-in" required><option value="">Select staff…</option>@foreach($staff as $e)<option value="{{ $e->id }}">{{ $fullname($e) }}</option>@endforeach</select></div>
                        <div class="mb-3"><label class="lwu-fl">Reason</label><textarea name="reason" class="lwu-in" rows="2" placeholder="e.g. unwell, personal work…"></textarea></div>
                        <button class="btn btn--primary btn-block">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="hd"><span><span class="ico" style="background:#475569"><i class="tio-history"></i></span> Shift Change Requests</span></div>
                <table>
                    <thead><tr><th>Date</th><th>From → To</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($swaps as $sw)
                            <tr>
                                <td>{{ $sw->swap_date?->format('d M Y') }}<div class="mt">{{ $sw->shift->name ?? '' }}</div></td>
                                <td><span class="mt">{{ $fullname($sw->fromEmployee) }}</span> <i class="tio-arrow-forward" style="font-size:11px"></i> <strong>{{ $fullname($sw->toEmployee) }}</strong>
                                    @if($sw->reason)<div class="mt">{{ \Illuminate\Support\Str::limit($sw->reason, 50) }}</div>@endif</td>
                                <td><span class="chip {{ $sw->status==='approved'?'g':($sw->status==='rejected'?'r':'a') }}">{{ ucfirst($sw->status) }}</span></td>
                                <td style="text-align:right;white-space:nowrap">
                                    @if($sw->status === 'pending' && (auth('vendor')->check() || hasPermission('shift_manage', 'edit')))
                                        <a href="{{ route('vendor.shifts.swap.status', [$sw->id, 'approved']) }}" class="btn btn-xs btn-soft-success">Approve</a>
                                        <a href="{{ route('vendor.shifts.swap.status', [$sw->id, 'rejected']) }}" class="btn btn-xs btn-soft-danger">Reject</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="lwu-empty">No shift change requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
