<div class="" id="services" aria-labelledby="pills-category-tab">
    <div class="section_spacing">

        <div class="tab-class text-center">
            {{-- <div class="row g-4" style=" --bs-gutter-x: 0rem !important;">
                                    <div class="col-lg-8 text-start">
                                        <ul class="nav nav-pills d-inline-flex text-center ">
                                            @foreach ($productdata as $key => $cat)
                                                <li class="nav-item">
                                                    <a class="d-flex m-2 p-2 bg-light rounded-pill {{ !$key ? 'active' : '' }}"
                                                        data-bs-toggle="pill" href="#tab--{{ $key }}">
                                                        <span class="text-dark"
                                                            style="white-space: nowrap;">{{ $cat->name }}</span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div> --}}
            <div class="tab-content">
                @foreach ($invItemdata as $key => $cat)
                    <a class="d-flex m-2 p-2 bg-warning rounded-pill active" style="width:fit-content;"
                        data-bs-toggle="pill">
                        <span class="text-dark" style="white-space: nowrap;">{{ $cat->name }}</span>
                    </a>
                    <div id="tab--{{ $key }}" class="tab-pane p-0   show active">
                        <div class="row g-0">
                            <div class="col-lg-12">
                                <div class="row g-3">
                                    @foreach ($cat->items as $pro)
                                        @php
                                            $variations = json_decode($pro->variations);
                                            $firstVr = !empty($variations) ? json_encode($variations[0]) : '';
                                            if ($firstVr) {
                                                $selling_price = json_decode($firstVr)->price;
                                                $mrp = json_decode($firstVr)->mrpprice ?? json_decode($firstVr)->price;
                                            } else {
                                                $selling_price = $pro->price;
                                                $mrp = $pro->mrp_price;
                                            }
                                        @endphp
                                        <div class="pr_{{ $pro->id }} col-md-4 col-lg-3 col-xl-2 col-sm-4 product_card"
                                            style="    max-width: 264px !important;">
                                            <div class="rounded position-relative fruite-item">

                                                <div class="fruite-img" style="height: 200px !important;">
                                                    <a
                                                        href="{{ route('product.details', [$cat->cat_slug, $pro->slug]) }}">
                                                        <img loading="lazy"
                                                            style="height: 200px !important;object-fit:cover"
                                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                            class="img-fluid w-100 rounded-top" alt=""></a>
                                                </div>

                                                @if ($module == 5)
                                                    <span class="badge rounded-pill bg-light text-dark time_badge"><i
                                                            class="fas fa-fire text-secondary"></i>
                                                        {{ strtoupper($store->delivery_time) }}
                                                    </span>
                                                @endif

                                                @if ($pro->discount > 0)
                                                    <div class="discount_badge">
                                                        <span class="">
                                                            {{ floor($pro->discount) }}{{ $pro->discount_type == 'percent' ? '% OFF' : \App\CentralLogics\Helpers::currency_symbol() . ' OFF' }}<span>
                                                    </div>
                                                @endif

                                                <div onclick="wishlist({{ $pro->id }}, '{{ _itemExistInWishlist($pro->id) ? 'remove' : 'add' }}')"
                                                    class="prHeart_{{ $pro->id }}  p-1 rounded  position-absolute"
                                                    style="top: 10px; right: 10px;cursor:pointer"><i
                                                        class="fa fa-heart  heart_{{ $pro->id }} {{ _itemExistInWishlist($pro->id) ? 'text_red' : 'text_grey' }} fs-4"></i>
                                                </div>

                                                <div class="p-2 border border-top-0 rounded-bottom text-start">

                                                    <a
                                                        href="{{ route('product.details', [$cat->cat_slug, $pro->slug]) }}">
                                                        <h4 class="one-line-ellipsis text-start product_name"
                                                            data-id="pr_{{ $pro->id }}"
                                                            title="{{ ucfirst($pro->name) }}" style="font-size: 18px;">
                                                            {{ ucfirst($pro->name) }}</h4>
                                                    </a>
                                                    @if ($module == 5)
                                                        <p class="one-line-ellipsis"
                                                            style="min-height:25px ;font-size: 12px;text-align: start; color:#616161; margin-bottom:5px;letter-spacing: 1px;">
                                                            {{ !empty($variations) ? $variations[0]->type : '' }}
                                                        </p>
                                                        @if (count($variations) > 1)
                                                            <span style="font-size: 9px;"
                                                                class="badge badge-pill badge-light text-dark border">+{{ count($variations) - 1 }}
                                                                more option(s)</span>
                                                        @endif

                                                        <div
                                                            class="d-flex justify-content-between flex-lg-wrap  align-items-center">
                                                            <div class="">
                                                                <p class="text-dark fs-5 fw-bold mb-0">
                                                                    {{ _price($selling_price) }}
                                                                </p>

                                                                <p class="text-danger text-decoration-line-through mb-0 text-start"
                                                                    style="    min-height: 24px;">
                                                                    @if ($pro->discount > 0)
                                                                        {{ _price($mrp) }}
                                                                    @endif
                                                                </p>
                                                            </div>

                                                            {{-- @if (_isStoreActive($pro->id)) --}}
                                                            <div class="cart-section cartSec_{{ $pro->id }}">
                                                                @php $firstVr = !empty($variations) ? json_encode($variations[0]) : "" @endphp

                                                                @if (_itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')))
                                                                    <button
                                                                        onclick="updateCart({{ $pro->id }}, 'remove','{{ !empty($variations) ? 0 : '' }}',  {{ _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']')) }})"
                                                                        class="btn border border-secondary rounded p-1 px-2 text-primary fs-5"><i
                                                                            class="fa fa-times me-2 text-primary"></i>Remove</button>
                                                                @else
                                                                    <button
                                                                        onclick="updateCart({{ $pro->id }}, 'add','{{ !empty($variations) ? 0 : '' }}',  '')"
                                                                        class="btn border border-secondary rounded p-1 px-2 text-primary fs-5">
                                                                        <i
                                                                            class="fa fa-shopping-bag me-2 text-primary"></i>
                                                                        Add</button>
                                                                @endif
                                                            </div>
                                                            {{-- @endif --}}

                                                        </div>
                                                    @else
                                                        @if ($pro->item_type == 'product')
                                                            <div
                                                                class="d-flex justify-content-between flex-lg-wrap  align-items-center">
                                                                <div class="">
                                                                    <p class="text-dark fs-5 fw-bold mb-0">
                                                                        {{ _price($selling_price) }}
                                                                    </p>
                                                                    <p class="text-danger text-decoration-line-through mb-0 text-start"
                                                                        style="min-height: 24px;">
                                                                        @if ($pro->discount > 0 || $mrp > $selling_price)
                                                                            {{ _price($mrp) }}
                                                                        @endif
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @if (auth('web')->user())
                                                            <button
                                                                onclick="bookService({{ $pro->id }}, this, {{ $store['id'] }})"
                                                                class="btn border border-secondary rounded p-1 px-2 text-primary action__btn"><i
                                                                    class="fas fa-user-cog"></i>
                                                                Enquiry Now</button>
                                                        @else
                                                            <button data-bs-toggle="modal" data-bs-target="#loginModal"
                                                                class="btn border border-secondary rounded p-1 px-2 text-primary action__btn"><i
                                                                    class="fas fa-user-cog"></i>
                                                                Enquiry Now</button>
                                                        @endif
                                                    @endif


                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if (!count($productdata))
                    No Products found ...
                @endif
            </div>
        </div>
    </div>
</div>
