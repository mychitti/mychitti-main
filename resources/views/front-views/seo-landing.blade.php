@extends('front-views.layout')

@php $subject = $item->name ?? $category->name; @endphp

@section('title', $seo->meta_title ?: ($subject . ' in ' . $zone->name))
@section('meta_description', $seo->meta_description)
@section('meta_keywords', implode(', ', $seo->keywords ?? []))

@push('meta_tags')
    <meta property="og:title" content="{{ $seo->meta_title ?: ($subject . ' in ' . $zone->name) }}" />
    <meta property="og:description" content="{{ $seo->meta_description }}" />
    <meta property="og:url" content="{{ $canonical }}" />
    <script type="application/ld+json">
    @php
        $faqJson = collect($seo->faqs ?? [])->map(fn($f) => [
            '@type' => 'Question',
            'name' => $f['q'] ?? '',
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a'] ?? ''],
        ])->values();
        $graph = [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter([
                [
                    '@type' => 'Service',
                    'name' => ($subject . ' in ' . $zone->name),
                    'areaServed' => ['@type' => 'City', 'name' => $zone->name],
                    'url' => $canonical,
                ],
                $faqJson->isNotEmpty() ? ['@type' => 'FAQPage', 'mainEntity' => $faqJson] : null,
            ])),
        ];
    @endphp
    {!! json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <div class="container py-4">
        @if ($item)
            <div class="mb-2">
                <a href="{{ url($zone->slug . '/services/' . $category->slug) }}" class="text-muted small">
                    &larr; All {{ $category->name }} in {{ $zone->name }}
                </a>
            </div>
        @endif

        <h1 class="mb-3">{{ $seo->h1 ?: ($subject . ' in ' . $zone->name) }}</h1>

        @if ($seo->intro_paragraph)
            <p class="text-muted mb-4">{{ $seo->intro_paragraph }}</p>
        @endif

        <h2 class="h5 mb-3">{{ $stores->count() }} {{ $subject }} providers in {{ $zone->name }}</h2>

        <div class="row">
            @forelse ($stores as $store)
                <div class="col-md-4 col-sm-6 mb-3">
                    <a href="{{ url($zone->slug . '/store/' . $store->slug) }}" class="text-decoration-none">
                        <div class="card h-100 p-3">
                            <div class="d-flex align-items-center">
                                @if ($store->logo)
                                    <img src="{{ asset('storage/app/public/store/' . $store->logo) }}"
                                         alt="{{ $store->name }} — {{ $category->name }} in {{ $zone->name }}"
                                         width="48" height="48" class="rounded mr-2" style="object-fit:cover;">
                                @endif
                                <div>
                                    <div class="font-weight-bold text-dark">{{ $store->name }}</div>
                                    <div class="small text-muted">{{ $store->address }}</div>
                                    @if ($store->rating_count)
                                        <div class="small">★ {{ number_format((float) $store->average_rating, 1) }}
                                            ({{ $store->rating_count }})</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12"><p>No providers listed yet.</p></div>
            @endforelse
        </div>

        @if (!empty($seo->faqs))
            <div class="mt-5">
                <h2 class="h5 mb-3">Frequently asked questions</h2>
                @foreach ($seo->faqs as $faq)
                    <div class="mb-3">
                        <div class="font-weight-bold">{{ $faq['q'] ?? '' }}</div>
                        <div class="text-muted">{{ $faq['a'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
