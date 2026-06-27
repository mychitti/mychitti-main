<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $req->request_no }} — Cash Handover Slip</title>
    <style>
        * { font-family: 'Segoe UI', Arial, sans-serif; box-sizing: border-box; }
        body { max-width: 720px; margin: 0 auto; padding: 24px; color: #1a1a2e; font-size: 13px; }
        h1 { text-align: center; font-size: 20px; letter-spacing: 1px; margin: 0 0 2px; color: #0f3460; }
        .muted { color: #666; }
        .row { display: flex; justify-content: space-between; gap: 12px; }
        .box { border: 1px solid #d8dce6; border-radius: 8px; padding: 10px 12px; margin-top: 12px; }
        .box h3 { margin: 0 0 6px; font-size: 12px; text-transform: uppercase; letter-spacing: .6px; color: #0f3460; }
        table { width: 100%; border-collapse: collapse; }
        .den td, .den th { border: 1px solid #d8dce6; padding: 6px 9px; text-align: right; }
        .den th { background: #f4f6fb; }
        .den td:first-child, .den th:first-child { text-align: left; }
        .tot td { font-weight: 700; background: #eef3ff; }
        .kv { display: flex; justify-content: space-between; padding: 3px 0; }
        .kv .l { color: #666; }
        .amt { font-variant-numeric: tabular-nums; font-weight: 700; }
        .var.pos { color: #1b7a43; } .var.neg { color: #dc3545; }
        .sign { margin-top: 28px; display: flex; justify-content: space-between; gap: 30px; }
        .sign .s { flex: 1; text-align: center; }
        .sign .line { border-top: 1px solid #333; margin-top: 34px; padding-top: 4px; font-size: 12px; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background:#eef3ff; color:#0f3460; }
        @media print { .no-print { display: none; } @page { margin: 12mm; } }
    </style>
</head>
<body onload="window.print()">
    @php
        $purposeLabels = \App\Modules\PosRetail\Controllers\Vendor\RetailPosController::CASH_PURPOSES;
        $denoms = \App\Modules\PosRetail\Controllers\Vendor\RetailPosController::CASH_DENOMS;
        $money = fn($n) => '₹' . number_format((float) $n, 2);
    @endphp

    <h1>CASH HANDOVER SLIP</h1>
    <div class="row muted" style="font-size:12px;">
        <div><b style="color:#dc3545;">{{ $req->request_no }}</b></div>
        <div>{{ \Carbon\Carbon::parse($req->raised_at ?? $req->created_at)->format('d-m-Y h:i A') }}</div>
    </div>
    <div class="row" style="margin-top:4px;">
        <div><b>{{ $store->name ?? 'Store' }}</b></div>
        <div class="badge">{{ ucfirst($req->status) }}</div>
    </div>

    <div class="box">
        <h3>Details</h3>
        <div class="kv"><span class="l">Purpose</span><span>{{ $purposeLabels[$req->purpose] ?? ucfirst((string) $req->purpose) }}{{ $req->purpose_other ? ' — ' . $req->purpose_other : '' }}</span></div>
        <div class="kv"><span class="l">Branch / Counter</span><span>{{ $branchName }}{{ $counterName ? ' · ' . $counterName : '' }}</span></div>
        <div class="kv"><span class="l">Payment mode</span><span>{{ ucfirst(str_replace('_', ' ', $req->payment_mode)) }}</span></div>
        <div class="kv"><span class="l">Handed over by</span><span><b>{{ $fromName }}</b>{{ $req->from_label ? ' (' . $req->from_label . ')' : '' }}</span></div>
        <div class="kv"><span class="l">Received by</span><span><b>{{ $toName }}</b>{{ $req->to_label ? ' (' . $req->to_label . ')' : '' }}</span></div>
    </div>

    <div class="box">
        <h3>Cash Denominations</h3>
        <table class="den">
            <thead><tr><th>Denomination</th><th>Qty</th><th>Amount</th></tr></thead>
            <tbody>
                @foreach ($denoms as $dv)
                    <tr><td>₹ {{ $dv }}</td><td>{{ (int) ($den[(string) $dv] ?? 0) }}</td><td>{{ $money($dv * (int) ($den[(string) $dv] ?? 0)) }}</td></tr>
                @endforeach
                <tr><td>Coins</td><td>—</td><td>{{ $money($den['coins'] ?? 0) }}</td></tr>
                <tr class="tot"><td>Total Cash</td><td></td><td>{{ $money($req->cash_amount) }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="box">
        <h3>Reconciliation</h3>
        <div class="kv"><span class="l">Cash</span><span class="amt">{{ $money($req->cash_amount) }}</span></div>
        @if ((float) $req->upi_amount > 0)
            <div class="kv"><span class="l">UPI / Online</span><span class="amt">{{ $money($req->upi_amount) }}</span></div>
        @endif
        <div class="kv"><span class="l">Requested / Declared</span><span class="amt">{{ $money($req->requested_amount) }}</span></div>
        @if (in_array($req->type, ['handover', 'close']) && (float) $req->expected_amount > 0)
            <div class="kv"><span class="l">Expected in drawer</span><span class="amt">{{ $money($req->expected_amount) }}</span></div>
            <div class="kv"><span class="l">Variance (counted − expected)</span>
                <span class="amt var {{ (float) $req->variance < 0 ? 'neg' : 'pos' }}">{{ (float) $req->variance < 0 ? '-' : '+' }}{{ $money(abs((float) $req->variance)) }}</span>
            </div>
        @endif
        @if ($req->note)<div class="kv"><span class="l">Remarks</span><span>{{ $req->note }}</span></div>@endif
        @if ($approvedName)
            <div class="kv"><span class="l">Approved / Verified by</span><span>{{ $approvedName }} · {{ $req->approved_at ? \Carbon\Carbon::parse($req->approved_at)->format('d-m-Y h:i A') : '' }}</span></div>
        @endif
    </div>

    <div class="sign">
        <div class="s"><div class="line">Handed over by — {{ $fromName }}</div></div>
        <div class="s"><div class="line">Received by — {{ $toName }}</div></div>
    </div>

    <div class="no-print" style="text-align:center;margin-top:18px;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
