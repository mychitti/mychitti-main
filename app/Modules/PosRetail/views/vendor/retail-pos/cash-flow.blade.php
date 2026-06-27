@extends('layouts.vendor.app')

@section('title', 'Cash Flow')

@push('css_or_js')
    @include('posretail::vendor.retail-pos._styles')
    <style>
        .rp .cf-amt { font-variant-numeric: tabular-nums; font-weight: 700; }
        .rp .cf-var.pos { color: #1b7a43; } .rp .cf-var.neg { color: #dc3545; }
        .rp .cf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px; margin-bottom: 16px; }
        .rp .cf-tile { border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; background: #fff; }
        .rp .cf-stat { display: flex; justify-content: space-between; font-size: 12.5px; padding: 2px 0; }
        .rp .cf-stat .l { color: var(--muted); }
    </style>
@endpush

@section('content')
    @php
        $purposeLabels = \App\Modules\PosRetail\Controllers\Vendor\RetailPosController::CASH_PURPOSES;
        $nameFrom = fn($r) => $r->from_role === 'owner' ? 'Owner' : ($staffNames[(int) $r->from_id] ?? 'Staff');
        $nameTo = fn($r) => $r->to_role === 'manager' ? 'Owner / Manager' : ($r->to_id ? ($staffNames[(int) $r->to_id] ?? 'Staff') : 'Owner / Manager');
        $badge = ['draft' => 'muted', 'pending' => 'info', 'approved' => 'info', 'received' => 'ok', 'closed' => 'ok', 'rejected' => 'bad'];
    @endphp

    <div class="content container-fluid rp">
        <div class="rp-head">
            <div>
                <h1>Cash Flow</h1>
                <div class="sub">Cash requests, shift handover &amp; counter close with raise / approve / receive accountability</div>
            </div>
            <a href="{{ route('vendor.retail-pos.cash-flow.new') }}" class="rp-btn p">+ New Cash Request</a>
        </div>

        {{-- Live counter cash status --}}
        @if (count($rows))
            <div class="cf-grid">
                @foreach ($rows as $row)
                    @php $c = $row['counter']; $st = $row['state']; @endphp
                    <div class="cf-tile">
                        <div style="font-weight:700;">{{ $c->name }} <span class="text-muted small">· {{ $row['branch'] }}</span></div>
                        @if ($st['open'])
                            <div class="small mb-1"><span class="rp-badge ok">Open</span> · {{ $staffNames[(int) $st['holder_id']] ?? 'Staff' }}</div>
                            <div class="cf-stat"><span class="l">Opening</span><span class="cf-amt">₹{{ number_format($st['opening'], 2) }}</span></div>
                            <div class="cf-stat"><span class="l">Cash sales</span><span class="cf-amt">₹{{ number_format($row['cash_sales'], 2) }}</span></div>
                            <div class="cf-stat"><span class="l">Expected</span><span class="cf-amt">₹{{ number_format($row['expected'], 2) }}</span></div>
                        @else
                            <div class="small"><span class="rp-badge muted">Closed</span> · no cash assigned</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="rp-card">
            <div class="hd"><span class="accent">{{ $isManager ? 'All cash requests' : 'My cash requests' }}</span></div>
            <div class="table-responsive">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>Req. No</th><th>Date</th><th>Purpose</th><th>From → To</th>
                            <th class="text-right">Amount</th><th class="text-right">Diff</th><th>Status</th><th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $r)
                            @php
                                $iAmRecipient = ((int) $r->to_id === $meId && $r->to_role === $meRole) || ($r->to_role === 'manager' && $isManager);
                                $iAmRequester = ((int) $r->from_id === $meId && $r->from_role === $meRole);
                            @endphp
                            <tr>
                                <td><b>{{ $r->request_no }}</b></td>
                                <td class="text-muted">{{ \Carbon\Carbon::parse($r->raised_at ?? $r->created_at)->format('d M, h:i A') }}</td>
                                <td>{{ $purposeLabels[$r->purpose] ?? ucfirst((string) $r->purpose) }}</td>
                                <td class="small">{{ $nameFrom($r) }} → {{ $nameTo($r) }}</td>
                                <td class="text-right cf-amt">₹{{ number_format((float) $r->requested_amount, 2) }}</td>
                                <td class="text-right">
                                    @if (in_array($r->type, ['handover', 'close']) && (float) $r->expected_amount > 0)
                                        <span class="cf-var {{ (float) $r->variance < 0 ? 'neg' : 'pos' }}">{{ (float) $r->variance < 0 ? '-' : '+' }}₹{{ number_format(abs((float) $r->variance), 2) }}</span>
                                    @else — @endif
                                </td>
                                <td><span class="rp-badge {{ $badge[$r->status] ?? 'muted' }}">{{ ucfirst($r->status) }}</span></td>
                                <td class="text-right">
                                    <a href="{{ route('vendor.retail-pos.cash-flow.show', $r->id) }}" class="rp-btn o sm">View</a>
                                    @if ($r->status === 'pending' && $iAmRecipient)
                                        <form method="post" action="{{ route('vendor.retail-pos.cash-flow.action', $r->id) }}" style="display:inline">
                                            @csrf <input type="hidden" name="action" value="receive">
                                            <button class="rp-btn p sm">Receive</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="rp-empty">No cash requests yet.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
