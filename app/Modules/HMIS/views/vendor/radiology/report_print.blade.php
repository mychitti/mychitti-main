<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $study->study_no }} — Radiology Report</title>
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
        .sec{margin-top:16px}
        .sec-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#0A2463;border-bottom:1px solid #E5E7EB;padding-bottom:4px;margin-bottom:6px}
        .sec-body{font-size:12px;line-height:1.7;white-space:pre-wrap}
        .crit{background:#FFEBEE;border:1px solid #C62828;border-radius:8px;padding:10px 12px;margin-top:12px;color:#C62828;font-weight:600;font-size:12px}
        .foot{margin-top:46px;display:flex;justify-content:space-between;font-size:11px;color:#4B5563}
        .sign .line{border-top:1px solid #9CA3AF;width:190px;margin-top:34px;padding-top:4px;text-align:center}
        @media print{.rp-actions{display:none}body{padding:0}}
    </style>
</head>
<body>
    <div class="rp-actions"><button onclick="window.print()">🖨 Print / Save PDF</button></div>
    @php
        $age = $study->patient?->dob ? \Carbon\Carbon::parse($study->patient->dob)->age . ' Years' : '—';
        $doc = $study->doctorProfile ? 'Dr. ' . trim(($study->doctorProfile->employee->f_name ?? '') . ' ' . ($study->doctorProfile->employee->l_name ?? '')) : ($study->referred_by ?: '—');
    @endphp
    <div class="head">
        <div>
            <div class="name">{{ $letterhead['name'] }}</div>
            <div class="meta">{{ $letterhead['address'] }}</div>
            <div class="meta">{{ $letterhead['phone'] }}{{ $letterhead['phone'] && $letterhead['email'] ? ' · ' : '' }}{{ $letterhead['email'] }}</div>
            @if ($letterhead['gst_no'])
                <div class="meta"><strong>GSTIN:</strong> {{ $letterhead['gst_no'] }}</div>
            @endif
            @foreach ($letterhead['licenses'] as $license)
                <div class="meta">{{ $license->label() }}{{ $license->valid_till ? ' (valid till ' . $license->valid_till->format('d M Y') . ')' : '' }}</div>
            @endforeach
        </div>
        <div style="text-align:right">
            <div class="meta"><strong>Study No:</strong> {{ $study->study_no }}</div>
            <div class="meta"><strong>Printed:</strong> {{ now()->format('d M Y · h:i A') }}</div>
        </div>
    </div>

    <div class="title">Radiology / Imaging Report</div>

    <div class="info">
        <div><span class="k">Patient:</span> <strong>{{ $study->patient->name ?? '—' }}</strong></div>
        <div><span class="k">Study ID:</span> {{ $study->study_no }}</div>
        <div><span class="k">Age / Sex:</span> {{ $age }} / {{ ucfirst($study->patient->gender ?? '—') }}</div>
        <div><span class="k">Ref. Doctor:</span> {{ $doc }}</div>
        <div><span class="k">Modality:</span> {{ $study->modality }} — {{ $study->study_name }}</div>
        <div><span class="k">Date:</span> {{ $study->reported_at?->format('d M Y · h:i A') ?? $study->created_at?->format('d M Y') }}</div>
    </div>

    @if ($study->clinical_history)
        <div class="sec"><div class="sec-title">Clinical History</div><div class="sec-body">{{ $study->clinical_history }}</div></div>
    @endif
    <div class="sec"><div class="sec-title">Findings</div><div class="sec-body">{{ $study->findings ?: '—' }}</div></div>
    <div class="sec"><div class="sec-title">Impression</div><div class="sec-body">{{ $study->impression ?: '—' }}</div></div>
    @if ($study->recommendations)
        <div class="sec"><div class="sec-title">Recommendations</div><div class="sec-body">{{ $study->recommendations }}</div></div>
    @endif
    @if ($study->is_critical)
        <div class="crit">🚨 CRITICAL FINDING — referring doctor notified{{ $study->critical_notified_to ? ' (' . $study->critical_notified_to . ')' : '' }}.</div>
    @endif

    <div class="foot">
        <div style="font-size:10px">*Electronically generated radiology report.</div>
        <div class="sign"><div class="line">{{ $study->radiologist ?: 'Reporting Radiologist' }}</div></div>
    </div>
</body>
</html>
