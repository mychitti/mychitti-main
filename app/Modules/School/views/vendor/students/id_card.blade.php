<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ID Card — {{ $student->admission_no }}</title>
    <style>
        body { font-family: system-ui, Arial, sans-serif; background: #f1f5f9; margin: 0; }
        .wrap { width: 340px; margin: 24px auto; }
        .card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,.1); border: 1px solid #e2e8f0; }
        .top { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; padding: 14px 16px; text-align: center; }
        .top h2 { margin: 0; font-size: 16px; }
        .top small { opacity: .9; font-size: 11px; }
        .body { padding: 16px; text-align: center; }
        .photo { width: 96px; height: 96px; border-radius: 8px; object-fit: cover; border: 3px solid #e0e7ff; }
        .ph-empty { width: 96px; height: 96px; border-radius: 8px; background: #eef2ff; color: #6366f1; display:flex; align-items:center; justify-content:center; font-size:34px; margin: 0 auto; }
        .name { font-size: 17px; font-weight: 700; margin: 8px 0 2px; color: #1e293b; }
        .cls { color: #4f46e5; font-weight: 600; font-size: 13px; }
        table { width: 100%; font-size: 12px; margin-top: 10px; }
        td { padding: 3px 4px; }
        td.k { color: #64748b; width: 45%; }
        td.v { font-weight: 600; color: #1e293b; }
        .foot { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 8px; text-align: center; font-size: 10px; color: #64748b; }
        .no-print { text-align:center; margin: 14px 0; }
        .btn { padding: 8px 18px; border:none; border-radius:6px; background:#4f46e5; color:#fff; font-weight:600; cursor:pointer; }
        @media print { .no-print { display:none; } body { background:#fff; } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="no-print"><button class="btn" onclick="window.print()">🖨 Print ID Card</button></div>
        <div class="card">
            <div class="top">
                <h2>{{ $store?->name ?? 'School' }}</h2>
                @if($branch ?? null)<small style="display:block; font-weight:600;">{{ $branch->name }}</small>@endif
                <small>{{ ($branch->address ?? null) ?: $store?->address }}</small>
            </div>
            <div class="body">
                @if($student->photo)
                    <img class="photo" src="{{ asset('storage/app/public/school/students/'.$student->photo) }}">
                @else <div class="ph-empty">{{ strtoupper(substr($student->name,0,1)) }}</div>@endif
                <div class="name">{{ $student->name }}</div>
                <div class="cls">{{ $student->currentEnrollment?->schoolClass?->name }} {{ $student->currentEnrollment?->section ? '- '.$student->currentEnrollment->section->name : '' }}</div>
                <table>
                    <tr><td class="k">Admission No</td><td class="v">{{ $student->admission_no }}</td></tr>
                    <tr><td class="k">Roll No</td><td class="v">{{ $student->currentEnrollment?->roll_no ?? '—' }}</td></tr>
                    <tr><td class="k">DOB</td><td class="v">{{ $student->dob?->format('d/m/Y') ?? '—' }}</td></tr>
                    <tr><td class="k">Blood Group</td><td class="v">{{ $student->blood_group ?? '—' }}</td></tr>
                    <tr><td class="k">Guardian</td><td class="v">{{ $student->guardian_name ?? '—' }}</td></tr>
                    <tr><td class="k">Contact</td><td class="v">{{ $student->guardian_phone ?? $student->phone ?? '—' }}</td></tr>
                </table>
            </div>
            <div class="foot">{{ $store?->phone }} @if($store?->phone && $store?->email) · @endif {{ $store?->email }}</div>
        </div>
    </div>
</body>
</html>
