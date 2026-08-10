    <style>
        .wn-card { border-left: 3px solid #e7eaf3; }
        .wn-card.wn-default { border-left-color: #00c9a7; }
        .wn-num { font-family: 'Courier New', monospace; font-size: 15px; font-weight: 600; }
        .wn-meta { font-size: 11.5px; color: #8c98a4; }
        .wn-slot { font-size: 11px; background: #f8fafd; border: 1px solid #e7eaf3; border-radius: 3px; padding: 1px 6px; }
    </style>
    <style>
        .wb {
            --wb-green:#25d366; --wb-green-d:#128c7e; --wb-ink:#0f172a; --wb-ink-2:#334155;
            --wb-mute:#7c8798; --wb-line:#e9edf3; --wb-soft:#f6f8fb; --wb-radius:16px;
        }
        .wb-card { border:1px solid var(--wb-line); border-radius:var(--wb-radius); background:#fff;
                   margin-bottom:20px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
        .wb-card-h { padding:16px 22px; border-bottom:1px solid var(--wb-line); display:flex;
                     align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px; }
        .wb-card-t { font-weight:700; font-size:14.5px; color:var(--wb-ink); margin:0; }
        .wb-card-b { padding:22px; }
        .wb-sub { font-size:12px; color:var(--wb-mute); line-height:1.55; }
        .wb-price { font-size:30px; font-weight:800; color:var(--wb-ink); line-height:1.05; letter-spacing:-.02em; }

        /* ── summary tiles ────────────────────────────────────────────── */
        .wb-tiles { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:1px;
                    background:var(--wb-line); border-radius:var(--wb-radius); overflow:hidden;
                    border:1px solid var(--wb-line); margin-bottom:20px; }
        .wb-tile { background:#fff; padding:18px 20px; }
        .wb-tile-k { font-size:11px; font-weight:600; letter-spacing:.06em; text-transform:uppercase;
                     color:var(--wb-mute); margin-bottom:7px; }
        .wb-tile-v { font-size:19px; font-weight:750; color:var(--wb-ink); line-height:1.2; }
        .wb-tile-n { font-size:11.5px; color:var(--wb-mute); margin-top:4px; }

        .wb-chip { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; padding:4px 11px;
                   border-radius:999px; font-weight:650; line-height:1.4; }
        .wb-chip i { font-size:12px; }
        .wb-chip-ok { background:#e7f9ef; color:#0f7a43; }
        .wb-chip-no { background:#fdeaea; color:#b42318; }
        .wb-chip-off { background:#eef1f6; color:#5b6675; }
        .wb-chip-warn { background:#fff4e5; color:#a35b00; }

        /* ── plan cards ──────────────────────────────────────────────── */
        .wb-plan { position:relative; border:1.5px solid var(--wb-line); border-radius:14px;
                   padding:22px 20px 20px; height:100%; display:flex; flex-direction:column;
                   background:#fff; transition:border-color .15s, box-shadow .15s, transform .15s; }
        .wb-plan:hover { border-color:#cbd5e1; box-shadow:0 6px 20px rgba(15,23,42,.07); transform:translateY(-2px); }
        .wb-plan.is-current { border-color:var(--wb-green); background:linear-gradient(180deg,#f4fdf8 0%,#fff 46%); }
        .wb-plan.is-current:hover { transform:none; box-shadow:none; }
        .wb-plan-tag { position:absolute; top:-11px; left:50%; transform:translateX(-50%); font-size:10.5px;
                       font-weight:700; letter-spacing:.05em; text-transform:uppercase; padding:4px 12px;
                       border-radius:999px; white-space:nowrap; }
        .wb-plan-tag.cur { background:var(--wb-green); color:#fff; }
        .wb-plan-tag.pop { background:var(--wb-ink); color:#fff; }
        .wb-plan-name { font-size:14px; font-weight:750; color:var(--wb-ink); }
        .wb-plan-amt { font-size:29px; font-weight:800; color:var(--wb-ink); letter-spacing:-.02em; line-height:1.1; margin-top:10px; }
        .wb-plan-amt small { font-size:13px; font-weight:600; color:var(--wb-mute); letter-spacing:0; }
        .wb-feat { list-style:none; padding:0; margin:16px 0 0; flex-grow:1; }
        .wb-feat li { display:flex; gap:9px; font-size:12.5px; color:var(--wb-ink-2); padding:5px 0; line-height:1.5; }
        .wb-feat i { color:var(--wb-green); font-size:14px; flex-shrink:0; margin-top:1px; }
        .wb-feat li.off { color:var(--wb-mute); }
        .wb-feat li.off i { color:#cbd5e1; }

        /* ── rows & meters ───────────────────────────────────────────── */
        .wb-row { display:flex; justify-content:space-between; align-items:center; gap:14px;
                  font-size:13px; color:var(--wb-ink-2); padding:11px 0; border-bottom:1px solid #f2f5f9; }
        .wb-row:last-child { border-bottom:0; }
        .wb-row b { color:var(--wb-ink); font-weight:650; }

        .wb-meter { background:var(--wb-soft); border:1px solid var(--wb-line); border-radius:12px; padding:16px; }
        .wb-meter + .wb-meter { margin-top:12px; }
        .wb-meter-top { display:flex; justify-content:space-between; align-items:baseline; gap:10px; margin-bottom:8px; }
        .wb-meter-n { font-size:12.5px; font-weight:700; color:var(--wb-ink); }
        .wb-meter-v { font-size:22px; font-weight:800; color:var(--wb-ink); line-height:1.1; letter-spacing:-.02em; }
        .wb-meter-v small { font-size:12px; font-weight:600; color:var(--wb-mute); letter-spacing:0; }
        .wb-bar { height:7px; border-radius:99px; background:#e4e9f0; overflow:hidden; margin:10px 0 4px; }
        .wb-bar > span { display:block; height:100%; border-radius:99px;
                         background:linear-gradient(90deg,var(--wb-green-d),var(--wb-green)); transition:width .3s; }
        .wb-bar.low > span { background:linear-gradient(90deg,#dc6803,#f79009); }
        .wb-bar.out > span { background:#d92d20; }
        .wb-split { display:flex; gap:16px; font-size:11.5px; color:var(--wb-mute); }
        .wb-split b { color:var(--wb-ink-2); font-weight:650; }

        .wb-buy { display:flex; gap:8px; margin-top:12px; }
        .wb-buy .form-control { max-width:82px; height:38px; font-size:13px; text-align:center; }
        .wb-buy .btn { height:38px; font-size:12.5px; font-weight:650; padding:0 14px; flex-grow:1; white-space:nowrap; }

        .wb-note { font-size:11.5px; color:var(--wb-mute); line-height:1.6; background:var(--wb-soft);
                   border-radius:10px; padding:12px 14px; margin-top:16px; }

        /* onboarding gate — nothing on this page works until the setup fee lands */
        .wb-gate { display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start; padding:22px;
                   border:1.5px solid #fec84b; border-radius:var(--wb-radius); margin-bottom:20px;
                   background:linear-gradient(135deg,#fffaeb,#fff); }
        .wb-gate-i { width:44px; height:44px; border-radius:12px; flex-shrink:0; display:flex;
                     align-items:center; justify-content:center; font-size:21px;
                     background:#fef0c7; color:#b54708; }
        .wb-gate-t { font-weight:750; font-size:15px; color:var(--wb-ink); }
        .wb-gate-l { list-style:none; padding:0; margin:12px 0 0; }
        .wb-gate-l li { display:flex; gap:9px; font-size:12.5px; color:var(--wb-ink-2);
                        padding:4px 0; line-height:1.5; }
        .wb-gate-l i { color:#d92d20; font-size:13px; flex-shrink:0; margin-top:2px; }

        /* plan card with no CTA yet — onboarding is unpaid */
        .wb-soon { display:flex; align-items:center; justify-content:center; gap:7px;
                   border:1px dashed var(--wb-line); border-radius:9px; padding:11px;
                   font-size:12px; font-weight:600; color:var(--wb-mute); }

        /* a card whose actions cannot work yet */
        .wb-locked { position:relative; }
        .wb-locked .wb-card-b > *:not(.wb-lock) { opacity:.4; pointer-events:none; user-select:none; }
        .wb-lock { display:flex; gap:11px; align-items:flex-start; background:#fffaeb;
                   border:1px solid #fec84b; border-radius:10px; padding:12px 14px;
                   font-size:12px; color:#7a4a06; line-height:1.6; margin-bottom:16px; }
        .wb-lock i { font-size:15px; flex-shrink:0; margin-top:1px; }

        .wb-empty { text-align:center; padding:34px 16px; color:var(--wb-mute); }
        .wb-empty i { font-size:30px; color:#cbd5e1; display:block; margin-bottom:10px; }

        .wb-tbl thead th { font-size:11px; letter-spacing:.05em; text-transform:uppercase;
                           color:var(--wb-mute); font-weight:650; border-top:0; }
        .wb-tbl td { font-size:13px; color:var(--wb-ink-2); vertical-align:middle; }
        .wb-ico { width:30px; height:30px; border-radius:8px; display:inline-flex; align-items:center;
                  justify-content:center; font-size:14px; flex-shrink:0; }
        .wb-inv { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:650;
                  color:var(--wb-green-d); white-space:nowrap; }
        .wb-inv:hover { color:var(--wb-green); text-decoration:underline; }

        @media (max-width:575px) {
            .wb-card-b { padding:18px 16px; }
            .wb-card-h { padding:14px 16px; }
            .wb-price, .wb-plan-amt { font-size:25px; }
        }
    </style>
<style>
.waba-payment-guide{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:24px;
    color:#374151;
    font-size:15px;
    line-height:1.7;
}

.waba-payment-guide__header{
    margin-bottom:20px;
}

.waba-payment-guide__header h3{
    margin:0 0 8px;
    font-size:22px;
    color:#111827;
}

.waba-payment-guide__header p{
    margin:0;
    color:#6b7280;
}

.waba-payment-guide__notice{
    background:#fff8e6;
    border-left:4px solid #f59e0b;
    padding:14px 16px;
    border-radius:6px;
    margin-bottom:24px;
}

.waba-payment-guide h4{
    margin:22px 0 12px;
    font-size:17px;
    color:#111827;
}

.waba-payment-guide__steps{
    padding-left:22px;
    margin:0;
}

.waba-payment-guide__steps li{
    margin-bottom:10px;
}

.waba-payment-guide__checklist{
    list-style:none;
    padding:0;
    margin:0;
}

.waba-payment-guide__checklist li{
    position:relative;
    padding-left:28px;
    margin-bottom:10px;
}

.waba-payment-guide__checklist li::before{
    content:"✔";
    position:absolute;
    left:0;
    top:0;
    color:#16a34a;
    font-weight:bold;
}

.waba-payment-guide__footer{
    margin-top:24px;
    padding:14px 16px;
    background:#f9fafb;
    border:1px solid #e5e7eb;
    border-radius:6px;
    color:#4b5563;
}

.waba-payment-guide a{
    color:#1877f2;
    text-decoration:underline;
}

.waba-payment-guide a:hover{
    color:#0f5bd7;
}

.waba-payment-guide a .tio-open-in-new{
    font-size:12px;
    margin-left:2px;
    vertical-align:baseline;
}

</style>
