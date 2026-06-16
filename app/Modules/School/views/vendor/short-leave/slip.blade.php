<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gate Pass — {{ $pass->gate_pass_no }}</title>
    <style>
        body { font-family: system-ui, Arial, sans-serif; background: #f1f5f9; margin: 0; }
        .wrap { width: 420px; margin: 24px auto; }
        .card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,.1); border: 1px solid #e2e8f0; }
        .top { background: #b45309; color: #fff; padding: 14px 18px; }
        .top h2 { margin: 0; font-size: 16px; }
        .top small { opacity: .9; font-size: 11px; }
        .tag { float: right; background: rgba(255,255,255,.2); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .body { padding: 18px; }
        .ttl { text-align: center; font-size: 15px; font-weight: 700; letter-spacing: 1px; color: #b45309; margin-bottom: 12px; }
        table { width: 100%; font-size: 13px; border-collapse: collapse; }
        td { padding: 6px 4px; border-bottom: 1px dashed #e5e7eb; }
        td.k { color: #64748b; width: 42%; }
        td.v { font-weight: 600; color: #1e293b; }
        .sign { display: flex; justify-content: space-between; margin-top: 28px; font-size: 11px; color: #64748b; }
        .sign div { border-top: 1px solid #94a3b8; padding-top: 4px; width: 45%; text-align: center; }
        .foot { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 8px; text-align: center; font-size: 10px; color: #64748b; }
        .no-print { text-align: center; margin: 14px 0; }
        .btn { padding: 8px 18px; border: none; border-radius: 6px; background: #b45309; color: #fff; font-weight: 600; cursor: pointer; }
        @media print { .no-print { display: none; } body { background: #fff; } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="no-print"><button class="btn" onclick="window.print()">🖨 Print Gate Pass</button></div>
        <div class="card">
            <div class="top">
                <span class="tag">{{ $pass->gate_pass_no }}</span>
                <h2>{{ $store?->name ?? 'School' }}</h2>
                <small>{{ $store?->address }}</small>
            </div>
            <div class="body">
                <div class="ttl">STUDENT GATE PASS</div>
                <table>
                    <tr><td class="k">Student</td><td class="v">{{ $pass->student?->name }}</td></tr>
                    <tr><td class="k">Class</td><td class="v">{{ $pass->student?->currentEnrollment?->schoolClass?->name ?? '—' }}</td></tr>
                    <tr><td class="k">Admission No</td><td class="v">{{ $pass->student?->admission_no }}</td></tr>
                    <tr><td class="k">Date</td><td class="v">{{ $pass->leave_date?->format('d M Y') }}</td></tr>
                    <tr><td class="k">Out Time</td><td class="v">{{ $pass->out_time }}</td></tr>
                    <tr><td class="k">Returning Today</td><td class="v">{{ $pass->is_returning ? 'Yes' : 'No' }}</td></tr>
                    <tr><td class="k">Reason</td><td class="v">{{ $pass->reason ?: '—' }}</td></tr>
                    <tr><td class="k">Picked up by</td><td class="v">{{ $pass->taken_by ?: '—' }}{{ $pass->taken_by_relation ? ' ('.$pass->taken_by_relation.')' : '' }}</td></tr>
                    <tr><td class="k">Contact</td><td class="v">{{ $pass->contact ?: '—' }}</td></tr>
                    <tr><td class="k">Issued by</td><td class="v">{{ $pass->issued_by }}</td></tr>
                </table>
                <div class="sign">
                    <div>Guardian Signature</div>
                    <div>Authorised Signature</div>
                </div>
            </div>
            <div class="foot">{{ $store?->phone }} @if($store?->phone && $store?->email) · @endif {{ $store?->email }}</div>
        </div>
    </div>
</body>
</html>
