@extends('front-views.layout')

@section('title', $keyword ? ('Search: ' . $keyword . ' | My Chitti') : 'Search | My Chitti')
@section('meta_description', 'Search results for local services on My Chitti.')

@section('content')
    <style>
        .srch-wrap { max-width: 1080px; margin: 0 auto; padding: 90px 16px 60px; }
        .srch-form { display: flex; gap: 10px; margin-bottom: 8px; }
        .srch-form input { flex: 1; border: 1px solid #e2e2e2; border-radius: 10px; padding: 12px 16px; font-size: 15px; }
        .srch-form button { background: var(--color-primary, #C8522A); color: #fff; border: 0; border-radius: 10px; padding: 12px 22px; font-weight: 700; cursor: pointer; }
        .srch-meta { color: #64748b; font-size: 14px; margin-bottom: 26px; }
        .srch-sec-title { font-size: 18px; font-weight: 800; margin: 26px 0 12px; }
        .srch-chips { display: flex; flex-wrap: wrap; gap: 10px; }
        .srch-chip { display: inline-block; padding: 8px 16px; background: #f4f1ef; color: #C8522A; border-radius: 999px; font-weight: 600; font-size: 13.5px; border: 1px solid #eadfd9; text-decoration: none; }
        .srch-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
        .srch-card { display: flex; gap: 12px; align-items: center; background: #fff; border: 1px solid #ececec; border-radius: 12px; padding: 12px; text-decoration: none; color: inherit; transition: box-shadow .15s ease, transform .15s ease; }
        .srch-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,.08); transform: translateY(-2px); }
        .srch-card img, .srch-ph { width: 52px; height: 52px; border-radius: 9px; object-fit: cover; flex: 0 0 52px; background: #f1efec; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #C8522A; }
        .srch-card .nm { font-weight: 700; font-size: 14.5px; line-height: 1.25; }
        .srch-card .sub { font-size: 12.5px; color: #64748b; margin-top: 3px; }
        .srch-empty { text-align: center; color: #64748b; padding: 48px 16px; }
    </style>

    <div class="srch-wrap">
        <form class="srch-form" action="{{ route('search-page') }}" method="get" role="search">
            <input type="search" name="q" value="{{ $keyword }}" placeholder="Search services, providers…" aria-label="Search" autofocus>
            <button type="submit">Search</button>
        </form>

        @if (strlen($keyword) < 2)
            <div class="srch-empty">Type at least 2 characters to search local services in {{ ucfirst($city) }}.</div>
        @else
            @php $total = $items->count() + $stores->count() + $categories->count(); @endphp
            <p class="srch-meta">{{ $total }} result{{ $total == 1 ? '' : 's' }} for “{{ $keyword }}” in {{ ucfirst($city) }}</p>

            @if ($total === 0)
                <div class="srch-empty">No results found. Try a different term.</div>
            @endif

            @if ($categories->isNotEmpty())
                <h2 class="srch-sec-title">Categories</h2>
                <div class="srch-chips">
                    @foreach ($categories as $cat)
                        <a class="srch-chip" href="{{ route('category.listing', [$cat->slug, $city]) }}">{{ $cat->name }}</a>
                    @endforeach
                </div>
            @endif

            @if ($items->isNotEmpty())
                <h2 class="srch-sec-title">Services</h2>
                <div class="srch-grid">
                    @foreach ($items as $item)
                        <a class="srch-card" href="{{ route('product.details', [$city, $item->slug]) }}">
                            @if ($item->image)
                                <img loading="lazy" src="{{ \App\CentralLogics\Helpers::onerror_image_helper($item->image, asset('storage/app/public/product/') . '/' . $item->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}" alt="{{ $item->name }}">
                            @else
                                <span class="srch-ph">{{ strtoupper(mb_substr($item->name, 0, 1)) }}</span>
                            @endif
                            <span>
                                <span class="nm">{{ $item->name }}</span>
                                <span class="sub d-block">{{ $item->cat_name }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($stores->isNotEmpty())
                <h2 class="srch-sec-title">Providers</h2>
                <div class="srch-grid">
                    @foreach ($stores as $store)
                        <a class="srch-card" href="{{ route('store.details', [$city, $store->slug]) }}">
                            @if ($store->logo)
                                <img loading="lazy" src="{{ asset('storage/app/public/store/' . $store->logo) }}" alt="{{ $store->name }}">
                            @else
                                <span class="srch-ph">{{ strtoupper(mb_substr($store->name, 0, 1)) }}</span>
                            @endif
                            <span>
                                <span class="nm">{{ $store->name }}</span>
                                <span class="sub d-block">
                                    @if ($store->rating_count){{ number_format((float) $store->average_rating, 1) }} ★ · {{ $store->rating_count }} reviews @else New @endif
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
@endsection
