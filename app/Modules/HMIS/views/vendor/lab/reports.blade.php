@extends('layouts.vendor.app')
@section('title', 'Laboratory — Lab Reports')

@section('content')
<div class="content container-fluid"><div class="labx">
    @include('hmis::vendor.lab._chrome')
    <div class="lab-body">
        @php $cols = 'display:grid;grid-template-columns:90px 1fr 160px 110px 95px 95px 150px;gap:8px'; @endphp
        <div class="lcard">
            <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltgreen)">📄</div> Lab Reports — Completed</h3></div>
            <div class="search-bar">
                <form method="get" class="search-wrap mb-0"><input class="si" name="search" value="{{ request('search') }}" placeholder="🔍 Search patient, sample ID..."></form>
            </div>
            <div class="tbl-hd" style="{{ $cols }}"><div>Sample ID</div><div>Patient</div><div>Tests</div><div>Date</div><div>Flags</div><div>Status</div><div>Actions</div></div>
            @forelse ($orders as $o)
                @php
                    $abn = $o->results->whereIn('result_flag', ['H', 'L'])->count();
                    $crit = $o->results->where('is_critical', true)->count();
                @endphp
                <div class="tbl-row" style="{{ $cols }}">
                    <div class="num" style="font-size:11px;color:var(--blue)">{{ $o->order_no }}</div>
                    <div><div style="font-weight:700">{{ $o->patient->name ?? '—' }}</div><div style="font-size:10px;color:var(--light)">{{ $o->patient->patient_uid ?? '' }}</div></div>
                    <div style="font-size:11px">{{ $o->items->pluck('test_name')->take(3)->implode(', ') }}</div>
                    <div style="font-size:11px;color:var(--muted)">{{ $o->reported_at?->format('d M Y') ?? $o->updated_at?->format('d M Y') }}</div>
                    <div>
                        @if ($crit)<span class="pill pill-red" style="font-size:9px">{{ $crit }} Critical</span>
                        @elseif ($abn)<span class="pill pill-amber" style="font-size:9px">{{ $abn }} Abnormal</span>
                        @else<span class="pill pill-green" style="font-size:9px">All Normal</span>@endif
                    </div>
                    <div>@if ($o->status === 'sent')<span class="pill pill-green">Sent</span>@else<span class="pill pill-teal">Verified</span>@endif</div>
                    <div style="display:flex;gap:4px">
                        <a href="{{ route('vendor.lab.orders.report', $o->id) }}" target="_blank" class="btn btn-ghost btn-xs">View</a>
                        @if (hasPermission('lab_report', 'send'))<a href="{{ route('vendor.lab.orders.send', $o->id) }}" class="btn btn-primary btn-xs">{{ $o->status === 'sent' ? 'Resend' : 'Send' }}</a>@endif
                        <a href="{{ route('vendor.lab.orders.report', $o->id) }}" target="_blank" class="btn btn-outline btn-xs">🖨</a>
                    </div>
                </div>
            @empty
                <div class="empty">No completed reports yet.</div>
            @endforelse
        </div>
        <div class="d-flex justify-content-end">{{ $orders->links() }}</div>
    </div>
</div></div>
@endsection
