@extends('front-views.layout')

@section('title', 'Cart')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <!-- Single Page Header start -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Cart</h1>
        <ol class="breadcrumb justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active text-white">Cart</li>
        </ol>
    </div>
    <!-- Single Page Header End -->


    <!-- Cart Page Start -->
    <div class="container-fluid py-5 cart-outer">

        <div
            class="container py-5 cart-inner {{ !count($cart) ? ' d-flex flex-column align-items-center justify-content-center' : '' }}">
            @if (!count($cart))
                <img style="width: 400px; " src="{{ asset('public/assets/front/img/empty-cart-yellow.png') }}"
                    alt="cart">
                <a href="{{ route('home') }}" class="btn btn-primary mt-3 text-light">Browse Products</a>
            @else
                <div class="table-responsive" style="    overflow-x: hidden;">
                    <table class="table">
                        <thead class="t_heade">
                            <tr>
                                <th scope="col">Products</th>
                                <th scope="col">Name</th>
                                <th scope="col">Price</th>
                                <th scope="col">Quantity</th>
                                <th scope="col total_head">Total (discounted)</th>
                                <th scope="col">Handle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $item_total = 0;
                                $cart_total = 0;
                            @endphp
                            @foreach ($cart as $ct)
                                @php $variations = json_decode(json_decode($ct->variation, true), true) ;  @endphp
                                @php $firstVr = !empty($variations) ? json_encode($variations[0]) : '';
                                    if ($firstVr) {
                                        $selling_price = json_decode($firstVr)->price;
                                        $mrp = json_decode($firstVr)->mrpprice ?? json_decode($firstVr)->price ;
                                    } else {
                                        $selling_price = $ct->item_price;
                                         $mrp = $ct->mrp_price;
                                } @endphp
                                @php
                                    $item_total +=  $mrp * $ct->quantity;
                                    $cart_total += $selling_price  * $ct->quantity;
                                @endphp

                                <tr>
                                    <th scope="row">
                                        <div class="d-flex align-items-center">
                                        <a href="{{ route('product.details', [$ct->cat_slug, $ct->slug]) }}">    <img data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ct->image, asset('storage/app/public/product/') . '/' . $ct->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                class="img-fluid me-5 rounded cart_img" alt="{{ $ct->name }}"> </a> 
                                        </div>
                                    </th>
                                    <td>
                                        <p class="mb-0 mt-4">{{ $ct->name }}
                                            {{ empty($variations) ? '' : '(' . $variations[0]['type'] . ')' }} </p>
                                    </td>
                                    <td>
                                        <p class="mb-0 mt-4">
                                           {{ _price($selling_price)}}
                                        </p>
                                        @if ($ct->item_discount)
                                            <p class="text-danger text-decoration-line-through mb-0">
                                                {{  _price($mrp)}}
                                            </p>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="input-group quantity mt-4" style="width: 100px;">
                                            <div class="input-group-btn">
                                                <button onclick="changeQty('decrease', {{ $ct->id }})"
                                                    class="btn btn-sm btn-minus rounded-circle bg-light border">
                                                    <i class="fa fa-minus"></i>
                                                </button>
                                            </div>
                                            <input type="text" style="pointer-events:none"
                                                class="current-quantity form-control form-control-sm text-center border-0"
                                                value="{{ $ct->quantity }}">
                                            <div class="input-group-btn">
                                                <button onclick="changeQty('increase', {{ $ct->id }})"
                                                    class="btn btn-sm btn-plus rounded-circle bg-light border">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pr_total">
                                        <p class="mb-0 mt-4">
                                            {{ _price($ct->price) }}</p>
                                    </td>
                                    <td>
                                        <button
                                            onclick="updateCart({{ $ct->item_id }}, 'remove','0', {{ $ct->id }})"
                                            class="btn btn-md rounded-circle bg-light border mt-4">
                                            <i class="fa fa-times text-danger"></i>
                                        </button>
                                    </td>

                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
                <div class="mt-5">
                    <a href="{{ route('store.details', [$cart[0]->store_slug]) }}"
                        class="btn border-secondary rounded-pill px-4 py-3 text-primary text-uppercase mb-4 ms-4"
                        type="button">+ Add more items</a>

                </div>
                <div class="row g-4 justify-content-end">
                    <div class="col-8"></div>
                    <div class="col-sm-8 col-md-7 col-lg-6 col-xl-4">
                        <div class="bg-light rounded">
                            <div class="p-4">
                                <h1 class="display-6 mb-4">Cart <span class="fw-normal">Total</span></h1>
                                <div class="d-flex justify-content-between mb-4">
                                    <h5 class="mb-0 me-4">Item Price:</h5>
                                    <p class="mb-0">{{ _price($item_total) }}</p>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <h5 class="mb-0 me-4">Discount</h5>
                                    <div class="">
                                        <p class="mb-0">-
                                            {{ _price($item_total - $cart_total) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="py-4 mb-4 border-top border-bottom d-flex justify-content-between">
                                <h5 class="mb-0 ps-4 me-4">Subtotal</h5>
                                <p class="mb-0 pe-4">{{ _price($cart_total) }}</p>
                            </div>
                            @if (auth('web')->user())
                                <a href="{{ route('checkout') }}"
                                    class="btn border-secondary rounded-pill px-4 py-3 text-primary text-uppercase mb-4 ms-4"
                                    type="button">Proceed Checkout</a>
                            @else
                                <a data-bs-toggle="modal" data-bs-target="#loginModal"
                                    class="btn border-secondary rounded-pill px-4 py-3 text-primary text-uppercase mb-4 ms-4"
                                    type="button">Proceed Checkout</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <!-- Cart Page End -->

@endsection



@push('script_2')
<script>
    function changeQty(action, cartId) {
        if ($('.current-quantity').val() == '1') {

        } else {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('change-cart-quantity') }}",
                data: {
                    action: action,
                    cartId: cartId
                },
                beforeSend: function() {},
                success: function(data) {
                    if (data.status) {
                        $('.cart-outer').load(window.location.href + ' .cart-inner')
                    }
                    toasterNotification(data.message)
                },
                complete: function() {}
            });
        }

    }

    function updateCart(prId, action, variation, cart_id) {
        if (action == 'add') {

            var url = "{{ route('add-to-cart') }}";
        } else {
            var url = "{{ route('remove-from-cart') }}";
        }
        console.log('proceed to add')
        return true;
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.post({
            url: url,
            data: {
                prId: prId,
                cart_id: cart_id,
                variation: variation
            },
            beforeSend: function() {
                $('#loading').show()
            },
            success: function(data) {
                console.log(data.firstvr)
                if (data.status) {
                    toasterNotification(data.message)

                    $('.cart-count-outer').load(window.location.href + ' .cart-count-inner')
                    $('.cart-outer').load(window.location.href + ' .cart-inner')
                    $('.phone-cart-outer').load(window.location.href + ' .phone-cart-inner')

                } else {
                    toasterNotification(data.message)
                }
            },
            complete: function() {
                $('#loading').hide()
            }
        });
    }
</script>
@endpush
