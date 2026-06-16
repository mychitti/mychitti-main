@extends('layouts.vendor.app')
@section('title', 'Laboratory — Critical Values')

@section('content')
<div class="content container-fluid"><div class="labx">
    @include('hmis::vendor.lab._chrome')
    <div class="lab-body">
        <div class="layout-2col">
            <div>
                <div class="lcard" style="border-color:var(--redB)">
                    <div class="card-hd" style="background:#FFF5F5"><h3><div class="hd-icon" style="background:var(--ltred)">🚨</div> Critical Values — Immediate Action Required</h3><span class="pill pill-red">{{ $open->count() }} Unresolved</span></div>
                    <div style="padding:14px;display:flex;flex-direction:column;gap:12px">
                        @forelse ($open as $r)
                            @php $doc = $r->order->doctorProfile ? 'Dr. ' . trim(($r->order->doctorProfile->employee->f_name ?? '') . ' ' . ($r->order->doctorProfile->employee->l_name ?? '')) : ($r->order->referred_by ?: 'Concerned Doctor'); @endphp
                            <div style="background:var(--ltred);border:1.5px solid #FECDD3;border-radius:10px;padding:14px">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
                                    <div><div style="font-size:14px;font-weight:700;color:var(--redA)">🚨 {{ $r->parameter_name }} = {{ $r->result_value }} {{ $r->unit }} — CRITICAL {{ $r->result_flag === 'H' ? 'HIGH' : 'LOW' }}</div>
                                        <div style="font-size:12px;color:var(--muted);margin-top:3px">{{ $r->order->patient->name ?? 'Patient' }} · {{ $r->order->patient->patient_uid ?? '' }} · {{ $r->order->department }} · {{ $r->order->order_no }}</div></div>
                                    <span class="pill pill-red">Not Notified</span>
                                </div>
                                <div style="font-size:12px;background:var(--white);border-radius:7px;padding:8px 10px;margin-bottom:8px">
                                    ⚠ Reference: {{ $r->ref_range_text ?: trim(($r->normal_low ?? '') . ' – ' . ($r->normal_high ?? '')) }} {{ $r->unit }}
                                    &nbsp;|&nbsp; Critical threshold: {{ $r->result_flag === 'H' ? '> ' . $r->critical_high : '< ' . $r->critical_low }} {{ $r->unit }}
                                </div>
                                @if (hasPermission('lab_critical', 'notify'))
                                <form method="post" action="{{ route('vendor.lab.results.notify', $r->id) }}" class="mb-0" style="display:flex;gap:8px">
                                    @csrf
                                    <input type="hidden" name="doctor" value="{{ $doc }}">
                                    <button class="btn btn-red btn-sm">📞 Notify {{ $doc }}</button>
                                </form>
                                @endif
                            </div>
                        @empty
                            <div class="empty">No unresolved critical values. 🎉</div>
                        @endforelse
                    </div>
                    @if ($open->count())
                        @if (hasPermission('lab_critical', 'notify'))<div style="padding:0 14px 14px"><form method="post" action="{{ route('vendor.lab.critical.notify-all') }}" class="mb-0">@csrf<button class="btn btn-red btn-sm">📢 Notify All Doctors</button></form></div>@endif
                    @endif
                </div>
            </div>
            <div>
                <div class="lcard">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltgreen)">📋</div> Critical Value Log</h3></div>
                    @forelse ($log as $r)
                        <div class="alert-row"><div class="alert-dot" style="background:var(--greenA)"></div><div style="flex:1"><div class="alert-title">{{ $r->parameter_name }} = {{ $r->result_value }} — Notified</div><div class="alert-sub">{{ $r->order->patient->name ?? '' }} · {{ $r->critical_notified_to }} · {{ $r->critical_notified_at?->format('d M h:i A') }}</div></div><span class="pill pill-green">Resolved</span></div>
                    @empty
                        <div class="empty" style="padding:20px">No notifications logged yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div></div>
@endsection
