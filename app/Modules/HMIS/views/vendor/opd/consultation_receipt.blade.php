@php
    $amt = fn($v) => number_format((float) $v, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OP Consultation Receipt #{{ $receipt->bill_no }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1a1a1a; margin: 0; padding: 0; font-size: 13px; }
        .sheet { width: 720px; margin: 16px auto; padding: 0 12px; }
        .hosp { text-align: center; padding-bottom: 8px; }
        .hosp .hname { font-size: 22px; font-weight: 800; }
        .hosp .sub { font-size: 11px; margin-top: 3px; }
        table.frame { width: 100%; border: 1px solid #000; border-collapse: collapse; }
        table.frame td { vertical-align: top; }
        .cell { padding: 10px 12px; }
        .rowline { margin-bottom: 4px; }
        .lbl { color: #333; }
        .val { font-weight: 700; }
        .validity { font-style: italic; font-weight: 700; margin-bottom: 4px; }
        .muted { color: #444; }
        .amt { margin-bottom: 3px; }
        .amt .k { display: inline-block; width: 120px; }
        @media print { .no-print { display: none !important; } body { font-size: 12px; } }
        .no-print { text-align: center; margin: 14px 0; }
        .btn { display: inline-block; padding: 8px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; border: none; cursor: pointer; }
        .btn-p { background: #0661cb; color: #fff; }
        .btn-d { background: #059669; color: #fff; }
        @if ($pdf ?? false)
        .sheet { width: 100%; margin: 0; padding: 0; }
        body { font-size: 11px; }
        .hosp .hname { font-size: 18px; }
        .cell { padding: 6px 8px; }
        @endif
    </style>
</head>
<body>
    <div class="sheet">
        @if (!($pdf ?? false))
            <div class="no-print">
                <button class="btn btn-p" onclick="window.print()">🖨 Print</button>
                <a class="btn btn-d" href="{{ route('vendor.opd.consultation-receipt.pdf', $visit->id) }}">⬇ Download PDF</a>
            </div>
        @endif

        <!-- Hospital header. Hospital Settings decides whether it prints, so the downloaded PDF
             and the browser print agree without the two being wired separately. -->
        @php $hdr = hmis_print_header('consultation_receipt', $visit->store_id ?? null); @endphp
        @if ($hdr['off'] && $hdr['mm'])
            <div style="height:{{ $hdr['mm'] }}mm" aria-hidden="true"></div>
        @endif
        <div class="hosp" @if ($hdr['off']) style="display:none" @endif>
            <div class="hname">{{ $store?->name ?? 'Hospital' }}</div>
            <div class="sub">
                @if ($store?->address){{ $store->address }}<br>@endif
                @if ($store?->phone)Phone: {{ $store->phone }}@endif
                @if ($store?->email) &nbsp;·&nbsp; {{ $store->email }}@endif
            </div>
        </div>

        <table class="frame" cellpadding="0" cellspacing="0">
            <!-- Title -->
            <tr>
                <td style="text-align:center; font-weight:700; letter-spacing:.5px; border-bottom:1px solid #000; padding:6px;">
                    OP CONSULTATION RECEIPT
                </td>
            </tr>

            <!-- Detail grid (two columns) -->
            <tr>
                <td style="border-bottom:1px solid #000; padding:0;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="50%" class="cell" style="border-right:1px solid #000;">
                                <div class="rowline"><span class="lbl">UHID</span>: <span class="val">{{ $patient?->patient_uid }}</span></div>
                                <div class="rowline"><span class="lbl">OPD No</span>: <span class="val">{{ $visit->id }}</span></div>
                                <div class="rowline"><span class="lbl">Patient Name</span>: <span class="val">{{ $patient?->name }}</span></div>
                                <div class="rowline"><span class="lbl">Phone No</span>: <span class="val">{{ $patient?->phone }}</span></div>
                                <div class="rowline"><span class="lbl">Consult Doctor</span>: <span class="val">{{ $doctorName }}</span></div>
                                <div class="rowline"><span class="lbl">Referral Doctor</span>: <span class="val">SELF</span></div>
                            </td>
                            <td width="50%" class="cell">
                                <div class="rowline"><span class="lbl">Date</span>: <span class="val">{{ \Carbon\Carbon::parse($receipt->created_at)->format('d/m/y h:i:s A') }}</span></div>
                                <div class="rowline"><span class="lbl">Bill No</span>: <span class="val">{{ $receipt->bill_no }}</span></div>
                                <div class="rowline"><span class="lbl">Age/Gender</span>: <span class="val">{{ $age }}{{ $age && $patient?->gender ? ' / ' : '' }}{{ ucfirst($patient?->gender ?? '') }}</span></div>
                                <div class="rowline"><span class="lbl">Department</span>: <span class="val">{{ $department }}</span></div>
                                <div class="rowline"><span class="lbl">Token</span>: <span class="val">{{ $visit->token_number }}</span></div>
                                <div class="rowline"><span class="lbl">Visit No</span>: <span class="val">{{ $visitNo }}</span></div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Towards OP Consultation -->
            <tr>
                <td style="border-bottom:1px solid #000; padding:0;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="cell" style="font-weight:700;">Towards OP Consultation</td>
                            <td class="cell" style="text-align:right; font-weight:700;">{{ $amt($receipt->amount) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Validity + amounts -->
            <tr>
                <td style="border-bottom:1px solid #000; padding:0;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="58%" class="cell" style="border-right:1px solid #000;">
                                <div class="validity">
                                    Validity : {{ $receipt->allowed_consultations }} Consultation(s) before
                                    {{ \Carbon\Carbon::parse($receipt->valid_until)->format('d-M-Y') }}
                                </div>
                                <div class="muted">Received with thanks from {{ $patient?->name }} sum of Rs. {{ $amt($receipt->paid) }}/-</div>
                                <div class="muted">In Words: {{ $amountWords }}</div>
                                <div style="margin-top:6px;">Mode of Payment : <strong>{{ strtoupper($receipt->payment_mode) }}</strong></div>
                            </td>
                            <td width="42%" class="cell">
                                <div class="amt"><span class="k">Total Amt</span>: <span class="val">{{ $amt($receipt->amount) }}</span></div>
                                <div class="amt"><span class="k">Paid Amt</span>: <span class="val">{{ $amt($receipt->paid) }}</span></div>
                                <div class="amt"><span class="k">Concession Amt</span>: <span class="val">{{ $amt($receipt->concession) }}</span></div>
                                <div class="amt"><span class="k">Due Amt</span>: <span class="val">{{ $amt($receipt->due) }}</span></div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Footer -->
            <tr>
                <td style="padding:0;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        @php $sign = hmis_print_sign('consultation_receipt', $visit->store_id ?? null); @endphp
                        @php $signSide = $sign['show'] && $sign['pos'] === 'left' ? 'left' : 'right'; @endphp
                        <tr>
                            {{-- The two cells swap alignment rather than swapping content: the
                                 signing block stays one piece of markup wherever it is asked to sit. --}}
                            <td class="cell" style="font-size:12px; text-align:{{ $signSide === 'left' ? 'right' : 'left' }};">
                                Billed User : <strong>{{ $receipt->billed_by }}</strong><br>
                                Print User : <strong>{{ $receipt->billed_by }}</strong>
                            </td>
                            <td class="cell" style="text-align:{{ $signSide }}; font-weight:700;">
                                For {{ $store?->name }}<br>
                                @if ($sign['show'])
                                    <img src="{{ $sign['url'] }}" alt="Signature"
                                         style="height:46px; max-width:170px; object-fit:contain; margin:2px 0;"><br>
                                    {{ $sign['name'] }}
                                @else
                                    <br>Cashier
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
