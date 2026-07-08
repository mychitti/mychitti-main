@extends('layouts.vendor.app')

@section('title', 'Retail POS')

@push('css_or_js')
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        /* Bind to the panel's theme color (set in theme-colors.blade) so POS matches the store brand. */
        .rpos { --ink:#212b36; --muted:#8893a3; --line:#edf0f5; --soft:#f6f7fb;
                --accent:var(--primary,#754BFF); --accent2:var(--primary-light-theme,#A099FF); --accent-dark:var(--primary-dark,#6e44fa);
                color:var(--ink); }
        .rpos .pos-topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px; }
        .rpos .pos-topbar h1 { font-size:22px; font-weight:700; margin:0; }
        .rpos .pos-topbar .sub { font-size:12px; color:var(--muted); }
        .rpos .rp-shift-pill { display:inline-block; margin-top:5px; font-size:11.5px; font-weight:600; padding:3px 11px; border-radius:999px; }
        .rpos .rp-shift-pill.on { background:#e7f7ec; color:#1b7a43; border:1px solid #b7e3c5; }
        .rpos .rp-shift-pill.off { background:#fff4e5; color:#9a6700; border:1px solid #ffd9a8; }

        .pos-wrap { display:flex; gap:16px; align-items:flex-start; }
        .pos-left { flex:1 1 56%; min-width:0; }
        .pos-right { flex:1 1 44%; position:sticky; top:12px; min-width:0; }
        .pos-card { background:#fff; border:1px solid var(--line); border-radius:16px; box-shadow:0 1px 3px rgba(16,24,40,.05); overflow:hidden; }
        .pc-hd { padding:13px 16px; border-bottom:1px solid var(--line); font-weight:700; font-size:14px; display:flex; justify-content:space-between; align-items:center;
                 background:var(--accent); color:#fff; }
        .pc-bd { padding:14px 16px; }

        #pos-search { border-radius:12px; border:1.5px solid #e3e7ef; height:50px; font-size:15px; padding:0 16px; }
        #pos-search:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(0,0,0,.05); }
        /* Header action buttons — solid white so they pop on the colored header */
        .btn-scan { border:0; background:#fff; color:var(--accent); border-radius:10px; padding:7px 13px; font-size:12px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,.15); }
        .btn-scan:hover { background:#f4f4f8; transform:translateY(-1px); }
        .pc-hd .btn-outline-secondary { background:#fff !important; border:0 !important; color:var(--accent) !important; font-weight:700; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.15); }
        .pc-hd .btn-outline-secondary:hover { background:#f4f4f8 !important; }
        .pc-hd .badge { background:var(--accent) !important; color:#fff !important; margin-left:4px; }

        .pos-results { max-height:260px; overflow:auto; border:1px solid var(--line); border-radius:12px; margin-top:8px; background:#fff; }
        .pos-results .res-row { padding:9px 14px; cursor:pointer; border-bottom:1px solid #f4f4f7; display:flex; justify-content:space-between; align-items:center; font-size:13px; }
        .pos-results .res-row:last-child { border-bottom:0; }
        .pos-results .res-row:hover { background:var(--soft); }

        .items-row { display:flex; gap:12px; margin-top:14px; align-items:flex-start; }
        .cat-bar { flex:0 0 156px; max-height:420px; overflow-y:auto; display:flex; flex-direction:column; gap:5px; padding-right:10px; border-right:1px solid var(--line); }
        .cat-tab { padding:9px 12px; border:1px solid #e7eaf3; border-radius:10px; cursor:pointer; font-size:12.5px; background:#fff; text-align:left; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; transition:.12s; }
        .cat-tab:hover { background:var(--soft); }
        .cat-tab.active { background:var(--accent); color:#fff; border-color:var(--accent); }

        .quick-grid { flex:1; display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:10px; max-height:420px; overflow-y:auto; padding:2px; }
        .quick-item { border:1px solid #e7eaf3; border-radius:12px; padding:12px 10px; cursor:pointer; text-align:center; font-size:12px; background:#fff; transition:.12s; display:flex; flex-direction:column; gap:6px; min-height:74px; justify-content:center; }
        .quick-item:hover { border-color:var(--accent); box-shadow:0 6px 16px rgba(16,24,40,.10); transform:translateY(-2px); }
        .quick-item .font-weight-bold { font-size:12.5px; line-height:1.25; }
        .quick-item .text-muted { color:var(--accent) !important; font-weight:700; font-size:13px; }
        /* Product image is hidden by default (Classic/Modern stay text-only); Compact shows it. */
        .quick-item .qi-img { display:none; }

        .cust-wrap { position:relative; margin-bottom:12px; }
        #cust-search { border-radius:10px; height:42px; }
        #cust-info { background:var(--soft); border:1px solid var(--line); border-radius:8px; padding:6px 10px; }

        .cart-table { margin-bottom:0; }
        .cart-table thead th { font-size:10.5px; text-transform:uppercase; letter-spacing:.03em; color:var(--muted); border-top:0; border-bottom:1px solid var(--line); padding:8px 6px; }
        .cart-table td { vertical-align:middle; font-size:13px; padding:9px 6px; border-color:#f4f4f7; }
        .cart-name { font-weight:600; line-height:1.25; }
        .cart-sub { font-size:11px; color:#c0392b; }
        .qty-stepper { display:inline-flex; align-items:center; gap:2px; border:1px solid var(--line); border-radius:9px; padding:2px; background:#fff; }
        .qty-stepper span { min-width:24px; text-align:center; font-weight:700; font-size:13px; }
        .qty-btn { width:26px; height:26px; line-height:1; padding:0; border-radius:7px; border:0; background:var(--soft); color:var(--ink); font-weight:700; cursor:pointer; }
        .qty-btn:hover { background:var(--accent); color:#fff; }
        .cart-del { color:#c0392b; cursor:pointer; font-size:16px; }
        .cart-del:hover { opacity:.7; }

        .totals { background:var(--soft); border:1px solid var(--line); border-radius:12px; padding:10px 14px; margin-top:12px; }
        .totals-row { display:flex; justify-content:space-between; padding:3px 0; font-size:13.5px; }
        .grand-bar { display:flex; justify-content:space-between; align-items:center; background:var(--accent); color:#fff; border-radius:12px; padding:12px 16px; margin-top:10px; }
        .grand-bar .lbl { font-size:13px; opacity:.92; } .grand-bar .val { font-size:24px; font-weight:800; }

        .pay-head { font-weight:700; font-size:13px; margin:16px 0 8px; }
        .denoms { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px; }
        .denoms .btn { border-radius:8px; }
        .pay-leg { display:flex; gap:8px; margin-bottom:6px; flex-wrap:wrap; }
        .pay-leg .form-control { border-radius:8px; }
        /* Keep cash / amount / ref on one row (BS .form-control is width:100% otherwise each wraps) */
        .pay-leg .pay-mode   { flex:0 0 88px; width:auto; }
        .pay-leg .pay-amount { flex:1 1 0; min-width:0; width:auto; }
        .pay-leg .pay-ref    { flex:1 1 0; min-width:0; width:auto; }

        /* Customer + branch share one row */
        .cam-row { display:flex; gap:8px; align-items:flex-start; margin-bottom:12px; }
        .cam-row > .cam-col { flex:1 1 50%; min-width:0; }
        .cam-row .cust-wrap { margin-bottom:0; }
        @media (max-width: 575.98px) {
            .cam-row { flex-wrap:wrap; }
            .cam-row > .cam-col { flex:1 1 100%; }
        }

        #btn-finalize, #btn-finalize:hover, #btn-finalize:focus, #btn-finalize:active {
            border-radius:12px; font-weight:700; border:0; color:#fff;
            background:var(--accent) !important; box-shadow:0 6px 16px rgba(0,0,0,.12); }
        #btn-finalize:hover { filter:brightness(.93); }
        #btn-hold { border-radius:12px; font-weight:600; }

        /* Keep the "Bill Discount ₹ [input]" on one line */
        .totals-row > span { white-space:nowrap; }
        .totals-row #bill-discount { width:80px; }
        /* Cart table scrolls horizontally instead of overflowing the card */
        .cart-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; }

        /* ── Tablet: stack the two panels ─────────────────────────────────── */
        @media (max-width: 991.98px) {
            .pos-wrap { flex-direction:column; }
            .pos-left, .pos-right { flex:1 1 100%; width:100%; }
            .pos-right { position:static; top:auto; }
        }

        /* ── Phone ───────────────────────────────────────────────────────── */
        @media (max-width: 767.98px) {
            .rpos .pos-topbar { margin-bottom:12px; }
            .rpos .pos-topbar h1 { font-size:19px; }
            .pc-hd { flex-wrap:wrap; gap:8px; padding:11px 13px; font-size:13px; }
            .pc-hd > span { flex:1 1 auto; }
            .pc-bd { padding:12px; }
            #pos-search { height:46px; font-size:14px; }
            .btn-scan { padding:7px 11px; font-size:11.5px; }

            /* Categories become a horizontal scroll strip above the item grid */
            .items-row { flex-direction:column; gap:10px; }
            .cat-bar { flex:0 0 auto; width:100%; flex-direction:row; max-height:none;
                       overflow-x:auto; overflow-y:hidden; border-right:0;
                       border-bottom:1px solid var(--line); padding:0 0 8px;
                       -webkit-overflow-scrolling:touch; }
            .cat-tab { flex:0 0 auto; }
            .quick-grid { max-height:none; grid-template-columns:repeat(auto-fill,minmax(102px,1fr)); }

            /* Compact cart so all columns fit a phone width */
            .cart-table thead th, .cart-table td { font-size:11.5px; padding:7px 3px; }
            .qty-stepper { gap:1px; padding:1px; }
            .qty-btn { width:22px; height:22px; }
            .qty-stepper input[type="number"] { width:44px !important; }

            .grand-bar .val { font-size:20px; }
            .pay-leg .form-control { flex:1 1 100%; }
        }

        /* ════════════ PER-TEMPLATE COLOR PALETTES ════════════
           Each template overrides the theme variables, so the whole UI (headers, buttons,
           totals bar, active tabs, accents) recolours automatically. Two classes outrank `.rpos`. */
        /* Classic keeps the store's own theme colour (inherits --accent from .rpos → --primary). */
        .rpos.rpos-tpl-compact { --accent:#0d9488; --accent-dark:#0f766e; --accent2:#5eead4; --soft:#ecfdf5; }
        .rpos.rpos-tpl-modern  { --accent:#db2777; --accent-dark:#9d174d; --accent2:#f9a8d4; --soft:#fdf2f8; }
        /* Tint the whole screen background to reinforce each palette (Classic stays neutral). */
        .rpos-tpl-compact { background:#f1fbf7; }
        .rpos-tpl-modern  { background:#fdf4f9; }

        /* ════════════════ UI TEMPLATE: COMPACT ════════════════
           Cart-first, dense layout for fast billing. Cart panel is wider and shown first. */
        .rpos-tpl-compact .pos-wrap { flex-direction:row-reverse; }
        .rpos-tpl-compact .pos-right { flex:1 1 54%; }
        .rpos-tpl-compact .pos-left  { flex:1 1 46%; }
        .rpos-tpl-compact .pc-bd { padding:10px 12px; }
        .rpos-tpl-compact .pc-hd { padding:10px 13px; font-size:13px; }
        .rpos-tpl-compact .cart-table td { padding:6px 5px; font-size:12.5px; }
        .rpos-tpl-compact .cart-table thead th { padding:6px 5px; }
        /* Compact = image-card grid: photo on top, name + price below. */
        .rpos-tpl-compact .quick-grid { grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:10px; max-height:440px; }
        .rpos-tpl-compact .quick-item { min-height:auto; padding:6px 6px 9px; justify-content:flex-start; gap:4px; text-align:center; }
        .rpos-tpl-compact .quick-item .qi-img { display:block; width:100%; height:78px; object-fit:cover; border-radius:6px; background:#eef2f7; margin-bottom:3px; }
        .rpos-tpl-compact .quick-item .font-weight-bold { font-size:12px; }
        .rpos-tpl-compact #pos-search { height:44px; font-size:14px; }
        .rpos-tpl-compact .totals { padding:8px 12px; margin-top:10px; }
        .rpos-tpl-compact .grand-bar { padding:10px 14px; }
        .rpos-tpl-compact .grand-bar .val { font-size:21px; }
        @media (max-width: 991.98px) { .rpos-tpl-compact .pos-wrap { flex-direction:column; } }

        /* ════════════════ UI TEMPLATE: MODERN ════════════════
           Bold, rounded, touch-friendly tiles with a gradient header. */
        .rpos-tpl-modern .pos-card { border:none; border-radius:20px; box-shadow:0 8px 30px rgba(16,24,40,.10); }
        .rpos-tpl-modern .pc-hd { background:linear-gradient(135deg, var(--accent), var(--accent-dark)); font-size:15px; padding:15px 18px; }
        .rpos-tpl-modern #pos-search { height:56px; border-radius:16px; font-size:16px; }
        .rpos-tpl-modern .quick-grid { grid-template-columns:repeat(auto-fill,minmax(142px,1fr)); gap:14px; }
        .rpos-tpl-modern .quick-item { border-radius:16px; min-height:92px; font-size:13px; box-shadow:0 2px 10px rgba(16,24,40,.06); border-color:#eceefb; }
        .rpos-tpl-modern .quick-item:hover { box-shadow:0 10px 24px rgba(16,24,40,.14); }
        .rpos-tpl-modern .cat-tab { border-radius:12px; padding:11px 13px; }
        .rpos-tpl-modern .totals { border-radius:16px; padding:14px 18px; }
        .rpos-tpl-modern .grand-bar { border-radius:18px; padding:18px 22px; background:linear-gradient(135deg, var(--accent), var(--accent-dark)); }
        .rpos-tpl-modern .grand-bar .val { font-size:30px; }
        .rpos-tpl-modern #btn-finalize, .rpos-tpl-modern #btn-finalize:hover, .rpos-tpl-modern #btn-finalize:active {
            height:54px; font-size:16px; border-radius:16px;
            background:linear-gradient(135deg, var(--accent), var(--accent-dark)) !important; }
        .rpos-tpl-modern .cat-tab.active { background:linear-gradient(135deg, var(--accent), var(--accent-dark)); border-color:var(--accent); }
        .rpos-tpl-modern .qty-btn { width:30px; height:30px; border-radius:9px; }
        .rpos-tpl-modern .qty-btn:hover { background:var(--accent); }
        .rpos-tpl-modern #cust-search { height:48px; border-radius:12px; }
        .rpos-tpl-modern .pos-topbar h1 { color:var(--accent-dark); }

        /* ── Compact: flat, square, no-frills (visually opposite of Modern) ── */
        .rpos-tpl-compact .pos-card { border-radius:8px; }
        .rpos-tpl-compact .quick-item { border-radius:7px; }
        .rpos-tpl-compact .totals, .rpos-tpl-compact .grand-bar { border-radius:8px; }
        .rpos-tpl-compact .pos-topbar h1 { color:var(--accent-dark); }
.cat-tab{
    min-height: 37px;
}
        /* ════════════════ UI TEMPLATE: CLASSIC ════════════════
           Clean list-style products: name on the left, price on the right, accent left border.
           (Compact = image cards · Modern = big rounded tiles · Classic = list rows.) */
        .rpos-tpl-classic .quick-grid { grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:8px; }
        .rpos-tpl-classic .quick-item { flex-direction:row; justify-content:flex-start; align-items:center;
            text-align:left; min-height:auto; padding:11px 14px; border-left:3px solid var(--accent); border-radius:8px; }
        .rpos-tpl-classic .quick-item:hover { background:var(--soft); transform:none; }
        .rpos-tpl-classic .quick-item .font-weight-bold { font-size:13px; }
        .rpos-tpl-classic .quick-item .text-muted { font-size:14px; white-space:nowrap; margin-left:auto; }
        .rpos-tpl-classic .quick-item::after { content:"＋"; color:var(--accent); font-weight:700; font-size:15px; margin-left:8px; opacity:.55; }
        .rpos-tpl-classic .pos-topbar h1 { color:var(--primary,#754BFF); }

        /* ════════════════ UI TEMPLATE: SEARCH-FIRST ════════════════
           Minimal — a single big search bar. Matching products appear below as you
           type; the always-visible product grid + category bar are hidden. */
        .rpos.rpos-tpl-search { --accent:#0ea5e9; --accent-dark:#0369a1; --accent2:#7dd3fc; --soft:#f0f9ff; }
        .rpos-tpl-search { background:#f2fbff; }
        .rpos-tpl-search .items-row { display:none; }          /* hide grid + categories */
        .rpos-tpl-search .pc-bd { padding:18px 18px 22px; }
        .rpos-tpl-search #pos-search { height:62px; font-size:18px; border-radius:14px; padding:0 18px; }
        .rpos-tpl-search .pc-hd span { font-size:15px; }
        .rpos-tpl-search .pos-results { max-height:none; margin-top:14px; border:0; }
        .rpos-tpl-search .pos-results .res-row { padding:14px 18px; font-size:15px; }
        /* Highlight each matched product as a tappable card. */
        .rpos-tpl-search .pos-results .res-row[data-it] {
            background:#f0f9ff; border:1px solid #bae6fd; border-left:4px solid var(--accent);
            border-radius:10px; margin:8px 0; font-weight:600; color:#0f3460;
        }
        .rpos-tpl-search .pos-results .res-row[data-it]:hover { background:#dbeefe; border-color:var(--accent); }
        .rpos-tpl-search .pos-results .res-row[data-it] small { font-weight:400; color:#64748b; }
        .rpos-tpl-search .search-hint { display:block; }
        .rpos-tpl-search .pos-topbar h1 { color:var(--accent-dark); }

        /* Two columns kept: the cart/checkout stays on the right (sticky, as before).
           Only the selected-items table is moved to the LEFT, below the search (via JS). */
        .rpos-tpl-search .selected-items-hd {
            font-weight:600; color:var(--accent-dark); font-size:13px;
            margin:16px 0 8px; padding-top:14px; border-top:1px solid var(--line);
        }
        /* Show every selected row in full — no fixed height / inner scroll. */
        .rpos-tpl-search .cart-scroll { overflow:visible; max-height:none; }
        /* Avoid the load flash: the items table is hidden while it's still in the right
           panel, then JS relocates it under the search (left) where this rule no longer
           matches, so it appears only in its final position. */
        .rpos-tpl-search .pos-right .cart-scroll { display:none; }

        /* Excel-style selected-items grid (bordered cells, header fill, zebra rows). */
        .rpos-tpl-search .cart-table { border-collapse:collapse; width:100%; }
        .rpos-tpl-search .cart-table thead th,
        .rpos-tpl-search .cart-table td { border:1px solid #cbd5e1; padding:8px 8px; }
        .rpos-tpl-search .cart-table thead th { background:#eef4fb; color:#334155; }
        .rpos-tpl-search .cart-table tbody tr:nth-child(even) td { background:#f8fafc; }
        /* hint is hidden in every other template */
        .search-hint { display:none; text-align:center; color:#7c8aa0; font-size:14px; padding:18px 10px 4px; }

        /* ════════════ KIOSK MODE (full-screen New Sale only) ════════════
           While full-screen, hide the panel chrome and show just the billing screen. */
        body.pos-kiosk #header,
        body.pos-kiosk .navbar-vertical-aside,
        body.pos-kiosk .footer { display:none !important; }
        body.pos-kiosk .main { padding-left:0 !important; padding-top:0 !important; }
        body.pos-kiosk .content.rpos { padding-top:14px; }

        /* Variations selection modal styles */
        .var-select-btn {
            display: flex;
            justify-content: space-between; 
            align-items: center;
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 8px;
            border: 1.5px solid #e7eaf3;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            transition: all 0.15s ease;
            font-size: 13.5px;
            text-align: left;
            color: #212b36;
            font-weight: 600;
        }
        .var-select-btn:hover {
            transform: translateY(-1.5px);
            box-shadow: 0 4px 10px rgba(0,0,0,.03);
            text-decoration: none;
            color: #fff !important;
             background: var(--primary);
        }
        .var-select-btn.main-opt {
            border: 2px dashed var(--accent);
            background-color: var(--soft);
        }
        .var-select-btn .badge-pill {
            font-size: 13px;
            padding: 6px 10px;
            background-color: var(--accent); 
            border-radius: 50rem;
            font-weight: 700;
        }
        .var-select-btn.main-opt .badge-pill {
            background-color: #6c757d;
        }

        /* Springy pop-up modal animation */
        @keyframes modalPopIn {
            0% { transform: scale(0.85); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .modal.show #variationSelectModal,
        .modal.show .modal-dialog {
            animation: modalPopIn 0.22s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        .offer-chip {
            display: flex; align-items: center; justify-content: space-between;
            border: 1px solid #cdefe0; background: #f2fbf7; border-radius: 8px;
            padding: 8px 10px; margin-bottom: 6px;
        }
        .offer-chip-title { font-weight: 700; font-size: 12.5px; color: #14794f; }
        .offer-chip-sub { font-size: 11px; color: #5b6b64; }
        .offer-chip-x {
            border: none; background: transparent; color: #c0392b;
            font-size: 20px; line-height: 1; cursor: pointer; padding: 0 4px;
        }
        .offer-row-modal {
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #f0f0f2; padding: 10px 4px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid rpos rpos-tpl-{{ $uiTemplate ?? 'classic' }}">
        <div class="pos-topbar">
            <div>
                <h1 class="page-header-title">Retail POS</h1>
                <div class="sub">Billing &amp; checkout</div>
                @if (!empty($shiftStatus))
                    @if ($shiftStatus['on'])
                        <div class="rp-shift-pill on" title="You are the active staff on this counter right now">
                            🟢 On shift{{ $shiftStatus['staff'] ? ' · ' . $shiftStatus['staff'] : '' }}{{ $shiftStatus['counter'] ? ' · ' . $shiftStatus['counter'] : '' }}{{ $shiftStatus['shift'] ? ' · 🕒 ' . $shiftStatus['shift'] : '' }}{{ $shiftStatus['auto'] ? ' · auto' : '' }}
                        </div>
                    @else
                        <div class="rp-shift-pill off" title="You are not the active counter staff right now — you may not be able to bill">
                            ⚠ Not the active counter staff right now{{ $shiftStatus['shift'] ? ' · your shift 🕒 ' . $shiftStatus['shift'] : '' }}
                        </div>
                    @endif
                @endif
            </div>
            <div class="d-flex" style="gap:8px;">
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-fullscreen" title="Toggle full screen">⛶ Full screen</button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-all-offers" title="View all offers">🎁 All Offers</button>
                @if (hasPermission('pos_bills', 'view'))
                    <a href="{{ route('vendor.retail-pos.today') }}" class="btn btn-sm btn-outline-primary">📋 Today's Bills</a>
                @endif
            </div>
        </div>

        <div class="pos-wrap">
            <!-- LEFT: scan / search / quick items -->
            <div class="pos-left">
                <div class="pos-card">
                    <div class="pc-hd">
                        <span>🔎 Add Items</span>
                        <button type="button" class="btn-scan" id="btn-cam-scan">📷 Scan with camera</button>
                    </div>
                    <div class="pc-bd">
                        <input type="text" id="pos-search" class="form-control" autofocus autocomplete="off"
                            placeholder="Scan with USB scanner, or type name / SKU / barcode, then Enter">
                        <div class="search-hint">🔎 Start typing a product name, SKU or barcode — matches appear below.</div>
                        <div class="pos-results" id="pos-results" style="display:none;"></div>

                        {{-- Camera scanner overlay --}}
                        <div id="cam-scan" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.75); z-index:1080; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:14px; padding:16px; width:340px; max-width:92vw; text-align:center;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <b>Scan a barcode</b>
                                    <a href="javascript:;" id="cam-close" class="text-danger" style="font-size:20px;">&times;</a>
                                </div>
                                <div id="cam-reader" style="width:100%;"></div>
                                <div class="small text-muted mt-2" id="cam-msg">Point the camera at the product barcode.</div>
                            </div>
                        </div>

                        <div class="items-row">
                            <div class="cat-bar" id="cat-tabs">
                                <div class="cat-tab active" data-cat="all">All</div>
                                <div class="cat-tab" data-cat="">★ Popular</div>
                                @foreach ($categories as $c)
                                    <div class="cat-tab" data-cat="{{ $c->id }}">{{ $c->name }}</div>
                                @endforeach
                            </div>

                            <div class="quick-grid" id="quick-grid">
                                @foreach ($quickItems as $qi)
                                    @php
                                        $qiData = [
                                            'id' => (string) $qi->id,
                                            'name' => $qi->item_name,
                                            'price' => (float) ($qi->selling_price ?? 0),
                                            'hsn' => $qi->hsn,
                                            'gst_rate' => (float) ($qi->gst_rate ?? 0),
                                            'gst_status' => $qi->gst_status ?? 'excluding',
                                            'unit' => $qi->unit,
                                            'variations' => json_decode($qi->variations, true) ?: [],
                                        ];
                                    @endphp
                                    <div class="quick-item" data-item='@json($qiData)'>
                                        <img class="qi-img" src="{{ $qi->image ? asset('storage/app/public/inventory-item/' . $qi->image) : asset('public/assets/admin/img/160x160/img2.jpg') }}" onerror="this.src='{{ asset('public/assets/admin/img/160x160/img2.jpg') }}'" alt="">
                                        <div class="font-weight-bold text-truncate">{{ $qi->item_name }}</div>
                                        <div class="text-muted">₹{{ number_format((float) ($qi->selling_price ?? 0), 2) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: cart + payment -->
            <div class="pos-right">
                <div class="pos-card">
                    <div class="pc-hd">
                        <span>🧾 Current Sale</span>
                        @if (hasPermission('pos_billing', 'resume'))
                            <button type="button" class="btn btn-xs btn-outline-secondary" id="btn-held">Held Bills <span class="badge badge-primary" id="held-count">{{ count($heldBills) }}</span></button>
                        @endif
                    </div>
                    <div class="pc-bd">
                        <div class="cam-row">
                            <div class="cust-wrap cam-col">
                                <input type="hidden" id="pos-customer" value="0">
                                <input type="text" id="cust-search" class="form-control" autocomplete="off"
                                    placeholder="👤 Walk-in Customer — type name/phone to link">
                                <div class="pos-results" id="cust-results" style="display:none; position:absolute; z-index:20; width:100%;"></div>
                                <div id="cust-info" class="small text-muted mt-1" style="display:none;"></div>
                            </div>

                            @if ($branchLocked)
                                <input type="hidden" id="pos-branch" value="{{ $myBranchId }}">
                                <div class="cam-col"><div class="rp-mini">🏬 Billing at <b>{{ optional($branches->firstWhere('id', $myBranchId))->name }}</b></div></div>
                            @elseif ($branches->count())
                                <div class="cam-col">
                                    <select id="pos-branch" class="form-control">
                                        <option value="" {{ !$defaultBranchId ? 'selected' : '' }}>🏬 Main Store (no branch)</option>
                                        @foreach ($branches as $b)
                                            <option value="{{ $b->id }}" {{ $defaultBranchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <input type="hidden" id="pos-branch" value="">
                            @endif
                        </div>

                        <div id="held-panel" class="pos-results mb-2" style="display:none;">
                            @forelse ($heldBills as $h)
                                <div class="res-row">
                                    <span><b>{{ $h['hold_code'] }}</b> · {{ $h['customer'] }} · {{ $h['item_count'] }} items · {{ $h['held_at'] }}</span>
                                    <span>
                                        @if (hasPermission('pos_billing', 'resume'))
                                            <a class="btn btn-sm btn-primary mr-2" style="cursor:pointer" onclick="resumeHeld({{ $h['id'] }})">Resume</a>
                                        @endif
                                        @if (hasPermission('pos_billing', 'hold'))
                                            <a class="text-danger" style="font-size:16px;cursor:pointer" onclick="deleteHeld({{ $h['id'] }})"><i class='tio-delete'></i></a>
                                        @endif
                                    </span>
                                </div>
                            @empty
                                <div class="res-row text-muted">No held bills</div>
                            @endforelse
                        </div>

                        <div class="cart-scroll">
                            <table class="table cart-table" style="min-width:330px;">
                                <thead>
                                    <tr>
                                        <th>Item</th><th width="120">Qty</th><th width="70" class="text-right">Rate</th><th width="80" class="text-right">Total</th><th width="28"></th>
                                    </tr>
                                </thead>
                                <tbody id="cart-body">
                                    <tr id="cart-empty"><td colspan="5" class="text-center text-muted py-4">🛒 Cart is empty — scan or pick an item</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="totals">
                            <div class="totals-row"><span>Subtotal (taxable)</span><span>₹<span id="t-subtotal">0.00</span></span></div>
                            <div class="totals-row" id="gst-row" style="{{ ($gstOn ?? true) ? '' : 'display:none;' }}"><span>GST</span><span>₹<span id="t-tax">0.00</span></span></div>
                            @if (hasPermission('pos_bill_discount', 'apply'))
                                <div class="totals-row align-items-center">
                                    <span>Bill Discount</span>
                                    <span>₹<input type="number" id="bill-discount" value="0" min="0" step="0.01" style="width:90px" class="form-control form-control-sm d-inline-block"></span>
                                </div>
                            @else
                                <input type="hidden" id="bill-discount" value="0">
                            @endif
                            <div class="totals-row" id="offer-discount-row" style="display:none;"><span>Offer Discount</span><span>− ₹<span id="t-offer-disc">0.00</span></span></div>
                            <div class="totals-row" id="coupon-discount-row" style="display:none;"><span>Coupon (<span id="coupon-code-label"></span>)</span><span>− ₹<span id="t-coupon-disc">0.00</span></span></div>
                        </div>
                        <div class="grand-bar"><span class="lbl">Total Payable</span><span class="val">₹<span id="t-grand">0.00</span></span></div>

                        <div class="pay-head">💳 Payment</div>

                        <div class="denoms" id="denoms">
                        <button type="button" class="btn btn-sm btn-outline-secondary denom" data-d="500">₹500</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary denom" data-d="200">₹200</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary denom" data-d="100">₹100</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary denom" data-d="50">₹50</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary denom" data-d="20">₹20</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="denom-exact">Exact ₹</button>
                    </div>

                    <div id="pay-legs">
                        <div class="pay-leg">
                            <select class="form-control form-control-sm pay-mode">
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="card">Credit Card</option>
                                <option value="debit">Debit Card</option>
                                <option value="wallet">Wallet</option>
                            </select>
                            <input type="number" class="form-control form-control-sm pay-amount" placeholder="Amount" step="0.01" data-auto="1">
                            <input type="text" class="form-control form-control-sm pay-ref" placeholder="Ref (optional)">
                            <input type="text" class="form-control form-control-sm pay-approval" placeholder="Last 4 / Approval" style="display:none">
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link p-0" id="add-leg">+ Add payment</button>

                    <div id="upi-qr" class="text-center my-2" style="display:none;">
                        <img id="upi-qr-img" alt="UPI QR" style="width:150px;height:150px;border:1px solid #eee;border-radius:8px">
                        <div class="small text-muted mt-1" id="upi-qr-note">Scan to pay via any UPI app</div>
                    </div>

                    <div class="totals-row mt-1"><span>Tendered</span><span>₹<span id="t-paid">0.00</span></span></div>
                    <div class="totals-row" id="change-row" style="display:none;color:#1e7e34;font-weight:700;border:2px dashed #1e7e34;background:#eafaf1;border-radius:8px;padding:9px 12px;margin-top:8px;"><span>💵 Change to return</span><span>₹<span id="t-change">0.00</span></span></div>
                    <div class="totals-row" id="due-row" style="display:none;color:#c0392b;font-weight:700;border:2px dashed #c0392b;background:#fdecea;border-radius:8px;padding:9px 12px;margin-top:8px;"><span>Balance (credit)</span><span>₹<span id="t-due">0.00</span></span></div>

                        <div id="promo-section" class="mt-3" style="display:none;">
                            <div id="promo-toggle" class="d-flex align-items-center justify-content-between"
                                style="cursor:pointer; padding:8px 10px; background:#f6f7fb; border-radius:8px;">
                                <span style="font-weight:600;">🎁 Offers &amp; Coupons <span id="promo-badge"
                                        class="badge badge-danger ml-1" style="display:none;"></span></span>
                                <span id="promo-chevron">&#9662;</span>
                            </div>
                            <div id="promo-body" style="display:none; padding-top:6px;">
                        <div id="offer-panel" style="display:none;">
                            <div class="pay-head" style="margin-bottom:6px;">🎁 Applicable Offers</div>
                            <div id="offer-list"></div>
                            <div id="offer-manual" style="display:none;">
                                <div class="d-flex" style="gap:6px;">
                                    <input type="text" id="offer-code-input" class="form-control form-control-sm"
                                        placeholder="Enter offer code" autocomplete="off">
                                    <button type="button" class="btn btn-sm btn--primary" id="offer-code-apply">Apply</button>
                                </div>
                                <div class="offer-chip-sub mt-1" id="offer-manual-msg"></div>
                            </div>
                        </div>

                        <div id="coupon-panel" class="mt-3" style="display:none;">
                            <div class="pay-head" style="margin-bottom:6px;">🏷️ Coupon</div>
                            <div id="coupon-applied"></div>
                            <div id="coupon-entry">
                                <div class="d-flex" style="gap:6px;">
                                    <input type="text" id="coupon-code-input" class="form-control form-control-sm"
                                        placeholder="Enter coupon code" autocomplete="off">
                                    <button type="button" class="btn btn-sm btn--primary" id="coupon-apply-btn">Apply</button>
                                </div>
                                <div id="coupon-available" class="mt-2"></div>
                                <div class="offer-chip-sub mt-1" id="coupon-msg"></div>
                            </div>
                        </div>
                            </div>{{-- /promo-body --}}
                        </div>{{-- /promo-section --}}

                        <div class="d-flex gap-2 mt-3 cart-actions">
                            @if (hasPermission('pos_billing', 'hold'))
                                <button type="button" class="btn btn-outline-warning btn-lg" id="btn-hold" style="flex:0 0 32%;">Hold</button>
                            @endif
                            @if (hasPermission('pos_billing', 'create'))
                                <button type="button" class="btn btn--primary btn-lg" id="btn-finalize" style="flex:1;">
                                    Finalize &amp; Print (F12)
                                </button>
                            @endif
                        </div>
                    </div>{{-- /pc-bd --}}
                </div>{{-- /pos-card --}}
            </div>{{-- /pos-right --}}
        </div>{{-- /pos-wrap --}}
    </div>
 
    {{-- Reuse the standard Add New Customer modal --}}
    @include('vendor-views/form_modals/customer_add')

    <!-- Variation Selection Modal -->
    <div class="modal" id="variationSelectModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                <div class="modal-header" style="padding: 12px 16px; border-bottom: 1px solid #f0f0f2;">
                    <h5 class="modal-title" id="varModalTitle" style="color: #212b36; font-size: 14px; font-weight: 700;">Select Option</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="padding: 12px 16px; margin: -12px -16px -12px auto;">
                        <span aria-hidden="true" style="font-size: 20px;">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 12px;">
                    <div id="varModalList" class="list-group">
                        <!-- Dynamic variation rows -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- All Offers Modal -->
    <div class="modal" id="allOffersModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                <div class="modal-header" style="padding: 12px 16px; border-bottom: 1px solid #f0f0f2;">
                    <h5 class="modal-title" style="color: #212b36; font-size: 14px; font-weight: 700;">🎁 All Offers</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="padding: 12px 16px; margin: -12px -16px -12px auto;">
                        <span aria-hidden="true" style="font-size: 20px;">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 12px; max-height: 65vh; overflow-y: auto;">
                    <div id="allOffersList">
                        <div class="text-center text-muted py-3">Loading…</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        const POS = {
            products: "{{ route('vendor.retail-pos.products') }}",
            finalize: "{{ route('vendor.retail-pos.finalize') }}",
            customers: "{{ route('vendor.retail-pos.customers') }}",
            offersAll: "{{ route('vendor.retail-pos.offers.all') }}",
            offersMatch: "{{ route('vendor.retail-pos.offers.match') }}",
            offersApplyCode: "{{ route('vendor.retail-pos.offers.apply-code') }}",
            coupons: "{{ route('vendor.retail-pos.coupons') }}",
            couponApply: "{{ route('vendor.retail-pos.coupon-apply') }}",
            hold: "{{ route('vendor.retail-pos.hold') }}",
            defaultBranch: "{{ route('vendor.retail-pos.default-branch') }}",
            held: "{{ route('vendor.retail-pos.held') }}",
            resumeUrl: "{{ route('vendor.retail-pos.resume', ['id' => '__ID__']) }}",
            heldDelUrl: "{{ route('vendor.retail-pos.held.delete', ['id' => '__ID__']) }}",
            csrf: "{{ csrf_token() }}",
            cart: [],
            holdId: null,
            gstOn: @json($gstOn ?? true),
            matchedOffers: [],
            removedOffers: [],
            offerDiscount: 0,
            coupon: null,
            availableCoupons: [],
            upiId: @json($upiId ?? null),
            storeName: @json($storeName ?? 'Store'),
            canHold: @json(hasPermission('pos_billing', 'hold')),
            canResume: @json(hasPermission('pos_billing', 'resume')),
            canCreate: @json(hasPermission('pos_billing', 'create')),
        };

        // Hardware desktop agent (Electron/localhost bridge). All calls fail silently
        // if the agent isn't running — the browser thermal window remains the fallback.
        // Contract documented in app/Modules/PosRetail/HARDWARE_AGENT.md
        const POSAgent = {
            base: 'http://localhost:9100',
            call: function (path, body) {
                return fetch(this.base + path, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body || {}),
                }).then(r => r.json()).catch(() => null);
            },
            openDrawer: function () { return this.call('/drawer/open'); },
            printReceipt: function (url) { return this.call('/print', { url: url, format: '80mm' }); },
            readScale: function () { return this.call('/scale/read').then(r => r && r.weight); },
            // Returns true if the hardware agent handled the print (so we skip the browser print).
            afterSale: function (d, cashUsed) {
                if (cashUsed) this.openDrawer();
                if (!d.thermal_url) return Promise.resolve(false);
                return this.printReceipt(d.thermal_url).then(function (r) { return r != null; });
            },
        };

        // Direct-print the receipt in place (hidden iframe) — like the POS token, no new tab.
        // The thermal page auto-calls window.print() on load.
        function directPrintReceipt(url, html) {
            var f = document.createElement('iframe');
            f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
            // Prefer the inlined HTML (no server round-trip); fall back to loading the URL.
            if (html) { f.srcdoc = html; } else { f.src = url; }
            document.body.appendChild(f);
            setTimeout(function () { try { f.parentNode && f.parentNode.removeChild(f); } catch (e) {} }, 60000);
        }

        function money(n) { return (Math.round(n * 100) / 100).toFixed(2); }
        function posBranch() { const el = document.getElementById('pos-branch'); return el ? (el.value || '') : ''; }

        let activeVariations = [];
        let activeParentItem = null;
        function addToCart(item) {
            // Check if item has variations that we need to select from
            if (item.variations && item.variations.length > 0) {
                activeVariations = item.variations;
                activeParentItem = item;
                const modal = $('#variationSelectModal');
                $('#varModalTitle').text(item.name + ' - Select Option');
                
                // Add the Main Product (Default) option as the first choice
                const mainProductPrice = parseFloat(item.price) || 0;
                const mainProductStock = parseFloat(item.stock) || 0;
                let html = `<button type="button" class="var-select-btn " onclick="selectMainProduct()">
                    <span><b>Main Product (Default)</b></span>
                    <span class="badge-pill">₹${money(mainProductPrice)} <small class="">(stk ${mainProductStock})</small></span>
                </button>`; 

                item.variations.forEach((varItem, idx) => {
                    if (varItem.type) {
                        const price = parseFloat(varItem.price) || parseFloat(item.price) || 0;
                        const stock = parseFloat(varItem.stock) || 0;
                        html += `<button type="button" class="var-select-btn" onclick="selectVarIndex(${idx})">
                            <span><b>${varItem.type}</b></span>
                            <span class="badge-pill">₹${money(price)} <small class="">(stk ${stock})</small></span>
                        </button>`;
                    }
                });
                $('#varModalList').html(html);
                modal.modal('show');
                return;
            }

            if (window.toastr) {
                if (item.expiry_warn) toastr.warning(item.name + ' expires ' + (item.expiry || 'soon'), 'Expiry warning');
                if (item.low_stock) toastr.warning(item.name + ' is out of stock', 'Stock');
            }
            let line = POS.cart.find(l => l.id == item.id);
            if (line) { line.qty += 1; }
            else {
                if (POS.cart.length >= 500) { (window.toastr ? toastr.error : alert)('Maximum 500 line items per bill'); return; }
                POS.cart.push({
                    id: item.id, name: item.name, price: parseFloat(item.price) || 0,
                    qty: 1, discount: 0, hsn: item.hsn || '',
                    gst_rate: parseFloat(item.gst_rate) || 0, gst_status: item.gst_status || 'excluding',
                    unit: item.unit || '',
                    // Loose item — weighed on the scale at sale time; billed weight × price/unit.
                    // `pieces` is an optional count (e.g. 4 apples) recorded alongside the weight.
                    sell_loose: !!item.sell_loose,
                    pieces: item.sell_loose ? 1 : null,
                });
                // For a loose item, jump straight to the scale so the weight is captured.
                if (item.sell_loose) weighLine(POS.cart.length - 1);
            }
            renderCart();
        }

        function selectMainProduct() {
            $('#variationSelectModal').modal('hide');
            if (!activeParentItem) return;
            
            const parentData = {
                id: activeParentItem.id,
                name: activeParentItem.name,
                price: parseFloat(activeParentItem.price) || 0,
                mrp: parseFloat(activeParentItem.mrp) || 0,
                hsn: activeParentItem.hsn || '',
                gst_rate: parseFloat(activeParentItem.gst_rate) || 0,
                gst_status: activeParentItem.gst_status || 'excluding',
                unit: activeParentItem.unit || '',
                sell_loose: !!activeParentItem.sell_loose,
                expiry: activeParentItem.expiry || '',
                expiry_warn: !!activeParentItem.expiry_warn,
                low_stock: parseFloat(activeParentItem.stock) <= 0
            };
            addToCart(parentData);
        }

        function selectVarIndex(idx) {
            $('#variationSelectModal').modal('hide');
            const varItem = activeVariations[idx];
            if (!varItem || !activeParentItem) return;
            
            const price = parseFloat(varItem.price) || parseFloat(activeParentItem.price) || 0;
            const mrp = parseFloat(varItem.mrpprice) || parseFloat(activeParentItem.mrp) || 0;
            const stock = parseFloat(varItem.stock) || 0;
            
            const varData = {
                id: activeParentItem.id + '-var-' + varItem.type,
                name: activeParentItem.name + ' (' + varItem.type + ')',
                price: price,
                mrp: mrp,
                hsn: activeParentItem.hsn || '',
                gst_rate: parseFloat(activeParentItem.gst_rate) || 0,
                gst_status: activeParentItem.gst_status || 'excluding',
                unit: activeParentItem.unit || '',
                sell_loose: !!activeParentItem.sell_loose,
                expiry: activeParentItem.expiry || '',
                expiry_warn: !!activeParentItem.expiry_warn,
                low_stock: stock <= 0
            };
            addToCart(varData);
        }

        // Weighing scale → set this line's qty (in the stock unit) from the connected scale.
        function weighLine(i) {
            POSAgent.readScale().then(w => {
                if (w && w > 0) { POS.cart[i].qty = Math.round(w * 1000) / 1000; renderCart(); }
                else { (window.toastr ? toastr.error : alert)('Scale not connected — enter weight manually'); }
            });
        }

        function renderCart() {
            const body = document.getElementById('cart-body');
            if (!POS.cart.length) {
                body.innerHTML = '<tr id="cart-empty"><td colspan="5" class="text-center text-muted py-3">Cart is empty</td></tr>';
            } else {
                body.innerHTML = POS.cart.map((l, i) => `
                    <tr>
                        <td>
                            <div class="cart-name">${l.name}</div>
                            ${l.sell_loose ? `<div class="cart-sub" style="color:#1a7d4f">loose · weighed${l.unit ? ' (' + l.unit + ')' : ''}</div>` : ''}
                            ${l.discount > 0 ? `<div class="cart-sub">− ₹${money(l.discount)} off</div>` : ''}
                        </td>
                        <td>
                            ${l.sell_loose ? `
                            <div class="qty-stepper" style="margin-bottom:4px;">
                                <input type="number" min="0" step="1" value="${l.pieces ?? ''}" placeholder="pcs" title="Pieces (e.g. 4 apples)"
                                    onchange="setPieces(${i}, this.value)" onfocus="this.select()"
                                    style="width:44px;text-align:center;border:0;background:transparent;font-weight:700;font-size:13px;-moz-appearance:textfield;">
                                <span style="font-size:11px;color:var(--muted)">pc</span>
                            </div>
                            <div class="qty-stepper">
                                <button class="qty-btn" onclick="chQty(${i},-1)">−</button>
                                <input type="number" min="0" step="0.001" value="${l.qty}" title="Weight (from scale)"
                                    onkeyup="liveQty(${i}, this.value)" onchange="setQty(${i}, this.value)" onfocus="this.select()"
                                    style="width:56px;text-align:center;border:0;background:transparent;font-weight:700;font-size:13px;-moz-appearance:textfield;">
                                <span style="font-size:11px;color:var(--muted)">${l.unit || 'kg'}</span>
                                <button class="qty-btn" onclick="chQty(${i},1)">+</button>
                                <button class="qty-btn" title="Weigh on scale" onclick="weighLine(${i})">⚖</button>
                            </div>` : `
                            <div class="qty-stepper">
                                <button class="qty-btn" onclick="chQty(${i},-1)">−</button>
                                <input type="number" min="0" step="1" value="${l.qty}"
                                    onkeyup="liveQty(${i}, this.value)" onchange="setQty(${i}, this.value)" onfocus="this.select()"
                                    style="width:56px;text-align:center;border:0;background:transparent;font-weight:700;font-size:13px;-moz-appearance:textfield;">
                                <button class="qty-btn" onclick="chQty(${i},1)">+</button>
                            </div>`}
                        </td>
                        <td class="text-right">${money(l.price)}</td>
                        <td class="text-right font-weight-bold" id="ltot-${i}">${money(l.price * l.qty - l.discount)}</td>
                        <td class="text-right"><a class="cart-del" title="Remove" onclick="rmLine(${i})"><i class="tio-delete"></i></a></td>
                    </tr>`).join('');
            }
            recalc();
            scheduleOfferMatch();
            scheduleCouponFetch();
            if (typeof updatePromoSection === 'function') updatePromoSection();
        }

        function chQty(i, d) {
            const l = POS.cart[i];
            const step = l.sell_loose ? 0.1 : 1;
            const min = l.sell_loose ? 0.001 : 1;
            l.qty = Math.max(min, Math.round((l.qty + d * step) * 1000) / 1000);
            renderCart();
        }
        // Live update as the cashier types (keyup) — refresh just this line's total and the
        // running totals, without re-rendering the cart (which would steal input focus).
        function liveQty(i, v) {
            const l = POS.cart[i];
            let q = parseFloat(v);
            if (!(q >= 0)) q = 0; // allow a transient/partial value mid-typing; normalised on blur
            l.qty = q;
            const cell = document.getElementById('ltot-' + i);
            if (cell) cell.textContent = money(l.price * l.qty - l.discount);
            recalc();
        }
        // Typed quantity / weight — loose lines accept decimals (kg from the scale or by hand),
        // normal lines stay whole. Price recalculates from the new quantity. Runs on blur/Enter
        // to normalise (clamp to a minimum) and redraw the row cleanly.
        function setQty(i, v) {
            const l = POS.cart[i];
            let q = parseFloat(v);
            if (!(q > 0)) q = l.sell_loose ? 0.001 : 1;
            l.qty = l.sell_loose ? Math.round(q * 1000) / 1000 : Math.max(1, Math.round(q));
            renderCart();
        }
        // Loose item piece count (e.g. 4 apples) — recorded & printed; price stays by weight.
        function setPieces(i, v) {
            const p = parseInt(v, 10);
            POS.cart[i].pieces = (p > 0) ? p : null;
            renderCart();
        }
        function rmLine(i) { POS.cart.splice(i, 1); renderCart(); }

        function recalc() {
            let subtotal = 0, tax = 0;
            POS.cart.forEach(l => {
                let gross = Math.max(0, l.price * l.qty - l.discount);
                let rate = POS.gstOn ? (l.gst_rate || 0) : 0;
                if (l.gst_status === 'including') {
                    let taxable = rate > 0 ? gross / (1 + rate / 100) : gross;
                    subtotal += taxable; tax += gross - taxable;
                } else {
                    subtotal += gross; tax += gross * rate / 100;
                }
            });
            let disc = parseFloat(document.getElementById('bill-discount').value) || 0;
            let offDisc = offerDiscountTotal();
            POS.offerDiscount = offDisc;
            let couponDisc = POS.coupon ? (parseFloat(POS.coupon.discount) || 0) : 0;
            let grand = Math.round(Math.max(0, subtotal + tax - disc - offDisc - couponDisc));
            document.getElementById('t-subtotal').textContent = money(subtotal);
            document.getElementById('t-tax').textContent = money(tax);
            document.getElementById('t-grand').textContent = money(grand);
            const offRow = document.getElementById('offer-discount-row');
            if (offDisc > 0) { offRow.style.display = 'flex'; document.getElementById('t-offer-disc').textContent = money(offDisc); }
            else { offRow.style.display = 'none'; }
            const couponRow = document.getElementById('coupon-discount-row');
            if (couponDisc > 0 && POS.coupon) {
                couponRow.style.display = 'flex';
                document.getElementById('coupon-code-label').textContent = POS.coupon.code;
                document.getElementById('t-coupon-disc').textContent = money(couponDisc);
            } else { couponRow.style.display = 'none'; }

            // Default the amount to the bill total (single leg, until manually changed).
            const legEls = document.querySelectorAll('.pay-leg');
            if (legEls.length === 1) {
                const amt0 = legEls[0].querySelector('.pay-amount');
                if (amt0.dataset.auto === '1') amt0.value = grand > 0 ? grand : '';
            }

            let paid = 0;
            document.querySelectorAll('.pay-leg .pay-amount').forEach(a => paid += parseFloat(a.value) || 0);
            document.getElementById('t-paid').textContent = money(paid);
            let diff = Math.round((grand - paid) * 100) / 100;
            const dueRow = document.getElementById('due-row');
            const changeRow = document.getElementById('change-row');
            if (paid > 0 && diff > 0.01) { dueRow.style.display = 'flex'; document.getElementById('t-due').textContent = money(diff); }
            else { dueRow.style.display = 'none'; }
            if (diff < -0.01) { changeRow.style.display = 'flex'; document.getElementById('t-change').textContent = money(-diff); }
            else { changeRow.style.display = 'none'; }
            window.__posGrand = grand;
            if (typeof updateUpiQr === 'function') updateUpiQr();
        }

        // ── Offers: matched offers are shown for the cashier to keep or remove; never auto-applied ──
        function offerIsRemoved(id) { return POS.removedOffers.indexOf(id) !== -1; }
        function selectedOffers() { return POS.matchedOffers.filter(o => !offerIsRemoved(o.id)); }
        function offerDiscountTotal() {
            return selectedOffers().reduce((s, o) => s + (parseFloat(o.discount) || 0), 0);
        }

        let offerMatchTimer = null;
        function scheduleOfferMatch() { clearTimeout(offerMatchTimer); offerMatchTimer = setTimeout(matchOffers, 350); }

        function matchOffers() {
            if (!POS.cart.length) { POS.matchedOffers = []; renderOfferPanel(); recalc(); return; }
            fetch(POS.offersMatch, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': POS.csrf },
                body: new URLSearchParams({
                    items: JSON.stringify(POS.cart),
                    customer_id: document.getElementById('pos-customer').value || 0,
                    branch_id: posBranch(),
                }),
            })
                .then(r => r.json())
                .then(d => {
                    POS.matchedOffers = d.offers || [];
                    const ids = POS.matchedOffers.map(o => o.id);
                    POS.removedOffers = POS.removedOffers.filter(id => ids.indexOf(id) !== -1);
                    renderOfferPanel(); recalc();
                })
                .catch(() => { POS.matchedOffers = []; renderOfferPanel(); recalc(); });
        }

        function renderOfferPanel() {
            const panel = document.getElementById('offer-panel');
            const list = document.getElementById('offer-list');
            const manual = document.getElementById('offer-manual');
            if (!POS.cart.length) { panel.style.display = 'none'; list.innerHTML = ''; return; }

            panel.style.display = 'block';
            const active = selectedOffers();
            if (active.length) {
                // An offer is applied — show it, hide the manual code field.
                manual.style.display = 'none';
                list.innerHTML = active.map(o => `
                    <div class="offer-chip" data-id="${o.id}">
                        <div>
                            <div class="offer-chip-title">${o.label}</div>
                            <div class="offer-chip-sub">${o.summary}</div>
                        </div>
                        <button type="button" class="offer-chip-x" title="Remove offer" onclick="removeOffer(${o.id})">&times;</button>
                    </div>`).join('');
            } else {
                // No offer applied — let the cashier check a code manually.
                list.innerHTML = '';
                manual.style.display = 'block';
                document.getElementById('offer-manual-msg').textContent = '';
            }
            if (typeof updatePromoSection === 'function') updatePromoSection();
        }

        function removeOffer(id) {
            if (POS.removedOffers.indexOf(id) === -1) POS.removedOffers.push(id);
            renderOfferPanel(); recalc();
        }
        window.removeOffer = removeOffer;

        function applyOfferCode() {
            const input = document.getElementById('offer-code-input');
            const msg = document.getElementById('offer-manual-msg');
            const code = (input.value || '').trim();
            if (!code) { msg.textContent = 'Enter an offer code.'; return; }
            if (!POS.cart.length) { msg.textContent = 'Cart is empty.'; return; }
            msg.textContent = 'Checking…';
            fetch(POS.offersApplyCode, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': POS.csrf },
                body: new URLSearchParams({
                    items: JSON.stringify(POS.cart),
                    code: code,
                    customer_id: document.getElementById('pos-customer').value || 0,
                    branch_id: posBranch(),
                }),
            })
                .then(r => r.json())
                .then(d => {
                    if (!d.status) { msg.textContent = d.msg || 'Offer not valid.'; return; }
                    const o = d.offer;
                    if (!POS.matchedOffers.some(x => x.id === o.id)) POS.matchedOffers.push(o);
                    POS.removedOffers = POS.removedOffers.filter(id => id !== o.id);
                    input.value = '';
                    renderOfferPanel(); recalc();
                })
                .catch(() => { msg.textContent = 'Could not validate the code.'; });
        }

        document.getElementById('offer-code-apply')?.addEventListener('click', applyOfferCode);
        document.getElementById('offer-code-input')?.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); applyOfferCode(); }
        });

        document.getElementById('btn-all-offers')?.addEventListener('click', function () {
            const box = document.getElementById('allOffersList');
            box.innerHTML = '<div class="text-center text-muted py-3">Loading…</div>';
            $('#allOffersModal').modal('show');
            fetch(POS.offersAll).then(r => r.json()).then(d => {
                const offers = d.offers || [];
                if (!offers.length) { box.innerHTML = '<div class="text-center text-muted py-3">No offers created yet.</div>'; return; }
                box.innerHTML = offers.map(o => `
                    <div class="offer-row-modal">
                        <div>
                            <div class="offer-chip-title">${o.name} <small class="text-muted">(${o.code})</small></div>
                            <div class="offer-chip-sub">${o.type} · ${o.start} → ${o.end}</div>
                        </div>
                        <span class="badge ${o.active ? 'badge-soft-success' : 'badge-soft-secondary'}">${o.active ? 'Active' : (o.status === 'draft' ? 'Draft' : 'Inactive')}</span>
                    </div>`).join('');
            }).catch(() => { box.innerHTML = '<div class="text-center text-danger py-3">Failed to load offers.</div>'; });
        });

        // ── Coupons (customer coupons + manual code; adjusted off the bill total) ──
        let couponFetchTimer = null;
        function scheduleCouponFetch() { clearTimeout(couponFetchTimer); couponFetchTimer = setTimeout(fetchCoupons, 350); }

        function fetchCoupons() {
            if (!POS.cart.length) { POS.availableCoupons = []; POS.coupon = null; renderCouponPanel(); recalc(); return; }
            fetch(POS.coupons, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': POS.csrf },
                body: new URLSearchParams({
                    items: JSON.stringify(POS.cart),
                    customer_id: document.getElementById('pos-customer').value || 0,
                    branch_id: posBranch(),
                }),
            })
                .then(r => r.json())
                .then(d => {
                    POS.availableCoupons = d.coupons || [];
                    if (POS.coupon) {
                        // Keep the applied coupon's discount in sync with the cart; drop if no longer valid.
                        const still = POS.availableCoupons.find(c => c.code === POS.coupon.code);
                        POS.coupon = still || null;
                    }
                    renderCouponPanel(); recalc();
                })
                .catch(() => { POS.availableCoupons = []; renderCouponPanel(); recalc(); });
        }

        function renderCouponPanel() {
            const panel = document.getElementById('coupon-panel');
            const applied = document.getElementById('coupon-applied');
            const entry = document.getElementById('coupon-entry');
            const avail = document.getElementById('coupon-available');
            if (!POS.cart.length) { panel.style.display = 'none'; applied.innerHTML = ''; return; }
            panel.style.display = 'block';

            if (POS.coupon) {
                entry.style.display = 'none';
                applied.innerHTML = `
                    <div class="offer-chip" data-code="${POS.coupon.code}">
                        <div>
                            <div class="offer-chip-title">${POS.coupon.title || POS.coupon.code} (${POS.coupon.code})</div>
                            <div class="offer-chip-sub">${POS.coupon.label}</div>
                        </div>
                        <button type="button" class="offer-chip-x" title="Remove coupon" onclick="removeCoupon()">&times;</button>
                    </div>`;
            } else {
                applied.innerHTML = '';
                entry.style.display = 'block';
                document.getElementById('coupon-msg').textContent = '';
                if (POS.availableCoupons.length) {
                    avail.innerHTML = POS.availableCoupons.map(c =>
                        `<a href="javascript:;" class="badge badge-soft-primary mr-1 mb-1" style="font-size:11px;"
                            onclick="applyCouponCode('${c.code}')" title="${c.label}">🏷️ ${c.code} · ${c.label}</a>`).join('');
                } else {
                    avail.innerHTML = '<span class="offer-chip-sub">No coupons for this customer.</span>';
                }
            }
            if (typeof updatePromoSection === 'function') updatePromoSection();
        }

        function applyCouponCode(code) {
            const msg = document.getElementById('coupon-msg');
            if (!code) { msg.textContent = 'Enter a coupon code.'; return; }
            if (!POS.cart.length) { msg.textContent = 'Cart is empty.'; return; }
            msg.textContent = 'Checking…';
            fetch(POS.couponApply, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': POS.csrf },
                body: new URLSearchParams({
                    items: JSON.stringify(POS.cart),
                    code: code,
                    customer_id: document.getElementById('pos-customer').value || 0,
                    branch_id: posBranch(),
                }),
            })
                .then(r => r.json())
                .then(d => {
                    if (!d.status) { msg.textContent = d.msg || 'Coupon not valid.'; return; }
                    POS.coupon = d.coupon;
                    document.getElementById('coupon-code-input').value = '';
                    renderCouponPanel(); recalc();
                })
                .catch(() => { msg.textContent = 'Could not validate the coupon.'; });
        }
        window.applyCouponCode = applyCouponCode;

        function removeCoupon() { POS.coupon = null; renderCouponPanel(); recalc(); }
        window.removeCoupon = removeCoupon;

        document.getElementById('coupon-apply-btn')?.addEventListener('click', function () {
            applyCouponCode((document.getElementById('coupon-code-input').value || '').trim());
        });
        document.getElementById('coupon-code-input')?.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); applyCouponCode((this.value || '').trim()); }
        });

        // ── Offers & Coupons collapsible section ──
        function updatePromoSection() {
            var section = document.getElementById('promo-section');
            if (!section) return;
            if (!POS.cart.length) { section.style.display = 'none'; return; }
            section.style.display = 'block';
            var count = selectedOffers().length + (POS.availableCoupons ? POS.availableCoupons.length : 0) + (POS.coupon ? 1 : 0);
            var badge = document.getElementById('promo-badge');
            if (count > 0) { badge.style.display = ''; badge.textContent = count; }
            else { badge.style.display = 'none'; }
        }

        document.getElementById('promo-toggle')?.addEventListener('click', function () {
            var body = document.getElementById('promo-body');
            var chev = document.getElementById('promo-chevron');
            var open = body.style.display !== 'none';
            body.style.display = open ? 'none' : 'block';
            chev.innerHTML = open ? '&#9662;' : '&#9652;';
        });

        // ── Search / scan ──
        let searchTimer = null;
        let lastInputAt = 0;
        let fastCount = 0; // consecutive rapid keystrokes → barcode scanner (keyboard wedge)
        const searchBox = document.getElementById('pos-search');
        const resultsBox = document.getElementById('pos-results');

        searchBox.addEventListener('input', function () {
            clearTimeout(searchTimer);
            const now = Date.now();
            const gap = now - lastInputAt;
            lastInputAt = now;
            fastCount = (gap > 0 && gap < 35) ? fastCount + 1 : 0;
            const q = this.value.trim();
            // Hide the "Start typing…" hint as soon as anything is entered.
            const hintEl = document.querySelector('.search-hint');
            if (hintEl) hintEl.style.display = this.value.length ? 'none' : '';
            if (q.length < 2) { resultsBox.style.display = 'none'; return; }
            // USB barcode scanners type as a keyboard wedge — a run of characters arrives within a
            // few ms of each other. Treat a sustained burst as a scan and auto-add even without a
            // trailing Enter; otherwise behave as a normal (debounced) search.
            if (fastCount >= 3) {
                searchTimer = setTimeout(() => {
                    const code = searchBox.value.trim();
                    fastCount = 0;
                    if (code) lookup(code, true);
                }, 130);
            } else {
                searchTimer = setTimeout(() => lookup(q, false), 200);
            }
        });

        searchBox.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); clearTimeout(searchTimer); fastCount = 0; lookup(this.value.trim(), true); }
        });

        function lookup(q, exact) {
            if (!q) return Promise.resolve(null);
            return fetch(POS.products + '?q=' + encodeURIComponent(q) + (exact ? '&exact=1' : '') + '&branch=' + encodeURIComponent(posBranch()))
                .then(r => r.json())
                .then(d => {
                    if (exact && d.items.length >= 1) {
                        const it = d.items[0];
                        // Scanned a variation's SKU → add that exact variation, skip the picker.
                        if (it.matched_variation && Array.isArray(it.variations) && it.variations.length) {
                            const vIdx = it.variations.findIndex(v => v.type === it.matched_variation);
                            if (vIdx > -1) {
                                activeVariations = it.variations;
                                activeParentItem = it;
                                selectVarIndex(vIdx);
                                searchBox.value = ''; resultsBox.style.display = 'none'; searchBox.focus();
                                const h0 = document.querySelector('.search-hint'); if (h0) h0.style.display = '';
                                if (window.toastr) toastr.success(it.name + ' (' + it.matched_variation + ') added to cart');
                                return it;
                            }
                        }
                        addToCart(it); searchBox.value = ''; resultsBox.style.display = 'none'; searchBox.focus();
                        const h = document.querySelector('.search-hint'); if (h) h.style.display = '';
                        if (window.toastr) toastr.success(it.name + ' added to cart');
                        return it;
                    }
                    if (!d.items.length) { resultsBox.innerHTML = '<div class="res-row text-muted">No products</div>'; resultsBox.style.display = 'block'; return null; }
                    resultsBox.innerHTML = d.items.map(it =>
                        `<div class="res-row" data-it='${JSON.stringify(it)}'>
                            <span>${it.name} <small class="text-muted">${it.sku || ''}</small></span>
                            <span>₹${money(it.price)} · stk ${it.stock}</span>
                        </div>`).join('');
                    resultsBox.style.display = 'block';
                    resultsBox.querySelectorAll('.res-row[data-it]').forEach(row => {
                        row.addEventListener('click', () => {
                            addToCart(JSON.parse(row.getAttribute('data-it')));
                            searchBox.value = ''; resultsBox.style.display = 'none'; searchBox.focus();
                        });
                    });
                    return null;
                });
        }

        // Quick-grid: delegated click (works for server-rendered Popular + AJAX category items).
        const QI_PLACEHOLDER = '{{ asset('public/assets/admin/img/160x160/img2.jpg') }}';
        const quickGrid = document.getElementById('quick-grid');
        const popularHtml = quickGrid.innerHTML; // remember the Popular grid
        quickGrid.addEventListener('click', function (e) {
            const card = e.target.closest('.quick-item');
            if (card) addToCart(JSON.parse(card.getAttribute('data-item')));
        });

        // Category tabs → load that category's items into the grid.
        document.querySelectorAll('.cat-tab').forEach(tab => tab.addEventListener('click', function () {
            document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const cat = this.getAttribute('data-cat');
            if (!cat) { quickGrid.innerHTML = popularHtml; return; }
            quickGrid.innerHTML = '<div class="text-muted p-2">Loading…</div>';
            fetch(POS.products + '?category=' + encodeURIComponent(cat) + '&branch=' + encodeURIComponent(posBranch()))
                .then(r => r.json())
                .then(d => {
                    if (!d.items.length) { quickGrid.innerHTML = '<div class="text-muted p-2">No items in this category.</div>'; return; }
                    quickGrid.innerHTML = d.items.map(it => {
                        const data = { id: it.id, name: it.name, price: it.price, hsn: it.hsn, gst_rate: it.gst_rate, gst_status: it.gst_status, unit: it.unit, expiry_warn: it.expiry_warn, expiry: it.expiry, low_stock: it.low_stock, sell_loose: it.sell_loose, variations: it.variations || [] };
                        const img = it.image || QI_PLACEHOLDER;
                        return '<div class="quick-item" data-item=\'' + JSON.stringify(data).replace(/'/g, '&#39;') + '\'>' +
                            '<img class="qi-img" src="' + img + '" onerror="this.src=\'' + QI_PLACEHOLDER + '\'" alt="">' +
                            '<div class="font-weight-bold text-truncate">' + it.name + '</div>' +
                            '<div class="text-muted">₹' + money(it.price) + '</div></div>';
                    }).join('');
                });
        }));

        // Default tab = All → load all items on open. Skipped in the search-first
        // template, where the grid is hidden and items only show via search.
        const allTab = document.querySelector('.cat-tab[data-cat="all"]');
        const searchFirst = document.querySelector('.rpos')?.classList.contains('rpos-tpl-search');
        if (allTab && !searchFirst) allTab.click();

        // Search-first: move the selected-items table to the LEFT (below the search),
        // keeping the cart / checkout (customer, totals, payment, Finalize) on the right.
        if (searchFirst) {
            const leftBody = document.querySelector('.pos-left .pc-bd');
            const cartScroll = document.querySelector('.pos-right .cart-scroll');
            if (leftBody && cartScroll) {
                const hd = document.createElement('div');
                hd.className = 'selected-items-hd';
                hd.textContent = '🛒 Selected Items';
                leftBody.appendChild(hd);
                leftBody.appendChild(cartScroll);
            }
        }

        // Owner switches branch → reload the grid so stock reflects that branch.
        const branchSel = document.getElementById('pos-branch');
        if (branchSel && branchSel.tagName === 'SELECT') {
            branchSel.addEventListener('change', function () {
                // Remember this choice for next time (empty = Main Store / no branch).
                fetch(POS.defaultBranch, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': POS.csrf },
                    body: new URLSearchParams({ branch_id: this.value }),
                }).catch(() => {});
                const active = document.querySelector('.cat-tab.active') || allTab;
                if (active) active.click();
            });
        }

        document.getElementById('bill-discount').addEventListener('input', recalc);

        // ── Payment legs ──
        document.getElementById('add-leg').addEventListener('click', function () {
            // Splitting: the first leg becomes manual so its auto-total doesn't fight the split.
            const first = document.querySelector('.pay-leg .pay-amount');
            if (first) first.dataset.auto = '0';
            const leg = document.querySelector('.pay-leg').cloneNode(true);
            leg.querySelectorAll('input').forEach(i => i.value = '');
            leg.querySelector('.pay-mode').value = 'cash';
            leg.querySelector('.pay-amount').dataset.auto = '0';
            leg.querySelector('.pay-approval').style.display = 'none';
            document.getElementById('pay-legs').appendChild(leg);
        });
        document.getElementById('pay-legs').addEventListener('input', e => {
            if (e.target.classList.contains('pay-amount')) {
                e.target.dataset.auto = '0'; // user typed → stop auto-filling
                recalc();
                updateUpiQr(); // keep the UPI QR in sync with the typed UPI amount
            }
        });

        // Mode change → card capture field + UPI QR.
        document.getElementById('pay-legs').addEventListener('change', e => {
            if (!e.target.classList.contains('pay-mode')) return;
            const leg = e.target.closest('.pay-leg');
            const mode = e.target.value;
            const appr = leg.querySelector('.pay-approval');
            const ref = leg.querySelector('.pay-ref');
            appr.style.display = (mode === 'card' || mode === 'debit') ? '' : 'none';
            ref.placeholder = mode === 'upi' ? 'UPI txn ref' : (mode === 'card' || mode === 'debit' ? 'Card network' : 'Ref (optional)');
            updateUpiQr();
        });

        // Cash denomination quick-keys → fill the first cash leg.
        function firstAmountInput() {
            let leg = [...document.querySelectorAll('.pay-leg')].find(l => l.querySelector('.pay-mode').value === 'cash')
                || document.querySelector('.pay-leg');
            return leg.querySelector('.pay-amount');
        }
        document.querySelectorAll('.denom').forEach(b => b.addEventListener('click', function () {
            const inp = firstAmountInput();
            const base = inp.dataset.auto === '1' ? 0 : (parseFloat(inp.value) || 0); // start fresh from auto
            inp.value = base + parseFloat(this.dataset.d);
            inp.dataset.auto = '0';
            recalc();
        }));
        document.getElementById('denom-exact').addEventListener('click', function () {
            const inp = firstAmountInput();
            inp.value = window.__posGrand || 0;
            inp.dataset.auto = '1'; // keep tracking the exact total
            recalc();
        });

        // UPI QR — render store QR (upi:// intent) for the current bill total.
        function updateUpiQr() {
            const box = document.getElementById('upi-qr');
            const hasUpiLeg = [...document.querySelectorAll('.pay-mode')].some(s => s.value === 'upi');
            if (!hasUpiLeg) { box.style.display = 'none'; return; }
            // QR is for the UPI portion only (sum of UPI legs), not the whole bill.
            let amt = 0;
            document.querySelectorAll('.pay-leg').forEach(l => {
                if (l.querySelector('.pay-mode').value === 'upi') {
                    amt += parseFloat(l.querySelector('.pay-amount').value) || 0;
                }
            });
            // UPI selected but amount not entered yet → fall back to the full bill total.
            if (amt <= 0) amt = window.__posGrand || 0;
            const note = document.getElementById('upi-qr-note');
            if (!POS.upiId) {
                box.style.display = 'block';
                document.getElementById('upi-qr-img').style.display = 'none';
                note.textContent = 'Set the store UPI ID under Terminals to show a QR.';
                return;
            }
            const intent = 'upi://pay?pa=' + encodeURIComponent(POS.upiId) + '&pn=' + encodeURIComponent(POS.storeName) + '&am=' + amt + '&cu=INR';
            document.getElementById('upi-qr-img').style.display = '';
            document.getElementById('upi-qr-img').src = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(intent);
            note.textContent = 'Scan to pay ₹' + money(amt) + ' · ' + POS.upiId;
            box.style.display = 'block';
        }

        // ── Customer search / link ──
        let custTimer = null;
        const custBox = document.getElementById('cust-search');
        const custResults = document.getElementById('cust-results');
        const custInfo = document.getElementById('cust-info');

        custBox.addEventListener('input', function () {
            clearTimeout(custTimer);
            const q = this.value.trim();
            document.getElementById('pos-customer').value = 0;
            custInfo.style.display = 'none';
            if (q.length < 2) { custResults.style.display = 'none'; return; }
            custTimer = setTimeout(() => {
                fetch(POS.customers + '?q=' + encodeURIComponent(q)).then(r => r.json()).then(d => {
                    let html = d.customers.length
                        ? d.customers.map(c =>
                            `<div class="res-row" data-c='${JSON.stringify(c)}'>
                                <span>${c.name} <small class="text-muted">${c.phone || ''}</small></span>
                                <span>★${c.loyalty_points} · ₹${money(c.wallet_balance)}</span>
                            </div>`).join('')
                        : '<div class="res-row text-muted">No customers</div>';
                    if (POS.canCreate) {
                        html += `<div class="res-row" id="cust-add-row" style="cursor:pointer;color:var(--accent,#e3342f);font-weight:700;">
                                    <span>+ Add new customer</span></div>`;
                    }
                    custResults.innerHTML = html;
                    custResults.style.display = 'block';
                    custResults.querySelectorAll('.res-row[data-c]').forEach(row =>
                        row.addEventListener('click', () => selectCustomer(JSON.parse(row.getAttribute('data-c')))));
                    const addRow = document.getElementById('cust-add-row');
                    if (addRow) addRow.addEventListener('click', () => openAddCustomerModal(q));
                });
            }, 200);
        });

        // NOTE: openAddCustomerModal() and the customer-form AJAX submit live in the script_2
        // stack at the bottom — it loads AFTER jQuery, whereas this script stack runs before it.

        function selectCustomer(c) {
            document.getElementById('pos-customer').value = c.id;
            custBox.value = c.name + (c.phone ? ' · ' + c.phone : '');
            custResults.style.display = 'none';
            custInfo.style.display = 'block';
            custInfo.innerHTML = `Points: <b>★${c.loyalty_points}</b> · Wallet: <b>₹${money(c.wallet_balance)}</b> · Credit: <b>₹${money(c.credit_balance)}</b> / ₹${money(c.credit_limit)}`;
            scheduleOfferMatch();
            scheduleCouponFetch();
        }

        // ── Hold & Resume ──
        function refreshHeld() {
            fetch(POS.held).then(r => r.json()).then(d => {
                document.getElementById('held-count').textContent = d.held.length;
                const panel = document.getElementById('held-panel');
                if (!d.held.length) { panel.innerHTML = '<div class="res-row text-muted">No held bills</div>'; }
                else panel.innerHTML = d.held.map(h =>
                    `<div class="res-row">
                        <span><b>${h.hold_code}</b> · ${h.customer} · ${h.item_count} items · ${h.held_at}</span>
                        <span>
                            ${POS.canResume ? `<a class="btn btn-sm btn-primary mr-2" style="cursor:pointer" onclick="resumeHeld(${h.id})">Resume</a>` : ''}
                            ${POS.canHold ? `<a class="text-danger" style="    font-size: 16px;cursor:pointer" onclick="deleteHeld(${h.id})"><i class='tio-delete'></i></a>` : ''}
                        </span>
                    </div>`).join('');
            });
        }

        document.getElementById('btn-held')?.addEventListener('click', function () {
            const p = document.getElementById('held-panel');
            p.style.display = p.style.display === 'none' ? 'block' : 'none';
            if (p.style.display === 'block') refreshHeld();
        });

        document.getElementById('btn-hold')?.addEventListener('click', function () {
            if (!POS.cart.length) { (window.toastr ? toastr.error : alert)('Nothing to hold'); return; }
            fetch(POS.hold, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': POS.csrf },
                body: new URLSearchParams({
                    items: JSON.stringify(POS.cart),
                    customer_id: document.getElementById('pos-customer').value || 0,
                    bill_discount: document.getElementById('bill-discount').value || 0,
                }),
            }).then(r => r.json()).then(d => {
                if (!d.status) { (window.toastr ? toastr.error : alert)(d.msg || 'Failed'); return; }
                if (window.toastr) toastr.success('Held as ' + d.hold_code);
                resetSale(); refreshHeld();
            });
        });

        function resumeHeld(id) {
            fetch(POS.resumeUrl.replace('__ID__', id), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(r => r.json()).then(d => {
                if (!d.status) return;
                POS.cart = (d.items || []).map(l => ({
                    id: l.id, name: l.name, price: parseFloat(l.price) || 0, qty: parseFloat(l.qty) || 1,
                    discount: parseFloat(l.discount) || 0, hsn: l.hsn || '',
                    gst_rate: parseFloat(l.gst_rate) || 0, gst_status: l.gst_status || 'excluding',
                    unit: l.unit || '', sell_loose: !!l.sell_loose,
                    pieces: l.pieces ? parseInt(l.pieces, 10) : null,
                }));
                POS.holdId = d.hold_id;
                document.getElementById('pos-customer').value = d.customer_id || 0;
                document.getElementById('bill-discount').value = (d.meta && d.meta.bill_discount) || 0;
                document.getElementById('held-panel').style.display = 'none';
                renderCart();
            });
        }

        function deleteHeld(id) {
            fetch(POS.heldDelUrl.replace('__ID__', id), {
                method: 'POST', headers: { 'X-CSRF-TOKEN': POS.csrf },
            }).then(() => refreshHeld());
        }

        function resetSale() {
            POS.cart = []; POS.holdId = null;
            POS.matchedOffers = []; POS.removedOffers = []; POS.offerDiscount = 0;
            POS.coupon = null; POS.availableCoupons = [];
            renderOfferPanel(); renderCouponPanel();
            document.getElementById('bill-discount').value = 0;
            document.getElementById('pos-customer').value = 0;
            custBox.value = ''; custInfo.style.display = 'none';
            // Drop extra split legs, keep one, restore auto-fill.
            const legs = document.querySelectorAll('.pay-leg');
            legs.forEach((l, i) => { if (i > 0) l.remove(); });
            const first = document.querySelector('.pay-leg');
            first.querySelector('.pay-mode').value = 'cash';
            first.querySelectorAll('input').forEach(i => i.value = '');
            first.querySelector('.pay-approval').style.display = 'none';
            first.querySelector('.pay-amount').dataset.auto = '1';
            renderCart();
        }

        // ── Finalize ──
        function finalize(allowOos) {
            if (!POS.cart.length) { toastr ? toastr.error('Cart is empty') : alert('Cart is empty'); return; }
            const payments = [];
            document.querySelectorAll('.pay-leg').forEach(leg => {
                const amt = parseFloat(leg.querySelector('.pay-amount').value) || 0;
                if (amt > 0) {
                    const mode = leg.querySelector('.pay-mode').value;
                    payments.push({
                        mode: mode,
                        amount: amt,
                        reference: leg.querySelector('.pay-ref').value || null,
                        sub_type: (mode === 'card' || mode === 'debit') ? (leg.querySelector('.pay-ref').value || null) : null,
                        approval_code: leg.querySelector('.pay-approval') ? (leg.querySelector('.pay-approval').value || null) : null,
                    });
                }
            });
            const btn = document.getElementById('btn-finalize');
            if (btn) { btn.disabled = true; btn.textContent = 'Processing…'; }

            fetch(POS.finalize, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': POS.csrf },
                body: new URLSearchParams({
                    items: JSON.stringify(POS.cart),
                    payments: JSON.stringify(payments),
                    customer_id: document.getElementById('pos-customer').value || 0,
                    bill_discount: document.getElementById('bill-discount').value || 0,
                    offer_ids: JSON.stringify(selectedOffers().map(o => o.id)),
                    coupon_code: POS.coupon ? POS.coupon.code : '',
                    branch_id: posBranch(),
                    hold_id: POS.holdId || '',
                    allow_oos: allowOos ? 1 : '',
                }),
            })
                .then(r => r.json())
                .then(d => {
                    if (btn) { btn.disabled = false; btn.textContent = 'Finalize & Print (F12)'; }
                    if (!d.status) {
                        if (d.oos && !allowOos && confirm((d.msg || 'Insufficient stock') + '\n\nOverride with manager approval?')) {
                            finalize(true); return;
                        }
                        (window.toastr ? toastr.error : alert)(d.msg || 'Failed'); return;
                    }
                    if (window.toastr) {
                        let m = d.invoice_id;
                        if (d.change > 0) m += ' · Change ₹' + money(d.change);
                        if (d.due > 0) m += ' · Credit ₹' + money(d.due);
                        if (d.points_earned > 0) m += ' · +' + d.points_earned + ' pts';
                        toastr.success(m);
                    }
                    // Direct print: hardware agent if running, otherwise in-place browser print
                    // (no new tab) — just like a POS token. Avoids double printing.
                    POSAgent.afterSale(d, payments.some(p => p.mode === 'cash') || payments.length === 0)
                        .then(function (agentPrinted) {
                            if (!agentPrinted && (d.receipt_html || d.thermal_url)) directPrintReceipt(d.thermal_url, d.receipt_html);
                        });
                    resetSale(); refreshHeld(); searchBox.focus();
                })
                .catch(() => { if (btn) { btn.disabled = false; btn.textContent = 'Finalize & Print (F12)'; } alert('Error'); });
        }

        document.getElementById('btn-finalize')?.addEventListener('click', finalize);
        document.addEventListener('keydown', e => { if (e.key === 'F12' && POS.canCreate) { e.preventDefault(); finalize(); } });

        // Enter also finalizes & prints (like F12) — except while typing in the item or
        // customer search (Enter there adds an item / picks a customer, and a USB scanner's
        // trailing Enter must not fire a sale).
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' || e.repeat || !POS.canCreate) return;
            const el = e.target || {};
            if ((el.tagName || '').toLowerCase() === 'textarea') return;
            if (el.id === 'pos-search' || el.id === 'cust-search') return;
            const cam = document.getElementById('cam-scan');
            if (cam && cam.style.display !== 'none') return;   // camera scanner open
            if (!POS.cart.length) return;                       // nothing to bill yet
            const btn = document.getElementById('btn-finalize');
            if (btn && btn.disabled) return;                    // already processing
            e.preventDefault();
            finalize();
        });
        // Held bills render server-side on load; JS only refreshes after hold / resume / finalize.

        // ── Camera barcode scanner (for phones/tablets/webcam, no USB scanner) ──
        (function () {
            const overlay = document.getElementById('cam-scan');
            const msg = document.getElementById('cam-msg');
            let cam = null, lastCode = '', lastAt = 0, scanBusy = false;

            function open() {
                if (typeof Html5Qrcode === 'undefined') { (window.toastr ? toastr.error : alert)('Scanner library not loaded'); return; }
                scanBusy = false; lastCode = ''; msg.textContent = 'Point the camera at the product barcode.';
                overlay.style.display = 'flex';
                cam = new Html5Qrcode('cam-reader');
                cam.start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, onScan, () => {})
                    .catch(e => { msg.textContent = 'Camera unavailable: ' + e; });
            }
            function close() {
                overlay.style.display = 'none';
                if (cam) { cam.stop().then(() => cam.clear()).catch(() => {}); cam = null; }
            }
            function onScan(code) {
                const now = Date.now();
                if (scanBusy || (code === lastCode && now - lastAt < 1500)) return; // de-dupe rapid frames
                lastCode = code; lastAt = now; scanBusy = true;
                if (navigator.vibrate) navigator.vibrate(60);
                // Exact match adds to cart; on success close the scanner and notify (toast comes
                // from lookup). If nothing matched, keep scanning and show a hint.
                lookup(code, true).then(item => {
                    if (item) { msg.textContent = 'Added: ' + item.name; close(); }
                    else { msg.textContent = 'Not found: ' + code; scanBusy = false; }
                }).catch(() => { scanBusy = false; });
            }

            document.getElementById('btn-cam-scan').addEventListener('click', open);
            document.getElementById('cam-close').addEventListener('click', close);
            overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
        })();

        // ── Kiosk toggle: hide the vendor sidebar + topbar (remembered per browser) ──
        // Pure CSS — the browser's own header (tabs/address bar) stays visible, so refreshing
        // and printing never disturb it. The preference is restored on load with no gesture.
        (function () {
            const KEY = 'rpos_fullscreen';
            const btn = document.getElementById('btn-fullscreen');
            function apply(on) {
                document.body.classList.toggle('pos-kiosk', on);
                if (btn) btn.innerHTML = on ? '⤢ Exit full screen' : '⛶ Full screen';
            }
            if (btn) btn.addEventListener('click', function () {
                const on = !document.body.classList.contains('pos-kiosk');
                localStorage.setItem(KEY, on ? '1' : '0');
                apply(on);
            });
            apply(localStorage.getItem(KEY) === '1');
        })();
    </script>
@endpush

{{-- jQuery-dependent code MUST live here: script_2 loads after vendor.min.js (jQuery/Bootstrap),
     whereas @push('script') runs before it. --}}
@push('script_2')
    <script>
        // Open the standard "Add New Customer" modal, prefilling name or phone from the search box.
        function openAddCustomerModal(prefill) {
            prefill = (prefill || '').trim();
            const isPhone = /^\d{5,}$/.test(prefill);
            const $m = $('#customerAddModal');
            $m.find('input[name="f_name"]').val(isPhone ? '' : prefill);
            $m.find('input[name="phone"]').val(isPhone ? prefill : '');
            document.getElementById('cust-results').style.display = 'none';
            $m.modal('show');
        }

        // AJAX-submit the standard customer form, then select the new customer into the sale.
        $(document).on('submit', '#customerAddModal .customer_add_form', function (e) {
            e.preventDefault();
            const form = this; 
            const $btn = $(form).find('button[type="submit"], button:not([type])').last();
            const oldTxt = $btn.text();
            $btn.prop('disabled', true).text('Saving…');
            const fd = new FormData(form);
            fd.append('form_type', 'ajax');
            $.ajax({
                url: form.action, method: 'POST', data: fd, processData: false, contentType: false,
                headers: { 'X-CSRF-TOKEN': POS.csrf },
                success: function (d) {
                    if (d && d.status && d.customer) {
                        $('#customerAddModal').modal('hide');
                        form.reset();
                        selectCustomer({
                            id: d.customer.id,
                            name: d.customer.f_name,
                            phone: d.customer.phone,
                            loyalty_points: d.customer.loyalty_points || 0,
                            wallet_balance: d.customer.wallet_balance || 0,
                            credit_balance: d.customer.credit_balance || 0,
                            credit_limit: d.customer.credit_limit || 0,
                        });
                        if (window.toastr) toastr.success('Customer added');
                    } else {
                        (window.toastr ? toastr.error : alert)((d && d.msg) || 'Failed to add customer');
                    }
                },
                error: function (xhr) {
                    let msg = 'Failed to add customer';
                    if (xhr.responseJSON && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors)[0][0];
                    else if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    (window.toastr ? toastr.error : alert)(msg);
                },
                complete: function () { $btn.prop('disabled', false).text(oldTxt); }
            });
        });
    </script>
@endpush
