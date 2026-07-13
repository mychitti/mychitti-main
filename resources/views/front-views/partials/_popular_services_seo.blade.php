{{--
    Popular services in {city} — internal links into the programmatic SEO landing pages.
    Shows category-level landings (e.g. "Repair & Services") AND item-level ones (e.g. "AC Repair").
    Params:
      $context : 'home' (section on homepage/city page) | 'footer' (footer column)
      $limit   : how many of each level to show (default 6)
--}}
@php
    $context  = $context ?? 'home';
    $limit    = $limit ?? 6;
    $catData  = _topServicesInCity($limit);
    $itemData = _topItemServicesInCity($limit);
    $seoCity  = $catData['city'] ?? ($itemData['city'] ?? null);
    $seoCats  = $catData['services'];
    $seoItems = $itemData['services'];
@endphp

@if ($seoCats->isNotEmpty() || $seoItems->isNotEmpty())
    @if ($context === 'footer')
        <div class="col-lg-3 col-md-6">
            <div class="d-flex flex-column text-start footer-item">
                <h4 class="text-light mb-3">Top Services{{ $seoCity ? ' in ' . $seoCity : '' }}</h4>
                @foreach ($seoCats as $svc)
                    <a class="btn-link" href="{{ url($svc->slug) }}">{{ $svc->title }}</a>
                @endforeach
                @foreach ($seoItems as $svc)
                    <a class="btn-link" href="{{ url($svc->slug) }}">{{ $svc->title }}</a>
                @endforeach
            </div>
        </div>
    @else
        <div class="pop-items-section">
            <h2 class="section_heading text_dark">Popular Services{{ $seoCity ? ' in ' . $seoCity : '' }}</h2>
            <p style="margin:0 auto;"><span class="text_dark">Explore top service categories near you.</span></p>

            @if ($seoCats->isNotEmpty())
                <div class="d-flex flex-wrap" style="gap:10px; margin-top:14px;">
                    @foreach ($seoCats as $svc)
                        <a href="{{ url($svc->slug) }}"
                           style="display:inline-block; padding:9px 18px; background:#f4f1ef; color:#C8522A; border-radius:999px; font-weight:600; font-size:14px; border:1px solid #eadfd9;">
                            {{ $svc->title }}{{ $seoCity ? ' in ' . $seoCity : '' }}
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($seoItems->isNotEmpty())
                <p class="text_dark mb-1" style="margin-top:18px; font-weight:600;">Popular services{{ $seoCity ? ' in ' . $seoCity : '' }}</p>
                <div class="d-flex flex-wrap" style="gap:10px;">
                    @foreach ($seoItems as $svc)
                        <a href="{{ url($svc->slug) }}"
                           style="display:inline-block; padding:9px 18px; background:#ffffff; color:#C8522A; border-radius:999px; font-weight:600; font-size:14px; border:1px solid #eadfd9;">
                            {{ $svc->title }}{{ $seoCity ? ' in ' . $seoCity : '' }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
@endif
