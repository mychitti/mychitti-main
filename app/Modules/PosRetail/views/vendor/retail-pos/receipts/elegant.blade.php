<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_id }}</title>
    <style>
        * { font-family: 'Cambria', 'Georgia', 'Times New Roman', serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { width: 80mm; margin: 0 auto; padding: 6px; font-size: 15px; font-weight: 600; color: #000; }
        .frame { border: 1.5px solid #000; border-radius: 4px; padding: 10px 9px; }
        .center { text-align: center; }
        .store { font-size: 20px; font-weight: 700; letter-spacing: 1px; margin: 0; }
        .tag { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #000; margin-top: 2px; }
        .addr { font-size: 13px; color: #000; margin-top: 4px; }
        .dbl { border: 0; border-top: 3px double #000; margin: 8px 0; }
        .thin { border: 0; border-top: 1px solid #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { font-size: 14px; font-weight: 600; padding: 3px 0; vertical-align: top; }
        th { text-align: left; font-style: italic; font-weight: 700; border-bottom: 1.5px solid #000; padding-bottom: 4px; }
        .r { text-align: right; white-space: nowrap; }
        .meta td { font-size: 13px; padding: 1.5px 0; }
        .meta .k { color: #000; font-style: italic; }
        .grand { border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; }
        .grand td { font-size: 15px; font-weight: 700; padding: 6px 0; }
        .void { font-size: 14px; font-weight: 700; letter-spacing: 3px; border: 1.5px solid #000; color: #000; display: inline-block; padding: 3px 14px; border-radius: 3px; }
        .foot { font-style: italic; font-size: 13px; font-weight: 700; color: #000; }
        @media print { .no-print { display: none; } @page { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    @php
        $money = fn($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
        $gstRaw = $store->gst ?? null;
        $gstCode = null;
        if ($gstRaw && ($d = json_decode($gstRaw, true)) && !empty($d['status']) && !empty($d['code'])) { $gstCode = $d['code']; }
    @endphp

    <div class="frame">
        <div class="center">
            <h3 class="store">{{ $store->name }}</h3>
            @if (!empty($gstCode))<div class="tag">Tax Invoice</div>@endif
            @if (!empty($store->branch_label))<div class="addr">{{ $store->branch_label }}</div>@endif
            @if (!empty($store->address))<div class="addr">{{ $store->address }}</div>@endif
            @if (!empty($gstCode))<div class="addr">GSTIN: {{ $gstCode }}</div>@endif
            @php
                $rcFssai = ($store->fssai_show ?? false) && ($store->fssai_number ?? null) ? $store->fssai_number : null;
                $rcDocs = [];
                try {
                    $rcDocs = \App\Models\StoreDocument::where('store_id', $store->id)->where('doc_type', 'other')
                        ->where('status', 1)->where('show_on_bill', 1)
                        ->whereNotNull('doc_number')->where('doc_number', '!=', '')->get();
                } catch (\Throwable $e) { $rcDocs = []; }
            @endphp
            @if ($rcFssai)<div class="addr">FSSAI: {{ $rcFssai }}</div>@endif
            @foreach ($rcDocs as $rd)<div class="addr">{{ $rd->doc_name }}: {{ $rd->doc_number }}</div>@endforeach
        </div>
        <hr class="dbl">

        <table class="meta">
            <tr><td class="k">Invoice No.</td><td class="r"><b>{{ $invoice->pos_status === 'void' ? 'V' . $invoice->invoice_id : $invoice->invoice_id }}</b></td></tr>
            <tr><td class="k">Date</td><td class="r">{{ $invoice->created_at->format('d-m-Y h:i A') }}</td></tr>
            <tr><td class="k">Billed To</td><td class="r">{{ optional($customer)->f_name ?: 'Walk-in' }}</td></tr>
        </table>
        <hr class="thin">

        <table>
            <tr><th style="width:30%;">Qty</th><th class="r" style="width:35%;">Rate</th><th class="r" style="width:35%;">Amt</th></tr>
            @foreach ($items as $it)
                @php
                    $isLoose = !empty(optional($it->item)->sell_loose) || !empty($it->pieces);
                    if ($isLoose) {
                        $unitTxt = optional(optional($it->item)->itemunit)->unit;
                        $qtyTxt = rtrim(rtrim(number_format((float) $it->qty, 3), '0'), '.') . ($unitTxt ? ' ' . $unitTxt : '');
                    } else {
                        $qtyTxt = rtrim(rtrim(number_format((float) $it->qty, 2), '0'), '.');
                    }
                @endphp
                <tr>
                    <td colspan="3" style="padding-bottom:0;">{{ $it->name }}@if ($it->hsn && !empty($gstCode))<br><small style="color:#000;">HSN {{ $it->hsn }}</small>@endif</td>
                </tr>
                <tr>
                    <td style="padding-top:0;">{{ $qtyTxt }}</td>
                    <td class="r" style="padding-top:0;">{{ $money($it->price) }}</td>
                    <td class="r" style="padding-top:0;">{{ $money((float) $it->price * (float) $it->qty) }}</td>
                </tr>
            @endforeach
        </table>
        <hr class="thin">

        <table>
            <tr><td class="k" style="font-style:italic;color:#000;">Subtotal</td><td class="r">{{ $money($invoice->taxable_amount ?? 0) }}</td></tr>
            @if (($invoice->final_tax ?? 0) > 0)
                <tr><td class="k" style="font-style:italic;color:#000;">GST</td><td class="r">{{ $money($invoice->final_tax) }}</td></tr>
            @endif
            @if (($invoice->discount_amount ?? 0) > 0)
                <tr><td class="k" style="font-style:italic;color:#000;">Discount</td><td class="r">-{{ $money($invoice->discount_amount) }}</td></tr>
            @endif
            @if (($invoice->round_off ?? 0) != 0)
                <tr><td class="k" style="font-style:italic;color:#000;">Round Off</td><td class="r">{{ $money($invoice->round_off) }}</td></tr>
            @endif
        </table>

        <table class="grand">
            <tr><td>TOTAL</td><td class="r">₹{{ $money($invoice->total_amount) }}</td></tr>
        </table>

        <table style="margin-top:6px;">
            @forelse ($legs as $leg)
                <tr><td class="k" style="font-style:italic;color:#000;">{{ ucfirst($leg->mode) }}</td><td class="r">{{ $money($leg->amount) }}</td></tr>
            @empty
                <tr><td class="k" style="font-style:italic;color:#000;">{{ ucfirst($invoice->payment_method) }}</td><td class="r">{{ $money($invoice->total_amount) }}</td></tr>
            @endforelse
            @if (($tendered ?? 0) > 0)
                <tr><td class="k" style="font-style:italic;color:#000;">Tendered</td><td class="r">{{ $money($tendered) }}</td></tr>
                @if (($changeReturn ?? 0) > 0)
                    <tr><td class="k" style="font-style:italic;color:#000;">Change</td><td class="r">{{ $money($changeReturn) }}</td></tr>
                @elseif (($balanceDue ?? 0) > 0)
                    <tr><td class="k" style="font-style:italic;color:#000;">Balance Due</td><td class="r">{{ $money($balanceDue) }}</td></tr>
                @endif
            @endif
        </table>
        <hr class="dbl">

        @if (($savedAmount ?? 0) > 0)
            <div class="center" style="font-weight:bold;">* * Saved Rs. {{ $money($savedAmount) }}/- On MRP * *</div>
            <hr class="dbl">
        @endif

        <div class="center">
            @if ($invoice->pos_status === 'void')<div class="void">VOID</div><br><br>@endif
            <div class="foot">Thank you for your patronage.</div>
        </div>
    </div>

    <div class="no-print center" style="margin-top:10px;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
