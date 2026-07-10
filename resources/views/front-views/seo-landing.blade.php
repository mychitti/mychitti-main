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
    <style>
        .seo-lp { --lp-accent: #2563eb; --lp-accent-dark: #1e40af; --lp-ink: #0f172a; --lp-muted: #64748b; color: var(--lp-ink); }
        .seo-lp a { text-decoration: none; }

        .seo-hero { position: relative; background: linear-gradient(135deg, #0b2545 0%, #13315c 45%, #1e4d8c 100%); color: #fff; padding: 56px 0 120px; overflow: hidden; }
        .seo-hero::after { content: ""; position: absolute; inset: 0; background:
            radial-gradient(600px 300px at 85% -10%, rgba(59,130,246,.35), transparent 70%),
            radial-gradient(500px 260px at 5% 110%, rgba(14,165,233,.25), transparent 70%); pointer-events: none; }
        .seo-hero .container { position: relative; z-index: 1; }
        .seo-breadcrumb { font-size: 13px; color: rgba(255,255,255,.7); margin-bottom: 14px; }
        .seo-breadcrumb a { color: rgba(255,255,255,.85); }
        .seo-breadcrumb a:hover { color: #fff; }
        .seo-hero h1 { font-size: clamp(26px, 4vw, 40px); font-weight: 800; line-height: 1.15; margin: 0 0 14px; letter-spacing: -.02em; }
        .seo-hero .lead { font-size: 17px; color: rgba(255,255,255,.88); max-width: 760px; margin: 0 0 26px; line-height: 1.6; }

        .seo-stats { display: flex; flex-wrap: wrap; gap: 12px; }
        .seo-stat { background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.16); backdrop-filter: blur(6px);
            border-radius: 14px; padding: 12px 18px; min-width: 130px; }
        .seo-stat .n { font-size: 22px; font-weight: 800; line-height: 1; }
        .seo-stat .l { font-size: 12px; color: rgba(255,255,255,.72); margin-top: 4px; text-transform: uppercase; letter-spacing: .04em; }

        .seo-hero-cta { margin-top: 28px; display: flex; flex-wrap: wrap; gap: 12px; }
        .seo-btn { display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: 15px; border-radius: 12px;
            padding: 12px 22px; transition: transform .15s ease, box-shadow .15s ease; border: 0; cursor: pointer; }
        .seo-btn-light { background: #fff; color: var(--lp-accent-dark); }
        .seo-btn-light:hover { color: var(--lp-accent-dark); transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.22); }
        .seo-btn-ghost { background: rgba(255,255,255,.08); color: #fff; border: 1px solid rgba(255,255,255,.35); }
        .seo-btn-ghost:hover { color: #fff; background: rgba(255,255,255,.16); transform: translateY(-2px); }

        .seo-body { position: relative; margin-top: -80px; z-index: 2; padding-bottom: 60px; }
        .seo-section-title { font-size: 22px; font-weight: 800; letter-spacing: -.01em; margin: 0 0 4px; }
        .seo-section-sub { color: var(--lp-muted); font-size: 14px; margin-bottom: 20px; }

        .seo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; }
        .seo-store { display: flex; flex-direction: column; background: #fff; border: 1px solid #e6eaf0; border-radius: 16px;
            padding: 18px; box-shadow: 0 1px 2px rgba(15,23,42,.04); transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; height: 100%; }
        .seo-store:hover { transform: translateY(-4px); box-shadow: 0 16px 34px rgba(15,23,42,.12); border-color: #c7d6ec; }
        .seo-store-head { display: flex; align-items: center; gap: 14px; }
        .seo-avatar { width: 54px; height: 54px; border-radius: 13px; object-fit: cover; flex: 0 0 54px; }
        .seo-avatar-ph { width: 54px; height: 54px; border-radius: 13px; flex: 0 0 54px; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 20px; color: #fff; background: linear-gradient(135deg, var(--lp-accent), #0ea5e9); }
        .seo-store-name { font-weight: 700; font-size: 16px; color: var(--lp-ink); line-height: 1.25; }
        .seo-store-addr { font-size: 13px; color: var(--lp-muted); margin-top: 6px; line-height: 1.45;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .seo-rating { display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 13px; color: var(--lp-muted); }
        .seo-stars { --pct: calc(var(--r, 0) / 5 * 100%); position: relative; display: inline-block; font-size: 15px; line-height: 1; letter-spacing: 1px; font-family: Arial, sans-serif; }
        .seo-stars::before { content: "★★★★★"; color: #dbe1ea; }
        .seo-stars::after { content: "★★★★★"; color: #f59e0b; position: absolute; left: 0; top: 0; width: var(--pct); overflow: hidden; white-space: nowrap; }
        .seo-rating b { color: var(--lp-ink); }

        .seo-store-foot { margin-top: auto; padding-top: 14px; }
        .seo-store-link { display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: 14px; color: var(--lp-accent); }
        .seo-store:hover .seo-store-link { gap: 10px; }

        .seo-empty { background: #fff; border: 1px dashed #cdd6e4; border-radius: 16px; padding: 40px; text-align: center; color: var(--lp-muted); }

        .seo-block { background: #fff; border: 1px solid #e6eaf0; border-radius: 18px; padding: 26px; margin-top: 30px; box-shadow: 0 1px 2px rgba(15,23,42,.04); }

        .seo-chips { display: flex; flex-wrap: wrap; gap: 10px; }
        .seo-chip { display: inline-block; padding: 8px 16px; background: #f1f5fb; color: #334155; border-radius: 999px; font-size: 13.5px; font-weight: 600;
            border: 1px solid #e2e8f2; transition: background .15s ease, color .15s ease; }
        .seo-chip:hover { background: var(--lp-accent); color: #fff; }

        .seo-faq-item { border-bottom: 1px solid #eef1f6; }
        .seo-faq-item:last-child { border-bottom: 0; }
        .seo-faq-q { width: 100%; text-align: left; background: none; border: 0; padding: 18px 40px 18px 0; font-weight: 700; font-size: 16px; color: var(--lp-ink);
            position: relative; cursor: pointer; }
        .seo-faq-q::after { content: "+"; position: absolute; right: 6px; top: 50%; transform: translateY(-50%); font-size: 22px; font-weight: 400; color: var(--lp-accent); transition: transform .2s ease; }
        .seo-faq-q[aria-expanded="true"]::after { content: "\2212"; }
        .seo-faq-a { color: var(--lp-muted); font-size: 15px; line-height: 1.65; padding: 0 0 20px; }

        .seo-cta-band { margin-top: 40px; background: linear-gradient(135deg, #0b2545, #1e4d8c); border-radius: 20px; padding: 34px; color: #fff;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 18px; }
        .seo-cta-band h3 { font-size: 22px; font-weight: 800; margin: 0 0 4px; }
        .seo-cta-band p { margin: 0; color: rgba(255,255,255,.82); font-size: 15px; }

        @media (max-width: 575px) {
            .seo-hero { padding: 40px 0 110px; }
            .seo-cta-band { padding: 26px; text-align: center; justify-content: center; }
        }
    </style>

    <div class="seo-lp">
        {{-- ============ HERO ============ --}}
        <section class="seo-hero">
            <div class="container">
                <div class="seo-breadcrumb">
                    <a href="{{ url('/') }}">Home</a>
                    <span class="mx-1">/</span>
                    <a href="{{ url($zone->slug . '/services/' . $category->slug) }}">{{ $category->name }} in {{ $zone->name }}</a>
                    @if ($item)
                        <span class="mx-1">/</span>
                        <span>{{ $item->name }}</span>
                    @endif
                </div>

                <h1>{{ $seo->h1 ?: ($subject . ' in ' . $zone->name) }}</h1>

                @if ($seo->intro_paragraph)
                    <p class="lead">{{ $seo->intro_paragraph }}</p>
                @endif

                @php
                    $providerCount = $stores->count();
                    $rated = $stores->where('rating_count', '>', 0);
                    $avgCity = $rated->count() ? $rated->avg('average_rating') : null;
                @endphp
                <div class="seo-stats">
                    <div class="seo-stat">
                        <div class="n">{{ $providerCount }}{{ $providerCount >= 60 ? '+' : '' }}</div>
                        <div class="l">Verified providers</div>
                    </div>
                    @if ($avgCity)
                        <div class="seo-stat">
                            <div class="n">{{ number_format((float) $avgCity, 1) }} ★</div>
                            <div class="l">Average rating</div>
                        </div>
                    @endif
                    <div class="seo-stat">
                        <div class="n">{{ $zone->name }}</div>
                        <div class="l">Service area</div>
                    </div>
                </div>

                <div class="seo-hero-cta">
                    <a href="#providers" class="seo-btn seo-btn-light">Browse providers</a>
                    <a href="{{ url($zone->slug . '/services/' . $category->slug) }}" class="seo-btn seo-btn-ghost">
                        Explore all {{ $category->name }}
                    </a>
                </div>
            </div>
        </section>

        {{-- ============ BODY ============ --}}
        <div class="seo-body">
            <div class="container">

                {{-- Providers --}}
                <div id="providers">
                    <h2 class="seo-section-title">Top {{ $subject }} providers in {{ $zone->name }}</h2>
                    <p class="seo-section-sub">
                        {{ $providerCount }} {{ Str::plural('provider', $providerCount) }} ready to help — compare ratings, location and services.
                    </p>

                    @if ($stores->isEmpty())
                        <div class="seo-empty">No providers are listed here yet — check back soon.</div>
                    @else
                        <div class="seo-grid">
                            @foreach ($stores as $store)
                                @php
                                    $rating = (float) ($store->average_rating ?? 0);
                                    $storeUrl = url($zone->slug . '/store/' . $store->slug);
                                @endphp
                                <a href="{{ $storeUrl }}" class="seo-store">
                                    <div class="seo-store-head">
                                        @if ($store->logo)
                                            <img src="{{ asset('storage/app/public/store/' . $store->logo) }}"
                                                 alt="{{ $store->name }} — {{ $subject }} in {{ $zone->name }}"
                                                 class="seo-avatar" loading="lazy" width="54" height="54">
                                        @else
                                            <span class="seo-avatar-ph">{{ strtoupper(mb_substr($store->name, 0, 1)) }}</span>
                                        @endif
                                        <div>
                                            <div class="seo-store-name">{{ $store->name }}</div>
                                            @if ($store->rating_count)
                                                <div class="seo-rating">
                                                    <span class="seo-stars" style="--r: {{ $rating }};"></span>
                                                    <span><b>{{ number_format($rating, 1) }}</b> ({{ $store->rating_count }})</span>
                                                </div>
                                            @else
                                                <div class="seo-rating"><span class="seo-stars" style="--r: 0;"></span> <span>New</span></div>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($store->address)
                                        <div class="seo-store-addr">📍 {{ $store->address }}</div>
                                    @endif

                                    <div class="seo-store-foot">
                                        <span class="seo-store-link">View details &rarr;</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Popular searches (generated keywords → real on-page content) --}}
                @if (!empty($seo->keywords))
                    <div class="seo-block">
                        <h2 class="seo-section-title">Popular {{ $subject }} searches in {{ $zone->name }}</h2>
                        <p class="seo-section-sub">What people look for in your area.</p>
                        <div class="seo-chips">
                            @foreach (array_slice($seo->keywords, 0, 18) as $kw)
                                <span class="seo-chip">{{ ucfirst($kw) }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- FAQ --}}
                @if (!empty($seo->faqs))
                    <div class="seo-block">
                        <h2 class="seo-section-title mb-3">Frequently asked questions</h2>
                        <div>
                            @foreach ($seo->faqs as $i => $faq)
                                <div class="seo-faq-item">
                                    <button type="button" class="seo-faq-q" aria-expanded="false"
                                            data-faq-target="#seoFaq{{ $i }}">
                                        {{ $faq['q'] ?? '' }}
                                    </button>
                                    <div class="seo-faq-a" id="seoFaq{{ $i }}" style="display:none;">
                                        {{ $faq['a'] ?? '' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- CTA band --}}
                <div class="seo-cta-band">
                    <div>
                        <h3>Are you a {{ $category->name }} provider in {{ $zone->name }}?</h3>
                        <p>List your business free and reach customers searching in your area.</p>
                    </div>
                    <a href="{{ url('list-your-business') }}" class="seo-btn seo-btn-light">List your business</a>
                </div>

            </div>
        </div>
    </div>

    <script>
        (function () {
            document.querySelectorAll('.seo-faq-q').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var panel = document.querySelector(btn.getAttribute('data-faq-target'));
                    if (!panel) return;
                    var open = btn.getAttribute('aria-expanded') === 'true';
                    btn.setAttribute('aria-expanded', open ? 'false' : 'true');
                    panel.style.display = open ? 'none' : 'block';
                });
            });
        })();
    </script>
@endsection
