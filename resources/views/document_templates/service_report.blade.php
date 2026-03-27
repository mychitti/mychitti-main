<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Report - PJS Power</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            padding: 20px;
        }

        .container {
            border: 2px solid #000;
            padding: 15px;
        }

        .header {
            border-bottom: 2px solid #000 !important;
            padding-bottom: 10px;
            margin-bottom: 15px;
            position: relative;
        }

        .logo-section {
            display: inline-block;
            vertical-align: top;
            width: 30%;
        }

        .logo {
            width: 120px;
        }

        .company-info {
            display: inline-block;
            vertical-align: top;
            width: 65%;
            text-align: center;
        }

        .record-copy {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 9pt;
            font-weight: bold;
        }

        .report-title {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #6B2C91;
            margin-bottom: 3px;
        }

        .address {
            font-size: 9pt;
            margin-bottom: 2px;
        }

        .contact {
            font-size: 9pt;
        }

        .customer-section {
            margin-bottom: 10px;
        }

        .field-label {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 3px;
        }

        .field-value {
            border-bottom: 1px solid #000;
            padding: 5px 0;
            min-height: 25px;
        }

        .field-value-no-border {
            padding: 5px 0;
            min-height: 25px;
        }

        .problem-section {
            border: 1px solid #000;
            padding: 10px;
            margin: 15px 0;
            {{-- height: 200px; --}}
        }

        .engineer-remark-section {
            border: 1px solid #000;
            padding: 10px;
            margin: 15px 0;
        }

        .remark-content {
            margin-top: 8px;
            font-size: 10pt;
            line-height: 1.6;
        }

        .suggestion-box {
            {{-- background-color: #f0f0f0; --}}
            padding: 10px;
            margin-top: 10px;
            border-left: 3px solid #000;
            {{-- height: 200px; --}}
        }

        .parts-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .parts-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }

        .parts-table .header-cell {
            font-weight: bold;
            background-color: #f5f5f5;
        }

        .customer-remark-section {
            border: 1px solid #000;
            padding: 10px;
            margin: 15px 0;
            min-height: 60px;
        }

        .footer-section {
            margin-top: 20px;
            display: table;
            width: 100%;
        }

        .footer-item {
            display: table-cell;
            width: 33.33%;
            padding: 5px;
        }

        .footer-label {
            font-weight: bold;
            font-size: 10pt;
        }

        ul {
            margin-left: 20px;
            margin-top: 5px;
        }

        li {
            margin-bottom: 3px;
        }

        .parts_used_area {
            height: 150px;
        }

        .remark-space {
            height: 100px;
        }

        .table td {
            border: 1px solid black !important;
            padding: 5px;
            text-align: left;
        }

        .table {
            width: 100%;
            border-collapse: collapse !important;
            table-layout: fixed;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <table>
                <tbody>
                    <tr>

                        <td style="width: 20%;">
                            <div class="logo-section">
                               @php
                            if(auth('admin')->check()){
                                 $logo_file = \App\Models\BusinessSetting::where('key', 'logo')->first()?->value;
                                 $logo = asset('storage/business/') . '/' . $logo_file  ;
                                 $logo_dir = 'business/' ;
                            }else{
                                $logo_file = \App\CentralLogics\Helpers::get_store_data()->logo;
                            $logo =  asset('storage/store/') . '/' .  $logo_file; 
                            $logo_dir = 'store/';
                            }@endphp
                            <img width="100" class=""
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($logo_file, $logo, asset('public/assets/admin/img/160x160/img1.jpg'),$logo_dir) }}"
                                alt="Logo">
                            </div>
                        </td>
                        <td style="text-align: center;width: 80%;">
                            <div class="company-info">
                                <div class="report-title">SERVICE REPORT</div>
                                <div class="company-name">{{ auth('admin')->check()  ? \App\Models\BusinessSetting::where('key', 'business_name')->first()?->value : $data['store']->name }}</div>
                                <div class="address">{{ auth('admin')->check()  ? \App\Models\BusinessSetting::where('key', 'address')->first()?->value : $data['store']->address }}.
                                </div>
                                <div class="contact">Phone: {{ auth('admin')->check()  ? \App\Models\BusinessSetting::where('key', 'phone')->first()?->value :$data['store']->phone  }}. Email:
                                    {{ auth('admin')->check()  ? \App\Models\BusinessSetting::where('key', 'email_address')->first()?->value : $data['store']->email  }}</div>
                            </div>
                        </td>
                        <td style="width: 20%;text-align: right;">

                            <small>RECORD COPY</small>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Customer Details -->
        <table style="width: 100%; margin-bottom: 10px;">
            <tbody>
                <tr>
                    <td style="width: 50%; padding-right: 10px;">
                        <div class="field-label">Customer Details:-</div>
                        <div class="field-value-no-border">
                            @if (isset($data['client']))
                                {{ $data['client']->f_name }} <br>
                                {{ $data['client']->phone }}
                            @endif
                        </div>
                    </td>
                    <td style="width: 50%; padding-left: 10px; text-align: right;">
                        <table>
                            <tr>
                                <td>
                                    <div class="field-label">Task Id:-</div>
                                </td>
                                <td>
                                    <div class="field-value-no-border">
                                        @if ($data['task_id'])
                                            {{ $data['task_id'] }}
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="field-label">Task Title:-</div>
                                </td>
                                <td>
                                    <div class="field-value-no-border">
                                        @if ($data['task'])
                                            {{ $data['task']->title }}
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="field-label">Service Date:-</div>
                                </td>
                                <td>
                                    <div class="field-value-no-border">
                                            {{ $serviceReport->service_date ?? $data['task']->created_at }}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- <div class="customer-section">
            <div class="field-label">Address:-Tirupati</div>
            <div class="field-value"></div>
        </div> --}}

        <!-- Problem Identified -->
        <div class="problem-section">
            @if ($serviceReport->content)
                {!! processTableForMPDF($serviceReport->content) !!}
            @endif
        </div>

        <!-- Engineer's Remark -->
        <div class="engineer-remark-section">
            <div class="field-label">Engineer's Remark:-
            </div>

            <div class="suggestion-box">
                @if ($serviceReport->engineer_remark)
                    {!! processTableForMPDF($serviceReport->engineer_remark) !!}
                @endif
            </div>
        </div>

        <!-- Parts Table -->
        <table class="parts-table">
            <tr>
                <td class="header-cell" style="width: 50%;"><strong>Parts Used for Service:-</strong></td>
                <td class="header-cell" style="width: 50%;"><strong>Parts Recommended:-</strong></td>
            </tr>
            <tr>
                <td class="parts_used_area" style="vertical-align: top;">
                    @if ($serviceReport->parts_used)
                        {!! processTableForMPDF($serviceReport->parts_used) !!}
                    @endif
                </td>
                <td class="parts_used_area">
                    @if ($serviceReport->parts_recommended)
                        {!! processTableForMPDF($serviceReport->parts_recommended) !!}
                    @endif
                </td>
            </tr>
        </table>

        <!-- Customer Remark -->
        <div class="customer-remark-section">
            <div class="field-label">Customer Remark:-</div>
            <div class="remark-space">
                @if ($serviceReport->customer_remark)
                    {!! processTableForMPDF($serviceReport->customer_remark) !!}
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-section">
            <table>
                <tbody>
                    <tr>
                        <td style="width: 70%;">
                            <div class="footer-item">
                                <span class="footer-label">Service Engineer:- </span>
                                {{ isset($data['employee']) && $data['employee'] ? $data['employee']->f_name . ' ' . $data['employee']->l_name : 'N/A' }}
                            </div>
                        </td>
                        <td style="width: 30%;">
                            <div class="footer-item" style="text-align: center;">
                                <span class="footer-label">Date:- </span>{{ $serviceReport->created_at }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 70%;">
                            <div class="footer-item" style="text-align: right;">
                                <span class="footer-label">Engineer Sign:-</span>
                            </div>
                        </td>
                        <td style="width: 30%;">
                            <div class="footer-item" style="text-align: right;">
                                <span class="footer-label">Customer Sign:-</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
