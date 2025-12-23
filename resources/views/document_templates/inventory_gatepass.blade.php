<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Pass - Inventory Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }

        .gate-pass {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 28px;
            {{-- letter-spacing: 2px; --}} margin-bottom: 10px;
        }

        .header h2 {
            margin-bottom: 0px;
            color: #3498db;
            font-size: 20px;
            font-weight: normal;
        }

        .pass-number {
            background: #3498db;
            color: white;
            padding: 5px 15px;
            display: inline-block;
            border-radius: 3px;
            font-size: 14px;
            margin-top: 10px;
            width: 200px;
            margin: 0 auto;
        }

        .info-section {
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .info-row {
            width: 100%;
            margin-bottom: 15px;
        }

        .info-row:after {
            content: "";
            display: table;
            clear: both;
        }

        .info-item {
            width: 48%;
            float: left;
            margin-right: 4%;
        }

        .info-item:nth-child(2n) {
            margin-right: 0;
        }

        .info-label {
            font-weight: bold;
            color: #2c3e50;
            font-size: 12px;
            margin-bottom: 5px;
            text-transform: uppercase;
            display: block;
        }

        .info-value {
            {{-- border-bottom: 1px solid #ddd; --}}
            padding: 5px 0;
            min-height: 25px;
            color: #333;
            display: block;
        }

        .items-section {
            margin: 25px 0;
        }

        .section-title {
            background: #2c3e50;
            color: white;
            padding: 10px 15px;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background: #34495e;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            border: 1px solid #2c3e50;
        }

        td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            font-size: 13px;
        }

        tr:nth-child(even) {
            background: #f8f9fa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary {
            margin: 30px 0;
            padding: 5px;
            background: #f8f9fa;
            border: 1px solid #3498db;
            border-radius: 8px;
        }

        .summary-row {
            width: 100%;
            padding: 12px 20px;
            font-size: 15px;
        }

        .summary-row:after {
            content: "";
            display: table;
            clear: both;
        }

        .summary-row .label {
            font-weight: 500;
            color: #2c3e50;
            float: left;
            width: 40%;
        }

        .summary-row .value {
            font-weight: 600;
            color: #333;
            width: 60%;
            text-align: right;
            {{-- border-bottom: 2px solid #bdc3c7; --}} padding: 5px 10px;
            float: right;
        }

        .summary-row.total {
            background: #2c3e50;
            {{-- margin: 15px -20px -20px -20px; --}} padding: 18px 40px;
            border-radius: 0 0 5px 5px;
        }

        .summary-row.total .label {
            font-size: 18px;
            font-weight: bold;
            color: white;
            letter-spacing: 1px;
        }

        .summary-row.total .value {
            font-size: 20px;
            font-weight: bold;
            color: white;
            {{-- border-bottom: 3px solid #3498db; --}}
        }

        .signatures {
            margin-top: 60px;
            padding: 0 20px;
        }

        .signatures:after {
            content: "";
            display: table;
            clear: both;
        }

        .signature-box {
            width: 30%;
            float: left;
            margin: 0 10px;
            text-align: center;
            position: relative;
        }

        .signature-box:last-child {
            margin-right: 0;
        }

        .signature-space {
            height: 60px;
            display: block;
        }

        .signature-line {
            border-top: 2px solid #2c3e50;
            padding-top: 10px;
            font-size: 13px;
            font-weight: bold;
            color: #2c3e50;
        }

        .footer {
            margin-top: 40px;
            padding: 20px 0;
            border-top: 3px solid #2c3e50;
            text-align: center;
        }

        .footer p {
            margin: 8px 0;
            line-height: 1.6;
        }

        .footer p:first-child {
            font-size: 12px;
            color: #555;
            font-weight: 600;
        }

        .footer p:last-child {
            font-size: 11px;
            color: #777;
        }

        .footer strong {
            color: #e74c3c;
            font-weight: bold;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .gate-pass {
                box-shadow: none;
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="gate-pass">
        <div class="header">
            <h2>GATE PASS</h2>
            <h1>{{ $store->name }}</h1>
            {{-- <div class="pass-number">VEHICLE NO: ______________</div> --}}
        </div>

        <div class="info-section">
            <div class="info-row">
                <div class="info-item">
                    <span class="info-label">Vehicle No.:</span>
                    <span class="info-value">{{$pass->vehicle_number}}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date:</span>
                    <span class="info-value">{{ date('Y-m-d') }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-item">
                    <span class="info-label">Driver Name:</span>
                    <span class="info-value">{{ data_get($driver_data, 'name', '') }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Pass No:</span>
                    <span class="info-value">{{ $gatePassNumber }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-item">
                    <span class="info-label">Driver Phone:</span>
                    <span class="info-value">{{ data_get($driver_data, 'phone', '') }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Sales Person:</span>
                    <span class="info-value">{{ data_get($driver_data, 'salesman_name', '') }}
                        ({{ data_get($driver_data, 'salesman_phone', '') }})</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-item">
                    <span class="info-label">Driver Address:</span>
                    <span class="info-value">{{ data_get($driver_data, 'address', '') }}
                    </span>
                </div>
                @if ($pass->invoice_id)
                    <div class="info-item">
                        <span class="info-label">Reference Invoice:</span>
                        <span class="info-value">{{ $pass->invoice_id ?? '' }}</span>
                    </div>
            </div>
            <div class="info-row">
                @endif
                <div class="info-item">
                    <span class="info-label">Route:</span>
                    <span class="info-value">{{ $pass->route }}</span>
                </div>
               
            </div>
        </div>

        <div class="items-section">
            <div class="section-title">ITEMS DETAILS</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">S.No</th>
                        <th style="width: 10%;">Qty</th>
                        <th style="width: 30%;">Item Name</th>
                        <th style="width: 10%;" class="text-right">MRP</th>
                        <th style="width: 10%;" class="text-center">Stock</th>
                        <th style="width: 10%;" class="text-center">Return</th>
                        <th style="width: 10%;" class="text-right">Rate</th>
                        <th style="width: 15%;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($gpItems as $key => $value)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $value->qty }} {{ $value->unitId?->unit }}</td>
                            <td>{{ $value->name }}</td>
                            <td class="text-right">{{ $value->price }}/-</td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-right"></td>
                            <td class="text-right">{{ _price($value->qty * $value->price) }}</td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>

        <div class="summary">
            {{-- <div class="summary-row">
                <span class="label">Subtotal:</span>
                <span class="value">₹5345345</span>
            </div> --}}
            <div class="summary-row">
                <span class="label">Total Quantity:</span>
                <span class="value">{{ $gpItems->sum('qty') }}</span>
            </div>
            <div class="summary-row total">
                <span class="label">TOTAL AMOUNT:</span>
                <span class="value">{{ _price($pass->total_amount) }}</span>
            </div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <span class="signature-space"></span>
                <div class="signature-line">Prepared By</div>
            </div>
            <div class="signature-box">
                <span class="signature-space"></span>
                <div class="signature-line">Checked By</div>
            </div>
            <div class="signature-box">
                <span class="signature-space"></span>
                <div class="signature-line">Approved By</div>
            </div>
        </div>

        <div class="footer">
            {{-- <p><strong>Note:</strong> This is a computer generated gate pass. Please verify all items before dispatch.</p> --}}
            <p>{{ $store->name }} | Contact: {{ $store->phone }} | Email: {{ $store->email }}</p>
        </div>
    </div>
</body>

</html>
