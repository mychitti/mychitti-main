<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Stock Report </title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            {{-- padding: 20px; --}} color: #333;
        }

        .header-table {
            width: 100%;
            margin-bottom: 25px;
            border-bottom: 2px solid #ccc;
            padding-bottom: 15px;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .store-info {
            width: 70%;
        }

        .store-info h2 {
            font-size: 18px;
            color: #2c3e50;
            margin: 0 0 8px 0;
        }

        .store-info p {
            margin: 2px 0;
            font-size: 11px;
            color: #666;
        }

        .logo-cell {
            width: 30%;
            text-align: right;
        }

        .logo-box {
            width: 80px;
            height: 60px;
            background-color: #4a90e2;
            border: 1px solid #ccc;
            text-align: center;
            line-height: 60px;
            color: white;
            font-weight: bold;
            font-size: 10px;
        }

        .report-title {
            text-align: center;
            margin: 25px 0;
        }

        .report-title h1 {
            font-size: 22px;
            margin: 0 0 5px 0;
            color: #2c3e50;
        }

        .report-period {
            font-size: 14px;
            color: #666;
        }

        .summary-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .summary-table td {
            width: 33.33%;
            padding: 20px 15px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .summary-card-blue {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }

        .summary-card-green {
            background-color: #f1f8e9;
            border-left: 4px solid #4caf50;
        }

        .summary-card-orange {
            background-color: #fff3e0;
            border-left: 4px solid #ff9800;
        }

        .card-title {
            font-size: 11px;
            color: #666;
            margin-bottom: 8px;
        }

        .card-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #ccc;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .transactions-table th {
            background-color: #f8f9fa;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            color: #495057;
            border: 1px solid #dee2e6;
            font-size: 10px;
        }

        .transactions-table td {
            padding: 12px 8px;
            border: 1px solid #dee2e6;
            font-size: 11px;
        }

        .transactions-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .invoice-id {
            color: #007bff;
            font-weight: bold;
        }

        .amount {
            font-weight: bold;
            text-align: right;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .footer p {
            margin: 3px 0;
        }
    </style>
</head>

<body>
    <!-- Header with Store Info and Logo -->
    <table class="header-table">
        <tr>
            <td class="store-info">
                <h2>{{ $store->name }}</h2>
                <p><strong>Address:</strong> {{ $store->address }}</p>
                <p><strong>Phone:</strong> {{ $store->phone }}</p>
                <p><strong>Email:</strong> {{ $store->email }}</p>
                <p><strong>GST No:</strong>
                    {{ ($gst = json_decode($store['gst'], true)) && isset($gst['code']) ? $gst['code'] : $bill_from['gst'] }}
                </p>
            </td>
            <td class="logo-cell">
                <div class="logo-box">
                    @php  $store_logo = $store['logo']; @endphp

                    <img width="80" class=""
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/store/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                        alt="Logo">
                </div>
            </td>
        </tr>
    </table>

    <!-- Report Title -->
    <div class="report-title">
        <h1>Stock Report</h1>
    </div>

    <!-- Summary Cards -->
    <table class="summary-table">
        <tr>
            <td class="summary-card-blue">
                <div class="card-title">Total Items</div>
                <div class="card-value">{{ count($items) }}</div>
            </td>
            <td class="summary-card-green">
                <div class="card-title">Total Stock Quantity</div>
                <div class="card-value">{{ $items->sum('stock') }}</div>
            </td>
            <td class="summary-card-orange">
                <div class="card-title">Low Stock Items</div>
                <div class="card-value">{{ $items->whereBetween('stock', [1, 5])->count() }}</div>
            </td>
            <td class="summary-card-pink">
                <div class="card-title">Out of Stock Items</div>
                <div class="card-value">{{ $items->where('stock', '<', 1)->count() }}</div>
            </td>
        </tr>
    </table>

    <!-- Transaction Details -->
    <div class="section-title">Transaction Details</div>
    <table class="transactions-table">
        <thead>
            <tr>
                <th style="width: 5%;">Sl</th>
                <th style="width: 15%;">Item</th>
                <th style="width: 12%;">Category</th>
                <th style="width: 15%;">Unit Price</th>
                <th style="width: 12%;">Stock Value</th>
                <th style="width: 10%;">Stock</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $key => $item)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>

                        {{ ucwords($item->item_name) ?? 'N/A' }}

                    </td>
                    <td>{{ $item->category?->name ?? 'Category Deleted' }}</td>
                    <td>{{ _price($item->selling_price) }}</td>
                    <td>{{ _price($item->selling_price * $item->stock) }}</td>

                    <td>
                        @if ($item->stock < 1)
                            <span class="badge badge-soft-danger">{{ $item->stock }}</span>
                        @elseif($item->stock < 5)
                            <span class="badge badge-soft-warning">{{ $item->stock }}</span>
                        @else
                            <span class="badge badge-soft-success">{{ $item->stock }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p><strong>Generated on:</strong>{{ now()->format('F d, Y \a\t H:i:s') }}</p>
        <p><strong>Report Period:</strong> {{ translate($data['preset']) }} | <strong>Currency:</strong> Indian Rupees
            (₹)</p>
        <p>This is a computer-generated report and does not require a signature.</p>
    </div>
</body>

</html>
