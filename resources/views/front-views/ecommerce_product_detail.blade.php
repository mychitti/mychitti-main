@extends('front-views.layout')

@php
    $title = $item->meta_title ?: $item->name;
    $desc  = $item->meta_desc  ?: $item->description;
@endphp

@section('title', $title)
@section('meta_keywords', $keywords ?? '')
@section('meta_description', $desc)

@push('meta_tags')
    <meta property="og:title"       content="{{ $item->name }}">
    <meta property="og:description" content="{{ $item->description }}">
    <meta property="og:image"       content="{{ asset('storage/app/public/product/' . $item->image) }}">
    <meta property="og:type"        content="product">
@endpush

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lg-thumbnail.min.css">
    <style>
        /* ── Layout ── */
        .ec-product-wrap      { padding: 30px 0 60px; }
        .ec-sticky            { position: sticky; top: 120px; }

        /* ── Gallery ── */
        .ec-thumb-strip       { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        .ec-thumb             { width: 64px; height: 64px; object-fit: cover; border-radius: 6px;
                                border: 2px solid #e5e7eb; cursor: pointer; transition: border-color .2s; }
        .ec-thumb.active,
        .ec-thumb:hover       { border-color: var(--bs-primary, #81c408); }
        .ec-main-img-wrap     { border-radius: 12px; overflow: hidden; background: #f8f8f8;
                                display: flex; align-items: center; justify-content: center;
                                height: 420px; }
        .ec-main-img-wrap img { max-height: 420px; width: 100%; object-fit: contain; }

        /* ── Badges ── */
        .ec-discount-badge    { background: #e53e3e; color: #fff; font-size: 12px; font-weight: 600;
                                padding: 3px 8px; border-radius: 4px; }
        .ec-stock-badge       { font-size: 12px; padding: 3px 8px; border-radius: 4px; font-weight: 600; }

        /* ── Price ── */
        .ec-price-current     { font-size: 26px; font-weight: 700; color: #1a1a1a; }
        .ec-price-mrp         { font-size: 14px; text-decoration: line-through; color: #9ca3af; margin-left: 8px; }
        .ec-tax-note          { font-size: 11px; color: #9ca3af; }

        /* ── Rating ── */
        .ec-stars             { color: #f59e0b; font-size: 15px; letter-spacing: 1px; }
        .ec-rating-count      { font-size: 13px; color: #6b7280; }

        /* ── Variations ── */
        .ec-var-group         { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px; }
        .ec-var-option        { position: relative; }
        .ec-var-option input  { position: absolute; opacity: 0; width: 0; height: 0; }
        .ec-var-label         { display: flex; flex-direction: column; align-items: center;
                                padding: 8px 14px; border: 2px solid #d1d5db; border-radius: 8px;
                                cursor: pointer; min-width: 80px; text-align: center;
                                transition: border-color .2s, box-shadow .2s; background: #fff; }
        .ec-var-label:hover   { border-color: #81c408; }
        .ec-var-option input:checked + .ec-var-label {
                                border-color: #81c408; box-shadow: 0 0 0 3px rgba(129,196,8,.18); }
        .ec-var-label .vl-type  { font-size: 13px; font-weight: 600; color: #374151; }
        .ec-var-label .vl-price { font-size: 12px; color: #6b7280; margin-top: 2px; }

        /* ── Qty ── */
        .ec-qty-wrap          { display: flex; align-items: center; gap: 0; border: 1px solid #d1d5db;
                                border-radius: 8px; overflow: hidden; width: fit-content; }
        .ec-qty-btn           { width: 36px; height: 36px; border: none; background: #f3f4f6;
                                font-size: 18px; cursor: pointer; line-height: 1;
                                transition: background .15s; }
        .ec-qty-btn:hover     { background: #e5e7eb; }
        .ec-qty-input         { width: 44px; height: 36px; border: none; text-align: center;
                                font-size: 15px; font-weight: 600; outline: none; }

        /* ── Actions ── */
        .ec-btn-cart          { flex: 1; padding: 11px 20px; font-size: 15px; font-weight: 600;
                                border-radius: 8px; }
        .ec-btn-wishlist      { width: 44px; height: 44px; border-radius: 8px; border: 1px solid #d1d5db;
                                background: #fff; cursor: pointer; display: flex; align-items: center;
                                justify-content: center; font-size: 18px; transition: all .2s; }
        .ec-btn-wishlist:hover { border-color: #e53e3e; color: #e53e3e; }
        .ec-btn-wishlist.wishlisted { color: #e53e3e; border-color: #e53e3e; }

        /* ── Meta rows ── */
        .ec-meta-row          { display: flex; gap: 8px; font-size: 13px; color: #6b7280;
                                padding: 6px 0; border-bottom: 1px solid #f3f4f6; }
        .ec-meta-row:last-child { border-bottom: none; }
        .ec-meta-label        { font-weight: 600; color: #374151; min-width: 100px; }

        /* ── Tabs ── */
        .ec-tab-btn           { background: none; border: none; padding: 10px 20px; font-weight: 600;
                                color: #6b7280; border-bottom: 2px solid transparent; cursor: pointer;
                                transition: all .2s; }
        .ec-tab-btn.active    { color: #81c408; border-color: #81c408; }
        .ec-tab-pane          { display: none; padding-top: 20px; }
        .ec-tab-pane.active   { display: block; }

        /* ── Reviews ── */
        .ec-review-card       { border: 1px solid #f3f4f6; border-radius: 10px; padding: 14px;
                                margin-bottom: 12px; }
        .ec-reviewer-avatar   { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }

        /* ── Related ── */
        .ec-related-card      { border-radius: 10px; overflow: hidden; border: 1px solid #e5e7eb;
                                transition: box-shadow .2s; }
        .ec-related-card:hover{ box-shadow: 0 4px 20px rgba(0,0,0,.1); }
        .ec-related-img       { height: 180px; object-fit: cover; width: 100%; }
        .ec-related-body      { padding: 10px; }

        /* ── Breadcrumb ── */
        .ec-breadcrumb         { font-size: 13px; color: #6b7280; margin-bottom: 16px; }
        .ec-breadcrumb a       { color: #6b7280; text-decoration: none; }
        .ec-breadcrumb a:hover { color: #81c408; }

        /* ── Store closed overlay ── */
        .ec-closed-overlay    { position: absolute; inset: 0; background: rgba(255,255,255,.85);
                                display: flex; align-items: center; justify-content: center;
                                border-radius: 12px; z-index: 2; }

        @media (max-width: 767px) {
            .ec-main-img-wrap  { height: 280px; }
            .ec-main-img-wrap img { max-height: 280px; }
            .ec-price-current  { font-size: 22px; }
        }
    </style>
@endpush

@section('content')

@php
    $variations     = json_decode($item->variations) ?? [];
    $firstVr        = !empty($variations) ? $variations[0] : null;
    $basePrice      = $firstVr ? $firstVr->price : $item->price;
    $baseMrp        = $firstVr ? ($firstVr->mrpprice ?? $firstVr->price) : ($item->mrp_price ?? $item->price);
    $inWishlist     = _itemExistInWishlist($item->id);
    $allImages      = collect();
    $allImages->push($item->image);
    if ($item->images) {
        foreach (json_decode($item->images, true) ?? [] as $img) { $allImages->push($img); }
    }
    $isAvailable    = $item->status && !(isset($item->suspended) && $item->suspended);
    $storeOpen      = $item->store_open ?? 1;
    $outOfStock     = ($item->stock ?? 1) <= 0;
    $currSymbol     = \App\CentralLogics\Helpers::currency_symbol();
@endphp

<div class="container ec-product-wrap">

    {{-- Breadcrumb --}}
    <nav class="ec-breadcrumb">
        <a href="{{ url('/') }}">Home</a> &rsaquo;
        @if (isset($item->category_name))
            <a href="#">{{ $item->category_name }}</a> &rsaquo;
        @endif
        <span class="text-dark">{{ $item->name }}</span>
    </nav>

    {{-- Main product row --}}
    <div class="row g-4 mb-5">

        {{-- ── LEFT: Image Gallery ── --}}
        <div class="col-md-5">
            <div class="ec-sticky">
                {{-- Main image --}}
                <div class="ec-main-img-wrap position-relative" id="ec-main-img-wrap">
                    @if (!$isAvailable)
                        <div class="ec-closed-overlay"><h5 class="text-danger">Not Available</h5></div>
                    @elseif (!$storeOpen)
                        <div class="ec-closed-overlay"><h5 class="text-warning">Store Closed</h5></div>
                    @elseif ($outOfStock)
                        <div class="ec-closed-overlay"><h5 class="text-danger">Out of Stock</h5></div>
                    @endif
                    <a id="ec-main-link" href="{{ asset('storage/app/public/product/' . $item->image) }}" data-lg-size="1600-1067">
                        <img id="ec-main-img"
                             src="{{ asset('storage/app/public/product/' . $item->image) }}"
                             alt="{{ $item->name }}"
                             style="{{ !$isAvailable ? 'filter:grayscale(1);' : '' }}">
                    </a>
                </div>

                {{-- Thumbnail strip --}}
                <div class="ec-thumb-strip" id="ec-thumb-strip">
                    @foreach ($allImages as $idx => $img)
                        <img class="ec-thumb {{ $idx === 0 ? 'active' : '' }}"
                             src="{{ asset('storage/app/public/product/' . $img) }}"
                             data-full="{{ asset('storage/app/public/product/' . $img) }}"
                             alt="{{ $item->name }} {{ $idx + 1 }}">
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── RIGHT: Product Info ── --}}
        <div class="col-md-7">
            <div class="ec-sticky">

                {{-- Badges row --}}
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    @if ($item->discount > 0)
                        <span class="ec-discount-badge">
                            {{ floor($item->discount) }}{{ $item->discount_type == 'percent' ? '% OFF' : ' ' . $currSymbol . ' OFF' }}
                        </span>
                    @endif
                    @if ($outOfStock)
                        <span class="ec-stock-badge bg-danger text-white">Out of Stock</span>
                    @else
                        <span class="ec-stock-badge bg-success text-white">In Stock</span>
                    @endif
                </div>

                {{-- Name --}}
                <h1 style="font-size:24px; font-weight:700;" class="mb-1">{{ $item->name }}</h1>

                {{-- Rating --}}
                @if ($item->rating_count)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="ec-stars">
                            @for ($s = 1; $s <= 5; $s++)
                                <i class="fa fa-star{{ $item->avg_rating >= $s ? '' : ($item->avg_rating >= $s - 0.5 ? '-half-alt' : '-o') }}"></i>
                            @endfor
                        </div>
                        <span class="ec-rating-count">{{ number_format($item->avg_rating, 1) }} ({{ $item->rating_count }} ratings)</span>
                    </div>
                @endif

                {{-- Price --}}
                <div class="d-flex align-items-baseline mb-1">
                    <span class="ec-price-current" id="ec-price">{{ $currSymbol }}{{ number_format($basePrice, 2) }}</span>
                    @if ($item->discount)
                        <span class="ec-price-mrp" id="ec-mrp">{{ $currSymbol }}{{ number_format($baseMrp, 2) }}</span>
                    @endif
                </div>
                <p class="ec-tax-note mb-3">Inclusive of all taxes</p>

                {{-- Variations --}}
                @if (!empty($variations))
                    <div class="mb-3">
                        <p class="fw-600 mb-2" style="font-size:14px;">Select Option:</p>
                        <div class="ec-var-group" id="ec-var-group">
                            @foreach ($variations as $idx => $vr)
                                <div class="ec-var-option">
                                    <input type="radio" name="ec_variation"
                                           id="ec_vr_{{ $idx }}"
                                           data-idx="{{ $idx }}"
                                           data-value="{{ $vr->type }}"
                                           class="ec-vr-radio"
                                           {{ $idx === 0 ? 'checked' : '' }}>
                                    <label class="ec-var-label" for="ec_vr_{{ $idx }}">
                                        <span class="vl-type">{{ preg_replace('/([a-z])([A-Z])/', '$1 $2', $vr->type) }}</span>
                                        <span class="vl-price">{{ $currSymbol }}{{ number_format($vr->price, 0) }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Hidden: selected variation index --}}
                <input type="hidden" id="selected_variation" value="{{ !empty($variations) ? 0 : '' }}">

                {{-- Quantity --}}
                <div class="mb-3">
                    <p class="fw-600 mb-2" style="font-size:14px;">Quantity:</p>
                    <div class="ec-qty-wrap">
                        <button class="ec-qty-btn" id="ec-qty-minus" type="button">−</button>
                        <input class="ec-qty-input" id="ec-qty" type="number" value="1" min="1"
                               max="{{ $item->maximum_cart_quantity ?? 99 }}">
                        <button class="ec-qty-btn" id="ec-qty-plus" type="button">+</button>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="d-flex align-items-center gap-2 mb-3" id="ec-action-row">
                    @if ($isAvailable && $storeOpen && !$outOfStock)
                        <button onclick="ecAddToCart({{ $item->id }})"
                                class="btn btn-primary ec-btn-cart" id="ec-add-cart-btn">
                            <i class="fa fa-shopping-cart me-2"></i> Add to Cart
                        </button>
                        <button onclick="ecToggleWishlist({{ $item->id }}, this)"
                                class="ec-btn-wishlist {{ $inWishlist ? 'wishlisted' : '' }}"
                                data-item-id="{{ $item->id }}"
                                title="{{ $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist' }}">
                            <i class="fa fa-heart"></i>
                        </button>
                    @elseif (!$storeOpen)
                        <button class="btn btn-secondary ec-btn-cart" disabled>Store Closed</button>
                    @elseif ($outOfStock)
                        <button class="btn btn-secondary ec-btn-cart" disabled>Out of Stock</button>
                    @else
                        <button class="btn btn-secondary ec-btn-cart" disabled>Not Available</button>
                    @endif
                </div>

                {{-- Meta info --}}
                <div class="mt-3">
                    @if ($item->store_name ?? null)
                        <div class="ec-meta-row">
                            <span class="ec-meta-label">Sold by</span>
                            <a href="{{ route('store.details', [_selectedCity(), $item->store_slug ?? '#']) }}"
                               class="text-primary text-decoration-none">{{ $item->store_name }}</a>
                        </div>
                    @endif
                    @if ($item->category_name ?? null)
                        <div class="ec-meta-row">
                            <span class="ec-meta-label">Category</span>
                            <span>{{ $item->category_name }}</span>
                        </div>
                    @endif
                    @if ($item->maximum_cart_quantity)
                        <div class="ec-meta-row">
                            <span class="ec-meta-label">Max per order</span>
                            <span>{{ $item->maximum_cart_quantity }}</span>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- ── Tabs: Description / Specifications / Reviews ── --}}
    <div class="mb-5">
        <div class="d-flex border-bottom gap-2 flex-wrap">
            <button class="ec-tab-btn active" data-tab="ec-desc">Description</button>
            @if ($item->specifications)
                <button class="ec-tab-btn" data-tab="ec-specs">Specifications</button>
            @endif
            <button class="ec-tab-btn" data-tab="ec-reviews">
                Reviews ({{ $data['reviews']->count() ?? 0 }})
            </button>
        </div>

        {{-- Description --}}
        <div id="ec-desc" class="ec-tab-pane active">
            <div class="text-muted lh-lg">{{ $item->description }}</div>
        </div>

        {{-- Specifications --}}
        @if ($item->specifications)
            <div id="ec-specs" class="ec-tab-pane">
                <div class="text-muted">{!! $item->specifications !!}</div>
            </div>
        @endif

        {{-- Reviews --}}
        <div id="ec-reviews" class="ec-tab-pane">
            @forelse ($data['reviews'] ?? [] as $rev)
                <div class="ec-review-card">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($rev->profile_image, asset('storage/app/public/profile/' . $rev->profile_image), asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                             class="ec-reviewer-avatar" alt="{{ $rev->f_name }}">
                        <div>
                            <div class="fw-600">{{ $rev->f_name . ' ' . $rev->l_name }}</div>
                            <div class="ec-stars" style="font-size:12px;">
                                @for ($s = 1; $s <= 5; $s++)
                                    <i class="fa fa-star{{ $rev->rating >= $s ? '' : '-o' }}"></i>
                                @endfor
                            </div>
                        </div>
                        <span class="ms-auto ec-rating-count">{{ _formatted_datetime($rev->created_at) }}</span>
                    </div>
                    <p class="mb-1 text-muted" style="font-size:14px;">{{ $rev->comment }}</p>
                    @if ($rev->attachment)
                        @foreach ((array) $rev->attachment as $img)
                            <a href="{{ asset('storage/app/public/' . $img) }}" target="_blank">
                                <img src="{{ asset('storage/app/public/' . $img) }}" style="width:60px;border-radius:4px;" alt="review">
                            </a>
                        @endforeach
                    @endif
                </div>
            @empty
                <p class="text-muted mt-3">No reviews yet.</p>
            @endforelse
        </div>
    </div>

    {{-- ── Related Products ── --}}
    @if (!empty($data['related_products']) && count($data['related_products']))
        <h2 style="font-size:20px; font-weight:700;" class="mb-3">Related Products</h2>
        <div class="row g-3">
            @foreach ($data['related_products'] as $pro)
                @php
                    $proVars  = json_decode($pro->variations) ?? [];
                    $proFirst = !empty($proVars) ? $proVars[0] : null;
                    $proPrice = $proFirst ? $proFirst->price : $pro->price;
                    $proMrp   = $proFirst ? ($proFirst->mrpprice ?? $proFirst->price) : ($pro->mrp_price ?? $pro->price);
                @endphp
                <div class="col-6 col-md-3">
                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}" class="text-decoration-none text-dark">
                        <div class="ec-related-card">
                            <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/' . $pro->image), asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                 class="ec-related-img" alt="{{ $pro->name }}">
                            <div class="ec-related-body">
                                <p class="mb-1 fw-600 two-line-ellipsis" style="font-size:14px; min-height:42px;">{{ ucfirst($pro->name) }}</p>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-700" style="color:#1a1a1a;">{{ $currSymbol }}{{ number_format($proPrice, 0) }}</span>
                                    @if ($pro->discount)
                                        <span style="font-size:12px; text-decoration:line-through; color:#9ca3af;">{{ $currSymbol }}{{ number_format($proMrp, 0) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

</div>

@push('script_2')
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/thumbnail/lg-thumbnail.min.js"></script>
<script>
$(function () {

    // ── Thumbnail switch ──────────────────────────────────────────
    $('#ec-thumb-strip').on('click', '.ec-thumb', function () {
        var full = $(this).data('full');
        $('#ec-main-img').attr('src', full);
        $('#ec-main-link').attr('href', full);
        $('.ec-thumb').removeClass('active');
        $(this).addClass('active');
    });

    // ── Quantity ──────────────────────────────────────────────────
    $('#ec-qty-minus').on('click', function () {
        var v = parseInt($('#ec-qty').val()) || 1;
        if (v > 1) $('#ec-qty').val(v - 1);
    });
    $('#ec-qty-plus').on('click', function () {
        var v   = parseInt($('#ec-qty').val()) || 1;
        var max = parseInt($('#ec-qty').attr('max')) || 99;
        if (v < max) $('#ec-qty').val(v + 1);
    });
    $('#ec-qty').on('input', function () {
        var v   = parseInt($(this).val()) || 1;
        var max = parseInt($(this).attr('max')) || 99;
        $(this).val(Math.min(Math.max(v, 1), max));
    });

    // ── Variation select → update price via AJAX ──────────────────
    $(document).on('change', '.ec-vr-radio', function () {
        var idx = $(this).data('idx');
        var val = $(this).data('value');
        $('#selected_variation').val(idx);

        $.ajax({
            url  : "{{ route('change-variation') }}",
            type : 'get',
            data : { type: val, id: {{ $item->id }} },
            success: function (data) {
                if (data.status) {
                    $('#ec-price').text('{{ $currSymbol }}' + parseFloat(data.data.discounted_price).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}));
                    if ($('#ec-mrp').length) {
                        $('#ec-mrp').text('{{ $currSymbol }}' + parseFloat(data.data.mrpprice).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}));
                    }
                    // Update main image if variation has its own image
                    if (data.data.img) {
                        var imgPath = "{{ asset('storage/app/public/product-variations/') }}/";
                        $('#ec-main-img').attr('src', imgPath + data.data.img);
                        $('#ec-main-link').attr('href', imgPath + data.data.img);
                    }
                }
            }
        });
    });

    // ── Tabs ──────────────────────────────────────────────────────
    $(document).on('click', '.ec-tab-btn', function () {
        var target = $(this).data('tab');
        $('.ec-tab-btn').removeClass('active');
        $('.ec-tab-pane').removeClass('active');
        $(this).addClass('active');
        $('#' + target).addClass('active');
    });

    // ── LightGallery on main image ────────────────────────────────
    lightGallery(document.getElementById('ec-main-img-wrap'), {
        selector : '.ec-main-img-wrap > a',
        plugins  : [lgThumbnail],
        speed    : 300,
    });
});

// ── Add to cart ───────────────────────────────────────────────────
function ecAddToCart(prId) {
    var variation = $('#selected_variation').val();
    var qty       = parseInt($('#ec-qty').val()) || 1;

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $.post({
        url  : "{{ route('add-to-cart') }}",
        data : { prId: prId, variation: variation, quantity: qty },
        beforeSend: function () { $('.page_loader').show(); },
        success: function (data) {
            if (data.status) {
                toasterNotification(data.message);
                $('#ec-add-cart-btn').html('<i class="fa fa-check me-2"></i> Added to Cart');
                $('#ec-add-cart-btn').removeClass('btn-primary').addClass('btn-success');
                $('.cart-count-outer').load(window.location.href + ' .cart-count-inner');
            } else {
                toasterNotification(data.message);
            }
        },
        complete: function () { $('.page_loader').hide(); }
    });
}

// ── Wishlist ──────────────────────────────────────────────────────
function ecToggleWishlist(itemId, btn) {
    var action = $(btn).hasClass('wishlisted') ? 'remove' : 'add';
    wishlist(itemId, action);
    $(btn).toggleClass('wishlisted');
}
</script>
@endpush

@endsection
