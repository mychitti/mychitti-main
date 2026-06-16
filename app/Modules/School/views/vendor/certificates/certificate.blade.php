@php $design = $design ?? 'classic'; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $certificate->typeLabel() }} — {{ $certificate->serial_no }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#1a1a1a; margin:0; }
        .page { width: 780px; margin: 18px auto; }
        @if($pdf ?? false) .page { width:100%; margin:0; } @endif
        .no-print{ text-align:center; margin:14px 0; }
        .btn{ display:inline-block; padding:8px 18px; border:none; border-radius:6px; font-weight:600; text-decoration:none; cursor:pointer; }
        .bp{ background:#4f46e5; color:#fff; } .bd{ background:#059669; color:#fff; }
        @media print { .no-print{ display:none; } .page{ margin:0; } }
    </style>
</head>
<body>
<div class="page">
    @if(!($pdf ?? false))
        <div class="no-print">
            <button class="btn bp" onclick="window.print()">🖨 Print</button>
            <a class="btn bd" href="{{ route('vendor.school.certificates.pdf', $certificate->id) }}" target="_blank">⬇ PDF</a>
        </div>
    @endif

    @includeFirst(
        ['school::vendor.certificates.designs.' . $design, 'school::vendor.certificates.designs.classic'],
        ['certificate' => $certificate, 'store' => $store, 'branch' => $branch ?? null]
    )
</div>
</body>
</html>
