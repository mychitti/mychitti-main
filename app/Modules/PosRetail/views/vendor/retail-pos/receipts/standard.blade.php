<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_id }}</title>
    <style>
        * { font-family: 'Courier New', monospace; }
        body { width: 80mm; margin: 0 auto; padding: 6px; font-size: 15px; font-weight: bold; color: #000; }
        .center { text-align: center; }
        .b { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { font-size: 14px; font-weight: bold; padding: 2px 0; vertical-align: top; }
        .r { text-align: right; }
        td.r { padding-left: 6px; white-space: nowrap; }
        h3 { margin: 2px 0; font-size: 20px; }
        @media print { .no-print { display: none; } @page { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    @php
        // Amount formatter: up to 2 decimals, drop trailing ".00"/zeros, keep thousands separators.
        $money = fn($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    @endphp
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
        <tr><td>Customer</td><td class="r">{{ optional($customer)->f_name ?: 'Walk-in' }}</td></tr>
    </table>
    <div class="line"></div>

    <table>
        <tr class="b"><td>Item</td><td class="r">Qty</td><td class="r">Rate</td><td class="r">Amt</td></tr>
        @foreach ($items as $it)
            <tr>
                @php
                    $isLoose = !empty(optional($it->item)->sell_loose) || !empty($it->pieces);
                    if ($isLoose) {
                        // Weighed item — show only the weight (with unit), not the piece count.
                        $unitTxt = optional(optional($it->item)->itemunit)->unit;
                        $qtyTxt = rtrim(rtrim(number_format((float) $it->qty, 3), '0'), '.') . ($unitTxt ? ' ' . $unitTxt : '');
                    } else {
                        $qtyTxt = rtrim(rtrim(number_format((float) $it->qty, 2), '0'), '.');
                    }
                @endphp
                <td>{{ $it->name }}@if ($it->hsn) <br><small>HSN {{ $it->hsn }}</small>@endif</td>
                <td class="r">{{ $qtyTxt }}</td>
                <td class="r">{{ $money($it->price) }}</td>
                <td class="r">{{ $money((float) $it->price * (float) $it->qty) }}</td>
            </tr>
        @endforeach
    </table>
    <div class="line"></div>

    <table>
        <tr><td>Subtotal</td><td class="r">{{ $money($invoice->taxable_amount ?? 0) }}</td></tr>
        @if (($invoice->final_tax ?? 0) > 0)
            <tr><td>GST</td><td class="r">{{ $money($invoice->final_tax) }}</td></tr>
        @endif
        @if (($invoice->discount_amount ?? 0) > 0)
            <tr><td>Discount</td><td class="r">-{{ $money($invoice->discount_amount) }}</td></tr>
        @endif
        @if (($invoice->round_off ?? 0) != 0)
            <tr><td>Round Off</td><td class="r">{{ $money($invoice->round_off) }}</td></tr>
        @endif
        <tr class="b"><td>TOTAL</td><td class="r">₹{{ $money($invoice->total_amount) }}</td></tr>
    </table>
    <div class="line"></div>

    <table>
        @forelse ($legs as $leg)
            <tr><td>{{ strtoupper($leg->mode) }}</td><td class="r">{{ $money($leg->amount) }}</td></tr>
        @empty
            <tr><td>{{ ucfirst($invoice->payment_method) }}</td><td class="r">{{ $money($invoice->total_amount) }}</td></tr>
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
