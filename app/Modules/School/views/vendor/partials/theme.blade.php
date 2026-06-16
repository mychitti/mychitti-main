{{-- Shared, scoped design system for the School module. Include once per page. --}}

{{-- Branch selector bar (renders once per page, at the top of the content) --}}
@php $schoolBranchList = school_branches(); @endphp
@if (school_can_switch_branch() && $schoolBranchList->count())
    <div class="sch-branchbar">
        <span class="sch-bb-label"><i class="tio-city"></i> Active Branch</span>
        <select class="sch-bb-select" onchange="window.location = this.options[this.selectedIndex].dataset.url">
            <option data-url="{{ route('vendor.school.branches.switch', 0) }}" @selected(!school_active_branch_id())>All Branches</option>
            @foreach ($schoolBranchList as $br)
                <option data-url="{{ route('vendor.school.branches.switch', $br->id) }}" @selected((int) school_active_branch_id() === (int) $br->id)>{{ $br->name }}</option>
            @endforeach
        </select>
        <a href="{{ route('vendor.school.branches.index') }}" class="sch-bb-manage"><i class="tio-settings"></i> Manage</a>
    </div>
@elseif (auth('vendor_employee')->check() && auth('vendor_employee')->user()->branch_id)
    <div class="sch-branchbar">
        <span class="sch-bb-label"><i class="tio-city"></i> Branch: <b>{{ optional(\App\Models\Branch::find(auth('vendor_employee')->user()->branch_id))->name ?? '—' }}</b></span>
    </div>
@endif

@once
@push('css_or_js')
<style>
:root{
    --sch-primary:#4f46e5; --sch-primary-2:#7c3aed; --sch-ink:#1e293b; --sch-muted:#64748b;
    --sch-border:#e9edf5; --sch-soft:#f8fafc; --sch-radius:14px;
    --sch-shadow:0 1px 2px rgba(15,23,42,.04), 0 4px 16px rgba(15,23,42,.05);
    --sch-shadow-h:0 10px 28px rgba(79,70,229,.14);
}

/* ---------- Branch bar ---------- */
.school-page .sch-branchbar{ display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    background:#fff; border:1px solid var(--sch-border); border-radius:12px; padding:8px 14px; margin-bottom:16px; box-shadow:var(--sch-shadow); }
.school-page .sch-bb-label{ font-size:12.5px; font-weight:600; color:var(--sch-muted); display:inline-flex; align-items:center; gap:6px; }
.school-page .sch-bb-label i{ color:var(--sch-primary); }
.school-page .sch-bb-select{ border:1px solid #dfe3ee; border-radius:8px; padding:5px 28px 5px 10px; font-size:13px; font-weight:600; color:var(--sch-ink); background:#fff; min-width:170px; }
.school-page .sch-bb-select:focus{ outline:none; border-color:var(--sch-primary); box-shadow:0 0 0 3px rgba(79,70,229,.15); }
.school-page .sch-bb-manage{ font-size:12.5px; font-weight:600; color:var(--sch-primary); text-decoration:none; }
.school-page .sch-bb-manage:hover{ text-decoration:underline; }

/* ---------- Page header ---------- */
.school-page .page-header{ border-bottom:1px solid var(--sch-border); padding-bottom:18px; margin-bottom:22px; }
.school-page .page-header-title{ display:flex; align-items:center; gap:12px; font-weight:700; font-size:22px; color:var(--sch-ink); letter-spacing:-.2px; }
.school-page .page-header-title > i:first-child,
.school-page .page-header-icon{
    display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; margin:0;
    border-radius:12px; color:#fff !important; font-size:20px;
    background:linear-gradient(135deg,var(--sch-primary),var(--sch-primary-2));
    box-shadow:0 6px 16px rgba(79,70,229,.35);
}
.school-page .page-header-title small,
.school-page .page-header .page-header-text{ font-size:13px; font-weight:400; color:var(--sch-muted); }

/* ---------- Cards ---------- */
.school-page .card{ border:1px solid var(--sch-border); border-radius:var(--sch-radius); box-shadow:var(--sch-shadow); transition:box-shadow .2s, transform .2s; }
.school-page .card:hover{ box-shadow:var(--sch-shadow-h); }
.school-page .card-header{ border-bottom:1px solid var(--sch-border); background:transparent; font-weight:600; color:var(--sch-ink); }
.school-page .card-footer{ background:var(--sch-soft); border-top:1px solid var(--sch-border); }

/* ---------- Tables ---------- */
.school-page .table thead.thead-light th,
.school-page .table thead th{ background:var(--sch-soft); color:var(--sch-muted); font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.6px; border-bottom:1px solid var(--sch-border) !important; padding-top:12px; padding-bottom:12px; }
.school-page .table td{ color:var(--sch-ink); vertical-align:middle; }
.school-page .table tbody tr{ transition:background .12s; }
.school-page .table tbody tr:hover{ background:#f6f7fe; }
.school-page .card-table td, .school-page .card-table th{ padding-left:1.2rem; padding-right:1.2rem; }

/* ---------- Buttons ---------- */
.school-page .btn{ border-radius:10px; font-weight:600; }
.school-page .btn-sm, .school-page .btn-xs{ border-radius:8px; }
.school-page .btn--primary, .school-page .btn-primary{
    background:linear-gradient(135deg,var(--sch-primary),var(--sch-primary-2)); border:none; color:#fff;
    box-shadow:0 4px 12px rgba(79,70,229,.28); }
.school-page .btn--primary:hover, .school-page .btn-primary:hover{ filter:brightness(1.05); color:#fff; }
.school-page .btn-outline-primary{ border-color:#c7c9f7; color:var(--sch-primary); }
.school-page .btn-outline-primary:hover{ background:var(--sch-primary); border-color:var(--sch-primary); }

/* ---------- Table action dropdown ---------- */
.school-page .sch-actions .btn-actions{ border:1px solid var(--sch-border); background:#fff; border-radius:8px; padding:4px 10px; color:var(--sch-muted); line-height:1; }
.school-page .sch-actions .btn-actions:hover, .school-page .sch-actions.show .btn-actions{ background:var(--sch-soft); color:var(--sch-primary); border-color:#c7c9f7; }
.school-page .sch-actions .dropdown-menu{ border:1px solid var(--sch-border); border-radius:12px; box-shadow:var(--sch-shadow-h); padding:6px; min-width:178px; }
.school-page .sch-actions .dropdown-item{ border-radius:8px; font-size:13px; font-weight:500; padding:8px 11px; display:flex; align-items:center; gap:9px; color:#334155; }
.school-page .sch-actions .dropdown-item:hover{ background:var(--sch-soft); }
.school-page .sch-actions .dropdown-item i{ font-size:15px; width:16px; text-align:center; }
.school-page .sch-actions .dropdown-item.text-danger{ color:#dc2626 !important; }
.school-page .sch-actions .dropdown-divider{ margin:5px 4px; }
/* let the action menu escape the table's horizontal scroll wrapper (list tables only, not the timetable grid) */
.school-page .table-responsive:has(.card-table){ overflow: visible; }

/* ---------- Detail label/value rows ---------- */
.school-page .sch-detail .sch-dlabel{ flex:0 0 140px; }
.school-page .sch-detail .sch-dval{ flex:1 1 auto; min-width:0; color:var(--sch-ink); }
@media (max-width:575px){ .school-page .sch-detail .sch-dlabel{ flex-basis:120px; } }

/* ---------- Forms ---------- */
.school-page .input-label{ font-weight:600; font-size:12.5px; color:#334155; margin-bottom:6px; }
.school-page .form-control, .school-page .custom-select{ border-radius:10px; border-color:#dfe3ee; color:var(--sch-ink); }
.school-page .form-control:focus, .school-page .custom-select:focus{ border-color:var(--sch-primary); box-shadow:0 0 0 3px rgba(79,70,229,.15); }
.school-page .form-control-sm{ border-radius:8px; }

/* ---------- Badges ---------- */
.school-page .badge{ border-radius:7px; font-weight:600; padding:.42em .7em; letter-spacing:.2px; }
.school-page .badge-soft-info{ background:#e0e7ff; color:#4338ca; }
.school-page .badge-soft-success{ background:#dcfce7; color:#15803d; }
.school-page .badge-soft-warning{ background:#fef3c7; color:#b45309; }
.school-page .badge-soft-danger{ background:#fee2e2; color:#b91c1c; }

/* ---------- Stat cards (dashboard) ---------- */
.sch-stat{ position:relative; overflow:hidden; border-radius:var(--sch-radius); padding:20px 22px; color:#fff; height:100%;
    box-shadow:0 8px 22px rgba(15,23,42,.12); }
.sch-stat .sch-stat-ico{ position:absolute; right:-6px; top:-6px; font-size:74px; opacity:.16; }
.sch-stat .sch-stat-label{ font-size:12.5px; font-weight:600; opacity:.92; letter-spacing:.3px; }
.sch-stat .sch-stat-value{ font-size:28px; font-weight:800; line-height:1.15; margin-top:4px; }
.sch-stat .sch-stat-sub{ font-size:11.5px; opacity:.85; margin-top:2px; }
.sch-stat--indigo{ background:linear-gradient(135deg,#4f46e5,#7c3aed); }
.sch-stat--green{ background:linear-gradient(135deg,#059669,#10b981); }
.sch-stat--amber{ background:linear-gradient(135deg,#d97706,#f59e0b); }
.sch-stat--sky{ background:linear-gradient(135deg,#0284c7,#38bdf8); }
.sch-stat--rose{ background:linear-gradient(135deg,#e11d48,#fb7185); }
.sch-stat--slate{ background:linear-gradient(135deg,#475569,#64748b); }

/* ---------- Quick action tiles ---------- */
.sch-quick{ display:flex; align-items:center; gap:14px; padding:16px 18px; border:1px solid var(--sch-border);
    border-radius:var(--sch-radius); background:#fff; box-shadow:var(--sch-shadow); transition:.18s; height:100%; color:var(--sch-ink); }
.sch-quick:hover{ transform:translateY(-3px); box-shadow:var(--sch-shadow-h); border-color:#c7c9f7; text-decoration:none; color:var(--sch-ink); }
.sch-quick .sch-quick-ico{ width:46px; height:46px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center;
    font-size:22px; color:#fff; flex:0 0 auto; background:linear-gradient(135deg,var(--sch-primary),var(--sch-primary-2)); }
.sch-quick .sch-quick-t{ font-weight:700; font-size:14px; }
.sch-quick .sch-quick-s{ font-size:12px; color:var(--sch-muted); }

/* ---------- Section title ---------- */
.sch-section-title{ font-size:15px; font-weight:700; color:var(--sch-ink); display:flex; align-items:center; gap:8px; margin-bottom:14px; }
.sch-section-title i{ color:var(--sch-primary); }

/* ---------- Empty state ---------- */
.school-page .text-center.text-muted.py-5{ color:#94a3b8 !important; }
/* select2 cosmetics — height is set inline by JS (sizeSelect2) so it always matches the native field */
.school-page .select2-container .select2-selection--single{
    background:#fff !important; border:1px solid #dfe3ee !important; border-radius:10px !important; }
.school-page .select2-container .select2-selection--single .select2-selection__rendered{
    padding-left:.9rem; padding-right:1.9rem; color:var(--sch-ink); }
.school-page .select2-container .select2-selection--single .select2-selection__placeholder{ color:#94a3b8; }
.school-page .select2-container .select2-selection--single .select2-selection__arrow{ top:0; right:8px; }
.school-page .select2-container .select2-selection--single .select2-selection__clear{ color:#94a3b8; margin-right:6px; }
.school-page .select2-container--open .select2-selection--single,
.school-page .select2-container--focus .select2-selection--single{
    border-color:var(--sch-primary) !important; box-shadow:0 0 0 3px rgba(79,70,229,.15); }
    .select2-selection.select2-selection--single.custom-select{
            height: 45px !important;
    }
</style>
@endpush

@push('script_2')
<script>
$(function () {
    function initAjaxSelect(selector, defaultUrlPlaceholder) {
        $(selector).each(function () {
            var $s = $(this);
            if ($s.data('s2-ready')) return;
            $s.data('s2-ready', true);
            $s.select2({
                width: $s.data('width') || '100%',
                placeholder: $s.data('placeholder') || defaultUrlPlaceholder,
                allowClear: !$s.prop('required'),
                minimumInputLength: 1,
                ajax: {
                    url: $s.data('url'),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) { return { q: params.term }; },
                    processResults: function (data) { return { results: data }; },
                    cache: true
                }
            });
        });
    }
    initAjaxSelect('select.js-ajax-teacher', 'Search teacher…');
    initAjaxSelect('select.js-ajax-student', 'Search student…');

    // Force every school select2 box to match the height of the native field it replaced.
    // (Inline styles beat the select2 stylesheet's fixed 28px regardless of load order/cache.)
    function sizeSelect2() {
        $('.school-page .select2-container').each(function () {
            var $cont = $(this);
            var $sel = $cont.prev('select');
            var sm = $sel.hasClass('form-control-sm');
            var h = sm ? 31 : 38;
            $cont.find('.select2-selection--single').css({
                height: h + 'px', display: 'flex', 'align-items': 'center'
            });
            $cont.find('.select2-selection__rendered').css({
                'line-height': 'normal', 'padding-top': 0, 'padding-bottom': 0,
                'font-size': sm ? '.875rem' : ''
            });
            $cont.find('.select2-selection__arrow').css({ height: (h - 2) + 'px' });
        });
    }
    sizeSelect2();
    setTimeout(sizeSelect2, 250);
    $(document).on('select2:open select2:close select2:select', function () { sizeSelect2(); });
});
</script>
@endpush
@endonce
