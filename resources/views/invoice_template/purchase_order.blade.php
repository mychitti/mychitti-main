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
    @if ($bill_data['tax_type'] != 'non-gst')
        <style>
            .border-bottom-none {
                border-bottom: none;
            }
        </style>
    @endif
</head>

<body>
    {{-- <div class="ribbon">
        <img width="100" class=""
            src="{{ asset('storage/app/public/util/') . '/' . $invoice->payment_status . '-ribbon.png' }}"
            alt="{{ $invoice->payment_status }}">
    </div> --}}

    @php
        $bill_gst_type = 'cgst_sgst';
        if ($bill_to) {
            if (
                isset($bill_from['state_code']) &&
                $bill_from['state_code'] &&
                isset($bill_to['state_code']) &&
                $bill_to['state_code'] &&
                $bill_from['state_code'] != $bill_to['state_code']
            ) {
                $bill_gst_type = 'igst';
            }
        }
        $gst_types = [0, 3, 5, 12, 18, 28];
        $gst_summary = array_fill_keys($gst_types, 0);

        $composition_vendor = false;
        $gst_vendor = true;
        if ($bill_from_type == 'vendor_to_user') {
            $vendor_id = $bill_from['id'];
            $store_det = _getUserDetails($vendor_id, $uType = 'store');
            if ($store_det) {
                if ($store_det->vendor_type == 'composition') {
                    $composition_vendor = true;
                }
                if ($store_det->gst && !json_decode($store_det->gst)->status) {
                    $gst_vendor = false;
                }
            }
        }
    @endphp
    <table class="no-border">
        <tr>
            {{-- <td style="width: 70%;">
                <h2>{{ $bill_from['name'] }}</h2><br>
                {!! $composition_vendor ? '<b>Composition Vendor</b> <br>' : '' !!}
                {{ $bill_from['address'] }}<br>
                @if ($bill_data['tax_type'] != 'non-gst')
                    GST NO:
                    {{ ($gst = json_decode($bill_from['gst'], true)) && isset($gst['code']) ? $gst['code'] : $bill_from['gst'] }}<br>
                    PAN NO:
                    {{ ($gst = json_decode($bill_from['gst'], true)) && isset($gst['code']) ? substr($gst['code'], 2, 10) : substr($bill_from['gst'], 2, 10) }}
                    <br>
                @endif

                {{ $bill_from['cin_number'] ? 'CIN No: ' . $bill_from['cin_number'] : '' }}
            </td> --}}
            <td style="width: 70%;">
                <h2>{{ $bill_to['name'] }}</h2><br>
                {{ $bill_to['address'] }}<br>
                @if ($bill_data['tax_type'] != 'non-gst')
                    PAN NO:
                    {{ ($gst = json_decode($bill_to['gst'], true)) && isset($gst['code']) ? substr($gst['code'], 2, 10) : substr($bill_to['gst'], 2, 10) }}
                    <br>
                    GST NO:
                    {{ ($gst = json_decode($bill_to['gst'], true)) && isset($gst['code']) ? $gst['code'] : $bill_to['gst'] }}<br>
                @endif

                {{ $bill_to['cin_number'] ? 'CIN No: ' . $bill_to['cin_number'] : '' }}
            </td>
            <td style="width: 30%; text-align: right;">
                @php  $store_logo = $bill_to['logo']; @endphp
                <img width="100" class=""
                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/store/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                    alt="Logo">

            </td>
        </tr>
    </table>


    <h3 class="text-center invoice-header">
        Purchase Order
    </h3>

    <table class="no-border">
        <tr>
            @if ($bill_to)
                <td style="width: 33.33%; vertical-align: top;">
                    <strong>Vendor / Supplier:</strong><br><br>
                    <h4> {{ $bill_from['name'] }}</h4>
                    {{ $bill_from['address'] }}<br>
                    {!! $bill_from['email'] ? 'Email: ' . $bill_from['email'] . '<br>' : '' !!}
                    Ph NO: {{ $bill_from['phone'] }}<br>
                    @if ($bill_data['tax_type'] != 'non-gst')
                        @php
                            $gst = $bill_from['gst'];

                            $decoded = json_decode($gst, true);

                            if ($decoded && isset($decoded['code'])) {
                                $gst = $decoded['code'];
                            }
                        @endphp
                        {!! $bill_from['gst'] ? 'GST NO : ' . $gst . '<br />' : '' !!}
                    @endif
                    @if ($invoice->reference)
                        <strong>Reference: {{ $invoice->reference }}</strong>
                    @endif
                </td>
                <td style="width: 33.33%; vertical-align: top;">
                    <strong>Ship to :</strong><br><br>
                    <h4> {{ $bill_to['name'] }}</h4>
                    {{ $bill_to['address'] }}<br>
                    {!! $shipping_address->email ? 'Email: ' . $shipping_address->email . '<br>' : '' !!}
                    Ph NO: {{ $shipping_address->contact_person_number }}<br>
                    @if ($bill_data['tax_type'] != 'non-gst')
                        GST NO:
                        {{ ($gst = json_decode($bill_to['gst'], true)) && isset($gst['code']) ? $gst['code'] : $bill_to['gst'] }}<br>
                    @endif
                    <br>
                </td>
            @elseif($invoice->customer_name)
                <td style="width: 33.33%; vertical-align: top;">
                    {{ $invoice->customer_name }}
                </td>
            @endif
            <td style="width: 33.33%; vertical-align: top; text-align: right; line-height: 1.6;">
                <span><strong>PO Number:</strong> {{ $bill_data['invoice_number'] }}</span><br>
                <span><strong>Purchase Date:</strong> {{ $invoice->invoice_date ?? date('Y-m-d') }}</span><br>
                @if ($invoice->payment_date)
                    <span><strong>Payment Date:</strong> {{ $invoice->payment_date }}</span><br>
                @endif
                @if ($bill_data['tax_type'] == 'gst')
                    @if ($bill_to)
                        <span><strong>Place of Supply:</strong>
                            @if ($bill_to['state_code'])
                                {{ $bill_to['state_code']['abbr'] . '-' . $bill_to['state_code']['code'] }}
                        </span><br>
                    @else
                        {{ $bill_from['state_code']['abbr'] . '-' . $bill_from['state_code']['code'] }}</span><br>
                    @endif
                @endif
                @endif
                @if ($invoice->custom_headers)
                    @foreach (json_decode($invoice->custom_headers) as $key => $value)
                        <span><strong>{{ $key }}:</strong> {{ $value }}</span><br>
                    @endforeach
                @endif
                @if (isset($invoice->order_type) && $invoice->order_type)
                    <span><strong>Order Type:</strong> {{ $invoice->order_type }}</span><br>
                @endif
                @if (isset($invoice->token_number) && $invoice->token_number)
                    <span><strong>Token Number:</strong> {{ $invoice->token_number }}</span><br>
                @endif
            </td>
        </tr>
    </table>

    <table style="margin-bottom:100px; font-size:10px;">
        <thead>
            <tr class="center">
                <th class="border-bottom-none">Sl</th>
                <th class="border-bottom-none" colspan="3">ITEM DESCRIPTION</th>
                @if ($bill_data['tax_type'] != 'non-gst')
                    <th class="border-bottom-none">HSN/SAC</th>
                @endif
                <th class="border-bottom-none">QTY</th>
                <th class="border-bottom-none">MRP</th>
                <th class="border-bottom-none">DISC</th>
                <th class="border-bottom-none">PRICE</th>
                @if ($bill_data['tax_type'] != 'non-gst' && !$composition_vendor)
                    <th class="border-bottom-none">TAXABLE</th>
                    @if ($bill_gst_type == 'igst')
                        <th class="border-bottom-none" colspan="2">IGST</th>
                    @else
                        <th class="border-bottom-none" colspan="2">CGST</th>
                        <th class="border-bottom-none" colspan="2">SGST</th>
                    @endif
                    <th class="border-bottom-none" style="font-size:9px;width: 40px;">TAX Total</th>
                @endif
                <th class="border-bottom-none">TOTAL</th>
            </tr>
            @if ($bill_data['tax_type'] != 'non-gst')

                <tr>
                    <th class="border-top-none"></th>
                    <th class="border-top-none" colspan="3"></th>
                    <th class="border-top-none"></th>
                    <th class="border-top-none"></th>
                    <th class="border-top-none"></th>
                    <th class="border-top-none"></th>
                    <th class="border-top-none"></th>
                    <th class="border-top-none"></th>
                    @if ($bill_data['tax_type'] != 'non-gst')
                        <th>RATE</th>
                        <th>AMT</th>
                        <th>RATE</th>
                        <th>AMT</th>
                        @if ($bill_gst_type == 'cgst_sgst')
                            <th class="border-top-none"></th>
                            <th class="border-top-none"></th>
                        @endif
                    @endif
                </tr>
            @endif

        </thead>
        <tbody>
            @foreach ($bill_data['invoice_items'] as $key => $qt)
                @php

                    $gst_percent = (int) $qt->tax; // e.g. 18
                    $gst_amount = _taxPrice($qt->price * $qt->qty, $qt->tax, 'actual'); // amount of GST

                    if (in_array($gst_percent, $gst_types)) {
                        $gst_summary[$gst_percent] += $gst_amount;
                    }
                @endphp
                <tr class="no-border">
                    <td class="no-border">{{ $key + 1 }}</td>
                    <td class="no-border" colspan="3">{!! $qt->item_name !!} </td>
                    @if ($bill_data['tax_type'] != 'non-gst')
                        <td class="no-border">{{ $qt->hsn }}</td>
                    @endif
                    <td class="no-border">{{ $qt->qty }} {{ $qt->unitId?->unit }}</td>
                    <td class="no-border">{{ $qt->unit_price }}</td>
                    <td class="no-border">0</td>
                    <td class="no-border">{{ $qt->unit_price }}</td>
                    @if ($bill_data['tax_type'] != 'non-gst' && !$composition_vendor)
                        <td class="no-border">{{ $qt->unit_price * $qt->qty }}</td>
                        @if ($bill_gst_type == 'cgst_sgst')
                            <td class="no-border">{{ $qt->tax_rate / 2 }}%</td><!-- CGST percent -->
                            <td class="no-border">{{ $qt->tax_amount / 2 }}</td>
                            <!-- CGST amount -->
                            <td class="no-border">{{ $qt->tax_rate / 2 }}%</td> <!-- SGST percent -->
                            <td class="no-border">{{ $qt->tax_amount / 2 }}</td>
                            <!-- SGST amount -->
                        @else
                            <td class="no-border">{{ $qt->tax_rate }}%</td> <!-- SGST percent -->
                            <td class="no-border">{{ $qt->tax_amount }} </td>
                            <!-- SGST amount -->
                        @endif
                        <td class="no-border">{{ $qt->tax_amount }}</td>
                    @endif
                    <td class="no-border">
                        {{ $qt->unit_price * $qt->qty + $qt->tax_amount }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table style="border: 1px dotted #999; border-collapse: collapse; width: 100%;  font-size: 10px; margin-top: 20px; "
        class="bottomo_sec">
        @php
            $totalAmt = round($bill_data['total_amount'],3); // keep two decimals as float
            $totalAmountRO = round($bill_data['total_amount']); // round to nearest integer
            $roundOff = number_format($totalAmt - $totalAmountRO, 3); // proper round off difference
        @endphp
        <tr>
            <td class="borderless_td" style="width: 20%; vertical-align: top; font-size:10px;">
                <b>INVOICE AMOUNT IN WORDS</b><br>
                {{ ucwords(_convertNumberToWords($totalAmountRO)) }}
                <br>
                <br>
                @if ($bill_data['tax_type'] != 'non-gst' && !$composition_vendor)

                    <b style="margin-top:10px">GST Summary</b>
                    <table style="width: 100%; border-collapse: collapse; font-size: 10px; " class="borderless_table">
                        <tr>
                            <td style="text-align: right;"></td>
                            @foreach ($gst_summary as $key => $value)
                                <td style="text-align: center; padding:0px;">{{ $key }}%</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td style="text-align: right;">
                                <b>{{ $bill_gst_type == 'cgst_sgst' ? 'CGST+SGST' : 'IGST' }}</b>
                            </td>
                            @foreach ($gst_summary as $key => $value)
                                <td style="text-align: center;">{{ $value > 0 ? $value : '-' }}</td>
                            @endforeach
                        </tr>

                    </table>
                @endif

                @if (isset($invoice->bankAccount) && $invoice->bankAccount && $invoice->bankAccount?->account_number)
                    <b style="margin-top:10px">Bank Details</b>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            @if ($invoice->bankAccount?->upi_qr_code)
                                <td style="width: 60px;">
                                    <img src="{{ asset('storage/store/documents/') . '/' . $invoice->bankAccount?->upi_qr_code }}"
                                        alt="QR Code" style="width: 70px; height: 70px;">
                                </td>
                            @endif
                            <td style="padding-left: 10px; font-size: 14px;">
                                <div style="font-size:12px; margin:5px  0; ">Bank Name:
                                    {{ $invoice->bankAccount?->bank_name }}</div>
                                <div style="font-size:12px; margin:5px  0; ">Account Holder Name:
                                    {{ $invoice->bankAccount?->account_holder_name }}</div>
                                <div style="font-size:12px; margin:5px  0; ">Bank Account No.:
                                    {{ $invoice->bankAccount?->account_number }}</div>
                                <div style="font-size:12px; margin:5px  0; ">Bank IFSC Code:
                                    {{ $invoice->bankAccount?->ifsc_code }}</div>
                            </td>
                        </tr>

                    </table>
                @endif

                @if ($invoice->advance_amount)
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td class="borderless_td" style="text-align: left; font-size:12px; padding: 10px 0;">
                                <span
                                    style="background: #dcf6dc;
    padding: 8px;
    border: 1px solid #54e954;
    border-radius: 5px;">
                                    Payable Amount:
                                    {{ \App\CentralLogics\Helpers::currency_symbol() . number_format($invoice->total_amount - $invoice->advance_amount, 3) }}
                                </span>
                            </td>

                        </tr>

                    </table>
                @endif


            </td>
            <td class="borderless_td" style="width: 30%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; font-size: 10px; " class="">

                    <tr>
                        <td class="borderless_td" style="text-align: right;">Sub Total:</td>
                        <td class="borderless_td" style="text-align: right;">
                            {{ \App\CentralLogics\Helpers::currency_symbol() . number_format($invoice->subtotal_amount, 3) }}
                        </td>
                    </tr>

                    @if ($bill_data['tax_type'] != 'non-gst' && !$composition_vendor)
                        <tr>
                            <td class="borderless_td" style="text-align: right;">Total Taxable Amount:</td>
                            <td class="borderless_td" style="text-align: right;">
                                {{ \App\CentralLogics\Helpers::currency_symbol() . number_format($invoice->subtotal_amount, 3) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="borderless_td" style="text-align: right;">Total Tax Amount</td>
                            <td class="borderless_td" style="text-align: right;">
                                {{ \App\CentralLogics\Helpers::currency_symbol() . number_format($invoice->tax_amount, 3) }}
                            </td>
                        </tr>
                    @endif



                    <tr>
                        <td class="borderless_td" style="text-align: right;">Rounded Off:</td>
                        <td class="borderless_td" style="text-align: right;">
                            {{ $roundOff }}
                        </td>
                    </tr>

                    <tr>
                        <td class="borderless_td" style="text-align: right; font-size:12px;"><b>Grand Total:</b></td>
                        <td class="borderless_td" style="text-align: right; font-size:12px;">
                            <b>{{ \App\CentralLogics\Helpers::currency_symbol() . number_format($totalAmountRO,3) }}</b>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>



    <h4>Terms and Conditions</h4>
    {!! _poTNC() !!}



    @if ((isset($bill_data['store']) && $bill_data['store']->invoice_footer_line) || $bill_from_type == 'admin_to_user')
        <p style="text-align: center;">This is a computer-generated invoice. No signature required.</p>
    @endif

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

    @if (isset($bill_data['store']))
        @if ($bill_data['store']->storeConfig?->jurisdiction_statement_status)
            <p style="text-align: center;">{{ $bill_data['store']->jurisdiction_statement }}</p>
        @endif
    @else
        <p style="text-align: center;">SUBJECT TO TIRUPATI JURISDICTION.</p>
    @endif
    <p style="text-align: center; font-size:10px;">
        {{ \App\Models\BusinessSetting::where(['key' => 'business_name'])->first()->value }}.</p>

</body>

</html>
