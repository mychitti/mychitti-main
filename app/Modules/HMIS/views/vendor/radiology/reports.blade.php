@extends('layouts.vendor.app')
@section('title', 'Radiology — Reports')

@section('content')
<div class="content container-fluid"><div class="radx">
    @include('hmis::vendor.radiology._chrome')
    <div class="rad-body">
        @php
            $canSend = hasPermission('radiology_report', 'send');
            $cols = 'grid-template-columns:100px 1fr 110px 150px 100px 100px 130px';
            $modPill = fn($m) => ['X-Ray'=>'pill-blue','CT Scan'=>'pill-purple','MRI'=>'pill-purple','Ultrasound'=>'pill-teal','ECG'=>'pill-amber'][$m] ?? 'pill-blue';
        @endphp
        <div class="card">
            <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltgreen)">📄</div> Completed Radiology Reports</h3></div>
            <div class="search-bar">
                <form method="get" class="search-wrap mb-0"><input class="si" name="search" value="{{ request('search') }}" placeholder="🔍 Search patient, study ID..."></form>
                <form method="get" class="mb-0"><select name="modality" class="fsel" onchange="this.form.submit()"><option value="">All Modalities</option>@foreach (['X-Ray','CT Scan','MRI','Ultrasound','ECG'] as $m)<option value="{{ $m }}" {{ request('modality')===$m?'selected':'' }}>{{ $m }}</option>@endforeach</select></form>
            </div>
            <div class="tbl-hd" style="{{ $cols }}"><div>Study ID</div><div>Patient</div><div>Modality</div><div>Study</div><div>Date</div><div>Status</div><div>Actions</div></div>
            @forelse ($studies as $s)
                <div class="tbl-row" style="{{ $cols }}">
                    <div class="cell-id num" style="font-size:11px;color:var(--blue)">{{ $s->study_no }}</div>
                    <div data-label="Patient"><div style="font-weight:700">{{ $s->patient->name ?? '—' }}</div><div style="font-size:10px;color:var(--light)">{{ $s->patient->patient_uid ?? '' }}</div></div>
                    <div data-label="Modality"><span class="pill {{ $modPill($s->modality) }}">{{ $s->modality }}</span></div>
                    <div data-label="Study" style="font-size:11px">{{ $s->study_name }}</div>
                    <div data-label="Date" style="font-size:11px;color:var(--muted)">{{ $s->reported_at?->format('d M Y') ?? $s->updated_at?->format('d M Y') }}</div>
                    <div data-label="Status">@if($s->status==='sent')<span class="pill pill-green">Sent</span>@else<span class="pill pill-teal">Verified</span>@endif</div>
                    <div class="cell-action" style="display:flex;gap:4px">
                        <a href="{{ route('vendor.radiology.studies.print', $s->id) }}" target="_blank" class="btn btn-ghost btn-xs">View</a>
                        @if($canSend)<a href="{{ route('vendor.radiology.studies.send', $s->id) }}" class="btn btn-primary btn-xs">{{ $s->status==='sent'?'Resend':'Send' }}</a>@endif
                        {{-- "Send" above goes to the referring doctor; this one goes to the patient,
                             which is why it needs a number on file rather than just the permission. --}}
                        @if($canSend && filled($s->patient->phone ?? null))
                            <form method="post" action="{{ route('vendor.hmis-whatsapp.radiology-report', $s->id) }}" class="mb-0"
                                  onsubmit="return confirm('Send this report to {{ addslashes($s->patient->name ?? 'the patient') }} on WhatsApp?')">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-xs" title="Send to patient on WhatsApp">WhatsApp</button>
                            </form>
                        @endif
                        <a href="{{ route('vendor.radiology.studies.print', $s->id) }}" target="_blank" class="btn btn-outline btn-xs">🖨</a>
                    </div>
                </div>
            @empty
                <div class="empty">No completed reports yet.</div>
            @endforelse
        </div>
        <div class="d-flex justify-content-end">{{ $studies->links() }}</div>
    </div>
</div></div>
@endsection
