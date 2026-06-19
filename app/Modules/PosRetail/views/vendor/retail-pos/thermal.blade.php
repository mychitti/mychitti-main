<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_id }}</title>
    <style>
        * { font-family: 'Courier New', monospace; }
        body { width: 80mm; margin: 0 auto; padding: 6px; font-size: 12px; color: #000; }
        .center { text-align: center; }
        .b { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { font-size: 11px; padding: 1px 0; vertical-align: top; }
        .r { text-align: right; }
        h3 { margin: 2px 0; font-size: 15px; }
        @media print { .no-print { display: none; } @page { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="center">
        <h3 class="b">{{ $store->name }}</h3>
        @if (!empty($store->address))<div>{{ $store->address }}</div>@endif
        @php
            $gstRaw = $store->gst ?? null;
            $gstCode = $gstRaw;
            if ($gstRaw && ($d = json_decode($gstRaw, true)) && isset($d['code'])) { $gstCode = $d['code']; }
        @endphp
        @if (!empty($gstCode))<div>GSTIN: {{ $gstCode }}</div>@endif
    </div>
    <div class="line"></div>

    <table>
        <tr><td>Invoice</td><td class="r">{{ $invoice->pos_status === 'void' ? 'V' . $invoice->invoice_id : $invoice->invoice_id }}</td></tr>
        <tr><td>Date</td><td class="r">{{ $invoice->created_at->format('d-m-Y h:i A') }}</td></tr>
        <tr><td>Customer</td><td class="r">{{ $invoice->customer_name ?? ($invoice->bill_to ? '#' . $invoice->bill_to : 'Walk-in') }}</td></tr>
    </table>
    <div class="line"></div>

    <table>
        <tr class="b"><td>Item</td><td class="r">Qty</td><td class="r">Rate</td><td class="r">Amt</td></tr>
        @foreach ($items as $it)
            <tr>
                <td>{{ $it->name }}@if ($it->hsn) <br><small>HSN {{ $it->hsn }}</small>@endif</td>
                <td class="r">{{ rtrim(rtrim(number_format((float) $it->qty, 2), '0'), '.') }}</td>
                <td class="r">{{ number_format((float) $it->price, 2) }}</td>
                <td class="r">{{ number_format((float) $it->price * (float) $it->qty, 2) }}</td>
            </tr>
        @endforeach
    </table>
    <div class="line"></div>

    <table>
        <tr><td>Subtotal</td><td class="r">{{ number_format((float) ($invoice->taxable_amount ?? 0), 2) }}</td></tr>
        @if (($invoice->final_tax ?? 0) > 0)
            <tr><td>GST</td><td class="r">{{ number_format((float) $invoice->final_tax, 2) }}</td></tr>
        @endif
        @if (($invoice->discount_amount ?? 0) > 0)
            <tr><td>Discount</td><td class="r">-{{ number_format((float) $invoice->discount_amount, 2) }}</td></tr>
        @endif
        @if (($invoice->round_off ?? 0) != 0)
            <tr><td>Round Off</td><td class="r">{{ number_format((float) $invoice->round_off, 2) }}</td></tr>
        @endif
        <tr class="b"><td>TOTAL</td><td class="r">₹{{ number_format((float) $invoice->total_amount, 2) }}</td></tr>
    </table>
    <div class="line"></div>

    <table>
        @forelse ($legs as $leg)
            <tr><td>{{ strtoupper($leg->mode) }}</td><td class="r">{{ number_format((float) $leg->amount, 2) }}</td></tr>
        @empty
            <tr><td>{{ ucfirst($invoice->payment_method) }}</td><td class="r">{{ number_format((float) $invoice->total_amount, 2) }}</td></tr>
        @endforelse
    </table>
    <div class="line"></div>

    <div class="center">
        @if ($invoice->pos_status === 'void')<div class="b">*** VOID ***</div>@endif
        <div>Thank you! Visit again.</div>
    </div>

    <div class="no-print center" style="margin-top:10px;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
