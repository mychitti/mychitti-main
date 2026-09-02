@extends('layouts.vendor.app')
@section('title', 'Laboratory — Test History')

@section('content')
<div class="content container-fluid"><div class="labx">
    @include('hmis::vendor.lab._chrome')
    <div class="lab-body">
        @php $cols = 'display:grid;grid-template-columns:90px 1fr 170px 100px 90px 90px 70px;gap:8px'; @endphp
        <div class="lcard">
            <div class="card-hd"><h3><div class="hd-icon" style="background:var(--ltblue)">📊</div> Test History</h3>
                <form method="get" class="card-actions mb-0">
                    <input class="fi" type="date" name="date" value="{{ request('date') }}" style="width:150px">
                    <input class="fi" name="search" value="{{ request('search') }}" placeholder="Patient / sample" style="width:170px">
                    <button class="btn btn-outline btn-sm">Filter</button>
                    {{-- Exports what the filter is showing, not the whole register: the figures a
                         lab is asked for are always "this month", "this doctor", "this patient". --}}
                    <a href="{{ route('vendor.lab.history.export', request()->only('date', 'search')) }}"
                       class="btn btn-outline btn-sm">⬇ Export</a>
                </form>
            </div>
            <div class="tbl-hd" style="{{ $cols }}"><div>Sample ID</div><div>Patient</div><div>Tests</div><div>Result</div><div>Date</div><div>Status</div><div>Report</div></div>
            @forelse ($orders as $o)
                @php $abn = $o->results->whereIn('result_flag', ['H', 'L'])->count(); $crit = $o->results->where('is_critical', true)->count(); @endphp
                <div class="tbl-row" style="{{ $cols }}">
                    <div class="num" style="font-size:11px;color:var(--blue)">{{ $o->order_no }}</div>
                    <div><div style="font-weight:600">{{ $o->patient->name ?? '—' }}</div><div style="font-size:10px;color:var(--light)">{{ $o->patient->patient_uid ?? '' }}</div></div>
                    <div style="font-size:11px">{{ $o->items->pluck('test_name')->take(3)->implode(', ') }}</div>
                    <div>@if ($crit)<span class="pill pill-red" style="font-size:9px">{{ $crit }} Critical</span>@elseif ($abn)<span class="pill pill-amber" style="font-size:9px">{{ $abn }} Abnormal</span>@else<span class="pill pill-green" style="font-size:9px">Normal</span>@endif</div>
                    <div style="font-size:11px;color:var(--muted)">{{ $o->created_at?->format('d M') }}</div>
                    <div>
                        @if ($o->status === 'sent')<span class="pill pill-green">Sent</span>
                        @elseif ($o->status === 'verified')<span class="pill pill-teal">Verified</span>
                        @elseif ($o->status === 'in_progress')<span class="pill pill-navy">In Progress</span>
                        @elseif ($o->status === 'resulted')<span class="pill pill-purple">Resulted</span>
                        @else<span class="pill pill-amber">Pending</span>@endif
                    </div>
                    <div>@if (in_array($o->status, ['verified', 'sent']))<a href="{{ route('vendor.lab.orders.report', $o->id) }}" target="_blank" class="btn btn-ghost btn-xs">View</a>@else —@endif</div>
                </div>
            @empty
                <div class="empty">No history for this filter.</div>
            @endforelse
        </div>
        <div class="d-flex justify-content-end">{{ $orders->links() }}</div>
    </div>
</div></div>
@endsection
