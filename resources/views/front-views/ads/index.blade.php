@extends('front-views.layout')

@section('title', 'Local Store Spotlights')

@push('css_or_js')
<style>
    .ads-page { padding: 20px 16px; max-width: 1400px; margin: 0 auto; }
    .ads-page__header { margin-bottom: 24px; }
    .ads-page__header h1 { font-size: 26px; font-weight: 700; color: #2c3e50; margin-bottom: 4px; }
    .ads-page__header p  { font-size: 14px; color: #777; margin: 0; }

    .ads-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }
    @media (min-width: 480px)  { .ads-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 768px)  { .ads-grid { grid-template-columns: repeat(4, 1fr); } }
    @media (min-width: 1024px) { .ads-grid { grid-template-columns: repeat(5, 1fr); } }
    @media (min-width: 1280px) { .ads-grid { grid-template-columns: repeat(6, 1fr); } }

    .ad-card {
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
        text-decoration: none;
        display: block;
        transition: transform .2s, box-shadow .2s;
    }
    .ad-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,.14); }
    .ad-card__img { aspect-ratio: 4/5; overflow: hidden; background: #f0f0f0; }
    .ad-card__img img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ad-card__body { padding: 8px 10px; }
    .ad-card__title { font-size: 12px; font-weight: 600; color: #222; margin: 0 0 2px; }
    .ad-card__desc  { font-size: 11px; color: #777; margin: 0; min-height: 28px; }

    .load-more-wrap { text-align: center; margin-top: 28px; }
    #loadMoreBtn { min-width: 140px; }
</style>
@endpush

@section('content')
<div style="height:80px;"></div>
<div class="ads-page">
    <div class="ads-page__header">
        <h1><i class="fas fa-bullhorn me-2" style="color:var(--primary);"></i>Spotlight from Local Stores</h1>
        <p>Discover what's trending from businesses near you</p>
    </div>

    <div class="ads-grid" id="adsGrid">
        @include('front-views.ads._ad_cards', ['ads' => $ads])
    </div>

    <div id="scrollSentinel" style="height:1px;"></div>
    <div id="loadingSpinner" style="display:none; text-align:center; padding:20px;">
        <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    var page = 2;
    var hasMore = {{ $ads->hasMorePages() ? 'true' : 'false' }};
    var loading = false;

    function loadMoreAds() {
        if (!hasMore || loading) return;
        loading = true;
        $('#loadingSpinner').show();

        $.get("{{ route('front.ads.load') }}", { page: page }, function(res) {
            $('#adsGrid').append(res.html);
            hasMore = res.has_more;
            page++;
            loading = false;
            $('#loadingSpinner').hide();
        });
    }

    $(window).on('scroll', function() {
        var scrollBottom = $(document).height() - $(window).height() - $(window).scrollTop();
        if (scrollBottom < 700) {
            loadMoreAds();
        }
    });

    $('#adsGrid').on('click', '.spotlight-ad-card', function() {
        var adId = $(this).data('ad-id');
        fetch('{{ route("track.ad.click") }}', {
            method: 'POST',
            keepalive: true,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify({ ad_id: adId })
        }).catch(function() {});
    });
</script>
@endpush
