<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $order->order_no }} — Lab Report</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'DM Sans',sans-serif;color:#0D1117;padding:26px 34px;font-size:13px}
        .rp-actions{text-align:center;margin-bottom:16px}
        .rp-actions button{background:#0A2463;color:#fff;border:none;padding:8px 18px;border-radius:7px;cursor:pointer;font-size:13px;font-family:'DM Sans',sans-serif}
        .head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #0A2463;padding-bottom:12px}
        .head .name{font-size:22px;font-weight:800;color:#0A2463}
        .head .meta{font-size:11px;color:#4B5563;margin-top:2px}
        .title{text-align:center;font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#0A2463;margin:14px 0}
        .info{display:grid;grid-template-columns:1fr 1fr;gap:4px 24px;border:1px solid #E5E7EB;border-radius:8px;padding:10px 14px;font-size:12px}
        .info .k{color:#9CA3AF}
        table{width:100%;border-collapse:collapse;margin-top:16px;font-size:12px}
        th{text-align:left;background:#F3F4F6;padding:7px 10px;font-size:10px;text-transform:uppercase;letter-spacing:.4px;border-bottom:1.5px solid #D1D5DB}
        td{padding:7px 10px;border-bottom:1px solid #F3F4F6}
        .dept td{background:#F9FAFB;font-weight:700;font-size:11px;text-transform:uppercase;color:#4B5563;letter-spacing:.5px}
        .val{font-family:'DM Mono',monospace;font-weight:700}
        .H{color:#C62828;font-weight:700}.L{color:#B45309;font-weight:700}.N{color:#2E7D32}
        .abn td{background:#FEF6F6}
        .crit td{background:#FFEBEE}
        .foot{margin-top:46px;display:flex;justify-content:space-between;font-size:11px;color:#4B5563}
        .sign .line{border-top:1px solid #9CA3AF;width:190px;margin-top:34px;padding-top:4px;text-align:center}
        @media print{.rp-actions{display:none}body{padding:0}}
    </style>
</head>
<body>
    <div class="rp-actions"><button onclick="window.print()">🖨 Print / Save PDF</button></div>

    @php
        $age = $order->patient?->dob ? \Carbon\Carbon::parse($order->patient->dob)->age . ' Years' : '—';
        $doc = $order->doctorProfile ? 'Dr. ' . trim(($order->doctorProfile->employee->f_name ?? '') . ' ' . ($order->doctorProfile->employee->l_name ?? '')) : ($order->referred_by ?: '—');
    @endphp

    <div class="head">
        <div>
            <div class="name">{{ $store->name ?? 'Laboratory' }}</div>
            <div class="meta">{{ $store->address ?? '' }}</div>
            <div class="meta">{{ $store->phone ?? '' }}{{ ($store->phone ?? '') && ($store->email ?? '') ? ' · ' : '' }}{{ $store->email ?? '' }}</div>
        </div>
        <div style="text-align:right">
            <div class="meta"><strong>Report No:</strong> {{ $order->order_no }}</div>
            <div class="meta"><strong>Printed:</strong> {{ now()->format('d M Y · h:i A') }}</div>
        </div>
    </div>

    <div class="title">Laboratory Investigation Report</div>

    <div class="info">
        <div><span class="k">Patient:</span> <strong>{{ $order->patient->name ?? '—' }}</strong></div>
        <div><span class="k">Sample ID:</span> {{ $order->order_no }}</div>
        <div><span class="k">Age / Sex:</span> {{ $age }} / {{ ucfirst($order->patient->gender ?? '—') }}</div>
        <div><span class="k">Ref. Doctor:</span> {{ $doc }}</div>
        <div><span class="k">Collected:</span> {{ $order->collected_at?->format('d M Y · h:i A') ?? $order->created_at?->format('d M Y · h:i A') }}</div>
        <div><span class="k">Reported:</span> {{ $order->reported_at?->format('d M Y · h:i A') ?? '—' }}</div>
    </div>

    <table>
        <thead><tr><th style="width:36%">Investigation</th><th>Result</th><th>Unit</th><th>Reference Range</th><th>Flag</th></tr></thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr class="dept"><td colspan="5">{{ $item->test_name }}{{ $item->department ? ' — ' . $item->department : '' }}</td></tr>
                @foreach ($item->results as $r)
                    @php
                        $ref = $r->ref_range_text ?: (($r->normal_low !== null || $r->normal_high !== null) ? trim(($r->normal_low ?? '') . ' – ' . ($r->normal_high ?? '')) : '—');
                        $rowCls = $r->is_critical ? 'crit' : (in_array($r->result_flag, ['H', 'L']) ? 'abn' : '');
                    @endphp
                    <tr class="{{ $rowCls }}">
                        <td>{{ $r->parameter_name }}</td>
                        <td class="val {{ $r->result_flag }}">{{ $r->result_value ?: '—' }} @if ($r->result_flag === 'H') ▲ @elseif ($r->result_flag === 'L') ▼ @endif</td>
                        <td>{{ $r->unit }}</td>
                        <td>{{ $ref }}</td>
                        <td class="{{ $r->result_flag }}">{{ $r->result_flag === 'H' ? 'HIGH' : ($r->result_flag === 'L' ? 'LOW' : ($r->result_flag === 'N' ? 'Normal' : '—')) }}{{ $r->is_critical ? ' (CRITICAL)' : '' }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    @if ($order->technician_notes)
        <div style="margin-top:12px;font-size:11px;color:#4B5563"><strong>Note:</strong> {{ $order->technician_notes }}</div>
    @endif

    <div class="foot">
        <div>
            <div><strong>Legend:</strong> <span class="H">▲ High</span> &nbsp; <span class="L">▼ Low</span> &nbsp; <span class="N">Normal</span></div>
            <div style="margin-top:6px">*Electronically generated report. Relates only to the sample(s) tested.</div>
        </div>
        <div class="sign">
            <div class="line">{{ $order->analysed_by ?: 'Analysed By' }}</div>
            <div class="line">{{ $order->verified_by_name ?: 'Verified Pathologist' }}</div>
        </div>
    </div>
</body>
</html>
