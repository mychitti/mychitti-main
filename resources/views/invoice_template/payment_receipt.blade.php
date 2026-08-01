<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $payment->receipt_no }}</title>
    <style>
        * {
            font-family: sans-serif;
        }

        body {
            font-size: 11px;
            color: #222;
        }

        .head-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .head-table td {
            vertical-align: top;
            padding: 0;
        }

        .store-name {
            font-size: 15px;
            font-weight: bold;
            color: #0f3460;
        }

        .store-meta {
            font-size: 10px;
            color: #555;
            line-height: 1.5;
        }

        .doc-title {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #0f3460;
            text-align: right;
        }

        .doc-meta {
            font-size: 10px;
            text-align: right;
            color: #555;
            line-height: 1.6;
        }

        .rule {
            border: 0;
            border-top: 2px solid #0f3460;
            margin: 6px 0 10px;
        }

        .box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .box td {
            border: 1px solid #d5d9e0;
            padding: 6px 8px;
            font-size: 10.5px;
        }

        .box td.lbl {
            background: #f4f6f9;
            color: #555;
            width: 28%;
        }

        .amount-strip {
            background: #eef4ff;
            border: 1px solid #0f3460;
            padding: 8px 10px;
            margin-bottom: 10px;
        }

        .amount-strip .amt {
            font-size: 18px;
            font-weight: bold;
            color: #0f3460;
        }

        .amount-strip .words {
            font-size: 10px;
            color: #444;
            margin-top: 2px;
        }

        .ledger {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .ledger th,
        .ledger td {
            border: 1px solid #d5d9e0;
            padding: 4px 6px;
            font-size: 10px;
        }

        .ledger th {
            background: #f4f6f9;
            color: #444;
            text-align: left;
        }

        .ledger td.num {
            text-align: right;
        }

        .ledger tr.this-one td {
            background: #fffbe6;
            font-weight: bold;
        }

        .foot {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .foot td {
            font-size: 9.5px;
            color: #666;
            vertical-align: bottom;
        }

        .sign {
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $cs = \App\CentralLogics\Helpers::currency_symbol();
        $total = (float) $invoice->total_amount;
        $balance = (float) $payment->balance_after;
    @endphp

    <table class="head-table">
        <tr>
            <td width="60%">
                <div class="store-name">{{ $store->name ?? '' }}</div>
                <div class="store-meta">
                    {{ $store->address ?? '' }}<br>
                    @if (!empty($store->phone))
                        Phone: {{ $store->phone }}<br>
                    @endif
                    @if (!empty($store->gst_number))
                        GSTIN: {{ $store->gst_number }}
                    @endif
                </div>
            </td>
            <td width="40%">
                <div class="doc-title">PAYMENT RECEIPT</div>
                <div class="doc-meta">
                    Receipt No: <b>{{ $payment->receipt_no }}</b><br>
                    Date: {{ \Carbon\Carbon::parse($payment->payment_date ?? $payment->created_at)->format('d M Y') }}
                </div>
            </td>
        </tr>
    </table>

    <hr class="rule">

    <table class="box">
        <tr>
            <td class="lbl">Received from</td>
            <td>
                <b>{{ $customer['name'] ?: 'Walk-in customer' }}</b>
                @if (!empty($customer['phone']))
                    <br>{{ $customer['phone'] }}
                @endif
                @if (!empty($customer['address']))
                    <br>{{ $customer['address'] }}
                @endif
            </td>
            <td class="lbl">Against invoice</td>
            <td>
                <b>{{ $invoice->invoice_id }}</b><br>
                Invoice total: {{ $cs . _num($total, 2) }}
            </td>
        </tr>
        <tr>
            <td class="lbl">Payment mode</td>
            <td>
                {{ $payment->payment_mode }}
                @if ($payment->payment_mode === 'Cash and Online')
                    <br>Cash {{ $cs . _num($payment->cash_amount, 2) }} · Online
                    {{ $cs . _num($payment->online_amount, 2) }}
                @endif
            </td>
            <td class="lbl">Reference</td>
            <td>{{ $payment->reference ?: '—' }}</td>
        </tr>
    </table>

    <div class="amount-strip">
        <span class="amt">{{ $cs . _num($payment->amount, 2) }}</span>
        <span style="font-size:10px;color:#444;"> received</span>
        <div class="words">{{ ucwords(_convertNumberToWords($payment->amount)) }}</div>
    </div>

    <table class="ledger">
        <thead>
            <tr>
                <th>#</th>
                <th>Receipt No</th>
                <th>Date</th>
                <th>Mode</th>
                <th class="num">Amount</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receipts as $i => $r)
                <tr class="{{ $r->id == $payment->id ? 'this-one' : '' }}">
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $r->receipt_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->payment_date ?? $r->created_at)->format('d M Y') }}</td>
                    <td>{{ $r->payment_mode }}</td>
                    <td class="num">{{ $cs . _num($r->amount, 2) }}</td>
                    <td class="num">{{ $cs . _num($r->balance_after, 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4" style="text-align:right;"><b>Total paid</b></td>
                <td class="num"><b>{{ $cs . _num($paid, 2) }}</b></td>
                <td class="num"><b>{{ $cs . _num($balance, 2) }}</b></td>
            </tr>
        </tbody>
    </table>

    <table class="foot">
        <tr>
            <td width="60%">
                @if ($balance > 0.009)
                    Balance of <b>{{ $cs . _num($balance, 2) }}</b> remains due on invoice
                    {{ $invoice->invoice_id }}.
                @else
                    Invoice {{ $invoice->invoice_id }} is settled in full. Thank you.
                @endif
                <br>This is a computer-generated receipt.
            </td>
            <td width="40%" class="sign">
                <div style="height:40px;">&nbsp;</div>
                For {{ $store->name ?? '' }}
            </td>
        </tr>
    </table>
</body>

</html>
