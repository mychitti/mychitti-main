<style>
    /* Shared Retail POS theme — bound to the store's brand color (var(--primary)). */
    .rp { --ink:#212b36; --muted:#8893a3; --line:#edf0f5; --soft:#f6f7fb;
          --accent:var(--primary,#754BFF); --accent-dark:var(--primary-dark,#6e44fa); color:var(--ink); }
    .rp .rp-head { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px; }
    .rp .rp-head h1 { font-size:22px; font-weight:700; margin:0; }
    .rp .rp-head .sub { font-size:12px; color:var(--muted); }

    .rp-card { background:#fff; border:1px solid var(--line); border-radius:16px; box-shadow:0 1px 3px rgba(16,24,40,.05); overflow:hidden; margin-bottom:16px; }
    .rp-card .hd { padding:13px 18px; border-bottom:1px solid var(--line); font-weight:700; font-size:14px; display:flex; justify-content:space-between; align-items:center; }
    .rp-card .hd .accent { border-left:3px solid var(--accent); padding-left:9px; }
    .rp-card .bd { padding:16px 18px; }

    .rp-table { width:100%; font-size:13px; margin:0; }
    .rp-table thead th { font-size:10.5px; text-transform:uppercase; letter-spacing:.03em; color:var(--muted); padding:10px 16px; border-bottom:1px solid var(--line); background:#fbfbfd; text-align:left; }
    .rp-table td { padding:11px 16px; border-bottom:1px solid var(--line); vertical-align:middle; }
    .rp-table tbody tr:last-child td { border-bottom:0; }
    .rp-table tbody tr:hover td { background:var(--soft); }
    .rp-table tfoot td { padding:11px 16px; border-top:2px solid var(--line); font-weight:700; }

    .rp-btn { display:inline-flex; align-items:center; gap:5px; border:0; border-radius:10px; padding:8px 14px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; }
    .rp-btn.p { background:var(--accent); color:#fff !important; }
    .rp-btn.p:hover { filter:brightness(.93); color:#fff !important; }
    .rp-btn.o { background:#fff; color:var(--ink) !important; }
    .rp-btn.o:hover { border-color:var(--accent); color:var(--accent) !important; }
    .rp-btn.sm { padding:5px 10px; font-size:12px; border-radius:8px; }

    .rp-input, .rp select.rp-input { border:1px solid #e3e7ef; border-radius:10px; padding:7px 12px; font-size:13px; height:38px; background:#fff; color:var(--ink); }
    .rp-input:focus { border-color:var(--accent); outline:0; box-shadow:0 0 0 3px rgba(0,0,0,.05); }
    .rp-filter { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

    .rp-badge { font-size:10px; font-weight:700; padding:4px 9px; border-radius:20px; text-transform:uppercase; letter-spacing:.02em; }
    .rp-badge.ok { background:#e8f7ef; color:#157347; }
    .rp-badge.bad { background:#fde9ea; color:#dc3545; }
    .rp-badge.info { background:#e7f1ff; color:#0d6efd; }
    .rp-badge.muted { background:#eef1f6; color:#64748b; }
    .rp-empty { text-align:center; color:var(--muted); padding:30px; }

    .rp-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; margin-bottom:16px; }
    .rp-kpi { background:#fff; border:1px solid var(--line); border-radius:14px; padding:14px 16px; box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .rp-kpi .v { font-size:20px; font-weight:700; line-height:1.1; }
    .rp-kpi .l { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; margin-top:3px; }

    .rp-field { margin-bottom:12px; }
    .rp-field label { font-size:12px; font-weight:600; color:#56606e; display:block; margin-bottom:4px; }
    .rp-field .rp-input { width:100%; }
</style>
