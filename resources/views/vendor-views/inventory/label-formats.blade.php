@extends('layouts.vendor.app')

@section('title', 'Label Formats')

@push('css_or_js')
    <style>
        .lf-canvas-wrap { background: #f1f3f7; padding: 22px; border-radius: 10px; overflow: auto; }
        #lf-canvas { position: relative; background: #fff; border: 1px solid #333; box-shadow: 0 2px 10px rgba(0,0,0,.12); }
        .lf-el { position: absolute; cursor: move; padding: 1px 2px; border: 1px dashed transparent; white-space: nowrap; user-select: none; }
        .lf-el:hover { border-color: #b9c2ff; }
        .lf-el.sel { border-color: #4f46e5; background: rgba(79,70,229,.06); }
        .lf-el .bc { display:inline-block; background: repeating-linear-gradient(90deg,#222 0 2px,#fff 2px 4px); }
        .lf-pal button { display:block; width:100%; text-align:left; margin-bottom:6px; }
        .lf-fmt-item { border:1px solid #e7eaf3; border-radius:8px; padding:8px 10px; margin-bottom:8px; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="page-header-title">Label Formats</h1>
                <small class="text-muted">Design label layouts, save multiple, set one default. Used by item label print.</small>
            </div>
            <a href="{{ route('vendor.inventory.settings') }}" class="btn btn-sm btn-outline-secondary">← Inventory Settings</a>
        </div>

        <div class="row">
            <!-- Saved formats -->
            <div class="col-lg-3 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Saved Formats</span>
                        <button class="btn btn-sm btn-primary" onclick="newFormat()">+ New</button>
                    </div>
                    <div class="card-body">
                        @forelse ($formats as $f)
                            <div class="lf-fmt-item">
                                <div class="d-flex justify-content-between">
                                    <b>{{ $f->name }}</b>
                                    @if ($f->is_default)<span class="badge badge-success">Default</span>@endif
                                </div>
                                <small class="text-muted">{{ rtrim(rtrim($f->width_mm, '0'), '.') }} × {{ rtrim(rtrim($f->height_mm, '0'), '.') }} mm @if(($f->cols ?? 1) > 1)· {{ $f->cols }}-up @endif</small>
                                <div class="mt-2 d-flex flex-wrap" style="gap:6px;">
                                    <button class="btn btn-xs btn-outline-primary" onclick='editFormat(@json($f))'>Edit</button>
                                    @if (!$f->is_default)
                                        <form method="post" action="{{ route('vendor.inventory.label-formats.default', $f->id) }}" class="d-inline">@csrf
                                            <button class="btn btn-xs btn-outline-success">Set default</button>
                                        </form>
                                    @endif
                                    <form method="post" action="{{ route('vendor.inventory.label-formats.delete', $f->id) }}" class="d-inline" onsubmit="return confirm('Delete this format?')">@csrf
                                        <button class="btn btn-xs btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted">No formats yet. Click <b>+ New</b> to design one.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Designer -->
            <div class="col-lg-6 mb-3">
                <div class="card">
                    <div class="card-header">Designer</div>
                    <div class="card-body">
                        <div class="form-row align-items-end" style="gap:8px;">
                            <div class="col"><label class="small mb-1">Format name</label>
                                <input id="fmt-name" class="form-control form-control-sm" placeholder="e.g. Retail 50×25"></div>
                            <div><label class="small mb-1">W (mm)</label>
                                <input id="fmt-w" type="number" value="50" min="10" class="form-control form-control-sm" style="width:80px" oninput="render()"></div>
                            <div><label class="small mb-1">H (mm)</label>
                                <input id="fmt-h" type="number" value="25" min="10" class="form-control form-control-sm" style="width:80px" oninput="render()"></div>
                            <div><label class="small mb-1" title="How many copies of this label print side by side per row">Labels per row</label>
                                <input id="fmt-cols" type="number" value="1" min="1" max="10" class="form-control form-control-sm" style="width:90px" oninput="render()"></div>
                            <div><label class="small mb-1">Gap (mm)</label>
                                <input id="fmt-gap" type="number" value="0" min="0" step="0.5" class="form-control form-control-sm" style="width:80px" oninput="render()"></div>
                            <div class="pb-1"><label class="small mb-0"><input type="checkbox" id="fmt-default"> Default</label></div>
                        </div>

                        <div class="lf-canvas-wrap mt-3 text-center">
                            <div id="lf-canvas"></div>
                        </div>
                        <small class="text-muted d-block mt-2">Drag a field to move it · click a field to edit it in the panel below ↓</small>

                        <div class="mt-3" id="lf-props" style="display:none; border:1px solid #d8def0; border-radius:10px; padding:12px 14px; background:#f3f6fc;">
                            <div class="font-weight-bold mb-2" style="font-size:13px;color:#4f46e5;">⚙ Field Properties
                                <span class="text-muted" style="font-weight:400;font-size:11px;">— editing the selected field</span></div>
                            <div class="d-flex flex-wrap align-items-end" style="gap:12px;">
                                <div style="flex:1; min-width:180px;">
                                    <label class="small mb-1" id="prop-text-lbl">Text / Label</label>
                                    <input id="prop-text" class="form-control form-control-sm" oninput="updProp()">
                                </div>
                                <div style="width:80px;">
                                    <label class="small mb-1">Font (pt)</label>
                                    <input id="prop-font" type="number" min="4" step="0.5" class="form-control form-control-sm" oninput="updProp()">
                                </div>
                                <div style="width:110px;">
                                    <label class="small mb-1">Alignment</label>
                                    <select id="prop-align" class="form-control form-control-sm" onchange="updProp()">
                                        <option value="left">Left</option>
                                        <option value="center">Center</option>
                                        <option value="right">Right</option>
                                    </select>
                                </div>
                                <div class="pb-1">
                                    <label class="small mb-1 d-block"><input type="checkbox" id="prop-bold" onchange="updProp()"> Bold</label>
                                    <span id="prop-newline-container" style="display:none;"><label class="small mb-0 d-block"><input type="checkbox" id="prop-newline" onchange="updProp()"> Value on new line</label></span>
                                </div>
                                <div id="prop-bc" style="display:none;">
                                    <label class="small mb-1">Barcode W × H (mm)</label>
                                    <div class="d-flex" style="gap:6px;">
                                        <input id="prop-bw" type="number" class="form-control form-control-sm" style="width:70px;" oninput="updProp()">
                                        <input id="prop-bh" type="number" class="form-control form-control-sm" style="width:70px;" oninput="updProp()">
                                    </div>
                                </div>
                                <div class="pb-1">
                                    <button class="btn btn-sm btn-outline-danger" onclick="removeEl()">Remove field</button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-primary" onclick="saveFormat()">💾 Save Format</button>
                            <button class="btn btn-outline-secondary" onclick="newFormat()">Clear</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Palette + properties -->
            <div class="col-lg-3 mb-3">
                <div class="card">
                    <div class="card-header">Add Field</div>
                    <div class="card-body lf-pal">
                        <button class="btn btn-sm btn-outline-dark" onclick="addEl('store_name')">Store Name</button>
                        <button class="btn btn-sm btn-outline-dark" onclick="addEl('subtitle')">Subtitle / Firm</button>
                        <button class="btn btn-sm btn-outline-dark" onclick="addEl('item_name')">Item Name</button>
                        <button class="btn btn-sm btn-outline-dark" onclick="addEl('code')">SKU / Code</button>
                        <button class="btn btn-sm btn-outline-dark" onclick="addEl('mrp')">MRP</button>
                        <button class="btn btn-sm btn-outline-dark" onclick="addEl('selling_price')">Selling Price</button>
                        <button class="btn btn-sm btn-outline-dark" onclick="addEl('packing_date')">Packing Date</button>
                        <button class="btn btn-sm btn-outline-dark" onclick="addEl('expiry_date')">Expiry Date</button>
                        <button class="btn btn-sm btn-outline-dark" onclick="addEl('barcode')">Barcode</button>
                        <button class="btn btn-sm btn-outline-dark" onclick="addEl('custom_text')">Custom Text</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="lf-save-form" method="post" action="{{ route('vendor.inventory.label-formats.save') }}" style="display:none;">
        @csrf
        <input name="id" id="f-id"><input name="name" id="f-name"><input name="width_mm" id="f-w">
        <input name="height_mm" id="f-h"><input name="cols" id="f-cols"><input name="col_gap_mm" id="f-gap">
        <input name="is_default" id="f-default"><input name="elements" id="f-elements">
    </form>

    <script>
        const SCALE = 4; // px per mm
        const LABELS = { store_name: '', subtitle: 'Firm name', item_name: '', code: 'Code', mrp: 'M.R.P', selling_price: 'S.S.C PRICE', packing_date: 'Date', expiry_date: 'Exp', custom_text: 'Text' };
        const SAMPLE = { store_name: 'WHOLE SALE MART', subtitle: 'SRI SAI & CO', item_name: 'GN SEEDS BULLET 1KG', code: '507', mrp: '230.00', selling_price: '210.00', packing_date: '13/06/26', expiry_date: '13/12/26', custom_text: 'Text' };
        let els = [], selIdx = -1, editId = '';
        const canvas = document.getElementById('lf-canvas');

        function newFormat() {
            els = []; selIdx = -1; editId = '';
            document.getElementById('fmt-name').value = '';
            document.getElementById('fmt-w').value = 50;
            document.getElementById('fmt-h').value = 25;
            document.getElementById('fmt-cols').value = 1;
            document.getElementById('fmt-gap').value = 0;
            document.getElementById('fmt-default').checked = false;
            document.getElementById('lf-props').style.display = 'none';
            render();
        }

        function editFormat(f) {
            editId = f.id;
            document.getElementById('fmt-name').value = f.name || '';
            document.getElementById('fmt-w').value = parseFloat(f.width_mm) || 50;
            document.getElementById('fmt-h').value = parseFloat(f.height_mm) || 25;
            document.getElementById('fmt-cols').value = parseInt(f.cols) || 1;
            document.getElementById('fmt-gap').value = parseFloat(f.col_gap_mm) || 0;
            document.getElementById('fmt-default').checked = !!(+f.is_default);
            try { els = JSON.parse(f.elements || '[]') || []; } catch (e) { els = []; }
            // Pull any out-of-bounds fields back onto the label so they're visible/deletable.
            var W = parseFloat(f.width_mm) || 50, H = parseFloat(f.height_mm) || 25;
            els.forEach(function (el) {
                el.x = Math.max(0, Math.min(W - 2, parseFloat(el.x) || 0));
                el.y = Math.max(0, Math.min(H - 2, parseFloat(el.y) || 0));
            });
            selIdx = -1;
            document.getElementById('lf-props').style.display = 'none';
            render();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Press Delete / Backspace to remove the selected field (unless typing in an input).
        document.addEventListener('keydown', function (e) {
            if ((e.key === 'Delete' || e.key === 'Backspace') && selIdx >= 0
                && !['INPUT', 'TEXTAREA', 'SELECT'].includes((document.activeElement || {}).tagName)) {
                e.preventDefault();
                removeAt(selIdx);
            }
        });

        function addEl(type) {
            const el = { type: type, text: LABELS[type] ?? '', x: 2, y: 2, font: type === 'store_name' ? 10 : 8, bold: (type === 'store_name'), w: 30, h: 10, newline: false, align: 'center' };
            els.push(el); selIdx = els.length - 1; render(); openProps();
        }

        function escapeHtml(str) {
            return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function render() {
            const W = parseFloat(document.getElementById('fmt-w').value) || 50;
            const H = parseFloat(document.getElementById('fmt-h').value) || 25;
            const cols = Math.max(1, parseInt(document.getElementById('fmt-cols').value) || 1);
            const gap = Math.max(0, parseFloat(document.getElementById('fmt-gap').value) || 0);
            canvas.style.width = ((W * cols + gap * (cols - 1)) * SCALE) + 'px';
            canvas.style.height = (H * SCALE) + 'px';
            canvas.innerHTML = '';
            // Faint cell outline per label so the multi-up layout is visible.
            for (let c = 0; c < cols; c++) {
                const cell = document.createElement('div');
                cell.style.cssText = 'position:absolute;top:0;border:1px dashed #cbd2e0;pointer-events:none;';
                cell.style.left = (c * (W + gap) * SCALE) + 'px';
                cell.style.width = (W * SCALE) + 'px';
                cell.style.height = (H * SCALE) + 'px';
                canvas.appendChild(cell);
            }
            els.forEach((el, i) => {
                const d = document.createElement('div');
                d.className = 'lf-el' + (i === selIdx ? ' sel' : '');
                d.style.left = (el.x * SCALE) + 'px';
                d.style.top = (el.y * SCALE) + 'px';
                d.style.fontSize = ((el.font || 8) * SCALE / 2.83) + 'px'; // pt→px approx
                d.style.fontWeight = el.bold ? 'bold' : 'normal';
                d.style.textAlign = el.align || 'center';
                const content = document.createElement('span');
                if (el.type === 'barcode') {
                    // Show the code under the bars — the final label prints the SKU there automatically.
                    content.innerHTML = '<span class="bc" style="width:' + ((el.w || 30) * SCALE) + 'px;height:' + ((el.h || 10) * SCALE) + 'px;display:block;"></span>' +
                        '<span style="display:block;text-align:center;line-height:1.1;font-size:' + Math.max(7, ((el.font || 8) * SCALE / 2.83) - 1) + 'px;">' + (SAMPLE.barcode_code || '1234567') + '</span>';
                } else {
                    d.style.whiteSpace = el.newline ? 'normal' : 'nowrap';
                    const t = el.text || '';
                    const s = SAMPLE[el.type] || '';
                    if (el.type === 'store_name') {
                        content.textContent = (t && t.toLowerCase() !== 'store') ? t : s;
                    } else if (el.type === 'subtitle' || el.type === 'custom_text') {
                        content.textContent = t || s;
                    } else {
                        // code, mrp, selling_price, packing_date, expiry_date, item_name
                        let prefix = '';
                        if (el.type === 'item_name') {
                            prefix = t;
                        } else {
                            prefix = t || LABELS[el.type] || '';
                        }
                        if (el.newline && prefix) {
                            content.innerHTML = escapeHtml(prefix) + '<br/>' + escapeHtml(s);
                        } else {
                            content.textContent = (prefix ? prefix + ' ' : '') + s;
                        }
                    }
                    if (!content.innerHTML && !content.textContent) {
                        content.textContent = '(' + el.type + ')';
                    }
                }
                d.appendChild(content);
                // edit + delete buttons — only on the selected field
                if (i === selIdx) {
                    if (el.type !== 'barcode') {
                        const edit = document.createElement('span');
                        edit.textContent = '✎';
                        edit.title = 'Edit text';
                        edit.style.cssText = 'position:absolute;bottom:100%;right:20px;margin-bottom:3px;color:#fff;background:#4f46e5;border-radius:50%;width:18px;height:18px;text-align:center;line-height:18px;font-size:10px;cursor:pointer;z-index:6;box-shadow:0 1px 3px rgba(0,0,0,.3);';
                        edit.addEventListener('mousedown', ev => ev.stopPropagation());
                        edit.addEventListener('click', ev => { ev.stopPropagation(); editText(i); });
                        d.appendChild(edit);
                    }
                    const del = document.createElement('span');
                    del.textContent = '×';
                    del.title = 'Remove field';
                    del.style.cssText = 'position:absolute;bottom:100%;right:0;margin-bottom:3px;color:#fff;background:#e3342f;border-radius:50%;width:18px;height:18px;text-align:center;line-height:17px;font-size:12px;cursor:pointer;z-index:6;box-shadow:0 1px 3px rgba(0,0,0,.3);';
                    del.addEventListener('mousedown', ev => ev.stopPropagation());
                    del.addEventListener('click', ev => { ev.stopPropagation(); removeAt(i); });
                    d.appendChild(del);
                }
                d.addEventListener('mousedown', e => startDrag(e, i));
                d.dataset.colEl = '1';
                canvas.appendChild(d);
            });

            // Ghost copies for the remaining columns — read-only preview of multi-up output.
            if (cols > 1) {
                const baseNodes = Array.from(canvas.querySelectorAll('.lf-el[data-col-el="1"]'));
                for (let c = 1; c < cols; c++) {
                    const offset = c * (W + gap) * SCALE;
                    baseNodes.forEach(node => {
                        const clone = node.cloneNode(true);
                        clone.classList.remove('sel');
                        clone.style.pointerEvents = 'none';
                        clone.style.opacity = '0.55';
                        clone.style.borderColor = 'transparent';
                        clone.style.left = (parseFloat(node.style.left || 0) + offset) + 'px';
                        clone.querySelectorAll('span[title="Edit text"], span[title="Remove field"]').forEach(b => b.remove());
                        canvas.appendChild(clone);
                    });
                }
            }
        }

        function removeAt(i) {
            els.splice(i, 1);
            selIdx = -1;
            document.getElementById('lf-props').style.display = 'none';
            render();
        }

        function editText(i) {
            const el = els[i]; if (!el) return;
            const freeText = ['custom_text', 'store_name', 'subtitle', 'item_name'].includes(el.type);
            const msg = freeText
                ? (el.type === 'store_name' ? 'Store name (leave blank to use your store name automatically):'
                    : 'Text to show on the label:')
                : 'Label shown before the value:';
            const v = prompt(msg, el.text || '');
            if (v !== null) { el.text = v; selIdx = i; openProps(); render(); }
        }

        let drag = null;
        function startDrag(e, i) {
            e.preventDefault();
            selIdx = i; openProps(); render();
            const el = els[i];
            const startX = e.clientX, startY = e.clientY, ox = el.x, oy = el.y;
            drag = { move(ev) {
                const W = parseFloat(document.getElementById('fmt-w').value) || 50;
                const H = parseFloat(document.getElementById('fmt-h').value) || 25;
                el.x = Math.max(0, Math.min(W, ox + (ev.clientX - startX) / SCALE));
                el.y = Math.max(0, Math.min(H, oy + (ev.clientY - startY) / SCALE));
                el.x = Math.round(el.x * 10) / 10; el.y = Math.round(el.y * 10) / 10;
                render();
            }};
            document.addEventListener('mousemove', drag.move);
            document.addEventListener('mouseup', stopDrag);
        }
        function stopDrag() { if (drag) document.removeEventListener('mousemove', drag.move); document.removeEventListener('mouseup', stopDrag); drag = null; }

        function openProps() {
            const el = els[selIdx]; if (!el) return;
            document.getElementById('lf-props').style.display = '';
            document.getElementById('prop-text').value = el.text || '';
            document.getElementById('prop-font').value = el.font || 8;
            document.getElementById('prop-bold').checked = !!el.bold;
            const isBc = el.type === 'barcode';
            document.getElementById('prop-bc').style.display = isBc ? '' : 'none';
            document.getElementById('prop-text-lbl').style.display = isBc ? 'none' : '';
            document.getElementById('prop-text').style.display = isBc ? 'none' : '';
            // The text box is the full content for free-text fields, or a prefix label for value fields.
            const freeText = ['custom_text', 'store_name', 'subtitle', 'item_name'].includes(el.type);
            document.getElementById('prop-text-lbl').textContent = freeText ? 'Text' : 'Prefix label (before the value)';
            document.getElementById('prop-text').placeholder = freeText ? 'Type your text…' : 'e.g. M.R.P';
            if (isBc) { document.getElementById('prop-bw').value = el.w || 30; document.getElementById('prop-bh').value = el.h || 10; }
            const showNewline = ['mrp', 'selling_price', 'packing_date', 'expiry_date', 'code', 'item_name'].includes(el.type);
            document.getElementById('prop-newline-container').style.display = showNewline ? '' : 'none';
            document.getElementById('prop-newline').checked = !!el.newline;
            document.getElementById('prop-align').value = el.align || 'center';
        }
        function updProp() {
            const el = els[selIdx]; if (!el) return;
            el.text = document.getElementById('prop-text').value;
            el.font = parseFloat(document.getElementById('prop-font').value) || 8;
            el.bold = document.getElementById('prop-bold').checked;
            el.align = document.getElementById('prop-align').value || 'center';
            if (el.type === 'barcode') { el.w = parseFloat(document.getElementById('prop-bw').value) || 30; el.h = parseFloat(document.getElementById('prop-bh').value) || 10; }
            if (['mrp', 'selling_price', 'packing_date', 'expiry_date', 'code', 'item_name'].includes(el.type)) {
                el.newline = document.getElementById('prop-newline').checked;
            }
            render();
        }
        function removeEl() { if (selIdx < 0) return; els.splice(selIdx, 1); selIdx = -1; document.getElementById('lf-props').style.display = 'none'; render(); }

        function saveFormat() {
            if (!document.getElementById('fmt-name').value.trim()) { alert('Enter a format name'); return; }
            document.getElementById('f-id').value = editId || '';
            document.getElementById('f-name').value = document.getElementById('fmt-name').value;
            document.getElementById('f-w').value = document.getElementById('fmt-w').value;
            document.getElementById('f-h').value = document.getElementById('fmt-h').value;
            document.getElementById('f-cols').value = document.getElementById('fmt-cols').value || 1;
            document.getElementById('f-gap').value = document.getElementById('fmt-gap').value || 0;
            document.getElementById('f-default').value = document.getElementById('fmt-default').checked ? 1 : 0;
            document.getElementById('f-elements').value = JSON.stringify(els);
            document.getElementById('lf-save-form').submit();
        }

        newFormat();
    </script>
@endsection
