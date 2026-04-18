<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt — {{ $order->order_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; }

        /* Receipt width — works for both 80mm thermal and A4 */
        .receipt {
            width: 300px;
            margin: 20px auto;
            padding: 14px;
            border: 1px dashed #999;
        }

        .store-name { text-align: center; font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .store-sub  { text-align: center; font-size: 11px; color: #555; margin-bottom: 6px; }
        .divider    { border-top: 1px dashed #000; margin: 8px 0; }

        .token      { text-align: center; font-size: 28px; font-weight: bold; letter-spacing: 2px; padding: 6px 0; }
        .token-label{ text-align: center; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #555; }

        .info-row   { display: flex; justify-content: space-between; padding: 2px 0; font-size: 11px; }
        .info-label { color: #555; }

        table { width: 100%; margin: 6px 0; font-size: 11px; }
        th    { text-align: left; border-bottom: 1px solid #ccc; padding: 3px 2px; font-size: 10px; text-transform: uppercase; color: #555; }
        td    { padding: 3px 2px; border-bottom: 1px solid #eee; }
        td.right, th.right { text-align: right; }

        .total-row  { display: flex; justify-content: space-between; font-weight: bold; padding: 4px 0; font-size: 13px; }
        .footer     { text-align: center; font-size: 10px; color: #777; margin-top: 8px; }

        @media print {
            body { margin: 0; }
            .receipt { border: none; margin: 0; padding: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="padding:14px; text-align:center;">
        <button onclick="window.print()" style="padding:8px 20px; cursor:pointer;">Print Receipt</button>
        <button onclick="window.close()" style="padding:8px 20px; cursor:pointer; margin-left:8px;">Close</button>
    </div>

    <div class="receipt">
        <div class="store-name">{{ $store->name ?? 'Laundry' }}</div>
        @if($store && $store->phone)
        <div class="store-sub">{{ $store->phone }}</div>
        @endif
        <div class="store-sub">Laundry Drop-off Receipt</div>

        <div class="divider"></div>

        <div class="token-label">Token / Order</div>
        <div class="token">{{ $order->order_no }}</div>

        <div class="divider"></div>

        <div class="info-row">
            <span class="info-label">Customer</span>
            <span>{{ $order->customer_display_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Phone</span>
            <span>{{ $order->customer_display_phone }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Drop Date</span>
            <span>{{ $order->drop_date->format('d M Y') }}</span>
        </div>
        @if($order->expected_ready_date)
        <div class="info-row">
            <span class="info-label">Ready By</span>
            <span><strong>{{ $order->expected_ready_date->format('d M Y') }}</strong></span>
        </div>
        @endif

        <div class="divider"></div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="right">Qty</th>
                    <th class="right">Rate</th>
                    <th class="right">Amt</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->item_name }}@if($item->notes) <br><small style="color:#777">{{ $item->notes }}</small>@endif</td>
                    <td class="right">{{ $item->qty }}</td>
                    <td class="right">{{ number_format($item->rate, 0) }}</td>
                    <td class="right">{{ number_format($item->amount, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="total-row">
            <span>Total Amount</span>
            <span>{{ number_format($order->total_amount, 2) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Payment</span>
            <span>{{ $order->payment_status === 'paid' ? 'Paid' : 'Due on Pickup' }}</span>
        </div>

        @if($order->notes)
        <div class="divider"></div>
        <div style="font-size:11px; color:#555;">Note: {{ $order->notes }}</div>
        @endif

        <div class="divider"></div>
        <div class="footer">Please bring this receipt at the time of pickup.<br>Thank you!</div>
    </div>

</body>
</html>
