@extends('front-views.layout')

@section('title', 'Checkout')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        #map {
            height: 300px;
            width: 100%;
        }

        .page_loader {
            height: 100vh;
            width: 100%;
            background: #1f1f1f;
            opacity: 0.8;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            z-index: 8;
        }

        .voucher_div .bg-light {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-radius: 10px;
            align-items: center;
        }
    </style>

    <script>
        function loadScript(src, callback) {
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.async = true;
            script.src = src;
            script.onload = callback;
            document.head.appendChild(script);
        }

        function initMap() {
            var initialLocation = {
                lat: @php echo session('latitude') @endphp,
                lng: @php echo session('longitude') @endphp
            };

            var map = new google.maps.Map(document.getElementById('map'), {
                zoom: 14,
                center: initialLocation,
                mapId: "b2c6179556df0b45"
            });

            const {
                AdvancedMarkerElement
            } = google.maps.marker;

            const markerElement = document.createElement("div");
            markerElement.innerHTML = `<img src="https://maps.gstatic.com/mapfiles/api-3/images/spotlight-poi2_hdpi.png" 
                                    style="width:30px; height:40px;">`;

            const marker = new AdvancedMarkerElement({
                position: initialLocation,
                map: map,
                title: "Your Location",
                content: markerElement
            });

            var geocoder = new google.maps.Geocoder();

            google.maps.event.addListener(map, 'click', function(event) {
                var lat = event.latLng.lat();
                var lng = event.latLng.lng();

                marker.position = event.latLng;

                updateLocation(lat, lng, geocoder);
            });

            function updateLocation(lat, lng, geocoder) {
                geocoder.geocode({
                    'location': {
                        lat: lat,
                        lng: lng
                    }
                }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        var address = results[0].formatted_address;
                        $('#address_field').val(address);
                        $('#address_hid').val(address);
                        $('#latitude').val(lat);
                        $('#longitude').val(lng);
                    } else {
                        window.alert('Geocoder failed: ' + status);
                    }
                });
            }
        }

        function init() {
            loadScript(
                "https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places,marker&callback=initMap&loading=async"
            );
        }

        window.onload = init;
    </script>


    <style>
        /* Main container */
        .main_cont {
            position: relative;
            width: 100%;
            /* height: 100vh; Adjust this as needed */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Loader overlay */
        .loader {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            /* Semi-transparent overlay */
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
            display: none;
        }

        /* Spinner animation */
        .loader::before {
            content: '';
            width: 40px;
            height: 40px;
            border: 4px solid #ffffff;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Spinner animation keyframes */
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@section('content')
    <div class="spacer" style="height: 63px;"></div>

    <div class="page_loader" style="display:none;">
        <img style="width: 300px;filter: hue-rotate(300deg);" src="{{ asset('storage/app/public/util/charlie-loader.gif') }}">
    </div>
    <!-- Single Page Header start -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Checkout</h1>
        <ol class="breadcrumb justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active text-white">Checkout</li>
        </ol>
    </div>
    <!-- Single Page Header End -->


    <!-- Checkout Page Start -->
    <div class="container-fluid py-5 main_cont">
        <div class="loader"></div>
        <div class="container py-5">
            <h1 class="mb-4">Deliver To</h1>
            <form action="{{ route('place-order') }}" class="placeOrder" method="post">
                @csrf
                <input type="hidden" name="coupon_code" id="coupon_code">

                <input type="hidden" name="store_id" id="store_id" value="{{ $store->id }}">


                <div class="row g-5">
                    <div class="col-md-12 col-lg-6 col-xl-7">

                        @foreach ($user_addresses as $key => $addr)
                            <div class="col-sm-12 my-2">
                                <div class="card">
                                    <label for="addr_{{ $addr->id }}" class="card-body cursor-pointer ">
                                        <input {{ !$key ? 'checked' : '' }} style="position: absolute; right:10px;"
                                            name="addr_card" class="addr_radio form-check-input bg-primary border-0 mx-1"
                                            type="radio" value="{{ $addr->id }}" data-id="{{ $key }}"
                                            id="addr_{{ $addr->id }}">
                                        <h5 class="card-title text-primary">{{ ucfirst($addr->address_type) }}</h5>
                                        <p class="card-text">{{ ucfirst($addr->address) }}</p>
                                        <div class="row">
                                            <input type="hidden" name="address[]" id="address"
                                                value="{{ $addr->address }}">
                                            <input type="hidden" name="longitude[]" value="{{ $addr->longitude }}">
                                            <input type="hidden" name="latitude[]" value="{{ $addr->latitude }}">
                                            <input type="hidden" name="contact_person_name[]" id="contact_person_name"
                                                value="{{ $addr->contact_person_name }}">
                                            <input type="hidden" name="contact_person_number[]" id="contact_person_number"
                                                value="{{ $addr->contact_person_number }}">
                                            <input type="text" value="{{ $addr->road }}" name="road[]"
                                                style="width:auto" class="col-3 form-control mx-1"
                                                placeholder="street number">
                                            <input type="text" value="{{ $addr->house }}" name="house[]"
                                                style="width:auto" class="col-3 form-control mx-1" placeholder="house">
                                            <input type="text" value="{{ $addr->floor }}" name="floor[]"
                                                style="width:auto" class="col-3 form-control mx-1" placeholder="floor">
                                        </div>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                        <input type="hidden" name="addr_id" id="addr_id_inp" value="0">

                        <p>
                            <a type="button" class=" text-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                + Add new address
                            </a>
                        </p>


                    </div>

                    <div class="col-md-12 col-lg-6 col-xl-5">

                        <div class="mb-5">
                            <div class="voucher_div">
                                <div class=" p-3 d-flex justify-content-between">
                                    <a data-bs-toggle="modal" class="btn btn-outline-primary w-100"
                                        data-bs-target="#couponModal" type="button">Add Voucher</a>
                                </div>
                            </div>
                        </div>
                        <a class="" href="{{ route('store.details', [_selectedCity() , $store->slug]) }}"> + Add more items </a>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Products</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Price</th>
                                        <th scope="col">Quantity</th>
                                        <th scope="col">Total</th>
                                        <th scope="col">Tax</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $item_total = 0;
                                        $cart_total = 0;
                                        $tax_amount = 0;
                                    @endphp
                                    @foreach ($cart as $ct)
                                        @php $variations = json_decode(json_decode($ct->variation, true), true) ;  @endphp
                                        @php
                                            $firstVr = !empty($variations) ? json_encode($variations[0]) : '';
                                            if ($firstVr) {
                                                $selling_price =  json_decode($firstVr)->price;
                                                $mrp = json_decode($firstVr)->mrpprice ?? json_decode($firstVr)->price;
                                                $tax = isset(json_decode($firstVr)->tax)
                                                    ? _taxPrice($selling_price, json_decode($firstVr)->tax)
                                                    : 0;
                                            } else {
                                                $selling_price = _discountedPrice(
                                                    $ct->item_price,
                                                    $ct->item_discount,
                                                    $ct->discount_type,
                                                );
                                                $mrp = $ct->item_price;
                                                $tax = 0;
                                        } @endphp
                                        @php
                                            $item_total += $mrp * $ct->quantity;
                                            $cart_total += $selling_price * $ct->quantity;
                                            $tax_amount += $tax * $ct->quantity;
                                        @endphp
                                        <tr>
                                            <th scope="row">
                                                <div class="d-flex align-items-center mt-2">
                                                    <a href="{{ route('product.details', [_selectedCity(), $ct->slug]) }}"> <img
                                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ct->image, asset('storage/app/public/product/') . '/' . $ct->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                            class="img-fluid rounded-circle"
                                                            style="width: 50px; height: 50px;" alt="{{ $ct->name }}"></a>
                                                </div>
                                            </th>
                                            @php $variations = json_decode(json_decode($ct->variation, true), true) ; @endphp
                                            <td class=""> <a
                                                    href="{{ route('product.details', [_selectedCity(), $ct->slug]) }}">{{ $ct->name }}
                                                    {{ empty($variations) ? '' : '(' . $variations[0]['type'] . ')' }}</a>
                                            </td>
                                            <td class="">
                                                {{ _price($selling_price) }}
                                                @if ($ct->item_discount)
                                                    <p class="text-danger text-decoration-line-through mb-0"
                                                        style="white-space: nowrap;">
                                                        {{ _price($mrp) }}</p>
                                                @endif
                                            </td>
                                            <td class="">{{ $ct->quantity }}</td>
                                            <td class="">
                                                {{ _price($ct->price) }}</td>

                                            <td class="">
                                                @if ($tax)
                                                    {{ $tax * $ct->quantity }}({{ !empty($variations) && isset($variations[0]['tax']) ? $variations[0]['tax'] . '%' : 0 }})
                                                @else 
                                                0
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th scope="row">Item Price</th>
                                    <td style="text-align: end;">
                                        {{ _price($item_total) }}</td>
                                </tr>

                                <tr>
                                    <th scope="row">Discount</th>
                                    <td style="text-align: end;">-
                                        {{ $item_total - $cart_total }}
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Subtotal</th>
                                    <td style="text-align: end;">
                                        {{ _price($cart_total) }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Tax Amount</th>
                                    <td style="text-align: end;">
                                        {{ \App\CentralLogics\Helpers::currency_symbol() }}{{ $tax_amount }}
                                    </td>
                                </tr>
                                <tr id="coupon_elem" style="display: none;">
                                    <th scope="row">Coupon Discount</th>
                                    <td style="text-align: end;">-
                                        {{ \App\CentralLogics\Helpers::currency_symbol() }}<span
                                            class="coupon_amount"></span></td>
                                </tr>
                                <tr>
                                    <th scope="row">Delivery Charge</th>
                                    <td style="text-align: end;">
                                        <span id="delivery_charge_amt">
                                            {{ \App\CentralLogics\Helpers::currency_symbol() }}
                                            <span class="charges_amount">
                                                @if (!$store->delivery)
                                                    0
                                                @elseif(count($user_addresses))
                                                    {{ _calcDeliveryCharge($user_addresses[0], $store, $user)['charges'] }}
                                                @endif
                                            </span>
                                            <span style="display: none;" id="delivery_charge_free"> </span>
                                        </span>
                                    </td>
                                </tr>
                                <tr> 
                                    <th scope="row">Rounded Off</th>
                                    <td style="text-align: end;">-{{ \App\CentralLogics\Helpers::currency_symbol() }}
                                        @php $deliver_charges = $store->delivery && count($user_addresses) ? _calcDeliveryCharge($user_addresses[0], $store, $user)['charges'] : 0 ; @endphp
                                       {{ \App\CentralLogics\Helpers::currency_symbol() }} <span
                                            id="total_amount">{{ _roundOff($cart_total + $tax_amount + $deliver_charges)['remaining_amount'] }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Total</th>
                                    @if (count($user_addresses))
                                        <td style="text-align: end;">{{ \App\CentralLogics\Helpers::currency_symbol() }}
                                            <span
                                                id="total_amount">{{ number_format(_roundOff($cart_total + $tax_amount + $deliver_charges)['final_amount'] ) }}</span>
                                        </td>
                                    @endif
                                </tr>
                                <input type="hidden" name="order_amount" id="order_amount"
                                    value="{{ floor($cart_total + $tax_amount + $deliver_charges) }}">
                            </tbody>
                        </table>

                        <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                            <div class="col-12 row">
                                @if ($store->delivery)
                                    <label for="Delivery-1" style="cursor:pointer"
                                        class="col-5 form-check text-start my-3 border rounded border-primary py-3 mx-1 px-1">
                                        <input type="radio" checked style="cursor:pointer"
                                            class="form-check-input bg-primary border-0 mx-1" id="Delivery-1"
                                            name="order_type" value="delivery">
                                        Home Delivery
                                    </label>
                                @endif
                                @if ($store->take_away)
                                    <label for="Delivery-2"
                                        class="col-5 form-check text-start my-3 border rounded border-primary py-3 px-1">
                                        <input type="radio" class="form-check-input bg-primary border-0 mx-1"
                                            {{ !$store->delivery ? 'checked' : '' }} id="Delivery-2" name="order_type"
                                            value="take_away">
                                        Take Away
                                    </label>
                                @endif
                            </div>
                        </div>

                        <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                            <div class="col-12">
                                <div class="form-check text-start my-3">
                                    <input checked type="radio" class="form-check-input bg-primary border-0"
                                        id="Delivery-1" name="payment_method" value="cash_on_delivery">
                                    <label class="form-check-label" for="Delivery-1">Cash On Delivery</label>
                                </div>
                                <div class="form-check text-start my-3">
                                    <input type="radio" class="form-check-input bg-primary border-0" id="Delivery-2"
                                        name="payment_method" value="digital_payment">
                                    <label class="form-check-label" for="Delivery-2">Razorpay</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 text-center align-items-center justify-content-center pt-4 ">
                            @if (count($user_addresses))
                                <button type="submit"
                                    class="place_order_btn btn border-secondary py-3 px-4 text-uppercase w-100 text-primary"
                                    style="{{ _calcDeliveryCharge($user_addresses[0], $store, $user)['error'] ? 'display: none;' : '' }}">Place
                                    Order</button>

                                <div class="alert alert-danger order_alert" role="alert"
                                    style="{{ !_calcDeliveryCharge($user_addresses[0], $store, $user)['error'] ? 'display: none;' : '' }}">
                                    {{ _calcDeliveryCharge($user_addresses[0], $store, $user)['msg'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="couponModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Availabel Vouchers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    @foreach ($coupondata as $cp)
                        <form action="{{ route('apply-coupon') }}" class="formSubmit couponForm" method="post">
                            <div class="row my-3">
                                <div class="col-6">
                                    <p class="mb-0">{{ $cp->title }}</p>
                                    <p class="mb-0"><b>Code : {{ $cp->code }} </b></p>
                                </div>
                                <input type="hidden" name="code" value="{{ $cp->code }}">
                                <input type="hidden" name="store_id" value="{{ $store->id }}">
                                <div class="col-6 justify-content-end d-flex align-items-end"><button type="submit"
                                        class="btn btn-primary">Use</button></div>
                            </div>
                        </form>
                    @endforeach
                </div>
                <div class="modal-footer">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form class=" addressForm" action="{{ route('add-new-address') }}" method="post">
                        @csrf
                        <div class="row mb-2">
                            <div class="rounded p-2 d-flex flex-column position-relative align-items-center"
                                style="height: fit-content;">
                                <div id="map"></div>
                                <!-- <p id="info"></p> -->
                            </div>

                            <div class="col-12 row">
                                <input type="hidden" name="latitude" id="latitude">
                                <input type="hidden" name="longitude" id="longitude">
                                <input type="hidden" name="address" id="address_hid">
                                <label for="Delivery-1" style="cursor:pointer"
                                    class="col-2 form-check text-start my-3 border rounded border-primary py-3 mx-1 px-1">
                                    <input type="radio" checked style="cursor:pointer"
                                        class="form-check-input bg-primary border-0 mx-1" id="Delivery-1"
                                        name="address_type" value="home">
                                    Home
                                </label>
                                <label for="Delivery-2" style="cursor:pointer"
                                    class="col-2 form-check text-start my-3 border rounded border-primary py-3  mx-1 px-1">
                                    <input type="radio" class="form-check-input bg-primary border-0 mx-1"
                                        id="Delivery-2" name="address_type" value="office">
                                    Work
                                </label>
                                <label for="Delivery-3" style="cursor:pointer"
                                    class="col-2 form-check text-start my-3 border rounded border-primary py-3  mx-1 px-1">
                                    <input type="radio" class="form-check-input bg-primary border-0 mx-1"
                                        id="Delivery-3" name="address_type" value="others">
                                    Other
                                </label>

                            </div>
                            <div class="col-md-12 col-lg-12">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Address<sup>*</sup></label>
                                    <input type="text" disabled name="" id="address_field" name="address"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Contact Person Name<sup>*</sup></label>
                                    <input type="text" name="contact_person_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Contact Person Mobile</label>
                                    <input name="contact_person_number" type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-12">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Street Number</label>
                                    <input name="road" type="text" placeholder="(Optional)" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">House</label>
                                    <input name="house" type="text" placeholder="(Optional)" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Floor</label>
                                    <input name="floor" type="text" placeholder="(Optional)" class="form-control">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </form>
                </div>
                <div class="modal-footer">
                </div>
            </div>
        </div>
    </div>
    <!-- Checkout Page End -->

@endsection

@push('script_2')
    <script>
        $(".placeOrder").on("submit", function(e) {
            e.preventDefault();
            $('.place_order_btn').text('Please wait...')
            $('.place_order_btn').attr('disabled', true)
            $('.page_loader').show()
            var formData = new FormData($(this)[0]);

            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
            });
            $.post({
                url: $(this).attr("action"),
                processData: false,
                contentType: false,
                async: false,
                cache: false,
                data: formData,
                beforeSend: function() {},
                success: function(data) {
                    if (data.errors && data.errors.length > 0) {
                        toasterNotification(data.errors[0].message);
                    } else {
                        if (data.payment_method == 'digital_payment') {
                            window.location.href = data.url;
                        } else {
                            toasterNotification(data.message);
                            var url = '{{ route('order-success', ['ID']) }}';
                            url = url.replace('ID', data.order_id);

                            setTimeout(() => {
                                window.location.href = url;
                            }, 500);
                        }
                    }
                    $('.page_loader').hide()
                    $('.place_order_btn').text('Place Order')
                    $('.place_order_btn').removeAttr('disabled')
                },
                complete: function(data) {
                    console.log(data.status);
                    if (data.status == 403) {
                        toasterNotification("Some error occured");
                    }
                },
            });
        });

        $('.order_type').on('change', function() {
            if ($(this).val() == 'take_away') {

            }
        })
        $('.addr_radio').on('change', function() {
            // console.log($(this).attr('data-id'))
            $('#addr_id_inp').val($(this).attr('data-id'))
            addr_id = $(this).val()
            $('.loader').show()
            //update delivery charges
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('get-delivery-charges') }}",
                data: {
                    addr_id: addr_id,
                    user_id: {{ $user->id }},
                    store_id: {{ $store->id }},
                    order_type: $('input[name="order_type"]:checked').val()
                },
                success: function(data) {
                    data = JSON.parse(data);
                    $(".charges_amount").text(data.charges)

                    if (data.error) {
                        $(".place_order_btn").hide()
                        $(".order_alert").text(data.msg)
                        $(".order_alert").show()
                        toasterNotification(data.msg);
                    } else {
                        $(".place_order_btn").show()
                        $(".order_alert").text('')
                        $(".order_alert").hide()
                    }
                },
                complete: function() {
                    $('.loader').hide()
                }
            });
        })
    </script>
@endpush
