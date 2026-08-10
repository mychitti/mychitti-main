{{-- Styles of the three pages merged into Automation, kept in their original order. --}}
    <style>
        .tr-card { border-left: 3px solid #e7eaf3; }
        .tr-card.tr-broken { border-left-color: #de4437; }
        .tr-card.tr-bound { border-left-color: #00c9a7; }
        .tr-slot { font-size: 11px; background: #f8fafd; border: 1px solid #e7eaf3; border-radius: 3px; padding: 1px 6px; }
    </style>
    <style>
        .wc { --wc-green:#25d366; --wc-green-d:#128c7e; --wc-ink:#0f172a; --wc-ink-2:#334155;
              --wc-mute:#7c8798; --wc-line:#e9edf3; --wc-soft:#f6f8fb; }
        .wc-card { border:1px solid var(--wc-line); border-radius:16px; background:#fff;
                   margin-bottom:20px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
        .wc-card-h { padding:16px 22px; border-bottom:1px solid var(--wc-line); display:flex;
                     align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
        .wc-card-t { font-weight:700; font-size:14.5px; color:var(--wc-ink); margin:0; }
        .wc-card-b { padding:22px; }
        .wc-sub { font-size:12px; color:var(--wc-mute); line-height:1.6; }

        .wc-chip { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; padding:4px 11px;
                   border-radius:999px; font-weight:650; }
        .wc-chip-ok { background:#e7f9ef; color:#0f7a43; }
        .wc-chip-off { background:#eef1f6; color:#5b6675; }

        /* live status banner */
        .wc-live { display:flex; align-items:flex-start; gap:14px; padding:18px 20px; border-radius:14px;
                   background:linear-gradient(135deg,#f4fdf8,#fff); border:1.5px solid var(--wc-green); }
        .wc-live.off { background:var(--wc-soft); border-color:var(--wc-line); }
        .wc-live-i { width:42px; height:42px; border-radius:12px; flex-shrink:0; display:flex;
                     align-items:center; justify-content:center; font-size:20px;
                     background:var(--wc-green); color:#fff; }
        .wc-live.off .wc-live-i { background:#cbd5e1; }
        .wc-live-t { font-weight:700; font-size:14px; color:var(--wc-ink); }

        /* permission rows */
        .wc-item { display:flex; align-items:flex-start; gap:14px; padding:16px 0;
                   border-bottom:1px solid #f2f5f9; margin:0; cursor:pointer; }
        .wc-item:last-of-type { border-bottom:0; }
        .wc-item input { margin-top:3px; width:16px; height:16px; flex-shrink:0; cursor:pointer; }
        .wc-item-l { font-size:13.5px; font-weight:650; color:var(--wc-ink); }
        .wc-item-d { font-size:12px; color:var(--wc-mute); line-height:1.6; margin-top:3px; }
        .wc-item.is-action { background:var(--wc-soft); border-radius:12px; padding:16px;
                             border-bottom:0; margin-bottom:6px; }

        .wc-note { font-size:11.5px; color:var(--wc-mute); line-height:1.6; background:var(--wc-soft);
                   border-radius:10px; padding:12px 14px; }
        .wc-empty { text-align:center; padding:34px 16px; color:var(--wc-mute); }
        .wc-empty i { font-size:30px; color:#cbd5e1; display:block; margin-bottom:10px; }
    </style>
    <style>
        /* Coverage chips double as the type filter — one control, two jobs. */
        .kn-cov {
            display:flex; align-items:center; gap:7px; padding:9px 12px; border:1px solid var(--wa-line);
            border-radius:10px; background:#fff; font-size:12.5px; color:var(--wa-ink);
            text-decoration:none; transition:border-color .15s, background .15s;
        }
        .kn-cov:hover { border-color:var(--wa-green); color:var(--wa-ink); }
        .kn-cov.active { border-color:var(--wa-green); background:#f0fdf4; }
        .kn-cov.empty { color:var(--wa-mute); background:var(--wa-bg); }
        .kn-cov b { font-size:13px; }
        .kn-cov-n { min-width:22px; height:22px; border-radius:6px; display:inline-flex; align-items:center;
            justify-content:center; font-size:11px; font-weight:700; background:rgba(37,211,102,.16); color:#15803d; }
        .kn-cov.empty .kn-cov-n { background:#e9edf2; color:#94a3b8; }
        .kn-doc-body {
            font-size:12.5px; color:#667781; line-height:1.55; white-space:pre-wrap;
            max-height:60px; overflow:hidden; position:relative;
        }
    </style>
