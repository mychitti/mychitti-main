<style>
    body {
        font-family: sans-serif;
        font-size: 12px;
        color: #333;
    }

    .approve_btn {
        background-color: #007bff;
        color: white;
        text-decoration: none;
        font-size: 14px;
        padding: 12px 40px;
        border: 1px solid #007bff;
    }

    h2 {
        text-align: center;
        font-size: 16px;
        border-bottom: 2px solid #444;
        padding-bottom: 4px;
    }

    .section-title {
        font-weight: bold;
        font-size: 13px;
        background: #f0f0f0;
        padding: 6px;
        border-left: 4px solid #007ACC;
        margin-top: 15px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }

    .table th,
    .table td {
        border: 1px solid #aaa;
        padding: 6px;
        font-size: 11px;
    }

    .table th {
        background: #007ACC;
        color: white;
    }

    .info-table td {
        padding: 6px 8px;
    }

    .ack-box {
        border: 1px dashed #666;
        background: #f9f9f9;
        padding: 10px;
        margin-top: 10px;
    }

    .btn {
        border: 1px solid #189fffff;
        color: #189fffff;
        border-radius: 8px;
    }
</style>

<h2>Product Receivable Receipt (Returnable)</h2>
<table width="100%" class="no-border">
    <tr>
        <td style="width: 70%;">
            @if (isset($data['store']))
                <strong>Company: {{ $data['store']->name }}</strong><br>
                {{ $data['store']->address }}<br>
                {{ $data['store']->phone }} <br>
                {{ $data['store']->email }} <br>
                {{ ($gst = json_decode($data['store']->gst, true)) && isset($gst['code']) ? 'GST: ' . $gst['code'] : '' }}
            @endif
        </td>
        <td style="width: 30%; text-align: right;">
            @if (auth('admin')->check())
                @php  $store_logo = $data['store']->logo; @endphp
                <img width="100" class=""
                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/business/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'business/') }}"
                    alt="Logo">
            @else
                @php  $store_logo = $data['store']['logo']; @endphp

                <img width="100" class=""
                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/store/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                    alt="Logo">
            @endif

        </td>
    </tr>
</table>

<div class="section-title">Customer Details</div>
<table width="100%" style="font-size: 12px; margin-bottom: 15px;" class="info-table">
    <tr>
        @if (isset($data['client']))
            <td><strong>Name:</strong> {{ $data['client']->f_name }} <br>
                <strong>Contact:</strong> {{ $data['client']->phone }} <br>
                <strong>Address:</strong> {{ $data['client']->address }}
            </td>
        @else
            <td></td>
        @endif
        <td style="line-height: 1.6; text-align:right">
            @if (isset($data['task_id']) && $data['task_id'])
                <strong>Task Id:</strong> {{ $data['task_id'] }}<br>
            @endif
            <strong>Receipt No.:</strong> {{ $data['receipt_number'] }}<br>
            <strong>Date:</strong> {{ date('d/m/Y') }}<br>
        </td>
    </tr>
</table>

<div class="section-title">Product Details</div>
<table class="table">
    <tr>
        <th>S.No</th>
        <th>Image</th>
        <th>Captured Images</th>
        <th>Product Name</th>
        <th>Brand/Model</th>
        <th>Serial No.</th>
        <th>Received For (Issue)</th>
        <th>Accessories Given</th>
        @if ($data['has_value'])
            <th>Product Value</th>
        @endif
    </tr>
    @foreach ($data['items'] as $key => $value)
        <tr>
            <td>{{ $key + 1 }}</td>
            <td> <img class="avatar-img onerror-image" style="width:50px;  aspect-ratio:1"
                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($value['image'], asset('storage/app/public/store/recivable-receipts/images/') . '/' . $value['image'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/recivable-receipts/images/') }}"
                    alt="Image"></td>
            <td>
                @php
                    $webcamFiles = $value['webcam_file'] ?? [];

                    if (is_string($webcamFiles) && str_starts_with($webcamFiles, '[')) {
                        $webcamFiles = json_decode($webcamFiles, true) ?? [];
                    }

                    if (!is_array($webcamFiles)) {
                        $webcamFiles = [$webcamFiles];
                    }
                @endphp
                <div style="display:flex;">
                    @if (!empty($webcamFiles))
                        @foreach ($webcamFiles as $file)
                            <a href="{{ asset('storage/app/public/store/recivable-receipts/images/' . $file) }}"
                                style="cursor: zoom-in" target="_blank">
                                <img style="width:20px; aspect-ratio:1"
                                    src="{{ asset('storage/app/public/store/recivable-receipts/images/' . $file) }}"
                                    alt="captured image" class="rounded">
                            </a>
                        @endforeach
                    @endif
                </div>
            </td>
            <td>{{ $value['name'] }}</td>
            <td>{{ $value['brand'] }}</td>
            <td>{{ $value['serial_no'] }}</td>
            <td>{{ $value['issue'] }}</td>
            <td>{{ $value['accessories_given'] }}</td>
            @if ($data['has_value'])
                <td>{{ _price($value['value']) }}</td>
            @endif
        </tr>
    @endforeach
</table>

<div class="section-title">Acknowledgement</div>
<div class="ack-box">
    The product mentioned above has been received for service and is **returnable**. All conditions and accessories have
    been verified.<br><br>
    The customer understands that this is a temporary handover and expects the item to be returned post-service.
</div>

<table style="width:100%;" class="info-table">
    <tr>
        <td><strong>Received By:</strong>
            {{ isset($data['employee']) && $data['employee'] ? $data['employee']->f_name . ' ' . $data['employee']->l_name : 'N/A' }}
            <br>
        </td>
        <td style="text-align:right; max-width:200px;">
            <div style="word-break: break-all; white-space: normal;">
                <strong>Customer Name:</strong>
                {{ isset($data['client']) ? $data['client']->f_name : '' }}
            </div>
        </td>

    </tr>
    <tr>
        <td>
            <strong>Signature:</strong> ______________
        </td>
        <td style="text-align:right;">
            <strong>Customer Signature:</strong> ______________ <br>
        </td>
    </tr>
    <tr>
        <td><strong>Date:</strong> {{ date('d/m/Y') }}</td>
        <td align="right">
            @if ((!isset($data['approved']) || !$data['approved']) && isset($data['rr_id']) && $data['rr_id'])
                <a class="approve_btn" href="{{ route('receivable-receipt.approve', [$data['rr_id']]) }}">
                    &nbsp; &nbsp; Approve &nbsp;&nbsp;
                </a>
            @else
                <a class="approve_btn" href="#">
                    &nbsp; &nbsp; Approved &nbsp;&nbsp;
                </a><br>
                <br>
                <span><b>Approved By :</b>{{ $data['approved_by'] ?? '' }} {{ $data['approved_phone'] ?? '' }}</span>
            @endif
        </td>
    </tr>
</table>

<div class="section-title " style="margin-top: 80px;">Terms and Conditions</div>
<div class="ack-box">
    {!! _getStoreConfigByKey('returnable_rr_tnc_content') !!}
</div>
