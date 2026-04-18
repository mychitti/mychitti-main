<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Challan — {{ $challan->challan_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 14px; }
        .header h2 { font-size: 18px; text-transform: uppercase; letter-spacing: 1px; }
        .header h3 { font-size: 13px; font-weight: normal; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 14px; }
        .meta-block p { margin-bottom: 3px; }
        .meta-block strong { display: inline-block; min-width: 110px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th { background: #f0f0f0; border: 1px solid #999; padding: 5px 7px; text-align: center; font-size: 11px; }
        td { border: 1px solid #ccc; padding: 5px 7px; text-align: center; font-size: 11px; }
        td.item-name { text-align: left; }
        tfoot td { background: #f0f0f0; font-weight: bold; }
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
        .sig-box { text-align: center; width: 200px; }
        .sig-line { border-top: 1px solid #000; padding-top: 6px; margin-top: 30px; font-size: 11px; }
        .status-badge { display: inline-block; padding: 2px 10px; border: 1px solid #000; border-radius: 3px; font-size: 11px; }
        @media print {
            body { padding: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom:16px;">
        <button onclick="window.print()" style="padding:8px 20px; cursor:pointer;">Print</button>
        <button onclick="window.close()" style="padding:8px 20px; cursor:pointer; margin-left:8px;">Close</button>
    </div>

    <div class="header">
        <h2>{{ $challan->store->name ?? 'Laundry' }}</h2>
        <h3>Laundry Delivery Challan</h3>
    </div>

    <div class="meta">
        <div class="meta-block">
            <p><strong>Challan No :</strong> {{ $challan->challan_no }}</p>
            <p><strong>Hotel / Client :</strong> {{ $challan->hotel->f_name ?? '-' }}</p>
            <p><strong>Phone :</strong> {{ $challan->hotel->phone ?? '-' }}</p>
            @if($challan->notes)
            <p><strong>Notes :</strong> {{ $challan->notes }}</p>
            @endif
        </div>
        <div class="meta-block" style="text-align:right;">
            <p><strong>Outward Date :</strong> {{ $challan->outward_date->format('d/m/Y') }}</p>
            <p><strong>Expected Inward :</strong> {{ $challan->expected_inward_date ? $challan->expected_inward_date->format('d/m/Y') : '-' }}</p>
            <p><strong>Inward Date :</strong> {{ $challan->inward_date ? $challan->inward_date->format('d/m/Y') : '-' }}</p>
            <p><strong>Status :</strong> <span class="status-badge">{{ $challan->status_label }}</span></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:22%; text-align:left;">Item</th>
                <th style="width:10%">Previous Balance</th>
                <th style="width:9%">Sent</th>
                <th style="width:9%">Total</th>
                <th style="width:10%">Received</th>
                <th style="width:10%">Damaged</th>
                <th style="width:9%">Balance</th>
                <th style="width:17%">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($challan->items as $key => $item)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td class="item-name">{{ $item->item_name }}</td>
                <td>{{ $item->previous_balance }}</td>
                <td>{{ $item->sent_qty }}</td>
                <td>{{ $item->total }}</td>
                <td>{{ $item->received_qty }}</td>
                <td>{{ $item->damaged_qty }}</td>
                <td><strong>{{ $item->balance }}</strong></td>
                <td style="text-align:left;">{{ $item->remarks }}</td>
            </tr>
            @endforeach
            {{-- Filler rows for visual spacing --}}
            @for($i = $challan->items->count(); $i < 12; $i++)
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right;">Total</td>
                <td>{{ $challan->items->sum('previous_balance') }}</td>
                <td>{{ $challan->items->sum('sent_qty') }}</td>
                <td>{{ $challan->items->sum('total') }}</td>
                <td>{{ $challan->items->sum('received_qty') }}</td>
                <td>{{ $challan->items->sum('damaged_qty') }}</td>
                <td>{{ $challan->items->sum('balance') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="signatures">
        <div class="sig-box">
            <div class="sig-line">Laundry Authority</div>
        </div>
        <div class="sig-box">
            <div class="sig-line">Receiver's Signature<br><small>(Hotel Stores Authority)</small></div>
        </div>
    </div>

</body>
</html>
