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

    <div class="ribbon">
        @if ($bill_data['template_type'] == 'quotation')
            <img width="100" class=""
                src="{{ asset('storage/util/') . '/' . 'quotation-ribbon2.png' }}" alt="Quotation">
        @else
            <img width="100" class=""
                src="{{ asset('storage/util/') . '/' . $invoice->payment_status . '-ribbon.png' }}"
                alt="{{ $invoice->payment_status }}">
        @endif
    </div>

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
            <td style="width: 70%;">
                <strong>Sold By: {{ $bill_from['name'] }}</strong><br>
                {!! $composition_vendor ? '<b>Composition Vendor</b> <br>' : '' !!}
                @if (!empty($bill_data['branch_label'])){{ $bill_data['branch_label'] }}<br>@endif
                {{ $bill_from['address'] }}<br>
                @if ($bill_data['tax_type'] != 'non-gst')
                    GST NO:
                    {{ ($gst = json_decode($bill_from['gst'], true)) && isset($gst['code']) ? $gst['code'] : $bill_from['gst'] }}<br>
                    PAN NO:
                    {{ ($gst = json_decode($bill_from['gst'], true)) && isset($gst['code']) ? substr($gst['code'], 2, 10) : substr($bill_from['gst'], 2, 10) }}
                    <br>
                @endif
                @php
                    $fssaiLine = null;
                    $otherDocLines = [];
                    try {
                        if (!empty($bill_from['id'])) {
                            $sellerStore = \App\Models\Store::find($bill_from['id']);
                            if ($sellerStore && $sellerStore->fssai_show && $sellerStore->fssai_number) {
                                $fssaiLine = $sellerStore->fssai_number;
                            }
                            $otherDocLines = \App\Models\StoreDocument::where('store_id', $bill_from['id'])
                                ->where('doc_type', 'other')->where('status', 1)->where('show_on_bill', 1)
                                ->whereNotNull('doc_number')->where('doc_number', '!=', '')->get();
                        }
                    } catch (\Throwable $e) {
                        $fssaiLine = null;
                        $otherDocLines = [];
                    }
                @endphp
                @if ($fssaiLine)
                    FSSAI: {{ $fssaiLine }}<br>
                @endif
                @foreach ($otherDocLines as $od)
                    {{ $od->doc_name }}: {{ $od->doc_number }}<br>
                @endforeach

                {{ $bill_from['cin_number'] ? 'CIN No: ' . $bill_from['cin_number'] : '' }}
            </td>
            <td style="width: 30%; text-align: right;">
                {{-- Whose document this is, not who it is addressed to. Testing for
                     vendor_to_user alone sent every other kind of vendor bill -- a hospital bill
                     to a patient is vendor_to_patient -- down the admin branch, and printed
                     MyChitti's logo on a hospital's own bill. --}}
                @if (isset($bill_from_type) && \Illuminate\Support\Str::startsWith($bill_from_type, 'vendor'))
                    @php  $store_logo = $bill_from['logo']; @endphp
                    <img width="100" class=""
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/store/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                        alt="Logo">
                @else
                    @php  $store_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value; @endphp
                    <img width="100" class=""
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/business/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'business/') }}"
                        alt="Logo">
                @endif
            </td>
        </tr>
    </table>

    @if ($bill_data['template_type'] == 'quotation')
        <h3 class="text-center invoice-header">Quotation</h3>
    @else
        <h3 class="text-center invoice-header">
            {{ $bill_data['heading'] ? $bill_data['heading'] : ($bill_data['tax_type'] == 'gst' ? 'Tax Invoice' : 'Invoice') }}
        </h3>
    @endif
    <table class="no-border">
        <tr>
            @if ($bill_to)
                <td style="width: 33.33%; vertical-align: top;">
                    <strong>Billing Address:</strong><br>
                    {{ $bill_to['full_name'] }}<br>
                    {{ $bill_to['address'] }}<br>
                    {!! $bill_to['email'] ? 'Email: ' . $bill_to['email'] . '<br>' : '' !!}
                    Ph NO: {{ $bill_to['phone'] }}<br>
                    @if ($bill_data['tax_type'] != 'non-gst')
                        {!! $bill_to['gst'] ? 'GST NO : ' . $bill_to['gst'] . '<br />' : '' !!}
                    @endif
                    @if ($invoice->reference)
                        <strong>Reference: {{ $invoice->reference }}</strong>
                    @endif
                </td>
                @if (isset($shipping_address->address) && ($shipping_address && $bill_to['address'] != $shipping_address->address))
                    <td style="width: 33.33%; vertical-align: top;">
                        <strong>Shipping Address:</strong><br>
                        {{ $shipping_address->contact_person_name }}<br>
                        {{ $shipping_address->address }}<br>
                        {!! $shipping_address->email ? 'Email: ' . $shipping_address->email . '<br>' : '' !!}
                        Ph NO: {{ $shipping_address->contact_person_number }}<br>
                    </td>
                @endif
            @elseif($invoice->customer_name)
                <td style="width: 33.33%; vertical-align: top;">
                    {{ $invoice->customer_name }}
                </td>
            @endif
            <td style="width: 33.33%; vertical-align: top; text-align: right; line-height: 1.6;">
                @if ($bill_data['task_id'])
                    <span><strong>Task Id:</strong> {{ $bill_data['task_id'] }}</span><br>
                @else
                @endif
                @if ($bill_data['template_type'] == 'quotation')
                    <span><strong>Quotation No:</strong> {{ $bill_data['quotation_id'] }}</span><br>
                    <span><strong>Quotation Date:</strong> {{ $invoice->invoice_date ?? date('Y-m-d') }}</span><br>
                @else
                    <span><strong>Invoice No:</strong> {{ $bill_data['invoice_number'] }}</span><br>
                    <span><strong>Invoice Date:</strong> {{ $invoice->invoice_date ?? date('Y-m-d') }}</span><br>
                @endif
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
                @if ($bill_data['tax_type'] != 'non-gst' || $bill_data['module_id'] == 5)
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
            @if ($bill_data['tax_type'] != 'non-gst' && !$composition_vendor)

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
            @php
                $totalPrice = 0;
                $subTotalPrice = 0;
            $totalTaxAmount = 0; @endphp
            @foreach ($bill_data['invoice_items'] as $key => $qt)
                @php
                    $qt_line = $qt->price * $qt->qty;
                    if (($qt->gst_status ?? 'excluding') === 'including') {
                        $qt_taxable = $qt->tax > 0 ? $qt_line / (1 + $qt->tax / 100) : $qt_line;
                        $gst_amount = $qt_line - $qt_taxable;
                        $qt_total   = $qt_line;
                    } else {
                        $qt_taxable = $qt_line;
                        $gst_amount = _taxPrice($qt_line, $qt->tax, 'actual');
                        $qt_total   = $qt_line + $gst_amount;
                    }

                    $totalPrice     += $qt_total;
                    $subTotalPrice  += $qt_taxable;
                    $totalTaxAmount += $gst_amount;

                    $gst_percent = (int) $qt->tax;
                    if (in_array($gst_percent, $gst_types)) {
                        $gst_summary[$gst_percent] += $gst_amount;
                    }

                    // Loose (weighed) lines show pieces + weight, e.g. "4 pc (0.9 kg)".
                    if (!empty(optional($qt->item)->sell_loose) || !empty($qt->pieces)) {
                        $unitLbl = optional(optional($qt->item)->itemunit)->unit;
                        $qtyDisplay = rtrim(rtrim(number_format((float) $qt->qty, 3), '0'), '.') . ($unitLbl ? ' ' . $unitLbl : '');
                        if (!empty($qt->pieces)) {
                            $qtyDisplay = (int) $qt->pieces . ' pc (' . $qtyDisplay . ')';
                        }
                    } else {
                        $qtyDisplay = trim(_num($qt->qty, 3, false) . ' ' . ($qt->unitId?->unit ?? ''));
                    }
                @endphp
                <tr class="no-border">
                    <td class="no-border">{{ $key + 1 }}</td>
                    <td class="no-border" colspan="3">
                        {!! $qt->name !!}
                        @php
                            $lineDesc = optional($qt->item)->description;
                            $lineAttrs = optional($qt->item)->description_attributes ?: [];
                        @endphp
                        @if (!empty($lineDesc) || !empty($lineAttrs))
                            <div style="font-size:9px; color:#555; margin-top:3px; line-height:1.35;">
                                @if (!empty($lineDesc)){!! nl2br(e($lineDesc)) !!}@endif
                                @foreach ($lineAttrs as $al => $av)
                                    <div><span style="color:#888;">{{ $al }}:</span> {{ $av }}</div>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    @if ($bill_data['tax_type'] != 'non-gst' || $bill_data['module_id'] == 5)
                        <td class="no-border">{{ $qt->hsn }}</td>
                    @endif
                    <td class="no-border">{{ $qtyDisplay }}</td>
                    <td class="no-border">{{ _num($qt->price, 3, false) }}</td>
                    <td class="no-border">0</td>
                    <td class="no-border">{{ _num($qt->price, 3, false) }}</td>
                    @if ($bill_data['tax_type'] != 'non-gst' && !$composition_vendor)
                        <td class="no-border">{{ round($qt_taxable, 3) }}</td>
                        @if ($bill_gst_type == 'cgst_sgst')
                            <td class="no-border">{{ ($qt->cgst_rate ?? $qt->tax / 2) }}%</td>
                            <td class="no-border">{{ $qt->cgst_amount ?? round($gst_amount / 2, 3) }}</td>
                            <td class="no-border">{{ $qt->sgst_rate ?? $qt->tax / 2 }}%</td>
                            <td class="no-border">{{ $qt->sgst_amount ?? round($gst_amount / 2, 3) }}</td>
                        @else
                            <td class="no-border">{{ $qt->igst_rate ?? $qt->tax }}%</td>
                            <td class="no-border">{{ $qt->igst_amount ?? round($gst_amount, 3) }}</td>
                        @endif
                        <td class="no-border">{{ round($gst_amount, 3) }}</td>
                    @endif
                    <td class="no-border">
                        @if($qt->total)
                            {{ round($qt->total) }}
                        @else
                            {{ round($qt_total, 3) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table style="border: 1px dotted #999; border-collapse: collapse; width: 100%;  font-size: 10px; margin-top: 20px; "
        class="bottomo_sec">
        <tr>
            <td class="borderless_td" style="width: 20%; vertical-align: top; font-size:10px;">
                <b>INVOICE AMOUNT IN WORDS</b><br>
                {{ ucwords(_convertNumberToWords($bill_data['total_amount'])) }}
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
                @if ($invoice->payment_method == 'Cash and Online')
                    <table>
                    <tr>
                            <th>Cash Amount</th>
                            <td>{{ _price($invoice->cash_amount) }}</td>
                        </tr>
                        <tr>
                            <th>Online Amount</th>
                            <td>{{ _price($invoice->online_amount) }}</td>
                        </tr>
                    </table>
                @endif

                @if (isset($invoice->bankAccount) && $invoice->bankAccount && $invoice->bankAccount?->account_number)
                    <b style="margin-top:10px">Bank Details</b>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            @if ($invoice->bankAccount?->upi_qr_code)
                                <td style="width: 60px;">
                                    <img src="{{ asset('storage/app/public/store/documents/') . '/' . $invoice->bankAccount?->upi_qr_code }}"
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
                                    {{ \App\CentralLogics\Helpers::currency_symbol() . _num($invoice->total_amount - $invoice->advance_amount, 3) }}
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
                     
                            {{ \App\CentralLogics\Helpers::currency_symbol() . _num(($invoice->subtotal_amount ?? $subTotalPrice), 3) }}
                        </td>
                    </tr>
                    @if ($invoice->additional_charges)
                        @foreach (json_decode($invoice->additional_charges) as $key => $value)
                            @php
                                $rowtax = _taxPrice($value->amount, $value->tax, 'actual');
                                $totalTaxAmount += $rowtax;
                            $totalPrice += $value->amount + $rowtax; @endphp
                            <tr>
                                <td class="borderless_td" style="text-align: right;">{{ formatLabel($value->name) }}:
                                </td>
                                <td class="borderless_td" style="text-align: right;">
                                    {{ \App\CentralLogics\Helpers::currency_symbol() . _num($value->calc_amount, 3) }}
                                </td>
                            </tr>
                        @endforeach

                    @endif
                    @if ($bill_data['tax_type'] != 'non-gst' && !$composition_vendor)
                        <tr>
                            <td class="borderless_td" style="text-align: right;">Total Taxable Amount:</td>
                            <td class="borderless_td" style="text-align: right;">
                                {{ \App\CentralLogics\Helpers::currency_symbol() . _num(($invoice->subtotal_amount  ?? $totalPrice - $totalTaxAmount), 3) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="borderless_td" style="text-align: right;">Total Tax Amount</td>
                            <td class="borderless_td" style="text-align: right;">
                                {{ \App\CentralLogics\Helpers::currency_symbol() . _num(($invoice->final_tax ?? $totalTaxAmount), 3) }}
                            </td>
                        </tr>
                    @endif
                    @if ($invoice->coupon_amount > 0)
                        <tr>
                            <td class="borderless_td" style="text-align: right;">Coupon:</td>
                            <td class="borderless_td" style="text-align: right;">
                                {{ \App\CentralLogics\Helpers::currency_symbol() . _num($invoice->coupon_amount, 3) }}
                            </td>
                        </tr>
                    @endif
                    @if ($invoice->discount_amount > 0)
                        <tr>
                            <td class="borderless_td" style="text-align: right;">Discount:</td>
                            <td class="borderless_td" style="text-align: right;">
                                {{ \App\CentralLogics\Helpers::currency_symbol() . _num($invoice->discount_amount, 3) }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="borderless_td" style="text-align: right;">Rounded Off:</td>
                        <td class="borderless_td" style="text-align: right;">
                          {{$invoice->round_off ?? 0}}  
                        </td>
                    </tr>

                    <tr>
                        <td class="borderless_td" style="text-align: right; font-size:12px;"><b>Grand Total:</b></td>
                        <td class="borderless_td" style="text-align: right; font-size:12px;">
                            <b>{{ \App\CentralLogics\Helpers::currency_symbol() . _num($bill_data['total_amount'], 3) }}</b>
                        </td>
                    </tr>
                    @if (!empty($invoice->payment_method))
                        <tr>
                            <td class="borderless_td" style="text-align: right; font-size:10px;">Payment Method:</td>
                            <td class="borderless_td" style="text-align: right; font-size:10px;">
                                {{ $invoice->payment_method }}
                            </td>
                        </tr>
                        @if (!empty($bill_data['payment_legs']) && count($bill_data['payment_legs']))
                            @foreach ($bill_data['payment_legs'] as $leg)
                                <tr>
                                    <td class="borderless_td" style="text-align: right; font-size:10px;">
                                        {{ ucfirst($leg->mode) }}{{ $leg->sub_type ? ' · ' . strtoupper($leg->sub_type) : '' }}
                                        @if (!empty($leg->reference) || !empty($leg->approval_code))
                                            <span style="color:#888;">(Txn: {{ $leg->reference ?: $leg->approval_code }})</span>
                                        @endif
                                        :
                                    </td>
                                    <td class="borderless_td" style="text-align: right; font-size:10px;">
                                        {{ \App\CentralLogics\Helpers::currency_symbol() . _num($leg->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        @if (!empty($bill_data['tendered']) && $bill_data['tendered'] > 0)
                            <tr>
                                <td class="borderless_td" style="text-align: right; font-size:10px;">Tendered:</td>
                                <td class="borderless_td" style="text-align: right; font-size:10px;">
                                    {{ \App\CentralLogics\Helpers::currency_symbol() . _num($bill_data['tendered']) }}
                                </td>
                            </tr>
                            @if (!empty($bill_data['change_return']) && $bill_data['change_return'] > 0)
                                <tr>
                                    <td class="borderless_td" style="text-align: right; font-size:10px;">Change Returned:</td>
                                    <td class="borderless_td" style="text-align: right; font-size:10px;">
                                        {{ \App\CentralLogics\Helpers::currency_symbol() . _num($bill_data['change_return']) }}
                                    </td>
                                </tr>
                            @elseif (!empty($bill_data['balance_due']) && $bill_data['balance_due'] > 0)
                                <tr>
                                    <td class="borderless_td" style="text-align: right; font-size:10px;">Balance Due:</td>
                                    <td class="borderless_td" style="text-align: right; font-size:10px;">
                                        {{ \App\CentralLogics\Helpers::currency_symbol() . _num($bill_data['balance_due']) }}
                                    </td>
                                </tr>
                            @endif
                        @endif
                    @endif
                    @if ($invoice->advance_amount)
                        <tr>
                            <td class="borderless_td"></td>
                            <td class="borderless_td" style="text-align: right; font-size:12px; padding-top: 30px;">
                                <b>Advance Payment: </b> {{ $invoice->advance_amount }} <br>
                                <b>Payable Amount: </b> {{ $invoice->total_amount - $invoice->advance_amount }}
                            </td>
                        </tr>
                    @endif

                    @if (!empty($bill_data['total_saved']) && $bill_data['total_saved'] > 0)
                        <tr>
                            <td colspan="2" class="borderless_td" style="text-align:right; padding-top:6px;">
                                <div style="display:inline-block;background:#e6f7ec;color:#1b7a43;border:1px dashed #5dba5d;border-radius:5px;font-weight:bold;font-size:11px;padding:3px 8px;">
                                    ★ You Saved {{ \App\CentralLogics\Helpers::currency_symbol() . _num($bill_data['total_saved']) }} on MRP ★
                                </div>
                            </td>
                        </tr>
                    @endif

                    @if (isset($bill_from_type))

                        <tr>
                            <td class="borderless_td"></td>
                            <td class="borderless_td" style="text-align: right;">
                                <div>
                                    <div style="height:100px !important;">&nbsp; </div>
                                    <div style="height:100px !important;">&nbsp; </div>
                                    @if (isset($bill_from_type) && \Illuminate\Support\Str::startsWith($bill_from_type, 'vendor') && (isset($invoice->sign) && $invoice->sign))
                                        <div><b>For {{ $bill_data['store']->name }}</b></div><br>

                                        <img src="{{ asset('storage/store/signature/') . '/' . _signImgById($invoice->sign) }}"
                                            width="110px">
                                    @elseif(!isset($bill_from_type) || !\Illuminate\Support\Str::startsWith($bill_from_type, 'vendor'))
                                        @if(isset($invoice->sign) && $invoice->sign)
                                            <div><b>For {{ \App\Models\BusinessSetting::where(['key' => 'business_name'])->first()->value ?? '' }}</b></div><br>
                                            <img src="{{ asset('storage/app/public/store/signature/') . '/' . _signImgById($invoice->sign) }}"
                                                width="110px">
                                        @else
                                            @php($sign = \App\Models\BusinessSetting::where('key', 'admin_signature')->first())
                                            @php($sign = $sign->value ?? '')
                                            <img src="{{ asset('storage/business/') . '/' . $sign }}"
                                                width="110px">
                                        @endif
                                    @endif 
                                </div>
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>
 


    @if (!empty($bill_data['receipts']) && count($bill_data['receipts']))
        @php($cs = \App\CentralLogics\Helpers::currency_symbol())
        @php($hasRef = collect($bill_data['receipts'])->contains(fn($r) => !empty($r->reference)))
        @php($totalCols = $hasRef ? 6 : 5)
        <table style="width:100%;border-collapse:collapse;margin-top:10px;font-size:10px;border:1px solid #cccccc;">
            <tr>
                <td colspan="{{ $totalCols }}" style="background:#eeeeee;padding:4px 6px;font-weight:bold;">PAYMENTS RECEIVED</td>
            </tr>
            <tr style="background:#f7f7f7;">
                <th style="border:1px solid #cccccc;padding:4px 6px;text-align:left;">Receipt No</th>
                <th style="border:1px solid #cccccc;padding:4px 6px;text-align:left;">Date</th>
                <th style="border:1px solid #cccccc;padding:4px 6px;text-align:left;">Mode</th>
                @if ($hasRef)
                    <th style="border:1px solid #cccccc;padding:4px 6px;text-align:left;">Ref / Txn ID</th>
                @endif
                <th style="border:1px solid #cccccc;padding:4px 6px;text-align:right;">Amount</th>
                <th style="border:1px solid #cccccc;padding:4px 6px;text-align:right;">Balance</th>
            </tr>
            @foreach ($bill_data['receipts'] as $receipt)
                @php($receiptUrl = \App\Services\InvoicePayments::receiptUrl($receipt->pdf ?? null))
                <tr>
                    <td style="border:1px solid #cccccc;padding:4px 6px;">
                        @if ($receiptUrl)
                            <a href="{{ $receiptUrl }}" style="color:#1a73e8;text-decoration:underline;">{{ $receipt->receipt_no }}</a>
                        @else
                            {{ $receipt->receipt_no }}
                        @endif
                    </td>
                    <td style="border:1px solid #cccccc;padding:4px 6px;">
                        {{ \Carbon\Carbon::parse($receipt->payment_date ?? $receipt->created_at)->format('d M Y') }}
                    </td>
                    <td style="border:1px solid #cccccc;padding:4px 6px;">{{ $receipt->payment_mode }}</td>
                    @if ($hasRef)
                        <td style="border:1px solid #cccccc;padding:4px 6px;font-size:9px;">{{ $receipt->reference ?: '—' }}</td>
                    @endif
                    <td style="border:1px solid #cccccc;padding:4px 6px;text-align:right;">
                        {{ $cs . _num($receipt->amount, 2) }}</td>
                    <td style="border:1px solid #cccccc;padding:4px 6px;text-align:right;">
                        {{ $cs . _num($receipt->balance_after, 2) }}</td>
                </tr>
            @endforeach
            <tr style="background:#f7f7f7;font-weight:bold;">
                <td colspan="{{ $totalCols - 2 }}" style="border:1px solid #cccccc;padding:4px 6px;text-align:right;">Total Paid</td>
                <td style="border:1px solid #cccccc;padding:4px 6px;text-align:right;">
                    {{ $cs . _num($bill_data['paid_total'], 2) }}</td>
                <td style="border:1px solid #cccccc;padding:4px 6px;text-align:right;">
                    {{ $bill_data['amount_due'] > 0 ? 'Due ' . $cs . _num($bill_data['amount_due'], 2) : 'Paid in full' }}
                </td>
            </tr>
        </table>
    @endif

    <?php
        $isQuotation = ($bill_data['template_type'] ?? '') === 'quotation';
        $storeConfig = isset($bill_data['store']) ? $bill_data['store']->storeConfig : null;
        $fromVendor  = isset($bill_from_type) && $bill_from_type === 'vendor_to_user';
        $fromAdmin   = isset($bill_from_type) && in_array($bill_from_type, ['admin_to_user', 'admin_to_vendor']);
        $isShop      = ($bill_data['vendor_typ'] ?? '') === 'shop';
    ?>

    @if ($isQuotation)
        {{-- Quotation: show TnC only if store has it enabled and content is set --}}
        @if ($storeConfig?->tnc_quotation_status && !empty($bill_data['quote_tnc']))
            <h4>Terms and Conditions</h4>
            {!! $bill_data['quote_tnc'] !!}
        @endif

    @elseif ($invoice->terms_and_conditions)
        {{-- Invoice-specific TnC (set at invoice creation) takes priority --}}
        <h4>Terms and Conditions</h4>
        {!! $invoice->terms_and_conditions !!}

    @elseif ($fromVendor && $storeConfig?->tnc_invoice_status)
        {{-- Vendor invoice: shop type shows link, service type shows inline text --}}
        @if ($isShop)
            <?php $tncUrl = _termsAndConditionsUrl($bill_from_type, $bill_from['id'], $bill_data['vendor_typ']); ?>
            @if ($tncUrl)
                <a target="_blank" href="{{ $tncUrl }}">Terms and Conditions</a>
            @endif
        @else
            <?php $tncContent = _vendorTandC($bill_from['id']); ?>
            @if ($tncContent)
                <h4>Terms and Conditions</h4>
                {!! $tncContent !!}
            @endif
        @endif

    @elseif ($fromAdmin)
        {{-- Admin invoice / subscription billing: show TnC configured in Invoice Settings --}}
        <?php $adminTnc = _adminInvoiceTnC(); ?>
        @if ($adminTnc)
            <h4>Terms and Conditions</h4>
            {!! $adminTnc->content !!}
        @else
            {{-- Fallback if admin has not configured any invoice TnC yet --}}
            <p class="section-title">BASIC TERMS & CONDITIONS</p>
            <ol style="font-size: 10px;">
                <li>Contract Duration: The contract duration is for one year or more, unless otherwise mutually agreed upon by the parties in writing under this agreement.</li>
                <li>Right to Terminate: My Chitti reserves the right to terminate the contract or any services at its discretion, with or without cause, by providing a thirty (30) day written notice to the vendor/service provider.</li>
                <li>No Guarantee of Business: My Chitti does not guarantee any specific business or sales leads to vendors/service providers listed on the platform. It merely acts as an intermediary connecting businesses and customers.</li>
                <li>Non-Refundable Payments: All payments made or due under this contract are non-refundable.</li>
                <li>Acceptance of Terms: By making a payment under this contract, the vendor/service provider agrees to the Terms of Use as outlined on the My Chitti platform.</li>
            </ol>
        @endif
    @endif


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
