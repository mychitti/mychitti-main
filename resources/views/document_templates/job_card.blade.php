<style>
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 12px;
        color: #222;
        margin: 0;
        padding: 0;
    }

    .approve_btn {
        background-color: #ffb74bff;
        color: black;
        text-decoration: none;
        font-size: 14px;
        padding: 12px 40px;
        border: 1px solid #ffb74bff;
    }

    .container {
        {{-- border: 1px solid #ddd; --}} {{-- padding: 10px; --}}
    }

    .title {
        background-color: #fff8dc;
        /* light yellow */
        color: #8b4513;
        /* saddle brown */
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        padding: 6px 0;
        margin-bottom: 10px;
        text-transform: uppercase;
        border-bottom: 1px solid #ccc;
    }

    .section h3 {
        background-color: #fff7d4;
        color: #a0522d;
        font-size: 13px;
        padding: 4px;
        margin-bottom: 5px;
        border-left: 4px solid #d4a017;
    }

    .info-group {
        margin-bottom: 10px !important;
    }

    .info-label {
        font-weight: bold;
        display: inline-block;
        width: 100px;
    }

    .row {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .col-50 {
        width: 49%;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        margin-top: 5px;
    }

    table th {
        background-color: #ffe899;
        color: #5c3d00;
        padding: 4px;
        border: 1px solid #bbb;
    }

    table td {
        padding: 4px;
        border: 1px solid #ccc;
    }

    .checklist span,
    .payment-options span {
        display: inline-block;
        margin-right: 10px;
        margin-bottom: 3px;
    }

    .charges td:first-child {
        width: 70%;
    }

    .charges td {
        padding: 3px;
    }

    .border-0 td {
        padding: 10px 0;
        border: 0 !important;
    }
</style>

<div class="container">
    <div class="title">Job Card</div>

    <div class="section">
        <table style="border:0px; ">
            <tr>
                <td style="border:0px; ">
                    @if (isset($data['store']))
                        <p class="info-group"><span class="info-label">Company Name:</span> {{ $data['store']->name }}</p>
                        <p class="info-group"><span class="info-label">Address:</span> {{ $data['store']->address }}</p>
                        <p class="info-group"><span class="info-label">Phone:</span> {{ $data['store']->phone }}</p>
                        <p class="info-group"><span class="info-label">Email:</span> {{ $data['store']->email }}</p>
                        <p class="info-group"><span class="info-label">GSTIN:</span>
                            {{ ($gst = json_decode($data['store']->gst, true)) && isset($gst['code']) ? $gst['code'] : '' }}
                        </p>
                    @endif
                </td>
                <td style="border:0px; ">
                    @if ($data['task_id'])
                        <div class="info-group"><span class="info-label">Task Id:</span> {{ $data['task_id'] }}
                        </div>
                    @endif
                    <div class="info-group"><span class="info-label">Job Card No.:</span> {{ $data['job_card_number'] }}
                    </div>
                    <div class="info-group"><span class="info-label">Date:</span> {{ date('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Customer Details</h3>
        @if (isset($data['client']))
            <div class="info-group"><span class="info-label">Name:</span> {{ $data['client']->f_name }}</div>
            <div class="info-group"><span class="info-label">Phone:</span> {{ $data['client']->phone }}</div>
            <div class="info-group"><span class="info-label">Address:</span> {{ $data['client']->address }}</div>
        @endif
    </div>


    <div class="section">
        <h3>Job / Service Details</h3>
        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <?php foreach ($data['columns'] as $key => $value): ?>
                    <th><?= ucfirst($key) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <?php foreach ($data['columns'] as $key => $value): ?>
                    <td>{{ $value }}</td>
                    @endforeach
                </tr>

            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Service Action</h3>
        <div class="checklist">

        </div>
        <div class="info-group"><span class="info-label">Work Done By:</span>
            {{ isset($data['employee']) && $data['employee'] ? $data['employee']->f_name . ' ' . $data['employee']->l_name : 'N/A' }}
        </div>
        <div class="info-group"><span class="info-label">Spare Parts Used:</span>
            @foreach ($data['items'] as $key => $value)
                <div>{{ $value['name'] }} ( Unit Price : {{ _price($value['unitprice']) }}, Tax: {{ $value['tax'] }}%,
                    Qty:{{ $value['quantity'] }})</div>
            @endforeach
        </div>
    </div>

    <div class="section">
        <h3>Charges</h3>
        <table class="charges">
            @foreach ($data['charges'] as $key => $value)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                    <td>{{ _price($value) }}</td>
                </tr>
            @endforeach
            <tr>
                <td>Discount</td>
                <td>{{ _price($data['discount']) }}</td>
            </tr>
            <tr>
                <td><strong>Total</strong></td>
                <td><strong>{{ _price($data['total_amount']) }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Payment</h3>
        <div class="payment-options">
            <b>Payment Method:</b>
            {{ ucfirst($data['payment_method']) }}

        </div>
        <br>
        <div class="payment-options">
            <b>Payment Amount</b>
            ₹{{ $data['total_amount'] }}
        </div>
        <br>
        <div class="payment-options">
            <b>Balance Due</b>
            ₹0
        </div>
    </div>
    <div class="section">
        <h3>Customer Acknowledgement</h3>
        <table style="width:100%;" class="info-table border-0">
            <tr>
                <td>
                    <div class="payment-options">
                        <p>I confirm that the service/repair work has been completed satisfactorily</p>
                        </p>
                    </div>
                </td>
                <td>
                    <div class="payment-options">
                        <p>Signature: _____________________</p>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="payment-options">
                        <p>Date: {{ date('d/m/Y') }}</p>
                        </p>
                    </div>
                </td>
                <td align="right">
                    @if ($data['status'])
                        <span style="color:green">Approved</span>
                    @else
                        @if (isset($data['job_id']) && $data['job_id'])
                            <a class="approve_btn" href="{{ route('job-card.approve', [$data['job_id']]) }}">
                                &nbsp; &nbsp; Approve &nbsp;&nbsp;
                            </a>
                        @else
                            <a class="approve_btn" href="#">
                                &nbsp; &nbsp; Approve &nbsp;&nbsp;
                            </a>
                        @endif
                    @endif

                </td>
            </tr>
        </table>
    </div>
    <div class="section">
        <h3>Technician Details</h3>
        @if (isset($data['employee']))
            <div class="section">
                <div class="info-group"><span class="info-label">Technician Name:</span>
                    {{ $data['employee']->f_name . ' ' . $data['employee']->l_name }}</div>
                <div class="info-group"><span class="info-label">Phone:</span> {{ $data['employee']->phone }}</div>
                <div class="info-group"><span class="info-label">Signature:</span> {{ $data['employee']->address }}
                </div>
            </div>
        @endif
    </div>
</div>
