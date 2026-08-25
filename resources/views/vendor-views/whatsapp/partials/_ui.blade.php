{{-- Shared look for every WhatsApp page. Included from each page's css_or_js push so the
     module reads as one product instead of seven screens that grew separately. Class prefix is
     wa- ; page-specific rules stay on their own page. --}}
<style>
    :root {
        --wa-green:#25d366; --wa-green-d:#128c7e;
        --wa-ink:#1e293b; --wa-mute:#8a94a6; --wa-line:#eef0f4; --wa-bg:#f8fafc;
    }

    /* ── Layout ─────────────────────────────────────────────── */
    .wa-col { margin-bottom:16px; }
    .wa-card { border:1px solid var(--wa-line); border-radius:14px; background:#fff; overflow:hidden; }
    .wa-card.h-100 { height:100%; }
    .wa-card-h {
        padding:14px 18px; border-bottom:1px solid #f1f3f7; font-weight:700; font-size:14px;
        color:var(--wa-ink); display:flex; align-items:center; justify-content:space-between; gap:10px;
    }
    .wa-card-h .wa-sub { font-weight:400; }
    .wa-card-b { padding:18px; }
    .wa-card-b.flush { padding:0; }

    /* ── Type ───────────────────────────────────────────────── */
    .wa-sub { font-size:12px; color:var(--wa-mute); }
    .wa-eyebrow { font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:var(--wa-mute); }
    .wa-price { font-size:26px; font-weight:800; color:var(--wa-ink); line-height:1.15; }

    /* ── Stat tiles ─────────────────────────────────────────── */
    .wa-stat {
        border:1px solid var(--wa-line); border-radius:14px; background:#fff; padding:16px 18px;
        display:flex; justify-content:space-between; align-items:flex-start; gap:12px; height:100%;
    }
    .wa-stat-val { font-size:24px; font-weight:800; line-height:1.1; color:var(--wa-ink); }
    .wa-stat-lbl { font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:var(--wa-mute); margin-top:4px; }
    .wa-stat-ico {
        width:38px; height:38px; border-radius:10px; display:flex; align-items:center;
        justify-content:center; font-size:18px; flex-shrink:0;
    }

    /* ── Rows & chips ───────────────────────────────────────── */
    .wa-row {
        display:flex; justify-content:space-between; align-items:center; gap:12px;
        font-size:13px; padding:9px 0; border-bottom:1px dashed var(--wa-line);
    }
    .wa-row:last-child { border-bottom:0; }
    .wa-chip { font-size:11px; padding:3px 10px; border-radius:20px; font-weight:600; display:inline-block; }

    /* ── Empty states ───────────────────────────────────────── */
    .wa-empty { text-align:center; padding:30px 18px; color:var(--wa-mute); }
    .wa-empty i { font-size:26px; display:block; margin-bottom:8px; opacity:.5; }
    .wa-empty-t { font-size:13px; color:var(--wa-ink); font-weight:600; margin-bottom:2px; }
    .wa-empty-s { font-size:12px; }

    /* ── Tabs ───────────────────────────────────────────────── */
    .wa-tabs { border-bottom:1px solid #f1f3f7; padding:0 8px; display:flex; gap:2px; flex-wrap:wrap; }
    .wa-tabs .nav-link {
        border:0; border-bottom:2px solid transparent; border-radius:0; padding:12px 12px;
        font-size:13px; font-weight:600; color:var(--wa-mute);
    }
    .wa-tabs .nav-link:hover { color:var(--wa-ink); }
    .wa-tabs .nav-link.active { color:var(--wa-green-d); border-bottom-color:var(--wa-green); background:transparent; }

    /* ── Collapsible section ────────────────────────────────── */
    .wa-toggle {
        display:flex; align-items:center; justify-content:space-between; gap:10px; width:100%;
        padding:14px 18px; background:none; border:0; text-align:left;
        font-weight:700; font-size:14px; color:var(--wa-ink); cursor:pointer;
    }
    .wa-toggle:hover { background:var(--wa-bg); }
    .wa-toggle .tio-chevron-down { transition:transform .18s ease; color:var(--wa-mute); }
    .wa-toggle[aria-expanded="true"] .tio-chevron-down { transform:rotate(180deg); }
    .wa-toggle + .collapse > .wa-card-b,
    .wa-toggle + .collapsing > .wa-card-b { border-top:1px solid #f1f3f7; }

    /* ── Tables ─────────────────────────────────────────────── */
    .wa-table { font-size:13px; margin-bottom:0; }

    /* The panel-wide stylesheet makes EVERY thead inside a .table-responsive sticky:
           .table-responsive thead { position:sticky; top:0; z-index:9 }
       with no background behind it. On these short lists that buys nothing — they are never tall
       enough to scroll a header out of view — and it costs the header itself: with nothing opaque
       behind it, the first row shows straight through the labels and the two read as one line.
       Same specificity as the global rule (0,1,1), and this stack renders after style.css, so
       simply putting it back in flow here wins without touching a file every other screen shares. */
    .wa-table thead { position:static; }

    .wa-table thead th {
        font-size:11px; text-transform:uppercase; letter-spacing:.3px; color:var(--wa-mute);
        font-weight:600; border-top:0; border-bottom:1px solid var(--wa-line); padding:10px 12px; white-space:nowrap;
    }
    .wa-table tbody td { padding:11px 12px; vertical-align:middle; border-top:1px solid #f6f7fa; }
    .wa-table tbody tr:hover { background:var(--wa-bg); }

    /* ── Callout ────────────────────────────────────────────── */
    .wa-note { border:1px solid var(--wa-line); border-left:3px solid var(--wa-green); border-radius:10px;
        background:var(--wa-bg); padding:12px 14px; font-size:13px; color:#475569; }
    .wa-note b { color:var(--wa-ink); }

    /* ── Phone ──────────────────────────────────────────────
       These screens pair a title with an action on one non-wrapping row and set type at
       desktop sizes; on a phone that squeezes headings into two words a line and cuts the
       button in half. Let the header rows wrap, hand the action its own full-width line,
       and step the type down. Scoped to .wa-page so the panel's other screens are untouched. */
    @media (max-width: 767.98px) {
        .wa-page.content.container-fluid { padding-left:12px; padding-right:12px; }

        .wa-page .page-header { gap:8px !important; }
        .wa-page .page-header-title { font-size:19px; }
        .wa-page .wa-sub { font-size:12px; line-height:1.5; }

        /* Card headers: title on its own line, action beneath it at full width. */
        .wa-page .card-header,
        .wa-page .wa-card-h { flex-wrap:wrap; gap:8px; padding-left:14px; padding-right:14px; }
        .wa-page .card-header > .card-title,
        .wa-page .card-header > h5 { width:100%; font-size:15px; }
        .wa-page .card-header > .btn,
        .wa-page .wa-card-h > .btn { width:100%; justify-content:center; }
        /* Card headers that pair a title with a group of chips/actions: title first, group below. */
        .wa-page .wa-card-h > span:first-child { width:100%; }
        .wa-page .wa-card-h > .d-flex { width:100%; }

        /* Recipient toggles and other button rows stack instead of overflowing. */
        .wa-page .btn-group:not(.btn-group-sm) { flex-wrap:wrap; }
        .wa-page .nav-pills { flex-wrap:wrap; }
        .wa-page .nav-pills .nav-link { padding:6px 11px !important; font-size:12px !important; }
        .wa-page .form-control, .wa-page select.form-control { max-width:100%; }

        .wa-page .wa-tabs { padding:0 4px; }
        .wa-page .wa-tabs .nav-link { padding:10px 9px; font-size:12px; }

        .wa-page .wa-card-b { padding:14px; }
        .wa-page .wa-stat { padding:14px; }
        .wa-page .wa-stat-val { font-size:21px; }
        .wa-page .wa-price { font-size:23px; }
        .wa-page .wa-note { font-size:12.5px; line-height:1.55; }

        /* Rows that pair a label with a value keep both readable instead of colliding. */
        .wa-page .wa-row { flex-wrap:wrap; }

        /* Wide lists scroll inside their card rather than dragging the page sideways. */
        .wa-page .table-responsive { -webkit-overflow-scrolling:touch; }
        .wa-page .wa-table { min-width:520px; }
    }

    @media (max-width: 575.98px) {
        .wa-card-b { padding:14px; }
        .wa-stat-val { font-size:21px; }
        .wa-page .page-header-title { font-size:17px; }
        .wa-page .wa-tabs .nav-link { padding:9px 7px; font-size:11.5px; }
    }
</style>
