{{--
    Reusable rich text editor (vanilla JS, no dependencies).
    Usage:
      @include('admin-views.partials._rich_editor', [
          'name'      => 'about_us[]',           // form field name (required)
          'value'     => $html ?? '',            // initial HTML
          'id'        => 'aboutus',              // unique id per instance
          'uploadUrl' => route('testing.upload-image'),
      ])
    Binds to the enclosing <form>: syncs editor HTML into a hidden <textarea name="{name}"> on input/submit.
--}}
@php
    $rid       = $id ?? 'rte';
    $rname     = $name ?? 'content';
    $rvalue    = $value ?? '';
    $ruploadUrl = $uploadUrl ?? (\Illuminate\Support\Facades\Route::has('testing.upload-image') ? route('testing.upload-image') : '');
@endphp

@once
    @push('css_or_js')
        <style>
            .rte { --rte-accent:#0661cb; background:#fff; border:1px solid #e2e8f0; border-radius:10px; }
            .rte-toolbar { position:sticky; top:3.75rem; z-index:20; display:flex; flex-wrap:wrap; gap:4px; align-items:center; padding:8px 12px; background:#fff; border-bottom:1px solid #e2e8f0; border-radius:9px 9px 0 0; }
            .rte-toolbar .grp { display:flex; gap:2px; padding:0 6px; border-right:1px solid #eef2f6; }
            .rte-toolbar .grp:last-child { border-right:none; }
            .rte .tb-btn { min-width:32px; height:32px; padding:0 8px; border:none; background:transparent; border-radius:6px; cursor:pointer; font-size:15px; color:#334155; display:inline-flex; align-items:center; justify-content:center; }
            .rte .tb-btn:hover { background:#f1f5f9; }
            .rte .tb-btn.active { background:#e0e7ff; color:var(--rte-accent); }
            .rte .tb-btn b { font-weight:800; }
            .rte .tb-select { height:32px; border:1px solid #e2e8f0; border-radius:6px; padding:0 6px; font-size:13px; color:#334155; background:#fff; cursor:pointer; }
            .rte-content { min-height:340px; padding:18px 20px; outline:none; line-height:1.7; font-size:15px; color:#1f2937; border-radius:0 0 10px 10px; }
            .rte-content:empty::before { content:attr(data-placeholder); color:#94a3b8; }
            .rte-content::after { content:''; display:block; clear:both; }
            .rte-content h1 { font-size:1.8rem; margin:.6em 0 .4em; }
            .rte-content h2 { font-size:1.45rem; margin:.6em 0 .4em; }
            .rte-content h3 { font-size:1.2rem; margin:.6em 0 .4em; }
            .rte-content p { margin:0 0 .8em; }
            .rte-content ul, .rte-content ol { padding-left:1.6em; margin:0 0 .8em; }
            .rte-content blockquote { border-left:3px solid var(--rte-accent); margin:.8em 0; padding:6px 16px; background:#f5f8ff; color:#1e40af; border-radius:0 6px 6px 0; }
            .rte-content pre { background:#0f172a; color:#a5f3fc; padding:14px 16px; border-radius:8px; overflow:auto; font-family:ui-monospace,Consolas,monospace; font-size:14px; }
            .rte-content a { color:var(--rte-accent); }
            .rte-content img { max-width:100%; height:auto; border-radius:6px; }
            .rte-content img.rte-img-selected { outline:2px solid var(--rte-accent); outline-offset:2px; }
            .rte-content hr { border:none; border-top:1px solid #e2e8f0; margin:1.2em 0; }
            .rte-content table { border-collapse:collapse; width:100%; }
            .rte-content td, .rte-content th { border:1px solid #ddd; padding:8px; }
            .rte-img-bar { position:fixed; z-index:1090; display:none; gap:2px; background:#1e293b; border-radius:8px; padding:4px; box-shadow:0 6px 18px rgba(0,0,0,.25); }
            .rte-img-bar.show { display:flex; }
            .rte-img-bar button { border:none; background:transparent; color:#e2e8f0; font-size:12px; font-weight:600; padding:5px 8px; border-radius:5px; cursor:pointer; line-height:1; }
            .rte-img-bar button:hover { background:#334155; }
            .rte-img-bar button.active { background:#0661cb; }
            .rte-img-bar .sep { width:1px; background:#475569; margin:2px; }
            .rte-img-handle { position:fixed; z-index:1091; width:14px; height:14px; display:none; background:#0661cb; border:2px solid #fff; border-radius:50%; box-shadow:0 1px 4px rgba(0,0,0,.35); }
            .rte-img-handle.show { display:block; }
            .rte-img-handle[data-corner=nw], .rte-img-handle[data-corner=se] { cursor:nwse-resize; }
            .rte-img-handle[data-corner=ne], .rte-img-handle[data-corner=sw] { cursor:nesw-resize; }
        </style>
    @endpush

    @push('script_2')
        <script>
            window.initMCRichEditor = function (opts) {
                const id = opts.id, uploadUrl = opts.uploadUrl;
                const content = document.getElementById('rte-content-' + id);
                const toolbar = document.getElementById('rte-toolbar-' + id);
                const hidden  = document.getElementById('rte-input-' + id);
                if (!content || !toolbar || !hidden) return;
                const form = hidden.closest('form');
                const csrf = () => (document.querySelector('meta[name=csrf-token]') || {}).content
                    || (document.querySelector('input[name=_token]') || {}).value || '';

                try { document.execCommand('styleWithCSS', false, false); } catch (e) {}
                try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}

                function sync() { hidden.value = content.innerHTML; }
                function exec(cmd, value = null) { content.focus(); document.execCommand(cmd, false, value); refreshActive(); sync(); }

                let savedRange = null;
                function saveRange() { const s = window.getSelection(); if (s && s.rangeCount && content.contains(s.anchorNode)) savedRange = s.getRangeAt(0); }
                function restoreRange() { content.focus(); if (savedRange) { const s = window.getSelection(); s.removeAllRanges(); s.addRange(savedRange); } }

                toolbar.addEventListener('mousedown', e => {
                    if (e.target.closest('.tb-btn')) { e.preventDefault(); return; }
                    if (e.target.closest('.tb-select')) { saveRange(); }
                });
                toolbar.addEventListener('click', e => {
                    const btn = e.target.closest('.tb-btn'); if (!btn) return;
                    if (btn.dataset.cmd) exec(btn.dataset.cmd);
                    else if (btn.dataset.block) exec('formatBlock', btn.dataset.block);
                    else if (btn.dataset.role === 'link') { const u = prompt('Link URL:', 'https://'); if (u) exec('createLink', u); }
                    else if (btn.dataset.role === 'image') { const u = prompt('Image URL:'); if (u) exec('insertImage', u); }
                    else if (btn.dataset.role === 'upload-image') { saveRange(); fileInput.click(); }
                });
                const formatSel = toolbar.querySelector('[data-role=format]');
                if (formatSel) formatSel.addEventListener('change', function () { restoreRange(); exec('formatBlock', this.value); });

                function refreshActive() {
                    ['bold','italic','underline','strikeThrough','insertUnorderedList','insertOrderedList'].forEach(cmd => {
                        const b = toolbar.querySelector('[data-cmd="' + cmd + '"]'); if (!b) return;
                        let on = false; try { on = document.queryCommandState(cmd); } catch (e) {}
                        b.classList.toggle('active', on);
                    });
                }
                document.addEventListener('selectionchange', () => { if (document.activeElement === content) refreshActive(); });
                content.addEventListener('input', sync);
                content.addEventListener('paste', e => {
                    e.preventDefault();
                    const t = (e.clipboardData || window.clipboardData).getData('text/plain');
                    document.execCommand('insertText', false, t); sync();
                });

                // hidden file input for upload
                const fileInput = document.createElement('input');
                fileInput.type = 'file'; fileInput.accept = 'image/*'; fileInput.style.display = 'none';
                document.body.appendChild(fileInput);
                fileInput.addEventListener('change', async function () {
                    const file = this.files && this.files[0]; this.value = ''; if (!file || !uploadUrl) return;
                    const fd = new FormData(); fd.append('image', file);
                    try {
                        const res = await fetch(uploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }, body: fd });
                        const data = await res.json();
                        if (!res.ok || !data.url) throw new Error('upload failed');
                        restoreRange(); document.execCommand('insertImage', false, data.url); sync();
                    } catch (err) { alert('Image upload failed'); console.error(err); }
                });

                // floating image bar + resize handle
                const bar = document.createElement('div'); bar.className = 'rte-img-bar';
                bar.innerHTML = '<button type="button" data-size="25%">25%</button><button type="button" data-size="50%">50%</button><button type="button" data-size="100%">100%</button><button type="button" data-size="">Auto</button><span class="sep"></span><button type="button" data-wrap="left">⬅ Wrap</button><button type="button" data-wrap="none">Center</button><button type="button" data-wrap="right">Wrap ➡</button><button type="button" data-wrap="inline">Inline</button><span class="sep"></span><button type="button" data-del="1">🗑</button>';
                document.body.appendChild(bar);
                const CORNERS = ['nw', 'ne', 'sw', 'se'];
                const handles = {};
                CORNERS.forEach(c => { const h = document.createElement('div'); h.className = 'rte-img-handle'; h.dataset.corner = c; document.body.appendChild(h); handles[c] = h; });
                let selectedImg = null;
                function positionImgUI() {
                    if (!selectedImg) return;
                    const r = selectedImg.getBoundingClientRect();
                    bar.style.top = Math.max(8, r.top - 44) + 'px';
                    bar.style.left = r.left + 'px';
                    handles.nw.style.top = (r.top - 7) + 'px';    handles.nw.style.left = (r.left - 7) + 'px';
                    handles.ne.style.top = (r.top - 7) + 'px';    handles.ne.style.left = (r.right - 7) + 'px';
                    handles.sw.style.top = (r.bottom - 7) + 'px'; handles.sw.style.left = (r.left - 7) + 'px';
                    handles.se.style.top = (r.bottom - 7) + 'px'; handles.se.style.left = (r.right - 7) + 'px';
                }
                function markWrap() { const f = selectedImg ? (selectedImg.style.cssFloat || selectedImg.style.float) : ''; const d = selectedImg ? selectedImg.style.display : ''; let c = 'inline'; if (f === 'left') c = 'left'; else if (f === 'right') c = 'right'; else if (d === 'block') c = 'none'; bar.querySelectorAll('[data-wrap]').forEach(b => b.classList.toggle('active', b.dataset.wrap === c)); }
                function showImgUI(img) { if (selectedImg) selectedImg.classList.remove('rte-img-selected'); selectedImg = img; img.classList.add('rte-img-selected'); positionImgUI(); markWrap(); bar.classList.add('show'); CORNERS.forEach(c => handles[c].classList.add('show')); }
                function hideImgUI() { if (selectedImg) selectedImg.classList.remove('rte-img-selected'); selectedImg = null; bar.classList.remove('show'); CORNERS.forEach(c => handles[c].classList.remove('show')); }
                content.addEventListener('click', e => { if (e.target.tagName === 'IMG') showImgUI(e.target); else hideImgUI(); });
                document.addEventListener('mousedown', e => {
                    if (e.target.closest('.rte-img-bar') || e.target.classList.contains('rte-img-handle')) return;
                    if (e.target.tagName === 'IMG' && content.contains(e.target)) return;
                    if (!content.contains(e.target)) hideImgUI();
                });
                bar.addEventListener('mousedown', e => e.preventDefault());
                bar.addEventListener('click', e => {
                    const btn = e.target.closest('button'); if (!btn || !selectedImg) return;
                    if (btn.hasAttribute('data-size')) { selectedImg.style.width = btn.dataset.size; selectedImg.style.height = 'auto'; }
                    else if (btn.dataset.wrap) {
                        const w = btn.dataset.wrap;
                        selectedImg.style.float = ''; selectedImg.style.display = ''; selectedImg.style.margin = '';
                        if (w === 'left') { selectedImg.style.float = 'left'; selectedImg.style.margin = '4px 16px 8px 0'; }
                        else if (w === 'right') { selectedImg.style.float = 'right'; selectedImg.style.margin = '4px 0 8px 16px'; }
                        else if (w === 'none') { selectedImg.style.display = 'block'; selectedImg.style.margin = '12px auto'; }
                        else { selectedImg.style.display = 'inline'; }
                        markWrap();
                    } else if (btn.dataset.del) { selectedImg.remove(); hideImgUI(); sync(); return; }
                    positionImgUI(); sync();
                });
                let resizing = false, sx = 0, sw = 0, dir = 1;
                CORNERS.forEach(c => {
                    handles[c].addEventListener('mousedown', e => {
                        if (!selectedImg) return;
                        e.preventDefault(); e.stopPropagation();
                        resizing = true; sx = e.clientX; sw = selectedImg.getBoundingClientRect().width;
                        dir = (c === 'ne' || c === 'se') ? 1 : -1;   // right corners grow rightward, left corners grow leftward
                        document.body.style.userSelect = 'none';
                    });
                });
                window.addEventListener('mousemove', e => { if (!resizing || !selectedImg) return; const w = Math.max(40, Math.round(sw + dir * (e.clientX - sx))); selectedImg.style.width = w + 'px'; selectedImg.style.height = 'auto'; positionImgUI(); });
                window.addEventListener('mouseup', () => { if (resizing) { resizing = false; document.body.style.userSelect = ''; sync(); } });
                window.addEventListener('scroll', positionImgUI, true);
                window.addEventListener('resize', positionImgUI);

                if (form) form.addEventListener('submit', () => { content.querySelectorAll('img.rte-img-selected').forEach(i => i.classList.remove('rte-img-selected')); sync(); });
                sync();
            };
        </script>
    @endpush
@endonce

<div class="rte">
    <div class="rte-toolbar" id="rte-toolbar-{{ $rid }}">
        <div class="grp">
            <select class="tb-select" data-role="format" title="Paragraph format">
                <option value="P">Paragraph</option>
                <option value="H1">Heading 1</option>
                <option value="H2">Heading 2</option>
                <option value="H3">Heading 3</option>
                <option value="PRE">Code block</option>
            </select>
        </div>
        <div class="grp">
            <button type="button" class="tb-btn" data-cmd="bold" title="Bold"><b>B</b></button>
            <button type="button" class="tb-btn" data-cmd="italic" title="Italic"><i>I</i></button>
            <button type="button" class="tb-btn" data-cmd="underline" title="Underline"><u>U</u></button>
            <button type="button" class="tb-btn" data-cmd="strikeThrough" title="Strikethrough"><s>S</s></button>
        </div>
        <div class="grp">
            <button type="button" class="tb-btn" data-cmd="insertUnorderedList" title="Bullet list">• ⃪</button>
            <button type="button" class="tb-btn" data-cmd="insertOrderedList" title="Numbered list">1.</button>
            <button type="button" class="tb-btn" data-block="BLOCKQUOTE" title="Quote">❝</button>
        </div>
        <div class="grp">
            <button type="button" class="tb-btn" data-cmd="justifyLeft" title="Align left">⫷</button>
            <button type="button" class="tb-btn" data-cmd="justifyCenter" title="Align center">≡</button>
            <button type="button" class="tb-btn" data-cmd="justifyRight" title="Align right">⫸</button>
        </div>
        <div class="grp">
            <button type="button" class="tb-btn" data-role="link" title="Insert link">🔗</button>
            <button type="button" class="tb-btn" data-role="image" title="Insert image (URL)">🖼</button>
            <button type="button" class="tb-btn" data-role="upload-image" title="Upload image">📤</button>
            <button type="button" class="tb-btn" data-cmd="insertHorizontalRule" title="Divider">―</button>
        </div>
        <div class="grp">
            <button type="button" class="tb-btn" data-cmd="removeFormat" title="Clear formatting">⌫</button>
            <button type="button" class="tb-btn" data-cmd="undo" title="Undo">↶</button>
            <button type="button" class="tb-btn" data-cmd="redo" title="Redo">↷</button>
        </div>
    </div>

    <div class="rte-content" id="rte-content-{{ $rid }}" contenteditable="true"
         data-placeholder="Start writing…">{!! $rvalue !!}</div>
</div>

<textarea name="{{ $rname }}" id="rte-input-{{ $rid }}" style="display:none">{!! $rvalue !!}</textarea>

@push('script_2')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.initMCRichEditor({ id: '{{ $rid }}', uploadUrl: '{{ $ruploadUrl }}' });
        });
    </script>
@endpush
