@extends('layouts.vendor.app')
@section('title', 'Radiology — Urgent Findings')

@section('content')
<div class="content container-fluid"><div class="radx">
    @include('hmis::vendor.radiology._chrome')
    <div class="rad-body">
        @php $canNotify = hasPermission('radiology_urgent', 'notify'); @endphp
        <div class="layout-2col">
            <div>
                <div class="card" style="border-color:var(--redB)">
                    <div class="card-hd" style="background:#FFF5F5"><h3><div class="hd-icon" style="background:var(--ltred)">🚨</div> Urgent / Critical Radiology Findings</h3><span class="pill pill-red">{{ $open->count() }} Unresolved</span></div>
                    <div style="padding:14px;display:flex;flex-direction:column;gap:12px">
                        @forelse ($open as $s)
                            @php $doc = $s->doctorProfile ? 'Dr. '.trim(($s->doctorProfile->employee->f_name ?? '').' '.($s->doctorProfile->employee->l_name ?? '')) : ($s->referred_by ?: 'Referring doctor'); @endphp
                            <div style="background:var(--ltred);border:1.5px solid #FECDD3;border-radius:10px;padding:14px">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
                                    <div><div style="font-size:14px;font-weight:700;color:var(--redA)">🚨 {{ \Illuminate\Support\Str::limit($s->impression ?: $s->study_name, 50) }} — {{ $s->modality }}</div>
                                        <div style="font-size:12px;color:var(--muted);margin-top:3px">{{ $s->patient->name ?? 'Patient' }} · {{ $s->patient->patient_uid ?? '' }} · {{ $s->study_no }} · {{ $s->created_at?->format('d M Y') }}</div></div>
                                    <span class="pill pill-red">Not Notified</span>
                                </div>
                                @if($s->findings)<div style="font-size:12px;background:var(--white);border-radius:7px;padding:9px 11px;margin-bottom:10px;line-height:1.6">{{ \Illuminate\Support\Str::limit($s->findings, 240) }}</div>@endif
                                <div style="display:flex;gap:8px">
                                    @if($canNotify)<form method="post" action="{{ route('vendor.radiology.studies.notify', $s->id) }}" class="mb-0"><input type="hidden" name="doctor" value="{{ $doc }}">@csrf<button class="btn btn-red btn-sm">📞 Notify {{ $doc }}</button></form>@endif
                                    <a href="{{ route('vendor.radiology.studies.print', $s->id) }}" target="_blank" class="btn btn-amber btn-sm">📄 View Report</a>
                                </div>
                            </div>
                        @empty
                            <div class="empty">No unresolved urgent findings. 🎉</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div>
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltgreen)">✓</div> Resolved Urgent Findings</h3></div>
                    @forelse ($resolved as $s)
                        <div class="alert-row"><div class="alert-dot" style="background:var(--greenA)"></div><div style="flex:1"><div class="alert-title">{{ $s->study_no }} — {{ \Illuminate\Support\Str::limit($s->impression ?: $s->study_name, 28) }}</div><div class="alert-sub">{{ $s->patient->name ?? '' }} · {{ $s->critical_notified_to }} · {{ $s->critical_notified_at?->format('d M h:i A') }}</div></div><span class="pill pill-green">Resolved</span></div>
                    @empty
                        <div class="empty" style="padding:20px">No resolved findings yet.</div>
                    @endforelse
                </div>
                <div class="card">
                    <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">ℹ</div> Urgent Finding Protocol</h3></div>
                    <div style="padding:12px 14px;font-size:12px;line-height:1.9;color:var(--muted)">
                        📞 <strong>Step 1:</strong> Call referring doctor immediately<br>
                        📋 <strong>Step 2:</strong> Document notification in HMIS<br>
                        📄 <strong>Step 3:</strong> Issue preliminary report<br>
                        ✅ <strong>Step 4:</strong> Follow up within 1 hour<br>
                        📊 <strong>Step 5:</strong> Final report within 24 hours
                    </div>
                </div>
            </div>
        </div>
    </div>
</div></div>
@endsection
