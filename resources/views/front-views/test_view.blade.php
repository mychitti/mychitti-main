{{-- My Chitti — lightweight rich text editor (vanilla JS, no dependencies) --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Chitti Editor</title>
    <style>
        :root { --rte-border:#e2e8f0; --rte-accent:#4f46e5; --rte-bg:#f8fafc; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--rte-bg);
            color: #1e293b;
        }
        .rte-wrap { max-width: 900px; margin: 24px auto; padding: 0 16px; }

        /* Top bar */
        .rte-top {
            display: flex; align-items: center; gap: 12px; margin-bottom: 14px;
        }
        .rte-top h1 { font-size: 18px; margin: 0; }
        .rte-top .spacer { flex: 1; }
        .rte-status { font-size: 13px; color: #64748b; }
        .btn {
            border: 1px solid transparent; border-radius: 8px; padding: 9px 16px;
            font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-save { background: #059669; color: #fff; }
        .btn-save:hover { background: #047857; }
        .btn-view { background: #eef2ff; color: var(--rte-accent); border-color: #c7d2fe; }

        /* Editor shell */
        .rte {
            background: #fff; border: 1px solid var(--rte-border); border-radius: 12px;
            overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,.04);
        }
        .rte-title {
            width: 100%; border: none; outline: none; padding: 18px 22px 6px;
            font-size: 26px; font-weight: 700; color: #0f172a;
        }
        .rte-title::placeholder { color: #cbd5e1; }

        /* Toolbar */
        .rte-toolbar {
            position: sticky; top: 0; z-index: 5;
            display: flex; flex-wrap: wrap; gap: 4px; align-items: center;
            padding: 8px 14px; background: #fff;
            border-top: 1px solid var(--rte-border); border-bottom: 1px solid var(--rte-border);
        }
        .rte-toolbar .grp { display: flex; gap: 2px; padding: 0 6px; border-right: 1px solid #eef2f6; }
        .rte-toolbar .grp:last-child { border-right: none; }
        .tb-btn {
            min-width: 32px; height: 32px; padding: 0 8px; border: none; background: transparent;
            border-radius: 6px; cursor: pointer; font-size: 15px; color: #334155;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .tb-btn:hover { background: #f1f5f9; }
        .tb-btn.active { background: #e0e7ff; color: var(--rte-accent); }
        .tb-btn b { font-weight: 800; }
        .tb-select {
            height: 32px; border: 1px solid var(--rte-border); border-radius: 6px;
            padding: 0 6px; font-size: 13px; color: #334155; background: #fff; cursor: pointer;
        }

        /* Editable area */
        .rte-content {
            min-height: 420px; padding: 22px 24px; outline: none; line-height: 1.7;
            font-size: 16px; color: #1f2937;
        }
        .rte-content:empty::before { content: attr(data-placeholder); color: #94a3b8; }
        .rte-content::after { content: ''; display: block; clear: both; }
        .rte-content h1 { font-size: 1.8rem; margin: .6em 0 .4em; }
        .rte-content h2 { font-size: 1.45rem; margin: .6em 0 .4em; }
        .rte-content h3 { font-size: 1.2rem; margin: .6em 0 .4em; }
        .rte-content p { margin: 0 0 .8em; }
        .rte-content ul, .rte-content ol { padding-left: 1.6em; margin: 0 0 .8em; }
        .rte-content blockquote {
            border-left: 3px solid var(--rte-accent); margin: .8em 0; padding: 6px 16px;
            background: #f5f3ff; color: #4338ca; border-radius: 0 6px 6px 0;
        }
        .rte-content pre {
            background: #0f172a; color: #a5f3fc; padding: 14px 16px; border-radius: 8px;
            overflow: auto; font-family: ui-monospace, Consolas, monospace; font-size: 14px;
        }
        .rte-content a { color: var(--rte-accent); }
        .rte-content img { max-width: 100%; height: auto; border-radius: 6px; }
        .rte-content img.rte-img-selected { outline: 2px solid var(--rte-accent); outline-offset: 2px; }

        /* Floating image toolbar */
        .rte-img-bar {
            position: fixed; z-index: 50; display: none; gap: 2px;
            background: #1e293b; border-radius: 8px; padding: 4px;
            box-shadow: 0 6px 18px rgba(0,0,0,.25);
        }
        .rte-img-bar.show { display: flex; }
        .rte-img-bar button {
            border: none; background: transparent; color: #e2e8f0; font-size: 12px;
            font-weight: 600; padding: 5px 8px; border-radius: 5px; cursor: pointer; line-height: 1;
        }
        .rte-img-bar button:hover { background: #334155; }
        .rte-img-bar button.active { background: var(--rte-accent); }
        .rte-img-bar .sep { width: 1px; background: #475569; margin: 2px; }

        /* Drag-to-resize handle (bottom-right of selected image) */
        .rte-img-handle {
            position: fixed; z-index: 51; width: 14px; height: 14px; display: none;
            background: var(--rte-accent); border: 2px solid #fff; border-radius: 50%;
            cursor: nwse-resize; box-shadow: 0 1px 4px rgba(0,0,0,.35);
        }
        .rte-img-handle.show { display: block; }
        .rte-content hr { border: none; border-top: 1px solid var(--rte-border); margin: 1.2em 0; }
        .rte-content table { border-collapse: collapse; width: 100%; }
        .rte-content td, .rte-content th { border: 1px solid #ddd; padding: 8px; }
    </style>
</head>
<body>
    <div class="rte-wrap">
        <div class="rte-top">
            <h1>My Chitti Editor</h1>
            <span class="spacer"></span>
            <span class="rte-status" id="rte-status"></span>
            <a class="btn btn-view" href="{{ route('testing.preview') }}" target="_blank">👁 View on frontend</a>
            <button type="button" class="btn btn-save" id="rte-save">💾 Save</button>
        </div>

        <div class="rte">
            <input type="text" class="rte-title" id="rte-title" placeholder="Untitled document"
                   value="{{ $savedTitle }}">

            <div class="rte-toolbar" id="rte-toolbar">
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
                    <button class="tb-btn" data-cmd="bold" title="Bold (Ctrl+B)"><b>B</b></button>
                    <button class="tb-btn" data-cmd="italic" title="Italic (Ctrl+I)"><i>I</i></button>
                    <button class="tb-btn" data-cmd="underline" title="Underline (Ctrl+U)"><u>U</u></button>
                    <button class="tb-btn" data-cmd="strikeThrough" title="Strikethrough"><s>S</s></button>
                </div>
                <div class="grp">
                    <button class="tb-btn" data-cmd="insertUnorderedList" title="Bullet list">• ⃪</button>
                    <button class="tb-btn" data-cmd="insertOrderedList" title="Numbered list">1.</button>
                    <button class="tb-btn" data-block="BLOCKQUOTE" title="Quote">❝</button>
                </div>
                <div class="grp">
                    <button class="tb-btn" data-cmd="justifyLeft" title="Align left">⫷</button>
                    <button class="tb-btn" data-cmd="justifyCenter" title="Align center">≡</button>
                    <button class="tb-btn" data-cmd="justifyRight" title="Align right">⫸</button>
                </div>
                <div class="grp">
                    <button class="tb-btn" data-role="link" title="Insert link">🔗</button>
                    <button class="tb-btn" data-role="image" title="Insert image (URL)">🖼</button>
                    <button class="tb-btn" data-role="upload-image" title="Upload image">📤</button>
                    <button class="tb-btn" data-cmd="insertHorizontalRule" title="Divider">―</button>
                </div>
                <div class="grp">
                    <button class="tb-btn" data-cmd="removeFormat" title="Clear formatting">⌫</button>
                    <button class="tb-btn" data-cmd="undo" title="Undo">↶</button>
                    <button class="tb-btn" data-cmd="redo" title="Redo">↷</button>
                </div>
            </div>

            <div class="rte-content" id="rte-content" contenteditable="true"
                 data-placeholder="Start writing…">{!! $savedContent !!}</div>
        </div>

        <input type="file" id="rte-image-input" accept="image/*" style="display:none">
    </div>

    <div class="rte-img-bar" id="rte-img-bar">
        <button data-size="25%" title="25%">25%</button>
        <button data-size="50%" title="50%">50%</button>
        <button data-size="100%" title="Full width">100%</button>
        <button data-size="" title="Original size">Auto</button>
        <span class="sep"></span>
        <button data-wrap="left"   title="Float left — text wraps on the right">⬅ Wrap</button>
        <button data-wrap="none"   title="Center, no wrap">Center</button>
        <button data-wrap="right"  title="Float right — text wraps on the left">Wrap ➡</button>
        <button data-wrap="inline" title="Inline with text">Inline</button>
        <span class="sep"></span>
        <button data-del="1" title="Delete image">🗑</button>
    </div>

    <div class="rte-img-handle" id="rte-img-handle"></div>

    <script>
        (function () {
            const content = document.getElementById('rte-content');
            const toolbar = document.getElementById('rte-toolbar');
            const status  = document.getElementById('rte-status');

            // execCommand styles with semantic tags where possible
            try { document.execCommand('styleWithCSS', false, false); } catch (e) {}
            try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}

            function exec(cmd, value = null) {
                content.focus();
                document.execCommand(cmd, false, value);
                refreshActive();
            }

            // Keep the selection when clicking toolbar buttons.
            // NOTE: do NOT preventDefault on the <select> — that blocks it from opening.
            toolbar.addEventListener('mousedown', e => {
                if (e.target.closest('.tb-btn')) { e.preventDefault(); return; }
                if (e.target.closest('.tb-select')) { saveRange(); }   // remember selection, let it open
            });

            toolbar.addEventListener('click', e => {
                const btn = e.target.closest('.tb-btn');
                if (!btn) return;
                if (btn.dataset.cmd) {
                    exec(btn.dataset.cmd);
                } else if (btn.dataset.block) {
                    exec('formatBlock', btn.dataset.block);
                } else if (btn.dataset.role === 'link') {
                    const url = prompt('Link URL:', 'https://');
                    if (url) exec('createLink', url);
                } else if (btn.dataset.role === 'image') {
                    const url = prompt('Image URL:');
                    if (url) exec('insertImage', url);
                } else if (btn.dataset.role === 'upload-image') {
                    saveRange();
                    document.getElementById('rte-image-input').click();
                }
            });

            // ── Image upload (file → Laravel → insert) ───────────
            let savedRange = null;
            function saveRange() {
                const s = window.getSelection();
                if (s && s.rangeCount && content.contains(s.anchorNode)) savedRange = s.getRangeAt(0);
            }
            function restoreRange() {
                content.focus();
                if (savedRange) {
                    const s = window.getSelection();
                    s.removeAllRanges();
                    s.addRange(savedRange);
                }
            }
            document.getElementById('rte-image-input').addEventListener('change', async function () {
                const file = this.files && this.files[0];
                this.value = '';
                if (!file) return;
                status.textContent = 'Uploading image…';
                const fd = new FormData();
                fd.append('image', file);
                try {
                    const res = await fetch("{{ route('testing.upload-image') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        },
                        body: fd
                    });
                    const data = await res.json();
                    if (!res.ok || !data.url) throw new Error(data.message || 'Upload failed');
                    restoreRange();
                    document.execCommand('insertImage', false, data.url);
                    status.textContent = '✓ Image inserted';
                } catch (err) {
                    status.textContent = '⚠ Image upload failed';
                    console.error(err);
                }
            });

            // Paragraph format dropdown — restore the saved selection, then apply
            toolbar.querySelector('[data-role=format]').addEventListener('change', function () {
                restoreRange();
                exec('formatBlock', this.value);
            });

            // Highlight active inline buttons
            function refreshActive() {
                ['bold', 'italic', 'underline', 'strikeThrough',
                 'insertUnorderedList', 'insertOrderedList'].forEach(cmd => {
                    const btn = toolbar.querySelector(`[data-cmd="${cmd}"]`);
                    if (!btn) return;
                    let on = false;
                    try { on = document.queryCommandState(cmd); } catch (e) {}
                    btn.classList.toggle('active', on);
                });
            }
            document.addEventListener('selectionchange', () => {
                if (document.activeElement === content) refreshActive();
            });

            // ── Image resize / wrap (click an image) ─────────────
            const imgBar    = document.getElementById('rte-img-bar');
            const imgHandle = document.getElementById('rte-img-handle');
            let selectedImg = null;

            function positionImgUI() {
                if (!selectedImg) return;
                const r = selectedImg.getBoundingClientRect();
                imgBar.style.top  = Math.max(8, r.top - 44) + 'px';
                imgBar.style.left = r.left + 'px';
                imgHandle.style.top  = (r.bottom - 7) + 'px';
                imgHandle.style.left = (r.right - 7) + 'px';
            }
            function markActiveWrap() {
                const f = selectedImg ? selectedImg.style.cssFloat || selectedImg.style.float : '';
                const disp = selectedImg ? selectedImg.style.display : '';
                let cur = 'inline';
                if (f === 'left') cur = 'left';
                else if (f === 'right') cur = 'right';
                else if (disp === 'block') cur = 'none';
                imgBar.querySelectorAll('[data-wrap]').forEach(b =>
                    b.classList.toggle('active', b.dataset.wrap === cur));
            }
            function showImgUI(img) {
                document.querySelectorAll('.rte-content img.rte-img-selected')
                    .forEach(i => i.classList.remove('rte-img-selected'));
                selectedImg = img;
                img.classList.add('rte-img-selected');
                positionImgUI();
                markActiveWrap();
                imgBar.classList.add('show');
                imgHandle.classList.add('show');
            }
            function hideImgUI() {
                if (selectedImg) selectedImg.classList.remove('rte-img-selected');
                selectedImg = null;
                imgBar.classList.remove('show');
                imgHandle.classList.remove('show');
            }

            content.addEventListener('click', e => {
                if (e.target.tagName === 'IMG') showImgUI(e.target);
                else hideImgUI();
            });
            document.addEventListener('mousedown', e => {
                if (e.target.closest('#rte-img-bar') || e.target.closest('#rte-img-handle')) return;
                if (e.target.tagName === 'IMG' && content.contains(e.target)) return;
                hideImgUI();
            });

            imgBar.addEventListener('mousedown', e => e.preventDefault());
            imgBar.addEventListener('click', e => {
                const btn = e.target.closest('button');
                if (!btn || !selectedImg) return;
                if (btn.hasAttribute('data-size')) {
                    selectedImg.style.width = btn.dataset.size;   // '' = original
                    selectedImg.style.height = 'auto';
                } else if (btn.dataset.wrap) {
                    const w = btn.dataset.wrap;
                    // reset
                    selectedImg.style.float = '';
                    selectedImg.style.display = '';
                    selectedImg.style.margin = '';
                    if (w === 'left') {
                        selectedImg.style.float = 'left';
                        selectedImg.style.margin = '4px 16px 8px 0';
                    } else if (w === 'right') {
                        selectedImg.style.float = 'right';
                        selectedImg.style.margin = '4px 0 8px 16px';
                    } else if (w === 'none') {
                        selectedImg.style.display = 'block';
                        selectedImg.style.margin = '12px auto';
                    } else { // inline
                        selectedImg.style.display = 'inline';
                    }
                    markActiveWrap();
                } else if (btn.dataset.del) {
                    selectedImg.remove();
                    hideImgUI();
                    return;
                }
                positionImgUI();
            });

            // Drag the corner handle to stretch the image
            let resizing = false, startX = 0, startW = 0;
            imgHandle.addEventListener('mousedown', e => {
                if (!selectedImg) return;
                e.preventDefault(); e.stopPropagation();
                resizing = true;
                startX = e.clientX;
                startW = selectedImg.getBoundingClientRect().width;
                document.body.style.userSelect = 'none';
            });
            window.addEventListener('mousemove', e => {
                if (!resizing || !selectedImg) return;
                const w = Math.max(40, Math.round(startW + (e.clientX - startX)));
                selectedImg.style.width = w + 'px';
                selectedImg.style.height = 'auto';
                positionImgUI();
            });
            window.addEventListener('mouseup', () => {
                if (resizing) { resizing = false; document.body.style.userSelect = ''; }
            });

            window.addEventListener('scroll', positionImgUI, true);
            window.addEventListener('resize', positionImgUI);

            // Paste as plain text (avoids messy markup from Word/Docs)
            content.addEventListener('paste', e => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text/plain');
                document.execCommand('insertText', false, text);
            });

            // ── Save to Laravel ──────────────────────────────────
            async function save() {
                status.textContent = 'Saving…';
                // Drop the transient selection outline so it isn't saved
                content.querySelectorAll('img.rte-img-selected')
                    .forEach(i => i.classList.remove('rte-img-selected'));
                const body = new URLSearchParams();
                body.set('title', document.getElementById('rte-title').value.trim());
                body.set('content', content.innerHTML);
                try {
                    const res = await fetch("{{ route('testing.save') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept': 'application/json'
                        },
                        body: body.toString()
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const t = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    status.textContent = '✓ Saved ' + t;
                } catch (err) {
                    status.textContent = '⚠ Save failed';
                    console.error(err);
                }
            }
            document.getElementById('rte-save').addEventListener('click', save);
            document.addEventListener('keydown', e => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); save(); }
            });
        })();
    </script>
</body>
</html>
