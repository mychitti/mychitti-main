<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: monospace;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .receipt {
            width: 100%;
            max-width: 300px;
        }

        .center {
            text-align: center;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 2px 4px;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="receipt">
        @if (!$kitchen)
            <div class="center bold">{{ $token->seller_name }}</div>
            <div class="center">
                {{ $token->address }}<br>
                Phone: {{ $store->phone }}<br>
                Email: {{ $store->email }}
                @if ($token->gst_type && $token->gst_number)
                    <br>GST: {{ $token->gst_number }}
                @endif

            </div>
        @endif

        <div class="line"></div>

        <table>
            <tr>
                <td class="bold">TOKEN NO:</td>
                <td class="right bold">{{ $token->token_number }}</td>
            </tr>
            <tr>
                <td>Date:</td>
                <td class="right">{{ $token->created_at->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td>Time:</td>
                <td class="right">{{ $token->created_at->format('H:i:s') }}</td>
            </tr>
        </table>

        <div class="line"></div>

        <table>
            <tr class="bold">
                <td>Qty</td>
                <td class="center">Item</td>
                @if (!$kitchen)
                    <td class="center">CGST</td>
                    <td class="center">SGST</td>
                    <td class="right">Price</td>
                @endif
            </tr>
            @foreach ($token->tokenItems as $key => $value)
                <tr>
                    <td>{{ $value->qty }}</td>
                    <td class="center">{{ $value->item_name }}</td>
                    @if (!$kitchen)
                        <td class="center">
                            {{ $value->gst_percent ? number_format($value->gst_percent / 2, 2) . '% (incl.)' : 0 . '%' }}
                        </td>
                        <td class="center">
                            {{ $value->gst_percent ? number_format($value->gst_percent / 2, 2) . '% (incl.)' : 0 . '%' }}
                        </td>
                        <td class="right">₹{{ $value->item_total }}</td>
                    @endif
                </tr>
            @endforeach

        </table>

        <div class="line"></div>
        @if (!$kitchen)
            <table>
                <tr>
                    <td>Sub Total:</td>
                    <td class="right">₹{{ $token->subtotal }}</td>
                </tr>
                <tr>
                    <td>CGST:</td>
                    <td class="right">₹{{ number_format($token->gst_amount / 2, 2) }}</td>
                </tr>
                <tr>
                    <td>SGST:</td>
                    <td class="right">₹{{ number_format($token->gst_amount / 2, 2) }}</td>
                </tr>
                @if ($token->coupon > 0 )
                    <tr>
                        <td>Coupon</td>
                        <td class="right">₹{{ $token->coupon }}</td>
                    </tr>
                @endif
                @if ($token->discount > 0 )
                    <tr>
                        <td>Discount</td>
                        <td class="right">₹{{ $token->discount }}</td>
                    </tr>
                @endif
                @if ($token->delivery > 0 )
                <tr>
                    <td>Delivery:</td>
                    <td class="right">₹{{ $token->delivery }}</td>
                </tr>
                @endif
                <tr class="bold">
                    <td>Total:</td>
                    <td class="right">₹{{ $token->total }}</td>
                </tr>
            </table>

            <div class="line"></div>

            <div>Payment Method: {{ ucfirst($token->payment_method) }} ({{ ucfirst($token->payment_status) }})</div>
            <div>Order Type: {{ ucfirst($token->order_from) }} </div>

            <div class="line"></div>

            <div class="center">
                {{ $store_config && $store_config->token_footer_line_status ? $store_config->token_footer_line : '' }}
            </div>
            <div class="center">Powered by Mychitti.net</div>
        @endif
    </div>
</body>

</html>
