@extends('front-views.layout')

@php
    $serviceName = $item->name;
    $pageTitle = $serviceName . ' by Brand in ' . $cityName;
@endphp

@section('title', $pageTitle . ' | My Chitti')
@section('meta_description', 'Browse all ' . $brands->count() . ' brands we service for ' . $serviceName . ' in ' . $cityName . '.')

@push('meta_tags')
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="My Chitti">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:url" content="{{ $canonical }}">

    @php
        $graph = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => $serviceName, 'item' => $itemUrl],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => 'Brands', 'item' => $canonical],
                    ],
                ],
                [
                    '@type' => 'ItemList',
                    'itemListElement' => $brands->values()->map(fn($b, $i) => [
                        '@type' => 'ListItem',
                        'position' => $i + 1,
                        'name' => $b->name . ' ' . $serviceName,
                    ])->all(),
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
    <style>
        .brands-lp { --bl-accent: var(--color-primary, #C8522A); --bl-ink: #0f172a; --bl-muted: #64748b; color: var(--bl-ink); }
        .brands-lp a { text-decoration: none; }
        .brands-lp .container { max-width: 1100px; }

        .brands-hero { padding: 32px 0 8px; }
        .brands-breadcrumb { font-size: 13px; color: var(--bl-muted); margin-bottom: 10px; }
        .brands-breadcrumb a { color: var(--bl-muted); }
        .brands-breadcrumb a:hover { color: var(--bl-accent); }
        .brands-hero h1 { font-size: clamp(22px, 3vw, 30px); font-weight: 800; letter-spacing: -.01em; margin: 0 0 8px; }
        .brands-hero p { color: var(--bl-muted); font-size: 14.5px; margin: 0 0 20px; }

        .brands-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; padding: 4px 0 40px; }
        .brands-item { display: block; padding: 14px 16px; background: #fff; border: 1px solid #e6eaf0; border-radius: 12px;
            font-size: 14px; font-weight: 600; color: var(--bl-ink); transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .brands-item:hover { color: var(--bl-ink); border-color: var(--bl-accent); box-shadow: 0 6px 16px rgba(15,23,42,.08); transform: translateY(-2px); }

        .brands-back { display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: 14px; color: var(--bl-accent); margin-bottom: 22px; }
        .brands-back:hover { color: var(--bl-accent); text-decoration: underline; }
    </style>

    <div class="brands-lp">
        <div class="container brands-hero">
            <div class="brands-breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                <span class="mx-1">/</span>
                <a href="{{ $itemUrl }}">{{ $serviceName }}</a>
                <span class="mx-1">/</span>
                <span>Brands</span>
            </div>

            <a href="{{ $itemUrl }}" class="brands-back">&larr; Back to {{ $serviceName }}</a>

            <h1>{{ $serviceName }} by Brand</h1>
            <p>All {{ $brands->count() }} brands our verified providers service in {{ $cityName }}.</p>
        </div>

        <div class="container">
            <div class="brands-grid">
                @foreach ($brands as $brand)
                    <span class="brands-item">{{ $brand->name }} {{ $serviceName }}</span>
                @endforeach
            </div>
        </div>
    </div>
@endsection
