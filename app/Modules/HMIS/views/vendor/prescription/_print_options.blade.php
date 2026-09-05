@php
    // Print options for a prescription sheet, shared by the standalone prescription page and the
    // OPD consultation screen so both print the same way and one setting covers both.
    //
    //   $sheetId    id of the printable element on this page
    //   $sections   [data-rx-sec key => label], in the order they appear on the sheet. Pass only
    //               the blocks this particular sheet actually carries -- a checkbox for a diagnosis
    //               the prescription does not have is a control that does nothing.
    //   $headerOff / $headerMm  the hospital's standing letterhead choice for this document, from
    //               Hospital Settings. The panel opens on it and the operator may override it for
    //               a single print; unlike the section choices it is deliberately not remembered
    //               here, because the standing version of that decision now lives in settings.
    //   $storageKey shared by default, so a clinic that always prints on pre-printed pads sets it
    //               once and gets it on every screen.
    $rxSheetId  = $sheetId ?? 'rxPrint';
    $rxSections = $sections ?? [];
    $rxOptsKey  = $storageKey ?? 'hmis.rx.print.v1';
    $rxHdrOff   = (bool) ($headerOff ?? false);
    $rxHdrMm    = (int) ($headerMm ?? 40);
    // Rendered as the caret half of a split button beside Print Rx, where the label would only
    // repeat the word "Print" the button next to it already says.
    $rxCompact  = (bool) ($compact ?? false);
    $rxBtnClass = $btnClass ?? 'btn-outline-secondary';
@endphp

<span class="rx-print-toolbar no-print" data-rx-sheet="{{ $rxSheetId }}" data-rx-key="{{ $rxOptsKey }}">
    <span class="rx-opts-wrap">
        <button type="button" class="btn btn-sm {{ $rxBtnClass }} rx-opts-btn {{ $rxCompact ? 'rx-opts-compact' : '' }}"
                aria-expanded="false" title="Print options — choose what goes on the sheet, and see it as you go">
            @if (!$rxCompact)
            <i class="tio-settings"></i> Print options
            @endif
            <i class="tio-chevron-down rx-opts-chev"></i>
        </button>
        <div class="rx-opts-panel" hidden>
            <div class="rx-opts-titlebar">
                <span class="rx-opts-title">Print options</span>
                <button type="button" class="rx-opts-close" aria-label="Close print options">&times;</button>
            </div>
            {{-- The one thing that is not obvious from looking at the panel: it is not a form you
                 submit, the sheet is already showing the answer. --}}
            <p class="rx-opts-hint">
               Changing it here
                applies to this print only.
            </p>

            <p class="rx-opts-head">Letterhead</p>
            <label class="rx-tile">
                <input type="radio" name="rx_header_mode_{{ $rxSheetId }}" value="with" {{ $rxHdrOff ? '' : 'checked' }}>
                <span class="rx-tile-body">
                    <span class="rx-tile-title">With header</span>
                    <span class="rx-tile-sub">Clinic name, address and doctor details</span>
                </span>
            </label>
            <label class="rx-tile">
                <input type="radio" name="rx_header_mode_{{ $rxSheetId }}" value="without" {{ $rxHdrOff ? 'checked' : '' }}>
                <span class="rx-tile-body">
                    <span class="rx-tile-title">Without header</span>
                    <span class="rx-tile-sub">For paper that already carries the letterhead</span>
                </span>
            </label>
            <div class="rx-blank-row">
                <label class="rx-opt-inline">
                    Leave blank at top
                    <input type="number" class="rx-blank-mm" min="0" max="120" step="5" value="{{ $rxHdrMm }}">
                    <span class="rx-unit">mm</span>
                </label>
                <span class="rx-blank-why">so the print clears your pre-printed letterhead</span>
            </div>

            <div class="rx-opts-head rx-opts-head-sep rx-opts-head-row">
                <span>Sections to print</span>
                <button type="button" class="rx-opts-all">Clear all</button>
            </div>
            @foreach ($rxSections as $key => $label)
            <label class="rx-opt-check">
                <input type="checkbox" data-rx-toggle="{{ $key }}" checked>
                <span>{{ $label }}</span>
            </label>
            @endforeach

            <div class="rx-opts-foot">
                <button type="button" class="rx-opts-reset">Reset to default</button>
                <button type="button" class="btn btn-sm btn--primary rx-opts-print">
                    <i class="tio-print"></i> Print
                </button>
            </div>
        </div>
    </span>
</span>

{{-- Per-sheet, because the id differs by screen. The sheet is moved into .rx-print-portal for the
     duration of the print (see the script below), so everything else on the page can be collapsed
     outright: hiding it with visibility alone leaves it occupying space, which on a screen as long
     as the consultation desk prints as a run of blank pages after the prescription. --}}
<style>
@media print {
    /* The page box deliberately carries no margin of its own. Chrome's print dialog has a Margins
       setting that silently overrides @page, and a front desk that once set it to None keeps it
       for every print after -- which is how a sheet ends up hard against the paper edge. The
       margin lives on the sheet as padding instead, which no dialog setting can take away, and
       the result is the same whether the dialog says Default or None. */
    @page { size: A4; margin: 0; }
    /* Guarded on the portal actually being there: if the script never ran, the page prints the
       way it did before rather than printing a blank sheet. */
    body:has(.rx-print-portal) > *:not(.rx-print-portal) { display: none !important; }
    .rx-print-portal, .rx-print-portal > * { display: block !important; }
    .rx-print-portal #{{ $rxSheetId }} {
        width: auto !important; max-width: none !important;
        margin: 0 !important; padding: 12mm 14mm !important;
        border: none !important; border-radius: 0 !important; box-shadow: none !important;
    }
    /* Screen-only furniture that travels inside the sheet: the draft badge, the page guide. */
    .rx-print-portal .no-print { display: none !important; }
    /* Keep a medicine on one page, and repeat the column headings when the table breaks. */
    .rx-print-portal thead { display: table-header-group; }
    .rx-print-portal tr { page-break-inside: avoid; }
}
</style>

@once
<style>
/* ── Print options panel ──────────────────────────────────── */
.rx-print-toolbar { display: inline-flex; align-items: center; gap: 8px; }
.rx-opts-wrap { position: relative; display: inline-block; }
.rx-opts-btn { position: relative; }
.rx-opts-chev { font-size: 10px; margin-left: 2px; transition: transform .15s; display: inline-block; }
.rx-opts-compact { padding-left: 9px; padding-right: 9px; }
.rx-opts-compact .rx-opts-chev { margin-left: 0; }

/* Print and its options are one job, so they are one control: a split button whose caret half
   opens the panel. Two separate buttons side by side, styled differently, read as two tasks. */
.rx-split { display: inline-flex; align-items: stretch; }
.rx-split > .rx-split-main { border-top-right-radius: 0; border-bottom-right-radius: 0; }
.rx-split .rx-print-toolbar { margin-left: -1px; }
.rx-split .rx-opts-btn { border-top-left-radius: 0; border-bottom-left-radius: 0; }
.rx-split .rx-opts-btn::before {
    content: ''; position: absolute; left: 0; top: 6px; bottom: 6px;
    border-left: 1px solid rgba(255, 255, 255, .35);
}
.rx-opts-btn[aria-expanded="true"] .rx-opts-chev { transform: rotate(180deg); }
/* A dot on the button whenever the sheet is not printing in full, so nobody hands a patient a
   prescription with the medicines switched off and never notices the setting was still on. */
.rx-opts-btn.rx-opts-custom::after {
    content: ''; position: absolute; top: 3px; right: 3px;
    width: 7px; height: 7px; background: #f59e0b; border-radius: 50%;
}
.rx-opts-panel {
    position: absolute; right: 0; top: calc(100% + 6px); z-index: 1050; width: 300px;
    background: #fff; border: 1px solid #dbe3ec; border-radius: 10px;
    box-shadow: 0 12px 32px rgba(15, 23, 42, .18); padding: 0 14px 12px;
    text-align: left; color: #0f172a;
    font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
}
.rx-opts-panel[hidden] { display: none !important; }

.rx-opts-titlebar {
    display: flex; align-items: center; justify-content: space-between;
    margin: 0 -14px 10px; padding: 10px 12px 9px 14px; border-bottom: 1px solid #eef2f7;
}
.rx-opts-title { font-size: 13px; font-weight: 700; }
.rx-opts-close {
    border: 0; background: none; color: #94a3b8; font-size: 20px; line-height: 1;
    padding: 0 4px; cursor: pointer; border-radius: 4px;
}
.rx-opts-close:hover { color: #334155; background: #f1f5f9; }

.rx-opts-hint {
    font-size: 11px; line-height: 1.45; color: #0f5132; margin: 0 0 12px;
    background: #eefaf3; border: 1px solid #cfeddd; border-radius: 6px; padding: 7px 9px;
}
.rx-opts-hint strong { font-weight: 700; }

.rx-opts-head {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    color: #64748b; margin: 0 0 8px;
}
.rx-opts-head-sep { margin-top: 14px; border-top: 1px solid #eef2f7; padding-top: 12px; }
.rx-opts-head-row { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
.rx-opts-all {
    border: 0; background: none; padding: 0; cursor: pointer;
    font-size: 11px; font-weight: 600; letter-spacing: 0; text-transform: none; color: #2563eb;
}
.rx-opts-all:hover { text-decoration: underline; }

/* Letterhead choices as tiles: a bigger target than a bare radio, and the chosen one reads at a
   glance from across a busy front desk. */
.rx-tile {
    display: flex; gap: 9px; align-items: flex-start; margin: 0 0 7px; cursor: pointer;
    border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px; font-weight: 400;
    transition: border-color .12s, background-color .12s;
}
.rx-tile:hover { border-color: #cbd5e1; background: #f8fafc; }
.rx-tile:has(input:checked) { border-color: #2563eb; background: #eff6ff; }
.rx-tile input { margin: 2px 0 0; flex: 0 0 auto; }
.rx-tile-title { display: block; font-size: 13px; font-weight: 600; }
.rx-tile-sub { display: block; font-size: 11px; color: #94a3b8; line-height: 1.35; margin-top: 1px; }

.rx-blank-row { padding: 2px 0 0 30px; margin-bottom: 2px; }
.rx-opt-inline {
    display: flex; align-items: center; gap: 6px; margin: 0;
    font-size: 12px; font-weight: 400; color: #475569;
}
.rx-opt-inline input {
    width: 58px; padding: 3px 7px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 12px;
}
.rx-unit { color: #94a3b8; }
.rx-blank-why { display: block; font-size: 10.5px; color: #a3aec0; margin-top: 3px; }
.rx-opts-disabled { opacity: .38; pointer-events: none; }

.rx-opt-check {
    display: flex; align-items: center; gap: 9px; margin: 0; padding: 5px 6px;
    font-size: 13px; font-weight: 400; color: #334155; cursor: pointer; border-radius: 6px;
}
.rx-opt-check:hover { background: #f1f5f9; }
.rx-opt-check input { flex: 0 0 auto; margin: 0; }

.rx-opts-foot {
    display: flex; justify-content: space-between; align-items: center;
    margin-top: 14px; border-top: 1px solid #eef2f7; padding-top: 11px;
}
.rx-opts-reset {
    border: 0; background: none; padding: 0; cursor: pointer;
    font-size: 12px; font-weight: 600; color: #64748b;
}
.rx-opts-reset:hover { color: #334155; text-decoration: underline; }

/* A section switched off in the panel. The same class governs screen and print, which is what
   makes the sheet on screen an honest preview of the one coming out of the printer. */
.rx-sec-off { display: none !important; }

/* Top margin held clear for pre-printed stationery. Zero until the header is switched off. */
.rx-blank-head { height: 0; }

/* ── Preview: the sheet at its printed size ───────────────── */
.rx-preview-sheet {
    position: relative !important;
    width: 210mm !important; max-width: 210mm !important;
    padding: 12mm 14mm !important; margin: 0 auto !important;
    background: #fff; border: 1px solid #cbd5e1 !important; border-radius: 0 !important;
    box-shadow: 0 10px 30px rgba(15, 23, 42, .18) !important;
}
.rx-page-guide { position: absolute; left: 0; right: 0; border-top: 1px dashed #f59e0b; pointer-events: none; }
.rx-page-guide span {
    position: absolute; right: 0; top: -9px; background: #fffbeb; color: #b45309;
    font-family: system-ui, -apple-system, sans-serif; font-size: 9px; padding: 1px 6px;
    border-radius: 3px; white-space: nowrap;
}

/* Docked: while previewing, the panel is part of the workspace rather than a popover. It stays
   put as the sheet is scrolled, and the sheet slides off centre so the two never overlap --
   otherwise the controls float over the page they are meant to be changing, then scroll away. */
@media (min-width: 1250px) {
    .rx-print-toolbar.rx-docked .rx-opts-panel {
        position: fixed; top: 92px; right: 24px; left: auto;
        max-height: calc(100vh - 120px); overflow-y: auto;
    }
    body.rx-opts-docked .rx-preview-sheet { margin-left: 0 !important; margin-right: auto !important; }
}
/* Below A4 width the page frame is a lie, so preview drops back to plain full width. */
@media (max-width: 860px) {
    .rx-preview-sheet { width: 100% !important; max-width: 100% !important; padding: 20px 22px !important; }
    .rx-page-guide { display: none !important; }
}
</style>

<script>
(function () {
    // Display only: nothing here changes what the prescription says, so none of it is worth a round
    // trip. The choice lives in this browser because it belongs to the machine the printer is
    // attached to -- the front desk prints on pre-printed pads, the ward prints on plain paper.
    var MM = 96 / 25.4;   // CSS px per mm
    // The page box has no margin of its own, so one printed page is a full A4 of the sheet's own
    // box -- and preview pads the sheet exactly as print does, so the two measure the same.
    var PAGE_MM = 297;

    function wire(bar) {
        var sheet = document.getElementById(bar.getAttribute('data-rx-sheet'));
        var panel = bar.querySelector('.rx-opts-panel');
        var btn   = bar.querySelector('.rx-opts-btn');
        if (!sheet || !panel || !btn) return;

        var KEY      = bar.getAttribute('data-rx-key');
        var blankMm  = panel.querySelector('.rx-blank-mm');
        var blankRow = panel.querySelector('.rx-blank-row');
        var allBtn   = panel.querySelector('.rx-opts-all');
        var checks   = [].slice.call(panel.querySelectorAll('[data-rx-toggle]'));
        var radios   = [].slice.call(panel.querySelectorAll('input[type="radio"]'));

        // Built here rather than asked of every page that prints a prescription: neither is
        // content, and a page that forgets one would silently lose the feature.
        var blank = document.createElement('div');
        blank.className = 'rx-blank-head';
        blank.setAttribute('aria-hidden', 'true');
        sheet.insertBefore(blank, sheet.firstChild);

        var guide = document.createElement('div');
        guide.className = 'rx-page-guide no-print';
        guide.hidden = true;
        guide.innerHTML = '<span>Page 1 ends about here</span>';
        sheet.appendChild(guide);

        function previewOn() { return sheet.classList.contains('rx-preview-sheet'); }

        function headerMode() {
            var on = panel.querySelector('input[type="radio"]:checked');
            return on ? on.value : 'with';
        }

        function setSection(name, visible) {
            [].forEach.call(sheet.querySelectorAll('[data-rx-sec="' + name + '"]'), function (el) {
                el.classList.toggle('rx-sec-off', !visible);
            });
        }

        function apply() {
            var withHeader = headerMode() === 'with';
            setSection('header', withHeader);
            blankRow.classList.toggle('rx-opts-disabled', withHeader);

            var mm = parseInt(blankMm.value, 10);
            if (isNaN(mm) || mm < 0) mm = 0;
            if (mm > 120) mm = 120;
            blank.style.height = withHeader ? '0' : mm + 'mm';

            var trimmed = !withHeader, allOn = true;
            checks.forEach(function (cb) {
                setSection(cb.getAttribute('data-rx-toggle'), cb.checked);
                if (!cb.checked) { trimmed = true; allOn = false; }
            });
            btn.classList.toggle('rx-opts-custom', trimmed);
            if (allBtn) allBtn.textContent = allOn ? 'Clear all' : 'Select all';

            save();
            syncForms();
            positionGuide();
        }

        function stored() {
            try { return JSON.parse(localStorage.getItem(KEY) || 'null') || {}; } catch (e) { return {}; }
        }

        function current() {
            var state = { header: headerMode(), blank: blankMm.value, secs: {} };
            checks.forEach(function (cb) { state.secs[cb.getAttribute('data-rx-toggle')] = cb.checked; });
            return state;
        }

        function save() {
            // Sections only. The letterhead is the hospital's standing decision now, kept in
            // Hospital Settings, so remembering an override here would quietly outrank it and
            // leave a clinic wondering why the setting it just changed did nothing.
            //
            // Merged, not replaced: this screen's sheet may carry fewer sections than the next
            // one, and rewriting the whole record here would drop the settings for blocks this
            // page happens not to have.
            var now   = current();
            var state = stored();
            state.secs = state.secs || {};
            Object.keys(now.secs).forEach(function (k) { state.secs[k] = now.secs[k]; });
            try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {}
        }

        // Anything the same choice should govern off-screen -- the WhatsApp PDF, above all. A
        // patient who is sent the attachment gets the sheet the sender was looking at, rather than
        // a second document that quietly says more than the printed one.
        function syncForms() {
            var payload = JSON.stringify(current());
            [].forEach.call(document.querySelectorAll('form[data-rx-print-opts]'), function (f) {
                var input = f.querySelector('input[name="print_opts"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'print_opts';
                    f.appendChild(input);
                }
                input.value = payload;
            });
        }

        function load() {
            // The letterhead radios and the mm box are left as rendered -- Blade has already set
            // them from Hospital Settings.
            var state = stored();
            if (state.secs) {
                checks.forEach(function (cb) {
                    var k = cb.getAttribute('data-rx-toggle');
                    if (Object.prototype.hasOwnProperty.call(state.secs, k)) cb.checked = !!state.secs[k];
                });
            }
        }

        function positionGuide() {
            if (!previewOn()) { guide.hidden = true; return; }
            guide.style.top = (PAGE_MM * MM) + 'px';
            guide.hidden = sheet.scrollHeight <= PAGE_MM * MM;
        }

        // Options and preview are one mode: opening the panel puts the sheet at its printed size
        // and docks the controls beside it, closing it puts the screen back. There is no separate
        // Preview button to fall out of step with the panel.
        function openPanel(open) {
            panel.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            setPreview(open);
            bar.classList.toggle('rx-docked', open);
            document.body.classList.toggle('rx-opts-docked', open);
        }

        var hidden = null;

        function setPreview(on) {
            if (on === previewOn()) return;
            sheet.classList.toggle('rx-preview-sheet', on);
            // A sheet that sits collapsed on its screen has to be opened to be previewed, and put
            // back the way it was found on the way out.
            if (on) {
                hidden = sheet.style.display === 'none' ? 'none' : null;
                if (hidden) sheet.style.display = '';
            } else if (hidden) {
                sheet.style.display = hidden;
                hidden = null;
            }
            positionGuide();
            if (on) sheet.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        btn.addEventListener('click', function () { openPanel(panel.hidden); });
        panel.querySelector('.rx-opts-close').addEventListener('click', function () { openPanel(false); });
        // Deliberately no close-on-outside-click: while the panel is open the sheet beside it is
        // the thing being worked on, and clicking it is looking at your work, not asking for the
        // controls to go away. The X, the button itself and Escape all close it.
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') openPanel(false); });

        radios.forEach(function (r) { r.addEventListener('change', apply); });
        checks.forEach(function (cb) { cb.addEventListener('change', apply); });
        blankMm.addEventListener('input', apply);

        if (allBtn) {
            allBtn.addEventListener('click', function () {
                var turnOn = checks.some(function (cb) { return !cb.checked; });
                checks.forEach(function (cb) { cb.checked = turnOn; });
                apply();
            });
        }

        panel.querySelector('.rx-opts-reset').addEventListener('click', function () {
            radios.forEach(function (r) { r.checked = r.value === 'with'; });
            blankMm.value = 40;
            checks.forEach(function (cb) { cb.checked = true; });
            apply();
        });

        // The sheet is lifted out to a direct child of <body> for the duration of the print, so the
        // print rule can collapse every other top-level block without touching the sheet's own
        // layout. Put back on afterprint, which is what makes this non-destructive -- the older
        // approach swapped document.body.innerHTML and then reloaded the page.
        var portal = null, marker = null;

        function toPortal() {
            if (portal || !sheet.parentNode) return;
            marker = document.createComment('rx-print-sheet');
            sheet.parentNode.insertBefore(marker, sheet);
            portal = document.createElement('div');
            portal.className = 'rx-print-portal';
            portal.appendChild(sheet);
            document.body.appendChild(portal);
        }

        function fromPortal() {
            if (!portal) return;
            marker.parentNode.insertBefore(sheet, marker);
            marker.parentNode.removeChild(marker);
            document.body.removeChild(portal);
            portal = null;
            marker = null;
        }

        window.addEventListener('beforeprint', toPortal);
        window.addEventListener('afterprint', fromPortal);

        panel.querySelector('.rx-opts-print').addEventListener('click', function () {
            openPanel(false);
            toPortal();
            window.print();
        });

        load();
        apply();
        // Web fonts land after this runs and move the page break with them.
        window.addEventListener('load', positionGuide);
    }

    function init() {
        [].forEach.call(document.querySelectorAll('.rx-print-toolbar'), wire);
    }

    // This block is emitted once however many toolbars a page carries, so it waits for the whole
    // document rather than wiring only the toolbars that happen to sit above it.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endonce
