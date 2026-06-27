<!DOCTYPE html>
<html>

<head>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: #fff;
            line-height: 1.5;
        }

        /* ── ACCENT STRIPE ── */
        .top-stripe {
            height: 5px;
            background: linear-gradient(90deg, #0f3460 0%, #16213e 40%, #e94560 100%);
            margin-bottom: 0;
        }



        .sold-by-block {
            width: 65%;
        }

        .sold-by-block .company-name {
            font-size: 15px;
            font-weight: 600;
            color: #0f3460;
            letter-spacing: -0.3px;
            margin-bottom: 3px;
        }

        .sold-by-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #8a8fa8;
            margin-bottom: 4px;
        }

        .sold-by-block .detail-line {
            font-size: 10px;
            color: #555;
            line-height: 1.7;
        }

        /* ── DOCUMENT TITLE ── */
        .doc-title-bar {
            background: #0f3460;
            color: #fff;
            text-align: center;
            padding: 10px 0;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        /* ── RIBBON (repositioned) ── */

        .main-wrapper {
            position: relative;
        }

        /* ── META ROW (billing + invoice info) ── */
        .meta-row {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #e8eaf0;
        }

        .meta-cell {
            padding: 14px 18px;
            border-right: 1px solid #e8eaf0;
            vertical-align: top;
        }

        .meta-cell:last-child {
            border-right: none;
        }

        .meta-label {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: #8a8fa8;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .meta-cell strong {
            color: #0f3460;
            font-weight: 600;
            font-size: 11px;
        }

        .meta-cell p {
            font-size: 10px;
            color: #444;
            line-height: 1.65;
        }

        .meta-cell a {
            color: #e94560;
        }

        .info-pill {
            display: block;
            margin-bottom: 4px;
            font-size: 10px;
            white-space: nowrap;
        }

        .info-pill .key {
            color: #8a8fa8;
        }

        .info-pill .val {
            color: #0f3460;
            font-weight: 500;
            font-family: 'DM Mono', monospace;
        }

        /* ── ITEMS TABLE ── */
        .items-wrap {
            padding: 0;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin: 0;
        }

        table.items-table thead tr {
            background: #16213e;
        }

        table.items-table thead th {
            padding: 8px 10px;
            font-weight: 500;
            font-size: 8.5px;
            color: #c8d0e7;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            text-align: center;
        }

        table.items-table thead th:first-child,
        table.items-table thead th.left {
            text-align: left;
        }

        table.items-table thead tr.sub-header {
            background: #1a2a4a;
        }

        table.items-table thead tr.sub-header th {
            padding: 5px 10px;
            font-size: 8px;
            color: #8a9fc0;
        }

        table.items-table tbody tr {
            border-bottom: 1px solid #eef0f6;
        }

        table.items-table tbody tr:nth-child(even) {
            background: #f8f9fc;
        }

        table.items-table tbody td {
            padding: 8px 10px;
            border: none;
            text-align: center;
            color: #333;
            vertical-align: middle;
        }

        table.items-table tbody td.left {
            text-align: left;
        }

        table.items-table tbody td.item-name {
            text-align: left;
            font-weight: 500;
            color: #1a1a2e;
        }

        table.items-table tbody td.total-col {
            font-weight: 600;
            color: #0f3460;
            font-family: 'DM Mono', monospace;
        }

        /* ── FOOTER SUMMARY ── */
        .footer-grid {
            display: flex;
            border-top: 2px solid #0f3460;
            min-height: 120px;
        }

        .footer-left {
            flex: 1.4;
            padding: 16px 18px;
            border-right: 1px solid #e8eaf0;
        }

        .footer-right {
            flex: 1;
            padding: 14px 18px;
        }

        .amount-words-label {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: #8a8fa8;
            margin-bottom: 4px;
        }

        .amount-words {
            font-size: 10px;
            color: #0f3460;
            font-weight: 500;
            line-height: 1.5;
        }

        .gst-summary-label {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #8a8fa8;
            margin: 12px 0 4px;
        }

        table.gst-mini {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        table.gst-mini td {
            padding: 3px 5px;
            border: none;
            text-align: center;
        }

        table.gst-mini td:first-child {
            text-align: left;
            color: #555;
        }

        .bank-box {
            margin-top: 12px;
            background: #f0f4ff;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 9.5px;
        }

        .bank-box .bank-label {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #8a8fa8;
            margin-bottom: 5px;
        }

        .bank-box .bank-row {
            color: #333;
            line-height: 1.8;
        }

        .bank-box strong {
            color: #0f3460;
        }

        .totals-table {
            width: 100%;
        }

        .totals-table td {
            padding: 4px 0;
            border: none;
            font-size: 10px;
        }

        .totals-table td.lbl {
            text-align: left;
            color: #777;
        }

        .totals-table td.amt {
            text-align: right;
            color: #1a1a2e;
            font-family: 'DM Mono', monospace;
            font-weight: 500;
        }

        .grand-total-row td {
            padding-top: 8px;
            border-top: 1px solid #e8eaf0;
            font-size: 13px;
            font-weight: 600;
        }

        .grand-total-row td.lbl {
            color: #0f3460;
        }

        .grand-total-row td.amt {
            color: #e94560;
        }

        .payable-badge {
            display: inline-block;
            margin-top: 10px;
            background: #e6ffe6;
            border: 1px solid #5dba5d;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 10px;
            color: #276927;
            font-weight: 500;
        }

        .sign-area {
            text-align: right;
            padding-top: 12px;
            margin-top: 12px;
            border-top: 1px dashed #dce0ee;
        }

        .sign-area .for-text {
            font-size: 9px;
            color: #777;
            margin-bottom: 4px;
        }

        .sign-area img {
            width: 90px;
        }

        /* ── PAYMENT METHOD PILL ── */
        .pm-badge {
            display: inline-block;
            background: #e8f0fe;
            color: #174ea6;
            border-radius: 20px;
            padding: 2px 9px;
            font-size: 9px;
            font-weight: 500;
        }

        /* ── PAYMENT PANEL (split legs + tendered/change) ── */
        .pay-panel {
            width: 100%;
            border-collapse: collapse;
            background: #f7f9fc;
            border: 1px solid #e8eaf0;
            border-radius: 5px;
            margin-top: 5px;
        }

        .pay-panel td {
            border: none;
            font-size: 9px;
            padding: 2px 8px;
            line-height: 1.3;
            vertical-align: middle;
            white-space: nowrap;
        }

        .pay-panel .ph td {
            padding: 3px 8px;
            font-size: 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #0f3460;
            font-weight: 600;
            border-bottom: 1px solid #e8eaf0;
        }

        .pay-panel .pk {
            color: #555;
        }

        .pay-panel .pv {
            font-family: 'DM Mono', monospace;
            color: #1a1a2e;
            font-weight: 600;
        }

        .pay-panel .txn {
            color: #9098a8;
            font-size: 8px;
        }

        /* dashed divider between the payment legs and the tender/change summary */
        .pay-panel .sep {
            border-left: 1px dashed #d6dbe8;
        }

        .pay-panel .pos {
            color: #1b7a43;
        }

        .pay-panel .neg {
            color: #c62828;
        }

        .saved-line {
            display: inline-block;
            background: #e6f7ec;
            color: #1b7a43;
            border: 1px dashed #5dba5d;
            border-radius: 5px;
            text-align: center;
            font-weight: 700;
            font-size: 10px;
            padding: 3px 8px;
            margin-top: 5px;
        }

        /* ── TNC / FOOTER ── */
        .tnc-block {
            padding: 14px 20px;
            border-top: 1px solid #e8eaf0;
            font-size: 9.5px;
            color: #555;
        }

        .tnc-block h4 {
            font-size: 10px;
            color: #0f3460;
            margin-bottom: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .tnc-block ol {
            padding-left: 16px;
        }

        .tnc-block li {
            margin-bottom: 3px;
        }

        .bottom-bar {
            background: #0f3460;
            color: #8a9fc0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 20px;
            font-size: 9px;
        }

        .bottom-bar a {
            color: #c8d0e7;
            text-decoration: none;
        }

        .computer-generated {
            text-align: center;
            font-size: 9px;
            color: #aaa;
            padding: 8px 0;
        }

        .jurisdiction {
            text-align: center;
            font-size: 9px;
            color: #888;
            padding: 4px 0 8px;
        }

        .cash-online-table {
            margin-top: 10px;
        }

        .cash-online-table td {
            border: none;
            padding: 2px 4px;
            font-size: 9.5px;
        }

        .cash-online-table th {
            border: none;
            padding: 2px 4px;
            font-size: 9.5px;
            color: #555;
            text-align: left;
        }

        /* logo-block stacks ribbon + logo vertically */
        .logo-block {
            text-align: right;
            display: block;
        }

        .logo-block img {
            width: 90px;
        }

        /* ── PAID/RIBBON STAMP ── */
        /* mPDF fixed positioning is relative to the content area (inside margins).
           Negative offsets of margin_right/margin_top (15mm each) push the ribbon
           to the physical top-right corner of the paper. */
        .ribbon {
            position: fixed;
            top: -15mm;
            right: -15mm;
            width: 110px;
            height: 110px;
        }

        .ribbon img {
            display: block;
            width: 110px;
            height: 110px;
        }
    </style>

    @if ($bill_data['tax_type'] != 'non-gst')
        <style>
            .border-bottom-none {
                border-bottom: none !important;
            }
        </style>
    @endif
</head>

<body>
    <!-- RIBBON STAMP — fixed to top-right page corner -->
    <div class="ribbon">
        @if ($bill_data['template_type'] == 'quotation')
            <img src="{{ asset('storage/util/') . '/' . 'quotation-ribbon2.png' }}" alt="Quotation">
        @else
            <img src="{{ asset('storage/util/') . '/' . $invoice->payment_status . '-ribbon.png' }}"
                alt="{{ $invoice->payment_status }}">
        @endif
    </div>

    <div class="main-wrapper">

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

        <!-- TOP ACCENT STRIPE -->
        <div class="top-stripe"></div>

        <!-- HEADER -->
        <!-- HEADER -->
        <table style="width:100%; background:#f8f9fc; border-bottom: 1px solid #e8eaf0; border-collapse: collapse;">
            <tr>
                <td style="vertical-align:top; padding: 20px 0 16px 24px;">
                    <div class="sold-by-label">Sold by</div>
                    <div class="company-name">{{ $bill_from['name'] }}</div>
                    {!! $composition_vendor
                        ? '<span style="font-size:9px;background:#fff3cd;color:#856404;padding:2px 7px;border-radius:3px;display:inline-block;margin-bottom:4px;">Composition Vendor</span>'
                        : '' !!}
                    <div class="detail-line">
                        {{ $bill_from['address'] }}<br>
                        @if ($bill_data['tax_type'] != 'non-gst')
                            GST:
                            {{ ($gst = json_decode($bill_from['gst'], true)) && isset($gst['code']) ? $gst['code'] : $bill_from['gst'] }}&nbsp;&nbsp;
                            PAN:
                            {{ ($gst = json_decode($bill_from['gst'], true)) && isset($gst['code']) ? substr($gst['code'], 2, 10) : substr($bill_from['gst'], 2, 10) }}<br>
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
                        {{ $bill_from['cin_number'] ? 'CIN: ' . $bill_from['cin_number'] : '' }}
                    </div>
                </td>
                <td style="vertical-align:top; text-align:right; padding: 8px 8px 8px 0; width:110px;">
                    <!-- Logo -->
                    <div>
                        @if (isset($bill_from_type) && $bill_from_type == 'vendor_to_user')
                            @php $store_logo = $bill_from['logo']; @endphp
                            <img width="90"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/store/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                alt="Logo">
                        @else
                            @php $store_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value; @endphp
                            <img width="90"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/business/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'business/') }}"
                                alt="Logo">
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- DOCUMENT TITLE -->
        <div class="doc-title-bar">
            @if ($bill_data['template_type'] == 'quotation')
                Quotation
            @else
                {{ $bill_data['heading'] ? $bill_data['heading'] : ($bill_data['tax_type'] == 'gst' ? 'Tax Invoice' : 'Invoice') }}
            @endif
        </div>

        <!-- META ROW — billing address (left) + invoice info (right) in one table row -->
        <table class="meta-row">
            <tr>
                <!-- Left: billing / shipping address -->
                <td class="meta-cell">
                    @if ($bill_to)
                        <div class="meta-label">Billing Address</div>
                        <strong>{{ $bill_to['full_name'] }}</strong>
                        <p>
                            {{ $bill_to['address'] }}<br>
                            @if ($bill_to['email'])
                                Email: {{ $bill_to['email'] }}<br>
                            @endif
                            Ph: {{ $bill_to['phone'] }}<br>
                            @if ($bill_data['tax_type'] != 'non-gst')
                                @if ($bill_to['gst'])
                                    GST: {{ $bill_to['gst'] }}<br>
                                @endif
                            @endif
                            @if ($invoice->reference)
                                <strong>Ref: {{ $invoice->reference }}</strong>
                            @endif
                        </p>
                    @elseif($invoice->customer_name)
                        <div class="meta-label">Customer</div>
                        <strong>{{ $invoice->customer_name }}</strong>
                    @endif
                </td>

                @if (isset($shipping_address->address) && ($shipping_address && $bill_to && $bill_to['address'] != $shipping_address->address))
                    <td class="meta-cell">
                        <div class="meta-label">Shipping Address</div>
                        <strong>{{ $shipping_address->contact_person_name }}</strong>
                        <p>
                            {{ $shipping_address->address }}<br>
                            @if ($shipping_address->email)
                                Email: {{ $shipping_address->email }}<br>
                            @endif
                            Ph: {{ $shipping_address->contact_person_number }}
                        </p>
                    </td>
                @endif

                <!-- Right: invoice number, date, and other meta pills -->
                <td class="meta-cell" style="text-align:right; width:160px; border-right:none;">
                    @if ($bill_data['task_id'])
                        <div class="info-pill"><span class="key">Task ID:</span> <span class="val">{{ $bill_data['task_id'] }}</span></div>
                    @endif

                    @if ($bill_data['template_type'] == 'quotation')
                        <div class="info-pill"><span class="key">Quotation No:</span> <span class="val">{{ $bill_data['quotation_id'] }}</span></div>
                    @else
                        <div class="info-pill"><span class="key">Invoice No:</span> <span class="val">{{ $bill_data['invoice_number'] }}</span></div>
                    @endif

                    <div class="info-pill"><span class="key">Date:</span> <span class="val">{{ $invoice->invoice_date ?? date('Y-m-d') }}</span></div>

                    @if ($invoice->payment_date)
                        <div class="info-pill"><span class="key">Payment Date:</span> <span class="val">{{ $invoice->payment_date }}</span></div>
                    @endif

                    @if ($bill_data['tax_type'] == 'gst' && $bill_to)
                        <div class="info-pill">
                            <span class="key">Place of Supply:</span>
                            <span class="val">
                                @if ($bill_to['state_code'])
                                    {{ $bill_to['state_code']['abbr'] . '-' . $bill_to['state_code']['code'] }}
                                @else
                                    {{ $bill_from['state_code']['abbr'] . '-' . $bill_from['state_code']['code'] }}
                                @endif
                            </span>
                        </div>
                    @endif

                    @if ($invoice->custom_headers)
                        @foreach (json_decode($invoice->custom_headers) as $key => $value)
                            <div class="info-pill"><span class="key">{{ $key }}:</span> <span class="val">{{ $value }}</span></div>
                        @endforeach
                    @endif

                    @if (isset($invoice->order_type) && $invoice->order_type)
                        <div class="info-pill"><span class="key">Order Type:</span> <span class="val">{{ $invoice->order_type }}</span></div>
                    @endif

                    @if (isset($invoice->token_number) && $invoice->token_number)
                        <div class="info-pill"><span class="key">Token:</span> <span class="val">{{ $invoice->token_number }}</span></div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- ITEMS TABLE -->
        <div class="items-wrap">
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:30px;">#</th>
                        <th class="left" colspan="3">Description</th>
                        @if ($bill_data['tax_type'] != 'non-gst' || $bill_data['module_id'] == 5)
                            <th>HSN/SAC</th>
                        @endif
                        <th>Qty</th>
                        <th>MRP</th>
                        <th>Disc</th>
                        <th>Price</th>
                        @if ($bill_data['tax_type'] != 'non-gst' && !$composition_vendor)
                            <th>Taxable</th>
                            @if ($bill_gst_type == 'igst')
                                <th colspan="2">IGST</th>
                            @else
                                <th colspan="2">CGST</th>
                                <th colspan="2">SGST</th>
                            @endif
                            <th>Tax Total</th>
                        @endif
                        <th>Total</th>
                    </tr>
                    @if ($bill_data['tax_type'] != 'non-gst' && !$composition_vendor)
                        <tr class="sub-header">
                            <th></th>
                            <th colspan="3"></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            @if ($bill_data['tax_type'] != 'non-gst')
                                <th>Rate</th>
                                <th>Amt</th>
                                <th>Rate</th>
                                <th>Amt</th>
                                @if ($bill_gst_type == 'cgst_sgst')
                                    <th></th>
                                    <th></th>
                                @endif
                            @endif
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @php
                        $totalPrice = 0;
                        $subTotalPrice = 0;
                        $totalTaxAmount = 0;
                    @endphp
                    @foreach ($bill_data['invoice_items'] as $key => $qt)
                        @php
                            $qt_line = $qt->price * $qt->qty;
                            if (($qt->gst_status ?? 'excluding') === 'including') {
                                $qt_taxable = $qt->tax > 0 ? $qt_line / (1 + $qt->tax / 100) : $qt_line;
                                $gst_amount = $qt_line - $qt_taxable;
                                $qt_total = $qt_line;
                            } else {
                                $qt_taxable = $qt_line;
                                $gst_amount = _taxPrice($qt_line, $qt->tax, 'actual');
                                $qt_total = $qt_line + $gst_amount;
                            }

                            $totalPrice += $qt_total;
                            $subTotalPrice += $qt_taxable;
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
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td class="item-name" colspan="3">
                                {!! $qt->name !!}
                                @php
                                    $lineDesc = optional($qt->item)->description;
                                    $lineAttrs = optional($qt->item)->description_attributes ?: [];
                                @endphp
                                @if (!empty($lineDesc) || !empty($lineAttrs))
                                    <div style="font-size:9px; color:#666; margin-top:3px; line-height:1.35;">
                                        @if (!empty($lineDesc)){!! nl2br(e($lineDesc)) !!}@endif
                                        @foreach ($lineAttrs as $al => $av)
                                            <div><span style="color:#999;">{{ $al }}:</span> {{ $av }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            @if ($bill_data['tax_type'] != 'non-gst' || $bill_data['module_id'] == 5)
                                <td>{{ $qt->hsn }}</td>
                            @endif
                            <td>{{ $qtyDisplay }}</td>
                            <td>{{ _num($qt->price, 3, false) }}</td>
                            <td>0</td>
                            <td>{{ _num($qt->price, 3, false) }}</td>
                            @if ($bill_data['tax_type'] != 'non-gst' && !$composition_vendor)
                                <td>{{ round($qt_taxable, 3) }}</td>
                                @if ($bill_gst_type == 'cgst_sgst')
                                    <td>{{ $qt->cgst_rate ?? $qt->tax / 2 }}%</td>
                                    <td>{{ $qt->cgst_amount ?? round($gst_amount / 2, 3) }}</td>
                                    <td>{{ $qt->sgst_rate ?? $qt->tax / 2 }}%</td>
                                    <td>{{ $qt->sgst_amount ?? round($gst_amount / 2, 3) }}</td>
                                @else
                                    <td>{{ $qt->igst_rate ?? $qt->tax }}%</td>
                                    <td>{{ $qt->igst_amount ?? round($gst_amount, 3) }}</td>
                                @endif
                                <td>{{ round($gst_amount, 3) }}</td>
                            @endif
                            <td class="total-col">
                                @if ($qt->total)
                                    {{ round($qt->total) }}
                                @else
                                    {{ round($qt_total, 3) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- FOOTER SUMMARY -->
        <div class="footer-grid">

            <!-- LEFT: words, GST summary, bank, etc. -->
            <div class="footer-left">
                <div class="amount-words-label">Amount in words</div>
                <div class="amount-words">{{ ucwords(_convertNumberToWords($bill_data['total_amount'])) }}</div>

                @if ($bill_data['tax_type'] != 'non-gst' && !$composition_vendor)
                    <div class="gst-summary-label">GST Summary</div>
                    <table class="gst-mini">
                        <tr>
                            <td></td>
                            @foreach ($gst_summary as $key => $value)
                                <td style="color:#555;font-weight:500;">{{ $key }}%</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td><strong>{{ $bill_gst_type == 'cgst_sgst' ? 'CGST+SGST' : 'IGST' }}</strong></td>
                            @foreach ($gst_summary as $key => $value)
                                <td style="color:#0f3460;">{{ $value > 0 ? $value : '–' }}</td>
                            @endforeach
                        </tr>
                    </table>
                @endif

                @if ($invoice->payment_method == 'Cash and Online')
                    <table class="cash-online-table">
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
                    <div class="bank-box">
                        <div class="bank-label">Bank Details</div>
                        <div class="bank-row">
                            @if ($invoice->bankAccount?->upi_qr_code)
                                <img src="{{ asset('storage/store/documents/') . '/' . $invoice->bankAccount?->upi_qr_code }}"
                                    alt="QR Code" style="width:60px;height:60px;float:right;margin-left:8px;">
                            @endif
                            <strong>{{ $invoice->bankAccount?->bank_name }}</strong><br>
                            A/c Holder: {{ $invoice->bankAccount?->account_holder_name }}<br>
                            A/c No: {{ $invoice->bankAccount?->account_number }}<br>
                            IFSC: {{ $invoice->bankAccount?->ifsc_code }}
                        </div>
                    </div>
                @endif

                @if ($invoice->advance_amount)
                    <div class="payable-badge">
                        Payable:
                        {{ \App\CentralLogics\Helpers::currency_symbol() . _num($invoice->total_amount - $invoice->advance_amount, 3) }}
                    </div>
                @endif
            </div>

            <!-- RIGHT: totals + signature -->
            <div class="footer-right">
                <table class="totals-table">
                    <tr>
                        <td class="lbl">Sub Total</td>
                        <td class="amt">
                            {{ \App\CentralLogics\Helpers::currency_symbol() . _num($invoice->subtotal_amount ?? $subTotalPrice, 3) }}
                        </td>
                    </tr>

                    @if ($invoice->additional_charges)
                        @foreach (json_decode($invoice->additional_charges) as $key => $value)
                            @php
                                $rowtax = _taxPrice($value->amount, $value->tax, 'actual');
                                $totalTaxAmount += $rowtax;
                                $totalPrice += $value->amount + $rowtax;
                            @endphp
                            <tr>
                                <td class="lbl">{{ formatLabel($value->name) }}</td>
                                <td class="amt">
                                    {{ \App\CentralLogics\Helpers::currency_symbol() . _num($value->calc_amount, 3) }}
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    @if ($bill_data['tax_type'] != 'non-gst' && !$composition_vendor)
                        <tr>
                            <td class="lbl">Taxable Amount</td>
                            <td class="amt">
                                {{ \App\CentralLogics\Helpers::currency_symbol() . _num($invoice->subtotal_amount ?? $totalPrice - $totalTaxAmount, 3) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Tax Amount</td>
                            <td class="amt">
                                {{ \App\CentralLogics\Helpers::currency_symbol() . _num($invoice->final_tax ?? $totalTaxAmount, 3) }}
                            </td>
                        </tr>
                    @endif

                    @if ($invoice->coupon_amount > 0)
                        <tr>
                            <td class="lbl">Coupon</td>
                            <td class="amt">–
                                {{ \App\CentralLogics\Helpers::currency_symbol() . _num($invoice->coupon_amount, 3) }}
                            </td>
                        </tr>
                    @endif

                    @if ($invoice->discount_amount > 0)
                        <tr>
                            <td class="lbl">Discount</td>
                            <td class="amt">–
                                {{ \App\CentralLogics\Helpers::currency_symbol() . _num($invoice->discount_amount, 3) }}
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td class="lbl">Rounded Off</td>
                        <td class="amt">{{ $invoice->round_off ?? 0 }}</td>
                    </tr>

                    <tr class="grand-total-row">
                        <td class="lbl">Grand Total</td>
                        <td class="amt">
                            {{ \App\CentralLogics\Helpers::currency_symbol() . _num($bill_data['total_amount'], 3) }}
                        </td>
                    </tr>

                    @if (!empty($invoice->payment_method))
                        @if (!empty($bill_data['payment_legs']) && count($bill_data['payment_legs']))
                            <tr>
                                <td colspan="2" style="padding:2px 0 0;">
                                    @php
                                        $legs = $bill_data['payment_legs'];
                                        $hasTender = !empty($bill_data['tendered']) && $bill_data['tendered'] > 0;
                                        $hasChange = !empty($bill_data['change_return']) && $bill_data['change_return'] > 0;
                                        $hasBalance = !empty($bill_data['balance_due']) && $bill_data['balance_due'] > 0;
                                        $payCols = $legs->count() + ($hasTender ? 1 + ($hasChange || $hasBalance ? 1 : 0) : 0);
                                        if ($payCols < 2) $payCols = 2;
                                        $cs = \App\CentralLogics\Helpers::currency_symbol();
                                    @endphp
                                    <table class="pay-panel">
                                        <tr class="ph">
                                            <td colspan="{{ $payCols - 1 }}">Payment</td>
                                            <td style="text-align:right;"><span class="pm-badge">{{ $invoice->payment_method }}</span></td>
                                        </tr>
                                        <tr class="data">
                                            @foreach ($legs as $leg)
                                                <td>
                                                    <span class="pk">{{ ucfirst($leg->mode) }}{{ $leg->sub_type ? ' · ' . strtoupper($leg->sub_type) : '' }}</span>
                                                    <span class="pv">{{ $cs . _num($leg->amount, 2) }}</span>
                                                    @if (!empty($leg->reference) || !empty($leg->approval_code))
                                                        <span class="txn">· {{ $leg->reference ?: $leg->approval_code }}</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            @if ($hasTender)
                                                <td class="sep">
                                                    <span class="pk">Tendered</span>
                                                    <span class="pv">{{ $cs . _num($bill_data['tendered']) }}</span>
                                                </td>
                                                @if ($hasChange)
                                                    <td>
                                                        <span class="pk pos">Change</span>
                                                        <span class="pv pos">{{ $cs . _num($bill_data['change_return']) }}</span>
                                                    </td>
                                                @elseif ($hasBalance)
                                                    <td>
                                                        <span class="pk neg">Balance</span>
                                                        <span class="pv neg">{{ $cs . _num($bill_data['balance_due']) }}</span>
                                                    </td>
                                                @endif
                                            @endif
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td class="lbl">Payment</td>
                                <td class="amt"><span class="pm-badge">{{ $invoice->payment_method }}</span></td>
                            </tr>
                        @endif
                    @endif

                    @if ($invoice->advance_amount)
                        <tr>
                            <td class="lbl">Advance</td>
                            <td class="amt">{{ $invoice->advance_amount }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Payable</td>
                            <td class="amt">{{ $invoice->total_amount - $invoice->advance_amount }}</td>
                        </tr>
                    @endif

                    @if (!empty($bill_data['total_saved']) && $bill_data['total_saved'] > 0)
                        <tr>
                            <td colspan="2" style="padding:6px 0 0; text-align:right;">
                                <div class="saved-line">★ You Saved {{ \App\CentralLogics\Helpers::currency_symbol() . _num($bill_data['total_saved']) }} on MRP ★</div>
                            </td>
                        </tr>
                    @endif
                </table>

                @if (isset($bill_from_type))
                    <div class="sign-area">
                        @if (isset($bill_from_type) && $bill_from_type == 'vendor_to_user' && (isset($invoice->sign) && $invoice->sign))
                            <div class="for-text">For {{ $bill_data['store']->name }}</div>
                            <img src="{{ asset('storage/store/signature/') . '/' . _signImgById($invoice->sign) }}">
                        @elseif($bill_from_type != 'vendor_to_user')
                            @if (isset($invoice->sign) && $invoice->sign)
                                <div class="for-text">For
                                    {{ \App\Models\BusinessSetting::where(['key' => 'business_name'])->first()->value ?? '' }}
                                </div>
                                <img
                                    src="{{ asset('storage/store/signature/') . '/' . _signImgById($invoice->sign) }}">
                            @else
                                @php($sign = \App\Models\BusinessSetting::where('key', 'admin_signature')->first())
                                @php($sign = $sign->value ?? '')
                                <img src="{{ asset('storage/business/') . '/' . $sign }}">
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- TERMS & CONDITIONS -->
        <?php
        $isQuotation = ($bill_data['template_type'] ?? '') === 'quotation';
        $storeConfig = isset($bill_data['store']) ? $bill_data['store']->storeConfig : null;
        $fromVendor = isset($bill_from_type) && $bill_from_type === 'vendor_to_user';
        $fromAdmin = isset($bill_from_type) && in_array($bill_from_type, ['admin_to_user', 'admin_to_vendor']);
        $isShop = ($bill_data['vendor_typ'] ?? '') === 'shop';
        ?>

        @if ($isQuotation)
            @if ($storeConfig?->tnc_quotation_status && !empty($bill_data['quote_tnc']))
                <div class="tnc-block">
                    <h4>Terms &amp; Conditions</h4>{!! $bill_data['quote_tnc'] !!}
                </div>
            @endif
        @elseif ($invoice->terms_and_conditions)
            <div class="tnc-block">
                <h4>Terms &amp; Conditions</h4>{!! $invoice->terms_and_conditions !!}
            </div>
        @elseif ($fromVendor && $storeConfig?->tnc_invoice_status)
            @if ($isShop)
                <?php $tncUrl = _termsAndConditionsUrl($bill_from_type, $bill_from['id'], $bill_data['vendor_typ']); ?>
                @if ($tncUrl)
                    <div class="tnc-block"><a target="_blank" href="{{ $tncUrl }}">Terms &amp; Conditions</a>
                    </div>
                @endif
            @else
                <?php $tncContent = _vendorTandC($bill_from['id']); ?>
                @if ($tncContent)
                    <div class="tnc-block">
                        <h4>Terms &amp; Conditions</h4>{!! $tncContent !!}
                    </div>
                @endif
            @endif
        @elseif ($fromAdmin)
            <?php $adminTnc = _adminInvoiceTnC(); ?>
            @if ($adminTnc)
                <div class="tnc-block">
                    <h4>Terms &amp; Conditions</h4>{!! $adminTnc->content !!}
                </div>
            @else
                <div class="tnc-block">
                    <h4>Basic Terms &amp; Conditions</h4>
                    <ol>
                        <li>Contract Duration: The contract duration is for one year or more, unless otherwise mutually
                            agreed upon by the parties in writing under this agreement.</li>
                        <li>Right to Terminate: My Chitti reserves the right to terminate the contract or any services
                            at its discretion, with or without cause, by providing a thirty (30) day written notice to
                            the vendor/service provider.</li>
                        <li>No Guarantee of Business: My Chitti does not guarantee any specific business or sales leads
                            to vendors/service providers listed on the platform. It merely acts as an intermediary
                            connecting businesses and customers.</li>
                        <li>Non-Refundable Payments: All payments made or due under this contract are non-refundable.
                        </li>
                        <li>Acceptance of Terms: By making a payment under this contract, the vendor/service provider
                            agrees to the Terms of Use as outlined on the My Chitti platform.</li>
                    </ol>
                </div>
            @endif
        @endif

        <!-- COMPUTER GENERATED NOTE -->
        @if ((isset($bill_data['store']) && $bill_data['store']->invoice_footer_line) || $bill_from_type == 'admin_to_user')
            <p class="computer-generated">This is a computer-generated invoice. No signature required.</p>
        @endif

        <!-- BOTTOM BAR -->
        <div class="bottom-bar">
            <a href="https://www.mychitti.net">www.mychitti.net</a>
            <span>
                @if (isset($bill_data['store']))
                    @if ($bill_data['store']->storeConfig?->jurisdiction_statement_status)
                        {{ $bill_data['store']->jurisdiction_statement }}
                    @endif
                @else
                    Subject to Tirupati Jurisdiction
                @endif
            </span>
            <span>{{ \App\Models\BusinessSetting::where(['key' => 'business_name'])->first()->value }}</span>
        </div>

    </div>
</body>

</html>
