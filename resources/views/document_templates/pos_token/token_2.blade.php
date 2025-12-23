<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>POS Token</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        .receipt {
            max-width: 280px;
            margin: 0 auto;
        }

        .store-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }

        .store-header h2 {
            margin: 0;
            font-size: 14px;
        }

        .store-header p {
            margin: 2px 0;
            font-size: 11px;
        }

        .token {
            text-align: center;
            margin: 10px 0;
        }

        .token h1 {
            font-size: 22px;
            margin: 0;
        }

        .details,
        .totals {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .details th,
        .details td {
            border-bottom: 1px dashed #ccc;
            padding: 4px;
            text-align: left;
        }

        .details th {
            font-size: 11px;
            text-align: center;
        }

        .details td {
            font-size: 11px;
        }

        .totals td {
            padding: 4px;
            font-size: 12px;
        }

        .totals .label {
            text-align: right;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <div class="receipt">
        @if (!$kitchen)
            <div class="store-header">
                <h2>{{ $token->seller_name }}</h2>
                <p>{{ $token->address }}</p>
                <p>Email: {{ $store->email }}<br>Phone: {{ $store->phone }}</p>
                @if ($token->gst_type && $token->gst_number)
                    <p>GST: {{ $token->gst_number }}</p>
                @endif

            </div>
        @endif

        <div class="token">
            <p><strong>{{ $kitchen ? 'Kitchen' : '' }} Token</strong></p>
            <h1>{{ $token->token_number }}</h1>
            <p>{{ $token->created_at }}</p>
        </div>

        <table class="details">
            <tr>
                <th>Qty</th>
                <th>Item</th>
                @if (!$kitchen)
                    <th>CGST</th>
                    <th>SGST</th>
                    <th style="text-align:right;">Price</th>
                @endif
            </tr>
            @foreach ($token->tokenItems as $key => $value)
                <tr>
                    <td style="text-align:center;">{{ $value->qty }}</td>
                    <td style="text-align:center;">{{ $value->item_name }}</td>
                    @if (!$kitchen)
                        <td>{{ $value->gst_percent ? number_format($value->gst_percent / 2, 2) . '% (incl.)' : 0 . '%' }}
                        </td>
                        <td>{{ $value->gst_percent ? number_format($value->gst_percent / 2, 2) . '% (incl.)' : 0 . '%' }}
                        </td>
                        <td style="text-align:right;">₹{{ $value->item_total }}</td>
                    @endif
                </tr>
            @endforeach

        </table>
        @if (!$kitchen)
            <table class="totals">
                <tr>
                    <td class="label">Sub Total:</td>
                    <td style="text-align:right;">₹{{ $token->subtotal }}</td>
                </tr>
                <tr>
                    <td class="label">CGST</td>
                    <td style="text-align:right;">₹{{ number_format($token->gst_amount / 2, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">SGST</td>
                    <td style="text-align:right;">₹{{ number_format($token->gst_amount / 2, 2) }}</td>
                </tr>
                @if ($token->coupon > 0 )
                    <tr>
                        <td class="label">Coupon</td>
                        <td style="text-align:right;">₹{{ $token->coupon }}</td>
                    </tr>
                @endif
                @if ($token->discount > 0)
                    <tr>
                        <td class="label">Discount</td>
                        <td style="text-align:right;">₹{{ $token->discount }}</td>
                    </tr>
                @endif
                @if ($token->delivery > 0)
                <tr>
                    <td class="label">Delivery:</td>
                    <td style="text-align:right;">₹{{ $token->delivery }}</td>
                </tr>
                @endif

                <tr>
                    <td class="label"><strong>Total:</strong></td>
                    <td style="text-align:right;"><strong>₹{{ $token->total }}</strong></td>
                </tr>
            </table>
            <div class="footer">
                <p>Payment Method: {{ ucfirst($token->payment_method) }} | {{ ucfirst($token->payment_status) }}</p>
                <p>Order Type: {{ ucfirst($token->order_from) }} </p>
                <p>{{ $store_config && $store_config->token_footer_line_status ? $store_config->token_footer_line : '' }}
                </p>
                <p>Powered by Mychitti.net</p>
            </div>
        @endif

    </div>
</body>

</html>
