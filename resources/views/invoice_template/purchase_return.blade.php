<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        .borderless_td {
            border: none;
        }

        .bottomo_sec {
            border: 1px dotted #999;
            border-collapse: collapse;
        }

        .borderless_table td {
            {{-- border: 1px solid rgb(207, 207, 207); --}} border: none;

        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        td,
        th {
            padding: 4px;
            border: 1px solid #000;
        }

        .no-border td,
        .no-border th {
            border: none;
        }

        .section-title {
            font-weight: bold;
            margin-top: 10px;
        }

        .footer-note {
            font-size: 10px;
            text-align: center;
            margin-top: 20px;
        }

        .border-top-none {
            border-top: none;
        }

        .invoice-header {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }



        .box.ofh {
            overflow: hidden;
        }

        /* Ribbon 1 */


        /*Ribbon 4 */
        .ribbon {
            height: 110px;
            width: 110px;
            position: absolute;
            top: -1px;
            right: -10px;
        }
    </style>

</head>

<body>


    <table class="no-border">
        <tr>
            <td style="width: 70%;">
                <strong> {{ $data['store']['name'] }}</strong><br>
                {{ $data['store']['address'] }}<br>
                {{ $data['store']['email'] }}<br>
                {{ $data['store']['phone'] }}<br>


            </td>
            <td style="width: 30%; text-align: right;">
                @php  $store_logo = $data['store']['logo']; @endphp
                <img width="100" class=""
                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/store/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                    alt="Logo">
            </td>
        </tr>
    </table>


    <h3 class="text-center invoice-header">
        Purchase Return Slip
    </h3>

    <table class="no-border">
        <tr>
            <td style="width: 33.33%; vertical-align: top;">
                <strong>Vendor:</strong><br>
                {{ $data['vendor']['name'] }}<br>
                {{ $data['vendor']['address'] }}<br>
                {!! $data['vendor']['email'] ? 'Email: ' . $data['vendor']['email'] . '<br>' : '' !!}
                Ph NO: {{ $data['vendor']['phone'] }}<br>

            </td>


            <td style="width: 33.33%; vertical-align: top; text-align: right; line-height: 1.6;">
                @foreach ($data['invoices'] as $key => $value)
                    <strong>Reference:</strong> {{ $value['invoice_id'] }}<br>
                    <span><strong>Date:</strong> {{ $value['date'] }}</span><br>
                @endforeach

            </td>
        </tr>
    </table>

    <table style="margin-bottom:100px; font-size:10px;">
        <thead>
            <tr class="center">
                <th class="border-bottom-none">Sl</th>
                <th class="border-bottom-none" colspan="3">ITEM DESCRIPTION</th>
                <th class="border-bottom-none">QTY</th>
                <th class="border-bottom-none">PRICE</th>
                <th class="border-bottom-none">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAmount = 0 @endphp
            @foreach ($data['item_name'] as $key => $item)
                <tr class="no-border">
                    <td class="no-border">{{ $key + 1 }}</td>
                    <td class="no-border" colspan="3">{!! $item !!} </td>
                    <td class="no-border">{{ $data['qty'][$key] }}</td>
                    <td class="no-border">{{ $data['price'][$key] }}</td>
                    <td class="no-border">{{ $data['qty'][$key] * $data['price'][$key] }}</td>
                </tr>
                @php $totalAmount += $data['qty'][$key] * $data['price'][$key];@endphp
            @endforeach
        </tbody>
    </table>
    <table style="border: 1px dotted #999; border-collapse: collapse; width: 100%;  font-size: 10px; margin-top: 20px; "
        class="bottomo_sec">
        @php
            $totalAmt = round($totalAmount, 2); // keep two decimals as float
            $totalAmountRO = round($totalAmount); // round to nearest integer
            $roundOff = number_format($totalAmt - $totalAmountRO, 3); // proper round off difference
        @endphp
        <tr>
            <td class="borderless_td" style="width: 20%; vertical-align: top; font-size:10px;">
                <b>INVOICE AMOUNT IN WORDS</b><br>
                {{ ucwords(_convertNumberToWords($totalAmountRO)) }}
                <br>
                <br>

            </td>
            <td class="borderless_td" style="width: 30%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; font-size: 10px; " class="">

                    <tr>
                        <td class="borderless_td" style="text-align: right;">Sub Total:</td>
                        <td class="borderless_td" style="text-align: right;">
                            {{ \App\CentralLogics\Helpers::currency_symbol() . number_format($totalAmount, 3) }}
                        </td>
                    </tr>

                    <tr>
                        <td class="borderless_td" style="text-align: right;">Rounded Off:</td>
                        <td class="borderless_td" style="text-align: right;">
                            {{ $roundOff }}
                        </td>
                    </tr>

                    <tr>
                        <td class="borderless_td" style="text-align: right; font-size:12px;"><b>Grand Total:</b></td>
                        <td class="borderless_td" style="text-align: right; font-size:12px;">
                            <b>{{ \App\CentralLogics\Helpers::currency_symbol() . number_format($totalAmountRO, 3) }}</b>
                        </td>
                    </tr>


                </table>
            </td>
        </tr>
    </table>



    <table class="no-border" style="width: 100%; font-size: 12px;">
        <tr>
            <td align="left">
                <a href="https://www.mychitti.net">www.mychitti.net</a>
            </td>
            <td align="right">
                {{-- <a href="mailto:mychitti@mychitti.net">mychitti@mychitti.net</a> --}}
            </td>
        </tr>
    </table>

    <p style="text-align: center; font-size:10px;">
        {{ \App\Models\BusinessSetting::where(['key' => 'business_name'])->first()->value }}.</p>

</body>

</html>
