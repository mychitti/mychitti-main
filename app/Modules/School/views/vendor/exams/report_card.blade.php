@php $n = fn($v) => is_numeric($v) ? rtrim(rtrim(number_format($v,2),'0'),'.') : $v; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card — {{ $student->name }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#1a1a1a; margin:0; font-size:13px; }
        .sheet { width: 700px; margin: 16px auto; padding: 0 12px; }
        .hosp { text-align:center; padding-bottom:6px; }
        .hosp .h { font-size:21px; font-weight:800; }
        .hosp .s { font-size:11px; }
        .title { text-align:center; font-weight:700; border:1px solid #000; padding:6px; background:#f8fafc; }
        .info { width:100%; border:1px solid #000; border-top:none; border-collapse:collapse; }
        .info td { padding:6px 12px; }
        table.marks { width:100%; border-collapse:collapse; margin-top:0; }
        table.marks th, table.marks td { border:1px solid #000; padding:6px 10px; font-size:12px; }
        table.marks th { background:#eef2ff; }
        .tot { width:100%; border:1px solid #000; border-top:none; border-collapse:collapse; }
        .tot td { padding:6px 12px; font-weight:700; }
        .no-print{ text-align:center; margin:14px 0; }
        .btn{ padding:8px 18px; border:none; border-radius:6px; background:#4f46e5; color:#fff; font-weight:600; cursor:pointer; }
        @media print { .no-print{ display:none; } }
    </style>
</head>
<body>
<div class="sheet">
    <div class="no-print"><button class="btn" onclick="window.print()">🖨 Print Report Card</button></div>

    <div class="hosp">
        <div class="h">{{ $store?->name ?? 'School' }}</div>
        <div class="s">{{ $store?->address }}</div>
    </div>
    <div class="title">REPORT CARD — {{ strtoupper($exam->name) }} ({{ $exam->exam_type }})</div>
    <table class="info">
        <tr>
            <td width="50%"><b>Student:</b> {{ $student->name }}<br><b>Admission No:</b> {{ $student->admission_no }}</td>
            <td width="50%"><b>Class:</b> {{ $exam->schoolClass?->name }} {{ $student->currentEnrollment?->section ? '- '.$student->currentEnrollment->section->name : '' }}<br>
                <b>Roll No:</b> {{ $student->currentEnrollment?->roll_no ?? '—' }}</td>
        </tr>
    </table>
    <table class="marks">
        <thead><tr><th>Subject</th><th>Max</th><th>Pass</th><th>Obtained</th><th>Grade</th><th>Result</th></tr></thead>
        <tbody>
        @foreach($detail['rows'] as $r)
            <tr>
                <td>{{ $r['subject'] }}</td>
                <td>{{ $n($r['max']) }}</td>
                <td>{{ $n($r['pass']) }}</td>
                <td>{{ $n($r['obtained']) }}</td>
                <td>{{ $r['grade'] }}</td>
                <td>{{ $r['obtained']==='AB' ? 'Absent' : ($r['passed'] ? 'Pass' : 'Fail') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <table class="tot">
        <tr>
            <td width="33%">Total: {{ $n($detail['obtained']) }} / {{ $n($detail['max']) }}</td>
            <td width="22%">Percentage: {{ $detail['percentage'] }}%</td>
            <td width="20%">Grade: {{ $detail['grade'] }}</td>
            <td width="25%">Result: {{ $detail['result'] }}</td>
        </tr>
    </table>
</div>
</body>
</html>
