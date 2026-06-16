@php $m = fn($v) => number_format((float) $v, 2); $sym = \App\CentralLogics\Helpers::currency_symbol(); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Receipt — {{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#1a1a1a; margin:0; font-size:13px; }
        .sheet { width: 640px; margin: 16px auto; padding: 0 12px; }
        @if($pdf ?? false) .sheet { width:100%; margin:0; padding:0; } body{font-size:11px;} @endif
        .hosp { text-align:center; padding-bottom:6px; }
        .hosp .h { font-size:20px; font-weight:800; }
        .hosp .s { font-size:11px; }
        table.frame { width:100%; border:1px solid #000; border-collapse:collapse; }
        .title { text-align:center; font-weight:700; border-bottom:1px solid #000; padding:6px; }
        .cell { padding:8px 12px; }
        td { vertical-align:top; }
        .items { width:100%; border-collapse:collapse; }
        .items th, .items td { border-bottom:1px solid #e2e8f0; padding:6px 12px; font-size:12px; }
        .items th { background:#f8fafc; text-align:left; }
        .tot td { padding:3px 12px; }
        .no-print{ text-align:center; margin:14px 0; }
        .btn{ display:inline-block; padding:8px 18px; border:none; border-radius:6px; font-weight:600; text-decoration:none; cursor:pointer; }
        .bp{ background:#4f46e5; color:#fff; } .bd{ background:#059669; color:#fff; }
        @media print { .no-print{ display:none; } }
    </style>
</head>
<body>
<div class="sheet">
    @if(!($pdf ?? false))
        <div class="no-print">
            <button class="btn bp" onclick="window.print()">🖨 Print</button>
            <a class="btn bd" href="{{ route('vendor.school.fees.receipt.pdf', $invoice->id) }}">⬇ PDF</a>
        </div>
    @endif

    <div class="hosp">
        <div class="h">{{ $store?->name ?? 'School' }}</div>
        @if($branch ?? null)<div class="s" style="font-weight:700;">{{ $branch->name }}</div>@endif
        @php $addr = ($branch->address ?? null) ?: $store?->address; @endphp
        <div class="s">@if($addr){{ $addr }}<br>@endif
            @if($store?->phone)Phone: {{ $store->phone }}@endif @if($store?->email) · {{ $store->email }}@endif</div>
    </div>

    <table class="frame" cellpadding="0" cellspacing="0">
        <tr><td class="title">FEE RECEIPT</td></tr>
        <tr><td style="border-bottom:1px solid #000; padding:0;">
            <table width="100%"><tr>
                <td width="50%" class="cell" style="border-right:1px solid #000;">
                    <div><b>Student:</b> {{ $invoice->student?->name }}</div>
                    <div><b>Admission No:</b> {{ $invoice->student?->admission_no }}</div>
                    <div><b>Class:</b> {{ $invoice->student?->currentEnrollment?->schoolClass?->name ?? '—' }}</div>
                </td>
                <td width="50%" class="cell">
                    <div><b>Receipt No:</b> {{ $invoice->payments->first()?->receipt_no ?? $invoice->invoice_no }}</div>
                    <div><b>Invoice No:</b> {{ $invoice->invoice_no }}</div>
                    <div><b>Date:</b> {{ \Carbon\Carbon::parse($invoice->created_at)->format('d/m/Y h:i A') }}</div>
                </td>
            </tr></table>
        </td></tr>
        <tr><td style="border-bottom:1px solid #000; padding:0;">
            <table class="items">
                <thead><tr><th>Fee Head</th><th style="text-align:right;">Amount</th></tr></thead>
                <tbody>
                @foreach($invoice->items ?? [] as $it)
                    <tr><td>{{ $it['name'] }}</td><td style="text-align:right;">{{ $m($it['amount']) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </td></tr>
        <tr><td style="padding:0;">
            <table width="100%"><tr>
                <td width="55%" class="cell" style="border-right:1px solid #000;">
                    <div><b>Mode:</b> {{ strtoupper($invoice->payments->first()?->payment_mode ?? '—') }}</div>
                    <div><b>Collected By:</b> {{ $invoice->payments->first()?->collected_by ?? '—' }}</div>
                    @if($invoice->gst_amount > 0)<div class="text-muted" style="font-size:11px;">(incl. GST {{ $m($invoice->gst_amount) }})</div>@endif
                </td>
                <td width="45%" class="cell">
                    <table class="tot" width="100%">
                        <tr><td>Total</td><td style="text-align:right;">{{ $m($invoice->total_amount) }}</td></tr>
                        @if($invoice->concession > 0)
                        <tr><td>Concession</td><td style="text-align:right;">- {{ $m($invoice->concession) }}</td></tr>
                        @if(!empty($invoice->concession_note))<tr><td colspan="2" style="font-size:10px;color:#666;">{{ $invoice->concession_note }}</td></tr>@endif
                        @endif
                        <tr><td><b>Paid</b></td><td style="text-align:right;"><b>{{ $m($invoice->paid_amount) }}</b></td></tr>
                        <tr><td>Due</td><td style="text-align:right;">{{ $m($invoice->due_amount) }}</td></tr>
                    </table>
                </td>
            </tr></table>
        </td></tr>
    </table>
</div>
</body>
</html>
