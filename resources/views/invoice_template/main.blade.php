<!DOCTYPE html>
<html lang="zxx">

<head>
    <title>Invoice</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- External CSS libraries -->
    <link type="text/css" rel="stylesheet" href="{{ asset('public/assets/invoice') }}/css/bootstrap.min.css">
    <link type="text/css" rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

    <!-- Favicon icon -->
    <link rel="shortcut icon" href="{{ asset('public/assets/invoice') }}/img/favicon.ico" type="image/x-icon">

    <!-- Google fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- Custom Stylesheet -->
    <link type="text/css" rel="stylesheet" href="{{ asset('public/assets/invoice') }}/css/style.css">

</head>

<body>
    <!-- Invoice 8 start -->
    <div class="invoice-8 invoice-content">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="invoice-btn-section clearfix d-print-none">

                        <a href="javascript:window.print()" class="btn btn-lg btn-print">
                            <i class="fa fa-print"></i> Print
                        </a>
                    </div>
                    <div class="invoice-inner clearfix">
                        <div class="invoice-info clearfix" id="invoice_wrapper">

                            <div class="invoice-center ">
                                <div class="order-summary">
                                    <div class="table-outer">
                                        <table class="default-table invoice-table  table-borderless">
                                            <tbody>
                                                <tr>
                                                    <td style="width: 50%;">
                                                        <div class="">
                                                            <h1 class="invoice-name">Invoice</h1>
                                                        </div>
                                                    </td>
                                                    <td style="width: 50%; text-align:right; ">
                                                        <div class="">
                                                            <div class="invoice-number-inner">
                                                                <h2 class="name">Invoice No: #<span
                                                                        id="invoice_number">{{ $bill_data['invoice_number'] }}</span>
                                                                </h2>
                                                                <p class="mb-0">Invoice Date:
                                                                    <span>{{ $bill_data['invoice_date'] }}</span></p>
                                                            </div>
                                                        </div>
                                                    </td>

                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="col-sm-6 mb-30">
                                                            <div class="invoice-number">
                                                                <h4 class="inv-title-1">Invoice To</h4>
                                                                <h2 class="name mb-10">{{ $bill_to['full_name'] }}</h2>
                                                                <p class="invo-addr-1 mb-0">
                                                                    Phone : {{ $bill_to['phone'] }}<br />
                                                                    Address : {{ $bill_to['address'] }}<br />
                                                                    {!! $bill_to['gst'] ? 'GST : ' . $bill_to['gst'] . '<br />' : '' !!}
                                                                    Place of supply : {{ $bill_to['place_of_supply'] }}<br />
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td style=" text-align:right;">
                                                        <div class="invoice-number">
                                                            <div class="invoice-number-inner">
                                                                <h4 class="inv-title-1">Invoice From</h4>
                                                                    <h2 class="name mb-10">
                                                                        {{ $bill_from['name'] }}</h2>
                                                                    <p class="invo-addr-1 mb-0">
                                                                            <b>GST :
                                                                            </b>{{ $bill_from['gst'] }}
                                                                            <br />
                                                                      
                                                                        {{ $bill_from['phone'] }} <br />
                                                                        {{ $bill_from['email'] }} <br />
                                                                        {{ $bill_from['address'] }} <br />
                                                                    </p>
                                                              
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="invoice-center border-shadow-bg">
                                <div class="order-summary">
                                    <div class="table-outer">
                                        <table class="default-table invoice-table table_1">
                                            <thead>
                                                <tr>
                                                    <th>Description</th>
                                                    <th>Unit Price </th>
                                                    <th>Qty</th>
                                                    <th>TAX</th>

                                                    <th>HSN</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>

                                            <tbody>

                                                @php $totalPrice = 0;
                                                $totalTaxAmount = 0; @endphp
                                                @foreach ($bill_data['invoice_items'] as $qt)
                                                    @php $totalPrice += _taxIncludedPrice(
                                                            $qt->price * $qt->qty,
                                                            $qt->tax,
                                                        );

                                                    $totalTaxAmount += _taxPrice($qt->price * $qt->qty, $qt->tax); @endphp
                                                    <tr>
                                                        <td>{{ $qt->name }}</td>
                                                        <td>{{ \App\CentralLogics\Helpers::currency_symbol() . number_format($qt->price,3) }}
                                                        </td>
                                                        <td>{{ $qt->qty }}</td>
                                                        <td>{{ $qt->tax }}%</td>
                                                        <td>{{ $qt->hsn }}</td>
                                                        <td>{{ \App\CentralLogics\Helpers::currency_symbol() . number_format(_taxIncludedPrice($qt->price * $qt->qty, $qt->tax),3) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <table class="default-table invoice-table borderless_table">
                                            <tbody>
                                                <tr>
                                                    <td style="width: 70%;"><strong>Total Due</strong></td>

                                                    <td style="text-align: right; width: 30%;">
                                                        <strong>{{ \App\CentralLogics\Helpers::currency_symbol() . number_format($totalPrice,3) }}</strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="width: 70%;"><strong>GST Amount Total</strong></td>

                                                    <td style="text-align: right; width: 30%;">
                                                        <strong>{{ \App\CentralLogics\Helpers::currency_symbol() . number_format($totalTaxAmount,3) }}</strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="width: 70%;"><strong>Amount In Words</strong></td>

                                                    <td style="text-align: right; width: 30%;">
                                                        <strong>{{ ucwords(_convertNumberToWords($totalPrice)) }}</strong>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="invoice-bottom border-shadow-bg bsb2 p-0 ">
                                <div class="row">
                                    <div class="col-lg-7 col-md-7 col-sm-7 p-0">
                                        <div class="payment-method mb-30">
                                            <h3 class="inv-title-1 mb-10">Payment Method</h3>
                                            <ul class="payment-method-list-1 text-14">
                                                <li><strong>Cash On Delivery</strong></li>
                                                @if (!empty($existingInvoice) && !empty($existingInvoice[0]) && $existingInvoice[0]->payment_status == 'Paid')
                                                    <li><strong class="text-success payment_status">Paid</strong></li>
                                                @else
                                                    <li><strong class="text-danger payment_status">Unpaid</strong></li>
                                                @endif
                                            </ul>
                                        </div>
                                        <div class="terms-conditions mb-30">
                                            <h3 class="inv-title-1 mb-10">Thank you for purchasing our services</h3>
                                            <p>No Guarantee. No Warranty.</p>
                                            <a target="_blank"
                                                href="{{ isset($vendor_contact_det) ? route('vendor-terms-conditions', [$vendor_contact_det->id]) : route('user-terms-and-conditions') }}">Terms
                                                and Conditions</a>

                                        </div>
                                    </div>
                                    <div class="col-lg-5 col-md-5 col-sm-5">

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Invoice 8 end -->

    <script src="{{ asset('public/assets/invoice') }}/js/jquery.min.js"></script>
    <script src="{{ asset('public/assets/invoice') }}/js/jspdf.min.js"></script>
    <script src="{{ asset('public/assets/invoice') }}/js/html2canvas.js"></script>
    <script src="{{ asset('public/assets/invoice') }}/js/app.js"></script>
    <script>
        $("#pmnt_stts_btn").on('click', function() {
            $('.payment_status').text('Paid')
            $('.payment_status').addClass('text-success')
            $('.payment_status').removeClass('text-danger')
            toasterNotification('Payment status changed successfully');
            saveInvoice('Paid')
        })

      

        function toasterNotification(msg) {
            $("#toast").text(msg);
            $("#toast").addClass("show");
            setTimeout(function() {
                $("#toast").removeClass("show");
            }, 3000);
        }
    </script>
</body>

</html>
