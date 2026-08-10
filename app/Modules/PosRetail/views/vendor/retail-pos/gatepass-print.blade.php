<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Gatepass {{ $gatepass->gatepass_no }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #222; margin: 0; padding: 24px; font-size: 13px; }
        .gp-wrap { max-width: 720px; margin: 0 auto; }
        .gp-top { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #222; padding-bottom: 10px; }
        .gp-top h2 { margin: 0; font-size: 20px; }
        .gp-top .store { font-weight: 700; font-size: 15px; }
        .gp-title { text-align: center; font-size: 16px; font-weight: 700; letter-spacing: 1px; margin: 14px 0 10px; }
        .gp-meta { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .gp-meta div { font-size: 13px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 7px 9px; text-align: left; }
        th { background: #f3f4f6; font-size: 12px; text-transform: uppercase; letter-spacing: .03em; }
        td.r, th.r { text-align: right; }
        .gp-sign { display: flex; justify-content: space-between; margin-top: 50px; }
        .gp-sign div { width: 45%; border-top: 1px solid #888; padding-top: 6px; text-align: center; font-size: 12px; color: #555; }
        .gp-actions { text-align: center; margin-top: 22px; }
        .gp-actions button { padding: 8px 18px; border: 0; border-radius: 6px; background: #e3342f; color: #fff; font-weight: 700; cursor: pointer; }
        @media print { .gp-actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="gp-wrap">
        <div class="gp-top">
            <div>
                <div class="store">{{ $store->name ?? 'Store' }}</div>
                @if (!empty($store->address))<div>{{ $store->address }}</div>@endif
                @if (!empty($store->phone))<div>Ph: {{ $store->phone }}</div>@endif
            </div>
            <div style="text-align:right;">
                <h2>GATEPASS</h2>
                <div><b>{{ $gatepass->gatepass_no }}</b></div>
            </div>
        </div>

        <div class="gp-title">STOCK TRANSFER NOTE</div>

        <div class="gp-meta">
            <div><b>From:</b> {{ optional($fromBranch ?? null)->name ?? 'Main Store' }}</div>
            <div><b>To:</b> {{ optional($branch)->name ?? '—' }}</div>
            <div><b>Date:</b> {{ \Carbon\Carbon::parse($gatepass->created_at)->format('d M Y, h:i A') }}</div>
        </div>
        @if (!empty($gatepass->note))
            <div style="margin-bottom:10px;"><b>Note:</b> {{ $gatepass->note }}</div>
        @endif

        <table> 
            <thead>
                <tr><th width="40">#</th><th>Item</th><th>SKU</th><th class="r" width="140">Qty</th></tr>
            </thead>
            <tbody>
                @forelse ($items as $i => $row)
                    <tr> 
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $row->variation_type ? $row->item_name . ' (' . $row->variation_type . ')' : $row->item_name }}</td>
                        <td>{{ $row->sku_id }}</td>
                        <td class="r">{{ $row->qty_label ?? (rtrim(rtrim(number_format((float) $row->qty, 3), '0'), '.') . ' ' . $row->unit_label) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;">No items</td></tr>
                @endforelse
            </tbody>
        </table> 

        <div class="gp-sign">
            <div>Issued By</div>
            <div>Received By</div>
        </div>

        <div class="gp-actions">
            <button onclick="window.print()">🖨 Print</button>
        </div>
    </div>
    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
</body>
</html>
