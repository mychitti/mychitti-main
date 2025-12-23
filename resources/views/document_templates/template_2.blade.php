<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #2d2d2d;
    }

    h2 {
        text-align: center;
        font-size: 16px;
        font-weight: bold;
        border-bottom: 2px solid #2980b9;
        padding-bottom: 6px;
        margin-bottom: 15px;
    }

    .section-title {
        font-weight: bold;
        font-size: 13px;
        background-color: #e0e0e0;
        padding: 6px;
        border-left: 4px solid #2980b9;
        margin-top: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    .info-table td {
        padding: 5px 8px;
        border: 1px solid #ccc;
        vertical-align: top;
    }

    .table th,
    .table td {
        border: 1px solid #aaa;
        padding: 6px;
        font-size: 11px;
        vertical-align: top;
    }

    .table th {
        background-color: #2980b9;
        color: white;
    }

    .ack-box {
        border: 1px dashed #666;
        background-color: #f9f9f9;
        padding: 10px;
        margin-top: 10px;
        font-size: 12px;
    }

    .approve_btn {
        background-color: #27ae60;
        color: white;
        font-size: 13px;
        padding: 8px 24px;
        border: 1px solid #27ae60;
        text-decoration: none;
    }

    .logo-img {
        width: 100px;
        height: auto;
    }

    .signature-line {
        border-top: 1px solid #444;
        margin-top: 40px;
        width: 180px;
    }
</style>

<h2>Product Receivable Receipt (Returnable)</h2>

<!-- Store and Logo Info -->
<table>
    <tr>
        <td style="width: 70%;">
            @if (isset($data['store']))
                <strong>Company:</strong> {{ $data['store']->name }}<br>
                {{ $data['store']->address }}<br>
                {{ $data['store']->phone }}<br>
                {{ $data['store']->email }}<br>
                {{ ($gst = json_decode($data['store']->gst, true)) && isset($gst['code']) ? 'GST: ' . $gst['code'] : '' }}
            @endif
        </td>
        <td style="width: 30%; text-align: right;">
            @php $store_logo = $data['store']['logo']; @endphp
            <img class="logo-img" src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/store/') . '/' . $store_logo, '', 'store/') }}" alt="Logo">
        </td>
    </tr>
</table>

<!-- Client and Receipt Info -->
<table class="info-table">
    <tr>
        <td>
            @if (isset($data['client']))
                <strong>Name:</strong> {{ $data['client']->f_name }}<br>
                <strong>Contact:</strong> {{ $data['client']->phone }}<br>
                <strong>Address:</strong> {{ $data['client']->address }}
            @endif
        </td>
        <td>
            <strong>Receipt No.:</strong> {{ $data['receipt_number'] }}<br>
            <strong>Date:</strong> {{ date('d/m/Y') }}
        </td>
    </tr>
</table>

<!-- Product Table -->
<div class="section-title">Product Details</div>
<table class="table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>Image</th>
            <th>Product Name</th>
            <th>Brand/Model</th>
            <th>Serial No.</th>
            <th>Received For (Issue)</th>
            <th>Accessories Given</th>
            @if ($data['has_value']) <th>Product Value</th> @endif
        </tr>
    </thead>
    <tbody>
        @foreach ($data['items'] as $key => $value)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>
                    <img style="width: 40px; height: 40px;"
                         src="{{ \App\CentralLogics\Helpers::onerror_image_helper($value['image'], asset('storage/app/public/store/recivable-receipts/images/') . '/' . $value['image'], '', 'store/recivable-receipts/images/') }}"
                         alt="Product">
                </td>
                <td>{{ $value['name'] }}</td>
                <td>{{ $value['brand'] }}</td>
                <td>{{ $value['serial_no'] }}</td>
                <td>{{ $value['issue'] }}</td>
                <td>{{ $value['accessories_given'] }}</td>
                @if ($data['has_value']) <td>{{ _price($value['value']) }}</td> @endif
            </tr>
        @endforeach
    </tbody>
</table>

<!-- Acknowledgement -->
<div class="section-title">Acknowledgement</div>
<div class="ack-box">
    The product mentioned above has been received for service and is <strong>returnable</strong>. All conditions and accessories have been verified.<br><br>
    The customer understands that this is a temporary handover and expects the item to be returned post-service.
</div>

<!-- Signature Section -->
<table style="margin-top: 20px;">
    <tr>
        <td>
            <strong>Received By:</strong>
            {{ isset($data['employee']) ? $data['employee']->f_name . ' ' . $data['employee']->l_name : 'N/A' }}
            <div class="signature-line"></div>
        </td>
        <td align="right">
            <strong>Customer Name:</strong> {{ $data['client']->f_name ?? '' }}
            <div class="signature-line" style="float: right;"></div>
        </td>
    </tr>
    <tr>
        <td><strong>Date:</strong> {{ date('d/m/Y') }}</td>
        <td align="right">
            @if(isset($data['rr_id']) && $data['rr_id'])
                <a class="approve_btn" href="{{ route('receivable-receipt.approve', [$data['rr_id']]) }}">Approve</a>
            @else
                <a class="approve_btn" href="#">Approve</a>
            @endif
        </td>
    </tr>
</table>

<!-- Terms and Conditions -->
<div class="section-title">Terms and Conditions</div>
<div class="ack-box">
    {!! _getStoreConfigByKey('returnable_rr_tnc_content') !!}
</div>
