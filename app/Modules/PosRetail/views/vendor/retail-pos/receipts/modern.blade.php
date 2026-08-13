<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_id }}</title>
    <style>
        * { font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { width: 80mm; margin: 0 auto; padding: 8px; font-size: 15px; font-weight: 600; color: #000; }
        .center { text-align: center; }
        .muted { color: #000; }
        .store { font-size: 21px; font-weight: 800; letter-spacing: .5px; margin: 0 0 2px; }
        .rule { border: 0; border-top: 1.5px solid #000; margin: 8px 0; }
        .soft { border: 0; border-top: 1px solid #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { font-size: 14px; font-weight: 600; padding: 3px 0; vertical-align: top; }
        th { text-transform: uppercase; font-size: 11.5px; letter-spacing: .5px; color: #000; text-align: left; border-bottom: 1px solid #000; }
        .r { text-align: right; white-space: nowrap; }
        .meta td { padding: 1px 0; }
        .meta .k { color: #000; }
        .totals td { padding: 2px 0; }
        .totalbox { background: #000; color: #fff; border-radius: 8px; padding: 8px 12px; margin: 8px 0; display: flex; justify-content: space-between; align-items: center; font-weight: 800; }
        .totalbox .amt { font-size: 18px; }
        .badge { display: inline-block; background: #fff; color: #000; border: 1.5px solid #000; font-weight: 700; padding: 2px 10px; border-radius: 20px; font-size: 11px; }
        .thanks { font-size: 13px; font-weight: 700; color: #000; }
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

    <div class="center">
        <h3 class="store">{{ $store->name }}</h3>
        @if (!empty($store->branch_label))<div class="muted">{{ $store->branch_label }}</div>@endif
        @if (!empty($store->address))<div class="muted">{{ $store->address }}</div>@endif
        @if (!empty($gstCode))<div class="muted">GSTIN: {{ $gstCode }}</div>@endif
        @php
            $rcFssai = ($store->fssai_show ?? false) && ($store->fssai_number ?? null) ? $store->fssai_number : null;
            $rcDocs = [];
            try {
                $rcDocs = \App\Models\StoreDocument::where('store_id', $store->id)->where('doc_type', 'other')
                    ->where('status', 1)->where('show_on_bill', 1)
                    ->whereNotNull('doc_number')->where('doc_number', '!=', '')->get();
            } catch (\Throwable $e) { $rcDocs = []; }
        @endphp
        @if ($rcFssai)<div class="muted">FSSAI: {{ $rcFssai }}</div>@endif
        @foreach ($rcDocs as $rd)<div class="muted">{{ $rd->doc_name }}: {{ $rd->doc_number }}</div>@endforeach
    </div>
    <hr class="rule">

    <table class="meta">
        <tr><td class="k">Invoice</td><td class="r"><b>{{ $invoice->pos_status === 'void' ? 'V' . $invoice->invoice_id : $invoice->invoice_id }}</b></td></tr>
        <tr><td class="k">Date</td><td class="r">{{ $invoice->created_at->format('d-m-Y h:i A') }}</td></tr>
        <tr><td class="k">Customer</td><td class="r">{{ optional($customer)->f_name ?: 'Walk-in' }}</td></tr>
    </table>
    <hr class="soft">

    <table>
        <tr><th style="width:30%;">Qty</th><th class="r" style="width:35%;">Rate</th><th class="r" style="width:35%;">Amt</th></tr>
        @foreach ($items as $it)
            @php($qtyTxt = $it->qty_label ?? rtrim(rtrim(number_format((float) $it->qty, 2), '0'), '.'))
            <tr>
                <td colspan="3" style="padding-bottom:0;">{{ $it->name }}@if ($it->hsn && !empty($gstCode))<br><span class="muted" style="font-size:10px;">HSN {{ $it->hsn }}</span>@endif</td>
            </tr>
            <tr>
                <td class="muted" style="padding-top:0;">{{ $qtyTxt }}</td>
                <td class="r" style="padding-top:0;">{{ $money($it->price) }}</td>
                <td class="r" style="padding-top:0;">{{ $money((float) $it->price * (float) $it->qty) }}</td>
            </tr>
        @endforeach
    </table>
    <hr class="soft">

    <table class="totals">
        <tr><td class="muted">Subtotal</td><td class="r">{{ $money($invoice->taxable_amount ?? 0) }}</td></tr>
        @if (($invoice->final_tax ?? 0) > 0)
            <tr><td class="muted">GST</td><td class="r">{{ $money($invoice->final_tax) }}</td></tr>
        @endif
        @if (($invoice->discount_amount ?? 0) > 0)
            <tr><td class="muted">Discount</td><td class="r">-{{ $money($invoice->discount_amount) }}</td></tr>
        @endif
        @if (($invoice->round_off ?? 0) != 0)
            <tr><td class="muted">Round Off</td><td class="r">{{ $money($invoice->round_off) }}</td></tr>
        @endif
    </table>

    <div class="totalbox">
        <span>TOTAL</span>
        <span class="amt">₹{{ $money($invoice->total_amount) }}</span>
    </div>

    <table>
        @forelse ($legs as $leg)
            <tr><td class="muted">{{ strtoupper($leg->mode) }}</td><td class="r">{{ $money($leg->amount) }}</td></tr>
        @empty
            <tr><td class="muted">{{ ucfirst($invoice->payment_method) }}</td><td class="r">{{ $money($invoice->total_amount) }}</td></tr>
        @endforelse
        @if (($tendered ?? 0) > 0)
            <tr><td class="muted">Tendered</td><td class="r">{{ $money($tendered) }}</td></tr>
            @if (($changeReturn ?? 0) > 0)
                <tr><td class="muted">Change</td><td class="r">{{ $money($changeReturn) }}</td></tr>
            @elseif (($balanceDue ?? 0) > 0)
                <tr><td class="muted">Balance Due</td><td class="r">{{ $money($balanceDue) }}</td></tr>
            @endif
        @endif
    </table>
    <hr class="soft">

    @if (($savedAmount ?? 0) > 0)
        <div class="center" style="font-weight:bold;">* * Saved Rs. {{ $money($savedAmount) }}/- On MRP * *</div>
        <hr class="soft">
    @endif

    <div class="center">
        @if ($invoice->pos_status === 'void')<div class="badge">VOID</div><br><br>@endif
        <div class="thanks">Thank you for shopping with us!</div>
    </div>

    <div class="no-print center" style="margin-top:10px;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
