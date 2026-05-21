<!DOCTYPE html>
<html lang="zxx">

<head>
    <title>Invoice</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- External CSS libraries -->
    <link type="text/css" rel="stylesheet" href="{{asset('public/assets/invoice')}}/css/bootstrap.min.css">
    <link type="text/css" rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

    <!-- Favicon icon -->
    <link rel="shortcut icon" href="{{asset('public/assets/invoice')}}/img/favicon.ico" type="image/x-icon">

    <!-- Google fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Custom Stylesheet -->
    <link type="text/css" rel="stylesheet" href="{{asset('public/assets/invoice')}}/css/style.css">

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
                        <!-- <a id="invoice_download_btn" class="btn btn-lg btn-download btn-theme">
                            <i class="fa fa-download"></i> Download Invoice
                        </a> -->
                    </div>
                    <div class="invoice-inner clearfix">
                        <div class="invoice-info clearfix" id="invoice_wrapper">
                       
                            <div class="invoice-center ">
                                <div class="order-summary">
                                    <div class="table-outer">
                                        <table class="default-table invoice-table  table-borderless">
                                            <tbody>
                                                <tr>
                                                    <!-- <td>
                                                        <div class="invoice-logo">
                                                            <div class="">
                                                                @php ($store_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value) @endphp
                                                                <img style="width: 150px;margin-right:250px" class="navbar-brand-logo initial--36 onerror-image onerror-image" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" src="{{\App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/business/').'/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg') ,'business/' )}}" alt="Logo">
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="invoice-contact-us">
                                                            <h1>Contact Us</h1>
                                                            <ul class="link">
                                                                <li>
                                                                    <i class="fa fa-map-marker"></i>{{_footerInfo('address')}}
                                                                </li>
                                                                <li>
                                                                    <i class="fa fa-envelope"></i> <a href="mailto:{{_footerInfo('email_address')}}">{{_footerInfo('email_address')}}</a>
                                                                </li>
                                                                <li>
                                                                    <i class="fa fa-phone"></i> <a href="tel:{{_footerInfo('phone')}}">{{_footerInfo('phone')}}</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td> -->

                                                </tr>

                                                <tr>
                                                    <td style="width: 50%;">
                                                        <div class="">
                                                            <h1 class="invoice-name">Invoice</h1>
                                                        </div>
                                                    </td>
                                                    <td style="width: 50%; text-align:right; ">
                                                        <div class="">
                                                            <div class="invoice-number-inner">
                                                                <h2 class="name">Invoice No: #<span id="invoice_number">{{$invoice_id}}</span></h2>
                                                                <p class="mb-0">Invoice Date: <span>{{date('d M Y')}}</span></p>
                                                            </div>
                                                        </div>
                                                    </td>

                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="col-sm-6 mb-30">
                                                            <div class="invoice-number">
                                                                <h4 class="inv-title-1">Invoice To</h4>
                                                                <h2 class="name mb-10">{{$service_det->name? $service_det->name : $service_det->f_name . ' ' . $service_det->l_name}}</h2>
                                                                <p class="invo-addr-1 mb-0">
                                                                    Phone : {{$service_det->phone}}<br />
                                                                    <!-- {{ $service_det->address ? "Address :  " .$service_det->address."<br />" : ''}} -->
                                                                    Address : {{$addr['address']}}<br /> 
                                                                
                                                                   {!! $service_det->gst_number ? 'GST : ' . $service_det->gst_number . '<br />' : ( $service_det->gst ? 'GST : '. json_decode($service_det->gst)->code . '<br>': '') !!}
                                                                
                                                                    
                                                                    Place of supply : {{$addr['city']}}<br />
                                                                    <!-- {{$service_det->email}}<br /> -->
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td style=" text-align:right;">
                                                        <div class="invoice-number">
                                                            <div class="invoice-number-inner">
                                                                <h4 class="inv-title-1">Invoice From</h4>
                                                                @if(isset($vendor_contact_det) && $vendor_contact_det)
                                                                <h2 class="name mb-10">{{$vendor_contact_det->name}}</h2>
                                                                <p class="invo-addr-1 mb-0">
                                                                @if($vendor_contact_det->gst_number)
                                                                <b>GST : </b>{{$vendor_contact_det->gst_number}} <br />
                                                                @endif
                                                                    {{$vendor_contact_det->phone}} <br />
                                                                    {{$vendor_contact_det->email}} <br />
                                                                    {{$vendor_contact_det->address}} <br />
                                                                </p>
                                                                @else
                                                                <h2 class="name mb-10">{{\App\Models\BusinessSetting::where('key', 'business_name')->first()->value}}</h2>
                                                                <p class="invo-addr-1 mb-0">
                                                                    <b>GST : </b>{{ \App\Models\BusinessSetting::where('key', 'gst_number')->first()->value }} <br />
                                                                    {{ \App\Models\BusinessSetting::where('key', 'phone')->first()->value }} <br />
                                                                    {{ \App\Models\BusinessSetting::where('key', 'email_address')->first()->value  }} <br />
                                                                    {{ \App\Models\BusinessSetting::where('key', 'address')->first()->value }} <br />
                                                                </p>
                                                                @endif
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
                                                $totalTaxAmount = 0 ; @endphp
                                                @foreach($quotations as $qt)
                                                @php $totalPrice += _taxIncludedPrice($qt->price * $qt->qty, $qt->tax ) ;

                                                $totalTaxAmount += _taxPrice($qt->price* $qt->qty, $qt->tax); @endphp
                                                <tr>
                                                    <td>{{$qt->name}}</td>
                                                    <td>{{ \App\CentralLogics\Helpers::currency_symbol(). number_format($qt->price  )}}</td>
                                                    <td>{{$qt->qty}}</td>
                                                    <td>{{$qt->tax}}%</td>
                                                    <td>{{$qt->hsn}}</td>
                                                    <td>{{ \App\CentralLogics\Helpers::currency_symbol(). number_format(_taxIncludedPrice($qt->price * $qt->qty, $qt->tax )) }}</td>
                                                </tr>
                                                @endforeach
                                                </tbody>
                                        </table>
                                        <table class="default-table invoice-table borderless_table">
                                            <tbody>
                                                <tr>
                                                    <td style="width: 70%;" ><strong>Total Due</strong></td>
                                                
                                                    <td style="text-align: right; width: 30%;"><strong>{{ \App\CentralLogics\Helpers::currency_symbol(). number_format($totalPrice)}}</strong></td>
                                                </tr>
                                                <tr >
                                                    <td style="width: 70%;"><strong>GST Amount Total</strong></td>
                                                
                                                    <td style="text-align: right; width: 30%;"><strong>{{ \App\CentralLogics\Helpers::currency_symbol(). number_format($totalTaxAmount)}}</strong></td>
                                                </tr>
                                                <tr > 
                                                    <td style="width: 70%;"><strong>Amount In Words</strong></td>
                                                  
                                                    <td style="text-align: right; width: 30%;"><strong>{{ucwords(_convertNumberToWords($totalPrice))}}</strong></td>
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
                                                @if(!empty($existingInvoice) && !empty($existingInvoice[0]) && $existingInvoice[0]->payment_status == 'Paid')
                                                <li><strong class="text-success payment_status">Paid</strong></li>
                                                @else
                                                <li><strong class="text-danger payment_status">Unpaid</strong></li>
                                                @endif
                                            </ul>
                                        </div>
                                        <div class="terms-conditions mb-30">
                                            <h3 class="inv-title-1 mb-10">Thank you for purchasing our services</h3>
                                            <p>No Guarantee. No Warranty.</p>
                                            <a target="_blank" href="{{isset($vendor_contact_det)? route('vendor-terms-conditions', [$vendor_contact_det->id]) : route('user-terms-and-conditions')}}">Terms and Conditions</a>

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

    <script src="{{asset('public/assets/invoice')}}/js/jquery.min.js"></script>
    <script src="{{asset('public/assets/invoice')}}/js/jspdf.min.js"></script>
    <script src="{{asset('public/assets/invoice')}}/js/html2canvas.js"></script>
    <script src="{{asset('public/assets/invoice')}}/js/app.js"></script>
    <script>
        $("#pmnt_stts_btn").on('click', function() {
            $('.payment_status').text('Paid')
            $('.payment_status').addClass('text-success')
            $('.payment_status').removeClass('text-danger')
            toasterNotification('Payment status changed successfully');
            saveInvoice('Paid')
        })

        function saveInvoice(stts = null) {
            var invoiceNum = $('#invoice_number').text();
            var service_id = "{{$invoice_id}}"

            if (stts != null) {
                payment_stts = stts
            } else {
                payment_stts = $('.payment_status').text()
            }

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{route('vendor.invoice.save-invoice')}}",
                data: {
                    service_id: service_id,
                    invoiceNum: invoiceNum,
                    payment_stts: payment_stts
                },
                success: function(data) {
                    if (data.status) {
                        $('#save_btn').text('Saved');
                    }
                    toasterNotification(data.message);
                },
            });

        }

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