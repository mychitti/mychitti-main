@extends('front-views.layout')

@section('title', $store['meta_title'] ?? ($data['store_config']?->webpage_name ?? $store['name']))
@section('meta_keywords', $keywords)
@section('meta_description', $store['meta_description'])

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ─── SCOPE: prefix all rules so they don't bleed into layout ─── */
.t17 *,
.t17 *::before,
.t17 *::after { box-sizing: border-box; }

.t17 {
  --pr: #E8380D;
  --pr-l: #FF5733;
  --pr-bg: #FFF5F3;
  --gr: #1BAC4B;
  --gr-bg: #F0FBF4;
  --yl: #FFB800;
  --ink: #1A1A1A;
  --mut: #666;
  --bdr: #EBEBEB;
  --bg: #F8F8F8;
  --wh: #FFFFFF;
  --sans: 'Poppins', sans-serif;
  --disp: 'Nunito', sans-serif;
  --rad: 16px;
  --sh: 0 2px 12px rgba(0,0,0,.08);
  --sh-md: 0 4px 24px rgba(0,0,0,.12);
  font-family: var(--sans);
  background: var(--bg);
  color: var(--ink);
}

/* TOP BAR */
.t17 .t17-topbar {
  background: var(--pr); color: #fff;
  text-align: center; padding: 8px 16px;
  font-size: .72rem; font-weight: 600; letter-spacing: .04em;
}
.t17 .t17-topbar span {
  background: rgba(255,255,255,.2); border-radius: 99px;
  padding: 2px 10px; margin: 0 4px;
}

/* NAV */
.t17 .t17-nav {
  position: sticky; top: 0; z-index: 200;
  background: var(--wh);
  border-bottom: 1px solid var(--bdr);
  padding: 0 24px;
  display: flex; align-items: center; gap: 16px;
  height: 64px;
  box-shadow: 0 2px 8px rgba(0,0,0,.06);
}
.t17 .t17-logo {
  display: flex; align-items: center; gap: 8px;
  font-family: var(--disp); font-size: 1.4rem; font-weight: 900;
  color: var(--pr); text-decoration: none; white-space: nowrap;
}
.t17 .t17-logo img {
  height: 40px; width: 40px; object-fit: cover; border-radius: 10px;
}
.t17 .t17-search-wrap { flex: 1; position: relative; }
.t17 .t17-search-wrap input {
  width: 100%;
  border: 1.5px solid var(--bdr); border-radius: 12px;
  padding: 10px 16px 10px 42px;
  font-family: var(--sans); font-size: .88rem;
  background: var(--bg); outline: none;
  transition: border-color .2s, background .2s;
  color: var(--ink);
}
.t17 .t17-search-wrap input:focus { border-color: var(--pr); background: var(--wh); }
.t17 .t17-search-wrap input::placeholder { color: #aaa; }
.t17 .t17-search-icon {
  position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
  font-size: 1rem; color: #aaa; pointer-events: none;
}
.t17 .t17-nav-right { display: flex; align-items: center; gap: 8px; }
.t17 .t17-cart-btn {
  display: flex; align-items: center; gap: 6px;
  padding: 9px 18px; border-radius: 10px;
  background: var(--pr); color: #fff;
  font-size: .82rem; font-weight: 600;
  text-decoration: none; white-space: nowrap;
  transition: background .2s;
}
.t17 .t17-cart-btn:hover { background: var(--pr-l); color: #fff; }
.t17 .t17-cart-count-outer {
  background: #fff; color: var(--pr);
  font-size: .65rem; font-weight: 800;
  min-width: 18px; height: 18px; border-radius: 99px;
  display: flex; align-items: center; justify-content: center;
  padding: 0 4px;
}

/* CATEGORY STRIP */
.t17 .t17-cat-strip {
  background: var(--wh);
  border-bottom: 1px solid var(--bdr);
  padding: 0 24px;
  display: flex; align-items: center; gap: 4px;
  overflow-x: auto; scrollbar-width: none;
}
.t17 .t17-cat-strip::-webkit-scrollbar { display: none; }
.t17 .t17-cat-pill {
  display: flex; align-items: center; gap: 6px;
  padding: 12px 16px;
  font-size: .78rem; font-weight: 600;
  cursor: pointer; white-space: nowrap;
  color: var(--mut);
  border-bottom: 2.5px solid transparent;
  transition: all .2s; flex-shrink: 0;
  text-decoration: none;
}
.t17 .t17-cat-pill:hover { color: var(--ink); }
.t17 .t17-cat-pill.active { color: var(--pr); border-bottom-color: var(--pr); }

/* LAYOUT */
.t17 .t17-page { display: flex; max-width: 1320px; margin: 0 auto; }

/* SIDEBAR */
.t17 .t17-sidebar {
  width: 110px; flex-shrink: 0;
  background: var(--wh);
  border-right: 1px solid var(--bdr);
  min-height: calc(100vh - 120px);
  position: sticky; top: 64px;
  height: calc(100vh - 64px);
  overflow-y: auto; scrollbar-width: none;
}
.t17 .t17-sidebar::-webkit-scrollbar { display: none; }
.t17 .t17-sitem {
  display: flex; flex-direction: column; align-items: center;
  padding: 14px 8px; gap: 6px;
  cursor: pointer; text-decoration: none;
  border-left: 3px solid transparent;
  transition: all .15s;
}
.t17 .t17-sitem:hover { background: var(--bg); }
.t17 .t17-sitem.active { background: var(--pr-bg); border-left-color: var(--pr); }
.t17 .t17-sicon {
  width: 52px; height: 52px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden; background: #f5f5f5;
  transition: transform .2s;
}
.t17 .t17-sicon img { width: 100%; height: 100%; object-fit: cover; border-radius: 14px; }
.t17 .t17-sitem:hover .t17-sicon { transform: scale(1.05); }
.t17 .t17-slabel {
  font-size: .62rem; font-weight: 700; text-align: center;
  color: var(--ink); line-height: 1.3;
}
.t17 .t17-sitem.active .t17-slabel { color: var(--pr); }

/* MAIN */
.t17 .t17-main { flex: 1; padding: 20px 20px 60px; min-width: 0; }

/* HERO BANNERS */
.t17 .t17-hero {
  display: grid;
  grid-template-columns: 1fr 260px;
  grid-template-rows: 210px;        /* fixed row height — prevents stretching */
  gap: 12px; margin-bottom: 24px;
  align-items: stretch;
}
.t17 .t17-banner-main {
  border-radius: var(--rad); overflow: hidden;
  cursor: pointer; display: block;
  height: 100%;
}
.t17 .t17-banner-main img {
  width: 100%; height: 100%; object-fit: cover; display: block;
}
.t17 .t17-banner-fallback {
  height: 100%; border-radius: var(--rad);
  background: linear-gradient(120deg,#E8380D 0%,#FF7043 50%,#FF8C00 100%);
  display: flex; align-items: center; padding: 28px 32px;
  position: relative; overflow: hidden;
}
.t17 .t17-banner-fallback::after {
  content: ''; position: absolute; right: -30px; top: -30px;
  width: 200px; height: 200px; border-radius: 50%;
  background: rgba(255,255,255,.08);
}
.t17 .t17-bfall-text { position: relative; z-index: 1; }
.t17 .t17-bfall-text .bfall-title {
  font-family: var(--disp); font-size: 1.8rem; font-weight: 900;
  color: #fff; line-height: 1.15; margin-bottom: 6px;
}
.t17 .t17-bfall-text .bfall-sub { font-size: .8rem; color: rgba(255,255,255,.8); margin-bottom: 16px; }
.t17 .t17-bfall-text .bfall-cta {
  background: #fff; color: var(--pr);
  font-size: .75rem; font-weight: 700;
  padding: 8px 18px; border-radius: 99px;
  border: none; cursor: pointer; transition: transform .2s;
  text-decoration: none; display: inline-block;
}
.t17 .t17-bfall-text .bfall-cta:hover { transform: scale(1.03); }

.t17 .t17-banner-side {
  display: flex; flex-direction: column; gap: 10px;
  height: 100%;                     /* fill the grid row */
}
.t17 .t17-banner-sm {
  border-radius: 14px; flex: 1;
  overflow: hidden; cursor: pointer; display: block;
  min-height: 0;                    /* allow flex shrink */
}
.t17 .t17-banner-sm img { width: 100%; height: 100%; object-fit: cover; display: block; }
.t17 .t17-banner-sm-fb {
  border-radius: 14px; flex: 1; padding: 16px 20px;
  cursor: pointer; display: flex; flex-direction: column; justify-content: flex-end;
  transition: transform .2s; min-height: 0;
}
.t17 .t17-banner-sm-fb:hover { transform: translateY(-2px); }
.t17 .t17-banner-sm-fb .sm-title { font-family: var(--disp); font-size: .95rem; font-weight: 800; color: #fff; line-height: 1.2; }
.t17 .t17-banner-sm-fb .sm-sub { font-size: .68rem; color: rgba(255,255,255,.75); margin-top: 2px; }

/* PROMISE STRIP */
.t17 .t17-promise { display: flex; gap: 12px; margin-bottom: 24px; }
.t17 .t17-pcard {
  flex: 1; background: var(--wh); border-radius: 14px;
  padding: 14px 16px; display: flex; align-items: center; gap: 12px;
  box-shadow: var(--sh);
}
.t17 .t17-pcard-icon { font-size: 1.5rem; flex-shrink: 0; }
.t17 .t17-pcard-title { font-size: .8rem; font-weight: 700; }
.t17 .t17-pcard-sub { font-size: .68rem; color: var(--mut); }

/* SECTION HEADER */
.t17 .t17-sh { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.t17 .t17-sh-title { font-family: var(--disp); font-size: 1.1rem; font-weight: 800; }
.t17 .t17-sh-title span { color: var(--pr); }
.t17 .t17-sh-link { font-size: .78rem; font-weight: 600; color: var(--pr); text-decoration: none; }
.t17 .t17-sh-link:hover { text-decoration: underline; }

/* CATEGORY CARDS */
.t17 .t17-cat-cards {
  display: flex; gap: 12px; overflow-x: auto;
  padding-bottom: 4px; margin-bottom: 28px; scrollbar-width: none;
}
.t17 .t17-cat-cards::-webkit-scrollbar { display: none; }
.t17 .t17-ccard {
  flex-shrink: 0; width: 90px;
  background: var(--wh); border-radius: 14px;
  padding: 14px 8px 10px;
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  cursor: pointer; box-shadow: var(--sh);
  transition: transform .2s, box-shadow .2s;
  text-align: center; text-decoration: none;
}
.t17 .t17-ccard:hover { transform: translateY(-3px); box-shadow: var(--sh-md); }
.t17 .t17-ccard-img {
  width: 52px; height: 52px; border-radius: 12px; overflow: hidden; background: #f0f0f0;
}
.t17 .t17-ccard-img img { width: 100%; height: 100%; object-fit: cover; }
.t17 .t17-ccard-name { font-size: .68rem; font-weight: 700; color: var(--ink); line-height: 1.3; }

/* PRODUCT GRID */
.t17 .t17-pgrid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 14px; margin-bottom: 32px;
}
.t17 .t17-pcard {
  background: var(--wh); border-radius: 16px;
  overflow: hidden; box-shadow: var(--sh);
  transition: transform .2s, box-shadow .2s;
  display: flex; flex-direction: column;
}
.t17 .t17-pcard:hover { transform: translateY(-3px); box-shadow: var(--sh-md); }

/* Image */
.t17 .t17-pimg {
  height: 148px;
  position: relative; overflow: hidden; flex-shrink: 0;
}
.t17 .t17-pimg a { display: block; width: 100%; height: 100%; }
.t17 .t17-pimg img {
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .35s;
}
.t17 .t17-pcard:hover .t17-pimg img { transform: scale(1.07); }
.t17 .t17-pbadge {
  position: absolute; top: 8px; left: 8px;
  font-size: .6rem; font-weight: 700;
  padding: 3px 8px; border-radius: 99px;
  background: var(--pr); color: #fff;
  letter-spacing: .04em; z-index: 1;
}

/* Body */
.t17 .t17-pbody {
  padding: 10px 12px 12px;
  display: flex; flex-direction: column; flex: 1;
  width: 100%;
}
.t17 .t17-pweight { font-size: .64rem; color: var(--mut); margin-bottom: 2px; }
.t17 .t17-pname {
  font-size: .82rem; font-weight: 700; line-height: 1.35;
  color: var(--ink); flex: 1;
  display: -webkit-box; -webkit-line-clamp: 2;
  -webkit-box-orient: vertical; overflow: hidden;
  margin-bottom: 10px;
}

/* Footer row: price left, button right */
.t17 .t17-pfooter {
  display: flex; align-items: center;
  justify-content: space-between; gap: 6px;
  margin-top: auto;
}
.t17 .t17-pprice { display: flex; flex-direction: column; min-width: 0; }
.t17 .t17-pcurr { font-size: .95rem; font-weight: 800; color: var(--ink); white-space: nowrap; }
.t17 .t17-pold  { font-size: .66rem; color: var(--mut); text-decoration: line-through; }

/* Add / Remove button (initial render) */
.t17 .t17-add-btn {
  width: 36px; height: 36px; border-radius: 10px;
  background: var(--wh); color: var(--pr);
  border: 1.5px solid var(--pr);
  font-size: 1.3rem; font-weight: 300; line-height: 1;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all .15s; flex-shrink: 0;
  text-decoration: none; user-select: none;
}
.t17 .t17-add-btn:hover { background: var(--pr); color: #fff; }
.t17 .t17-add-btn.in-cart {
  background: var(--pr); color: #fff; font-size: .7rem;
}

/* ─── Override layout-injected cart buttons ─── */
.t17 [class^="cartSec_"] a.btn,
.t17 [class*=" cartSec_"] a.btn {
  width: 36px !important; height: 36px !important;
  border-radius: 10px !important; padding: 0 !important;
  background: var(--wh) !important; color: var(--pr) !important;
  border: 1.5px solid var(--pr) !important;
  display: inline-flex !important; align-items: center !important;
  justify-content: center !important;
  font-size: 0 !important; /* hide text */
  transition: all .15s !important; flex-shrink: 0;
}
.t17 [class^="cartSec_"] a.btn:hover,
.t17 [class*=" cartSec_"] a.btn:hover {
  background: var(--pr) !important; color: #fff !important;
}
.t17 [class^="cartSec_"] a.btn i,
.t17 [class*=" cartSec_"] a.btn i {
  font-size: .9rem !important; margin: 0 !important;
  color: inherit !important;
}
/* "Remove" state — red tint */
.t17 [class^="cartSec_"] a.btn .fa-times,
.t17 [class*=" cartSec_"] a.btn .fa-times {
  color: var(--pr) !important;
}
/* "Add" state after remove — green tint */
.t17 [class^="cartSec_"] a.btn .fa-shopping-bag,
.t17 [class*=" cartSec_"] a.btn .fa-shopping-bag {
  color: var(--gr) !important;
}
.t17 [class^="cartSec_"] a.btn:has(.fa-shopping-bag),
.t17 [class*=" cartSec_"] a.btn:has(.fa-shopping-bag) {
  border-color: var(--gr) !important;
}

/* Out-of-stock & options states */
.t17 .t17-oos-badge { background: #888 !important; }
.t17 .t17-oos-img { opacity: .55; filter: grayscale(.4); }
.t17 .t17-sold-out {
  font-size: .65rem; font-weight: 700; color: #aaa;
  letter-spacing: .03em; flex-shrink: 0;
}
.t17 .t17-options-btn {
  font-size: .72rem; font-weight: 700; padding: 0 12px;
  white-space: nowrap; border-radius: 10px;
  border: 1.5px solid var(--pr); color: var(--pr);
  text-decoration: none; height: 36px;
  display: inline-flex; align-items: center; justify-content: center;
  transition: all .15s; flex-shrink: 0;
}
.t17 .t17-options-btn:hover { background: var(--pr); color: #fff; }

/* Soft pastel card image backgrounds — cycles per card position */
.t17 .t17-pimg { background: #FFF4F0; }
.t17 .t17-pgrid > .t17-pcard:nth-child(4n+2) .t17-pimg { background: #F0FBF4; }
.t17 .t17-pgrid > .t17-pcard:nth-child(4n+3) .t17-pimg { background: #F0F5FF; }
.t17 .t17-pgrid > .t17-pcard:nth-child(4n+4) .t17-pimg { background: #FFFAF0; }

/* OFFER BANNER */
.t17 .t17-offer {
  border-radius: var(--rad);
  background: linear-gradient(120deg,#1BAC4B,#0D7A35);
  padding: 24px 28px;
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 28px; position: relative; overflow: hidden;
}
.t17 .t17-offer::after {
  content: ''; position: absolute; right: -40px; top: -40px;
  width: 180px; height: 180px; border-radius: 50%;
  background: rgba(255,255,255,.07);
}
.t17 .t17-offer-tag {
  background: rgba(255,255,255,.2); color: #fff;
  font-size: .65rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .08em; padding: 3px 10px; border-radius: 99px;
  display: inline-block; margin-bottom: 8px;
}
.t17 .t17-offer-title {
  font-family: var(--disp); font-size: 1.4rem; font-weight: 900;
  color: #fff; line-height: 1.2;
}
.t17 .t17-offer-sub { font-size: .76rem; color: rgba(255,255,255,.75); margin-top: 4px; }
.t17 .t17-offer-right { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; z-index: 1; }
.t17 .t17-offer-btn {
  background: #fff; color: var(--gr); border: none;
  padding: 10px 22px; border-radius: 99px;
  font-size: .78rem; font-weight: 700;
  cursor: pointer; transition: transform .2s;
  text-decoration: none; display: inline-block;
}
.t17 .t17-offer-btn:hover { transform: scale(1.03); color: var(--gr); }

/* SECTION SCROLL TARGET */
.t17 .t17-section { scroll-margin-top: 120px; margin-bottom: 36px; }

/* RESPONSIVE */
@media (max-width: 1024px) {
  .t17 .t17-sidebar { display: none; }
  .t17 .t17-hero { grid-template-columns: 1fr; grid-template-rows: auto; }
  .t17 .t17-banner-main { height: 200px; }
  .t17 .t17-banner-side { flex-direction: row; height: 110px; }
}
@media (max-width: 700px) {
  .t17 .t17-nav { padding: 0 14px; gap: 10px; }
  .t17 .t17-main { padding: 14px 12px 80px; }
  .t17 .t17-hero { grid-template-columns: 1fr; grid-template-rows: auto; gap: 8px; }
  .t17 .t17-banner-main { height: 160px; }
  .t17 .t17-banner-fallback { padding: 20px; }
  .t17 .t17-bfall-text .bfall-title { font-size: 1.3rem; }
  .t17 .t17-banner-side { flex-direction: row; height: 100px; }
  .t17 .t17-promise { flex-wrap: wrap; }
  .t17 .t17-pgrid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .t17 .t17-offer { flex-direction: column; gap: 16px; }
  .t17 .t17-offer-right { align-items: flex-start; }
}
</style>
@endpush

@section('content')
@php
  $storeName = $data['store_config']?->webpage_name ?? $store['name'];
  $banners   = $data['banners'] ?? [];
  $mainBanner = $banners[0] ?? null;
  $sm1        = $banners[1] ?? null;
  $sm2        = $banners[2] ?? null;
@endphp

<div class="t17">

  {{-- TOP BAR --}}
  @if($data['store_config']?->announcement ?? null)
    <div class="t17-topbar">{{ $data['store_config']->announcement }}</div>
  @else
    <div class="t17-topbar">🎉 Welcome to <span>{{ $storeName }}</span> — Fast local delivery!</div>
  @endif

  {{-- NAV --}}
  <nav class="t17-nav">
    <a href="#" class="t17-logo">
      <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store['logo'], asset('storage/app/public/store/') . '/' . $store['logo'], asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
           alt="{{ $storeName }}">
      {{ $storeName }}
    </a>

    <div class="t17-search-wrap">
      <span class="t17-search-icon">🔍</span>
      <input type="text" placeholder="Search products…" id="t17SearchInput" autocomplete="off">
    </div>

    <div class="t17-nav-right">
      <a href="{{ route('cart') }}" class="t17-cart-btn">
        🛒 Cart
        <span class="t17-cart-count-outer cart-count-outer">
          <span class="cart-count-inner">{{ count(\App\Models\Cart::where('user_id', auth()->id() ?? session()->getId())->get()) }}</span>
        </span>
      </a>
    </div>
  </nav>

  {{-- CATEGORY PILLS STRIP --}}
  <div class="t17-cat-strip" id="t17CatStrip">
    <a href="#t17-all" class="t17-cat-pill active" data-cat="all">🏠 All</a>
    @foreach($productdata as $key => $cat)
      <a href="#t17-cat-{{ $key }}" class="t17-cat-pill" data-cat="{{ $key }}">{{ $cat->name }}</a>
    @endforeach
    @foreach($invItemdata as $key => $cat)
      <a href="#t17-inv-{{ $key }}" class="t17-cat-pill" data-cat="inv{{ $key }}">{{ $cat->name }}</a>
    @endforeach
  </div>

  {{-- PAGE WRAP --}}
  <div class="t17-page">

    {{-- SIDEBAR --}}
    <div class="t17-sidebar">
      <a href="#t17-all" class="t17-sitem active" data-scat="all">
        <div class="t17-sicon" style="background:#FFF0ED;">🏠</div>
        <div class="t17-slabel">All</div>
      </a>
      @foreach($productdata as $key => $cat)
        <a href="#t17-cat-{{ $key }}" class="t17-sitem" data-scat="{{ $key }}">
          <div class="t17-sicon">
            @if($cat->image ?? null)
              <img src="{{ asset('storage/app/public/category/') . '/' . $cat->image }}"
                   onerror="this.style.display='none';this.parentElement.innerHTML='🛍️';"
                   alt="{{ $cat->name }}">
            @else
              🛍️
            @endif
          </div>
          <div class="t17-slabel">{{ Str::limit($cat->name, 12) }}</div>
        </a>
      @endforeach
    </div>

    {{-- MAIN --}}
    <div class="t17-main" id="t17-all">

      {{-- HERO BANNERS --}}
      <div class="t17-hero">
        {{-- Main banner --}}
        @if($mainBanner)
          <a href="{{ $mainBanner->default_link ?? '#' }}" class="t17-banner-main" onclick="trackBannerClick({{ $mainBanner->id }})">
            <img src="{{ asset('storage/app/public/banner/') . '/' . $mainBanner->image }}"
                 alt="{{ $mainBanner->title ?? $storeName }}">
          </a>
        @else
          <div class="t17-banner-fallback">
            <div class="t17-bfall-text">
              <div class="bfall-title">{{ $storeName }}</div>
              <div class="bfall-sub">{{ $store['meta_description'] ? Str::limit($store['meta_description'], 70) : 'Shop fresh, shop local.' }}</div>
              <a href="#t17-products" class="bfall-cta">Shop Now →</a>
            </div>
          </div>
        @endif

        {{-- Side banners --}}
        <div class="t17-banner-side">
          @if($sm1)
            <a href="{{ $sm1->default_link ?? '#' }}" class="t17-banner-sm" onclick="trackBannerClick({{ $sm1->id }})">
              <img src="{{ asset('storage/app/public/banner/') . '/' . $sm1->image }}" alt="{{ $sm1->title ?? '' }}">
            </a>
          @else
            <div class="t17-banner-sm-fb" style="background:linear-gradient(135deg,#1BAC4B,#0D8B39);">
              <div class="sm-title">Free Delivery</div>
              <div class="sm-sub">On qualifying orders</div>
            </div>
          @endif
          @if($sm2)
            <a href="{{ $sm2->default_link ?? '#' }}" class="t17-banner-sm" onclick="trackBannerClick({{ $sm2->id }})">
              <img src="{{ asset('storage/app/public/banner/') . '/' . $sm2->image }}" alt="{{ $sm2->title ?? '' }}">
            </a>
          @else
            <div class="t17-banner-sm-fb" style="background:linear-gradient(135deg,#FFB800,#E09800);">
              <div class="sm-title">Fresh Daily</div>
              <div class="sm-sub">Quality guaranteed</div>
            </div>
          @endif
        </div>
      </div>

      {{-- DELIVERY PROMISE --}}
      <div class="t17-promise">
        <div class="t17-pcard"><div class="t17-pcard-icon">⚡</div><div><div class="t17-pcard-title">Fast Delivery</div><div class="t17-pcard-sub">Right to your door</div></div></div>
        <div class="t17-pcard"><div class="t17-pcard-icon">✅</div><div><div class="t17-pcard-title">Quality Assured</div><div class="t17-pcard-sub">Or full refund</div></div></div>
        <div class="t17-pcard"><div class="t17-pcard-icon">🔒</div><div><div class="t17-pcard-title">Safe Payments</div><div class="t17-pcard-sub">UPI, Card, COD</div></div></div>
        <div class="t17-pcard"><div class="t17-pcard-icon">🌿</div><div><div class="t17-pcard-title">Local Sourced</div><div class="t17-pcard-sub">Support local vendors</div></div></div>
      </div>

      {{-- CATEGORY CARDS --}}
      @if(count($productdata) > 0)
        <div class="t17-sh">
          <div class="t17-sh-title">Shop by <span>Category</span></div>
        </div>
        <div class="t17-cat-cards" id="t17-products">
          @foreach($productdata as $key => $cat)
            <a href="#t17-cat-{{ $key }}" class="t17-ccard">
              <div class="t17-ccard-img">
                @if($cat->image ?? null)
                  <img src="{{ asset('storage/app/public/category/') . '/' . $cat->image }}"
                       onerror="this.style.display='none';" alt="{{ $cat->name }}">
                @else
                  <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.6rem;">🛍️</div>
                @endif
              </div>
              <div class="t17-ccard-name">{{ Str::limit($cat->name, 14) }}</div>
            </a>
          @endforeach
        </div>
      @endif

      {{-- PRODUCT SECTIONS BY CATEGORY --}}
      @foreach($productdata as $key => $cat)
        @if(count($cat->items) > 0)
          <div class="t17-section" id="t17-cat-{{ $key }}">
            <div class="t17-sh">
              <div class="t17-sh-title">{{ $cat->name }}</div>
            </div>
            <div class="t17-pgrid">
              @foreach($cat->items as $pro)
                @php
                  $variations   = json_decode($pro->variations);
                  $firstVr      = !empty($variations) ? json_encode($variations[0]) : '';
                  $multiVariant = !empty($variations) && count($variations) > 1;
                  $outOfStock   = isset($pro->stock) && $pro->stock <= 0;
                  $selling_price = $pro->price;
                  $mrp          = $pro->mrp_price ?? $pro->price;
                  $hasDiscount  = $mrp && $mrp > $selling_price;
                  $inCart       = _itemExistInCart($pro->id, json_encode('[' . $firstVr . ']'));
                @endphp
                <div class="t17-pcard">
                  <div class="t17-pimg">
                    @if($outOfStock)
                      <div class="t17-pbadge t17-oos-badge">Out of Stock</div>
                    @elseif($hasDiscount)
                      <div class="t17-pbadge">{{ round((($mrp - $selling_price) / $mrp) * 100) }}% OFF</div>
                    @endif
                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                      <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                           alt="{{ $pro->name }}" loading="lazy" class="{{ $outOfStock ? 't17-oos-img' : '' }}">
                    </a>
                  </div>
                  <div class="t17-pbody">
                    @if($pro->unit_type ?? null)
                      <div class="t17-pweight">{{ $pro->unit_type }}</div>
                    @endif
                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}" class="t17-pname" style="text-decoration:none;">{{ $pro->name }}</a>
                    <div class="t17-pfooter">
                      <div class="t17-pprice">
                        <span class="t17-pcurr">₹{{ number_format($selling_price, 0) }}</span>
                        @if($hasDiscount)
                          <span class="t17-pold">₹{{ number_format($mrp, 0) }}</span>
                        @endif
                      </div>
                      @if($outOfStock)
                        <span class="t17-sold-out">Unavailable</span>
                      @elseif($multiVariant)
                        <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}" class="t17-add-btn t17-options-btn">Options</a>
                      @else
                        <div class="cartSec_{{ $pro->id }}">
                          @if($inCart)
                            <a onclick="updateCart({{ $pro->id }}, 'remove', '', '{{ $inCart }}')" class="t17-add-btn in-cart" title="Remove">✕</a>
                          @else
                            <a onclick="updateCart({{ $pro->id }}, 'add', '0', '')" class="t17-add-btn" title="Add to cart">+</a>
                          @endif
                        </div>
                      @endif
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          {{-- Offer banner after first category --}}
          @if($key === array_key_first((array)$productdata) && count($productdata) > 1)
            <div class="t17-offer">
              <div>
                <div class="t17-offer-tag">🎉 Shop More Save More</div>
                <div class="t17-offer-title">Best Deals<br>Every Day</div>
                <div class="t17-offer-sub">Fresh products at unbeatable prices</div>
              </div>
              <div class="t17-offer-right">
                <a href="#t17-products" class="t17-offer-btn">Explore All →</a>
              </div>
            </div>
          @endif
        @endif
      @endforeach

      {{-- INVENTORY ITEMS --}}
      @foreach($invItemdata as $key => $cat)
        @if(count($cat->items) > 0)
          <div class="t17-section" id="t17-inv-{{ $key }}">
            <div class="t17-sh">
              <div class="t17-sh-title">{{ $cat->name }}</div>
            </div>
            <div class="t17-pgrid">
              @foreach($cat->items as $pro)
                @php
                  $selling_price = $pro->price;
                  $mrp          = $pro->mrp_price ?? $pro->price;
                  $hasDiscount  = $mrp && $mrp > $selling_price;
                  $outOfStock   = isset($pro->stock) && $pro->stock <= 0;
                  $inCart       = _itemExistInCart($pro->id, '');
                @endphp
                <div class="t17-pcard">
                  <div class="t17-pimg">
                    @if($outOfStock)
                      <div class="t17-pbadge t17-oos-badge">Out of Stock</div>
                    @elseif($hasDiscount)
                      <div class="t17-pbadge">{{ round((($mrp - $selling_price) / $mrp) * 100) }}% OFF</div>
                    @endif
                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}">
                      <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($pro->image, asset('storage/app/public/product/') . '/' . $pro->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                           alt="{{ $pro->name }}" loading="lazy" class="{{ $outOfStock ? 't17-oos-img' : '' }}">
                    </a>
                  </div>
                  <div class="t17-pbody">
                    <a href="{{ route('product.details', [_selectedCity(), $pro->slug]) }}" class="t17-pname" style="text-decoration:none;">{{ $pro->name }}</a>
                    <div class="t17-pfooter">
                      <div class="t17-pprice">
                        <span class="t17-pcurr">₹{{ number_format($selling_price, 0) }}</span>
                        @if($hasDiscount)
                          <span class="t17-pold">₹{{ number_format($mrp, 0) }}</span>
                        @endif
                      </div>
                      @if($outOfStock)
                        <span class="t17-sold-out">Unavailable</span>
                      @else
                        <div class="cartSec_{{ $pro->id }}">
                          @if($inCart)
                            <a onclick="updateCart({{ $pro->id }}, 'remove', '', '{{ $inCart }}')" class="t17-add-btn in-cart" title="Remove">✕</a>
                          @else
                            <a onclick="updateCart({{ $pro->id }}, 'add', '', '')" class="t17-add-btn" title="Add to cart">+</a>
                          @endif
                        </div>
                      @endif
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endif
      @endforeach

    </div>{{-- /main --}}
  </div>{{-- /page --}}
</div>{{-- /t17 --}}

@push('css_or_js')
<script>
// Pill / sidebar active state on scroll
(function () {
  const sections = document.querySelectorAll('.t17-section');
  const pills    = document.querySelectorAll('#t17CatStrip .t17-cat-pill');
  const sitems   = document.querySelectorAll('.t17-sitem');

  function setActive(key) {
    pills.forEach(p => p.classList.toggle('active', p.dataset.cat === key));
    sitems.forEach(s => s.classList.toggle('active', s.dataset.scat === key));
  }

  const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        const id = e.target.id; // e.g. t17-cat-0
        const key = id.replace('t17-cat-', '').replace('t17-inv-', 'inv');
        setActive(key);
      }
    });
  }, { rootMargin: '-30% 0px -60% 0px' });

  sections.forEach(s => obs.observe(s));

  // Smooth scroll on pill click
  document.querySelectorAll('.t17-cat-pill, .t17-sitem').forEach(el => {
    el.addEventListener('click', function (e) {
      const href = this.getAttribute('href');
      if (href && href.startsWith('#')) {
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

  // Search filter
  const input = document.getElementById('t17SearchInput');
  if (input) {
    input.addEventListener('input', function () {
      const q = this.value.toLowerCase().trim();
      document.querySelectorAll('.t17-pcard').forEach(card => {
        const name = card.querySelector('.t17-pname')?.textContent?.toLowerCase() ?? '';
        card.style.display = (!q || name.includes(q)) ? '' : 'none';
      });
    });
  }
})();
</script>
@endpush

@include('front-views.partials._store-review-form', ['store' => $store])
@endsection
