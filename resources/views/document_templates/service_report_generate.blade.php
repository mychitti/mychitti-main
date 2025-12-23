<style>
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
        {{-- background-color: #f0f0f0; --}} padding: 10px 0;
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


    .parts_used_area {
        height: 150px;
    }

    .remark-space {
        {{-- height: 100px; --}}
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

<div class="container">
    <!-- Header Section -->
    <div class="header">
        <table>
            <tbody>
                <tr>

                    <td style="width: 20%;">
                        <div class="logo-section">
                            @php
                                $data['store'] = \App\CentralLogics\Helpers::get_store_data();
                            $store_logo = $data['store']->logo; @endphp
                            <img width="100" class=""
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/store/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                alt="Logo">
                        </div>
                    </td>
                    <td style="text-align: center;width: 80%;">
                        <div class="company-info">
                            <div class="report-title">SERVICE REPORT</div>
                            <div class="company-name">{{ $data['store']->name }}</div>
                            <div class="address">{{ $data['store']->address }}.
                            </div>
                            <div class="contact">Phone: {{ $data['store']->phone }}. Email:
                                {{ $data['store']->email }}</div>
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
                        Client Details
                    </div>
                </td>
                <td style="width: 50%; padding-left: 10px; text-align: right;">
                    <table style="    float: right;">
                        <tr>
                            <td>
                                <div class="field-label">Task Id:-</div>
                            </td>
                            <td>
                                <div class="field-value-no-border">
                                    Task Id
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="field-label">Task Title:-</div>
                            </td>
                            <td>
                                <div class="field-value-no-border">
                                    Task Title
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="field-label">Service Date:-</div> 
                            </td>
                            <td>
                                <div class="field-value-no-border">
                                    <input type="date" class="form-control" name="service_date" value="{{ date('Y-m-d') }}"
                                        id="">
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
        {{-- TEXT EDITOR --}}
        <textarea placeholder="Start Typing ..." class="form-control ck_editor" id="main_content" name="content"></textarea>

    </div>

    <!-- Engineer's Remark -->
    <div class="engineer-remark-section">
        <div class="field-label">Engineer's Remark:-
        </div>

        <div class="suggestion-box">
            <div class="suggestion-space">
                {{-- TEXT EDITOR --}}
                <textarea placeholder="Start Typing ..." class="form-control ck_editor" id="engineer_remark" name="engineer_remark"></textarea>
            </div>
        </div>
    </div>

    <!-- Parts Table -->
    <table class="parts-table">
        <tr>
            <td class="header-cell" style="width: 50%;"><strong>Parts Used for Service:-</strong>
            </td>
            <td class="header-cell" style="width: 50%;"><strong>Parts Recommended:-</strong>
            </td>
        </tr>
        <tr>
            <td class="parts_used_area" style="vertical-align: top;">
                <textarea placeholder="Start Typing ..." class="form-control ck_editor" id="parts_used" name="parts_used"></textarea>
            </td>
            <td class="parts_used_area">
                <textarea placeholder="Start Typing ..." class="form-control ck_editor" id="parts_recommended" name="parts_recommended"></textarea>
            </td>
        </tr>
    </table>

    <!-- Customer Remark -->
    <div class="customer-remark-section">
        <div class="field-label">Customer Remark:-</div>
        <div class="remark-space">
            {{-- TEXT EDITOR --}}
            <textarea placeholder="Start Typing ..." class="form-control ck_editor" id="customer_remark" name="customer_remark"></textarea>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer-section">
        <div class="footer-item">
            <span class="footer-label">Service Engineer:- </span>
            Engineer Name
        </div>
        <div class="footer-item" style="text-align: center;">
            <span class="footer-label">Date:- </span>{{ date('Y-m-d') }}
        </div>
        <div class="footer-item" style="text-align: right;">
            <span class="footer-label">Customer Sign:-</span>
        </div>
        <div class="footer-item" style="text-align: right;">
            <span class="footer-label">Engineer Sign:-</span>
        </div>
    </div>
</div>
