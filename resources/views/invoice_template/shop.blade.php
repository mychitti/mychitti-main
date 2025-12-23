<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
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

        .items_table td {
            text-align: center;
            vertical-align: middle;
            /* optional: centers content vertically */
        }
    </style>
</head>

<body>
    @php
        $same_state = $data['user_state_code'] == $data['store_state_code']; // if both are same
        $gst_types = [0,3, 5, 12, 18, 28];
        $gst_summary = array_fill_keys($gst_types, 0);

        $composition_vendor = false;
        if ($order->store && $order->store->vendor_type == 'composition') {
            $composition_vendor = true;
        }

    @endphp
    @if (!$composition_vendor)
        <style>
            .border-bottom-none {
                border-bottom: none;
            }
        </style>
    @endif

    <table class="no-border">
        <tr>
            <td style="width: 70%;">
                <strong>Sold By: {{ $order->store?->name }}</strong><br>
                {!! $composition_vendor ? '<b>Composition Vendor</b> <br>' : '' !!}
                {{ $order->store?->address }}<br>
                @if ($order->store?->gst_number || ($order->store?->gst && json_decode($order->store?->gst)->status) )
                GST NO: {{ $order->store?->gst_number }}<br>
                PAN NO: {{ $order->store?->gst_number ? substr($order->store?->gst_number, 2, 10) : '' }}<br>
                @endif
            </td>
            <td style="width: 30%; text-align: right;">
                @php
                $store_logo = $order->store?->logo; @endphp
                <img width="100" class=""
                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/store/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                    alt="Logo">
            </td>
        </tr>
    </table>

    <h3 class="text-center invoice-header">Tax Invoice</h3>

    <table class="no-border">
        <tr>
            <td style="width: 33.33%; vertical-align: top;">

                <strong>Billing Address:</strong><br>
                {{ $data['billing_address']->contact_person_name }}<br>
                {!! wordwrap($data['billing_address']->address, 35, '<br>') !!}<br>
                Email: {{ $data['user']->email }}<br>
                Ph NO: {{ $data['billing_address']->contact_person_number }}<br>
            </td>
            @php
                $customer_address = json_decode($order->delivery_address);
            @endphp
            @if ($order->order_type == 'delivery' && $customer_address->address != $data['billing_address']->address)
                <td style="width: 33.33%; vertical-align: top;">

                    <strong>Shipping Address:</strong><br>
                    {{ $customer_address->contact_person_name }}<br>
                    {!! wordwrap($customer_address->address, 35, '<br>') !!}<br>
                    Email: {{ $customer_address->contact_person_email }}<br>
                    Ph NO: {{ $customer_address->contact_person_number }}<br>
                </td>
            @endif
            <td style="width: 33.33%; vertical-align: top; text-align: right; line-height: 1.6;">
                <span><strong>Order Id:</strong> {{ $order->id }}</span><br>
                <span><strong>Invoice No:</strong> {{ $order->invoice_id }}</span><br>
                <span><strong>Invoice Date:</strong> {{ date('d-m-Y', strtotime($order->created_at)) }}</span><br>
                <span><strong>Invoice Time:</strong> {{ date('H:i:s', strtotime($order->created_at)) }}</span><br>
                <span><strong>Place of Supply:</strong>
                    {{ $data['user_state_code'] ? $data['user_state_code']['abbr'] . '-' . $data['user_state_code']['code'] : '' }}</span>
            </td>
        </tr>
    </table>

    <table style="margin-bottom:100px; font-size:10px;" class="items_table">
        <thead>
            <tr class="center">
                <th class="border-bottom-none">Sl</th>
                <th class="border-bottom-none" colspan="3">ITEM DESCRIPTION</th>
                <th class="border-bottom-none">HSN/SAC</th>
                <th class="border-bottom-none">QTY</th>
                <th class="border-bottom-none">MRP</th>
                <th class="border-bottom-none">DISC</th>
                <th class="border-bottom-none">PRICE</th>
                @if (!$composition_vendor)
                    <th class="border-bottom-none">TAXABLE</th>
                    @if ($same_state)
                        <th class="border-bottom-none" colspan="2">CGST</th>
                        <th class="border-bottom-none" colspan="2">SGST</th>
                    @else
                        <th class="border-bottom-none" colspan="2">IGST</th>
                    @endif
                    <th class="border-bottom-none" style="font-size:9px;width: 40px;">TAX Total</th>
                @endif
                <th class="border-bottom-none">TOTAL</th>
            </tr>
            @if (!$composition_vendor)
                <tr>
                    <th class="border-top-none"></th>
                    <th class="border-top-none" colspan="3"></th>
                    <th class="border-top-none"></th>
                    <th class="border-top-none"></th>
                    <th class="border-top-none"></th>
                    <th class="border-top-none"></th>
                    <th class="border-top-none"></th>
                    <th class="border-top-none"></th>
                    <th>RATE</th>
                    <th>AMT</th>
                    <th>RATE</th>
                    <th>AMT</th>
                    @if ($same_state)
                        <th class="border-top-none"></th>
                        <th class="border-top-none"></th>
                    @endif
                </tr>
            @endif
        </thead>
        <tbody>
            @php
                $subTotalPrice = 0;
                $total_discount = 0;
                $totalTaxAmount = 0;
                $totalTaxableAmount = 0;
            @endphp
            @foreach ($order->details as $key => $ordr)
                @php
                    $item = json_decode($ordr->item_details); // item
                    $variation = json_decode($ordr->variation); // variation
                    $cgst_percent = $sgst_percent =
                        $variation && isset($variation[0]->tax) ? $variation[0]->tax / 2 : $item->tax / 2;
                    $igst_percent = $variation && isset($variation[0]->tax) ? $variation[0]->tax : $item->tax;

                    $total_discount += $ordr->discount_on_item * $ordr->quantity;
                    $subTotalPrice += $ordr->price * $ordr->quantity;
                    $totalTaxAmount += $ordr->tax_amount * $ordr->quantity;

                    $taxable_amount =
                        $ordr->taxable_amount ??
                        $ordr->price * $ordr->quantity - $ordr->discount_on_item * $ordr->quantity; // subtotal - discount

                    $totalTaxableAmount += $taxable_amount;

                    //for gst table
                    $gst_percent = $variation && isset($variation[0]->tax) ? $variation[0]->tax : $item->tax; // e.g. 18
                    $gst_amount = round($ordr->tax_amount * $ordr->quantity, 1); // amount of GST

                    if (in_array($gst_percent, $gst_types)) {
                        $gst_summary[$gst_percent] += $gst_amount;
                    }
                @endphp


                <tr class="no-border">
                    <td class="no-border">{{ $key + 1 }}</td>
                    <td class="no-border" colspan="3">{!! $item->name !!}</td> <!-- name -->
                    <td class="no-border">{{ $item->hsn_code ?? '' }}</td><!-- hsn -->
                    <td class="no-border">{{ $ordr->quantity }}</td><!-- qty -->
                    <td class="no-border">{{ $item->mrp_price ?? $item->price }}</td><!-- mrp -->
                    <td class="no-border">{{ $ordr->discount_on_item }}</td><!-- discount -->
                    <td class="no-border">{{ $taxable_amount }}</td><!-- selling price -->
                    @if (!$composition_vendor)
                        <td class="no-border">{{ $taxable_amount * $ordr->quantity }}</td><!-- taxable -->
                        <!-- taxable amount -->
                        @if ($same_state)
                            <td class="no-border">{{ $cgst_percent }}%</td><!-- CGST percent -->
                            <td class="no-border">{{ round(($ordr->tax_amount * $ordr->quantity) / 2, 1) }}</td>
                            <!-- CGST amount -->
                            <td class="no-border">{{ $sgst_percent }}%</td> <!-- SGST percent -->
                            <td class="no-border">{{ round(($ordr->tax_amount * $ordr->quantity) / 2, 1) }}</td>
                            <!-- SGST amount -->
                        @else
                            <td class="no-border">{{ $igst_percent }}%</td> <!-- IGST percent -->
                            <td class="no-border">{{ round($ordr->tax_amount * $ordr->quantity, 1) }}</td>
                            <!-- IGST amount -->
                        @endif
                        <td class="no-border">{{ round($ordr->tax_amount * $ordr->quantity, 1) }}</td>
                        <!-- Tax total amount -->
                    @endif
                    <td class="no-border">
                        {{ $ordr->price * $ordr->quantity }}<!-- item total amount -->
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @php
        $calculatedTotal = $subTotalPrice + $order->delivery_charge;
    @endphp
    <table style="border: 1px dotted #999; border-collapse: collapse; width: 100%;  font-size: 10px; margin-top: 20px; "
        class="bottomo_sec">
        <tr>
            <td class="borderless_td" style="width: 20%; vertical-align: top; font-size:10px;">
                <b>INVOICE AMOUNT IN WORDS</b><br>
                {{ ucwords(_convertNumberToWords(_roundOff($calculatedTotal)['final_amount'])) }}
                <br>
                <br>
                @if (!$composition_vendor)

                    <b style="margin-top:10px">GST Summary</b>
                    <table style="width: 100%; border-collapse: collapse; font-size: 10px; " class="borderless_table">
                        <tr>
                            <td style="text-align: right;"></td>
                            @foreach ($gst_summary as $key => $value)
                                <td style="text-align: center;">{{ $key }}%</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td style="text-align: right;"><b>{{ $same_state ? 'CGST+SGST' : 'IGST' }}</b></td>
                            @foreach ($gst_summary as $key => $value)
                                <td style="text-align: center;">{{ $value > 0 ? $value : '-' }}</td>
                            @endforeach
                        </tr>
                    </table>
                @endif
            </td>
            <td class="borderless_td" style="width: 30%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; font-size: 10px; " class="">

                    <tr>
                        <td class="borderless_td" style="text-align: right;">Sub Total:</td>
                        <td class="borderless_td" style="text-align: right;">
                            {{ \App\CentralLogics\Helpers::currency_symbol() . number_format($subTotalPrice, 2) }}
                        </td>
                    </tr>

                    {{-- <tr>
                        <td class="borderless_td" style="text-align: right;">Discount Amount:</td>
                        <td class="borderless_td" style="text-align: right;">
                            {{ \App\CentralLogics\Helpers::currency_symbol() . number_format($total_discount, 2) }}
                        </td>
                    </tr> --}}
                    @if (!$composition_vendor)
                        <tr>
                            <td class="borderless_td" style="text-align: right;">Total Taxable Amount:</td>
                            <td class="borderless_td" style="text-align: right;">
                                @if ($totalTaxableAmount)
                                    {{ \App\CentralLogics\Helpers::currency_symbol() . number_format($totalTaxableAmount, 2) }}
                                @else
                                    {{ \App\CentralLogics\Helpers::currency_symbol() . number_format($subTotalPrice - $total_discount, 2) }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="borderless_td" style="text-align: right;">Total Tax Amount</td>
                            <td class="borderless_td" style="text-align: right;">
                                {{ \App\CentralLogics\Helpers::currency_symbol() . number_format($totalTaxAmount, 2) }}
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td class="borderless_td" style="text-align: right;">Delivery Charge:</td>
                        <td class="borderless_td" style="text-align: right;">
                            {{ number_format($order->delivery_charge, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="borderless_td" style="text-align: right;">Rounded Off:</td>
                        <td class="borderless_td" style="text-align: right;">
                            {{ _roundOff($calculatedTotal)['remaining_amount'] }}
                        </td>
                    </tr>
                    <tr>
                        <td class="borderless_td" style="text-align: right; font-size:12px;"><b>Grand Total:</b></td>
                        <td class="borderless_td" style="text-align: right; font-size:12px;">
                            <b>{{ \App\CentralLogics\Helpers::currency_symbol() . number_format(_roundOff($order->order_amount)['final_amount']) }}</b>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p class="section-title">BASIC TERMS & CONDITIONS</p>
    <ol style="font-size: 10px;">
        <li>Contract Duration: The contract duration is for one year or more, unless otherwise mutually agreed upon by
            the parties in writing under this agreement.</li>
        <li>Right to Terminate: My Chitti reserves the right to terminate the contract or any services at its
            discretion, with or without cause, by providing a thirty (30) day written
            notice to the vendor/service provider.</li>
        <li>No Guarantee of Business: My Chitti does not guarantee any specific business or sales leads to
            vendors/service providers listed on the platform. It merely acts as
            an intermediary connecting businesses and customers.</li>
        <li>Non-Refundable Payments: All payments made or due under this contract are non-refundable.</li>
        <li>Acceptance of Terms: By making a payment under this contract, the vendor/service provider agrees to the
            Terms of Use as outlined on the My Chitti platform.</li>
    </ol>

    @if (_termsAndConditionsUrl('vendor_to_user', $order->store_id, 'shop'))
        <a target="_blank" href="{{ _termsAndConditionsUrl('vendor_to_user', $order->store_id, 'shop') }}">Terms
            and
            Conditions</a>
    @endif


    <p style="text-align: center;">This is a computer-generated invoice. No signature required.</p>
    <table class="no-border" style="width: 100%; font-size: 12px;">
        <tr>
            <td align="left">
                <a href="https://www.mychitti.net">www.mychitti.net</a>
            </td>
            <td align="right">
                <a href="mailto:mychitti@mychitti.net">mychitti@mychitti.net</a>
            </td>
        </tr>
    </table>
    <p style="text-align: center;">SUBJECT TO TIRUPATI JURISDICTION.</p>
    <p style="text-align: center; font-size:10px;">
        {{ \App\Models\BusinessSetting::where(['key' => 'business_name'])->first()->value }}.</p>
</body>

</html>
