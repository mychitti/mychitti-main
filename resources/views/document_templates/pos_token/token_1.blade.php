<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>POS Token</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
            width: 72mm;
        }

        .ticket {
            width: 100%;
            box-sizing: border-box;
            padding: 0;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .left {
            text-align: left;
        }

        header {
            padding-bottom: 4px;
            border-bottom: 1px dashed #000;
            margin-bottom: 6px;
        }

        .brand {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .small {
            font-size: 10px;
        }

        .token-box {
            margin: 6px 0;
            padding: 6px 0;
        }

        .token-number {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 6px;
        }

        table.items th,
        table.items td {
            padding: 4px 0;
            vertical-align: top;
            font-size: 11px;
        }

        table.items th {
            font-weight: 600;
        }

        .item-line {
            border-bottom: 0px solid rgba(0, 0, 0, 0.06);
        }

        .qty {
            width: 12mm;
        }

        .gst {
            width: 12mm;
        }

        .name {
            width: calc(100% - 36mm);
        }

        .price {
            width: 24mm;
            text-align: right;
        }

        .totals {
            margin-top: 6px;
            border-top: 1px dashed #000;
            padding-top: 6px;
        }

        .totals table {
            width: 100%;
        }

        .totals td {
            padding: 3px 0;
            font-size: 12px;
        }

        footer {
            margin-top: 8px;
            border-top: 1px dashed #000;
            padding-top: 6px;
            font-size: 10px;
        }

        .qr {
            margin-top: 6px;
            text-align: center;
        }

        .fw_bold {
            font-weight: bold;
        }

        .meta {
            font-family: monospace;
            font-size: 10px;
        }

        .no-break {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    <div class="ticket">
        @if (!$kitchen)
            <header class="center no-break">
                <div class="brand">{{ $token->seller_name }}</div>
                <div class="small">{{ $token->address }}</div>
                <div class="small">{{ $store->email }}</div>
                <div class="small">Phone: {{ $store->phone }}</div>
                @if($token->gst_type && $token->gst_number)<div class="small">GST: {{ $token->gst_number }}</div>@endif
            </header>
        @endif

        <section class="token-box center no-break">
            <div class="small">{{ $kitchen ? 'Kitchen' : '' }} Token</div>
            <div class="token-number">{{ $token->token_number }}</div>
            <div class="small meta">{{ $token->created_at }}</div>
        </section>

        <section class="no-break">
            <table class="items" role="presentation">
                <thead>
                    <tr>
                        <th class="left qty fw_bold">Qty</th>
                        <th class="left name fw_bold">Item</th>
                        @if (!$kitchen)

                            @if ($token->gst_type == 'gst')
                                <th class="left gst fw_bold">CGST</th>
                                <th class="left gst fw_bold">SGST</th>
                            @endif
                            <th class="right price fw_bold">Price</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($token->tokenItems as $key => $value)
                        <tr class="item-line">
                            <td class="qty left">{{ $value->qty }}</td>
                            <td class="name left">{{ ucfirst($value->item_name) }}</td>
                            @if (!$kitchen)
                                @if ($token->gst_type == 'gst')
                                    <td class="gst left">
                                        {{ $value->gst_percent ? number_format($value->gst_percent / 2, 2) . '% (incl.)' : 0 . '%' }}
                                    </td>
                                    <td class="gst left">
                                        {{ $value->gst_percent ? number_format($value->gst_percent / 2, 2) . '% (incl.)' : 0 . '%' }}
                                    </td>
                                @endif
                                <td class="price right">₹ {{ $value->item_total }}</td>
                            @endif
                        </tr>
                    @endforeach

                </tbody>
            </table>
            @if (!$kitchen)
                <div class="totals no-break">
                    <table role="presentation">
                        <tr>
                            <td class="left">Sub Total</td>
                            <td class="right">₹{{ $token->subtotal }}</td>
                        </tr>
                        <tr>
                            <td class="left">CGST</td>
                            <td class="right">₹{{ number_format($token->gst_amount / 2, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="left">SGST</td>
                            <td class="right">₹{{ number_format($token->gst_amount / 2 , 2)}}</td>
                        </tr>
                        @if($token->coupon > 0)
                        <tr>
                            <td class="left">Coupon</td>
                            <td class="right">₹{{ $token->coupon }}</td>
                        </tr>
                        @endif
                        @if($token->discount >0)
                        <tr>
                            <td class="left">Discount</td>
                            <td class="right">₹{{ $token->discount }}</td>
                        </tr>
                        @endif
                       @if($token->delivery > 0)
                        <tr>
                            <td class="left">Delivery</td>
                            <td class="right">₹{{ $token->delivery }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="left" style="font-weight:700;">Total</td>
                            <td class="right" style="font-weight:700;">₹{{ $token->total }}</td>
                        </tr>
                    </table>
                </div>
            @endif
        </section>
        @if (!$kitchen)
            <section class="qr">
                <div class="small">Payment Method: <span class="meta">{{ ucfirst($token->payment_method) }} |
                        {{ ucfirst($token->payment_status) }}</span>
                </div>
                <div class="small">Order Type: <span class="meta">{{ ucfirst($token->order_from) }} </span></div>
            </section>

            <footer class="center">
                <div class="small">
                    {{ $store_config && $store_config->token_footer_line_status ? $store_config->token_footer_line : '' }}
                </div>
                <div class="small">Powered by Mychitti.net</div>
            </footer>
        @endif
    </div>
</body>

</html>
