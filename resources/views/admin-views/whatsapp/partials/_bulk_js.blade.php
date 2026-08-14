    <script>
        (function () {
            var TEMPLATES = @json($templates);
            var BATCH = {{ \App\Http\Controllers\Admin\WhatsAppBulkController::BATCH_LIMIT }};
            var RECIPIENTS_URL = '{{ route('admin.business-settings.third-party.whatsapp-bulk.recipients') }}';
            var SEND_URL = '{{ route('admin.business-settings.third-party.whatsapp-bulk.send') }}';
            var MEDIA_URL = '{{ route('admin.business-settings.third-party.whatsapp-bulk.header-media') }}';
            var HISTORY_URL = '{{ route('admin.business-settings.third-party.whatsapp-bulk', ['tab' => 'history']) }}';
            var CSRF = '{{ csrf_token() }}';

            // Built by concatenation so Blade never sees a literal double-brace in this script.
            var OPEN = '{' + '{', CLOSE = '}' + '}';

            var audience = 'vendors';
            var picked = { vendors: new Set(), customers: new Set() };
            var loaded = [];
            var filterTotal = 0;
            var searchTimer = null;
            var mediaUrl = '';

            var $tpl = document.getElementById('wb-template');
            var $vars = document.getElementById('wb-vars');
            var $preview = document.getElementById('wb-preview');
            var $previewBody = document.getElementById('wb-preview-body');
            var $list = document.getElementById('wb-list');
            var $zone = document.getElementById('wb-zone');
            var $status = document.getElementById('wb-status');
            var $statusWrap = document.getElementById('wb-status-wrap');
            var $search = document.getElementById('wb-search');
            var $total = document.getElementById('wb-total');
            var $all = document.getElementById('wb-all');
            var $allBox = document.getElementById('wb-all-box');
            var $allLimit = document.getElementById('wb-all-limit');
            var $allLabel = document.getElementById('wb-all-label');
            var $skip = document.getElementById('wb-skip');
            var $numbers = document.getElementById('wb-numbers');
            var $numbersCount = document.getElementById('wb-numbers-count');
            var $count = document.getElementById('wb-selected-count');
            var $summary = document.getElementById('wb-summary');
            var $send = document.getElementById('wb-send');
            var $progress = document.getElementById('wb-progress');
            var $bar = document.getElementById('wb-progress-bar');
            var $ptext = document.getElementById('wb-progress-text');
            var $results = document.getElementById('wb-results');

            var $media = document.getElementById('wb-media');
            var $mediaFile = document.getElementById('wb-media-file');
            var $mediaLabel = document.getElementById('wb-media-label');
            var $mediaStatus = document.getElementById('wb-media-status');
            var $mediaPreview = document.getElementById('wb-media-preview');

            var MEDIA_ACCEPT = {
                IMAGE: 'image/jpeg,image/png',
                VIDEO: 'video/mp4',
                DOCUMENT: 'application/pdf'
            };

            var LABEL = { vendors: 'vendors', customers: 'customers', manual: 'numbers' };

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function currentTemplate() {
                return $tpl.value === '' ? null : TEMPLATES[parseInt($tpl.value, 10)];
            }

            function selectedSet() {
                return picked[audience] || new Set();
            }

            // Sent with both the picker request and every send batch, so the audience the server
            // builds is the one the admin was looking at when they pressed the button.
            function filters() {
                return {
                    zone_id: $zone.value || '',
                    status: $status.value,
                    search: $search.value.trim(),
                    skip_days: $skip.checked ? 30 : 0
                };
            }

            // ---- template variables ---------------------------------------------------------
            // One entry per body slot, in send order: {key, value}. Auto slots carry no value —
            // the server fills them from the recipient row.
            function paramValues() {
                return Array.prototype.map.call($vars.querySelectorAll('.wb-var'), function (i) {
                    var auto = i.dataset.auto === '1';
                    return { key: i.dataset.key, auto: auto, value: auto ? '' : i.value };
                });
            }

            function templateVars(t) {
                if (t && t.vars && t.vars.length) return t.vars;
                var out = [], n = (t && t.var_count) || 0;
                for (var i = 1; i <= n; i++) out.push({ key: String(i), label: 'Variable ' + i, auto: false });
                return out;
            }

            /**
             * Templates greet with a variable ("Hi" followed by the first token) and label the
             * contact slot ("Phone: " followed by a token) — prefill those with {name} / {phone}
             * so nobody has to know the tokens exist. Anchored to the start of a line and to a
             * label right before the token, so a variable buried mid-sentence is left alone.
             */
            function defaultVarValue(body, key) {
                var at = String(body || '').indexOf(OPEN + key + CLOSE);
                if (at < 0) return '';
                var before = body.slice(0, at);

                if (/(^|\n)\s*(hi+|hey+|hello|dear|namaste|greetings)\b[\s,!:.-]*$/i.test(before)) {
                    return '{name}';
                }
                if (/(phone|mobile|contact|whats\s*app|cell)\s*(number|no\.?|#)?\s*[:\-–]?\s*$/i.test(before)) {
                    return '{phone}';
                }
                return '';
            }

            function renderVars() {
                var t = currentTemplate();
                $vars.innerHTML = '';
                var vars = templateVars(t);
                if (!t || !vars.length) { syncSend(); return; }

                var help = document.createElement('small');
                help.className = 'text-muted d-block mb-2';
                help.innerHTML = 'Fill each variable. <code>{name}</code> and <code>{phone}</code> are replaced ' +
                    'with each recipient’s own name and number.';
                $vars.appendChild(help);

                vars.forEach(function (v) {
                    var wrap = document.createElement('div');
                    wrap.className = 'form-group mb-2';

                    if (v.auto) {
                        // Filled from the recipient row on the server — an editable box would only
                        // invite a value that gets thrown away.
                        wrap.innerHTML = '<label style="font-size:12px;" class="mb-1">' + esc(v.label) +
                            ' <code>' + OPEN + esc(v.key) + CLOSE + '</code></label>' +
                            '<input type="text" class="form-control form-control-sm wb-var" readonly ' +
                            'data-key="' + esc(v.key) + '" data-auto="1" ' +
                            'value="Filled in automatically for each recipient">';
                    } else {
                        wrap.innerHTML = '<label style="font-size:12px;" class="mb-1">Variable ' +
                            OPEN + esc(v.key) + CLOSE + '</label>' +
                            '<input type="text" class="form-control form-control-sm wb-var" ' +
                            'data-key="' + esc(v.key) + '" data-auto="0" ' +
                            'value="' + esc(defaultVarValue(t.body, v.key)) + '" ' +
                            'placeholder="Value for ' + OPEN + esc(v.key) + CLOSE + '">';
                    }
                    $vars.appendChild(wrap);
                });

                Array.prototype.forEach.call($vars.querySelectorAll('.wb-var'), function (input) {
                    input.addEventListener('input', syncSend);
                });
                syncSend();
            }

            // A real recipient out of the current selection, so the preview reads the way the
            // first person will actually receive it rather than showing placeholders.
            function sampleRow() {
                var set = selectedSet();
                for (var i = 0; i < loaded.length; i++) {
                    if (set.has(loaded[i].id)) return loaded[i];
                }
                return loaded[0] || null;
            }

            function renderPreview() {
                var t = currentTemplate();
                if (!t) { $preview.style.display = 'none'; return; }

                var body = t.body;
                paramValues().forEach(function (p) {
                    var slot = OPEN + p.key + CLOSE;
                    var shown = p.key === 'customer_name' ? '{name}'
                        : p.key === 'customer_phone' ? '{phone}'
                        : (p.value || slot);
                    body = body.split(slot).join(shown);
                });

                var c = audience === 'manual' ? null : sampleRow();
                $previewBody.innerHTML = esc(body)
                    .replace(/\{name\}/g, '<b>' + esc((c && c.name) || 'each recipient’s name') + '</b>')
                    .replace(/\{(customer_)?phone\}/g, '<b>' + esc((c && c.phone) || 'each recipient’s number') + '</b>');
                $preview.style.display = 'block';
            }

            // ---- audience -------------------------------------------------------------------
            function parseNumbers() {
                var raw = ($numbers.value || '').split(/[\s,;]+/);
                var seen = {}, out = [];
                raw.forEach(function (n) {
                    var digits = n.replace(/[^0-9]/g, '');
                    if (digits.length < 10) return;
                    var key = digits.slice(-10);
                    if (seen[key]) return;
                    seen[key] = 1;
                    out.push(n.trim());
                });
                return out;
            }

            function renderRows() {
                if (!loaded.length) {
                    $list.innerHTML = '<div class="text-muted text-center p-3" style="font-size:13px;">Nobody matches these filters.</div>';
                    return;
                }
                var set = selectedSet();
                $list.innerHTML = loaded.map(function (r) {
                    return '<label class="wbulk-row">' +
                        '<input type="checkbox" class="wb-row" value="' + r.id + '"' + (set.has(r.id) ? ' checked' : '') + '>' +
                        '<span><b>' + esc(r.name || 'Unnamed') + '</b> ' +
                        '<span class="text-muted">' + esc(r.phone) + '</span></span></label>';
                }).join('');

                Array.prototype.forEach.call($list.querySelectorAll('.wb-row'), function (box) {
                    box.addEventListener('change', function () {
                        var id = parseInt(this.value, 10);
                        this.checked ? selectedSet().add(id) : selectedSet().delete(id);
                        syncSend();
                    });
                });
            }

            function loadRows() {
                if (audience === 'manual') return;

                $list.innerHTML = '<div class="text-muted text-center p-3" style="font-size:13px;">Loading…</div>';
                var f = filters();
                var qs = 'audience=' + audience +
                    '&search=' + encodeURIComponent(f.search) +
                    '&zone_id=' + encodeURIComponent(f.zone_id) +
                    '&status=' + encodeURIComponent(f.status) +
                    '&skip_days=' + f.skip_days;

                fetch(RECIPIENTS_URL + '?' + qs, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    loaded = d.rows || [];
                    filterTotal = d.total || 0;
                    $total.textContent = filterTotal
                        ? filterTotal.toLocaleString() + ' match' + (filterTotal === 1 ? 'es' : ' in total') +
                          (d.truncated ? ' — showing the first ' + loaded.length : '')
                        : '';
                    $allLimit.max = filterTotal;
                    if (!$allLimit.value || parseInt($allLimit.value, 10) > filterTotal || $allLimit.value === '0') {
                        $allLimit.value = filterTotal;
                    }
                    $allLabel.textContent = filterTotal
                        ? 'Send to all ' + filterTotal.toLocaleString() + ' matching these filters'
                        : 'Send to everyone matching these filters';
                    renderRows();
                    syncSend();
                })
                .catch(function () {
                    $list.innerHTML = '<div class="text-danger text-center p-3" style="font-size:13px;">Could not load that audience.</div>';
                });
            }

            function sendAll() {
                return audience !== 'manual' && $all.checked;
            }

            function allCount() {
                var n = parseInt($allLimit.value, 10);
                if (isNaN(n) || n < 1) return 0;
                return Math.min(n, filterTotal);
            }

            function recipientCount() {
                if (audience === 'manual') return parseNumbers().length;
                if (sendAll()) return allCount();
                return selectedSet().size;
            }

            function setAudience(next) {
                audience = next;
                Array.prototype.forEach.call(document.querySelectorAll('.wb-aud'), function (el) {
                    el.classList.toggle('active', el.dataset.aud === next);
                });
                document.getElementById('wb-pane-list').style.display = next === 'manual' ? 'none' : 'block';
                document.getElementById('wb-pane-manual').style.display = next === 'manual' ? 'block' : 'none';
                // "Active stores only" is a store column — it means nothing for customers.
                $statusWrap.style.display = next === 'vendors' ? '' : 'none';

                if (next !== 'manual') loadRows();
                syncSend();
            }

            function renderSummary() {
                var n = recipientCount();
                if (!n) { $summary.style.display = 'none'; return; }

                var how = audience === 'manual' ? 'pasted number' + (n === 1 ? '' : 's')
                    : (sendAll() ? 'of the ' + filterTotal.toLocaleString() + ' matching ' + LABEL[audience]
                        : 'selected ' + LABEL[audience]);

                $summary.innerHTML = 'This send goes to <b>' + n.toLocaleString() + '</b> ' + how +
                    ', in batches of ' + BATCH + '.' +
                    ($skip.checked && audience !== 'manual'
                        ? '<div class="text-muted mt-1">Anyone the platform messaged in the last 30 days is skipped.</div>'
                        : '');
                $summary.style.display = 'block';
            }

            function syncSend() {
                renderPreview();
                var t = currentTemplate();
                var filled = !t || paramValues().every(function (p) { return p.auto || p.value.trim() !== ''; });
                var n = recipientCount();

                // A media template cannot go out without its file — the whole batch would come
                // back as error 132012, so the button stays down until the upload finishes.
                var mediaReady = !t || !t.needs_media || !!mediaUrl;

                $send.disabled = !t || !filled || !mediaReady || n === 0;
                $count.textContent = n ? n.toLocaleString() + ' selected' : '0 selected';
                $send.textContent = n ? 'Send to ' + n.toLocaleString() + ' recipient' + (n === 1 ? '' : 's') : 'Send';

                $numbersCount.textContent = audience === 'manual' && n
                    ? n + ' valid number' + (n === 1 ? '' : 's') + ' — duplicates and anything under 10 digits are dropped.'
                    : '';

                renderSummary();
            }

            // ---- sending --------------------------------------------------------------------
            function buildBatches() {
                var f = filters();
                var batches = [];
                var i;

                if (audience === 'manual') {
                    var nums = parseNumbers();
                    for (i = 0; i < nums.length; i += BATCH) {
                        batches.push({ audience: 'manual', mode: 'selected', numbers: nums.slice(i, i + BATCH) });
                    }
                    return batches;
                }

                if (sendAll()) {
                    // No offset. The server excludes everyone already claimed in this run, so each
                    // batch returns the NEXT unmessaged people — an offset walk would restart at
                    // the same rows every time.
                    var n = allCount();
                    for (i = 0; i < n; i += BATCH) {
                        batches.push(Object.assign({
                            audience: audience, mode: 'all', limit: Math.min(BATCH, n - i)
                        }, f));
                    }
                    return batches;
                }

                var ids = Array.from(selectedSet());
                for (i = 0; i < ids.length; i += BATCH) {
                    batches.push(Object.assign({
                        audience: audience, mode: 'selected', ids: ids.slice(i, i + BATCH)
                    }, f));
                }
                return batches;
            }

            function batchSize(b) {
                return b.mode === 'all' ? b.limit : (b.numbers || b.ids).length;
            }

            function sendBatches() {
                var t = currentTemplate();
                var total = recipientCount();

                // One id for the whole send. The server claims each recipient against it before
                // dispatching, so a retried batch — or a whole run started again after a break —
                // skips anyone already messaged instead of messaging them twice.
                var runId = (window.crypto && crypto.randomUUID)
                    ? crypto.randomUUID()
                    : 'r' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);

                var batches = buildBatches();
                var done = 0, sent = 0, skipped = 0, blocked = 0, failures = [];

                $send.disabled = true;
                $progress.style.display = 'block';
                $results.style.display = 'none';

                function step(index, attempt) {
                    attempt = attempt || 0;
                    if (index >= batches.length) {
                        $ptext.textContent = 'Finished — ' + sent + ' sent, ' + failures.length + ' failed'
                            + (skipped ? ', ' + skipped + ' already messaged' : '')
                            + (blocked ? ', ' + blocked + ' opted out' : '') + '.';
                        showResults(sent, skipped, blocked, failures);
                        $send.disabled = false;
                        return;
                    }

                    fetch(SEND_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(Object.assign({
                            template: t.name,
                            language: t.language,
                            params: paramValues(),
                            header_media: mediaUrl,
                            run_id: runId
                        }, batches[index]))
                    })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        if (!res.ok) {
                            failures.push({ name: '—', phone: '—', error: res.d.message || 'Request rejected.' });
                        } else {
                            sent += res.d.sent || 0;
                            skipped += res.d.skipped || 0;
                            blocked += res.d.blocked || 0;
                            (res.d.results || []).forEach(function (r) { if (!r.success) failures.push(r); });
                        }
                        done += batchSize(batches[index]);
                        $bar.style.width = Math.round((done / total) * 100) + '%';
                        $ptext.textContent = done.toLocaleString() + ' of ' + total.toLocaleString() + ' processed…';
                        step(index + 1);
                    })
                    .catch(function () {
                        // Retrying a lost request is safe: the server claims each recipient before
                        // dispatching, so anyone the first attempt already messaged comes back as
                        // skipped rather than as a duplicate.
                        if (attempt < 1) {
                            step(index, attempt + 1);
                            return;
                        }
                        failures.push({
                            name: '—', phone: '—',
                            error: 'Network error on a batch of ' + batchSize(batches[index]) +
                                '. Nobody in it was messaged twice — send again to cover them.'
                        });
                        done += batchSize(batches[index]);
                        step(index + 1);
                    });
                }

                step(0, 0);
            }

            function showResults(sent, skipped, blocked, failures) {
                var html = '<div class="alert ' + (failures.length ? 'alert-warning' : 'alert-success') + '" style="font-size:13px;">' +
                    '<b>' + sent.toLocaleString() + '</b> message' + (sent === 1 ? '' : 's') + ' sent' +
                    (failures.length ? ', <b>' + failures.length + '</b> failed' : '') +
                    (skipped ? ', <b>' + skipped + '</b> skipped (already messaged in this run)' : '') +
                    (blocked ? ', <b>' + blocked + '</b> skipped (opted out)' : '') + '.</div>';

                if (failures.length) {
                    html += '<div class="border rounded" style="max-height:220px;overflow-y:auto;">' +
                        failures.map(function (f) {
                            return '<div class="px-3 py-2 border-bottom" style="font-size:12px;">' +
                                '<b>' + esc(f.name) + '</b> <span class="text-muted">' + esc(f.phone) + '</span><br>' +
                                '<span class="text-danger">' + esc(f.error) + '</span></div>';
                        }).join('') + '</div>';
                }

                html += '<div class="mt-2"><a href="' + HISTORY_URL + '" class="btn btn-sm btn-outline-secondary">' +
                    '<i class="tio-history"></i> See every number this went to</a></div>';

                $results.innerHTML = html;
                $results.style.display = 'block';
            }

            // ---- media header ---------------------------------------------------------------
            function syncMedia() {
                var t = currentTemplate();
                mediaUrl = '';
                $mediaFile.value = '';
                $mediaStatus.textContent = '';
                $mediaPreview.style.display = 'none';

                if (!t || !t.needs_media) {
                    $media.style.display = 'none';
                    return;
                }

                var kind = (t.header || 'IMAGE').toLowerCase();
                $mediaLabel.textContent = kind.charAt(0).toUpperCase() + kind.slice(1);
                $mediaFile.setAttribute('accept', MEDIA_ACCEPT[t.header] || MEDIA_ACCEPT.IMAGE);
                $media.style.display = '';
            }

            $mediaFile.addEventListener('change', function () {
                var file = $mediaFile.files && $mediaFile.files[0];
                mediaUrl = '';
                $mediaPreview.style.display = 'none';
                if (!file) { $mediaStatus.textContent = ''; syncSend(); return; }

                $mediaStatus.className = 'mt-2 text-muted';
                $mediaStatus.textContent = 'Uploading…';
                syncSend();

                var body = new FormData();
                body.append('file', file);
                body.append('_token', CSRF);

                fetch(MEDIA_URL, { method: 'POST', headers: { 'Accept': 'application/json' }, body: body })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (!res.success) { throw new Error(res.message || 'Upload failed.'); }
                        mediaUrl = res.url;
                        $mediaStatus.className = 'mt-2 text-success';
                        $mediaStatus.textContent = 'Attached — ' + res.name;
                        if (/\.(jpe?g|png)$/i.test(res.url)) {
                            $mediaPreview.src = res.url;
                            $mediaPreview.style.display = '';
                        }
                    })
                    .catch(function (e) {
                        $mediaStatus.className = 'mt-2 text-danger';
                        $mediaStatus.textContent = e.message || 'Could not upload that file.';
                    })
                    .then(syncSend);
            });

            // ---- wiring ---------------------------------------------------------------------
            $tpl.addEventListener('change', function () {
                syncMedia();
                renderVars();
            });

            Array.prototype.forEach.call(document.querySelectorAll('.wb-aud'), function (el) {
                el.addEventListener('click', function () { setAudience(this.dataset.aud); });
            });

            $search.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(loadRows, 300);
            });
            $zone.addEventListener('change', loadRows);
            $status.addEventListener('change', loadRows);
            $skip.addEventListener('change', function () {
                // The exclusion changes who is in the audience, so the counts behind it have to
                // be re-asked rather than merely re-labelled.
                loadRows();
                syncSend();
            });

            $all.addEventListener('change', function () {
                $allBox.style.display = this.checked ? 'flex' : 'none';
                syncSend();
            });
            $allLimit.addEventListener('input', syncSend);
            $numbers.addEventListener('input', syncSend);

            document.getElementById('wb-select-page').addEventListener('click', function () {
                loaded.forEach(function (r) { selectedSet().add(r.id); });
                renderRows();
                syncSend();
            });
            document.getElementById('wb-clear').addEventListener('click', function () {
                selectedSet().clear();
                renderRows();
                syncSend();
            });

            $send.addEventListener('click', function () {
                var n = recipientCount();
                var who = audience === 'manual' ? 'pasted number' + (n === 1 ? '' : 's') : LABEL[audience];
                if (!confirm('Send this template to ' + n.toLocaleString() + ' ' + who + ' from the MyChitti number?')) return;
                sendBatches();
            });

            setAudience('vendors');
        })();
    </script>
